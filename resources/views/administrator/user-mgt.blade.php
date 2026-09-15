@extends('layouts.master')

@section('title', 'Manajemen Pengguna')

@section('content')

<div class="container mb-4">
    <div class="d-flex flex-column mb-4 border-bottom pb-3 align-items-center text-center">
        <h2 class="fw-bold text-dark mb-1">Manajemen Pengguna </h2>
        <small class="text-muted">Kelola informasi detail dan pengaturan akun pengguna.</small>
    </div>
    <div class="mb-3 d-flex gap-2 bg-white rouded p-2">
        <button class="btn btn-secondary" data-bs-toggle="modal" data-bs-target="#addUserModal">Tambah Pengguna</button>
        <input type="text" id="searchUser" class="form-control w-25" placeholder="Cari pengguna...">
    </div>

    <div class="card shadow-sm">
        <div class="card-body p-0">
            <div style="overflow-y:auto;">
                <table id="userTable" class="table table-striped table-bordered align-middle">
                    <thead class="table-warning">
                        <tr>
                            <th class="freeze-col col-nomor">Nomor</th>
                            <th class="freeze-col col-id"><span style="white-space:nowrap">ID Karyawan</span></th>
                            <th class="freeze-col col-nama"><span style="white-space:nowrap">Nama Pengguna</span></th>
                            <th class="freeze-col col-jabatan">Jabatan</th>
                            <th class="freeze-col col-divisi">Departemen</th>
                            <th class="px-4"><span style="white-space:nowrap">Peran </span></th>
                            <th>Aktivasi</th>
                            <th>Aksi</th>
                            <th><span style="white-space:nowrap">Proses Terakhir</span></th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($users as $index => $user)
                        <tr>
                            <td class="freeze-col col-nomor">{{ $index + 1 }}</td>
                            <td class="freeze-col col-id">{{ $user->emp_id }}</td>
                            <td class="freeze-col col-nama user-name">{{ $user->full_name }}</td>
                            <td class="freeze-col col-jabatan text-nowrap">{{ $user->position->position_name ?? '-' }}</td>
                            <td class="freeze-col col-divisi text-nowrap">{{ $user->division->division_name ?? '-' }}</td>
                            @php
                                // Ambil role utama
                                $main = $user->mainRole->role_name ?? '';

                                // Ambil sub-role (bisa string dipisah koma, atau array)
                                $subs = $user->sub_role_names ?? [];

                                // Pastikan sub-role jadi array
                                if (!is_array($subs)) {
                                    $subs = array_map('trim', explode(',', $subs));
                                }

                                // Gabungkan semuanya
                                $roles = array_filter(array_unique(array_merge([$main], $subs)));

                                // Jadi string "Learner, Instructor" dst
                                $roleDisplay = implode(', ', $roles);
                            @endphp

                            <td>{{ $roleDisplay }}</td>
                            <td>
                                <span class="badge {{ $user->is_active ? 'bg-success' : 'bg-danger' }}">
                                    {{ $user->is_active ? 'Aktif' : 'Tidak Aktif' }}
                                </span>
                            </td>
                            <td style="white-space:nowrap">
                                <div class="btn-group" role="group" aria-label="Aksi">
                                    <!-- Ubah -->
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
                                            data-photo="{{ asset($user->photo_profile) }}">
                                        ✏️ Ubah
                                    </button>

                                    <!-- Atur Hak -->
                                    <button type="button"
                                            class="btn btn-sm btn-outline-primary"
                                            data-bs-toggle="modal"
                                            data-bs-target="#userPrivilages"
                                            data-id="{{ $user->user_id }}"
                                            data-sub_role='@json($user->sub_role ?? [])'>
                                        ⚙️ Atur Hak
                                    </button>

                                    <!-- Operasi -->
                                    <button class="btn btn-sm btn-outline-danger"
                                            data-bs-toggle="modal" 
                                            data-bs-target="#DeleteUser"
                                            data-id="{{ $user->user_id }}"
                                            data-name="{{ $user->full_name }}"
                                            data-active="{{ $user->is_active ? 1 : 0 }}">
                                        🗂 Operasi
                                    </button>
                                </div>
                            </td>
                            <td>
                                <p class="text-muted fst-italic">
                                    {{ $user->person_process }} {{ $user->updated_at }}
                                </p>
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="10" class="text-center text-muted">Belum ada data pengguna.</td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    <div class="d-flex justify-content-between align-items-center mt-3 flex-wrap gap-2">
        <div id="paginationInfo" class="text-muted small mx-3"></div>
        <nav>
            <ul class="pagination pagination-sm mb-3" id="paginationControls"></ul>
        </nav>
    </div>
</div>


<!-- Modal Add User -->
<div class="modal modal-lg fade" id="addUserModal" tabindex="-1" aria-labelledby="exampleModalLabel" aria-hidden="true"
     data-bs-backdrop="static" data-bs-keyboard="false">
  <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="exampleModalLabel">Formulir Pengguna Baru</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body">
        <form action="{{ route('user.store') }}" method="POST" enctype="multipart/form-data" class="needs-validation" novalidate>
          @csrf

          <!-- Upload Photo Profile (opsional) -->
          <div class="mb-3">
            <label for="photo_profile" class="form-label">Foto Profil</label>
            <input type="file" class="form-control" id="photo_profile" name="photo_profile" accept="image/*">
          </div>

        <!-- ID Karyawan -->
          <div class="mb-3">
            <label for="emp_id" class="form-label fw-semibold">ID Karyawan <span class="text-danger">*</span></label>
            <input type="text" class="form-control" id="emp_id" name="emp_id" placeholder="Masukkan ID karyawan" required>
            <div class="invalid-feedback">ID Karyawan wajib diisi.</div>
          </div>

          <!-- Nama Lengkap -->
          <div class="mb-3">
            <label for="full_name" class="form-label fw-semibold">Nama Lengkap <span class="text-danger">*</span></label>
            <input type="text" class="form-control" id="full_name" name="full_name" placeholder="Masukkan nama lengkap" required>
            <div class="invalid-feedback">Nama Lengkap wajib diisi.</div>
          </div>

          <!-- Jabatan -->
          <div class="mb-3">
            <label for="position_id" class="form-label fw-semibold">Jabatan <span class="text-danger">*</span></label>
            <select class="form-select" id="position_id" name="position_id" required>
              <option value="" disabled selected>Pilih Jabatan</option>
              @foreach($position as $positions)
                <option value="{{ $positions->position_id }}">{{ $positions->position_name }}</option>
              @endforeach
            </select>
            <div class="invalid-feedback">Jabatan wajib dipilih.</div>
          </div>

          <!-- Kategori Divisi -->
          <div class="mb-3">
            <label for="departement_cat" class="form-label fw-semibold">Kategori Departemen <span class="text-danger">*</span></label>
            <select class="form-select" id="departement_cat" name="departement_cat" required>
              <option value="" disabled selected>Pilih Divisi</option>
              @foreach($division as $divisions)
                <option value="{{ $divisions->division_id }}">{{ $divisions->division_name }}</option>
              @endforeach
            </select>
            <div class="invalid-feedback">Departemen wajib dipilih.</div>
          </div>

          <!-- Peran Utama -->
          <div class="mb-3">
            <label for="role_id" class="form-label fw-semibold">Peran Utama <span class="text-danger">*</span></label>
                @php
                    $user = auth()->user();
                    $isUserIT = $user->role_id == 1 || 
                        (is_array($user->sub_role) ? in_array(1, $user->sub_role) : in_array(1, json_decode($user->sub_role ?? '[]', true)));
                @endphp
            <select class="form-select" id="role_id" name="role_id" required>
              <option value="" disabled selected>Pilih Peran</option>
                    @foreach($mainRole as $mainRoles)
                        @if($isUserIT || in_array($mainRoles->role_id, [2,3,4]))
                            <option value="{{ $mainRoles->role_id }}">{{ $mainRoles->role_name }}</option>
                        @endif
                    @endforeach
            </select>
            <div class="invalid-feedback">Peran wajib dipilih.</div>
          </div>

          <!-- Tombol -->
          <div class="modal-footer">
            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batalkan</button>
            <button type="submit" class="btn btn-primary">Tambahkan Pengguna</button>
          </div>
        </form>
      </div>
    </div>
  </div>
</div>

