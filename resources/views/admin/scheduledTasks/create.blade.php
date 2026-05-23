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
        <form method="POST" action="{{ route('admin.scheduled-tasks.store') }}">
            @csrf

            {{-- DRIVER + STATUS --}}
            <div class="row">
                <div class="col-lg-6 mb-3">
                    <label class="required" for="driver_id">Driver</label>
                    <select name="driver_id" id="driver_id" class="form-control select2" required>
                        @foreach ($drivers as $id => $entry)
                            <option value="{{ $id }}">{{ $entry }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-lg-6 mb-3">
                    <label for="status">Status</label>
                    <select name="status" id="status" class="form-control">
                        @foreach (App\Models\ScheduledTask::STATUS_SELECT as $key => $label)
                            <option value="{{ $key }}" {{ old('status', 'enabled') == $key ? 'selected' : '' }}>{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
            </div>

            {{-- DATES --}}
            <div class="row">
                <div class="col-lg-6 mb-3">
                    <label class="required" for="start_date">Start Date</label>
                    <input type="text" class="form-control" name="start_date" id="start_date"
                        data-mf-date="date" autocomplete="off" required>
                </div>
                <div class="col-lg-6 mb-3">
                    <label class="required" for="end_date">End Date</label>
                    <input type="text" class="form-control" name="end_date" id="end_date"
                        data-mf-date="date" autocomplete="off" required>
                </div>
            </div>

            {{-- FROM LOCATION + TO LOCATION --}}
            <div class="row">
                <div class="col-lg-6 mb-3">
                    <label class="required" for="from_location_id">From Location</label>
                    <select
                        class="form-control select2"
                        name="from_location_id[]"
                        id="from_location_id"
                        data-placeholder="Select one or more locations"
                        multiple
                        required
                    >
                        @foreach ($from_locations as $id => $entry)
                            <option value="{{ $id }}" {{ (is_array(old('from_location_id')) && in_array($id, old('from_location_id'))) ? 'selected' : '' }}>{{ $entry }}</option>
                        @endforeach
                    </select>
                </div>

                <div class="col-lg-6 mb-3">
                    <label class="required" for="to_location_id">To Location</label>
                    <select class="form-control select2" name="to_location_id" id="to_location_id" required>
                        @foreach ($to_locations as $id => $entry)
                            <option value="{{ $id }}">{{ $entry }}</option>
                        @endforeach
                    </select>
                </div>
            </div>

            {{-- CLIENT + TASK TYPE --}}
            <div class="row">
                <div class="col-lg-6 mb-3">
                    <label class="required" for="client_id">Client</label>
                    <select name="client_id" id="client_id" class="form-control select2" required>
                        @foreach ($clients as $id => $entry)
                            <option value="{{ $id }}">{{ $entry }}</option>
                        @endforeach
                    </select>
                </div>

                <div class="col-lg-6 mb-3">
                    <label class="required" for="task_type">Task Type</label>
                    <select name="task_type" id="task_type" class="form-control" required>
                        @foreach (App\Models\ScheduledTask::TASK_TYPE_SELECT as $key => $label)
                            <option value="{{ $key }}" {{ old('task_type', 'SAMPLE') == $key ? 'selected' : '' }}>{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
            </div>

            {{-- DAYS --}}
            <div class="row">
                <div class="col-lg-6 mb-3">
                    <label class="required" for="days">Days</label>
                    <select name="days[]" id="days" class="form-control select2"
                        data-placeholder="Select one or more days" multiple required>
                        @foreach ($days as $day)
                            <option value="{{ $day }}">{{ $day }}</option>
                        @endforeach
                    </select>
                </div>
            </div>

            {{-- VISIT HOURS TABLE --}}
            <div class="row mt-2">
                <div class="col-lg-8 mb-3">
                    <h5 class="mb-3">Visit Hours Per From Location</h5>

                    <table class="table table-bordered" id="visit-hours-table">
                        <thead>
                            <tr>
                                <th>From Location</th>
                                <th>Visit Hour</th>
                            </tr>
                        </thead>
                        <tbody>
                            {{-- Generated by JS --}}
                        </tbody>
                    </table>
                </div>
            </div>

            {{-- SAVE --}}
            <div class="col-lg-12 d-flex justify-content-end flex-wrap mt-2" style="gap: 10px;">
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
    const oldVisitHours = {!! json_encode(old('visit_hours', [])) !!};

    $('.select2').each(function () {
        $(this).select2({
            placeholder: $(this).data('placeholder') || 'Please select',
            allowClear: false
        });
    });

    const tableBody = $('#visit-hours-table tbody');

    function syncTable() {
        const locations = $('#from_location_id').select2('data');
        const selectedIds = locations.map(loc => String(loc.id));

        // 1. Remove rows whose location is no longer selected
        tableBody.find('tr').each(function () {
            const rowId = String($(this).data('loc-id'));
            if (!selectedIds.includes(rowId)) {
                $(this).remove();
            }
        });

        // 2. Add rows for newly-selected locations and initialize Flatpickr immediately
        locations.forEach(loc => {
            const existing = tableBody.find('tr[data-loc-id="' + loc.id + '"]');
            if (existing.length === 0) {
                let oldVal = oldVisitHours[loc.id] ? oldVisitHours[loc.id] : '';
                let newRow = $(`
                    <tr data-loc-id="${loc.id}">
                        <td>${loc.text}</td>
                        <td>
                            <input
                                type="text"
                                class="form-control visit-hour-input"
                                name="visit_hours[${loc.id}]"
                                value="${oldVal}"
                                placeholder="Select time"
                                autocomplete="off"
                                required
                            >
                        </td>
                    </tr>
                `);
                tableBody.append(newRow);

                // Init Flatpickr ONLY on this new row's input to prevent nesting bugs
                flatpickr(newRow.find('.visit-hour-input')[0], {
                    enableTime: true,
                    noCalendar: true,
                    dateFormat: 'H:i',
                    altInput: true,
                    altFormat: 'h:i K',
                    time_24hr: false,
                    allowInput: false,
                    minuteIncrement: 5,
                    defaultDate: oldVal ? oldVal : null
                });
            }
        });
    }

    $('#from_location_id').on('change', syncTable);

    $('form').on('submit', function(e) {
        let valid = true;
        tableBody.find('input[type="hidden"].visit-hour-input').each(function () {
            if (!$(this).val()) {
                valid = false;
                return false; // break loop
            }
        });
        if (!valid) {
            e.preventDefault();
            alert('Please select a Visit Hour for all selected locations!');
        }
    });

    // Run once on load in case of old input
    if ($('#from_location_id').val() && $('#from_location_id').val().length > 0) {
        syncTable();
    }
});
</script>
@endsection
