@extends('layouts.master')

@section('css')
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">
@endsection

@section('content')
    <div class="card modern-filter-card">
        <div class="card-header">
            <h4 class="card-title mb-0">{{ trans('global.create') }} {{ trans('cruds.scheduledTask.title_singular') }}</h4>
        </div>

        @if ($errors->has('visit_hours'))
            <div class="alert alert-danger mx-3 mt-3">
                {{ $errors->first('visit_hours') }}
            </div>
        @endif

        <div class="card-body">
            <form method="POST" action="{{ route('admin.scheduled-tasks.quickAction') }}" enctype="multipart/form-data">
                @csrf

                {{-- NAME + STATUS --}}
                <div class="row">
                    <div class="col-lg-6 mb-3">
                        <label class="required" for="name">{{ trans('cruds.scheduledTask.fields.name') }}</label>
                        <input class="form-control {{ $errors->has('name') ? 'is-invalid' : '' }}" type="text"
                            name="name" id="name" value="{{ old('name', '') }}" placeholder="Task name" required>
                        @if ($errors->has('name'))
                            <div class="invalid-feedback">{{ $errors->first('name') }}</div>
                        @endif
                        <small class="help-block text-muted">{{ trans('cruds.scheduledTask.fields.name_helper') }}</small>
                    </div>

                    <div class="col-lg-6 mb-3">
                        <label for="statuss">{{ trans('cruds.scheduledTask.fields.status') }}</label>
                        <select class="form-control {{ $errors->has('status') ? 'is-invalid' : '' }}" name="status"
                            id="statuss">
                            <option value disabled {{ old('status', null) === null ? 'selected' : '' }}>
                                {{ trans('global.pleaseSelect') }}
                            </option>
                            @foreach (App\Models\ScheduledTask::STATUS_SELECT as $key => $label)
                                <option value="{{ $key }}"
                                    {{ old('status', 'enabled') === (string) $key ? 'selected' : '' }}>
                                    {{ $label }}
                                </option>
                            @endforeach
                        </select>
                        @if ($errors->has('status'))
                            <div class="invalid-feedback">{{ $errors->first('status') }}</div>
                        @endif
                        <small class="help-block text-muted">{{ trans('cruds.scheduledTask.fields.status_helper') }}</small>
                    </div>
                </div>

                {{-- DATES --}}
                <div class="row">
                    <div class="col-lg-6 mb-3">
                        <label class="required" for="start_date">{{ trans('cruds.scheduledTask.fields.start_date') }}</label>
                        <input class="form-control {{ $errors->has('start_date') ? 'is-invalid' : '' }}" type="text"
                            data-mf-date="date" name="start_date" id="start_date" value="{{ old('start_date') }}"
                            autocomplete="off" required>
                        @if ($errors->has('start_date'))
                            <div class="invalid-feedback">{{ $errors->first('start_date') }}</div>
                        @endif
                        <small class="help-block text-muted">{{ trans('cruds.scheduledTask.fields.start_date_helper') }}</small>
                    </div>

                    <div class="col-lg-6 mb-3">
                        <label class="required" for="end_date">{{ trans('cruds.scheduledTask.fields.end_date') }}</label>
                        <input class="form-control {{ $errors->has('end_date') ? 'is-invalid' : '' }}" type="text"
                            data-mf-date="date" name="end_date" id="end_date" value="{{ old('end_date') }}"
                            autocomplete="off" required>
                        @if ($errors->has('end_date'))
                            <div class="invalid-feedback">{{ $errors->first('end_date') }}</div>
                        @endif
                        <small class="help-block text-muted">{{ trans('cruds.scheduledTask.fields.end_date_helper') }}</small>
                    </div>
                </div>

                {{-- FROM LOCATION + TO LOCATION --}}
                <div class="row">
                    <div class="col-lg-6 mb-3">
                        <label class="required" for="from_location_id">{{ trans('cruds.scheduledTask.fields.from_location') }}</label>
                        <select class="form-control select2 {{ $errors->has('from_location') ? 'is-invalid' : '' }}"
                            name="from_location_id" id="from_location_id" required>
                            @foreach ($from_locations as $id => $entry)
                                <option value="{{ $id }}"
                                    {{ $id == old('from_location_id') ? 'selected' : '' }}>
                                    {{ $entry }}
                                </option>
                            @endforeach
                        </select>
                        @if ($errors->has('from_location'))
                            <div class="invalid-feedback">{{ $errors->first('from_location') }}</div>
                        @endif
                        <small class="help-block text-muted">{{ trans('cruds.scheduledTask.fields.from_location_helper') }}</small>
                    </div>

                    <div class="col-lg-6 mb-3">
                        <label class="required" for="to_location_id">{{ trans('cruds.scheduledTask.fields.to_location') }}</label>
                        <select class="form-control select2 {{ $errors->has('to_location') ? 'is-invalid' : '' }}"
                            name="to_location_id" id="to_location_id" required>
                            @foreach ($to_locations as $id => $entry)
                                <option value="{{ $id }}"
                                    {{ old('to_location_id') == $id ? 'selected' : '' }}>
                                    {{ $entry }}
                                </option>
                            @endforeach
                        </select>
                        @if ($errors->has('to_location'))
                            <div class="invalid-feedback">{{ $errors->first('to_location') }}</div>
                        @endif
                        <small class="help-block text-muted">{{ trans('cruds.scheduledTask.fields.to_location_helper') }}</small>
                    </div>
                </div>

                {{-- CLIENT + TASK TYPE --}}
                <div class="row">
                    <div class="col-lg-6 mb-3">
                        <label class="required" for="client_id">{{ trans('cruds.scheduledTask.fields.client') }}</label>
                        <select class="form-control select2 {{ $errors->has('client') ? 'is-invalid' : '' }}"
                            name="client_id" id="client_id" required>
                            @foreach ($clients as $id => $entry)
                                <option value="{{ $id }}" {{ old('client_id') == $id ? 'selected' : '' }}>
                                    {{ $entry }}
                                </option>
                            @endforeach
                        </select>
                        @if ($errors->has('client'))
                            <div class="invalid-feedback">{{ $errors->first('client') }}</div>
                        @endif
                        <small class="help-block text-muted">{{ trans('cruds.scheduledTask.fields.client_helper') }}</small>
                    </div>

                    <div class="col-lg-6 mb-3">
                        <label class="required" for="task_type">{{ trans('cruds.scheduledTask.fields.task_type') }}</label>
                        <select class="form-control {{ $errors->has('task_type') ? 'is-invalid' : '' }}"
                            name="task_type" id="task_type" required>
                            <option value disabled {{ old('task_type', null) === null ? 'selected' : '' }}>
                                {{ trans('global.pleaseSelect') }}
                            </option>
                            @foreach (App\Models\ScheduledTask::TASK_TYPE_SELECT as $key => $label)
                                <option value="{{ $key }}"
                                    {{ old('task_type', 'SAMPLE') === (string) $key ? 'selected' : '' }}>
                                    {{ $label }}
                                </option>
                            @endforeach
                        </select>
                        @if ($errors->has('task_type'))
                            <div class="invalid-feedback">{{ $errors->first('task_type') }}</div>
                        @endif
                        <small class="help-block text-muted">{{ trans('cruds.scheduledTask.fields.task_type_helper') }}</small>
                    </div>
                </div>

                {{-- DAYS + DRIVER --}}
                <div class="row">
                    <div class="col-lg-6 mb-3">
                        <label class="required" for="days">{{ trans('cruds.scheduledTask.fields.days') }}</label>
                        <select class="form-control select2 {{ $errors->has('days') ? 'is-invalid' : '' }}"
                            name="days[]" id="days" data-placeholder="Select one or more days" multiple required>
                            @foreach ($days as $day)
                                <option value="{{ $day }}"
                                    {{ in_array($day, old('days', [])) ? 'selected' : '' }}>
                                    {{ $day }}
                                </option>
                            @endforeach
                        </select>
                        @if ($errors->has('days'))
                            <div class="invalid-feedback">{{ $errors->first('days') }}</div>
                        @endif
                    </div>

                    <div class="col-lg-6 mb-3">
                        <label for="driver_id">{{ trans('cruds.scheduledTask.fields.driver') }}</label>
                        <select class="form-control select2 {{ $errors->has('driver') ? 'is-invalid' : '' }}"
                            name="driver_id" id="driver_id" required>
                            @foreach ($drivers as $id => $entry)
                                <option value="{{ $id }}" {{ old('driver_id') == $id ? 'selected' : '' }}>
                                    {{ $entry }}
                                </option>
                            @endforeach
                        </select>
                        @if ($errors->has('driver'))
                            <div class="invalid-feedback">{{ $errors->first('driver') }}</div>
                        @endif
                    </div>
                </div>

                {{-- VISIT HOURS --}}
                <div class="row mt-2">
                    <div class="col-lg-8 mb-3">
                        <h5 class="mb-2">Visit Hours</h5>
                        <div id="visits-container"></div>

                        <button type="button" class="btn btn-create mt-2" id="add-visit">
                            <i class="ri-add-line"></i> Add Visit
                        </button>
                    </div>
                </div>

                {{-- ACTIONS --}}
                <div class="col-lg-12 d-flex justify-content-end flex-wrap mt-3" style="gap: 10px;">
                    <a href="{{ route('admin.scheduled-tasks.index') }}" class="btn btn-reset mb-1">
                        {{ trans('global.cancel') }}
                    </a>
                    <button class="btn btn-save mb-1" type="submit">
                        <i class="fas fa-save"></i> {{ trans('global.save') }}
                    </button>
                </div>
            </form>
        </div>
    </div>
