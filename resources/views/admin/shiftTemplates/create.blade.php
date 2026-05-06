@extends('layouts.master')
@section('title')
    Create Shift Template
@endsection
@section('content')
    @component('components.breadcrumb')
        @slot('li_1')
            @lang('translation.appname')
        @endslot
        @slot('title')
            Create Shift Template
        @endslot
    @endcomponent

    <div class="card shadow-sm border-0">
        <div class="card-header bg-success text-white">
            <h5 class="mb-0 text-white"><i class="ri-add-circle-line me-2"></i> Create New Template</h5>
        </div>

        <div class="card-body p-4">
            <form method="POST" action="{{ route('admin.shift-templates.store') }}">
                @csrf
                <div class="row g-4">
                    <div class="col-md-12">
                        <div class="form-group">
                            <label class="required fw-bold text-muted" for="name">Template Name (e.g., Morning, Evening)</label>
                            <div class="input-group">
                                <span class="input-group-text bg-light"><i class="ri-edit-line text-primary"></i></span>
                                <input class="form-control {{ $errors->has('name') ? 'is-invalid' : '' }}" type="text" name="name" id="name" value="{{ old('name', '') }}" required>
                            </div>
                            @if($errors->has('name'))
                                <div class="text-danger small mt-1">{{ $errors->first('name') }}</div>
                            @endif
                        </div>
                    </div>

                    <div class="col-md-6">
                        <div class="form-group">
                            <label class="required fw-bold text-muted" for="start_time">Shift Start Time</label>
                            <div class="input-group">
                                <span class="input-group-text bg-light"><i class="ri-time-line text-success"></i></span>
                                <input class="form-control {{ $errors->has('start_time') ? 'is-invalid' : '' }}" type="time" name="start_time" id="start_time" value="{{ old('start_time') }}" required>
                            </div>
                        </div>
                    </div>

                    <div class="col-md-6">
                        <div class="form-group">
                            <label class="required fw-bold text-muted" for="end_time">Shift End Time</label>
                            <div class="input-group">
                                <span class="input-group-text bg-light"><i class="ri-time-line text-danger"></i></span>
                                <input class="form-control {{ $errors->has('end_time') ? 'is-invalid' : '' }}" type="time" name="end_time" id="end_time" value="{{ old('end_time') }}" required>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="form-group mt-5 text-end">
                    <a href="{{ route('admin.shift-templates.index') }}" class="btn btn-light border px-4 me-2">Cancel</a>
                    <button class="btn btn-success px-5 shadow-sm" type="submit">
                        <i class="ri-save-3-line me-1"></i> Save Template
                    </button>
                </div>
            </form>
        </div>
    </div>
@endsection
