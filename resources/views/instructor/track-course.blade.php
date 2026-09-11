@extends('layouts.master')

@section('title', 'Kursus Saya (Instructor)')

@section('content')
    <!-- ========================== -->
    <!-- 📘 Course Header Section -->
    <!-- ========================== -->
    <div class="card shadow-sm mb-4">
        <div class="card-body">
            <div class="row align-items-stretch">
                <!-- 📸 Gambar Course -->
                <div class="col-md-3 text-center p-3">
                    @if($course->course_image)
                        <img src="{{ asset('assets/img/course/'.$course->course_image) }}" 
                             class="img-fluid rounded shadow-sm w-100" 
                             alt="Image of {{ $course->course_title }}" 
                             style="max-height:150px; object-fit:contain;">
                    @else
                        <div class="bg-light border d-flex align-items-center justify-content-center rounded w-100" 
                             style="height:150px;">
                            <span class="text-muted">No Image</span>
                        </div>
                    @endif
                    <button 
                        type="button" 
                        class="btn btn-outline-secondary w-100 mb-3 fw-semibold shadow-sm mt-4"
                        onclick="
                            if (document.referrer && document.referrer !== window.location.href) {
                                window.history.back();
                            } else {
                                window.location.href='{{ route('instructor.course') }}';
                            }
                        ">
                        ⬅️ Kembali
                    </button>
                </div>

                <!-- 📄 Informasi Kursus -->
                <div class="col-md-9 d-flex flex-column justify-content-center">
                    <div class="border bg-light rounded p-3 mb-2 text-center">
                        <h5 class="fw-bold mb-0">{{ $course->course_title }}</h5>
                    </div>

                    <div class="row g-2">
                        <!-- Trainer & Kategori -->
                        <div class="col-md-6">
                            <div class="border bg-white rounded p-3 h-100">
                                <small class="text-muted d-block">Trainer Kursus</small>
                                <span class="fw-semibold">
                                    {{ $trainer->full_name ?? $course->course_trainer_name ?? 'Unknown Trainer' }}
                                </span>
                                <hr>
                                <small class="text-muted d-block">Kategori Kursus</small>
                                <span class="fw-semibold">{{ $course->course_category }}</span>
                            </div>
                        </div>

                        <!-- Max Participant & Learner -->
                        <div class="col-md-6">
                            <div class="border bg-white rounded p-3 h-100">
                                <div class="d-flex justify-content-between align-items-center mb-2">
                                    <div class="small">
                                        <table class="table table-sm mb-0">
                                            <tbody>
                                                <tr>
                                                    <td class="text-muted">Peserta Aktif</td>
                                                    <td class="fw-semibold text-dark">: {{ $course->active_count }}</td>
                                                </tr>
                                                <tr>
                                                    <td class="text-muted">Peserta Tergabung</td>
                                                    <td class="fw-semibold text-dark">: {{ $course->joined_count }}</td>
                                                </tr>
                                                <tr>
                                                    <td class="text-muted">Maksimal Peserta</td>
                                                    <td class="fw-semibold text-dark">: {{ $course->max_participant }}</td>
                                                </tr>
                                            </tbody>
                                        </table>
                                    </div>
                                </div>

                                <hr>

                                <div class="d-flex justify-content-between align-items-center">
                                    <div>
                                        <small class="text-muted d-block">Jumlah Learner Tergabung</small>
                                        <span class="fw-semibold">{{ $course->enrollments->count() }}</span>
                                    </div>
                                    <button class="btn btn-sm btn-outline-secondary"
                                            data-bs-toggle="modal"
                                            data-bs-target="#viewLearnersTrackModal">
                                        Lihat Detail
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- ========================== -->
    <!-- 🎯 Tombol Aksi Atas -->
    <!-- ========================== -->
    <div class="d-flex justify-content-start gap-2 mb-3">
        <button class="btn btn-sm btn-danger" data-bs-toggle="modal" data-bs-target="#learnerRemedialModal" data-course-id="{{ $course->course_id }}">
            🚨 Lihat Learner Remedial
        </button>
        <button class="btn btn-sm btn-warning" data-bs-toggle="modal" data-bs-target="#learnerProgressModal" data-course-id="{{ $course->course_id }}">
            📊 Lihat Learner Progress
        </button>
    </div>

   <!-- ========================== -->
    <!-- 📚 Accordion Materi (Independent Toggle) -->
    <!-- ========================== -->
    <div class="accordion mt-3" id="courseModuleAccordion">
        @forelse($course->weeks as $week)
            <div class="accordion-item">
                <h2 class="accordion-header" id="heading-{{ $week->course_week_id }}">
                    <button class="accordion-button"  {{-- tetap terbuka awalnya --}}
                            type="button"
                            data-bs-toggle="collapse"
                            data-bs-target="#collapse-{{ $week->course_week_id }}"
                            aria-expanded="true"
                            aria-controls="collapse-{{ $week->course_week_id }}">
                        📚 
                        {{ str_replace('Materi', 'Bagian', $week->course_week_title ?? 'Bagian ' . $week->week_order) }}
                        <span class="badge bg-secondary ms-2">
                            {{ $week->items->count() }} Materi
                        </span>
                    </button>
                </h2>

                <div id="collapse-{{ $week->course_week_id }}"
                    class="accordion-collapse collapse show"  {{-- tetap terbuka --}}
                    aria-labelledby="heading-{{ $week->course_week_id }}">
                    {{-- 🚫 data-bs-parent dihapus agar independen --}}
                    <div class="accordion-body">
                        @if($week->items->count() > 0)
                            <ul class="list-group">
                                @foreach($week->items as $item)
                                    <li class="list-group-item d-flex justify-content-between align-items-center">
                                        <div>
                                            <strong>{{ $item->course_item_name ?? 'Untitled Item' }}</strong>
                                            <p class="mb-0 text-muted small">
                                                {{ $item->course_describe ?? 'Tidak ada deskripsi.' }}
                                            </p>
                                        </div>
                                        <button class="btn btn-sm btn-outline-secondary text-nowrap"
                                                data-bs-toggle="modal"
                                                data-bs-target="#learnerModal"
                                                data-item="{{ $item->course_item_name }}"
                                                data-item-id="{{ $item->item_id }}"
                                                data-course-id="{{ $course->course_id }}">
                                            👥 Lihat Learner
                                        </button>
                                    </li>
                                @endforeach
                            </ul>
                        @else
                            <p class="text-muted fst-italic">Belum ada materi pada week ini.</p>
                        @endif
                    </div>
                </div>
            </div>
        @empty
            <p class="text-muted">❌ Belum ada module / week di course ini.</p>
        @endforelse
    </div>


    <!-- ========================== -->
    <!-- 👥 Modal: Tracking per Materi -->
    <!-- ========================== -->
    <div class="modal fade" id="learnerModal" tabindex="-1">
        <div class="modal-dialog modal-xl modal-dialog-scrollable">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">👥 Tracking Learner per Materi</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <h6 id="selectedItemTitle" class="mb-3 text-muted"></h6>
                    <table class="table table-bordered table-striped align-middle">
                        <thead class="table-light">
                            <tr>
                                <th>Nama Learner</th>
                                <th>Submit</th>
                                <th>Tidak Submit</th>
                                <th>Lulus</th>
                                <th>Tidak Lulus</th>
                                <th>Nilai</th>
                                <th>Nilai Lulus</th>
                            </tr>
                        </thead>
                        <tbody id="learnerItemTable">
                            <!-- Akan diisi dinamis lewat JS -->
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <!-- ========================== -->
    <!-- 🟥 Modal: Learner Remedial -->
    <!-- ========================== -->
    <div class="modal fade" id="learnerRemedialModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-xl modal-dialog-scrollable">
            <div class="modal-content">
                <div class="modal-header bg-danger text-white">
                    <h5 class="modal-title fw-bold">🚨 Daftar Learner Remedial</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="table-responsive">
                        <table class="table table-bordered table-striped align-middle">
                            <thead class="table-light">
                                <tr>
                                    <th>Nama Learner</th>
                                    <th>Employee ID</th>
                                    <th>Jumlah Remedial</th>
                                    <th>Aksi</th>
                                </tr>
                            </thead>
                            <tbody id="remedialLearnerTable">
                                <tr>
                                    <td colspan="5" class="text-center text-muted">⏳ Memuat data...</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- ========================== -->
    <!-- 🟦 Modal: Learner Progress -->
    <!-- ========================== -->
    <div class="modal fade" id="learnerProgressModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-xl modal-dialog-scrollable">
            <div class="modal-content">
                <div class="modal-header bg-primary text-white">
                    <h5 class="modal-title fw-bold">📊 Progress Learner</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="table-responsive">
                        <table class="table table-bordered table-striped align-middle">
                            <thead class="table-light">
                                <tr>
                                    <th>Nama Learner</th>
                                    <th>Employee ID</th>
                                    <th>Progress (%)</th>
                                    <th>Status</th>
                                </tr>
                            </thead>
                            <tbody id="progressLearnerTable">
                                <tr>
                                    <td colspan="4" class="text-center text-muted">⏳ Memuat data...</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- ========================== -->
    <!-- 🧍 Modal: Daftar Learner -->
    <!-- ========================== -->
    <div class="modal fade" id="viewLearnersTrackModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-lg modal-dialog-scrollable">
            <div class="modal-content shadow-sm border-0">
                <div class="modal-header bg-light">
                    <h5 class="modal-title fw-bold">Daftar Learner Tergabung</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>

                <div class="modal-body">
                    @if($course->enrollments->count() > 0)
                        <ul class="list-group">
                            @foreach($course->enrollments as $enroll)
                                @php
                                    $user = $enroll->user;

                                    // Format waktu akses terakhir
                                    if ($enroll->last_access) {
                                        $parsed = \Carbon\Carbon::parse($enroll->last_access)->timezone('Asia/Jakarta');
                                        $lastAccess = $parsed->translatedFormat('d F Y, H:i') . ' (' . $parsed->diffForHumans() . ')';
                                    } else {
                                        $lastAccess = 'Belum pernah akses';
                                    }

                                    // Status aktif berdasarkan is_opened
                                    $isActive = $enroll->is_opened ?? 0;
                                    $statusBadge = $isActive
                                        ? '<span class="badge bg-success">Aktif</span>'
                                        : '<span class="badge bg-secondary">Belum Aktif</span>';
                                @endphp

                                <li class="list-group-item d-flex justify-content-between align-items-center flex-wrap py-3">
                                    <div class="me-3">
                                        <strong class="d-block">{{ $user->full_name ?? '-' }}</strong>
                                        <small class="text-muted d-block">
                                            {{ $user->emp_id ?? '-' }} | {{ $user->division_label ?? '-' }}
                                        </small>
                                        <small class="text-muted fst-italic d-block mt-1">
                                            ⏰ {{ $lastAccess }}
                                        </small>
                                    </div>
                                    {!! $statusBadge !!}
                                </li>
                            @endforeach
                        </ul>
                    @else
                        <p class="text-muted mb-0 text-center">Belum ada learner tergabung pada kursus ini.</p>
                    @endif
                </div>
            </div>
        </div>
    </div>

@endsection
