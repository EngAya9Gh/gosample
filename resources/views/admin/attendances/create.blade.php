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
                    <h4 class="card-title mb-0"><i class="ri-calendar-check-line"></i> {{ trans('translation.create') }} {{ trans('translation.attendance') }}</h4>
                </div>

                <div class="card-body">
                    <form method="POST" action="{{ route('admin.attendances.store') }}" enctype="multipart/form-data">
                        @csrf
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="driver_id"><i class="ri-steering-2-line"></i> {{ trans('translation.attendance.fields.driver') }}</label>
                                <select class="form-control select2 {{ $errors->has('driver_id') ? 'is-invalid' : '' }}"
                                    name="driver_id" id="driver_id" required data-placeholder="Select driver">
                                    <option value=""></option>
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

                            <div class="col-md-6 mb-3">
                                <label for="shift_id"><i class="ri-history-line"></i> الوردية (Shift)</label>
                                <select class="form-control select2 {{ $errors->has('shift_id') ? 'is-invalid' : '' }}"
                                    name="shift_id" id="shift_id" data-placeholder="اختر السائق أولاً (Select a driver first)" disabled>
                                    <option value=""></option>
                                </select>
                                <small class="help-block" id="shift_hint">اختر السائق أولاً لعرض الورديات المتاحة.</small>
                            </div>

                            <input type="hidden" name="source" value="manual">

                            <div class="col-md-6 mb-3">
                                <div class="attendance-sub-card attendance-sub-card--checkin">
                                    <label for="checkin_time"><i class="ri-login-circle-line"></i> Check-in Time (وقت الحضور)</label>
                                    <input class="form-control {{ $errors->has('checkin_time') ? 'is-invalid' : '' }}"
                                        type="time" name="checkin_time" id="checkin_time" value="{{ old('checkin_time') }}">
                                    <small class="help-block">اتركه فارغاً إذا لم يحضر السائق بعد.</small>
                                </div>
                            </div>

                            <div class="col-md-6 mb-3">
                                <div class="attendance-sub-card attendance-sub-card--checkout">
                                    <label for="checkout_time"><i class="ri-logout-circle-r-line"></i> Check-out Time (وقت الانصراف)</label>
                                    <input class="form-control {{ $errors->has('checkout_time') ? 'is-invalid' : '' }}"
                                        type="time" name="checkout_time" id="checkout_time" value="{{ old('checkout_time') }}">
                                    <small class="help-block">اتركه فارغاً إذا لم ينصرف السائق بعد.</small>
                                </div>
                            </div>

                            <div class="col-md-6 mb-3">
                                <label for="delay_minutes"><i class="ri-timer-flash-line" style="color:#f59e0b"></i> Delay Minutes (دقائق التأخير)</label>
                                <div class="input-group">
                                    <input type="number" class="form-control" name="delay_minutes" id="delay_minutes"
                                        value="{{ old('delay_minutes', 0) }}" min="0" placeholder="0">
                                    <div class="input-group-append">
                                        <span class="input-group-text">min</span>
                                    </div>
                                </div>
                            </div>

                            <div class="col-md-6 mb-3">
                                <label for="overtime_minutes"><i class="ri-time-line" style="color:#0ea5e9"></i> Overtime (الساعات الإضافية)</label>
                                <div class="input-group">
                                    <input type="number" class="form-control" name="overtime_minutes" id="overtime_minutes"
                                        value="{{ old('overtime_minutes', 0) }}" min="0" placeholder="0">
                                    <div class="input-group-append">
                                        <span class="input-group-text">min</span>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="col-lg-12">
                            <a href="{{ route('admin.attendances.index') }}" class="btn btn-reset">
                                Cancel
                            </a>
                            <button class="btn btn-save" type="submit">
                                <i class="ri-save-3-line"></i> Save Attendance
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
        const $shift = $('#shift_id');
        const $hint  = $('#shift_hint');

        function resetShiftDropdown(message, disable) {
            $shift.prop('disabled', !!disable);
            $shift.empty().append('<option value=""></option>');
            // Re-sync Select2 with the new (empty) option set
            $shift.trigger('change.select2');
            if (message) $hint.text(message);
        }

        $('#driver_id').on('change', function() {
            const driverId = $(this).val();

            if (!driverId) {
                resetShiftDropdown('اختر السائق أولاً لعرض الورديات المتاحة.', true);
                return;
            }

            // Loading state while AJAX runs
            resetShiftDropdown('...جاري تحميل الورديات', true);

            $.get(`/admin/drivers/${driverId}/get-shifts`)
                .done(function(data) {
                    $shift.empty().append('<option value=""></option>');

                    if (data && data.length > 0) {
                        $.each(data, function(_, shift) {
                            const label = shift.shift_number
                                ? 'Shift #' + shift.shift_number
                                : 'Custom Shift';
                            $shift.append(
                                `<option value="${shift.id}">${label} (${shift.start_time} - ${shift.end_time})</option>`
                            );
                        });
                        $shift.prop('disabled', false);
                        $hint.text('اختر الوردية المحددة لهذا السائق اليوم.');
                    } else {
                        $shift.prop('disabled', true);
                        $hint.text('لا توجد ورديات مسجلة لهذا السائق.');
                    }

                    // CRITICAL: tell Select2 the underlying <option> list changed
                    $shift.trigger('change.select2');
                })
                .fail(function() {
                    resetShiftDropdown('تعذر تحميل الورديات. حاول مرة أخرى.', true);
                });
        });
    });
</script>
@endsection
