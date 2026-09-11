@extends('layouts.master')

@section('title', 'Jelajahi Kursus')

@section('content')
<div class="container py-4">
    <div class="d-flex flex-column mb-4 border-bottom pb-3 align-items-center text-center">
        <h2 class="fw-bold text-dark mb-1">Jelajahi Kursus </h2>
        <small class="text-muted"> Lihat dan gabung kursus yang tersedia </small>
    </div>

    <!-- Filter/Search Bar -->
    <div class="card shadow-sm mb-4 border-0">
        <div class="card-body">
            <form id="searchForm" class="row g-3 align-items-center" method="GET" action="{{ route('learner.explore') }}">
                <div class="col-md-4">
                    <input type="text" id="searchInput" name="q" value="{{ $q ?? '' }}" class="form-control" placeholder="Cari kursus...">
                </div>
            </form>
        </div>
    </div>

    <!-- Grid Kursus -->
    <div class="row g-4">
        @forelse ($courses as $index => $course)
            <div class="col-md-3 col-sm-6">
                <div class="card shadow-sm h-100 border-0 course-card" style="--delay: {{ $index * 0.1 }}s">
                    <div class="card-body d-flex flex-column">
                        
                        <!-- Thumbnail -->
                        <div class="mb-3 bg-light d-flex justify-content-center align-items-center rounded overflow-hidden course-thumb">
                            <img src="{{ $course->course_image_url }}" 
                                 class="img-fluid" 
                                 alt="cover">
                        </div>
                        
                        <!-- Judul -->
                        <h6 class="fw-bold text-center text-truncate" title="{{ $course->course_title }}">
                            {{ $course->course_title }}
                        </h6>

                        <!-- Deskripsi -->
                        <p class="text-muted small mb-3 flex-grow-1 text-truncate-multiline">
                            {{ Str::limit($course->course_description, 90) }}
                        </p>

                        <!-- Tombol -->
                        <div class="mt-auto">
                            <a href="{{ route('learner.detail-course', $course->course_id) }}" 
                               class="btn btn-secondary w-100">
                                Lihat Detail
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        @empty
            <div class="col-12 text-center text-muted">Belum ada Kursus Publik.</div>
        @endforelse
    </div>
</div>

<!-- langsung include CSS biar pasti ke-render -->
<style>
    /* Animasi hover */
    .course-card {
        transition: transform 0.2s ease, box-shadow 0.2s ease;
        opacity: 0;
        transform: translateY(20px);
        animation: fadeInUp 0.6s forwards;
        animation-delay: var(--delay, 0s);
    }
    .course-card:hover {
        transform: translateY(-5px);
        box-shadow: 0 6px 15px rgba(0,0,0,0.15);
    }

    /* Thumbnail kursus */
    .course-thumb {
        height: 150px;
        border: 1px solid #ddd;
    }
    .course-thumb img {
        max-height: 100%;
        max-width: 100%;
        object-fit: cover;
    }

    /* Multiline truncate (2 baris) */
    .text-truncate-multiline {
        display: -webkit-box;
        -webkit-line-clamp: 2; 
        -webkit-box-orient: vertical;
        overflow: hidden;
    }

    /* Entry animation */
    @keyframes fadeInUp {
        to {
            opacity: 1;
            transform: translateY(0);
        }
    }
</style>

<!-- JS untuk auto submit search -->
<script>
document.addEventListener("DOMContentLoaded", function(){
    const input = document.getElementById("searchInput");
    const form = document.getElementById("searchForm");
    let timer = null;

    input.addEventListener("keyup", function(){
        clearTimeout(timer);
        timer = setTimeout(() => {
            form.submit();
        }, 400); // delay biar ga spam
    });
});
</script>
@endsection
