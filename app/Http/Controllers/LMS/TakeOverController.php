<?php

namespace App\Http\Controllers\LMS;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use App\Models\CourseTakeover;
use App\Models\Course;
use App\Models\User;
use App\Models\CourseWeekModule;
use App\Models\CourseWeekItem;
use App\Models\CourseType\CourseEssay;
use App\Models\CourseType\CourseTypeOption;
use App\Models\CourseType\CourseTypeQuestion;
use App\Models\Thread\ForumThread;
use Carbon\Carbon;


class TakeoverController extends Controller
{
    /**
     * Halaman Takeover (Instructor)
     */
    // public function takeoverPage()
    // {
    //     $user = Auth::user();
    //     $now  = Carbon::now();
        
    //     // Ambil semua takeover di mana user ini adalah trainer baru
    //     $takeovers = CourseTakeover::with('course')
    //         ->where('new_trainer_id', $user->user_id)
    //         ->get();

    //     foreach ($takeovers as $takeover) {
    //         $startDate = $takeover->start_date
    //             ? Carbon::parse($takeover->start_date)->startOfDay()
    //             : null;

    //         $endDate = $takeover->end_date
    //             ? Carbon::parse($takeover->end_date)->endOfDay()
    //             : null;

    //         $newStatus = $takeover->status;

    //         // 🚫 Skip kalau sudah selesai atau reverted
    //         if (in_array($takeover->status, ['completed', 'reverted'])) {
    //             continue;
    //         }

    //         // ✅ Case 1: Hari ini masih sebelum start → pending
    //         if ($startDate && $now->lt($startDate)) {
    //             $newStatus = 'pending';
    //         }

    //         // ✅ Case 2: Hari ini adalah hari-H atau antara start–end → active
    //         elseif ($startDate && $endDate && $now->between($startDate, $endDate)) {
    //             $newStatus = 'active';

    //             if ($takeover->status !== 'active') {
    //                 $course = $takeover->course;

    //                 if ($course) {
    //                     $course->update([
    //                         'is_takeover' => 1,
    //                     ]);
    //                 }
    //             }
    //         }

    //         // ✅ Case 3: Kalau sudah lewat H+1 → revert
    //         elseif ($endDate && $now->greaterThan($endDate->copy()->addDay())) {
    //             $newStatus = 'reverted';

    //             if ($takeover->status !== 'reverted') {
    //                 $course = $takeover->course;

    //                 if ($course && $takeover->old_trainer_id) {
    //                     $oldTrainer = User::find($takeover->old_trainer_id);

    //                     if ($oldTrainer) {
    //                         $course->update([
    //                             'course_trainer_id'   => $oldTrainer->user_id,
    //                             'course_trainer_name' => $oldTrainer->full_name,
    //                             'is_takeover'         => 0,
    //                         ]);
    //                     }
    //                 }
    //             }
    //         }

    //         // 🧩 Update status jika berubah
    //         if ($takeover->status !== $newStatus) {
    //             $takeover->update(['status' => $newStatus]);
    //         }
    //     }

    //     // 🔑 Cek apakah super admin
    //     $isSuperAdmin = ($user->role_id == 1 || $user->sub_role == 1);

    //     // 🔍 Filter data untuk tampilan
    //     if ($isSuperAdmin) {
    //         $filteredTakeovers = CourseTakeover::with('course')
    //             ->where('new_trainer_id', $user->user_id)
    //             ->get();
    //     } else {
    //         $filteredTakeovers = CourseTakeover::with('course')
    //             ->where('new_trainer_id', $user->user_id)
    //             ->where('status', 'active')
    //             ->get();
    //     }

    //     return view('instructor.takeover', [
    //         'takeovers'    => $filteredTakeovers,
    //         'isSuperAdmin' => $isSuperAdmin,
    //     ]);
    // }

