<?php

namespace App\Http\Controllers\LMS;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use App\Models\CourseEnrollment;
use App\Models\CourseWeekModule;
use App\Models\CourseWeekItem;
use App\Models\Course;
use App\Models\CourseType\CourseEssay;
use App\Models\CourseType\CourseEssaySubmission;
use App\Models\CourseType\CourseMcSubmission;
use App\Models\CourseType\CourseForumDiscussion;
use App\Models\CourseType\CourseForumDiscussionReply;
use App\Models\CourseType\CourseAttachmentSubmission;
use App\Models\Thread\ForumReply;
use App\Models\Thread\ForumThread;
use App\Models\CourseProgress;
use App\Models\CourseLearnerActivity;
use App\Models\CourseFeedback;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;



class CourseEnrollmentController extends Controller
{

    // public function index()
    // {
    //     $user = Auth::user();
    //     $userId = $user->user_id ?? $user->id;

    //     Log::info('📘 [Learner Index] User login:', [
    //         'user_id'   => $userId,
    //         'user_name' => $user->full_name ?? $user->name,
    //     ]);

    //     // ===================================================
    //     // 🔹 Ambil semua enrollment aktif user
    //     // ===================================================
    //     $enrollments = CourseEnrollment::query()
    //         ->where('user_id', $userId)
    //         ->where('status_join', 1)
    //         ->whereHas('course', function($query) {
    //             $query->where('is_approved', 1);
    //         })
    //         ->with('course')
    //         ->get();

    //     Log::info('📘 [Learner Index] Enrollments ditemukan:', [
    //         'count' => $enrollments->count(),
    //         'course_ids' => $enrollments->pluck('course.course_id'),
    //     ]);

    //     // ===================================================
    //     // 🔹 Hitung progress tiap course
    //     // ===================================================
    //     $progress = CourseProgress::where('user_id', $userId)
    //         ->select(
    //             'course_id',
    //             DB::raw('COUNT(DISTINCT course_item_id) as checked'),
    //             DB::raw('MAX(total_item) as total')
    //         )
    //         ->groupBy('course_id')
    //         ->get()
    //         ->mapWithKeys(function ($row) {
    //             $pct = $row->total > 0 ? round(($row->checked / $row->total) * 100, 2) : 0;
    //             return [$row->course_id => $pct];
    //         });

    //     Log::info('📗 [Learner Index] Progress data:', $progress->toArray());

    //     // ===================================================
    //     // 🔥 Tambahkan REMEDIAL LOGIC per course
    //     // ===================================================
    //   foreach ($enrollments as $enroll) {
    //         $course = $enroll->course;
    //         if (!$course) continue;

    //         $courseId = $course->course_id;

    //         // Ambil semua item yang termasuk penilaian
    //         $items = CourseWeekItem::where('course_id', $courseId)
    //             ->whereIn('course_item_type', [3, 4, 7])
    //             ->get();

    //         $totalItems = $items->count();
    //         $failed = 0;
    //         $hasSubmission = false;   // default

    //         foreach ($items as $item) {
    //             $passing = $item->passing_grade ?? 0;

    //             // ===== ESSAY =====
    //             if ($item->course_item_type == 3) {
    //                 $grade = DB::table('course_essay_submissions')
    //                     ->where('user_id', $userId)
    //                     ->where(function ($q) use ($item) {
    //                         $q->where('item_id', $item->item_id)
    //                         ->orWhere('essay_id', $item->item_id);
    //                     })
    //                     ->where('is_remedial', 0)
    //                     ->orderByDesc('submitted_at')
    //                     ->value('grade');

    //                 if (!is_null($grade)) {
    //                     $hasSubmission = true;   // 🔥 FIX
    //                     if ($grade < $passing) $failed++;
    //                 }
    //             }

    //             // ===== MULTIPLE CHOICE =====
    //             elseif ($item->course_item_type == 4) {
    //                 $grades = DB::table('course_mc_submissions')
    //                     ->where('user_id', $userId)
    //                     ->where('item_id', $item->item_id)
    //                     ->orderByDesc('attempt_no')
    //                     ->pluck('grade')
    //                     ->filter()
    //                     ->values();

    //                 if ($grades->isNotEmpty()) {
    //                     $hasSubmission = true;   // 🔥 FIX
    //                     $hasPassed = $grades->contains(fn($g) => $g >= $passing);
    //                     if (!$hasPassed) $failed++;
    //                 }
    //             }

    //             // ===== ATTACHMENT =====
    //             elseif ($item->course_item_type == 7) {
    //                 $grade = DB::table('course_attachment_submissions')
    //                     ->where('user_id', $userId)
    //                     ->where('item_id', $item->item_id)
    //                     ->where('is_remedial', 0)
    //                     ->orderByDesc('submitted_at')
    //                     ->value('grade');

    //                 if (!is_null($grade)) {
    //                     $hasSubmission = true;   // 🔥 FIX
    //                     if ($grade < $passing) $failed++;
    //                 }
    //             }
    //         }

    //         // REMEDIAL THRESHOLD (50% logic)
    //         $threshold = ceil($totalItems / 2);
    //         $isRemedial = $failed >= $threshold;

    //         // Simpan ke enrollment object
    //         $enroll->failed_items = $failed;
    //         $enroll->total_items  = $totalItems;

    //         // ===== FINAL BADGE LOGIC =====
    //         if (!$hasSubmission) {
    //             $enroll->is_passed = null;  // ❌ No Badge (user belum submit apapun)
    //         } else {
    //             $enroll->is_passed = !$isRemedial; // TRUE = Lulus
    //         }

    //         Log::info("🧪 [Learner Badge] Per Course", [
    //             'course_id'   => $courseId,
    //             'title'       => $course->course_title,
    //             'total_items' => $totalItems,
    //             'failed'      => $failed,
    //             'threshold'   => $threshold,
    //             'hasSubmission' => $hasSubmission,
    //             'passed?'     => !$isRemedial,
    //         ]);
    //     }


    //     // ===================================================
    //     // 🔥 EXISTING ACTIVITY LOGIC (unchanged)
    //     // ===================================================
    //     $activities = CourseLearnerActivity::where('user_id', $userId)
    //         ->get()
    //         ->keyBy('course_id');

    //     foreach ($enrollments as $enroll) {
    //         $courseId = $enroll->course->course_id ?? null;
    //         $activity = $activities[$courseId] ?? null;

    //         $enroll->is_opened   = $activity->is_opened ?? 0;
    //         $enroll->last_access = $activity->last_access ?? null;
    //         $enroll->progress    = $progress[$courseId] ?? 0;

    //         $enroll->start_formatted = $enroll->course->start_course
    //             ? Carbon::parse($enroll->course->start_course)->format('d M Y')
    //             : '-';

    //         $enroll->end_formatted = $enroll->course->end_course
    //             ? Carbon::parse($enroll->course->end_course)->format('d M Y')
    //             : '-';

    //         // ============================
    //         // ⏰ PERIODE WAKTU (FIXED)
    //         // ============================
    //         $start = $enroll->course->start_course ?? null;
    //         $end   = $enroll->course->end_course ?? null;
    //         $now   = Carbon::now();

    //         // START dianggap mulai pukul 00:00 hari itu
    //         $startDay = $start ? Carbon::parse($start)->startOfDay() : null;

    //         // END dianggap berakhir pukul 23:59 hari itu
    //         $endDay   = $end ? Carbon::parse($end)->endOfDay() : null;

    //         // ============================
    //         // 🔍 LOGIKA FINAL
    //         // ============================
    //         if ($startDay && $now->lt($startDay)) {
    //             // Hari ini masih sebelum tanggal mulai
    //             $enroll->is_period_open = false;
    //             $enroll->period_message = 'Belum dimulai';
    //         }
    //         elseif ($endDay && $now->gt($endDay)) {
    //             // Sudah lewat 23:59 tanggal end
    //             $enroll->is_period_open = false;
    //             $enroll->period_message = 'Telah Berakhir';
    //         }
    //         else {
    //             // Antara start_date (00:00) sampai end_date (23:59)
    //             $enroll->is_period_open = true;
    //             $enroll->period_message = 'Sedang berlangsung';
    //         }
    //     }

    //     // ===================================================
    //     // 📤 Kirim ke View
    //     // ===================================================
    //     return view('learner.course', compact('enrollments'));
    // }
    
