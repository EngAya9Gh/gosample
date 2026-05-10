<header id="page-topbar">
    <div class="layout-width">
        <div class="navbar-header">




            <div class="d-flex">
                <!-- LOGO -->
                <div class="navbar-brand-box horizontal-logo">
                    <a href="index" class="logo logo-dark">
                        <span class="logo-sm">
                            <img src="{{ URL::asset('assets/images/logo-sm.png') }}" alt="" height="22">
                        </span>
                        <span class="logo-lg">
                            <img src="{{ URL::asset('assets/images/logo-dark.png') }}" alt="" height="17">
                        </span>
                    </a>

                    <a href="index" class="logo logo-light">
                        <span class="logo-sm">
                            <img src="{{ URL::asset('assets/images/logo-sm.png') }}" alt="" height="22">
                        </span>
                        <span class="logo-lg">
                            <img src="{{ URL::asset('assets/images/logo-light.png') }}" alt="" height="17">
                        </span>
                    </a>
                </div>

                <button type="button" class="btn btn-sm px-3 fs-16 header-item vertical-menu-btn topnav-hamburger"
                    id="topnav-hamburger-icon">
                    <span class="hamburger-icon">
                        <span></span>
                        <span></span>
                        <span></span>
                    </span>
                </button>




                <div class="dropdown topbar-head-dropdown ms-1 header-item">

                    <button type="button" class="btn btn-icon btn-topbar btn-ghost-secondary"
                        id="page-header-action-dropdown" data-bs-toggle="dropdown" aria-haspopup="true"
                        aria-expanded="false">
                        <div class="d-flex align-items-center">
                            <h6 class="dropdown-header me-2">Actions</h6>
                            <i class="bx bx-plus fs-22"></i>
                        </div>
                    </button>

                    <div class="dropdown-menu dropdown-menu-right" aria-labelledby="page-header-action-dropdown">

                        <a class="dropdown-item" href="{{ route('admin.scheduled-tasks.quick') }}">
                            <i class="bx bx-plus-circle me-2"></i>Add Quick Schedule Task
                        </a>
                        <a class="dropdown-item" href="{{ route('admin.tasks.create') }}">
                            <i class="bx bx-plus-circle me-2"></i>Add Task
                        </a>
			<a class="dropdown-item" href="{{ route('admin.shipments.create') }}">
                            <i class="bx bx-plus-circle me-2"></i>Add Shipment
                        </a>
                        <a class="dropdown-item" href="{{ route('admin.tasks.index') }}">
                            <i class="bx bx-list-check me-2"></i>List Tasks (New Tasks)
                            <span id="new-tasks-badge" class="badge bg-danger d-none">0</span>
                        </a>

                        <a class="dropdown-item" href="{{ route('admin.swaprequests.index') }}">
                            <i class="bx bx-transfer me-2"></i>Swap Request
                            <span id="new-swaps-badge" class="badge bg-danger d-none">0</span>
                        </a>
                        <a class="dropdown-item" href="{{ route('admin.swaprequests.create') }}">
                            <i class="bx bx-transfer me-2"></i>New Swap Request
                        </a>
                    </div>
                </div>


            </div>





            <div class="d-flex align-items-center">

                <div class="dropdown d-md-none topbar-head-dropdown header-item">
                    <button type="button" class="btn btn-icon btn-topbar btn-ghost-secondary rounded-circle"
                        id="page-header-search-dropdown" data-bs-toggle="dropdown" aria-haspopup="true"
                        aria-expanded="false">
                        <i class="bx bx-search fs-22"></i>
                    </button>
                    <div class="dropdown-menu dropdown-menu-lg dropdown-menu-end p-0"
                        aria-labelledby="page-header-search-dropdown">
                        <form class="p-3">
                            <div class="form-group m-0">
                                <div class="input-group">
                                    <input type="text" class="form-control" placeholder="Search ..."
                                        aria-label="Recipient's username">
                                    <button class="btn btn-primary" type="submit"><i
                                            class="mdi mdi-magnify"></i></button>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>




                <div class="ms-1 header-item d-none d-sm-flex">
                    <a href="{{ route('admin.samples.lost') }}"
                        class="btn btn-icon btn-topbar btn-ghost-secondary rounded-circle light-dark-mode">
                        <i class="ri-flask-line fs-22"></i>
                        <span id="lost-samples-badge"
                            class="position-absolute topbar-badge fs-10 translate-middle badge rounded-pill bg-danger d-none">
                            0</span>
                        </button>
                    </a>
                </div>


                <div class="dropdown topbar-head-dropdown ms-1 header-item">
                    <button type="button" class="btn btn-icon btn-topbar btn-ghost-secondary rounded-circle"
                        id="page-header-notifications-dropdown" data-bs-toggle="dropdown" aria-haspopup="true"
                        aria-expanded="false">
                        <i class='bx bx-bell fs-22'></i>
                        <span id="topbar-notification-badge"
                            class="position-absolute topbar-badge fs-10 translate-middle badge rounded-pill bg-danger d-none">
                            0<span class="visually-hidden">unread messages</span></span>
                    </button>
                    <div class="dropdown-menu dropdown-menu-lg dropdown-menu-end p-0"
                        id="topbar-notification-dropdown-container"
                        aria-labelledby="page-header-notifications-dropdown">
                        <div class="text-center py-4">
                            <div class="spinner-border text-primary" role="status">
                                <span class="visually-hidden">Loading...</span>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="dropdown ms-sm-3 header-item topbar-user">
                    <button type="button" class="btn" id="page-header-user-dropdown" data-bs-toggle="dropdown"
                        aria-haspopup="true" aria-expanded="false">
                        <span class="d-flex align-items-center">
                            <img class="rounded-circle header-profile-user"
                                src="@if (Auth::user()->avatar != '') {{ URL::asset('images/' . Auth::user()->avatar) }}@else{{ URL::asset('assets/images/users/avatar-1.jpg') }} @endif"
                                alt="Header Avatar">
                            <span class="text-start ms-xl-2">
                                <span
                                    class="d-none d-xl-inline-block ms-1 fw-medium user-name-text">{{ Auth::user()->name }}</span>
                                <span
                                    class="d-none d-xl-block ms-1 fs-12 text-muted user-name-sub-text">{{ Auth::user()->roles[0]->name }}</span>
                            </span>
                        </span>
                    </button>
                    <div class="dropdown-menu dropdown-menu-end">
                        <!-- item-->
                        <h6 class="dropdown-header">Welcome {{ Auth::user()->name }}!</h6>
                        <a class="dropdown-item" href="pages-profile"><i
                                class="mdi mdi-account-circle text-muted fs-16 align-middle me-1"></i> <span
                                class="align-middle">Profile</span></a>

                        <a class="dropdown-item" href="pages-faqs"><i
                                class="mdi mdi-lifebuoy text-muted fs-16 align-middle me-1"></i> <span
                                class="align-middle">Help</span></a>
                        <div class="dropdown-divider"></div>
                        <!-- <a class="dropdown-item" href="pages-profile"><i
                                class="mdi mdi-wallet text-muted fs-16 align-middle me-1"></i> <span
                                class="align-middle">Balance : <b>$5971.67</b></span></a> -->
                        <!-- <a class="dropdown-item" href="pages-profile-settings"><span
                                class="badge bg-soft-success text-success mt-1 float-end">New</span><i
                                class="mdi mdi-cog-outline text-muted fs-16 align-middle me-1"></i> <span
                                class="align-middle">Settings</span></a> -->
                        <!-- <a class="dropdown-item" href="auth-lockscreen-basic"><i
                                class="mdi mdi-lock text-muted fs-16 align-middle me-1"></i> <span class="align-middle">Lock screen</span></a> -->
                        <a class="dropdown-item " href="javascript:void();"
                            onclick="event.preventDefault(); document.getElementById('logout-form').submit();"><i
                                class="bx bx-power-off font-size-16 align-middle me-1"></i> <span
                                key="t-logout">@lang('translation.logout')</span></a>
                        <form id="logout-form" action="{{ route('logout') }}" method="POST"
                            style="display: none;">
                            @csrf
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
</header>

