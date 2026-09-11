<?php

namespace App\Http\Controllers\LMS;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\Hash;
use Illuminate\Http\Request;
use App\Models\Course;
use App\Models\CourseProgress;
use Illuminate\Support\Collection;
use App\Models\CourseWeekItem;
use App\Models\CourseType\CourseAttachmentSubmission;
use App\Models\CourseType\CourseEssaySubmission;
use App\Models\CourseType\CourseMcSubmission;
use App\Models\User;

class ProfileController extends Controller
{
//   public function index()
//     {
//         $currentUser = Auth::user();
//         $userId = $currentUser->user_id;

//         // ============================================
//         // 1. Ambil semua course progress 100%
//         // ============================================
//         $finishedCourses = CourseProgress::where('user_id', $userId)
//             ->where('progress_pct', '>=', 100)
//             ->with('course')
//             ->get()
//             ->pluck('course')
//             ->unique('course_id')
//             ->values();

//         // Kita modifikasi: tambahkan flag is_passed berdasarkan remedial logic
//         $finishedCourses = $finishedCourses->map(function ($course) use ($userId) {

//             $items = CourseWeekItem::where('course_id', $course->course_id)
//                 ->whereIn('course_item_type', [3, 4, 7]) // hanya item penilaian
//                 ->get();

//             $totalSubmissionItems = $items->count();
//             $failed = 0;

//             foreach ($items as $item) {
//                 $passing = $item->passing_grade ?? 0;

//                 // ========================= ESSAY ========================
//                 if ($item->course_item_type == 3) {
//                     $grade = DB::table('course_essay_submissions')
//                         ->where('user_id', $userId)
//                         ->where(function ($q) use ($item) {
//                             $q->where('item_id', $item->item_id)
//                             ->orWhere('essay_id', $item->item_id);
//                         })
//                         ->where('is_remedial', 0)
//                         ->orderByDesc('submitted_at')
//                         ->value('grade');

//                     if (!is_null($grade) && $grade < $passing) {
//                         $failed++;
//                     }
//                 }

//                 // ====================== MULTIPLE CHOICE ====================
//                 elseif ($item->course_item_type == 4) {
//                     $grades = DB::table('course_mc_submissions')
//                         ->where('user_id', $userId)
//                         ->where('item_id', $item->item_id)
//                         ->orderByDesc('attempt_no')
//                         ->get()
//                         ->pluck('grade')
//                         ->filter() // remove null
//                         ->values();

//                     if ($grades->isNotEmpty()) {
//                         $hasPassed = $grades->contains(fn($g) => $g >= $passing);
//                         if (!$hasPassed) $failed++;
//                     }
//                 }

//                 // ======================== ATTACHMENT =======================
//                 elseif ($item->course_item_type == 7) {
//                     $grade = DB::table('course_attachment_submissions')
//                         ->where('user_id', $userId)
//                         ->where('item_id', $item->item_id)
//                         ->where('is_remedial', 0)
//                         ->orderByDesc('submitted_at')
//                         ->value('grade');

//                     if (!is_null($grade) && $grade < $passing) {
//                         $failed++;
//                     }
//                 }
//             }

//             // ========================= REMEDIAL LOGIC =========================
//             $threshold = ceil($totalSubmissionItems / 2);
//             $isRemedial = $failed >= $threshold;

//             // ================================
//             //  LOG PER COURSE
//             // =================================
//             // \Log::info("📘 [PROFILE: COURSE EVALUATION]", [
//             //     'user_id'          => $userId,
//             //     'course_id'        => $course->course_id,
//             //     'course_title'     => $course->course_title,
//             //     'total_items'      => $totalSubmissionItems,
//             //     'failed_items'     => $failed,
//             //     'threshold'        => $threshold,
//             //     'is_remedial'      => $isRemedial,
//             //     'passed?'          => !$isRemedial,
//             //     'timestamp'        => now()->toDateTimeString(),
//             // ]);

//             // Simpan ke object course (untuk dipakai di blade)
//             $course->is_passed = !$isRemedial; // true = lulus, false = belum lulus
//             $course->failed_items = $failed;
//             $course->total_items = $totalSubmissionItems;

//             return $course;
//         });

//         // ============================================
//         // 2. Hitung nilai (average dari last submissions)
//         // ============================================
//         $courseIds = $finishedCourses->pluck('course_id');
//         $itemIds = CourseWeekItem::whereIn('course_id', $courseIds)->pluck('item_id');

//         $attachmentGrades = CourseAttachmentSubmission::whereIn('item_id', $itemIds)
//             ->where('user_id', $userId)
//             ->where('is_remedial', 0)
//             ->whereNotNull('grade')
//             ->orderBy('submitted_at', 'desc')
//             ->value('grade');

//         $essayGrades = CourseEssaySubmission::whereIn('item_id', $itemIds)
//             ->where('user_id', $userId)
//             ->where('is_remedial', 0)
//             ->whereNotNull('grade')
//             ->orderBy('created_at', 'desc')
//             ->value('grade');

//         $mcGrades = CourseMcSubmission::whereIn('item_id', $itemIds)
//             ->where('user_id', $userId)
//             ->where('is_remedial', 0)
//             ->whereNotNull('grade')
//             ->max('grade');

//         $gradeSets = collect([$attachmentGrades, $essayGrades, $mcGrades])
//             ->filter(fn($v) => !is_null($v));

//         $getTotalGradeCourse = $gradeSets->isNotEmpty()
//             ? round($gradeSets->avg(), 2)
//             : 0.00;

//         // ============================================
//         // 3. Foto & Label
//         // ============================================
//         $photoUrl = $currentUser->photo_profile
//             ? (Str::startsWith($currentUser->photo_profile, ['http://', 'https://'])
//                 ? $currentUser->photo_profile
//                 : asset($currentUser->photo_profile))
//             : asset('assets/img/defaultPic.jpeg');

//         $deptMap = [1=>'IT',2=>'Finance',3=>'HR',4=>'Marketing',5=>'Operation'];
//         $roleMap = [1=>'Super Admin/IT',2=>'Administrator',3=>'Instructor',4=>'Learner'];

//         // ============================================
//         // 4. RETURN VIEW
//         // ============================================
//         return view('general.profile', [
//             'currentUser'         => $currentUser,
//             'photoUrl'            => $photoUrl,
//             'deptLabel'           => $deptMap[$currentUser->departement_cat] ?? 'Tidak Diketahui',
//             'roleLabel'           => $roleMap[$currentUser->role_id] ?? 'Tidak Diketahui',
//             'getFinishedCourse'   => $finishedCourses,
//             'getTotalGradeCourse' => $getTotalGradeCourse,
//         ]);
//     }