   public function index()
    {
        $user = Auth::user();
        $userId = $user->user_id ?? $user->id;

        // Log::info('📘 [Learner Index] User login:', [
        //     'user_id'   => $userId,
        //     'user_name' => $user->full_name ?? $user->name,
        // ]);

        // ===================================================
        // 🔹 Ambil semua enrollment aktif user
        // ===================================================
        $enrollments = CourseEnrollment::query()
            ->where('user_id', $userId)
            ->where('status_join', 1)
            ->whereHas('course', function($query) {
                $query->where('is_approved', 1);
            })
            ->with('course')
            ->get();

        // Log::info('📘 [Learner Index] Enrollments ditemukan:', [
        //     'count' => $enrollments->count(),
        //     'course_ids' => $enrollments->pluck('course.course_id'),
        // ]);

        // ===================================================
        // 🔹 Hitung progress tiap course
        // ===================================================
        $progress = CourseProgress::where('user_id', $userId)
            ->select(
                'course_id',
                DB::raw('COUNT(DISTINCT course_item_id) as checked'),
                DB::raw('MAX(total_item) as total')
            )
            ->groupBy('course_id')
            ->get()
            ->mapWithKeys(function ($row) {
                $pct = $row->total > 0 ? round(($row->checked / $row->total) * 100, 2) : 0;
                return [$row->course_id => $pct];
            });

        // Log::info('📗 [Learner Index] Progress data:', $progress->toArray());

        // ===================================================
        // 🔥 REMEDIAL LOGIC (UPDATED)
        // ===================================================
        foreach ($enrollments as $enroll) {

            $course = $enroll->course;
            if (!$course) continue;

            $courseId = $course->course_id;
            
            $progressPct = $progress[$courseId] ?? 0;
            
            $items = CourseWeekItem::where('course_id', $courseId)
                ->whereIn('course_item_type', [3, 4, 7]) // essay, mc, attachment
                ->get();

            $totalItems = $items->count();
            $failed     = 0;
            $hasSubmission = false;

            foreach ($items as $item) {
                $passing = $item->passing_grade ?? 0;

                // ===================================================
                // ESSAY
                // ===================================================
                if ($item->course_item_type == 3) {

                    $row = DB::table('course_essay_submissions')
                        ->where('user_id', $userId)
                        ->where(function($q) use ($item) {
                            $q->where('item_id', $item->item_id)
                            ->orWhere('essay_id', $item->item_id);
                        })
                        ->where('is_remedial', 0)
                        ->orderByDesc('submitted_at')
                        ->first();

                    if ($row) {
                        $hasSubmission = true;

                        // FIX → jika grade null dianggap gagal
                        if (is_null($row->grade) || $row->grade < $passing) {
                            $failed++;
                        }
                    }
                }

                // ===================================================
                // MULTIPLE CHOICE
                // ===================================================
                elseif ($item->course_item_type == 4) {

                    $rows = DB::table('course_mc_submissions')
                        ->where('user_id', $userId)
                        ->where('item_id', $item->item_id)
                        ->orderByDesc('attempt_no')
                        ->get();

                    if ($rows->isNotEmpty()) {
                        $hasSubmission = true;

                        $validGrades = $rows->pluck('grade')->filter(); // remove null

                        // FIX → jika semua grade null → langsung gagal
                        if ($validGrades->isEmpty()) {
                            $failed++;
                        } else {
                            $hasPassed = $validGrades->contains(fn($g) => $g >= $passing);
                            if (!$hasPassed) $failed++;
                        }
                    }
                }

                // ===================================================
                // ATTACHMENT
                // ===================================================
                elseif ($item->course_item_type == 7) {

                    $row = DB::table('course_attachment_submissions')
                        ->where('user_id', $userId)
                        ->where('item_id', $item->item_id)
                        ->where('is_remedial', 0)
                        ->orderByDesc('submitted_at')
                        ->first();

                    if ($row) {
                        $hasSubmission = true;

                        // FIX → null atau < passing dianggap gagal
                        if (is_null($row->grade) || $row->grade < $passing) {
                            $failed++;
                        }
                    }
                }
            }
            
            // ===================================================
            // 🔒 HARD GATE: PROGRESS < 100% → MUTLAK TIDAK LULUS
            // ===================================================
            if ($progressPct < 100) {

                $enroll->failed_items = $failed;
                $enroll->total_items  = $totalItems;
                $enroll->is_passed    = false;

                // Log::info("⛔ [Learner Badge] FAIL BY PROGRESS", [
                //     'course_id' => $courseId,
                //     'progress'  => $progressPct,
                // ]);

                continue; // STOP evaluasi course ini
            }
            
            // ===================================================
            // REMEDIAL THRESHOLD 50%
            // ===================================================
            $threshold  = ceil($totalItems / 2);
            $isRemedial = $failed >= $threshold;

            $enroll->failed_items = $failed;
            $enroll->total_items  = $totalItems;

            // ===================================================
            // FINAL BADGE LOGIC
            // ===================================================
            if (!$hasSubmission) {
                $enroll->is_passed = null; // Tidak tampil badge
            } else {
                $enroll->is_passed = !$isRemedial;
            }

            // Log::info("🧪 [Learner Badge] Per Course", [
            //     'course_id'   => $courseId,
            //     'title'       => $course->course_title,
            //     'total_items' => $totalItems,
            //     'failed'      => $failed,
            //     'threshold'   => $threshold,
            //     'hasSubmission' => $hasSubmission,
            //     'passed?'     => !$isRemedial,
            // ]);
        }

        // ===================================================
        // 🔥 EXISTING ACTIVITY LOGIC (unchanged)
        // ===================================================
        $activities = CourseLearnerActivity::where('user_id', $userId)
            ->get()
            ->keyBy('course_id');

        foreach ($enrollments as $enroll) {
            $courseId = $enroll->course->course_id ?? null;
            $activity = $activities[$courseId] ?? null;

            $enroll->is_opened   = $activity->is_opened ?? 0;
            $enroll->last_access = $activity->last_access ?? null;
            $enroll->progress    = $progress[$courseId] ?? 0;

            $enroll->start_formatted = $enroll->course->start_course
                ? Carbon::parse($enroll->course->start_course)->format('d M Y')
                : '-';

            $enroll->end_formatted = $enroll->course->end_course
                ? Carbon::parse($enroll->course->end_course)->format('d M Y')
                : '-';

            // Waktu periode
            $start = $enroll->course->start_course ?? null;
            $end   = $enroll->course->end_course ?? null;
            $now   = Carbon::now();

            $startDay = $start ? Carbon::parse($start)->startOfDay() : null;
            $endDay   = $end ? Carbon::parse($end)->endOfDay() : null;

            if ($startDay && $now->lt($startDay)) {
                $enroll->is_period_open = false;
                $enroll->period_message = 'Belum dimulai';
            }
            elseif ($endDay && $now->gt($endDay)) {
                $enroll->is_period_open = false;
                $enroll->period_message = 'Telah Berakhir';
            }
            else {
                $enroll->is_period_open = true;
                $enroll->period_message = 'Sedang berlangsung';
            }
        }

        // ===================================================
        // 📤 VIEW
        // ===================================================
        return view('learner.course', compact('enrollments'));
    }
    
