@extends('layouts.master')
@section('title')
    @lang('translation.terms')
@endsection
@section('content')
    @component('components.breadcrumb')
        @slot('li_1')
            @lang('translation.appname')
        @endslot
        @slot('title')
            @lang('translation.terms')
        @endslot
    @endcomponent

    <div class="card modern-filter-card">
        <div class="card-header">
            <h4 class="card-title mb-0">{{ trans('translation.edit') }} {{ trans('translation.term.title_singular') }}</h4>
        </div>

        <div class="card-body">
            <form method="POST" action="{{ route('admin.terms.update', [$term->id]) }}" enctype="multipart/form-data">
                @method('PUT')
                @csrf
                <div class="row">
                    <div class="col-lg-6 mb-3">
                        <label for="english_text">{{ trans('translation.term.fields.english_text') }}</label>
                        <textarea class="form-control {{ $errors->has('english_text') ? 'is-invalid' : '' }}"
                            name="english_text" id="english_text" rows="5" placeholder="English term text">{{ old('english_text', $term->english_text) }}</textarea>
                        @if ($errors->has('english_text'))
                            <div class="invalid-feedback">{{ $errors->first('english_text') }}</div>
                        @endif
                        <small class="help-block text-muted">{{ trans('translation.term.fields.english_text_helper') }}</small>
                    </div>

                    <div class="col-lg-6 mb-3">
                        <label for="arabic_text">{{ trans('translation.term.fields.arabic_text') }}</label>
                        <textarea class="form-control {{ $errors->has('arabic_text') ? 'is-invalid' : '' }}"
                            name="arabic_text" id="arabic_text" rows="5" placeholder="النص باللغة العربية" dir="rtl">{{ old('arabic_text', $term->arabic_text) }}</textarea>
                        @if ($errors->has('arabic_text'))
                            <div class="invalid-feedback">{{ $errors->first('arabic_text') }}</div>
                        @endif
                        <small class="help-block text-muted">{{ trans('translation.term.fields.arabic_text_helper') }}</small>
                    </div>
                </div>

                <div class="col-lg-12 d-flex justify-content-end flex-wrap mt-2" style="gap: 10px;">
                    <a href="{{ route('admin.terms.index') }}" class="btn btn-reset mb-1">
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
