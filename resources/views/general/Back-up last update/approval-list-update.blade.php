@extends('layouts.master')
@section('title', 'Daftar Persetujuan')
@section('content')

<div class="container py-4">
    <div class="d-flex flex-column mb-4 border-bottom pb-3 align-items-center text-center">
        <h2 class="fw-bold text-dark mb-1">Daftar Approval</h2>
        <small class="text-muted">Informasi untuk melihat dan membuat Approval</small>
    </div>

    @php
        $user = Auth::user();
        $subRoles = $user->sub_role ?? [];
    @endphp

    <!-- Tombol Ajukan -->
    <button id="btn-ajukan" class="btn btn-secondary mb-4" data-bs-toggle="modal" data-bs-target="#approveModal">
        Ajukan Permohonan
    </button>

    @if($requests->isEmpty())
        <p class="text-center text-muted">Tidak ada Persetujuan saat ini.</p>
    @else
        @foreach($requests as $typeName => $list)
            <div class="card mb-4 shadow-sm">
                <div class="card-header bg-light d-flex justify-content-between align-items-center">
                    <h6 class="fs-7 fw-bold mb-0">{{ $typeName }}</h6>
                </div>

                <div class="card-body">
                    @if($list->count())
                        <div class="table-responsive">
                            <table class="table table-striped table-hover align-middle mb-0">
                                <thead class="table-secondary text-center">
                                    <tr>
                                        <th class="fw-bold">Judul</th>
                                        <th class="fw-bold">Pengaju</th>
                                        <th class="fw-bold">Status</th>
                                        <th class="fw-bold">Diajukan</th>
                                        <th class="fw-bold">Aksi</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($list as $req)
                                        <tr>
                                            <td class="text-truncate" style="max-width: 200px;">
                                                {{ $req->request_title }}
                                            </td>
                                            <td>{{ $req->requester->full_name ?? '-' }}</td>
                                            <td class="text-center">
                                                <span class="badge rounded-pill bg-{{ 
                                                    $req->status === 'In Review' ? 'warning text-dark' : 
                                                    ($req->status === 'Approved Final' ? 'success' : 'danger') 
                                                }}">
                                                    {{ $req->status }}
                                                </span>
                                            </td>
                                            <td class="text-center">
                                                {{ $req->submitted_at?->format('d M Y H:i') }}
                                            </td>
                                            <td class="text-center">
                                                <a href="{{ route('approval.detail', $req->request_id) }}" 
                                                   class="btn btn-sm btn-secondary shadow-sm">
                                                    <i class="bi bi-box-arrow-up-right"></i> Buka
                                                </a>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @else
                        <div class="text-muted small">Tidak ada request pada kategori ini.</div>
                    @endif
                </div>
            </div>
        @endforeach
    @endif
</div>

