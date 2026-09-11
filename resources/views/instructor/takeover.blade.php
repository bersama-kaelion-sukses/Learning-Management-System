@extends('layouts.master')

@section('title', 'Kursus Takeover Saya')

@section('content')
<div class="container py-4">
    <div class="d-flex flex-column mb-4 border-bottom pb-3 align-items-center text-center">
        <h2 class="fw-bold text-dark mb-1">Takeover Kursus </h2>
        <small class="text-muted">Kelola kursus Instructor lain</small>
    </div>
    @php
        use Carbon\Carbon;
    @endphp

    <div class="row g-4">
        @forelse ($takeovers as $takeover)
            @php
                $course = $takeover->course;
                $startDate = $takeover->start_date ? Carbon::parse($takeover->start_date) : null;
                $endDate   = $takeover->end_date ? Carbon::parse($takeover->end_date) : null;
            @endphp
            <div class="col-md-3 col-sm-6">
                <div class="card shadow-sm h-100 position-relative 
                    {{ $isSuperAdmin && $takeover->status !== 'active' ? 'opacity-75' : '' }}">

                    {{-- Badge takeover status --}}
                    <span class="badge 
                        {{ $takeover->status === 'active' ? 'bg-success' 
                            : ($takeover->status === 'pending' ? 'bg-warning text-dark' 
                            : ($takeover->status === 'reverted' ? 'bg-danger' 
                            : 'bg-secondary')) }} 
                        position-absolute top-0 end-0 m-2">
                        {{ ucfirst($takeover->status) }}
                    </span>

                    {{-- Badge status publikasi --}}
                    <span class="badge 
                        {{ $course->is_approved ? 'bg-success' : 'bg-secondary' }} 
                        position-absolute top-0 start-0 m-2">
                        {{ $course->is_approved ? 'Telah Disetujui' : 'Belum Disetujui' }}
                    </span>

                   <div class="card-body d-flex flex-column justify-content-between">

                    {{-- Thumbnail --}}
                    <div class="mb-3 bg-light d-flex justify-content-center align-items-center"
                        style="height:140px; border:1px solid #ddd;">
                        <img src="{{ $course->course_image 
                                    ? asset('assets/img/course/' . $course->course_image) 
                                    : asset('assets/img/course/default-course.jpeg') }}"
                            alt="Thumbnail"
                            style="max-height: 100%; max-width: 100%; object-fit: contain;">
                    </div>

                    {{-- Judul Kursus --}}
                    <h6 class="fw-bold text-center">{{ $course->course_title }}</h6>

                    @php
                        $startDate = $takeover->start_date ? Carbon::parse($takeover->start_date) : null;
                        $endDate   = $takeover->end_date   ? Carbon::parse($takeover->end_date)   : null;
                        $now       = Carbon::now();
                        $daysLeft  = null;

                        if ($startDate && $endDate) {
                            if ($endDate->lt($startDate)) {
                                $daysLeft = 0;
                            } else {
                                if ($takeover->status === 'pending') {
                                    $daysLeft = null; // pending → belum dimulai
                                }
                                elseif ($takeover->status === 'active') {
                                    $daysLeft = $now->lessThanOrEqualTo($endDate)
                                        ? $now->diffInDays($endDate) + 1
                                        : 0;
                                }
                                else {
                                    $daysLeft = 0;
                                }
                            }
                        }

                        $isPending  = ($takeover->status === 'pending');
                        $isEndToday = $endDate && $now->isSameDay($endDate);
                        $isEnded    = $endDate && $now->greaterThan($endDate);
                    @endphp

                    {{-- DETAIL LIST --}}
                    <ul class="list-group list-group-flush small mt-2">

                        {{-- Periode --}}
                        @if($startDate && $endDate)
                            <li class="list-group-item d-flex justify-content-between align-items-center">
                                <span class="fw-semibold">Periode</span>
                                <span class="text-dark text-wrap">
                                    {{ $startDate->format('d M Y') }} s/d {{ $endDate->format('d M Y') }}
                                </span>
                            </li>
                        @endif

                        {{-- Sisa Waktu --}}
                        @if($startDate && $endDate)
                            <li class="list-group-item d-flex justify-content-between align-items-center">
                                <span class="fw-semibold">Sisa Waktu</span>

                                @if($isPending)
                                    <span class="text-primary fw-bold">Belum dimulai</span>

                                @elseif($isEndToday)
                                    <span class="fw-bold">Habis Hari Ini</span>

                                @elseif($isEnded)
                                    <span class="text-danger fw-bold">Sudah berakhir</span>

                                @elseif(!is_null($daysLeft))
                                    <span class="text-success fw-bold">{{ $daysLeft }} hari lagi</span>

                                @endif
                            </li>
                        @endif

                        {{-- Dibuat Pada --}}
                        <li class="list-group-item d-flex justify-content-between align-items-center">
                            <span class="fw-semibold">Dibuat Pada</span>
                            <span class="text-dark">{{ $takeover->created_at->format('d M Y, H:i') }}</span>
                        </li>

                        {{-- Perubahan Trainer --}}
                        <li class="list-group-item">
                            <span class="fw-semibold d-block">Perubahan Trainer</span>
                            <span class="text-dark fw-bold">
                                {{ $takeover->oldTrainer->full_name ?? '-' }}
                                →
                                {{ $takeover->newTrainer->full_name ?? '-' }}
                            </span>
                        </li>

                        {{-- Alasan Takeover --}}
                        <li class="list-group-item d-flex justify-content-between align-items-center">
                            <span class="fw-semibold">Alasan</span>
                            <span class="text-dark">
                                {{ $takeover->remarks ?? 'Belum ada Alasan' }}
                            </span>
                        </li>
                        @if ($takeover->status === 'pending')
                            <li class="list-group-item d-flex justify-content-between align-items-center w-100">
                                <form action="{{ route('takeover.revert', $takeover->takeover_id) }}" 
                                    method="POST" 
                                    class="w-100"
                                    onsubmit="return confirm('Yakin ingin membatalkan takeover ini?');">
                                    @csrf
                                    <button class="btn btn-sm btn-danger w-100">
                                        Batalkan Takeover
                                    </button>
                                </form>
                            </li>
                        @endif
                    </ul>

                </div>


                    <div class="card-footer text-center fw-bold">
                        <div class="dropdown">
                            <button class="btn btn-secondary dropdown-toggle w-100" 
                                    type="button" 
                                    data-bs-toggle="dropdown"
                                    {{ (!$isSuperAdmin && $takeover->status === 'pending') ? 'disabled' : '' }}>
                                Kelola Course
                            </button>
                            <ul class="dropdown-menu">
                                <li> 
                                    <a class="dropdown-item edit-course-btn" href="#" 
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
                                        )'>⚙️ Ubah Master Kursus </a>
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
                                        data-bs-target="#takeover_duplicateCourse"
                                        data-id="{{ $course->course_id }}"
                                        data-title="{{ $course->course_title }}"
                                        > 📋 Duplikat Kursus 
                                        
                                    </a>
                                </li>
                                <li><hr class="dropdown-divider"></li>
                                <li>
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
                                </li>
                                <li>
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
                                </li>
                                <li>
                                    <button class="dropdown-item text-danger" 
                                            data-bs-toggle="modal" 
                                            data-bs-target="#finishTakeoverModal"
                                            data-course="{{ $course->course_title }}"
                                            data-id="{{ $takeover->takeover_id }}">
                                        ✅ Selesaikan Takeover
                                    </button>
                                </li>
                            </ul>
                        </div>
                    </div>
                </div>
            </div>
        @empty
            <div class="col-12 text-center py-5">
                @if ($isSuperAdmin)
                    <h5 class="fw-bold text-muted mb-2">📭 Tidak Ada Takeover</h5>
                    <p class="text-secondary">
                        Belum ada data takeover (pending, active, maupun riwayat).
                    </p>
                @else
                    <h5 class="fw-bold text-muted mb-2"> Tidak Ada Takeover Aktif</h5>
                    <p class="text-secondary">
                        Saat ini Anda tidak memiliki kursus takeover yang sedang berlangsung.<br>
                        Silakan cek kembali nanti atau hubungi admin jika Anda merasa ada kesalahan.
                    </p>
                @endif
            </div>
        @endforelse
    </div>
