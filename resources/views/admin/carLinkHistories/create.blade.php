@extends('layouts.admin')
@section('content')
    <div class="card modern-filter-card">
        <div class="card-header">
            <h4 class="card-title mb-0">{{ trans('translation.create') }} {{ trans('cruds.carLinkHistory.title_singular') }}</h4>
        </div>

        <div class="card-body">
            <form method="POST" action="{{ route('admin.car-link-histories.store') }}" enctype="multipart/form-data">
                @csrf
                <div class="row">
                    <div class="col-lg-6 mb-3">
                        <label for="driver_id">{{ trans('cruds.carLinkHistory.fields.driver') }}</label>
                        <select class="form-control select2 {{ $errors->has('driver') ? 'is-invalid' : '' }}"
                            name="driver_id" id="driver_id">
                            @foreach ($drivers as $id => $entry)
                                <option value="{{ $id }}" {{ old('driver_id') == $id ? 'selected' : '' }}>{{ $entry }}</option>
                            @endforeach
                        </select>
                        @if ($errors->has('driver'))
                            <div class="invalid-feedback">{{ $errors->first('driver') }}</div>
                        @endif
                        <small class="help-block text-muted">{{ trans('cruds.carLinkHistory.fields.driver_helper') }}</small>
                    </div>

                    <div class="col-lg-6 mb-3">
                        <label class="required" for="car_id">{{ trans('cruds.carLinkHistory.fields.car') }}</label>
                        <select class="form-control select2 {{ $errors->has('car') ? 'is-invalid' : '' }}"
                            name="car_id" id="car_id" required>
                            @foreach ($cars as $id => $entry)
                                <option value="{{ $id }}" {{ old('car_id') == $id ? 'selected' : '' }}>{{ $entry }}</option>
                            @endforeach
                        </select>
                        @if ($errors->has('car'))
                            <div class="invalid-feedback">{{ $errors->first('car') }}</div>
                        @endif
                        <small class="help-block text-muted">{{ trans('cruds.carLinkHistory.fields.car_helper') }}</small>
                    </div>

                    <div class="col-lg-6 mb-3">
                        <label class="required" for="action">{{ trans('cruds.carLinkHistory.fields.action') }}</label>
                        <select class="form-control {{ $errors->has('action') ? 'is-invalid' : '' }}" name="action"
                            id="action" required>
                            <option value disabled {{ old('action', null) === null ? 'selected' : '' }}>
                                {{ trans('translation.pleaseSelect') }}
                            </option>
                            @foreach (App\Models\CarLinkHistory::ACTION_SELECT as $key => $label)
                                <option value="{{ $key }}" {{ old('action', '') === (string) $key ? 'selected' : '' }}>
                                    {{ $label }}
                                </option>
                            @endforeach
                        </select>
                        @if ($errors->has('action'))
                            <div class="invalid-feedback">{{ $errors->first('action') }}</div>
                        @endif
                        <small class="help-block text-muted">{{ trans('cruds.carLinkHistory.fields.action_helper') }}</small>
                    </div>
                </div>

                <div class="col-lg-12 d-flex justify-content-end flex-wrap mt-2" style="gap: 10px;">
                    <a href="{{ route('admin.car-link-histories.index') }}" class="btn btn-reset mb-1">
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
