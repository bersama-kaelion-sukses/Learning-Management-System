@extends('layouts.master')

@section('title', 'Detail Kursus')

@section('content')
<div class="container mb-4">

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
                            style="max-height:150px; object-fit: contain;">
                    @else
                        <div class="bg-light border d-flex align-items-center justify-content-center rounded w-100" 
                            style="height:150px;">
                            <span class="text-muted">No Image</span>
                        </div>
                    @endif
                        {{-- Tombol kembali --}}
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
                                        <span class="fw-semibold">{{ $learnerCount }}</span>
                                    </div>
                                    <button class="btn btn-sm btn-outline-secondary"
                                            data-bs-toggle="modal"
                                            data-bs-target="#viewLearnersModal">
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

    <!-- Row 2: Pratinjau & Materi -->
    @php
        use App\Models\CourseWeekModule;
        use App\Models\CourseWeekItem;

        $modules = CourseWeekModule::where('course_id', $course->course_id)
            ->orderBy('week_order', 'asc')
            ->get();
    @endphp
    <div class="card shadow-sm">
        <div class="card-body">
            <div class="row g-3">
                @php
                    $icons = [
                        1 => '🎬', // Video
                        2 => '📄', // PDF / Dokumen
                        3 => '📝', // Esai
                        4 => '❓', // Pilihan Ganda (Quiz)
                        5 => '💬', // Forum / Diskusi Umum
                        6 => '🎓', // Sertifikat / Kelulusan
                        7 => '💭',  // Diskusi lain
                        8 => '🎧' // Audio
                    ];
                @endphp
               <!-- Left: Pratinjau -->
                <div class="col-md-3 d-flex flex-column">
                    <div class="border rounded p-3 bg-light d-flex flex-column flex-grow-1 position-relative">
                        <h5 class="mb-3 text-center">Pratinjau Kursus</h5>

                        <div class="accordion flex-grow-1" id="materiAccordion" style="overflow-y:auto; max-height:60vh;">
                            @forelse($modules as $module)
                                <div class="accordion-item mb-2">
                                    <h2 class="accordion-header" id="materi{{ $module->course_week_id }}Heading">
                                        <button class="accordion-button collapsed py-2" type="button"
                                                data-bs-toggle="collapse"
                                                data-bs-target="#materi{{ $module->course_week_id }}Items">
                                                {{ str_replace('Materi', 'Bagian', $module->course_week_title ?? "Bagian " . $module->week_order) }}
                                        </button>
                                    </h2>

                                    <div id="materi{{ $module->course_week_id }}Items"
                                        class="accordion-collapse collapse"
                                        data-bs-parent="#materiAccordion">
                                        <div class="accordion-body p-0">
                                            <ul class="list-group list-group-flush">
                                                @php
                                                    $items =CourseWeekItem::where('course_week_id', $module->course_week_id)
                                                        ->where('course_id', $course->course_id)
                                                        ->orderBy('item_order', 'asc')
                                                        ->get();
                                                @endphp

                                                @forelse($items as $item)
                                                    <li class="list-group-item list-group-item-action item-option"
                                                        data-item-id="{{ $item->item_id }}"
                                                        data-name="{{ $item->course_item_name }}"
                                                        data-desc="{{ $item->course_describe }}"
                                                        data-file="{{ $item->course_media }}"
                                                        data-type="{{ $item->course_item_type }}">
                                                        <span class="me-2">
                                                            {{ $icons[$item->course_item_type] ?? '📌' }}
                                                        </span>
                                                        <span>{{ $item->course_item_name }}</span>
                                                    </li>
                                                @empty
                                                    <li class="list-group-item text-muted fst-italic">
                                                        Belum ada sub-materi
                                                    </li>
                                                @endforelse
                                            </ul>
                                        </div>
                                    </div>
                                </div>
                            @empty
                                <div class="text-center mt-2 mb-0 fst-italic py-3">
                                    📘 Kursus ini belum mempunyai materi.
                                </div>
                            @endforelse
                        </div>

                        <!-- Tombol Thread Diskusi -->
                        <div class="mt-auto pt-3">
                            <div class="bg-secondary text-center rounded shadow-sm">
                                <button id="add-thread-forum"
                                        class="btn btn-warning btn-md w-100"
                                        data-bs-toggle="modal"
                                        data-bs-target="#ThreadDiscussionLearnerView"
                                        data-course-id="{{ $course->course_id }}">
                                     Buka Thread Diskusi
                                </button>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Right: Materi (Viewer) -->
                <div class="col-md-9 d-flex flex-column">
                    <div id="course-detail-panel" 
                         class="flex-fill border rounded p-4 bg-white shadow-sm"
                         style="min-height:50vh; max-height:50vh; overflow:auto;">
                        <h5 class="mb-3">👉 Pilih materi di kiri untuk melihat detail</h5>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
