<?php

namespace App\Http\Controllers\LMS;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Course;
use App\Models\CourseWeekItem;
use App\Models\CourseEnrollment;
use App\Models\CourseType\CourseEssaySubmission;
use App\Models\CourseType\CourseEssay;
use App\Models\CourseType\CourseMcSubmission;
use App\Models\CourseType\CourseTypeQuestion;
use App\Models\CourseType\CourseForumDiscussion;
use App\Models\CourseType\CourseForumDiscussionReply;
use App\Models\CourseType\CourseAttachmentSubmission;
use App\Models\Thread\ForumReply;
use App\Models\Thread\ForumThread;
use App\Models\CourseLearnerActivity;
use App\Models\Division;
use App\Models\User;
use Illuminate\Support\Facades\Log;

class LearnerSubmissionController extends Controller
{
    public function index($course_id)
    {
        $course = Course::with([
            'weeks.items',
            'enrollments.user' => function ($q) {
                $q->select('user_id', 'full_name', 'emp_id', 'departement_cat', 'role_id', 'sub_role');
            }
        ])
        ->where('is_deleted', 0)
        ->find($course_id);

        if (!$course) {
            abort(404, 'Course tidak ditemukan');
        }

        $divisions = Division::pluck('division_name', 'division_id')->toArray();

        $activities = CourseLearnerActivity::where('course_id', $course->course_id)
            ->get()
            ->keyBy('user_id');

        // 🔥 FILTER PASTI (hapus semua user role_id = 1 dan sub_role berisi 1)
        $course->enrollments = $course->enrollments->filter(function ($enroll) {
            $user = $enroll->user;
            if (!$user) return false; // tidak ada user = skip
            if ((int)$user->role_id === 1) return false; // role_id 1 = skip

            // cek sub_role
            $sub = $user->sub_role;
            if (is_string($sub)) {
                $decoded = json_decode($sub, true);
                $sub = $decoded ?: explode(',', $sub);
            }
            if (is_array($sub) && in_array(1, array_map('intval', $sub))) {
                return false;
            }

            return true;
        })
        ->values()
        ->transform(function ($enroll) use ($divisions, $activities) {
            $user = $enroll->user;
            if ($user) {
                $user->division_label = $divisions[$user->departement_cat] ?? 'Tidak Diketahui';
            }

            $activity = $activities[$enroll->user_id] ?? null;
            $enroll->last_access = $activity->last_access ?? null;
            $enroll->is_opened   = $activity->is_opened ?? 0;
            return $enroll;
        });

        // ✅ daftar user yang sudah lolos filter
        $filteredUserIds = $course->enrollments->pluck('user.user_id')->filter()->unique();

        // 👥 Hitung ulang peserta aktif & tergabung
        $activeCount = CourseLearnerActivity::where('course_id', $course->course_id)
            ->where('is_opened', 1)
            ->whereIn('user_id', $filteredUserIds)
            ->count();

        $joinedCount = CourseEnrollment::where('course_id', $course->course_id)
            ->where('status_join', 1)
            ->whereIn('user_id', $filteredUserIds)
            ->count();

        $learnerCount = $course->enrollments->count() ?? 0;

        $course->active_count = $activeCount;
        $course->joined_count = $joinedCount;
        $course->filtered_learner_count = $learnerCount;

        $trainer = User::where('user_id', $course->course_trainer_id)->first();

        $forumThreads = ForumThread::where('course_id', $course->course_id)
            ->orderBy('created_at', 'desc')
            ->get();

        return view('instructor.submission-course', compact(
            'course',
            'trainer',
            'learnerCount',
            'forumThreads'
        ));
    }