</div>

<!-- Modal Konfirmasi -->
<div class="modal fade" id="finishTakeoverModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content">
      <form method="POST" id="finishTakeoverForm">
        @csrf
        <div class="modal-header">
          <h5 class="modal-title fw-bold">Konfirmasi Selesaikan Takeover</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
        </div>
        <div class="modal-body text-center">
          <p class="mb-3">Apakah Anda yakin ingin menyelesaikan takeover untuk kursus:</p>
          <h6 id="courseName" class="fw-bold text-danger"></h6>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batalkan</button>
          <button type="submit" class="btn btn-success">Ya, Selesaikan</button>
        </div>
      </form>
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
            action="{{ route('takeover.courseMasterEdit', ['id' => 0]) }}"
            novalidate>
            @csrf
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
                        $categories = \App\Models\CourseCategory::where('is_active', 1)->orderBy('category_name')->get();
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
                    *Ketika kursus ditakedown, kursus akan hilang dari sisi learner, dan instructor perlu melakukan persetujuan ulang untuk ditampilkan lagi.
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
<div class="modal fade" id="takeover_duplicateCourse" tabindex="-1" aria-hidden="true"
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
        <h6 class="fw-bold" id="takeover_duplicateCourseName">-</h6>
        <p class="small mt-2">
          Semua data kursus dan materi di dalamnya akan disalin ke course baru.
          <br>
          Nama baru akan menjadi:
          <br>
          <span id="takeover_duplicateCopyName" class="fw-bold text-warning">Copy - (Nama Course)</span>
        </p>
      </div>

      <!-- Footer -->
      <div class="modal-footer justify-content-center">
        <form id="takeover_duplicateCourseForm" method="POST" action="">
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

