@extends('layouts.master')

@section('title', 'Laporan HR - LMS')

@section('content')
<div class="container py-4">
    <div class="d-flex flex-column mb-4 border-bottom pb-3 align-items-center text-center">
            <h2 class="fw-bold text-dark mb-1">Laporan HR </h2>
            <small class="text-muted"> Informasi terkait LMS  </small>
    </div>

    <!-- Statistik Ringkas -->
    <div class="d-flex justify-content-between align-items-stretch gap-3 mb-4 flex-wrap">
        <div class="card shadow-sm border-0 flex-grow-0" style="width: 200px;">
            <div class="card-body text-center">
                <h4 class="fw-bold text-primary mb-0">{{ $totalEmployees }}</h4>
                <p class="text-muted mb-0">Total Karyawan</p>
            </div>
        </div>

        <div class="card shadow-sm border-0 flex-grow-0" style="width: 200px;">
            <div class="card-body text-center">
                <h4 class="fw-bold text-success mb-0">{{ $activeCourses }}</h4>
                <p class="text-muted mb-0">Kursus Aktif</p>
            </div>
        </div>

        <div class="card shadow-sm border-0 flex-grow-0" style="width: 200px;">
            <div class="card-body text-center">
                <h4 class="fw-bold text-warning mb-0">{{ $avgProgressCourses }}%</h4>
                <p class="text-muted mb-0">Rata-rata Progress</p>
            </div>
        </div>

        <div class="card shadow-sm border-0 flex-grow-0" style="width: 200px;">
            <div class="card-body text-center">
                <h4 class="fw-bold text-info mb-0">{{ $totalLogins }}</h4>
                <p class="text-muted mb-0">Total Login</p>
            </div>
        </div>
            <div class="card shadow-sm border-0 flex-fill" style="min-width: 250px;">
            <div class="card-body text-center">
                <h5 class="fw-semibold text-info mb-2">🏆 Top Active Learners</h5>
                @if(isset($topActiveLearners) && count($topActiveLearners) > 0)
                    <ul class="list-unstyled mb-0 small">
                        @foreach($topActiveLearners as $i => $learner)
                            <li class="mb-1">
                                <span class="fw-bold text-dark">
                                    {{ $i+1 }}. {{ $learner['name'] }}
                                </span>
                                <br>
                                <span class="text-muted">{{ $learner['progress'] }}%</span>
                            </li>
                        @endforeach
                    </ul>
                @else
                    <p class="text-muted mb-0">Belum ada data progress.</p>
                @endif
            </div>
        </div>
    </div>

    {{-- FILTER EXPORT --}}
    <div class="card shadow-sm mb-4 border-0">
        <div class="card-body">
            <h6 class="fw-semibold mb-3 fs-6">
            Filter Tampilan Data
            </h6>

            <form id="filterForm" class="row g-3 align-items-end" method="GET" action="{{ route('administrator.report') }}">
                {{-- Periode Tanggal --}}
                <div class="col-md-4">
                    <label for="startDate" class="form-label fw-semibold">Dari Tanggal</label>
                    <input type="date" id="startDate" name="start_date" class="form-control"
                        value="{{ request('start_date') }}">
                </div>

                <div class="col-md-4">
                    <label for="endDate" class="form-label fw-semibold">Sampai Tanggal</label>
                    <input type="date" id="endDate" name="end_date" class="form-control"
                        value="{{ request('end_date') }}">
                </div>

                {{-- Kategori Kursus --}}
                <div class="col-md-4">
                    <label for="courseCategory" class="form-label fw-semibold">Kategori Kursus</label>
                    <select id="courseCategory" name="course_category" class="form-select">
                        <option value="">-- Pilih Kategori --</option>
                        @foreach ($courseCategory as $cat)
                            <option value="{{ $cat->category_name }}"
                                {{ request('course_category') == $cat->category_name ? 'selected' : '' }}>
                                {{ $cat->category_name }}
                            </option>
                        @endforeach
                    </select>
                </div>
                
                {{-- Tombol Aksi --}}
                <div class="col-md-12 text-end">
                    <button type="submit" class="btn btn-warning px-4">
                        Inquiry
                    </button>
                    <a href="{{ route('administrator.report') }}" class="btn btn-outline-secondary px-4 ms-2">
                        Reset
                    </a>
                </div>
            </form>
        </div>
    </div>
   <!-- Progress Kursus -->

    <!-- <div class="card shadow-sm mb-4">
        <div class="card-header bg-light fw-bold">
            Daftar Kursus :
        </div>
        <div class="card-body" style="max-height: 500px; overflow-y: auto;">
            @forelse($courseData as $course)
                <div class="mb-3"> -->
                    <!-- Toggle Nama Course -->
                    <!-- <p class="mb-1 fw-bold">
                        <a class="d-block text-decoration-none text-dark p-2 rounded"
                        style="transition: all 0.2s; border: 1px solid var(--theme-border);"
                        data-bs-toggle="collapse"
                        href="#course-{{ $course['course_id'] }}"
                        role="button"
                        aria-expanded="true"
                        aria-controls="course-{{ $course['course_id'] }}"
                        onmouseover="this.style.backgroundColor='var(--theme-background)'; this.style.boxShadow='0 2px 6px rgba(0,0,0,0.1)';"
                        onmouseout="this.style.backgroundColor='transparent'; this.style.boxShadow='none';">
                            📘 {{ $course['course_name'] }}
                        </a>
                    </p> -->

                    <!-- Divisi -->
                    <!-- <div class="collapse show" id="course-{{ $course['course_id'] }}"> {{-- langsung terbuka --}}
                        <div class="ms-3 p-2 border rounded bg-light">
                            @foreach($course['divisions'] as $division)
                                <div class="mb-3"> -->
                                    <!-- Toggle Divisi -->
                                    <!-- <div class="d-flex justify-content-between align-items-center">
                                        <a class="fw-semibold text-decoration-none"
                                        data-bs-toggle="collapse"
                                        href="#division-{{ Str::slug($course['course_id'].'-'.$division['division']) }}"
                                        role="button"
                                        aria-expanded="true"
                                        aria-controls="division-{{ Str::slug($course['course_id'].'-'.$division['division']) }}">
                                            👥 {{ $division['division'] }}
                                            <span class="badge bg-secondary">{{ $division['total_users'] }} User</span>
                                        </a>
                                    </div> -->

                                    <!-- Progress Bar -->
                                    <!-- <div class="progress my-2 ms-3" style="height: 18px;">
                                        <div class="progress-bar bg-success fw-bold"
                                            style="width: {{ $division['progress'] }}%;">
                                            {{ $division['progress'] }}%
                                        </div>
                                    </div> -->

                                    <!-- List User -->
                                    <!-- <div class="collapse show" id="division-{{ Str::slug($course['course_id'].'-'.$division['division']) }}"> {{-- langsung terbuka --}}
                                        <div class="ms-3" style="max-height: 200px; overflow-y: auto;">
                                            <ul class="list-group list-group-flush">
                                                @foreach($division['users'] as $user)
                                                    <li class="list-group-item py-1 px-2 d-flex justify-content-between align-items-center">
                                                        <div class="d-flex align-items-center">
                                                            <i class="bi bi-person-circle me-2 text-muted"></i>
                                                            {{ $user['name'] }}
                                                        </div>
                                                        <div class="progress" style="width: 120px; height: 12px;">
                                                            <div class="progress-bar bg-info"
                                                                style="width: {{ $user['progress'] }}%;">
                                                                {{ $user['progress'] }}
                                                            </div>
                                                        </div>
                                                    </li>
                                                @endforeach
                                            </ul>
                                        </div>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    </div>
                </div>
            @empty
                <p class="text-muted">Tidak ada data kursus aktif.</p>
            @endforelse
        </div>
    </div> -->
    
    <div class="card shadow-sm mb-4">
        <div class="card-header bg-light fw-bold">
            <div class="d-flex justify-content-between">
                <h6> Daftar Kursus :</h6>
                <a href="{{ route('administrator.report.course.export', [
                    'start_date' => request('start_date'),
                    'end_date' => request('end_date'),
                    'course_category' => request('course_category'),
                ]) }}" 
                class="btn btn-secondary">
                    ⬇️ Tarik Data Semua Course
                </a>
            </div>
            </div>
                <div class="card-body" style="max-height: 650px; overflow-y: auto;">
                    <div class="row g-3">
                        @forelse($courseData as $course)
                        <div class="col-md-4 col-sm-6 col-12">
                            <div class="card h-100 w-75 mx-auto border-1 shadow-sm transition-all hover-shadow text-center">
                            {{-- Gambar Kursus --}}
                            <img src="{{ asset('assets/img/course/' . ($course['course_image'] ?? 'default-course.jpg')) }}"
                                alt="{{ $course['course_name'] }}"
                                class="card-img-top"
                                style="object-fit: contain; height: 160px; border-top-left-radius: .5rem; border-top-right-radius: .5rem;">

                            {{-- Informasi Kursus --}}
                            <div class="card-body p-3">
                                <h6 class="fw-semibold mb-1 text-wrap" title="{{ $course['course_name'] }}">
                                {{ $course['course_name'] }}
                                </h6>
                                <p class="small mb-1 text-muted">
                                {{ $course['course_category'] ?? 'Tidak ada kategori' }}
                                </p>
                                <p class="small mb-0 text-muted">
                                {{ $course['start_course'] ?? '-' }} — {{ $course['end_course'] ?? '-' }}
                                </p>
                            </div>

                            {{-- Footer Button (Dropdown) --}}
                            <div class="card-footer p-0 border-top-0">
                                <div class="dropdown">
                                <button class="btn btn-secondary dropdown-toggle w-100 rounded-0" type="button" data-bs-toggle="dropdown">
                                    Tarik Data
                                </button>
                                <ul class="dropdown-menu text-start">
                                <li>
                                    <a class="dropdown-item" href="#" 
                                    data-bs-toggle="modal" 
                                    data-bs-target="#modalParticipantList"
                                    data-course-id="{{ $course['course_id'] }}">
                                    👥 Get List All Participant
                                    </a>
                                </li>

                                <li>
                                    <a class="dropdown-item" href="#"
                                    data-bs-toggle="modal"
                                    data-bs-target="#modalTestReport"
                                    data-course-id="{{ $course['course_id'] }}"
                                    >
                                    🧮 Get Activity Task Report 
                                    </a>
                                </li>

                                <li>
                                    <a class="dropdown-item" href="#"
                                    data-bs-toggle="modal"
                                    data-bs-target="#modalLearnerProgress"
                                    data-course-id="{{ $course['course_id'] }}">
                                    ⏱️ List Progress Learner in Course 
                                    </a>
                                </li>

                                <li>
                                    <a class="dropdown-item" href="#"
                                    data-bs-toggle="modal"
                                    data-bs-target="#modalFeedbackInstructor"
                                    data-course-id="{{ $course['course_id'] }}">
                                    🧮 Get Feedback Instructor Report  
                                    </a>
                                </li>

                                <li>
                                    <a class="dropdown-item" href="{{ route('instructor.submission-course', $course['course_id']) }}">
                                        📂 Unggahan Learner
                                    </a>
                                </li>
                                <li>
                                    <a class="dropdown-item" href="{{ route('instructor.track-course', $course['course_id']) }}">
                                        📊 Lacak Learner
                                    </a>
                                </li>
                                </ul>
                                </div>
                            </div>
                            </div>
                        </div>
                        @empty
                        <div class="col-12 text-center text-muted py-4">
                            Tidak ada data kursus.
                        </div>
                        @endforelse
                    </div>
                </div>
    </div>
    <!-- Tabel Detail Laporan -->
    <!-- <div class="card shadow-sm">
        <div class="card-header bg-light fw-bold">
            Laporan Detail Penyelesaian Kursus
        </div> -->
        <!-- kasih pembatas tinggi -->
        <!-- <div class="card-body p-0" style="max-height: 300px; overflow-y: auto;">
            <table class="table table-bordered table-striped mb-0 text-center align-middle">
                <thead class="table-secondary" style="position: sticky; top: 0; z-index: 1;">
                    <tr>
                        <th>Nama Karyawan</th>
                        <th>Departemen</th>
                        <th>Kursus Diikuti</th>
                        <th>Kursus Selesai</th>
                        <th>Progress</th>
                    </tr>
                </thead>
                <tbody>
                @foreach($reportDetails as $row)
                    <tr>
                        <td>{{ $row['name'] }}</td>
                        <td>{{ $row['department'] }}</td>
                        <td>{{ $row['total_courses'] }}</td>
                        <td>{{ $row['completed'] }}</td>
                        <td>
                            <div class="progress" style="height: 8px;">
                                <div class="progress-bar 
                                    @if($row['progress'] >= 70) bg-success 
                                    @elseif($row['progress'] >= 40) bg-info 
                                    @else bg-danger @endif"
                                    style="width: {{ $row['progress'] }}%;">
                                </div>
                            </div>
                        </td>
                    </tr>
                @endforeach
                </tbody>
            </table>
        </div>
    </div> -->


    <!-- Modal  -->
    {{-- 1️⃣ Modal - List All Participant --}}
    <div class="modal fade" id="modalParticipantList" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable">
            <div class="modal-content border-0 shadow-lg">
            <div class="modal-header bg-primary text-white">
                <h5 class="modal-title fw-semibold">👥 Inquiry - List All Participant inside the Course</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>

            <div class="modal-body">
                <h6 class="fw-semibold mb-4 text-center fs-6">
                Filter Tampilan Data & Tarik Data
                </h6>

                <form id="participantForm" class="row justify-content-center g-3"
                        method="GET"
                        action="{{ route('administrator.report.participant.export') }}">
                <input type="hidden" name="course_id" id="courseIdInput">
                {{-- Periode --}}
                <div class="col-md-4 col-sm-6">
                    <label class="form-label fw-semibold text-center">Dari Tanggal</label>
                    <input type="date" name="start_date" class="form-control">
                </div>

                <div class="col-md-4 col-sm-6">
                    <label class="form-label fw-semibold text-center">Sampai Tanggal</label>
                    <input type="date" name="end_date" class="form-control">
                </div>

                {{-- Departemen --}}
                <div class="col-md-4  col-sm-6">
                    <label for="department" class="form-label fw-semibold">Learner Departemen</label>
                    <select id="department" name="department" class="form-select">
                    <option value="">-- Pilih Kategori --</option>
                    @foreach ($userDivision as $div)
                        <option value="{{ $div->division_id }}"
                        {{ request('department') == $div->division_id ? 'selected' : '' }}>
                        {{ $div->division_name }}
                        </option>
                    @endforeach
                    </select>
                </div>

                {{-- Tombol --}}
                <div class="col-12 text-end mt-3">
                    <button type="submit" class="btn btn-primary px-4 me-2">Inquiry</button>
                    <button type="reset" class="btn btn-outline-secondary px-4">Reset</button>
                </div>
                </form>
            </div>
            </div>
        </div>
    </div>
    {{-- 3️⃣ Modal - Pre/Post Test Report --}}
    <div class="modal fade" id="modalTestReport" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable">
            <div class="modal-content border-0 shadow-lg">
            <div class="modal-header bg-info text-white">
                <h5 class="modal-title fw-semibold">🧮 Inquiry - Pre-Test / Post-Test Report</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <h6 class="fw-semibold mb-3 fs-6">Filter Tampilan Data & Tarik Data</h6>
                <form id="testReportForm" class="row g-3 justify-content-center" action="{{ route('administrator.activity.report.excel') }}">
               <input type="hidden" name="course_id" id="task_courseIdInput">
                <div class="col-md-4 col-sm-6">
                    <label class="form-label fw-semibold text-center">Dari Tanggal</label>
                    <input type="date" name="start_date" class="form-control">
                </div>

                <div class="col-md-4 col-sm-6">
                    <label class="form-label fw-semibold text-center">Sampai Tanggal</label>
                    <input type="date" name="end_date" class="form-control">
                </div>
                {{-- Departemen --}}
                <div class="col-md-4  col-sm-6">
                    <label for="department" class="form-label fw-semibold">Learner Departemen</label>
                    <select id="department" name="department" class="form-select">
                    <option value="">-- Pilih Kategori --</option>
                    @foreach ($userDivision as $div)
                        <option value="{{ $div->division_id }}"
                        {{ request('department') == $div->division_id ? 'selected' : '' }}>
                        {{ $div->division_name }}
                        </option>
                    @endforeach
                    </select>
                </div>
                <div class="col-md-12 text-end">
                    <button type="submit" class="btn btn-info px-4 text-white">Inquiry</button>
                    <button type="reset" class="btn btn-outline-secondary px-4 ms-2">Reset</button>
                </div>
                </form>
            </div>
            </div>
        </div>
    </div>
    {{-- 5️⃣ Modal - Learner Progress --}}
    <div class="modal fade" id="modalLearnerProgress" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable">
            <div class="modal-content border-0 shadow-lg">
            <div class="modal-header bg-secondary text-white">
                <h5 class="modal-title fw-semibold">⏱️ Inquiry - Learner Progress & Completion</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <h6 class="fw-semibold mb-3 fs-6">Filter Tampilan Data & Tarik Data</h6>
                <form id="progressForm" class="row g-3 justify-content-center" action="{{ route('administrator.report.learner.progress') }}">
                <input type="hidden" name="course_id" id="progress_courseIdInput">
                <div class="col-md-4">
                    <label class="form-label fw-semibold">Dari Tanggal</label>
                    <input type="date" name="start_date" class="form-control">
                </div>
                <div class="col-md-4">
                    <label class="form-label fw-semibold">Sampai Tanggal</label>
                    <input type="date" name="end_date" class="form-control">
                </div>
                {{-- Departemen --}}
                <div class="col-md-4  col-sm-6">
                    <label for="department" class="form-label fw-semibold">Learner Departemen</label>
                    <select id="department" name="department" class="form-select">
                    <option value="">-- Pilih Kategori --</option>
                    @foreach ($userDivision as $div)
                        <option value="{{ $div->division_id }}"
                        {{ request('department') == $div->division_id ? 'selected' : '' }}>
                        {{ $div->division_name }}
                        </option>
                    @endforeach
                    </select>
                </div>
                <div class="col-md-12 text-end">
                    <button type="submit" class="btn btn-secondary px-4">Inquiry</button>
                    <button type="reset" class="btn btn-outline-secondary px-4 ms-2">Reset</button>
                </div>
                </form>
            </div>
            </div>
        </div>
    </div>
     {{-- 5️⃣ Modal -Feedback Instructor --}}
    <div class="modal fade" id="modalFeedbackInstructor" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable">
            <div class="modal-content border-0 shadow-lg">

                <div class="modal-header bg-secondary text-white">
                    <h5 class="modal-title fw-semibold">📄 Inquiry – Feedback Instructor</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>

                <div class="modal-body">
                    <h6 class="fw-semibold mb-3 fs-6">Filter Tampilan Data & Tarik Data</h6>

                    <form id="feedbackForm" 
                        class="row g-3 justify-content-center" 
                        action="{{ route('administrator.instructor.feedback') }}">

                        <input type="hidden" name="course_id" id="feedback_courseIdInput">

                        {{-- Date Range --}}
                        <div class="col-md-4">
                            <label class="form-label fw-semibold">Dari Tanggal</label>
                            <input type="date" name="start_date" class="form-control">
                        </div>

                        <div class="col-md-4">
                            <label class="form-label fw-semibold">Sampai Tanggal</label>
                            <input type="date" name="end_date" class="form-control">
                        </div>

                        {{-- Departemen --}}
                        <div class="col-md-4 col-sm-6">
                            <label for="feedback_department" class="form-label fw-semibold">Learner Departemen</label>
                            <select id="feedback_department" name="department" class="form-select">
                                <option value="">-- Pilih Departemen --</option>
                                @foreach ($userDivision as $div)
                                    <option value="{{ $div->division_id }}"
                                        {{ request('department') == $div->division_id ? 'selected' : '' }}>
                                        {{ $div->division_name }}
                                    </option>
                                @endforeach
                            </select>
                        </div>

                        <div class="col-md-12 text-end">
                            <button type="submit" class="btn btn-secondary px-4">Inquiry</button>
                            <button type="reset" class="btn btn-outline-secondary px-4 ms-2">Reset</button>
                        </div>

                    </form>
                </div>

            </div>
        </div>
    </div>
