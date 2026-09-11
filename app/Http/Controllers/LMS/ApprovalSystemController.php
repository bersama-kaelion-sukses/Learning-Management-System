<?php
namespace App\Http\Controllers\LMS;
use Carbon\Carbon;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\ApprovalSystem\ApprovalType;
use App\Models\ApprovalSystem\ApprovalRequest;
use App\Models\ApprovalSystem\ApprovalRoute;
use App\Models\ApprovalSystem\ApprovalHistory;
use App\Models\User;
use App\Models\Course;
use App\Models\CourseEnrollment;
use App\Models\ConsultationRequest;
// 
use Illuminate\Support\Facades\Log;
use Exception;

class ApprovalSystemController extends Controller
{
    /**
     * Daftar semua approval request (index page).
     */
    public function index(Request $request)
    {
        $user = Auth::user();

        // ambil role_id
        $roleId = (int) $user->role_id;

        // decode sub_role kalau json
        $subRoles = is_array($user->sub_role)
            ? $user->sub_role
            : json_decode($user->sub_role, true);

        if (!is_array($subRoles)) {
            $subRoles = [];
        }

        // super admin jika role_id = 1 atau punya sub_role 1
        $isSuperAdmin = ($roleId === 1) || in_array(1, $subRoles);

        /*
        |--------------------------------------------------------------------------
        | Approval Requests
        |--------------------------------------------------------------------------
        */
        $requestsQuery = ApprovalRequest::with(['approvalType', 'requester'])

            // 🔒 Non-super-admin restriction
            ->when(!$isSuperAdmin, function ($query) use ($user) {
                $query->where(function ($q) use ($user) {
                    $q->where('requester_id', $user->user_id)
                    ->orWhereHas('routes', function ($q2) use ($user) {
                        $q2->where('approver_user_id', $user->user_id)
                            ->where('action_status', 'Pending')
                            ->whereColumn(
                                'approval_requests.current_step',
                                'approval_routes.step_no'
                            );
                    });
                });
            })

            // 🔍 Super Admin – filter by drafter
            ->when($isSuperAdmin && $request->filled('drafter_id'), function ($q) use ($request) {
                $q->where('requester_id', $request->drafter_id);
            })

            ->orderBy('created_at', 'desc');

        $requests = $requestsQuery
            ->get()
            ->groupBy(fn ($item) =>
                $item->approvalType->approval_name ?? 'Tanpa Tipe'
            );

        /*
        |--------------------------------------------------------------------------
        | Drafter List (Super Admin Only)
        |--------------------------------------------------------------------------
        */
        $drafters = collect();

        if ($isSuperAdmin) {
            $drafters = ApprovalRequest::with('requester')
                ->select('requester_id')
                ->distinct()
                ->get()
                ->pluck('requester')
                ->filter()
                ->unique('user_id')
                ->values();
        }

        /*
        |--------------------------------------------------------------------------
        | Course Data (existing logic untouched)
        |--------------------------------------------------------------------------
        */
        $previewCourses = Course::where('is_approved', 0)
            ->where('is_deleted', 0)
            ->where('is_draft', 0)
            ->orderBy('course_title', 'asc')
            ->get();

        $deleteCourses = Course::where('is_approved', 1)
            ->where('is_deleted', 0)
            ->where('is_draft', 0)
            ->orderBy('course_title', 'asc')
            ->get();

        $consulCourses = Course::where('is_approved', 1)
            ->where('is_deleted', 0)
            ->where('is_draft', 0)
            ->orderBy('course_title', 'asc')
            ->get();

        $takeoverCourses = Course::where('is_deleted', 0)
            ->where('is_draft', 0)
            ->orderBy('course_title', 'asc')
            ->get();

        return view(
            'general.approval-list',
            compact(
                'requests',
                'previewCourses',
                'deleteCourses',
                'consulCourses',
                'takeoverCourses',
                'isSuperAdmin',
                'drafters'
            )
        );
    }
    
