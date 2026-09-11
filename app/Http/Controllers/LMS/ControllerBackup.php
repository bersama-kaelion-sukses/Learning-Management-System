<?php

namespace App\Http\Controllers\LMS;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Http\Request;
use App\Models\Course;
use App\Models\User;
use App\Models\CourseEnrollment;
use App\Models\CourseWeekModule;
use App\Models\CourseWeekItem;
use App\Models\CourseType\CourseEssay;
use App\Models\CourseType\CourseTypeOption;
use App\Models\CourseType\CourseTypeQuestion;
use App\Models\CourseType\CourseForumDiscussion;

class CourseController extends Controller
{
    public function requested(Request $request)
    {
        $validated = $request->validate([
            'course_title'      => 'required|string|max:255',
            'course_category'   => 'required|string|max:100',
            'course_describe'   => 'required|string',
            'max_participant'   => 'required|integer|min:1',
            'course_image'      => 'nullable|image|max:2048',
            'course_trainer_id' => 'required|exists:users,user_id', // 🔥 ambil dari form
        ]);

        // Cari trainer berdasarkan user_id
        $trainer = User::findOrFail($validated['course_trainer_id']);

        $courseData = [
            'course_title'        => $validated['course_title'],
            'course_category'     => $validated['course_category'],
            'course_trainer_id'   => $trainer->user_id,                     // simpan user_id
            'course_trainer_name' => $trainer->full_name ?? $trainer->name, // simpan nama
            'course_describe'     => $validated['course_describe'],
            'max_participant'     => $validated['max_participant'],
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
        $userId   = Auth::id();
        $userName = Auth::user()->full_name ?? Auth::user()->name;

        $courses = Course::query()
            ->where(function ($q) use ($userId, $userName) {
                $q->where('course_trainer_id', $userId)   // cek by user_id
                ->orWhere('course_trainer_name', $userName); // cek by nama
            })
            ->where('is_deleted', 0)
            ->where('is_draft', 0)
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

        return view('instructor.course', compact('courses'));
    }
   public function store(Request $request)
    {
        $validated = $request->validate([
            'course_title'        => 'required|string|max:255',
            'course_category'     => 'required|string|max:100',
            'course_describe'     => 'required|string',
            'max_participant'     => 'required|integer|min:1',
            'course_image'        => 'nullable|image|max:2048', 
            'course_trainer_id'   => 'nullable|exists:users,user_id', 
        ]);

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
            // 1) Validasi langsung ambil course_id & user_ids
            $validated = $request->validate([
                'course_id'   => 'required|integer|exists:course,course_id',
                'user_ids'    => 'required|array',
                'user_ids.*'  => 'integer|exists:users,user_id',
            ]);

            $courseId = $validated['course_id'];
            $userIds  = $validated['user_ids'];

            // 2) Loop dan insert/update ke tabel course_enrollment
            foreach ($userIds as $userId) {
                CourseEnrollment::updateOrCreate(
                    [
                        'course_id' => $courseId,
                        'user_id'   => $userId,
                    ],
                    [
                        'status_join'    => 1, // enrolled
                        'is_approve'     => 1, // langsung approve
                        'enroll_date'    => now(),
                        'last_process'   => now(),
                        'person_process' => Auth::user()->full_name ?? 'system',
                    ]
                );
            }

            return response()->json([
                'success' => true,
                'message' => 'Learner berhasil di-assign ke course.'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error: '.$e->getMessage(),
            ], 500);
        }
    }
    public function detailCourse($id) {
        $courses = Course::all();
        $course = $courses->firstWhere('course_id', $id);
        
        if (!$course) {
            abort(404, 'Course tidak ditemukan');
        }
        return view('instructor.detail-course', compact('course', 'courses'));
    }

    public function modifyCourse(Request $request, $id)
    {
        $course = Course::findOrFail($id);

        // Ambil modul berdasarkan course_id
        $modules = CourseWeekModule::where('course_id', $id)->get();

        return view('instructor.modify-course', compact('course', 'modules'));
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
        $validated = $request->validate([
            'course_id'               => 'required|integer',
            'course_item_name'        => 'nullable|string|max:255',
            'course_describe'         => 'nullable|string',
            'course_item_type'        => 'nullable|string|max:100',
            'course_due_start'        => 'nullable|date_format:Y-m-d\TH:i',
            'course_due_end'          => 'nullable|date_format:Y-m-d\TH:i|after_or_equal:course_due_start',
            'course_duration'         => 'nullable|integer',
            'course_one_timesubmitted'=> 'boolean',
            'course_media'            => 'nullable|string',
            'course_pre_requirment'   => 'nullable|string',
            'course_assignment'       => 'nullable|string',
            'course_attachment'       => 'nullable|array',
        ]);

        $courseItem = CourseWeekItem::create([
            // ✅ pastikan integer
            'course_id'               => (int) $validated['course_id'],
            'course_week_id'          => (int) $id,
            'course_item_name'        => $validated['course_item_name'],
            'course_describe'         => $validated['course_describe'] ?? null,
            'course_item_type'        => $validated['course_item_type'] ?? null,
            'course_due_start'        => $validated['course_due_start'] ?? null,
            'course_due_end'          => $validated['course_due_end'] ?? null,
            'course_duration'         => $validated['course_duration'] ?? null,
            'course_one_timesubmitted'=> $validated['course_one_timesubmitted'] ?? false,
            'course_media'            => $validated['course_media'] ?? null,
            'course_pre_requirment'   => $validated['course_pre_requirment'] ?? null,
            'course_assignment'       => $validated['course_assignment'] ?? null,
            'is_checked'              => false,
            'last_process'            => now(),
            'person_process'          => auth()->id(),
        ]);

        // kalau ada attachment → simpan JSON
        if (!empty($validated['course_attachment'])) {
            $courseItem->course_attachment = json_encode($validated['course_attachment']);
            $courseItem->save();
        }

        return response()->json([
            'success' => true,
            'message' => 'Course item berhasil dibuat',
            'data'    => $courseItem,
        ]);
    }
    public function itemEditCourseModule(Request $request, $id)
    {
        $courseItem = CourseWeekItem::findOrFail($id);

        $validated = $request->validate([
            'course_id'               => 'required|integer',
            'course_week_id'          => 'required|integer',
            'course_item_name'        => 'required|string|max:255',
            'course_describe'         => 'nullable|string',
            'course_item_type'        => 'nullable|string|max:100',
            'course_due_start'        => 'nullable|date_format:Y-m-d\TH:i',
            'course_due_end'          => 'nullable|date_format:Y-m-d\TH:i|after_or_equal:course_due_start',
            'course_duration'         => 'nullable|integer',
            'course_one_timesubmitted'=> 'nullable|boolean',
            'course_media'            => 'nullable|string',
            'course_pre_requirment'   => 'nullable|string',
            'course_assignment'       => 'nullable|string',
            'is_checked'              => 'boolean',
        ]);

        $courseItem->update([
            'course_id'               => $validated['course_id'],
            'course_week_id'          => $validated['course_week_id'],
            'course_item_name'        => $validated['course_item_name'],
            'course_describe'         => $validated['course_describe'] ?? null,
            'course_item_type'        => $validated['course_item_type'] ?? null,
            'course_due_start'        => $validated['course_due_start'] ?? null,
            'course_due_end'          => $validated['course_due_end'] ?? null,
            'course_duration'         => $validated['course_duration'] ?? null,
            'course_one_timesubmitted'=> $validated['course_one_timesubmitted'] ?? false,
            'course_media'            => $validated['course_media'] ?? null,
            'course_pre_requirment'   => $validated['course_pre_requirment'] ?? null,
            'course_assignment'       => $validated['course_assignment'] ?? null,
            'is_checked'              => $validated['is_checked'] ?? false,
            'last_process'            => now(),
            'person_process'          => auth()->id(),
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Course item berhasil diperbarui',
            'data'    => $courseItem,
        ]);
    }