<!-- Modal Ubah User -->
<div class="modal modal-lg fade" id="EditUserModal" tabindex="-1" aria-labelledby="exampleModalLabel" aria-hidden="true"
     data-bs-backdrop="static" data-bs-keyboard="false">
    <div class="modal-dialog">
        <div class="modal-content">
        <div class="modal-header">
            <h5 class="modal-title fw-bold" id="exampleModalLabel"> Perubahan Pengguna</h5>
            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
        </div>
        <div class="modal-body">
            <form id="editUserForm" 
                method="POST" 
                enctype="multipart/form-data"
                class="needs-validation" 
                novalidate>
            @csrf
            @method('PUT')

            <!-- Photo Preview -->
            <div class="text-center mb-3">
                <img id="photoPreview" src="" alt="Photo"
                    class="rounded-circle border shadow-sm"
                    style="width: 90px; height: 90px; object-fit: cover;">
            </div>

            <!-- Upload Photo Profile -->
            <div class="mb-3">
                <label for="photo_profile" class="form-label fw-semibold">Foto Profil</label>
                <input type="file" 
                    class="form-control @error('photo_profile') is-invalid @enderror" 
                    id="photo_profile" 
                    name="photo_profile" 
                    accept="image/*">
                @error('photo_profile')
                <div class="invalid-feedback">{{ $message }}</div>
                @enderror
            </div>
            
            <!-- ID Karyawan (Non-editable) -->
            <div class="mb-3">
                <label for="emp_id" class="form-label fw-semibold">ID Karyawan</label>
                <input type="text" class="form-control" id="emp_id" disabled>
                <input type="hidden" name="emp_id" id="emp_id_hidden">
            </div>
            <!-- Nama Lengkap -->
            <div class="mb-3">
                <label for="full_name" class="form-label fw-semibold">
                Nama Lengkap <span class="text-danger">*</span>
                </label>
                <input type="text" 
                    class="form-control @error('full_name') is-invalid @enderror" 
                    id="full_name" 
                    name="full_name" 
                    placeholder="Masukkan nama lengkap" 
                    required>
                @error('full_name')
                <div class="invalid-feedback">{{ $message }}</div>
                @else
                <div class="invalid-feedback">Nama Lengkap wajib diisi.</div>
                @enderror
            </div>

            <!-- Jabatan -->
            <div class="mb-3">
                <label for="position_id" class="form-label fw-semibold">
                Jabatan <span class="text-danger">*</span>
                </label>
                <select class="form-select" id="position_id" name="position_id" required>
                <option value="" disabled selected>Pilih Jabatan</option>
                @foreach($position as $positions)
                    <option value="{{ $positions->position_id }}">{{ $positions->position_name }}</option>
                @endforeach
                </select>
                <div class="invalid-feedback">Jabatan wajib dipilih.</div>
            </div>

            <!-- Kategori Divisi -->
            <div class="mb-3">
                <label for="departement_cat" class="form-label fw-semibold">
                Kategori Departemen <span class="text-danger">*</span>
                </label>
                <select class="form-select" id="departement_cat" name="departement_cat" required>
                <option value="" disabled selected>Pilih Departemen</option>
                @foreach($division as $divisions)
                    <option value="{{ $divisions->division_id }}">{{ $divisions->division_name }}</option>
                @endforeach
                </select>
                <div class="invalid-feedback">Departemen wajib dipilih.</div>
            </div>

            <!-- Peran Utama -->
            <div class="mb-3">
                <label for="role_id" class="form-label fw-semibold">
                Peran Utama <span class="text-danger">*</span>
                </label>
                @php
                    $user = auth()->user();
                    $isUserIT = $user->role_id == 1 || 
                        (is_array($user->sub_role) ? in_array(1, $user->sub_role) : in_array(1, json_decode($user->sub_role ?? '[]', true)));
                @endphp
                <select class="form-select" id="role_id" name="role_id" required>
                    <option value="" disabled selected>Pilih Peran</option>

                    @foreach($mainRole as $mainRoles)
                        @if($isUserIT || in_array($mainRoles->role_id, [2,3,4]))
                            <option value="{{ $mainRoles->role_id }}">{{ $mainRoles->role_name }}</option>
                        @endif
                    @endforeach
                </select>
                <div class="invalid-feedback">Peran wajib dipilih.</div>
            </div>

            <!-- Tombol -->
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batalkan</button>
                <button type="submit" name="action" value="resetError" class="btn btn-secondary">Reset Error</button>
                <button type="submit" name="action" value="reset" class="btn btn-warning">Reset Password</button>
                <button type="submit" name="action" value="update" class="btn btn-primary">Simpan Perubahan</button>
            </div>
            </form>
        </div>
        </div>
    </div>
</div>