    /**
     * Ambil semua learner + submission untuk 1 item course
     */
//    public function getAllSubmissions($item_id, $course_id)
//     {
//         try {
//             $item = CourseWeekItem::find($item_id);
//             if (!$item) {
//                 return response()->json([
//                     'success' => false,
//                     'message' => 'Item tidak ditemukan',
//                 ], 404);
//             }

//             // --- Pertanyaan item
//             $questions = null;
//             switch ($item->course_item_type) {
//                 case 3: // Essay
//                     $q = CourseEssay::where('item_id', $item_id)->first();
//                     $questions = $q ? $q->essay_title : null;
//                     break;

//                 case 4: // Multiple Choice
//                     $questions = CourseTypeQuestion::where('item_id', $item_id)->get()->map(function ($q) {
//                         return [
//                             'question_text' => $q->question_text,
//                             'options' => [
//                                 'A' => $q->option_a,
//                                 'B' => $q->option_b,
//                                 'C' => $q->option_c,
//                                 'D' => $q->option_d,
//                             ],
//                             'correct' => $q->correct_option,
//                         ];
//                     });
//                     break;

//                 case 5: // Forum
//                     $forum = CourseForumDiscussion::where('item_id', $item_id)->first();
//                     if ($forum) {
//                         $questions = [
//                             'topic'    => $forum->topic,
//                             'question' => $forum->question_text,
//                         ];
//                     }
//                     break;

//                 case 7: // Attachment Upload
//                     $questions = $item->course_describe;
//                     break;
//             }

//             // --- Learners + submissions
//             $learners = CourseEnrollment::with('user')
//                 ->where('course_id', $course_id)
//                 ->where('is_approve', 1)
//                 ->get();

//             $result = [];
//             foreach ($learners as $enroll) {
//                 $user = $enroll->user;
//                 $submission = null;

//                 switch ($item->course_item_type) {
//                     case 3: // Essay
//                         $submission = CourseEssaySubmission::where('item_id', $item_id)
//                             ->where('user_id', $user->user_id)
//                             ->latest()
//                             ->first();
//                         break;

//                     case 4: // Multiple Choice
//                         $submission = CourseMcSubmission::where('item_id', $item_id)
//                             ->where('user_id', $user->user_id)
//                             ->latest()
//                             ->first();
//                         break;

//                     case 5: // Forum
//                         $discussion = CourseForumDiscussion::where('item_id', $item_id)->first();
//                         if ($discussion) {
//                             $replies = CourseForumDiscussionReply::where('forum_id', $discussion->forum_id)
//                                 ->where('user_id', $user->user_id)
//                                 ->with('user')
//                                 ->get();

//                             if ($replies->count() > 0) {
//                                 $last = $replies->last();
//                                 $submission = [
//                                     'replies'    => $replies,
//                                     'grade'      => $last->grade,
//                                     'feedback'   => $last->feedback,
//                                     'submitted_at' => $last->created_at,
//                                 ];
//                             }
//                         }
//                         break;

//                     case 7: // Upload
//                         $s = CourseAttachmentSubmission::where('item_id', $item_id)
//                             ->where('user_id', $user->user_id)
//                             ->orderByDesc('submitted_at')
//                             ->first();
//                         if ($s) {
//                             $submission = [
//                                 'id'           => $s->id,
//                                 'file_path'    => $s->file_path,
//                                 'file_url'     => $s->file_path ? asset($s->file_path) : null,
//                                 'grade'        => $s->grade,
//                                 'feedback'     => $s->feedback,
//                                 'submitted_at' => $s->submitted_at,
//                             ];
//                         }
//                         break;
//                 }

//                 $result[] = [
//                     'id'        => $user->user_id,
//                     'name'      => $user->full_name ?? $user->name,
//                     'emp'       => $user->emp_id ?? '-',
//                     'submission'=> $submission,
//                     'graded'    => $submission && (
//                         (is_array($submission) && (isset($submission['grade']) || isset($submission['feedback'])))
//                         || (is_object($submission) && ($submission->grade !== null || $submission->feedback !== null))
//                     ),
//                 ];
//             }

//             return response()->json([
//                 'success'        => true,
//                 'type'           => $item->course_item_type,
//                 'item_id'        => $item_id,
//                 'passing_grade'  => $item->passing_grade ?? null, // ✅ added
//                 'essay_title'    => ($item->course_item_type == 3)
//                     ? ($questions ?? null) // ambil pertanyaan essay
//                     : null,

//                 'course_describe' => ($item->course_item_type == 7)
//                     ? ($questions ?? null) // ambil deskripsi tugas upload
//                     : null,
//                 'questions'      => $questions,
//                 'learners'       => $result,
//             ]);
//         } catch (\Throwable $e) {
//             Log::error("💥 getAllSubmissions error", [
//                 'message' => $e->getMessage(),
//                 'trace'   => $e->getTraceAsString(),
//             ]);

//             return response()->json([
//                 'success' => false,
//                 'error'   => $e->getMessage(),
//             ], 500);
//         }
//     }

