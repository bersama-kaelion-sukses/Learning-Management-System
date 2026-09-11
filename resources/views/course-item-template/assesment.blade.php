@extends('layouts.master')

@section('title', 'Assessment Submission')

@section('content')
<div class="container-fluid mt-4">
    <div class="row">
        <!-- Left Sidebar (Preview) -->
        <div class="col-md-3">
            <h5 class="text-center border p-2 mb-2 bg-light">Pratinjau</h5>
            <div class="accordion" id="weekAccordion">
                <!-- Week 1 -->
                <div class="accordion-item mb-2">
                    <h2 class="accordion-header" id="week1Heading">
                        <button class="accordion-button bg-success bg-opacity-25 collapsed" type="button" 
                                data-bs-toggle="collapse" data-bs-target="#week1Items">
                            Week 1
                        </button>
                    </h2>
                    <div id="week1Items" class="accordion-collapse collapse" data-bs-parent="#weekAccordion">
                        <div class="accordion-body p-0">
                            <div class="list-group">
                                <button class="list-group-item list-group-item-action bg-success bg-opacity-25">Item 1</button>
                                <button class="list-group-item list-group-item-action bg-danger bg-opacity-25">Item 2</button>
                                <button class="list-group-item list-group-item-action">Item 3</button>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Week 2 -->
                <div class="accordion-item mb-2">
                    <h2 class="accordion-header" id="week2Heading">
                        <button class="accordion-button bg-success bg-opacity-25 collapsed" type="button" 
                                data-bs-toggle="collapse" data-bs-target="#week2Items">
                            Week 2
                        </button>
                    </h2>
                    <div id="week2Items" class="accordion-collapse collapse" data-bs-parent="#weekAccordion">
                        <div class="accordion-body p-0">
                            <div class="list-group">
                                <button class="list-group-item list-group-item-action">Item 1</button>
                                <button class="list-group-item list-group-item-action">Item 2</button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Certificate -->
            <div class="border text-center p-2 bg-secondary bg-opacity-25 mt-3">
                Certificate (Locked)
            </div>
        </div>

        <!-- Right Content (Assessment/Assignment) -->
        <div class="col-md-9 d-flex flex-column">
            <div class="flex-fill border p-4 mb-3" style="min-height:50vh; max-height:50vh; overflow:auto;">
                <h5 class="mb-3 text-primary">📂 Assessment / Assignment Submission</h5>

                <!-- Deskripsi Tugas -->
                <div class="mb-4">
                    <p><strong>Instruksi:</strong></p>
                    <ul>
                        <li>Kerjakan studi kasus tentang strategi pemasaran digital sesuai materi minggu ini.</li>
                        <li>Buat laporan dalam format PDF maksimal 10 halaman.</li>
                        <li>Unggah file tugas di bawah ini sebelum <strong>10 Agustus 2025, 23:59</strong>.</li>
                    </ul>
                </div>

                <!-- Form Upload File -->
                <form>
                    <div class="mb-3">
                        <label for="fileUpload" class="form-label fw-bold">Upload File (PDF/DOCX)</label>
                        <input class="form-control" type="file" id="fileUpload" accept=".pdf,.doc,.docx">
                        <small class="text-muted">Max size: 10MB</small>
                    </div>

                    <div class="mb-3">
                        <label for="comment" class="form-label fw-bold">Catatan (Opsional)</label>
                        <textarea class="form-control" id="comment" rows="3" placeholder="Tambahkan catatan untuk instructor..."></textarea>
                    </div>

                    <button type="submit" class="btn btn-success">Submit Tugas</button>
                </form>

                <!-- Riwayat Submit (Static) -->
                <div class="mt-4">
                    <h6 class="fw-bold">Riwayat Pengumpulan:</h6>
                    <ul class="list-group">
                        <li class="list-group-item d-flex justify-content-between align-items-center">
                            Tugas_Week1.pdf
                            <span class="badge bg-success">Diterima</span>
                        </li>
                        <li class="list-group-item d-flex justify-content-between align-items-center">
                            Draft_Tugas.docx
                            <span class="badge bg-warning text-dark">Menunggu Review</span>
                        </li>
                    </ul>
                </div>
            </div>

            <div class="d-flex justify-content-between mt-2">
                <div class="border p-2 bg-light">Progress : 3/3</div>
                <a href="#" class="btn btn-primary px-4">Selanjutnya</a>
            </div>
        </div>
    </div>
</div>
@endsection