<!-- Modal Approval -->
<div class="modal fade" id="approveModal" tabindex="-1" data-bs-backdrop="static" data-bs-keyboard="false">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <form method="POST" action="{{ route('approval.store') }}" id="approvalForm" novalidate enctype="multipart/form-data" class="needs-validation">
                @csrf

                <!-- Hidden field untuk approval mode -->
                <input type="hidden" name="approval_mode" id="approvalModeInput" value="">
                <input type="hidden" id="currentUserId" value="{{ $user->user_id }}">
                <input type="hidden" id="isSuperAdmin" value="{{ $isSuperAdmin ? 1 : 0 }}">

                <div class="modal-header">
                    <h5 class="modal-title">Ajukan Permohonan</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>

                <div class="modal-body">
                    {{-- Step Approval --}}
                    <button type="button" class="btn btn-sm btn-warning w-100 mb-3"
                            data-bs-toggle="collapse" data-bs-target="#stepApproval">
                        Tentukan tahapan permohonan
                    </button>

                    <div class="collapse show" id="stepApproval">
                        <div class="card card-body">
                            <div class="row">
                                <!-- 🔍 Search -->
                                <div class="mb-3 position-relative">
                                    <input type="text" id="searchInput" class="form-control" placeholder="Cari Nama Pengguna...">
                                    <ul id="searchDropdown" 
                                        class="list-group position-absolute w-100 shadow-sm mt-1" 
                                        style="display:none; z-index:1050; max-height: 180px; overflow-y: auto; font-size:0.85rem;">
                                    </ul>
                                </div>
                                
                                <!-- 👥 Daftar Pengguna -->
                                <div class="col-md-5">
                                    <label class="form-label fw-semibold">Daftar Pengguna </label>
                                    <div class="accordion-wrapper border rounded shadow-sm bg-white" style="max-height: 230px; overflow-y: auto;">
                                        <div class="accordion " id="divisionAccordion">
                                            @php
                                                $divisions = [
                                                    1 => 'IT',
                                                    2 => 'Finance',
                                                    3 => 'HR & GA',
                                                    4 => 'Marketing',
                                                    5 => 'Operation',
                                                    6 => 'Product & Planning',
                                                    7 => 'Retail Marketing',
                                                    8 => 'Fleet Marketing',
                                                    9 => 'Operation & Customer Care',
                                                    10 => 'Risk Management',
                                                    11 => 'Credit',
                                                    12 => 'Collection',
                                                    13 => 'Internal Audit',
                                                    14 => 'Legal & Compliance',
                                                    15 => 'Finance & Accounting',
                                                ];
                                            @endphp

                                            @foreach($divisions as $id => $name)
                                                <div class="accordion-item border-0 mb-2">
                                                    <h2 class="accordion-header bg-secondary " id="heading{{ $id }}">
                                                        <button class="accordion-button collapsed py-2 px-3 fw-semibold bg-light text-dark" 
                                                                style="font-size:0.85rem;"
                                                                type="button"
                                                                data-bs-toggle="collapse"
                                                                data-bs-target="#collapseDivision{{ $id }}">{{ $name }}
                                                        </button>
                                                    </h2>

                                                    <div id="collapseDivision{{ $id }}" class="accordion-collapse collapse"
                                                        data-bs-parent="#divisionAccordion">
                                                        <div class="accordion-body p-2" 
                                                            style="font-size: 0.8rem;">
                                                            @php
                                                                $divisionUsers = \App\Models\User::with('position')
                                                                    ->where('departement_cat', $id)
                                                                    ->where('is_deleted', 0)
                                                                    ->get();
                                                            @endphp
                                                            @if($divisionUsers->count())
                                                                <ul class="list-group list-group-flush user-list m-0">
                                                                    @foreach($divisionUsers as $u)
                                                                        <li class="list-group-item py-1 px-2 border-0 user-item" 
                                                                            style="cursor:pointer;"
                                                                            data-id="{{ $u->user_id }}"
                                                                            data-name="{{ $u->full_name ?? $u->name }}"
                                                                            data-position="{{ $u->position?->position_name ?? '-' }}"
                                                                            data-empid="{{ $u->emp_id }}">
                                                                            <input type="radio" 
                                                                                name="selected_user" 
                                                                                class="d-none user-radio"
                                                                                value="{{ $u->user_id }}">
                                                                            <div>
                                                                                <strong>{{ $u->full_name ?? $u->name }}</strong><br>
                                                                                <small class="text-muted">
                                                                                    ({{ $u->emp_id }}) – {{ $u->position?->position_name ?? 'Tanpa Jabatan' }}
                                                                                </small>
                                                                            </div>
                                                                        </li>
                                                                    @endforeach
                                                                </ul>
                                                            @else
                                                                <div class="text-muted small ps-2">Tidak ada pengguna</div>
                                                            @endif
                                                        </div>
                                                    </div>
                                                </div>
                                            @endforeach
                                        </div>
                                    </div>
                                </div>
                                <!-- 🔘 Pilih Jenis Approval -->
                                <div class="col-md-2 d-flex flex-column justify-content-center align-items-center text-center h-100 py-3">
                                    <div class="p-2 rounded text-start">
                                        <label class="d-block mb-1" style="font-size:0.85rem;">
                                            <input type="radio" name="approval_type" value="Penyetuju" class="me-1"> Penyetuju
                                        </label>
                                        <label class="d-block mb-0" style="font-size:0.85rem;">
                                            <input type="radio" name="approval_type" value="Setuju" class="me-1"> Setuju
                                        </label>
                                    </div>

                                    <div class="d-flex flex-column align-items-center gap-2 mt-3">
                                        <button type="button" class="btn btn-outline-secondary approval-move-right px-3 py-1">&gt;</button>
                                        <button type="button" class="btn btn-outline-secondary approval-move-left px-3 py-1">&lt;</button>
                                    </div>
                                </div>

                                <!-- ✅ Daftar Persetujuan -->
                                <div class="col-md-5">
                                    <label class="form-label fw-semibold">
                                        Daftar Persetujuan <span class="text-danger">*</span>
                                    </label>

                                    <div class="border rounded shadow-sm p-2 bg-white"
                                        style="height: 230px; overflow-y: auto; display: flex; flex-direction: column;">
                                        <ul id="approvalList" class="list-group list-group-flush flex-grow-1 m-0"></ul>
                                    </div>

                                    <div class="invalid-feedback mt-1">
                                        Minimal satu penyetuju harus dipilih.
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Hasil render urutan approval -->
                        <div id="approvalResultTable" class="mt-3"></div>
                    </div>
                    
                    <div class="mb-3 approval-extra d-none" id="form-course-preview">
                        <label class="form-label">Course</label>
                        <input type="text" id="approval_course_title" class="form-control" readonly>
                    </div>

                    <!-- Judul -->
                    <div class="mb-3">
                        <label class="form-label">Judul Permohonan <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" name="request_title" placeholder="Tuliskan Judul Permohonan..." required>
                        <div class="invalid-feedback">Judul permohonan wajib diisi.</div>
                    </div>

                    <!-- Jenis Permohonan -->
                    <div class="mb-3 mt-3">
                        <label for="courseAction" class="form-label">Pilih Jenis Permohonan <span class="text-danger">*</span></label>
                        <select id="courseAction" class="form-select" name="approval_id" required>
                            <option value="" selected disabled>-- Pilih Opsi --</option>
                            @if((int) $user->role_id === 2 || in_array(2, $subRoles))
                                <option value="1">Ajukan Pembuatan Kursus</option>
                                <option value="5">Permintaan Takeover Kursus</option>
                            @endif

                            @if((int) $user->role_id === 3 || in_array(3, $subRoles))
                                <option value="2">Pengajuan Penghapusan Course</option>
                                <option value="3">Konsultasi Learner</option>
                            @endif
                            @if(in_array((int) $user->role_id, [2,3]) || array_intersect([2,3], $subRoles))
                                <option value="4">Pratinjau dan Publikasi Kursus</option>
                            @endif
                        </select>
                        <div class="invalid-feedback">Jenis permohonan wajib dipilih.</div>
                    </div>

                    <!-- FORM: Ajukan Pembuatan Kursus -->
                    <div id="form-course-create" class="approval-extra d-none mt-3">
                        <h6 class="fw-bold mb-3">Formulir Pembuatan Kursus</h6>
                        <div class="row">
                            <!-- Kolom Kiri -->
                            <div class="col-md-4">
                                <div class="mb-3">
                                    <label class="form-label">Judul Kursus <span class="text-danger">*</span></label>
                                    <input type="text" class="form-control" name="course_title" required>
                                    <div class="invalid-feedback">Judul kursus wajib diisi.</div>
                                </div>
                                <div class="mb-3">
                                    <label class="form-label">Kategori Kursus <span class="text-danger">*</span></label>
                                    <select class="form-select" name="course_category" required>
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

                                <!-- Dropdown Trainer -->
                                <div class="mb-3">
                                    <label class="form-label">Nama Trainer <span class="text-danger">*</span></label>
                                    <select class="form-select" name="course_trainer_id" required>
                                        <option value="">-- Pilih Trainer --</option>
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
                                    <div class="invalid-feedback">Trainer wajib dipilih.</div>
                                </div>

                                <div class="mb-3">
                                    <label class="form-label">Maksimal Peserta <span class="text-danger">*</span></label>
                                    <input type="number" class="form-control" name="max_participant" min="1" required>
                                    <div class="invalid-feedback">Maksimal peserta wajib diisi.</div>
                                </div>
                                <div class="mb-3">
                                    <label class="form-label">Gambar Kursus</label>
                                    <input type="file" class="form-control" name="course_image" accept="image/*">
                                </div>
                                <div class="mb-3">
                                    <label class="form-label">Visibilitas Kursus <span class="text-danger">*</span></label>
                                    <select class="form-select" name="is_public" required>
                                        <option value="" disable> -- Pilih Tipe -- </option>
                                        <option value="1">Public</option>
                                        <option value="0">Private</option>
                                    </select>
                                    <div class="invalid-feedback">Visibilitas kursus wajib dipilih.</div>
                                </div>
                            </div>

                            <!-- Kolom Kanan -->
                            <div class="col-md-8 d-flex flex-column">
                                <div class="mb-3 flex-fill">
                                    <label class="form-label">Deskripsi Kursus <span class="text-danger">*</span></label>
                                    <textarea class="form-control" rows="10" name="course_describe" required></textarea>
                                    <div class="invalid-feedback">Deskripsi kursus wajib diisi.</div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- FORM: Publikasi Kursus -->
                    <div id="form-course-publish" class="approval-extra d-none">
                        <h6 class="fw-bold">Publikasi Kursus</h6>
                        <div class="mb-3">
                            <label class="form-label">Pilih Kursus <span class="text-danger">*</span></label>
                            <select id="approval_course_select" name="course_id" class="form-select" required>
                                <option value="">-- Pilih Kursus --</option>
                                @foreach($previewCourses as $c)
                                    <option value="{{ $c->course_id }}">{{ $c->course_title }}</option>
                                @endforeach
                            </select>
                            <input type="hidden" id="approval_course_id" value="">
                            <div class="invalid-feedback">Kursus wajib dipilih.</div>
                        </div>
                    </div>

                    <!-- FORM: Hapus Kursus -->
                    <div id="form-course-delete" class="approval-extra d-none">
                        <h6 class="fw-bold">Penghapusan Kursus</h6>
                        <p class="text-muted small">Pilih kursus yang akan diajukan untuk dihapus.</p>
                        <select  id="approval_course_delete" name="course_id" class="form-select" required>
                            <option value="">-- Pilih Kursus --</option>
                            @foreach($deleteCourses as $c)
                                <option value="{{ $c->course_id }}">{{ $c->course_title }}</option>
                            @endforeach
                        </select>
                        <div class="invalid-feedback">Kursus wajib dipilih.</div>
                    </div>

                    <!-- FORM: Konsultasi Learner Kursus -->
                    <div id="form-course-consultation" class="approval-extra d-none">
                        <h6 class="fw-bold">Konsultasi Learner</h6>
                        <p class="text-muted small">Pilih Course terlebih dahulu, kemudian pilih Learner.</p>

                        <!-- Pilih Course -->
                        <div class="mb-3">
                            <label for="courseSelect" class="form-label">Course <span class="text-danger">*</span></label>
                            <select id="courseSelect" name="course_id" class="form-select" required>
                                <option value="">-- Pilih Course --</option>
                                @foreach($consulCourses as $c)
                                    <option value="{{ $c->course_id }}">{{ $c->course_title }}</option>
                                @endforeach
                            </select>
                            <div class="invalid-feedback">Course wajib dipilih.</div>
                        </div>

                        <!-- Pilih Learner -->
                        <div class="mb-3">
                            <label for="learnerSelect" class="form-label">Learner <span class="text-danger">*</span></label>
                            <select id="learnerSelect" name="learner_id" class="form-select" required>
                                <option value="">-- Pilih Learner --</option>
                            </select>
                            <div class="invalid-feedback">Learner wajib dipilih.</div>
                        </div>
                    </div>

                    <!-- FORM: Takeover Course -->
                    <div id="form-course-takeover" class="approval-extra d-none">
                        <h6 class="fw-bold">Takeover Kursus</h6>
                        <p class="text-muted small">Pilih Course terlebih dahulu, kemudian pilih Instructor baru</p>

                        <!-- Pilih Course -->
                        <div class="mb-3">
                            <label for="courseTakeover" class="form-label">Course <span class="text-danger">*</span></label>
                            <select id="courseTakeover" name="takeover[course_id]" class="form-select" required>
                                <option value="">-- Pilih Course --</option>
                                @foreach($consulCourses as $c)
                                    <option 
                                        value="{{ $c->course_id }}"
                                        data-trainer-id="{{ $c->course_trainer_id }}"
                                        data-trainer-name="{{ $c->trainer->full_name ?? $c->trainer->name ?? 'Unknown' }}">
                                        {{ $c->course_title }}
                                    </option>
                                @endforeach
                            </select>
                            <div class="invalid-feedback">Course takeover wajib dipilih.</div>
                        </div>

                        <!-- Old Trainer -->
                        <div class="mb-3">
                            <label class="form-label">Instructor Sebelumnya</label>
                            <input type="text" id="OldTrainerName" class="form-control" value="-" readonly>
                            <input type="hidden" name="takeover[old_trainer_id]" id="OldTrainerId">
                        </div>
                        
                        <!-- Pilih Instructor Baru -->
                        <div class="mb-3">
                            <label for="InstructorTakeoverCourse" class="form-label">Takeover Instructor <span class="text-danger">*</span></label>
                            <select class="form-select" name="takeover[new_trainer_id]" id="InstructorTakeoverCourse" required>
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
                                <label for="TakeoverStartDate" class="form-label">Start Date <span class="text-danger">*</span></label>
                                <input type="date" class="form-control" id="TakeoverStartDate" name="takeover[start_date]" required>
                                <div class="invalid-feedback">Tanggal mulai wajib diisi.</div>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="TakeoverEndDate" class="form-label">End Date <span class="text-danger">*</span></label>
                                <input type="date" class="form-control" id="TakeoverEndDate" name="takeover[end_date]" required>
                                <div class="invalid-feedback">Tanggal akhir wajib diisi.</div>
                            </div>
                        </div>

                        <!-- Remarks -->
                        <div class="mb-3">
                            <label for="TakeoverRemarks" class="form-label">Alasan Takeover</label>
                            <textarea class="form-control" id="TakeoverRemarks" name="takeover[remarks]" rows="3" placeholder="Catatan tambahan..."></textarea>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="submit" class="btn btn-secondary w-100">Kirim Permohonan</button>
                </div>
            </form>
        </div>
    </div>
</div>



@endsection