    public function takeoverPage() {
        $user = Auth::user();
        $now = Carbon::now();

        // ====================================================
        // 🔍 Access Check: SuperAdmin bisa melihat semua
        // ====================================================
        $isSuperAdmin = ($user->role_id == 1 || $user->sub_role == 1);

        // ====================================================
        // 🎯 Ambil Takeover sesuai role
        // ====================================================
        $takeovers = CourseTakeover::with(['course', 'oldTrainer', 'newTrainer'])
            ->when(!$isSuperAdmin, function ($q) use ($user) {
                // 🧑‍🏫 Trainer hanya melihat takeover miliknya
                $q->where('new_trainer_id', $user->user_id);
            })
            ->orderBy('created_at', 'desc')
            ->get();
        
            // ====================================================
            // 🔁 AUTO UPDATE STATUS
            // ====================================================
            foreach ($takeovers as $takeover) {

                // Skip kalau sudah selesai final
                if (in_array($takeover->status, ['completed', 'reverted'])) {
                    continue;
                }

                $startDate = $takeover->start_date
                    ? Carbon::parse($takeover->start_date)->startOfDay()
                    : null;

                $endDate = $takeover->end_date
                    ? Carbon::parse($takeover->end_date)->endOfDay()
                    : null;

                $newStatus = $takeover->status;
                $course    = $takeover->course;

                // Case: sebelum mulai → PENDING
                if ($startDate && $now->lt($startDate)) {
                    $newStatus = 'pending';
                }

                // Case: antara start dan end → ACTIVE
                elseif ($startDate && $endDate && $now->between($startDate, $endDate)) {

                    $newStatus = 'active';

                    if ($takeover->status !== 'active' && $course) {
                        $course->update(['is_takeover' => 1]);
                    }
                }

                // Case: sudah lewat +1 hari → REVERT
                elseif ($endDate && $now->greaterThan($endDate->copy()->addDay())) {

                    $newStatus = 'reverted';

                    if ($takeover->status !== 'reverted' && $course && $takeover->old_trainer_id) {
                        $oldTrainer = User::find($takeover->old_trainer_id);

                        if ($oldTrainer) {
                            $course->update([
                                'course_trainer_id'   => $oldTrainer->user_id,
                                'course_trainer_name' => $oldTrainer->full_name,
                                'is_takeover'         => 0,
                            ]);
                        }
                    }
                }

                if ($takeover->status !== $newStatus) {
                    $takeover->update(['status' => $newStatus]);
                }
            }

            // ====================================================
            // 🎯 Filter Final Untuk Ditampilkan ke UI
            // ====================================================
            if ($isSuperAdmin) {

                // 👑 SuperAdmin → SEMUA TAKEOVER
                $filteredTakeovers = $takeovers;

            } else {

                // 🧑‍🏫 Trainer → hanya pending + active
                $filteredTakeovers = $takeovers->filter(function ($t) {
                    return in_array($t->status, ['pending', 'active']);
                });
            }

            return view('instructor.takeover', [
                'takeovers'    => $filteredTakeovers,
                'isSuperAdmin' => $isSuperAdmin,
            ]);

    }
    
    /**
     * Akhiri takeover (manual revert)
     */
    public function finish($id)
    {
        $takeover = CourseTakeover::findOrFail($id);
        $takeover->status = 'reverted';
        $takeover->save();

        $course = $takeover->course;

        if ($course) {
            $oldTrainer = User::find($takeover->old_trainer_id);

            if ($oldTrainer) {
                $course->update([
                    'course_trainer_id'   => $oldTrainer->user_id,
                    'course_trainer_name' => $oldTrainer->full_name,
                    'is_takeover'         => 0,
                ]);
            }
        }

        return redirect()->back()->with('success', 'Takeover kursus berhasil diselesaikan.');
    }