{{-- Script untuk binding data --}}
<script>
document.addEventListener("DOMContentLoaded", () => {
    const modal = document.getElementById("finishTakeoverModal");

    modal.addEventListener("show.bs.modal", function (event) {
        const button = event.relatedTarget;
        const courseTitle = button.getAttribute("data-course");
        const takeoverId = button.getAttribute("data-id");

        this.querySelector("#courseName").textContent = courseTitle;
        this.querySelector("#finishTakeoverForm").action = "/takeover/finish/" + takeoverId;
    });

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

            form.action = `/takeover/editCourseDepan/${courseId}`;
            document.getElementById('edit_course_title').value    = this.dataset.title;
            document.getElementById('edit_course_category').value = this.dataset.category;
            document.getElementById('edit_max_participant').value = this.dataset.max;
            document.getElementById('edit_course_describe').value = this.dataset.describe;
            document.getElementById('edit_is_public').value       = this.dataset.public;
            // document.getElementById('edit_start_course').value    = this.dataset.start;
            // document.getElementById('edit_end_course').value      = this.dataset.end;


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
        // ==================
        //  Duplikat course
        // =================
        const duplicateModal = document.getElementById('takeover_duplicateCourse');
        const courseNameEl   = document.getElementById('takeover_duplicateCourseName');
        const copyNameEl       = document.getElementById('takeover_duplicateCopyName');
        const form           = document.getElementById('takeover_duplicateCourseForm');

        duplicateModal.addEventListener('show.bs.modal', function(event) {
            const button = event.relatedTarget; // tombol yang membuka modal
            const courseId = button.getAttribute('data-id');
            const courseTitle = button.getAttribute('data-title');

            // 🟡 Tampilkan nama asli di bagian atas
            courseNameEl.textContent = courseTitle;

            // 🟢 Tampilkan versi "Copy - (Nama Course)"
            copyNameEl.textContent = `Copy - ${courseTitle}`;


        // 🟢 Update form action (sesuai route duplikat)
        form.action = `/takeover/duplicate/${courseId}`;
    });
});
</script>
@endsection
