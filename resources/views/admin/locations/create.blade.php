@extends('layouts.master')
@section('title')
    @lang('translation.locations')
@endsection
@section('content')
    @component('components.breadcrumb')
        @slot('li_1')
            @lang('translation.appname')
        @endslot
        @slot('title')
            @lang('translation.locations')
        @endslot
    @endcomponent

    <div class="card modern-filter-card">
        <div class="card-header">
            <h4 class="card-title mb-0">{{ trans('translation.create') }} {{ trans('translation.location') }}</h4>
        </div>

        <div class="card-body">
            <form method="POST" action="{{ route('admin.locations.store') }}" enctype="multipart/form-data">
                @csrf
                <div class="row">
                    <div class="col-lg-6 mb-3">
                        <label class="required" for="name">{{ trans('translation.location.fields.name') }}</label>
                        <input class="form-control {{ $errors->has('name') ? 'is-invalid' : '' }}" type="text"
                            name="name" id="name" value="{{ old('name', '') }}" placeholder="Location name" required>
                        @if ($errors->has('name'))
                            <div class="invalid-feedback">{{ $errors->first('name') }}</div>
                        @endif
                    </div>

                    <div class="col-lg-6 mb-3">
                        <label class="required" for="arabic_name">{{ trans('translation.location.fields.arabic_name') }}</label>
                        <input class="form-control {{ $errors->has('arabic_name') ? 'is-invalid' : '' }}" type="text"
                            name="arabic_name" id="arabic_name" value="{{ old('arabic_name', '') }}" placeholder="الاسم العربي" required>
                        @if ($errors->has('arabic_name'))
                            <div class="invalid-feedback">{{ $errors->first('arabic_name') }}</div>
                        @endif
                    </div>

                    <div class="col-lg-6 mb-3">
                        <label class="required" for="description">{{ trans('translation.location.fields.description') }}</label>
                        <input class="form-control {{ $errors->has('description') ? 'is-invalid' : '' }}" type="text"
                            name="description" id="description" value="{{ old('description', '') }}"
                            placeholder="Short description" required>
                        @if ($errors->has('description'))
                            <div class="invalid-feedback">{{ $errors->first('description') }}</div>
                        @endif
                    </div>

                    <div class="col-lg-6 mb-3">
                        <label for="statuss">{{ trans('translation.location.fields.status') }}</label>
                        <select class="form-control {{ $errors->has('status') ? 'is-invalid' : '' }}" name="status"
                            id="statuss">
                            <option value disabled {{ old('status', null) === null ? 'selected' : '' }}>
                                {{ trans('translation.pleaseSelect') }}
                            </option>
                            @foreach (App\Models\Location::STATUS_SELECT as $key => $label)
                                <option value="{{ $key }}" {{ old('status', '1') === (string) $key ? 'selected' : '' }}>
                                    {{ $label }}
                                </option>
                            @endforeach
                        </select>
                        @if ($errors->has('status'))
                            <div class="invalid-feedback">{{ $errors->first('status') }}</div>
                        @endif
                    </div>

                    <div class="col-lg-6 mb-3">
                        <label for="lat">{{ trans('translation.location.fields.lat') }}</label>
                        <input class="form-control {{ $errors->has('lat') ? 'is-invalid' : '' }}" type="text"
                            name="lat" id="lat" value="{{ old('lat', '') }}" placeholder="24.7117...">
                        @if ($errors->has('lat'))
                            <div class="invalid-feedback">{{ $errors->first('lat') }}</div>
                        @endif
                    </div>

                    <div class="col-lg-6 mb-3">
                        <label for="lng">{{ trans('translation.location.fields.lng') }}</label>
                        <input class="form-control {{ $errors->has('lng') ? 'is-invalid' : '' }}" type="text"
                            name="lng" id="lng" value="{{ old('lng', '') }}" placeholder="46.6752...">
                        @if ($errors->has('lng'))
                            <div class="invalid-feedback">{{ $errors->first('lng') }}</div>
                        @endif
                    </div>

                    <div class="col-lg-6 mb-3">
                        <label for="pickup_waiting_time">{{ trans('translation.location.fields.pickup_waiting_time') }}</label>
                        <input class="form-control {{ $errors->has('pickup_waiting_time') ? 'is-invalid' : '' }}"
                            type="text" name="pickup_waiting_time" id="pickup_waiting_time"
                            value="{{ old('pickup_waiting_time', '') }}" placeholder="Minutes">
                        @if ($errors->has('pickup_waiting_time'))
                            <div class="invalid-feedback">{{ $errors->first('pickup_waiting_time') }}</div>
                        @endif
                    </div>

                    <div class="col-lg-6 mb-3">
                        <label for="drop_off_waiting_time">{{ trans('translation.location.fields.drop_off_waiting_time') }}</label>
                        <input class="form-control {{ $errors->has('drop_off_waiting_time') ? 'is-invalid' : '' }}"
                            type="text" name="drop_off_waiting_time" id="drop_off_waiting_time"
                            value="{{ old('drop_off_waiting_time', '') }}" placeholder="Minutes">
                        @if ($errors->has('drop_off_waiting_time'))
                            <div class="invalid-feedback">{{ $errors->first('drop_off_waiting_time') }}</div>
                        @endif
                    </div>

                    <div class="col-lg-12 mb-3">
                        <label>Map (click to set location)</label>
                        <div id="searchbox" class="mb-2">
                            <input id="pac-input" class="form-control" type="text" placeholder="Search address" autocomplete="off">
                        </div>
                        <div id="map-wrap" style="position: relative;">
                            <div id="map" style="height: 320px; border-radius: 10px; overflow: hidden; border: 1.5px solid #e2e8f0;"></div>
                            <div id="map-fallback" style="display:none; height: 320px; border-radius: 10px; border: 1.5px dashed #e2e8f0; background: #f8fafc; padding: 30px; text-align: center; color: #64748b;">
                                <i class="ri-map-pin-off-line" style="font-size: 2.4rem; opacity: 0.5; display: block; margin-bottom: 10px;"></i>
                                <div style="color: #475569; font-weight: 600; margin-bottom: 4px;">Map unavailable</div>
                                <div style="font-size: 0.85rem;">The Google Maps API key needs attention. You can still enter the latitude and longitude manually above.</div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="col-lg-12 d-flex justify-content-end flex-wrap mt-2" style="gap: 10px;">
                    <a href="{{ route('admin.locations.index') }}" class="btn btn-reset mb-1">
                        {{ trans('global.cancel') }}
                    </a>
                    <button class="btn btn-save mb-1" type="submit">
                        <i class="fas fa-save"></i> {{ trans('translation.save') }}
                    </button>
                </div>
            </form>
        </div>
    </div>
