@extends('layouts.master')

@section('title', 'Profil Saya')

@section('content')
@php
    $finishedCourseCount = $getFinishedCourse->count();
    $passedCourseCount = $getFinishedCourse->filter(fn ($course) => (bool) $course->is_passed)->count();
    $averageGrade = $finishedCourseCount > 0 ? number_format((float) $getTotalGradeCourse, 1) : null;
@endphp

<main class="container profile-page py-4 py-lg-5">
    <header class="profile-page-header">
        <div>
            <p class="profile-page-eyebrow mb-2">Pengaturan Akun</p>
            <h1 class="profile-page-title mb-2">Profil Saya</h1>
            <p class="profile-page-description mb-0">Kelola foto profil dan tinjau informasi kepegawaian serta riwayat pembelajaran Anda.</p>
        </div>
        <button type="button"
                class="btn btn-secondary profile-edit-action"
                data-bs-toggle="modal"
                data-bs-target="#EditProfileModal"
                data-id="{{ $currentUser->user_id }}"
                data-full_name="{{ $currentUser->full_name }}"
                data-emp_id="{{ $currentUser->emp_id }}"
                data-photo="{{ $photoUrl }}">
            Ubah Foto Profil
        </button>
    </header>

    @if(session('success'))
        <div class="alert alert-success profile-alert" role="status">
            {{ session('success') }}
        </div>
    @endif

    @if($errors->any())
        <div class="alert alert-danger profile-alert" role="alert">
            <strong>Profil belum dapat diperbarui.</strong>
            <span>{{ $errors->first() }}</span>
        </div>
    @endif

    <section class="profile-identity-panel" aria-labelledby="profile-name">
        <div class="profile-identity-main">
            <img src="{{ $photoUrl }}"
                 alt="Foto profil {{ $currentUser->full_name ?? 'pengguna' }}"
                 class="profile-avatar"
                 onerror="this.src='{{ asset('assets/img/defaultPic.jpeg') }}'">
            <div class="profile-identity-copy">
                <span class="profile-role-label">{{ $roleLabel }}</span>
                <h2 class="profile-identity-name mb-1" id="profile-name">{{ $currentUser->full_name ?? '-' }}</h2>
                <p class="profile-employee-summary mb-0">{{ $currentUser->emp_id ?? '-' }} · {{ $deptLabel }}</p>
            </div>
        </div>

        <dl class="profile-detail-grid mb-0">
            <div class="profile-detail-item">
                <dt>ID Karyawan</dt>
                <dd>{{ $currentUser->emp_id ?? '-' }}</dd>
            </div>
            <div class="profile-detail-item">
                <dt>Departemen</dt>
                <dd>{{ $deptLabel }}</dd>
            </div>
            <div class="profile-detail-item">
                <dt>Peran Utama</dt>
                <dd>{{ $roleLabel }}</dd>
            </div>
        </dl>
    </section>

    <section class="profile-metrics" aria-label="Ringkasan pembelajaran">
        <article class="profile-metric">
            <span class="profile-metric-label">Kursus Selesai</span>
            <strong class="profile-metric-value">{{ $finishedCourseCount }}</strong>
            <span class="profile-metric-note">Total pembelajaran diselesaikan</span>
        </article>
        <article class="profile-metric">
            <span class="profile-metric-label">Kursus Lulus</span>
            <strong class="profile-metric-value">{{ $passedCourseCount }}</strong>
            <span class="profile-metric-note">Memenuhi standar kelulusan</span>
        </article>
        <article class="profile-metric">
            <span class="profile-metric-label">Rata-rata Nilai</span>
            <strong class="profile-metric-value">{{ $averageGrade ?? '—' }}{{ $averageGrade !== null ? '%' : '' }}</strong>
            <span class="profile-metric-note">Berdasarkan penilaian terakhir</span>
        </article>
    </section>

    <section class="profile-learning-section" aria-labelledby="completed-courses-title">
        <div class="profile-section-heading">
            <div>
                <p class="profile-page-eyebrow mb-1">Riwayat Pembelajaran</p>
                <h2 class="mb-1" id="completed-courses-title">Kursus Selesai</h2>
                <p class="mb-0">Kursus yang telah mencapai progres 100%.</p>
            </div>
            <span class="profile-course-count">{{ $finishedCourseCount }} kursus</span>
        </div>

        @if($getFinishedCourse->isNotEmpty())
            <div class="row g-3 g-lg-4">
                @foreach($getFinishedCourse as $gfc)
                    <div class="col-md-6 col-xl-4">
                        <article class="profile-course-card h-100">
                            <div class="profile-course-media">
                                <img src="{{ asset('assets/img/course/' . $gfc->course_image) }}"
                                     alt="Sampul kursus {{ $gfc->course_title }}"
                                     loading="lazy"
                                     onerror="this.src='{{ asset('assets/img/course/default-course.jpeg') }}'">
                            </div>
                            <div class="profile-course-body">
                                <p class="profile-course-type mb-2">Kursus</p>
                                <h3 class="profile-course-title mb-3">{{ $gfc->course_title }}</h3>
                                <div class="profile-course-status {{ $gfc->is_passed ? 'is-passed' : 'is-pending' }}">
                                    <span class="profile-course-status-dot" aria-hidden="true"></span>
                                    {{ $gfc->is_passed ? 'Lulus' : 'Perlu Tindak Lanjut' }}
                                </div>
                            </div>
                        </article>
                    </div>
                @endforeach
            </div>
        @else
            <div class="profile-empty-state">
                <h3 class="mb-2">Belum ada kursus selesai</h3>
                <p class="mb-0">Kursus yang telah mencapai progres 100% akan ditampilkan di sini.</p>
            </div>
        @endif
    </section>
