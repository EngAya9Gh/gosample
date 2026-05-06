<div class="table-responsive">
    <table class="table table-hover align-middle mb-0">
        <thead class="table-light text-muted">
            <tr>
                <th class="ps-4">Rank</th>
                <th>Driver Name</th>
                <th class="text-center">Days Present</th>
                <th class="text-center">Days Absent</th>
                <th class="text-center">Days Late</th>
                <th class="text-center text-danger">Violations</th>
                <th class="text-center">Total Delay (Min)</th>
                <th class="text-center">Hrs Balance</th>
                <th class="text-center">Performance Score</th>
                <th class="pe-4 text-end">Action</th>
            </tr>
        </thead>
        <tbody>
            @forelse($drivers->sortByDesc('performance_score') as $driver)
                @php
                    $score = $driver->performance_score;
                    $scoreClass = $score >= 80 ? 'success' : ($score >= 50 ? 'warning' : 'danger');
                    $balance = ($driver->total_overtime ?? 0) - ($driver->total_early_leave ?? 0);
                    $balanceClass = $balance >= 0 ? 'text-success' : 'text-danger';
                @endphp
                <tr>
                    <td class="ps-4">
                        @if($loop->index == 0) <span class="badge bg-warning fs-14">🥇 1st</span>
                        @elseif($loop->index == 1) <span class="badge bg-light text-muted fs-12">🥈 2nd</span>
                        @elseif($loop->index == 2) <span class="badge bg-light text-muted fs-12">🥉 3rd</span>
                        @else <span class="text-muted fw-bold ms-2">{{ $loop->iteration }}th</span>
                        @endif
                    </td>
                    <td>
                        <div class="d-flex align-items-center">
                            <div class="avatar-xs flex-shrink-0 me-2">
                                <div class="avatar-title bg-light text-primary rounded-circle fs-10">
                                    {{ substr($driver->name, 0, 1) }}
                                </div>
                            </div>
                            <div>
                                <h6 class="mb-0 fw-bold">{{ $driver->name }}</h6>
                                <small class="text-muted">{{ $driver->username }}</small>
                            </div>
                        </div>
                    </td>
                    <td class="text-center fw-medium">{{ $driver->days_present }}</td>
                    <td class="text-center text-warning fw-bold">{{ $driver->days_absent }}</td>
                    <td class="text-center text-danger">{{ $driver->days_late }}</td>
                    <td class="text-center">
                        <span class="badge rounded-pill {{ ($driver->kpi_violations ?? 0) > 0 ? 'bg-danger-subtle text-danger' : 'bg-success-subtle text-success' }}">
                            {{ $driver->kpi_violations ?? 0 }}
                        </span>
                    </td>
                    <td class="text-center">{{ $driver->total_delay }}</td>
                    <td class="text-center {{ $balanceClass }} fw-bold">
                        {{ $balance >= 0 ? '+' : '' }}{{ round($balance / 60, 1) }}h
                    </td>
                    <td class="text-center">
                        <div class="d-flex align-items-center justify-content-center gap-2">
                            <div class="progress progress-sm flex-grow-1" style="width: 80px;">
                                <div class="progress-bar bg-{{ $scoreClass }}" role="progressbar" style="width: {{ $score }}%"></div>
                            </div>
                            <span class="badge badge-soft-{{ $scoreClass }}">{{ $score }}%</span>
                        </div>
                    </td>
                    <td class="pe-4 text-end">
                        <a href="{{ route('admin.drivers.show', $driver->id) }}" class="btn btn-ghost-primary btn-sm">View Profile</a>
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="10" class="text-center py-5">
                        <i class="ri-error-warning-line display-4 text-muted"></i>
                        <p class="mt-2 text-muted">No data available for this period.</p>
                    </td>
                </tr>
            @endforelse
        </tbody>
    </table>
</div>