    public function getCourseLearners($id) 
    {
        // pastikan course valid
            $course = Course::where('course_id', $id)
                ->where('is_deleted', 0)
                ->firstOrFail();

            // ambil learners
            $learners = CourseEnrollment::with('user')
                ->where('course_id', $id)
                ->get()
                ->map(fn($enroll) => [
                    'id'   => $enroll->user->user_id,
                    'name' => $enroll->user->full_name ?? $enroll->user->name,
                    'emp'  => $enroll->user->emp_id ?? '-'
                ]);

            return response()->json($learners);
    }
    
   public function store(Request $request)
    {
        try {
            $request->validate([
                'approval_id'       => 'required|exists:approval_type,approval_id',
                'request_title'     => 'required|string|max:255',
                'request_detail'    => 'nullable|string',
                'approvers'         => 'required|array|min:1',
                'approval_mode'     => 'required|in:Penyetuju,Setuju',
                'course_title'      => 'nullable|string|max:255',
                'course_category'   => 'nullable|string|max:100',
                'course_describe'   => 'nullable|string',
                'max_participant'   => 'nullable|integer|min:1',
                'start_course'      => 'nullable|date',
                'end_course'        => 'nullable|date|after_or_equal:start_course',
                'course_image'      => 'nullable|image|max:2048',
                'course_trainer_id' => 'nullable|integer|exists:users,user_id',
            ]);

            $user = Auth::user();

        // Buat approval request
        $approvalRequest = ApprovalRequest::create([
            'approval_id'   => $request->approval_id,
            'requester_id'  => $user->user_id,
            'request_title' => $request->request_title,
            'request_detail'=> $request->request_detail,
            'status'        => 'In Review',
            'current_step'  => 1,
            'submitted_at'  => now(),
        ]);

        // Simpan alur approval
        if ($request->approval_mode === 'Penyetuju') {
            $step = 1;
            foreach ($request->approvers as $approverId) {
                $approvalRequest->routes()->create([
                    'step_no'          => $step,
                    'approver_user_id' => $approverId,
                    'action_status'    => $step === 1 ? 'Pending' : 'Waiting',
                ]);
                $step++;
            }
        } else {
            foreach ($request->approvers as $approverId) {
                $approvalRequest->routes()->create([
                    'step_no'          => 1, 
                    'approver_user_id' => $approverId,
                    'action_status'    => 'Pending',
                ]);
            }
        }

        // Catat history
        $approvalRequest->histories()->create([
            'step_no'       => 1,
            'actor_user_id' => $user->user_id,
            'action'        => 'Submit',
            'notes'         => 'Request diajukan oleh drafter',
        ]);

        /**
         * 🔥 approval_id = 1 → Ajukan Kursus
         */
        if ((int)$request->approval_id === 1) {
            $trainerId   = $request->course_trainer_id ?: $user->user_id;
            $trainer     = User::find($trainerId);
            $trainerName = $trainer?->full_name ?? $trainer?->name ?? $user->full_name ?? $user->name;

            $startCourse = $request->start_course
                ? \Carbon\Carbon::parse($request->start_course)->format('Y-m-d H:i:s')
                : null;

            $endCourse = $request->end_course
                ? \Carbon\Carbon::parse($request->end_course)->format('Y-m-d H:i:s')
                : null;

            $courseData = [
                'course_title'        => $request->course_title,
                'course_category'     => $request->course_category,
                'course_trainer_id'   => $trainerId,
                'course_trainer_name' => $trainerName,
                'course_describe'     => $request->course_describe,
                'max_participant'     => $request->max_participant,
                'start_course'        => $startCourse,
                'end_course'          => $endCourse,
                'is_approved'         => 0,
                'is_public'           => 0,
                'is_deleted'          => 0,
                'is_requested'        => 1,
                'is_draft'            => 1,
                'last_process'        => now(),
                'person_process'      => $user->emp_id ?? $user->user_id,
            ];

            if ($request->hasFile('course_image')) {
                $imageName = time().'_'.$request->file('course_image')->getClientOriginalName();
                $request->file('course_image')->move(public_path('assets/img/course'), $imageName);
                $courseData['course_image'] = $imageName;
            } else {
                $courseData['course_image'] = 'default-course.jpeg';
            }

            $course = Course::create($courseData);

            $approvalRequest->update([
                'request_detail' => "
                    <div>
                        <h4>Permohonan Pembuatan Kursus</h4>
                        <ul>
                            <li><strong>Course ID:</strong> {$course->course_id}</li>
                            <li><strong>Judul:</strong> {$course->course_title}</li>
                            <li><strong>Kategori:</strong> {$course->course_category}</li>
                            <li><strong>Nama Trainer:</strong> {$course->course_trainer_name}</li>
                            <li><strong>Deskripsi:</strong> {$course->course_describe}</li>
                            <li><strong>Maks. Peserta:</strong> {$course->max_participant}</li>
                            <li><strong>Tanggal Mulai:</strong> {$course->start_course}</li>
                            <li><strong>Tanggal Selesai:</strong> {$course->end_course}</li>
                            
                        </ul>
                    </div>
                "
            ]);
        }
        /**
         * 🔥 approval_id = 2 → Hapus Kursus
         */
        if ((int)$request->approval_id === 2) {
            $course = Course::where('course_id', $request->course_id)->first();

            if (!$course) {
                return back()->with('error', 'Course tidak valid untuk dihapus.');
            }

            $approvalRequest->update([
                'request_detail' => "
                    <div>
                        <h4>Permohonan Penghapusan Kursus</h4>
                        <ul>
                            <li><strong>Course ID:</strong> {$course->course_id}</li>
                            <li><strong>Judul Kursus:</strong> {$course->course_title}</li>
                            <li><strong>Deskripsi:</strong> {$course->course_describe}</li>
                        </ul>
                    </div>
                "
            ]);
        }
        /**
         * 🔥 approval_id = 3 → Konsultasi Learner
         */
        if ((int)$request->approval_id === 3) {
            $course = Course::where('course_id', $request->course_id)
                ->where('is_approved', 1)
                ->where('is_deleted', 0)
                ->where('is_draft', 0)
                ->first();

            if (!$course) {
                return back()->with('error', 'Course tidak valid untuk konsultasi.');
            }

            $learner = User::find($request->learner_id);
            if (!$learner) {
                return back()->with('error', 'Learner tidak ditemukan.');
            }

            $approvalRequest->update([
                'request_detail' => "
                    <div>
                        <h4>Konsultasi Learner</h4>
                        <ul>
                            <li><strong>Course ID:</strong> {$course->course_id}</li>
                            <li><strong>Judul Kursus:</strong> {$course->course_title}</li>
                            <li><strong>Deskripsi:</strong> {$course->course_describe}</li>
                            <li><strong>Learner:</strong> {$learner->full_name} ({$learner->emp_id})</li>
                        </ul>
                    </div>
                "
            ]);
        }

        /**
         * 🔥 approval_id = 4 → Publikasi Kursus
         */
        if ((int)$request->approval_id === 4) {
            // 🔎 Log isi request supaya tahu key apa yang dikirim
            \Log::info('📌 Payload Publikasi diterima:', $request->all());

            // cek dua kemungkinan sumber course_id
            $courseId = $request->course_id ?? $request->input('approval.course_id');

            \Log::info('📌 Course ID yang dipakai:', ['course_id' => $courseId]);

            $course = Course::where('course_id', $courseId)->first();

            if (!$course) {
                \Log::warning('⚠️ Course tidak ditemukan untuk publikasi', ['course_id' => $courseId]);
                return back()->with('error', 'Course tidak valid untuk publikasi.');
            }

            $approvalRequest->update([
                'request_detail' => "
                    <div>
                        <h4>Permohonan Pratinjau & Publikasi Kursus</h4>
                        <ul>
                            <li><strong>Course ID:</strong> {$course->course_id}</li>
                            <li><strong>Judul Kursus:</strong> {$course->course_title}</li>
                            <li><strong>Kategori:</strong> {$course->course_category}</li>
                            <li><strong>Deskripsi:</strong> {$course->course_describe}</li>
                        </ul>
                    </div>
                "
            ]);
        }

        if ((int) $request->approval_id === 5) {
            $courseId     = $request->takeover['course_id'] ?? null;
            $oldTrainerId = $request->takeover['old_trainer_id'] ?? null;
            $newTrainerId = $request->takeover['new_trainer_id'] ?? null;

            $takeover = \App\Models\CourseTakeover::create([
                'course_id'      => $courseId,
                'old_trainer_id' => $oldTrainerId,
                'new_trainer_id' => $newTrainerId,
                'start_date'     => Carbon::parse($request->takeover['start_date']),
                'end_date'       => !empty($request->takeover['end_date']) ? Carbon::parse($request->takeover['end_date']) : null,
                'status'         => 'pending',
                'remarks'        => $request->takeover['remarks'] ?? null,
            ]);

            \Log::info('✅ Takeover tersimpan (status pending):', $takeover->toArray());

            $approvalRequest->update([
                'request_detail' => "
                    <div>
                        <h4>Permohonan Takeover Kursus</h4>
                        <ul>
                            <li><strong>Course ID:</strong> {$courseId}</li>
                            <li><strong>Old Trainer:</strong> " . (\App\Models\User::find($oldTrainerId)?->full_name ?? '-') . "</li>
                            <li><strong>New Trainer:</strong> " . (\App\Models\User::find($newTrainerId)?->full_name ?? '-') . "</li>
                            <li><strong>Periode:</strong> {$request->takeover['start_date']} s/d {$request->takeover['end_date']}</li>
                            <li><strong>Remarks:</strong> {$request->takeover['remarks']}</li>
                        </ul>
                    </div>
                "
            ]);
        }

            return redirect()->route('approval.index')->with('success', 'Permohonan berhasil diajukan.');

        } catch (Exception $e) {
            // 🔥 Catat error detail ke log
            Log::error('Error di ApprovalRequestController@store', [
                'message' => $e->getMessage(),
                'trace'   => $e->getTraceAsString(),
                'request' => $request->all(),
            ]);

            // Optional: tampilkan error ke user
            return back()->with('error', 'Terjadi kesalahan saat memproses permohonan. Silakan cek log.');
        }
    }
    /**
     * Detail request + status approvalnya.
     */
    public function detail($id)
    {
        $request = ApprovalRequest::with(['approvalType','requester','routes.approver','histories.actor'])
                   ->findOrFail($id);
        
        $courseId = null; 
        if ($request->request_detail) {
            if (preg_match('/Course ID:\s*(\d+)/', strip_tags($request->request_detail), $matches)) {
                $courseId = $matches[1];
            }
        }

         return view('general.approval-detail', compact('request', 'courseId'));
    }
    /**
     * Proses Approve / Reject.
     */

