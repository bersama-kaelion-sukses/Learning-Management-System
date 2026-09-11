<?php

namespace App\Http\Controllers\LMS;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Course;
use App\Models\CourseWeekItem;
use App\Models\CourseWeekModule;
use App\Models\CourseEnrollment;
use App\Models\CourseProgress;
use App\Models\CourseType\CourseEssaySubmission;
use App\Models\CourseType\CourseEssay;
use App\Models\CourseType\CourseMcSubmission;
use App\Models\CourseType\CourseTypeQuestion;
use App\Models\CourseType\CourseForumDiscussion;
use App\Models\CourseType\CourseForumDiscussionReply;
use App\Models\CourseType\CourseAttachmentSubmission;
use App\Models\Thread\ForumReply;

use Illuminate\Support\Facades\Log;


class LearnerSubmissionRealeaseController extends Controller
{
    public function index(Request $request)
    {
        // Ambil semua course untuk dropdown
        $courses = Course::where('is_deleted', 0)
        ->orderBy('created_at', 'desc')
        ->get();

        // Jika user sudah pilih course → ambil modulnya
        $selectedCourseId = $request->get('course_id');
        $modules = collect(); // default kosong

        if ($selectedCourseId) {
            $modules = CourseWeekModule::where('course_id', $selectedCourseId)
                ->select('course_week_id', 'course_week_title', 'week_order')
                ->orderBy('week_order', 'asc')
                ->with(['items' => function ($query) {
                    $query->select(
                        'item_id',
                        'course_week_id',
                        'course_item_name',
                        'course_item_type',
                        'course_describe',
                        'course_duration',
                        'passing_grade',
                        'course_due_start',
                        'course_due_end'
                    )->orderBy('item_id', 'asc');
                }])
                ->get();
        }

        return view('administrator.realeased', compact('courses', 'modules', 'selectedCourseId'));
    }
   public function showSubmissions($courseId, $itemId)
    {
        try {
            // ============================================================
            // 1️⃣ Dapatkan Item dan Tipe Materi
            // ============================================================
            $item = CourseWeekItem::where('course_id', $courseId)
                ->where('item_id', $itemId)
                ->firstOrFail();

            $itemType = (int) $item->course_item_type;

            // ============================================================
            // 2️⃣ Dapatkan List User yang Terdaftar di Course (Enrollment)
            // ============================================================
            $enrolledUsers = CourseEnrollment::where('course_id', $courseId)
                ->whereHas('user', function ($query) {
                    $query->where('role_id', '!=', 1)
                        ->where(function ($q) {
                            $q->whereNull('sub_role')                // jika null → tetap tampil
                                ->orWhereJsonLength('sub_role', 0)     // jika []
                                ->orWhereRaw("JSON_CONTAINS(sub_role, '1') = 0"); // tidak mengandung 1
                        });
                })
                ->with('user:user_id,full_name,role_id,sub_role')
                ->get()
                ->pluck('user');

            // ============================================================
            // 3️⃣ Tentukan Model Submission Berdasarkan Type Materi
            // ============================================================
            switch ($itemType) {
                case 3: // Essay
                    $submissionModel = CourseEssaySubmission::class;
                    break;
                case 4: // Multiple Choice
                    $submissionModel = CourseMcSubmission::class;
                    break;
                case 7: // Attachment Upload
                    $submissionModel = CourseAttachmentSubmission::class;
                    break;
                default:
                    return response()->json([
                        'success' => false,
                        'message' => "Materi ini tidak perlu Submission.",
                    ], 400);
            }

            // ============================================================
            // 4️⃣ Ambil Submission Berdasarkan Item ID
            // ============================================================
            $submissions = $submissionModel::where('item_id', $itemId)
                ->where('is_remedial', 0)
                ->with('user:user_id,full_name')
                ->get();

            // ============================================================
            // 5️⃣ Gabungkan Informasi: siapa yang sudah & belum submit
            // ============================================================
            $result = $enrolledUsers->map(function ($user) use ($submissions) {
                $submission = $submissions->firstWhere('user_id', $user->user_id);

                return [
                    'user_id' => $user->user_id,
                    'full_name' => $user->full_name,
                    'is_submitted' => (bool) $submission,
                    'submitted_at' => $submission->created_at ?? null,
                    'grade' => $submission->grade ?? null,
                    'status' => $submission->status ?? null,
                ];
            });

            // ============================================================
            // 6️⃣ Kirim Respon JSON
            // ============================================================
            return response()->json([
                'success' => true,
                'course_id' => $courseId,
                'item_id' => $itemId,
                'item_name' => $item->course_item_name,
                'item_type' => $itemType,
                'submissions' => $result,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Terjadi kesalahan: ' . $e->getMessage(),
            ], 500);
        }
    }
//    public function deleteSubmission($courseId, $itemId, $userId)
//     {
//         try {
//             Log::info('🗑️ Delete request diterima', compact('courseId', 'itemId', 'userId'));

//             // =======================================================
//             // 1️⃣ Validasi & Identifikasi tipe item
//             // =======================================================
//             $item = CourseWeekItem::findOrFail($itemId);
//             $model = match ((int)$item->course_item_type) {
//                 3 => \App\Models\CourseType\CourseEssaySubmission::class,
//                 4 => \App\Models\CourseType\CourseMcSubmission::class,
//                 7 => \App\Models\CourseType\CourseAttachmentSubmission::class,
//                 default => null
//             };

//             if (!$model) {
//                 Log::warning('❌ Invalid item type', ['item_type' => $item->course_item_type]);
//                 return response()->json([
//                     'success' => false,
//                     'message' => 'Invalid item type.'
//                 ], 400);
//             }

//             Log::info('📘 Model yang digunakan', ['model' => $model]);

//             // =======================================================
//             // 2️⃣ Hapus hanya submission dengan is_remedial = 0
//             // =======================================================
//             $deleted = $model::where('item_id', $itemId)
//                 ->where('user_id', $userId)
//                 ->where('is_remedial', 0)
//                 ->delete();

//             Log::info('🧹 Hasil delete submission', ['deleted' => $deleted]);

//             // =======================================================
//             // 3️⃣ Jika submission berhasil dihapus → hapus progress
//             // =======================================================
//             if ($deleted) {
//                 $progressDeleted = \App\Models\CourseProgress::where('course_id', $courseId)
//                     ->where('course_item_id', $itemId)
//                     ->where('user_id', $userId)
//                     ->delete();

//                 Log::info('📉 Progress terkait dihapus', [
//                     'course_id' => $courseId,
//                     'item_id' => $itemId,
//                     'user_id' => $userId,
//                     'progress_deleted' => $progressDeleted
//                 ]);

//                 return response()->json([
//                     'success' => true,
//                     'message' => 'Submission (non-remedial) dan progress berhasil dihapus.',
//                     'deleted_submission' => $deleted,
//                     'deleted_progress' => $progressDeleted
//                 ]);
//             }

//             // =======================================================
//             // 4️⃣ Jika tidak ada submission ditemukan
//             // =======================================================
//             return response()->json([
//                 'success' => false,
//                 'message' => 'Submission non-remedial tidak ditemukan untuk dihapus.'
//             ]);

//         } catch (\Exception $e) {
//             Log::error('🔥 Error delete submission', [
//                 'error' => $e->getMessage(),
//                 'course_id' => $courseId,
//                 'item_id' => $itemId,
//                 'user_id' => $userId
//             ]);

//             return response()->json([
//                 'success' => false,
//                 'message' => 'Terjadi kesalahan saat menghapus submission: ' . $e->getMessage()
//             ], 500);
//         }
//     }

