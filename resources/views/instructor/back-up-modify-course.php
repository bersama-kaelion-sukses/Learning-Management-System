@extends('layouts.master')

@section('title', 'Modify Course')

@section('content')
<div class="container mt-4">
    <!-- Row 1: Header Course -->
    <div class="card shadow-sm mb-4">
        <div class="card-body">
            <div class="row align-items-stretch">

                <!-- Gambar Course + Aksi -->
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

                    <!-- Dropdown hanya untuk pratinjau -->
                    <div class="dropdown mt-3">
                        <button class="btn btn-outline-secondary dropdown-toggle w-100" 
                                type="button" id="aksiDropdown" 
                                data-bs-toggle="dropdown" aria-expanded="false">
                            ⚙️ Aksi Lain
                        </button>
                        <ul class="dropdown-menu shadow w-100" aria-labelledby="aksiDropdown">
                            <li>
                                @if($course->is_approved == 1)
                                    <span class="dropdown-item text-success">
                                        ✅ Course telah disetujui
                                    </span>
                                @else
                                    <form action="{{ route('approval.index') }}" method="GET" class="m-0">
                                        <input type="hidden" name="course_id" value="{{ $course->course_id }}">
                                        <input type="hidden" name="course_title" value="{{ $course->course_title }}">
                                        <button type="submit" class="dropdown-item">
                                            📤 Ajukan Pratinjau
                                        </button>
                                    </form>
                                @endif
                            </li>
                        </ul>
                    </div>

                    <!-- Tombol Tambah Materi -->
                    <div class="d-grid mt-3">
                    </div>
                </div>

                <!-- Info Course -->
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
                                            data-bs-target="#viewLearnersModal2">
                                        Lihat Detail
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Tombol Simpan Perubahan -->
                    <div class="d-flex justify-content-end gap-2 mt-2">
                        <button id="add-thread-forum" class="btn btn-warning w-100" data-bs-toggle="modal" data-bs-target="#ThreadDiscussion" data-course-id="{{ $course->course_id }}">
                            Buat Thread Diskusi
                        </button>
                        <button id="add-materi" class="btn btn-secondary w-100" data-course-id="{{ $course->course_id }}">
                            ➕ Tambah Materi Baru
                        </button>
                    </div>
                </div>

            </div>
        </div>
    </div>
    
    <!-- Container Materi -->
    <div id="materi-container" class="border bg-light p-4 text-center rounded">
        <input type="hidden" id="materiCountInitial" value="{{ $modules->count() }}">
        @if($modules->count() > 0)
            @foreach($modules as $module)
                <div class="mb-3 border rounded bg-white materi-item"
                    data-module-id="{{ $module->course_week_id }}"
                    data-course-id="{{ $course->course_id }}"
                    data-week-title="{{ $module->course_week_title }}">
                    
                    <div class="d-flex justify-content-between align-items-center p-2 bg-light">
                        <strong class="materi-title">
                            {{ $module->course_week_title ?? "Materi " . $module->week_order }}
                        </strong>
                        <div class="btn-group btn-group-sm">
                            <button class="btn btn-warning add-sub"
                                    title="Tambah Sub"
                                    data-bs-course-id="{{ $course->course_id }}"
                                    data-bs-course-week-id="{{ $module->course_week_id }}"
                                    data-bs-course-week-title="{{ $module->course_week_title }}">
                                ➕
                            </button>
                            <!-- Panah Ke Atas -->
                            <button class="btn btn-outline-secondary btn-module-up" 
                                    data-module-id="{{ $module->course_week_id }}">
                                ⬆️
                            </button>

                            <button class="btn btn-outline-secondary btn-module-down" 
                                    data-module-id="{{ $module->course_week_id }}">
                                ⬇️
                            </button>
                            
                            <button class="btn btn-danger delete-btn" title="Hapus">🗑</button>
                        </div>
                    </div>
                    <div class="p-3 text-center sub-container">
                        @php
                            $items = \App\Models\CourseWeekItem::where('course_week_id', $module->course_week_id)->get();
                            $icons = [
                                1 => '🎬', // Video
                                2 => '📄', // PDF / Dokumen
                                3 => '📝', // Esai
                                4 => '❓', // Pilihan Ganda (Quiz)
                                5 => '💬', // Forum / Diskusi Umum
                                6 => '🎓', // Sertifikat / Kelulusan
                                7 => '💭'  // Diskusi (lebih beda dari forum)
                            ];
                        @endphp

                        @if($items->count() > 0)
                            @foreach($items as $item)
                                <div class="d-flex justify-content-between align-items-center border p-2 mb-2 rounded sub-item"
                                    data-course-id="{{ $item->course_id }}"
                                    data-existing="true"
                                    data-course-week-id="{{ $item->course_week_id }}"
                                    data-item-id="{{ $item->item_id }}">

                                    <span class="sub-title d-flex align-items-center gap-2">
                                        {!! $icons[$item->course_item_type] ?? '<i class="bi bi-file-earmark"></i>' !!}
                                        {{ $item->course_item_name }}
                                    </span>

                                    <div class="btn-group btn-group-sm">
                                        <button class="btn btn-warning modify-btn"
                                                data-course-id="{{ $item->course_id }}"
                                                data-course-week-id="{{ $item->course_week_id }}"
                                                data-course-week-title="{{ $module->course_week_title }}"
                                                data-item-id="{{ $item->item_id }}"
                                                data-course-item-name="{{ $item->course_item_name }}"
                                                data-course-describe="{{ $item->course_describe }}"
                                                data-course-item-type="{{ $item->course_item_type }}"
                                                data-course-due-start="{{ $item->course_due_start }}"
                                                data-course-due-end="{{ $item->course_due_end }}"
                                                data-course-duration="{{ $item->course_duration }}"
                                                data-course-media="{{ $item->course_media }}"
                                                data-course-grade="{{ $item->passing_grade }}"
                                                data-course-multiply-chance="{{ $item->course_multiply_chance ?? 1 }}"
                                                data-course-assignment="{{ $item->course_assignment }}">
                                            ✏
                                        </button>
                                        <!-- Move Up -->
                                        <button type="button"
                                                class="btn btn-outline-secondary btn-item-move-up"
                                                title="Pindah ke Atas"
                                                data-item-id="{{ $item->item_id }}">
                                            ⬆️
                                        </button>

                                        <!-- Move Down -->
                                        <button type="button"
                                                class="btn btn-outline-secondary btn-item-move-down"
                                                title="Pindah ke Bawah"
                                                data-item-id="{{ $item->item_id }}">
                                            ⬇️
                                        </button>
                                        <button class="btn btn-danger delete-btn" title="Hapus">🗑</button>
                                    </div>
                                </div>
                            @endforeach
                        @else
                            Belum ada sub-materi <br>
                            <button class="btn btn-secondary btn-sm mt-2 add-sub"
                                    data-bs-course-id="{{ $course->course_id }}"
                                    data-bs-course-week-id="{{ $module->course_week_id }}"
                                    data-bs-course-week-title="{{ $module->course_week_title }}">
                                Tambah Sub Materi
                            </button>
                        @endif
                    </div>
                </div>
            @endforeach
        @else
            Belum ada materi pada kursus ini
        @endif
    </div>
  <!-- Modal Modify Course Item -->
    <div class="modal fade" id="modifyModal" tabindex="-1" data-bs-backdrop="static" data-bs-keyboard="false">
        <div class="modal-dialog modal-dialog-centered modal-dialog-scrollable modal-lg">
            <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Tambahkan Materi Pembahasan</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
                <div class="modal-body">
                <form id="modifyCourseForm" action="" method="POST" enctype="multipart/form-data" novalidate>
                    @csrf
                    @method('PUT')

                    <div class="row">
                    <!-- Hidden -->
                    <input type="hidden" name="course_id" id="course_id">
                    <input type="hidden" name="course_week_id" id="course_week_id">
                    <input type="hidden" id="hiddenCourseWeekTitle" name="course_week_title">
                    <input type="hidden" id="current_item_id" name="current_item_id">
                    <input type="hidden" id="quizItemId" name="quiz_item_id">
                    <input type="hidden" id="forumItemId" name="forum_item_id">
                    <input type="hidden" name="_method" value="PUT">
                    <input type="hidden" id="courseExistingMedia" name="course_existing_media">


                    <!-- Single Column -->
                    <div class="col-12">
                        <div class="mb-3">
                        <label class="form-label">Judul Tugas/Pembahasan <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" id="course_item_name" name="course_item_name">
                        <div class="invalid-feedback">Bagian ini wajib diisi.</div>
                        </div>

                        <div class="mb-3">
                        <label class="form-label">Instruksi Tugas <span class="text-danger">*</span></label>
                        <textarea class="form-control" id="courseDescription" name="course_describe" placeholder="Jabarkan Instruksi Tugas..."></textarea>
                        <div class="invalid-feedback">Bagian ini wajib diisi.</div>
                        </div>

                        <div class="mb-3">
                        <label class="form-label">Tipe Materi Tugas <span class="text-danger">*</span></label>
                        @php
                            use App\Models\CourseItemType;
                            $types = CourseItemType::where('is_active', 1)->get();
                        @endphp
                        <select class="form-select" id="courseType" name="course_item_type">
                            {{-- Default --}}
                            @if(empty($courseItem->course_item_type ?? null))
                                <option value="" selected>-- Pilih Tipe Materi --</option>
                            @else
                                <option value="">-- Pilih Tipe Materi --</option>
                            @endif

                            {{-- Loop pilihan --}}
                            @foreach($types as $type)
                                <option value="{{ $type->id }}" 
                                    {{ ($courseItem->course_item_type ?? null) == $type->id ? 'selected' : '' }}>
                                    {{ $type->item_name }}
                                </option>
                            @endforeach
                        </select>
                        <div class="invalid-feedback">Silakan pilih tipe materi.</div>
                        </div>

                        <div class="mb-3">
                        <label class="form-label">Awal Masa Tugas</label>
                        <input type="datetime-local" class="form-control" id="courseStart" name="course_due_start">
                        </div>

                        <div class="mb-3">
                        <label class="form-label">Akhir Masa Tugas</label>
                        <input type="datetime-local" class="form-control" id="courseEnd" name="course_due_end">
                        </div>

                        <div class="mb-3">
                        <label class="form-label">Jangka Waktu (Menit)</label>
                        <input type="number" class="form-control" id="courseDuration" name="course_duration" placeholder="Countdown in minutes">
                        </div>
                        <div class="mb-3">
                            <label for="form-label"> Passing Grade </label>
                            <input type="number" class="form-control" id="coursePassingGrade" name="course_passing" placeholder=" Minimal Nilai Kelulusan">
                        </div>

                        <div class="mb-3 d-none" id="videoTypeWrapper">
                        <label class="form-label">Tipe Unggahan Video <span class="text-danger">*</span></label>
                        <select class="form-select" id="CourseVideoType" name="course_media_type">
                            <option value="">-- Default --</option>
                            <option value="url">URL</option>
                            <option value="upload">Unggahan</option>
                        </select>
                        <div class="invalid-feedback">Silakan pilih tipe video.</div>
                        </div>

                        <div class="mb-3 d-none" id="courseFileWrapper">
                        <label class="form-label">Unggahan Materi (.pdf) <span class="text-danger">*</span></label>
                        <input type="file" class="form-control" id="courseFile" name="course_media_file" accept=".pdf">
                        <small id="existingFileNotePdf" class="text-muted d-none"></small>
                        <div class="invalid-feedback">File wajib diunggah.</div>
                        </div>

                        <div class="mb-3 d-none" id="audioFileWrapper">
                        <label class="form-label">Unggahan Audio  <span class="text-danger">*</span> </label>
                        <input type="file" class="form-control" id="courseAudio" name="course_media_audio">
                        <div class="invalid-feedback">File wajib diunggah.</div>

                        </div>

                        <div class="mb-3 d-none" id="videoUrlWrapper">
                        <label class="form-label">URL Video Pembelajaran <span class="text-danger">*</span></label>
                        <input type="url" class="form-control" id="courseVideoUrl" name="course_media_url">
                        <div class="invalid-feedback">URL video wajib diisi.</div>
                        </div>

                        <div class="mb-3 d-none" id="videoUploadWrapper">
                        <label class="form-label">Unggah Video Pembelajaran (Not Exceed than 100MB) <span class="text-danger">*</span></label>
                        <!-- <label class="form-label">Unggah Video Pembelajaran (Not Exceed than 200MB) <span class="text-danger">*</span></label> -->

                            <input type="file" 
                                class="form-control" 
                                id="courseVideoUpload" 
                                name="course_media_video" 
                                accept="video/mp4,video/mkv,video/avi,video/mov,video/webm">
                            <div class="invalid-feedback">File video wajib diunggah.</div>
                            <small id="existingFileNoteVideo" class="text-muted d-none"></small>
                        </div>

                        <!-- 🔥 Universal Progress Bar -->
                        <div class="progress mt-3 d-none" id="uploadProgressWrapper">
                            <div class="progress-bar bg-warning" id="uploadProgress"
                                role="progressbar" style="width: 0%">0%</div>
                        </div>

                        <!-- Tambahan -->
                        <div class="mb-3 essay-wrapper d-none">
                        <label class="form-label">Materi Esai</label>
                        <button type="button"
                                class="btn btn-outline-primary w-100 openEssayModal"
                                data-bs-toggle="modal"
                                data-bs-target="#EssayItemModal">
                            Buat Esai
                        </button>
                        </div>

                        <div class="mb-3 quiz-wrapper d-none">
                        <label class="form-label">Materi Pilihan Ganda</label>
                        <button type="button" class="btn btn-outline-primary w-100 openQuizModal" data-bs-toggle="modal" data-bs-target="#MultiplyChoiceItemModal">Buat Pilihan Ganda</button>
                        </div>

                        <div class="mb-3 forum-wrapper d-none">
                        <label class="form-label">Materi Forum Diskusi</label>
                        <button type="button" class="btn btn-outline-primary w-100 openForumModal" data-bs-toggle="modal" data-bs-target="#DiscussionForumModal">Buat Forum Diskusi</button>
                        </div>

                        <div class="mb-3 assignment-wrapper d-none">
                        <label class="form-label">Dokumen Materi (.pdf)</label>
                        <input type="file" class="form-control" id="courseAssignmentFile" name="course_assignment_file" accept="application/pdf">
                        <small id="existingAssignmentNote" class="text-muted d-none"></small>
                        </div>
                    </div>
                    </div>
                </form>
                </div>

            <div class="modal-footer">
                <button class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                <button type="submit" class="btn btn-primary" id="saveCourseBtn" form="modifyCourseForm">Simpan Perubahan</button>
            </div>
            </div>
        </div>
    </div>
    <!-- Modal Assign Learner -->
    <div class="modal fade" id="assignModal" tabindex="-1" aria-labelledby="assignModalLabel" data-bs-backdrop="static" data-bs-keyboard="false">
        <div class="modal-dialog modal-dialog-centered modal-lg">
            <div class="modal-content">

                <!-- Header Modal -->
                <div class="modal-header flex-column align-items-center">
                    <h5 class="modal-title text-center mb-1" id="assignModalLabel">
                        Tugaskan Learner ke Kursus
                    </h5>
                    <p id="assignCourseNameLabel" class="text-center fst-italic small mb-0"></p>
                    <button type="button" class="btn-close position-absolute end-0 me-2" 
                            data-bs-dismiss="modal" aria-label="Close"></button>
                </div>

                <!-- Body Modal -->
                <form id="assignForm" onsubmit="return false;">
                    @csrf
                    <input type="hidden" name="course_id" id="assignCourseIdInput">

                    <div class="modal-body">
                        <div class="mb-3 d-flex justify-content-between align-items-center">
                            <div class="d-flex align-items-center gap-2">
                                @php
                                    $divisions = \App\Models\Division::select("division_id", "division_name")->get();
                                @endphp
                                <select class="form-select" name="division" id="assignDivisionSelect">
                                    <option value="all">Semua Learner</option>
                                    @foreach ($divisions as $division)
                                        <option value="{{ $division->division_id }}">{{ $division->division_name }}</option>
                                    @endforeach
                                </select> 
                            </div>
                        </div>

                        <div class="row">
                            <!-- Kolom Kiri -->
                            <div class="col-md-5">
                                <div class="mb-2">
                                    <input type="text" class="form-control" id="assignSearchUserInput" placeholder="Cari Learner...">
                                </div>
                                <select class="form-select" id="assignUserSelect" multiple size="10">
                                    @php
                                        use App\Models\CourseEnrollment;

                                        $enrolledUsers = CourseEnrollment::where('course_id', $course->course_id)
                                            ->with('user')
                                            ->get()
                                            ->pluck('user');
                                    @endphp

                                    @foreach($enrolledUsers as $user)
                                        <option 
                                            value="{{ $user->user_id }}" 
                                            data-division="{{ implode(',', (array) $user->departement_cat) }}">
                                            {{ $user->full_name }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>

                            <!-- Kolom Tengah -->
                            <div class="col-md-2 d-flex flex-column align-items-center justify-content-center gap-2">
                                <button type="button" class="btn btn-outline-secondary" id="assignMoveRight">&gt;</button>
                                <button type="button" class="btn btn-outline-secondary" id="assignMoveLeft">&lt;</button>
                            </div>

                            <!-- Kolom Kanan -->
                            <div class="col-md-5">
                                <div class="d-flex justify-content-between align-items-center mb-2">
                                    <label class="form-label mb-0">Learner Ditugaskan</label>
                                    <div class="d-flex gap-2">
                                        <button type="button" class="btn btn-primary btn-sm" id="assignSelectAllLearner">Semua learner</button>
                                        <button type="button" class="btn btn-warning btn-sm" id="assignResetLearner">Reset</button>
                                    </div>
                                </div>
                                <select class="form-select" id="assignAssignedSelect" multiple size="10"></select>
                            </div>
                        </div>
                    </div>

                    <!-- Footer Modal -->
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                        <button type="button" class="btn btn-primary" id="saveAssignBtn">Tugaskan Learner</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    <!-- Modal Esai -->
    <div class="modal fade" id="EssayItemModal" tabindex="-1" data-bs-backdrop="static" data-bs-keyboard="false">
        <div class="modal-dialog modal-dialog-centered modal-lg">
            <div class="modal-content">
                <form id="essayForm" method="POST" enctype="multipart/form-data" novalidate>
                    @csrf
                    <input type="hidden" id="essayId" name="essay_id">
                    <input type="hidden" id="essayItemId" name="item_id">
                    <input type="hidden" id="essayExistingAttachment" name="essay_existing_attachment">

                    <!-- Header -->
                    <div class="modal-header bg-warning text-white">
                        <h5 class="modal-title">Buat Materi Esai</h5>
                        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                    </div>

                    <!-- Body -->
                    <div class="modal-body">
                        <!-- Judul -->
                        <div class="mb-3">
                            <label class="form-label">Pertanyaan Esai <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" name="essay_title" id="essayTitle"
                                placeholder="Masukkan judul esai...">
                            <div class="invalid-feedback">Pertanyaan esai wajib diisi.</div>
                        </div>

                        <!-- Instruksi -->
                        <div class="mb-3">
                            <label class="form-label">Instruksi / Deskripsi <span class="text-danger">*</span></label>
                            <textarea class="form-control" rows="4" name="instruction" id="essayInstruction"
                                    placeholder="Tuliskan instruksi untuk mahasiswa..."></textarea>
                            <div class="invalid-feedback">Instruksi wajib diisi.</div>
                        </div>

                        <!-- Kaitkan File Materi -->
                        <div class="mb-3">
                            <label class="form-label">Tipe Lampiran </label>
                            <select class="form-select" id="essayAttachmentType" name="attachment_type">
                                <option value="">-- Pilih Tipe --</option>
                                <option value="pdf">PDF</option>
                                <option value="url">URL</option>
                                <option value="video">Video</option>
                            </select>
                        </div>

                        <!-- Input PDF -->
                        <div class="mb-3 attachment-field" id="essayPdfWrapper" style="display:none;">
                            <label class="form-label">Unggah PDF</label>
                            <input type="file" class="form-control" name="essay_attachment_pdf" id="essayAttachmentPdf"
                                accept=".pdf">
                            <small id="essayNotePdf" class="text-muted d-none"></small>
                        </div>

                        <!-- Input URL -->
                        <div class="mb-3 attachment-field" id="essayUrlWrapper" style="display:none;">
                            <label class="form-label">URL Pendukung</label>
                            <input type="url" class="form-control" name="essay_attachment_url" id="essayAttachmentUrl"
                                placeholder="https://...">
                        </div>

                        <!-- Input Video -->
                        <div class="mb-3 attachment-field" id="essayVideoWrapper" style="display:none;">
                            <label class="form-label">Unggah Video (Not Exceed than 100MB) </label>
                            <!-- <label class="form-label">Unggah Video (Not Exceed than 200MB) </label> -->
                            <input type="file" class="form-control" name="essay_attachment_video" id="essayAttachmentVideo"
                                accept="video/mp4,video/mkv,video/avi">
                            <small id="essayNoteVideo" class="text-muted d-none"></small>
                        </div>
                    </div>
                    <!-- Progress bar universal -->
                    <div class="mx-3">
                    <div id="uploadProgressEssay" class="progress d-none mt-3 mb-2">
                        <div id="uploadProgressEssayBar" class="progress-bar bg-warning" role="progressbar"
                            style="width: 0%;" aria-valuemin="0" aria-valuemax="100">0%</div>
                    </div>
                    </div>
                    <!-- Footer -->
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                        <button type="submit" class="btn btn-primary">Simpan Esai</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    <!-- Modal Pilihan Ganda -->
    <div class="modal fade" id="MultiplyChoiceItemModal" tabindex="-1"
        data-bs-backdrop="static" data-bs-keyboard="false">
        <div class="modal-dialog modal-dialog-centered modal-lg">
            <div class="modal-content">

                <!-- Header -->
                <div class="modal-header bg-warning text-white">
                    <h5 class="modal-title">Buat Materi Pilihan Ganda</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>

                <!-- Body -->
                <div class="modal-body">
                    <input type="hidden" id="mc_item_id" value="">
                    <div id="mcQuestionsContainer">
                        <!-- Awalnya kosong → diisi lewat prefill JS atau tombol Tambah Soal -->
                    </div>

                    <!-- Tambah soal baru -->
                    <button class="btn btn-outline-success btn-sm" type="button" id="addMcQuestion">➕ Tambah Soal</button>
                </div>

                <!-- Footer -->
                <div class="modal-footer">
                    <button class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                    <button class="btn btn-primary" id="saveMcBtn">Simpan Semua Soal</button>
                </div>

            </div>
        </div>
    </div>

    <!-- Modal Forum Diskusi -->
    <div class="modal fade" id="DiscussionForumModal" tabindex="-1"
        data-bs-backdrop="static" data-bs-keyboard="false">
        <div class="modal-dialog modal-dialog-centered modal-lg">
            <div class="modal-content">

                <!-- Header -->
                <div class="modal-header bg-warning">
                    <h5 class="modal-title">Buat Forum Diskusi</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>

                <!-- Body -->
                <div class="modal-body">
                    <input type="hidden" id="forum_item_id" name="item_id">

                    <!-- Judul Forum -->
                    <div class="mb-3">
                        <label class="form-label">Pertanyaan Forum <span class="text-danger">*</span></label>
                        <input type="text" class="form-control forum-title-field" name="forum_title" placeholder="Masukkan judul forum..." required>
                        <div class="invalid-feedback">Judul forum wajib diisi.</div>
                    </div>

                    <!-- Pertanyaan Pemantik -->
                    <div class="mb-3">
                        <label class="form-label">Instruksi Forum Diskusi <span class="text-danger">*</span></label>
                        <textarea class="form-control forum-question-field" rows="4" name="forum_question" placeholder="Tulis pertanyaan atau topik diskusi di sini..." required></textarea>
                        <div class="invalid-feedback">Instruksi forum wajib diisi.</div>
                    </div>

                    <!-- Tipe Lampiran -->
                    <div class="mb-3">
                        <label class="form-label">Tipe Lampiran</label>
                        <select class="form-select" id="forumAttachmentType" name="forum_attachment_type">
                            <option value="">-- Pilih Tipe --</option>
                            <option value="pdf">PDF</option>
                            <option value="url">URL</option>
                            <option value="video">Video</option>
                        </select>
                    </div>

                    <!-- Input PDF -->
                    <div class="mb-3 attachment-field" id="forumPdfWrapper" style="display:none;">
                        <label class="form-label">Unggah PDF</label>
                        <input type="file" class="form-control" name="forum_attachment_pdf" accept=".pdf">
                    </div>

                    <!-- Input URL -->
                    <div class="mb-3 attachment-field" id="forumUrlWrapper" style="display:none;">
                        <label class="form-label">URL Pendukung</label>
                        <input type="url" class="form-control" name="forum_attachment_url" placeholder="https://...">
                    </div>

                    <!-- Input Video -->
                    <div class="mb-3 attachment-field" id="forumVideoWrapper" style="display:none;">
                        <label class="form-label">Unggah Video</label>
                        <input type="file" class="form-control" name="forum_attachment_video" accept="video/mp4,video/mkv,video/avi">
                    </div>
                    <div class="mx-3">
                    <div id="uploadProgressForum" class="progress d-none mt-3 mb-2">
                        <div id="uploadProgressForumBar" class="progress-bar bg-warning" role="progressbar"
                            style="width: 0%;" aria-valuemin="0" aria-valuemax="100">0%</div>
                    </div>
                </div>
                </div>
                <!-- Footer -->
                <div class="modal-footer">
                    <button class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                    <button class="btn btn-primary" id="saveForumBtn">Simpan Forum</button>
                </div>

            </div>
        </div>
    </div>
    <!-- Modal Detail Learner -->
    <div class="modal fade" id="viewLearnersModal2" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-lg modal-dialog-scrollable">
            <div class="modal-content">
                <div class="modal-header">
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
                                    <span class="badge {{ $enroll->is_opened ? 'bg-success' : 'bg-secondary' }}">
                                        {{ $enroll->is_opened ? 'Aktif' : 'Belum Aktif' }}
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
    <!-- Modal Thread Forum Diskusi -->
    <div class="modal fade" id="ThreadDiscussion" tabindex="-1" data-bs-backdrop="static" data-bs-keyboard="false">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content">

        <!-- Header -->
        <div class="modal-header bg-warning">
            <h5 class="modal-title fw-bold text-dark">Buat Thread Diskusi</h5>
            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
        </div>

        <div class="modal-body" style="max-height:70vh; overflow-y:auto;">
            <form id="Thread_FormContainer">
            <div id="Thread_FormList">
                <div class="Thread_FormBlock border rounded-4 p-3 mb-4 shadow-sm" style="background:#fffef8;">
                <div class="d-flex justify-content-between align-items-center mb-2">
                    <h6 class="fw-bold text-dark mb-0">🟡 Diskusi 1</h6>
                    <button type="button" class="btn btn-sm btn-outline-danger Thread_RemoveBtn" style="display:none;">
                    Hapus
                    </button>
                </div>

                <!-- Hidden fields -->
                <input type="hidden" id="Thread_course_id" name="Thread_course_id">
                <input type="hidden" name="thread_id" value="">
                <input type="hidden" name="thread_seq" value="">
                <input type="hidden" class="Thread_ExistingMedia" name="Thread_existing_media[]">

                <!-- (1) Topik Soal -->
                <div class="mb-3">
                    <label class="form-label fw-semibold">Topik Soal <span class="text-danger">*</span></label>
                    <input type="text" class="form-control" name="Thread_topic_title[]" placeholder="Masukkan topik atau nama soal..." required>
                    <div class="invalid-feedback">Topik Soal wajib diisi.</div>
                </div>

                <!-- (2) Pertanyaan Forum -->
                <div class="mb-3">
                    <label class="form-label fw-semibold">Pertanyaan Forum <span class="text-danger">*</span></label>
                    <input type="text" class="form-control" name="Thread_forum_title[]" placeholder="Masukkan pertanyaan forum..." required>
                    <div class="invalid-feedback">Pertanyaan Forum wajib diisi.</div>
                </div>

                <!-- (3) Instruksi Forum -->
                <div class="mb-3">
                    <label class="form-label fw-semibold">Instruksi Diskusi <span class="text-danger">*</span></label>
                    <textarea class="form-control" rows="3" name="Thread_forum_question[]" placeholder="Tuliskan instruksi atau arahkan diskusi di sini..." required></textarea>
                    <div class="invalid-feedback">Instruksi Diskusi wajib diisi.</div>
                </div>

                <!-- (4) Tipe Lampiran -->
                <div class="mb-3">
                    <label class="form-label fw-semibold">Tipe Lampiran</label>
                    <select class="form-select Thread_AttachType" name="Thread_forum_attachment_type[]">
                    <option value="">-- Pilih Tipe --</option>
                    <option value="pdf">PDF</option>
                    <option value="url">URL</option>
                    <option value="video">Video</option>
                    </select>
                </div>

                <!-- (5) Input PDF -->
                <div class="mb-3 Thread_PDFWrapper" style="display:none;">
                    <label class="form-label fw-semibold">Unggah PDF</label>
                    <input type="file" class="form-control Thread_PDFInput" name="Thread_forum_attachment_pdf[]" accept=".pdf">
                    <small class="text-muted d-none Thread_ExistingPDFNote"></small>
                    <div class="invalid-feedback">File PDF wajib diunggah.</div>
                </div>

                <!-- (6) Input URL -->
                <div class="mb-3 Thread_URLWrapper" style="display:none;">
                    <label class="form-label fw-semibold">URL Pendukung</label>
                    <input type="url" class="form-control" name="Thread_forum_attachment_url[]" placeholder="https://...">
                    <div class="invalid-feedback">URL wajib diisi.</div>
                </div>

                <!-- (7) Input Video -->
                <div class="mb-3 Thread_VideoWrapper" style="display:none;">
                    <label class="form-label fw-semibold">Unggah Video</label>
                    <input type="file" class="form-control Thread_VideoInput" name="Thread_forum_attachment_video[]" accept="video/mp4,video/mkv,video/avi">
                    <small class="text-muted d-none Thread_ExistingVideoNote"></small>
                    <div class="invalid-feedback">Video wajib diunggah.</div>
                </div>
                </div>
            </div>

            <!-- Tombol Tambah Soal -->
            <div class="text-center">
                <button type="button" id="Thread_AddFormBtn" class="btn btn-outline-secondary rounded-pill mt-2">
                Tambah Soal Baru
                </button>
            </div>
            </form>

            <!-- 🔄 Progress Bar -->
            <div class="mt-4">
            <div class="progress" style="height: 20px; display:none;" id="Thread_ProgressWrapper">
                <div class="progress-bar progress-bar-striped progress-bar-animated bg-success" 
                    id="Thread_ProgressBar" 
                    style="width: 0%;">0%</div>
            </div>
            <div class="text-center mt-2" id="Thread_ProgressText" style="display:none;">
                <small class="text-muted">Sedang mengunggah, mohon tunggu...</small>
            </div>
            </div>

        </div>

        <!-- Footer -->
        <div class="modal-footer">
            <button class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
            <button type="button" class="btn btn-success" id="Thread_saveForumBtn">Simpan Thread</button>
        </div>

        </div>
    </div>
    </div>



@endsection
