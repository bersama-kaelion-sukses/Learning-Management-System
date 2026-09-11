<?php

namespace App\Http\Controllers\LMS;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use App\Models\ConsultationRequest;
use App\Models\CourseProgress;
use App\Models\CourseType\CourseEssaySubmission;
use App\Models\CourseType\CourseAttachmentSubmission;
use App\Models\CourseType\CourseMcSubmission;
use App\Models\CourseWeekModule;
use App\Models\CourseWeekItem;

class ConsultationLearnerController extends Controller
{
    // ======================================================
    // 📋 INDEX — Menampilkan daftar konsultasi learner
    // ======================================================
    public function index()
    {
        $user = Auth::user();

        // Ambil data sesuai role
        $consultations = ConsultationRequest::with(['course', 'trainer', 'learner'])
            ->orderByDesc('created_at')
            ->get();

        return view('instructor.consultation-learner', compact('consultations'));
    }

    // ======================================================
    // ➕ STORE — Learner mengajukan konsultasi baru
    // ======================================================
    public function store(Request $request)
    {
        $validated = $request->validate([
            'course_id' => 'required|exists:course,course_id',
            'trainer_id' => 'required|exists:users,user_id',
            'topic' => 'required|string|max:255',
        ]);

        ConsultationRequest::create([
            'course_id' => $validated['course_id'],
            'trainer_id' => $validated['trainer_id'],
            'learner_id' => Auth::user()->user_id,
            'topic' => $validated['topic'],
            'status_consultation' => 'waiting',
        ]);

        // Catat log aktivitas (opsional)
        DB::table('activity_logs')->insert([
            'user_id' => Auth::id(),
            'activity_desc' => "User ID " . Auth::id() . " mengajukan konsultasi baru: " . $validated['topic'],
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return back()->with('success', 'Konsultasi berhasil diajukan.');
    }

    // ======================================================
    // 🔍 SHOW — Detail konsultasi berdasarkan ID
    // ======================================================
    public function show($id)
    {
        $consult = ConsultationRequest::with(['course', 'trainer', 'learner'])
            ->find($id);

        if (!$consult) {
            return response()->json(['error' => 'Data konsultasi tidak ditemukan.'], 404);
        }

        return response()->json($consult);
    }

    // public function updateStatus(Request $request, $id)
    // {
    //     $validated = $request->validate([
    //         'status_consultation' => 'required|in:waiting,progress,done',
    //         'feedback' => 'nullable|string|max:500',
    //     ]);

    //     $consult = ConsultationRequest::find($id);
    //     if (!$consult) {
    //         return back()->with('error', 'Konsultasi tidak ditemukan.');
    //     }

    //     // 🔄 Update status & feedback
    //     $consult->update([
    //         'status_consultation' => $validated['status_consultation'],
    //         'feedback' => $validated['feedback'] ?? $consult->feedback,
    //     ]);

    //     // 🧹 Jika status = done → hapus submission & progress yang relevan
    //     if ($validated['status_consultation'] === 'done') {
    //         try {
    //             DB::beginTransaction();

    //             $courseId  = $consult->course_id;
    //             $learnerId = $consult->learner_id;

    //             // Ambil semua item dari course yang bisa punya submission
    //             $items = DB::table('course_week_item')
    //                 ->whereIn('course_week_id', function ($q) use ($courseId) {
    //                     $q->select('course_week_id')
    //                         ->from('course_week_module')
    //                         ->where('course_id', $courseId);
    //                 })
    //                 ->whereIn('course_item_type', [3, 4, 7]) // Essay, MC, Attachment
    //                 ->get(['item_id', 'course_item_type']);

    //             $itemIds = $items->pluck('item_id')->toArray();

    //             // 🔧 Hapus submission per jenis
    //             CourseEssaySubmission::where('user_id', $learnerId)
    //                 ->whereIn('item_id', $itemIds)
    //                 ->delete();

    //             CourseMcSubmission::where('user_id', $learnerId)
    //                 ->whereIn('item_id', $itemIds)
    //                 ->delete();

    //             CourseAttachmentSubmission::where('user_id', $learnerId)
    //                 ->whereIn('item_id', $itemIds)
    //                 ->delete();

    //             // 🧭 Hapus progress hanya untuk course_item_id yang sesuai
    //             CourseProgress::where('course_id', $courseId)
    //                 ->where('user_id', $learnerId)
    //                 ->whereIn('course_item_id', $itemIds)
    //                 ->delete();

    //             DB::commit();

    //             \Log::info("🧹 Data learner berhasil dibersihkan setelah konsultasi selesai", [
    //                 'consult_id'   => $id,
    //                 'course_id'    => $courseId,
    //                 'learner_id'   => $learnerId,
    //                 'deleted_item' => count($itemIds),
    //             ]);
    //         } catch (\Exception $e) {
    //             DB::rollBack();

    //             \Log::error("❌ Gagal hapus data setelah konsultasi selesai: " . $e->getMessage(), [
    //                 'consult_id' => $id,
    //                 'course_id'  => $consult->course_id,
    //                 'learner_id' => $consult->learner_id,
    //                 'trace'      => $e->getTraceAsString(),
    //             ]);

    //             return back()->with('error', 'Terjadi kesalahan saat membersihkan data learner.');
    //         }
    //     }

    //     return back()->with('success', 'Status konsultasi berhasil diperbarui.');
    // }

    public function updateStatus(Request $request, $id)
    {
        $validated = $request->validate([
            'status_consultation' => 'required|in:waiting,progress,done',
            'feedback' => 'nullable|string|max:500',
        ]);

        $consult = ConsultationRequest::find($id);
        if (!$consult) {
            return back()->with('error', 'Konsultasi tidak ditemukan.');
        }

        // 🔄 Update status & feedback
        $consult->update([
            'status_consultation' => $validated['status_consultation'],
            'feedback' => $validated['feedback'] ?? $consult->feedback,
        ]);

        // 🧭 Jika status = done → tandai semua submission terkait menjadi remedial
        if ($validated['status_consultation'] === 'done') {
            try {
                DB::beginTransaction();
                

                $courseId  = $consult->course_id;
                $learnerId = $consult->learner_id;

                // Ambil semua item dari course yang bisa punya submission
                $items = DB::table('course_week_item')
                    ->whereIn('course_week_id', function ($q) use ($courseId) {
                        $q->select('course_week_id')
                            ->from('course_week_module')
                            ->where('course_id', $courseId);
                    })
                    ->whereIn('course_item_type', [3, 4, 7]) // Essay, MC, Attachment
                    ->get(['item_id', 'course_item_type', 'passing_grade']);

                $itemIds = $items->pluck('item_id')->toArray();

                // // 🔧 Update semua submission menjadi is_remedial = 1
                // $essayUpdated = CourseEssaySubmission::where('user_id', $learnerId)
                //     ->whereIn('item_id', $itemIds)
                //     ->update(['is_remedial' => 1]);

                // $mcUpdated = CourseMcSubmission::where('user_id', $learnerId)
                //     ->whereIn('item_id', $itemIds)
                //     ->update(['is_remedial' => 1]);

                // $attachUpdated = CourseAttachmentSubmission::where('user_id', $learnerId)
                //     ->whereIn('item_id', $itemIds)
                //     ->update(['is_remedial' => 1]);

                $essayUpdated = 0;
                $mcUpdated = 0;
                $attachUpdated = 0;

                foreach($items as $item) {
                    $passing = (float) $item->passing_grade;

                    // 1️⃣ Essay Submission
                    $essays = CourseEssaySubmission::where('user_id', $learnerId)
                        ->where('item_id', $item->item_id)
                        ->get();

                    foreach ($essays as $e) {
                        if (is_null($e->grade)) continue; // skip kalau belum ada nilai
                        if ($e->is_remedial == 1) continue;
                        if ($e->grade < $passing) {
                            $e->update(['is_remedial' => 1]);
                            $essayUpdated++;
                        }
                    }

                    // 2️⃣ MC Submission
                    $mcs = CourseMcSubmission::where('user_id', $learnerId)
                        ->where('item_id', $item->item_id)
                        ->get();

                    foreach ($mcs as $m) {
                        if (is_null($m->grade)) continue;
                        if ($m->is_remedial == 1) continue;
                        if ($m->grade < $passing) {
                            $m->update(['is_remedial' => 1]);
                            $mcUpdated++;
                        }
                    }
                    // 3️⃣ Attachment Submission
                    $atts = CourseAttachmentSubmission::where('user_id', $learnerId)
                        ->where('item_id', $item->item_id)
                        ->get();

                    foreach ($atts as $a) {
                        if (is_null($a->grade)) continue;
                        if ($a->is_remedial == 1) continue;
                        if ($a->grade < $passing) {
                            $a->update(['is_remedial' => 1]);
                            $attachUpdated++;
                        }
                    }
                }

                DB::commit();

                \Log::info("✅ Semua submission learner ditandai remedial setelah konsultasi selesai", [
                    'consult_id'    => $id,
                    'course_id'     => $courseId,
                    'learner_id'    => $learnerId,
                    'essay_updated' => $essayUpdated,
                    'mc_updated'    => $mcUpdated,
                    'attachment_updated' => $attachUpdated,
                    'item_count'    => count($itemIds),
                ]);
            } catch (\Exception $e) {
                DB::rollBack();

                \Log::error("❌ Gagal flag remedial setelah konsultasi selesai: " . $e->getMessage(), [
                    'consult_id' => $id,
                    'course_id'  => $consult->course_id,
                    'learner_id' => $consult->learner_id,
                    'trace'      => $e->getTraceAsString(),
                ]);

                return back()->with('error', 'Terjadi kesalahan saat menandai remedial learner.');
            }
        }

        return back()->with('success', 'Status konsultasi berhasil diperbarui.');
    }
}
