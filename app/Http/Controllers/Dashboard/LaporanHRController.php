<?php

namespace App\Http\Controllers\Dashboard;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Models\User;
use App\Models\Course;
use App\Models\CourseWeekItem;
use App\Models\CourseWeekModule;
use App\Models\CourseType\CourseMcSubmission;
use App\Models\CourseType\CourseEssaySubmission;
use App\Models\CourseType\CourseAttachment;
use App\Models\CourseItemType;
use App\Models\CourseEnrollment;
use App\Models\CourseProgress;
use App\Models\LmsLoginSession;
use App\Models\CourseCategory;
use App\Models\Division;
use App\Models\CourseLearnerActivity;
use App\Models\CourseFeedback;
use Maatwebsite\Excel\Facades\Excel;
use App\Exports\LoginSessionExport; 
use App\Exports\CoursesExport;
use App\Exports\AllParticipantExport;
use App\Exports\AllParticipantMultiSheetExport;
use App\Exports\ActivityTaskExport;
use App\Exports\LearnerProgressExport;
use App\Exports\InstructorFeedbackExport;
use Carbon\Carbon;

class LaporanHRController extends Controller
{
    public function index(Request $request)
    {
        // ==========================
        // 0️⃣ FILTER OPSIONAL (periode & kategori)
        // ==========================
        $startDate   = $request->input('start_date');
        $endDate     = $request->input('end_date');
        $categoryName = $request->input('course_category'); // sekarang pakai nama langsung
        $startMonth  = $request->input('start_month');
        $endMonth    = $request->input('end_month');

        $courseCategory = CourseCategory::get();
        $userDivision   = Division::get();

        // ==========================
        // Roles (utama untuk IT)
        // ==========================
        $user = auth()->user();
        $mainRole = [$user->role_id];
        $subRoles = is_array($user->sub_role)
            ? $user->sub_role
            : (json_decode($user->sub_role, true) ?: []);
        $allRoles = array_merge($mainRole, $subRoles);
        $isIT = in_array(1, $allRoles);

        // ==========================
        // Statistik Global
        // ==========================
        $totalEmployees = User::where('is_deleted', 0)->count();

        $activeCourses = Course::where('is_approved', 1)
            ->where('is_deleted', 0)
            ->where('is_draft', 0)
            ->count();

        // Hitung total login session (filter jika ada rentang bulan)
        $loginQuery = LmsLoginSession::query();
        if ($startMonth && $endMonth) {
            $loginQuery->whereBetween('login_time', [
                $startMonth . '-01',
                date("Y-m-t", strtotime($endMonth . '-01'))
            ]);
        }
        $totalLogins = $loginQuery->count();

        // ==========================
        // 1️⃣ DATA PER COURSE (with filters)
        // ==========================
        $courseQuery = Course::where([
                ['is_approved', 1],
                ['is_deleted', 0],
                ['is_draft', 0],
            ])
            ->with(['enrollments.user.division']);

        // 🔹 Filter kategori (langsung by nama)
        if ($categoryName) {
            $courseQuery->where('course_category', $categoryName);
        }

        // 🔹 Filter tanggal berdasarkan created_at
        if ($startDate && $endDate) {
            $courseQuery->whereBetween('created_at', [
                $startDate . ' 00:00:00',
                $endDate . ' 23:59:59'
            ]);
        }

        // 🔹 Urutkan dari yang terbaru
        $courseQuery->orderByDesc('created_at');

        $courses = $courseQuery->get();

        // Mapping data untuk tampilan Blade
        $courseData = $courses->map(function ($course) {
            $divisions = $course->enrollments
                ->groupBy(fn($enroll) => $enroll->user->division->division_name ?? 'Tidak ada divisi')
                ->map(function ($group, $divisionName) use ($course) {
                    $totalUsers = $group->count();

                    // progress tiap user
                    $users = $group->map(function ($enroll) use ($course) {
                        $progressRow = CourseProgress::where('user_id', $enroll->user_id)
                            ->where('course_id', $course->course_id)
                            ->selectRaw('COUNT(DISTINCT course_item_id) as checked_items, MAX(total_item) as total_items')
                            ->first();

                        $pct = ($progressRow && $progressRow->total_items > 0)
                            ? round(($progressRow->checked_items / $progressRow->total_items) * 100, 2)
                            : 0;

                        return [
                            'name'     => $enroll->user->full_name,
                            'progress' => $pct,
                        ];
                    });

                    // rata-rata progress per divisi
                    $avgProgress = $users->count() > 0
                        ? round($users->avg('progress'), 2)
                        : 0;

                    return [
                        'division'     => $divisionName,
                        'total_users'  => $totalUsers,
                        'progress'     => $avgProgress,
                        'users'        => $users->toArray(),
                    ];
                })->values();

            return [
                'course_id'        => $course->course_id,
                'course_name'      => $course->course_title,
                'course_image'     => $course->course_image,
                'course_category'  => $course->course_category,
                'start_course'     => $course->start_course,
                'end_course'       => $course->end_course,
                'created_at'       => $course->created_at,
                'divisions'        => $divisions,
            ];
        });

        // ==========================
        // 2️⃣ DETAIL LAPORAN PER USER
        // ==========================
        $learners = User::with(['division', 'enrollments.course'])
            ->where('role_id', 4)
            ->get();

        $reportDetails = $learners->map(function ($learner) {
            $enrollments = $learner->enrollments;
            $totalCourses = $enrollments->count();

            $completed = CourseProgress::where('user_id', $learner->user_id)
                ->whereIn('course_id', $enrollments->pluck('course_id'))
                ->select('course_id', DB::raw('MAX(progress_pct) as pct'))
                ->groupBy('course_id')
                ->having('pct', '>=', 100)
                ->count();

            $progress = $totalCourses > 0
                ? round(($completed / $totalCourses) * 100, 2)
                : 0;

            return [
                'name'          => $learner->full_name,
                'department'    => $learner->division->division_name ?? 'Tidak ada',
                'total_courses' => $totalCourses,
                'completed'     => $completed,
                'progress'      => $progress,
            ];
        });

        // ==========================
        // 3️⃣ AVERAGE PROGRESS SEMUA COURSE
        // ==========================
        $activeCourseIds = Course::where('is_approved', 1)
            ->where('is_deleted', 0)
            ->where('is_draft', 0)
            ->pluck('course_id');

        $avgPerCourse = CourseProgress::whereIn('course_id', $activeCourseIds)
            ->select('course_id', DB::raw('AVG(progress_pct) as avg_pct'))
            ->groupBy('course_id')
            ->pluck('avg_pct');

        $avgProgressCourses = $avgPerCourse->count() > 0
            ? round($avgPerCourse->avg(), 2)
            : 0;

        // ==========================
        // 4️⃣ TOP ACTIVE LEARNERS (progress tertinggi)
        // ==========================
        // $topProgress = CourseProgress::select(
        //         'user_id',
        //         DB::raw('AVG(progress_pct) as avg_progress')
        //     )
        //     ->groupBy('user_id')
        //     ->orderByDesc('avg_progress')
        //     ->take(5)
        //     ->get();
        
        // ===============
            $topProgress = CourseProgress::select(
                'user_id',
                DB::raw('AVG(progress_pct) as avg_progress')
            )
            ->whereNotIn('user_id', function($query) {
                $query->select('user_id')
                    ->from('users')
                    ->where(function ($q) {
                        $q->where('role_id', 1)
                        ->orWhere('sub_role', 1);
            });
            })
            ->groupBy('user_id')
            ->orderByDesc('avg_progress')
            ->take(5)
            ->get();
        // ===============

        $topActiveLearners = $topProgress->map(function ($row) {
            $user = User::select('full_name')
                ->where('user_id', $row->user_id)
                ->first();

            return [
                'name' => $user->full_name ?? '-',
                'progress' => round($row->avg_progress, 2),
            ];
        });

        // ==========================
        // 5️⃣ Kirim ke View
        // ==========================
        return view('administrator.report', compact(
            'totalEmployees',
            'activeCourses',
            'totalLogins',
            'avgProgressCourses',
            'courseData',
            'reportDetails',
            'startMonth',
            'endMonth',
            'allRoles',
            'isIT',
            'courseCategory',
            'userDivision',
            'topActiveLearners'
        ));
    }
    public function export(Request $request)
    {
        $startMonth = $request->input('start_month');
        $endMonth   = $request->input('end_month');

        // Export ke Excel langsung berdasarkan filter bulan
        return Excel::download(
            new LoginSessionExport($startMonth, $endMonth), 
            'login_sessions.xlsx'
        );
    }
    public function courseExportExcel(Request $request) 
    {
        $startDate  = $request->input('start_date');
        $endDate    = $request->input('end_date');
        $category   = $request->input('course_category');

        $query = Course::where([
            // ['is_approved', 1],
            ['is_deleted', 0],
            ['is_draft', 0],
        ]);
        if ($category) {
        $query->where('course_category', $category);
        }

        if ($startDate && $endDate) {
            $query->whereBetween('created_at', [
                $startDate . ' 00:00:00',
                $endDate . ' 23:59:59'
            ]);
        }

        $courses = $query->orderByDesc('created_at')->get([
            'course_title',
            'course_category',
            'course_trainer_name',
            'max_participant',
            'is_approved', 
            'is_public',
            'is_requested',
            'start_course',
            'end_course',
            'created_at',
        ]);

        // Export ke Excel
        $fileName = 'Laporan_Course_' . now()->format('Ymd_His') . '.xlsx';
        return Excel::download(new CoursesExport($courses), $fileName);
    }
    public function AllParticipantCourse(Request $request)
    {
        $startDate  = $request->input('start_date');
        $endDate    = $request->input('end_date');
        $department = $request->input('department');
        $courseId   = $request->input('course_id');

        // Log awal
        // \Log::info("📥 [Participant] Incoming Request", [
        //     'course_id'  => $courseId,
        //     'start_date' => $startDate,
        //     'end_date'   => $endDate,
        //     'department' => $department,
        // ]);

        $query = CourseEnrollment::with(['user.division', 'course'])

            // ================================
            // 🔹 Filter Course
            // ================================
            ->when($courseId, function ($q) use ($courseId) {
                // \Log::info("🔍 Filter: Course ID applied", ['course_id' => $courseId]);
                return $q->where('course_id', $courseId);
            })

            // ================================
            // 🔹 Filter Divisi
            // ================================
            ->when($department, function ($q) use ($department) {

                // \Log::info("🔍 Checking Department Filter", ['department' => $department]);

                return $q->whereHas('user.division', function ($d) use ($department) {

                    // \Log::info("🏢 Comparing with division table ...");

                    $d->where('division_id', $department);

                    // \Log::info("🏷️ SQL Segment", [
                    //     'sql' => $d->toSql(),
                    //     'bindings' => $d->getBindings()
                    // ]);

                });
            })

            // ================================
            // 🔹 Filter Date — BETWEEN
            // ================================
            ->when($startDate && $endDate, function ($q) use ($startDate, $endDate) {
                $start = Carbon::parse($startDate)->startOfDay();
                $end   = Carbon::parse($endDate)->endOfDay();

                // \Log::info("📆 Date Filter: BETWEEN", [
                //     'parsed_start' => $start->toDateTimeString(),
                //     'parsed_end'   => $end->toDateTimeString(),
                // ]);

                return $q->whereBetween('created_at', [$start, $end]);
            })

            // ================================
            // 🔹 Filter Date — START only
            // ================================
            ->when($startDate && !$endDate, function ($q) use ($startDate) {
                $start = Carbon::parse($startDate)->startOfDay();

                // \Log::info("📆 Date Filter: START ONLY", [
                //     'parsed_start' => $start->toDateTimeString()
                // ]);

                return $q->where('created_at', '>=', $start);
            })

            // ================================
            // 🔹 Filter Date — END only
            // ================================
            ->when(!$startDate && $endDate, function ($q) use ($endDate) {
                $end = Carbon::parse($endDate)->endOfDay();

                // \Log::info("📆 Date Filter: END ONLY", [
                //     'parsed_end' => $end->toDateTimeString()
                // ]);

                return $q->where('created_at', '<=', $end);
            });

        // ================================
        // 🔹 Execute Query
        // ================================
        $participants = $query->orderByDesc('created_at')->get();


        // \Log::info("📊 [Participant] Total found", [
        //     'count' => $participants->count()
        // ]);

        foreach ($participants as $p) {

            $p->status_opened = $p->activity && $p->activity->is_opened
                ? 'Sudah Masuk'
                : 'Belum Masuk';

            $p->formatted_last_access = $p->activity && $p->activity->last_access
                ? Carbon::parse($p->activity->last_access)->format('d M Y H:i')
                : '-';
        }
        // ================================
        // 🔹 Export File
        // ================================
        $fileName = 'List_Participant_' . now()->format('Ymd_His') . '.xlsx';

        // \Log::info("📤 Exporting Participant Excel", [
        //     'file_name' => $fileName
        // ]);

        return Excel::download(new AllParticipantMultiSheetExport($participants), $fileName);
    }

