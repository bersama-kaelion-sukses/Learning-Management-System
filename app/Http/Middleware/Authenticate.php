<?php

namespace App\Http\Middleware;

use Closure;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;

class Authenticate extends \Illuminate\Auth\Middleware\Authenticate
{
    protected function redirectTo($request)
    {
        if (! $request->expectsJson()) {
            return route('login');
        }
    }

    public function handle($request, Closure $next, ...$guards)
    {
        // Cek apakah user sudah login
        if (!Auth::check()) {
            return redirect()->route('login')->with('error', 'Sesi Sudah kedaluwarsa, Silahkan Login Lagi.');
        }

        // Ambil last activity dari session
        $lastActivity = session('last_activity');
        $now = Carbon::now();

        // Idle timeout 2 jam
        if ($lastActivity && $now->diffInMinutes(Carbon::parse($lastActivity)) > 120) {
            Auth::logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();
            return redirect()->route('login')->with('error', 'Kamu telah keluar, Silahkan Login Lagi.');
        }

        // Refresh session token tiap 1 hari
        $lastRefresh = session('last_session_refresh', $now);
        if (Carbon::parse($lastRefresh)->diffInHours($now) >= 24) {
            $request->session()->regenerate(true);
            session(['last_session_refresh' => $now]);
        }

        // Update last activity
        session(['last_activity' => $now]);

        return $next($request);
    }
}

