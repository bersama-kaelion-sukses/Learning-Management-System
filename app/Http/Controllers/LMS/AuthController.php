<?php

namespace App\Http\Controllers\LMS;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Http;
use App\Models\User;
use App\Models\LmsLoginSession;
use Illuminate\Support\Facades\Log;

class AuthController extends Controller
{
    /**
     * Show login form
     */
    public function showLoginForm()
    {
        return view('auth.login'); // Blade login UI
    }

    /**
     * Show change password page (first login)
     */
    public function showChangePassword()
    {
        return view('auth.newLogin');
    }

    /**
     * Update password (for first login)
     */
    public function updatePassword(Request $request)
    {
        $request->validate([
            'password' => [
                'required', 'confirmed', 'min:8',
                'regex:/[A-Z]/', 'regex:/[a-z]/', 'regex:/[0-9]/', 'regex:/[\W]/'
            ],
        ], [
            'password.required'  => json_lang('New password is required.'),
            'password.confirmed' => json_lang('Password confirmation does not match.'),
            'password.min'       => json_lang('Password must be at least 8 characters.'),
            'password.regex'     => json_lang('Password must contain uppercase, lowercase, number, and symbol.'),
        ]);

        // Ambil user dari session sementara (bukan Auth)
        $userId = session('first_login_user_id');
        $user = User::find($userId);

        if (!$user) {
            return redirect()->route('login')->with('error', json_lang('Session expired, please login again.'));
        }

        // Update password dan flag first login
        $user->password = Hash::make($request->password);
        $user->is_first_login = 0;
        $user->password_changed_at = now();
        $user->save();

        // Hapus session sementara
        session()->forget('first_login_user_id');

        // Login otomatis setelah ganti password
        Auth::login($user);

        return redirect()->route('login')->with('success', json_lang('Password updated successfully.'));
    }

    
    // public function login(Request $request)
    // {
    //     // Sanitasi input
    //     $request->merge([
    //         'emp_id'   => trim(preg_replace('/\s+/', '', $request->input('emp_id'))),
    //         'password' => trim($request->input('password')),
    //     ]);

    //     $request->validate([
    //         'emp_id'   => 'required',
    //         'password' => 'required',
    //         'g-recaptcha-response' => 'required'
    //     ]);
        
    //     $captcha = $request->input('g-recaptcha-response');

    //     $response = Http::asForm()->post(
    //         'https://www.google.com/recaptcha/api/siteverify',
    //         [
    //             'secret'   => env('RECAPTCHA_SECRET_KEY'),
    //             'response' => $captcha,
    //             'remoteip' => $request->ip()
    //         ]
    //     );

    //     $result = $response->json();
        
    //     // Recaptcha (Urgent Fix)
    //     // $captcha = $request->input('g-recaptcha-response');
        
    //     // try {
        
    //     //     $response = Http::timeout(5)
    //     //         ->asForm()
    //     //         ->post(
    //     //             'https://www.google.com/recaptcha/api/siteverify',
    //     //             [
    //     //                 'secret'   => env('RECAPTCHA_SECRET_KEY'),
    //     //                 'response' => $captcha,
    //     //                 'remoteip' => $request->ip()
    //     //             ]
    //     //         );
        
    //     //     $result = $response->json();
        
    //     // } catch (\Exception $e) {
        
    //     //     \Log::warning('Recaptcha verification failed: ' . $e->getMessage());
        
    //     //     // Anggap captcha valid jika Google tidak dapat dihubungi
    //     //     $result = [
    //     //         'success' => true
    //     //     ];
    //     // }

    //     // 🔥 SAFE CHECK
    //     if (!($result['success'] ?? false)) {
    //          return back()->with('error', json_lang('reCaptcha verification failed.'))->withInput();
    //     }
        
    //     // Cari user
    //     $users = User::where('emp_id', $request->emp_id)->get();

    //     // -------------------------------------------------
    //     // ❌ USER NOT FOUND
    //     // -------------------------------------------------
    //     if ($users->isEmpty()) {