    public function detailMyCourse($id)
    {
        $user = Auth::user();
        
        $userId = $user->user_id;

        // 🔹 Ambil course + validasi
        $course = Course::where('course_id', $id)->firstOrFail();

        // 🔹 Load module + relasi item lengkap
        $courseModules = CourseWeekModule::with([
            'items.essay' => function ($q) {
                $q->select(
                    'essay_id',
                    'item_id',
                    'essay_title',
                    'instruction',
                    'attachment_type',
                    'attachment_value'
                );
            },
            'items.essay.submissions.user',
            'items.forum',
            'items.questions.options',
            'items.attachment' => function ($q) {
                $q->select(
                    'submission_id',
                    'item_id',
                    'user_id',
                    'file_path',
                    'grade',
                    'feedback',
                    'submitted_at',
                    'is_remedial'
                )->with('user');
            }
        ])
        ->where('course_id', $id)
        ->orderBy('week_order', 'asc')
        ->get();
        
        $courseModules = $courseModules->sortBy('week_order')->values();

        foreach ($courseModules as $module) {
            $module->items = $module->items->sortBy('item_order')->values();
        }

        // 🔹 Default status tiap item
        // foreach ($courseModules as $module) {
        //     foreach ($module->items as $item) {
        //         $item->status_lock = 'not_started';
        //     }
        // }

        // ============================================
        // 🔥 MODULE-LEVEL LOCKING (Final Version)
        // ============================================

        // Module pertama selalu unlocked
        $previousModuleCompleted = true;

        foreach ($courseModules as $index => $module) {

            // Default lock/unlock based on previous module
            $module->status_module_lock = $previousModuleCompleted ? 'unlocked' : 'locked';

            // ==================================
            // 🔥 NEW: Jika module ini sudah pernah dimulai,
            // maka jangan lock lagi (meski refresh)
            // ==================================

            $moduleHasProgress = false;

            foreach ($module->items as $it) {

                // Cek progress dasar
                $p = CourseProgress::where('course_item_id', $it->item_id)
                    ->where('user_id', $userId)
                    ->exists();

                if ($p) { 
                    $moduleHasProgress = true; 
                    break; 
                }

                // Cek semua submission
                if (in_array($it->course_item_type, ['3','4','7'])) {

                    $submitted = match($it->course_item_type) {
                        '3' => CourseEssaySubmission::where('item_id', $it->item_id)
                                    ->where('user_id', $userId)
                                    ->exists(),
                        '4' => CourseMcSubmission::where('item_id', $it->item_id)
                                    ->where('user_id', $userId)
                                    ->exists(),
                        '7' => CourseAttachmentSubmission::where('item_id', $it->item_id)
                                    ->where('user_id', $userId)
                                    ->exists(),
                        default => false
                    };

                    if ($submitted) { 
                        $moduleHasProgress = true; 
                        break; 
                    }
                }
            }

            // Jika module sudah pernah dimulai → paksa UNLOCK
            if ($moduleHasProgress) {
                $module->status_module_lock = 'unlocked';
            }

            // ==================================
            // 🔥 CHECK KELENGKAPAN MODULE (asli)
            // untuk menentukan apakah module next unlocked
            // ==================================
            $allCompleted = true;

            foreach ($module->items as $item) {

                $hasProgress = CourseProgress::where('course_item_id', $item->item_id)
                                ->where('user_id', $userId)
                                ->exists();

                if (!$hasProgress) { 
                    $allCompleted = false; 
                }

                if (in_array($item->course_item_type, ['3','4','7'])) {

                    $hasFinal = match($item->course_item_type) {
                        '3' => CourseEssaySubmission::where('item_id', $item->item_id)
                                    ->where('user_id', $userId)
                                    ->where('is_remedial', 0)
                                    ->exists(),
                        '4' => CourseMcSubmission::where('item_id', $item->item_id)
                                    ->where('user_id', $userId)
                                    ->where('is_remedial', 0)
                                    ->exists(),
                        '7' => CourseAttachmentSubmission::where('item_id', $item->item_id)
                                    ->where('user_id', $userId)
                                    ->where('is_remedial', 0)
                                    ->exists(),
                    };

                    if (!$hasFinal) {
                        $allCompleted = false;
                    }
                }
            }

            // Tentukan apakah module selanjutnya unlocked
            $previousModuleCompleted = $allCompleted;
        }

        // ==========================================================
        // 🔥 SIMPAN / UPDATE AKTIVITAS USER (LOG BUKA COURSE)
        // ==========================================================
        $activity = CourseLearnerActivity::where('course_id', $course->course_id)
            ->where('user_id', $user->user_id)
            ->first();

        if ($activity) {
            // update waktu terakhir akses
            $activity->update([
                'is_opened' => 1,
                'last_access' => Carbon::now(),
                'person_process' => $user->full_name ?? $user->name,
            ]);
        } else {
            // buat record baru
            CourseLearnerActivity::create([
                'course_id' => $course->course_id,
                'user_id' => $user->user_id,
                'is_opened' => 1,
                'last_access' => Carbon::now(),
                'person_process' => $user->full_name ?? $user->name,
            ]);
        }

        // ==========================================================

        // \Log::info("Status Locking Debug", [
        //     'item_id' => $item->item_id,
        //     'lock_reason' => 'completed',
        //     'status_lock' => $item->status_lock,
        // ]);
        
        $progressCourse = CourseProgress::where('user_id', $userId)
            ->where('course_id', $course->course_id)
            ->max('progress_pct') ?? 0;
        
        $now = Carbon::now();
        $isCourseEnded = false;

        if ($course->end_course) {
            $isCourseEnded = Carbon::parse($course->end_course)->lt($now);
        }

        $previewMode = ($isCourseEnded);

        // $previewMode = ($isCourseEnded);

        if($previewMode) {
            foreach($courseModules as $module) {
                $module->status_module_lock = 'unlocked';
            }
        }
        
        return view("learner.course-enrolled", compact('course', 'courseModules','progressCourse', 'user', 'previewMode'));

    }
    public function essaySubmission (Request $request, $itemId) 
    {
        $request->validate([
            'essay_id' => 'required|integer',
            'item_id' => 'required|integer',
            'answer_text' => 'nullable|string',
        ]);

        $submission = CourseEssaySubmission::create([
            'essay_id' => $request->essay_id,
            'item_id' => $request->item_id,
            'user_id' => Auth::id(), // ambil user yang login
            'answer_text' => $request->answer_text,
            'is_graded' => false,
            'submitted_at' => now(),
        ]);

        return response()->json([
            'success' => true,
            'data' => $submission
        ]);
    }
    public function getEssaySubmissions($itemId)
    {
        $submissions = CourseEssaySubmission::with(['user'])
            ->where('item_id', $itemId)
            ->orderBy('submitted_at', 'desc')
            ->get([
                'essay_submission_id',
                'item_id',
                'user_id',
                'answer_text',
                'grade',
                'feedback',
                'is_remedial',
                'submitted_at'
            ]);

        return response()->json($submissions);
    }
    // public function mcSubmission(Request $request, $itemId)
    // {
    //     $request->validate([
    //         'questions' => 'nullable|array',
    //         'grade'     => 'nullable|numeric',
    //     ]);

    //     $userId = Auth::id();

    //     // Hitung attempt user sebelumnya
    //     $attemptCount = CourseMcSubmission::where('item_id', $itemId)
    //         ->where('user_id', $userId)
    //         ->count();

    //     // Simpan submission baru
    //     $submission = CourseMcSubmission::create([
    //         'item_id'      => $itemId,
    //         'user_id'      => $userId,
    //         'questions'    => $request->questions,  // otomatis encode JSON
    //         'grade'        => $request->grade,
    //         'submitted_at' => now(),
    //         'attempt_no'   => $attemptCount + 1,
    //     ]);

    //     // Ambil semua history percobaan user pada item ini
    //     $history = CourseMcSubmission::where('item_id', $itemId)
    //         ->where('user_id', $userId)
    //         ->orderBy('attempt_no', 'asc')
    //         ->get()
    //         ->map(function ($s) {
    //             return [
    //                 'attempt_no'   => $s->attempt_no,
    //                 'grade'        => $s->grade,
    //                 'is_remedial'  => $s->is_remedial, 
    //                 'submitted_at' => $s->submitted_at ? $s->submitted_at->format('d M Y H:i') : null,
    //             ];
    //         });

    //     return response()->json([
    //         'success' => true,
    //         'data'    => $submission,
    //         'attempt' => $submission->attempt_no,
    //         'history' => $history,
    //     ]);
    // }
    
    // Active this code after everthing clear, and Mc Above will commented: 
    public function mcSubmission(Request $request, $itemId)
    {
        $request->validate([
            'questions' => 'nullable|array',
            'grade'     => 'nullable|numeric',
        ]);

        $userId    = Auth::id();
        $requestId = (string) Str::uuid(); // 🔑 tracing id

        // Log::info('📥 [MC_SUBMIT][INCOMING]', [
        //     'request_id' => $requestId,
        //     'item_id'    => $itemId,
        //     'user_id'    => $userId,
        //     'ip'         => $request->ip(),
        //     'ua'         => $request->userAgent(),
        //     'payload'    => $request->all(),
        // ]);

        return DB::transaction(function () use ($request, $itemId, $userId, $requestId) {

            // Log::info('🔒 [MC_SUBMIT][LOCK_ATTEMPT]', [
            //     'request_id' => $requestId,
            //     'item_id'    => $itemId,
            //     'user_id'    => $userId,
            // ]);

            // 🔒 Lock submission row (race-condition safe)
            $latest = CourseMcSubmission::where('item_id', $itemId)
                ->where('user_id', $userId)
                ->orderByDesc('attempt_no')
                ->lockForUpdate()
                ->first();

            // Log::info('🔓 [MC_SUBMIT][LOCK_ACQUIRED]', [
            //     'request_id' => $requestId,
            //     'latest_attempt' => $latest?->attempt_no,
            //     'latest_time'    => $latest?->submitted_at,
            // ]);

            // ⛔ Block duplicate (window 5 detik)
            if ($latest && $latest->submitted_at->diffInSeconds(now()) < 5) {

                // Log::warning('⛔ [MC_SUBMIT][DUPLICATE_BLOCKED]', [
                //     'request_id' => $requestId,
                //     'blocked_attempt' => $latest->attempt_no,
                //     'submitted_at'    => $latest->submitted_at,
                // ]);

                return response()->json([
                    'success' => true,
                    'message' => 'Duplicate submission ignored',
                    'attempt' => $latest->attempt_no,
                ]);
            }

            $attemptNo = $latest ? $latest->attempt_no + 1 : 1;

            $submission = CourseMcSubmission::create([
                'item_id'      => $itemId,
                'user_id'      => $userId,
                'questions'    => $request->questions,
                'grade'        => $request->grade,
                'submitted_at' => now(),
                'attempt_no'   => $attemptNo,
            ]);

            // Log::info('✅ [MC_SUBMIT][CREATED]', [
            //     'request_id' => $requestId,
            //     'attempt_no' => $attemptNo,
            //     'submission_id' => $submission->id,
            // ]);

            $history = CourseMcSubmission::where('item_id', $itemId)
                ->where('user_id', $userId)
                ->orderBy('attempt_no', 'asc')
                ->get()
                ->map(function ($s) {
                    return [
                        'attempt_no'   => $s->attempt_no,
                        'grade'        => $s->grade,
                        'is_remedial'  => $s->is_remedial,
                        'submitted_at' => $s->submitted_at,
                    ];
                });

            return response()->json([
                'success' => true,
                'data'    => $submission,
                'attempt' => $attemptNo,
                'history' => $history,
            ]);
        });
    }
    
