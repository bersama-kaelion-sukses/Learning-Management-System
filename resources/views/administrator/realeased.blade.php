@extends('layouts.master')

@section('title', 'Learner Submission Release')

@section('content')
<div class="container py-4">
    <div class="d-flex flex-column mb-4 border-bottom pb-3 align-items-center text-center">
        <h2 class="fw-bold text-dark mb-1">Leaner Submission Release </h2>
        <small class="text-muted">Kekola jawaban pengguna yang mengalami gangguan</small>
    </div>
    <!-- ====================== -->
    <!-- CARD: PILIH COURSE -->
    <!-- ====================== -->
    <div class="card shadow-sm mb-4">
        <div class="card-header fw-bold bg-light">
            📚 Pilih Course
        </div>

        <div class="card-body">
            @if($courses->isEmpty())
                <div class="alert alert-warning text-center mb-0">
                    Tidak ada course yang tersedia.
                </div>
            @else
                <form method="GET" action="{{ route('administrator.learner-submission-realeased') }}">
                    <div class="mb-3">
                        <label for="courseSelect" class="form-label fw-semibold">Pilih Course</label>
                        <select id="courseSelect" name="course_id" class="form-select">
                            <option value="">-- Pilih Course --</option>
                            @foreach($courses as $course)
                                <option value="{{ $course->course_id }}"
                                    {{ $selectedCourseId == $course->course_id ? 'selected' : '' }}>
                                    {{ $course->course_title }}
                                    @if(!empty($course->course_trainer_name))
                                        – {{ $course->course_trainer_name }}
                                    @elseif(!empty($course->trainer?->full_name))
                                        – {{ $course->trainer->full_name }}
                                    @endif
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div class="text-end">
                        <button type="submit" class="btn btn-secondary px-4 btn-sm">
                            Terapkan
                        </button>
                    </div>
                </form>
            @endif
        </div>
    </div>

    <!-- ====================== -->
    <!-- LAYOUT: DETAIL COURSE -->
    <!-- ====================== -->
    <div class="row g-4" id="releasedLayout">

        <!-- ========== SIDEBAR: Modul & Item ========== -->
        <div class="col-md-4">
            <div class="card shadow-sm h-100">
                <div class="card-header fw-bold bg-light">
                    📘 Daftar Modul & Item
                </div>

                <div class="card-body p-3" style="max-height: 700px; overflow-y: auto;" id="moduleList">
                    @if(!$selectedCourseId)
                        <div class="text-center mb-0 text-muted fst-italic">
                            Silakan pilih course terlebih dahulu untuk melihat modul dan item-nya.
                        </div>
                    @elseif($modules->isEmpty())
                        <div class="alert alert-warning mb-0">
                            ⚠️ Tidak ada modul ditemukan untuk course ini.
                        </div>
                    @else
                        @foreach($modules as $module)
                            <div class="card mb-3 shadow-sm">
                                <div class="card-header bg-light fw-bold">
                                    📦 {{ str_replace('Materi', 'Bagian', $module->course_week_title ?? '(Tanpa Judul)') }}
                                </div>
                                <ul class="list-group list-group-flush">
                                    @forelse($module->items as $item)
                                        <li class="list-group-item p-0">
                                            <a href="#"
                                                class="d-flex justify-content-between align-items-center text-decoration-none text-dark p-3 item-trigger"
                                                data-item-id="{{ $item->item_id }}"
                                                data-item-name="{{ $item->course_item_name }}">
                                                <div>
                                                    @php
                                                        switch ((int)$item->course_item_type) {
                                                            case 1: $icon = '📖'; break;
                                                            case 2: $icon = '📘'; break;
                                                            case 3: $icon = '📝'; break;
                                                            case 4: $icon = '❓'; break;
                                                            case 5: $icon = '💬'; break;
                                                            case 7: $icon = '📎'; break;
                                                            default: $icon = '📄'; break;
                                                        }
                                                    @endphp
                                                    {{ $icon }}
                                                    <strong>{{ $item->course_item_name }}</strong>
                                                </div>
                                            </a>
                                        </li>
                                    @empty
                                        <li class="list-group-item text-muted fst-italic">
                                            Tidak ada item di modul ini.
                                        </li>
                                    @endforelse
                                </ul>
                            </div>
                        @endforeach
                    @endif
                </div>
            </div>
        </div>

        <!-- ========== KONTEN: Submission Learners ========== -->
        <div class="col-md-8">
            <div class="card shadow-sm h-100">
                <div class="card-header fw-bold bg-light">
                    📑 Informasi Submission Learners
                </div>
                 <div class="card-body" id="submissionList" style="max-height: 650px;overflow-y:auto;">
                    <div class="text-muted">Klik item di sebelah kiri untuk melihat submission.</div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection    