<style>
  /* === Mini Calendar === */
  .calendar {
    display: grid;
    grid-template-columns: repeat(7, 1fr);
    gap: 4px;
    font-size: 0.85rem;
    text-align: center;
  }

  .calendar div {
    padding: 6px 0;
    border-radius: 4px;
    position: relative; /* penting untuk tooltip */
    cursor: default;
  }

  .calendar .header {
    font-weight: 600;
    background: #f8f9fa;
  }

  /* range course */
  .calendar .range-primary {
    background: #0d6efd;
    color: #fff;
  }

  /* TODAY harus selalu menang */
  .calendar .today {
    background: #fda50d !important;
    color: #fff;
    font-weight: bold;
  }

  /* ===== Tooltip ===== */
  .calendar .course-tooltip {
    visibility: hidden;
    opacity: 0;
    position: absolute;
    bottom: 120%;
    left: 50%;
    transform: translateX(-50%);
    background: #212529;
    color: #fff;
    padding: 4px 8px;
    border-radius: 4px;
    font-size: 0.75rem;
    white-space: nowrap;
    z-index: 10;
    transition: opacity 0.2s ease;
  }

  .calendar div:hover .course-tooltip {
    visibility: visible;
    opacity: 1;
  }

  /* === Dashboard Cards === */
  .dashboard-section .card {
    border: none;
    border-radius: 0.75rem;
  }

  .dashboard-section .card-body {
    padding: 1rem 1.25rem;
  }

  .course-card {
    transition: all 0.2s ease;
  }

  .course-card:hover {
    transform: translateY(-4px);
    box-shadow: 0 0.75rem 1.25rem rgba(0, 0, 0, 0.08);
  }

  .list-group-item-action:hover {
    background-color: #f9fafb;
  }
   .reveal-card {
      opacity: 0;
      transform: translateY(20px);
      transition: opacity 0.5s ease, transform 0.5s ease;
  }

  .reveal-card.show {
      opacity: 1;
      transform: translateY(0);
  }
</style>

@php
  $coursePeriods = $calendarCourses->map(function ($c) {
    return [
      'title' => $c->course_title,
      'start' => $c->start_course,
      'end'   => $c->end_course,
    ];
  })->values();
@endphp