    public function getMcSubmission($itemId)
    {
        $userId = Auth::id();

        $submissions = CourseMcSubmission::where('item_id', $itemId)
            ->where('user_id', $userId)
            ->orderBy('attempt_no', 'asc')
            ->get()
            ->map(function ($s) {
                return [
                    'attempt_no'   => $s->attempt_no,
                    'grade'        => $s->grade,
                    'is_remedial'  => $s->is_remedial,
                    'submitted_at' => $s->submitted_at ? $s->submitted_at->format('d M Y H:i') : null,
                ];
            });

        if ($submissions->isNotEmpty()) {
            return response()->json([
                'exists'     => true,
                'last_grade' => $submissions->last()['grade'] ?? 0,
                'attempts'   => $submissions,
            ]);
        }

        return response()->json(['exists' => false]);
    }
    public function storeForumReply(Request $request, $forumId)
    {
        $request->validate([
            'reply_text' => 'required|string',
            'parent_id' => 'nullable|integer|exists:course_item_forum_replies,reply_id',
        ]);

        $reply = CourseForumDiscussionReply::create([
            'forum_id'   => $forumId,
            'user_id'    => Auth::id(),
            'reply_text' => $request->reply_text,
            'parent_id'  => $request->parent_id, // bisa null
        ]);

        return response()->json([
            'success' => true,
            'data'    => $reply->load('user'),
        ]);
    }
    public function getForumDiscussion($forumId)
    {
        $forum = CourseForumDiscussionReply::with(['user', 'children'])
            ->where('forum_id', $forumId)
            ->whereNull('parent_id') // hanya ambil root, children ikut otomatis
            ->orderBy('created_at', 'desc')
            ->get();

        return response()->json($forum);
    }
    // public function uploadSubmission(Request $request, $itemId)
    // {
    //     Log::info('📥 Upload request diterima', [
    //         'item_id' => $itemId,
    //         'user_id' => auth()->id(),
    //         'filesize' => $request->file('task_file') ? $request->file('task_file')->getSize() : null,
    //     ]);

    //     try {
    //         // ===============================
    //         // 1️⃣ Validasi File
    //         // ===============================
    //         $request->validate([
    //             'task_file' => 'nullable|mimes:pdf,doc,docx,ppt,pptx,xls,xlsx,zip,jpg,png|max:102400', // 100MB
    //         ]);
    //         Log::info('✅ Validasi berhasil.');

    //         // ===============================
    //         // 2️⃣ Proses Upload (optional)
    //         // ===============================
    //         $file = $request->file('task_file');
    //         $filePath = null;

    //         if ($file) {
    //             $filename = time().'_'.$file->getClientOriginalName();
    //             $destination = public_path('assets/file');

    //             Log::info('📂 Siap memindahkan file...', [
    //                 'filename' => $filename,
    //                 'destination' => $destination,
    //             ]);

    //             if (!file_exists($destination)) {
    //                 mkdir($destination, 0775, true);
    //                 Log::info('📁 Folder upload dibuat otomatis.');
    //             }

    //             $file->move($destination, $filename);
    //             $filePath = 'assets/file/'.$filename;

    //             Log::info('✅ File berhasil dipindahkan ke folder tujuan.');
    //         } else {
    //             Log::info('⚠️ Tidak ada file yang diunggah. Melanjutkan tanpa attachment.');
    //         }

    //         // ===============================
    //         // 3️⃣ Simpan ke Database
    //         // ===============================
    //         $submission = CourseAttachmentSubmission::create([
    //             'user_id'   => auth()->id(),
    //             'item_id'   => $itemId,
    //             'file_path' => $filePath, // null kalau tidak ada file
    //         ]);

    //         Log::info('✅ Data submission disimpan ke database.', ['submission_id' => $submission->id]);

    //         // ===============================
    //         // 4️⃣ Return Response JSON
    //         // ===============================
    //         return response()->json([
    //             'success' => true,
    //             'message' => $file
    //                 ? 'Tugas berhasil diunggah.'
    //                 : 'Tugas berhasil disimpan tanpa lampiran.',
    //             'submission' => $submission
    //         ]);

    //     } catch (\Illuminate\Validation\ValidationException $e) {
    //         Log::error('❌ Validasi gagal.', ['errors' => $e->errors()]);
    //         return response()->json(['success' => false, 'errors' => $e->errors()]);
    //     } catch (\Exception $e) {
    //         Log::error('💥 Terjadi error saat upload.', ['exception' => $e->getMessage()]);
    //         return response()->json([
    //             'success' => false,
    //             'message' => 'Terjadi kesalahan saat mengunggah file.',
    //             'error'   => $e->getMessage()
    //         ]);
    //     }
    // }
    
