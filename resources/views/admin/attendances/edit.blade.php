@extends('layouts.master')
@section('title')
    @lang('translation.attendances')
@endsection
@section('content')
    @component('components.breadcrumb')
        @slot('li_1')
            @lang('translation.appname')
        @endslot
        @slot('title')
            @lang('translation.attendances')
        @endslot
    @endcomponent

    <style>
        /* Local tweaks for the colored sub-cards on this page only */
        .attendance-sub-card {
            border-radius: 10px;
            padding: 14px 16px 12px;
            background-color: #f8fafc;
            border-left: 3px solid #e2e8f0;
            transition: border-color .15s ease, background-color .15s ease;
        }
        .attendance-sub-card--checkin { border-left-color: #22c55e; background-color: rgba(34, 197, 94, 0.04); }
        .attendance-sub-card--checkout { border-left-color: #ef4444; background-color: rgba(239, 68, 68, 0.04); }
        .attendance-sub-card label { font-weight: 600; margin-bottom: 8px; display: block; }
        .attendance-sub-card--checkin label { color: #16a34a; }
        .attendance-sub-card--checkout label { color: #dc2626; }
        .attendance-sub-card label i { margin-right: 4px; }
    </style>

    <div class="row justify-content-center">
        <div class="col-lg-11 col-xl-10">
            <div class="card modern-filter-card">
                <div class="card-header">
                    <h4 class="card-title mb-0"><i class="ri-edit-box-line"></i> {{ trans('translation.edit') }} {{ trans('translation.attendance') }}</h4>
                </div>

                <div class="card-body">
                    <form method="POST" action="{{ route('admin.attendances.update', [$attendance->id]) }}" enctype="multipart/form-data">
                        @method('PUT')
                        @csrf
                        <div class="row">
                            <div class="col-md-12 mb-3">
                                <label for="driver_id"><i class="ri-steering-2-line"></i> {{ trans('translation.attendance.fields.driver') }}</label>
                                <select class="form-control select2 {{ $errors->has('driver_id') ? 'is-invalid' : '' }}"
                                    name="driver_id" id="driver_id" required data-placeholder="Select driver">
                                    @foreach ($drivers as $id => $entry)
                                        <option value="{{ $id }}"
                                            {{ (old('driver_id') ? old('driver_id') : $attendance->driver->id ?? '') == $id ? 'selected' : '' }}>
                                            {{ $entry }}
                                        </option>
                                    @endforeach
                                </select>
                                @if ($errors->has('driver_id'))
                                    <div class="text-danger small mt-1">{{ $errors->first('driver_id') }}</div>
                                @endif
                            </div>

                            <input type="hidden" name="source" value="manual">

                            <div class="col-md-6 mb-3">
                                <div class="attendance-sub-card attendance-sub-card--checkin">
                                    <label for="checkin_time"><i class="ri-login-circle-line"></i> Check-in Time (وقت الحضور)</label>
                                    <input class="form-control {{ $errors->has('checkin_time') ? 'is-invalid' : '' }}"
                                        type="time" name="checkin_time" id="checkin_time"
                                        value="{{ old('checkin_time', $attendance->checkin_time ? \Carbon\Carbon::parse($attendance->checkin_time)->format('H:i') : '') }}">
                                </div>
                            </div>

                            <div class="col-md-6 mb-3">
                                <div class="attendance-sub-card attendance-sub-card--checkout">
                                    <label for="checkout_time"><i class="ri-logout-circle-r-line"></i> Check-out Time (وقت الانصراف)</label>
                                    <input class="form-control {{ $errors->has('checkout_time') ? 'is-invalid' : '' }}"
                                        type="time" name="checkout_time" id="checkout_time"
                                        value="{{ old('checkout_time', $attendance->checkout_time ? \Carbon\Carbon::parse($attendance->checkout_time)->format('H:i') : '') }}">
                                </div>
                            </div>

                            <div class="col-md-6 mb-3">
                                <label for="delay_minutes"><i class="ri-timer-flash-line" style="color:#f59e0b"></i> Delay Minutes (دقائق التأخير)</label>
                                <div class="input-group">
                                    <input type="number" class="form-control" name="delay_minutes" id="delay_minutes"
                                        value="{{ old('delay_minutes', $attendance->delay_minutes ?? 0) }}" min="0" placeholder="0">
                                    <div class="input-group-append">
                                        <span class="input-group-text">min</span>
                                    </div>
                                </div>
                            </div>

                            <div class="col-md-6 mb-3">
                                <label for="overtime_minutes"><i class="ri-time-line" style="color:#0ea5e9"></i> Overtime (الساعات الإضافية)</label>
                                <div class="input-group">
                                    <input type="number" class="form-control" name="overtime_minutes" id="overtime_minutes"
                                        value="{{ old('overtime_minutes', $attendance->overtime_minutes ?? 0) }}" min="0" placeholder="0">
                                    <div class="input-group-append">
                                        <span class="input-group-text">min</span>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="col-lg-12 d-flex justify-content-end flex-wrap mt-2" style="gap: 10px;">
                            <a href="{{ route('admin.attendances.index') }}" class="btn btn-reset mb-1">
                                {{ trans('global.cancel') }}
                            </a>
                            <button class="btn btn-save mb-1" type="submit">
                                <i class="ri-save-3-line"></i> Update Record
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
@endsection
