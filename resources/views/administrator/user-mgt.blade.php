@extends('layouts.master')

@section('title', 'Manajemen Pengguna')

@section('content')
@php
    $totalUsers = $users->count();
    $activeUsers = $users->where('is_active', 1)->count();
    $inactiveUsers = $totalUsers - $activeUsers;
    $authenticatedUser = auth()->user();
    $authenticatedSubRoles = is_array($authenticatedUser->sub_role)
        ? $authenticatedUser->sub_role
        : (json_decode($authenticatedUser->sub_role ?? '[]', true) ?: []);
    $isUserIT = $authenticatedUser->role_id == 1 || in_array(1, $authenticatedSubRoles);
@endphp

<main class="container user-management-page py-4 py-lg-5">
    <header class="user-management-header">
        <div>
            <p class="user-management-eyebrow mb-2">Administrasi Akun</p>
            <h1 class="user-management-title mb-2">Manajemen Pengguna</h1>
            <p class="user-management-description mb-0">Kelola identitas, peran, akses, dan status akun pengguna LMS.</p>
        </div>
        <button type="button" class="btn btn-secondary user-management-primary-action" data-bs-toggle="modal" data-bs-target="#addUserModal">
            Tambah Pengguna
        </button>
    </header>

    @if(session('success'))
        <div class="alert alert-success user-management-alert" role="status">{{ session('success') }}</div>
    @endif

    @if(session('error'))
        <div class="alert alert-danger user-management-alert" role="alert">{{ session('error') }}</div>
    @endif

    @if($errors->any())
        <div class="alert alert-danger user-management-alert" role="alert">
            <strong>Data pengguna belum dapat disimpan.</strong>
            <span>{{ $errors->first() }}</span>
        </div>
    @endif

    <section class="user-management-metrics" aria-label="Ringkasan pengguna">
        <article class="user-management-metric">
            <span>Total Pengguna</span>
            <strong id="userTotalMetric">{{ $totalUsers }}</strong>
            <small>Seluruh akun terdaftar</small>
        </article>
        <article class="user-management-metric is-active">
            <span>Pengguna Aktif</span>
            <strong id="userActiveMetric">{{ $activeUsers }}</strong>
            <small>Dapat mengakses LMS</small>
        </article>
        <article class="user-management-metric is-inactive">
            <span>Pengguna Nonaktif</span>
            <strong id="userInactiveMetric">{{ $inactiveUsers }}</strong>
            <small>Akses sedang dinonaktifkan</small>
        </article>
    </section>

    <section class="user-directory" aria-labelledby="user-directory-title">
        <div class="user-directory-heading">
            <div>
                <h2 id="user-directory-title" class="mb-1">Direktori Pengguna</h2>
                <p class="mb-0">Cari dan kelola akun berdasarkan identitas, unit kerja, peran, atau status.</p>
            </div>
            <span id="userResultsCount" class="user-directory-count">{{ $totalUsers }} pengguna</span>
        </div>

        <div class="user-directory-toolbar">
            <div class="user-search-field">
                <label for="searchUser" class="form-label">Cari pengguna</label>
                <div class="user-search-control">
                    <input type="search" id="searchUser" class="form-control" placeholder="Nama, ID, jabatan, departemen, atau peran" autocomplete="off">
                    <button type="button" class="btn btn-light user-search-clear d-none" id="clearUserSearch" aria-label="Hapus pencarian" title="Hapus pencarian">&times;</button>
                </div>
            </div>
            <div class="user-filter-field">
                <label for="userStatusFilter" class="form-label">Status akun</label>
                <select id="userStatusFilter" class="form-select">
                    <option value="all">Semua status</option>
                    <option value="active">Aktif</option>
                    <option value="inactive">Tidak aktif</option>
                </select>
            </div>
            <div class="user-filter-field">
                <label for="userPageSize" class="form-label">Baris per halaman</label>
                <select id="userPageSize" class="form-select">
                    <option value="10">10</option>
                    <option value="25">25</option>
                    <option value="50">50</option>
                </select>
            </div>
        </div>

        <div class="user-table-shell">
            <div class="table-responsive user-table-scroll">
                <table id="userTable" class="table user-table align-middle mb-0">
                    <thead>
                        <tr>
                            <th scope="col" class="user-table-number">No.</th>
                            <th scope="col">ID Karyawan</th>
                            <th scope="col">Nama Pengguna</th>
                            <th scope="col">Jabatan</th>
                            <th scope="col">Departemen</th>
                            <th scope="col">Peran</th>
                            <th scope="col">Status</th>
                            <th scope="col">Aksi</th>
                            <th scope="col">Proses Terakhir</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($users as $index => $user)
                            @php
                                $mainRoleName = $user->mainRole->role_name ?? '';
                                $subRoleNames = $user->sub_role_names ?? [];
                                if (!is_array($subRoleNames)) {
                                    $subRoleNames = array_map('trim', explode(',', $subRoleNames));
                                }
                                $roleNames = array_filter(array_unique(array_merge([$mainRoleName], $subRoleNames)), fn ($role) => $role && $role !== '-');
                            @endphp
                            <tr data-user-row data-status="{{ $user->is_active ? 'active' : 'inactive' }}">
                                <td data-label="Nomor" class="user-row-number">{{ $index + 1 }}</td>
                                <td data-label="ID Karyawan"><span class="user-employee-id">{{ $user->emp_id }}</span></td>
                                <td data-label="Nama Pengguna"><strong class="user-name">{{ $user->full_name }}</strong></td>
                                <td data-label="Jabatan">{{ $user->position->position_name ?? '-' }}</td>
                                <td data-label="Departemen">{{ $user->division->division_name ?? '-' }}</td>
                                <td data-label="Peran">
                                    <div class="user-role-list">
                                        @forelse($roleNames as $roleName)
                                            <span class="user-role-badge">{{ $roleName }}</span>
                                        @empty
                                            <span class="text-muted">-</span>
                                        @endforelse
                                    </div>
                                </td>
                                <td data-label="Status">
                                    <span class="user-status-badge {{ $user->is_active ? 'is-active' : 'is-inactive' }}">
                                        <span aria-hidden="true"></span>
                                        {{ $user->is_active ? 'Aktif' : 'Tidak Aktif' }}
                                    </span>
                                </td>
                                <td data-label="Aksi">
                                    <div class="user-row-actions" role="group" aria-label="Aksi untuk {{ $user->full_name }}">
                                        <button type="button"
                                                class="btn btn-sm btn-outline-secondary"
                                                data-bs-toggle="modal"
                                                data-bs-target="#EditUserModal"
                                                data-id="{{ $user->user_id }}"
                                                data-full_name="{{ $user->full_name }}"
                                                data-emp_id="{{ $user->emp_id }}"
                                                data-position_id="{{ $user->position_id }}"
                                                data-departement_cat="{{ $user->departement_cat }}"
                                                data-role_id="{{ $user->role_id }}"
                                                data-photo="{{ $user->photo_profile ? asset($user->photo_profile) : asset('assets/img/defaultPic.jpeg') }}">
                                            Ubah
                                        </button>
                                        <button type="button"
                                                class="btn btn-sm btn-outline-primary"
                                                data-bs-toggle="modal"
                                                data-bs-target="#userPrivilages"
                                                data-id="{{ $user->user_id }}"
                                                data-sub_role='@json($user->sub_role ?? [])'>
                                            Hak Akses
                                        </button>
                                        <button type="button"
                                                class="btn btn-sm btn-outline-danger"
                                                data-bs-toggle="modal"
                                                data-bs-target="#DeleteUser"
                                                data-id="{{ $user->user_id }}"
                                                data-name="{{ $user->full_name }}"
                                                data-active="{{ $user->is_active ? 1 : 0 }}">
                                            Operasi
                                        </button>
                                    </div>
                                </td>
                                <td data-label="Proses Terakhir">
                                    <div class="user-last-process">
                                        <span>{{ $user->person_process ?: 'Sistem' }}</span>
                                        <time datetime="{{ optional($user->updated_at)->toIso8601String() }}">{{ optional($user->updated_at)->format('d M Y, H:i') ?: '-' }}</time>
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                        <tr id="userEmptyStateRow" class="{{ $totalUsers > 0 ? 'd-none' : '' }}">
                            <td colspan="9">
                                <div class="user-directory-empty">
                                    <strong>Belum ada data pengguna</strong>
                                    <span>Pengguna baru akan ditampilkan di direktori ini.</span>
                                </div>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
            <footer class="user-directory-footer">
                <div id="paginationInfo" class="user-pagination-info" aria-live="polite"></div>
                <nav id="paginationControls" class="user-pagination-controls" aria-label="Navigasi halaman pengguna"></nav>
            </footer>
        </div>
    </section>
