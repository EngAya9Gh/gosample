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

    <div class="row justify-content-center">
        <div class="col-lg-11 col-xl-10">
            <div class="card shadow-sm border-0">
                <div class="card-header bg-warning text-white py-3">
                    <h5 class="mb-0 text-white"><i class="ri-edit-box-line mr-2"></i> {{ trans('translation.edit') }} {{ trans('translation.attendance') }}</h5>
                </div>

                <div class="card-body p-4">
                    <form method="POST" action="{{ route('admin.attendances.update', [$attendance->id]) }}" enctype="multipart/form-data">
                        @method('PUT')
                        @csrf
                        <div class="row">
                            <div class="col-md-12 mb-4">
                                <div class="form-group">
                                    <label for="driver_id" class="fw-bold text-muted"><i class="ri-steering-2-line mr-1"></i> {{ trans('translation.attendance.fields.driver') }}</label>
                                    <select class="form-control select2 {{ $errors->has('driver_id') ? 'is-invalid' : '' }}" name="driver_id" id="driver_id" required>
                                        @foreach ($drivers as $id => $entry)
                                            <option value="{{ $id }}" {{ (old('driver_id') ? old('driver_id') : $attendance->driver->id ?? '') == $id ? 'selected' : '' }}>
                                                {{ $entry }}
                                            </option>
                                        @endforeach
                                    </select>
                                    @if ($errors->has('driver_id'))
                                        <div class="text-danger small mt-1">{{ $errors->first('driver_id') }}</div>
                                    @endif
                                </div>
                            </div>

                            <input type="hidden" name="source" value="manual">

                            <div class="col-md-6 mb-4">
                                <div class="card border-0 rounded-lg h-100 p-3" style="background-color: rgba(10, 179, 156, 0.05); border-left: 4px solid #0ab39c !important;">
                                    <label for="checkin_time" class="fw-bold text-success"><i class="ri-login-circle-line mr-1"></i> Check-in Time (وقت الحضور)</label>
                                    <input class="form-control border-success {{ $errors->has('checkin_time') ? 'is-invalid' : '' }}"
                                        type="time" name="checkin_time" id="checkin_time" value="{{ old('checkin_time', $attendance->checkin_time ? \Carbon\Carbon::parse($attendance->checkin_time)->format('H:i') : '') }}">
                                </div>
                            </div>

                            <div class="col-md-6 mb-4">
                                <div class="card border-0 rounded-lg h-100 p-3" style="background-color: rgba(240, 101, 72, 0.05); border-left: 4px solid #f06548 !important;">
                                    <label for="checkout_time" class="fw-bold text-danger"><i class="ri-logout-circle-r-line mr-1"></i> Check-out Time (وقت الانصراف)</label>
                                    <input class="form-control border-danger {{ $errors->has('checkout_time') ? 'is-invalid' : '' }}"
                                        type="time" name="checkout_time" id="checkout_time" value="{{ old('checkout_time', $attendance->checkout_time ? \Carbon\Carbon::parse($attendance->checkout_time)->format('H:i') : '') }}">
                                </div>
                            </div>
                            
                            <div class="col-md-6 mb-4">
                                <div class="form-group">
                                    <label for="delay_minutes" class="fw-bold text-warning"><i class="ri-timer-flash-line mr-1"></i> Delay Minutes (دقائق التأخير)</label>
                                    <div class="input-group">
                                        <input type="number" class="form-control" name="delay_minutes" value="{{ old('delay_minutes', $attendance->delay_minutes ?? 0) }}" min="0">
                                        <div class="input-group-append">
                                            <span class="input-group-text bg-light">min</span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="col-md-6 mb-4">
                                <div class="form-group">
                                    <label for="overtime_minutes" class="fw-bold text-info"><i class="ri-time-line mr-1"></i> Overtime (الساعات الإضافية)</label>
                                    <div class="input-group">
                                        <input type="number" class="form-control" name="overtime_minutes" value="{{ old('overtime_minutes', $attendance->overtime_minutes ?? 0) }}" min="0">
                                        <div class="input-group-append">
                                            <span class="input-group-text bg-light">min</span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="form-group mt-4 text-center">
                            <a href="{{ route('admin.attendances.index') }}" class="btn btn-light border px-5 mr-3">Cancel</a>
                            <button class="btn btn-warning text-white px-5 shadow-sm" type="submit">
                                <i class="ri-save-3-line mr-1"></i> Update Record
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
@endsection
