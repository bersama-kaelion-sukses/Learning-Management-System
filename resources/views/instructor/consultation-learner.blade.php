@extends('layouts.master')

@section('title', 'Konsultasi Learner')

@section('content')
<div class="container py-4">

    {{-- HEADER --}}
    <div class="d-flex justify-content-center align-items-center mb-4">
        <di class="text-center">
            <h3 class="fw-bold mb-0 text-dark"> Konsultasi Learner</h3>
            <p class="text-muted mb-0">Pantau dan kelola sesi konsultasi antara learner dan trainer.</p>
        </div>
    </div>

    {{-- TABLE --}}
    <div class="card shadow-sm border-0">
        <div class="card-body">
            <h6 class="fw-bold mb-3 text-dark">📋 Daftar Konsultasi</h6>

            <div class="table-responsive">
                <table class="table table-hover table-striped align-middle mb-0">
                    <thead class="table-light text-center align-middle">
                        <tr>
                            <th style="width: 50px;">#</th>
                            <th style="width: 180px;">Nama Kursus</th>
                            <th>Topik</th>
                            <th style="width: 160px;">Instructor</th>
                            <th style="width: 160px;">Learner</th>
                            <th style="width: 120px;">Status</th>
                            <th>Feedback</th>
                            <th style="width: 200px;">Aksi</th>
                        </tr>
                    </thead>

                    <tbody class="text-center">
                        @forelse($consultations as $index => $c)
                            @php
                                $badgeColor = match($c->status_consultation) {
                                    'waiting' => 'bg-warning text-dark',
                                    'progress' => 'bg-info text-dark',
                                    'done' => 'bg-success',
                                    default => 'bg-secondary'
                                };
                            @endphp

                            <tr>
                                <td class="fw-semibold">{{ $index + 1 }}</td>
                                <td class="fw-medium">{{ $c->course->course_title ?? '-' }}</td>
                                <td class="text-start">{{ $c->topic ?? '—' }}</td>
                                <td>{{ $c->trainer->full_name ?? '-' }}</td>
                                <td>{{ $c->learner->full_name ?? '-' }}</td>
                                <td>
                                    <span class="badge {{ $badgeColor }}">
                                        {{ ucfirst($c->status_consultation) }}
                                    </span>
                                </td>
                                <td class="text-start">
                                    {{ $c->feedback ? Str::limit($c->feedback, 80) : '—' }}
                                </td>
                                <td class="text-center">
                                    {{-- Aksi Trainer --}}
                                    @if(strtolower(trim($c->status_consultation)) === 'waiting')
                                        <form action="{{ route('learner.consultation.status', $c->consultation_id) }}" method="POST" class="d-inline">
                                            @csrf
                                            <input type="hidden" name="status_consultation" value="progress">
                                            <button type="submit" class="btn btn-sm btn-success shadow-sm">
                                                Mulai
                                            </button>
                                        </form>

                                    @elseif(strtolower(trim($c->status_consultation)) === 'progress')
                                        <button class="btn btn-sm btn-warning text-dark shadow-sm"
                                                data-bs-toggle="modal"
                                                data-bs-target="#modalSetResult"
                                                data-id="{{ $c->consultation_id }}"
                                                data-topic="{{ $c->topic }}"
                                                data-feedback="{{ $c->feedback }}">
                                            <i class="bi bi-clipboard-check me-1"></i> Tetapkan Hasil
                                        </button>

                                    @elseif(strtolower(trim($c->status_consultation)) === 'done')
                                        <div class="text-center">
                                            <span class="badge bg-success mb-1">
                                                <i class="bi bi-check-circle me-1"></i> Selesai
                                            </span>
                                            <div class="small text-muted">
                                                {{ $c->updated_at ? $c->updated_at->format('d M Y, H:i') : '-' }}
                                            </div>
                                        </div>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="8" class="text-muted py-4">
                                    <i class="bi bi-inbox me-1"></i> Belum ada data konsultasi.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>


{{-- ===================================================== --}}
{{-- 💬 MODAL: TETAPKAN HASIL (TRAINER) --}}
{{-- ===================================================== --}}
<div class="modal fade" id="modalSetResult" tabindex="-1" data-bs-backdrop="static">
    <div class="modal-dialog">
        <div class="modal-content border-0 shadow">
            <div class="modal-header bg-light">
                <h5 class="modal-title fw-bold">📝 Tetapkan Hasil Konsultasi</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>

            <form id="setResultForm" method="POST">
                @csrf
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Topik</label>
                        <input type="text" id="resultTopic" class="form-control" disabled>
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-semibold">Feedback</label>
                        <textarea name="feedback" id="resultFeedback" class="form-control" rows="3" required></textarea>
                    </div>
                </div>

                <div class="modal-footer bg-light">
                    <input type="hidden" name="status_consultation" value="done">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-success fw-semibold">Simpan Hasil</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', () => {
    const modal = document.getElementById('modalSetResult');
    const form = document.getElementById('setResultForm');
    const topic = document.getElementById('resultTopic');
    const feedback = document.getElementById('resultFeedback');

    modal.addEventListener('show.bs.modal', (event) => {
        const button = event.relatedTarget;
        const id = button.getAttribute('data-id');
        const t = button.getAttribute('data-topic');
        const f = button.getAttribute('data-feedback') || '';

        topic.value = t;
        feedback.value = f;
        form.action = `/learner-consultation/status/${id}`;
    });
});
</script>
@endpush