</main>

<div class="modal fade" id="addUserModal" tabindex="-1" aria-labelledby="addUserModalLabel" aria-hidden="true" data-bs-backdrop="static" data-bs-keyboard="false">
    <div class="modal-dialog modal-lg modal-dialog-scrollable">
        <div class="modal-content user-management-modal">
            <form action="{{ route('user.store') }}" method="POST" enctype="multipart/form-data" class="needs-validation" novalidate>
                @csrf
                <div class="modal-header user-management-modal-header">
                    <div>
                        <p class="user-management-eyebrow mb-1">Akun Baru</p>
                        <h2 class="modal-title" id="addUserModalLabel">Tambah Pengguna</h2>
                    </div>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Tutup"></button>
                </div>
                <div class="modal-body user-management-modal-body">
                    <p class="user-management-form-intro">Lengkapi identitas utama dan penempatan pengguna. Hak akses tambahan dapat diatur setelah akun dibuat.</p>
                    <div class="user-management-form-grid">
                        <div class="user-form-field is-full-width">
                            <label for="add_photo_profile" class="form-label">Foto Profil <span class="text-muted fw-normal">(opsional)</span></label>
                            <input type="file" class="form-control" id="add_photo_profile" name="photo_profile" accept="image/jpeg,image/png">
                            <div class="form-text">Format JPG atau PNG, maksimal 10 MB.</div>
                        </div>
                        <div class="user-form-field">
                            <label for="add_emp_id" class="form-label">ID Karyawan <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" id="add_emp_id" name="emp_id" value="{{ old('emp_id') }}" placeholder="Masukkan ID karyawan" required>
                            <div class="invalid-feedback">ID karyawan wajib diisi.</div>
                        </div>
                        <div class="user-form-field">
                            <label for="add_full_name" class="form-label">Nama Lengkap <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" id="add_full_name" name="full_name" value="{{ old('full_name') }}" placeholder="Masukkan nama lengkap" required>
                            <div class="invalid-feedback">Nama lengkap wajib diisi.</div>
                        </div>
                        <div class="user-form-field">
                            <label for="add_position_id" class="form-label">Jabatan <span class="text-danger">*</span></label>
                            <select class="form-select" id="add_position_id" name="position_id" required>
                                <option value="" disabled @selected(!old('position_id'))>Pilih jabatan</option>
                                @foreach($position as $positions)
                                    <option value="{{ $positions->position_id }}" @selected(old('position_id') == $positions->position_id)>{{ $positions->position_name }}</option>
                                @endforeach
                            </select>
                            <div class="invalid-feedback">Jabatan wajib dipilih.</div>
                        </div>
                        <div class="user-form-field">
                            <label for="add_departement_cat" class="form-label">Departemen <span class="text-danger">*</span></label>
                            <select class="form-select" id="add_departement_cat" name="departement_cat" required>
                                <option value="" disabled @selected(!old('departement_cat'))>Pilih departemen</option>
                                @foreach($division as $divisions)
                                    <option value="{{ $divisions->division_id }}" @selected(old('departement_cat') == $divisions->division_id)>{{ $divisions->division_name }}</option>
                                @endforeach
                            </select>
                            <div class="invalid-feedback">Departemen wajib dipilih.</div>
                        </div>
                        <div class="user-form-field is-full-width">
                            <label for="add_role_id" class="form-label">Peran Utama <span class="text-danger">*</span></label>
                            <select class="form-select" id="add_role_id" name="role_id" required>
                                <option value="" disabled @selected(!old('role_id'))>Pilih peran utama</option>
                                @foreach($mainRole as $mainRoles)
                                    @if($isUserIT || in_array($mainRoles->role_id, [2, 3, 4]))
                                        <option value="{{ $mainRoles->role_id }}" @selected(old('role_id') == $mainRoles->role_id)>{{ $mainRoles->role_name }}</option>
                                    @endif
                                @endforeach
                            </select>
                            <div class="invalid-feedback">Peran utama wajib dipilih.</div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer user-management-modal-footer">
                    <button type="button" class="btn btn-light" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-primary">Tambah Pengguna</button>
                </div>
            </form>
        </div>
    </div>
