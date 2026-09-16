<div class="container py-4">

    {{-- ===== HEADER ===== --}}
    <div class="card shadow-sm border-0 mb-4">
        <div class="card-body bg-light d-flex justify-content-between align-items-center flex-wrap rounded-3 px-4 py-3">
            <div>
                <h4 class="fw-bold text-dark mb-1">🎓 Administrator Dashboard</h4>
                <p class="text-muted mb-0">Pantau performa keseluruhan sistem, aktivitas pengguna, dan kemajuan kursus.</p>
            </div>
        </div>
    </div>

    {{-- ===== OVERALL STATISTICS ===== --}}
    <div class="card shadow-sm border-0 mb-4">
        <div class="card-body py-4">
            <div class="row text-center g-4">
                <div class="col-6 col-md-3">
                    <div class="p-3 rounded bg-success bg-opacity-10">
                        <h4 class="fw-bold mb-1 text-primary">{{ $totalUsers }}/{{ $activeUsers }}</h4>
                        <small class="text-muted fw-semibold">User Terdaftar / Aktif</small>
                    </div>
                </div>
                <div class="col-6 col-md-3">
                    <div class="p-3 rounded bg-success bg-opacity-10">
                        <h4 class="fw-bold mb-1 text-success">{{ $totalCourses }} / {{ $activeCourses }}</h4>
                        <small class="text-muted fw-semibold">Kursus / Kursus Aktif</small>
                    </div>
                </div>
                <div class="col-6 col-md-3">
                    <div class="p-3 rounded bg-success bg-opacity-10">
                        <h4 class="fw-bold mb-1 text-info">{{ $completeCourses }}%</h4>
                        <small class="text-muted fw-semibold">Rata-rata Completion</small>
                    </div>
                </div>
                <div class="col-6 col-md-3">
                    <div class="p-3 rounded bg-success bg-opacity-10">
                        <h4 class="fw-bold mb-1 text-warning">{{ $dailyLogins }} / {{ $weeklyLogins }}</h4>
                        <small class="text-muted fw-semibold">Login Harian / Mingguan</small>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- ===== DETAIL SECTIONS ===== --}}
    <div class="row g-4">

        {{-- === LEARNER SECTION === --}}
        <div class="col-md-4">
            <div class="card shadow-sm border-0 h-100">
                <div class="card-body">
                    <h6 class="fw-bold mb-3 text-primary">
                        👨‍🎓 Learner
                    </h6>

                    <p class="fw-semibold mb-2">
                        Aktif Hari Ini / Minggu Ini:
                        <span class="text-dark">{{ $activeToday }}</span> /
                        <span class="text-dark">{{ $activeWeek }}</span>
                    </p>

                    <div class="mb-3">
                        <p class="fw-semibold text-secondary mb-1">Progress Rendah:</p>
                        <ul class="small ps-3 mb-0" style="max-height: 400px; overflow-y:auto;">
                            @forelse($lowProgressLearners as $learner)
                                <li>{{ $learner['name'] }} — <span class="text-danger">{{ $learner['progress'] }}%</span></li>
                            @empty
                                <li class="text-muted fst-italic">Tidak ada peserta dengan progress rendah</li>
                            @endforelse
                        </ul>
                    </div>

                    <div>
                        <p class="fw-semibold text-secondary mb-1">Completion Tiap Learner:</p>
                        <ul class="small ps-3 mb-0" style="max-height: 400px; overflow-y:auto;">
                            @forelse($reportDetails as $learner)
                                <li>{{ $learner['name'] }} — {{ $learner['progress'] }}%</li>
                            @empty
                                <li class="text-muted fst-italic">Tidak ada data peserta</li>
                            @endforelse
                        </ul>
                    </div>
                </div>
            </div>
        </div>

        {{-- === COURSE SECTION === --}}
        <div class="col-md-4">
            <div class="card shadow-sm border-0 h-100">
                <div class="card-body">
                    <h6 class="fw-bold mb-3 text-primary">📚 Course</h6>

                    <p class="fw-semibold mb-2">
                        Aktif / Jumlah Peserta:
                        <span class="text-dark">{{ $activeCourses }}</span> /
                        <span class="text-dark">{{ $participantCount }}</span>
                    </p>

                    <div class="mb-3">
                        <p class="fw-semibold text-secondary mb-1">Status Kursus:</p>
                        <ul class="small ps-3 mb-0">
                            <li>Berjalan (Aktif): {{ $activeCourses }}</li>
                            <li>Dalam Proses Persetujuan: {{ $requestCourseCount }}</li>
                            <!-- <li>Selesai: {{ $completedCourses ?? 0 }}</li> -->
                        </ul>
                    </div>

                    <div>
                        <p class="fw-semibold text-secondary mb-1">Completion Rendah:</p>
                        <ul class="small ps-3 mb-0" style="max-height: 500px; overflow-y:auto;">
                            @forelse($lowProgressCourses as $course)
                                <li>{{ $course['course'] }} — <span class="text-danger">{{ $course['progress'] }}%</span></li>
                            @empty
                                <li class="text-muted fst-italic">Tidak ada kursus dengan completion rendah</li>
                            @endforelse
                        </ul>
                    </div>
                </div>
            </div>
        </div>

        {{-- === INSTRUCTOR SECTION === --}}
        <div class="col-md-4">
            <div class="card shadow-sm border-0 h-100">
                <div class="card-body">
                    <h6 class="fw-bold mb-3 text-primary">👩‍🏫 Instruktur</h6>

                    <p class="fw-semibold mb-2">Jumlah Kursus Diampu:</p>
                    <ul class="list-unstyled border rounded px-3 py-2 mb-3 small" style="max-height: 350px; overflow-y:auto;">
                        @forelse($instructorCourse as $instructor)
                            <li class="py-1 border-bottom">
                                {{ $instructor->trainer->full_name ?? 'Unknown' }}
                                — <span class="text-primary">{{ $instructor->total_courses }} kursus</span>
                            </li>
                        @empty
                            <li class="text-muted fst-italic">Belum ada instruktur terdaftar</li>
                        @endforelse
                    </ul>

                    <div>
                        <p class="fw-semibold text-secondary mb-1">Completion Rendah per Instruktur:</p>
                        <ul class="small ps-3 mb-0"  style="max-height: 350px; overflow-y:auto;">
                            @forelse($lowProgressInstructorCourses as $course)
                                <li>{{ $course['course'] }} — {{ $course['instructor'] }} 
                                    (<span class="text-danger">{{ $course['progress'] }}%</span>)
                                </li>
                            @empty
                                <li class="text-muted fst-italic">Tidak ada data completion rendah per instruktur</li>
                            @endforelse
                        </ul>
                    </div>

                    <hr class="my-3">
                    <p class="fw-bold text-dark mb-0">Instruktur Aktif: 
                        <span class="text-success">{{ $instructorActive }}</span>
                    </p>
                </div>
            </div>
        </div>

    </div>
</div>