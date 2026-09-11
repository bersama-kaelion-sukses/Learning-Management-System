<?php

namespace App\Http\Controllers\LMS;

use Carbon\Carbon;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use App\Models\Course;
use App\Models\User;
use App\Models\CourseEnrollment;
use App\Models\CourseWeekModule;
use App\Models\CourseWeekItem;
use App\Models\CourseProgress;
use App\Models\CourseLearnerActivity;
use App\Models\CourseType\CourseEssay;
use App\Models\CourseType\CourseEssaySubmission;
use App\Models\CourseType\CourseAttachmentSubmission;
use App\Models\CourseType\CourseMcSubmission;
use App\Models\CourseType\CourseTypeOption;
use App\Models\CourseType\CourseTypeQuestion;
use App\Models\CourseType\CourseForumDiscussion;
use App\Models\Thread\ForumThread;
use App\Models\Thread\ForumReply;
use App\Models\CourseTakeover;
use App\Models\Division;



class CourseController extends Controller
{
    public function requested(Request $request)
    {
        $validated = $request->validate([
            'course_title'      => 'required|string|max:255',
            'course_category'   => 'required|string|max:100',
            'course_describe'   => 'required|string',
            'max_participant'   => 'required|integer|min:1',
            'start_coure'       => 'nullable|date',
            'end_course'        => 'nullable|date|after_or_equal:start_course',
            'course_image'      => 'nullable|image|max:2048',
            'course_trainer_id' => 'required|exists:users,user_id', // 🔥 ambil dari form
        ]);

        if ($request->filled('start_course')) {
            $validated['start_course'] = Carbon::parse($request->start_course)->format('Y-m-d H:i:s');
        }

        if ($request->filled('end_course')) {
            $validated['end_course'] = Carbon::parse($request->end_course)->format('Y-m-d H:i:s');
        }
        // Cari trainer berdasarkan user_id
        $trainer = User::findOrFail($validated['course_trainer_id']);

        $courseData = [
            'course_title'        => $validated['course_title'],
            'course_category'     => $validated['course_category'],
            'course_trainer_id'   => $trainer->user_id,                     // simpan user_id
            'course_trainer_name' => $trainer->full_name ?? $trainer->name, // simpan nama
            'course_describe'     => $validated['course_describe'],
            'max_participant'     => $validated['max_participant'],
            'start_course'        => $validated['start_course'],
            'end_course'          => $validated['end_course'],
            'is_approved'         => false,
            'is_public'           => false,
            'is_requested'        => true,
            'is_draft'            => true,
            'last_process'        => now(),
            'person_process'      => Auth::user()->emp_id ?? Auth::id(),
        ];

        // Handle upload gambar
        if ($request->hasFile('course_image')) {
            $imageName = time() . '_' . $request->file('course_image')->getClientOriginalName();
            $request->file('course_image')->move(public_path('assets/img/course'), $imageName);
            $courseData['course_image'] = $imageName;
        } else {
            $courseData['course_image'] = 'default-course.jpeg';
        }

        // Simpan course
        $course = Course::create($courseData);

        return redirect()->back()->with('success', 'Permohonan kursus baru berhasil diajukan.');
    }
    public function index(Request $request)
    {
        $user       = Auth::user();
        $userId     = $user->user_id ?? $user->id;
        $userName   = $user->full_name ?? $user->name;

        // Gabungkan role utama & sub role
        $allRoles = array_merge(
            [$user->role_id],
            is_array($user->sub_role) ? $user->sub_role : (json_decode($user->sub_role, true) ?: [])
        );

        $isIT = in_array(1, $allRoles); // ✅ role 1 = IT (Super Admin)

        // =============================
        // 🔹 Ambil daftar course
        // =============================
        $coursesQuery = Course::query()
            ->where('is_deleted', 0)
            ->where('is_draft', 0);

        // Jika bukan IT, tampilkan hanya kursus miliknya
        if (!$isIT) {
            $coursesQuery->where(function ($q) use ($userId, $userName) {
                $q->where('course_trainer_id', $userId)
                ->orWhere('course_trainer_name', $userName);
            });
        }

        // =============================
        // 🔹 Tambahkan learner_count per course
        // =============================
        $courses = $coursesQuery
            ->latest('updated_at')
            ->get()
            ->map(function ($course) {
                // Gambar fallback
                $file = $course->course_image ? public_path('assets/img/course/' . $course->course_image) : null;
                $course->course_image_url = (!$course->course_image || !file_exists($file))
                    ? asset('assets/img/course/default-course.jpeg')
                    : asset('assets/img/course/' . $course->course_image);

                // Hitung learner aktif (exclude IT)
                $learnerCount = $course->enrollments()
                    ->whereHas('user', function ($q) {
                        $q->where('role_id', '!=', 1)
                        ->orWhereJsonDoesntContain('sub_role', 1);
                    })
                    ->count();

                $course->learner_count = $learnerCount;

                // Gunakan untuk validasi max_participant (frontend)
                $course->max_participant_hint = $course->max_participant < $learnerCount
                    ? "⚠️ Tidak boleh di bawah $learnerCount learner aktif"
                    : null;

                return $course;
            });

        // =============================
        // 🔹 Course list untuk Takeover modal
        // =============================
        $consulCourses = Course::query()
            ->where('is_deleted', 0)
            ->where('is_draft', 0)
            ->get();

        return view('instructor.course', compact('courses', 'consulCourses'));
    }
    public function editDepan(Request $request, $id)
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
        
        // update trainer id & name
        $course->course_trainer_id   = Auth::user()->user_id;
        $course->course_trainer_name = Auth::user()->full_name ?? Auth::user()->name;

        if ($request->hasFile('course_image')) {
            // buat nama unik untuk file baru
            $filename = time() . '_' . $request->file('course_image')->getClientOriginalName();
            $request->file('course_image')->move(public_path('assets/img/course'), $filename);

            // simpan hanya nama file ke DB
            $course->course_image = $filename;
        }

        $course->save();