    public function ActivityTaskReport(Request $request)
    {
        $startDate  = $request->input('start_date');
        $endDate    = $request->input('end_date');
        $department = $request->input('department');
        $courseId   = $request->input('course_id');

        // =============================
        // 1️⃣ LOAD ITEMS
        // =============================
        $items = CourseWeekItem::with([
                'module.course',
                'mcSubmissions.user.division',
                'submissions.user.division',
                'attachment.user.division',
            ])
            ->when($courseId, fn($q) => $q->where('course_id', $courseId))
            ->get();

        foreach ($items as $it) {
            \Log::info("📌 Item", [
                'item_id' => $it->item_id,
                'item_name' => $it->course_item_name,
                'type' => $it->course_item_type,
            ]);
        }

        // =============================
        // 2️⃣ TYPE MAP
        // =============================
        $typeMap = [
            1 => 'Video',
            2 => 'PDF',
            3 => 'Essai',
            4 => 'Pilihan Ganda',
            5 => 'Forum Diskusi',
            6 => 'Sertifikat',
            7 => 'Unggahan Tugas',
            8 => 'Audio',
        ];

        // =============================
        // 3️⃣ PROCESS ITEMS
        // =============================
        $results = $items->flatMap(function ($item) use ($startDate, $endDate, $department, $typeMap) {

            \Log::info("🧾 [ItemProcess]", [
                'item_id' => $item->item_id,
                'name' => $item->course_item_name
            ]);

            // --------------------------
            // 🔹 Flexible Date Filter
            // --------------------------
            $filterDate = function ($collection) use ($startDate, $endDate) {

                if (!$startDate && !$endDate) {
                    \Log::info("⛔ [FilterDate] No date filter");
                    return $collection;
                }

                $start = $startDate ?Carbon::parse($startDate)->startOfDay() : null;
                $end   = $endDate   ?Carbon::parse($endDate)->endOfDay()   : null;

                \Log::info("📆 [FilterDate] Condition", [
                    'start' => $start ? $start->toDateTimeString() : null,
                    'end'   => $end ? $end->toDateTimeString() : null
                ]);

                return $collection->filter(function($row) use ($start, $end) {

                    $created =Carbon::parse($row->created_at);

                    \Log::info("🕒 Compare Date", [
                        'created' => $created->toDateTimeString(),
                        '>= start?' => $start ? ($created >= $start) : 'SKIP',
                        '<= end?'   => $end ? ($created <= $end) : 'SKIP',
                    ]);

                    if ($start && $end) {
                        return $created >= $start && $created <= $end;
                    } elseif ($start) {
                        return $created >= $start;
                    } elseif ($end) {
                        return $created <= $end;
                    }

                    return true;
                });
            };

            // --------------------------
            // 🔹 Department Filter (Auto-detect)
            // --------------------------
            $filterDept = function ($collection) use ($department) {

                if (!$department) {
                    \Log::info("⛔ [FilterDept] No department filter");
                    return $collection;
                }

                \Log::info("🏢 [FilterDept] Applying...", ['department' => $department]);

                return $collection->filter(function($row) use ($department) {

                    $divModel = $row->user->division;

                    $value = $divModel->division_id ?? $divModel->departement_cat ?? null;

                    \Log::info("🏷️ Dept Compare", [
                        'user' => $row->user->full_name ?? '-',
                        'value' => $value,
                        'match' => $value == $department ? 'YES' : 'NO'
                    ]);

                    return $value == $department;
                });
            };

            // --- APPLY FILTERS ---
            $mcFiltered    = $filterDept($filterDate(collect($item->mcSubmissions)));
            $essayFiltered = $filterDept($filterDate(collect($item->submissions)));
            $attFiltered   = $filterDept($filterDate(collect($item->attachment)));

            \Log::info("📉 [AfterFilter]", [
                'mc' => $mcFiltered->count(),
                'essay' => $essayFiltered->count(),
                'attach' => $attFiltered->count(),
            ]);

            // ----------------------
            // 🔹 MAP RESULT
            // ----------------------
            $courseName  = $item->module->course->course_title ?? '-';
            $moduleName  = $item->module->course_week_title ?? '-';
            $itemName    = $item->course_item_name ?? '-';
            $itemType    = $typeMap[$item->course_item_type] ?? 'Unknown';

            $combined = collect();

            return $combined
                ->merge($mcFiltered->map(fn($r) => [
                    'course_name' => $courseName,
                    'course_module' => $moduleName,
                    'course_item_name' => $itemName,
                    'tipe_tugas' => $itemType,
                    'learner' => $r->user->full_name ?? '-',
                    'hasil' => $r->grade ?? '-',
                    'submitted_at' => optional($r->submitted_at)->format('d M Y H:i'),
                ]))
                ->merge($essayFiltered->map(fn($r) => [
                    'course_name' => $courseName,
                    'course_module' => $moduleName,
                    'course_item_name' => $itemName,
                    'tipe_tugas' => $itemType,
                    'learner' => $r->user->full_name ?? '-',
                    'hasil' => $r->grade ?? '-',
                    'submitted_at' => optional($r->submitted_at)->format('d M Y H:i'),
                ]))
                ->merge($attFiltered->map(fn($r) => [
                    'course_name' => $courseName,
                    'course_module' => $moduleName,
                    'course_item_name' => $itemName,
                    'tipe_tugas' => $itemType,
                    'learner' => $r->user->full_name ?? '-',
                    'hasil' => $r->grade ?? '-',
                    'submitted_at' => optional($r->submitted_at)->format('d M Y H:i'),
                ]));
        });

        \Log::info("📦 FINAL RESULT COUNT", [
            'rows' => $results->count()
        ]);

        $fileName = 'Activity_Task_Report_' . now()->format('Ymd_His') . '.xlsx';

        return Excel::download(new ActivityTaskExport($results), $fileName);
    }