    //         $this->logLoginSession([
    //             'emp_id'    => $request->emp_id,
    //             'is_success'=> false
    //         ]);

    //         return back()->with('error', json_lang('Username not found.'))->withInput();
    //     }


    //     // Pilih user aktif
    //     $user = $users->where('is_deleted', 0)->first() ?? $users->first();

    //     // -------------------------------------------------
    //     // ❌ USER DELETED
    //     // -------------------------------------------------
    //     if ($user->is_deleted == 1) {

    //         $this->logLoginSession([
    //             'emp_id'    => $user->emp_id,
    //             'user_id'   => $user->user_id,
    //             'role_id'   => $user->role_id,
    //             'sub_role'  => $user->sub_role,
    //             'is_success'=> false
    //         ]);

    //         return back()->with('error', json_lang('Account has been deleted.'))->withInput();
    //     }

    //     // -------------------------------------------------
    //     // ❌ USER INACTIVE
    //     // -------------------------------------------------
    //     if ($user->is_active == 0) {

    //         $this->logLoginSession([
    //             'emp_id'    => $user->emp_id,
    //             'user_id'   => $user->user_id,
    //             'role_id'   => $user->role_id,
    //             'sub_role'  => $user->sub_role,
    //             'is_success'=> false
    //         ]);

    //         return back()->with('error', json_lang('Account is inactive.'))->withInput();
    //     }

    //     // -------------------------------------------------
    //     // ❌ WRONG PASSWORD
    //     // -------------------------------------------------
    //     if (!Hash::check($request->password, $user->password)) {

    //         $this->logLoginSession([
    //             'emp_id'    => $user->emp_id,
    //             'user_id'   => $user->user_id,
    //             'role_id'   => $user->role_id,
    //             'sub_role'  => $user->sub_role,
    //             'is_success'=> false
    //         ]);

    //         return back()->with('error',  json_lang('Invalid username or password.'))->withInput();
    //     }


    //     // Default Password Case
    //     $isDefaultPassword = strcasecmp($request->password, $user->emp_id) === 0;

    //     if ($isDefaultPassword && $user->is_first_login == 1) {
    //         session(['first_login_user_id' => $user->user_id]);
    //         return redirect()->route('password.change')
    //             ->with('warning', json_lang('You are using the default password. Please change it before continuing.'));
    //     }

    //     // -------------------------------------------------
    //     // ✅ LOGIN SUCCESS
    //     // -------------------------------------------------
    //     Auth::login($user);

    //     session([
    //         'user_id'    => $user->user_id,
    //         'emp_id'     => $user->emp_id,
    //         'full_name'  => $user->full_name,
    //         'role_id'    => $user->role_id,
    //         'sub_role'   => $user->sub_role,
    //         'login_time' => now(),
    //     ]);

    //     // ⏳ LOG SUCCESS
    //     $this->logLoginSession([
    //         'emp_id'    => $user->emp_id,
    //         'user_id'   => $user->user_id,
    //         'role_id'   => $user->role_id,
    //         'sub_role'  => $user->sub_role,
    //         'is_success'=> true
    //     ]);

    //     return redirect()->route('dashboard')
    //         ->with('success', json_lang('Login successful, welcome ') . $user->full_name . '!');
    // }