<!-- Modal Detail Learner -->
<div class="modal fade" id="viewLearnersModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-scrollable">
        <div class="modal-content border-0 shadow-sm">
            <div class="modal-header bg-light">
                <h5 class="modal-title fw-bold">Daftar Learner Tergabung</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>

            <div class="modal-body">
                @if($course->enrollments->count() > 0)
                    <ul class="list-group list-group-flush">
                        @foreach($course->enrollments as $enroll)
                            @php 
                                $user = $enroll->user; 

                                if ($enroll->last_access) {
                                    $parsed = \Carbon\Carbon::parse($enroll->last_access)->timezone('Asia/Jakarta');
                                    $lastAccess = $parsed->translatedFormat('d F Y, H:i') . ' (' . $parsed->diffForHumans() . ')';
                                } else {
                                    $lastAccess = 'Belum pernah akses';
                                }
                            @endphp

                            <li class="list-group-item d-flex justify-content-between align-items-center">
                                <div>
                                    <strong>{{ $user->full_name ?? '-' }}</strong><br>
                                    <small class="text-muted">
                                        {{ $user->emp_id ?? '-' }} | {{ $user->division_label ?? '-' }}
                                    </small><br>
                                    <small class="text-muted fst-italic">
                                        ⏰ Akses terakhir: {{ $lastAccess }}
                                    </small>
                                </div>

                                <span class="badge {{ $enroll->last_access ? 'bg-success' : 'bg-secondary' }}">
                                    {{ $enroll->last_access ? 'Aktif' : 'Belum Aktif' }}
                                </span>
                            </li>
                        @endforeach
                    </ul>
                @else
                    <p class="text-muted text-center mb-0">
                        Belum ada learner tergabung pada kursus ini.
                    </p>
                @endif
            </div>
        </div>
    </div>
</div>