<!-- Modal Atur Privileges -->
<div class="modal fade" id="userPrivilages" tabindex="-1" aria-labelledby="assignModalLabel" aria-hidden="true"
     data-bs-backdrop="static" data-bs-keyboard="false">
  <div class="modal-dialog modal-dialog-centered modal-lg">
    <div class="modal-content">

    <form id="privForm" method="POST" action="{{ url('user-management') }}/{{ $user->user_id }}/roles">
        @csrf
        @method('PUT')

        <div class="modal-header">
          <h5 class="modal-title w-100 text-center" id="assignModalLabel">Atur Hak Pengguna</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
        </div>

        <div class="modal-body">
          <div class="row">
            <div class="col-md-5">
              <label class="form-label">Daftar Hak</label>
                @php
                    $user = auth()->user();
                    $isUserIT = $user->role_id == 1 || 
                        (is_array($user->sub_role) ? in_array(1, $user->sub_role) : in_array(1, json_decode($user->sub_role ?? '[]', true)));
                @endphp
                <select class="form-select available-privileges" multiple size="10">
                @foreach($mainRole as $mainRoles)
                    @if($isUserIT || in_array($mainRoles->role_id, [2,3,4]))
                        <option value="{{ $mainRoles->role_id }}">{{ $mainRoles->role_name }}</option>
                    @endif
                @endforeach
                </select>
            </div>

            <div class="col-md-2 d-flex flex-column align-items-center justify-content-center gap-2">
              <button type="button" class="btn btn-outline-secondary move-right">&gt;</button>
              <button type="button" class="btn btn-outline-secondary move-left">&lt;</button>
            </div>

            <div class="col-md-5">
              <label class="form-label">Hak saat ini</label>
              <select class="form-select current-privileges" multiple size="10"></select>
            </div>
          </div>
        </div>

        <div class="modal-footer">
          <button class="btn btn-secondary" type="button" data-bs-dismiss="modal">Batal</button>
          <button class="btn btn-primary apply-privileges" type="submit">Terapkan Hak</button>
        </div>

        <!-- container untuk hidden inputs sub_role[] -->
        <div id="subRoleHiddenContainer"></div>
      </form>

    </div>
  </div>
</div>

<!-- Modal Delete User -->
<div class="modal fade" id="DeleteUser" tabindex="-1" aria-hidden="true"
     data-bs-backdrop="static" data-bs-keyboard="false">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">

            <!-- Header Modal -->
            <div class="modal-header border-0">
                <h5 class="modal-title fw-bold">Konfirmasi Operasi Pengguna</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>

            <!-- Body Modal -->
            <div class="modal-body text-center">
                <p class="mb-3">Apakah Anda yakin ingin menindaklanjuti pengguna ini?</p>

                <!-- Pilihan Konfirmasi -->
                <div class="mb-4">
                    <select class="form-select w-50 mx-auto" id="confirmAction">
                        <option value="" selected disabled>-- Pilih Tindakan --</option>
                        <option value="delete">Ya, saya yakin hapus</option>
                        <option value="activate">Ya, saya yakin aktifkan kembali pengguna</option>
                        <option value="deactivate">Ya, saya yakin nonaktifkan pengguna</option>
                    </select>
                </div>
                <!-- Tombol Aksi -->
                <div class="d-flex justify-content-center gap-3">
                    <button class="btn btn-secondary px-4" data-bs-dismiss="modal">Batal</button>
                    <button class="btn btn-warning px-4" id="nonactiveBtn">Nonaktifkan</button>
                    <button class="btn btn-danger px-4" id="deleteUserBtn">Hapus</button>
                </div>
            </div>
        </div>
    </div>
</div>

{{-- Animasi langsung di Blade --}}
<style>
    /* #userTable tbody tr {
        opacity: 0;
        transform: translateY(20px);
        transition: opacity 0.5s ease, transform 0.5s ease;
    }
    #userTable tbody tr.show {
        opacity: 1;
        transform: translateY(0);
    } */
    /* Style umum */
    .freeze-col {
        position: sticky;
        background: #fff;
        z-index: 3;
    }

    /* Freeze sampai Divisi */
    .col-nomor   { left: 0;    min-width: 60px;  }
    .col-id      { left: 60px; min-width: 120px; }
    .col-nama    { left: 180px; min-width: 200px; }
    .col-jabatan { left: 380px; min-width: 160px; }
    .col-divisi  { left: 540px; min-width: 200px; box-shadow: 2px 0 5px rgba(0,0,0,0.1); }

    /* Header lebih tegas */
    thead th {
        background: var(--theme-primary) !important;
        color: var(--theme-text);
        z-index: 4;
    }

</style>

