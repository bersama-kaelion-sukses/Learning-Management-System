@extends('layouts.master')

@section('title', 'Kursus Saya (Instructor)')

@section('content')
<div class="row g-4 justify-content-center align-items-start text-center">
    <div class="d-flex flex-column mb-4 border-bottom pb-3 align-items-center text-center">
        <h2 class="fw-bold text-dark mb-1">Kursus Saya (Instructor)</h2>
        <small class="text-muted">Kelola Kursus yang dibuat oleh instructor terkait.</small>
    </div>

    @php
        use Carbon\Carbon;
        use App\Models\CourseTakeover;
    @endphp

    @forelse ($courses as $course)
        @php
            // Cek apakah kursus sedang di-takeover
            $takeover = CourseTakeover::with(['oldTrainer', 'newTrainer'])
                ->where('course_id', $course->course_id)
                ->where('status', 'active')
                ->latest('start_date')
                ->first();

            $isTakeoverActive = !is_null($takeover);
        @endphp

        <div class="col-md-3 col-sm-6">
            <div class="card shadow-sm h-100 position-relative">
                {{-- 🔖 Badge asal kursus (kanan atas) --}}
                @if(!$isTakeoverActive)
                    <span class="badge 
                        {{ $course->is_requested ? 'bg-warning text-dark' : 'bg-info text-white' }} 
                        position-absolute top-0 end-0 m-2 shadow-sm">
                        {{ $course->is_requested ? 'Pengajuan oleh HR' : 'Dibuat oleh Instructor' }}
                    </span>
                @endif

                {{-- 🔖 Badge status publikasi (kiri atas) --}}
                @if(!$isTakeoverActive)
                    <span class="badge 
                        {{ $course->is_approved ? 'bg-success' : 'bg-secondary' }} 
                        position-absolute top-0 start-0 m-2 shadow-sm">
                        {{ $course->is_approved ? 'Telah Disetujui' : 'Belum Disetujui' }}
                    </span>
                @endif

                {{-- 🚨 Badge takeover (tengah atas) --}}
                @if($isTakeoverActive)
                    <span class="badge bg-danger text-white position-absolute top-0 start-50 translate-middle-x m-2 d-flex align-items-center gap-1 shadow-sm takeover-badge">
                        Kursus Di Takeover
                    </span>
                @endif


                {{-- Gambar --}}
                <div class="card-body d-flex flex-column justify-content-between" style="height:300px; max-height:300px; overflow-y: auto;">
                    <div class="mb-3 bg-light d-flex justify-content-center align-items-center"
                        style="height:140px; border:1px solid #ddd;">
                        <img src="{{ $course->course_image 
                                    ? asset('assets/img/course/' . $course->course_image) 
                                    : asset('assets/img/course/default-course.jpeg') }}"
                            alt="Thumbnail"
                            style="max-height: 100%; max-width: 100%; object-fit: contain;">
                    </div>

                    <h6 class="fw-bold text-center">{{ $course->course_title }}</h6>
                    {{-- 🚨 Jika sedang di-takeover → tampilkan informasi takeover --}}
                    @if($isTakeoverActive)
                        @php
                            $now = \Carbon\Carbon::now();
                            $start = \Carbon\Carbon::parse($takeover->start_date);
                            $end = \Carbon\Carbon::parse($takeover->end_date);

                            if ($end->isPast()) {
                                $statusText = 'Selesai';
                                $statusClass = 'badge bg-secondary';
                            } elseif ($end->diffInDays($now) <= 3) {
                                $statusText = 'Segera Berakhir';
                                $statusClass = 'badge bg-warning text-dark';
                            } else {
                                $statusText = 'Aktif';
                                $statusClass = 'badge bg-success';
                            }
                        @endphp

                        <div class="border-0 mt-2 mb-0 py-2 px-3 shadow-sm bg-light rounded">
                            <div class="d-flex align-items-center mb-2">
                                <h6 class="fw-bold mb-0 text-danger">Kursus Sedang Di-Takeover</h6>
                            </div>

                            <ul class="list-group list-group-flush small rounded overflow-hidden">
                                <li class="list-group-item d-flex justify-content-between align-items-center">
                                    <span class="fw-semibold text-muted">Status</span>
                                    <span class="{{ $statusClass }}">{{ $statusText }}</span>
                                </li>
                                <li class="list-group-item d-flex justify-content-between align-items-center">
                                    <span class="fw-semibold text-muted">Periode</span>
                                    <span class="fs-7 text-muted">{{ $start->format('d M Y') }} – {{ $end->format('d M Y') }}</span>
                                </li>
                                <li class="list-group-item d-flex justify-content-between align-items-center">
                                    <span class="fw-semibold text-muted">Dari</span>
                                    <span class="text-dark">{{ $takeover->oldTrainer->full_name ?? 'Tidak diketahui' }}</span>
                                </li>
                                <li class="list-group-item d-flex justify-content-between align-items-center">
                                    <span class="fw-semibold text-muted">Kepada</span>
                                    <span class="text-dark">{{ $takeover->newTrainer->full_name ?? 'Tidak diketahui' }}</span>
                                </li>
                                <li class="list-group-item d-flex justify-content-between align-items-center">
                                    <span class="fw-semibold text-muted">Alasan</span>
                                    <span class="text-dark">{{ $takeover->remarks ?? 'Belum ada Alasan' }}</span>
                                </li>
                            </ul>
                        </div>

                    @else
                        {{-- 💡 Fallback modern jika belum ada takeover --}}
                        <div class=" d-flex align-items-center justify-content-between small mt-2 py-2 px-3 mb-0 rounded shadow-sm">
                            <div class="d-flex align-items-center">
                                <i class="bi bi-info-circle me-2 fs-5 text-muted"></i>
                                <div>
                                    <div class="fw-semibold text-dark">Belum Ada Takeover</div>
                                    <div class="text-muted">Kursus ini belum pernah ditakeover.</div>
                                </div>
                            </div>
                        </div>
                    @endif
                </div>

                {{-- Footer --}}
                <div class="card-footer text-center fw-bold p-0">
                    @if($isTakeoverActive)
                        {{-- Jika sedang di-takeover, hanya tombol Batalkan --}}
                        <form action="{{ route('takeover.finish', $takeover->takeover_id) }}" method="POST" class="m-0">
                            @csrf
                            <button type="submit" class="btn btn-danger w-100 rounded-0">
                                ❌ Batalkan Takeover
                            </button>
                        </form>
                    @else
                        {{-- Default dropdown --}}
                        <div class="dropdown">
                            <button class="btn btn-secondary dropdown-toggle w-100 rounded-0" type="button" data-bs-toggle="dropdown">
                                Kelola Course
                            </button>
                            <ul class="dropdown-menu w-100">

                                <!-- <li> <a class="dropdown-item edit-course-btn" href="#" data-bs-toggle="modal" data-bs-target="#editCourseModal" data-id="{{ $course->course_id }}"
                                 data-title="{{ $course->course_title }}" data-category="{{ $course->course_category }}" data-max="{{ $course->max_participant }}" 
                                 data-describe="{{ $course->course_describe }}" data-image="{{ $course->course_image_url }}" data-public="{{ $course->is_public == 1 ? '1' : '0' }}" 
                                 data-learner="{{ $course->learner_count }}"> ⚙️ Ubah Master Kursus </a> 
                                </li> -->
                                <li>
                                    <a class="dropdown-item edit-course-btn"
                                        href="#"
                                        data-bs-toggle="modal"
                                        data-bs-target="#editCourseModal"
                                        data-id="{{ $course->course_id }}"
                                        data-title="{{ $course->course_title }}"
                                        data-category="{{ $course->course_category }}"
                                        data-max="{{ $course->max_participant }}"
                                        data-start="{{ $course->start_course }}"
                                        data-end="{{ $course->end_course }}"
                                        data-describe="{{ $course->course_describe }}"
                                        data-image="{{ $course->course_image_url }}"
                                        data-public="{{ $course->is_public == 1 ? '1' : '0' }}"
                                        data-learner="{{ $course->learner_count }}"
                                        data-enrollments='@json(
                                            $course->enrollments->map(fn($e) => [
                                                "user_id"  => $e->user_id,
                                                "role_id"  => $e->user->role_id,
                                                "sub_role" => $e->user->sub_role // bisa JSON atau null
                                            ])
                                        )'>
                                        ⚙️ Ubah Master Kursus
                                    </a>
                                    </li>
                                <li>
                                    <a class="dropdown-item" href="{{ route('instructor.detail-course', $course->course_id) }}">
                                        📖 Detail Kursus
                                    </a>
                                </li>
                                @if(!$course->is_approved)
                                    <li>
                                        <a class="dropdown-item" href="{{ route('instructor.modify-course', $course->course_id) }}">
                                            ✏️ Ubah Konten
                                        </a>
                                    </li>
                                @endif
                                <li>
                                    <a class="dropdown-item" href="{{ route('instructor.submission-course', $course->course_id) }}">
                                        📂 Unggahan Learner
                                    </a>
                                </li>
                                <li>
                                    <a class="dropdown-item" href="{{ route('instructor.track-course', $course->course_id) }}">
                                        📊 Lacak Learner
                                    </a>
                                </li>
                                <li>
                                    <a class="dropdown-item" href="#" 
                                        data-bs-toggle="modal"
                                        data-bs-target="#duplicateCourse"
                                        data-id="{{ $course->course_id }}"
                                        data-title="{{ $course->course_title }}"
                                        > 📋 Duplikat Kursus 
                                        
                                    </a>
                                </li>

                                <li><hr class="dropdown-divider"></li>

                                {{-- Takeover action --}}
                                @if(!$course->is_takeover)
                                    <a class="dropdown-item text-danger takeover-course-btn"
                                        href="#"
                                        data-bs-toggle="modal"
                                        data-bs-target="#takeoverCourseModal"
                                        data-id="{{ $course->course_id }}"
                                        data-title="{{ $course->course_title }}"
                                        data-course-trainer="{{ $course->course_trainer_id }}"
                                        data-course-trainer-name="{{ $course->trainer->full_name ?? $course->trainer->name ?? 'Unknown' }}">
                                        🚀 Takeover Kursus
                                    </a>
                                @else
                                    <span class="dropdown-item text-muted disabled">🚫 Sedang Di Takeover</span>
                                @endif
                                
                                @if($course->is_approved)
                                <a class="dropdown-item text-danger takedown-btn"
                                    href="#TakedownCourse"
                                    data-bs-toggle="modal"
                                    data-bs-target="#TakedownCourse"
                                    data-id="{{ $course->course_id }}"
                                    data-title="{{ $course->course_title }}"
                                    data-course-trainer="{{ $course->course_trainer_id }}"
                                    data-course-trainer-name="{{ $course->trainer->full_name ?? $course->trainer->name ?? 'Unknown' }}">
                                    ❌ Takedown Kursus
                                </a>
                                @endif


                                <li><hr class="dropdown-divider"></li>

                                {{-- Delete --}}
                                @if($course->is_approved || $course->is_requested)
                                    <a class="dropdown-item text-warning"
                                    href="{{ route('approval.index', ['course_id' => $course->course_id, 'delete' => 1]) }}">
                                    Ajukan Penghapusan
                                    </a>
                                @else
                                    <form action="{{ route('instructor.delete', $course->course_id) }}" method="POST"
                                        onsubmit="return confirm('Hapus Kursus ini?')">
                                        @csrf
                                        <button type="submit" class="dropdown-item text-danger">🗑️ Hapus Kursus</button>
                                    </form>
                                @endif
                            </ul>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    @empty
        <div class="col-12">
            <p class="text-center text-muted">Tidak ada Kursus yang dibuat oleh Instructor.</p>
        </div>
    @endforelse
