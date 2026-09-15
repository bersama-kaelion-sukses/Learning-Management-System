<?php

namespace Tests\Feature;

use App\Http\Controllers\LMS\CourseEnrollmentController;
use App\Models\CourseWeekItem;
use App\Models\CourseType\CourseEssay;
use App\Models\CourseType\CourseEssaySubmission;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Tests\TestCase;

class EssaySubmissionTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        config(['database.default' => 'essay_test', 'database.connections.essay_test' => [
            'driver' => 'sqlite', 'database' => ':memory:', 'prefix' => '',
        ]]);
        DB::purge('essay_test');
        Schema::create('course_week_item', function (Blueprint $table) {
            $table->increments('item_id');
            $table->unsignedInteger('course_id')->default(1);
            $table->unsignedInteger('passing_grade')->nullable();
            $table->string('course_item_type');
            $table->string('course_item_name');
            $table->text('course_describe')->nullable();
            $table->timestamps();
        });
        Schema::create('course_item_essays', function (Blueprint $table) {
            $table->increments('essay_id');
            $table->unsignedInteger('item_id');
            $table->string('essay_title');
            $table->text('instruction');
            $table->boolean('is_essay_submitted')->default(false);
            $table->timestamps();
        });
        Schema::create('course_essay_submissions', function (Blueprint $table) {
            $table->increments('essay_submission_id');
            $table->unsignedInteger('essay_id');
            $table->unsignedInteger('item_id');
            $table->unsignedInteger('user_id');
            $table->text('answer_text')->nullable();
            $table->boolean('is_graded');
            $table->boolean('is_remedial')->nullable();
            $table->unsignedInteger('grade')->nullable();
            $table->timestamp('submitted_at');
            $table->timestamps();
        });
        Schema::create('course_progress', function (Blueprint $table) {
            $table->increments('progress_id');
            $table->unsignedInteger('course_id');
            $table->unsignedInteger('course_item_id');
            $table->unsignedInteger('user_id');
        });
        Auth::shouldReceive('id')->andReturn(1);
    }

    private function item(string $type = '3'): CourseWeekItem
    {
        return CourseWeekItem::create([
            'course_item_type' => $type,
            'course_item_name' => 'Explain the process',
            'course_describe' => 'Describe each step in your own words.',
        ]);
    }

    private function submit(CourseWeekItem $item, ?int $essayId = null, string $answer = 'First answer')
    {
        $request = Request::create('/', 'POST', [
            'item_id' => $item->item_id, 'essay_id' => $essayId, 'answer_text' => $answer,
        ]);
        return app(CourseEnrollmentController::class)->essaySubmission($request, $item->item_id);
    }

    public function test_missing_essay_is_created_and_repeat_submission_preserves_first_answer(): void
    {
        $item = $this->item();
        $this->assertTrue($this->submit($item)->getData()->success);
        $essay = CourseEssay::sole();
        $this->assertSame($item->course_item_name, $essay->essay_title);
        $this->assertSame($item->course_describe, $essay->instruction);
        $this->submit($item, null, 'Replacement answer');
        $this->assertSame(1, CourseEssay::count());
        $this->assertSame('First answer', CourseEssaySubmission::sole()->answer_text);
        $this->assertEquals($essay->essay_id, CourseEssaySubmission::sole()->essay_id);
    }

    public function test_existing_essay_is_reused_without_overwriting_its_content(): void
    {
        $item = $this->item();
        $essay = $item->essay()->create(['essay_title' => 'Existing title', 'instruction' => 'Existing instructions']);
        $this->submit($item);
        $this->assertSame(1, CourseEssay::count());
        $this->assertSame('Existing instructions', $essay->fresh()->instruction);
        $this->assertEquals($essay->essay_id, CourseEssaySubmission::sole()->essay_id);
    }

    public function test_an_unrelated_essay_id_is_rejected(): void
    {
        $item = $this->item();
        try {
            $this->submit($item, 999);
            $this->fail('Expected an invalid essay ID to be rejected.');
        } catch (HttpException $error) {
            $this->assertSame(422, $error->getStatusCode());
        }
        $this->assertSame(0, CourseEssay::count());
        $this->assertSame(0, CourseEssaySubmission::count());
    }

    public function test_non_essay_item_is_rejected(): void
    {
        $this->expectException(HttpException::class);
        $this->submit($this->item('4'));
    }

    public function test_ungraded_essay_counts_as_submitted_but_does_not_pass_course_grading(): void
    {
        $item = $this->item();
        $item->update(['passing_grade' => 70]);
        $before = app(CourseEnrollmentController::class)->checkSubmission($item->item_id)->getData();
        $this->assertFalse($before->exists);
        $this->submit($item);
        $status = app(CourseEnrollmentController::class)->checkSubmission($item->item_id)->getData();
        $this->assertTrue($status->exists);
        $this->assertEquals(0, $status->is_remedial);
        $this->assertNull($status->grade);
        $this->assertFalse($status->all_completed);

        CourseEssaySubmission::sole()->update(['grade' => 80, 'is_graded' => true]);
        $graded = app(CourseEnrollmentController::class)->checkSubmission($item->item_id)->getData();
        $this->assertTrue($graded->exists);
        $this->assertTrue($graded->all_completed);
    }

    public function test_existing_ungraded_record_with_null_remedial_status_is_recognized(): void
    {
        $item = $this->item();
        $this->submit($item);
        CourseEssaySubmission::sole()->update(['is_remedial' => null]);
        $status = app(CourseEnrollmentController::class)->checkSubmission($item->item_id)->getData();
        $this->assertTrue($status->exists);
        $this->assertEquals(0, $status->is_remedial);
    }
}
