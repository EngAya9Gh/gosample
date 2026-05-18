@extends('layouts.master')
@section('title')
    @lang('translation.shipments')
@endsection
@section('content')
    @component('components.breadcrumb')
        @slot('li_1')
            @lang('translation.appname')
        @endslot
        @slot('title')
            @lang('translation.shipments')
        @endslot
    @endcomponent

    <div class="card modern-filter-card">
        <div class="card-header">
            <h4 class="card-title mb-0">{{ trans('global.create') }} {{ trans('cruds.shipment.title_singular') }}</h4>
        </div>

        <div class="card-body">
            <form method="POST" action="{{ route('admin.shipments.store') }}" enctype="multipart/form-data">
                @csrf
                <div class="row">
                    <div class="col-lg-3 mb-3">
                        <label for="sender_name">{{ trans('cruds.shipment.fields.sender_name') }}</label>
                        <input class="form-control" type="text" name="sender_name" id="sender_name" value="{{ old('sender_name') }}" placeholder="Sender name">
                    </div>
                    <div class="col-lg-3 mb-3">
                        <label for="sender_long">{{ trans('cruds.shipment.fields.sender_long') }}</label>
                        <input class="form-control" type="text" name="sender_long" id="sender_long" value="{{ old('sender_long') }}" placeholder="Longitude">
                    </div>
                    <div class="col-lg-3 mb-3">
                        <label for="sender_lat">{{ trans('cruds.shipment.fields.sender_lat') }}</label>
                        <input class="form-control" type="text" name="sender_lat" id="sender_lat" value="{{ old('sender_lat') }}" placeholder="Latitude">
                    </div>
                    <div class="col-lg-3 mb-3">
                        <label for="sender_mobile">{{ trans('cruds.shipment.fields.sender_mobile') }}</label>
                        <input class="form-control" type="text" name="sender_mobile" id="sender_mobile" value="{{ old('sender_mobile') }}" placeholder="Mobile">
                    </div>

                    <div class="col-lg-3 mb-3">
                        <label for="receiver_name">{{ trans('cruds.shipment.fields.receiver_name') }}</label>
                        <input class="form-control" type="text" name="receiver_name" id="receiver_name" value="{{ old('receiver_name') }}" placeholder="Receiver name">
                    </div>
                    <div class="col-lg-3 mb-3">
                        <label for="receiver_long">{{ trans('cruds.shipment.fields.receiver_long') }}</label>
                        <input class="form-control" type="text" name="receiver_long" id="receiver_long" value="{{ old('receiver_long') }}" placeholder="Longitude">
                    </div>
                    <div class="col-lg-3 mb-3">
                        <label for="receiver_lat">{{ trans('cruds.shipment.fields.receiver_lat') }}</label>
                        <input class="form-control" type="text" name="receiver_lat" id="receiver_lat" value="{{ old('receiver_lat') }}" placeholder="Latitude">
                    </div>
                    <div class="col-lg-3 mb-3">
                        <label for="receiver_mobile">{{ trans('cruds.shipment.fields.receiver_mobile') }}</label>
                        <input class="form-control" type="text" name="receiver_mobile" id="receiver_mobile" value="{{ old('receiver_mobile') }}" placeholder="Mobile">
                    </div>

                    <div class="col-lg-4 mb-3">
                        <label class="required" for="carrier">{{ trans('cruds.shipment.fields.carrier') }}</label>
                        <input class="form-control" type="text" name="carrier" id="carrier" value="{{ old('carrier') }}" required placeholder="Carrier">
                    </div>
                    <div class="col-lg-4 mb-3">
                        <label class="required" for="reference_number">{{ trans('cruds.shipment.fields.reference_number') }}</label>
                        <input class="form-control" type="text" name="reference_number" id="reference_number" value="{{ old('reference_number') }}" required placeholder="Reference">
                    </div>
                    <div class="col-lg-4 mb-3">
                        <label class="required" for="batch">{{ trans('cruds.shipment.fields.batch') }}</label>
                        <input class="form-control" type="text" name="batch" id="batch" value="{{ old('batch') }}" required placeholder="Batch">
                    </div>

                    <div class="col-lg-3 mb-3">
                        <label class="required" for="task">{{ trans('cruds.shipment.fields.task') }}</label>
                        <select class="form-control" name="task" id="task" required>
                            <option value="" {{ old('task') ? '' : 'selected' }}>— Select task —</option>
                            @foreach ($tasks as $id => $entry)
                                @if ($id !== '' && $entry !== trans('translation.pleaseSelect'))
                                    <option value="{{ $entry }}" {{ (string) old('task') === (string) $entry ? 'selected' : '' }}>
                                        {{ $entry }}
                                    </option>
                                @endif
                            @endforeach
                        </select>
                        @if ($errors->has('task'))
                            <div class="invalid-feedback">{{ $errors->first('task') }}</div>
                        @endif
                    </div>

                    <div class="col-lg-3 mb-3">
                        <label class="required" for="from_location">{{ trans('translation.task.fields.from_location') }}</label>
                        <select class="form-control" name="from_location" id="from_location" required>
                            <option value="" {{ old('from_location') ? '' : 'selected' }}>— Select from location —</option>
                            @foreach ($from_locations as $id => $entry)
                                <option value="{{ $id }}" {{ (string) old('from_location') === (string) $id ? 'selected' : '' }}>
                                    {{ $entry }}
                                </option>
                            @endforeach
                        </select>
                        @if ($errors->has('from_location'))
                            <div class="invalid-feedback">{{ $errors->first('from_location') }}</div>
                        @endif
                    </div>

                    <div class="col-lg-3 mb-3">
                        <label for="to_location">{{ trans('translation.task.fields.to_location') }}</label>
                        <select class="form-control {{ $errors->has('to_location') ? 'is-invalid' : '' }}"
                            name="to_location" id="to_location">
                            <option value="" {{ old('to_location') ? '' : 'selected' }}>— Select to location —</option>
                            @foreach ($to_locations as $id => $entry)
                                @if ($id !== '' && $entry !== trans('translation.pleaseSelect'))
                                    <option value="{{ $id }}" {{ (string) old('to_location') === (string) $id ? 'selected' : '' }}>
                                        {{ $entry }}
                                    </option>
                                @endif
                            @endforeach
                        </select>
                        @if ($errors->has('to_location'))
                            <div class="invalid-feedback">{{ $errors->first('to_location') }}</div>
                        @endif
                    </div>

                    <div class="col-lg-3 mb-3">
                        <label for="driver_id">{{ trans('translation.task.fields.driver') }}</label>
                        <select class="form-control {{ $errors->has('driver') ? 'is-invalid' : '' }}"
                            name="driver_id" id="driver_id">
                            <option value="" {{ old('driver_id') ? '' : 'selected' }}>— Select driver —</option>
                            @foreach ($drivers as $id => $entry)
                                @if ($id !== '' && $entry !== trans('translation.pleaseSelect'))
                                    <option value="{{ $id }}" {{ (string) old('driver_id') === (string) $id ? 'selected' : '' }}>
                                        {{ $entry }}
                                    </option>
                                @endif
                            @endforeach
                        </select>
                        @if ($errors->has('driver'))
                            <div class="invalid-feedback">{{ $errors->first('driver') }}</div>
                        @endif
                    </div>
                </div>

                <div class="col-lg-12 d-flex justify-content-end flex-wrap mt-2" style="gap: 10px;">
                    <a href="{{ route('admin.shipments.index') }}" class="btn btn-reset mb-1">
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

