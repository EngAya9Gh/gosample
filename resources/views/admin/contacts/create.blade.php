@extends('layouts.admin')
@section('content')
    <div class="card modern-filter-card">
        <div class="card-header">
            <h4 class="card-title mb-0">{{ trans('translation.create') }} {{ trans('cruds.contact.title_singular') }}</h4>
        </div>

        <div class="card-body">
            <form method="POST" action="{{ route('admin.contacts.store') }}" enctype="multipart/form-data">
                @csrf
                <div class="row">
                    <div class="col-lg-6 mb-3">
                        <label for="type">{{ trans('cruds.contact.fields.type') }}</label>
                        <input class="form-control {{ $errors->has('type') ? 'is-invalid' : '' }}" type="text"
                            name="type" id="type" value="{{ old('type', '') }}" placeholder="Contact type">
                        @if ($errors->has('type'))
                            <div class="invalid-feedback">{{ $errors->first('type') }}</div>
                        @endif
                        <small class="help-block text-muted">{{ trans('cruds.contact.fields.type_helper') }}</small>
                    </div>

                    <div class="col-lg-6 mb-3">
                        <label for="email">{{ trans('cruds.contact.fields.email') }}</label>
                        <input class="form-control {{ $errors->has('email') ? 'is-invalid' : '' }}" type="text"
                            name="email" id="email" value="{{ old('email', '') }}" placeholder="name@example.com">
                        @if ($errors->has('email'))
                            <div class="invalid-feedback">{{ $errors->first('email') }}</div>
                        @endif
                        <small class="help-block text-muted">{{ trans('cruds.contact.fields.email_helper') }}</small>
                    </div>
                </div>

                <div class="col-lg-12 d-flex justify-content-end flex-wrap mt-2" style="gap: 10px;">
                    <a href="{{ route('admin.contacts.index') }}" class="btn btn-reset mb-1">
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
