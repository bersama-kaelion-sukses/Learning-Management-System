<?php

namespace App\Http\Controllers\LMS;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use App\Models\Course;
use App\Models\CourseWeekItem;
use App\Models\CourseEnrollment;
use App\Models\CourseType\CourseAttachmentSubmission; 
use App\Models\CourseType\CourseEssaySubmission;
use App\Models\CourseType\CourseForumDiscussionReply;
use App\Models\CourseType\CourseMcSubmission;
use App\Models\CourseProgress;
use App\Models\CourseLearnerActivity;
use App\Models\Division;
use App\Models\User;


class TrackController extends Controller
{

    public function show($courseId)
    {
        // ===================================================
        // 📦 Ambil course + struktur weeks/items + enrollments/user
        // ===================================================
        $course = Course::with([
            'weeks.items',
            'enrollments.user' => function ($q) {
                $q->select('user_id', 'full_name', 'emp_id', 'departement_cat', 'role_id', 'sub_role');
            }
        ])
        ->where('is_deleted', 0)
        ->findOrFail($courseId);

        // ===================================================
        // 🏢 Ambil mapping nama division (id -> nama)
        // ===================================================
        $divisions = Division::pluck('division_name', 'division_id')->toArray();

        // ===================================================
        // 🕒 Ambil aktivitas learner (last_access & is_opened)
        // ===================================================
        $activities = CourseLearnerActivity::where('course_id', $course->course_id)
            ->get()
            ->keyBy('user_id');

        // ===================================================
        // 🔍 Filter enrollments (hapus user role_id = 1 & sub_role berisi 1)
        // ===================================================
        $course->enrollments = $course->enrollments->filter(function ($enroll) {
            $user = $enroll->user;
            if (!$user) return false;

            // Role utama bukan 1
            if ((int)$user->role_id === 1) return false;

            // Sub_role tidak mengandung 1 (baik JSON maupun CSV string)
            $sub = $user->sub_role ?? [];
            if (is_string($sub)) {
                $decoded = json_decode($sub, true);
                $sub = $decoded ?: explode(',', $sub);
            }

            $sub = array_map('intval', (array)$sub);
            return !in_array(1, $sub);
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

        // ===================================================
        // 👥 Hitung jumlah peserta aktif dan tergabung (mengikuti filter)
        // ===================================================
        $filteredUserIds = $course->enrollments->pluck('user.user_id')->filter()->unique();

        $activeCount = CourseLearnerActivity::where('course_id', $course->course_id)
            ->where('is_opened', 1)
            ->whereIn('user_id', $filteredUserIds)
            ->count();

        $joinedCount = CourseEnrollment::where('course_id', $course->course_id)
            ->where('status_join', 1)
            ->whereIn('user_id', $filteredUserIds)
            ->count();

        $learnerCount = $course->enrollments->count() ?? 0;

        // Tambahkan properti agar bisa dipakai langsung di view
        $course->active_count = $activeCount;
        $course->joined_count = $joinedCount;
        $course->filtered_learner_count = $learnerCount;

        // ===================================================
        // 👤 Ambil data trainer
        // ===================================================
        $trainer = User::where('user_id', $course->course_trainer_id)->first();

        // ===================================================
        // 📤 Kirim data lengkap ke view
        // ===================================================
        return view('instructor.track-course', [
            'course'       => $course,
            'students'     => $course->enrollments,
            'trainer'      => $trainer,
            'learnerCount' => $learnerCount,
        ]);
    }

    
    public function getLearnerCourse($courseId, $itemId)
    {
        try {
            // =====================================================
            // 📚 Ambil course & item
            // =====================================================
            $course = Course::with(['enrollments.user'])->findOrFail($courseId);
            $item   = CourseWeekItem::with('forum')->findOrFail($itemId);
    
            // =====================================================
            // 🚫 Filter learner hanya untuk Non-IT (role_id ≠ 1 & sub_role tidak mengandung 1)
            // =====================================================
            $learners = $course->enrollments
                ->filter(function ($enroll) {
                    $user = $enroll->user;
                    if (!$user) return false;
    
                    // 1️⃣ Exclude IT utama
                    if ((int)trim($user->role_id) === 1) return false;
    
                    // 2️⃣ Parse sub_role (bisa "[3,4]", "3,4", atau ["3","4"])
                    $sub = $user->sub_role ?? '[]';
    
                    if (is_string($sub)) {
                        $decoded = json_decode($sub, true);
                        if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
                            $sub = $decoded;
                        } else {
                            // fallback jika bukan JSON valid
                            $sub = str_replace(['[', ']', '"', "'"], '', $sub);
                            $sub = array_filter(array_map('trim', explode(',', $sub)));
                        }
                    }
    
                    // 3️⃣ Pastikan integer semua
                    $sub = array_map('intval', (array)$sub);
    
                    // 4️⃣ Tampilkan hanya user yang tidak punya sub_role 1
                    return !in_array(1, $sub);
                })
                ->map(function ($enroll) use ($item) {
                    $user = $enroll->user;
                    $userId = $user->user_id;
                    $type   = $item->course_item_type;
    
                    $hasSubmission = false;
                    $grade = null;
                    $passed = 0;
                    $notPassed = 0;
                    $submission = null;
    
                    // =====================================================
                    // 🔍 Cek submission berdasarkan tipe item
                    // =====================================================
                    switch ($type) {
                        case 3: // Essay
                            $submission = \DB::table('course_essay_submissions')
                                ->where('user_id', $userId)
                                ->where(function ($q) use ($item) {
                                    $q->where('item_id', $item->item_id)
                                      ->orWhere('essay_id', $item->item_id);
                                })
                                ->orderByDesc('submitted_at')
                                ->first();
                            break;
    
                        case 4: // Multiple Choice
                            $submission = \DB::table('course_mc_submissions')
                                ->where('user_id', $userId)
                                ->where('item_id', $item->item_id)
                                ->orderByDesc('grade')
                                ->orderByDesc('submitted_at')
                                ->first();
                            break;
    
                        case 5: // Forum
                            $submission = ($item->forum)
                                ? \DB::table('course_item_forum_replies')
                                    ->where('user_id', $userId)
                                    ->where('forum_id', $item->forum->forum_id)
                                    ->orderByDesc('reply_id')
                                    ->first()
                                : null;
                            break;
    
                        case 7: // Attachment Upload
                            $submission = \DB::table('course_attachment_submissions')
                                ->where('user_id', $userId)
                                ->where('item_id', $item->item_id)
                                ->orderByDesc('submitted_at')
                                ->first();
                            break;
                    }
    
                    // =====================================================
                    // ✅ Cek apakah user sudah submit & passing grade
                    // =====================================================
                    if ($submission) {
                        $hasSubmission = true;
                        $grade = $submission->grade ?? null;
    
                        if (!is_null($item->passing_grade) && !is_null($grade)) {
                            if ($grade >= $item->passing_grade) {
                                $passed = 1;
                            } else {
                                $notPassed = 1;
                            }
                        }
                    }
    
                    // =====================================================
                    // 🧾 Logging tiap learner
                    // =====================================================
                    \Log::info('📘 Learner Submission Check', [
                        'user' => $user->full_name,
                        'user_id' => $userId,
                        'type' => $type,
                        'item_id' => $item->item_id,
                        'has_submission' => $hasSubmission,
                        'grade' => $grade,
                        'passing_grade' => $item->passing_grade,
                        'passed' => $passed,
                        'not_passed' => $notPassed,
                    ]);
    
                    // =====================================================
                    // 🎯 Return ke frontend
                    // =====================================================
                    return [
                        'full_name'     => $user->full_name ?? 'Unknown',
                        'emp_id'        => $user->emp_id ?? '-',
                        'submit'        => $hasSubmission ? 1 : 0,
                        'not_submit'    => $hasSubmission ? 0 : 1,
                        'grade'         => $grade ?? '-',
                        'passing_grade' => $item->passing_grade ?? '-',
                        'passed'        => $passed,
                        'not_passed'    => $notPassed,
                    ];
                })
                ->values(); // reset index collection agar rapi
    
            // =====================================================
            // 🧩 Logging ringkasan
            // =====================================================
            \Log::info('✅ getLearnerCourse Summary (Non-IT only)', [
                'item_id'          => $itemId,
                'course_id'        => $courseId,
                'learners_total'   => $learners->count(),
                'submitted_count'  => $learners->where('submit', 1)->count(),
                'passed_count'     => $learners->where('passed', 1)->count(),
                'not_passed_count' => $learners->where('not_passed', 1)->count(),
            ]);
    
            // =====================================================
            // 📤 Response ke frontend
            // =====================================================
            return response()->json([
                'success'  => true,
                'item'     => $item->course_item_name,
                'learners' => $learners,
            ]);
    
        } catch (\Exception $e) {
            \Log::error("❌ getLearnerCourse gagal: " . $e->getMessage(), [
                'courseId' => $courseId,
                'itemId'   => $itemId,
                'trace'    => $e->getTraceAsString(),
            ]);
    
            return response()->json([
                'success' => false,
                'error'   => $e->getMessage(),
            ], 500);
        }
    }


  
   public function getLearnerRemedial($courseId)
    {
        try {
            // Log::info("🚀 [getLearnerRemedial] START", [
            //     'course_id' => $courseId,
            //     'timestamp' => now()->toDateTimeString()
            // ]);
    
            $course = Course::with(['weeks.items', 'enrollments.user'])
                ->findOrFail($courseId);
    
            // Log::info("📘 Course Loaded", [
            //     'course_title' => $course->course_title,
            //     'weeks'        => $course->weeks->count(),
            //     'enrollments'  => $course->enrollments->count(),
            // ]);
    
            $learners = $course->enrollments->map(function ($enroll) use ($course) {
    
                $user = $enroll->user;
    
                if (!$user) {
                    // Log::warning("⚠️ Enrollment tanpa user, SKIPPED");
                    return null;
                }
    
                // Log::info("➡️ [CHECK USER]", [
                //     'user_id'   => $user->user_id,
                //     'full_name' => $user->full_name,
                //     'role_id'   => $user->role_id,
                //     'sub_role'  => $user->sub_role,
                // ]);
    
                // SKIP SUPERVISOR
                if ($user->role_id == 1) {
                    // Log::info("⛔ SKIPPED ROLE Supervisor", [
                    //     'user_id' => $user->user_id
                    // ]);
                    return null;
                }
    
                // Normalisasi SUB ROLE
                $rawSubRole = $user->sub_role;
    
                if (is_array($rawSubRole)) {
                    $subRoles = $rawSubRole;
                } elseif (is_string($rawSubRole) && $rawSubRole !== "") {
                    $decoded = json_decode($rawSubRole, true);
                    $subRoles = is_array($decoded) ? $decoded : [$rawSubRole];
                } else {
                    $subRoles = [];
                }
    
                // Log::info("🧩 Normalized sub_role", [
                //     'user_id'    => $user->user_id,
                //     'normalized' => $subRoles
                // ]);
    
                if (in_array(1, $subRoles)) {
                    // Log::info("⛔ SKIPPED karena Sub Role 1", [
                    //     'user_id' => $user->user_id
                    // ]);
                    return null;
                }
    
                // ==========================================================
                // HITUNG REMEDIAL
                // ==========================================================
                $failedItems = 0;
                $totalSubmissionItems = 0;
    
                foreach ($course->weeks as $week) {
                    foreach ($week->items as $item) {
    
                        if (!in_array($item->course_item_type, [3, 4, 7])) {
                            continue;
                        }
    
                        $totalSubmissionItems++;
    
                        // Log::info("📘 ITEM CHECK", [
                        //     'user_id'  => $user->user_id,
                        //     'item_id'  => $item->item_id,
                        //     'type'     => $item->course_item_type,
                        //     'passing'  => $item->passing_grade,
                        // ]);
    
                        $passing = $item->passing_grade;
    
                        // ==========================================================
                        // 3 = ESSAY
                        // ==========================================================
                        if ($item->course_item_type == 3) {
    
                            $query = DB::table('course_essay_submissions')
                                ->where('user_id', $user->user_id)
                                ->where(function ($q) use ($item) {
                                    $q->where('item_id', $item->item_id)
                                      ->orWhere('essay_id', $item->item_id);
                                })
                                ->where('is_remedial', 0)
                                ->orderByDesc('submitted_at');
    
                            $submissionExists = $query->exists();
                            $grade = $query->value('grade');
    
                            // Log::info("📝 ESSAY STATUS", [
                            //     'exists' => $submissionExists,
                            //     'grade'  => $grade
                            // ]);
    
                            if (!$submissionExists) {
                                // Log::info("🟡 SKIP Essay — belum submit");
                                continue;
                            }
    
                            if (is_null($grade)) {
                                // Log::info("🟡 SKIP Essay — grade null (menunggu penilaian)");
                                continue;
                            }
    
                            if ($grade < $passing) {
                                $failedItems++;
                                // Log::warning("❌ ESSAY FAILED", [
                                //     'grade'   => $grade,
                                //     'passing' => $passing
                                // ]);
                            }
                        }
    
                        // ==========================================================
                        // 4 = MULTIPLE CHOICE
                        // ==========================================================
                        elseif ($item->course_item_type == 4) {
    
                            $subs = DB::table('course_mc_submissions')
                                ->where('user_id', $user->user_id)
                                ->where('item_id', $item->item_id)
                                ->orderBy('attempt_no')
                                ->get();
    
                            // Log::info("❓ MC ATTEMPTS", [
                            //     'count'  => $subs->count(),
                            //     'grades' => $subs->pluck('grade')
                            // ]);
    
                            if ($subs->count() == 0) {
                                // Log::info("🟡 SKIP MC — belum submit");
                                continue;
                            }
    
                            $validGrades = $subs->filter(fn($mc) => !is_null($mc->grade));
    
                            if ($validGrades->isEmpty()) {
                                // Log::info("🟡 SKIP MC — semua grade null");
                                continue;
                            }
    
                            $hasPassed = $validGrades->contains(fn($mc) => $mc->grade >= $passing);
    
                            if (!$hasPassed) {
                                $failedItems++;
                                // Log::warning("❌ MC FAILED — tidak ada attempt lulus");
                            }
                        }
    
                        // ==========================================================
                        // 7 = ATTACHMENT
                        // ==========================================================
                        elseif ($item->course_item_type == 7) {
    
                            $query = DB::table('course_attachment_submissions')
                                ->where('user_id', $user->user_id)
                                ->where('item_id', $item->item_id)
                                ->where('is_remedial', 0)
                                ->orderByDesc('submitted_at');
    
                            $submissionExists = $query->exists();
                            $grade = $query->value('grade');
    
                            // Log::info("📎 ATTACHMENT STATUS", [
                            //     'exists' => $submissionExists,
                            //     'grade'  => $grade
                            // ]);
    
                            if (!$submissionExists) {
                                // Log::info("🟡 SKIP Attachment — belum submit");
                                continue;
                            }
    
                            if (is_null($grade)) {
                                // Log::info("🟡 SKIP Attachment — grade null");
                                continue;
                            }
    
                            if ($grade < $passing) {
                                $failedItems++;
                                // Log::warning("❌ ATTACH FAILED", [
                                //     'grade'   => $grade,
                                //     'passing' => $passing
                                // ]);
                            }
                        }
                    }
                }
    
                // ==========================================================
                // SUMMARY PER USER
                // ==========================================================
                $threshold = ceil($totalSubmissionItems / 2);
                $isRemedial = $failedItems >= $threshold;
    
                // Log::info("📊 SUMMARY USER", [
                //     'user_id'   => $user->user_id,
                //     'failed'    => $failedItems,
                //     'total'     => $totalSubmissionItems,
                //     'threshold' => $threshold,
                //     'remedial'  => $isRemedial,
                // ]);
    
                if (!$isRemedial) {
                    // Log::info("⏭️ USER TIDAK REMEDIAL — SKIPPED", [
                    //     'user_id' => $user->user_id
                    // ]);
                    return null;
                }
    
                return [
                    'user_id'       => $user->user_id,
                    'full_name'     => $user->full_name,
                    'emp_id'        => $user->emp_id,
                    'failed_items'  => $failedItems,
                    'total_items'   => $totalSubmissionItems,
                    'threshold'     => $threshold,
                    'is_remedial'   => true,
                ];
            })
            ->filter()
            ->values();
    
            // Log::info("✅ [getLearnerRemedial] SUCCESS", [
            //     'returned_learners' => $learners->count(),
            // ]);
    
            return response()->json([
                'success'  => true,
                'learners' => $learners,
            ]);
    
        } catch (\Exception $e) {
    
            // Log::error("❌ [getLearnerRemedial ERROR]", [
            //     'error' => $e->getMessage()
            // ]);
    
            return response()->json([
                'success' => false,
                'error'   => $e->getMessage(),
            ], 500);
        }
    }


//    public function getLearnerProgress($courseId)
//     {
//         try {
//             $course = Course::with(['enrollments.user'])->findOrFail($courseId);

//             $learners = $course->enrollments->map(function ($enroll) use ($course) {
//                 $user = $enroll->user;

//                 // ✅ Ambil nilai progress tertinggi (MAX)
//                 $maxProgress = CourseProgress::where('course_id', $course->course_id)
//                     ->where('user_id', $user->user_id)
//                     ->max('progress_pct');

//                 $progress = $maxProgress ?? 0;

//                 // 🪶 Logging
//                 \Log::info('📊 Learner Progress Check', [
//                     'user' => $user->full_name,
//                     'emp_id' => $user->emp_id,
//                     'course_id' => $course->course_id,
//                     'max_progress_pct' => $progress,
//                 ]);

//                 return [
//                     'user_id'    => $user->user_id,
//                     'full_name'  => $user->full_name ?? 'Unknown',
//                     'emp_id'     => $user->emp_id ?? '-',
//                     'progress'   => $progress,
//                 ];
//             });

//             // 🧩 Summary log (only essential info)
//             \Log::info('✅ getLearnerProgress Summary', [
//                 'course_id' => $courseId,
//                 'total_learners' => $learners->count(),
//                 'completed' => $learners->where('progress', '>=', 100)->count(),
//             ]);

//             return response()->json([
//                 'success'  => true,
//                 'course_id' => $courseId,
//                 'learners' => $learners,
//             ]);

//         } catch (\Exception $e) {
//             \Log::error("❌ getLearnerProgress gagal: " . $e->getMessage(), [
//                 'courseId' => $courseId,
//                 'trace'    => $e->getTraceAsString(),
//             ]);

//             return response()->json([
//                 'success' => false,
//                 'error'   => $e->getMessage(),
//             ], 500);
//         }
//     }

    public function getLearnerProgress($courseId)
    {
        try {
            // =====================================================
            // 📚 Ambil course dan user enrolled
            // =====================================================
            $course = Course::with(['enrollments.user'])->findOrFail($courseId);

            // =====================================================
            // 🚫 Filter hanya learner non-IT
            // =====================================================
            $learners = $course->enrollments
                ->filter(function ($enroll) {
                    $user = $enroll->user;
                    if (!$user) return false;

                    // 1️⃣ Exclude role_id = 1
                    if ((int)$user->role_id === 1) return false;

                    // 2️⃣ Parse sub_role JSON / string
                    $sub = $user->sub_role ?? [];
                    if (is_string($sub)) {
                        $decoded = json_decode($sub, true);
                        $sub = $decoded ?: explode(',', $sub);
                    }

                    // 3️⃣ Convert ke int dan cek jika ada "1"
                    $sub = array_map('intval', (array)$sub);
                    return !in_array(1, $sub);
                })
                ->map(function ($enroll) use ($course) {
                    $user = $enroll->user;

                    // ✅ Ambil nilai progress tertinggi (MAX)
                    $maxProgress = CourseProgress::where('course_id', $course->course_id)
                        ->where('user_id', $user->user_id)
                        ->max('progress_pct');

                    $progress = $maxProgress ?? 0;

                    // 🪶 Logging per learner
                    \Log::info('📊 Learner Progress Check (Non-IT)', [
                        'user'       => $user->full_name,
                        'emp_id'     => $user->emp_id,
                        'course_id'  => $course->course_id,
                        'progress'   => $progress,
                    ]);

                    return [
                        'user_id'    => $user->user_id,
                        'full_name'  => $user->full_name ?? 'Unknown',
                        'emp_id'     => $user->emp_id ?? '-',
                        'progress'   => $progress,
                        
                    ];
                });

            // =====================================================
            // 🧩 Summary Log
            // =====================================================
            \Log::info('✅ getLearnerProgress Summary (Non-IT only)', [
                'course_id'       => $courseId,
                'total_learners'  => $learners->count(),
                'completed'       => $learners->where('progress', '>=', 100)->count(),
            ]);

            // =====================================================
            // 📤 Response
            // =====================================================
            return response()->json([
                'success'   => true,
                'course_id' => $courseId,
                'learners'  => $learners,
            ]);

        } catch (\Exception $e) {
            \Log::error("❌ getLearnerProgress gagal: " . $e->getMessage(), [
                'courseId' => $courseId,
                'trace'    => $e->getTraceAsString(),
            ]);

            return response()->json([
                'success' => false,
                'error'   => $e->getMessage(),
            ], 500);
        }
    }
}
