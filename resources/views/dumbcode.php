@extends('layouts.master')

@section('title', 'Modify Course')

@section('content')
<div class="container mt-4">
    <div class="d-flex justify-content-between">
        <button id="add-materi" class="btn btn-primary mb-3">Tambah Materi Baru</button>
        <button id="draft-course" 
                class="btn btn-primary mb-3" 
                data-course-id="{{ $course->course_id }}">
            Simpan Kursus
        </button>
        <button id="preview-course" class="btn btn-primary mb-3">Ajukan Pratinjau</button>
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
                            <button class="btn btn-success add-sub"
                                    title="Tambah Sub"
                                    data-bs-course-id="{{ $course->course_id }}"
                                    data-bs-course-week-id="{{ $module->course_week_id }}"
                                    data-bs-course-week-title="{{ $module->course_week_title }}">
                                ➕
                            </button>
                            <button class="btn btn-danger delete-btn" title="Hapus">🗑</button>
                        </div>
                    </div>
                    <div class="p-3 text-center sub-container">
                        @php
                            $items = \App\Models\CourseWeekItem::where('course_week_id', $module->course_week_id)->get();

                            $icons = [
                                1 => '🎬', // Video
                                2 => '📄', // PDF
                                3 => '✏️', // Esai
                                4 => '✅', // Pilihan Ganda
                                5 => '💬', // Forum
                                6 => '🏆', // Sertifikat
                                7 => '✏️' // Discussion
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
                                                data-course-one-timesubmitted="{{ $item->course_one_timesubmitted }}"
                                                data-course-media="{{ $item->course_media }}"
                                                data-course-pre-requirment="{{ $item->course_pre_requirment }}"
                                                data-course-assignment="{{ $item->course_assignment }}"
                                                data-is-checked="{{ $item->is_checked }}">
                                            ✏
                                        </button>
                                        <button class="btn btn-danger delete-btn" title="Hapus">🗑</button>
                                    </div>
                                </div>
                            @endforeach
                        @else
                            Belum ada sub-materi <br>
                            <button class="btn btn-outline-primary btn-sm mt-2 add-sub"
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
    <div class="modal fade" id="modifyModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-scrollable modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Tambahkan Materi Pembahasan</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>

                <div class="modal-body">
                    <form id="modifyCourseForm" action="" method="POST" enctype="multipart/form-data">
                        @csrf
                        @method('PUT')
                    
                        <div class="row g-3">
                            <input type="hidden" name="course_id" id="course_id">
                            <input type="hidden" name="course_week_id" id="course_week_id">
                            <input type="hidden" id="hiddenCourseWeekTitle" name="course_week_title">
                            <input type="hidden" name="_method" value="PUT">

                            <!-- Kolom Kiri -->
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label">Judul Sub Materi</label>
                                    <input type="text" class="form-control" id="course_item_name" name="course_item_name">
                                </div>
                                <div class="mb-3">
                                    <label class="form-label">Deskripsi Kursus</label>
                                    <textarea class="form-control" id="courseDescription" name="course_describe"></textarea>
                                </div>
                                <div class="mb-3">
                                    <label class="form-label">Tipe Materi Kursus</label>
                                    @php
                                        use App\Models\CourseItemType;
                                        $types = CourseItemType::where('is_active', 1)->get();
                                    @endphp
                                    <select class="form-select" id="courseType" name="course_item_type">
                                        <option value="">-- Pilih Tipe Materi --</option>
                                        @foreach($types as $type)
                                            <option value="{{ $type->id }}">{{ $type->item_name }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="mb-3">
                                    <label class="form-label">Awal Masa Kursus</label>
                                    <input type="datetime-local" class="form-control" id="courseStart" name="course_due_start">
                                </div>
                                <div class="mb-3">
                                    <label class="form-label">Akhir Masa Kursus</label>
                                    <input type="datetime-local" class="form-control" id="courseEnd" name="course_due_end">
                                </div>
                                <div class="mb-3">
                                    <label class="form-label">Jangka Waktu (Menit)</label>
                                    <input type="number" class="form-control" id="courseDuration" name="course_duration" placeholder="Countdown in minutes">
                                </div>
                            </div>

                            <!-- Kolom Kanan -->
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label">Apakah satu kali unggahan</label>
                                    <select class="form-select" id="courseOneTimeUpload" name="course_one_timesubmitted">
                                        <option value="">No</option>
                                        <option value="1">Yes</option>
                                    </select>
                                </div>
                                <div class="mb-3">
                                    <label class="form-label">Prasyarat Materi</label>
                                    <select class="form-select" id="courseRequirement" name="course_pre_requirment">
                                        <option value="">-- Default --</option>
                                        <option value="before">Sebelum Materi Sebelumnya Selesai</option>
                                        <option value="after">Sesudah Materi Sebelumnya Selesai</option>
                                        <option value="none">Tanpa Prasyarat</option>
                                        <option value="all_done">Sesudah Seluruh Materi Selesai</option>
                                    </select>
                                </div>
                                <div class="mb-3" id="videoTypeWrapper"> 
                                    <label class="form-label">Tipe Unggahan Video</label> 
                                    <select class="form-select" id="CourseVideoType" name="course_media"> 
                                        <option value="">-- Default --</option> 
                                        <option value="url">URL</option> 
                                        <option value="upload">Unggahan</option> 
                                    </select> 
                                </div>
                                <div class="mb-3">
                                    <label class="form-label">Unggahan Materi Kursus</label>
                                    <input type="file" class="form-control" id="courseFile" name="course_media_file">
                                </div>

                                <div class="mb-3">
                                    <label class="form-label">Salinan URL Video Pembelajaran</label>
                                    <input type="url" class="form-control" id="courseVideoUrl" name="course_media_url">
                                </div>

                                <div class="mb-3">
                                    <label class="form-label">Unggah Video Pembelajaran</label>
                                    <input type="file" class="form-control" id="courseVideoUpload" name="course_media_video">
                                </div>
                                <div class="mb-3">
                                    <label class="form-label">Materi Esai</label>
                                    <button type="button" class="btn btn-outline-primary w-100" id="openEssayModal" data-bs-toggle="modal" data-bs-target="#EssayItemModal">
                                        Buat Esai
                                    </button>
                                </div>
                                <div class="mb-3">
                                    <label class="form-label">Materi Pilihan Ganda</label>
                                    <button type="button" class="btn btn-outline-primary w-100" id="openQuizModal" data-bs-toggle="modal" data-bs-target="#MultiplyChoiceItemModal">
                                        Buat Pilihan Ganda
                                    </button>
                                </div>
                                <div class="mb-3">
                                    <label class="form-label">Materi Forum Diskusi</label>
                                    <button type="button" class="btn btn-outline-primary w-100" id="openForumModal" data-bs-toggle="modal" data-bs-target="#DiscussionForumModal">
                                        Buat Forum Diskusi
                                    </button>
                                </div>
                                <!-- <div class="mb-3">
                                    <label class="form-label">Unggahan Template Sertifikat</label>
                                    <input type="file" class="form-control" id="courseCertificateFile" name="course_certificate_file">
                                </div> -->
                                <div class="mb-3">
                                    <label class="form-label">Tipe Akses</label>
                                    <select class="form-select" id="courseAccessType" name="is_checked">
                                        <option value="">-- Default --</option>
                                        <option value="1">Setelah batas waktu habis terkunci</option>
                                        <option value="0">Sebelum batas waktu habis terbuka</option>
                                    </select>
                                </div>
                                <div class="mb-3">
                                    <label class="form-label">Penugasan Materi</label> 
                                    <div class="form-check mb-2">
                                        <input class="form-check-input" type="checkbox" id="assignLaterCheckbox">
                                        <label class="form-check-label" for="assignLaterCheckbox">
                                            Atur Nanti
                                        </label>
                                    </div>
                                    <button type="button" class="btn btn-outline-primary w-100" id="openAssignModal" data-bs-toggle="modal" data-bs-target="#assignModal">
                                        Atur Penugasan
                                    </button>
                                </div>
                                {{-- hasil akhir assignment --}}
                                <input type="hidden" name="course_assignment" id="assignedUsersJson">
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
<div class="modal fade" id="assignModal" tabindex="-1" aria-labelledby="assignModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
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

<!-- Modal Fullscreen Esai -->
<div class="modal fade" id="EssayItemModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-fullscreen">
    <div class="modal-content">
      <div class="modal-header bg-primary text-white">
        <h5 class="modal-title">Buat Materi Esai</h5>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body">
        <div class="mb-3">
            <label class="form-label">Judul Esai</label>
            <input type="text" class="form-control" placeholder="Masukkan judul esai...">
        </div>
        <div class="mb-3">
            <label class="form-label">Konten Esai</label>
            <textarea class="form-control" rows="10" placeholder="Tulis materi esai di sini..."></textarea>
        </div>
      </div>
      <div class="modal-footer">
        <button class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
        <button class="btn btn-success">Simpan Esai</button>
      </div>
    </div>
  </div>
</div>
<!-- Modal Fullscreen Pilihan Ganda -->
<div class="modal fade" id="MultiplyChoiceItemModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-fullscreen">
    <div class="modal-content">
      <div class="modal-header bg-success text-white">
        <h5 class="modal-title">Buat Materi Pilihan Ganda</h5>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body">
        <div class="mb-3">
            <label class="form-label">Pertanyaan</label>
            <textarea class="form-control" rows="3" placeholder="Tulis pertanyaan di sini..."></textarea>
        </div>
        <div class="mb-3">
            <label class="form-label">Opsi Jawaban</label>
            <input type="text" class="form-control mb-2" placeholder="Opsi A">
            <input type="text" class="form-control mb-2" placeholder="Opsi B">
            <input type="text" class="form-control mb-2" placeholder="Opsi C">
            <input type="text" class="form-control mb-2" placeholder="Opsi D">
        </div>
      </div>
      <div class="modal-footer">
        <button class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
        <button class="btn btn-success">Simpan Soal</button>
      </div>
    </div>
  </div>
</div>
<!-- Modal Fullscreen Forum Diskusi -->
<div class="modal fade" id="DiscussionForumModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-fullscreen">
    <div class="modal-content">
      <div class="modal-header bg-warning">
        <h5 class="modal-title">Buat Forum Diskusi</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body">
        <div class="mb-3">
            <label class="form-label">Judul Forum</label>
            <input type="text" class="form-control" placeholder="Masukkan judul forum...">
        </div>
        <div class="mb-3">
            <label class="form-label">Pertanyaan Pemantik Diskusi</label>
            <textarea class="form-control" rows="5" placeholder="Tulis pertanyaan atau topik diskusi di sini..."></textarea>
        </div>
      </div>
      <div class="modal-footer">
        <button class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
        <button class="btn btn-success">Simpan Forum</button>
      </div>
    </div>
  </div>
</div>

@endsection
