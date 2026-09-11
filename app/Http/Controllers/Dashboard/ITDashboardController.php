<?php

namespace App\Http\Controllers\Dashboard;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
use App\Models\ApprovalSystem\ApprovalHistory;
use App\Models\ApprovalSystem\ApprovalRoute;
use App\Exports\LoginSessionExport;
use Maatwebsite\Excel\Facades\Excel;



class ITDashboardController extends Controller
{
    /**
     * ============================================================
     * 🧭 INDEX: IT Dashboard Overview
     * ============================================================
     */
    public function index()
    {
        // Summary data for IT Dashboard
        $activeUsers = DB::table('login_sessions')
            ->whereMonth('created_at', now()->month)
            ->distinct('user_id')
            ->count('user_id');

        $loginSessions = DB::table('login_sessions')
            ->whereMonth('created_at', now()->month)
            ->count();

        $approvalRequests = DB::table('approval_requests')->count();

        $learnerLogs = DB::table('course_essay_submissions')->count()
            + DB::table('course_attachment_submissions')->count()
            + DB::table('course_mc_submissions')->count()
            + DB::table('course_enrollment')->count();

        // Login chart 6-month trend
        $loginMonths = [];
        $loginData = [];
        for ($i = 5; $i >= 0; $i--) {
            $month = now()->subMonths($i)->format('M');
            $count = DB::table('login_sessions')
                ->whereBetween('created_at', [
                    now()->subMonths($i)->startOfMonth(),
                    now()->subMonths($i)->endOfMonth()
                ])
                ->count();
            $loginMonths[] = $month;
            $loginData[] = $count;
        }

        // Approval Activities
        $recentApprovals = DB::table('approval_requests as ar')
            ->leftJoin('approval_type as at', 'ar.approval_id', '=', 'at.approval_id')
            ->select(
                'ar.request_id',
                'ar.approval_id',
                'at.approval_name as approval_type', // ✅ Mapping tipe approval
                'ar.request_title',
                'ar.status',
                'ar.updated_at'
            )
            ->orderByDesc('ar.updated_at')
            ->get()
            ->map(function ($a) {
                $a->status_color = match ($a->status) {
                    'Approved Final' => 'success',
                    'Rejected' => 'danger',
                    'In Review' => 'warning text-dark',
                    default => 'secondary'
                };
                return $a;
            });

        
        // 🧾 Approval History (new section)
        $approvalHistories = ApprovalHistory::with(['actor.division', 'request.approvalType'])
            ->orderByDesc('created_at')
            ->get();

        $approvalRoutes = ApprovalRoute::with(['request.approvalType', 'approver', 'backup', 'actor'])
            ->orderByDesc('acted_at')
            ->get();
        
        // Top Active Learners
        // $topLearners = DB::table('course_progress')
        //     ->join('users', 'course_progress.user_id', '=', 'users.user_id')
        //     ->leftJoin('divisions', 'users.departement_cat', '=', 'divisions.division_id')
        //     ->select(
        //         'users.emp_id',
        //         'users.full_name as name',
        //         'divisions.division_name as division_name',
        //         DB::raw('COUNT(course_progress.course_id) as completed'),
        //         DB::raw('AVG(course_progress.progress_pct) as progress')
        //     )
        //     ->groupBy('users.emp_id', 'users.full_name', 'divisions.division_name')
        //     ->orderByDesc('progress')
        //     ->get()
        //     ->map(function ($l) {
        //         $p = $l->progress ?? 0;
        //         $l->progress_color = match (true) {
        //             $p >= 85 => 'bg-success',
        //             $p >= 60 => 'bg-warning',
        //             $p >= 40 => 'bg-primary',
        //             default => 'bg-danger'
        //         };
        //         return $l;
        //     });
            
        // ====================
         $topLearners = DB::table('course_progress')
            ->join('users', 'course_progress.user_id', '=', 'users.user_id')
            ->leftJoin('divisions', 'users.departement_cat', '=', 'divisions.division_id')
            ->select(
                'users.emp_id',
                'users.full_name as name',
                'divisions.division_name as division_name',
                DB::raw('COUNT(course_progress.course_id) as completed'),
                DB::raw('AVG(course_progress.progress_pct) as progress')
            )
            ->where(function ($q) {
                $q->where('users.role_id', '!=', 1)
                ->where('users.sub_role', '!=', 1);
            })
            ->groupBy('users.emp_id', 'users.full_name', 'divisions.division_name')
            ->orderByDesc('progress')
            
            ->get()
            ->map(function ($l) {
                $p = $l->progress ?? 0;
                $l->progress_color = match (true) {
                    $p >= 85 => 'bg-success',
                    $p >= 60 => 'bg-warning',
                    $p >= 40 => 'bg-primary',
                    default => 'bg-danger'
                };
                return $l;
            });
        // ====================

        // Log dashboard access
        DB::table('activity_logs')->insert([
            'user_id' => Auth::id(),
            'activity_desc' => "Mengakses halaman IT Dashboard",
            'created_at' => now(),
            'updated_at' => now()
        ]);

        return view('dashboard.component.it-dashboard', [
            'activeUsers' => $activeUsers,
            'loginSessions' => $loginSessions,
            'approvalRequests' => $approvalRequests,
            'learnerLogs' => $learnerLogs,
            'recentApprovals' => $recentApprovals,
            'topLearners' => $topLearners,
            'loginMonths' => $loginMonths,
            'loginData' => $loginData,
            'approvalHistories' => $approvalHistories,
            'approvalRoutes' => $approvalRoutes,
        ]);
    }

