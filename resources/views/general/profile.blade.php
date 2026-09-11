@extends('layouts.master')

@section('title', 'Profil Pengguna')

@section('content')
<div class="container py-4">
    <div class="d-flex flex-column mb-4 border-bottom pb-3 align-items-center text-center">
        <h2 class="fw-bold text-dark mb-1">Halaman Profile</h2>
        <small class="text-muted">Kelola informasi akun pengguna.</small>
    </div>
    {{-- Profile Section --}}
    <div class="card shadow-sm mb-4 border-0">
        <div class="card-body d-flex flex-wrap align-items-center justify-content-between">
            {{-- Foto + Info --}}
            <div class="d-flex align-items-center">
                <img
                    src="{{ $photoUrl }}"
                    alt="{{ $currentUser->full_name ?? 'User Photo' }}"
                    class="rounded-circle border border-2 border-white shadow-sm me-3"
                    style="width:90px;height:90px;object-fit:cover;"
                    onerror="this.src='{{ asset('assets/img/defaultPic.jpeg') }}'">

                <div>
                    <h4 class="mb-1 fw-bold">{{ $currentUser->full_name ?? '-' }}</h4>
                    <p class="text-muted mb-0">ID Karyawan : {{ $currentUser->emp_id ?? '-' }}</p>
                    <p class="text-muted mb-0">Departemen : {{ $deptLabel }}</p>
                    <p class="text-muted mb-0">Role : {{ $roleLabel }}</p>
                </div>
            </div>

            {{-- Tombol Aksi --}}
            <div class="mt-3 mt-md-0">
                <button type="button" class="btn btn-outline-secondary btn-md"
                        data-bs-toggle="modal"
                        data-bs-target="#EditProfileModal"
                        data-id="{{ $currentUser->user_id }}"
                        data-full_name="{{ $currentUser->full_name }}"
                        data-emp_id="{{ $currentUser->emp_id }}"
                        data-photo="{{ $photoUrl }}">
                    ✏️ Ubah Profil
                </button>
            </div>
        </div>
    </div>

    {{-- Finished Courses --}}
    <h4 class="fw-bold mb-3">📚 Kursus Selesai</h4>
    <div class="row">
        @forelse($getFinishedCourse as $gfc)
            <div class="col-md-4 mb-4">
                <div class="card h-100 border-0 shadow-sm">
                    <img src="{{ asset('assets/img/course/' . $gfc->course_image) }}" 
                        class="card-img-top p-2"
                        alt="{{ $gfc->course_title }}" 
                        style="height: 180px; object-fit: contain;">

                    <div class="card-body text-center">
                        <h6 class="fw-bold text-dark mb-2">{{ $gfc->course_title }}</h6>
                        @if($gfc->is_passed)
                            <span class="badge bg-success">✅ Lulus</span>
                        @else
                            <span class="badge bg-danger">❌ Belum Lulus</span>
                        @endif
                    </div>
                </div>
            </div>
        @empty
            <div class="col-12 text-center">
                <p class="text-muted fst-italic">Belum ada kursus yang selesai.</p>
            </div>
        @endforelse
    </div>
</div>

{{-- Modal Ubah User --}}
<div class="modal fade" id="EditProfileModal" tabindex="-1" aria-labelledby="EditProfileModalLabel" aria-hidden="true" data-bs-backdrop="static">
  <div class="modal-dialog modal-lg">
    <div class="modal-content border-0 shadow">
      <div class="modal-header bg-light border-bottom-0">
        <h5 class="modal-title fw-bold" id="EditProfileModalLabel">✏️ Perubahan Profil</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Tutup"></button>
      </div>
      <div class="modal-body">
        <form id="editUserForm" 
              class="needs-validation" 
              novalidate
              method="POST" 
              enctype="multipart/form-data" 
              action="{{ route('profile.editUser') }}">
            @csrf
            @method('PUT')

            {{-- Photo Preview --}}
            <div class="text-center mb-3">
                <img id="photoPreview" src="{{ $photoUrl }}" alt="Photo"
                    class="rounded-circle border shadow-sm"
                    style="width: 100px; height: 100px; object-fit: cover;">
            </div>

            {{-- ID Karyawan (readonly) --}}
            <div class="mb-3">
                <label for="emp_id" class="form-label fw-semibold">ID Karyawan</label>
                <input type="text" class="form-control" value="{{ $currentUser->emp_id }}" disabled>
                <input type="hidden" name="emp_id" value="{{ $currentUser->emp_id }}">
            </div>

              {{-- Nama Lengkap --}}
            <div class="mb-3">
                <label for="full_name" class="form-label fw-semibold">
                    Nama Lengkap <span class="text-danger">*</span>
                </label>
                <input type="text" 
                       class="form-control @error('full_name') is-invalid @enderror" 
                       id="full_name" 
                       name="full_name" 
                       value="{{ old('full_name', $currentUser->full_name) }}" 
                       required 
                       placeholder="Masukkan nama lengkap" disabled>
                @error('full_name')
                    <div class="invalid-feedback">{{ $message }}</div>
                @else
                    <div class="invalid-feedback">Nama Lengkap wajib diisi.</div>
                @enderror
            </div>

            {{-- Upload Photo --}}
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


            {{-- Tombol --}}
            <div class="d-flex justify-content-between gap-2">
                <div> 
                    <button type="button" class="btn btn-warning" id="btnResetPassword"> Reset Password </button>
                </div>
                <div>                 
                    <button type="button" class="btn btn-light" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-secondary">Simpan</button>
            </div>


            </div>
        </form>
      </div>
    </div>
  </div>
</div>
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
    const form = document.getElementById('editUserForm');

    form.addEventListener('submit', function (event) {
        // Cegah submit kalau form tidak valid
        if (!form.checkValidity()) {
            event.preventDefault();
            event.stopPropagation();
        }

        // Tambah kelas Bootstrap biar invalid-feedback tampil
        form.classList.add('was-validated');
    }, false);
});
</script>
@endpush
