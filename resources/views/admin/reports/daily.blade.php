@extends('layouts.master')
@section('title') Daily Report - {{ $date }} @endsection
@section('content')
    @component('components.breadcrumb')
        @slot('li_1') @lang('translation.reports') @endslot
        @slot('title') Daily Operations Report @endslot
    @endcomponent

    <div class="row mb-4">
        <div class="col-12">
            <div class="card border-0 shadow-sm">
                <div class="card-body">
                    <form action="{{ route('admin.reports.daily') }}" method="GET" class="row align-items-center">
                        <div class="col-md-4">
                            <label class="form-label fw-bold text-muted">Select Report Date</label>
                            <div class="input-group">
                                <span class="input-group-text bg-primary text-white"><i class="ri-calendar-line"></i></span>
                                <input type="date" name="date" class="form-control" value="{{ $date }}" onchange="this.form.submit()">
                            </div>
                        </div>
                        <div class="col-md-8 text-md-end mt-3 mt-md-0">
                            <div class="d-flex justify-content-md-end gap-2">
                                <div class="bg-light p-2 px-3 rounded-3 border">
                                    <small class="text-muted d-block">Active Drivers</small>
                                    <span class="fw-bold text-primary fs-16">{{ $drivers->count() }}</span>
                                </div>
                                <div class="bg-light p-2 px-3 rounded-3 border">
                                    <small class="text-muted d-block">Total Delays</small>
                                    <span class="fw-bold text-danger fs-16">{{ $drivers->sum('delayed_tasks_count') }}</span>
                                </div>
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
                    <h5 class="card-title mb-0"><i class="ri-history-line me-1 align-middle text-primary"></i> Operational Logs ({{ $date }})</h5>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0">
                            <thead class="table-light text-muted">
                                <tr>
                                    <th class="ps-4">Driver</th>
                                    <th class="text-center">Check-in</th>
                                    <th class="text-center">Check-out</th>
                                    <th class="text-center">Delay Start</th>
                                    <th class="text-center">Operational Delays</th>
                                    <th class="text-center">Status</th>
                                    <th class="pe-4 text-end">Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($drivers as $driver)
                                    @php
                                        $att = $driver->day_attendance;
                                        $isLate = $att->is_late ?? false;
                                        $delayMin = $att->delay_minutes ?? 0;
                                        $opDelays = $driver->delayed_tasks_count;
                                    @endphp
                                    <tr>
                                        <td class="ps-4">
                                            <div class="d-flex align-items-center">
                                                <div class="avatar-xs flex-shrink-0 me-2">
                                                    <div class="avatar-title bg-soft-primary text-primary rounded-circle fs-10">
                                                        {{ substr($driver->name, 0, 1) }}
                                                    </div>
                                                </div>
                                                <div>
                                                    <h6 class="mb-0 fw-bold">{{ $driver->name }}</h6>
                                                    <small class="text-muted">{{ $driver->mobile }}</small>
                                                </div>
                                            </div>
                                        </td>
                                        <td class="text-center">
                                            @if($att && $att->checkin_time)
                                                <span class="fw-medium">{{ \Carbon\Carbon::parse($att->checkin_time)->format('H:i') }}</span>
                                            @else
                                                <span class="text-muted">--:--</span>
                                            @endif
                                        </td>
                                        <td class="text-center">
                                            @if($att && $att->checkout_time)
                                                <span class="fw-medium">{{ \Carbon\Carbon::parse($att->checkout_time)->format('H:i') }}</span>
                                            @else
                                                <span class="text-muted">--:--</span>
                                            @endif
                                        </td>
                                        <td class="text-center">
                                            @if($isLate)
                                                <span class="badge badge-soft-danger animate__animated animate__pulse animate__infinite">{{ $delayMin }} min late</span>
                                            @elseif($att && $att->checkin_time)
                                                <span class="badge badge-soft-success">On Time</span>
                                            @else
                                                <span class="text-muted small">Not Started</span>
                                            @endif
                                        </td>
                                        <td class="text-center">
                                            @if($opDelays > 0)
                                                <span class="badge bg-soft-warning text-warning border border-warning-subtle rounded-pill px-3">
                                                    <i class="ri-error-warning-line me-1"></i> {{ $opDelays }} steps delayed
                                                </span>
                                            @else
                                                <span class="badge bg-soft-light text-muted rounded-pill px-3">No Delays</span>
                                            @endif
                                        </td>
                                        <td class="text-center">
                                            @if($att && $att->checkout_time)
                                                <span class="badge bg-success">Finished</span>
                                            @elseif($att && $att->checkin_time)
                                                <span class="badge bg-primary">In Service</span>
                                            @else
                                                <span class="badge bg-light text-muted">Offline</span>
                                            @endif
                                        </td>
                                        <td class="pe-4 text-end">
                                            <a href="{{ route('admin.drivers.show', $driver->id) }}" class="btn btn-icon btn-soft-primary btn-sm rounded-circle">
                                                <i class="ri-eye-line"></i>
                                            </a>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="7" class="text-center py-5">
                                            <p class="text-muted mb-0">No operational data found for this date.</p>
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
