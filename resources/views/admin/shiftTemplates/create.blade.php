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

    <div class="card modern-filter-card">
        <div class="card-header">
            <h4 class="card-title mb-0"><i class="ri-add-circle-line"></i> Create New Template</h4>
        </div>

        <div class="card-body">
            <form method="POST" action="{{ route('admin.shift-templates.store') }}">
                @csrf
                <div class="row">
                    <div class="col-md-12 mb-3">
                        <label class="required" for="name">Template Name <small class="text-muted">(e.g., Morning, Evening)</small></label>
                        <div class="input-group">
                            <span class="input-group-text"><i class="ri-edit-line" style="color:#0d9488"></i></span>
                            <input class="form-control {{ $errors->has('name') ? 'is-invalid' : '' }}" type="text"
                                name="name" id="name" value="{{ old('name', '') }}" required placeholder="e.g., Morning Shift">
                        </div>
                        @if ($errors->has('name'))
                            <div class="text-danger small mt-1">{{ $errors->first('name') }}</div>
                        @endif
                    </div>

                    <div class="col-md-6 mb-3">
                        <label class="required" for="start_time">Shift Start Time</label>
                        <div class="input-group">
                            <span class="input-group-text"><i class="ri-time-line" style="color:#22c55e"></i></span>
                            <input class="form-control {{ $errors->has('start_time') ? 'is-invalid' : '' }}" type="time"
                                name="start_time" id="start_time" value="{{ old('start_time') }}" required>
                        </div>
                        @if ($errors->has('start_time'))
                            <div class="text-danger small mt-1">{{ $errors->first('start_time') }}</div>
                        @endif
                    </div>

                    <div class="col-md-6 mb-3">
                        <label class="required" for="end_time">Shift End Time</label>
                        <div class="input-group">
                            <span class="input-group-text"><i class="ri-time-line" style="color:#ef4444"></i></span>
                            <input class="form-control {{ $errors->has('end_time') ? 'is-invalid' : '' }}" type="time"
                                name="end_time" id="end_time" value="{{ old('end_time') }}" required>
                        </div>
                        @if ($errors->has('end_time'))
                            <div class="text-danger small mt-1">{{ $errors->first('end_time') }}</div>
                        @endif
                    </div>
                </div>

                <div class="col-lg-12">
                    <a href="{{ route('admin.shift-templates.index') }}" class="btn btn-reset">
                        Cancel
                    </a>
                    <button class="btn btn-search" type="submit">
                        <i class="ri-save-3-line"></i> Save Template
                    </button>
                </div>
            </form>
        </div>
    </div>
@endsection
