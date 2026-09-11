@extends('layouts.master')

@section('title', 'Detail Course')

@section('content')
<div class="container mt-4">
    <div class="d-flex flex-column mb-4 border-bottom pb-3 align-items-center text-center">
        <h2 class="fw-bold text-dark mb-1">Detail Kursus </h2>
        <small class="text-muted">Informasi detail materi ajar kursus.  </small>
    </div>
    <div class="row">
        <!-- Kolom Kiri: Gambar + Info Course -->
        <div class="col-md-4 mb-3">
            <div class="border bg-white p-3 mb-3 rounded shadow-sm">
                {{-- Cover course --}}
                <img src="{{ $course->course_image_url }}" 
                    alt="course_picture" 
                    class="img-fluid rounded w-100 mb-3" 
                    style="max-height:220px; object-fit:contain;">

                {{-- Judul --}}
                <h4 class="fw-bold text-dark mb-2">{{ $course->course_title }}</h4>

                {{-- Kategori --}}
                <p class="text-secondary fst-italic mb-2"> Kategori Kursus : 
                    {{ $course->course_category ?? 'Kategori' }}
                </p>
                {{-- Trainer --}}
                <p class="text-muted mb-0">
                    👨‍🏫 Trainer: <span class="fw-semibold">{{ $course->course_trainer_name ?? 'Mentor' }}</span>
                </p>
                {{-- Info kapasitas --}}
                <p class="text-muted mb-1">
                    👥 Kapasitas: <span class="fw-semibold">{{ $course->max_participant ?? '-' }}</span> Learner
                </p>
                {{-- Tergabung --}}
                <p class="text-muted mb-1">
                    ✅ Tergabung: <span class="fw-semibold">{{ $course->enrollment ?? '-' }}</span> Learner
                </p>
                {{-- Periode --}}
                <p class="text-muted mb-0">
                    🗓️ Periode: <span class="fw-semibold">{{ $course->period }}</span>
                </p>
                <button 
                    type="button" 
                    class="btn btn-outline-secondary w-100 mb-3 fw-semibold shadow-sm mt-4"
                    onclick="
                        if (document.referrer && document.referrer !== window.location.href) {
                            window.history.back();
                        } else {
                            window.location.href='{{ route('learner.course') }}';
                        }
                    ">
                    ⬅️ Kembali
                </button>
            </div>

            <div class="border bg-white p-3 rounded shadow-sm">
                <h6 class="fw-bold">Deskripsi Kursus</h6>
                 <p id="course-description" class="text-muted mb-0" style="max-height:500px; overflow-x:auto">
                    {{ $course->course_describe ?? '—' }}
                </p>
            </div>

            <!-- Tombol join -->
            <div class="mt-3">
                @if(session('success'))
                    <div class="alert alert-success text-center">{{ session('success') }}</div>
                @elseif(session('info'))
                    <div class="alert alert-info text-center">{{ session('info') }}</div>
                @endif

                {{-- ====================================================== --}}
                {{-- 🔹 Logika Utama Berdasarkan Status Periode Kursus --}}
                {{-- ====================================================== --}}
                @if($course->period_status === 'not_started')
                    {{-- 🕒 Belum dimulai --}}
                    @if($enrollment)
                        {{-- Sudah join tapi belum dibuka --}}
                        <button class="btn btn-secondary w-100" disabled>
                            🔒 Course masih ditutup
                            @if($course->start_course)
                                <br>
                                <small class="text-light opacity-75">
                                    Dibuka pada {{ \Carbon\Carbon::parse($course->start_course)->format('d M Y') }}
                                </small>
                            @endif
                        </button>
                    @else
                        {{-- Belum join tapi boleh daftar --}}
                        <form method="POST" action="{{ route('learner.request-join', ['courseId' => $course->course_id]) }}">
                            @csrf
                            <button type="submit" class="btn btn-success w-100">
                                Gabung Kursus
                                @if($course->start_course)
                                    <br>
                                    <small class="text-light opacity-75">
                                        Dibuka pada {{ \Carbon\Carbon::parse($course->start_course)->format('d M Y') }}
                                    </small>
                                @endif
                            </button>
                        </form>
                    @endif

                @elseif($course->period_status === 'ended')
                    {{-- 🛑 Sudah berakhir --}}
                    <button class="btn btn-danger w-100" disabled>
                        🛑 Kursus Telah Berakhir
                        @if($course->end_course)
                            <br>
                            <small class="text-light opacity-75">
                                Berakhir {{ \Carbon\Carbon::parse($course->end_course)->format('d M Y') }}
                            </small>
                        @endif
                    </button>

                @elseif($course->period_status === 'active')
                    {{-- 🟢 Sedang berlangsung --}}
                    @if($enrollment)
                        <a href="{{ route('learner.course-enrolled', $course->course_id) }}" 
                        class="btn btn-warning w-100">
                            Masuk Course
                        </a>
                    @else
                        <form method="POST" action="{{ route('learner.request-join', ['courseId' => $course->course_id]) }}">
                            @csrf
                            <button type="submit" class="btn btn-success w-100">
                                Gabung Kursus
                            </button>
                        </form>
                    @endif
                @endif
            </div>

        </div>

        <!-- Kolom Kanan: Pratinjau Materi -->
        <div class="col-md-8 mb-3">
            <div class="border bg-white p-3 h-100">
                <h5 class="mb-3 text-center">Materi Kursus</h5>
                <div class="accordion" id="materiAccordion">
                    @forelse($modules as $m)
                        @php
                            $collapseId = 'm_'.$m->course_week_id;
                            $childItems = $items->get($m->course_week_id) ?? collect();
                        @endphp
                        <div class="accordion-item mb-2">
                            <h2 class="accordion-header" id="h_{{ $m->course_week_id }}">
                                <button class="accordion-button collapsed" type="button"
                                        data-bs-toggle="collapse" data-bs-target="#{{ $collapseId }}">
                                        {{ str_replace('Materi', 'Bagian', $m->course_week_title ?? 'Bagian') }}
                                </button>
                            </h2>
                            <div id="{{ $collapseId }}" class="accordion-collapse collapse" data-bs-parent="#materiAccordion">
                                <div class="accordion-body">
                                    @if($childItems->count())
                                        <ul class="list-group">
                                            @foreach($childItems as $it)
                                                <li class="list-group-item">
                                                    {{ $it->course_item_name }}
                                                    @if(!empty($it->course_describe))
                                                        <small class="text-muted d-block">{{ $it->course_describe }}</small>
                                                    @endif
                                                </li>
                                            @endforeach
                                        </ul>
                                    @else
                                        <div class="text-muted">Belum ada item pada modul ini.</div>
                                    @endif
                                </div>
                            </div>
                        </div>
                    @empty
                        <div class="text-muted text-center">Belum ada modul/sekat.</div>
                    @endforelse
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