    // Tipe Materi Course 
    public function EssayItemModule (Request $request, $id) 
    {
         $validated = $request->validate([
                'item_id'                => 'required|integer|exists:course_week_item,item_id',
                'essay_title'            => 'required|string|max:255',
                'instruction'            => 'required|string',
                'attachment_type'        => 'nullable|in:pdf,url,video',
                'essay_attachment_pdf'   => 'nullable|file|mimes:pdf,doc,docx|max:20480',
                'essay_attachment_video' => 'nullable|file|mimes:mp4,mkv,avi|max:51200',
                'essay_attachment_url'   => 'nullable|url',
            ]);

            $path = null;

            // 📂 manual path (no storage facade)
            $uploadPath = public_path('assets/img/course/course-media');

            if (!file_exists($uploadPath)) {
                mkdir($uploadPath, 0777, true); // pastikan folder ada
            }

            // ✅ Check based on attachment type
            if ($validated['attachment_type'] === 'pdf' && $request->hasFile('essay_attachment_pdf')) {
                $file = $request->file('essay_attachment_pdf');
                $fileName = 'essay_pdf_' . time() . '.' . $file->getClientOriginalExtension();
                $file->move($uploadPath, $fileName);
                $path = 'assets/img/course/course-media/' . $fileName; // relative path
            }

            if ($validated['attachment_type'] === 'video' && $request->hasFile('essay_attachment_video')) {
                $file = $request->file('essay_attachment_video');
                $fileName = 'essay_video_' . time() . '.' . $file->getClientOriginalExtension();
                $file->move($uploadPath, $fileName);
                $path = 'assets/img/course/course-media/' . $fileName;
            }

            if ($validated['attachment_type'] === 'url' && !empty($validated['essay_attachment_url'])) {
                $path = $validated['essay_attachment_url']; // langsung simpan URL
            }

            // ✅ Save to DB
            $essay = CourseEssay::create([
                'item_id'            => $validated['item_id'],
                'essay_title'        => $validated['essay_title'],
                'instruction'        => $validated['instruction'],
                'attachment_type'    => $validated['attachment_type'] ?? null,
                'attachment_value'   => $path,
                'is_essay_submitted' => 0,
            ]);

             return redirect()->back()->with('success', 'Essay berhasil dibuat');
    }
    public function MultipleChoice(Request $request, $id)
    {
        // Validasi request
        $request->validate([
            'questions' => 'required|array|min:1',
            'questions.*.item_id' => 'required|integer',
            'questions.*.question_text' => 'required|string',
            'questions.*.options' => 'required|array|min:2',
            'questions.*.options.*.option_text' => 'required|string',
            'questions.*.options.*.is_correct' => 'required|boolean',
        ]);

        $savedQuestions = [];

        foreach ($request->questions as $qData) {
            // ✅ simpan pertanyaan ke DB
            $question = CourseTypeQuestion::create([
                'item_id'       => $id, // ambil dari route param
                'question_text' => $qData['question_text'],
            ]);

            // ✅ simpan semua opsi ke DB
            foreach ($qData['options'] as $opt) {
                CourseTypeOption::create([
                    'question_id' => $question->id,
                    'option_text' => $opt['option_text'],
                    'is_correct'  => $opt['is_correct'] ? 1 : 0, // pastikan boolean jadi 0/1
                ]);
            }

            $savedQuestions[] = $question->load('options');
        }

        // ✅ redirect kembali dengan pesan sukses
       return response()->json([
            'success' => true,
            'message' => 'Multiple Choice berhasil dibuat',
            'data' => $savedQuestions
        ]);
    }
    public function forumDiscussion(Request $request, $id)
    {
       $request->validate([
        'forum_title'    => 'required|string|max:255',
        'forum_question' => 'required|string',
        'attachment_type'  => 'nullable|in:pdf,url,video',
        'attachment_value' => 'nullable|string',
        ]);

        // Simpan forum baru
        $forum = CourseForumDiscussion::create([
            'item_id'          => $id,
            'forum_title'      => $request->forum_title,
            'forum_question'   => $request->forum_question,
            'attachment_type'  => $request->attachment_type,
            'attachment_value' => $request->attachment_value,
            'is_draft'         => 0, // default publish
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
        $item = CourseWeekItem::findOrFail($id);
        $moduleId = $item->course_week_id;

        // 🔹 Hapus essay kalau ada
        if ($item->essay) {
            $item->essay()->delete();
        }

        // 🔹 Hapus forum kalau ada
        if ($item->forum) {
            $item->forum()->delete();
        }

        // 🔹 Hapus pertanyaan + optionnya
        if ($item->questions()->exists()) {
            foreach ($item->questions as $q) {
                $q->options()->delete();
                $q->delete();
            }
        }

        // 🔹 Terakhir hapus item
        $item->delete();

        // Ambil ulang semua item dari modul ini, urut by created_at
        $items = CourseWeekItem::where('course_week_id', $moduleId)
                    ->orderBy('created_at', 'asc')
                    ->get();

        // Reorder ulang item_order
        $order = 1;
        foreach ($items as $i) {
            $i->update([
                'item_order' => $order
            ]);
            $order++;
        }

        return response()->json([
            'success' => true,
            'message' => 'Item and related data deleted, items reordered successfully'
        ]);
}

    public function getEssay($itemId)
    {
        $essay = CourseEssay::where('item_id', $itemId)->first();
        return response()->json($essay);
    }

    // ✅ Forum
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

}