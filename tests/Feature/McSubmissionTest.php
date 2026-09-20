<?php

namespace Tests\Feature;

use App\Http\Controllers\LMS\CourseEnrollmentController;
use App\Models\CourseType\CourseMcSubmission;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Tests\TestCase;

class McSubmissionTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        config(['database.default' => 'mc_test', 'database.connections.mc_test' => [
            'driver' => 'sqlite', 'database' => ':memory:', 'prefix' => '',
        ]]);
        DB::purge('mc_test');
        Schema::create('course_week_item', function (Blueprint $table) {
            $table->increments('item_id');
            $table->integer('course_id');
            $table->string('course_item_type');
        });
        Schema::create('course_enrollment', function (Blueprint $table) {
            $table->increments('enrollment_id');
            $table->integer('course_id');
            $table->integer('user_id');
            $table->boolean('status_join');
        });
        Schema::create('course_item_questions', function (Blueprint $table) {
            $table->increments('question_id');
            $table->integer('item_id');
            $table->text('question_text')->nullable();
            $table->string('question_image')->nullable();
        });
        Schema::create('course_item_options', function (Blueprint $table) {
            $table->increments('option_id');
            $table->integer('question_id');
            $table->text('option_text')->nullable();
            $table->string('option_image')->nullable();
            $table->boolean('is_correct');
        });
        Schema::create('course_mc_submissions', function (Blueprint $table) {
            $table->increments('mc_submission_id');
            $table->integer('item_id');
            $table->integer('user_id');
            $table->json('questions');
            $table->json('answer_details')->nullable();
            $table->integer('grade');
            $table->integer('attempt_no');
            $table->boolean('is_remedial')->default(false);
            $table->timestamp('submitted_at');
            $table->timestamps();
        });
        DB::table('course_week_item')->insert(['item_id' => 1, 'course_id' => 1, 'course_item_type' => '4']);
        DB::table('course_enrollment')->insert(['course_id' => 1, 'user_id' => 1, 'status_join' => 1]);
        foreach ([1, 2, 3] as $id) {
            DB::table('course_item_questions')->insert([
                'question_id' => $id, 'item_id' => 1, 'question_text' => "Question $id",
            ]);
            DB::table('course_item_options')->insert([
                ['option_id' => $id * 10, 'question_id' => $id, 'option_text' => 'Correct', 'is_correct' => 1],
                ['option_id' => $id * 10 + 1, 'question_id' => $id, 'option_text' => 'Wrong', 'is_correct' => 0],
            ]);
        }
        Auth::shouldReceive('id')->andReturn(1);
    }

    private function submit(array $details)
    {
        return app(CourseEnrollmentController::class)->mcSubmission(Request::create('/', 'POST', [
            'answer_details' => $details, 'grade' => 100, 'questions' => [999],
        ]), 1)->getData(true);
    }

    public function test_server_grades_and_preserves_correct_wrong_and_unanswered_details(): void
    {
        $response = $this->submit([
            ['question_id' => 1, 'selected_option_id' => 10, 'is_correct' => false],
            ['question_id' => 2, 'selected_option_id' => 21, 'is_correct' => true],
            ['question_id' => 3, 'selected_option_id' => null],
        ]);
        $saved = CourseMcSubmission::sole();
        $this->assertEquals(33, $saved->grade);
        $this->assertEquals(33, $response['data']['grade']);
        $this->assertSame([1, 2], $saved->questions);
        $this->assertSame([true, false, false], array_column($saved->answer_details, 'is_correct'));
        $this->assertNull($saved->answer_details[2]['selected_option_id']);
        $this->assertSame(20, $saved->answer_details[1]['correct_option_id']);
        DB::table('course_item_questions')->where('question_id', 1)->update(['question_text' => 'Edited']);
        $this->assertSame('Question 1', $saved->fresh()->answer_details[0]['question_text']);
        $this->assertSame('Correct', $saved->answer_details[0]['options'][0]['option_text']);
    }

    public function test_omitted_answers_are_saved_as_unanswered_and_reduce_grade(): void
    {
        $this->submit([['question_id' => 1, 'selected_option_id' => 10]]);
        $saved = CourseMcSubmission::sole();
        $this->assertEquals(33, $saved->grade);
        $this->assertCount(3, $saved->answer_details);
        $this->assertNull($saved->answer_details[1]['selected_option_id']);
    }

    public function test_all_unanswered_questions_produce_zero_grade(): void
    {
        $this->submit([]);
        $this->assertEquals(0, CourseMcSubmission::sole()->grade);
        $this->assertCount(3, CourseMcSubmission::sole()->answer_details);
    }

    public function test_invalid_question_and_option_are_rejected_without_saving(): void
    {
        foreach ([[999, 10], [1, 20], [1, 999]] as [$questionId, $optionId]) {
            try {
                $this->submit([['question_id' => $questionId, 'selected_option_id' => $optionId]]);
                $this->fail('Expected invalid answer to be rejected.');
            } catch (HttpException $error) {
                $this->assertSame(422, $error->getStatusCode());
            }
        }
        $this->assertSame(0, CourseMcSubmission::count());
    }

    public function test_duplicate_question_ids_are_rejected(): void
    {
        $this->expectException(ValidationException::class);
        $this->submit([
            ['question_id' => 1, 'selected_option_id' => 10],
            ['question_id' => 1, 'selected_option_id' => 11],
        ]);
    }

    public function test_unenrolled_user_cannot_submit(): void
    {
        DB::table('course_enrollment')->update(['status_join' => 0]);
        try {
            $this->submit([]);
            $this->fail('Expected access to be denied.');
        } catch (HttpException $error) {
            $this->assertSame(403, $error->getStatusCode());
        }
        $this->assertSame(0, CourseMcSubmission::count());
    }

    public function test_duplicate_submission_returns_saved_result_and_later_attempt_is_separate(): void
    {
        $first = $this->submit([['question_id' => 1, 'selected_option_id' => 10]]);
        $duplicate = $this->submit([]);
        $this->assertSame($first['data']['mc_submission_id'], $duplicate['data']['mc_submission_id']);
        $this->assertEquals(33, $duplicate['data']['grade']);
        $this->assertSame(1, CourseMcSubmission::count());
        $this->assertSame($first['history'], $duplicate['history']);
        $this->travel(6)->seconds();
        $second = $this->submit([]);
        $this->assertSame(2, $second['attempt']);
        $this->assertEquals(0, $second['data']['grade']);
        $this->assertEquals(33, CourseMcSubmission::find($first['data']['mc_submission_id'])->grade);
        $this->travelBack();
    }

    public function test_history_and_review_can_be_reopened_without_changing_attempts(): void
    {
        DB::table('course_item_questions')->where('question_id', 1)->update(['question_image' => 'images/question.png']);
        DB::table('course_item_options')->where('option_id', 10)->update(['option_image' => 'images/option.png']);
        $first = $this->submit([
            ['question_id' => 1, 'selected_option_id' => 10],
            ['question_id' => 2, 'selected_option_id' => 21],
        ]);
        $this->travel(6)->seconds();
        $second = $this->submit([]);
        $this->travelBack();

        // Exercise the real routes with the authenticated ID supplied by the test facade.
        $this->withoutMiddleware();
        $history = $this->getJson('/course/1/mc-submission/check')->assertOk()
            ->assertJsonPath('attempts.0.has_answer_details', true)
            ->assertJsonPath('attempts.1.mc_submission_id', $second['data']['mc_submission_id']);
        $this->assertSame($second['history'], $history->json('attempts'));
        DB::table('course_item_questions')->where('question_id', 1)->update(['question_text' => 'Edited later']);

        $url = '/course/1/mc-submission/'.$first['data']['mc_submission_id'];
        $before = CourseMcSubmission::orderBy('attempt_no')->get()->toArray();
        $review = $this->getJson($url)->assertOk()
            ->assertJsonPath('grade', 33)
            ->assertJsonPath('answer_details.0.question_text', 'Question 1')
            ->assertJsonPath('answer_details.0.question_image', 'images/question.png')
            ->assertJsonPath('answer_details.0.options.0.option_image', 'images/option.png')
            ->assertJsonPath('answer_details.0.is_correct', true)
            ->assertJsonPath('answer_details.1.is_correct', false)
            ->assertJsonPath('answer_details.2.selected_option_id', null);
        $this->getJson($url)->assertExactJson($review->json());
        $this->assertSame($before, CourseMcSubmission::orderBy('attempt_no')->get()->toArray());
    }

    public function test_legacy_submissions_report_unavailable_details(): void
    {
        $this->submit([]);
        CourseMcSubmission::sole()->update(['answer_details' => null]);
        $this->withoutMiddleware();
        $this->getJson('/course/1/mc-submission/check')->assertOk()
            ->assertJsonPath('attempts.0.has_answer_details', false);
        $this->getJson('/course/1/mc-submission/1')->assertOk()
            ->assertJsonPath('has_answer_details', false)
            ->assertJsonPath('answer_details', [])
            ->assertJsonPath('message', 'Detail jawaban tidak tersedia untuk percobaan ini');
    }

    public function test_review_rejects_other_users_other_quizzes_and_missing_attempts(): void
    {
        $this->submit([]);
        $this->withoutMiddleware();
        $this->getJson('/course/2/mc-submission/1')->assertNotFound();
        $this->getJson('/course/1/mc-submission/999')->assertNotFound();
        CourseMcSubmission::sole()->update(['user_id' => 2]);
        $this->getJson('/course/1/mc-submission/1')->assertNotFound();
        $this->getJson('/course/1/mc-submission/check')->assertExactJson(['exists' => false]);
    }
}