        public function login (Request $request) {
            // 1. sanitize input
            $empId = trim(preg_replace('/\s+/', '', $request->emp_id));
            $password = trim($request->password);
    
            $request->merge([
                'emp_id' => $empId,
                'password' => $password,
            ]);
    
            $request->validate([
                'emp_id'   => 'required',
                'password' => 'required',
                'g-recaptcha-response' => 'required'
            ]);
    
            $captcha = $request->input('g-recaptcha-response');
    
            $response = Http::asForm()->post(
                'https://www.google.com/recaptcha/api/siteverify',
                [
                    'secret'   => env('RECAPTCHA_SECRET_KEY'),
                    'response' => $captcha,
                    'remoteip' => $request->ip()
                ]
            );
    
            $result = $response->json();
    
            // Recaptcha (Urgent Fix)
            // $captcha = $request->input('g-recaptcha-response');
            
            // try {
            
            //     $response = Http::timeout(5)
            //         ->asForm()
            //         ->post(
            //             'https://www.google.com/recaptcha/api/siteverify',
            //             [
            //                 'secret'   => env('RECAPTCHA_SECRET_KEY'),
            //                 'response' => $captcha,
            //                 'remoteip' => $request->ip()
            //             ]
            //         );
            
            //     $result = $response->json();
            
            // } catch (\Exception $e) {
            
            //     \Log::warning('Recaptcha verification failed: ' . $e->getMessage());
            
            //     // Anggap captcha valid jika Google tidak dapat dihubungi
            //     $result = [
            //         'success' => true
            //     ];
            // }
    
            // Cari user
            $user = User::where('emp_id', $empId)->first();
            // User not found 
            if (!$user) {
                $this->logLoginSession([
                    'emp_id'    => $request->emp_id,
                    'is_success'=> false
                ]);
                return back()->with('error', json_lang('Username not found.'))->withInput();
            }
    
            // 🔥 SAFE CHECK
            if (!($result['success'] ?? false)) {
                if ($user) {
                    $user->increment('failed_login_count');
                }
                 return back()->with('error', json_lang('reCaptcha verification failed.'))->withInput();
            }
    
            // Backoff Exponential Login 
            $lastSuccess = LmsLoginSession::where('ip_address', $request->ip())
                ->where('is_success', true)
                ->latest()
                ->first();
    
            $failedCount = LmsLoginSession::where('ip_address', $request->ip())
                ->where('is_success', false)
                ->when($lastSuccess, fn($q) => $q->where('created_at', '>', $lastSuccess->created_at))
                ->count();
    
            if ($failedCount >= 10) {
    
                $delaySeconds = min(
                    $failedCount - 1,
                    10
                );
    
                sleep($delaySeconds);
                sleep(180);
            }
            
            // if ($failedCount >= 5) {
            
            //     $delaySeconds = min(
            //         ($failedCount - 4) * 5,
            //         300
            //     );
            
            //     sleep($delaySeconds);
            // }
    
            // Ip Based Block Case  Multiple Account 
            $failedByIp = LmsLoginSession::where('ip_address', $request->ip())
                ->where('is_success', false)
                ->where('created_at', '>=', now()->subMinutes(10))
                ->count();
    
            if ($failedByIp >= 20) {
                return back()->with('error', json_lang('Too Many Attempt'))->withInput();
            }
    
            // Delete user 
            if ($user->is_deleted) {
    
                $user->increment('failed_login_count');
                $this->logLoginSession([
                    'emp_id'    => $user->emp_id,
                    'user_id'   => $user->user_id,
                    'role_id'   => $user->role_id,
                    'sub_role'  => $user->sub_role,
                    'is_success'=> false
                ]);
                return back()->with('error', json_lang('Account has been deleted.'))->withInput();
            }
            // Inactive User
            if (!$user->is_active) {
    
                $user->increment('failed_login_count');
                $this->logLoginSession([
                    'emp_id' => $user->emp_id,
                    'user_id' => $user->user_id,
                    'role_id' => $user->role_id,
                    'sub_role' => $user->sub_role,
                    'is_success' => false
                ]);
    
                return back()->with('error', json_lang('Account is inactive.'))->withInput();
            }
            // Login Attempt
            if ($user->failed_login_count > 2) {
                $user->is_locked = 1;
                $user->save();
                $this->logLoginSession([
                    'emp_id' => $user->emp_id,
                    'user_id' => $user->user_id,
                    'role_id' => $user->role_id,
                    'sub_role' => $user->sub_role,
                    'is_success' => false
                ]);
                return back()->with('error', json_lang('Account is Locked'))->withInput();
            }
    
            // Account Locked 
            if ($user->is_locked == 1) {
                $this->logLoginSession([
                    'emp_id' => $user->emp_id,
                    'user_id' => $user->user_id,
                    'role_id' => $user->role_id,
                    'sub_role' => $user->sub_role,
                    'is_success' => false
                ]);
                return back()->with('error', json_lang('Account is Locked'))->withInput();
            }
            
            // Wrong Password
            if (!Hash::check($request->password, $user->password)) {
    
                $result = $user->increment('failed_login_count');
                $this->logLoginSession([
                    'emp_id'    => $user->emp_id,
                    'user_id'   => $user->user_id,
                    'role_id'   => $user->role_id,
                    'sub_role'  => $user->sub_role,
                    'is_success'=> false
                ]);
                return back()
                    ->with('error', json_lang('Invalid username or password.'))
                    ->withInput();
            }
    
            // Default Password Case/First Login
            if (
                strcasecmp($password, $user->emp_id) === 0 &&
                $user->is_first_login
            ) {
                session(['first_login_user_id' => $user->user_id]);
    
                return redirect()->route('password.change')
                    ->with('warning', json_lang('You are using the default password. Please change it before continuing.'));
            }
    
    
            // LOGIN SUCCESS
            Auth::login($user);
            $request->session()->regenerate();
            $user->failed_login_count = 0;
            $user->is_locked = 0;
            $user->save();
    
            // minimal session (no redundancy)
            session([
                'login_time' => now(),
            ]);
    
            $this->logLoginSession([
                'emp_id' => $user->emp_id,
                'user_id' => $user->user_id,
                'role_id' => $user->role_id,
                'sub_role' => $user->sub_role,
                'is_success' => true
            ]);
    
            return redirect()->route('dashboard')
                ->with('success', json_lang('Login successful, welcome ') . $user->full_name . '!');
        }

