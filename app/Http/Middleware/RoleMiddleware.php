<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Support\Facades\Auth;

class RoleMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request $request
     * @param  \Closure $next
     * @param  mixed ...$roles -> daftar role_id yang diizinkan
     */
    public function handle($request, Closure $next, ...$roles)
    {
        if (!Auth::check()) {
            return redirect()->route('login');
        }

        $user = Auth::user();

        // 🔹 Ambil role utama
        $mainRole = (int) $user->role_id;

        // 🔹 Ambil sub_role (bisa array atau JSON)
        $subRoles = is_array($user->sub_role)
            ? $user->sub_role
            : json_decode($user->sub_role ?? '[]', true);

        // 🔹 Gabungkan semuanya ke dalam satu array dan ubah ke integer
        $userRoles = array_map('intval', array_merge([$mainRole], $subRoles ?: []));

        // 🔹 Konversi daftar role yang diizinkan ke integer juga
        $allowedRoles = array_map('intval', $roles);

        // 🔹 Cek apakah user memiliki salah satu role yang diizinkan
        $hasAccess = count(array_intersect($userRoles, $allowedRoles)) > 0;

        if (!$hasAccess) {
            abort(403, 'Access denied. You are not authorized.');
        }

        return $next($request);
    }
}