</div>
<!-- Modal Unggahan Learner -->
<div class="modal fade" id="submissionModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title fw-bold">Learner Submission</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                
                <!-- Container assignment -->
                <div class="mb-4 border rounded p-3 bg-white">
                    <h6 class="fw-bold">Course 1 - Week 1 - Item 1</h6>

                    <!-- Example submission -->
                    <div class="d-flex justify-content-between align-items-center border p-2 rounded mb-2">
                        <div>Title, description, attachment.pdf</div>
                        <button class="btn btn-outline-primary btn-sm">Grade</button>
                    </div>
                    <div class="d-flex justify-content-between align-items-center border p-2 rounded mb-2">
                        <div>Title, description, attachment.docx</div>
                        <span class="badge bg-secondary fs-6">10/10</span>
                    </div>
                </div>

            </div>
        </div>
    </div>
</div>
<!-- Modal Lacak Learner -->
<div class="modal fade" id="trackModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title fw-bold">Lacak Learner</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">

                <!-- Example Learner Tracking -->
                <div class="border rounded p-3 bg-white mb-3">
                    <h6 class="fw-bold">Course 1</h6>
                    <div class="d-flex align-items-center gap-3 flex-wrap">
                        <span class="fw-bold">Learner Name</span>
                        <span class="badge bg-success">Tidak Submit : 5</span>
                        <span class="badge bg-danger">Submit : 3</span>
                        <span class="badge bg-info text-dark">Total Absen: 1</span>
                        <span class="badge bg-danger"> Gagal : 5</span>
                        <span class="badge bg-secondary">Status: Aktif</span>
                        <button class="btn btn-outline-secondary btn-sm ms-auto">Ajukan Konsultasi</button>
                    </div>
                </div>

            </div>
        </div>
    </div>