@endsection

@section('script')
    <script type="text/javascript">
        // Called by Google Maps when the API key is rejected (invalid, restricted, unpaid, etc.)
        window.gm_authFailure = function () {
            var map = document.getElementById('map');
            var fallback = document.getElementById('map-fallback');
            if (map) map.style.display = 'none';
            if (fallback) fallback.style.display = 'flex';
            // Center the fallback content vertically
            if (fallback) {
                fallback.style.flexDirection = 'column';
                fallback.style.alignItems = 'center';
                fallback.style.justifyContent = 'center';
            }
            console.warn('Google Maps auth failed. Check the API key (billing, restrictions, allowed referrers).');
        };

        function initMap() {
            var myStyles = [{
                featureType: "poi",
                elementType: "labels",
                stylers: [{ visibility: "off" }]
            }];

            const myLatlng = { lat: 24.71171284420022, lng: 46.67523452599728 };
            var marker;
            let markers = [];

            var map = new google.maps.Map(document.getElementById('map'), {
                center: { lat: 24.7156901, lng: 46.6439257 },
                zoom: 12,
                styles: myStyles
            });

            function placeMarker(location) {
                if (marker) {
                    marker.setPosition(location);
                } else {
                    marker = new google.maps.Marker({ position: location, map: map });
                }
            }

            google.maps.event.addListener(map, 'click', function (event) {
                placeMarker(event.latLng);
                $("#lat").val(event.latLng.lat());
                $("#lng").val(event.latLng.lng());
            });

            var searchBox = new google.maps.places.SearchBox(document.getElementById('pac-input'));
            map.controls[google.maps.ControlPosition.TOP_CENTER].push(document.getElementById('pac-input'));
            google.maps.event.addListener(searchBox, 'places_changed', function () {
                searchBox.set('map', null);
                var places = searchBox.getPlaces();
                var bounds = new google.maps.LatLngBounds();
                var i, place;
                for (i = 0; place = places[i]; i++) {
                    (function (place) {
                        var marker = new google.maps.Marker({ position: place.geometry.location });
                        $("#lat").val(marker.position.lat());
                        $("#lng").val(marker.position.lng());
                        marker.bindTo('map', searchBox, 'map');
                        google.maps.event.addListener(marker, 'map_changed', function () {
                            if (!this.getMap()) { this.unbindAll(); }
                        });
                        bounds.extend(place.geometry.location);
                    }(place));
                }
                map.fitBounds(bounds);
                searchBox.set('map', map);
                map.setZoom(Math.min(map.getZoom(), 12));
            });
        }

        function notify(message) {
            $("#notification").fadeIn("slow").append(message);
            $(".dismiss").click(function () { $("#notification").fadeOut("slow"); });
        }
    </script>

    <script src="https://maps.googleapis.com/maps/api/js?key=AIzaSyDf1ht01vFyWcfWS33mmdfd30qm5-uyWhM&libraries=places&callback=initMap" async defer></script>
    <link href="{{ asset('css/map.css') }}" rel="stylesheet" type="text/css" />
    <script src="{{ URL::asset('assets/libs/prismjs/prismjs.min.js') }}"></script>
    <script src="{{ URL::asset('assets/js/pages/notifications.init.js') }}"></script>
    <script src="{{ URL::asset('/assets/js/app.min.js') }}"></script>
@endsection