<!-- Modal Fullscreen Thread -->
<div class="modal fade" id="ThreadDiscussionLearnerView" tabindex="-1" aria-hidden="true" data-bs-backdrop="static" data-bs-keyboard="false">
    <div class="modal-dialog modal-lg modal-dialog-scrollable">
        <div class="modal-content">

            <div class="modal-header bg-warning">
                <h5 class="modal-title fw-semibold">💬 Diskusi Thread Kursus</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Tutup"></button>
            </div>

            <div class="modal-body">
                <!-- Hidden Course ID -->
                <input type="hidden" id="Thread_course_id_View" value="{{ $course->course_id ?? '' }}">

                <!-- Thread List -->
                <div id="ForumThreadList_View" class="container-fluid">
                    @if(isset($forumThreads) && $forumThreads->count() > 0)
                        @foreach($forumThreads as $thread)
                            <div class="card mb-4 shadow-sm border-0">
                                <div class="card-header bg-white border-bottom-0">
                                    <div class="d-flex justify-content-between align-items-center">
                                        <h6 class="fw-bold mb-0 text-dark">
                                            {{ $loop->iteration }}. {{ $thread->topic_title ?? '(Tanpa Judul)' }}
                                        </h6>
                                        <span class="badge bg-light text-dark border shadow-sm fs-6">
                                            👤 {{ $thread->creator->full_name ?? 'Unknown' }}
                                        </span>
                                    </div>
                                </div>

                                <div class="card-body pb-2">
                                    <p class="fw-semibold mb-1">{{ $thread->forum_title ?? '-' }}</p>
                                    <p class="text-muted mb-2">{{ $thread->forum_question ?? '' }}</p>

                                   {{-- Lampiran --}}
                                    @if($thread->attachment_type && $thread->attachment_path)
                                        @php
                                            $type = strtolower($thread->attachment_type);
                                            $path = trim($thread->attachment_path);
                                            $embed = null;
                                        @endphp

                                        {{-- 📄 PDF --}}
                                        @if($type === 'pdf')
                                            <div class="text-end p-2 bg-light border-top">
                                                <a href="{{ asset($path) }}" target="_blank" class="btn btn-sm btn-outline-secondary">
                                                    📄 Buka PDF di Tab Baru
                                                </a>
                                            </div>
                                            <div class="mt-3 border rounded shadow-sm overflow-hidden">
                                                <div class="ratio ratio-16x9">
                                                    <iframe 
                                                        src="{{ asset($path) }}" 
                                                        title="PDF Viewer" 
                                                        style="border:0; width:100%; height:100%;" 
                                                        allowfullscreen>
                                                    </iframe>
                                                </div>
                                            </div>

                                        {{-- 🎥 Video --}}
                                        @elseif($type === 'video')
                                            <div class="ratio ratio-16x9 mt-3">
                                                <video controls preload="metadata" playsinline class="w-100 rounded" style="background:#000;">
                                                    <source src="{{ asset($path) }}" type="video/mp4">
                                                    Browser tidak mendukung video.
                                                </video>
                                            </div>

                                        {{-- 🖼️ Gambar --}}
                                        @elseif($type === 'image')
                                            <img src="{{ asset($path) }}" class="img-fluid rounded mb-3">

                                        {{-- 🔗 URL (YouTube / Google Drive / eksternal) --}}
                                        @elseif($type === 'url')
                                            @php
                                                $url = $path;
                                                $embed = ''; 

                                                // YouTube
                                                if (str_contains($url, 'youtube.com') || str_contains($url, 'youtu.be')) {
                                                    preg_match('/[?&]v=([^&]+)/', $url, $m1);
                                                    preg_match('/youtu\.be\/([^?]+)/', $url, $m2);
                                                    preg_match('/embed\/([^?]+)/', $url, $m3);
                                                    $videoId = $m1[1] ?? $m2[1] ?? $m3[1] ?? null;
                                                    if ($videoId) {
                                                        $embed = "<div class='ratio ratio-16x9 mt-3'>
                                                            <iframe src='https://www.youtube.com/embed/{$videoId}' 
                                                                    title='YouTube video' frameborder='0'
                                                                    allow='accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture'
                                                                    allowfullscreen></iframe></div>";
                                                    }
                                                }

                                                // Google Drive
                                                elseif (str_contains($url, 'drive.google.com')) {
                                                    preg_match('/\/d\/([a-zA-Z0-9_-]+)/', $url, $m1);
                                                    preg_match('/[?&]id=([a-zA-Z0-9_-]+)/', $url, $m2);
                                                    $fileId = $m1[1] ?? $m2[1] ?? null;
                                                    if ($fileId) {
                                                        $embed = "<div class='ratio ratio-16x9 mt-3'>
                                                            <iframe src='https://drive.google.com/file/d/{$fileId}/preview' 
                                                                    title='Google Drive preview' frameborder='0'
                                                                    allow='autoplay' allowfullscreen></iframe></div>";
                                                    }
                                                }

                                                // Fallback eksternal link biasa
                                                if (!$embed) {
                                                    $embed = "<a href='{$url}' target='_blank' class='btn btn-sm btn-outline-secondary mt-2'>
                                                                🔗 Buka Tautan Eksternal
                                                            </a>";
                                                }
                                            @endphp

                                            {!! $embed !!}

                                        @endif
                                    @endif
                                    <small class="text-secondary">
                                        Dibuat {{ $thread->created_at->diffForHumans() }}
                                    </small>
                                </div>
                            </div>
                        @endforeach
                    @else
                        <div class="text-center mb-0">
                            Belum ada thread diskusi untuk kursus ini.
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>


@endsection
