@extends('layouts.master')

@section('content')
<div class="container py-4">
    <h3 class="mb-3 fw-bold">Detail Permohonan</h3>

    {{-- Informasi utama --}}
    <div class="card mb-4 shadow-sm">
        <div class="card-body">
            <h5 class="card-title">{{ $request->request_title }}</h5>
            <p class="mb-2"><strong>Jenis Permohonan:</strong> {{ $request->approvalType->approval_name }}</p>
            <p class="mb-2"><strong>Pengaju:</strong> {{ $request->requester->full_name }} ({{ $request->requester->emp_id }})</p>
            <p class="mb-2"><strong>Status:</strong> 
            <span class="badge rounded-pill 
                {{ $request->status === 'Rejected' ? 'bg-danger' : 
                ($request->status === 'Approved Final' ? 'bg-success' : 
                ($request->status === 'Cancelled' ? 'bg-secondary' : 'bg-warning text-dark')) }}">
                {{ $request->status }}
            </span>
            </p>
            <p><strong>Diajukan pada:</strong> {{ $request->submitted_at?->format('d M Y H:i') }}</p>
            <p><strong>Detail:</strong></p>
            {!! $request->request_detail ?? '-' !!}
            {{-- Tombol untuk buka detail course --}}
            @if($request->approval_id == 4)
                @if($courseId)
                    <a href="{{ route('instructor.detail-course', $courseId) }}" 
                    class="btn btn-secondary mb-3">
                        📖 Lihat Detail Course
                    </a>
                @endif
            @endif
        </div>
    </div>

    {{-- Alur Approval --}}
    <h5 class="fw-bold">Alur Approval</h5>
    <table class="table table-sm table-bordered mb-4">
        <thead>
            <tr>
                <th>Step</th>
                <th>Approver</th>
                <th>Status</th>
                <th>Action Time</th>
                <th>Notes</th>
            </tr>
        </thead>
        <tbody>
            @foreach($request->routes as $route)
            <tr>
                <td>{{ $route->step_no }}</td>
                <td>{{ $route->approver?->full_name ?? '-' }}</td>
                <td>
                    <span class="badge bg-{{ 
                        $route->action_status === 'Approved' ? 'success' : 
                        ($route->action_status === 'Rejected' ? 'danger' : 
                        ($route->action_status === 'Pending' ? 'warning' : 'secondary')) 
                    }}">
                        {{ $route->action_status }}
                    </span>
                </td>
                <td>{{ $route->acted_at ? \Carbon\Carbon::parse($route->acted_at)->format('d M Y H:i') : '-' }}</td>
                <td>{{ $route->notes ?? '-' }}</td>
            </tr>
            @endforeach
        </tbody>
    </table>

    {{-- Riwayat (History) --}}
    <h5 class="fw-bold">Riwayat</h5>
    <ul class="list-group mb-4">
        @foreach($request->histories as $history)
        <li class="list-group-item small">
            <strong>{{ $history->actor?->full_name }}</strong> 
            melakukan <span class="text-primary">{{ $history->action }}</span> 
            pada step {{ $history->step_no }} 
            ({{ $history->created_at ? \Carbon\Carbon::parse($history->created_at)->format('d M Y H:i') : '-' }})<br>
            <em>{{ $history->notes ?? '' }}</em>
        </li>
        @endforeach
    </ul>

    {{-- Proses Approval --}}
    @if($request->status === 'In Review')
        @php
            $user = Auth::user();

            // Drafter check
            $isDrafter = (int)$request->requester_id === (int)$user->user_id;
            $hasApproved = $request->routes()->where('action_status', 'Approved')->exists();

            // Cari apakah user ini approver aktif (di step saat ini dengan status pending)
            $activeRoute = $request->routes
                ->where('step_no', $request->current_step)
                ->where('action_status', 'Pending')
                ->firstWhere('approver_user_id', $user->user_id);
        @endphp
        

        <div class="card shadow-sm mb-4">
            <div class="card-body">
                <h5 class="fw-bold mb-3">Proses Approval</h5>

                {{-- Drafter bisa cancel kalau belum ada yang approve --}}
                @if($isDrafter && !$hasApproved)
                    <form method="POST" action="{{ route('approval.cancel', $request->request_id) }}" 
                          onsubmit="return confirm('Yakin batalkan request ini?')">
                        @csrf
                        <button type="submit" class="btn btn-warning">Batalkan Permohonan</button>
                    </form>
                @endif

                {{-- Approver aktif bisa approve/reject --}}
                @if($activeRoute)
                    <hr>
                    <form method="POST" action="{{ route('approval.process', $request->request_id) }}">
                        @csrf
                        <div class="mb-3">
                            <label class="form-label">Catatan</label>
                            <textarea name="notes" class="form-control" rows="3"></textarea>
                        </div>
                        <div class="d-flex gap-2">
                            <button type="submit" name="action" value="Approve" class="btn btn-success">Setujui</button>
                            <button type="submit" name="action" value="Reject" class="btn btn-danger">Tolak</button>
                            <a href="{{ route('approval.index') }}" class="btn btn-secondary">⬅ Kembali</a>
                        </div>
                    </form>
                @endif

                {{-- Jika bukan drafter dan bukan approver aktif --}}
                @if(!$isDrafter && !$activeRoute)
                    <div class="alert alert-info mb-0 mt-3">
                        Permohonan ini sedang diproses oleh approver lain atau Anda tidak memiliki aksi pada tahap ini.
                    </div>
                @endif
            </div>
        </div>
    @endif

    <a href="{{ route('approval.index') }}" class="btn btn-secondary">Kembali</a>
</div>
@endsection
