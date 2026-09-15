@extends('layouts.master')

@section('title', 'Jelajahi Kursus')

@section('content')
<div class="container py-4">
    <div class="d-flex flex-column mb-4 border-bottom pb-3 align-items-center text-center">
        <h2 class="fw-bold text-dark mb-1">Penugasan Kursus </h2>
        <small class="text-muted">Kekola pengguna yang ingin digabungkan ke dalam Kursus.</small>
    </div>
    <!-- Filter Bar -->
    <div class="card shadow-sm mb-4">
        <div class="card-body">
            <form class="row g-3 align-items-center" onsubmit="return false;">
                <div class="col-md-4">
                    <input type="text" id="searchCourse" class="form-control" placeholder="Cari kursus...">
                </div>
            </form>
        </div>
    </div>

    <!-- Grid Kursus -->
    <div class="row g-4">
        @forelse ($courses as $course)
            <div class="col-md-3 col-sm-6 course-card"> <!-- 🔥 animasi dihandle class course-card -->
                <div class="card shadow-sm h-100">
                    <div class="card-body d-flex flex-column">
                        
                        <!-- Thumbnail -->
                        <div class="mb-3 bg-light d-flex justify-content-center align-items-center" 
                             style="height:140px; border:1px solid #ddd;">
                            <img src="{{ $course->course_image_url }}" 
                                 alt="{{ $course->course_title ?? 'Course Thumbnail' }}" 
                                 style="max-height: 100%; max-width: 100%; object-fit: contain;">
                        </div>

                        <!-- Nama Kursus -->
                        <h6 class="fw-bold course-title text-center mb-1">{{ $course->course_title }}</h6>

                        <!-- Trainer -->
                        <p class="text-muted mb-3 course-desc text-center fst-italic"> 
                            {{ $course->course_trainer_name }}
                        </p>

                        <!-- Action Button -->
                        <div class="mt-auto d-grid gap-2">
                            <button class="btn btn-success" 
                                    data-bs-toggle="modal" 
                                    data-bs-target="#assignModal"
                                    data-course-id="{{ $course->course_id }}"
                                    data-course-name="{{ $course->course_title }}"
                                    data-max-participant="{{ $course->max_participant }}">

                                Gabungkan Learner
                            </button>
                            <button class="btn btn-secondary" 
                                    data-bs-toggle="modal" 
                                    data-bs-target="#operateModal"
                                    data-course-id="{{ $course->course_id }}"
                                    data-course-name="{{ $course->course_title }}">
                                Operasi Learner
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        @empty
            <div class="col-12">
                <p class="text-center text-muted">Tidak ada kursus tersedia.</p>
            </div>
        @endforelse
    </div>

    <!-- Modal Operate -->
    <div class="modal fade" id="operateModal" tabindex="-1" aria-labelledby="operateModalLabel" aria-hidden="true" data-bs-backdrop="static" data-bs-keyboard="false">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="operateModalLabel">Kelola Learner Tergabung</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Tutup"></button>
                </div>
                <div class="modal-body">
                        <label for="enrolledUsers" class="form-label fw-bold">
                            Silahkan checklist learner yang ingin dihapus:
                        </label>                  
                        <form id="rollbackForm">
                        @csrf
                        <input type="hidden" id="rollbackCourseId" name="course_id">
                        
                        <!-- tempat list user -->
                        <div id="enrolledUsers" class="mb-3" style="max-height:400px; overflow-y:auto;">
                            <p class="text-muted">Loading enrolled users...</p>
                        </div>
                        <!-- tombol -->
                        <div class="d-flex justify-content-between gap-2">
                            <div>
                                <button type="button" class="btn btn-secondary" id="selectAllCheck"> Pilih Semua </button>
                            </div>
                            <div>
                                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Tutup</button>
                                <button type="submit" class="btn btn-danger mr-2">Simpan Perubahan </button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal Assign Learner -->
    <div class="modal fade" id="assignModal" tabindex="-1" aria-labelledby="assignModalLabel" aria-hidden="true"
        data-bs-backdrop="static" data-bs-keyboard="false">
        <div class="modal-dialog modal-dialog-centered modal-lg">
            <div class="modal-content">

                <!-- Header Modal -->
                <div class="modal-header flex-column align-items-center">
                    <h5 class="modal-title text-center mb-1" id="assignModalLabel">
                        Tugaskan Learner ke Kursus
                    </h5>
                    <p id="courseNameLabel" class="text-center fst-italic small mb-0"></p>
                    <p id="currentLearner" class="text-center fst-italic small mb-0"></p>
                    <button type="button" class="btn-close position-absolute end-0 me-2" 
                            data-bs-dismiss="modal" aria-label="Close"></button>
                </div>

                <!-- Body Modal -->
                <form id="assignForm" method="POST" action="{{ route('administrator.course-list.assign') }}">
                    @csrf
                    <input type="hidden" name="course_id" id="courseIdInput">

                    <div class="modal-body">
                        <div class="mb-3">
                            <label for="divisionSelect" class="form-label">Filter Divisi</label>
                            @php
                                $divisions = \App\Models\Division::select("division_id", "division_name")->get();
                            @endphp
                            <select class="form-select" name="division" id="divisionSelect">
                                <option value="all"> Semua Learner </option>
                                @foreach ($divisions as $division)
                                    <option value="{{ $division->division_id }}">{{ $division->division_name }}</option>
                                @endforeach
                            </select> 

                            <label for="searchUser" class="form-label mt-2">Cari Learner</label>
                            <input type="text" id="searchUserInput" class="form-control" placeholder="Ketik nama learner...">
                        </div>

                        <div class="row">
                            <!-- Kolom Kiri -->
                            <div class="col-md-5">
                                @php
                                    $allUsers = \App\Models\User::select('user_id', 'full_name', 'departement_cat', 'role_id', 'sub_role')
                                        ->where('is_active', 1)   // hanya yang aktif
                                        ->where('is_deleted', 0)  // tidak terhapus
                                        ->where(function($q){
                                            $q->where('role_id', 4)
                                            ->orWhereJsonContains('sub_role', 4);
                                        })
                                        ->get();
                                @endphp
                                <select class="form-select" id="userSelect" multiple size="10">
                                    @foreach($allUsers as $user)
                                        <option 
                                            value="{{ $user->user_id }}" 
                                            data-division="{{ $user->departement_cat }}">
                                            {{ $user->full_name }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                            <!-- Kolom Tengah -->
                            <div class="col-md-2 d-flex flex-column align-items-center justify-content-center gap-2">
                                <button type="button" class="btn btn-outline-secondary" id="moveRight">&gt;</button>
                                <button type="button" class="btn btn-outline-secondary" id="moveLeft">&lt;</button>
                            </div>

                            <!-- Kolom Kanan -->
                            <div class="col-md-5">
                                <div class="d-flex justify-content-between align-items-center mb-2">
                                    <label class="form-label mb-0">Learner Ditugaskan</label>
                                    <div class="d-flex gap-2">
                                        <button type="button" class="btn btn-secondary btn-sm" id="selectAllLearner">Semua learner</button>
                                        <button type="button" class="btn btn-warning btn-sm" id="resetLearner">Reset</button>
                                    </div>
                                </div>
                                <select class="form-select" id="assignedSelect" name="user_ids[]" multiple size="10"></select>
                            </div>
                        </div>
                    </div>

                    <!-- Footer Modal -->
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                        <button type="submit" class="btn btn-warning">Tugaskan Learner</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

{{-- Style & Animasi --}}
<style>
.course-card {
    opacity: 0;
    transform: translateY(20px);
    transition: opacity 0.6s ease, transform 0.6s ease;
}
.course-card.show {
    opacity: 1;
    transform: translateY(0);
}
.course-card .card {
    border-radius: 12px;
    transition: transform 0.3s ease, box-shadow 0.3s ease;
}
.course-card .card:hover {
    transform: translateY(-5px);
    box-shadow: 0 8px 16px rgba(0,0,0,0.15);
}
.btn-success:hover {
    transform: scale(1.03);
}
.btn-outline-primary:hover {
    background-color: var(--theme-primary);
    color: var(--theme-text);
    transform: scale(1.05);
}
</style>

<script>
document.addEventListener("DOMContentLoaded", function () {
    const cards = document.querySelectorAll(".course-card");
    cards.forEach((card, index) => {
        setTimeout(() => {
            card.classList.add("show");
        }, index * 120);
    });
});
</script>
@endsection
