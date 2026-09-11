<?php

namespace App\Http\Controllers\LMS;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\User;
use App\Models\Position;
use App\Models\Division;
use App\Models\LmsRole;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\Validator;

class UserController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $users = User::where('is_deleted', 0)
            ->orderBy('created_at', 'desc')
            // ->paginate(10);
            ->get();

        $position = Position::all();
        $division = Division::all();
        $mainRole = LmsRole::all();

        // Transformasi data sub_role → nama role
        // $users->setCollection(
        //     $users->getCollection()->map(function ($user) {
        //         if (!empty($user->sub_role)) {
        //             $ids = is_array($user->sub_role) ? $user->sub_role : json_decode($user->sub_role, true);
        //             $names = LmsRole::whereIn('role_id', $ids)->pluck('role_name')->toArray();
        //             $user->sub_role_names = implode(', ', $names);
        //         } else {
        //             $user->sub_role_names = '-';
        //         }
        //         return $user;
        //     })
        // );

        $users = $users->map(function ($user) {
            if (!empty($user->sub_role)) {
                $ids = is_array($user->sub_role) ? $user->sub_role : json_decode($user->sub_role, true);
                $names = LmsRole::whereIn('role_id', $ids)->pluck('role_name')->toArray();
                $user->sub_role_names = implode(', ', $names);
            } else {
                $user->sub_role_names = '-';
            }
            return $user;
        });


        return view('administrator.user-mgt', compact('users', 'position', 'division', 'mainRole'));
    }

    /**
     * Store a newly created user.
     */
   public function store(Request $request)
    {
        // =============================
        // 🧩 VALIDASI MANUAL
        // =============================
        $validator = Validator::make($request->all(), [
            'full_name'       => 'required|string|max:255',

            'emp_id'          => [
                'required',
                'string',
                'max:50',
                Rule::unique('users', 'emp_id')
                    ->where(function($q) {
                        return $q->where('is_deleted', 0)
                                ->where('is_active', 1); // user aktif → tidak boleh duplikat
                    }),
            ],

            'position_id'     => 'required',
            'departement_cat' => 'required',
            'role_id'         => 'required|integer|in:1,2,3,4',
            'photo_profile'   => 'nullable|image|max:2048',
        ]);

        // =============================
        // ❌ Jika VALIDASI GAGAL
        // =============================
        if ($validator->fails()) {

            // Jika emp_id yang gagal → custom error message
            if ($validator->errors()->has('emp_id')) {
                return back()
                    ->with('error', "Employee ID duplikat dan tidak boleh digunakan kembali.")
                    ->withInput();
            }

            // Error lain tetap ke Laravel
            return back()
                ->withErrors($validator)
                ->withInput();
        }

        // =============================
        // Ambil data valid
        // =============================
        $validated = $validator->validated();

        // =============================
        // 📸 Upload foto profil
        // =============================
        if ($request->hasFile('photo_profile')) {
            $file = $request->file('photo_profile');
            $filename = Str::slug($validated['emp_id']) . '.' . $file->getClientOriginalExtension();

            $destination = public_path('assets/img');
            if (!file_exists($destination)) {
                mkdir($destination, 0755, true);
            }

            $file->move($destination, $filename);
            $validated['photo_profile'] = 'assets/img/' . $filename;
        } else {
            $validated['photo_profile'] = 'assets/img/defaultPic.jpeg';
        }

        // =============================
        // ⚙️ Default values
        // =============================
        $validated['password']        = Hash::make($validated['emp_id']);
        $validated['person_process']  = auth()->user()->emp_id ?? 'system';
        $validated['is_active']       = 1;
        $validated['is_deleted']      = 0;
        $validated['is_first_login']  = 1;
        $validated['password_changed_at'] = now();

        // =============================
        // 🧠 Handle SUB ROLE
        // =============================
        $subRoles = (array) $request->input('sub_role', []);

        // Convert string → int
        $subRoles = array_map('intval', $subRoles);

        // Pastikan learner (4) selalu ada
        if (!in_array(4, $subRoles)) {
            $subRoles[] = 4;
        }

        // Unik & terurut
        $validated['sub_role'] = array_values(array_unique($subRoles));

        // =============================
        // 💾 SIMPAN USER
        // =============================
        User::create($validated);

        return back()->with('success', 'Pengguna baru berhasil ditambahkan dengan password awal sesuai emp_id.');
    }
    
    /**
     * Update user data or reset password.
     */
   public function update(Request $request, string $id)
    {
        $user = User::findOrFail($id);
        $authUser = auth()->user();

        // 🔹 Reset password
        if ($request->action === 'reset') {
            $defaultPassword = $user->emp_id; // password default = emp_id user

            $user->update([
                'password'            => bcrypt($defaultPassword),
                'is_first_login'      => 1,
                'password_changed_at' => now(),
            ]);

            return redirect()->back()->with('success', "Password berhasil direset ke default (Emp ID: {$defaultPassword}).");
        }
        
        if ($request->action === 'resetError') {
            $user->update([
                'failed_login_count'  => 0,
                'is_locked'           => 0,
            ]);
            return redirect()->back()->with(
                'success',
                json_lang(
                    'Error Count has been reset.'
            ));
        }
        
        // 🔹 Update data pengguna
        $validated = $request->validate([
            'full_name'       => 'required|string|max:255',
            'departement_cat' => 'required|string|max:50',
            'role_id'         => 'required|integer|in:1,2,3,4',
            'position_id'     => 'required',
            'photo_profile'   => 'nullable|image|max:2048',
        ]);

        if (in_array($authUser->role_id, [2, 3, 4])) {

            // Konversi sub_role ke array jika disimpan dalam JSON string
            $targetSubRoles = is_string($user->sub_role)
                ? json_decode($user->sub_role, true)
                : ($user->sub_role ?? []);

            // Deteksi apakah target punya peran IT
            $targetIsIT = $user->role_id == 1 || in_array(1, $targetSubRoles);

            if ($targetIsIT) {
                return redirect()->back()->with('error', 'Anda tidak memiliki izin untuk mengubah data pengguna IT.');
            }
        }

        // Upload foto baru jika ada
        if ($request->hasFile('photo_profile')) {
            $file = $request->file('photo_profile');
            $filename = Str::slug($user->emp_id) . '.' . $file->getClientOriginalExtension();
            $destination = public_path('assets/img');
            if (!file_exists($destination)) mkdir($destination, 0755, true);
            $file->move($destination, $filename);
            $validated['photo_profile'] = 'assets/img/' . $filename;
        }

        // Catat siapa yang mengubah
        $validated['person_process'] = auth()->user()->emp_id ?? 'system';

        $user->update($validated);

        return redirect()->back()->with('success', 'Data pengguna berhasil diperbarui.');
    }

    /**
     * Update sub roles.
     */
    public function updateRoles(Request $request, $id)
    {
        $validated = $request->validate([
            'sub_role'   => 'nullable|array',
            'sub_role.*' => 'integer|in:1,2,3,4',
        ]);

        $subRoles = array_map('intval', (array) $request->input('sub_role', []));
        if (!in_array(4, $subRoles)) $subRoles[] = 4; // tetap tambahkan learner

        $subRoles = array_values(array_unique($subRoles));
        sort($subRoles);

        $user = User::findOrFail($id);
        $user->sub_role = $subRoles;
        $user->save();

        return response()->json([
            'ok'       => true,
            'message'  => 'Hak Pengguna berhasil diubah',
            'sub_role' => $user->sub_role,
        ]);
    }

    /**
     * Soft-delete the user.
     */
    public function destroy(string $id)
    {
        $user = User::findOrFail($id);

        if ($user->is_deleted) {
            return back()->with('info', 'Pengguna sudah dihapus sebelumnya.');
        }

        $user->update([
            'is_deleted'     => 1,
            'is_active'      => 0,
            'person_process' => auth()->user()->emp_id ?? 'system',
        ]);

        return back()->with('success', 'Pengguna berhasil dihapus (soft delete & dinonaktifkan).');
    }

    /**
     * Toggle user active status.
     */
    public function changeStatus(string $id)
    {
        $user = User::findOrFail($id);
        if ($user->is_deleted) {
            return back()->with('error', 'Tidak bisa mengubah status: pengguna sudah dihapus.');
        }

        $user->is_active = !$user->is_active;
        $user->person_process = auth()->user()->emp_id ?? 'system';
        $user->save();

        return back()->with('success', 'Status pengguna diubah menjadi ' . ($user->is_active ? 'Aktif' : 'Tidak Aktif') . '.');
    }
}
