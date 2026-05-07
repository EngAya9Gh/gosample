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
                <div class="card-header bg-primary text-white py-3">
                    <h5 class="mb-0 text-white"><i class="ri-calendar-check-line mr-2"></i> {{ trans('translation.create') }} {{ trans('translation.attendance') }}</h5>
                </div>

                <div class="card-body p-4">
                    <form method="POST" action="{{ route('admin.attendances.store') }}" enctype="multipart/form-data">
                        @csrf
                        <div class="row">
                            <div class="col-md-6 mb-4">
                                <div class="form-group">
                                    <label for="driver_id" class="fw-bold text-muted"><i class="ri-steering-2-line mr-1"></i> {{ trans('translation.attendance.fields.driver') }}</label>
                                    <select class="form-control select2 {{ $errors->has('driver_id') ? 'is-invalid' : '' }}" name="driver_id" id="driver_id" required>
                                        <option value="" disabled selected>{{ trans('translation.pleaseSelect') }}</option>
                                        @foreach ($drivers as $id => $entry)
                                            <option value="{{ $id }}" {{ old('driver_id') == $id ? 'selected' : '' }}>
                                                {{ $entry }}
                                            </option>
                                        @endforeach
                                    </select>
                                    @if ($errors->has('driver_id'))
                                        <div class="text-danger small mt-1">{{ $errors->first('driver_id') }}</div>
                                    @endif
                                </div>
                            </div>

                            <div class="col-md-6 mb-4">
                                <div class="form-group">
                                    <label for="shift_id" class="fw-bold text-muted"><i class="ri-history-line mr-1"></i> الوردية (Shift)</label>
                                    <select class="form-control select2 {{ $errors->has('shift_id') ? 'is-invalid' : '' }}" name="shift_id" id="shift_id">
                                        <option value="">{{ trans('translation.pleaseSelect') }}</option>
                                    </select>
                                    <small class="text-muted">اختر الوردية المحددة لهذا السائق اليوم.</small>
                                </div>
                            </div>

                            <input type="hidden" name="source" value="manual">

                            <div class="col-md-6 mb-4">
                                <div class="card bg-light-success border-0 rounded-lg h-100 p-3" style="background-color: rgba(10, 179, 156, 0.05); border-left: 4px solid #0ab39c !important;">
                                    <label for="checkin_time" class="fw-bold text-success"><i class="ri-login-circle-line mr-1"></i> Check-in Time (وقت الحضور)</label>
                                    <input class="form-control border-success {{ $errors->has('checkin_time') ? 'is-invalid' : '' }}"
                                        type="time" name="checkin_time" id="checkin_time" value="{{ old('checkin_time') }}">
                                    <small class="text-muted d-block mt-2">اتركه فارغاً إذا لم يحضر السائق بعد.</small>
                                </div>
                            </div>

                            <div class="col-md-6 mb-4">
                                <div class="card bg-light-danger border-0 rounded-lg h-100 p-3" style="background-color: rgba(240, 101, 72, 0.05); border-left: 4px solid #f06548 !important;">
                                    <label for="checkout_time" class="fw-bold text-danger"><i class="ri-logout-circle-r-line mr-1"></i> Check-out Time (وقت الانصراف)</label>
                                    <input class="form-control border-danger {{ $errors->has('checkout_time') ? 'is-invalid' : '' }}"
                                        type="time" name="checkout_time" id="checkout_time" value="{{ old('checkout_time') }}">
                                    <small class="text-muted d-block mt-2">اتركه فارغاً إذا لم ينصرف السائق بعد.</small>
                                </div>
                            </div>
                            
                            <div class="col-md-6 mb-4">
                                <div class="form-group">
                                    <label for="delay_minutes" class="fw-bold text-warning"><i class="ri-timer-flash-line mr-1"></i> Delay Minutes (دقائق التأخير)</label>
                                    <div class="input-group">
                                        <input type="number" class="form-control" name="delay_minutes" value="{{ old('delay_minutes', 0) }}" min="0">
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
                                        <input type="number" class="form-control" name="overtime_minutes" value="{{ old('overtime_minutes', 0) }}" min="0">
                                        <div class="input-group-append">
                                            <span class="input-group-text bg-light">min</span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="form-group mt-4 text-center">
                            <a href="{{ route('admin.attendances.index') }}" class="btn btn-light border px-5 mr-3">Cancel</a>
                            <button class="btn btn-primary px-5 shadow-sm" type="submit">
                                <i class="ri-save-3-line mr-1"></i> Save Attendance
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
@endsection

@section('scripts')
<script>
    $(document).ready(function() {
        $('#driver_id').on('change', function() {
            let driverId = $(this).val();
            if (driverId) {
                // جلب الورديات الخاصة بالسائق عبر AJAX
                $.get(`/admin/drivers/${driverId}/get-shifts`, function(data) {
                    let shiftDropdown = $('#shift_id');
                    shiftDropdown.empty();
                    shiftDropdown.append('<option value="">{{ trans('translation.pleaseSelect') }}</option>');
                    
                    if (data.length > 0) {
                        $.each(data, function(index, shift) {
                            shiftDropdown.append(`<option value="${shift.id}">${shift.shift_number ? 'Shift #' + shift.shift_number : 'Custom Shift'} (${shift.start_time} - ${shift.end_time})</option>`);
                        });
                    } else {
                        // في حال لا يوجد ورديات مسجلة في الجدول المنفصل، نعطي خياراً فارغاً للاعتماد على بيانات السائق الأساسية
                        shiftDropdown.append('<option value="">Default Driver Schedule</option>');
                    }
                });
            }
        });
    });
</script>
@endsection