    public function index()
    {
        $currentUser = Auth::user();
        $userId = $currentUser->user_id;
    
        // ============================================
        // 1. Ambil semua course progress 100%
        // ============================================
        $finishedCourses = CourseProgress::where('user_id', $userId)
            ->where('progress_pct', '>=', 100)
            ->with('course')
            ->get()
            ->pluck('course')
            ->unique('course_id')
            ->values();
    
        // ============================================
        // 2. Tambahkan remedial + not graded logic
        // ============================================
        $finishedCourses = $finishedCourses->map(function ($course) use ($userId) {
    
            $items = CourseWeekItem::where('course_id', $course->course_id)
                    ->whereIn('course_item_type', [3, 4, 7])
                    ->get();
                
                $totalItems = $items->count();
                $failed = 0;
                $notGraded = 0;
                
                foreach ($items as $item) {
                
                    $passing = $item->passing_grade ?? 0;
                
                    // =============== ESSAY ===============
                    if ($item->course_item_type == 3) {
                
                        $row = DB::table('course_essay_submissions')
                            ->where('user_id', $userId)
                            ->where(function ($q) use ($item) {
                                $q->where('item_id', $item->item_id)
                                  ->orWhere('essay_id', $item->item_id);
                            })
                            ->where('is_remedial', 0)
                            ->orderByDesc('submitted_at')
                            ->first();
                
                        if ($row) {
                            // ⭐ RULE: NULL = gagal
                            if (is_null($row->grade)) {
                                $notGraded++;
                                $failed++;
                            } elseif ($row->grade < $passing) {
                                $failed++;
                            }
                        }
                        // tidak submit → aman
                    }
                
                    // =============== MULTIPLE CHOICE ===============
                    elseif ($item->course_item_type == 4) {
                
                        $rows = DB::table('course_mc_submissions')
                            ->where('user_id', $userId)
                            ->where('item_id', $item->item_id)
                            ->orderByDesc('attempt_no')
                            ->get();
                
                        if ($rows->isNotEmpty()) {
                
                            $validGrades = $rows->pluck('grade')->filter(); // remove nulls
                
                            if ($validGrades->isEmpty()) {
                                // ⭐ semua grade NULL → gagal
                                $notGraded++;
                                $failed++;
                            } else {
                                $hasPassed = $validGrades->contains(fn($g) => $g >= $passing);
                                if (!$hasPassed) {
                                    $failed++;
                                }
                            }
                        }
                    }
                
                    // =============== ATTACHMENT ===============
                    elseif ($item->course_item_type == 7) {
                
                        $row = DB::table('course_attachment_submissions')
                            ->where('user_id', $userId)
                            ->where('item_id', $item->item_id)
                            ->where('is_remedial', 0)
                            ->orderByDesc('submitted_at')
                            ->first();
                
                        if ($row) {
                            if (is_null($row->grade)) {
                                $notGraded++;
                                $failed++;
                            } elseif ($row->grade < $passing) {
                                $failed++;
                            }
                        }
                    }
                }
                
                // ===============================
                // REMEDIAL LOGIC (FINAL)
                // ===============================
                $threshold = ceil($totalItems / 2);
                $isRemedial = $failed >= $threshold;
                
                // ===============================
                // BADGE LOGIC (FINAL)
                // ===============================
                $course->is_passed = !$isRemedial; // ⭐ lulus jika tidak remedial
                $course->failed_items = $failed;
                $course->not_graded = $notGraded;
                $course->total_items = $totalItems;
                
                // Debug log optional
                \Log::info("🟦 DEBUG BADGE PROFILE", [
                    'course_id'   => $course->course_id,
                    'title'       => $course->course_title,
                    'total_items' => $totalItems,
                    'failed'      => $failed,
                    'not_graded'  => $notGraded,
                    'threshold'   => $threshold,
                    'is_passed'   => $course->is_passed,
                ]);
            
            return $course;
        });
    
        // ============================================
        // 3. Hitung nilai rata-rata terakhir
        // ============================================
        $courseIds = $finishedCourses->pluck('course_id');
        $itemIds = CourseWeekItem::whereIn('course_id', $courseIds)->pluck('item_id');
    
        $attachmentGrades = CourseAttachmentSubmission::whereIn('item_id', $itemIds)
            ->where('user_id', $userId)
            ->where('is_remedial', 0)
            ->whereNotNull('grade')
            ->orderBy('submitted_at', 'desc')
            ->value('grade');
    
        $essayGrades = CourseEssaySubmission::whereIn('item_id', $itemIds)
            ->where('user_id', $userId)
            ->where('is_remedial', 0)
            ->whereNotNull('grade')
            ->orderBy('created_at', 'desc')
            ->value('grade');
    
        $mcGrades = CourseMcSubmission::whereIn('item_id', $itemIds)
            ->where('user_id', $userId)
            ->where('is_remedial', 0)
            ->whereNotNull('grade')
            ->max('grade');
    
        $gradeSets = collect([$attachmentGrades, $essayGrades, $mcGrades])
            ->filter(fn($v) => !is_null($v));
    
        $getTotalGradeCourse = $gradeSets->isNotEmpty()
            ? round($gradeSets->avg(), 2)
            : 0.00;
    
        // ============================================
        // 4. foto & label user
        // ============================================
        $photoUrl = $currentUser->photo_profile
            ? (Str::startsWith($currentUser->photo_profile, ['http://', 'https://'])
                ? $currentUser->photo_profile
                : asset($currentUser->photo_profile))
            : asset('assets/img/defaultPic.jpeg');
    
        $deptMap = [
            1  => 'IT',
            3  => 'HR & GA',
            4  => 'Marketing',
            6  => 'Product & Planning',
            7  => 'Retail Marketing',
            8  => 'Fleet Marketing',
            9  => 'Operation & Customer Care',
            10 => 'Risk Management',
            11 => 'Credit',
            12 => 'Collection',
            13 => 'Internal Audit',
            14 => 'Legal & Compliance',
            15 => 'Finance & Accounting',
            16 => 'KB Capital',
        ];
    
        $roleMap = [
            1 => 'Super Admin/IT', 2 => 'Administrator', 3 => 'Instructor', 4 => 'Learner'
        ];
    
        // ============================================
        // 5. RETURN VIEW
        // ============================================
        return view('general.profile', [
            'currentUser'         => $currentUser,
            'photoUrl'            => $photoUrl,
            'deptLabel'           => $deptMap[$currentUser->departement_cat] ?? 'Tidak Diketahui',
            'roleLabel'           => $roleMap[$currentUser->role_id] ?? 'Tidak Diketahui',
            'getFinishedCourse'   => $finishedCourses,
            'getTotalGradeCourse' => $getTotalGradeCourse,
        ]);
    }


