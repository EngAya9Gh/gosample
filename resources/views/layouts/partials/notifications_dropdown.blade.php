<div class="dropdown-head bg-primary bg-pattern rounded-top">
    <div class="p-3">
        <div class="row align-items-center">
            <div class="col">
                <h6 class="m-0 fs-16 fw-semibold text-white"> Notifications </h6>
            </div>
            <div class="col-auto dropdown-tabs">
                <span class="badge badge-soft-light fs-13"> {{ $delayed_count }} New</span>
            </div>
        </div>
    </div>

    <div class="px-2 pt-2">
        <ul class="nav nav-tabs dropdown-tabs nav-tabs-custom" data-dropdown-tabs="true" id="notificationItemsTab" role="tablist">
            <li class="nav-item waves-effect waves-light">
                <a class="nav-link active" data-bs-toggle="tab" href="#all-noti-tab" role="tab" aria-selected="true">
                    Lost Samples ({{ count($lost_samples) }})
                </a>
            </li>
            <li class="nav-item waves-effect waves-light">
                <a class="nav-link" data-bs-toggle="tab" href="#messages-tab" role="tab" aria-selected="false">
                    Tasks ({{ $delayed_count - count($lost_samples) - $systemNotifications->count() }})
                </a>
            </li>
            <li class="nav-item waves-effect waves-light">
                <a class="nav-link" data-bs-toggle="tab" href="#alerts-tab" role="tab" aria-selected="false">
                    Alerts ({{ $systemNotifications->count() }})
                </a>
            </li>
        </ul>
    </div>
</div>

<div class="tab-content" id="notificationItemsTabContent">
    <div class="tab-pane fade show active py-2 ps-2" id="all-noti-tab" role="tabpanel">
        <div data-simplebar style="max-height: 300px;" class="pe-2">
            @foreach ($lost_samples as $sample)
                <div class="text-reset notification-item d-block dropdown-item position-relative">
                    <div class="d-flex">
                        <div class="flex-1">
                            <a href="{{ url('admin/samples/' . $sample->id) }}" class="stretched-link">
                                <h6 class="mt-0 mb-2 lh-base">The <b>Sample</b> is lost!, please check</h6>
                            </a>
                            <p class="mb-0 fs-11 fw-medium text-uppercase text-muted">
                                <span><i class="mdi mdi-clock-outline"></i> {{ $sample->barcode_id }}</span>
                            </p>
                        </div>
                    </div>
                </div>
            @endforeach
        </div>
    </div>

    <div class="tab-pane fade py-2 ps-2" id="messages-tab" role="tabpanel">
        <div data-simplebar style="max-height: 300px;" class="pe-2">
            @foreach ($pickup_delayedTasks as $record)
                <div class="text-reset notification-item d-block dropdown-item position-relative">
                    <div class="d-flex">
                        <div class="flex-1">
                            <a href="{{ url('admin/tasks/' . $record->id) }}" class="stretched-link">
                                <p class="mt-0 mb-2 lh-base">The <b>Task {{ $record->id }}</b> is delayed!, please check</p>
                            </a>
                            <p class="mb-0 fs-11 fw-medium text-uppercase text-muted">
                                <span><i class="mdi mdi-clock-outline"></i> {{ $record->created_at }}</span>
                            </p>
                        </div>
                    </div>
                </div>
            @endforeach
            @foreach ($drop_off_delayedTasks as $record)
                <div class="text-reset notification-item d-block dropdown-item position-relative">
                    <div class="d-flex">
                        <div class="flex-1">
                            <a href="{{ url('admin/tasks/' . $record->id) }}" class="stretched-link">
                                <p class="mt-0 mb-2 lh-base">The <b>Task {{ $record->id }}</b> is delayed!, please check</p>
                            </a>
                            <p class="mb-0 fs-11 fw-medium text-uppercase text-muted">
                                <span><i class="mdi mdi-clock-outline"></i> {{ $record->created_at }}</span>
                            </p>
                        </div>
                    </div>
                </div>
            @endforeach
            @foreach ($delayed_tasks_in_freezer as $record)
                <div class="text-reset notification-item d-block dropdown-item position-relative">
                    <div class="d-flex">
                        <div class="flex-1">
                            <a href="{{ url('admin/tasks/' . $record->id) }}" class="stretched-link">
                                <p class="mt-0 mb-2 lh-base">The <b>Task {{ $record->id }}</b> is delayed!, please check</p>
                            </a>
                            <p class="mb-0 fs-11 fw-medium text-uppercase text-muted">
                                <span><i class="mdi mdi-clock-outline"></i> {{ $record->created_at }}</span>
                            </p>
                        </div>
                    </div>
                </div>
            @endforeach
            @foreach ($delayed_tasks_delivered as $record)
                <div class="text-reset notification-item d-block dropdown-item position-relative">
                    <div class="d-flex">
                        <div class="flex-1">
                            <a href="{{ url('admin/tasks/' . $record->id) }}" class="stretched-link">
                                <p class="mt-0 mb-2 lh-base">The <b>Task {{ $record->id }}</b> is delayed!, please check</p>
                            </a>
                            <p class="mb-0 fs-11 fw-medium text-uppercase text-muted">
                                <span><i class="mdi mdi-clock-outline"></i> {{ $record->created_at }}</span>
                            </p>
                        </div>
                    </div>
                </div>
            @endforeach
        </div>
    </div>

    <div class="tab-pane fade py-2 ps-2" id="alerts-tab" role="tabpanel">
        <div data-simplebar style="max-height: 300px;" class="pe-2">
            @forelse($systemNotifications as $notification)
                <div class="text-reset notification-item d-block dropdown-item position-relative">
                    <div class="d-flex">
                        <div class="avatar-xs me-3">
                            <span class="avatar-title bg-soft-danger text-danger rounded-circle fs-16">
                                <i class="ri-alarm-warning-line"></i>
                            </span>
                        </div>
                        <div class="flex-1">
                            <a href="javascript:void(0);" class="stretched-link">
                                <h6 class="mt-0 mb-1 fs-13 fw-semibold">{{ $notification->data['title'] ?? 'System Alert' }}</h6>
                            </a>
                            <div class="fs-13 text-muted">
                                <p class="mb-1">{{ $notification->data['message'] ?? '' }}</p>
                            </div>
                            <p class="mb-0 fs-11 fw-medium text-uppercase text-muted">
                                <span><i class="mdi mdi-clock-outline"></i> {{ $notification->created_at->diffForHumans() }}</span>
                            </p>
                        </div>
                    </div>
                </div>
            @empty
                <div class="text-center pb-5 mt-2">
                    <div class="w-25 pt-3 mx-auto">
                        <img src="{{ URL::asset('assets/images/svg/bell.svg') }}" class="img-fluid" alt="user-pic">
                    </div>
                    <h6 class="fs-16 fw-semibold lh-base mt-4">No new system alerts!</h6>
                </div>
            @endforelse
        </div>
    </div>
</div>