    // public function getAllSubmissions($item_id, $course_id)
    // {
    //     try {
    //         $item = CourseWeekItem::find($item_id);
    //         if (!$item) {
    //             return response()->json([
    //                 'success' => false,
    //                 'message' => 'Item tidak ditemukan',
    //             ], 404);
    //         }

    //         // --- Pertanyaan item
    //         $questions = null;
    //         switch ($item->course_item_type) {
    //             case 3: // Essay
    //                 $q = CourseEssay::where('item_id', $item_id)->first();
    //                 $questions = $q ? $q->essay_title : null;
    //                 break;

    //             case 4: // Multiple Choice
    //                 $questions = CourseTypeQuestion::where('item_id', $item_id)->get()->map(function ($q) {
    //                     return [
    //                         'question_text' => $q->question_text,
    //                         'options' => [
    //                             'A' => $q->option_a,
    //                             'B' => $q->option_b,
    //                             'C' => $q->option_c,
    //                             'D' => $q->option_d,
    //                         ],
    //                         'correct' => $q->correct_option,
    //                     ];
    //                 });
    //                 break;

    //             case 5: // Forum
    //                 $forum = CourseForumDiscussion::where('item_id', $item_id)->first();
    //                 if ($forum) {
    //                     $questions = [
    //                         'topic'    => $forum->topic,
    //                         'question' => $forum->question_text,
    //                     ];
    //                 }
    //                 break;

    //             case 7: // Attachment Upload
    //                 $questions = $item->course_describe;
    //                 break;
    //         }

    //         // --- Learners + submissions
    //         $learners = CourseEnrollment::with('user')
    //             ->where('course_id', $course_id)
    //             ->where('is_approve', 1)
    //             ->get();

    //         $result = [];
    //         foreach ($learners as $enroll) {
    //             $user = $enroll->user;
    //             $submission = null;

    //             switch ($item->course_item_type) {
    //                 case 3: // Essay
    //                     $submissions = CourseEssaySubmission::where('item_id', $item_id)
    //                         ->where('user_id', $user->user_id)
    //                         ->orderByDesc('submitted_at')
    //                         ->get();

    //                     if ($submissions->isNotEmpty()) {
    //                         $submission = $submissions->map(function ($s) {
    //                             return [
    //                                 'id'           => $s->essay_submission_id ?? $s->id,
    //                                 'answer_text'  => $s->answer_text,
    //                                 'grade'        => $s->grade,
    //                                 'feedback'     => $s->feedback,
    //                                 'is_remedial'  => $s->is_remedial,
    //                                 'submitted_at' => $s->submitted_at,
    //                             ];
    //                         });
    //                     }
    //                     break;

    //                 case 4: // Multiple Choice
    //                     $submissions = CourseMcSubmission::where('item_id', $item_id)
    //                         ->where('user_id', $user->user_id)
    //                         ->orderByDesc('submitted_at')
    //                         ->get();

    //                     if ($submissions->isNotEmpty()) {
    //                         $submission = $submissions->map(function ($s) {
    //                             return [
    //                                 'id'           => $s->id,
    //                                 'grade'        => $s->grade,
    //                                 'feedback'     => $s->feedback,
    //                                 'is_remedial'  => $s->is_remedial ?? 0,
    //                                 'submitted_at' => $s->submitted_at,
    //                             ];
    //                         });
    //                     }
    //                     break;

    //                 case 5: // Forum
    //                     $discussion = CourseForumDiscussion::where('item_id', $item_id)->first();
    //                     if ($discussion) {
    //                         $replies = CourseForumDiscussionReply::where('forum_id', $discussion->forum_id)
    //                             ->where('user_id', $user->user_id)
    //                             ->with('user')
    //                             ->orderBy('created_at', 'asc')
    //                             ->get();