</div>
<!-- Modal Konfirmasi Hapus -->
<div class="modal" id="deleteCourseModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content">
      <div class="modal-body text-center">
        <p class="fw-bold mb-3">Apakah Anda ingin menghapus Kursus ini?</p>
        <p>"Saya Instructor <span id="courseName">Nama Kursus</span> ingin menghapus kursus ini"</p>

        <!-- Input konfirmasi -->
        <input type="text" class="form-control my-3" placeholder="Ketik nama kursus untuk konfirmasi" id="confirmInput">

        <!-- Tombol aksi -->
        <div class="d-flex justify-content-center gap-3">
          <button class="btn btn-secondary" data-bs-dismiss="modal">Batalkan</button>
          <button class="btn btn-danger" id="submitDelete">Ajukan Penghapusan</button>
        </div>
      </div>
    </div>
  </div>
</div>
<!-- Tombol Melayang -->
<button class="btn btn-secondary rounded-circle shadow"  data-bs-toggle="modal" data-bs-target="#createCourseModal" style="position: fixed; bottom: 20px; right: 20px; width: 60px; height: 60px; z-index: 10000;"> + </button>
<!-- Modal Create Course -->
<div class="modal fade" id="createCourseModal" tabindex="-1" aria-hidden="true"
     data-bs-backdrop="static" data-bs-keyboard="false">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content">

            <!-- Header Modal -->
            <div class="modal-header">
                <h5 class="modal-title">Buat Kursus Baru</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>

            <!-- Body Modal -->
            <div class="modal-body">
                <form id="createCourseForm" action="{{ route('instructor.store') }}" method="POST" enctype="multipart/form-data" novalidate>
                    @csrf
                    <div class="row">
                        <!-- Kolom Kiri -->
                        <div class="col-md-4">
                            <div class="mb-3">
                                <label class="form-label">Judul Kursus <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" name="course_title" placeholder="Tuliskan Judul Kursus..."required>
                                <div class="invalid-feedback">Judul kursus wajib diisi.</div>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Kategori Kursus <span class="text-danger">*</span></label>
                                <select class="form-select" name="course_category" required>
                                    <option value="">-- Pilih Kategori --</option>
                                    @php
                                        $categories = \App\Models\CourseCategory::where('is_active', 1)->orderBy('category_id')->get();
                                    @endphp
                                    @foreach($categories as $cat)
                                        <option value="{{ $cat->category_name }}">{{ $cat->category_name }}</option>
                                    @endforeach
                                </select>
                                <div class="invalid-feedback">Kategori kursus wajib dipilih.</div>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Nama Trainer <span class="text-danger">*</span></label>
                                <input type="text" 
                                    class="form-control bg-light text-muted" 
                                    name="course_trainer_name"
                                    value="{{ Auth::user()->full_name ?? Auth::user()->name }}" 
                                    readonly 
                                    tabindex="-1" 
                                    style="pointer-events: none; user-select: none; opacity: 0.8;">
                                <div class="form-text text-muted">
                                    Kolom ini terisi otomatis berdasarkan akun instructor yang sedang login.
                                </div>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Maksimal Peserta <span class="text-danger">*</span></label>
                                <input type="number" class="form-control" name="max_participant" min="1" placeholder="Jumlah Maksimal Peserta..." required>
                                <div class="invalid-feedback">Isi jumlah maksimal peserta (≥1).</div>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Gambar Kursus</label>
                                <input type="file" class="form-control" name="course_image" accept="image/*">
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Visibilitas Kursus <span class="text-danger">*</span></label>
                                <select class="form-select" name="is_public" required>
                                    <option value="">-- Pilih Visibilitas --</option>
                                    <option value="1">Public</option>
                                    <option value="0">Private</option>
                                </select>
                                <div class="invalid-feedback">Visibilitas wajib dipilih.</div>
                            </div>
                        </div>

                        <!-- Kolom Kanan -->
                        <div class="col-md-8 d-flex flex-column">
                            <div class="mb-3"> 
                                <label class="form-label"> Tanggal dan Waktu dibuka Course </label>
                                <input type="datetime-local" class="form-control" name="start_course">
                            </div>
                            <div class="mb-3"> 
                                <label class="form-label"> Tanggal dan Waktu ditutup Course </label>
                                <input type="datetime-local" class="form-control" name="end_course">
                            </div>
                            
                            <div class="mb-3 flex-fill">
                                <label class="form-label">Deskripsi Kursus <span class="text-danger">*</span></label>
                                <textarea class="form-control" rows="10" name="course_describe" placeholder="Masukkan Deskripsi Kursus...." required></textarea>
                                <div class="invalid-feedback">Deskripsi kursus wajib diisi.</div>
                            </div>
                        </div>
                    </div>

                    <!-- Footer Modal -->
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                        <button type="submit" class="btn btn-warning">Buat Kursus</button>
                    </div>
                </form>
            </div>

        </div>
    </div>
