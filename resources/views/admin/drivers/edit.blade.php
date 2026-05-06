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

    <div class="row justify-content-center">
        <div class="col-lg-11 col-xl-10"> <!-- Reduced width to ensure it doesn't hit sidebar -->
            <div class="card shadow-sm border-0">
                <div class="card-header bg-warning text-white py-3">
                    <h5 class="mb-0 text-white"><i class="ri-user-settings-line mr-2"></i> {{ trans('translation.edit') }} {{ trans('translation.driver') }}</h5>
                </div>

                <div class="card-body p-4">
                    <form method="POST" action="{{ route('admin.drivers.update', [$driver->id]) }}" enctype="multipart/form-data">
                        @method('PUT')
                        @csrf
                        
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <div class="form-group">
                                    <label class="required fw-bold text-muted" for="name">{{ trans('translation.driver.fields.name') }}</label>
                                    <div class="input-group">
                                        <div class="input-group-prepend">
                                            <span class="input-group-text bg-light"><i class="ri-user-line text-primary"></i></span>
                                        </div>
                                        <input class="form-control {{ $errors->has('name') ? 'is-invalid' : '' }}" type="text"
                                            name="name" id="name" value="{{ old('name', $driver->name) }}" required>
                                    </div>
                                    @if ($errors->has('name'))
                                        <div class="text-danger small mt-1">{{ $errors->first('name') }}</div>
                                    @endif
                                </div>
                            </div>
                            
                            <div class="col-md-6 mb-3">
                                <div class="form-group">
                                    <label class="fw-bold text-muted" for="password">{{ trans('translation.driver.fields.password') }}</label>
                                    <div class="input-group">
                                        <div class="input-group-prepend">
                                            <span class="input-group-text bg-light"><i class="ri-lock-password-line text-primary"></i></span>
                                        </div>
                                        <input class="form-control {{ $errors->has('password') ? 'is-invalid' : '' }}" type="password"
                                            name="password" id="password" placeholder="Leave empty to keep current">
                                    </div>
                                    @if ($errors->has('password'))
                                        <div class="text-danger small mt-1">{{ $errors->first('password') }}</div>
                                    @endif
                                </div>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <div class="form-group">
                                    <label class="required fw-bold text-muted" for="username">{{ trans('translation.driver.fields.username') }}</label>
                                    <div class="input-group">
                                        <div class="input-group-prepend">
                                            <span class="input-group-text bg-light"><i class="ri-at-line text-primary"></i></span>
                                        </div>
                                        <input class="form-control {{ $errors->has('username') ? 'is-invalid' : '' }}" type="text"
                                            name="username" id="username" value="{{ old('username', $driver->username) }}" required>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="col-md-6 mb-3">
                                <div class="form-group">
                                    <label class="required fw-bold text-muted" for="mobile">{{ trans('translation.driver.fields.mobile') }}</label>
                                    <div class="input-group">
                                        <div class="input-group-prepend">
                                            <span class="input-group-text bg-light"><i class="ri-phone-line text-primary"></i></span>
                                        </div>
                                        <input class="form-control {{ $errors->has('mobile') ? 'is-invalid' : '' }}" type="text"
                                            name="mobile" id="mobile" value="{{ old('mobile', $driver->mobile) }}" required>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <div class="form-group mb-0">
                                    <label class="fw-bold text-muted mb-1" for="email">{{ trans('translation.driver.fields.email') }}</label>
                                    <div class="input-group flex-nowrap">
                                        <span class="input-group-text bg-light"><i class="ri-mail-line text-primary"></i></span>
                                        <input class="form-control {{ $errors->has('email') ? 'is-invalid' : '' }}" type="email"
                                            name="email" id="email" value="{{ old('email', $driver->email) }}">
                                    </div>
                                </div>
                            </div>
                            
                            <div class="col-md-6 mb-3">
                                <div class="form-group mb-0">
                                    <label class="required fw-bold text-muted mb-1" for="language">{{ trans('translation.driver.fields.language') }}</label>
                                    <div class="input-group flex-nowrap">
                                        <span class="input-group-text bg-light"><i class="ri-global-line text-primary"></i></span>
                                        <select class="form-control select2 {{ $errors->has('language') ? 'is-invalid' : '' }}" name="language" id="language" required style="width: 100%;">
                                            <option value="en" {{ old('language', $driver->language) == 'en' ? 'selected' : '' }}>English</option>
                                            <option value="ar" {{ old('language', $driver->language) == 'ar' ? 'selected' : '' }}>Arabic</option>
                                        </select>
                                    </div>
                                    @if ($errors->has('language'))
                                        <div class="text-danger small mt-1">{{ $errors->first('language') }}</div>
                                    @endif
                                </div>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <div class="form-group mb-0">
                                    <label class="required fw-bold text-muted mb-1" for="status">{{ trans('translation.driver.fields.status') }}</label>
                                    <div class="input-group flex-nowrap">
                                        <span class="input-group-text bg-light"><i class="ri-checkbox-circle-line text-primary"></i></span>
                                        <select class="form-control select2 {{ $errors->has('status') ? 'is-invalid' : '' }}" name="status" id="status" required style="width: 100%;">
                                            @foreach(App\Models\Driver::STATUS_SELECT as $key => $label)
                                                <option value="{{ $key }}" {{ old('status', $driver->status) == (string) $key ? 'selected' : '' }}>{{ $label }}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                    @if ($errors->has('status'))
                                        <div class="text-danger small mt-1">{{ $errors->first('status') }}</div>
                                    @endif
                                </div>
                            </div>
                        </div>

                        <!-- Shift Section -->
                        <div class="card bg-light border-0 mt-4 mb-4">
                            <div class="card-header bg-soft-primary border-bottom-0">
                                <h6 class="fw-bold text-primary mb-0"><i class="ri-time-line mr-1"></i> Shift & Schedule (الدوام والورديات)</h6>
                            </div>
                            <div class="card-body">
                                <div class="row">
                                    <div class="col-md-4 mb-3">
                                        <div class="form-group">
                                            <label class="required fw-medium text-muted">Employment Type</label>
                                            <select class="form-control" name="employment_type" required>
                                                <option value="full_time" {{ old('employment_type', $driver->employment_type) == 'full_time' ? 'selected' : '' }}>Full Time</option>
                                                <option value="part_time" {{ old('employment_type', $driver->employment_type) == 'part_time' ? 'selected' : '' }}>Part Time</option>
                                            </select>
                                        </div>
                                    </div>
                                    <div class="col-md-4 mb-3">
                                        <div class="form-group">
                                            <label class="required fw-medium text-muted">Required Hours</label>
                                            <div class="input-group">
                                                <input class="form-control" type="number" name="total_working_hours" value="{{ old('total_working_hours', $driver->total_working_hours ?? 8) }}" required>
                                                <div class="input-group-append">
                                                    <span class="input-group-text bg-white">hrs/day</span>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-md-4 mb-3">
                                        <div class="form-group">
                                            <label class="required fw-medium text-muted">Shift Count</label>
                                            <select class="form-control" name="shift_count" required>
                                                <option value="1" {{ old('shift_count', $driver->shift_count) == '1' ? 'selected' : '' }}>1 Shift</option>
                                                <option value="2" {{ old('shift_count', $driver->shift_count) == '2' ? 'selected' : '' }}>2 Shifts</option>
                                                <option value="3" {{ old('shift_count', $driver->shift_count) == '3' ? 'selected' : '' }}>3 Shifts</option>
                                            </select>
                                        </div>
                                    </div>
                                </div>
                                
                                <div class="row">
                                    <div class="col-12 mb-3">
                                        <label class="fw-bold text-muted"><i class="ri-magic-line mr-1"></i> Quick Shift Selection</label>
                                        <select class="form-control border-primary" id="shift_template_selector">
                                            <option value="">-- Select Template or Enter Manually --</option>
                                            @foreach($shiftTemplates as $template)
                                                <option value="{{ $template->id }}" data-start="{{ $template->start_time }}" data-end="{{ $template->end_time }}">
                                                    {{ $template->name }} ({{ \Carbon\Carbon::parse($template->start_time)->format('H:i') }} - {{ \Carbon\Carbon::parse($template->end_time)->format('H:i') }})
                                                </option>
                                            @endforeach
                                        </select>
                                    </div>
                                    
                                    <div class="col-md-6 mb-3">
                                        <div class="form-group">
                                            <label class="required fw-medium text-muted">Shift 1 Start Time</label>
                                            <input class="form-control" type="time" name="working_hours_start" id="working_hours_start" value="{{ old('working_hours_start', $driver->working_hours_start ? \Carbon\Carbon::parse($driver->working_hours_start)->format('H:i') : '') }}" required>
                                        </div>
                                    </div>
                                    <div class="col-md-6 mb-3">
                                        <div class="form-group">
                                            <label class="required fw-medium text-muted">Shift 1 End Time</label>
                                            <input class="form-control" type="time" name="working_hours_end" id="working_hours_end" value="{{ old('working_hours_end', $driver->working_hours_end ? \Carbon\Carbon::parse($driver->working_hours_end)->format('H:i') : '') }}" required>
                                        </div>
                                    </div>

                                    <!-- Second Shift (Dynamic Visibility via JS) -->
                                    <div class="col-md-6 mb-3 second-shift-fields" style="{{ old('shift_count', $driver->shift_count) >= 2 ? '' : 'display:none' }}">
                                        <div class="form-group">
                                            <label class="fw-medium text-muted">Shift 2 Start Time</label>
                                            <input class="form-control" type="time" name="second_shift_working_hours_start" value="{{ old('second_shift_working_hours_start', $driver->second_shift_working_hours_start ? \Carbon\Carbon::parse($driver->second_shift_working_hours_start)->format('H:i') : '') }}">
                                        </div>
                                    </div>
                                    <div class="col-md-6 mb-3 second-shift-fields" style="{{ old('shift_count', $driver->shift_count) >= 2 ? '' : 'display:none' }}">
                                        <div class="form-group">
                                            <label class="fw-medium text-muted">Shift 2 End Time</label>
                                            <input class="form-control" type="time" name="second_shift_working_hours_end" value="{{ old('second_shift_working_hours_end', $driver->second_shift_working_hours_end ? \Carbon\Carbon::parse($driver->second_shift_working_hours_end)->format('H:i') : '') }}">
                                        </div>
                                    </div>

                                    <!-- Third Shift (Dynamic Visibility via JS) -->
                                    <div class="col-md-6 mb-3 third-shift-fields" style="{{ old('shift_count', $driver->shift_count) >= 3 ? '' : 'display:none' }}">
                                        <div class="form-group">
                                            <label class="fw-medium text-muted">Shift 3 Start Time</label>
                                            <input class="form-control" type="time" name="third_shift_working_hours_start" value="{{ old('third_shift_working_hours_start', $driver->third_shift_working_hours_start ? \Carbon\Carbon::parse($driver->third_shift_working_hours_start)->format('H:i') : '') }}">
                                        </div>
                                    </div>
                                    <div class="col-md-6 mb-3 third-shift-fields" style="{{ old('shift_count', $driver->shift_count) >= 3 ? '' : 'display:none' }}">
                                        <div class="form-group">
                                            <label class="fw-medium text-muted">Shift 3 End Time</label>
                                            <input class="form-control" type="time" name="third_shift_working_hours_end" value="{{ old('third_shift_working_hours_end', $driver->third_shift_working_hours_end ? \Carbon\Carbon::parse($driver->third_shift_working_hours_end)->format('H:i') : '') }}">
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <div class="form-group">
                                    <label class="fw-bold text-muted" for="zone_id">{{ trans('translation.driver.fields.zone') }}</label>
                                    <select class="form-control select2" name="zone_id" id="zone_id" required>
                                        @foreach ($zones as $id => $entry)
                                            <option value="{{ $id }}" {{ (old('zone_id') ? old('zone_id') : $driver->zone->id ?? '') == $id ? 'selected' : '' }}>{{ $entry }}</option>
                                        @endforeach
                                    </select>
                                </div>
                            </div>
                            
                            <div class="col-md-6 mb-3">
                                <div class="form-group">
                                    <label class="required fw-bold text-muted" for="national_id">{{ trans('translation.driver.fields.national_id') }}</label>
                                    <input class="form-control" type="text" name="national_id" value="{{ old('national_id', $driver->national_id) }}" required>
                                </div>
                            </div>
                        </div>

                        <div class="form-group mt-5 text-center">
                            <a href="{{ route('admin.drivers.index') }}" class="btn btn-light border px-5 mr-3">Cancel</a>
                            <button class="btn btn-warning text-white px-5 shadow-sm" type="submit">
                                <i class="ri-save-3-line mr-1"></i> Update Driver Data
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
@endsection

