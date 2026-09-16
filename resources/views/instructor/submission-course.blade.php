@extends('layouts.master')

@section('title', 'Kursus Saya (Instructor)')

@section('content')
<div class="container py-4">
        <!-- Row 1: Header Course -->
       <div class="card shadow-sm mb-4">
        <div class="card-body">
            <div class="row align-items-stretch">
                <!-- Gambar Course -->
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
                                <!-- Maksimal Peserta -->
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

                                <!-- Jumlah Learner + Tombol -->
                                <div class="d-flex justify-content-between align-items-center">
                                    <div>
                                        <small class="text-muted d-block">Jumlah Learner Tergabung</small>
                                        <span class="fw-semibold">{{ $course->enrollments->count() }}</span>
                                    </div>
                                    <button class="btn btn-sm btn-outline-secondary"
                                            data-bs-toggle="modal"
                                            data-bs-target="#viewLearnersCourseModal">
                                        Lihat Detail
                                    </button>
                                </div>
                            </div>
                        </div>
                        <butto class="btn btn-sm btn-warning" data-bs-toggle="modal" data-bs-target="#threadModal" data-course-id="{{ $course->course_id }}"> Lihat Thread </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="row">
        <!-- Sidebar kiri: daftar submission -->
        <div class="col-md-4" style="max-height:750px; overflow-y:auto;">
            @php
                $icons = [
                    1 => '🎬', // Video
                    2 => '📄', // PDF / Dokumen
                    3 => '📝', // Esai
                    4 => '❓', // Pilihan Ganda (Quiz)
                    5 => '💬', // Forum / Diskusi
                    6 => '🎓', // Sertifikat / Kelulusan
                    7 => '💭', // Diskusi lain
                    8 => '🎧'  // Audio
                ];
            @endphp

            @foreach($course->weeks as $week)
                <div class="card shadow-sm mb-3">
                    <div class="card-header fw-bold">
                        {{ $course->course_title }} + 
                        {{ str_replace('Materi', 'Bagian', $week->course_week_title ?? ('Bagian ' . $week->week_order)) }}
                    </div>
                    <div class="card-body">
                        <div class="list-group">
                            @forelse($week->items as $item)
                                @php
                                    $icon = $icons[$item->course_item_type] ?? '📌';
                                @endphp
                                <a href="#"
                                   class="list-group-item list-group-item-action d-flex justify-content-between align-items-center course-item"
                                   data-item-id="{{ $item->item_id }}"
                                   data-item-type="{{ $item->course_item_type }}">
                                    <span>{{ $icon }} {{ $item->course_item_name }}</span>
                                </a>
                            @empty
                                <p class="text-muted">Belum ada item di week ini.</p>
                            @endforelse
                        </div>
                    </div>
                </div>
            @endforeach
        </div>
       <div class="col-md-8" id="submissionPage" data-course-id="{{ $course->course_id }}">
        <div class="row g-3">
            <!-- Kartu Pertanyaan -->
            <!-- <div class="col-12">
                <div class="card shadow-sm">
                    <div class="card-header fw-bold">📑 Pertanyaan Item</div>
                    <div class="card-body" id="questionBox" style="max-height:200px; overflow-y:auto;">
                        <p class="text-muted fst-italic">
                            Pertanyaan item akan muncul di sini setelah memilih item kursus.
                        </p>
                    </div>
                </div>
            </div> -->

            <!-- Kartu Informasi Submission -->
            <div class="col-12">
                <div class="card shadow-sm">
                    <div class="card-header fw-bold">📑 Informasi Submission Learners</div>
                    <div class="card-body" style="max-height: 700px; overflow-y:auto;">
                        <div id="submissionListLearner">
                            <p class="text-muted fst-italic">
                                Klik salah satu item kursus di sebelah kiri untuk menampilkan daftar submission semua learner.
                            </p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    </div>
    <!-- Modal Detail Learner -->
    <div class="modal fade" id="viewLearnersCourseModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-lg modal-dialog-scrollable">
            <div class="modal-content">
                <div class="modal-header">
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

                                <li class="list-group-item d-flex justify-content-between align-items-center">
                                    <div>
                                        <strong>{{ $user->full_name ?? '-' }}</strong><br>
                                        <small class="text-muted">
                                            {{ $user->emp_id ?? '-' }} | {{ $user->division_label ?? '-' }}
                                        </small><br>
                                        <small class="text-muted fst-italic">
                                            ⏰ {{ $lastAccess }}
                                        </small>
                                    </div>
                                    {!! $statusBadge !!}
                                </li>
                            @endforeach
                        </ul>
                    @else
                        <p class="text-muted mb-0">Belum ada learner tergabung pada kursus ini.</p>
                    @endif
                </div>
            </div>
        </div>
    </div>

    <!-- Modal Thread -->
     <div class="modal fade" id="threadModal" tabindex="-1" data-bs-backdrop="static" data-bs-keyboard="false">
        <div class="modal-dialog modal-lg modal-dialog-scrollable">
            <div class="modal-content">
                <div class="modal-header bg-warning">
                    <h5 class="modal-title">💬 Diskusi Thread</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div id="threadContent" class="text-muted">⏳ Memuat thread...</div>
                </div>
            </div>
        </div>
    </div>
@endsection
