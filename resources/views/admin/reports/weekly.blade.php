@extends('layouts.master')
@section('title') Weekly Performance @endsection
@section('content')
    @component('components.breadcrumb')
        @slot('li_1') @lang('translation.reports') @endslot
        @slot('title') Weekly Performance Trends @endslot
    @endcomponent

    <div class="row mb-4">
        <div class="col-12">
            <div class="card border-0 shadow-sm">
                <div class="card-body">
                    <form action="{{ route('admin.reports.weekly') }}" method="GET" class="row align-items-center">
                        <div class="col-md-6">
                            <label class="form-label fw-bold text-muted">Date Range</label>
                            <div class="input-group">
                                <input type="date" name="start_date" class="form-control" value="{{ $start }}">
                                <span class="input-group-text bg-light border-start-0 border-end-0">to</span>
                                <input type="date" name="end_date" class="form-control" value="{{ $end }}">
                                <button type="submit" class="btn btn-primary"><i class="ri-search-line me-1"></i> Filter</button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-12">
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-transparent border-bottom">
                    <h5 class="card-title mb-0"><i class="ri-line-chart-line me-1 align-middle text-success"></i> Driver Consistency Log</h5>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0">
                            <thead class="table-light text-muted">
                                <tr>
                                    <th class="ps-4">Driver</th>
                                    <th class="text-center">Days Worked</th>
                                    <th class="text-center">Total Delays</th>
                                    <th class="text-center">Total Overtime</th>
                                    <th class="text-center">Average Punctuality</th>
                                    <th class="pe-4 text-end">Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($drivers as $driver)
                                    @php
                                        $atts = $driver->attendances;
                                        $daysWorked = $atts->count();
                                        $totalLate = $atts->where('is_late', true)->count();
                                        $avgPunc = $driver->punctuality_score;
                                        $scoreClass = $avgPunc >= 80 ? 'success' : ($avgPunc >= 50 ? 'warning' : 'danger');
                                    @endphp
                                    <tr>
                                        <td class="ps-4">
                                            <div class="d-flex align-items-center">
                                                <div class="avatar-xs flex-shrink-0 me-2">
                                                    <div class="avatar-title bg-soft-info text-info rounded-circle fs-10">
                                                        {{ substr($driver->name, 0, 1) }}
                                                    </div>
                                                </div>
                                                <div>
                                                    <h6 class="mb-0 fw-bold">{{ $driver->name }}</h6>
                                                    <small class="text-muted">{{ $driver->username }}</small>
                                                </div>
                                            </div>
                                        </td>
                                        <td class="text-center fw-medium">{{ $daysWorked }} days</td>
                                        <td class="text-center">
                                            <span class="text-danger fw-bold">{{ $totalLate }} times</span>
                                        </td>
                                        <td class="text-center text-success">
                                            {{ round($atts->sum('overtime_minutes') / 60, 1) }} hrs
                                        </td>
                                        <td class="text-center">
                                            <span class="badge badge-soft-{{ $scoreClass }} px-3 py-2 fs-12">{{ $avgPunc }}% Consistent</span>
                                        </td>
                                        <td class="pe-4 text-end">
                                            <a href="{{ route('admin.drivers.show', $driver->id) }}" class="btn btn-soft-primary btn-sm">Details</a>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="6" class="text-center py-5">
                                            <p class="text-muted mb-0">No attendance data found for this week.</p>
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