    public function deleteSubmission($courseId, $itemId, $userId)
    {
        try {
            Log::info('♻️ Request ubah status remedial diterima', compact('courseId', 'itemId', 'userId'));

            // =======================================================
            // 1️⃣ Validasi & Identifikasi tipe item
            // =======================================================
            $item = CourseWeekItem::findOrFail($itemId);
            $model = match ((int)$item->course_item_type) {
                3 => \App\Models\CourseType\CourseEssaySubmission::class,
                4 => \App\Models\CourseType\CourseMcSubmission::class,
                7 => \App\Models\CourseType\CourseAttachmentSubmission::class,
                default => null
            };

            if (!$model) {
                Log::warning('❌ Invalid item type', ['item_type' => $item->course_item_type]);
                return response()->json([
                    'success' => false,
                    'message' => 'Invalid item type.'
                ], 400);
            }

            Log::info('📘 Model yang digunakan', ['model' => $model]);

            // =======================================================
            // 2️⃣ Update flag is_remedial dari 0 → 1
            // =======================================================
            $updated = $model::where('item_id', $itemId)
                ->where('user_id', $userId)
                ->where('is_remedial', 0)
                ->update(['is_remedial' => 1]);

            Log::info('📝 Hasil update remedial', ['updated_rows' => $updated]);

            // =======================================================
            // 3️⃣ Respon hasil
            // =======================================================
            if ($updated > 0) {
                return response()->json([
                    'success' => true,
                    'message' => 'Submission berhasil diubah ke status remedial (is_remedial = 1).',
                    'updated_submission' => $updated
                ]);
            }

            return response()->json([
                'success' => false,
                'message' => 'Tidak ada submission dengan is_remedial = 0 yang ditemukan untuk diperbarui.'
            ]);

        } catch (\Exception $e) {
            Log::error('🔥 Error update remedial submission', [
                'error' => $e->getMessage(),
                'course_id' => $courseId,
                'item_id' => $itemId,
                'user_id' => $userId
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Terjadi kesalahan saat memperbarui submission: ' . $e->getMessage()
            ], 500);
        }
    }

}