    /**
     * ============================
     *  ⏺️ REUSABLE LOGIN LOGGER
     * ============================
     */
    private function logLoginSession(array $data)
    {
        LmsLoginSession::create([
            'emp_id'      => $data['emp_id'] ?? null,
            'user_id'     => $data['user_id'] ?? null,
            'role_id'     => $data['role_id'] ?? null,
            'sub_role'    => $data['sub_role'] ?? null,
            'session'     => session()->getId(),
            'login_time'  => now(),
            'device_info' => request()->header('User-Agent'),
            'ip_address'  => request()->ip(),
            'is_success'  => $data['is_success'] ?? false,
        ]);
    }


    /**
     * Logout user
     */
    // public function logout(Request $request)
    // {
    //     // update logout_time pada session login terakhir
    //     LmsLoginSession::where('session', session()->getId())
    //         ->whereNull('logout_time')
    //         ->update(['logout_time' => now()]);

    //     Auth::logout();
    //     $request->session()->invalidate();
    //     $request->session()->regenerateToken();

    //     return redirect()->route('login')->with('success', 'Anda telah keluar.');
    // }

    public function logout(Request $request)
    {
        // 🔹 Update logout_time pada session login terakhir
        LmsLoginSession::where('session', session()->getId())
            ->whereNull('logout_time')
            ->update(['logout_time' => now()]);

        // 🔹 Logout Laravel
        Auth::logout();

        // 🔹 Hapus semua data session server-side
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        // 🔹 Hapus semua cookie yang tersimpan di browser
        $cookies = $request->cookies->keys();
        foreach ($cookies as $cookie) {
            \Cookie::queue(\Cookie::forget($cookie));
        }

        // 🔹 Redirect ke login page dengan pesan
        return redirect()->route('login')
            ->with('success', json_lang('You have been logged out and all sessions have been cleared.'));
    }

    /**
     * Reset password ke emp_id (oleh user sendiri)
     */
    public function resetByUser(Request $request)
    {
        $user = Auth::user();

        if (!$user) {
            return redirect()->route('login')->with('error', 'User tidak ditemukan');
        }

        if (empty($user->emp_id)) {
            return back()->with('error', json_lang('User does not have an employee ID. Reset cannot be performed.'));
        }

        $user->password = Hash::make($user->emp_id);
        $user->is_first_login = 1;
        $user->password_changed_at = now();
        $user->save();

        // Logout paksa
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login')
            ->with('warning', json_lang('Your password has been reset to your employee ID. Please log in again and change your password.'));
    }
}

