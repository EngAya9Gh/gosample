@extends('layouts.master')
@section('title')
    @lang('translation.drivers')
@endsection
@section('content')
    @component('components.breadcrumb')
        @slot('li_1')
            @lang('translation.appname')
        @endslot
        @slot('title')
            @lang('translation.drivers')
        @endslot
    @endcomponent

    @section('css')
        <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">
    @endsection

    <style>
        /* Section divider for the Shift & Schedule block */
        .shift-section {
            background: rgba(13, 148, 136, 0.04);
            border: 1px solid rgba(13, 148, 136, 0.18);
            border-radius: 12px;
            padding: 18px 18px 4px;
            margin: 10px 0 18px;
        }
        .shift-section__title {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            color: #0d9488;
            font-weight: 600;
            font-size: 0.95rem;
            margin: 0 0 14px;
        }
        .shift-section__title i { font-size: 1.05rem; }

        /* Hint chip next to required-hours input */
        .input-suffix {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            height: 44px;
            padding: 0 12px;
            background: #f1f5f9;
            color: #475569;
            font-size: 0.82rem;
            font-weight: 500;
            border: 1.5px solid #e2e8f0;
            border-left: 0;
            border-radius: 0 10px 10px 0;
            white-space: nowrap;
        }
        .input-suffix-wrap {
            display: flex;
            align-items: stretch;
        }
        .input-suffix-wrap .form-control {
            border-top-right-radius: 0 !important;
            border-bottom-right-radius: 0 !important;
        }
    </style>

    <div class="card modern-filter-card">
        <div class="card-header">
            <h4 class="card-title mb-0">
                <i class="ri-user-add-line"></i> {{ trans('translation.create') }} {{ trans('translation.driver') }}
            </h4>
        </div>

        <div class="card-body">
            <form method="POST" action="{{ route('admin.drivers.store') }}" enctype="multipart/form-data">
                @csrf
                <div class="row">
                    {{-- Identity --}}
                    <div class="col-lg-6 mb-3">
                        <label class="required" for="name">{{ trans('translation.driver.fields.name') }}</label>
                        <input class="form-control {{ $errors->has('name') ? 'is-invalid' : '' }}" type="text"
                            name="name" id="name" value="{{ old('name', '') }}"
                            placeholder="Driver full name" required>
                        @if ($errors->has('name'))
                            <div class="invalid-feedback">{{ $errors->first('name') }}</div>
                        @endif
                    </div>

                    <div class="col-lg-6 mb-3">
                        <label class="required" for="password">{{ trans('translation.driver.fields.password') }}</label>
                        <input class="form-control {{ $errors->has('password') ? 'is-invalid' : '' }}" type="password"
                            name="password" id="password" placeholder="Minimum 6 characters" required>
                        @if ($errors->has('password'))
                            <div class="invalid-feedback">{{ $errors->first('password') }}</div>
                        @endif
                    </div>

                    <div class="col-lg-6 mb-3">
                        <label class="required" for="username">{{ trans('translation.driver.fields.username') }}</label>
                        <input class="form-control {{ $errors->has('username') ? 'is-invalid' : '' }}" type="text"
                            name="username" id="username" value="{{ old('username', '') }}"
                            placeholder="Login username" required>
                        @if ($errors->has('username'))
                            <div class="invalid-feedback">{{ $errors->first('username') }}</div>
                        @endif
                    </div>

                    <div class="col-lg-6 mb-3">
                        <label class="required" for="mobile">{{ trans('translation.driver.fields.mobile') }}</label>
                        <input class="form-control {{ $errors->has('mobile') ? 'is-invalid' : '' }}" type="text"
                            name="mobile" id="mobile" value="{{ old('mobile', '') }}"
                            placeholder="05XXXXXXXX" required>
                        @if ($errors->has('mobile'))
                            <div class="invalid-feedback">{{ $errors->first('mobile') }}</div>
                        @endif
                    </div>

                    <div class="col-lg-6 mb-3">
                        <label for="email">{{ trans('translation.driver.fields.email') }}</label>
                        <input class="form-control {{ $errors->has('email') ? 'is-invalid' : '' }}" type="email"
                            name="email" id="email" value="{{ old('email', '') }}"
                            placeholder="driver@example.com">
                        @if ($errors->has('email'))
                            <div class="invalid-feedback">{{ $errors->first('email') }}</div>
                        @endif
                    </div>

                    <div class="col-lg-6 mb-3">
                        <label class="required" for="language">{{ trans('translation.driver.fields.language') }}</label>
                        <select class="form-control {{ $errors->has('language') ? 'is-invalid' : '' }}" name="language"
                            id="language" required>
                            <option value="en" {{ old('language', 'en') == 'en' ? 'selected' : '' }}>English</option>
                            <option value="ar" {{ old('language', 'en') == 'ar' ? 'selected' : '' }}>Arabic</option>
                        </select>
                        @if ($errors->has('language'))
                            <div class="invalid-feedback">{{ $errors->first('language') }}</div>
                        @endif
                    </div>

                    <div class="col-lg-6 mb-3">
                        <label class="required" for="status">{{ trans('translation.driver.fields.status') }}</label>
                        <select class="form-control {{ $errors->has('status') ? 'is-invalid' : '' }}" name="status"
                            id="status" required>
                            @foreach (App\Models\Driver::STATUS_SELECT as $key => $label)
                                <option value="{{ $key }}" {{ old('status', '1') == (string) $key ? 'selected' : '' }}>
                                    {{ $label }}
                                </option>
                            @endforeach
                        </select>
                        @if ($errors->has('status'))
                            <div class="invalid-feedback">{{ $errors->first('status') }}</div>
                        @endif
                    </div>
                </div>

                {{-- Shift & Schedule --}}
                <div class="shift-section">
                    <div class="shift-section__title">
                        <i class="ri-time-line"></i> Shift &amp; Schedule
                        <span style="font-weight:400; color:#64748b; font-size:.85rem;">(الدوام والورديات)</span>
                    </div>

                    <div class="row">
                        <div class="col-lg-4 mb-3">
                            <label class="required" for="employment_type">Employment Type</label>
                            <select class="form-control" name="employment_type" id="employment_type" required>
                                <option value="full_time" {{ old('employment_type', 'full_time') == 'full_time' ? 'selected' : '' }}>Full Time</option>
                                <option value="part_time" {{ old('employment_type', 'full_time') == 'part_time' ? 'selected' : '' }}>Part Time</option>
                            </select>
                        </div>

                        <div class="col-lg-4 mb-3">
                            <label class="required" for="total_working_hours">Required Hours</label>
                            <div class="input-suffix-wrap">
                                <input class="form-control" type="number" name="total_working_hours"
                                    id="total_working_hours" value="{{ old('total_working_hours', 8) }}" required>
                                <span class="input-suffix">hrs/day</span>
                            </div>
                        </div>

                        <div class="col-lg-4 mb-3">
                            <label class="required" for="shift_count">Shift Count</label>
                            <select class="form-control" name="shift_count" id="shift_count" required>
                                <option value="1" {{ old('shift_count', '1') == '1' ? 'selected' : '' }}>1 Shift</option>
                                <option value="2" {{ old('shift_count', '1') == '2' ? 'selected' : '' }}>2 Shifts</option>
                                <option value="3" {{ old('shift_count', '1') == '3' ? 'selected' : '' }}>3 Shifts</option>
                            </select>
                        </div>

                        <div class="col-lg-12 mb-3">
                            <label for="shift_template_selector">
                                <i class="ri-magic-line" style="color:#0d9488"></i> Quick Shift Selection
                            </label>
                            <select class="form-control" id="shift_template_selector">
                                <option value="">— Select Template or Enter Manually —</option>
                                @foreach ($shiftTemplates as $template)
                                    <option value="{{ $template->id }}" data-start="{{ $template->start_time }}" data-end="{{ $template->end_time }}">
                                        {{ $template->name }} ({{ \Carbon\Carbon::parse($template->start_time)->format('H:i') }} - {{ \Carbon\Carbon::parse($template->end_time)->format('H:i') }})
                                    </option>
                                @endforeach
                            </select>
                        </div>

                        {{-- Shift 1 (always visible) --}}
                        <div class="col-lg-6 mb-3">
                            <label class="required">Shift 1 Start Time</label>
                            <input type="text" class="form-control driver-time" name="working_hours_start"
                                id="working_hours_start" value="{{ old('working_hours_start') }}"
                                placeholder="Pick time" autocomplete="off" required>
                        </div>
                        <div class="col-lg-6 mb-3">
                            <label class="required">Shift 1 End Time</label>
                            <input type="text" class="form-control driver-time" name="working_hours_end"
                                id="working_hours_end" value="{{ old('working_hours_end') }}"
                                placeholder="Pick time" autocomplete="off" required>
                        </div>

                        {{-- Shift 2 --}}
                        <div class="col-lg-6 mb-3 second-shift-fields" style="{{ old('shift_count') >= 2 ? '' : 'display:none' }}">
                            <label>Shift 2 Start Time</label>
                            <input type="text" class="form-control driver-time" name="second_shift_working_hours_start"
                                value="{{ old('second_shift_working_hours_start') }}"
                                placeholder="Pick time" autocomplete="off">
                        </div>
                        <div class="col-lg-6 mb-3 second-shift-fields" style="{{ old('shift_count') >= 2 ? '' : 'display:none' }}">
                            <label>Shift 2 End Time</label>
                            <input type="text" class="form-control driver-time" name="second_shift_working_hours_end"
                                value="{{ old('second_shift_working_hours_end') }}"
                                placeholder="Pick time" autocomplete="off">
                        </div>

                        {{-- Shift 3 --}}
                        <div class="col-lg-6 mb-3 third-shift-fields" style="{{ old('shift_count') >= 3 ? '' : 'display:none' }}">
                            <label>Shift 3 Start Time</label>
                            <input type="text" class="form-control driver-time" name="third_shift_working_hours_start"
                                value="{{ old('third_shift_working_hours_start') }}"
                                placeholder="Pick time" autocomplete="off">
                        </div>
                        <div class="col-lg-6 mb-3 third-shift-fields" style="{{ old('shift_count') >= 3 ? '' : 'display:none' }}">
                            <label>Shift 3 End Time</label>
                            <input type="text" class="form-control driver-time" name="third_shift_working_hours_end"
                                value="{{ old('third_shift_working_hours_end') }}"
                                placeholder="Pick time" autocomplete="off">
                        </div>
                    </div>
                </div>

                <div class="row">
                    <div class="col-lg-6 mb-3">
                        <label for="zone_id">{{ trans('translation.driver.fields.zone') }}</label>
                        <select class="form-control select2" name="zone_id" id="zone_id" required
                            data-placeholder="Select zone">
                            @foreach ($zones as $id => $entry)
                                <option value="{{ $id }}" {{ old('zone_id') == $id ? 'selected' : '' }}>{{ $entry }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div class="col-lg-6 mb-3">
                        <label class="required" for="national_id">{{ trans('translation.driver.fields.national_id') }}</label>
                        <input class="form-control" type="text" name="national_id" id="national_id"
                            value="{{ old('national_id', '') }}" placeholder="National ID number" required>
                    </div>
                </div>

                <div class="col-lg-12 d-flex justify-content-end flex-wrap mt-2" style="gap: 10px;">
                    <a href="{{ route('admin.drivers.index') }}" class="btn btn-reset mb-1">
                        {{ trans('global.cancel') }}
                    </a>
                    <button class="btn btn-save mb-1" type="submit">
                        <i class="ri-save-3-line"></i> Save New Driver
                    </button>
                </div>
            </form>
        </div>
    </div>
@endsection

@section('scripts')
    @parent
    <script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>
    <script>
        $(document).ready(function () {
            // Select2 for zone
            $('.select2').each(function () {
                $(this).select2({
                    placeholder: $(this).data('placeholder') || 'Please select',
                    allowClear: false
                });
            });

            // Time pickers — 12-hour AM/PM display, 24-hour HH:MM submitted (backend contract).
            // Hour input is set wide via altInputClass so it inherits the form-control style.
            flatpickr('.driver-time', {
                enableTime: true,
                noCalendar: true,
                dateFormat: 'H:i',
                altInput: true,
                altFormat: 'h:i K',
                time_24hr: false,
                allowInput: false,
                minuteIncrement: 5
            });

            // Quick Shift Selection → fills Shift 1 start/end (preserving backend HH:MM contract)
            $('#shift_template_selector').on('change', function () {
                const $opt = $(this).find(':selected');
                const start = $opt.data('start');
                const end   = $opt.data('end');
                if (start && end) {
                    const s = String(start).substring(0, 5);
                    const e = String(end).substring(0, 5);
                    if (document.getElementById('working_hours_start')._flatpickr) {
                        document.getElementById('working_hours_start')._flatpickr.setDate(s, true);
                    } else {
                        $('#working_hours_start').val(s);
                    }
                    if (document.getElementById('working_hours_end')._flatpickr) {
                        document.getElementById('working_hours_end')._flatpickr.setDate(e, true);
                    } else {
                        $('#working_hours_end').val(e);
                    }
                }
            });

            // Toggle Shift 2 / 3 visibility
            $('#shift_count').on('change', function () {
                const count = parseInt($(this).val(), 10) || 1;
                $('.second-shift-fields').toggle(count >= 2);
                $('.third-shift-fields').toggle(count >= 3);
            });
        });
    </script>
@endsection
