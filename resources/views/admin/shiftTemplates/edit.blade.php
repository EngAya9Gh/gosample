@extends('layouts.master')
@section('title')
    Edit Shift Template
@endsection
@section('content')
    @component('components.breadcrumb')
        @slot('li_1')
            @lang('translation.appname')
        @endslot
        @slot('title')
            Edit Shift Template
        @endslot
    @endcomponent

    <div class="card modern-filter-card">
        <div class="card-header">
            <h4 class="card-title mb-0"><i class="ri-edit-box-line"></i> Update Template Details</h4>
        </div>

        <div class="card-body">
            <form method="POST" action="{{ route('admin.shift-templates.update', [$shiftTemplate->id]) }}">
                @method('PUT')
                @csrf
                <div class="row">
                    <div class="col-md-12 mb-3">
                        <label class="required" for="name">Template Name <small class="text-muted">(e.g., Morning, Evening)</small></label>
                        <div class="input-group">
                            <span class="input-group-text"><i class="ri-edit-line" style="color:#0d9488"></i></span>
                            <input class="form-control {{ $errors->has('name') ? 'is-invalid' : '' }}" type="text"
                                name="name" id="name" value="{{ old('name', $shiftTemplate->name) }}" required
                                placeholder="e.g., Morning Shift">
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
                                name="start_time" id="start_time"
                                value="{{ old('start_time', \Carbon\Carbon::parse($shiftTemplate->start_time)->format('H:i')) }}"
                                required>
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
                                name="end_time" id="end_time"
                                value="{{ old('end_time', \Carbon\Carbon::parse($shiftTemplate->end_time)->format('H:i')) }}"
                                required>
                        </div>
                        @if ($errors->has('end_time'))
                            <div class="text-danger small mt-1">{{ $errors->first('end_time') }}</div>
                        @endif
                    </div>
                </div>

                <div class="col-lg-12 d-flex justify-content-end flex-wrap mt-2" style="gap: 10px;">
                    <a href="{{ route('admin.shift-templates.index') }}" class="btn btn-reset mb-1">
                        {{ trans('global.cancel') }}
                    </a>
                    <button class="btn btn-save mb-1" type="submit">
                        <i class="ri-save-3-line"></i> Update Template
                    </button>
                </div>
            </form>
        </div>
    </div>
@endsection
