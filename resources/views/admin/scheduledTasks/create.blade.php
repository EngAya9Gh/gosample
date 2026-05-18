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
                            <option value="{{ $id }}">{{ $entry }}</option>
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

    $('.select2').each(function () {
        $(this).select2({
            placeholder: $(this).data('placeholder') || 'Please select',
            allowClear: false
        });
    });
    // Start/End date pickers are auto-initialized by the modern-filters partial
    // because the inputs carry data-mf-date="date" inside a .modern-filter-card.

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

        // 2. Add rows for newly-selected locations (preserve existing rows + their values)
        locations.forEach(loc => {
            const existing = tableBody.find('tr[data-loc-id="' + loc.id + '"]');
            if (existing.length === 0) {
                tableBody.append(`
                    <tr data-loc-id="${loc.id}">
                        <td>${loc.text}</td>
                        <td>
                            <input
                                type="text"
                                class="form-control visit-hour-input"
                                name="visit_hours[${loc.id}]"
                                placeholder="Select time"
                                autocomplete="off"
                                required
                            >
                        </td>
                    </tr>
                `);
            }
        });

        // 3. Init Flatpickr on any inputs that don't already have it attached.
        //    Display: 12-hour AM/PM. Submitted value: HH:MM (24-hour) — backend contract unchanged.
        tableBody.find('.visit-hour-input').each(function () {
            if (!this._flatpickr) {
                flatpickr(this, {
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
        });
    }

    $('#from_location_id').on('change', syncTable);
});
</script>
@endsection