    public function process(Request $req, $id)
    {
        $approvalRequest = ApprovalRequest::with('routes')->findOrFail($id);
        $user = Auth::user();
        $step = $approvalRequest->current_step;

        // Cari route aktif
        $route = $approvalRequest->routes()
            ->where('step_no', $step)
            ->where('action_status', 'Pending')
            ->where(function ($q) use ($user) {
                $q->where('approver_user_id', $user->user_id)
                ->orWhere('backup_user_id', $user->user_id);
            })->first();

        if (!$route) {
            return back()->with('error', 'Anda tidak berhak memproses permohonan ini.');
        }

        /**
         * ============================================================
         * 🔥 FAIL-FAST VALIDATION (Dijalankan duluan sebelum approve)
         * ============================================================
         */
        $validatedCourse = null; // placeholder untuk approval_id = 4

        if ($req->action === 'Approve') {

            // ONLY validate for approval_id = 4 (publikasi kursus)
            if ((int) $approvalRequest->approval_id === 4) {

                // Ambil Course ID
                preg_match('/Course ID:<\/strong>\s*(\d+)/', $approvalRequest->request_detail, $idMatch);

                // Ambil Judul Kursus
                preg_match('/Judul Kursus:<\/strong>\s*(.*?)<\/li>/', $approvalRequest->request_detail, $titleMatch);

                // Fail fast jika hilang
                if (empty($idMatch[1]) || empty($titleMatch[1])) {
                    return back()->with('error', 'Approval Error');
                }

                $courseId = (int) $idMatch[1];
                $requestedTitle = trim($titleMatch[1]);

                $course = Course::find($courseId);

                // Fail fast jika mismatch
                if (!$course || strcasecmp($requestedTitle, $course->course_title) !== 0) {
                    return back()->with('error', 'Approval Error');
                }

                // Keep the validated course to avoid finding again later
                $validatedCourse = $course;
            }
        }

        /**
         * ============================================================
         * 🔥 APPROVE LOGIC
         * ============================================================
         */
        if ($req->action === 'Approve') {

            // Step berikutnya
            $nextStep = $step + 1;
            $nextRoute = $approvalRequest->routes()->where('step_no', $nextStep)->first();

            if ($nextRoute) {

                // Masih ada step berikutnya
                $nextRoute->update(['action_status' => 'Pending']);
                $approvalRequest->update(['current_step' => $nextStep]);

            } else {

                // ===============================
                // 🔥 FINAL APPROVAL
                // ===============================
                $approvalRequest->update(['status' => 'Approved Final']);

                /**
                 * ============================================================
                 * 🔥 ACTION TAMBAHAN PER APPROVAL ID
                 * ============================================================
                 */

                // 1. Pembuatan Kursus
                if ((int) $approvalRequest->approval_id === 1) {
                    preg_match('/Course ID.*?(\d+)/', strip_tags($approvalRequest->request_detail), $matches);
                    if (!empty($matches[1])) {
                        Course::where('course_id', $matches[1])
                            ->update(['is_draft' => 0]);
                    }
                }

                // 2. Hapus Kursus
                if ((int) $approvalRequest->approval_id === 2) {
                    preg_match('/Course ID.*?(\d+)/', strip_tags($approvalRequest->request_detail), $matches);
                    if (!empty($matches[1])) {
                        Course::where('course_id', $matches[1])
                            ->update([
                                'is_deleted'  => 1,
                                'is_approved' => 0,
                            ]);
                    }
                }

                // 3. Konsultasi Learner
                if ((int) $approvalRequest->approval_id === 3) {

                    preg_match('/Course ID:\s*(\d+)/', strip_tags($approvalRequest->request_detail), $courseMatch);
                    preg_match('/Learner:.*?\((.*?)\)/', strip_tags($approvalRequest->request_detail), $empMatch);

                    $courseId = $courseMatch[1] ?? null;
                    $empId    = $empMatch[1] ?? null;

                    if ($courseId && $empId) {
                        $learner = User::where('emp_id', $empId)
                            ->where('is_deleted', 0)
                            ->where('is_active', 1)
                            ->first();

                        $course  = Course::find($courseId);

                        if ($learner && $course) {
                            ConsultationRequest::create([
                                'course_id'           => $course->course_id,
                                'trainer_id'          => $course->course_trainer_id,
                                'learner_id'          => $learner->user_id,
                                'topic'               => $approvalRequest->request_title,
                                'status_consultation' => 'waiting',
                                'feedback'            => null,
                            ]);
                        }
                    }
                }

                // 4. Publikasi Kursus (validated earlier)
                if ((int) $approvalRequest->approval_id === 4) {
                    $validatedCourse->update([
                        'is_approved' => 1,
                        'is_draft'    => 0,
                    ]);
                }

                // 5. Takeover Kursus
                if ((int) $approvalRequest->approval_id === 5) {
                    preg_match('/Course ID.*?(\d+)/', strip_tags($approvalRequest->request_detail), $matches);
                    if (!empty($matches[1])) {
                        $courseId = $matches[1];

                        $takeover = \App\Models\CourseTakeover::where('course_id', $courseId)
                            ->latest()
                            ->first();

                        if ($takeover) {
                            $takeover->update(['status' => 'pending']);

                            if ($takeover->new_trainer_id) {
                                $newTrainer = User::find($takeover->new_trainer_id);
                                if ($newTrainer) {
                                    Course::where('course_id', $courseId)->update([
                                        'course_trainer_id'   => $newTrainer->user_id,
                                        'course_trainer_name' => $newTrainer->full_name ?? $newTrainer->name,
                                    ]);
                                }
                            }
                        }
                    }
                }
            }

            // Update route menjadi approved
            $route->update([
                'action_status' => 'Approved',
                'acted_by'      => $user->user_id,
                'acted_at'      => now(),
                'notes'         => $req->notes,
            ]);

            // History approve
            $approvalRequest->histories()->create([
                'step_no'       => $step,
                'actor_user_id' => $user->user_id,
                'action'        => 'Approve',
                'notes'         => $req->notes,
            ]);

        }

        /**
         * ============================================================
         * 🔥 REJECT LOGIC (Tidak diubah sama sekali)
         * ============================================================
         */
        else {

            $route->update([
                'action_status' => 'Rejected',
                'acted_by'      => $user->user_id,
                'acted_at'      => now(),
                'notes'         => $req->notes,
            ]);

            $approvalRequest->update(['status' => 'Rejected']);

            $approvalRequest->histories()->create([
                'step_no'       => $step,
                'actor_user_id' => $user->user_id,
                'action'        => 'Reject',
                'notes'         => $req->notes,
            ]);

            // 1. Reject Pembuatan Kursus
            if ((int) $approvalRequest->approval_id === 1) {
                preg_match('/Course ID:\s*(\d+)/', $approvalRequest->request_detail, $matches);
                if (!empty($matches[1])) {
                    Course::where('course_id', $matches[1])
                        ->update(['is_draft' => 1, 'is_approved' => 0, 'is_deleted' => 1]);
                }
            }

            // 2. Reject Hapus
            if ((int) $approvalRequest->approval_id === 2) {
                preg_match('/Course ID:\s*(\d+)/', $approvalRequest->request_detail, $matches);
                if (!empty($matches[1])) {
                    Course::where('course_id', $matches[1])
                        ->update(['is_deleted' => 0]);
                }
            }

            // 3. Reject Konsultasi
            if ((int) $approvalRequest->approval_id === 3) {
                $approvalRequest->update(['status' => 'Rejected']);
            }

            // 4. Reject Publikasi
            if ((int) $approvalRequest->approval_id === 4) {
                preg_match('/Course ID:\s*(\d+)/', $approvalRequest->request_detail, $matches);
                if (!empty($matches[1])) {
                    Course::where('course_id', $matches[1])
                        ->update(['is_approved' => 0, 'is_public' => 0]);
                }
            }

            // 5. Reject Takeover
            if ((int) $approvalRequest->approval_id === 5) {
                preg_match('/Course ID.*?(\d+)/', strip_tags($approvalRequest->request_detail), $matches);
                if (!empty($matches[1])) {
                    \App\Models\CourseTakeover::where('course_id', $matches[1])
                        ->latest()
                        ->first()?->update(['status' => 'completed']);
                }
            }
        }

        return redirect()->route('approval.index')->with('success', 'Permohonan ini berhasil diproses.');
    }


    public function cancel($id)
    {
        $user = Auth::user();
        $request = ApprovalRequest::with('routes')->findOrFail($id);

        // hanya drafter yang boleh cancel
        if ((int)$request->requester_id !== (int)$user->user_id) {
            return redirect()->back()->with('error', 'Anda tidak berhak membatalkan permohonan ini.');
        }

        // cek apakah sudah ada yang approve
        $hasApproved = $request->routes->where('action_status', 'Approved')->isNotEmpty();
        if ($hasApproved) {
            return redirect()->back()->with('error', 'Permohonan sudah disetujui sebagian, tidak bisa dibatalkan.');
        }

        // update status jadi Cancelled
        $request->update([
            'status' => 'Cancelled',
        ]);

        // tulis ke history
        $request->histories()->create([
            'step_no'       => $request->current_step,
            'actor_user_id' => $user->user_id,
            'action'        => 'Cancel',
            'notes'         => 'Request dibatalkan oleh drafter',
        ]);

        return redirect()->route('approval.index')->with('success', 'Permohonan berhasil dibatalkan.');
    }
}