</main>

<div class="modal fade" id="EditProfileModal" tabindex="-1" aria-labelledby="EditProfileModalLabel" aria-hidden="true" data-bs-backdrop="static" data-action="{{ route('profile.editUser') }}">
    <div class="modal-dialog modal-lg">
        <div class="modal-content profile-modal">
            <form id="editUserForm"
                  class="needs-validation"
                  novalidate
                  method="POST"
                  enctype="multipart/form-data"
                  action="{{ route('profile.editUser') }}">
                @csrf
                @method('PUT')
                <input type="hidden" id="emp_id_hidden" name="emp_id" value="{{ $currentUser->emp_id }}">

                <div class="modal-header profile-modal-header">
                    <div>
                        <p class="profile-page-eyebrow mb-1">Pengaturan Profil</p>
                        <h2 class="modal-title" id="EditProfileModalLabel">Perbarui Foto Profil</h2>
                    </div>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Tutup"></button>
                </div>

                <div class="modal-body profile-modal-body">
                    <div class="profile-photo-editor">
                        <img id="photoPreview"
                             src="{{ $photoUrl }}"
                             alt="Pratinjau foto profil"
                             class="profile-photo-preview"
                             onerror="this.src='{{ asset('assets/img/defaultPic.jpeg') }}'">
                        <div class="profile-photo-control">
                            <label for="photo_profile" class="form-label fw-semibold">Pilih foto baru</label>
                            <input type="file"
                                   class="form-control @error('photo_profile') is-invalid @enderror"
                                   id="photo_profile"
                                   name="photo_profile"
                                   accept="image/jpeg,image/png">
                            <p class="form-text mb-0">Gunakan JPG atau PNG dengan ukuran maksimal 10 MB.</p>
                            @error('photo_profile')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>

                    <div class="profile-modal-account">
                        <h3>Informasi Akun</h3>
                        <p>Informasi kepegawaian dikelola oleh administrator dan tidak dapat diubah dari halaman ini.</p>
                        <dl class="profile-modal-details mb-0">
                            <div>
                                <dt>Nama Lengkap</dt>
                                <dd>{{ $currentUser->full_name ?? '-' }}</dd>
                            </div>
                            <div>
                                <dt>ID Karyawan</dt>
                                <dd>{{ $currentUser->emp_id ?? '-' }}</dd>
                            </div>
                            <div>
                                <dt>Departemen</dt>
                                <dd>{{ $deptLabel }}</dd>
                            </div>
                        </dl>
                    </div>

                    <div class="profile-security-row">
                        <div>
                            <h3 class="mb-1">Reset Password</h3>
                            <p class="mb-0">Password akan dikembalikan ke ID karyawan dan Anda akan diminta masuk kembali.</p>
                        </div>
                        <div class="profile-security-control">
                            <label for="currentPassword" class="form-label fw-semibold">Password saat ini</label>
                            <div class="profile-security-action">
                                <input type="password"
                                       class="form-control"
                                       id="currentPassword"
                                       autocomplete="current-password"
                                       placeholder="Masukkan password">
                                <button type="button" class="btn btn-outline-danger" id="btnResetPassword">Reset Password</button>
                            </div>
                            <p class="profile-reset-feedback d-none mb-0" id="profileResetFeedback" role="status"></p>
                        </div>
                    </div>
                </div>

                <div class="modal-footer profile-modal-footer">
                    <button type="button" class="btn btn-light" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-secondary">Simpan Foto</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection
