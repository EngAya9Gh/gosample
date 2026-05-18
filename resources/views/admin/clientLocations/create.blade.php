@extends('layouts.admin')
@section('content')
    <div class="card modern-filter-card">
        <div class="card-header">
            <h4 class="card-title mb-0">{{ trans('translation.create') }} {{ trans('cruds.clientLocation.title_singular') }}</h4>
        </div>

        <div class="card-body">
            <form method="POST" action="{{ route('admin.client-locations.store') }}" enctype="multipart/form-data">
                @csrf
                <div class="row">
                    <div class="col-lg-6 mb-3">
                        <label for="client_id">{{ trans('cruds.clientLocation.fields.client') }}</label>
                        <select class="form-control select2 {{ $errors->has('client') ? 'is-invalid' : '' }}"
                            name="client_id" id="client_id">
                            @foreach ($clients as $id => $entry)
                                <option value="{{ $id }}" {{ old('client_id') == $id ? 'selected' : '' }}>
                                    {{ $entry }}
                                </option>
                            @endforeach
                        </select>
                        @if ($errors->has('client'))
                            <div class="invalid-feedback">{{ $errors->first('client') }}</div>
                        @endif
                        <small class="help-block text-muted">{{ trans('cruds.clientLocation.fields.client_helper') }}</small>
                    </div>

                    <div class="col-lg-6 mb-3">
                        <label for="location_id">{{ trans('cruds.clientLocation.fields.location') }}</label>
                        <select class="form-control select2 {{ $errors->has('location') ? 'is-invalid' : '' }}"
                            name="location_id" id="location_id">
                            @foreach ($locations as $id => $entry)
                                <option value="{{ $id }}" {{ old('location_id') == $id ? 'selected' : '' }}>
                                    {{ $entry }}
                                </option>
                            @endforeach
                        </select>
                        @if ($errors->has('location'))
                            <div class="invalid-feedback">{{ $errors->first('location') }}</div>
                        @endif
                        <small class="help-block text-muted">{{ trans('cruds.clientLocation.fields.location_helper') }}</small>
                    </div>
                </div>

                <div class="col-lg-12 d-flex justify-content-end flex-wrap mt-2" style="gap: 10px;">
                    <a href="{{ route('admin.client-locations.index') }}" class="btn btn-reset mb-1">
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
