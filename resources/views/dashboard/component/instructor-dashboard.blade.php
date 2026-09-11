<div class="container py-3">

    {{-- ===== HEADER ===== --}}
    <div class="card shadow-sm mb-4 border-0">
        <div class="card-body bg-light rounded d-flex justify-content-between align-items-center flex-wrap">
            <div>
                <h4 class="fw-bold text-dark mb-1">🧑‍🏫 Instructor Dashboard</h4>
                <p class="text-muted mb-0">
                    Overview of Learner Performance and Course Management.
                </p>
            </div>
        </div>
    </div>

    {{-- ===== RINGKASAN INSTRUKTUR ===== --}}
    <div class="card shadow-sm mb-4 border-0">
        <div class="card-body">
            <h5 class="fw-bold mb-3">📖 Ringkasan Instructor</h5>
            <ul class="list-group list-group-flush">
                <li class="list-group-item d-flex justify-content-between">
                    <span>Total Kursus yang Diajarkan</span>
                    <span class="fw-bold text-primary">{{ $courses->count() }}</span>
                </li>
                <li class="list-group-item d-flex justify-content-between">
                    <span>Total Peserta Aktif</span>
                    <span class="fw-bold text-success">{{ $getActiveLearner }}</span>
                </li>
                <li class="list-group-item d-flex justify-content-between">
                    <span>Tugas Menunggu Penilaian</span>
                    <span class="fw-bold text-danger">{{ $getWaitingGrading }}</span>
                </li>
                <li class="list-group-item d-flex justify-content-between">
                    <span>Aktivitas Materi Kursus terjadwal </span>
                    <span class="fw-bold text-warning">{{ $getUpcomingActivity }}</span>
                </li>
                <li class="list-group-item d-flex justify-content-between">
                    <span>Peserta Belum Aktif</span>
                    <span class="fw-bold text-muted">{{ $getNonActiveLearner }}</span>
                </li>
                <li class="list-group-item d-flex justify-content-between">
                    <span>Rata-rata Penyelesaian Kursus</span>
                    <span class="fw-bold text-info">{{ $getAverageCompletionCourses }}%</span>
                </li>
            </ul>
        </div>
    </div>

    {{-- ===== ROW: COURSE & ACTIVITY ===== --}}
    <div class="row g-3 mb-4">

        {{-- Kursus Diampu --}}
        <div class="col-md-6">
            <div class="card shadow-sm h-100 border-0">
                <div class="card-body">
                    <h5 class="fw-bold mb-3">📚 Kursus Diampu</h5>
                    <div class="list-group list-group-flush" style="max-height: 250px; overflow-y: auto;">
                        @forelse($courses as $course)
                            <a href="{{ route('instructor.modify-course', $course->course_id) }}" 
                               class="list-group-item list-group-item-action d-flex justify-content-between align-items-center">
                                <span class="text-truncate" style="max-width: 90%;" title="{{ $course->course_title }}">
                                    {{ $course->course_title }}
                                </span>
                                <i class="bi bi-chevron-right text-muted small"></i>
                            </a>
                        @empty
                            <div class="list-group-item text-center text-muted"><em>Tidak ada kursus diampu</em></div>
                        @endforelse
                    </div>
                </div>
            </div>
        </div>

        {{-- Aktivitas Peserta --}}
        <div class="col-md-6">
            <div class="card shadow-sm h-100 border-0">
                <div class="card-body">
                    <h5 class="fw-bold mb-3">👥 Aktivitas Peserta Terbaru</h5>
                    <ul class="list-group list-group-flush" style="max-height: 250px; overflow-y: auto;">
                        @forelse($recentActivities as $activity)
                            <li class="list-group-item d-flex justify-content-between align-items-center">
                                <span>
                                    <strong>{{ $activity['user'] }}</strong> {{ $activity['action'] }} 
                                    <em>{{ $activity['course'] }}</em>
                                </span>
                                <small class="text-muted">
                                    {{ \Carbon\Carbon::parse($activity['time'])->format('d M Y H:i') }}
                                </small>
                            </li>
                        @empty
                            <li class="list-group-item text-center text-muted"><em>Tidak ada aktivitas terbaru</em></li>
                        @endforelse
                    </ul>
                </div>
            </div>
        </div>

    </div>

    {{-- ===== TUGAS MENUNGGU PENILAIAN ===== --}}
    <div class="card shadow-sm border-0">
        <div class="card-body">
            <h5 class="fw-bold mb-3">📝 Tugas Menunggu Penilaian</h5>
            <div class="table-responsive" style="max-height: 280px; overflow-y: auto;">
                <table class="table table-sm align-middle">
                    <thead class="table-light">
                        <tr>
                            <th>Kursus</th>
                            <th>Minggu</th>
                            <th>Tugas</th>
                            <th>Jumlah Submit</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($waitingAssignments as $assign)
                            <tr>
                                <td>{{ $assign['course'] }}</td>
                                <td>{{ $assign['week'] }}</td>
                                <td>{{ $assign['item'] }}</td>
                                <td>{{ $assign['submitted'] }}</td>
                                <td>
                                    <span class="badge bg-{{ $assign['badge'] }}">
                                        {{ $assign['status'] }}
                                    </span>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="text-center text-muted">
                                    <em>Belum ada data tugas</em>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

</div>

{{-- === STYLE TAMBAHAN === --}}
<style>
    .list-group-item {
        transition: background-color 0.2s ease-in-out;
    }
    .list-group-item:hover {
        background-color: #f9fafb;
    }
    .fw-bold { letter-spacing: 0.2px; }
</style>