@section('scripts')
    @parent
    <style>
        /* Definitive Select2 + Input Group Fix */
        .input-group > .select2-container--default {
            flex: 1 1 auto !important;
            width: auto !important;
        }
        .input-group > .select2-container--default .select2-selection--single {
            height: 38px !important;
            line-height: 38px !important;
            border-top-left-radius: 0 !important;
            border-bottom-left-radius: 0 !important;
            border-left: 0 !important;
            display: flex !important;
            align-items: center !important;
        }
        .input-group-text {
            border-right: 0 !important;
        }
    </style>
    <script>
        $(document).ready(function() {
            $('#shift_template_selector').on('change', function() {
                const selected = $(this).find(':selected');
                const startTime = selected.data('start');
                const endTime = selected.data('end');
                
                if (startTime && endTime) {
                    const formattedStart = startTime.substring(0, 5);
                    const formattedEnd = endTime.substring(0, 5);
                    
                    $('#working_hours_start').val(formattedStart);
                    $('#working_hours_end').val(formattedEnd);
                }
            });

            // Toggle Shift Fields
            $('select[name="shift_count"]').on('change', function() {
                const count = parseInt($(this).val());
                
                // Handle Second Shift
                if (count >= 2) {
                    $('.second-shift-fields').fadeIn();
                } else {
                    $('.second-shift-fields').fadeOut();
                }

                // Handle Third Shift
                if (count >= 3) {
                    $('.third-shift-fields').fadeIn();
                } else {
                    $('.third-shift-fields').fadeOut();
                }
            });
        });
    </script>
@endsection
