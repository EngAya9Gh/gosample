@extends('layouts.master')
@section('title')
    {{ trans('cruds.barcode.title') }}
@endsection
@section('content')
    @component('components.breadcrumb')
        @slot('li_1')
            @lang('translation.appname')
        @endslot
        @slot('title')
            {{ trans('cruds.barcode.title') }}
        @endslot
    @endcomponent

    <div class="card modern-filter-card">
        <div class="card-header">
            <h4 class="card-title mb-0">{{ trans('translation.edit') }} {{ trans('cruds.barcode.title_singular') }}</h4>
        </div>

        <div class="card-body">
            <form method="POST" action="{{ route('admin.barcodes.update', [$barcode->id]) }}" enctype="multipart/form-data">
                @method('PUT')
                @csrf
                <div class="row">
                    <div class="col-lg-6 mb-3">
                        <label class="required" for="type">{{ trans('cruds.barcode.fields.type') }}</label>
                        <input class="form-control {{ $errors->has('type') ? 'is-invalid' : '' }}" type="text"
                            name="type" id="type" value="{{ old('type', $barcode->type) }}"
                            placeholder="e.g. SAMPLE, BAG, BOX" required>
                        @if ($errors->has('type'))
                            <div class="invalid-feedback">{{ $errors->first('type') }}</div>
                        @endif
                        <small class="help-block text-muted">{{ trans('cruds.barcode.fields.type_helper') }}</small>
                    </div>

                    <div class="col-lg-6 mb-3">
                        <label for="last_number">{{ trans('cruds.barcode.fields.last_number') }}</label>
                        <input class="form-control {{ $errors->has('last_number') ? 'is-invalid' : '' }}" type="number"
                            name="last_number" id="last_number" value="{{ old('last_number', $barcode->last_number) }}"
                            step="1" placeholder="Starting / last issued number">
                        @if ($errors->has('last_number'))
                            <div class="invalid-feedback">{{ $errors->first('last_number') }}</div>
                        @endif
                        <small class="help-block text-muted">{{ trans('cruds.barcode.fields.last_number_helper') }}</small>
                    </div>
                </div>

                <div class="col-lg-12 d-flex justify-content-end flex-wrap mt-2" style="gap: 10px;">
                    <a href="{{ route('admin.barcodes.index') }}" class="btn btn-reset mb-1">
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
