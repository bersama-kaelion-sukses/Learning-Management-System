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

        <!-- Right Content -->
        <div class="col-md-9 d-flex flex-column">

            <div id="quiz-container" class="flex-fill border p-4 mb-3" style="min-height:50vh; max-height:50vh; overflow:auto;">
                <!-- Pertanyaan 1 -->
                <div class="question">
                    <h5 class="mb-3">Pertanyaan 1 - Multiple Choice</h5>
                    <p class="fw-bold text-primary">🔹 Multiple Choice Quiz</p>
                    <p>Pilih jawaban yang benar di bawah ini:</p>
                    <ul class="list-group mb-3">
                        <li class="list-group-item"><input type="radio" name="q1"> A. Jawaban 1</li>
                        <li class="list-group-item"><input type="radio" name="q1"> B. Jawaban 2</li>
                        <li class="list-group-item"><input type="radio" name="q1"> C. Jawaban 3</li>
                        <li class="list-group-item"><input type="radio" name="q1"> D. Jawaban 4</li>
                    </ul>
                </div>

                <!-- Pertanyaan 2 -->
                <div class="question d-none">
                    <h5 class="mb-3">Pertanyaan 2 - Multiple Choice</h5>
                    <p class="fw-bold text-primary">🔹 Multiple Choice Quiz</p>
                    <p>Pilih jawaban yang benar di bawah ini:</p>
                    <ul class="list-group mb-3">
                        <li class="list-group-item"><input type="radio" name="q2"> A. Jawaban 1</li>
                        <li class="list-group-item"><input type="radio" name="q2"> B. Jawaban 2</li>
                        <li class="list-group-item"><input type="radio" name="q2"> C. Jawaban 3</li>
                        <li class="list-group-item"><input type="radio" name="q2"> D. Jawaban 4</li>
                    </ul>
                </div>

                <!-- Pertanyaan 3 -->
                <div class="question d-none">
                    <h5 class="mb-3">Pertanyaan 3 - Multiple Choice</h5>
                    <p class="fw-bold text-primary">🔹 Multiple Choice Quiz</p>
                    <p>Pilih jawaban yang benar di bawah ini:</p>
                    <ul class="list-group mb-3">
                        <li class="list-group-item"><input type="radio" name="q3"> A. Jawaban 1</li>
                        <li class="list-group-item"><input type="radio" name="q3"> B. Jawaban 2</li>
                        <li class="list-group-item"><input type="radio" name="q3"> C. Jawaban 3</li>
                        <li class="list-group-item"><input type="radio" name="q3"> D. Jawaban 4</li>
                    </ul>
                </div>
            </div>

            <!-- Navigation with Pagination -->
            <div class="d-flex justify-content-between align-items-center">
                <button id="prevBtn" class="btn btn-secondary" disabled>Sebelumnya</button>
                <div id="pageIndicator">1 / 3</div>
                <button id="nextBtn" class="btn btn-primary">Selanjutnya</button>
            </div>
        </div>
    </div>
</div>

<script>
    const questions = document.querySelectorAll('.question');
    const prevBtn = document.getElementById('prevBtn');
    const nextBtn = document.getElementById('nextBtn');
    const pageIndicator = document.getElementById('pageIndicator');

    let currentIndex = 0;

    function updateQuestion() {
        questions.forEach((q, idx) => {
            q.classList.toggle('d-none', idx !== currentIndex);
        });
        pageIndicator.textContent = `${currentIndex + 1} / ${questions.length}`;
        prevBtn.disabled = currentIndex === 0;
        nextBtn.disabled = currentIndex === questions.length - 1;
    }

    prevBtn.addEventListener('click', () => {
        if (currentIndex > 0) {
            currentIndex--;
            updateQuestion();
        }
    });

    nextBtn.addEventListener('click', () => {
        if (currentIndex < questions.length - 1) {
            currentIndex++;
            updateQuestion();
        }
    });

    updateQuestion();
</script>
@endsection