    public function LearnerProgressReport(Request $request)
    {
        // =========================
        // 1️⃣ LOG REQUEST INPUT
        // =========================
        // \Log::info("📥 [ProgressReport] Incoming Request", [
        //     'course_id'  => $request->course_id,
        //     'start_date' => $request->start_date,
        //     'end_date'   => $request->end_date,
        //     'department' => $request->department,
        // ]);

        $startDate  = $request->input('start_date');
        $endDate    = $request->input('end_date');
        $department = $request->input('department');
        $courseId   = $request->input('course_id');

        // Log extracted inputs
        // \Log::info("🔧 [ProgressReport] Extracted", [
        //     'course_id'  => $courseId,
        //     'start_date' => $startDate,
        //     'end_date'   => $endDate,
        //     'department' => $department,
        // ]);

        // =========================
        // 2️⃣ BUILD QUERY
        // =========================
        // \Log::info("🛠️ [ProgressReport] Building Query...");

        $query = \App\Models\CourseProgress::select(
                'user_id',
                'course_id',
                \DB::raw('MAX(progress_pct) as max_progress'),
                \DB::raw('MAX(total_item) as total_item'),
                \DB::raw('MAX(total_checked) as total_checked'),
                \DB::raw('MAX(status_course) as status_course'),
                \DB::raw('MAX(last_update) as last_update')
            )
            ->when($courseId, function ($q) use ($courseId) {
                // \Log::info("🧲 Filter by course_id", ['course_id' => $courseId]);
                $q->where('course_id', $courseId);
            })

            // =========================
            // 🔍 Flexible Date Filter
            // =========================
            ->when($startDate && $endDate, function ($q) use ($startDate, $endDate) {

                $start = $startDate . ' 00:00:00';
                $end   = $endDate   . ' 23:59:59';

                // \Log::info("📆 Applying BETWEEN Filter", [
                //     'start' => $start,
                //     'end'   => $end,
                // ]);

                $q->whereBetween('last_update', [$start, $end]);
            })

            ->when($startDate && !$endDate, function ($q) use ($startDate) {

                $start = $startDate . ' 00:00:00';

                // \Log::info("📆 Applying START-ONLY Filter", [
                //     'start' => $start,
                // ]);

                $q->where('last_update', '>=', $start);
            })

            ->when(!$startDate && $endDate, function ($q) use ($endDate) {

                $end = $endDate . ' 23:59:59';

                // \Log::info("📆 Applying END-ONLY Filter", [
                //     'end' => $end,
                // ]);

                $q->where('last_update', '<=', $end);
            });

        // =========================
        // 3️⃣ FILTER BY DEPARTMENT (via user → division)
        // =========================
        if (!empty($department)) {

            // \Log::info("🏢 Applying Department Filter", [
            //     'target_department' => $department
            // ]);

            $query->whereHas('user.division', function($q) use ($department) {
                $q->where('division_id', $department);
            });
        }

        $query->groupBy('user_id', 'course_id');

        // =========================
        // 4️⃣ FETCH DATA
        // =========================
        // \Log::info("📦 [ProgressReport] Executing Query...");

        $progressData = $query->with(['user.division', 'course'])->get();

        // \Log::info("📦 [ProgressReport] Query Result", [
        //     'rows_returned' => $progressData->count(),
        // ]);

        // =========================
        // 5️⃣ DEBUG EACH ROW
        // =========================
        foreach ($progressData as $row) {

            // Log raw date
            // \Log::info("🕒 [RowCheck] Raw last_update", [
            //     'user_id'       => $row->user_id,
            //     'course_id'     => $row->course_id,
            //     'last_update'   => $row->last_update,
            //     'last_update_type' => gettype($row->last_update),
            // ]);

            // Try to parse date
            try {
                $dt = \Carbon\Carbon::parse($row->last_update);
                \Log::info("📅 [RowCheck] Parsed last_update", [
                    'parsed' => $dt->toDateTimeString(),
                ]);
            } catch (\Exception $e) {
                \Log::error("❌ [RowCheck] Failed to parse last_update", [
                    'value' => $row->last_update,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        // =========================
        // 6️⃣ MAP RESULTS
        // =========================
        // \Log::info("🔄 [ProgressReport] Mapping Results...");

        $results = $progressData->map(function ($row) {

            return [
                'course_title'   => $row->course->course_title ?? '-',
                'learner_name'   => $row->user->full_name ?? '-',
                'division_name'  => $row->user->division->division_name ?? '-',
                'total_items'    => $row->total_item ?? 0,
                'checked_items'  => $row->total_checked ?? 0,
                'progress_pct'   => number_format($row->max_progress ?? 0, 2) . '%',
                'status_course'  => $row->status_course ? 'Completed' : 'Ongoing',
                'last_update'    => optional($row->last_update)->format('d M Y H:i'),
            ];
        });

        // \Log::info("📊 [ProgressReport] Final Rows for Export", [
        //     'total_rows' => $results->count(),
        // ]);

        // =========================
        // 7️⃣ EXPORT
        // =========================
        $fileName = 'Learner_Progress_Report_' . now()->format('Ymd_His') . '.xlsx';

        // \Log::info("📤 Exporting Excel", [
        //     'file_name' => $fileName
        // ]);

        return \Maatwebsite\Excel\Facades\Excel::download(
            new \App\Exports\LearnerProgressExport($results),
            $fileName
        );
    }

    public function InstructorFeedback(Request $request)
    {
        // =============================
        // 1. LOG REQUEST INPUT
        // =============================
        // \Log::info("📥 [FeedbackReport] Incoming Request", [
        //     'course_id'  => $request->course_id,
        //     'start_date' => $request->start_date,
        //     'end_date'   => $request->end_date,
        //     'department' => $request->department,
        // ]);

        $startDate  = $request->input('start_date');
        $endDate    = $request->input('end_date');
        $department = $request->input('department');
        $courseId   = $request->input('course_id');

        // \Log::info("🔧 [FeedbackReport] Extracted Inputs", [
        //     'course_id'  => $courseId,
        //     'start_date' => $startDate,
        //     'end_date'   => $endDate,
        //     'department' => $department,
        // ]);

        // Likert Scale
        $likert = [
            1 => 'Sangat Tidak Setuju',
            2 => 'Tidak Setuju',
            3 => 'Netral',
            4 => 'Setuju',
            5 => 'Sangat Setuju',
        ];

        // =============================
        // 2. BUILD QUERY
        // =============================
        // \Log::info("🛠️ [FeedbackReport] Building Query...");

        $query = CourseFeedback::with(['user.division', 'course'])
            ->when($courseId, function ($q) use ($courseId) {
                // \Log::info("🎯 Filter: course_id", ['course_id' => $courseId]);
                $q->where('course_id', $courseId);
            })

            // =============================
            // 📅 FLEXIBLE DATE FILTER WITH LOGS
            // =============================
            ->when($startDate && $endDate, function ($q) use ($startDate, $endDate) {
                $start = $startDate . ' 00:00:00';
                $end   = $endDate   . ' 23:59:59';

                // \Log::info("📆 Applying BETWEEN date filter", [
                //     'start' => $start,
                //     'end'   => $end,
                // ]);

                $q->whereBetween('submitted_at', [$start, $end]);
            })
            ->when($startDate && !$endDate, function ($q) use ($startDate) {
                $start = $startDate . ' 00:00:00';

                // \Log::info("📆 Applying START-ONLY date filter", [
                //     'start' => $start,
                // ]);

                $q->where('submitted_at', '>=', $start);
            })
            ->when(!$startDate && $endDate, function ($q) use ($endDate) {
                $end = $endDate . ' 23:59:59';

                // \Log::info("📆 Applying END-ONLY date filter", [
                //     'end' => $end,
                // ]);

                $q->where('submitted_at', '<=', $end);
            });

        // =============================
        // 3. DEPARTMENT FILTER
        // =============================
        if (!empty($department)) {
            // \Log::info("🏢 Applying Department Filter", [
            //     'target_department' => $department,
            // ]);

            $query->whereHas('user', function ($q) use ($department) {
                $q->where('departement_cat', $department);
            });
        } else {
            // \Log::info("⛔ [DeptFilter] SKIPPED (no department)");
        }

        // =============================
        // 4. EXECUTE QUERY
        // =============================
        // \Log::info("📦 [FeedbackReport] Executing Query...");
        $feedbackRows = $query->orderBy('submitted_at', 'desc')->get();

        // \Log::info("📦 [FeedbackReport] Query Result", [
        //     'rows_returned' => $feedbackRows->count()
        // ]);

        // =============================
        // 5. ROW-LEVEL LOG
        // =============================
        foreach ($feedbackRows as $row) {
            // \Log::info("📝 [RowCheck] Feedback Row", [
            //     'user'       => $row->user->full_name ?? '-',
            //     'division'   => $row->user->division->division_name ?? '-',
            //     'submitted'  => $row->submitted_at,
            // ]);
        }

        // =============================
        // 6. MAP RESULT FOR EXPORT
        // =============================
        // \Log::info("🔄 [FeedbackReport] Mapping Results...");

        $results = $feedbackRows->map(function ($row) use ($likert) {

            $mappedLikert = [];
            for ($i = 1; $i <= 14; $i++) {
                $key = "q{$i}";
                $mappedLikert[$key] = $likert[$row->$key] ?? '-';
            }

            return array_merge([
                'course_title'  => $row->course->course_title ?? '-',
                'learner_name'  => $row->user->full_name ?? '-',
                'division_name' => $row->user->division->division_name ?? '-',
                'submitted_at'  => optional($row->submitted_at)->format('d M Y H:i'),
            ], $mappedLikert, [
                'q15' => $row->q15,
                'q16' => $row->q16,
                'q17' => $row->q17,
            ]);
        });

        // \Log::info("📊 [FeedbackReport] Final Rows for Export", [
        //     'total_rows' => $results->count()
        // ]);

        // =============================
        // 7. EXPORT
        // =============================
        $fileName = 'Instructor_Feedback_Report_' . now()->format('Ymd_His') . '.xlsx';

        // \Log::info("📤 Exporting Excel", ['file_name' => $fileName]);

        return \Maatwebsite\Excel\Facades\Excel::download(
            new InstructorFeedbackExport($results),
            $fileName
        );
    }

}

