@extends('layouts.master')
@section('title')
    @lang('translation.dashboards')
@endsection

@section('css')
    <link href="{{ URL::asset('assets/libs/jsvectormap/jsvectormap.min.css') }}" rel="stylesheet" type="text/css" />
    <link href="{{ URL::asset('assets/libs/swiper/swiper.min.css') }}" rel="stylesheet" type="text/css" />
    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
    <style type="text/css">
        #mymap { border: 1px solid red; width: 100%; height: 800px; }
        #map { height: 800px; width: 100%; }
    </style>
    <style>
        /* Loader overlay */
        #ajax-loader-overlay {
        position: absolute;
        top: 0;
        left: 0;
        right: 0;
        bottom: 0;
        background: rgba(255,255,255,0.75);
        display: flex;
        align-items: center;
        justify-content: center;
        z-index: 9999;
        visibility: hidden; /* hidden by default */
        opacity: 0;
        transition: opacity 0.2s ease, visibility 0.2s;
        }

        /* show */
        #ajax-loader-overlay.show {
        visibility: visible;
        opacity: 1;
        }
    </style>
@endsection

@section('content')
    @component('components.breadcrumb')
        @slot('li_1') Dashboards @endslot
        @slot('title') Dashboard @endslot
    @endcomponent

    <div class="row">
        <div class="col-lg-12">
            <div class="card">
                <div class="card-header"><h4 class="card-title mb-0">Filters</h4></div>
                <div class="card-body">
                    <!-- form الآن id=filter-form و سيتعمل أجاكس -->
                    <form id="filter-form" method="POST" action="javascript:void(0);" enctype="multipart/form-data">
                        @csrf
                        <div class="row">
                            <div class="col-lg-6">
                                <label for="driver_id">{{ trans('translation.car.fields.driver') }}</label>
                                <select class="form-control select2" name="driver_id" id="driver_id">
                                    <option value="">{{ __('Select Driver') }}</option>
                                    @foreach ($drivers as $driver)
                                        <option value="{{ $driver->id }}">{{ $driver->name }}</option>
                                    @endforeach
                                </select>
                            </div>

                            <div class="col-lg-6">
                                <label class="required" for="imei">{{ trans('translation.car.fields.imei') }}</label>
                                <input class="form-control" type="text" name="imei" id="imei" value="{{ old('imei', '') }}">
                            </div>
                        </div>
                        
                        <div class="row mt-3">
                            <div class="col-lg-6">
                                <label class="required" for="plate_number">{{ trans('translation.car.fields.plate_number') }}</label>
                                <!-- <input class="form-control" type="text" name="plate_number" id="plate_number" value="{{ old('plate_number', '') }}"> -->
                                <select class="form-control select2" name="plate_number" id="plate_number">
                                    <option value="">{{ __('Select Plate Number') }}</option>
                                    @foreach ($plateNumbers as $plt)
                                        <option value="{{ $plt }}">{{ $plt }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-lg-6">
                                <p></p>
                                <button class="btn btn-danger" type="submit">{{ trans('translation.search') }}</button>
                                <button class="btn btn-secondary" type="button" id="btn-reset">Reset</button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- Map card -->
    <div class="row">
        <div class="col-lg-12">
            <div class="card">
                <div class="card-header"><h4 class="card-title mb-0">Drivers</h4></div>
                <div class="card-body">
                    <!-- <div id="map"></div> -->
                    <div id="map" style="height:800px; width:100%"></div>

                    <!-- AJAX Loader Overlay -->
                    <div id="ajax-loader-overlay" aria-hidden="true">
                        <div class="text-center">
                            <!-- Bootstrap spinner (لو عندك bootstrap) -->
                            <div class="spinner-border" role="status" style="width:3rem; height:3rem;">
                            <span class="visually-hidden">Loading...</span>
                            </div>
                            <div style="margin-top:10px; font-weight:600;">Loading ...</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Hidden trigger button and Modal (same modal content) -->
    <button type="button" hidden class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#exampleModalgrid" id="driver_pin"></button>

    <div class="modal fade" id="exampleModalgrid" tabindex="-1" aria-labelledby="exampleModalgridLabel" aria-modal="true">
        <div class="modal-dialog  modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="exampleModalgridLabel">Task Details</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="col-xxl-12">
                        <div class="card">
                            <div class="border">
                                <ul class="nav nav-pills custom-hover-nav-tabs">
                                    <li class="nav-item">
                                        <a href="#custom-hover-customere" data-bs-toggle="tab" class="nav-link active">
                                            <i class="ri-user-fill nav-icon nav-tab-position"></i>
                                            <h5 class="nav-titl nav-tab-position m-0">Driver</h5>
                                        </a>
                                    </li>
                                    <li class="nav-item">
                                        <a href="#custom-hover-description" data-bs-toggle="tab" class="nav-link">
                                            <i class="ri-file-text-line nav-icon nav-tab-position"></i>
                                            <h5 class="nav-titl nav-tab-position m-0">Tasks</h5>
                                        </a>
                                    </li>
                                    <li class="nav-item">
                                        <a href="#car-details" data-bs-toggle="tab" class="nav-link">
                                            <i class="ri-car-line nav-icon nav-tab-position"></i>
                                            <h5 class="nav-titl nav-tab-position m-0">Car Details</h5>
                                        </a>
                                    </li>
                                    <li class="nav-item">
                                        <a href="#car_tracking_table" data-bs-toggle="tab" class="nav-link">
                                            <i class="ri-map-pin-fill nav-icon nav-tab-position"></i>
                                            <h5 class="nav-titl nav-tab-position m-0">Car Tracking</h5>
                                        </a>
                                    </li>
                                </ul>
                            </div>

                            <div class="card-body">
                                <div class="tab-content text-muted">
                                    <div class="tab-pane show active" id="custom-hover-customere">
                                        <div class="table-responsive">
                                            <table class="table table-borderless mb-0">
                                                <tbody>
                                                    <tr><th class="ps-0" scope="row">Full Name :</th><td class="text-muted" id="driver_name"></td></tr>
                                                    <tr><th class="ps-0" scope="row">Mobile :</th><td class="text-muted" id="driver_mobile"></td></tr>
                                                    <tr><th class="ps-0" scope="row">E-mail :</th><td class="text-muted" id="driver_email"></td></tr>
                                                    <tr><th class="ps-0" scope="row">Car Plate Number:</th><td class="text-muted" id="plate_number_result"></td></tr>
                                                    <tr><th class="ps-0" scope="row">IMEI</th><td class="text-muted" id="imei_result"></td></tr>
                                                </tbody>
                                            </table>
                                        </div>
                                    </div>

                                    <div class="tab-pane" id="custom-hover-description">
                                        <div class="table-responsive">
                                            <table class="table mb-0" id="tasks_table">
                                                <thead>
                                                    <tr><th>Id</th><th>From</th><th>To</th><th>Status</th><th>Samples</th></tr>
                                                </thead>
                                                <tbody></tbody>
                                            </table>
                                        </div>
                                    </div>

                                    <div class="tab-pane" id="car-details">
                                        <h6>Car Details</h6>
                                        <div class="table-responsive">
                                            <table class="table mb-0" id="car_table">
                                                <thead><tr><th>Id</th><th>Plate Number</th><th>Model</th></tr></thead>
                                                <tbody></tbody>
                                            </table>
                                        </div>
                                    </div>

                                    <div class="tab-pane" id="car_tracking_table">
                                        <h6>Car Tracking</h6>
                                        <div class="table-responsive">
                                            <table class="table mb-0" id="car_tracking_table">
                                                <thead><tr><th>Id</th><th>Address</th><th>Temp5</th><th>Temp6</th><th>Temp7</th><th>Temp8</th></tr></thead>
                                                <tbody></tbody>
                                            </table>
                                        </div>
                                    </div>

                                </div>
                            </div><!-- end card-body -->
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

@endsection

@section('script')
    <!-- jQuery -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.6.4/jquery.min.js"></script>

    <!-- Google Maps API (ضع مفتاحك هنا إذا أردت تغييره) -->
    <script src="https://maps.googleapis.com/maps/api/js?key=AIzaSyDf1ht01vFyWcfWS33mmdfd30qm5-uyWhM&callback=initMap" defer></script>

    <!-- select2 -->
    <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>

    <script>
        // خريطة عالمية و markers array
        let map;
        let markers = [];
        const defaultCenter = { lat: 24.7597608, lng: 46.7141881 };
        function showLoader() {
        $('#ajax-loader-overlay').addClass('show');
        }
        function hideLoader() {
        $('#ajax-loader-overlay').removeClass('show');
        }

        function initMap() {
            const myStyles = [{ featureType: "poi", elementType: "labels", stylers: [{ visibility: "off" }] }];
            map = new google.maps.Map(document.getElementById("map"), {
                zoom: 12,
                center: defaultCenter,
                styles: myStyles
            });

            // تحميل البيانات للمرّة الأولى بدون فلتر
            loadLocations();
        }

        // حذف الماركرز القديمة
        function clearMarkers() {
            markers.forEach(m => m.setMap(null));
            markers = [];
        }

        // تابع بناء المودال من بيانات السائق
        function populateModal(value) {
            $('#driver_pin').click(); // يفتح المودال
            $('#driver_name').text(value.name || '');
            $('#driver_mobile').text(value.mobile || '');
            $('#driver_email').text(value.email || '');
            $('#plate_number_result').text((value.car && value.car.plate_number) ? value.car.plate_number : (value.plate_number || ''));
            $('#imei_result').text((value.car && value.car.imei) ? value.car.imei : (value.imei || ''));

            // tasks table
            $('#tasks_table tbody').empty();
            if (value.driver_active_tasks && value.driver_active_tasks.length) {
                value.driver_active_tasks.forEach(task => {
                    const fromName = task.from ? task.from.name : '-';
                    const toName = task.to ? task.to.name : '-';
                    const samplesCount = task.samples ? task.samples.length : 0;
                    $('#tasks_table tbody').append(
                        `<tr>
                            <td>${task.id}</td>
                            <td>${fromName}</td>
                            <td>${toName}</td>
                            <td>${task.status || ''}</td>
                            <td>${samplesCount}</td>
                        </tr>`
                    );
                });
            }

            // car table
            $('#car_table tbody').empty();
            if (value.car) {
                $('#car_table tbody').append(
                    `<tr>
                        <td>${value.car.id || ''}</td>
                        <td>${value.car.plate_number || ''}</td>
                        <td>${value.car.model || ''}</td>
                    </tr>`
                );
            }

            // car tracking
            $('#car_tracking_table tbody').empty();
            if (value.car && value.car.car_tracking && value.car.car_tracking.length) {
                value.car.car_tracking.forEach(rec => {
                    const address = (rec.lat && rec.lng) ? (rec.lat + ', ' + rec.lng) : (rec.address || '');
                    $('#car_tracking_table tbody').append(
                        `<tr>
                            <td>${rec.id}</td>
                            <td>${address}</td>
                            <td>${rec.temp5 ?? ''}</td>
                            <td>${rec.temp6 ?? ''}</td>
                            <td>${rec.temp7 ?? ''}</td>
                            <td>${rec.temp8 ?? ''}</td>
                        </tr>`
                    );
                });
            }
        }

        // تحميل الأماكن من السيرفر عبر AJAX
        function loadLocations(filters = {}) {
            const data = {
                _token: "{{ csrf_token() }}",
                ...filters
            };

            $.ajax({
                url: "{{ route('map.filter') }}",
                type: "POST",
                data: data,
                dataType: "json",
                beforeSend() {
                    showLoader();
                },
                success(response) {
                    clearMarkers();

                    // response يمكن أن يكون array من السجلات
                    response.forEach(function(value) {
                        // الحصول على آخر إحداثيات إن كانت موجودة ضمن car.car_tracking أو الحقول lat/lng مباشرة
                        let lat = value.lat;
                        let lng = value.lng;

                        // compatibility: Laravel relations may be snake_case
                        if ((!lat || !lng) && value.car && value.car.car_tracking && value.car.car_tracking.length) {
                            // نبحث أحدث تتبع
                            const sorted = value.car.car_tracking.slice().sort((a,b) => new Date(b.created_at) - new Date(a.created_at));
                            if (sorted.length) {
                                lat = sorted[0].lat;
                                lng = sorted[0].lng;
                            }
                        }

                        if (!lat || !lng) return; // تجاهل بدون إحداثيات

                        const icon = (value.driver_active_delayed_tasks && value.driver_active_delayed_tasks.length > 0)
                            ? "{{ URL::asset('assets/images/pin-delayed.png') }}"
                            : ((value.driver_active_tasks && value.driver_active_tasks.length > 0)
                                ? "{{ URL::asset('assets/images/pin-active.png') }}"
                                : "{{ URL::asset('assets/images/pin-no-task.png') }}" );

                        const labelText = (value.driver_active_tasks && value.driver_active_tasks.length) ? String(value.driver_active_tasks.length) : '0';

                        const marker = new google.maps.Marker({
                            position: { lat: Number(lat), lng: Number(lng) },
                            map: map,
                            icon: icon,
                            label: {
                                text: labelText,
                                fontWeight: 'bold',
                                fontSize: '16px'
                            }
                        });

                        // event click -> فتح المودال وتعبئة البيانات
                        marker.addListener('click', function() {
                            // بعض الحقول قد ترجع بصيغة snake_case أو camelCase، لكن نحن نفترض response كما أرسلناه من الController
                            populateModal(value);
                        });

                        marker.addListener('mouseover', function() {
                            // on hover يمكن تغيير label مؤقتاً
                            const label = value.name || '';
                            this.setLabel({ text: label, fontWeight: 'bold', fontSize: '12px' });
                        });

                        marker.addListener('mouseout', function() {
                            this.setLabel({ text: labelText, fontWeight: 'bold', fontSize: '16px' });
                        });

                        markers.push(marker);
                    });

                    // اختياري: ضبط bound لوضع كل الماركرز في الشاشة
                    if (markers.length) {
                        const bounds = new google.maps.LatLngBounds();
                        markers.forEach(m => bounds.extend(m.getPosition()));
                        map.fitBounds(bounds);
                    } else {
                        map.setCenter(defaultCenter);
                        map.setZoom(12);
                    }
                },
                error(xhr, status, error) {
                    console.error('AJAX Error:', error);
                },
                complete() {
                    hideLoader();
                }
            });
        }

        // تهيئة select2 و form submit
        $(document).ready(function() {
            $('#driver_id').select2({ placeholder: "Select Driver", allowClear: true, width: '100%' });
            $('#plate_number').select2({ placeholder: "Select Plate", allowClear: true, width: '100%' });

            $('#filter-form').on('submit', function(e) {
                e.preventDefault();
                const filters = {
                    driver_id: $('#driver_id').val(),
                    imei: $('#imei').val(),
                    plate_number: $('#plate_number').val()
                };
                loadLocations(filters);
            });

            $('#btn-reset').on('click', function() {
                $('#driver_id').val(null).trigger('change');
                $('#imei').val('');
                // $('#plate_number').val('');
                $('#plate_number').val(null).trigger('change');
                loadLocations(); // load all
            });
        });

        // expose initMap to google callback
        window.initMap = initMap;
    </script>

    <!-- لو عندك ملفات محلية أخرى -->
    {{-- <script src="{{ URL::asset('/assets/js/app.min.js') }}"></script> --}}
@endsection