    public function uploadSubmission(Request $request, $itemId)
    {
        Log::info('📥 Upload request diterima', [
            'item_id' => $itemId,
            'user_id' => auth()->id(),
            'filesize' => $request->file('task_file') 
                ? $request->file('task_file')->getSize() 
                : null,
        ]);

        try {
            // ===============================
            // 1️⃣ VALIDASI FILE (max: 100 MB)
            // ===============================
            $request->validate([
                'task_file' => [
                    'nullable',
                    'mimes:pdf,doc,docx,ppt,pptx,xls,xlsx,zip,jpg,png',
                    'max:102400', // 100Mb
                ]
            ], [
                'task_file.max'   => 'Melebihi batas maksimal file.',
                'task_file.mimes' => 'Format file tidak diizinkan.'
            ]);

            Log::info('✅ Validasi berhasil.');

            // ===============================
            // 2️⃣ PROSES UPLOAD
            // ===============================
            $file = $request->file('task_file');
            $filePath = null;

            if ($file) {
                $filename = time() . '_' . $file->getClientOriginalName();
                $destination = public_path('assets/file');

                if (!file_exists($destination)) {
                    mkdir($destination, 0775, true);
                }

                $file->move($destination, $filename);
                $filePath = 'assets/file/' . $filename;
            }

            // ===============================
            // 3️⃣ SIMPAN DB
            // ===============================
            $submission = CourseAttachmentSubmission::create([
                'user_id'   => auth()->id(),
                'item_id'   => $itemId,
                'file_path' => $filePath,
            ]);

            return response()->json([
                'success' => true,
                'message' => $file
                    ? 'Tugas berhasil diunggah.'
                    : 'Tugas berhasil disimpan tanpa lampiran.',
                'submission' => $submission
            ]);

        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Validasi gagal.',
                'errors'  => $e->errors()
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Terjadi kesalahan saat mengunggah file.',
                'error'   => $e->error()['task_file'][0] ?? 'Validasi Gagal'
            ]);
        }
    }
    
    // Sprint I 
    public function getCourseItemLocks($courseId)
    {
        $userId = Auth::id();

        // Ambil semua item di course ini
        $items = CourseWeekItem::where('course_id', $courseId)->get();

        $result = $items->map(function ($item) use ($userId) {
            $status = 'unlocked';
            $now = now();

            // ===============================
            // 1️⃣ Logika start/end utama modul
            // ===============================

            if (is_null($item->course_start) && is_null($item->course_end)) {
                // Tidak ada batas — selalu terbuka
                $status = 'unlocked';
            } elseif (is_null($item->course_start) && $item->course_end) {
                // Tanpa start — terbuka sampai end
                $status = $now->lte($item->course_end) ? 'unlocked' : 'locked_after_end';
            } elseif ($item->course_start && is_null($item->course_end)) {
                // Tanpa end — terbuka mulai start selamanya
                $status = $now->gte($item->course_start) ? 'unlocked' : 'locked_before_start';
            } else {
                // Keduanya ada
                if ($now->lt($item->course_start)) {
                    $status = 'locked_before_start';
                } elseif ($now->gt($item->course_end)) {
                    $status = 'locked_after_end';
                }
            }

            // ===============================
            // 2️⃣ Logika tanggal due untuk item
            // ===============================
            if ($item->course_due_start || $item->course_due_end) {
                if (is_null($item->course_due_start) && is_null($item->course_due_end)) {
                    // tidak ada due lock
                } elseif (is_null($item->course_due_start) && $item->course_due_end) {
                    // tanpa due start — terbuka sampai due end
                    if ($now->gt($item->course_due_end)) $status = 'locked_item_after';
                } elseif ($item->course_due_start && is_null($item->course_due_end)) {
                    // tanpa due end — terbuka mulai due start
                    if ($now->lt($item->course_due_start)) $status = 'locked_item_before';
                } else {
                    // dua-duanya ada
                    if ($now->lt($item->course_due_start)) {
                        $status = 'locked_item_before';
                    } elseif ($now->gt($item->course_due_end)) {
                        $status = 'locked_item_after';
                    }
                }
            }

            // ===============================
            // 3️⃣ Logging untuk debug
            // ===============================
            Log::info('[LOCK CHECK]', [
                'item_id'   => $item->item_id,
                'start'     => $item->course_start,
                'end'       => $item->course_end,
                'due_start' => $item->course_due_start,
                'due_end'   => $item->course_due_end,
                'status'    => $status,
                'now'       => $now->toDateTimeString(),
            ]);

            return [
                'item_id' => $item->item_id,
                'status'  => $status,
            ];
        });

        return response()->json([
            'success' => true,
            'items'   => $result,
        ]);
    }
    // public function savedProgress(Request $request)
    // {
    //     $userId = Auth::id();

    //     $request->validate([
    //         'course_id'          => 'required|integer',
    //         'course_week_id'     => 'required|integer',
    //         'course_item_id'     => 'required|integer',
    //         'current_step_module'=> 'required|integer',
    //         'current_step_item'  => 'required|integer',
    //     ]);

    //      // hitung total modul & total item
    //     $courseId = $request->course_id;
    //     $totalModule = CourseWeekModule::where('course_id', $courseId)->count();
    //     $totalItem   = CourseWeekItem::where('course_id', $courseId)->count();

    //     // hitung sudah berapa item yg selesai oleh user
    //     $totalChecked = CourseProgress::where('user_id', $userId)
    //         ->where('course_id', $courseId)
    //         ->distinct('course_item_id')
    //         ->count('course_item_id');

    //     // hitung persentase
    //     $progressPct = $totalItem > 0 ? round(($totalChecked / $totalItem) * 100, 2) : 0;

    //     // status
    //     $statusCourse = $progressPct >= 100;
    //     $statusItem   = true;

    //     $progress = CourseProgress::updateOrCreate(
    //     [
    //         'user_id'   => $userId,
    //         'course_id' => $courseId,
    //         'course_item_id' => $request->course_item_id,
    //     ],
    //     [
    //         'course_week_id'      => $request->course_week_id,
    //         'total_module'        => $totalModule,
    //         'total_item'          => $totalItem,
    //         'total_checked'       => $totalChecked,
    //         'progress_pct'        => $progressPct,
    //         'current_step_module' => $request->current_step_module,
    //         'current_step_item'   => $request->current_step_item,
    //         'status_course'       => $statusCourse,
    //         'status_item'         => $statusItem,
    //         'last_update'         => now(),
    //     ]
    // );
    //     return response()->json([
    //         'success'  => true,
    //         'progress' => $progress
    //     ]);    
    // }
    // ============================
     public function savedProgress(Request $request)
        {
            $userId = Auth::id();

            $request->validate([
                'course_id'           => 'required|integer',
                'course_week_id'      => 'required|integer',
                'course_item_id'      => 'required|integer',
                'current_step_module' => 'required|integer',
                'current_step_item'   => 'required|integer',
            ]);

            // Log::info('[LMS] savedProgress CALLED', [
            //     'user_id' => $userId,
            //     'payload' => $request->all(),
            // ]);

            return DB::transaction(function () use ($request, $userId) {

                $courseId = $request->course_id;

                // ===============================
                // 1️⃣ TOTAL DATA MASTER
                // ===============================
                $totalModule = CourseWeekModule::where('course_id', $courseId)->count();
                $totalItem   = CourseWeekItem::where('course_id', $courseId)->count();

                // ===============================
                // 2️⃣ HITUNG SEBELUM SAVE (DEBUG)
                // ===============================
                $beforeChecked = CourseProgress::where('user_id', $userId)
                    ->where('course_id', $courseId)
                    ->distinct('course_item_id')
                    ->count('course_item_id');

                // Log::info('[LMS] BEFORE SAVE', [
                //     'course_id'     => $courseId,
                //     'total_item'    => $totalItem,
                //     'total_checked' => $beforeChecked,
                // ]);

                // ===============================
                // 3️⃣ SAVE / UPDATE ITEM PROGRESS
                // ===============================
                $progress = CourseProgress::updateOrCreate(
                    [
                        'user_id'        => $userId,
                        'course_id'      => $courseId,
                        'course_item_id' => $request->course_item_id,
                    ],
                    [
                        'course_week_id'      => $request->course_week_id,
                        'current_step_module' => $request->current_step_module,
                        'current_step_item'   => $request->current_step_item,
                        'status_item'         => true,
                        'last_update'         => now(),
                    ]
                );

                // Log::info('[LMS] ITEM SAVED', [
                //     'progress_id'    => $progress->id,
                //     'course_item_id' => $progress->course_item_id,
                // ]);

                // ===============================
                // 4️⃣ HITUNG ULANG SETELAH SAVE
                // ===============================
                $fixedTotalChecked = CourseProgress::where('user_id', $userId)
                    ->where('course_id', $courseId)
                    ->distinct('course_item_id')
                    ->count('course_item_id');

                $fixedProgressPct = $totalItem > 0
                    ? round(($fixedTotalChecked / $totalItem) * 100, 2)
                    : 0;

                $fixedStatusCourse = $fixedTotalChecked >= $totalItem;

                // Log::info('[LMS] AFTER RECALC', [
                //     'fixed_total_checked' => $fixedTotalChecked,
                //     'total_item'          => $totalItem,
                //     'progress_pct'        => $fixedProgressPct,
                //     'status_course'       => $fixedStatusCourse,
                // ]);

                // ===============================
                // 5️⃣ SYNC SEMUA ROW COURSE
                // ===============================
                CourseProgress::where('user_id', $userId)
                    ->where('course_id', $courseId)
                    ->update([
                        'total_module'  => $totalModule,
                        'total_item'    => $totalItem,
                        'total_checked' => $fixedTotalChecked,
                        'progress_pct'  => $fixedProgressPct,
                        'status_course' => $fixedStatusCourse,
                    ]);

                // ===============================
                // 6️⃣ RESPONSE KE FRONTEND
                // ===============================
                $progress->total_module  = $totalModule;
                $progress->total_item    = $totalItem;
                $progress->total_checked = $fixedTotalChecked;
                $progress->progress_pct  = $fixedProgressPct;
                $progress->status_course = $fixedStatusCourse;

                // Log::info('[LMS] RESPONSE SENT', [
                //     'progress_pct'   => $progress->progress_pct,
                //     'status_course'  => $progress->status_course,
                // ]);

                return response()->json([
                    'success'  => true,
                    'progress' => $progress,
                ]);
            });
    }
    // ============================
    public function getCourseProgress($courseId)
    {
        $userId = auth()->id();

        $progressItems = CourseProgress::where('user_id', $userId)
            ->where('course_id', $courseId)
            ->pluck('course_item_id')
            ->toArray();

        return response()->json([
            'success' => true,
            'items'   => $progressItems,
        ]);
    }
    // Use this Back-up
    // public function checkSubmission($itemId)
    // {
    //     $userId = Auth::id();
    //     $item   = CourseWeekItem::findOrFail($itemId);

    //     // SANITASI TIPE ITEM
    //     $cleanType = trim((string)$item->course_item_type);
    //     $cleanType = preg_replace('/[^0-9]/', '', $cleanType);

    //     $exists = false;
    //     $submittedAt = null;
    //     $isRemedial = 0;
    //     $grade = null;
    //     $passingGrade = $item->passing_grade ?? null;

    //     // Default (supaya tidak undefined)
    //     $gradePassed = true;
    //     $allCompleted = true;

    //     // =============================
    //     // 🔍 LOG ITEM awal
    //     // =============================
    //     // \Log::info("🔎 CHECK SUBMISSION START", [
    //     //     'itemId'        => $itemId,
    //     //     'type_raw'      => $item->course_item_type,
    //     //     'type_clean'    => $cleanType,
    //     //     'userId'        => $userId,
    //     //     'passing_grade' => $passingGrade,
    //     // ]);

    //     // ===================================================
    //     // 🔵 PER ITEM VALIDATION (ESSAY / MCQ / ATTACHMENT)
    //     // ===================================================
    //     switch ($cleanType) {

    //         // ======================================
    //         // 📝 CASE 3 - ESSAY
    //         // ======================================
    //         case '3':
    //             // \Log::info("📝 CHECK ESSAY", ['itemId' => $itemId]);

    //             $submission = CourseEssaySubmission::where('item_id', $itemId)
    //                 ->where('user_id', $userId)
    //                 ->latest('created_at')
    //                 ->first();

    //             // \Log::info("📝 ESSAY SUBMISSION FETCHED", [
    //             //     'exists' => (bool)$submission,
    //             //     'data'   => $submission ? $submission->toArray() : null
    //             // ]);

    //             if ($submission) {
    //                 $exists      = true;
    //                 $submittedAt = $submission->created_at;
    //                 $isRemedial  = $submission->is_remedial ?? 0;
    //                 $grade       = $submission->grade;
    //             }
    //             break;

    //         // ======================================
    //         // ❓ CASE 4 - MCQ
    //         // ======================================
    //         case '4':
    //             // \Log::info("❓ CHECK MCQ", ['itemId' => $itemId]);

    //             $submissions = CourseMcSubmission::where('item_id', $itemId)
    //                 ->where('user_id', $userId)
    //                 ->orderBy('created_at', 'asc')
    //                 ->get();

    //             // \Log::info("❓ MCQ SUBMISSIONS FETCHED", [
    //             //     'total'       => $submissions->count(),
    //             //     'submissions' => $submissions->toArray(),
    //             // ]);

    //             if ($submissions->isNotEmpty()) {

    //                 $hasFinal       = $submissions->contains('is_remedial', 0);
    //                 $latestFinal    = $submissions->where('is_remedial', 0)->last();
    //                 $latestRemedial = $submissions->where('is_remedial', '!=', 0)->last();

    //                 // \Log::info("❓ MCQ PROCESS", [
    //                 //     'hasFinal'        => $hasFinal,
    //                 //     'latestFinal'     => $latestFinal ? $latestFinal->toArray() : null,
    //                 //     'latestRemedial'  => $latestRemedial ? $latestRemedial->toArray() : null,
    //                 // ]);

    //                 if ($hasFinal && $latestFinal) {
    //                     $exists      = true;
    //                     $submittedAt = $latestFinal->created_at;
    //                     $isRemedial  = 0;
    //                     $grade       = $latestFinal->grade;
    //                 } elseif ($latestRemedial) {
    //                     $exists      = false;
    //                     $submittedAt = $latestRemedial->created_at;
    //                     $isRemedial  = $latestRemedial->is_remedial ?? 1;
    //                     $grade       = $latestRemedial->grade;
    //                 }
    //             }
    //             break;

    //         // ======================================
    //         // 📂 CASE 7 - ATTACHMENT
    //         // ======================================
    //         case '7':
    //             // \Log::info("📂 CHECK ATTACHMENT", ['itemId' => $itemId]);

    //             $submissions = CourseAttachmentSubmission::where('item_id', $itemId)
    //                 ->where('user_id', $userId)
    //                 ->orderBy('submitted_at', 'asc')
    //                 ->get();

    //             // \Log::info("📂 ATTACHMENT SUBMISSIONS FETCHED", [
    //             //     'total'       => $submissions->count(),
    //             //     'submissions' => $submissions->toArray(),
    //             // ]);

    //             if ($submissions->isNotEmpty()) {

    //                 $hasFinal       = $submissions->contains('is_remedial', 0);
    //                 $latestFinal    = $submissions->where('is_remedial', 0)->last();
    //                 $latestRemedial = $submissions->where('is_remedial', '!=', 0)->last();

    //                 // \Log::info("📂 ATTACHMENT PROCESS", [
    //                 //     'hasFinal'        => $hasFinal,
    //                 //     'latestFinal'     => $latestFinal ? $latestFinal->toArray() : null,
    //                 //     'latestRemedial'  => $latestRemedial ? $latestRemedial->toArray() : null,
    //                 // ]);

    //                 if ($hasFinal && $latestFinal) {
    //                     $exists      = true;
    //                     $submittedAt = $latestFinal->submitted_at;
    //                     $isRemedial  = 0;
    //                     $grade       = $latestFinal->grade;
    //                 } elseif ($latestRemedial) {
    //                     $exists      = false;
    //                     $submittedAt = $latestRemedial->submitted_at;
    //                     $isRemedial  = $latestRemedial->is_remedial ?? 1;
    //                     $grade       = $latestRemedial->grade;
    //                 }
    //             }
                
    //             $attachments = CourseAttachmentSubmission::where('item_id', $itemId)
    //                 ->where('user_id', $userId)
    //                 ->orderBy('submitted_at', 'asc')
    //                 ->get();
                    
    //             break;

    //         default:
    //             // \Log::warning("⚠️ UNKNOWN ITEM TYPE", [
    //             //     'type_raw'   => $item->course_item_type,
    //             //     'type_clean' => $cleanType
    //             // ]);
    //     }

    //     // ===================================================
    //     // 🔥 GRADE PASSED (TIDAK UNDEFINED)
    //     // ===================================================
    //     if (!is_null($passingGrade) && !is_null($grade)) {
    //         $gradePassed = $grade >= $passingGrade;
    //     }

    //     // ===================================================
    //     // 🔥 FULL COURSE VALIDATION (Optional: based on last code)
    //     // ===================================================
    //     if (true) { // toggle on/off
    //         $courseId = $item->course_id;
    //         $allItems = CourseWeekItem::where('course_id', $courseId)->get();

    //         foreach ($allItems as $ci) {

    //             $ciType = trim(preg_replace('/[^0-9]/', '', (string)$ci->course_item_type));

    //             if (!in_array($ciType, ['3','4','7'])) {
    //                 continue;
    //             }

    //             // fetch submission
    //             $finalSubmission = match($ciType) {
    //                 '3' => CourseEssaySubmission::where('item_id', $ci->item_id)
    //                                             ->where('user_id', $userId)
    //                                             ->where('is_remedial', 0)
    //                                             ->latest('created_at')
    //                                             ->first(),

    //                 '4' => CourseMcSubmission::where('item_id', $ci->item_id)
    //                                         ->where('user_id', $userId)
    //                                         ->where('is_remedial', 0)
    //                                         ->latest('created_at')
    //                                         ->first(),

    //                 '7' => CourseAttachmentSubmission::where('item_id', $ci->item_id)
    //                                                 ->where('user_id', $userId)
    //                                                 ->where('is_remedial', 0)
    //                                                 ->orderBy('submitted_at', 'desc') // ✔ FIX HERE
    //                                                 ->first(),
    //             };

    //             // \Log::info("📌 CHECK COURSE ITEM", [
    //             //     'item_id' => $ci->item_id,
    //             //     'type_clean' => $ciType,
    //             //     'has_final' => (bool)$finalSubmission,
    //             //     'grade' => $finalSubmission->grade ?? null,
    //             //     'passing_grade' => $ci->passing_grade,
    //             // ]);

    //             if (!$finalSubmission) {
    //                 $allCompleted = false;
    //                 break;
    //             }

    //             if (!is_null($ci->passing_grade) && !is_null($finalSubmission->grade)) {
    //                 if ($finalSubmission->grade < $ci->passing_grade) {
    //                     $allCompleted = false;
    //                     break;
    //                 }
    //             }
    //         }
    //     }

    //     // =====================================================
    //     // 🔍 LOG FINAL OUTPUT
    //     // =====================================================
    //     // \Log::info("✅ FINAL CHECK SUBMISSION RESULT", [
    //     //     'itemId'        => $itemId,
    //     //     'exists'        => $exists,
    //     //     'is_remedial'   => $isRemedial,
    //     //     'submitted_at'  => (string)$submittedAt,
    //     //     'grade'         => $grade,
    //     //     'passing_grade' => $passingGrade,
    //     //     'grade_passed'  => $gradePassed,
    //     //     'all_completed' => $allCompleted,
    //     // ]);

    //     // =====================================================
    //     // 🔄 FINAL RESPONSE
    //     // =====================================================
    //     return response()->json([
    //         'exists'        => $exists,
    //         'is_remedial'   => $isRemedial,
    //         'submitted_at'  => $submittedAt ? \Carbon\Carbon::parse($submittedAt)->toDateTimeString() : null,
    //         'grade'         => $grade,
    //         'passing_grade' => $passingGrade,
    //         'grade_passed'  => $gradePassed,
    //         'all_completed' => $allCompleted,
    //         'attachments'   => $attachments ?? [],

    //     ]);
    // }
    public function checkSubmission($itemId)
    {
        $userId = Auth::id();
        $item   = CourseWeekItem::findOrFail($itemId);
    
        $cleanType = trim((string)$item->course_item_type);
        $cleanType = preg_replace('/[^0-9]/', '', $cleanType);
    
        $exists        = false;
        $submittedAt   = null;
        $isRemedial    = 0;
        $grade         = null;
        $passingGrade  = $item->passing_grade ?? null;
        $gradePassed   = true;
        $allCompleted  = true;
        $attachments   = [];
    
        // \Log::info("🔎 CHECK SUBMISSION START", [
        //     'itemId'        => $itemId,
        //     'type_clean'    => $cleanType,
        //     'userId'        => $userId,
        //     'passing_grade' => $passingGrade,
        // ]);
    
        // ===================================================
        // 🔵 PER ITEM CHECK
        // ===================================================
        switch ($cleanType) {
    
            case '3':
                $submission = CourseEssaySubmission::where('item_id', $itemId)
                    ->where('user_id', $userId)
                    ->where('is_remedial', 0)
                    ->whereNotNull('grade')
                    ->orderByDesc('grade')
                    ->first();
                break;
    
            case '4':
                $submission = CourseMcSubmission::where('item_id', $itemId)
                    ->where('user_id', $userId)
                    ->where('is_remedial', 0)
                    ->whereNotNull('grade')
                    ->orderByDesc('grade')
                    ->first();
                break;
    
            case '7':
                $submission = CourseAttachmentSubmission::where('item_id', $itemId)
                    ->where('user_id', $userId)
                    ->where('is_remedial', 0)
                    ->whereNotNull('grade')
                    ->orderByDesc('grade')
                    ->first();
    
                $attachments = CourseAttachmentSubmission::where('item_id', $itemId)
                    ->where('user_id', $userId)
                    ->orderBy('submitted_at', 'asc')
                    ->get();
                break;
    
            default:
                $submission = null;
        }
    
        if (!empty($submission)) {
            $exists      = true;
            $submittedAt = $submission->created_at ?? $submission->submitted_at ?? null;
            $isRemedial  = $submission->is_remedial ?? 0;
            $grade       = $submission->grade;
        }
    
        if (!is_null($passingGrade) && !is_null($grade)) {
            $gradePassed = $grade >= $passingGrade;
        }
    
        // ===================================================
        // 🔥 FULL COURSE VALIDATION (ANTI-CELAH VERSION)
        // ===================================================
    
        $courseId = $item->course_id;
    
        $allItems = CourseWeekItem::where('course_id', $courseId)->get();
    
        $progressItemIds = CourseProgress::where('course_id', $courseId)
            ->where('user_id', $userId)
            ->pluck('course_item_id')
            ->toArray();
    
        foreach ($allItems as $ci) {
    
            $ciType = trim(preg_replace('/[^0-9]/', '', (string)$ci->course_item_type));
    
            // ==========================================
            // 1️⃣ NON SUBMISSION TYPE → WAJIB PROGRESS
            // ==========================================
            if (!in_array($ciType, ['3','4','7'])) {
    
                if (!in_array($ci->item_id, $progressItemIds)) {
                    // \Log::warning("❌ FAILED: Non-submission progress missing", [
                    //     'item_id' => $ci->item_id
                    // ]);
                    $allCompleted = false;
                    break;
                }
    
                continue;
            }
    
            // ==========================================
            // 2️⃣ SUBMISSION TYPE → WAJIB FINAL SUBMISSION
            // ==========================================
            $finalSubmission = match($ciType) {
                '3' => CourseEssaySubmission::where('item_id', $ci->item_id)
                                        ->where('user_id', $userId)
                                        ->where('is_remedial', 0)
                                        ->orderByDesc('grade')
                                        ->first(),

                '4' => CourseMcSubmission::where('item_id', $ci->item_id)
                                        ->where('user_id', $userId)
                                        ->where('is_remedial', 0)
                                        ->orderByDesc('grade')
                                        ->first(),
    
                '7' => CourseAttachmentSubmission::where('item_id', $ci->item_id)
                                                ->where('user_id', $userId)
                                                ->where('is_remedial', 0)
                                                ->orderByDesc('grade')
                                                ->first(),
            };
    
            if (!$finalSubmission) {
                // \Log::warning("❌ FAILED: Submission missing", [
                //     'item_id' => $ci->item_id
                // ]);
                $allCompleted = false;
                break;
            }
    
            // Passing grade check
            if (!is_null($ci->passing_grade)) {
    
                if (is_null($finalSubmission->grade)) {
                    // \Log::warning("❌ FAILED: Grade not evaluated", [
                    //     'item_id' => $ci->item_id
                    // ]);
                    $allCompleted = false;
                    break;
                }
    
                if ($finalSubmission->grade < $ci->passing_grade) {
                    // \Log::warning("❌ FAILED: Grade below passing", [
                    //     'item_id' => $ci->item_id,
                    //     'grade'   => $finalSubmission->grade,
                    //     'passing' => $ci->passing_grade
                    // ]);
                    $allCompleted = false;
                    break;
                }
            }
        }
    
        // \Log::info("✅ FINAL CHECK SUBMISSION RESULT", [
        //     'exists'        => $exists,
        //     'is_remedial'   => $isRemedial,
        //     'grade'         => $grade,
        //     'grade_passed'  => $gradePassed,
        //     'all_completed' => $allCompleted,
        // ]);
    
        return response()->json([
            'exists'        => $exists,
            'is_remedial'   => $isRemedial,
            'submitted_at'  => $submittedAt ? \Carbon\Carbon::parse($submittedAt)->toDateTimeString() : null,
            'grade'         => $grade,
            'passing_grade' => $passingGrade,
            'grade_passed'  => $gradePassed,
            'all_completed' => $allCompleted,
            'attachments'   => $attachments ?? [],
        ]);
    }
    
    
    // Modified 1
    // public function checkSubmission($itemId)
    //     {
    //         $userId = Auth::id();
    //         $item   = CourseWeekItem::findOrFail($itemId);

    //         // =============================
    //         // SANITASI TIPE ITEM
    //         // =============================
    //         $cleanType = preg_replace('/[^0-9]/', '', (string)$item->course_item_type);

    //         $exists        = false;
    //         $submittedAt  = null;
    //         $isRemedial   = 0;
    //         $grade        = null;
    //         $passingGrade = $item->passing_grade ?? null;

    //         $gradePassed  = true;
    //         $allCompleted = true;
    //         $attachments  = [];

    //         // ===================================================
    //         // 🔵 PER ITEM SUBMISSION CHECK
    //         // ===================================================
    //         switch ($cleanType) {

    //             // =============================
    //             // 📝 ESSAY
    //             // =============================
    //             case '3':
    //                 $submission = CourseEssaySubmission::where('item_id', $itemId)
    //                     ->where('user_id', $userId)
    //                     ->latest('created_at')
    //                     ->first();

    //                 if ($submission) {
    //                     $exists      = true;
    //                     $submittedAt = $submission->created_at;
    //                     $isRemedial  = $submission->is_remedial ?? 0;
    //                     $grade       = $submission->grade;
    //                 }
    //                 break;

    //             // =============================
    //             // ❓ MCQ
    //             // =============================
    //             case '4':
    //                 $final = CourseMcSubmission::where('item_id', $itemId)
    //                     ->where('user_id', $userId)
    //                     ->where('is_remedial', 0)
    //                     ->latest('created_at')
    //                     ->first();

    //                 if ($final) {
    //                     $exists      = true;
    //                     $submittedAt = $final->created_at;
    //                     $isRemedial  = 0;
    //                     $grade       = $final->grade;
    //                 }
    //                 break;

    //             // =============================
    //             // 📂 ATTACHMENT
    //             // =============================
    //             case '7':
    //                 $final = CourseAttachmentSubmission::where('item_id', $itemId)
    //                     ->where('user_id', $userId)
    //                     ->where('is_remedial', 0)
    //                     ->orderBy('submitted_at', 'desc')
    //                     ->first();

    //                 if ($final) {
    //                     $exists      = true;
    //                     $submittedAt = $final->submitted_at;
    //                     $isRemedial  = 0;
    //                     $grade       = $final->grade;
    //                 }

    //                 $attachments = CourseAttachmentSubmission::where('item_id', $itemId)
    //                     ->where('user_id', $userId)
    //                     ->orderBy('submitted_at', 'asc')
    //                     ->get();
    //                 break;
    //         }

    //         // =============================
    //         // 🔥 GRADE PASSED
    //         // =============================
    //         if (!is_null($passingGrade) && !is_null($grade)) {
    //             $gradePassed = $grade >= $passingGrade;
    //         }

    //         // ===================================================
    //         // 🔥 FULL COURSE PROGRESS VALIDATION
    //         // RULE:
    //         // - semua item KECUALI terakhir
    //         // - harus punya CourseProgress
    //         // - jika submission item → submission final valid
    //         // ===================================================
    //         $courseId = $item->course_id;

    //         // Ambil semua item course (urut!)
    //         $allItems = CourseWeekItem::where('course_id', $courseId)
    //             ->orderBy('item_order') // ganti kalau field beda
    //             ->get();

    //         // Kecualikan item terakhir
    //         $itemsExceptLast = $allItems->slice(0, -1);

    //         // Ambil semua progress user
    //         $progressItemIds = CourseProgress::where('course_id', $courseId)
    //             ->where('user_id', $userId)
    //             ->pluck('course_item_id')
    //             ->toArray();

    //         foreach ($itemsExceptLast as $ci) {

    //             // =============================
    //             // 1️⃣ WAJIB PUNYA PROGRESS
    //             // =============================
    //             if (!in_array($ci->item_id, $progressItemIds)) {
    //                 $allCompleted = false;
    //                 break;
    //             }

    //             // =============================
    //             // 2️⃣ JIKA ITEM SUBMISSION
    //             // =============================
    //             $ciType = preg_replace('/[^0-9]/', '', (string)$ci->course_item_type);

    //             if (in_array($ciType, ['3','4','7'])) {

    //                 $finalSubmission = match ($ciType) {
    //                     '3' => CourseEssaySubmission::where('item_id', $ci->item_id)
    //                             ->where('user_id', $userId)
    //                             ->where('is_remedial', 0)
    //                             ->latest('created_at')
    //                             ->first(),

    //                     '4' => CourseMcSubmission::where('item_id', $ci->item_id)
    //                             ->where('user_id', $userId)
    //                             ->where('is_remedial', 0)
    //                             ->latest('created_at')
    //                             ->first(),

    //                     '7' => CourseAttachmentSubmission::where('item_id', $ci->item_id)
    //                             ->where('user_id', $userId)
    //                             ->where('is_remedial', 0)
    //                             ->orderBy('submitted_at', 'desc')
    //                             ->first(),
    //                 };

    //                 if (!$finalSubmission) {
    //                     $allCompleted = false;
    //                     break;
    //                 }

    //                 if (
    //                     !is_null($ci->passing_grade) &&
    //                     !is_null($finalSubmission->grade) &&
    //                     $finalSubmission->grade < $ci->passing_grade
    //                 ) {
    //                     $allCompleted = false;
    //                     break;
    //                 }
    //             }
    //         }

    //         // ===================================================
    //         // 🔄 FINAL RESPONSE
    //         // ===================================================
    //         return response()->json([
    //             'exists'        => $exists,
    //             'is_remedial'   => $isRemedial,
    //             'submitted_at'  => $submittedAt ? \Carbon\Carbon::parse($submittedAt)->toDateTimeString() : null,
    //             'grade'         => $grade,
    //             'passing_grade' => $passingGrade,
    //             'grade_passed'  => $gradePassed,
    //             'all_completed' => $allCompleted,
    //             'attachments'   => $attachments,
    //         ]);
    //     }
    
    public function stream($filename)
    {
        // 🔍 Daftar lokasi file yang mungkin digunakan
        $paths = [
            public_path('assets/thread/video/' . $filename), // ✅ Tambahan 1
            public_path('assets/thread/pdf/' . $filename),  
            public_path('assets/video/' . $filename),
            public_path('assets/img/course/course-media/' . $filename),
            public_path('assets/forum/' . $filename),
            public_path('assets/essay/' . $filename),
            public_path('videos/' . $filename),
        ];

        $path = null;

        // 🔎 Cek lokasi satu per satu
        foreach ($paths as $p) {
            if (file_exists($p)) {
                $path = $p;
                break;
            }
        }

        // ❌ Kalau tidak ditemukan
        if (!$path) {
            abort(404, "File not found: $filename");
        }

        // ✅ Lanjut proses stream
        $size = filesize($path);
        $start = 0;
        $length = $size;
        $end = $size - 1;
        $fp = fopen($path, 'rb');

        header('Content-Type: video/mp4');
        header('Accept-Ranges: bytes');

        if (isset($_SERVER['HTTP_RANGE'])) {
            $range = str_replace('bytes=', '', $_SERVER['HTTP_RANGE']);
            $range = explode('-', $range);
            $start = intval($range[0]);
            $end = isset($range[1]) && is_numeric($range[1]) ? intval($range[1]) : $end;
            $length = $end - $start + 1;
            fseek($fp, $start);
            header('HTTP/1.1 206 Partial Content');
        }

        header("Content-Length: {$length}");
        header("Content-Range: bytes {$start}-{$end}/{$size}");

        $buffer = 1024 * 8;
        while (!feof($fp) && ($pos = ftell($fp)) <= $end) {
            if ($pos + $buffer > $end) {
                $buffer = $end - $pos + 1;
            }
            echo fread($fp, $buffer);
            flush();
        }

        fclose($fp);
        exit;
    }
    // ==================
    // Thread Function 
    // ==================
    // ====================================================
    // 1️⃣ GET: Semua thread berdasarkan course_id
    // ====================================================
    public function GetThread($course_id)
    {
        try {
            $threads = ForumThread::where('course_id', $course_id)
                ->with('creator:user_id,full_name') // opsional: join user
                ->orderBy('thread_seq', 'asc')
                ->get();

            return response()->json([
                'success' => true,
                'data' => $threads
            ]);
        } catch (\Exception $e) {
            \Log::error('Error GetThread: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Gagal memuat thread forum.',
                'error' => $e->getMessage()
            ], 500);
        }
    }
    public function ReplyThread($thread_id)
    {
        try {
            $replies = ForumReply::where('thread_id', $thread_id)
                ->with('replier:user_id,full_name') // ambil nama user dari replied_by
                ->orderBy('created_at', 'asc')
                ->get()
                ->map(function ($r) {
                    return [
                        'reply_id'        => $r->reply_id,
                        'replied_by'      => $r->replied_by,
                        'full_name'       => $r->replier->full_name ?? 'Pengguna tidak dikenal',
                        'reply_content'   => $r->reply_content,
                        'attachment_type' => $r->attachment_type,
                        'attachment_path' => $r->attachment_path,
                        'parent_id'       => $r->parent_id,
                        'created_at'      => $r->created_at,
                    ];
                });

            return response()->json([
                'success' => true,
                'data'    => $replies
            ]);
        } catch (\Exception $e) {
            \Log::error('Error ReplyThread: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Gagal memuat balasan forum.',
                'error'   => $e->getMessage()
            ], 500);
        }
    }
    // ====================================================
    // 3️⃣ POST: Simpan balasan baru
    // ====================================================
    public function StoreReply(Request $request)
    {
        try {
            $request->validate([
                'thread_id'       => 'required|exists:forum_threads,thread_id',
                'reply_content'   => 'required|string',
                'parent_id'       => 'nullable|integer',
                'attachment_type' => 'nullable|in:pdf,url,video',
                'attachment_file' => 'nullable|file|max:102400',
                'attachment_path' => 'nullable|string|max:500',
            ]);

            $basePath = public_path('assets/forum_replies');
            if (!file_exists($basePath)) mkdir($basePath, 0777, true);

            $attachmentType = $request->attachment_type;
            $attachmentPath = null;

            // 🔹 Handle file upload
            if (in_array($attachmentType, ['pdf', 'video']) && $request->hasFile('attachment_file')) {
                $folder = $attachmentType === 'pdf' ? 'pdf' : 'video';
                $file = $request->file('attachment_file');
                $filename = $folder . '_' . time() . '_' . Str::random(8) . '.' . $file->getClientOriginalExtension();
                $file->move($basePath . '/' . $folder, $filename);
                $attachmentPath = 'assets/forum_replies/' . $folder . '/' . $filename;
            }

            // 🔹 Handle URL type
            if ($attachmentType === 'url') {
                $attachmentPath = $request->attachment_path;
            }

            // 🔹 Simpan ke database
            $reply = ForumReply::create([
                'thread_id'       => $request->thread_id,
                'parent_id'       => $request->parent_id ?? null,
                'replied_by'      => Auth::id(),        // siapa yang membalas (user login)
                'reply_content'   => $request->reply_content,
                'attachment_type' => $attachmentType,
                'attachment_path' => $attachmentPath,
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Balasan berhasil dikirim.',
                'data'    => $reply
            ], 201);

        } catch (\Exception $e) {
            \Log::error('Error StoreReply: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Gagal menyimpan balasan.',
                'error'   => $e->getMessage()
            ], 500);
        }
    }

    public function storeFeedback(Request $request)
    {
        try {
            // ✅ Validasi dasar
            $validated = $request->validate([
                'course_id' => 'required|exists:course,course_id', // pastikan nama tabel "courses"
            ]);

            $user = Auth::user();

            // ✅ Ambil semua input Q1–Q17
            $feedbackQuestions = $request->only([
                'q1','q2','q3','q4','q5','q6','q7',
                'q8','q9','q10','q11','q12','q13','q14',
                'q15','q16','q17'
            ]);

            // ✅ Gabungkan metadata
            $feedbackData = array_merge($feedbackQuestions, [
                'course_id'    => $request->course_id,
                'user_id'      => $user->user_id,
                'submitted_at' => now(),
            ]);

            // ✅ Simpan ke database
            $savedFeedback = CourseFeedback::create($feedbackData);

            // ✅ Log detail untuk debugging
            Log::info("📝 Feedback berhasil disimpan", [
                'course_id' => $request->course_id,
                'user_id'   => $user->user_id,
                'submitted_at' => $savedFeedback->submitted_at,
                'feedback_data' => $feedbackQuestions, // seluruh nilai Q1–Q17
            ]);

            // ✅ Log per pertanyaan (biar lebih eksplisit)
            foreach ($feedbackQuestions as $key => $value) {
                Log::debug("📊 Feedback detail", [
                    'question' => $key,
                    'value' => $value,
                    'user_id' => $user->user_id,
                    'course_id' => $request->course_id,
                ]);
            }

            return back()->with('success', 'Terima kasih! Feedback Anda berhasil dikirim.');
        } catch (\Throwable $e) {
            Log::error("❌ Gagal menyimpan feedback: " . $e->getMessage(), [
                'trace' => $e->getTraceAsString(),
                'input_data' => $request->all(),
            ]);
            return back()->with('error', 'Terjadi kesalahan saat menyimpan feedback.');
        }
    }
}
