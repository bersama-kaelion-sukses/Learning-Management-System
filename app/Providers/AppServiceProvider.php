<?php

namespace App\Providers;

use Illuminate\Support\Facades\View;
use Illuminate\Support\Facades\App;
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
    public function boot(): void
    {
        
        App::setLocale(session('locale', 'en'));

        require_once app_path('helpers.php');
        
        View::composer('layouts.master', function ($view) {

            \Log::info('🟢 View composer triggered for layouts.master');

            $user = Auth::user();

            // ======================================================
            // 🧩 0️⃣ Handle jika belum login
            // ======================================================
            if (!$user) {
                \Log::info('⚪ No user logged in.');
                $view->with([
                    'notifications' => collect(),
                    'hasNewNotif'   => false,
                    'latestNotifId' => null,
                ]);
                return;
            }

            \Log::info('👤 Current user detected', [
                'user_id' => $user->user_id ?? $user->id,
                'name'    => $user->full_name ?? $user->name ?? 'Unknown'
            ]);

            // ======================================================
            // 1️⃣ Generate notifikasi baru (trigger check)
            // ======================================================
            $this->generateNotificationsFor($user);

            // ======================================================
            // 2️⃣ Ambil data notifikasi dari database
            // ======================================================
            $notificationsQuery = UserNotification::where('user_id', $user->user_id ?? $user->id)
                ->where('is_read', false)
                ->latest('created_at');

            $notifications = $notificationsQuery->get();
            $hasNewNotif   = $notifications->where('is_read', false)->isNotEmpty();

            // 🔹 Ambil ID notifikasi terbaru
            $latestNotif   = (clone $notificationsQuery)->first();
            $latestNotifId = $latestNotif?->notification_id ?? null;

            \Log::info('📬 Notifications loaded for view', [
                'count'   => $notifications->count(),
                'hasNew'  => $hasNewNotif,
                'latestId'=> $latestNotifId,
            ]);

            // ======================================================
            // 3️⃣ Kirim semua data ke Blade
            // ======================================================
            $view->with([
                'notifications' => $notifications,
                'hasNewNotif'   => $hasNewNotif,
                'latestNotifId' => $latestNotifId,
            ]);
        });
    }
    /**
     * 🔔 Hanya untuk menulis notifikasi ke DB (bukan render)
     */
    private function generateNotificationsFor($user)
    {
        \Log::info('⚡ generateNotificationsFor() called', [
            'user_id' => $user->user_id ?? $user->id
        ]);

        // --- Ambil semua sumber data ---
        $consultations = ConsultationRequest::with(['learner', 'trainer'])
            ->where('status_consultation', 'progress')
            ->where('learner_id', $user->user_id)
            ->latest('updated_at')
            ->get();

        $enrollments = CourseEnrollment::with(['user', 'course'])
            ->where('user_id', $user->user_id)
            ->where('status_join', true)
            ->latest('updated_at')
            ->get();

        $attachments = CourseAttachmentSubmission::with(['item.module.course'])
            ->where('user_id', $user->user_id)
            ->whereNotNull('grade')
            ->latest('submitted_at')
            ->get();

        $essays = CourseEssaySubmission::with(['item.module.course'])
            ->where('user_id', $user->user_id)
            ->where('is_graded', true)
            ->latest('updated_at')
            ->get();

        $takeovers = CourseTakeover::with(['course', 'oldTrainer', 'newTrainer'])
            ->where(function ($q) use ($user) {
                $q->where('old_trainer_id', $user->user_id)
                  ->orWhere('new_trainer_id', $user->user_id);
            })
            ->whereIn('status', ['active', 'reverted'])
            ->latest('updated_at')
            ->get();

        $approvalRequests = ApprovalRequest::with(['requester'])
            ->where('requester_id', $user->user_id)
            ->whereIn('status', ['Approved Final', 'Rejected', 'Cancelled'])
            ->latest('updated_at')
            ->get();

        $approvalRoutes = ApprovalRoute::with(['request'])
            ->where('approver_user_id', $user->user_id)
            ->latest('updated_at')
            ->get();

        // --- Log jumlah hasil per sumber ---
        \Log::info('📦 Data counts per source', [
            'consultations' => $consultations->count(),
            'enrollments'   => $enrollments->count(),
            'attachments'   => $attachments->count(),
            'essays'        => $essays->count(),
            'takeovers'     => $takeovers->count(),
            'approvalReq'   => $approvalRequests->count(),
            'approvalRoute' => $approvalRoutes->count(),
        ]);

        // --- Gabungkan semua hasil ---
        $merged = collect()
            ->concat($consultations)
            ->concat($enrollments)
            ->concat($attachments)
            ->concat($essays)
            ->concat($takeovers)
            ->concat($approvalRequests)
            ->concat($approvalRoutes);

        \Log::info('🧩 Total merged items', ['count' => $merged->count()]);

        foreach ($merged as $item) {
            \Log::info('🔁 Processing item', [
                'type' => get_class($item),
                'id' => $item->id ?? $item->submission_id ?? $item->request_id ?? $item->route_id ?? $item->enrollment_id?? $item->takeover_id?? null
            ]);

            [$sourceType, $sourceId, $message] = $this->buildMessage($item);

            if (!$message || !$sourceType || !$sourceId) {
                \Log::warning('⚠️ Skipped incomplete message', [
                    'class' => get_class($item),
                    'sourceType' => $sourceType,
                    'sourceId' => $sourceId,
                    'message' => $message,
                ]);
                continue;
            }

            $notif = UserNotification::where([
                'user_id'     => $user->user_id,
                'source_type' => $sourceType,
                'source_id'   => $sourceId,
            ])->first();

            if (!$notif) {
                try {
                    UserNotification::create([
                        'user_id'     => $user->user_id,
                        'source_type' => $sourceType,
                        'source_id'   => $sourceId,
                        'message'     => $message,
                        'redirect_url'=> $this->resolveRedirectUrl($sourceType, $sourceId),
                        'status'      => 'new',
                        'is_read'     => false,
                    ]);

                    \Log::info('🔔 Created new notification', [
                        'user_id' => $user->user_id,
                        'source_type' => $sourceType,
                        'source_id' => $sourceId,
                        'message' => $message,
                    ]);
                } catch (\Throwable $e) {
                    \Log::error('❌ Failed to create notification', [
                        'error' => $e->getMessage(),
                        'trace' => $e->getTraceAsString(),
                    ]);
                }
            } elseif ($notif->message !== $message) {
                $notif->update([
                    'message' => $message,
                    'redirect_url' => $this->resolveRedirectUrl($sourceType, $sourceId),
                    'updated_at' => now(),
                ]);

                \Log::info('📝 Updated notification message', [
                    'user_id' => $user->user_id,
                    'source_type' => $sourceType,
                    'source_id' => $sourceId,
                ]);
            } else {
                \Log::debug('⏭️ Notification unchanged — skipped', [
                    'user_id' => $user->user_id,
                    'source_type' => $sourceType,
                    'source_id' => $sourceId,
                ]);
            }
        }
    }

    /**
     * 🧩 Builder pesan per jenis source
     */
    private function buildMessage($item): array
    {
        if ($item instanceof ConsultationRequest) {
            return [
                'consultation',
                $item->consultation_id,
                "🗣️ Kepada Leaner {$item->learner->full_name} Harap menghubungi HR!"
            ];
        }

        if ($item instanceof CourseEnrollment) {
            return [
                'enrollment',
                $item->enrollment_id,
                "🎉 Selamat bergabung di Course \"{$item->course->course_title}\"."
            ];
        }

        if ($item instanceof CourseAttachmentSubmission) {
            $course = optional($item->item->module->course)->course_title ?? 'Unknown Course';
            $module = optional($item->item->module)->course_week_title ?? 'Unknown Module';
            $itemNm = $item->item->course_item_name ?? 'Unknown Item';
            return ['attachment', $item->submission_id, "📂 Tugas dinilai di {$course} / {$module} / {$itemNm}."];
        }

        if ($item instanceof CourseEssaySubmission) {
            $course = optional($item->item->module->course)->course_title ?? 'Unknown Course';
            $module = optional($item->item->module)->course_week_title ?? 'Unknown Module';
            $itemNm = $item->item->course_item_name ?? 'Unknown Item';
            return ['essay', $item->essay_submission_id, "📝 Esai dinilai di {$course} / {$module} / {$itemNm}."];
        }

        if ($item instanceof CourseTakeover) {
            $courseTitle = optional($item->course)->course_title ?? 'Unknown';
            $old = optional($item->oldTrainer)->full_name ?? 'Trainer Lama';
            $new = optional($item->newTrainer)->full_name ?? 'Trainer Baru';
            $text = $item->status === 'active'
                ? "sedang ditakeover dari {$old} ke {$new}"
                : "telah dikembalikan (reverted) dari {$new} ke {$old}";
            return ['takeover', $item->takeover_id, "🔄 Kursus \"{$courseTitle}\" {$text}."];
        }

        if ($item instanceof ApprovalRequest) {
            $statusText = match ($item->status) {
                'Approved Final' => '✅ Disetujui final',
                'Rejected'       => '❌ Ditolak',
                'Cancelled'      => '💤 Dibatalkan',
                default          => 'diperbarui',
            };
            return ['approval_request', $item->request_id, "📄 Request \"{$item->request_title}\" {$statusText}."];
        }

        if ($item instanceof ApprovalRoute) {
            $related = $item->request;
            return ['approval_route', $item->route_id, "📩 Approval baru untuk \"{$related->request_title}\" (Step {$item->step_no})."];
        }

        \Log::warning('⚠️ buildMessage() returned null', ['class' => get_class($item)]);
        return [null, null, null];
    }

   private function resolveRedirectUrl($sourceType, $sourceId)
    {
        return match ($sourceType) {

            // =====================================================
            // 🔵 ENROLLMENT — sourceId = enrollment_id → ambil course_id
            // =====================================================
            'enrollment' => (function () use ($sourceId) {
                $enroll = CourseEnrollment::find($sourceId);

                if (!$enroll) {
                    return '#';
                }

                return route('learner.course-enrolled', [
                    'id' => $enroll->course_id
                ]);
            })(),

            // =====================================================
            // 🔵 ATTACHMENT — sourceId = submission_id → ambil course_id
            // =====================================================
            'attachment' => (function () use ($sourceId) {
                $submission = CourseAttachmentSubmission::with('item.module')->find($sourceId);

                if (!$submission) {
                    return '#';
                }

                $courseId = $submission->item->module->course_id ?? null;

                return $courseId
                    ? route('learner.course-enrolled', ['id' => $courseId])
                    : '#';
            })(),

            // =====================================================
            // 🔵 ESSAY — sourceId = essay_submission_id → ambil course_id
            // =====================================================
            'essay' => (function () use ($sourceId) {
                $submission = CourseEssaySubmission::with('item.module')->find($sourceId);

                if (!$submission) {
                    return '#';
                }

                $courseId = $submission->item->module->course_id ?? null;

                return $courseId
                    ? route('learner.course-enrolled', ['id' => $courseId])
                    : '#';
            })(),

            // =====================================================
            // 🔵 TAKEOVER
            // =====================================================
            'takeover' => route('instructor.takeover'),

            // =====================================================
            // 🔵 CONSULTATION
            // =====================================================
            'consultation' => '#',

            // =====================================================
            // 🔵 APPROVAL (requester & approver)
            // =====================================================
            'approval_request' => route('approval.detail', ['id' => $sourceId]),
            'approval_route'   => route('approval.detail', ['id' => $sourceId]),

            // =====================================================
            default => '#',
        };
    }
}
