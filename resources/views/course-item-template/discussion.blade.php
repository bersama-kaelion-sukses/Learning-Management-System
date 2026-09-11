@extends('layouts.master')

@section('title', 'Discussion Forum')

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

        <!-- Right Content (Discussion Forum) -->
        <div class="col-md-9 d-flex flex-column">
            <div class="flex-fill border p-4 mb-3" style="min-height:50vh; max-height:50vh; overflow:auto;">
                <h5 class="mb-3 text-primary">💬 Diskusi: Pertanyaan dari Instructor</h5>

                <!-- Pertanyaan Instructor -->
                <div class="border p-3 mb-4 bg-light rounded">
                    <div class="d-flex align-items-center mb-2">
                        <img src="https://via.placeholder.com/40" class="rounded-circle me-2">
                        <div>
                            <strong>Instructor John</strong><br>
                            <small class="text-muted">2 jam yang lalu</small>
                        </div>
                    </div>
                    <p class="mb-0">Apa pendapat kalian tentang strategi bisnis pada materi minggu ini?</p>
                </div>

                <!-- Komentar dari User -->
                <div class="mb-3">
                    <div class="border p-3 rounded mb-2">
                        <strong class="d-block">Mahasiswa A</strong>
                        Menurut saya, strategi ini efektif untuk pasar lokal tetapi butuh adaptasi untuk internasional.
                        <div class="text-muted mt-1" style="font-size: 0.8rem;">1 jam yang lalu</div>
                    </div>
                    <div class="border p-3 rounded mb-2">
                        <strong class="d-block">Mahasiswa B</strong>
                        Setuju, tapi saya rasa faktor kompetisi harus diperhitungkan lebih detail.
                        <div class="text-muted mt-1" style="font-size: 0.8rem;">30 menit yang lalu</div>
                    </div>
                </div>

                <!-- Form Tambah Komentar -->
                <form class="d-flex mt-3">
                    <input type="text" class="form-control me-2" placeholder="Tulis komentar...">
                    <button class="btn btn-primary">Kirim</button>
                </form>
            </div>

            <div class="d-flex justify-content-between">
                <div class="border p-2 bg-light">Progress : 2/3</div>
                <a href="#" class="btn btn-primary px-4">Selanjutnya</a>
            </div>
        </div>
    </div>
</div>
@endsection