    //                         if ($replies->count() > 0) {
    //                             $submission = $replies->map(function ($r) {
    //                                 return [
    //                                     'reply'        => $r->reply_content,
    //                                     'grade'        => $r->grade,
    //                                     'feedback'     => $r->feedback,
    //                                     'submitted_at' => $r->created_at,
    //                                 ];
    //                             });
    //                         }
    //                     }
    //                     break;

    //                 case 7: // Upload
    //                     $submissions = CourseAttachmentSubmission::where('item_id', $item_id)
    //                         ->where('user_id', $user->user_id)
    //                         ->orderByDesc('submitted_at')
    //                         ->get();

    //                     if ($submissions->isNotEmpty()) {
    //                         $submission = $submissions->map(function ($s) {
    //                             return [
    //                                 'id'           => $s->id,
    //                                 'file_path'    => $s->file_path,
    //                                 'file_url'     => $s->file_path ? asset($s->file_path) : null,
    //                                 'grade'        => $s->grade,
    //                                 'feedback'     => $s->feedback,
    //                                 'is_remedial'  => $s->is_remedial,
    //                                 'submitted_at' => $s->submitted_at,
    //                             ];
    //                         });
    //                     }
    //                     break;
    //             }

    //             $result[] = [
    //                 'id'        => $user->user_id,
    //                 'name'      => $user->full_name ?? $user->name,
    //                 'emp'       => $user->emp_id ?? '-',
    //                 'submission'=> $submission,
    //                 'graded'    => !empty($submission),
    //             ];
    //         }

    //         return response()->json([
    //             'success'        => true,
    //             'type'           => $item->course_item_type,
    //             'item_id'        => $item_id,
    //             'passing_grade'  => $item->passing_grade ?? null,
    //             'essay_title'    => ($item->course_item_type == 3) ? ($questions ?? null) : null,
    //             'course_describe'=> ($item->course_item_type == 7) ? ($questions ?? null) : null,
    //             'questions'      => $questions,
    //             'learners'       => $result,
    //         ]);
    //     } catch (\Throwable $e) {
    //         Log::error("💥 getAllSubmissions error", [
    //             'message' => $e->getMessage(),
    //             'trace'   => $e->getTraceAsString(),
    //         ]);

    //         return response()->json([
    //             'success' => false,
    //             'error'   => $e->getMessage(),
    //         ], 500);
    //     }
    // }