<div class="dashboard-section">
  {{-- HEADER --}}
  <div class="card shadow-sm mb-4 border-0">
    <div class="card-body bg-light rounded d-flex justify-content-between align-items-center flex-wrap">
      <div>
        <h4 class="fw-bold text-dark mb-1">🎓 Learner Dashboard</h4>
        <p class="text-muted mb-0">Overview of your learning progress and activities.</p>
      </div>
    </div>
  </div>

  <div class="row g-3">

    {{-- ==== LEFT COLUMN ==== --}}
    <div class="col-md-4 d-flex flex-column gap-3">
    
      {{-- Calendar --}}
      <div class="card shadow-sm">
        <div class="card-body text-center">
          <h6 class="fw-bold mb-3">📅 Kalender</h6>

          <div class="mb-2">
            <p class="fw-bold fs-5 mb-0" id="currentTime"></p>
            <p class="fw-semibold text-muted" id="todayDate"></p>
          </div>

          <div id="calendarBox" class="calendar border rounded p-2 bg-light"></div>
        </div>
      </div>

      {{-- Quick Access --}}
      <div class="card shadow-sm">
        <div class="card-body">
          <h6 class="fw-bold mb-3">⚡ Akses Cepat LMS</h6>
          <ul class="list-group list-group-flush">
            <li class="list-group-item">
              <a href="{{ route('learner.course') }}" class="text-decoration-none text-dark">📚 Kursus Saya</a>
            </li>
            <li class="list-group-item">
              <a href="{{ route('general.profile') }}" class="text-decoration-none text-dark">🎓 Profile Saya</a>
            </li>
          </ul>
        </div>
      </div>
    
      {{-- Informasi --}}
      <div class="card shadow-sm">
        <div class="card-body">
          <div class="d-flex justify-content-between align-items-center mb-3">
            <h6 class="fw-bold mb-0">ℹ️ Informasi Tugas</h6>
            <button class="btn btn-sm btn-outline-secondary" data-bs-toggle="modal" data-bs-target="#taskDetailModal">
              Lihat Detail
            </button>
          </div>
          <ul class="list-group list-group-flush" style="max-height:150px; overflow-y:auto;">
            @forelse($taskCourses as $task)
              <li class="list-group-item d-flex justify-content-between align-items-center">
                <span>
                  @php
                    $dueStart = $task->course_due_start?->translatedFormat('d F Y');
                    $dueEnd = $task->course_due_end?->translatedFormat('d F Y');
                  @endphp

                  @if($task->status === 'upcoming')
                    📌 <strong>{{ $task->course->course_title }}</strong> 
                    → {{ $task->module->course_week_title }} → {{ $task->course_item_name }} 
                    dibuka pada <strong>{{ $dueStart }}</strong>.
                  @elseif($task->status === 'active')
                    ⏳ <strong>{{ $task->course->course_title }}</strong> 
                    → {{ $task->module->course_week_title }} → {{ $task->course_item_name }} 
                    sampai <strong>{{ $dueEnd }}</strong>.
                  @endif
                </span>

                <a href="{{ route('learner.course-enrolled', $task->course_id) }}" class="btn btn-sm btn-secondary">
                  Lihat
                </a>
              </li>
            @empty
              <li class="list-group-item text-center text-muted">Belum ada aktivitas terkini.</li>
            @endforelse
          </ul>
        </div>
      </div>

      {{-- Motivation --}}
      <div class="card shadow-sm">
        <div class="card-body text-center">
          <blockquote class="blockquote mb-0">
            <p id="motivationalQuote" class="fst-italic">
              <!-- Quote akan muncul di sini -->
            </p>
            <footer class="blockquote-footer mt-1">{{ json_lang('Motivational Quote') }}</footer>
          </blockquote>
        </div>
      </div>

    </div>

    {{-- ==== RIGHT COLUMN ==== --}}
    <div class="col-md-8 d-flex flex-column gap-3">

     {{-- Enrolled Courses --}}
      <div class="card shadow-sm">
        <div class="card-body">
          <h5 class="fw-bold mb-3">📊 Kursus Tergabung</h5>

          @php $totalCourses = $enrollments->count(); @endphp

          <div class="d-flex overflow-auto gap-3">
            @forelse($enrollments as $enroll)
              <div class="flex-shrink-0" style="width:280px;">
                <div class="card course-card h-100 shadow-sm reveal-card">
                  <div class="card-body d-flex flex-column justify-content-between">
                    {{-- Thumbnail --}}
                    <div class="mb-3 bg-light d-flex justify-content-center align-items-center overflow-hidden" 
                        style="height:140px; border:1px solid #eee;">
                      @if($enroll->course && $enroll->course->course_image)
                        <img src="{{ asset('assets/img/course/'.$enroll->course->course_image) }}" 
                            alt="Thumbnail" class="img-fluid"
                            style="height:100%; width:100%; object-fit:contain;">
                      @else
                        <span class="text-muted small">No Thumbnail</span>
                      @endif
                    </div>

                    {{-- Judul --}}
                    <h6 class="fw-bold text-center mb-0 fs-6">{{ $enroll->course->course_title ?? '-' }}</h6>
                    <span class="text-muted text-center text-nowrap fst-italic fs-6">
                      Terakhir akses {{ $enroll->last_access ?? '-' }}
                    </span>
                  </div>

                  {{-- Footer --}}
                  <div class="card-footer bg-white border-0 pt-2">
                    @php
                      $progress = $enroll->progress ?? 0;
                      $color = $progress >= 70 ? 'bg-success' : ($progress >= 30 ? 'bg-warning' : 'bg-danger');
                    @endphp

                    {{-- Progress --}}
                   <div class="mb-2">
                      <div class="progress" style="height:8px;">
                        <div class="progress-bar {{ $color }} animated-progress" style="width:0%;" data-progress="{{ $progress }}"></div>

                      </div>
                      <small class="text-muted">
                        {{ $progress }}{{ json_lang('% Completed') }}
                      </small>
                    </div>

                    {{-- Tombol Status --}}
                    @if($enroll->progress == 100)
                        <a href="{{ route('learner.course-enrolled', $enroll->course->course_id) }}" 
                          class="btn btn-success w-100">
                            ✅ {{ json_lang('Completed') }}

                            @if($enroll->start_formatted || $enroll->end_formatted)
                                <br><small class="text-light opacity-75">
                                    ({{ $enroll->start_formatted }} – {{ $enroll->end_formatted }})
                                </small>
                            @endif
                        </a>
                    @elseif(
                        !$enroll->is_period_open &&
                        $enroll->progress < 100)

                        <a href="{{ route('learner.course-enrolled', $enroll->course->course_id) }}" class="btn btn-secondary w-100">
                          🛑 Preview Course

                          @if($enroll->start_formatted || $enroll->end_formatted)
                              <br><small class="text-light">
                                  ({{ $enroll->start_formatted }} – {{ $enroll->end_formatted }})
                              </small>
                          @endif
                        </a>

                     @elseif(
                        !$enroll->is_period_open &&
                        $enroll->period_message === 'Belum dimulai'
                    )
                        <button class="btn btn-secondary w-100" disabled>
                            ⏳ {{ json_lang('Not Started') }}

                            @if($enroll->start_formatted || $enroll->end_formatted)
                                <br><small class="text-light">
                                    ({{ $enroll->start_formatted }} – {{ $enroll->end_formatted }})
                                </small>
                            @endif
                        </button>
                    @elseif($enroll->is_opened)

                        <a href="{{ route('learner.course-enrolled', $enroll->course->course_id) }}" 
                          class="btn btn-warning w-100">

                            {{ json_lang('Continue Progress') }}

                            @if($enroll->start_formatted || $enroll->end_formatted)
                                <br><small class="text-light opacity-75">
                                    ({{ $enroll->start_formatted }} – {{ $enroll->end_formatted }})
                                </small>
                            @endif
                        </a>

                    @else
                        <a href="{{ route('learner.course-enrolled', $enroll->course->course_id) }}" 
                          class="btn btn-secondary w-100">

                            {{ json_lang('Enter Course') }}

                            @if($enroll->start_formatted || $enroll->end_formatted)
                                <br><small class="text-light opacity-75">
                                    ({{ $enroll->start_formatted }} – {{ $enroll->end_formatted }})
                                </small>
                            @endif
                        </a>
                    @endif
                  </div>
                </div>
              </div>
            @empty
              <div class="text-center text-muted py-3">
                Kamu belum mengikuti kursus apapun.
              </div>
            @endforelse
          </div>
        </div>
      </div>

      {{-- Kursus Relevan --}}
      @php
        /**
        * Jika course relevan kosong,
        * maka tampilkan course kategori "Lainnya"
        */
        $displayCourses = $relevantCourses->isNotEmpty()
            ? $relevantCourses
            : $otherCourses;
      @endphp

      <div class="card shadow-sm">
        <div class="card-body">
          <h6 class="fw-bold mb-3">
            📚 {{ json_lang('Relevant Courses') }}
          </h6>

          <div class="d-flex overflow-auto gap-3">
            @forelse($displayCourses as $course)
              <div class="flex-shrink-0" style="width:280px;">
                <div class="card course-card h-100 shadow-sm reveal-card">

                  {{-- Thumbnail --}}
                  <div class="card-body d-flex flex-column justify-content-between">
                    <div class="mb-3 bg-light d-flex justify-content-center align-items-center overflow-hidden"
                        style="height:140px; border:1px solid #eee;">
                      @if($course->course_image)
                        <img src="{{ asset('assets/img/course/'.$course->course_image) }}"
                            class="img-fluid"
                            style="height:100%; width:100%; object-fit:cover;">
                      @else
                        <span class="text-muted small">
                          {{ json_lang('No Thumbnail') }}
                        </span>
                      @endif
                    </div>

                    {{-- Title --}}
                    <h6 class="fw-bold text-center mb-0">
                      {{ $course->course_title ?? '-' }}
                    </h6>
                  </div>

                  {{-- Footer --}}
                  <div class="card-footer bg-white border-0 pt-2">
                    <a href="{{ route('learner.detail-course', $course->course_id) }}"
                      class="btn btn-secondary w-100">
                      {{ json_lang('View Details') }}
                    </a>
                  </div>

                </div>
              </div>
            @empty
              <div class="text-center text-muted py-3">
                {{ json_lang('No courses available at the moment.') }}
              </div>
            @endforelse
          </div>
        </div>
      </div>


      {{-- Kursus Publik --}}
      <div class="card shadow-sm">
        <div class="card-body">
          <div class="d-flex justify-content-between align-items-center mb-3">
            <h6 class="fw-bold mb-0">🌍 Kursus Publik</h6>
            <a href="{{ route('learner.explore') }}" class="btn btn-outline-secondary btn-sm fw-semibold">
              🔍 Lihat Semua
            </a>
          </div>
          <div class="d-flex overflow-auto gap-3">
            @forelse($publicCourses as $public)
              <div class="flex-shrink-0" style="width:280px;">
                <div class="card course-card h-100 shadow-sm reveal-card">
                  <div class="card-body d-flex flex-column justify-content-between">
                    <div class="mb-3 bg-light d-flex justify-content-center align-items-center overflow-hidden" 
                         style="height:140px; border:1px solid #eee;">
                      @if($public->course_image)
                        <img src="{{ asset('assets/img/course/'.$public->course_image) }}" 
                             class="img-fluid" style="height:100%; width:100%; object-fit:cover;">
                      @else
                        <span class="text-muted small">No Thumbnail</span>
                      @endif
                    </div>
                    <h6 class="fw-bold text-center mb-0">{{ $public->course_title ?? '-' }}</h6>
                  </div>
                  <div class="card-footer bg-white border-0 pt-2">
                    <a href="{{ route('learner.detail-course', $public->course_id) }}" 
                       class="btn btn-secondary w-100">Lihat Detail</a>
                  </div>
                </div>
              </div>
            @empty
              <div class="text-center text-muted py-3">Belum ada kursus publik.</div>
            @endforelse
          </div>
        </div>
      </div>

    </div>
  </div>
