@extends('layouts.admin')
@section('content')
    <div class="card modern-filter-card">
        <div class="card-header">
            <h4 class="card-title mb-0">{{ trans('translation.edit') }} {{ trans('cruds.userAlert.title_singular') }}</h4>
        </div>

        <div class="card-body">
            <form method="POST" action="{{ route('admin.user-alerts.update', [$userAlert->id]) }}" enctype="multipart/form-data">
                @method('PUT')
                @csrf
                <div class="row">
                    <div class="col-lg-12 mb-3">
                        <small class="text-muted">No additional fields to edit on this record.</small>
                    </div>
                </div>

                <div class="col-lg-12 d-flex justify-content-end flex-wrap mt-2" style="gap: 10px;">
                    <a href="{{ route('admin.user-alerts.index') }}" class="btn btn-reset mb-1">
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