</div>
@endsection

<script>
document.addEventListener('DOMContentLoaded', function() {
    const modal = document.getElementById('modalParticipantList');

    modal.addEventListener('show.bs.modal', function (event) {
        const trigger = event.relatedTarget; // tombol yang memicu modal
        if (!trigger) return;

        const courseId = trigger.getAttribute('data-course-id');
        const input = modal.querySelector('#courseIdInput');

        if (courseId && input) {
            input.value = courseId;
            // console.log('✅ Course ID set:', courseId); // debug check
        }
    });

    const modalActivityTask = document.getElementById('modalTestReport');

     modalActivityTask.addEventListener('show.bs.modal', function (event) {
        const trigger = event.relatedTarget; // tombol yang memicu modal
        if (!trigger) return;

        const courseId = trigger.getAttribute('data-course-id');
        const input = modalActivityTask.querySelector('#task_courseIdInput');

        if (courseId && input) {
            input.value = courseId;
            // console.log('✅ Course Task ID set:', courseId); // debug check
        }
    });
    const modalProgressLearner = document.getElementById('modalLearnerProgress');

     modalProgressLearner.addEventListener('show.bs.modal', function (event) {
        const trigger = event.relatedTarget; // tombol yang memicu modal
        if (!trigger) return;

        const courseId = trigger.getAttribute('data-course-id');
        const input = modalProgressLearner.querySelector('#progress_courseIdInput');

        if (courseId && input) {
            input.value = courseId;
            // console.log('✅ Course PRogress ID set:', courseId); // debug check
        }
    });

    const modalFeedbackInstructor = document.getElementById('modalFeedbackInstructor');
    modalFeedbackInstructor.addEventListener('show.bs.modal', function(event) {
        const trigger = event.relatedTarget;
        if (!trigger) return;

        const courseId = trigger.getAttribute('data-course-id');
        const input = modalFeedbackInstructor.querySelector('#feedback_courseIdInput');

        if (courseId && input) {
            input.value = courseId;
            // console.log('✅ Course PRogress ID set:', courseId); // debug check
        }
    });
});
</script>