</div>

<div class="modal fade" id="EditUserModal" tabindex="-1" aria-labelledby="editUserModalLabel" aria-hidden="true" data-bs-backdrop="static" data-bs-keyboard="false">
    <div class="modal-dialog modal-lg modal-dialog-scrollable">
        <div class="modal-content user-management-modal">
            <form id="editUserForm" method="POST" enctype="multipart/form-data" class="needs-validation" novalidate>
                @csrf
                @method('PUT')
                <div class="modal-header user-management-modal-header">
                    <div>
                        <p class="user-management-eyebrow mb-1">Pengaturan Akun</p>
                        <h2 class="modal-title" id="editUserModalLabel">Ubah Pengguna</h2>
                    </div>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Tutup"></button>
                </div>
                <div class="modal-body user-management-modal-body">
                    <div class="user-edit-photo">
                        <img id="photoPreview" src="{{ asset('assets/img/defaultPic.jpeg') }}" alt="Pratinjau foto pengguna" onerror="this.src='{{ asset('assets/img/defaultPic.jpeg') }}'">
                        <div>
                            <label for="photo_profile" class="form-label">Foto Profil</label>
                            <input type="file" class="form-control @error('photo_profile') is-invalid @enderror" id="photo_profile" name="photo_profile" accept="image/jpeg,image/png">
                            <div class="form-text">Format JPG atau PNG, maksimal 10 MB.</div>
                            @error('photo_profile')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                    </div>
                    <div class="user-management-form-grid">
                        <div class="user-form-field">
                            <label for="emp_id" class="form-label">ID Karyawan</label>
                            <input type="text" class="form-control" id="emp_id" disabled>
                            <input type="hidden" name="emp_id" id="emp_id_hidden">
                        </div>
                        <div class="user-form-field">
                            <label for="full_name" class="form-label">Nama Lengkap <span class="text-danger">*</span></label>
                            <input type="text" class="form-control @error('full_name') is-invalid @enderror" id="full_name" name="full_name" placeholder="Masukkan nama lengkap" required>
                            <div class="invalid-feedback">Nama lengkap wajib diisi.</div>
                        </div>
                        <div class="user-form-field">
                            <label for="position_id" class="form-label">Jabatan <span class="text-danger">*</span></label>
                            <select class="form-select" id="position_id" name="position_id" required>
                                <option value="" disabled>Pilih jabatan</option>
                                @foreach($position as $positions)
                                    <option value="{{ $positions->position_id }}">{{ $positions->position_name }}</option>
                                @endforeach
                            </select>
                            <div class="invalid-feedback">Jabatan wajib dipilih.</div>
                        </div>
                        <div class="user-form-field">
                            <label for="departement_cat" class="form-label">Departemen <span class="text-danger">*</span></label>
                            <select class="form-select" id="departement_cat" name="departement_cat" required>
                                <option value="" disabled>Pilih departemen</option>
                                @foreach($division as $divisions)
                                    <option value="{{ $divisions->division_id }}">{{ $divisions->division_name }}</option>
                                @endforeach
                            </select>
                            <div class="invalid-feedback">Departemen wajib dipilih.</div>
                        </div>
                        <div class="user-form-field is-full-width">
                            <label for="role_id" class="form-label">Peran Utama <span class="text-danger">*</span></label>
                            <select class="form-select" id="role_id" name="role_id" required>
                                <option value="" disabled>Pilih peran utama</option>
                                @foreach($mainRole as $mainRoles)
                                    @if($isUserIT || in_array($mainRoles->role_id, [2, 3, 4]))
                                        <option value="{{ $mainRoles->role_id }}">{{ $mainRoles->role_name }}</option>
                                    @endif
                                @endforeach
                            </select>
                            <div class="invalid-feedback">Peran utama wajib dipilih.</div>
                        </div>
                    </div>
                    <div class="user-account-tools">
                        <div>
                            <h3 class="mb-1">Pemulihan Akun</h3>
                            <p class="mb-0">Gunakan hanya saat pengguna mengalami kendala akses atau kata sandi.</p>
                        </div>
                        <div class="user-account-tool-actions">
                            <button type="submit" name="action" value="resetError" class="btn btn-outline-secondary">Reset Error</button>
                            <button type="submit" name="action" value="reset" class="btn btn-outline-danger">Reset Password</button>
                        </div>
                    </div>
                </div>
                <div class="modal-footer user-management-modal-footer">
                    <button type="button" class="btn btn-light" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" name="action" value="update" class="btn btn-primary">Simpan Perubahan</button>
                </div>
            </form>
        </div>
    </div>
</div>

<div class="modal fade" id="userPrivilages" tabindex="-1" aria-labelledby="assignModalLabel" aria-hidden="true" data-bs-backdrop="static" data-bs-keyboard="false">
    <div class="modal-dialog modal-lg modal-dialog-scrollable">
        <div class="modal-content user-management-modal">
            <form id="privForm" method="POST" action="#">
                @csrf
                @method('PUT')
                <div class="modal-header user-management-modal-header">
                    <div>
                        <p class="user-management-eyebrow mb-1">Otorisasi</p>
                        <h2 class="modal-title" id="assignModalLabel">Atur Hak Akses</h2>
                    </div>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Tutup"></button>
                </div>
                <div class="modal-body user-management-modal-body">
                    <p class="user-management-form-intro">Pilih peran tambahan yang diperlukan pengguna. Peran utama tetap tercantum dan tidak dapat dihapus di sini.</p>
                    <div class="user-privilege-editor">
                        <div class="user-privilege-list">
                            <label for="availablePrivileges" class="form-label">Hak Tersedia</label>
                            <select id="availablePrivileges" class="form-select available-privileges" multiple size="10">
                                @foreach($mainRole as $mainRoles)
                                    @if($isUserIT || in_array($mainRoles->role_id, [2, 3, 4]))
                                        <option value="{{ $mainRoles->role_id }}">{{ $mainRoles->role_name }}</option>
                                    @endif
                                @endforeach
                            </select>
                        </div>
                        <div class="user-privilege-actions">
                            <button type="button" class="btn btn-outline-secondary move-right" aria-label="Tambahkan hak akses" title="Tambahkan hak akses"><span aria-hidden="true">&rarr;</span><span>Tambahkan</span></button>
                            <button type="button" class="btn btn-outline-secondary move-left" aria-label="Hapus hak akses" title="Hapus hak akses"><span aria-hidden="true">&larr;</span><span>Hapus</span></button>
                        </div>
                        <div class="user-privilege-list">
                            <label for="currentPrivileges" class="form-label">Hak Saat Ini</label>
                            <select id="currentPrivileges" class="form-select current-privileges" multiple size="10"></select>
                        </div>
                    </div>
                </div>
                <div class="modal-footer user-management-modal-footer">
                    <button type="button" class="btn btn-light" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-primary apply-privileges">Terapkan Hak</button>
                </div>
                <div id="subRoleHiddenContainer"></div>
            </form>
        </div>
    </div>
