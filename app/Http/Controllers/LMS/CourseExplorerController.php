<?php

namespace App\Http\Controllers\LMS;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use App\Models\Course;
use App\Models\CourseWeekItem;
use App\Models\CourseWeekModule;
use App\Models\CourseEnrollment;
use Carbon\Carbon;

class CourseExplorerController extends Controller
{
    public function index(Request $request)
    {
        $q = trim($request->input('q', ''));

        $query = Course::query()
            ->where("is_approved", 1)
            ->where('is_public', 1)
            ->where('is_deleted', 0);

        if ($q !== '') {
            $query->where(function ($x) use ($q) {
                $x->where('course_title', 'like', "%{$q}%")
                ->orWhere('course_describe', 'like', "%{$q}%")
                ->orWhere('course_category', 'like', "%{$q}%");
            });
        }

        // 🔄 ganti paginate -> get
        $courses = $query->latest('updated_at')->get();

        // ✅ pastikan gambar tetap dicek
        $courses->transform(function ($c) {
            $file = $c->course_image ? public_path('assets/img/course/'.$c->course_image) : null;
            $c->course_image_url = ($c->course_image && $file && file_exists($file))
                ? asset('assets/img/course/'.$c->course_image)
                : asset('assets/img/course/default-course.jpeg');
            return $c;
        });

        return view('learner.explore', compact('courses','q'));
    }

    public function show($courseId)
    {
        $course = Course::where('course_id', $courseId)
            ->where('is_public', 1)
            ->where('is_deleted', 0)
            ->firstOrFail();

        // =============================
        // COVER IMAGE
        // =============================
        $file = $course->course_image ? public_path('assets/img/course/'.$course->course_image) : null;
        $course->course_image_url = ($course->course_image && $file && file_exists($file))
            ? asset('assets/img/course/'.$course->course_image)
            : asset('assets/img/course/default-course.jpeg');

        // =============================
        // ENROLLMENT COUNT (Learner only)
        // =============================
        $course->enrollment = CourseEnrollment::where('course_id', $courseId)
            ->whereHas('user', function ($q) {
                $q->where('role_id', '!=', 1)
                ->where(function ($sub) {
                    $sub->whereNull('sub_role')
                        ->orWhereRaw('JSON_CONTAINS(sub_role, "[1]") = 0');
                });
            })
            ->count();

        // =============================
        // DISPLAY PERIOD TEXT
        // =============================
        $start = $course->start_course ? Carbon::parse($course->start_course)->format('d M Y') : null;
        $end   = $course->end_course ? Carbon::parse($course->end_course)->format('d M Y') : null;

        if ($start && $end) {
            $course->period = "{$start} � {$end}";
        } elseif ($start && !$end) {
            $course->period = "Dibuka dari {$start}";
        } elseif (!$start && $end) {
            $course->period = "Terbuka hingga {$end}";
        } else {
            $course->period = "Tidak ditentukan";
        }

        // =============================
        // MODULES & ITEMS
        // =============================
        // $modules = CourseWeekModule::where('course_id', $courseId)
        //     ->orderBy('course_start', 'asc')
        //     ->orderBy('course_week_id', 'asc')
        //     ->get();

        // $items = CourseWeekItem::where('course_id', $courseId)
        //     ->orderBy('course_week_id', 'asc')
        //     ->orderBy('created_at', 'asc')
        //     ->get()
        //     ->groupBy('course_week_id');
        
        // =============================
        // MODULES & ITEMS
        // =============================
        $modules = CourseWeekModule::where('course_id', $courseId)
            ->orderBy('week_order', 'asc')
            ->get();

        $items = CourseWeekItem::where('course_id', $courseId)
            ->orderBy('item_order', 'asc')
            ->get()
            ->groupBy('course_week_id');

        // =============================
        // PERIOD STATUS WITH FIXED TIME LOGIC
        // =============================
        $now = Carbon::now();

        $startRaw = $course->start_course ? Carbon::parse($course->start_course)->startOfDay() : null;
        $endRaw   = $course->end_course ? Carbon::parse($course->end_course)->endOfDay() : null;

        if ($startRaw && $now->lt($startRaw)) {
            $course->period_status  = 'not_started';
            $course->period_message = 'Belum dimulai';
        }
        elseif ($endRaw && $now->gt($endRaw)) {
            $course->period_status  = 'ended';
            $course->period_message = 'Telah berakhir';
        }
        else {
            $course->period_status  = 'active';
            $course->period_message = 'Sedang berlangsung';
        }

        // =============================
        // USER ENROLLMENT (OPTIONAL)
        // =============================
        $enrollment = null;
        if (Auth::check()) {
            $enrollment = CourseEnrollment::where('course_id', $courseId)
                ->where('user_id', Auth::id())
                ->first();
        }

        return view('learner.detail-course', compact('course','modules','items','enrollment'));
    }
    
     public function requestJoinUser($courseId)
        {
            $userId = Auth::id();
    
            $course = Course::findOrFail($courseId);
    
            $currentCount = CourseEnrollment::where('course_id', $courseId)
            ->where('status_join', 1)
            ->count();
    
            if ($course->max_participant !== null && $currentCount >= $course->max_participant) {
                return back()->with('error', 'Maaf, kapasitas peserta sudah penuh dan tidak dapat menerima peserta baru.');
            }
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
                    'person_process' => Auth::user()->full_name ?? Auth::user()->name ?? 'system',
                ]
            );
    
            return back()->with('success', 'Anda berhasil join ke course.');
        }
}