</div>
{{-- MODAL DETAIL INFORMASI --}}
<div class="modal fade" id="taskDetailModal" tabindex="-1" data-bs-backdrop="static" data-bs-keyboard="false">
  <div class="modal-dialog modal-xl modal-dialog-scrollable">
    <div class="modal-content">
      <div class="modal-header bg-light">
        <h5 class="modal-title">📋 Detail Tugas Kursus</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body">
        <div class="mb-3">
          <label class="form-label fw-semibold">Pilih Kursus</label>
          <select class="form-select" id="filterCourseSelect">
            <option value="">-- Semua Kursus --</option>
            @foreach($courseLearner as $course)
              <option value="{{ $course->course_id }}">{{ $course->course_title ?? 'Tanpa Judul' }}</option>
            @endforeach
          </select>
        </div>

        <ul class="list-group list-group-flush" id="taskList" style="max-height:500px; overflow-y:auto;">
          @forelse($taskCourses as $task)
            <li class="list-group-item d-flex justify-content-between align-items-center"
                data-course-id="{{ $task->course_id }}">
              <span>
                @php
                  $dueStart = $task->course_due_start?->translatedFormat('d F Y');
                  $dueEnd = $task->course_due_end?->translatedFormat('d F Y');
                @endphp

                @if($task->status === 'upcoming')
                  📌 <strong>{{ $task->course->course_title }}</strong> 
                  → {{ $task->module->course_week_title }} → {{ $task->course_item_name }} 
                  dibuka pada <strong>{{ $dueStart }}</strong>.
                @elseif($task->status === 'active')
                  ⏳ <strong>{{ $task->course->course_title }}</strong> 
                  → {{ $task->module->course_week_title }} → {{ $task->course_item_name }} 
                  sampai <strong>{{ $dueEnd }}</strong>.
                @endif
              </span>

              <a href="{{ route('learner.course-enrolled', $task->course_id) }}" class="btn btn-sm btn-secondary">
                Lihat
              </a>
            </li>
          @empty
            <li class="list-group-item text-muted text-center">Belum ada informasi tugas.</li>
          @endforelse
        </ul>
      </div>
    </div>
  </div>
