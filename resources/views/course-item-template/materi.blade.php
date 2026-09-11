@extends('layouts.master')

@section('title', 'Detail Course')

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

        <!-- Right Content (Render By ID nanti KAtegori Logic JS nya aja) -->
        <div class="col-md-9 d-flex flex-column">
        <div class="flex-fill border p-4 mb-3" style="min-height:50vh; max-height:50vh; overflow:auto;">
            <h5 class="mb-3">Item 1</h5>
            <p>Materi Pembahasan:</p>
            <ul>
                <li>PDF</li>
                <li>Video</li>
                <li>URL (Youtube)</li>
            </ul>
        </div>

            <div class="d-flex justify-content-between">
                <div class="border p-2 bg-light">Progress : 1/3</div>
                <a href="#" class="btn btn-primary px-4">Selanjutnya</a>
            </div>
        </div>
    </div>
</div>
@endsection
