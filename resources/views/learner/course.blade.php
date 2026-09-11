@extends('layouts.master')

@section('title', 'Kursus Saya')

@section('content')
<div class="container py-4">
    <div class="d-flex flex-column mb-4 border-bottom pb-3 align-items-center text-center">
        <h2 class="fw-bold text-dark mb-1">Kursus Saya (Learner)</h2>
        <small class="text-muted">Informasi kursus Anda yang telah tergabung.</small>
    </div>

    <div class="row g-4">
        @forelse($enrollments as $enroll)
            <div class="col-md-3 col-sm-6">
                <div class="card shadow-sm h-100 course-card border-0">
                    <div class="card-body d-flex flex-column justify-content-between">
                        <!-- Thumbnail -->
                        <div class="mb-3 bg-light d-flex justify-content-center align-items-center overflow-hidden" 
                            style="height:140px; border:1px solid #ddd;">
                            @if($enroll->course && $enroll->course->course_image)
                                <img src="{{ asset('assets/img/course/'.$enroll->course->course_image) }}" 
                                    alt="Thumbnail" 
                                    class="img-fluid"
                                    style="height:100%; width:100%; object-fit:contain;">
                            @else
                                <span class="text-muted">No Thumbnail</span>
                            @endif
                        </div>

                        <!-- Judul -->
                        <h6 class="fw-bold text-center mb-3">
                            {{ $enroll->course->course_title ?? '-' }}
                        </h6>

                        <!-- Status Akses -->
                        @php
                            $isOpened = $enroll->is_opened ?? 0;
                            $lastAccess = $enroll->last_access 
                                ? \Carbon\Carbon::parse($enroll->last_access)->format('d M Y H:i')
                                : null;
                        @endphp
                        <div class="d-flex flex-column align-items-center mb-2">
                            @if(is_null($enroll->is_passed))
                                {{-- 🔹 Belum ada submission → tidak tampil apa-apa --}}
                            @elseif($enroll->is_passed)
                                <span class="badge bg-success">✅ Lulus</span>
                            @else
                                <span class="badge bg-danger">✘ Belum Lulus</span>
                            @endif
                        </div>
                        <div class="d-flex flex-column align-items-center mb-2">
                            @if($isOpened)
                                <span class="badge bg-success mb-1">Sudah Dibuka</span>
                                @if($lastAccess)
                                    <small class="text-muted">Terakhir diakses: {{ $lastAccess }}</small>
                                @endif
                            @else
                                <span class="badge bg-secondary mb-1">Belum Dibuka</span>
                            @endif
                        </div>
                        <!-- Progress -->
                        @php
                            $progress = $enroll->progress ?? 0;
                            $color = 'bg-danger';
                            if ($progress >= 70) $color = 'bg-success';
                            elseif ($progress >= 30) $color = 'bg-warning';
                        @endphp
                        <div class="mb-2">
                            <div class="progress" style="height: 8px;">
                                <div class="progress-bar {{ $color }}" role="progressbar" 
                                    style="width: {{ $progress }}%;" 
                                    aria-valuenow="{{ $progress }}" aria-valuemin="0" aria-valuemax="100">
                                </div>
                            </div>
                            <small class="text-muted">{{ $progress }}% Selesai</small>
                        </div>
                    </div>

                    <div class="card-footer bg-white border-0">

                        {{-- PRIORITY 1 : COMPLETED (override SEMUA kondisi waktu) --}}
                        @if($enroll->progress >= 100)

                            <a href="{{ route('learner.course-enrolled', $enroll->course->course_id) }}" 
                            class="btn btn-success w-100">
                                ✅ {{ json_lang('Completed') }}
                                <br>
                                <small class="text-light opacity-75">
                                    ({{ $enroll->start_formatted }} – {{ $enroll->end_formatted }})
                                </small>
                            </a>

                        {{-- PRIORITY 2 : COURSE ENDED (progress belum 100) --}}
                        @elseif($enroll->period_message === 'Telah Berakhir')

                           <a href="{{ route('learner.course-enrolled', $enroll->course->course_id) }}" class="btn btn-secondary w-100">
                                🛑 Preview Course

                                @if($enroll->start_formatted || $enroll->end_formatted)
                                    <br><small class="text-light">
                                        ({{ $enroll->start_formatted }} – {{ $enroll->end_formatted }})
                                    </small>
                                @endif
                            </a>

                        {{-- PRIORITY 3 : NOT STARTED --}}
                        @elseif($enroll->period_message === 'Belum dimulai')

                            <button type="button" class="btn btn-secondary w-100" disabled>
                                ⏳ {{ json_lang('Course Not Started Yet') }}
                                <br>
                                <small class="text-light">
                                    ({{ $enroll->start_formatted }} – {{ $enroll->end_formatted }})
                                </small>
                            </button>

                        {{-- PRIORITY 4 : COURSE AKTIF --}}
                        @else

                            {{-- Sudah pernah buka --}}
                            @if($enroll->is_opened)
                                <a href="{{ route('learner.course-enrolled', $enroll->course->course_id) }}" 
                                class="btn btn-warning w-100">
                                    🔁 {{ json_lang('Continue Progress') }}
                                </a>

                            {{-- Pertama kali masuk --}}
                            @else
                                <a href="{{ route('learner.course-enrolled', $enroll->course->course_id) }}" 
                                class="btn btn-primary w-100">
                                    🚀 {{ json_lang('Enter Course') }}
                                </a>
                            @endif

                        @endif
                    </div>
                </div>
            </div>
        @empty
            <div class="col-12">
                <div class="alert alert-info text-center">
                    Kamu belum mengikuti kursus apapun.
                </div>
            </div>
        @endforelse
    </div>
</div>

{{-- Animasi --}}
<style>
    .course-card {
        opacity: 0;
        transform: translateY(20px);
        transition: all 0.5s ease;
    }
    .course-card.show {
        opacity: 1;
        transform: translateY(0);
    }
</style>

<script>
    document.addEventListener("DOMContentLoaded", function () {
        const cards = document.querySelectorAll(".course-card");
        cards.forEach((card, i) => {
            setTimeout(() => {
                card.classList.add("show");
            }, i * 150); // delay 150ms tiap card biar smooth
        });
    });
</script>
@endsection
