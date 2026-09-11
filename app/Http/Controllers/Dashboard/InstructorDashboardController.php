<?php

namespace App\Http\Controllers\Dashboard;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\User;
use App\Models\CourseEnrollment;
use App\Models\Course;
use App\Models\LoginSession;
use App\Models\CourseProgress;
use App\Models\CourseType\CourseEssaySubmission;
use App\Models\CourseType\CourseAttachmentSubmission;
use App\Models\CourseType\CourseMcSubmission;
use App\Models\CourseType\CourseForumDiscussionReply;
use App\Models\CourseWeekItem;
use App\Models\CourseLearnerActivity;
use Illuminate\Support\Facades\DB;

class InstructorDashboardController extends Controller
{
    public function index()
    {
        $user = Auth::user();

        // ============================================================
        // 🔹 1. Ambil semua course yang diajar instructor ini
        // ============================================================
        $courses = Course::where('course_trainer_id', $user->user_id)
            ->where('is_deleted', 0)
            ->withCount([
                // Semua learner yang join
                'enrollments as total_learners_count',

                // 🟢 Active learner → sudah buka course (is_opened = 1)
                'enrollments as active_learners_count' => function ($q) {
                    $q->whereHas('activity', function ($a) {
                        $a->where('is_opened', 1);
                    });
                },

                // ⚪ Non-active learner → join tapi belum buka course
                'enrollments as non_active_learners_count' => function ($q) {
                    $q->whereDoesntHave('activity')
                      ->orWhereHas('activity', function ($a) {
                          $a->where('is_opened', 0);
                      });
                },
            ])
            ->get();

        // ============================================================
        // 🔹 2. Hitung total learner aktif & nonaktif di semua course
        // ============================================================
        $getActiveLearner    = $courses->sum('active_learners_count');
        $getNonActiveLearner = $courses->sum('non_active_learners_count');
        $getCourseInstructor = $courses;

        // 🔹 Tambahkan daftar ID course untuk query berikutnya
        $courseIds = $courses->pluck('course_id');

        // ============================================================
        // 🔹 3. Average completion (dari table progress)
        // ============================================================
        $getAverageCompletionCourses = round(
            CourseProgress::whereIn('course_id', $courseIds)->avg('progress_pct') ?? 0,
            2
        );

        // ============================================================
        // 🔹 4. Ambil submission terbaru dari semua jenis
        // ============================================================
        $essaySubs = CourseEssaySubmission::with(['user', 'item.course'])
            ->whereHas('item', fn($q) => $q->whereIn('course_id', $courseIds))
            ->latest()
            ->take(10)
            ->get()
            ->map(fn($s) => [
                'user'   => $s->user->full_name ?? $s->user->name,
                'action' => 'mengumpulkan essay',
                'course' => $s->item?->course?->course_title ?? '-',
                'time'   => $s->created_at,
                'grade'  => $s->grade ?? null,
            ]);

        $attachSubs = CourseAttachmentSubmission::with(['user', 'item.course'])
            ->whereHas('item', fn($q) => $q->whereIn('course_id', $courseIds))
            ->latest('submitted_at')
            ->take(10)
            ->get()
            ->map(fn($s) => [
                'user'   => $s->user->full_name ?? $s->user->name,
                'action' => 'mengunggah lampiran',
                'course' => $s->item?->course?->course_title ?? '-',
                'time'   => $s->submitted_at,
                'grade'  => $s->grade ?? null,
            ]);

        $mcSubs = CourseMcSubmission::with(['user', 'item.course'])
            ->whereHas('item', fn($q) => $q->whereIn('course_id', $courseIds))
            ->latest()
            ->take(10)
            ->get()
            ->map(fn($s) => [
                'user'   => $s->user->full_name ?? $s->user->name,
                'action' => 'mengerjakan kuis',
                'course' => $s->item?->course?->course_title ?? '-',
                'time'   => $s->created_at,
                'grade'  => $s->grade ?? null,
            ]);

        $forumSubs = CourseForumDiscussionReply::with(['user', 'discussion.item.course'])
            ->whereHas('discussion.item', fn($q) => $q->whereIn('course_id', $courseIds))
            ->latest()
            ->take(10)
            ->get()
            ->map(fn($s) => [
                'user'   => $s->user->full_name ?? $s->user->name,
                'action' => 'membalas forum',
                'course' => $s->discussion?->item?->course?->course_title ?? '-',
                'time'   => $s->created_at,
                'grade'  => null,
            ]);

        // ============================================================
        // 🔹 5. Gabungkan semua aktivitas dan urutkan
        // ============================================================
        $recentActivities = collect()
            ->merge($essaySubs)
            ->merge($attachSubs)
            ->merge($mcSubs)
            ->merge($forumSubs)
            ->sortByDesc('time')
            ->take(15)
            ->values();

        // ============================================================
        // 🔹 6. Hitung tugas menunggu penilaian
        // ============================================================
        $getWaitingGrading = collect()
            ->merge($essaySubs)
            ->merge($attachSubs)
            ->merge($mcSubs)
            ->filter(fn($s) => is_null($s['grade']))
            ->count();

        // ============================================================
        // 🔹 7. Hitung aktivitas/tugas yang akan datang (H+7)
        // ============================================================
        $now = now();
        $nextWeek = $now->copy()->addDays(7);

        $getUpcomingActivity = CourseWeekItem::whereIn('course_id', $courseIds)
            ->whereNotNull('course_due_start')
            ->whereBetween('course_due_start', [$now, $nextWeek]) // ⏳ dalam 7 hari ke depan
            ->count();

        // ============================================================
        // 🔹 8. Daftar tugas yang belum dikumpulkan learner (type 3,4,7)
        // ============================================================
        $waitingAssignments = CourseWeekItem::with(['course', 'module', 'submissions'])
            ->whereIn('course_id', $courseIds)
            ->whereIn('course_item_type', [3, 4, 7]) // 🎯 hanya materi tipe Essay, MC, Attachment
            ->get()
            ->map(function ($item) {
                $totalLearners  = $item->course->enrollments()->count(); // total peserta yang join
                $submittedCount = $item->submissions->count();           // jumlah learner yang sudah submit
                $pendingCount   = max($totalLearners - $submittedCount, 0);

                // kalau semua sudah submit → jangan tampilkan
                if ($pendingCount === 0) {
                    return null;
                }

                // label tipe materi
                $typeLabel = match ((int) $item->course_item_type) {
                    3 => '🧠 Essay',
                    4 => '📝 Kuis',
                    7 => '📎 Lampiran',
                    default => '📘 Materi',
                };

                return [
                    'course'    => $item->course->course_title ?? '-',
                    'week'      => $item->module->course_week_title ?? '-',
                    'item'      => "{$typeLabel}: " . ($item->course_item_name ?? '-'),
                    'submitted' => "{$submittedCount}/{$totalLearners}",
                    'status'    => "{$pendingCount} belum submit",
                    'badge'     => 'warning',
                ];
            })
            ->filter()
            ->values();


        // ============================================================
        // 🔹 9. Return ke View
        // ============================================================
        return view('dashboard.component.instructor-dashboard', compact(
            'courses',
            'getActiveLearner',
            'getNonActiveLearner',
            'getCourseInstructor',
            'getWaitingGrading',
            'getUpcomingActivity',
            'getAverageCompletionCourses',
            'recentActivities',
            'waitingAssignments'
        ));
    }
}