@section('script')
    <script src="{{ URL::asset('/assets/js/app.min.js') }}"></script>
    <script>
        $(document).ready(function() {
            function loadNotifications() {
                // 1. Load HTML Content for Dropdown
                $.ajax({
                    url: "{{ route('admin.header.notifications') }}",
                    type: 'GET',
                    data: { html: 1 },
                    success: function(response) {
                        $('#topbar-notification-dropdown-container').html(response);
                    }
                });

                // 2. Load JSON Data for Badges
                $.getJSON("{{ route('admin.header.notifications') }}", function(data) {
                    if (data.delayed_count > 0) {
                        $('#topbar-notification-badge').text(data.delayed_count).removeClass('d-none');
                    } else {
                        $('#topbar-notification-badge').addClass('d-none');
                    }
                    
                    if (data.lost_samples && data.lost_samples.length > 0) {
                        $('#lost-samples-badge').text(data.lost_samples.length).removeClass('d-none');
                    } else {
                        $('#lost-samples-badge').addClass('d-none');
                    }
                    
                    if (data.newTasksCount > 0) {
                        $('#new-tasks-badge').text(data.newTasksCount).removeClass('d-none');
                    }
                    if (data.newSwapTasksCount > 0) {
                        $('#new-swaps-badge').text(data.newSwapTasksCount).removeClass('d-none');
                    }
                });
            }

            loadNotifications();
            // Refresh every 5 minutes
            setInterval(loadNotifications, 300000);
        });
    </script>
@endsection
