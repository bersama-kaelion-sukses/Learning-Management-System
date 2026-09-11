<?php

namespace App\Http\Controllers\LMS;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\LmsLoginSession; // model login table
use Illuminate\Support\Facades\Auth;

class LoginSessionController extends Controller
{
    /**
     * Display all login sessions (Admin view).
     */
    public function index()
    {
        $sessions = LmsLoginSession::orderBy('login_time', 'desc')->paginate(20);
        return view('lms.sessions.index', compact('sessions'));
    }

    /**
     * Store a new login session log.
     */
    public function store(Request $request)
    {
        // Call this after successful login
        LmsLoginSession::create([
            'emp_id' => Auth::user()->emp_id,
            'user_id' => Auth::id(),
            'role_id' => Auth::user()->role_id,
            'sub_role' => Auth::user()->sub_role,
            'session' => session()->getId(),
            'login_time' => now(),
            'device_info' => $request->header('User-Agent'),
            'ip_address' => $request->ip(),
            'is_success' => 1,
        ]);

        return response()->json(['message' => 'Login session recorded']);
    }

    /**
     * Update a session (ex: mark logout time).
     */
    public function update(Request $request, string $id)
    {
        $session = LmsLoginSession::findOrFail($id);
        $session->update([
            'logout_time' => now(),
        ]);

        return response()->json(['message' => 'Session updated']);
    }

    /**
     * Destroy a session (force logout).
     */
    public function destroy(string $id)
    {
        $session = LmsLoginSession::findOrFail($id);
        $session->update([
            'logout_time' => now(),
        ]);

        return response()->json(['message' => 'Session terminated']);
    }
}