</div>
<!-- Modal Edit Course  -->
<div class="modal fade" id="editCourseModal" tabindex="-1" aria-hidden="true" data-bs-backdrop="static" data-bs-keyboard="false">
  <div class="modal-dialog modal-dialog-centered modal-lg">
    <div class="modal-content">

      <div class="modal-header">
        <h5 class="modal-title">Ubah Informasi Kursus</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>

      <div class="modal-body">
        <form id="editCourseForm" 
            method="POST" 
            enctype="multipart/form-data" 
            action="{{ route('instructor.edit', ['id' => 0]) }}" 
            novalidate>
            @csrf
            @method('PUT')
          <div class="row">
            <div class="col-md-4">
              <div class="mb-3">
                <label class="form-label">Judul Kursus <span class="text-danger">*</span></label>
                <input type="text" class="form-control" name="course_title" id="edit_course_title" required>
                <div class="invalid-feedback">Judul kursus wajib diisi.</div>
              </div>
                <div class="mb-3">
                <label class="form-label">Kategori Kursus <span class="text-danger">*</span></label>
                <select class="form-select" name="course_category" id="edit_course_category" required>
                    <option value="">-- Pilih Kategori --</option>
                    @php
                        $categories = \App\Models\CourseCategory::where('is_active', 1)->orderBy('category_id')->get();
                    @endphp
                    @foreach($categories as $cat)
                        <option value="{{ $cat->category_name }}">{{ $cat->category_name }}</option>
                    @endforeach
                </select>
                <div class="invalid-feedback">Kategori kursus wajib dipilih.</div>
                </div>
              <div class="mb-3">
                <label class="form-label">Maksimal Peserta <span class="text-danger">*</span></label>
                <input type="number" class="form-control" name="max_participant" id="edit_max_participant" min="1" required>
                <div class="invalid-feedback">Isi jumlah maksimal peserta (≥1).</div>
              </div>
              <div class="mb-3">
                <label class="form-label">Gambar Kursus</label>
                <input type="file" class="form-control" name="course_image" accept="image/*">
                <img id="edit_preview_image" class="mt-2 rounded" style="max-height:120px; display:none;">
              </div>
              <div class="mb-3">
                <label class="form-label">Visibilitas <span class="text-danger">*</span></label>
                <select class="form-select" name="is_public" id="edit_is_public" required>
                  <option value="">-- Pilih Visibilitas --</option>
                  <option value="1">Public</option>
                  <option value="0">Private</option>
                </select>
                <div class="invalid-feedback">Visibilitas wajib dipilih.</div>
              </div>
            </div>
            <div class="col-md-8">
                <div class="mb-3"> 
                    <label class="form-label"> Tanggal dan Waktu dibuka Course </label>
                    <input type="datetime-local" class="form-control" id="edit_start_course" name="start_course">
                </div>
                <div class="mb-3"> 
                    <label class="form-label"> Tanggal dan Waktu ditutup Course </label>
                    <input type="datetime-local" class="form-control" id="edit_end_course" name="end_course">
                </div>
                <div class="mb-3">
                    <label class="form-label">Deskripsi <span class="text-danger">*</span></label>
                    <textarea class="form-control" rows="10" name="course_describe" id="edit_course_describe" required></textarea>
                    <div class="invalid-feedback">Deskripsi kursus wajib diisi.</div>
                </div>
            </div>
          </div>
          <div class="modal-footer">
            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
            <button type="submit" class="btn btn-warning">Simpan Perubahan</button>
          </div>
        </form>
      </div>

    </div>
  </div>
