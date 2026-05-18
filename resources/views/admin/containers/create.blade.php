@extends('layouts.master')
@section('title')
    @lang('translation.containers')
@endsection
@section('content')
    @component('components.breadcrumb')
        @slot('li_1')
            @lang('translation.appname')
        @endslot
        @slot('title')
            @lang('translation.containers')
        @endslot
    @endcomponent

    <div class="card modern-filter-card">
        <div class="card-header">
            <h4 class="card-title mb-0">{{ trans('translation.create') }} {{ trans('translation.container.title_singular') }}</h4>
        </div>

        <div class="card-body">
            <form method="POST" action="{{ route('admin.containers.store') }}" enctype="multipart/form-data">
                @csrf
                <div class="row">
                    <div class="col-6 mb-3">
                        <label for="car_id">{{ trans('translation.container.fields.car') }}</label>
                        <select class="form-control select2 {{ $errors->has('car') ? 'is-invalid' : '' }}"
                            name="car_id" id="car_id" data-placeholder="Select car">
                            <option value=""></option>
                            @foreach ($cars as $id => $entry)
                                <option value="{{ $id }}" {{ old('car_id') == $id ? 'selected' : '' }}>
                                    {{ $entry }}</option>
                            @endforeach
                        </select>
                        @if ($errors->has('car'))
                            <div class="invalid-feedback">
                                {{ $errors->first('car') }}
                            </div>
                        @endif
                        <small class="help-block text-muted">{{ trans('translation.container.fields.car_helper') }}</small>
                    </div>
                    <div class="col-6 mb-3">
                        <label class="required"
                            for="imei">{{ trans('translation.container.fields.sensor') }}</label>
                        <input class="form-control {{ $errors->has('imei') ? 'is-invalid' : '' }}" type="text"
                            name="imei" id="imei" value="{{ old('imei', '') }}" required placeholder="Enter sensor IMEI">
                        @if ($errors->has('imei'))
                            <div class="invalid-feedback">
                                {{ $errors->first('imei') }}
                            </div>
                        @endif
                        <small class="help-block text-muted">{{ trans('translation.container.fields.imei_helper') }}</small>
                    </div>
                </div>
                <div class="row">
                    <div class="col-6 mb-3">
                        <label class="required"
                            for="model">{{ trans('translation.container.fields.model') }}</label>
                        <input class="form-control {{ $errors->has('model') ? 'is-invalid' : '' }}" type="text"
                            name="model" id="model" value="{{ old('model', '') }}" required placeholder="Enter model">
                        @if ($errors->has('model'))
                            <div class="invalid-feedback">
                                {{ $errors->first('model') }}
                            </div>
                        @endif
                        <small class="help-block text-muted">{{ trans('translation.container.fields.imei_helper') }}</small>
                    </div>
                    <div class="col-6 mb-3">
                        <label class="required">{{ trans('translation.container.fields.type') }}</label>
                        <select class="form-control {{ $errors->has('type') ? 'is-invalid' : '' }}" name="type"
                            id="type" required data-placeholder="Select type">
                            <option value=""></option>
                            @foreach (App\Models\Container::TYPE_SELECT as $key => $label)
                                <option value="{{ $key }}"
                                    {{ old('type') === (string) $key ? 'selected' : '' }}>{{ $label }}
                                </option>
                            @endforeach
                        </select>
                        @if ($errors->has('type'))
                            <div class="invalid-feedback">
                                {{ $errors->first('type') }}
                            </div>
                        @endif
                        <small class="help-block text-muted">{{ trans('translation.container.fields.type_helper') }}</small>
                    </div>
                </div>
                <div class="row">
                    <div class="col-6 mb-3">
                        <label for="description">{{ trans('translation.container.fields.description') }}</label>
                        <textarea class="form-control {{ $errors->has('description') ? 'is-invalid' : '' }}" name="description"
                            id="description" rows="3" placeholder="Optional notes">{{ old('description') }}</textarea>
                        @if ($errors->has('description'))
                            <div class="invalid-feedback">
                                {{ $errors->first('description') }}
                            </div>
                        @endif
                        <small class="help-block text-muted">{{ trans('translation.container.fields.description_helper') }}</small>
                    </div>
                    <div class="col-6 mb-3">
                        <label class="required">{{ trans('translation.container.fields.status') }}</label>
                        <select class="form-control {{ $errors->has('status') ? 'is-invalid' : '' }}" name="status"
                            id="statuss" required data-placeholder="Select status">
                            <option value=""></option>
                            @foreach (App\Models\Container::STATUS_SELECT as $key => $label)
                                <option value="{{ $key }}"
                                    data-color="{{ $key == 1 ? '#22c55e' : '#ef4444' }}"
                                    {{ old('status') === (string) $key ? 'selected' : '' }}>{{ $label }}
                                </option>
                            @endforeach
                        </select>
                        @if ($errors->has('status'))
                            <div class="invalid-feedback">
                                {{ $errors->first('status') }}
                            </div>
                        @endif
                        <small class="help-block text-muted">{{ trans('translation.container.fields.status_helper') }}</small>
                    </div>
                </div>
                <div class="col-lg-12">
                    <a href="{{ route('admin.containers.index') }}" class="btn btn-reset">
                        Cancel
                    </a>
                    <button class="btn btn-save" type="submit">
                        <i class="fas fa-save"></i> {{ trans('translation.save') }}
                    </button>
                </div>
            </form>
        </div>
    </div>
@endsection