</div>
<script>
  // 🔹 Inject ke JavaScript
  window.coursePeriods = @json($coursePeriods);
  
    const quotes = [
        "Belajar itu bukan tentang siapa yang tercepat, tapi siapa yang konsisten.",
        "Langkah kecil setiap hari akan menghasilkan perubahan besar.",
        "Konsistensi mengalahkan bakat saat bakat tidak bekerja keras.",
        "Jangan menunggu kesempatan. Mulailah dari sekarang.",
        "Batasanmu—hanya ada di dalam imajinasimu.",
        "Sukses dimulai dari keberanian untuk memulai.",
        "Setiap hari adalah kesempatan baru untuk menjadi lebih baik.",
        "Jangan takut gagal, takutlah untuk tidak mencoba.",
        "Perubahan besar lahir dari kebiasaan kecil yang konsisten.",
        "Kegigihan hari ini menentukan keberhasilan besok."
      ];

  // Pilih quote random
  const randomIndex = Math.floor(Math.random() * quotes.length);
  const quoteElement = document.getElementById('motivationalQuote');
  quoteElement.innerText = quotes[randomIndex];
  
  document.addEventListener("DOMContentLoaded", function () {

      const cards = document.querySelectorAll(".reveal-card");

      cards.forEach((card, index) => {
          setTimeout(() => {
              card.classList.add("show");
          }, index * 200); // 150ms delay antar card
      });

      const bars = document.querySelectorAll(".animated-progress");

      bars.forEach(bar => {
          const value = bar.dataset.progress;

          setTimeout(() => {
              bar.style.transition = "width 2s ease";
              bar.style.width = value + "%";
          }, 250);
      });

  });
  
</script>