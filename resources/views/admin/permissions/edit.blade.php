@extends('layouts.master')
@section('content')
    @php
        $isWeb       = Auth::guard('web')->check();
        $updateRoute = $isWeb ? route('admin.permissions.update', [$permission->id]) : route('admin.client-permissions.update', [$permission->id]);
        $indexRoute  = $isWeb ? route('admin.permissions.index') : route('admin.client-permissions.index');
        $guardName   = $isWeb ? 'web' : 'client_users';
    @endphp

    <div class="card modern-filter-card">
        <div class="card-header">
            <h4 class="card-title mb-0">{{ trans('global.edit') }} {{ trans('cruds.permission.title_singular') }}</h4>
        </div>

        <div class="card-body">
            <form method="POST" action="{{ $updateRoute }}" enctype="multipart/form-data">
                @method('PUT')
                @csrf
                <input type="hidden" name="guard_name" value="{{ $guardName }}">

                <div class="row">
                    <div class="col-lg-6 mb-3">
                        <label class="required" for="name">{{ trans('cruds.permission.fields.name') }}</label>
                        <input class="form-control {{ $errors->has('name') ? 'is-invalid' : '' }}" type="text"
                            name="name" id="name" value="{{ old('name', $permission->name) }}"
                            placeholder="e.g. user_management_access" required>
                        @if ($errors->has('name'))
                            <div class="invalid-feedback">{{ $errors->first('name') }}</div>
                        @endif
                        <small class="help-block text-muted">{{ trans('cruds.permission.fields.title_helper') }}</small>
                    </div>
                </div>

                <div class="col-lg-12 d-flex justify-content-end flex-wrap mt-2" style="gap: 10px;">
                    <a href="{{ $indexRoute }}" class="btn btn-reset mb-1">
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