    public function editUser(Request $request)
    {
        $user = Auth::user();
    
        // ONLY VALIDATE PHOTO
        $request->validate([
            'photo_profile' => ['nullable', 'image', 'mimes:jpeg,png,jpg', 'max:10240'],
        ], [
            'photo_profile.image' => 'File harus berupa gambar.',
            'photo_profile.mimes' => 'Format foto hanya boleh jpeg, png, atau jpg.',
            'photo_profile.max'   => 'Ukuran foto maksimal 10MB.',
        ]);
    
        // HANDLE PHOTO UPLOAD
        if ($request->hasFile('photo_profile')) {
            $file = $request->file('photo_profile');
    
            $filename = Str::slug($user->emp_id) . '.' . $file->getClientOriginalExtension();
            $destination = public_path('assets/img');
    
            if (!file_exists($destination)) {
                mkdir($destination, 0755, true);
            }
    
            $file->move($destination, $filename);
    
            $user->photo_profile = 'assets/img/' . $filename;
        }
    
        // FULL NAME & EMP_ID TIDAK DIUBAH KARENA HANYA HIASAN
        // (tidak dikirim ke backend dan tidak perlu diproses)
    
        $user->save();
    
        return back()->with('success', 'Foto profil berhasil diperbarui.');
    }

    public function resetPassword(Request $request, $emp_id)
    {
        if (!$request->filled('current_password')) {
            return response()->json([
                'success' => false,
                'message' => 'Password konfirmasi wajib diisi.'
            ], 422);
        }

        if (!Hash::check($request->current_password, Auth::user()->password)) {
        return response()->json([
            'success' => false,
            'message' => 'Password konfirmasi tidak valid.'
        ]);
        }

        // 🔹 Find user by employee ID
        $user = User::where('emp_id', $emp_id)->first();

        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'User not found.'
            ], 404);
        }

        // 🔹 Update password and flags
        $user->update([
            'password' => Hash::make($emp_id),
            'is_first_login' => 1,
            'password_changed_at' => now(),
        ]);

        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();
        
        return response()->json([
            'success' => true,
            'message' => 'Password has been reset successfully. Please login again.',
            'redirect' => route('login'),
        ]);
    }
}
