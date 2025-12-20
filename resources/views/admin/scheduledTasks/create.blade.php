@extends('layouts.master')

<!-- Flatpickr -->
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">
<script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>

@section('content')
<div class="card">
    <div class="card-header">
        {{ trans('global.create') }} {{ trans('cruds.scheduledTask.title_singular') }}
    </div>

    @if ($errors->has('visit_hours'))
        <div class="alert alert-danger">
            {{ $errors->first('visit_hours') }}
        </div>
    @endif

    <div class="card-body">
        <form method="POST" action="{{ route('admin.scheduled-tasks.store') }}">
            @csrf

            {{-- NAME + STATUS --}}
            <div class="row">
                <div class="col-6">
                    <div class="form-group">
                        <label class="required">Name</label>
                        <input type="text" name="name" class="form-control" value="{{ old('name') }}" required>
                    </div>
                </div>
                <div class="col-6">
                    <div class="form-group">
                        <label>Status</label>
                        <select name="status" class="form-control">
                            @foreach (App\Models\ScheduledTask::STATUS_SELECT as $key => $label)
                                <option value="{{ $key }}">{{ $label }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>
            </div>

            {{-- DATES --}}
            <div class="row">
                <div class="col-6">
                    <div class="form-group">
                        <label class="required">Start Date</label>
                        <input type="date" name="start_date" class="form-control" required>
                    </div>
                </div>
                <div class="col-6">
                    <div class="form-group">
                        <label class="required">End Date</label>
                        <input type="date" name="end_date" class="form-control" required>
                    </div>
                </div>
            </div>

            {{-- FROM LOCATION --}}
            <div class="row">
                <div class="col-6">
                    <div class="form-group">
                        <label class="required">From Location</label>
                        <select
                            class="form-control select2"
                            name="from_location_id[]"
                            id="from_location_id"
                            multiple
                            required
                        >
                            @foreach ($from_locations as $id => $entry)
                                <option value="{{ $id }}">{{ $entry }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>

                {{-- TO LOCATION --}}
                <div class="col-6">
                    <div class="form-group">
                        <label class="required">To Location</label>
                        <select class="form-control select2" name="to_location_id" required>
                            @foreach ($to_locations as $id => $entry)
                                <option value="{{ $id }}">{{ $entry }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>
            </div>

            {{-- CLIENT + TASK TYPE --}}
            <div class="row">
                <div class="col-6">
                    <label class="required">Client</label>
                    <select name="client_id" class="form-control select2" required>
                        @foreach ($clients as $id => $entry)
                            <option value="{{ $id }}">{{ $entry }}</option>
                        @endforeach
                    </select>
                </div>

                <div class="col-6">
                    <label class="required">Task Type</label>
                    <select name="task_type" class="form-control" required>
                        @foreach (App\Models\ScheduledTask::TASK_TYPE_SELECT as $key => $label)
                            <option value="{{ $key }}">{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
            </div>

            {{-- DAYS + DRIVER --}}
            <div class="row">
                <div class="col-6">
                    <label class="required">Days</label>
                    <select name="days[]" class="form-control select2" multiple required>
                        @foreach ($days as $day)
                            <option value="{{ $day }}">{{ $day }}</option>
                        @endforeach
                    </select>
                </div>

                <div class="col-6">
                    <label class="required">Driver</label>
                    <select name="driver_id" class="form-control select2" required>
                        @foreach ($drivers as $id => $entry)
                            <option value="{{ $id }}">{{ $entry }}</option>
                        @endforeach
                    </select>
                </div>
            </div>

            {{-- VISIT HOURS TABLE --}}
            <div class="row mt-4">
                <div class="col-8">
                    <h5>Visit Hours Per From Location</h5>

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
            <div class="form-group mt-3">
                <button class="btn btn-danger" type="submit">
                    {{ trans('global.save') }}
                </button>
            </div>
        </form>
    </div>
</div>
@endsection

@section('scripts')
<script>
$(document).ready(function () {

    $('.select2').select2();

    const tableBody = $('#visit-hours-table tbody');

    function rebuildTable() {
        tableBody.empty();

        const locations = $('#from_location_id').select2('data');

        locations.forEach(loc => {
            tableBody.append(`
                <tr>
                    <td>${loc.text}</td>
                    <td>
                        <input
                            type="time"
                            class="form-control"
                            name="visit_hours[${loc.id}]"
                            required
                        >
                    </td>
                </tr>
            `);
        });
    }

    $('#from_location_id').on('change', rebuildTable);
});
</script>
@endsection
