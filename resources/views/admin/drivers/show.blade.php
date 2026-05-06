@extends('layouts.master')
@section('title')
    @lang('translation.drivers') - {{ $driver->name }}
@endsection

@section('content')
    @component('components.breadcrumb')
        @slot('li_1') @lang('translation.appname') @endslot
        @slot('li_2') @lang('translation.drivers') @endslot
        @slot('title') Driver Profile @endslot
    @endcomponent

    <div class="row">
        <!-- Left Sidebar: Profile & Quick Info -->
        <div class="col-xl-4">
            <div class="card overflow-hidden border-0 shadow-sm">
                <div class="card-body pt-4 text-center">
                    <div class="mx-auto mb-3 avatar-xl">
                        <div class="avatar-title rounded-circle bg-light text-primary display-4">
                            <i class="ri-user-line"></i>
                        </div>
                    </div>
                    <h5 class="mb-1 fw-bold">{{ $driver->name }}</h5>
                    <p class="text-muted mb-3">{{ $driver->username }}</p>
                    
                    <div class="d-flex justify-content-center gap-2 mb-4">
                        @if($driver->status == 1)
                            <span class="badge badge-soft-success px-3 py-2 fs-12"><i class="ri-checkbox-circle-line align-middle me-1"></i> Active</span>
                        @else
                            <span class="badge badge-soft-danger px-3 py-2 fs-12"><i class="ri-close-circle-line align-middle me-1"></i> Inactive</span>
                        @endif
                        <span class="badge badge-soft-primary px-3 py-2 fs-12"><i class="ri-global-line align-middle me-1"></i> {{ strtoupper($driver->language ?? 'EN') }}</span>
                    </div>

                    <div class="row border-top pt-4">
                        <div class="col-6 border-end">
                            <h6 class="text-muted fs-11 text-uppercase fw-bold mb-1">Mobile</h6>
                            <p class="fw-medium mb-0 text-dark">{{ $driver->mobile }}</p>
                        </div>
                        <div class="col-6">
                            <h6 class="text-muted fs-11 text-uppercase fw-bold mb-1">Zone</h6>
                            <p class="fw-medium mb-0 text-dark">{{ $driver->zone->name ?? 'N/A' }}</p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Attendance Summary Dynamic -->
            <div class="card border-0 shadow-sm mt-4">
                <div class="card-header bg-transparent border-bottom align-items-center d-flex">
                    <h4 class="card-title mb-0 flex-grow-1"><i class="ri-calendar-check-line me-1 align-middle text-primary"></i> Attendance KPI</h4>
                </div>
                <div class="card-body">
                    @php
                        $pScore = $driver->punctuality_score;
                        $pClass = $pScore >= 80 ? 'success' : ($pScore >= 50 ? 'warning' : 'danger');
                        
                        $sScore = $driver->shift_completion_score;
                        $sClass = $sScore >= 80 ? 'primary' : ($sScore >= 50 ? 'warning' : 'danger');
                    @endphp
                    <div class="mb-3">
                        <div class="d-flex align-items-center mb-2">
                            <div class="flex-grow-1">
                                <h6 class="mb-0 fs-13">Punctuality Score</h6>
                            </div>
                            <div class="flex-shrink-0 text-end">
                                <span class="badge badge-soft-{{ $pClass }}">{{ $pScore }}%</span>
                            </div>
                        </div>
                        <div class="progress progress-sm animated-progess">
                            <div class="progress-bar bg-{{ $pClass }}" role="progressbar" style="width: {{ $pScore }}%" aria-valuenow="{{ $pScore }}" aria-valuemin="0" aria-valuemax="100"></div>
                        </div>
                    </div>
                    <div>
                        <div class="d-flex align-items-center mb-2">
                            <div class="flex-grow-1">
                                <h6 class="mb-0 fs-13">Shift Completion</h6>
                            </div>
                            <div class="flex-shrink-0 text-end">
                                <span class="badge badge-soft-{{ $sClass }}">{{ $sScore }}%</span>
                            </div>
                        </div>
                        <div class="progress progress-sm animated-progess">
                            <div class="progress-bar bg-{{ $sClass }}" role="progressbar" style="width: {{ $sScore }}%" aria-valuenow="{{ $sScore }}" aria-valuemin="0" aria-valuemax="100"></div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Right Content: Shifts & Details -->
        <div class="col-xl-8">
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-transparent border-bottom">
                    <ul class="nav nav-tabs-custom card-header-tabs border-bottom-0" role="tablist">
                        <li class="nav-item">
                            <a class="nav-link active" data-bs-toggle="tab" href="#shift-details" role="tab">
                                <i class="ri-time-line me-1 align-middle"></i> Working Shifts
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" data-bs-toggle="tab" href="#personal-details" role="tab">
                                <i class="ri-information-line me-1 align-middle"></i> Personal Info
                            </a>
                        </li>
                    </ul>
                </div>
                <div class="card-body p-4">
                    <div class="tab-content">
                        <!-- Tab 1: Working Shifts -->
                        <div class="tab-pane active" id="shift-details" role="tabpanel">
                            <div class="d-flex align-items-center mb-4">
                                <div class="flex-grow-1">
                                    <h5 class="card-title mb-0">Operational Schedule</h5>
                                    <p class="text-muted mb-0">Overview of assigned working shifts and total hours.</p>
                                </div>
                                <div class="flex-shrink-0">
                                    <span class="badge bg-light text-primary fs-12 border border-primary-subtle px-3 py-2">
                                        <i class="ri-timer-2-line me-1"></i> Total: {{ $driver->total_working_hours ?? '8' }} hrs/day
                                    </span>
                                </div>
                            </div>

                            <!-- Shift Timeline -->
                            <div class="row">
                                <!-- Shift 1 -->
                                <div class="col-md-4">
                                    <div class="p-3 border rounded-3 bg-light bg-opacity-10 h-100 border-dashed">
                                        <div class="d-flex align-items-center mb-3">
                                            <div class="avatar-xs flex-shrink-0">
                                                <div class="avatar-title bg-primary-subtle text-primary rounded-circle fs-16">
                                                    <i class="ri-sun-line"></i>
                                                </div>
                                            </div>
                                            <div class="flex-grow-1 ms-2">
                                                <h6 class="mb-0 fw-bold">Shift 1</h6>
                                                <small class="text-muted">Primary Shift</small>
                                            </div>
                                        </div>
                                        <div class="text-center">
                                            <h4 class="mb-1 text-primary">{{ \Carbon\Carbon::parse($driver->working_hours_start)->format('H:i') }}</h4>
                                            <p class="text-muted mb-2">to</p>
                                            <h4 class="mb-0 text-primary">{{ \Carbon\Carbon::parse($driver->working_hours_end)->format('H:i') }}</h4>
                                        </div>
                                    </div>
                                </div>

                                <!-- Shift 2 -->
                                @if($driver->shift_count >= 2)
                                <div class="col-md-4">
                                    <div class="p-3 border rounded-3 bg-light bg-opacity-10 h-100 border-dashed">
                                        <div class="d-flex align-items-center mb-3">
                                            <div class="avatar-xs flex-shrink-0">
                                                <div class="avatar-title bg-warning-subtle text-warning rounded-circle fs-16">
                                                    <i class="ri-moon-clear-line"></i>
                                                </div>
                                            </div>
                                            <div class="flex-grow-1 ms-2">
                                                <h6 class="mb-0 fw-bold">Shift 2</h6>
                                                <small class="text-muted">Secondary Shift</small>
                                            </div>
                                        </div>
                                        <div class="text-center">
                                            <h4 class="mb-1 text-warning">{{ \Carbon\Carbon::parse($driver->second_shift_working_hours_start)->format('H:i') }}</h4>
                                            <p class="text-muted mb-2">to</p>
                                            <h4 class="mb-0 text-warning">{{ \Carbon\Carbon::parse($driver->second_shift_working_hours_end)->format('H:i') }}</h4>
                                        </div>
                                    </div>
                                </div>
                                @endif

                                <!-- Shift 3 -->
                                @if($driver->shift_count >= 3)
                                <div class="col-md-4">
                                    <div class="p-3 border rounded-3 bg-light bg-opacity-10 h-100 border-dashed">
                                        <div class="d-flex align-items-center mb-3">
                                            <div class="avatar-xs flex-shrink-0">
                                                <div class="avatar-title bg-info-subtle text-info rounded-circle fs-16">
                                                    <i class="ri-flashlight-line"></i>
                                                </div>
                                            </div>
                                            <div class="flex-grow-1 ms-2">
                                                <h6 class="mb-0 fw-bold">Shift 3</h6>
                                                <small class="text-muted">Night Shift</small>
                                            </div>
                                        </div>
                                        <div class="text-center">
                                            <h4 class="mb-1 text-info">{{ \Carbon\Carbon::parse($driver->third_shift_working_hours_start)->format('H:i') }}</h4>
                                            <p class="text-muted mb-2">to</p>
                                            <h4 class="mb-0 text-info">{{ \Carbon\Carbon::parse($driver->third_shift_working_hours_end)->format('H:i') }}</h4>
                                        </div>
                                    </div>
                                </div>
                                @endif
                            </div>
                        </div>

                        <!-- Tab 2: Personal Details -->
                        <div class="tab-pane" id="personal-details" role="tabpanel">
                            <div class="table-responsive">
                                <table class="table table-borderless align-middle mb-0">
                                    <tbody>
                                        <tr>
                                            <th class="ps-0" scope="row">National ID</th>
                                            <td class="text-muted">{{ $driver->national_id ?? 'N/A' }}</td>
                                        </tr>
                                        <tr>
                                            <th class="ps-0" scope="row">Email Address</th>
                                            <td class="text-muted">{{ $driver->email }}</td>
                                        </tr>
                                        <tr>
                                            <th class="ps-0" scope="row">Current Location</th>
                                            <td class="text-muted">
                                                <i class="ri-map-pin-line text-danger me-1"></i>
                                                @if($driver->lat && $driver->lng)
                                                    <a href="https://www.google.com/maps?q={{ $driver->lat }},{{ $driver->lng }}" target="_blank" class="text-primary text-decoration-underline">View on Google Maps</a>
                                                @else
                                                    No coordinates set
                                                @endif
                                            </td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="card-footer bg-transparent border-top d-flex justify-content-between">
                    <a href="{{ route('admin.drivers.index') }}" class="btn btn-ghost-primary"><i class="ri-arrow-left-line me-1"></i> Back to List</a>
                    <a href="{{ route('admin.drivers.edit', $driver->id) }}" class="btn btn-warning"><i class="ri-edit-2-line me-1"></i> Edit Driver</a>
                </div>
            </div>

            <!-- Existing Relationships (Tasks & Car History) -->
            <div class="card border-0 shadow-sm mt-4">
                <div class="card-header bg-transparent border-bottom p-0">
                    <ul class="nav nav-pills nav-justified" role="tablist">
                        <li class="nav-item">
                            <a class="nav-link active" data-bs-toggle="tab" href="#car-history" role="tab">
                                {{ trans('translation.carLinkHistory') }}
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" data-bs-toggle="tab" href="#tasks-history" role="tab">
                                {{ trans('translation.tasks') }}
                            </a>
                        </li>
                    </ul>
                </div>
                <div class="tab-content">
                    <div class="tab-pane active" id="car-history" role="tabpanel">
                        @includeIf('admin.drivers.relationships.driverCarLinkHistories', ['carLinkHistories' => $driver->driverCarLinkHistories])
                    </div>
                    <div class="tab-pane" id="tasks-history" role="tabpanel">
                        @includeIf('admin.drivers.relationships.driverTasks', ['tasks' => $driver->driverTasks])
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection