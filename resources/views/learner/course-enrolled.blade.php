@extends('layouts.master')

@section('title', 'Detail Course')

@section('content')
<div class="container-fluid mt-4">
        <!-- Row 1: Header Course -->
    <div class="card shadow-sm mb-4">
        <div class="card-body">
            <div class="row align-items-center g-3">
                <!-- Gambar Course -->
                <div class="col-md-3 text-center">
                    @if($course->course_image)
                        <img src="{{ asset('assets/img/course/'.$course->course_image) }}" 
                            class="img-fluid rounded shadow-sm w-100" 
                            alt="Image of {{ $course->course_title }}" 
                            style="max-height:150px; object-fit:contain;">
                    @else
                        <div class="bg-light border d-flex align-items-center justify-content-center rounded w-100" 
                            style="height:120px;">
                            <span class="text-muted">No Image</span>
                        </div>
                    @endif
                    <button 
                        type="button" 
                        class="btn btn-outline-secondary w-100 mb-3 fw-semibold shadow-sm mt-4"
                        onclick="
                            if (document.referrer && document.referrer !== window.location.href) {
                                window.history.back();
                            } else {
                                window.location.href='{{ route('learner.course') }}';
                            }
                        ">
                        ⬅️ Kembali
                    </button>
                </div>

                <!-- Detail Course -->
                <div class="col-md-9">
                    <!-- INFO COURSE -->
                    <div class="mb-3">
                        <h5 class="fw-bold mb-1">{{ $course->course_title }}</h5>
                        <small class="text-muted">
                            {{ json_lang('Category') }} : {{ $course->course_category }}
                        </small>
                        <small class="text-muted d-block">
                            {{ json_lang('Trainer') }} :
                            {{ $trainer->full_name ?? $course->course_trainer_name ?? 'Unknown Trainer' }}
                        </small>
                    </div>

                    <!-- REMARKS (TETAP ADA) -->
                    <div class="form-text mb-4" id="remarkAttention">
                        <strong id="remarkTitle" class="d-block mb-1 d-none">⚠️ Mohon Perhatikan:</strong>
                        <ul class="mb-0 ps-3" id="remarkList"></ul>
                    </div>
                    
                    <div id="courseCompletedMessage"
                        class="text-center py-4 border-top {{ $progressCourse >= 100 ? '' : 'd-none' }}">
                        <h5 class="fw-semibold text-success mb-2">
                            🎉 Selamat! Anda telah menyelesaikan course
                        </h5>
                    </div>
                    
                </div>
            </div>
        </div>
    </div>
    <div class="row">
        <!-- Sidebar kiri -->
        <div class="col-md-3">
            <div class="border bg-white p-3 h-100">
                <h5 class="mb-3 text-center">Pratinjau Kursus</h5>
                    <div class="accordion" id="materiAccordion">
                        @foreach($courseModules as $module)
                            <div class="accordion-item mb-2">
                                <h2 class="accordion-header" id="materi{{ $module->course_week_id }}Heading">
                                <button class="accordion-button collapsed py-2 {{ $module->status_module_lock == 'locked' ? 'disabled' : '' }}"
                                        type="button"
                                        data-status-module="{{ $module->status_module_lock }}"
                                        data-bs-toggle="collapse"
                                        data-bs-target="#materi{{ $module->course_week_id }}Items"
                                        aria-expanded="false"
                                        aria-controls="materi{{ $module->course_week_id }}Items"
                                        @if($module->status_module_lock == 'locked') disabled @endif
                                >
                                    {{ str_replace('Materi', 'Bagian', $module->course_week_title ?? 'Bagian') }}

                                    {{-- Badge status --}}
                                    @if($module->status_module_lock == 'locked')
                                        <span class="badge bg-secondary ms-2">Locked</span>
                                    @else
                                        <span class="badge bg-success ms-2">Unlocked</span>
                                    @endif
                                </button>
                                </h2>
                                @if ($previewMode) 
                                    <div id="materi{{ $module->course_week_id }}Items" class="accordion-collapse collapse show" data-bs-parent="#materiAccordion">
                                @else
                                    <div id="materi{{ $module->course_week_id }}Items" class="accordion-collapse collapse" data-bs-parent="#materiAccordion">
                                @endif
                                    <div class="accordion-body p-0">
                                        @php
                                            $icons = [
                                                1 => '🎬', // Video
                                                2 => '📄', // PDF
                                                3 => '📝', // Essay
                                                4 => '❓', // Quiz
                                                5 => '💬', // Forum
                                                6 => '🎓', // Certificate
                                                7 => '📤', // Upload Learner
                                            ];
                                            $totalItems = $module->items->count();
                                        @endphp

                                        <ul class="list-group list-group-flush">
                                            @foreach($module->items as $index => $item)
                                                @php
                                                    // ================================
                                                    // 🎯 Quiz question data
                                                    // ================================
                                                    $questionsData = $item->questions?->map(function($q) {
                                                        return [
                                                            'question_id'   => $q->question_id,
                                                            'question_text' => $q->question_text,
                                                            'question_image'=> $q->question_image,
                                                            'options'       => $q->options?->map(fn($o) => [
                                                                'option_id'   => $o->option_id,
                                                                'option_text' => $o->option_text,
                                                                'option_image'=> $o->option_image,
                                                                'is_correct'  => $o->is_correct,
                                                            ])->values()->toArray(),
                                                        ];
                                                    })->values()->toArray();

                                                    // ================================
                                                    // 📎 Filter user-specific submissions
                                                    // ================================
                                                    $mySubmission = $item->attachment?->where('user_id', Auth::id())?->first();
                                                    $myEssaySubmission = $item->essay?->submissions?->where('user_id', Auth::id())?->first();
                                                @endphp

                                                <li class="list-group-item list-group-item-action item-option"
                                                    data-module-id="{{ $module->course_week_id }}"
                                                    data-index="{{ $index + 1 }}"
                                                    data-total="{{ $totalItems }}"
                                                    

                                                    {{-- Essay --}}
                                                    data-essay-id="{{ $item->essay->essay_id ?? '' }}"
                                                    data-essay-title="{{ $item->essay->essay_title ?? '' }}"
                                                    data-essay-instruction="{{ $item->essay->instruction ?? '' }}"
                                                    data-essay-type="{{ $item->essay->attachment_type ?? '' }}"
                                                    data-essay-attach="{{ $item->essay->attachment_value ?? '' }}"
                                                    data-grade-essay="{{ $myEssaySubmission->grade ?? '' }}"
                                                    data-feedback-essay="{{ $myEssaySubmission->feedback ?? '' }}"

                                                    {{-- Forum --}}
                                                    data-forum-id="{{ $item->forum->forum_id ?? '' }}"
                                                    data-forum-title="{{ $item->forum->forum_title ?? '' }}"
                                                    data-forum-question="{{ $item->forum->forum_question ?? '' }}"
                                                    data-forum-attachment-type="{{ $item->forum->attachment_type ?? '' }}"
                                                    data-forum-attachment-value="{{ $item->forum->attachment_value ?? '' }}"
                                                    data-grade-forum="{{ $item->forum->grade ?? '' }}"
                                                    data-feedback-forum="{{ $item->forum->feedback ?? '' }}"

                                                    {{-- Attachment (Upload Tugas) --}}
                                                    data-grade-attachment="{{ $mySubmission->grade ?? '' }}"
                                                    data-feedback-attachment="{{ $mySubmission->feedback ?? '' }}"
                                                    data-submitted-file="{{ $mySubmission->file_path ?? '' }}"
                                                    data-submitted-at="{{ $mySubmission->submitted_at ?? '' }}"
                                                    data-is-remedial="{{ $mySubmission->is_remedial ?? '0'}}"
                                                    data-attachments='@json($item->attachment?->where("user_id", Auth::id())->values())'

                                                    {{-- General --}}
                                                    data-item-id="{{ $item->item_id }}"
                                                    data-course-id="{{ $item->course_id }}"
                                                    data-course-start="{{ $item->course_due_start }}"
                                                    data-course-end="{{ $item->course_due_end }}"
                                                    data-name="{{ $item->course_item_name }}"
                                                    data-desc="{{ $item->course_describe }}"
                                                    data-file="{{ $item->course_media }}"
                                                    data-type="{{ $item->course_item_type }}"
                                                    data-course-duration="{{ $item->course_duration ?? 0 }}"
                                                    data-course-multiply-chance="{{ $item->course_multiply_chance ?? 1 }}"
                                                    data-passing-grade-item="{{ $item->passing_grade }}"
                                                    data-status="{{ $item->status_lock ?? 'unlocked' }}"
                                                    @if(!empty($questionsData))
                                                        data-questions='@json($questionsData)'
                                                    @endif
                                                >
                                                    <span class="me-2">{{ $icons[$item->course_item_type] ?? '📌' }}</span>
                                                    {{ $item->course_item_name }}
                                                </li>
                                            @endforeach
                                        </ul>
                                    </div>
                                </div>
                            </div>
                        @endforeach
                    </div>

                    <div class="bottom-0 start-0 end-0 p-3">
                        <div class="bg-secondary text-center rounded shadow-sm">
                            <button id="add-thread-forum" class="btn btn-warning btn-md w-100"  data-bs-toggle="modal" data-bs-target="#ThreadDiscussionLearner" data-course-id="{{ $course->course_id }}">Buka Thread Diskusi</button>
                        </div>
                    </div>
            </div>
        </div>

        <!-- Konten kanan -->
        <div class="col-md-9 d-flex flex-column">
            <div id="learner-detail-panel" 
                class="flex-fill border bg-white shadow-sm rounded p-4 mb-3"
                style="min-height:50vh; max-height:75vh; overflow:auto;">
                <h5 class="mb-3 text-muted fst-italic">👉 Pilih materi di kiri untuk melihat detail</h5>
            </div>
            <div class="d-flex justify-content-between align-items-center">
                <div id="progress-box" class="border bg-white shadow-sm rounded px-3 py-2">
                    Progress : 0/0
                </div>
                <!-- <a href="#" id="next-btn" class="btn btn-secondary px-4">Selanjutnya</a>-->
                @if ($previewMode) 
                    <button id="next-btn" class="btn btn-secondary px-4 d-none">
                        Selanjutnya
                    </button>
                @else
                    <button id="next-btn" class="btn btn-secondary px-4">
                        Selanjutnya
                    </button>
                @endif
            </div>
        </div>
    </div>
    <!-- Modal Fullscreen Thread -->
    <div class="modal fade" id="ThreadDiscussionLearner" tabindex="-1" aria-hidden="true" data-bs-backdrop="static" data-bs-keyboard="false">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
            <div class="modal-header bg-warning">
                <h5 class="modal-title fw-semibold">💬 Diskusi Thread Kursus</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Tutup"></button>
            </div>
            <div class="modal-body">
                <input type="hidden" id="Thread_course_id">

                <div id="ForumThreadList" class="container-fluid">
                    <!-- Form thread akan diprefill lewat JS -->
                </div>

                <!-- Progress Upload -->
                <div id="Thread_ProgressWrapper" class="progress mt-4" style="display:none; height:25px;">
                    <div id="Thread_ProgressBar" class="progress-bar" role="progressbar">0%</div>
                </div>
                <div id="Thread_ProgressText" class="text-center mt-2 text-muted" style="display:none;">
                    Mengunggah file, mohon tunggu...
                </div>
            </div>
            </div>
        </div>
    </div>

    <!-- ✅ Modal Form Feedback -->
    <div class="modal fade" id="feedbackModal" tabindex="-1" aria-hidden="true" data-bs-backdrop="static" data-bs-keyboard="false">
        <div class="modal-dialog modal-xl modal-dialog-centered modal-dialog-scrollable">
            <div class="modal-content shadow-lg">

            <!-- Header -->
            <div class="modal-header bg-light">
                <h5 class="modal-title fw-semibold">📋 Formulir Feedback e-Learning</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>

            <!-- Body -->
            <div class="modal-body">
                <form id="feedbackForm">
                    @csrf

                <input type="hidden" name="course_id" id="feedback_course_id" value="{{ $course->course_id }}">
                <!-- SECTION 1️⃣ USER EXPERIENCE -->
                <h6 class="mt-2 fw-bold">1️⃣ User Experience</h6>


                <div class="mb-3">
                    <label class="form-label fw-semibold">1. e-Learning mudah digunakan dan navigasinya jelas
                    <small class="text-muted d-block">The e-Learning is easy to use and has clear navigation</small>
                    </label>
                    <select class="form-select" name="q1" required>
                    <option value="">Pilih jawaban</option>
                    <option value="1">Sangat Tidak Setuju</option>
                    <option value="2">Tidak Setuju</option>
                    <!-- <option value="3">Netral</option> -->
                    <option value="4">Setuju</option>
                    <option value="5">Sangat Setuju</option>
                    </select>
                </div>

                <div class="mb-3">
                    <label class="form-label fw-semibold">2. Saya tidak mengalami kendala teknis yang mengganggu selama pembelajaran
                    <small class="text-muted d-block">I did not experience any technical issues that interfered with the learning process</small>
                    </label>
                    <select class="form-select" name="q2" required>
                    <option value="">Pilih jawaban</option>
                    <option value="1">Sangat Tidak Setuju</option>
                    <option value="2">Tidak Setuju</option>
                    <!-- <option value="3">Netral</option> -->
                    <option value="4">Setuju</option>
                    <option value="5">Sangat Setuju</option>
                    </select>
                </div>

                <div class="mb-3">
                    <label class="form-label fw-semibold">3. Tampilan dan desain e-Learning membuat saya nyaman belajar
                    <small class="text-muted d-block">The e-Learning layout and design make me feel comfortable learning</small>
                    </label>
                    <select class="form-select" name="q3" required>
                    <option value="">Pilih jawaban</option>
                    <option value="1">Sangat Tidak Setuju</option>
                    <option value="2">Tidak Setuju</option>
                    <!-- <option value="3">Netral</option> -->
                    <option value="4">Setuju</option>
                    <option value="5">Sangat Setuju</option>
                    </select>
                </div>

                <hr>

                <!-- SECTION 2️⃣ KUALITAS MATERI & AKTIVITAS BELAJAR -->
                <h6 class="fw-bold">2️⃣ Kualitas Materi & Aktivitas Belajar</h6>

                <div class="mb-3">
                    <label class="form-label fw-semibold">4. Urutan penyajian materi mudah diikuti dari awal hingga akhir
                    <small class="text-muted d-block">The sequence of material presentation is easy to follow from start to finish</small>
                    </label>
                    <select class="form-select" name="q4" required>
                    <option value="">Pilih jawaban</option>
                    <option value="1">Sangat Tidak Setuju</option>
                    <option value="2">Tidak Setuju</option>
                    <!-- <option value="3">Netral</option> -->
                    <option value="4">Setuju</option>
                    <option value="5">Sangat Setuju</option>
                    </select>
                </div>

                <div class="mb-3">
                    <label class="form-label fw-semibold">5. Materi disajikan dengan jelas dan mudah dipahami
                    <small class="text-muted d-block">The material is presented clearly and is easy to understand</small>
                    </label>
                    <select class="form-select" name="q5" required>
                    <option value="">Pilih jawaban</option>
                    <option value="1">Sangat Tidak Setuju</option>
                    <option value="2">Tidak Setuju</option>
                    <!-- <option value="3">Netral</option> -->
                    <option value="4">Setuju</option>
                    <option value="5">Sangat Setuju</option>
                    </select>
                </div>

                <div class="mb-3">
                    <label class="form-label fw-semibold">6. Kombinasi teks, gambar, video, dan aktivitas membuat pembelajaran lebih menarik
                    <small class="text-muted d-block">The combination of text, images, videos, and activities makes the learning more engaging</small>
                    </label>
                    <select class="form-select" name="q7" required>
                    <option value="">Pilih jawaban</option>
                    <option value="1">Sangat Tidak Setuju</option>
                    <option value="2">Tidak Setuju</option>
                    <!-- <option value="3">Netral</option> -->
                    <option value="4">Setuju</option>
                    <option value="5">Sangat Setuju</option>
                    </select>
                </div>

                <div class="mb-3">
                    <label class="form-label fw-semibold">7. Latihan, kuis, atau aktivitas yang diberikan membantu saya memahami materi
                    <small class="text-muted d-block">The exercises, quizzes, or activities provided help me understand the material</small>
                    </label>
                    <select class="form-select" name="q8" required>
                    <option value="">Pilih jawaban</option>
                    <option value="1">Sangat Tidak Setuju</option>
                    <option value="2">Tidak Setuju</option>
                    <!-- <option value="3">Netral</option> -->
                    <option value="4">Setuju</option>
                    <option value="5">Sangat Setuju</option>
                    </select>
                </div>

                <div class="mb-3">
                    <label class="form-label fw-semibold">8. Contoh atau studi kasus dalam materi pelatihan memberikan gambaran aktual tentang penerapan konsep yang diajarkan
                    <small class="text-muted d-block">The examples or case studies in the training materials provide a real picture of how the concepts are applied</small>
                    </label>
                    <select class="form-select" name="q9" required>
                    <option value="">Pilih jawaban</option>
                    <option value="1">Sangat Tidak Setuju</option>
                    <option value="2">Tidak Setuju</option>
                    <!-- <option value="3">Netral</option> -->
                    <option value="4">Setuju</option>
                    <option value="5">Sangat Setuju</option>
                    </select>
                </div>

                <hr>

                <!-- SECTION 3️⃣ RELEVANSI -->
                <h6 class="fw-bold">3️⃣ Relevansi</h6>

                <div class="mb-3">
                    <label class="form-label fw-semibold">9. Materi dalam course ini relevan dengan pekerjaan saya
                    <small class="text-muted d-block">The materials in this course are relevant to my job</small>
                    </label>
                    <select class="form-select" name="q10" required>
                    <option value="">Pilih jawaban</option>
                    <option value="1">Sangat Tidak Setuju</option>
                    <option value="2">Tidak Setuju</option>
                    <!-- <option value="3">Netral</option> -->
                    <option value="4">Setuju</option>
                    <option value="5">Sangat Setuju</option>
                    </select>
                </div>

                <div class="mb-3">
                    <label class="form-label fw-semibold">10. Course ini membantu menjawab tantangan yang saya hadapi di tempat kerja
                    <small class="text-muted d-block">This course helps me address the challenges I face at work</small>
                    </label>
                    <select class="form-select" name="q11" required>
                    <option value="">Pilih jawaban</option>
                    <option value="1">Sangat Tidak Setuju</option>
                    <option value="2">Tidak Setuju</option>
                    <!-- <option value="3">Netral</option> -->
                    <option value="4">Setuju</option>
                    <option value="5">Sangat Setuju</option>
                    </select>
                </div>

                <hr>

                <!-- SECTION 4️⃣ COMMITMENT TO APPLY LEARNING -->
                <h6 class="fw-bold">4️⃣ Commitment to Apply Learning</h6>

                <div class="mb-3">
                    <label class="form-label fw-semibold">11. Saya memahami bagaimana menerapkan pengetahuan dari course ini ke pekerjaan saya
                    <small class="text-muted d-block">I understand how to apply the knowledge from this course to my job</small>
                    </label>
                    <select class="form-select" name="q12" required>
                    <option value="">Pilih jawaban</option>
                    <option value="1">Sangat Tidak Setuju</option>
                    <option value="2">Tidak Setuju</option>
                    <!-- <option value="3">Netral</option> -->
                    <option value="4">Setuju</option>
                    <option value="5">Sangat Setuju</option>
                    </select>
                </div>

                <div class="mb-3">
                    <label class="form-label fw-semibold">12. Saya berencana mencoba menerapkan hal yang saya pelajari dalam waktu dekat
                    <small class="text-muted d-block">I plan to try applying what I’ve learned in the near future</small>
                    </label>
                    <select class="form-select" name="q13" required>
                    <option value="">Pilih jawaban</option>
                    <option value="1">Sangat Tidak Setuju</option>
                    <option value="2">Tidak Setuju</option>
                    <!-- <option value="3">Netral</option> -->
                    <option value="4">Setuju</option>
                    <option value="5">Sangat Setuju</option>
                    </select>
                </div>

                <div class="mb-3">
                    <label class="form-label fw-semibold">13. Saya merasa akan mendapatkan dukungan dari atasan untuk menerapkan materi dari course ini
                    <small class="text-muted d-block">I feel that I will receive support from my supervisor to apply what I’ve learned from this course</small>
                    </label>
                    <select class="form-select" name="q14" required>
                    <option value="">Pilih jawaban</option>
                    <option value="1">Sangat Tidak Setuju</option>
                    <option value="2">Tidak Setuju</option>
                    <!-- <option value="3">Netral</option> -->
                    <option value="4">Setuju</option>
                    <option value="5">Sangat Setuju</option>
                    </select>
                </div>

                <hr>

                <!-- SECTION 💬 OPEN FEEDBACK -->
                <h6 class="fw-bold">💬 Umpan Balik Terbuka / Open Feedback</h6>

                <div class="mb-3">
                    <label class="form-label">1. Hal apa yang paling membantu Anda selama mengikuti e-Learning ini?</label>
                    <textarea class="form-control" name="q15" rows="2"></textarea>
                </div>

                <div class="mb-3">
                    <label class="form-label">2. Apa yang sebaiknya diperbaiki dari materi atau tampilan e-Learning?</label>
                    <textarea class="form-control" name="q16" rows="2"></textarea>
                </div>

                <div class="mb-3">
                    <label class="form-label">3. Apakah ada topik lanjutan yang Anda harap tersedia di e-Learning?</label>
                    <textarea class="form-control" name="q17" rows="2"></textarea>
                </div>
                </form>
            </div>

            <!-- Footer -->
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Tutup</button>
                <button type="submit" form="feedbackForm" class="btn btn-success">Kirim Feedback</button>
            </div>
        </div>
    </div>
    </div>
    <!-- Modal Rules and Completed Course  -->
    <div class="modal modal-lg fade" id="courseRulesModal" data-bs-backdrop="static" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title fw-semibold">
                📘 Aturan Pengerjaan & Penyelesaian Course
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <p class="mb-3 fw-bold">
                    Mohon perhatikan ketentuan berikut agar proses pengerjaan dan
                    penyelesaian course dapat tercatat dengan benar:
                </p>
                    <ol class="mb-0">
                        <li class="mb-2">
                            Tombol
                            <span class="fw-bold text-danger">"Selesaikan Kursus"</span>
                            <strong>selalu dapat diklik</strong>.
                            Namun, proses penyelesaian kursus
                            <strong>hanya dapat dilanjutkan</strong>
                            apabila seluruh ketentuan telah terpenuhi.
                        </li>

                        <li class="mb-2">
                            Pastikan <strong>seluruh aktivitas kursus</strong>
                            (Pilihan Ganda, Essai, atau lampiran tugas) telah:
                            <ul>
                                <li>Disubmit dengan benar</li>
                                <li>Tidak berada dalam status <em>remedial</em></li>
                                <li>Ditandai dengan <strong>warna hijau</strong> sebagai tanda selesai</li>
                            </ul>
                        </li>

                        <li class="mb-2">
                            <strong>Perhatian:</strong> Setelah membuka materi pembelajaran (pdf atau video),
                            Anda <strong>wajib menekan tombol</strong>
                            <span class="fw-bold text-primary">"Selanjutnya"</span>
                            agar progress tersimpan dan tercatat dengan benar.
                        </li>

                        <li class="mb-2">
                            Setiap materi atau aktivitas yang berhasil diselesaikan
                            akan ditandai dengan <strong>warna hijau</strong>.
                            Jika materi <strong>belum berwarna hijau</strong>,
                            maka progress pengerjaan tersebut
                            <strong>belum tercatat </strong>.
                        </li>

                        <li class="mb-2">
                            Untuk melanjutkan ke materi berikutnya,
                            silakan tekan tombol
                            <strong>"Selanjutnya"</strong>
                            hingga <strong>seluruh materi dalam course berwarna hijau</strong>.
                        </li>

                        <li class="mb-2">
                            Apabila seluruh ketentuan telah terpenuhi,
                            maka setelah menekan tombol atau di materi terakhir aktivitas 
                            <span class="fw-bold text-danger">"Selesaikan Kursus"</span>,
                            learner akan langsung diarahkan ke
                            <strong>forum kuesioner feedback course</strong>.
                        </li>

                        <li>
                            Jika masih terdapat ketentuan yang belum terpenuhi,
                            sistem akan menampilkan
                            <strong>pop-screen informasi</strong>
                            yang menjelaskan hal ini.
                        </li>

                        <li>
                            <strong>Catatan:</strong>
                            Status <strong>Completed (100%)</strong>
                            hanya dapat diperoleh setelah learner telah 
                            <strong>mengisi kuesioner feedback course</strong>
                            hingga selesai.
                        </li>
                    </ol>
                <hr>
                <h5 class="text-center"> SELAMAT MENGERJAKAN !</h5>
            </div>


            <div class="modal-footer">
                <button type="button" class="btn btn-primary" data-bs-dismiss="modal">
                Saya Mengerti
                </button>
            </div>
            </div>
        </div>
    </div>
    
    <div id="course-wrapper"
        data-preview-mode="{{ $previewMode ? '1' : '0' }}">
    </div>
</div>
@endsection
