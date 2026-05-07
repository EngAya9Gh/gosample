@extends('layouts.master')
@section('title') Strategic KPI Dashboard @endsection
@section('content')
    @component('components.breadcrumb')
        @slot('li_1') @lang('translation.reports') @endslot
        @slot('title') 5.3 KPI-Based Performance Dashboard @endslot
    @endcomponent

    <div class="row">
        @foreach($drivers as $driver)
            <div class="col-xl-4 col-md-6">
                <div class="card border-0 shadow-sm overflow-hidden">
                    <div class="card-body p-0">
                        <div class="p-4 bg-primary text-white">
                            <div class="d-flex align-items-center">
                                <div class="avatar-sm flex-shrink-0 me-3">
                                    <div class="avatar-title bg-white-50 text-white rounded-circle fs-20">
                                        {{ substr($driver->name, 0, 1) }}
                                    </div>
                                </div>
                                <div>
                                    <h5 class="mb-1 text-white fw-bold">{{ $driver->name }}</h5>
                                    <span class="badge bg-white-50 text-white">Active Carrier</span>
                                </div>
                            </div>
                        </div>
                        <div class="p-4">
                            <!-- KPI 1: Punctuality -->
                            <div class="mb-4">
                                <div class="d-flex align-items-center mb-2">
                                    <div class="flex-grow-1">
                                        <h6 class="mb-0 fw-bold">Punctuality (Time Commitment)</h6>
                                    </div>
                                    <div class="flex-shrink-0">
                                        <span class="text-primary fw-bold">{{ $driver->kpi_punctuality }}%</span>
                                    </div>
                                </div>
                                <div class="progress progress-sm animated-progress">
                                    <div class="progress-bar bg-primary" role="progressbar" style="width: {{ $driver->kpi_punctuality }}%"></div>
                                </div>
                            </div>

                            <!-- KPI 2: Operation Speed -->
                            <div class="mb-4">
                                <div class="d-flex align-items-center mb-2">
                                    <div class="flex-grow-1">
                                        <h6 class="mb-0 fw-bold">Operation Speed (Avg Time)</h6>
                                    </div>
                                    <div class="flex-shrink-0 text-success fw-bold">
                                        <i class="ri-timer-line me-1"></i> {{ $driver->kpi_avg_speed }} min
                                    </div>
                                </div>
                                <p class="text-muted small mb-0">Average time between pickup and dropoff.</p>
                            </div>

                            <!-- KPI 3: Violations -->
                            <div class="d-flex align-items-center bg-light p-3 rounded-3 border">
                                <div class="flex-grow-1">
                                    <h6 class="mb-0 fw-bold text-danger">Operational Violations</h6>
                                    <small class="text-muted">Delayed steps & non-compliance</small>
                                </div>
                                <div class="flex-shrink-0 text-end">
                                    <span class="badge bg-danger rounded-pill fs-12">{{ $driver->kpi_violations }} Issues</span>
                                </div>
                            </div>
                        </div>
                        <div class="card-footer bg-transparent border-top p-3 text-center">
                            <a href="{{ route('admin.drivers.show', $driver->id) }}" class="btn btn-ghost-primary btn-sm w-100">Full Performance Analytics <i class="ri-arrow-right-line ms-1"></i></a>
                        </div>
                    </div>
                </div>
            </div>
        @endforeach
    </div>
@endsection