</div>

<div class="modal fade" id="DeleteUser" tabindex="-1" aria-labelledby="userOperationModalLabel" aria-hidden="true" data-bs-backdrop="static" data-bs-keyboard="false">
    <div class="modal-dialog">
        <div class="modal-content user-management-modal">
            <div class="modal-header user-management-modal-header">
                <div>
                    <p class="user-management-eyebrow mb-1">Tindakan Administratif</p>
                    <h2 class="modal-title" id="userOperationModalLabel">Operasi Pengguna</h2>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Tutup"></button>
            </div>
            <div class="modal-body user-management-modal-body">
                <p class="user-operation-message mb-3">Pilih tindakan yang akan diterapkan pada pengguna ini.</p>
                <label for="confirmAction" class="form-label">Tindakan</label>
                <select class="form-select" id="confirmAction">
                    <option value="" selected disabled>Pilih tindakan</option>
                    <option value="activate">Aktifkan kembali pengguna</option>
                    <option value="deactivate">Nonaktifkan pengguna</option>
                    <option value="delete">Hapus pengguna</option>
                </select>
                <p class="user-operation-note mb-0">Penghapusan akan mengeluarkan pengguna dari direktori aktif. Pastikan tindakan sudah sesuai.</p>
            </div>
            <div class="modal-footer user-management-modal-footer user-operation-actions">
                <button type="button" class="btn btn-light" data-bs-dismiss="modal">Batal</button>
                <button type="button" class="btn btn-warning" id="nonactiveBtn" disabled>Nonaktifkan</button>
                <button type="button" class="btn btn-danger" id="deleteUserBtn" disabled>Hapus</button>
            </div>
        </div>
    </div>
</div>
@endsection