</div>
<!-- Modal Takeover Course -->
<div class="modal fade" id="takeoverCourseModal" tabindex="-1" aria-labelledby="takeoverCourseLabel" aria-hidden="true" data-bs-backdrop="static" data-bs-keyboard="false">
  <div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable">
    <div class="modal-content" style="max-height: 90vh; overflow-y: auto;">
      <div class="modal-header">
        <h5 class="modal-title fw-bold" id="takeoverCourseLabel">Takeover Kursus</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>

      <form id="takeoverCourseForm" action="{{ route('instructor.takeoverbyInstructor') }}" method="POST" novalidate>
        @csrf
        <div class="modal-body">

            <p class="text-muted small">Mohon diperiksa kembali detail informasi kursus</p>

            <!-- Course Info -->
            <div class="mb-3">
                <label class="form-label">Course</label>
                <input type="text" id="takeover_course_title" class="form-control" value="-" readonly>
                <input type="hidden" name="takeover[course_id]" id="takeover_course_id">
            </div>

            <!-- Old Trainer -->
            <div class="mb-3">
                <label class="form-label">Instructor Sebelumnya</label>
                <input type="text" id="takeover_old_trainer_name" class="form-control" value="-" readonly>
                <input type="hidden" name="takeover[old_trainer_id]" id="takeover_old_trainer_id">
            </div>
            
            <!-- Pilih Instructor Baru -->
            <div class="mb-3">
                <label for="takeover_new_trainer_id" class="form-label">Takeover Instructor <span class="text-danger">*</span></label>
                <select class="form-select" name="takeover[new_trainer_id]" id="takeover_new_trainer_id" required>
                    <option value="">-- Pilih Instructor --</option>
                    @php
                        $trainers = \App\Models\User::where('is_deleted', 0)
                            ->where(function ($q) {
                                $q->where('role_id', 3)
                                  ->orWhereJsonContains('sub_role', 3);
                            })
                            ->get();
                    @endphp
                    @foreach($trainers as $trainer)
                        <option value="{{ $trainer->user_id }}">
                            {{ $trainer->full_name ?? $trainer->name }} ({{ $trainer->emp_id }})
                        </option>
                    @endforeach
                </select>
                <div class="invalid-feedback">Instructor takeover wajib dipilih.</div>
            </div>

            <!-- Periode Takeover -->
            <div class="row">
                <div class="col-md-6 mb-3">
                    <label for="takeover_start_date" class="form-label">Start Date <span class="text-danger">*</span></label>
                    <input type="date" class="form-control" id="takeover_start_date" name="takeover[start_date]" required>
                    <div class="invalid-feedback">Tanggal mulai wajib diisi.</div>
                </div>
                <div class="col-md-6 mb-3">
                    <label for="takeover_end_date" class="form-label">End Date <span class="text-danger">*</span></label>
                    <input type="date" class="form-control" id="takeover_end_date" name="takeover[end_date]" required>
                    <div class="invalid-feedback">Tanggal akhir wajib diisi.</div>
                </div>
            </div>

            <!-- Remarks -->
            <div class="mb-3">
                <label for="takeover_remarks" class="form-label">Alasan Takeover</label>
                <textarea class="form-control" id="takeover_remarks" name="takeover[remarks]" rows="3" placeholder="Catatan tambahan..."></textarea>
            </div>

        </div>
        <div class="modal-footer">
          <!-- <button type="submit" class="btn btn-secondary">Reset Takeover</button> -->
          <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
          <button type="submit" class="btn btn-warning">Simpan Takeover</button>
        </div>
      </form>
    </div>
  </div>
