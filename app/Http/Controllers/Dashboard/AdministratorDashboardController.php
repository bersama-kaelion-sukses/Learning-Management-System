<?php

namespace App\Http\Controllers\Dashboard;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\DB;
use App\Models\User;
use App\Models\Course;
use App\Models\CourseEnrollment;
use App\Models\CourseProgress;
use App\Models\LoginSession;

class AdministratorDashboardController extends Controller
{
    public function index()
    {
        // ==========================
        // 1) User stats
        // ==========================
        $totalUsers  = User::count();
        $activeUsers = User::where('is_active', 1)->count();

        // ==========================
        // 2) Course stats
        // ==========================
        $totalCourses   = Course::count();
        $activeCourses  = Course::where('is_approved', 1)->where('is_deleted', 0)->count();
        $completedCourses = CourseProgress::where('progress_pct', '>=', 100)
            ->distinct('course_id')
            ->count('course_id');

        // Average completion rate (all courses)
        $completeCourses = round(
            CourseProgress::avg('progress_pct') ?? 0,
            2
        );

        // ==========================
        // 3) Login stats
        // ==========================
        $dailyLogins = LoginSession::whereDate('login_time', today())->distinct('user_id')->count('user_id');
        $weeklyLogins = LoginSession::whereBetween('login_time', [now()->startOfWeek(), now()->endOfWeek()])
            ->distinct('user_id')
            ->count('user_id');

        // ==========================
        // 4) Instructors
        // ==========================
        $instructorCourse = Course::select('course_trainer_id')
            ->selectRaw('COUNT(*) as total_courses')
            ->groupBy('course_trainer_id')
            ->with('trainer')
            ->get();

        $instructorActive = User::where('is_active', 1)
            ->where(function ($q) {
                $q->where('role_id', 3)
                  ->orWhereJsonContains('sub_role', 3);
            })
            ->count();

        // ==========================
        // 5) Learners
        // ==========================
        $participantCount = User::where(function ($q) {
            $q->where('role_id', 4)->orWhereJsonContains('sub_role', 4);
        })->count();

        $requestCourseCount = Course::where('is_approved', 0)
            ->where('is_requested', 1)
            ->where('is_draft', 1)
            ->count();

        $activeToday = LoginSession::whereDate('login_time', today())
            ->join('users', 'login_sessions.user_id', '=', 'users.user_id')
            ->where(function($q) {
                $q->where('users.role_id', 4)
                ->orWhereJsonContains('users.sub_role', 4);
            })
            ->distinct('login_sessions.user_id')
            ->count('login_sessions.user_id');

        $activeWeek = LoginSession::whereBetween('login_time', [now()->startOfWeek(), now()->endOfWeek()])
            ->join('users', 'login_sessions.user_id', '=', 'users.user_id')
            ->where(function($q) {
                $q->where('users.role_id', 4)
                ->orWhereJsonContains('users.sub_role', 4);
            })
            ->distinct('login_sessions.user_id')
            ->count('login_sessions.user_id');
            
        // ==========================
        // 6) Extra: Pending Data
        // ==========================

        // Semua learner dengan progress rata-rata
        $reportDetails = User::where('role_id', 4)
            ->with('division', 'enrollments.course')
            ->get()
            ->map(function ($learner) {
                $courseIds = $learner->enrollments->pluck('course_id');
                $progress = CourseProgress::where('user_id', $learner->user_id)
                    ->whereIn('course_id', $courseIds)
                    ->avg('progress_pct');
                return [
                    'name'     => $learner->full_name,
                    'progress' => round($progress ?? 0, 2),
                ];
            });

        // Learner dengan progress rendah (<60%)
        $lowProgressLearners = $reportDetails->filter(fn($l) => $l['progress'] < 60)
            ->sortBy('progress')
            ->values();

        // Kursus dengan completion rendah
        $lowProgressCourses = Course::where('is_approved', 1)
            ->get()
            ->map(function ($course) {
                $avg = CourseProgress::where('course_id', $course->course_id)->avg('progress_pct');
                return [
                    'course'   => $course->course_title,
                    'progress' => round($avg ?? 0, 2),
                ];
            })
            ->filter(fn($c) => $c['progress'] < 60)
            ->sortBy('progress')
            ->values();

        // Kursus dengan completion rendah per instruktur
        $lowProgressInstructorCourses = Course::with('trainer')
            ->get()
            ->map(function ($course) {
                $avg = CourseProgress::where('course_id', $course->course_id)->avg('progress_pct');
                return [
                    'course'     => $course->course_title,
                    'instructor' => $course->trainer->full_name ?? 'Unknown',
                    'progress'   => round($avg ?? 0, 2),
                ];
            })
            ->filter(fn($c) => $c['progress'] < 60)
            ->sortBy('progress')
            ->values();

        // ==========================
        // Kirim ke View
        // ==========================
        return view('dashboard.component.hr-dashboard', compact(
            'totalUsers',
            'activeUsers',
            'totalCourses',
            'activeCourses',
            'completeCourses',
            'completedCourses',
            'dailyLogins',
            'weeklyLogins',
            'instructorCourse',
            'instructorActive',
            'participantCount',
            'requestCourseCount',
            'activeToday',
            'activeWeek',
            'reportDetails',
            'lowProgressLearners',
            'lowProgressCourses',
            'lowProgressInstructorCourses'
        ));
    }
}