@endsection

@section('scripts')
    <script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>
    <script>
        $(document).ready(function () {
            $('.select2').each(function () {
                $(this).select2({
                    placeholder: $(this).data('placeholder') || 'Please select',
                    allowClear: false
                });
            });

            const visitsContainer = $('#visits-container');

            function applyTimePicker(input) {
                if (input._flatpickr) return;
                flatpickr(input, {
                    enableTime: true,
                    noCalendar: true,
                    dateFormat: 'H:i',
                    altInput: true,
                    altFormat: 'h:i K',
                    time_24hr: false,
                    allowInput: false,
                    minuteIncrement: 5
                });
            }

            function addVisitInput() {
                const row = $(
                    '<div class="row align-items-end mb-2 visit-row">' +
                        '<div class="col-lg-4 mb-1">' +
                            '<label class="required">Visit Hour</label>' +
                            '<input type="text" class="form-control visit-hour-input" ' +
                                'name="visit_hours[]" placeholder="Select time" autocomplete="off" required>' +
                        '</div>' +
                        '<div class="col-lg-2 mb-1">' +
                            '<button type="button" class="btn btn-soft-danger btn-sm remove-visit" title="Remove">' +
                                '<i class="ri-delete-bin-fill"></i>' +
                            '</button>' +
                        '</div>' +
                    '</div>'
                );
                visitsContainer.append(row);
                applyTimePicker(row.find('.visit-hour-input')[0]);
            }

            $('#add-visit').on('click', addVisitInput);

            // Remove visit row (event delegation since rows are dynamic)
            visitsContainer.on('click', '.remove-visit', function () {
                $(this).closest('.visit-row').remove();
            });
        });
    </script>
@endsection
