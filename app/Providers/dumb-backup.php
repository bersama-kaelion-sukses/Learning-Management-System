<?php

namespace App\Providers;

use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Auth;
use App\Models\ConsultationRequest;
use App\Models\CourseEnrollment;
use App\Models\CourseType\CourseAttachmentSubmission;
use App\Models\CourseType\CourseEssaySubmission;
use App\Models\ApprovalSystem\ApprovalRoute;
use App\Models\ApprovalSystem\ApprovalRequest;
use App\Models\CourseTakeover;
use App\Models\UserNotification;

use Carbon\Carbon;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        View::composer('layouts.master', function ($view) {
            $user = Auth::user();

            if ($user) {
                // ======================================================
                // 1️⃣ Consultation Notifications (status = progress)
                // ======================================================
                $consultations = ConsultationRequest::with(['learner', 'trainer'])
                    ->where('status_consultation', 'progress')
                    ->where('learner_id', $user->user_id)
                    ->orderBy('updated_at', 'desc')
                    ->get();

                // ======================================================
                // 2️⃣ Enrollment Notifications (≤ H+1)
                // ======================================================
                $enrollments = CourseEnrollment::with(['user', 'course'])
                    ->where('user_id', $user->user_id)
                    ->where('status_join', true)
                    ->where('updated_at', '>=', Carbon::now()->subDay())
                    ->orderBy('updated_at', 'desc')
                    ->get();

                // ======================================================
                // 3️⃣ Attachment Submission Graded (≤ H+1)
                // ======================================================
                $attachmentGraded = CourseAttachmentSubmission::with(['item.module.course'])
                    ->where('user_id', $user->user_id)
                    ->whereNotNull('grade')
                    ->where('submitted_at', '>=', Carbon::now()->subDay())
                    ->orderBy('submitted_at', 'desc')
                    ->get();

                // ======================================================
                // 4️⃣ Essay Submission Graded (≤ H+1)
                // ======================================================
                $essayGraded = CourseEssaySubmission::with(['item.module.course'])
                    ->where('user_id', $user->user_id)
                    ->where('is_graded', true)
                    ->where('updated_at', '>=', Carbon::now()->subDay())
                    ->orderBy('updated_at', 'desc')
                    ->get();

                // ======================================================
                // 5️⃣ Course Takeover Notifications (status active/reverted)
                // ======================================================
                $takeovers = CourseTakeover::with(['course', 'oldTrainer', 'newTrainer'])
                    ->where(function ($q) use ($user) {
                        $q->where('old_trainer_id', $user->user_id)
                          ->orWhere('new_trainer_id', $user->user_id);
                    })
                    ->whereIn('status', ['active', 'reverted'])
                    ->orderBy('updated_at', 'desc')
                    ->take(5)
                    ->get();

                // ======================================================
                // 6️⃣ Approval Notifications (drafter & approver)
                // ======================================================

                // Drafter (requester)
                $approvalRequests = ApprovalRequest::with(['requester'])
                    ->where('requester_id', $user->user_id)
                    ->whereIn('status', ['Approved Final', 'Rejected', 'Cancelled'])
                    ->orderBy('updated_at', 'desc')
                    ->take(5)
                    ->get();

                // Approver
                $approvalRoutes = ApprovalRoute::with(['request'])
                    ->where('approver_user_id', $user->user_id)
                    ->where('action_status', 'In Review')
                    ->orderBy('updated_at', 'desc')
                    ->take(5)
                    ->get();

                // ======================================================
                // Gabungkan semua ke satu koleksi
                // ======================================================
                $merged = collect()
                    ->concat($consultations)
                    ->concat($enrollments)
                    ->concat($attachmentGraded)
                    ->concat($essayGraded)
                    ->concat($takeovers)
                    ->concat($approvalRequests)
                    ->concat($approvalRoutes)
                    ->sortByDesc('updated_at')
                    ->take(15);

                // ======================================================
                // Map ke array siap tampil di Blade
                // ======================================================
                $notifications = $merged->map(function ($item) use ($user) {
                    // 🗣️ Consultation
                    if ($item instanceof ConsultationRequest) {
                        return [
                            'id'      => 'consult-' . $item->consultation_id,
                            'message' => "🗣️ Konsultasi Learner telah dibuka, Learner \"{$item->learner->full_name}\" harap menemui Instructor \"{$item->trainer->full_name}\" segera.",
                            'time'    => $item->updated_at->diffForHumans(),
                            'status'  => ucfirst($item->status_consultation ?? 'Progress'),
                            'type'    => 'consultation',
                        ];
                    }

                    // 🎉 Enrollment
                    if ($item instanceof CourseEnrollment) {
                        return [
                            'id'      => 'enroll-' . $item->enrollment_id,
                            'message' => "🎉 Selamat Anda telah tergabung di Course \"{$item->course->course_title}\".",
                            'time'    => $item->updated_at->diffForHumans(),
                            'status'  => 'Active',
                            'type'    => 'enrollment',
                        ];
                    }

                    // 📂 Attachment Graded
                    if ($item instanceof CourseAttachmentSubmission) {
                        $course = optional(optional(optional($item->item)->module)->course)->course_title ?? 'Unknown Course';
                        $module = optional($item->item->module)->course_week_title ?? 'Unknown Module';
                        $itemNm = $item->item->course_item_name ?? 'Unknown Item';
                        return [
                            'id'      => 'attach-' . $item->submission_id,
                            'message' => "📂 Tugas Anda telah dinilai di Course \"{$course}\", Modul \"{$module}\", Item \"{$itemNm}\".",
                            'time'    => Carbon::parse($item->submitted_at)->diffForHumans(),
                            'status'  => 'Graded',
                            'type'    => 'graded',
                        ];
                    }

                    // 📝 Essay Graded
                    if ($item instanceof CourseEssaySubmission) {
                        $course = optional(optional(optional($item->item)->module)->course)->course_title ?? 'Unknown Course';
                        $module = optional($item->item->module)->course_week_title ?? 'Unknown Module';
                        $itemNm = $item->item->course_item_name ?? 'Unknown Item';
                        return [
                            'id'      => 'essay-' . $item->essay_submission_id,
                            'message' => "📝 Tugas Anda telah dinilai di Course \"{$course}\", Modul \"{$module}\", Item \"{$itemNm}\".",
                            'time'    => $item->updated_at->diffForHumans(),
                            'status'  => 'Graded',
                            'type'    => 'graded',
                        ];
                    }

                    // 🔄 Course Takeover
                    if ($item instanceof \App\Models\CourseTakeover) {
                        $courseTitle = optional($item->course)->course_title ?? 'Unknown Course';
                        $oldTrainer  = optional($item->oldTrainer)->full_name ?? 'Trainer Lama';
                        $newTrainer  = optional($item->newTrainer)->full_name ?? 'Trainer Baru';
                        $start       = optional($item->start_date)?->format('d M Y') ?? '-';
                        $end         = optional($item->end_date)?->format('d M Y') ?? '-';

                        $statusText = $item->status === 'active'
                            ? "sedang ditakeover dari {$oldTrainer} ke {$newTrainer}, periode {$start} s.d. {$end}"
                            : "telah dikembalikan (reverted) dari {$newTrainer} ke {$oldTrainer} pada {$end}";

                        return [
                            'id'      => 'takeover-' . $item->takeover_id,
                            'message' => "🔄 Kursus \"{$courseTitle}\" {$statusText}.",
                            'time'    => $item->updated_at?->diffForHumans() ?? '-',
                            'status'  => ucfirst($item->status),
                            'type'    => 'takeover',
                        ];
                    }

                    // ✅ Approval Request selesai (untuk drafter)
                    if ($item instanceof \App\Models\ApprovalSystem\ApprovalRequest) {
                        $statusText = match ($item->status) {
                            'Approved Final' => 'telah disetujui final ✅',
                            'Rejected'       => 'ditolak ❌',
                            'Cancelled'      => 'dibatalkan 💤',
                            default          => 'diperbarui',
                        };

                        return [
                            'id'      => 'req-' . $item->request_id,
                            'message' => "📄 Request \"{$item->request_title}\" {$statusText}.",
                            'time'    => $item->updated_at?->diffForHumans() ?? '-',
                            'status'  => $item->status,
                            'type'    => 'approval_request',
                        ];
                    }

                    // 📩 Approval Route baru masuk (untuk approver)
                    if ($item instanceof \App\Models\ApprovalSystem\ApprovalRoute) {
                        $related = $item->request;
                        return [
                            'id'      => 'route-' . $item->route_id,
                            'message' => "📩 Anda memiliki approval baru untuk Request \"{$related->request_title}\" (Step {$item->step_no}).",
                            'time'    => $item->updated_at?->diffForHumans() ?? '-',
                            'status'  => $item->action_status ?? 'In Review',
                            'type'    => 'approval_route',
                        ];
                    }

                    return null;
                })->filter();

                // ======================================================
                // Kirim ke view
                // ======================================================
                $hasNewNotif = $notifications->isNotEmpty();

                $view->with([
                    'notifications' => $notifications,
                    'hasNewNotif'   => $hasNewNotif,
                ]);
            } else {
                $view->with([
                    'notifications' => collect(),
                    'hasNewNotif'   => false,
                ]);
            }
        });
    }
}