        return redirect()
            ->route('instructor.course')
            ->with('success', 'Kursus berhasil diperbarui');
    }
    public function takeoverByInstructor(Request $request)
    {
        // ============================
        // 🔍 VALIDASI INPUT
        // ============================
        $validated = $request->validate([
            'takeover.course_id'      => 'required|integer|exists:course,course_id',
            'takeover.old_trainer_id' => 'required|integer|exists:users,user_id',
            'takeover.new_trainer_id' => 'required|integer|exists:users,user_id',
            'takeover.start_date'     => 'required|date',
            'takeover.end_date'       => 'required|date|after_or_equal:takeover.start_date',
            'takeover.remarks'        => 'nullable|string|max:500',
        ]);

        $data = $validated['takeover'];

        // ============================
        // 🛑 CEK: Tidak boleh takeover diri sendiri
        // ============================
        if ($data['old_trainer_id'] == $data['new_trainer_id']) {
            return back()->with('error', 
                'Takeover tidak dapat dilakukan karena Anda memilih diri sendiri sebagai pengganti.'
            );
        }

        // ============================
        // 💾 SIMPAN DATA TAKEOVER
        // ============================
        CourseTakeover::create([
            'course_id'      => $data['course_id'],
            'old_trainer_id' => $data['old_trainer_id'],
            'new_trainer_id' => $data['new_trainer_id'],
            'start_date'     => $data['start_date'],
            'end_date'       => $data['end_date'],
            'remarks'        => $data['remarks'] ?? null,
            'status'         => 'pending',
        ]);

        return redirect()->route('instructor.course')
            ->with('success', 'Takeover berhasil disimpan (status pending).');
    }
    public function store(Request $request)
    {
        $validated = $request->validate([
            'course_title'        => 'required|string|max:255',
            'course_category'     => 'required|string|max:100',
            'course_describe'     => 'required|string',
            'max_participant'     => 'required|integer|min:1',
            'course_image'        => 'nullable|image|max:2048', 
            'start_coure'         => 'nullable|date',
            'end_course'          => 'nullable|date|after_or_equal:start_course',
            'course_trainer_id'   => 'nullable|exists:users,user_id', 
        ]);

        if ($request->filled('start_course')) {
            $validated['start_course'] = Carbon::parse($request->start_course)->format('Y-m-d H:i:s');
        }

        if ($request->filled('end_course')) {
            $validated['end_course'] = Carbon::parse($request->end_course)->format('Y-m-d H:i:s');
        }

        // 🔥 Cek apakah ada trainer dipilih manual
        if ($request->filled('course_trainer_id')) {
            $trainer = User::findOrFail($request->course_trainer_id);
            $validated['course_trainer_id']   = $trainer->user_id; // simpan user_id trainer
            $validated['course_trainer_name'] = $trainer->full_name ?? $trainer->name;
        } else {
            // fallback → user login
            $validated['course_trainer_id']   = Auth::id();
            $validated['course_trainer_name'] = Auth::user()->full_name ?? Auth::user()->name;
        }

        // Status default
        $validated['is_approved']    = false;
        $validated['is_public']      = false;
        $validated['is_draft']       = false;
        $validated['last_process']   = now();
        $validated['person_process'] = Auth::user()->emp_id ?? Auth::id();

        // Simpan gambar ke public/assets/img/course
        if ($request->hasFile('course_image')) {
            $imageName = time() . '_' . $request->file('course_image')->getClientOriginalName();
            $request->file('course_image')->move(public_path('assets/img/course'), $imageName);
            $validated['course_image'] = $imageName;
        } else {
            $validated['course_image'] = 'default-course.jpeg';
        }

        $course = Course::create($validated);

        // Tambahkan URL gambar untuk response
        $course->course_image_url = asset('assets/img/course/' . ($course->course_image ?: 'default-course.jpeg'));

        return redirect()->back()->with('success', 'Course baru berhasil ditambahkan.');
    }
    public function update(Request $request, string $id)
    {
        $course = Course::findOrFail($id);

        $validated = $request->validate([
            'course_title'        => 'sometimes|required|string|max:255',
            'course_category'     => 'sometimes|required|string|max:100',
            'course_describe'     => 'sometimes|required|string',
            'max_participant'     => 'sometimes|required|integer|min:1',
            'course_image'        => 'nullable|image|max:2048',
            'is_approved'         => 'boolean',
            'is_public'           => 'boolean',
        ]);

        // Jika upload gambar baru → hapus yang lama jika ada
        if ($request->hasFile('course_image')) {
            if ($course->course_image && file_exists(public_path('assets/img/course/' . $course->course_image))) {
                // Jangan hapus default
                if ($course->course_image !== 'default-course.jpeg') {
                    unlink(public_path('assets/img/course/' . $course->course_image));
                }
            }

            $imageName = time() . '_' . $request->file('course_image')->getClientOriginalName();
            $request->file('course_image')->move(public_path('assets/img/course'), $imageName);
            $validated['course_image'] = $imageName;
        }

        // Update metadata proses
        $validated['last_process']   = now();
        $validated['person_process'] = Auth::user()->user_id ?? Auth::user()->emp_id ?? Auth::id();

        $course->update($validated);

        // Tambahkan URL gambar untuk response
        $file = $course->course_image ? public_path('assets/img/course/' . $course->course_image) : null;
        $course->course_image_url = file_exists($file)
            ? asset('assets/img/course/' . $course->course_image)
            : asset('assets/img/course/default-course.jpeg');

        return response()->json([
            'message' => 'Course updated successfully',
            'data'    => $course
        ]);
    }
    public function destroy(string $id)
    {
        $course = Course::findOrFail($id);

        // Flagging saja (custom soft delete)
        $course->forceFill([
            'is_deleted'     => 1,
            'last_process'   => now(),
            'person_process' => Auth::user()->user_id ?? Auth::user()->emp_id ?? Auth::id(),
        ])->save();

         return redirect()->back()->with('success', 'Course berhasil dihapus.');
    }
    public function takedown($id, Request $request)
    {
        try {
            // 🔍 Ambil data course langsung dari parameter id
            $course = Course::findOrFail($id);

            // ⚙️ Update status menjadi takedown
            $course->update([
                'is_approved'   => 0,
                'last_process'  => now(),
                'person_process'=> Auth::user()->emp_id ?? Auth::id(),
            ]);

            return redirect()->back()->with('success', "Kursus '{$course->course_title}' berhasil di-takedown.");

        } catch (\Exception $e) {
            return redirect()->back()->with('error', 'Gagal melakukan takedown: ' . $e->getMessage());
        }
    }
    public function duplicate($id) 
    {
        DB::beginTransaction();

        try {
            // 1️⃣ Ambil course asli beserta seluruh week dan item-nya
            $original = Course::with(['weeks.items.questions.options', 'weeks.items.essay'])->findOrFail($id);

            // 2️⃣ Buat salinan course
            $newCourse = $original->replicate();
            $newCourse->course_title   = 'Copy - ' . $original->course_title;
            $newCourse->person_process = Auth::user()->emp_id ?? Auth::id();
            $newCourse->last_process   = now();
            $newCourse->is_approved    = false;
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
                ->back()
                ->with('success', "✅ Kursus '{$original->course_title}' berhasil diduplikasi sebagai '{$newCourse->course_title}'.");

        } catch (\Throwable $e) {
            DB::rollBack();
            Log::error("❌ [COURSE COPY FAILED] {$e->getMessage()}");
            return redirect()
                ->back()
                ->with('error', '❌ Gagal menduplikasi kursus: ' . $e->getMessage());
        }
    }
    public function courseModuleWeek(Request $request, $id)
    {
        $course = Course::findOrFail($id);

        // buat module baru
        $module = CourseWeekModule::create([
            'course_id'            => $course->course_id,
            'course_week_title'    => $request->input('course_week_title', 'Bagian Baru'),
            'course_week_visibility' => $request->input('course_week_visibility', 1),
            'week_order'           => $request->input('week_order', 1),
            'course_start'         => $request->input('course_start'),
            'course_end'           => $request->input('course_end'),
            'is_checked'           => $request->input('is_checked', 0),
            'person_process'       => $request->input('person_process', 'system'),
        ]);

        return response()->json([
            'success'    => true,
            'module_id'  => $module->course_week_id,
            'module'     => $module
        ]);
    }
    public function courseWeekItem(Request $request, $courseWeekId)
    {
        $module = CourseWeekModule::findOrFail($courseWeekId);
        
        // STEP 1 — Fix existing NULL item_order to maintain proper sequence
        $existingItems = CourseWeekItem::where('course_week_id', $module->course_week_id)
            ->orderBy('item_order')
            ->orderBy('item_id') // fallback to guarantee order
            ->get();

        $order = 1;
        foreach ($existingItems as $item) {
            if ($item->item_order === null) {
                $item->update(['item_order' => $order]);
            }
            $order++;
        }

        // STEP 2 — Determine NEXT order for new item
        $nextOrder = CourseWeekItem::where('course_week_id', $module->course_week_id)
            ->max('item_order') + 1;

        if ($nextOrder < 1) {
            $nextOrder = 1;
        }
        
        $item = CourseWeekItem::create([
            'course_week_id'   => $module->course_week_id,
            'course_id'        => $module->course_id,
            'course_item_name' => $request->input('course_item_name', 'Sub-materi baru'),
            'course_describe'  => $request->input('course_describe'),
            'course_item_type' => $request->input('course_item_type', 0),
            'course_due_start' => $request->input('course_due_start'),
            'course_due_end'   => $request->input('course_due_end'),
            'course_duration'  => $request->input('course_duration'),
            'course_media'     => $request->input('course_media'),
            'course_assignment'=> $request->input('course_assignment'),
            'person_process'   => $request->input('person_process', 'system'),
            'item_order'       => $nextOrder
        ]);

        return response()->json([
            'success' => true,
            'item_id' => $item->item_id,
            'item'    => $item
        ]);
    }
    public function showListCourse(Request $request)
    {
        $courses = Course::query()
            ->where('is_deleted', 0)
            ->where('is_draft', 0)
            ->where('is_approved', 1)
            ->latest('updated_at')
            ->get()
            ->map(function ($course) {
                // Build URL gambar dengan fallback default
                $file = $course->course_image ? public_path('assets/img/course/' . $course->course_image) : null;

                if (!$course->course_image || !file_exists($file)) {
                    $course->course_image_url = asset('assets/img/course/default-course.jpeg');
                } else {
                    $course->course_image_url = asset('assets/img/course/' . $course->course_image);
                }
                return $course;
            });

        return view('administrator.course-list', compact('courses'));
    }
    public function assignUsertoCourse(Request $request)
    {
        try {
            // 1️⃣ Validasi input
            $validated = $request->validate([
                'course_id'   => 'required|integer|exists:course,course_id',
                'user_ids'    => 'required|array',
                'user_ids.*'  => 'integer|exists:users,user_id',
            ]);

            $courseId = $validated['course_id'];
            $userIds  = $validated['user_ids'];

            // 2️⃣ Ambil info course
            $course = Course::findOrFail($courseId);
            $maxParticipants = $course->max_participant;

            // Hitung peserta non-IT
            $currentCount = CourseEnrollment::where('course_id', $courseId)
                ->whereHas('user', function ($q) {
                    $q->where('role_id', '!=', 1)
                    ->where(function ($q2) {
                        $q2->whereNull('sub_role')
                            ->orWhereRaw('JSON_CONTAINS(sub_role, "1") = 0');
                    });
                })
                ->count();

            $availableSlots = $maxParticipants - $currentCount;

            if ($availableSlots <= 0) {
                return response()->json([
                    'success' => false,
                    'message' => "Kursus sudah penuh (maksimal {$maxParticipants} peserta).",
                    'enrolled' => [],
                    'excess'   => $userIds,
                ], 400);
            }

            // 3️⃣ Split user diterima dan kelebihan
            $acceptedUsers = array_slice($userIds, 0, $availableSlots);
            $excessUsers   = array_slice($userIds, $availableSlots);

            // 4️⃣ Enroll accepted learner (non-IT)
            foreach ($acceptedUsers as $userId) {
                $user = User::find($userId);

                if ($user && $user->role_id != 1) {
                    CourseEnrollment::updateOrCreate(
                        [
                            'course_id' => $courseId,
                            'user_id'   => $userId,
                        ],
                        [
                            'status_join'    => 1,
                            'is_approve'     => 1,
                            'enroll_date'    => now(),
                            'last_process'   => now(),
                            'person_process' => auth()->user()->full_name ?? 'system',
                        ]
                    );
                }
            }

            // 5️⃣ Pastikan IT & sub_role=1 ikut otomatis ter-enroll
            $autoUsers = User::where(function ($q) {
                    $q->where('role_id', 1)
                    ->orWhereJsonContains('sub_role', 1);
                })
                ->where('is_deleted', 0)
                ->where('is_active', 1)
                ->get(['user_id']);

            foreach ($autoUsers as $auto) {
                CourseEnrollment::updateOrCreate(
                    [
                        'course_id' => $courseId,
                        'user_id'   => $auto->user_id,
                    ],
                    [
                        'status_join'    => 1,
                        'is_approve'     => 1,
                        'enroll_date'    => now(),
                        'last_process'   => now(),
                        'person_process' => 'auto-assign-IT',
                    ]
                );
            }

            // 6️⃣ Hapus enrollment IT palsu kalau user berubah role
            CourseEnrollment::where('course_id', $courseId)
                ->whereHas('user', function ($q) {
                    $q->where('role_id', '!=', 1)
                    ->where(function ($q2) {
                        $q2->whereNull('sub_role')
                            ->orWhereRaw('JSON_CONTAINS(sub_role, "1") = 0');
                    });
                })
                ->where('person_process', 'auto-assign-IT')
                ->delete();

            return response()->json([
                'success'  => true,
                'message'  => count($excessUsers) > 0
                    ? "Sebagian learner berhasil di-assign, namun kursus sudah mencapai batas maksimum {$maxParticipants} peserta."
                    : "Learner berhasil di-assign ke course.",
                'enrolled' => $acceptedUsers,
                'excess'   => $excessUsers,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error: ' . $e->getMessage(),
            ], 500);
        }
    }
    public function rollbackAssignUsertoCourse(Request $request)
    {
        try {
            // 1️⃣ Validasi input
            $validated = $request->validate([
                'course_id'   => 'required|integer|exists:course,course_id',
                'user_ids'    => 'required|array',
                'user_ids.*'  => 'integer|exists:users,user_id',
            ]);

            $courseId = $validated['course_id'];
            $userIds  = $validated['user_ids'];

            Log::info('📥 Rollback request diterima', [
                'course_id' => $courseId,
                'user_ids'  => $userIds,
            ]);

            DB::transaction(function () use ($courseId, $userIds) {

                // ==========================================================
                // 🔹 1. Hapus seluruh progress learner
                // ==========================================================
                $deletedProgress = CourseProgress::where('course_id', $courseId)
                    ->whereIn('user_id', $userIds)
                    ->delete();
                Log::info('🧹 Deleted CourseProgress', ['count' => $deletedProgress]);

                // ==========================================================
                // 🔹 2. Hapus seluruh submission learner (Essay / Attachment / MC)
                // ==========================================================
                $deletedEssay = CourseEssaySubmission::whereHas('item.module', function ($q) use ($courseId) {
                        $q->where('course_id', $courseId);
                    })
                    ->whereIn('user_id', $userIds)
                    ->delete();
                Log::info('🧹 Deleted Essay Submissions', ['count' => $deletedEssay]);

                $deletedAttachment = CourseAttachmentSubmission::whereHas('item.module', function ($q) use ($courseId) {
                        $q->where('course_id', $courseId);
                    })
                    ->whereIn('user_id', $userIds)
                    ->delete();
                Log::info('🧹 Deleted Attachment Submissions', ['count' => $deletedAttachment]);

                $deletedMc = CourseMcSubmission::whereHas('item.module', function ($q) use ($courseId) {
                        $q->where('course_id', $courseId);
                    })
                    ->whereIn('user_id', $userIds)
                    ->delete();
                Log::info('🧹 Deleted MC Submissions', ['count' => $deletedMc]);

                // ==========================================================
                // 🔹 3. Hapus seluruh forum replies learner
                // ==========================================================
                try {
                    $matchedForum = ForumReply::whereHas('thread', function ($t) use ($courseId) {
                            $t->whereHas('course', function ($c) use ($courseId) {
                                $c->where('course_id', $courseId);
                            });
                        })
                        ->whereIn('replied_by', $userIds)
                        ->count();

                    Log::info('🔍 ForumReply ditemukan sebelum delete', [
                        'count' => $matchedForum,
                        'course_id' => $courseId,
                        'user_ids' => $userIds,
                    ]);

                    $deletedForum = ForumReply::whereHas('thread', function ($t) use ($courseId) {
                            $t->whereHas('course', function ($c) use ($courseId) {
                                $c->where('course_id', $courseId);
                            });
                        })
                        ->whereIn('replied_by', $userIds)
                        ->delete();

                    Log::info('🧹 Deleted Forum Replies', [
                        'deleted' => $deletedForum,
                        'expected' => $matchedForum
                    ]);

                } catch (\Throwable $ex) {
                    Log::error('❌ Error saat menghapus ForumReply', [
                        'message' => $ex->getMessage(),
                        'trace'   => $ex->getTraceAsString(),
                    ]);
                }

                // ==========================================================
                // 🔹 4. Hapus Course Activity (tracking akses learner)
                // ==========================================================
                $deletedActivity = CourseLearnerActivity::where('course_id', $courseId)
                    ->whereIn('user_id', $userIds)
                    ->delete();
                Log::info('🧹 Deleted CourseLearnerActivity', ['count' => $deletedActivity]);

                // ==========================================================
                // 🔹 5. Hapus record enrollment terakhir
                // ==========================================================
                $deletedEnroll = CourseEnrollment::where('course_id', $courseId)
                    ->whereIn('user_id', $userIds)
                    ->delete();
                Log::info('🧹 Deleted CourseEnrollment', ['count' => $deletedEnroll]);
            });

            Log::info('✅ Rollback selesai tanpa error', [
                'course_id' => $courseId,
                'user_ids'  => $userIds,
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Enrollment dan seluruh data progress learner telah dihapus.',
                'removed_user_ids' => $userIds,
            ]);

        } catch (\Throwable $e) {
            Log::error('💥 Rollback gagal', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'course_id' => $request->input('course_id'),
                'user_ids'  => $request->input('user_ids'),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Error: ' . $e->getMessage(),
            ], 500);
        }
    }
    public function getAssignUserinCourse(Request $request)
    {
        try {
            $validated = $request->validate([
                'course_id' => 'required|integer|exists:course,course_id',
            ]);

            $courseId = $validated['course_id'];

            // 🔍 Ambil semua user yang join, tapi sembunyikan IT & sub_role=1
            $assignedUsers = CourseEnrollment::with('user')
                ->where('course_id', $courseId)
                ->get()
                ->filter(function ($enroll) {
                    $user = $enroll->user;
                    if (!$user) return false;

                    // sembunyikan superadmin dan sub_role=1
                    if ($user->role_id == 1) return false;
                    if (is_array($user->sub_role) && in_array(1, $user->sub_role)) return false;

                    return true;
                })
                ->map(function ($enroll) {
                    $user = $enroll->user;
                    return [
                        'user_id'   => $user->user_id,
                        'full_name' => $user->full_name ?? '-',
                        'division'  => $user->departement_cat ?? '-',
                        'role_id'   => $user->role_id,
                        'sub_role'  => $user->sub_role,
                    ];
                })
                ->values();

            return response()->json([
                'success' => true,
                'users'   => $assignedUsers,
                'count'   => $assignedUsers->count(),
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error: ' . $e->getMessage(),
            ], 500);
        }
    }
    public function detailCourse($id)
    {
        // ===================================================
        // 📦 Ambil course + user enrollments (filter role & sub_role)
        // ===================================================
        $course = Course::with([
            'enrollments.user' => function ($q) {
                $q->select('user_id', 'full_name', 'emp_id', 'departement_cat', 'role_id', 'sub_role');
            }
        ])
        ->where('is_deleted', 0)
        ->find($id);

        if (!$course) {
            abort(404, 'Course tidak ditemukan');
        }

        // ===================================================
        // 🏢 Ambil semua division aktif → mapping [division_id => division_name]
        // ===================================================
        $divisions = Division::pluck('division_name', 'division_id')->toArray();

        // ===================================================
        // 📆 Ambil aktivitas learner (last_access per user)
        // ===================================================
        $activities = CourseLearnerActivity::where('course_id', $course->course_id)
            ->get()
            ->keyBy('user_id');

        // ===================================================
        // 🔍 Filter enrollments (hapus user role_id = 1 & sub_role = 1)
        // ===================================================
        $course->enrollments = $course->enrollments->filter(function ($enroll) {
            $user = $enroll->user;
            if (!$user) return false;

            // Role utama bukan 1
            if ((int)$user->role_id === 1) return false;

            // Sub_role tidak mengandung 1 (baik JSON maupun CSV)
            $sub = $user->sub_role ?? [];
            if (is_string($sub)) {
                $decoded = json_decode($sub, true);
                $sub = $decoded ?: explode(',', $sub);
            }

            // Pastikan array integer
            $sub = array_map('intval', (array)$sub);
            return !in_array(1, $sub);
        })
        ->values()
        ->transform(function ($enroll) use ($divisions, $activities) {
            $user = $enroll->user;
            if ($user) {
                $user->division_label = $divisions[$user->departement_cat] ?? 'Tidak Diketahui';
            }

            // Tambahkan last_access dari tabel aktivitas
            $activity = $activities[$enroll->user_id] ?? null;
            $enroll->last_access = $activity->last_access ?? null;

            return $enroll;
        });

        // ===================================================
        // 👤 Ambil trainer
        // ===================================================
        $trainer = User::where('user_id', $course->course_trainer_id)->first();

        // ===================================================
        // 👥 Hitung peserta aktif & tergabung (mengikuti filter)
        // ===================================================
        $filteredUserIds = $course->enrollments->pluck('user.user_id')->filter()->unique();

        $activeCount = CourseLearnerActivity::where('course_id', $course->course_id)
            ->where('is_opened', 1)
            ->whereIn('user_id', $filteredUserIds)
            ->count();

        $joinedCount = CourseEnrollment::where('course_id', $course->course_id)
            ->where('status_join', 1)
            ->whereIn('user_id', $filteredUserIds)
            ->count();

        $learnerCount = $course->enrollments->count() ?? 0;

        // Tambahkan properti baru ke $course supaya langsung bisa dipakai di view
        $course->active_count = $activeCount;
        $course->joined_count = $joinedCount;
        $course->filtered_learner_count = $learnerCount;

        // ===================================================
        // 💬 Ambil semua thread forum terkait course
        // ===================================================
        $forumThreads = ForumThread::where('course_id', $course->course_id)
            ->orderBy('created_at', 'desc')
            ->get();

        // ===================================================
        // 📤 Kirim ke view
        // ===================================================
        return view('instructor.detail-course', compact(
            'course',
            'trainer',
            'learnerCount',
            'forumThreads'
        ));
    }
    public function modifyCourse(Request $request, $id)
    {
        // ==============================
        // 1️⃣ Ambil Course + User Enroll
        // ==============================
        $course = Course::with([
            'enrollments.user' => function ($q) {
                $q->select('user_id', 'full_name', 'emp_id', 'departement_cat', 'role_id', 'sub_role');
            }
        ])
        ->where('is_deleted', 0)
        ->find($id);

        if (!$course) {
            abort(404, 'Course tidak ditemukan');
        }

        // ==============================
        // 2️⃣ Ambil daftar division aktif
        // ==============================
        $divisions = Division::pluck('division_name', 'division_id')->toArray();

        // ==============================
        // 3️⃣ Ambil aktivitas learner (last_access & is_opened)
        // ==============================
        $activities = \App\Models\CourseLearnerActivity::where('course_id', $course->course_id)
            ->get()
            ->keyBy('user_id');

        // ==============================
        // 4️⃣ Filter enrollments (hapus role_id = 1 & sub_role berisi 1)
        // ==============================
        $course->enrollments = $course->enrollments->filter(function ($enroll) {
            $user = $enroll->user;
            if (!$user) return false;

            // Role utama bukan 1
            if ((int)$user->role_id === 1) return false;

            // Sub_role tidak mengandung 1 (baik JSON maupun CSV string)
            $sub = $user->sub_role ?? [];
            if (is_string($sub)) {
                $decoded = json_decode($sub, true);
                $sub = $decoded ?: explode(',', $sub);
            }

            $sub = array_map('intval', (array)$sub);
            return !in_array(1, $sub);
        })
        ->values()
        ->transform(function ($enroll) use ($divisions, $activities) {
            $user = $enroll->user;
            if ($user) {
                $user->division_label = $divisions[$user->departement_cat] ?? 'Tidak Diketahui';
            }

            // Tambahkan data aktivitas learner
            $activity = $activities[$enroll->user_id] ?? null;
            $enroll->last_access = $activity->last_access ?? null;
            $enroll->is_opened   = $activity->is_opened ?? 0;

            return $enroll;
        });

        // ==============================
        // 5️⃣ Ambil trainer dan module
        // ==============================
        $trainer = User::where('user_id', $course->course_trainer_id)->first();
        $modules = CourseWeekModule::where('course_id', $id)
                    ->orderBy('week_order', 'asc')
                    ->get();

        // ==============================
        // 6️⃣ Hitung learner aktif & tergabung (mengikuti filter)
        // ==============================
        $filteredUserIds = $course->enrollments->pluck('user.user_id')->filter()->unique();

        $activeCount = \App\Models\CourseLearnerActivity::where('course_id', $course->course_id)
            ->where('is_opened', 1)
            ->whereIn('user_id', $filteredUserIds)
            ->count();

        $joinedCount = \App\Models\CourseEnrollment::where('course_id', $course->course_id)
            ->where('status_join', 1)
            ->whereIn('user_id', $filteredUserIds)
            ->count();

        $learnerCount = $course->enrollments->count() ?? 0;

        // Simpan hasil ke properti course
        $course->active_count = $activeCount;
        $course->joined_count = $joinedCount;
        $course->filtered_learner_count = $learnerCount;

        // ==============================
        // 7️⃣ Return ke view modify-course
        // ==============================
        return view('instructor.modify-course', compact(
            'course',
            'trainer',
            'modules',
            'learnerCount'
        ));
    }
    public function courseModule(Request $request, $id)
    {
        $modules = $request->input('modules', []);

        // ✅ Get current count of modules for this course
        $countModule = CourseWeekModule::where('course_id', $id)->count();

        foreach ($modules as $index => $module) {
            $countModule++; // increment for each new module

            CourseWeekModule::create([
                'course_id'              => $id, // ✅ from route param
                'course_week_title'      => $module['title'],
                'course_week_visibility' => true,
                'week_order'             => $countModule, // ✅ sequential order
                'course_start'           => now(),
                'course_end'             => now()->addWeek(),
                'is_checked'             => false,
                'last_process'           => now(),
                'person_process'         => auth()->id(),
            ]);
        }

        return response()->json(['success' => true]);
    }
    public function itemCourseModule(Request $request, $id)
    {
        try {
            // ================================
            // 📋 VALIDASI
            // ================================
            $validated = $request->validate([
                'course_id'                => 'required|integer',
                'course_item_name'         => 'nullable|string|max:255',
                'course_describe'          => 'nullable|string',
                'course_item_type'         => 'nullable|string|max:100',
                'course_due_start'         => 'nullable|date_format:Y-m-d\TH:i',
                'course_due_end'           => 'nullable|date_format:Y-m-d\TH:i|after_or_equal:course_due_start',
                'course_duration'          => 'nullable|integer',
                'course_passing'           => 'nullable|integer|min:0',
                'course_one_timesubmitted' => 'boolean',
                'course_media_type'        => 'nullable|in:url,upload',
                'course_media_file'        => 'nullable|file|mimes:pdf|max:102400',
                'course_media_video'       => 'nullable|file|mimes:mp4,mkv,avi,webm,mov|max:102400',
                'course_media_audio'       => 'nullable|file|mimetypes:audio/mpeg,audio/mp3,audio/wav|max:20480',
                'course_assignment_file'   => 'nullable|file|mimes:pdf|max:102400',
                'course_media_url'         => 'nullable|url',
                'course_existing_media'    => 'nullable|string',
                'course_pre_requirment'    => 'nullable|string',
                'course_assignment'        => 'nullable|string',
                'course_attachment'        => 'nullable|array',
            ]);

            // ================================
            // 🗂️ PERSIAPAN FOLDER
            // ================================
            $folders = [
                'assets/img/course/course-media',
                'assets/video',
                'assets/audio',
                'assets/assignments',
            ];

            foreach ($folders as $folder) {
                if (!file_exists(public_path($folder))) {
                    mkdir(public_path($folder), 0777, true);
                }
            }

            // ================================
            // 🧩 LOG DEBUG (penting untuk tracking)
            // ================================
            \Log::info('📦 [itemCourseModule] Upload request diterima', [
                'user_id'   => auth()->id(),
                'course_id' => $validated['course_id'],
                'media_type'=> $request->input('course_media_type'),
                'files'     => array_map(fn($f) => [
                    'name' => $f->getClientOriginalName(),
                    'size_MB' => round($f->getSize() / 1024 / 1024, 2),
                ], $request->allFiles()),
            ]);

            // ================================
            // 🔹 HANDLE MEDIA
            // ================================
            $path = $validated['course_existing_media'] ?? null;
            $mediaType = $validated['course_media_type'] ?? 'upload';

            if ($mediaType === 'upload') {

                // === PDF Materi ===
                if ($request->hasFile('course_media_file')) {
                    $file = $request->file('course_media_file');
                    $fileName = 'course_pdf_' . time() . '.' . $file->getClientOriginalExtension();
                    $file->move(public_path('assets/img/course/course-media'), $fileName);
                    $path = 'assets/img/course/course-media/' . $fileName;
                }

                // === Video ===
                elseif ($request->hasFile('course_media_video')) {
                    $file = $request->file('course_media_video');
                    $fileName = 'course_video_' . time() . '.' . $file->getClientOriginalExtension();
                    $file->move(public_path('assets/video'), $fileName);
                    $path = 'assets/video/' . $fileName;
                }

                // === Audio ===
                elseif ($request->hasFile('course_media_audio')) {
                    $file = $request->file('course_media_audio');
                    $fileName = 'course_audio_' . time() . '.' . $file->getClientOriginalExtension();
                    $file->move(public_path('assets/audio'), $fileName);
                    $path = 'assets/audio/' . $fileName;
                }

                // === Assignment ===
                elseif ($request->hasFile('course_assignment_file')) {
                    $file = $request->file('course_assignment_file');
                    $fileName = 'course_assignment_' . time() . '.' . $file->getClientOriginalExtension();
                    $file->move(public_path('assets/assignments'), $fileName);
                    $path = 'assets/assignments/' . $fileName;
                }

                // === Tidak upload baru tapi ada existing ===
                elseif (!empty($validated['course_existing_media'])) {
                    $path = $validated['course_existing_media'];
                }
            }

            // === URL ===
            elseif ($mediaType === 'url') {
                if (!empty($validated['course_media_url'])) {
                    $path = $validated['course_media_url'];
                } elseif (!empty($validated['course_existing_media'])) {
                    $path = $validated['course_existing_media'];
                }
            }

            // ================================
            // 💾 SIMPAN KE DATABASE
            // ================================
            $courseItem = CourseWeekItem::create([
                'course_id'                => (int) $validated['course_id'],
                'course_week_id'           => (int) $id,
                'course_item_name'         => $validated['course_item_name'],
                'course_describe'          => $validated['course_describe'] ?? null,
                'course_item_type'         => $validated['course_item_type'] ?? null,
                'course_due_start'         => $validated['course_due_start'] ?? null,
                'course_due_end'           => $validated['course_due_end'] ?? null,
                'course_duration'          => $validated['course_duration'] ?? null,
                'passing_grade'            => $validated['course_passing'] ?? null,
                'course_one_timesubmitted' => $validated['course_one_timesubmitted'] ?? false,
                'course_media'             => $path,
                'course_pre_requirment'    => $validated['course_pre_requirment'] ?? null,
                'course_assignment'        => $validated['course_assignment'] ?? null,
                'is_checked'               => false,
                'last_process'             => now(),
                'person_process'           => auth()->id(),
            ]);

            // Simpan attachment JSON bila ada
            if (!empty($validated['course_attachment'])) {
                $courseItem->course_attachment = json_encode($validated['course_attachment']);
                $courseItem->save();
            }

            // ================================
            // ✅ RESPON SUKSES
            // ================================
            \Log::info('✅ [itemCourseModule] Course item berhasil dibuat.', [
                'item_id' => $courseItem->id,
                'path'    => $path,
            ]);

            return response()->json([
                'success' => true,
                'message' => '✅ Course item berhasil dibuat.',
                'data'    => $courseItem,
            ]);
        } catch (\Throwable $th) {
            \Log::error('💥 [itemCourseModule] Gagal menyimpan course item', [
                'error' => $th->getMessage(),
                'trace' => $th->getTraceAsString(),
            ]);

            return response()->json([
                'success' => false,
                'message' => '❌ Gagal menyimpan course item!',
                'error'   => $th->getMessage(),
            ], 500);
        }
    }
    public function itemEditCourseModule(Request $request, $id)
    {
        $courseItem = CourseWeekItem::findOrFail($id);

        // 🔍 Log awal untuk melihat data mentah dari request
        // Log::info('📩 [ITEM EDIT REQUEST RECEIVED]', [
        //     'item_id' => $id,
        //     'user_id' => auth()->id(),
        //     'raw_input' => $request->all(),
        //     'timestamp' => now()->toDateTimeString(),
        // ]);

        $validated = $request->validate([
            'course_id'                => 'required|integer',
            'course_week_id'           => 'required|integer',
            'course_item_name'         => 'required|string|max:255',
            'course_describe'          => 'nullable|string',
            'course_item_type'         => 'nullable|string|max:100',
            'course_due_start'         => 'nullable|date_format:Y-m-d\TH:i',
            'course_due_end'           => 'nullable|date_format:Y-m-d\TH:i|after_or_equal:course_due_start',
            'course_duration'          => 'nullable|integer',
            'course_passing'           => 'nullable|integer|min:0',
            'course_multiply_chance'   => 'nullable|integer|min:1',
            'course_one_timesubmitted' => 'nullable|boolean',
            'course_media_url'         => 'nullable|url',
            'course_media_file'        => 'nullable|file|mimes:pdf|max:102400',
            'course_media_audio'       => 'nullable|file|mimes:mp3,wav,ogg,m4a|max:20480',
            'course_media_video'       => 'nullable|file|mimes:mp4,mov,avi,mkv|max:102400',
            'course_assignment_file'   => 'nullable|file|mimes:pdf|max:102400',
            'course_pre_requirment'    => 'nullable|string',
            'course_assignment'        => 'nullable|string',
            'is_checked'               => 'boolean',
        ]);

        // ======================================
        // 🕓 Handle Tanggal Kosong → NULL
        // ======================================
        $dueStart = $request->input('course_due_start');
        $dueEnd   = $request->input('course_due_end');

        $course_due_start = ($dueStart === null || $dueStart === '')
            ? null
            : \Carbon\Carbon::parse($dueStart)->format('Y-m-d H:i:s');

        $course_due_end = ($dueEnd === null || $dueEnd === '')
            ? null
            : \Carbon\Carbon::parse($dueEnd)->format('Y-m-d H:i:s');

        // Log::info('🕓 [ITEM DATE CHECK]', [
        //     'item_id'       => $courseItem->item_id,
        //     'old_due_start' => $courseItem->course_due_start,
        //     'new_due_start' => $course_due_start,
        //     'old_due_end'   => $courseItem->course_due_end,
        //     'new_due_end'   => $course_due_end,
        //     'user_id'       => auth()->id(),
        // ]);

        // ======================================
        // Handle Media File / URL
        // ======================================
        $mediaValue = $courseItem->course_media;

        if ($request->filled('course_media_url')) {
            $mediaValue = $validated['course_media_url'];
        } elseif ($request->hasFile('course_media_file')) {
            $file = $request->file('course_media_file');
            $filename = time().'_'.$file->getClientOriginalName();
            $file->move(public_path('assets/img/course/course-media'), $filename);
            $mediaValue = 'assets/img/course/course-media/'.$filename;
        } elseif ($request->hasFile('course_media_audio')) {
            $file = $request->file('course_media_audio');
            $filename = time().'_'.$file->getClientOriginalName();
            $file->move(public_path('assets/audio'), $filename);
            $mediaValue = 'assets/audio/'.$filename;
        } elseif ($request->hasFile('course_media_video')) {
            $file = $request->file('course_media_video');
            $filename = time().'_'.$file->getClientOriginalName();
            $file->move(public_path('assets/video'), $filename);
            $mediaValue = 'assets/video/'.$filename;
        } elseif ($request->hasFile('course_assignment_file')) {
            $file = $request->file('course_assignment_file');
            $filename = time().'_'.$file->getClientOriginalName();
            $file->move(public_path('assets/assignments'), $filename);
            $mediaValue = 'assets/assignments/'.$filename;
        }

        // ======================================
        // 🔄 Update item (pakai hasil parsing tanggal)
        // ======================================
        $courseItem->update([
            'course_id'                => $validated['course_id'],
            'course_week_id'           => $validated['course_week_id'],
            'course_item_name'         => $validated['course_item_name'],
            'course_describe'          => $validated['course_describe'] ?? $courseItem->course_describe,
            'course_item_type'         => $validated['course_item_type'] ?? $courseItem->course_item_type,
            'course_due_start'         => $course_due_start,
            'course_due_end'           => $course_due_end,
            'course_duration'          => $validated['course_duration'] ?? $courseItem->course_duration,
            'passing_grade'            => $validated['course_passing'] ?? $courseItem->passing_grade,
            'course_multiply_chance'   => $validated['course_multiply_chance'] ?? $courseItem->course_multiply_chance,
            'course_one_timesubmitted' => $validated['course_one_timesubmitted'] ?? $courseItem->course_one_timesubmitted,
            'course_media'             => $mediaValue,
            'course_pre_requirment'    => $validated['course_pre_requirment'] ?? $courseItem->course_pre_requirment,
            'course_assignment'        => $validated['course_assignment'] ?? $courseItem->course_assignment,
            'is_checked'               => $validated['is_checked'] ?? $courseItem->is_checked,
            'last_process'             => now(),
            'person_process'           => auth()->id(),
        ]);

        // ======================================
        // ✅ Log hasil setelah update
        // ======================================
        // Log::info('✅ [ITEM UPDATED RESULT]', [
        //     'item_id'         => $courseItem->item_id,
        //     'course_id'       => $courseItem->course_id,
        //     'week_id'         => $courseItem->course_week_id,
        //     'user_id'         => auth()->id(),
        //     'saved_due_start' => $courseItem->fresh()->course_due_start,
        //     'saved_due_end'   => $courseItem->fresh()->course_due_end,
        //     'media'           => $mediaValue,
        //     'timestamp'       => now()->toDateTimeString(),
        // ]);

        return response()->json([
            'success' => true,
            'message' => '✅ Course item berhasil diperbarui (termasuk tanggal dan file)',
            'data'    => $courseItem->fresh()
        ]);
    }
    
    public function updateWeekOrder(Request $request)
    {
        try {
            foreach ($request->order as $row) {

                // Buat title baru
                $newTitle = "Bagian " . $row['new_order'];

                CourseWeekModule::where('course_week_id', $row['course_week_id'])
                    ->update([
                        'week_order' => $row['new_order'],
                        'course_week_title' => $newTitle
                    ]);
            }

            return response()->json([
                'success' => true,
                'message' => 'Order & titles updated'
            ]);

        } catch (\Throwable $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 500);
        }
    }
    public function updateItemOrder(Request $request)
    {
        try {
            $orderData = $request->order;

            if (empty($orderData)) {
                return response()->json(['success' => true]); // silent
            }

            // parent course_week_id taken from first item
            $parentWeekId = $orderData[0]['course_week_id'];

            foreach ($orderData as $row) {

                $item = CourseWeekItem::find($row['item_id']);
                if (!$item) continue;

                // STRICT VALIDATION (silent reject)
                if ($item->course_week_id != $parentWeekId) {
                    continue; // do NOT update, do NOT error
                }

                // UPDATE ONLY ORDER
                $item->update([
                    'item_order' => $row['new_order']
                ]);
            }

            return response()->json([
                'success' => true,
                'message' => 'Item order updated'
            ]);

        } catch (\Throwable $e) {
            // still return success to avoid UI interruptions
            return response()->json(['success' => true]);
        }
    }
    
    public function EssayItemModule(Request $request, $id)
    {
        Log::info('🟨 [EssayItemModule] Request diterima', [
            'course_id' => $id,
            'user_id'   => Auth::id(),
            'input'     => $request->except(['essay_attachment_pdf', 'essay_attachment_video'])
        ]);

        $validated = $request->validate([
            'essay_id'                     => 'nullable|integer|exists:course_item_essays,essay_id',
            'item_id'                      => 'required|integer|exists:course_week_item,item_id',
            'essay_title'                  => 'required|string|max:255',
            'instruction'                  => 'required|string',
            'attachment_type'              => 'nullable|in:pdf,url,video',
            'essay_attachment_pdf'         => 'nullable|file|mimes:pdf|max:102400',
            'essay_attachment_video'       => 'nullable|file|mimes:mp4,mkv,avi|max:102400',
            'essay_attachment_url'         => 'nullable|url',
            // Tambahan field fallback dari frontend
            'essay_attachment_pdf_existing'   => 'nullable|string',
            'essay_attachment_video_existing' => 'nullable|string',
            'essay_attachment_url_existing'   => 'nullable|string',
        ]);

        $uploadPath = public_path('assets/img/course/course-media');
        if (!file_exists($uploadPath)) {
            mkdir($uploadPath, 0777, true);
            Log::info("📁 Folder upload dibuat: $uploadPath");
        }

        $path = null;

        try {
            // ==========================================================
            // 🔹 Tentukan nilai path berdasarkan tipe lampiran
            // ==========================================================
            switch ($validated['attachment_type'] ?? null) {
                case 'pdf':
                    if ($request->hasFile('essay_attachment_pdf')) {
                        // Upload baru
                        $file = $request->file('essay_attachment_pdf');
                        $fileName = 'essay_pdf_' . time() . '.' . $file->getClientOriginalExtension();
                        $file->move($uploadPath, $fileName);
                        $path = 'assets/img/course/course-media/' . $fileName;
                        Log::info('📄 File PDF diupload', ['path' => $path]);
                    } elseif (!empty($validated['essay_attachment_pdf_existing'])) {
                        // Tidak upload baru → gunakan file lama
                        $path = $validated['essay_attachment_pdf_existing'];
                        Log::info('📄 Gunakan file PDF lama', ['path' => $path]);
                    }
                    break;

                case 'video':
                    if ($request->hasFile('essay_attachment_video')) {
                        // Upload baru
                        $file = $request->file('essay_attachment_video');
                        $fileName = 'essay_video_' . time() . '.' . $file->getClientOriginalExtension();
                        $file->move($uploadPath, $fileName);
                        $path = 'assets/img/course/course-media/' . $fileName;
                        Log::info('🎥 File Video diupload', ['path' => $path]);
                    } elseif (!empty($validated['essay_attachment_video_existing'])) {
                        // Tidak upload baru → gunakan file lama
                        $path = $validated['essay_attachment_video_existing'];
                        Log::info('🎥 Gunakan file Video lama', ['path' => $path]);
                    }
                    break;

                case 'url':
                    if (!empty($validated['essay_attachment_url'])) {
                        $path = $validated['essay_attachment_url'];
                        Log::info('🔗 URL baru diset', ['url' => $path]);
                    } elseif (!empty($validated['essay_attachment_url_existing'])) {
                        $path = $validated['essay_attachment_url_existing'];
                        Log::info('🔗 Gunakan URL lama', ['url' => $path]);
                    }
                    break;

                default:
                    Log::info('ℹ️ Tidak ada tipe attachment yang dipilih.');
                    break;
            }

            // ==========================================================
            // 🔹 Simpan ke Database (update / create)
            // ==========================================================
            if (!empty($validated['essay_id'])) {
                $essay = CourseEssay::find($validated['essay_id']);

                if (!$essay) {
                    Log::warning('⚠️ Essay tidak ditemukan untuk update', ['essay_id' => $validated['essay_id']]);
                    return response()->json([
                        'success' => false,
                        'message' => 'Essay dengan ID tersebut tidak ditemukan.',
                    ], 404);
                }

                // Update hanya field yang diubah
                $essay->update([
                    'item_id'            => $validated['item_id'],
                    'essay_title'        => $validated['essay_title'],
                    'instruction'        => $validated['instruction'],
                    'attachment_type'    => $validated['attachment_type'] ?? null,
                    'attachment_value'   => $path ?? $essay->attachment_value, // ← fallback: tetap nilai lama
                    'is_essay_submitted' => 0,
                ]);

                Log::info('🟢 Essay berhasil diupdate', ['essay_id' => $essay->essay_id]);
                $message = 'Essay berhasil diperbarui.';
            } else {
                $essay = CourseEssay::create([
                    'item_id'            => $validated['item_id'],
                    'essay_title'        => $validated['essay_title'],
                    'instruction'        => $validated['instruction'],
                    'attachment_type'    => $validated['attachment_type'] ?? null,
                    'attachment_value'   => $path,
                    'is_essay_submitted' => 0,
                ]);

                Log::info('🟩 Essay baru berhasil dibuat', ['essay_id' => $essay->essay_id]);
                $message = 'Essay baru berhasil dibuat.';
            }

            return response()->json([
                'success' => true,
                'message' => $message,
                'data'    => $essay
            ]);
        } catch (\Throwable $th) {
            Log::error('❌ Error pada EssayItemModule', [
                'message' => $th->getMessage(),
                'trace'   => $th->getTraceAsString(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Terjadi kesalahan internal pada server.',
            ], 500);
        }
    }
    public function MultipleChoice(Request $request, $id)
    {
        Log::info("📥 Payload diterima:", $request->all());

        $savedQuestions = [];

        foreach ($request->questions as $qIndex => $qData) {
            // 🔎 Update atau create Question
            if (!empty($qData['question_id'])) {
                $question = CourseTypeQuestion::find($qData['question_id']);
                if ($question) {
                    $question->update([
                        'question_text' => $qData['question_text'] ?? '(empty)',
                    ]);
                    Log::info("✏️ Update Question ID {$question->question_id}: {$qData['question_text']}");
                } else {
                    $question = CourseTypeQuestion::create([
                        'item_id'       => $id,
                        'question_text' => $qData['question_text'] ?? '(empty)',
                        'is_draft'      => 0,
                    ]);
                    Log::info("➕ Create Question baru: {$qData['question_text']} (ID {$question->question_id})");
                }
            } else {
                $question = CourseTypeQuestion::create([
                    'item_id'       => $id,
                    'question_text' => $qData['question_text'] ?? '(empty)',
                    'is_draft'      => 0,
                ]);
                Log::info("➕ Create Question baru: {$qData['question_text']} (ID {$question->question_id})");
            }

            // 🔎 Update atau create Options
            foreach ($qData['options'] as $oIndex => $opt) {
                if (!empty($opt['option_id'])) {
                    $option = CourseTypeOption::find($opt['option_id']);
                    if ($option) {
                        $option->update([
                            'option_text' => $opt['option_text'] ?? '(empty)',
                            'is_correct'  => !empty($opt['is_correct']) ? 1 : 0,
                        ]);
                        Log::info("✏️ Update Option ID {$option->option_id} (Q{$question->question_id}): {$opt['option_text']} [is_correct={$opt['is_correct']}]");
                    } else {
                        $option = CourseTypeOption::create([
                            'question_id' => $question->question_id,
                            'option_text' => $opt['option_text'] ?? '(empty)',
                            'is_correct'  => !empty($opt['is_correct']) ? 1 : 0,
                        ]);
                        Log::info("➕ Create Option baru (Q{$question->question_id}) {$oIndex}: {$option->option_text}");
                    }
                } else {
                    $option = CourseTypeOption::create([
                        'question_id' => $question->question_id,
                        'option_text' => $opt['option_text'] ?? '(empty)',
                        'is_correct'  => !empty($opt['is_correct']) ? 1 : 0,
                    ]);
                    Log::info("➕ Create Option baru (Q{$question->question_id}) {$oIndex}: {$option->option_text}");
                }
            }

            $savedQuestions[] = $question->load('options');
        }

        return response()->json([
            'success' => true,
            'message' => 'Multiple Choice berhasil disimpan',
            'data'    => $savedQuestions
        ]);
    }
    public function DeleteMultiplyChoice(Request $request, $id) 
    {
       try {
            $question = CourseTypeQuestion::find($id);

            if (!$question) {
                return response()->json([
                    'success' => false,
                    'message' => 'Soal tidak ditemukan'
                ], 404);
            }

            if (method_exists($question, 'options')) {
                $question->options()->delete();
            }

            $question->delete();

            return response()->json([
                'success' => true,
                'message' => 'Soal berhasil dihapus'
            ]);

            } catch (\Exception $e) {
            \Log::error("❌ Gagal hapus soal MC: " . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Terjadi error saat menghapus soal'
            ], 500);
        }
    }
    public function forumDiscussion(Request $request, $id)
    {
        $request->validate([
            'forum_title'    => 'required|string|max:255',
            'forum_question' => 'required|string',
            'attachment_type'=> 'nullable|in:pdf,url,video',
            // validasi khusus tiap tipe
            'forum_attachment_video' => 'nullable|file|mimes:mp4,mov,avi,mkv|max:102400', // 100 MB
            'forum_attachment_pdf'   => 'nullable|file|mimes:pdf|max:20480',              // 20 MB
            'forum_attachment_url'   => 'nullable|url',
        ]);

        $attachmentValue = null;

        if ($request->attachment_type === 'video' && $request->hasFile('forum_attachment_video')) {
            $file = $request->file('forum_attachment_video');
            $filename = time().'_'.$file->getClientOriginalName();
            $file->move(public_path('assets/video'), $filename);
            $attachmentValue = 'assets/video/' . $filename;

        } elseif ($request->attachment_type === 'pdf' && $request->hasFile('forum_attachment_pdf')) {
            $file = $request->file('forum_attachment_pdf');
            $filename = time().'_'.$file->getClientOriginalName();
            $file->move(public_path('assets/pdf'), $filename);
            $attachmentValue = 'assets/pdf/' . $filename;

        } elseif ($request->attachment_type === 'url') {
            $attachmentValue = $request->input('forum_attachment_url');
        }

        $forum = CourseForumDiscussion::create([
            'item_id'          => $id,
            'forum_title'      => $request->forum_title,
            'forum_question'   => $request->forum_question,
            'attachment_type'  => $request->attachment_type,
            'attachment_value' => $attachmentValue,
            'is_draft'         => 0,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Forum berhasil dibuat',
            'data'    => $forum
        ]);
    }
    public function deleteModule($id)
    {
        $module = CourseWeekModule::findOrFail($id);
        $courseId = $module->course_id;

        // 🔥 Hapus semua item yang punya course_week_id = modul ini
        CourseWeekItem::where('course_week_id', $module->course_week_id)->delete();

        // Baru hapus modul
        $module->delete();

        // Ambil ulang modul sisa
        $modules = CourseWeekModule::where('course_id', $courseId)
                    ->orderBy('created_at', 'asc')
                    ->get();

        // Reorder ulang week_order + judul
        $order = 1;
        foreach ($modules as $m) {
            $m->update([
                'course_week_title' => "Materi " . $order,
                'week_order'        => $order
            ]);
            $order++;
        }

        return response()->json([
            'success' => true,
            'message' => 'Module & items deleted and reordered successfully'
        ]);
    }
    public function deleteItem($id)
    {
        try {
            $item = CourseWeekItem::findOrFail($id);
            $moduleId = $item->course_week_id;

            // 🔥 Pastikan relasi essay & submissions terhapus
            if ($item->essay) {
                $essay = $item->essay;

                // Kalau masih ada submission — hapus semua tanpa mikir
                try {
                    \DB::table('course_essay_submissions')
                        ->where('essay_id', $essay->essay_id)
                        ->delete();
                } catch (\Exception $e) {
                    \Log::warning("Gagal hapus submission essay_id={$essay->essay_id}: ".$e->getMessage());
                }

                // Hapus essay-nya
                $essay->delete();
            }

            // 🔥 Hapus forum (langsung pakai DB untuk jaga-jaga)
            try {
                \DB::table('course_forum_discussions')
                    ->where('item_id', $id)
                    ->delete();
            } catch (\Exception $e) {
                \Log::warning("Gagal hapus forum item_id={$id}: ".$e->getMessage());
            }

            // 🔥 Hapus pertanyaan + opsi MC
            try {
                $questionIds = \DB::table('course_type_questions')
                    ->where('item_id', $id)
                    ->pluck('question_id');

                \DB::table('course_type_options')
                    ->whereIn('question_id', $questionIds)
                    ->delete();

                \DB::table('course_type_questions')
                    ->where('item_id', $id)
                    ->delete();
            } catch (\Exception $e) {
                \Log::warning("Gagal hapus quiz item_id={$id}: ".$e->getMessage());
            }

            // 🔹 Terakhir hapus item
            $item->delete();

            // 🔁 Reorder item_order agar tetap urut
            $items = CourseWeekItem::where('course_week_id', $moduleId)
                ->orderBy('created_at', 'asc')
                ->get();

            foreach ($items as $index => $i) {
                $i->update(['item_order' => $index + 1]);
            }

            return response()->json([
                'success' => true,
                'message' => '✅ Item dan semua data terkait berhasil dihapus total.'
            ]);

        } catch (\Exception $e) {
            \Log::error("❌ Error deleteItem ID={$id}: ".$e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Terjadi kesalahan saat menghapus item.',
                'error'   => $e->getMessage(),
            ], 500);
        }
    }
    public function getEssay($itemId)
    {
        $essay = CourseEssay::where('item_id', $itemId)->first();
        return response()->json($essay);
    }
    public function getForum($itemId)
    {
        $forum = CourseForumDiscussion::where('item_id', $itemId)->first();
        return response()->json($forum);
    }
    public function getQuiz($itemId)
    {
        $questions = CourseTypeQuestion::where('item_id', $itemId)
            ->with('options')
            ->get();

        return response()->json($questions);
    }
    public function getThreads($course_id)
    {
        try {
            $threads = ForumThread::where('course_id', $course_id)
                ->orderBy('thread_seq', 'asc')
                ->get();

            return response()->json([
                'success' => true,
                'data' => $threads
            ]);
        } catch (\Exception $e) {
            \Log::error('Gagal ambil thread: '.$e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Gagal mengambil data thread.',
                'error' => $e->getMessage()
            ], 500);
        }
    }
      public function discussionThread(Request $request)
    {
        // 1️⃣ VALIDASI INPUT
        $request->validate([
            'course_id' => 'required|exists:course,course_id',
            'threads'   => 'required|array|min:1',
            'threads.*.topic_title'    => 'required|string|max:255',
            'threads.*.forum_title'    => 'required|string|max:255',
            'threads.*.forum_question' => 'required|string',
            'threads.*.attachment_type' => 'nullable|in:pdf,url,video',
            'threads.*.Thread_forum_attachment_pdf'   => 'nullable|file|mimes:pdf|max:51200',
            'threads.*.Thread_forum_attachment_video' => 'nullable|file|mimes:mp4,mkv,avi|max:102400',
            'threads.*.attachment_path' => 'nullable|string|max:500',
            'threads.*.existing_attachment' => 'nullable|string|max:500',
            'threads.*.thread_seq'      => 'nullable|integer|min:1',
            'threads.*.thread_id'       => 'nullable|integer',
        ]);

        try {
            // 2️⃣ BUAT FOLDER PENYIMPANAN JIKA BELUM ADA
            $basePath = public_path('assets/thread');
            if (!file_exists($basePath)) mkdir($basePath, 0777, true);
            if (!file_exists("$basePath/pdf")) mkdir("$basePath/pdf", 0777, true);
            if (!file_exists("$basePath/video")) mkdir("$basePath/video", 0777, true);

            $savedThreads = [];

            // 🟦 Ambil semua thread existing di DB
            $existingIds = ForumThread::where('course_id', $request->course_id)
                ->pluck('thread_id')
                ->toArray();

            // 🟦 Ambil semua thread_id dari request (yang tidak null)
            $incomingIds = collect($request->threads)
                ->pluck('thread_id')
                ->filter()
                ->toArray();

            // 🟥 Cari thread yang tidak ada di request → hapus dari DB
            $deleteIds = array_diff($existingIds, $incomingIds);
            if (!empty($deleteIds)) {
                ForumThread::whereIn('thread_id', $deleteIds)->delete();
            }

            // 3️⃣ LOOP SEMUA THREAD DARI REQUEST
            foreach ($request->threads as $i => $threadData) {

                $attachmentType = $threadData['attachment_type'] ?? null;
                $attachmentPath = null;

                // (a) PDF Upload
                if ($attachmentType === 'pdf' && $request->hasFile("threads.$i.Thread_forum_attachment_pdf")) {
                    $file = $request->file("threads.$i.Thread_forum_attachment_pdf");
                    $filename = 'pdf_' . time() . '_' . Str::random(8) . '.' . $file->getClientOriginalExtension();
                    $file->move($basePath . '/pdf', $filename);
                    $attachmentPath = 'assets/thread/pdf/' . $filename;
                }

                // (b) Video Upload
                elseif ($attachmentType === 'video' && $request->hasFile("threads.$i.Thread_forum_attachment_video")) {
                    $file = $request->file("threads.$i.Thread_forum_attachment_video");
                    $filename = 'video_' . time() . '_' . Str::random(8) . '.' . $file->getClientOriginalExtension();
                    $file->move($basePath . '/video', $filename);
                    $attachmentPath = 'assets/thread/video/' . $filename;
                }

                // (c) URL Input
                elseif ($attachmentType === 'url' && !empty($threadData['attachment_path'])) {
                    $attachmentPath = $threadData['attachment_path'];
                }

                // (d) Existing media
                elseif (!empty($threadData['existing_attachment'])) {
                    $attachmentPath = $threadData['existing_attachment'];
                }

                // (e) Jika kosong
                else {
                    $attachmentPath = null;
                }

                // 🆕 Ambil urutan thread
                $threadSeq = $threadData['thread_seq'] ?? ($i + 1);

                // 🟦 Cari berdasarkan thread_id (PERBAIKAN UTAMA)
                $existing = null;
                if (!empty($threadData['thread_id'])) {
                    $existing = ForumThread::find($threadData['thread_id']);
                }

                // 4️⃣ UPDATE / INSERT
                if ($existing) {

                    $existing->update([
                        'topic_title'     => $threadData['topic_title'],
                        'forum_title'     => $threadData['forum_title'],
                        'forum_question'  => $threadData['forum_question'],
                        'thread_seq'      => $threadSeq,
                        'attachment_type' => $attachmentType,
                        'attachment_path' => $attachmentPath ?? $existing->attachment_path,
                        'updated_at'      => now(),
                    ]);

                    $savedThreads[] = $existing;

                } else {

                    $newThread = ForumThread::create([
                        'course_id'       => $request->course_id,
                        'created_by'      => Auth::id(),
                        'thread_seq'      => $threadSeq,
                        'topic_title'     => $threadData['topic_title'],
                        'forum_title'     => $threadData['forum_title'],
                        'forum_question'  => $threadData['forum_question'],
                        'attachment_type' => $attachmentType,
                        'attachment_path' => $attachmentPath,
                    ]);

                    $savedThreads[] = $newThread;
                }
            }

            // 5️⃣ KEMBALIKAN RESPON
            return response()->json([
                'success' => true,
                'message' => 'Thread berhasil disimpan / diperbarui!',
                'data'    => $savedThreads,
            ], 201);

        } catch (\Throwable $e) {

            \Log::error('❌ Error simpan thread: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Terjadi kesalahan saat menyimpan thread.',
                'error'   => $e->getMessage(),
            ], 500);
        }
    }
    public function deleteThread($id)
    {
        Log::info('🧹 [DeleteThread] Permintaan penghapusan thread diterima.', ['thread_id' => $id]);

        try {
            // Cek apakah thread ditemukan
            $thread = ForumThread::findOrFail($id);
            Log::info('✅ [DeleteThread] Thread ditemukan.', [
                'id' => $thread->id,
                'attachment_path' => $thread->attachment_path,
            ]);

            // ===============================
            // HAPUS FILE ATTACHMENT (JIKA ADA)
            // ===============================
            if ($thread->attachment_path) {
                $path = public_path($thread->attachment_path);

                if (file_exists($path)) {
                    if (@unlink($path)) {
                        Log::info('🗑 [DeleteThread] File attachment berhasil dihapus.', ['path' => $path]);
                    } else {
                        Log::warning('⚠️ [DeleteThread] Gagal menghapus file attachment.', ['path' => $path]);
                    }
                } else {
                    Log::info('ℹ️ [DeleteThread] File attachment tidak ditemukan di server.', ['path' => $path]);
                }
            } else {
                Log::info('ℹ️ [DeleteThread] Thread tidak memiliki attachment_path.');
            }

            // ===============================
            // HAPUS RECORD DARI DATABASE
            // ===============================
            $thread->delete();
            Log::info('✅ [DeleteThread] Thread berhasil dihapus dari database.', ['thread_id' => $id]);

            return response()->json([
                'success' => true,
                'message' => 'Thread berhasil dihapus!'
            ]);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            Log::error('❌ [DeleteThread] Thread tidak ditemukan.', ['thread_id' => $id]);
            return response()->json([
                'success' => false,
                'message' => 'Thread tidak ditemukan.',
            ], 404);
        } catch (\Exception $e) {
            Log::error('💥 [DeleteThread] Gagal menghapus thread.', [
                'thread_id' => $id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Gagal menghapus thread!',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

}