</div>
<!-- ✅ Modal Takedown Course -->
<div class="modal fade" id="TakedownCourse" tabindex="-1" aria-labelledby="TakedownCourseLabel"
     aria-hidden="true" data-bs-backdrop="static" data-bs-keyboard="false">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow-sm">

            <!-- Header -->
            <div class="modal-header bg-light border-bottom-0">
                <h5 class="modal-title fw-bold text-danger" id="TakedownCourseLabel">
                    ⚠️ Konfirmasi Takedown Kursus
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>

            <!-- Body -->
            <div class="modal-body">
                <p class="mb-3 text-center">
                    Apakah Anda yakin ingin melakukan <strong>takedown</strong> kursus berikut?
                </p>
                <div class="text-center fw-semibold text-dark py-2 mb-3" id="takedownCourseTitle">
                    <!-- Judul kursus akan terisi via JS -->
                </div>
                <small class="text-muted d-block text-center">
                    *Ketika kursus ditakedown, kursus akan hilang dari sisi learner, dan instructor perlu melakukan persetujuan ulang untuk aktivasi.
                </small>
            </div>

            <!-- Footer -->
            <div class="modal-footer border-top-0">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                    Batal
                </button>
                <form id="takedownForm" method="POST" action="">
                    @csrf
                    <input type="hidden" name="course_id" id="takedownCourseId">
                    <button type="submit" class="btn btn-danger fw-semibold">
                        Ya, Takedown
                    </button>
                </form>
            </div>

        </div>
    </div>
</div>
<!-- ✅ Modal Duplikat Course -->
<div class="modal fade" id="duplicateCourse" tabindex="-1" aria-hidden="true"
     data-bs-backdrop="static" data-bs-keyboard="false">
      <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content shadow-sm">
      
      <!-- Header -->
      <div class="modal-header bg-light">
        <h5 class="modal-title fw-semibold">
          📋 Duplikat Kursus
        </h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <!-- Body -->
      <div class="modal-body text-center">
        <p class="fs-6">
          Anda akan menduplikasi course berikut:
        </p>
        <h6 class="fw-bold" id="duplicateCourseName">-</h6>
        <p class="small mt-2">
          Semua data kursus dan materi di dalamnya akan disalin ke course baru.
          <br>
          Nama baru akan menjadi:
          <br>
          <span id="duplicateCopyName" class="fw-bold text-warning">Copy - (Nama Course)</span>
        </p>
      </div>

      <!-- Footer -->
      <div class="modal-footer justify-content-center">
        <form id="duplicateCourseForm" method="POST" action="">
          @csrf
          <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
            Batal
          </button>
          <button type="submit" class="btn btn-warning">
            📋 Ya, Duplikat Course
          </button>
        </form>
      </div>

    </div>
  </div>
