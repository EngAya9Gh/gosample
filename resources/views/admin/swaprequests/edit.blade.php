@extends('layouts.master')
@section('title')
    @lang('translation.swaprequests')
@endsection
@section('content')
    @component('components.breadcrumb')
        @slot('li_1')
            @lang('translation.appname')
        @endslot
        @slot('title')
            @lang('translation.swaprequests')
        @endslot
    @endcomponent

    <div class="card modern-filter-card">
        <div class="card-header">
            <h4 class="card-title mb-0">{{ trans('translation.edit') }} {{ trans('translation.swaprequest.title_singular') }}</h4>
        </div>

        <div class="card-body">
            <form method="POST" action="{{ route('admin.swaprequests.update', [$swaprequest->id]) }}" enctype="multipart/form-data">
                @method('PUT')
                @csrf
                <div class="row">
                    <div class="col-lg-6 mb-3">
                        <label class="required" for="task_id">{{ trans('translation.swaprequest.fields.task') }}</label>
                        <select class="form-control {{ $errors->has('task') ? 'is-invalid' : '' }}"
                            name="task_id" id="task_id" required>
                            @foreach ($tasks as $id => $entry)
                                <option value="{{ $id }}"
                                    {{ (old('task_id') ? old('task_id') : $swaprequest->task->id ?? '') == $id ? 'selected' : '' }}>
                                    {{ $entry }}
                                </option>
                            @endforeach
                        </select>
                        @if ($errors->has('task'))
                            <div class="invalid-feedback">{{ $errors->first('task') }}</div>
                        @endif
                        <small class="help-block text-muted">{{ trans('translation.swaprequest.fields.task_helper') }}</small>
                    </div>

                    <div class="col-lg-6 mb-3">
                        <label class="required" for="driver_a">{{ trans('translation.swaprequest.fields.driver_a') }}</label>
                        <select class="form-control {{ $errors->has('driver') ? 'is-invalid' : '' }}"
                            name="driver_a" id="driver_a" required>
                            @foreach ($drivers as $id => $entry)
                                <option value="{{ $id }}"
                                    {{ (old('driver_a') ? old('driver_a') : $swaprequest->driverA->id ?? '') == $id ? 'selected' : '' }}>
                                    {{ $entry }}
                                </option>
                            @endforeach
                        </select>
                        @if ($errors->has('driver'))
                            <div class="invalid-feedback">{{ $errors->first('driver') }}</div>
                        @endif
                        <small class="help-block text-muted">{{ trans('translation.swaprequest.fields.driver_helper') }}</small>
                    </div>

                    <div class="col-lg-6 mb-3">
                        <label class="required" for="driver_id">{{ trans('translation.swaprequest.fields.driver') }}</label>
                        <select class="form-control {{ $errors->has('driver') ? 'is-invalid' : '' }}"
                            name="driver_id" id="driver_id" required>
                            @foreach ($drivers as $id => $entry)
                                <option value="{{ $id }}"
                                    {{ (old('driver_id') ? old('driver_id') : $swaprequest->driver->id ?? '') == $id ? 'selected' : '' }}>
                                    {{ $entry }}
                                </option>
                            @endforeach
                        </select>
                        @if ($errors->has('driver'))
                            <div class="invalid-feedback">{{ $errors->first('driver') }}</div>
                        @endif
                        <small class="help-block text-muted">{{ trans('translation.swaprequest.fields.driver_helper') }}</small>
                    </div>

                    <div class="col-lg-6 mb-3">
                        <label class="required" for="statuss">{{ trans('translation.swaprequest.fields.status') }}</label>
                        <select class="form-control {{ $errors->has('status') ? 'is-invalid' : '' }}" name="status"
                            id="statuss" required>
                            <option value disabled {{ old('status', null) === null ? 'selected' : '' }}>
                                {{ trans('translation.pleaseSelect') }}
                            </option>
                            @foreach (App\Models\Swap::STATUS_SELECT as $key => $label)
                                <option value="{{ $key }}"
                                    {{ old('status', $swaprequest->status) === (string) $key ? 'selected' : '' }}>
                                    {{ $label }}
                                </option>
                            @endforeach
                        </select>
                        @if ($errors->has('status'))
                            <div class="invalid-feedback">{{ $errors->first('status') }}</div>
                        @endif
                        <small class="help-block text-muted">{{ trans('translation.swaprequest.fields.status_helper') }}</small>
                    </div>
                </div>

                <div class="col-lg-12 d-flex justify-content-end flex-wrap mt-2" style="gap: 10px;">
                    <a href="{{ route('admin.swaprequests.index') }}" class="btn btn-reset mb-1">
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