    /**
     * ============================================================
     * 📘 ACTIVITY INQUIRY
     * ============================================================
     */
    public function ActivityInquiry()
    {
        $activities = collect();
        $now = now();

        // ===================== 🧍 USER LOGIN =====================
        $logins = DB::table('login_sessions')->latest('created_at')->take(10)->get();
        foreach ($logins as $l) {
            $activities->push([
                'user_id' => $l->user_id,
                'desc' => 'Login ke sistem',
                'time' => $l->created_at
            ]);
        }

        // =================== 🎓 LEARNER ACTIVITIES ================
        $enrolls = DB::table('course_enrollment')->latest('created_at')->take(10)->get();
        foreach ($enrolls as $e) {
            $activities->push([
                'user_id' => $e->user_id,
                'desc' => "Bergabung ke kursus ID {$e->course_id}",
                'time' => $e->created_at
            ]);
        }

        $essays = DB::table('course_essay_submissions')->latest('created_at')->take(10)->get();
        foreach ($essays as $s) {
            $activities->push([
                'user_id' => $s->user_id,
                'desc' => "Mengumpulkan tugas Essay (Item {$s->course_item_id})",
                'time' => $s->created_at
            ]);
        }

        $attachments = DB::table('course_attachment_submissions')->latest('created_at')->take(10)->get();
        foreach ($attachments as $a) {
            $activities->push([
                'user_id' => $a->user_id,
                'desc' => "Mengumpulkan tugas Attachment (Item {$a->course_item_id})",
                'time' => $a->created_at
            ]);
        }

        $multiple = DB::table('course_mc_submissions')->latest('created_at')->take(10)->get();
        foreach ($multiple as $m) {
            $activities->push([
                'user_id' => $m->user_id,
                'desc' => "Menyelesaikan kuis pilihan ganda (Item {$m->course_item_id})",
                'time' => $m->created_at
            ]);
        }

        $progress = DB::table('course_progress')->latest('updated_at')->take(10)->get();
        foreach ($progress as $p) {
            $activities->push([
                'user_id' => $p->user_id,
                'desc' => "Memperbarui progres kursus menjadi {$p->progress_pct}%",
                'time' => $p->updated_at
            ]);
        }

        // ================== 🧑‍🏫 INSTRUCTOR ACTIVITIES =============
        $createCourse = DB::table('course')->latest('created_at')->take(5)->get();
        foreach ($createCourse as $c) {
            $activities->push([
                'user_id' => $c->created_by,
                'desc' => "Membuat kursus baru: {$c->course_title}",
                'time' => $c->created_at
            ]);
        }

        $modifyCourse = DB::table('course')->whereNotNull('updated_at')->latest('updated_at')->take(5)->get();
        foreach ($modifyCourse as $c) {
            $activities->push([
                'user_id' => $c->updated_by,
                'desc' => "Memodifikasi kursus: {$c->course_title}",
                'time' => $c->updated_at
            ]);
        }

        $takeover = DB::table('course_takeovers')->latest('created_at')->take(5)->get();
        foreach ($takeover as $t) {
            $activities->push([
                'user_id' => $t->requested_by,
                'desc' => "Mengajukan takeover untuk kursus ID {$t->course_id}",
                'time' => $t->created_at
            ]);
        }

        // ================== 🛠️ ADMINISTRATOR ACTIVITIES ===========
        $adminCreateUser = DB::table('users')->latest('created_at')->take(5)->get();
        foreach ($adminCreateUser as $u) {
            $activities->push([
                'user_id' => $u->created_by ?? 'Admin',
                'desc' => "Menambahkan user baru: {$u->full_name}",
                'time' => $u->created_at
            ]);
        }

        $adminUpdateUser = DB::table('users')->whereNotNull('updated_at')->latest('updated_at')->take(5)->get();
        foreach ($adminUpdateUser as $u) {
            $activities->push([
                'user_id' => $u->updated_by ?? 'Admin',
                'desc' => "Memodifikasi user: {$u->full_name}",
                'time' => $u->updated_at
            ]);
        }

        $approvalRelease = DB::table('approval_requests')->latest('updated_at')->take(5)->get();
        foreach ($approvalRelease as $a) {
            $activities->push([
                'user_id' => $a->requested_by,
                'desc' => "Melakukan approval request tipe {$a->type_name}",
                'time' => $a->updated_at
            ]);
        }

        // ================== 🧾 FORMAT OUTPUT ======================
        $formatted = $activities->map(function ($a) {
            $uid = $a['user_id'] ?? '-';
            $desc = $a['desc'];
            $time = Carbon::parse($a['time'])->format('Y-m-d H:i:s');
            return "User ID {$uid} melakukan aktivitas {$desc} pada tanggal dan waktu {$time}.";
        });

        // ================== 💾 SIMPAN KE LOG TABEL ===============
        foreach ($formatted as $line) {
            DB::table('activity_logs')->insert([
                'user_id' => Auth::id() ?? null,
                'activity_desc' => $line,
                'created_at' => now(),
                'updated_at' => now()
            ]);
        }

        // ================== 📤 RETURN RESPONSE ===================
        return response()->json([
            'generated_by' => Auth::user()->full_name ?? 'System',
            'timestamp' => $now->format('Y-m-d H:i:s'),
            'activity_count' => $formatted->count(),
            'activity_logs' => $formatted
        ]);
    }

    public function export(Request $request)
    {
        $startMonth = $request->input('start_month');
        $endMonth   = $request->input('end_month');

        if (!$startMonth || !$endMonth) {
            return back()->with('error', 'Periode bulan harus diisi!');
        }

        return Excel::download(
            new LoginSessionExport($startMonth, $endMonth), 
            'login_sessions_'.$startMonth.'_to_'.$endMonth.'.xlsx'
        );
    }
}
