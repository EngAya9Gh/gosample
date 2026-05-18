@extends('layouts.master')
@section('title')
    @lang('translation.drivers')
@endsection
@section('content')
    @component('components.breadcrumb')
        @slot('li_1')
            @lang('translation.appname')
        @endslot
        @slot('title')
            @lang('translation.drivers')
        @endslot
    @endcomponent

    <div class="card modern-filter-card">
        <div class="card-header">
            <h4 class="card-title mb-0">{{ trans('global.edit') }} {{ trans('cruds.clientDriver.title_singular') }}</h4>
        </div>

        <div class="card-body">
            <form method="POST" action="{{ route('admin.client-drivers.update', [$clientDriver->id]) }}" enctype="multipart/form-data">
                @method('PUT')
                @csrf
                <div class="row">
                    <div class="col-lg-6 mb-3">
                        <label class="required" for="driver_id">{{ trans('cruds.clientDriver.fields.driver') }}</label>
                        <select class="form-control select2 {{ $errors->has('driver') ? 'is-invalid' : '' }}"
                            name="driver_id" id="driver_id" required>
                            @foreach ($drivers as $id => $entry)
                                <option value="{{ $id }}"
                                    {{ (old('driver_id') ? old('driver_id') : $clientDriver->driver->id ?? '') == $id ? 'selected' : '' }}>
                                    {{ $entry }}
                                </option>
                            @endforeach
                        </select>
                        @if ($errors->has('driver'))
                            <div class="invalid-feedback">{{ $errors->first('driver') }}</div>
                        @endif
                        <small class="help-block text-muted">{{ trans('cruds.clientDriver.fields.driver_helper') }}</small>
                    </div>

                    <div class="col-lg-6 mb-3">
                        <label class="required" for="client_id">{{ trans('cruds.clientDriver.fields.client') }}</label>
                        <select class="form-control select2 {{ $errors->has('client') ? 'is-invalid' : '' }}"
                            name="client_id" id="client_id" required>
                            @foreach ($clients as $id => $entry)
                                <option value="{{ $id }}"
                                    {{ (old('client_id') ? old('client_id') : $clientDriver->client->id ?? '') == $id ? 'selected' : '' }}>
                                    {{ $entry }}
                                </option>
                            @endforeach
                        </select>
                        @if ($errors->has('client'))
                            <div class="invalid-feedback">{{ $errors->first('client') }}</div>
                        @endif
                        <small class="help-block text-muted">{{ trans('cruds.clientDriver.fields.client_helper') }}</small>
                    </div>
                </div>

                <div class="col-lg-12 d-flex justify-content-end flex-wrap mt-2" style="gap: 10px;">
                    <a href="{{ route('admin.client-drivers.index') }}" class="btn btn-reset mb-1">
                        {{ trans('global.cancel') }}
                    </a>
                    <button class="btn btn-save mb-1" type="submit">
                        <i class="fas fa-save"></i> {{ trans('global.save') }}
                    </button>
                </div>
            </form>
        </div>
    </div>
@endsection