    public function editDepanTakeover(Request $request, $id)
    {
        $validated = $request->validate([
            'course_title'    => 'required|string|max:255',
            'course_category' => 'required|string|max:255',
            'max_participant' => 'required|integer|min:1',
            'course_describe' => 'required|string',
            'is_public'       => 'required|boolean',
            'start_course'    => 'nullable|date',
            'end_course'      => 'nullable|date|after_or_equal:start_course',
            'course_image'    => 'nullable|image|mimes:jpg,jpeg,png,webp|max:2048',
        ]);

        $course = Course::findOrFail($id);

        // 🔒 Simpan data trainer lama sebelum update agar tidak berubah
        $oldTrainerId   = $course->course_trainer_id;
        $oldTrainerName = $course->course_trainer_name;

        // 🟡 Update data course seperti biasa
        $course->course_title    = $validated['course_title'];
        $course->course_category = $validated['course_category'];
        $course->max_participant = $validated['max_participant'];
        $course->course_describe = $validated['course_describe'];
        $course->is_public       = $validated['is_public'];

        if ($request->has('start_course')) {
            $value = $request->input('start_course');
            $course->start_course = ($value === null || $value === '') 
                ? null 
                : \Carbon\Carbon::parse($value)->format('Y-m-d H:i:s');
        }

        if ($request->has('end_course')) {
            $value = $request->input('end_course');
            $course->end_course = ($value === null || $value === '') 
                ? null 
                : \Carbon\Carbon::parse($value)->format('Y-m-d H:i:s');
        }

        // 🚫 Jangan ubah trainer di mode takeover (tetap pakai data lama)
        $course->course_trainer_id   = $oldTrainerId;
        $course->course_trainer_name = $oldTrainerName;

        // 📷 Update gambar kalau ada file baru
        if ($request->hasFile('course_image')) {
            $filename = time() . '_' . $request->file('course_image')->getClientOriginalName();
            $request->file('course_image')->move(public_path('assets/img/course'), $filename);
            $course->course_image = $filename;
        }

        $course->save();

        // 🧾 Log optional untuk debugging
        \Log::info('✏️ [Takeover Edit] Course berhasil diperbarui tanpa mengubah trainer.', [
            'course_id' => $course->course_id,
            'trainer_id' => $course->course_trainer_id,
            'trainer_name' => $course->course_trainer_name,
        ]);

        return redirect()
            ->back()
            ->with('success', 'Kursus berhasil diperbarui tanpa mengubah data trainer.');
    }