    public function getAllSubmissions($item_id, $course_id)
    {
        try {
            // =====================================================
            // 📚 Ambil item
            // =====================================================
            $item = CourseWeekItem::find($item_id);
            if (!$item) {
                return response()->json([
                    'success' => false,
                    'message' => 'Item tidak ditemukan',
                ], 404);
            }

            // =====================================================
            // ❓ Ambil pertanyaan item
            // =====================================================
            $questions = null;
            switch ($item->course_item_type) {
                case 3: // Essay
                    $q = CourseEssay::where('item_id', $item_id)->first();
                    $questions = $q ? $q->essay_title : null;
                    break;

                case 4: // Multiple Choice
                    $questions = CourseTypeQuestion::where('item_id', $item_id)->get()->map(function ($q) {
                        return [
                            'question_text' => $q->question_text,
                            'options' => [
                                'A' => $q->option_a,
                                'B' => $q->option_b,
                                'C' => $q->option_c,
                                'D' => $q->option_d,
                            ],
                            'correct' => $q->correct_option,
                        ];
                    });
                    break;

                case 5: // Forum
                    $forum = CourseForumDiscussion::where('item_id', $item_id)->first();
                    if ($forum) {
                        $questions = [
                            'topic'    => $forum->topic,
                            'question' => $forum->question_text,
                        ];
                    }
                    break;

                case 7: // Attachment Upload
                    $questions = $item->course_describe;
                    break;
            }

            // =====================================================
            // 👥 Ambil learner & filter hanya non-IT
            // =====================================================
            $learners = CourseEnrollment::with('user')
                ->where('course_id', $course_id)
                ->where('is_approve', 1)
                ->get()
                ->filter(function ($enroll) {
                    $user = $enroll->user;
                    if (!$user) return false;

                    // Exclude role_id = 1
                    if ((int)$user->role_id === 1) return false;

                    // Parse sub_role JSON / string
                    $sub = $user->sub_role ?? [];
                    if (is_string($sub)) {
                        $decoded = json_decode($sub, true);
                        $sub = $decoded ?: explode(',', $sub);
                    }

                    // Ubah ke array angka dan cek apakah ada 1
                    $sub = array_map('intval', (array)$sub);
                    return !in_array(1, $sub);
                })
                ->values();

            // =====================================================
            // 📦 Kumpulkan submissions per learner
            // =====================================================
            $result = [];
            foreach ($learners as $enroll) {
                $user = $enroll->user;
                $submission = null;

                switch ($item->course_item_type) {
                    case 3: // Essay
                        $submissions = CourseEssaySubmission::where('item_id', $item_id)
                            ->where('user_id', $user->user_id)
                            ->orderByDesc('submitted_at')
                            ->get();

                        if ($submissions->isNotEmpty()) {
                            $submission = $submissions->map(function ($s) {
                                return [
                                    'id'           => $s->essay_submission_id ?? $s->id,
                                    'answer_text'  => $s->answer_text,
                                    'grade'        => $s->grade,
                                    'feedback'     => $s->feedback,
                                    'is_remedial'  => $s->is_remedial,
                                    'submitted_at' => $s->submitted_at,
                                ];
                            });
                        }
                        break;

                    case 4: // Multiple Choice
                        $submissions = CourseMcSubmission::where('item_id', $item_id)
                            ->where('user_id', $user->user_id)
                            ->orderByDesc('submitted_at')
                            ->get();

                        if ($submissions->isNotEmpty()) {
                            $submission = $submissions->map(function ($s) {
                                return [
                                    'id'           => $s->id,
                                    'grade'        => $s->grade,
                                    'feedback'     => $s->feedback,
                                    'is_remedial'  => $s->is_remedial ?? 0,
                                    'submitted_at' => $s->submitted_at,
                                ];
                            });
                        }
                        break;

                    case 5: // Forum
                        $discussion = CourseForumDiscussion::where('item_id', $item_id)->first();
                        if ($discussion) {
                            $replies = CourseForumDiscussionReply::where('forum_id', $discussion->forum_id)
                                ->where('user_id', $user->user_id)
                                ->with('user')
                                ->orderBy('created_at', 'asc')
                                ->get();

                            if ($replies->count() > 0) {
                                $submission = $replies->map(function ($r) {
                                    return [
                                        'reply'        => $r->reply_content,
                                        'grade'        => $r->grade,
                                        'feedback'     => $r->feedback,
                                        'submitted_at' => $r->created_at,
                                    ];
                                });
                            }
                        }
                        break;

                    case 7: // Upload
                        $submissions = CourseAttachmentSubmission::where('item_id', $item_id)
                            ->where('user_id', $user->user_id)
                            ->orderByDesc('submitted_at')
                            ->get();

                        if ($submissions->isNotEmpty()) {
                            $submission = $submissions->map(function ($s) {
                                return [
                                    'id'           => $s->id,
                                    'file_path'    => $s->file_path,
                                    'file_url'     => $s->file_path ? asset($s->file_path) : null,
                                    'grade'        => $s->grade,
                                    'feedback'     => $s->feedback,
                                    'is_remedial'  => $s->is_remedial,
                                    'submitted_at' => $s->submitted_at,
                                ];
                            });
                        }
                        break;
                }

                $result[] = [
                    'id'         => $user->user_id,
                    'name'       => $user->full_name ?? $user->name,
                    'emp'        => $user->emp_id ?? '-',
                    'submission' => $submission,
                    'graded'     => !empty($submission),
                ];
            }

            // =====================================================
            // 📤 Return response
            // =====================================================
            return response()->json([
                'success'        => true,
                'type'           => $item->course_item_type,
                'item_id'        => $item_id,
                'passing_grade'  => $item->passing_grade ?? null,
                'essay_title'    => ($item->course_item_type == 3) ? ($questions ?? null) : null,
                'course_describe'=> ($item->course_item_type == 7) ? ($questions ?? null) : null,
                'questions'      => $questions,
                'learners'       => $result,
            ]);
        } catch (\Throwable $e) {
            Log::error("💥 getAllSubmissions error", [
                'message' => $e->getMessage(),
                'trace'   => $e->getTraceAsString(),
            ]);

            return response()->json([
                'success' => false,
                'error'   => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Apply feedback / grading
     */
    public function applyFeedback(Request $request, $item_id, $learner_id)
    {
        $request->validate([
            'grade'    => 'nullable|numeric|min:0|max:100',
            'feedback' => 'nullable|string',
        ]);

        $item = CourseWeekItem::findOrFail($item_id);
        $submission = null;

        switch ($item->course_item_type) {
            case 3: // Essay
                $submission = CourseEssaySubmission::where('item_id', $item_id)
                    ->where('user_id', $learner_id)
                    ->latest()
                    ->first();

                    if ($submission) {
                        $submission->grade     = $request->grade;
                        $submission->feedback  = $request->feedback;
                        $submission->is_graded = 1; // ✅ pastikan tersimpan sebagai integer (tinyint)
                        $submission->save();
                    }
                break;

            case 4: // MC → hanya feedback
                $submission = CourseMcSubmission::where('item_id', $item_id)
                    ->where('user_id', $learner_id)
                    ->latest()
                    ->first();
                if ($submission) {
                    $submission->feedback = $request->feedback;
                    $submission->save();
                    return response()->json([
                        'success'    => true,
                        'submission' => $submission,
                    ]);
                }
                return response()->json(['success' => false, 'message' => 'Submission tidak ditemukan'], 404);

            case 5: // Forum → update semua reply learner
                $updated = CourseForumDiscussionReply::where('user_id', $learner_id)
                    ->whereHas('discussion', function ($q) use ($item_id) {
                        $q->where('item_id', $item_id);
                    })
                    ->update([
                        'grade'    => $request->grade,
                        'feedback' => $request->feedback,
                    ]);
                return response()->json([
                    'success'  => $updated > 0,
                    'grade'    => $request->grade,
                    'feedback' => $request->feedback,
                ]);

            case 7: // Upload
                $submission = CourseAttachmentSubmission::where('item_id', $item_id)
                    ->where('user_id', $learner_id)
                    ->orderByDesc('submitted_at')
                    ->first();
                break;
        }

        if ($submission) {
            if ($item->course_item_type != 4) {
                $submission->grade = $request->grade;
            }
            $submission->feedback = $request->feedback;
            $submission->save();
            return response()->json([
                'success'    => true,
                'submission' => $submission,
            ]);
        }

        return response()->json(['success' => false, 'message' => 'Submission tidak ditemukan'], 404);
    }

   public function getThreadDiscussion($course_id)
    {
        try {
            $threads = \App\Models\Thread\ForumThread::with([
                'creator:user_id,full_name',
                'replies.replier:user_id,full_name'
            ])
            ->where('course_id', $course_id)
            ->orderBy('thread_seq', 'asc')
            ->get();

            $formatted = $threads->map(function ($t) {
                // 🔹 Auto detect type display for frontend
                $attachmentView = null;
                if ($t->attachment_type && $t->attachment_path) {
                    switch ($t->attachment_type) {
                        case 'pdf':
                            $attachmentView = asset($t->attachment_path);
                            break;
                        case 'video':
                            $attachmentView = asset($t->attachment_path);
                            break;
                        case 'url':
                            $attachmentView = $t->attachment_path;
                            break;
                    }
                }

                return [
                    'topic_title'   => $t->topic_title ?? '(Tanpa Judul)',
                    'forum_question'=> $t->forum_question ?? '(Tidak ada pertanyaan)',
                    'creator_name'  => $t->creator->full_name ?? 'Unknown',
                    'attachment_type' => $t->attachment_type ?? null,
                    'attachment_path' => $attachmentView,
                    'replies'       => $t->replies->map(fn($r) => [
                        'replier_name'  => $r->replier->full_name ?? 'Unknown',
                        'reply_content' => $r->reply_content,
                    ])
                ];
            });

            return response()->json([
                'success' => true,
                'threads' => $formatted,
            ]);
        } catch (\Exception $e) {
            \Log::error("❌ getThreadDiscussion failed: " . $e->getMessage(), [
                'course_id' => $course_id,
                'trace'     => $e->getTraceAsString(),
            ]);
            return response()->json([
                'success' => false,
                'error'   => $e->getMessage(),
            ], 500);
        }
    }
}
