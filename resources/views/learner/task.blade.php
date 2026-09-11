@extends('layouts.master')

@section('title', 'Daftar Tugas')

@section('content')
<div class="container py-4">

    <!-- Baris Jadwal & Calendar -->
    <div class="row mb-4">
        <div class="col-md-8">
            <div class="card shadow-sm mb-3">
                <div class="card-body d-flex align-items-center gap-2">
                    <i class="bi bi-calendar-week text-primary fs-4"></i>
                    <h5 class="mb-0 fw-bold">Jadwal Tugas Kursus</h5>
                </div>
            </div>
            <!-- Tombol List Kursus -->
            <div class="d-flex flex-wrap gap-2 mb-3">
                <button class="btn btn-sm btn-primary rounded-pill px-3">Kursus A</button>
                <button class="btn btn-sm btn-outline-primary rounded-pill px-3">Kursus B</button>
                <button class="btn btn-sm btn-outline-primary rounded-pill px-3">Kursus C</button>
                <button class="btn btn-sm btn-outline-primary rounded-pill px-3">Kursus D</button>
            </div>
        </div>

        <div class="col-md-4">
            <div class="card shadow-sm h-100">
                <div class="card-body p-2">
                    <div id="calendar" style="min-height: 400px;"></div>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal Pop Up List Tugas Hari Ini -->
    <div class="modal fade" id="calendarTaskModal" tabindex="-1" aria-labelledby="calendarTaskModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header bg-light">
                    <h5 class="modal-title fw-bold" id="calendarTaskModalLabel">
                        Daftar Tugas <span id="selectedDate" class="text-primary"></span>
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <ul class="list-group" id="taskList"></ul>
                </div>
            </div>
        </div>
    </div>

    <!-- Daftar Tugas Dinamis -->
    <div class="row g-3">
        @for ($i = 1; $i <= 3; $i++)
        <div class="col-12">
            <div class="card shadow-sm hover-shadow-sm">
                <div class="card-body d-flex justify-content-between align-items-center">
                    <span>
                        <i class="bi bi-journal-check text-success me-2"></i>
                        Dynamic berubah berdasarkan tugas dari <strong>Kursus {{ $i }}</strong>
                    </span>
                    <button class="btn btn-outline-primary btn-sm px-3" data-bs-toggle="modal" data-bs-target="#taskModal">
                        <i class="bi bi-eye"></i> Lihat Detail
                    </button>
                </div>
            </div>
        </div>
        @endfor
    </div>

</div>

<!-- Modal Pop Up Detail Tugas -->
<div class="modal fade" id="taskModal" tabindex="-1" aria-labelledby="taskModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header bg-primary text-white">
                <h5 class="modal-title fw-bold" id="taskModalLabel">Nama Kursus - Week n</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <ul class="list-group mb-3">
                    <li class="list-group-item d-flex justify-content-between">
                        Item 1 <span class="badge bg-danger">Belum Selesai</span>
                    </li>
                    <li class="list-group-item d-flex justify-content-between">
                        Item 2 <span class="badge bg-danger">Belum Selesai</span>
                    </li>
                    <li class="list-group-item d-flex justify-content-between">
                        Item 3 <span class="badge bg-danger">Belum Selesai</span>
                    </li>
                </ul>
            </div>
            <div class="modal-footer">
                <button class="btn btn-secondary" data-bs-dismiss="modal">Tutup</button>
                <button class="btn btn-primary">Lompat</button>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    const tasks = [
        { title: 'Tugas Laravel', date: '2025-08-05' },
        { title: 'Kuis Flutter', date: '2025-08-05' },
        { title: 'Diskusi Database', date: '2025-08-08' },
        { title: 'Submit Project', date: '2025-08-12' },
        { title: 'Meeting HR', date: '2025-08-12' },
        { title: 'Review Module', date: '2025-08-12' }
    ];

    const calendarEl = document.getElementById('calendar');

    const calendar = new FullCalendar.Calendar(calendarEl, {
        initialView: 'dayGridMonth',
        locale: 'id',
        height: 400,
        events: tasks.map(task => ({
            title: '•',
            start: task.date,
            display: 'list-item',
            className: 'event-dot'
        })),
        dayMaxEventRows: 3
    });

    calendar.render();

    calendarEl.addEventListener('dblclick', function(e) {
        const cell = e.target.closest('.fc-daygrid-day');
        if (!cell) return;

        const date = cell.dataset.date;
        const taskToday = tasks.filter(t => t.date === date);

        document.getElementById('selectedDate').innerText = date;
        const taskList = document.getElementById('taskList');
        taskList.innerHTML = '';

        if (taskToday.length === 0) {
            taskList.innerHTML = '<li class="list-group-item text-muted">Tidak ada tugas hari ini</li>';
        } else {
            taskToday.forEach(t => {
                const li = document.createElement('li');
                li.className = 'list-group-item d-flex justify-content-between';
                li.innerHTML = `<span>${t.title}</span><span class="badge bg-warning text-dark">Belum Selesai</span>`;
                taskList.appendChild(li);
            });
        }

        new bootstrap.Modal(document.getElementById('calendarTaskModal')).show();
    });
});
</script>

<style>
    /* Dot merah untuk event */
    .event-dot .fc-event-title::before {
        content: "•";
        color: red;
        font-weight: bold;
        margin-right: 2px;
    }

    /* Hover card effect */
    .hover-shadow-sm:hover {
        transform: translateY(-3px);
        transition: 0.2s ease-in-out;
        box-shadow: 0 6px 20px rgba(0,0,0,0.1);
    }

    /* Tombol kursus lebih clean */
    .btn-outline-primary:hover {
        background-color: #0d6efd;
        color: #fff;
    }
</style>
@endpush