</div>


{{-- Script Validasi Bootstrap --}}
<script>
document.addEventListener("DOMContentLoaded", function () {

    // ===========================
    // VALIDASI CREATE COURSE
    // ===========================
    const createForm = document.getElementById('createCourseForm');
    if (createForm) {
        createForm.addEventListener('submit', function (event) {
            if (!createForm.checkValidity()) {
                event.preventDefault();
                event.stopPropagation();
            }
            createForm.classList.add('was-validated');
        }, false);
    }

    // ===========================
    // VALIDASI EDIT COURSE
    // ===========================
    const editForm = document.getElementById('editCourseForm');
    const maxInput = document.getElementById('edit_max_participant');
    let currentLearnerCount = 0;

    if (editForm) {
        // 🔹 Validasi saat submit
        editForm.addEventListener('submit', function (event) {
            if (!editForm.checkValidity()) {
                event.preventDefault();
                event.stopPropagation();
            }

            const newMax = parseInt(maxInput.value || 0);

            // 🔒 Validasi tambahan — tidak boleh di bawah jumlah learner
            if (newMax < currentLearnerCount) {
                event.preventDefault();
                event.stopPropagation();
                showInvalidFeedback(
                    maxInput,
                    `Tidak bisa di bawah (${currentLearnerCount}) karena sudah ada learner tergabung sebelumnya.`
                );
                return;
            }

            editForm.classList.add('was-validated');
        }, false);

        // 🔹 Validasi langsung saat user mengetik
        maxInput.addEventListener('input', function () {
            const newMax = parseInt(maxInput.value || 0);
            if (newMax < currentLearnerCount) {
                showInvalidFeedback(
                    maxInput,
                    `Tidak bisa di bawah (${currentLearnerCount}) karena sudah ada learner tergabung sebelumnya.`
                );
            } else {
                clearInvalidFeedback(maxInput);
            }
        });
    }

    document.querySelectorAll('.edit-course-btn').forEach(btn => {
        btn.addEventListener('click', function () {
            const courseId = this.dataset.id;
            let learnerCount = parseInt(this.dataset.learner || 0);

            // 🧩 Filter enrollment agar tidak menghitung user IT (role_id=1 atau sub_role mengandung 1)
            if (this.dataset.enrollments) {
                try {
                    const enrollments = JSON.parse(this.dataset.enrollments);

                    learnerCount = enrollments.filter(e => {
                        const roleId = parseInt(e.role_id);
                        let subRoles = [];

                        // pastikan sub_role bisa berupa string JSON atau array
                        if (e.sub_role) {
                            try {
                                subRoles = Array.isArray(e.sub_role)
                                    ? e.sub_role
                                    : JSON.parse(e.sub_role);
                            } catch {
                                subRoles = [];
                            }
                        }

                        // ✅ exclude jika role_id = 1 atau sub_role berisi 1
                        return roleId !== 1 && !subRoles.includes(1);
                    }).length;
                } catch (err) {
                    console.warn("⚠️ Gagal parse enrollments JSON:", err);
                }
            }

            // --- lanjut logika existing ---
            const form = document.getElementById('editCourseForm');
            const maxInput = document.getElementById('edit_max_participant');
            const startInput = document.getElementById('edit_start_course');
            const endInput   = document.getElementById('edit_end_course');

            const startRaw = this.dataset.start;
            const endRaw   = this.dataset.end;

            if (startRaw) {
                // ubah ke format "YYYY-MM-DDTHH:MM"
                const formattedStart = new Date(startRaw).toISOString().slice(0, 16);
                startInput.value = formattedStart;
            }

            if (endRaw) {
                const formattedEnd = new Date(endRaw).toISOString().slice(0, 16);
                endInput.value = formattedEnd;
            }

            form.action = `/instructor-course/editCourse/${courseId}`;
            document.getElementById('edit_course_title').value    = this.dataset.title;
            document.getElementById('edit_course_category').value = this.dataset.category;
            document.getElementById('edit_max_participant').value = this.dataset.max;
            document.getElementById('edit_course_describe').value = this.dataset.describe;
            document.getElementById('edit_is_public').value       = this.dataset.public;
            document.getElementById('edit_start_course').value    = this.dataset.start;
            document.getElementById('edit_end_course').value      = this.dataset.end;


            maxInput.addEventListener('input', () => {
                const newMax = parseInt(maxInput.value);
                maxInput.classList.remove('is-invalid');
                let feedback = maxInput.parentElement.querySelector('.invalid-feedback');
                if (feedback) feedback.remove();

                if (newMax < learnerCount) {
                    maxInput.classList.add('is-invalid');
                    const fb = document.createElement('div');
                    fb.classList.add('invalid-feedback');
                    fb.textContent = `Tidak bisa di bawah (${learnerCount}) karena sudah ada learner tergabung sebelumnya.`;
                    maxInput.parentElement.appendChild(fb);
                }
            });

            const editForm = document.getElementById('editCourseForm');
            editForm.addEventListener('submit', function (event) {
                const newMax = parseInt(maxInput.value);
                if (newMax < learnerCount) {
                    event.preventDefault();
                    event.stopPropagation();
                    maxInput.classList.add('is-invalid');
                }
            });

            const img = document.getElementById('edit_preview_image');
            if (this.dataset.image) {
                img.src = this.dataset.image;
                img.style.display = 'block';
            } else {
                img.style.display = 'none';
            }
        });
    });

    // ===========================
    // AUTOFILL MODAL TAKEOVER
    // ===========================
    const takeoverModal = document.getElementById("takeoverCourseModal");
    if (takeoverModal) {
        takeoverModal.addEventListener("show.bs.modal", function (event) {
            const button = event.relatedTarget;
            const courseId = button.getAttribute("data-id");
            const courseTitle = button.getAttribute("data-title");
            const courseTrainerId = button.getAttribute("data-course-trainer");
            const courseTrainerName = button.getAttribute("data-course-trainer-name") || "-";

            document.getElementById("takeoverCourseLabel").textContent = "Takeover Kursus: " + courseTitle;
            document.getElementById("takeover_course_title").value = courseTitle;
            document.getElementById("takeover_course_id").value = courseId;
            document.getElementById("takeover_old_trainer_name").value = courseTrainerName;
            document.getElementById("takeover_old_trainer_id").value = courseTrainerId;
        });
    }

    // ===========================
    // Takedown 
    // ===========================
        const takedownButtons = document.querySelectorAll(".takedown-btn");
        const takedownForm = document.getElementById("takedownForm");
        const takedownCourseId = document.getElementById("takedownCourseId");
        const takedownTitle = document.getElementById("takedownCourseTitle");

        takedownButtons.forEach(button => {
            button.addEventListener("click", () => {
                const courseId = button.dataset.id;
                const title = button.dataset.title;

                takedownTitle.textContent = `📘 ${title}`;
                takedownCourseId.value = courseId;
                takedownForm.action = `/instructor-course/takedown/${courseId}`;
            });
        });

    // ===========================
    // VALIDASI TAKEOVER FORM
    // ===========================
    const takeoverForm = document.getElementById('takeoverCourseForm');
    if (takeoverForm) {
        takeoverForm.addEventListener('submit', function (event) {
            if (!takeoverForm.checkValidity()) {
                event.preventDefault();
                event.stopPropagation();
            }
            takeoverForm.classList.add('was-validated');
        }, false);
    }

    // ===========================
    // HELPER FUNCTIONS
    // ===========================
    function showInvalidFeedback(input, message) {
        input.classList.add('is-invalid');
        let fb = input.parentElement.querySelector('.invalid-feedback');
        if (!fb) {
            fb = document.createElement('div');
            fb.classList.add('invalid-feedback');
            input.parentElement.appendChild(fb);
        }
        fb.textContent = message;
    }

    function clearInvalidFeedback(input) {
        input.classList.remove('is-invalid');
        const fb = input.parentElement.querySelector('.invalid-feedback');
        if (fb) fb.remove();
    }

    // Duplikat course
        const duplicateModal = document.getElementById('duplicateCourse');
        const courseNameEl   = document.getElementById('duplicateCourseName');
        const copyNameEl       = document.getElementById('duplicateCopyName');
        const form           = document.getElementById('duplicateCourseForm');

        duplicateModal.addEventListener('show.bs.modal', function(event) {
            const button = event.relatedTarget; // tombol yang membuka modal
            const courseId = button.getAttribute('data-id');
            const courseTitle = button.getAttribute('data-title');

            // 🟡 Tampilkan nama asli di bagian atas
            courseNameEl.textContent = courseTitle;

            // 🟢 Tampilkan versi "Copy - (Nama Course)"
            copyNameEl.textContent = `Copy - ${courseTitle}`;


        // 🟢 Update form action (sesuai route duplikat)
        form.action = `/instructor-course/duplicate/${courseId}`;
    });
});
</script>

@endsection