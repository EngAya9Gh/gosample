@extends('layouts.master')
@section('title')
    @lang('translation.driver_tracking')
@endsection
@section('content')
    @component('components.breadcrumb')
        @slot('li_1')
            @lang('translation.appname')
        @endslot
        @slot('title')
            @lang('translation.driver_tracking')
        @endslot
    @endcomponent

    <div class="row mb-3 align-items-center">
        <div class="col-md-6">
            <div class="search-box" style="max-width: 300px;">
                <input type="text" id="driverSearchInput" class="form-control form-control-sm" placeholder="بحث فورى باسم السائق...">
                <i class="ri-search-line search-icon"></i>
            </div>
        </div>
        <div class="col-md-6 text-end">
            <a href="{{ route('admin.driver-tracking') }}" class="btn btn-primary btn-sm">
                <i class="ri-refresh-line align-middle me-1"></i> @lang('translation.refresh')
            </a>
        </div>
    </div>

    <div class="row">
        @forelse($driverData as $data)
            @php 
                $driver = $data['driver'];
                $tasks = $data['tasks'];
                $activeTaskCount = collect($tasks)->filter(function($task) {
                    return !in_array($task->status, ['COLLECTED', 'CLOSED']);
                })->count();
            @endphp
            <div class="col-xl-4 col-md-6 driver-card-container" data-driver-name="{{ strtolower($driver->name . ' ' . ($driver->english_name ?? '') . ' ' . ($driver->username ?? '')) }}">
                <div class="card card-height-100">
                    <div class="card-header align-items-center d-flex">
                        <h4 class="card-title mb-0 flex-grow-1">
                            <div class="d-flex align-items-center">
                                <div class="avatar-xs me-2">
                                    <span class="avatar-title bg-primary-subtle text-primary rounded-circle fs-12">
                                        {{ mb_substr($driver->name, 0, 1) }}
                                    </span>
                                </div>
                                <span class="fs-14">{{ $driver->name }}</span>
                            </div>
                        </h4>
                        <div class="flex-shrink-0">
                            <span class="badge {{ $activeTaskCount > 0 ? 'bg-warning-subtle text-warning' : 'bg-success-subtle text-success' }} fs-11">
                                {{ $activeTaskCount }} Active
                            </span>
                        </div>
                    </div><!-- end card header -->

                    <div class="card-body p-0">
                        <div data-simplebar style="max-height: 310px;">
                            <ul class="list-group list-group-flush border-dashed px-3">
                                @forelse($tasks as $index => $task)
                                    @php
                                        $isCompleted = in_array($task->status, ['COLLECTED', 'CLOSED']);
                                        $isNext = !$isCompleted && ($index == 0 || in_array($tasks[$index-1]->status, ['COLLECTED', 'CLOSED']));
                                        
                                        $statusClass = 'bg-light text-muted';
                                        if($isCompleted) $statusClass = 'bg-success-subtle text-success';
                                        elseif($isNext) $statusClass = 'bg-primary';
                                    @endphp
                                    <li class="list-group-item ps-0 py-3">
                                        <div class="d-flex align-items-start">
                                            <div class="flex-shrink-0 me-3">
                                                <div class="avatar-xs">
                                                    @if($isCompleted)
                                                        <div class="avatar-title bg-success-subtle text-success rounded-circle fs-13">
                                                            <i class="ri-checkbox-circle-fill"></i>
                                                        </div>
                                                    @elseif($isNext)
                                                        <div class="avatar-title bg-primary rounded-circle fs-13">
                                                            <i class="ri-map-pin-time-fill"></i>
                                                        </div>
                                                    @else
                                                        <div class="avatar-title bg-light text-muted rounded-circle fs-13">
                                                            {{ $index + 1 }}
                                                        </div>
                                                    @endif
                                                </div>
                                            </div>
                                            
                                            <div class="flex-grow-1">
                                                <h5 class="fs-13 mb-1 {{ $isCompleted ? 'text-muted text-decoration-line-through' : '' }}">
                                                    {{ $task->client->english_name ?? 'Unknown Client' }}
                                                    <span class="badge {{ $statusClass }} float-end fs-10">{{ $task->status }}</span>
                                                </h5>
                                                
                                                <p class="text-muted mb-1 fs-12">
                                                    <i class="ri-map-pin-user-line align-middle text-danger me-1"></i>
                                                    <span class="fw-medium">من:</span> {{ $task->from->name ?? 'غير محدد' }}
                                                </p>
                                                <p class="text-muted mb-1 fs-12">
                                                    <i class="ri-map-pin-fill align-middle text-success me-1"></i>
                                                    <span class="fw-medium">إلى:</span> {{ $task->to->name ?? 'غير محدد' }}
                                                </p>
                                                <div class="d-flex flex-wrap gap-2 mb-0 fs-11 mt-2 text-muted">
                                                    <span><i class="ri-hashtag align-middle me-1"></i>رقم: {{ $task->id }}</span>
                                                    @if($task->pickup_time)
                                                        <span><i class="ri-calendar-event-line align-middle me-1"></i>{{ \Carbon\Carbon::parse($task->pickup_time)->format('Y-m-d H:i') }}</span>
                                                    @endif
                                                </div>
                                                
                                                @if($task->estimated_arrival_time)
                                                    <p class="mb-0 fs-11 mt-1 {{ $isNext ? 'text-primary fw-medium' : 'text-muted' }}">
                                                        <i class="ri-time-line align-middle me-1"></i>
                                                        ETA: {{ \Carbon\Carbon::parse($task->estimated_arrival_time)->format('h:i A') }}
                                                    </p>
                                                @endif
                                            </div>
                                        </div>
                                    </li>
                                @empty
                                    <li class="list-group-item text-center py-4 text-muted">
                                        <i class="ri-checkbox-circle-line fs-24 mb-2 d-block"></i>
                                        No tasks assigned
                                    </li>
                                @endforelse
                            </ul>
                        </div>
                    </div><!-- end card body -->
                </div><!-- end card -->
            </div><!-- end col -->
        @empty
            <div class="col-12">
                <div class="card">
                    <div class="card-body text-center p-4">
                        <div class="avatar-lg mx-auto mb-3">
                            <div class="avatar-title bg-light text-primary rounded-circle fs-3">
                                <i class="ri-car-line"></i>
                            </div>
                        </div>
                        <h5 class="text-muted">No drivers with active tasks found.</h5>
                    </div>
                </div>
            </div>
        @endforelse
    </div>
@endsection

@section('script')
<script>
    document.getElementById('driverSearchInput').addEventListener('input', function(e) {
        let searchTerm = e.target.value.toLowerCase();
        let cards = document.querySelectorAll('.driver-card-container');
        
        cards.forEach(function(card) {
            let driverName = card.getAttribute('data-driver-name');
            if(driverName.includes(searchTerm)) {
                card.style.display = 'block';
            } else {
                card.style.display = 'none';
            }
        });
    });
</script>
@endsection