{{-- Script --}}
<!-- <script>
    document.addEventListener("DOMContentLoaded", function () {
        const rows = document.querySelectorAll("#userTable tbody tr");

        // Animasi muncul per-row
        rows.forEach((row, index) => {
            setTimeout(() => row.classList.add("show"), index * 100);
        });

        // Filter pencarian multi kolom
        const searchInput = document.getElementById("searchUser");
        searchInput.addEventListener("keyup", function () {
            const keyword = this.value.toLowerCase();
            rows.forEach(row => {
                const text = row.textContent.toLowerCase();
                row.style.display = text.includes(keyword) ? "" : "none";
            });
        });

         (function () {
            'use strict'
            const forms = document.querySelectorAll('.needs-validation')
            Array.from(forms).forEach(form => {
            form.addEventListener('submit', event => {
                if (!form.checkValidity()) {
                event.preventDefault()
                event.stopPropagation()
                }
                form.classList.add('was-validated')
            }, false)
            })
        })()
    });
</script> -->
<script>
document.addEventListener("DOMContentLoaded", function () {
    const rows = Array.from(document.querySelectorAll("#userTable tbody tr"));
    const searchInput = document.getElementById("searchUser");
    const paginationControls = document.createElement("div");
    const paginationInfo = document.createElement("div");
    const rowsPerPage = 10;
    let currentPage = 1;
    let filteredRows = [...rows];

    // Tambahkan wrapper pagination
    const tableContainer = document.querySelector("#userTable").closest(".card-body");
    const paginationWrapper = document.createElement("div");
    paginationWrapper.className = "d-flex justify-content-between align-items-center mt-3 flex-wrap gap-2";
    paginationInfo.className = "text-muted small mx-3";
    paginationWrapper.appendChild(paginationInfo);
    paginationWrapper.appendChild(paginationControls);
    tableContainer.appendChild(paginationWrapper);

    // Fungsi ubah halaman
    function changePage(page) {
        currentPage = page;
        const start = (page - 1) * rowsPerPage;
        const end = start + rowsPerPage;
        rows.forEach(r => (r.style.display = "none"));
        filteredRows.slice(start, end).forEach(r => (r.style.display = ""));
        renderPagination();
    }

    // Render pagination
    function renderPagination() {
        const totalPages = Math.ceil(filteredRows.length / rowsPerPage);
        paginationControls.innerHTML = "";
        if (filteredRows.length === 0) {
            paginationInfo.textContent = "Tidak ada data ditemukan.";
            return;
        }
        const start = (currentPage - 1) * rowsPerPage + 1;
        const end = Math.min(currentPage * rowsPerPage, filteredRows.length);
        paginationInfo.textContent = `Menampilkan ${start}–${end} dari ${filteredRows.length} pengguna`;

        const prevBtn = createPageButton("← Sebelumnya", currentPage > 1, () => changePage(currentPage - 1));
        paginationControls.appendChild(prevBtn);

        const range = getVisibleRange(currentPage, totalPages, 5);
        range.forEach(i => {
            const btn = createPageButton(i, true, () => changePage(i), i === currentPage);
            paginationControls.appendChild(btn);
        });

        const nextBtn = createPageButton("Selanjutnya →", currentPage < totalPages, () => changePage(currentPage + 1));
        paginationControls.appendChild(nextBtn);
    }

    // Buat tombol pagination
    function createPageButton(text, enabled, onClick, active = false) {
        const btn = document.createElement("button");
        btn.className = `btn btn-sm ${active ? "btn-warning" : "btn-outline-warning"} mx-1 mb-3`;
        btn.textContent = text;
        btn.disabled = !enabled;
        if (enabled) btn.onclick = onClick;
        return btn;
    }

    // Hitung range tombol halaman
    function getVisibleRange(current, total, max) {
        const half = Math.floor(max / 2);
        let start = Math.max(1, current - half);
        let end = Math.min(total, current + half);
        if (end - start < max - 1) {
            if (start === 1) end = Math.min(total, start + max - 1);
            else if (end === total) start = Math.max(1, end - max + 1);
        }
        return Array.from({ length: end - start + 1 }, (_, i) => start + i);
    }

    // Filter pencarian
    searchInput.addEventListener("keyup", function () {
        const keyword = this.value.toLowerCase();
        filteredRows = rows.filter(row => row.textContent.toLowerCase().includes(keyword));
        currentPage = 1;
        changePage(1);
    });

    // Animasi baris
    rows.forEach((row, index) => {
        setTimeout(() => row.classList.add("show"), index * 100);
    });

    // Validasi Bootstrap
    (function () {
        "use strict";
        const forms = document.querySelectorAll(".needs-validation");
        Array.from(forms).forEach(form => {
            form.addEventListener("submit", event => {
                if (!form.checkValidity()) {
                    event.preventDefault();
                    event.stopPropagation();
                }
                form.classList.add("was-validated");
            }, false);
        });
    })();

    // Inisialisasi
    changePage(1);
});
</script>

@endsection