    public function duplicate($id) 
    {
        DB::beginTransaction();

        try {
            // Ambil User sekarang yang sedang login 
            $getUser = Auth::user();

            // 1️⃣ Ambil course asli beserta seluruh week dan item-nya
            $original = Course::with(['weeks.items.questions.options', 'weeks.items.essay'])->findOrFail($id);

            // 2️⃣ Buat salinan course
            $newCourse = $original->replicate();
            $newCourse -> course_trainer_id = $getUser->user_id;
            $newCourse -> course_trainer_name = $getUser->full_name;
            $newCourse->course_title   = 'Copy - ' . $original->course_title;
            $newCourse->person_process = Auth::user()->emp_id ?? Auth::id();
            $newCourse->is_requested = false;
            $newCourse->is_takeover = false;
            $newCourse->last_process   = now();
            $newCourse->created_at     = now();
            $newCourse->updated_at     = now();
            $newCourse->save();

            Log::info("📘 [COURSE COPY] '{$original->course_title}' -> '{$newCourse->course_title}' (course_id={$newCourse->course_id})");

            // 3️⃣ Duplikat setiap minggu (module)
            foreach ($original->weeks as $week) {
                $newWeek = $week->replicate();
                $newWeek->course_id = $newCourse->course_id;
                $newWeek->created_at = now();
                $newWeek->updated_at = now();
                $newWeek->save();

                Log::info("📗 [WEEK COPY] Week '{$week->course_week_title}' disalin ke course_id={$newCourse->course_id} (week_id={$newWeek->course_week_id})");

                // 4️⃣ Duplikat setiap item di minggu itu
                foreach ($week->items as $item) {
                    $newItem = $item->replicate();
                    $newItem->course_id      = $newCourse->course_id;
                    $newItem->course_week_id = $newWeek->course_week_id;
                    $newItem->created_at     = now();
                    $newItem->updated_at     = now();
                    $newItem->save();

                    if (!$newItem->item_id) {
                        Log::error("❌ [ITEM COPY] Gagal menyimpan item '{$item->course_item_name}'");
                        continue;
                    }

                    Log::info("🧩 [ITEM COPY] Item '{$item->course_item_name}' disalin ke item_id={$newItem->item_id}");

                    // 5️⃣ Duplikat Essay (jika ada)
                    if ($item->essay) {
                        try {
                            $oldEssay = $item->essay;
                            $newEssay = $oldEssay->replicate();
                            $newEssay->item_id = $newItem->item_id;
                            $newEssay->created_at = now();
                            $newEssay->updated_at = now();
                            $newEssay->save();

                            Log::info("✏️ [ESSAY COPY] Essay '{$oldEssay->essay_title}' berhasil disalin ke item_id={$newItem->item_id}");
                        } catch (\Throwable $th) {
                            Log::error("❌ [ESSAY COPY] Gagal duplikasi essay untuk item_id {$item->item_id}: {$th->getMessage()}");
                        }
                    }

                    // 6️⃣ Duplikat MC Question + Options
                    if ($item->questions && $item->questions->count() > 0) {
                        foreach ($item->questions as $question) {
                            try {
                                $newQuestion = $question->replicate();
                                $newQuestion->item_id = $newItem->item_id;
                                $newQuestion->created_at = now();
                                $newQuestion->updated_at = now();
                                $newQuestion->save();

                                Log::info("🧠 [QUESTION COPY] Question '{$question->question_text}' disalin ke item_id={$newItem->item_id} (new_question_id={$newQuestion->question_id})");

                                if ($question->options && $question->options->count() > 0) {
                                    foreach ($question->options as $opt) {
                                        $newOpt = $opt->replicate();
                                        $newOpt->question_id = $newQuestion->question_id;
                                        $newOpt->created_at = now();
                                        $newOpt->updated_at = now();
                                        $newOpt->save();
                                    }
                                    Log::info("🔘 [OPTION COPY] {$question->options->count()} opsi disalin ke question_id={$newQuestion->question_id}");
                                }
                            } catch (\Throwable $th) {
                                Log::error("❌ [QUESTION COPY] Gagal duplikasi question_id={$question->question_id}: {$th->getMessage()}");
                            }
                        }
                    }
                }
            }

            // ============================================================
            // 7️⃣ Duplikat Thread (level Course, bukan Week)
            // ============================================================
            $threads = ForumThread::where('course_id', $original->course_id)
                ->where(function ($q) {
                    $q->whereNull('forum_title')
                    ->orWhereRaw("forum_title NOT LIKE 'Copy - %'");
                })
                ->get();

            if ($threads->count() > 0) {
                Log::info("💬 [THREAD COPY] Menemukan {$threads->count()} thread pada course_id={$original->course_id}");
                foreach ($threads as $thread) {
                    try {
                        // Hindari duplikasi berulang
                        $exists = ForumThread::where('course_id', $newCourse->course_id)
                            ->where('forum_title', $thread->forum_title)
                            ->exists();

                        if ($exists) {
                            Log::warning("⚠️ [THREAD SKIP] Thread '{$thread->forum_title}' sudah ada di course_id={$newCourse->course_id}");
                            continue;
                        }

                        $newThread = $thread->replicate();
                        $newThread->course_id = $newCourse->course_id;
                        $newThread->forum_title = 'Copy - ' . $thread->forum_title;
                        $newThread->created_at = now();
                        $newThread->updated_at = now();
                        $newThread->save();

                        Log::info("💬 [THREAD COPY] Thread '{$thread->forum_title}' berhasil disalin ke course_id={$newCourse->course_id} (thread_id={$newThread->thread_id})");
                    } catch (\Throwable $th) {
                        Log::error("❌ [THREAD COPY] Gagal duplikasi thread_id={$thread->thread_id}: {$th->getMessage()}");
                    }
                }
            } else {
                Log::info("ℹ️ [THREAD COPY] Tidak ada thread ditemukan pada course_id={$original->course_id}");
            }

            // ✅ Commit
            DB::commit();

            return redirect()
                ->route('instructor.course')
                ->with('success', "✅ Kursus '{$original->course_title}' berhasil diduplikasi sebagai '{$newCourse->course_title}'.");

        } catch (\Throwable $e) {
            DB::rollBack();
            Log::error("❌ [COURSE COPY FAILED] {$e->getMessage()}");
            return redirect()
                ->back()
                ->with('error', '❌ Gagal menduplikasi kursus: ' . $e->getMessage());
        }
    }
    
    public function revert($id)
    {
        $takeover = CourseTakeover::findOrFail($id);

        // hanya boleh revert jika pending
        if ($takeover->status !== 'pending') {
            return back()->with('error', 'Takeover tidak bisa dibatalkan.');
        }

        $takeover->status = 'reverted';
        $takeover->save();

        return back()->with('success', 'Takeover berhasil dibatalkan.');
    }
}
