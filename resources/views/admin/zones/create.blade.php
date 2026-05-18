@extends('layouts.master')
@section('title')
    @lang('translation.zones')
@endsection
@section('content')
    @component('components.breadcrumb')
        @slot('li_1')
            @lang('translation.appname')
        @endslot
        @slot('title')
            @lang('translation.zones')
        @endslot
    @endcomponent

    <div class="card modern-filter-card">
        <div class="card-header">
            <h4 class="card-title mb-0">{{ trans('global.create') }} {{ trans('cruds.zone.title_singular') }}</h4>
        </div>

        <div class="card-body">
            <form method="POST" action="{{ route('admin.zones.store') }}" enctype="multipart/form-data" id="myform">
                @csrf
                <div class="row">
                    <div class="col-lg-6 mb-3">
                        <label class="required" for="name">{{ trans('cruds.zone.fields.name') }}</label>
                        <input class="form-control {{ $errors->has('name') ? 'is-invalid' : '' }}" type="text"
                            name="name" id="name" value="{{ old('name', '') }}" placeholder="Zone name" required>
                        @if ($errors->has('name'))
                            <div class="invalid-feedback">{{ $errors->first('name') }}</div>
                        @endif
                        <small class="help-block text-muted">{{ trans('cruds.zone.fields.name_helper') }}</small>
                    </div>

                    <div class="col-lg-12 mb-3">
                        <label class="required" for="area">{{ trans('cruds.zone.fields.area') }}</label>
                        <div id="map" style="height: 420px; border-radius: 10px; overflow: hidden; border: 1.5px solid #e2e8f0;"></div>
                        <input hidden type="text" name="area" id="area">
                        @if ($errors->has('area'))
                            <div class="invalid-feedback d-block">{{ $errors->first('area') }}</div>
                        @endif
                        <small class="help-block text-muted">{{ trans('cruds.zone.fields.area_helper') }}</small>
                    </div>
                </div>

                <div class="col-lg-12 d-flex justify-content-end flex-wrap mt-2" style="gap: 10px;">
                    <a href="{{ route('admin.zones.index') }}" class="btn btn-reset mb-1">
                        {{ trans('global.cancel') }}
                    </a>
                    <button class="btn btn-save mb-1" type="submit" id="save">
                        <i class="fas fa-save"></i> {{ trans('global.save') }}
                    </button>
                </div>
            </form>
        </div>
    </div>
@endsection

@section('script')
    <script type="text/javascript">
        var area = [];
        var polygonArray = [];
        var polygon2;

        $('#myform').on('submit', function () {
            $('#area').val(JSON.stringify(polygonArray));
            return true;
        });

        function initMap() {
            var map = new google.maps.Map(document.getElementById('map'), {
                center: { lat: 24.7156901, lng: 46.6439257 },
                zoom: 12
            });

            var drawingManager = new google.maps.drawing.DrawingManager({
                drawingMode: google.maps.drawing.OverlayType.POLYGON,
                drawingControl: true,
                drawingControlOptions: {
                    position: google.maps.ControlPosition.TOP_CENTER,
                    drawingModes: ['polygon']
                },
                polygonOptions: { editable: true }
            });
            drawingManager.setMap(map);
            google.maps.event.addListener(drawingManager, 'polygoncomplete', function (polygon) {
                polygonArray = [];
                polygon2 = polygon;
                for (var i = 0; i < polygon.getPath().getLength(); i++) {
                    var coords = polygon.getPath().getAt(i).toUrlValue(6).split(',');
                    polygonArray.push({ lat: coords[0], lng: coords[1] });
                }
            });
        }
    </script>

    <script src="https://maps.googleapis.com/maps/api/js?key=AIzaSyDf1ht01vFyWcfWS33mmdfd30qm5-uyWhM&libraries=drawing&callback=initMap" async defer></script>
    <link href="{{ asset('css/map.css') }}" rel="stylesheet" type="text/css" />
    <script src="{{ URL::asset('assets/libs/prismjs/prismjs.min.js') }}"></script>
    <script src="{{ URL::asset('assets/js/pages/notifications.init.js') }}"></script>
    <script src="{{ URL::asset('/assets/js/app.min.js') }}"></script>
@endsection
