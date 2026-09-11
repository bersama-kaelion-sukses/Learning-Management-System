<?php

namespace App\Http\Controllers\Dashboard;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use App\Models\User;
use App\Models\CourseEnrollment;
use App\Models\Course;
use App\Models\Division;
use App\Models\CourseWeekItem;
use App\Models\CourseProgress;
use Carbon\Carbon;
use App\Models\CourseLearnerActivity;
use Illuminate\Support\Facades\Log;

class LearnerDashboardController extends Controller
{
 public function index()
    {
        $user = Auth::user();
        $userId = $user->user_id;

        // ===================================================
        // ðŸ”¹ Ambil semua enrollment aktif + course-nya
        // ===================================================
        $enrollments = CourseEnrollment::where('user_id', $userId)
            ->whereHas('course', fn($q) => $q->where('is_approved', 1))
            ->with('course')
            ->orderBy('created_at', 'desc')
            ->get();

        // ðŸ”¹ Ambil list course dari enrollment
        $courseLearner = $enrollments->map(fn($enroll) => $enroll->course)->filter()->values();

        // ðŸ”¹ Nama divisi user
        // $divisionName = Division::where('division_id', $user->departement_cat)->value('division_name');

        // // ðŸ”¹ Course relevan & publik
        // $relevantCourses = Course::where('course_category', $divisionName)
        //     ->where('is_public', 1)
        //     ->where('is_approved', 1)
        //     ->where('is_draft', 0)
        //     ->get();
        
        // ======================
        // Bagian Sini
        // ======================
        // 🔹 Nama divisi user
        $divisionName = Division::where('division_id', $user->departement_cat)
            ->value('division_name');
        
        $mapping = config('division_course_map');

        // kategori relevan berdasarkan mapping
        $relevantCategories = $mapping[$divisionName] ?? [$divisionName];

        /**
         * 1️⃣ Course RELEVAN (hasil mapping)
         */
        $relevantCourses = Course::whereIn('course_category', $relevantCategories)
            ->where('is_public', 1)
            ->where('is_approved', 1)
            ->where('is_draft', 0)
            ->where('is_deleted', 0)
            ->get();
        /**
         * 2️⃣ Course UMUM (kategori Lainnya)
         */
        $otherCourses = Course::whereIn('course_category', ['Lainnya', 'Soft Skill','Mandatory'])
            ->where('is_public', 1)
            ->where('is_approved', 1)
            ->where('is_draft', 0)
            ->where('is_deleted', 0)
            ->get();
            
        /**
         * 3️⃣ Gabungkan & hindari duplikasi
         */
        $courses = $relevantCourses
            ->merge($otherCourses)
            ->unique('course_id')
            ->values();
        
        // ======================
        // Sampai Sini
        // ======================
        $calendarCourses = $enrollments
            ->map(fn ($enroll) => $enroll->course)
            ->filter()               // jaga-jaga kalau course null
            ->unique('course_id')    // hindari duplikasi
            ->values();
            
        $publicCourses = Course::where('is_public', 1)
            ->where('is_approved', 1)
            ->where('is_draft', 0)
            ->where('is_deleted', 0)
            ->orderBy('created_at', 'desc')
            ->get();

        // ===================================================
        // ðŸ”¹ Hitung Progress Tiap Course
        // ===================================================
        $progress = CourseProgress::where('user_id', $userId)
            ->select('course_id',
                DB::raw('COUNT(DISTINCT course_item_id) as checked'),
                DB::raw('MAX(total_item) as total'))
            ->groupBy('course_id')
            ->get()
            ->mapWithKeys(fn($row) => [
                $row->course_id => $row->total > 0 ? round(($row->checked / $row->total) * 100, 2) : 0
            ]);

        foreach ($enrollments as $enroll) {
            $enroll->progress = $progress[$enroll->course->course_id] ?? 0;
        }

        // ===================================================
        // ðŸ”¥ Task Courses (Active / Upcoming)
        // ===================================================
        $now = now();
        $taskCourses = CourseWeekItem::whereHas('course.enrollments', fn($q) =>
                $q->where('user_id', $user->user_id)
            )
            ->where(function ($q) use ($now) {
                $q->whereNull('course_due_end')
                  ->orWhere('course_due_end', '>=', $now);
            })
            ->where(function ($q) use ($now) {
                $q->whereBetween('course_due_start', [$now->copy()->subDay(), $now->copy()->addDays(7)])
                ->orWhereBetween('course_due_end', [$now->copy()->subDays(7), $now->copy()->addDay()]);
            })
            ->get()
            ->map(function ($course) use ($now) {
                $start = $course->course_due_start ? Carbon::parse($course->course_due_start) : null;
                $end   = $course->course_due_end ? Carbon::parse($course->course_due_end) : null;

                if ($start && $end && $now->between($start, $end)) {
                    $course->status = 'active';
                } elseif ($start && $start->isAfter($now) && $start->diffInDays($now, false) <= 7) {
                    $course->status = 'upcoming';
                } else {
                    $course->status = 'inactive';
                }

                return $course;
            });

        // ===================================================
        // ðŸ”¥ Cek / Buat Activity Learner
        // ===================================================
        foreach ($enrollments as $enroll) {
            $course = $enroll->course;
            if (!$course) continue;

            $activity = CourseLearnerActivity::firstOrCreate(
                [
                    'course_id' => $course->course_id,
                    'user_id'   => $userId,
                ],
                [
                    'is_opened'      => 0,
                    'last_access'    => null,
                    'person_process' => $user->full_name ?? $user->name,
                    'created_at'     => now(),
                ]
            );

            if (!$activity->is_opened) {
                $activity->update(['is_opened' => 0]);
            }
        }

        // ===================================================
        // ðŸ§© Gabungkan Activity + Periode
        // ===================================================
        $activities = CourseLearnerActivity::where('user_id', $userId)->get()->keyBy('course_id');

        foreach ($enrollments as $enroll) {
            $course = $enroll->course;
            if (!$course) continue;

            $courseId = $course->course_id ?? null;
            $activity = $activities[$courseId] ?? null;

            $enroll->is_opened   = $activity->is_opened ?? 0;
            $enroll->last_access = $activity->last_access ?? null;
            $enroll->progress    = $progress[$courseId] ?? 0;

            // ðŸ”¹ Format tanggal
            $startRaw = $course->start_course ?? null;
            $endRaw   = $course->end_course ?? null;

            $enroll->start_formatted = $startRaw
                ? Carbon::parse($startRaw)->format('d M Y')
                : '';

            if ($endRaw) {
                $enroll->end_formatted = Carbon::parse($endRaw)->format('d M Y');
            } elseif ($startRaw && Carbon::now()->gt(Carbon::parse($startRaw))) {
                $enroll->end_formatted = 'selesai';
            } else {
                $enroll->end_formatted = '';
            }

            // ðŸ”¹ Status Periode
            $now = Carbon::now();
            if ($startRaw && $now->lt(Carbon::parse($startRaw))) {
                $enroll->is_period_open = false;
                $enroll->period_message = 'Belum dimulai';
            } elseif ($endRaw && $now->gt(Carbon::parse($endRaw))) {
                $enroll->is_period_open = false;
                $enroll->period_message = 'Telah Berakhir';
            } else {
                $enroll->is_period_open = true;
                $enroll->period_message = 'Sedang berlangsung';
            }
        }

        // ===================================================
        // âœ… Global flag & log
        // ===================================================
        $isOpened = $enrollments->contains(fn($e) => $e->is_opened == 1);
        Log::info('âœ… [Learner Index] Data dikirim ke view', [
            'total_courses' => $enrollments->count(),
            'opened_courses' => $enrollments->where('is_opened', 1)->count(),
        ]);

        return view('dashboard.component.learner-dashboard', compact(
            'user',
            'calendarCourses',
            'enrollments',
            'courseLearner',
            'relevantCourses',
            'publicCourses',
            'isOpened',
            'taskCourses',
            'otherCourses'
        ));
    }
}
