@extends('layouts.master')
@section('title')
    @lang('translation.driverSchedules')
@endsection
@section('content')
    @component('components.breadcrumb')
        @slot('li_1')
            @lang('translation.appname')
        @endslot
        @slot('title')
            @lang('translation.driverSchedules')
        @endslot
    @endcomponent

    <div class="card modern-filter-card">
        <div class="card-header">
            <h4 class="card-title mb-0">{{ trans('translation.create') }} {{ trans('translation.driverSchedule.title_singular') }}</h4>
        </div>

        <div class="card-body">
            <form method="POST" action="{{ route('admin.driver-schedules.store') }}" enctype="multipart/form-data">
                @csrf
                <div class="row">
                    <div class="col-lg-6 mb-3">
                        <label for="from_location">{{ trans('translation.driverSchedule.fields.from_location') }}</label>
                        <select class="form-control select2 {{ $errors->has('from_location') ? 'is-invalid' : '' }}"
                            name="from_location" id="from_location">
                            @foreach ($from_locations as $id => $entry)
                                <option value="{{ $id }}" {{ old('from_location') == $id ? 'selected' : '' }}>
                                    {{ $entry }}
                                </option>
                            @endforeach
                        </select>
                        @if ($errors->has('from_location'))
                            <div class="invalid-feedback">{{ $errors->first('from_location') }}</div>
                        @endif
                        <small class="help-block text-muted">{{ trans('translation.driverSchedule.fields.from_location_helper') }}</small>
                    </div>

                    <div class="col-lg-6 mb-3">
                        <label for="to_location">{{ trans('translation.driverSchedule.fields.to_location') }}</label>
                        <select class="form-control select2 {{ $errors->has('to_location') ? 'is-invalid' : '' }}"
                            name="to_location" id="to_location">
                            @foreach ($to_locations as $id => $entry)
                                <option value="{{ $id }}" {{ old('to_location') == $id ? 'selected' : '' }}>
                                    {{ $entry }}
                                </option>
                            @endforeach
                        </select>
                        @if ($errors->has('to_location'))
                            <div class="invalid-feedback">{{ $errors->first('to_location') }}</div>
                        @endif
                        <small class="help-block text-muted">{{ trans('translation.driverSchedule.fields.to_location_helper') }}</small>
                    </div>

                    <div class="col-lg-6 mb-3">
                        <label for="driver_id">{{ trans('translation.driverSchedule.fields.driver') }}</label>
                        <select class="form-control select2 {{ $errors->has('driver') ? 'is-invalid' : '' }}"
                            name="driver_id" id="driver_id">
                            @foreach ($drivers as $id => $entry)
                                <option value="{{ $id }}" {{ old('driver_id') == $id ? 'selected' : '' }}>
                                    {{ $entry }}
                                </option>
                            @endforeach
                        </select>
                        @if ($errors->has('driver'))
                            <div class="invalid-feedback">{{ $errors->first('driver') }}</div>
                        @endif
                        <small class="help-block text-muted">{{ trans('translation.driverSchedule.fields.driver_helper') }}</small>
                    </div>

                    <div class="col-lg-6 mb-3">
                        <label for="plate_number">{{ trans('translation.driverSchedule.fields.plate_number') }}</label>
                        <input class="form-control {{ $errors->has('plate_number') ? 'is-invalid' : '' }}" type="text"
                            name="plate_number" id="plate_number" value="{{ old('plate_number', '') }}"
                            placeholder="Vehicle plate">
                        @if ($errors->has('plate_number'))
                            <div class="invalid-feedback">{{ $errors->first('plate_number') }}</div>
                        @endif
                        <small class="help-block text-muted">{{ trans('translation.driverSchedule.fields.plate_number_helper') }}</small>
                    </div>

                    <div class="col-lg-12 mb-3">
                        <label for="note">{{ trans('translation.driverSchedule.fields.note') }}</label>
                        <textarea class="form-control {{ $errors->has('note') ? 'is-invalid' : '' }}" name="note"
                            id="note" rows="3" placeholder="Optional note">{{ old('note') }}</textarea>
                        @if ($errors->has('note'))
                            <div class="invalid-feedback">{{ $errors->first('note') }}</div>
                        @endif
                        <small class="help-block text-muted">{{ trans('translation.driverSchedule.fields.note_helper') }}</small>
                    </div>
                </div>

                <div class="col-lg-12 d-flex justify-content-end flex-wrap mt-2" style="gap: 10px;">
                    <a href="{{ route('admin.driver-schedules.index') }}" class="btn btn-reset mb-1">
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
