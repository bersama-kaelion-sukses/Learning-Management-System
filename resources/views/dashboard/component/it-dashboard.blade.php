<div class="container py-3">

    {{-- HEADER --}}
    <div class="card shadow-sm mb-4 border-0">
        <div class="card-body bg-light rounded d-flex justify-content-between align-items-center flex-wrap">
            <div>
                <h4 class="fw-bold text-dark mb-1">💻 IT Dashboard</h4>
                <p class="text-muted mb-0">
                    This page summarizes system performance, issue logs, and audit trails.
                </p>
            </div>
            <div class="mt-2 mt-md-0">
                <!-- <button class="btn btn-sm btn-outline-secondary"
                        data-bs-toggle="modal"
                        data-bs-target="#AllDataInquiry">
                Lihat Semua
                </button> -->
            </div>
        </div>
    </div>

    {{-- FILTER EXPORT --}}
    <div class="card shadow-sm mb-4 border-0">
        <div class="card-body">
            <h6 class="fw-semibold mb-3 fs-6">📊 Tarik Data Login Pengguna</h6>
                <form class="d-flex align-items-center gap-2 flex-wrap" method="POST" action="{{ route('dashboard.it.activity') }}">
                    @csrf
                    <div class="d-flex align-items-center flex-grow-1 gap-2 flex-wrap">
                    <div class="input-group">
                        <span class="input-group-text">Dari</span>
                        <input type="month" class="form-control" name="start_month" required>
                    </div>
                    <div class="input-group">
                        <span class="input-group-text">Sampai</span>
                        <input type="month" class="form-control" name="end_month" required>
                    </div>
                </div>
                <div class="btn-group mt-2 mt-sm-0">
                    <button type="submit" class="btn btn-success">
                        Export Excel
                    </button>
                </div>
            </form>
        </div>
    </div>

    {{-- ROW 1: SUMMARY STAT --}}
    <div class="row g-3 mb-4">

        @php
            $stats = [
                ['icon' => '🧍‍♂️', 'title' => 'Active Users (This Month)', 'value' => $activeUsers ?? 0, 'sub' => '▲ Active this month', 'color' => 'text-success'],
                ['icon' => '🔐', 'title' => 'Login Sessions', 'value' => $loginSessions ?? 0, 'sub' => 'Recorded this month', 'color' => 'text-success'],
                ['icon' => '📋', 'title' => 'Approval Requests', 'value' => $approvalRequests ?? 0, 'sub' => 'All statuses included', 'color' => 'text-warning'],
                ['icon' => '📚', 'title' => 'Learner Activity Logs', 'value' => $learnerLogs ?? 0, 'sub' => 'Submissions & enrollments', 'color' => 'text-muted'],
            ];
        @endphp

        @foreach ($stats as $item)
        <div class="col-6 col-md-3">
            <div class="card text-center shadow-sm border-0 h-100 hover-shadow transition">
                <div class="card-body d-flex flex-column justify-content-center">
                    <h6 class="text-muted">{{ $item['icon'] }} {{ $item['title'] }}</h6>
                    <h3 class="fw-bold text-primary mb-2">{{ $item['value'] }}</h3>
                    <small class="{{ $item['color'] }} fw-semibold">{{ $item['sub'] }}</small>
                </div>
            </div>
        </div>
        @endforeach
    </div>

    {{-- ROW 2: TABLES --}}
    <div class="row g-3">

        {{-- Approval Table --}}
        <div class="col-md-6 g-3">
            <div class="card shadow-sm border-0 h-100">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <h6 class="fw-bold mb-0">✅ Recent Approval Request </h6>
                    </div>
                    <div class="table-responsive" style="max-height:400px;overflow-y">
                        <table class="table table-sm table-striped align-middle mb-0">
                            <thead class="table-light text-center">
                                <tr>
                                    <th class="text-nowrap">Approval Id</th>
                                    <th class="text-nowrap">Approval Type</th>
                                    <th class="text-nowrap">Judul Approval</th>
                                    <th>Status</th>
                                    <th class="text-nowrap">Update Terakhir</th>
                                </tr>
                            </thead>
                            <tbody class="text-center">
                                @forelse($recentApprovals as $item)
                                    <tr>
                                        <td>{{ $item->request_id }}</td>
                                        <td class="text-nowrap">{{ $item->approval_type ?? '-' }}</td>
                                        <td class="text-start">{{ $item->request_title }}</td>
                                        <td><span class="badge bg-{{ $item->status_color }}">{{ $item->status }}</span></td>
                                        <td>{{ \Carbon\Carbon::parse($item->updated_at)->format('Y-m-d') }}</td>
                                    </tr>
                                @empty
                                    <tr><td colspan="5" class="text-muted">No recent approvals.</td></tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>


        {{-- Learner Table --}}
        <div class="col-md-6">
            <div class="card shadow-sm border-0 h-100">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <h6 class="fw-bold mb-0">🎓 Top Active Learners</h6>
                    </div>

                    <div class="table-responsive" style="max-height:400px; overflow-y:auto;">
                        <table class="table table-sm table-hover align-middle mb-0">
                            <thead class="table-light text-center">
                                <tr>
                                    <th>Emp ID</th>
                                    <th>Name</th>
                                    <th>Division</th>
                                    <th>Completed</th>
                                    <th>Progress</th>
                                </tr>
                            </thead>
                            <tbody class="text-center">
                                @forelse($topLearners as $learner)
                                    <tr>
                                        <td>{{ $learner->emp_id ?? '-' }}</td>
                                        <td>{{ $learner->name }}</td>
                                        <td>{{ $learner->division_name ?? '-' }}</td>
                                        <td>{{ $learner->completed }}</td>
                                        <td>
                                            <div class="progress" style="height:6px;">
                                                <div class="progress-bar {{ $learner->progress_color }}" 
                                                    style="width: {{ round($learner->progress, 0) }}%;">
                                                </div>
                                            </div>
                                            <small class="text-muted">{{ round($learner->progress, 1) }}%</small>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="5" class="text-muted">No learner data available.</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="row g-3">
        <div class="col-md-12 mt-4">
            <div class="card shadow-sm border-0 h-100">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <h6 class="fw-bold mb-0">🧾 Riwayat Approval</h6>
                    </div>

                    <div class="table-responsive" style="max-height:400px; overflow-y:auto;">
                        <table class="table table-sm table-striped align-middle mb-0">
                            <thead class="table-light text-center">
                                <tr>
                                    <th class="text-nowrap">Approval Id</th>
                                    <th>Approval Type</th>
                                    <th>Judul Approval</th>
                                    <th>Step</th>
                                    <th>Aksi</th>
                                    <th>PIC</th>
                                    <th>Emp ID</th>
                                    <th>Departemen</th>
                                    <th>Catatan</th>
                                    <th>Tanggal</th>
                                </tr>
                            </thead>
                            <tbody class="text-center">
                                @forelse($approvalHistories as $item)
                                    @php
                                        $color = match($item->action) {
                                            'Submit' => 'secondary',
                                            'Approve' => 'success',
                                            'Reject' => 'danger',
                                            'Cancel'  => 'warning text-dark',
                                        };
                                    @endphp
                                    <tr>
                                        <td>{{ $item->request_id }}</td>
                                        <td>{{ $item->request?->approvalType?->approval_name ?? '-' }}</td>
                                        <td class="text-start " style="max-width:200px" title="{{ $item->request?->request_title }}">
                                            {{ $item->request?->request_title ?? '-' }}
                                        </td>
                                        <td>{{ $item->step_no ?? '-' }}</td>
                                        <td><span class="badge bg-{{ $color }}">{{ $item->action ?? '-' }}</span></td>
                                        <td>{{ $item->actor?->full_name ?? '-' }}</td>
                                        <td>{{ $item->actor?->emp_id ?? '-' }}</td>
                                        <td>{{ $item->actor?->division?->division_name ?? '-' }}</td>
                                        <td style="max-width:180px;" title="{{ $item->notes }}">
                                            {{ $item->notes ?? '-' }}
                                        </td>
                                        <td>{{ \Carbon\Carbon::parse($item->created_at)->format('Y-m-d H:i:s') }}</td>
                                    </tr>
                                @empty
                                    <tr><td colspan="10" class="text-muted">Tidak ada data Approval History.</td></tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-12 mt-4">
            <div class="card shadow-sm border-0 h-100">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <h6 class="fw-bold mb-0">🧭 Approval Route (Jalur Persetujuan)</h6>
                    </div>

                    <div class="table-responsive" style="max-height:400px; overflow-y:auto;">
                        <table class="table table-sm table-striped align-middle mb-0">
                            <thead class="table-light text-center">
                                <tr>
                                    <th class="text-nowrap">Approval Id</th>
                                    <th class="text-nowrap">Approval Type</th>
                                    <th>Judul Approval</th>
                                    <th>Step</th>
                                    <th>Approver</th>
                                    <th>Status</th>
                                    <th>PIC</th>
                                    <th>Departemen</th>
                                    <th>Catatan</th>
                                    <th class="text-nowrap">Tanggal</th>
                                </tr>
                            </thead>
                            <tbody class="text-center">
                                @forelse($approvalRoutes as $route)
                                    @php
                                        $statusColor = match($route->action_status) {
                                            'Approved' => 'success',
                                            'Rejected' => 'danger',
                                            'Pending' => 'secondary',
                                            'Revised' => 'warning text-dark',
                                            default => 'light text-dark'
                                        };
                                    @endphp
                                    <tr>
                                        <td>{{ $route->request_id }}</td>
                                        <td>{{ $route->request?->approvalType?->approval_name ?? '-' }}</td>
                                        <td class="text-start text-truncate" style="max-width:200px" title="{{ $route->request?->request_title }}">
                                            {{ $route->request?->request_title ?? '-' }}
                                        </td>
                                        <td>{{ $route->step_no ?? '-' }}</td>
                                        <td>{{ $route->approver?->full_name ?? '-' }}</td>
                                        <td><span class="badge bg-{{ $statusColor }}">{{ $route->action_status ?? '-' }}</span></td>
                                        <td>{{ $route->actor?->full_name ?? '-' }}</td>
                                        <td>{{ $route->actor?->division?->division_name ?? '-' }}</td>
                                        <td class="text-truncate" style="max-width:180px;" title="{{ $route->notes }}">
                                            {{ $route->notes ?? '-' }}
                                        </td>
                                        <td>
                                            {{ $route->acted_at ? \Carbon\Carbon::parse($route->acted_at)->format('Y-m-d H:i:s') : '-' }}
                                        </td>
                                    </tr>
                                @empty
                                    <tr><td colspan="10" class="text-muted">Tidak ada data Approval Route.</td></tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Fullscreen Modal -->
<div class="modal fade" id="AllDataInquiry" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-fullscreen">
    <div class="modal-content">
      <div class="modal-header bg-light">
        <h5 class="modal-title">📊 All Data Inquiry IT LOG </h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>

      <div class="modal-body">
        <p>Isi konten di sini...</p>
      </div>

      <div class="modal-footer bg-light">
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
        <button type="button" class="btn btn-primary">Save changes</button>
      </div>
    </div>
  </div>
</div>

{{-- STYLE tambahan --}}
<style>
    .hover-shadow:hover {
        transform: translateY(-3px);
        box-shadow: 0 0.5rem 1rem rgba(247, 247, 247, 0.1);
    }
    .transition {
        transition: all 0.2s ease-in-out;
    }
    .stat-card {
        cursor: pointer;
    }
</style>
