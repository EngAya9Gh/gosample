@extends('layouts.master')
@section('title')
    @lang('translation.swaprequests')
@endsection
@section('content')
    @component('components.breadcrumb')
        @slot('li_1')
            @lang('translation.appname')
        @endslot
        @slot('title')
            @lang('translation.swaprequests')
        @endslot
    @endcomponent

    @if ($errors->any())
        <div class="alert alert-danger">
            <ul>
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <div id="task_message" class="alert" style="display: none;"></div>

    <div class="card modern-filter-card">
        <div class="card-header">
            <h4 class="card-title mb-0">{{ trans('translation.create') }} {{ trans('translation.swaprequest.title_singular') }}</h4>
        </div>

        <div class="card-body">
            <form method="POST" action="{{ route('admin.swaprequests.store') }}" enctype="multipart/form-data">
                @csrf
                <div class="row">
                    <div class="col-lg-6 mb-3">
                        <label class="required" for="driver_a">{{ trans('translation.swaprequest.fields.driver_a') }}</label>
                        <select class="form-control select2 swap-select2 {{ $errors->has('driver') ? 'is-invalid' : '' }}"
                            name="driver_a" id="driver_a" data-placeholder="Select old driver" required>
                            <option></option>
                            @foreach ($drivers as $id => $entry)
                                <option value="{{ $id }}" {{ (string) old('driver_a') === (string) $id ? 'selected' : '' }}>
                                    {{ $entry }}
                                </option>
                            @endforeach
                        </select>
                        @if ($errors->has('driver'))
                            <div class="invalid-feedback">{{ $errors->first('driver') }}</div>
                        @endif
                        <small class="help-block text-muted">{{ trans('translation.swaprequest.fields.driver_helper') }}</small>
                    </div>

                    <div class="col-lg-6 mb-3">
                        <label class="required" for="task_id">{{ trans('translation.swaprequest.fields.task') }}</label>
                        <select class="form-control select2 swap-select2 {{ $errors->has('task') ? 'is-invalid' : '' }}"
                            name="task_id[]" id="task_id" data-placeholder="Select tasks" multiple required></select>
                        @if ($errors->has('task'))
                            <div class="invalid-feedback">{{ $errors->first('task') }}</div>
                        @endif
                        <small class="help-block text-muted">
                            {{ trans('translation.swaprequest.fields.task_helper') }}
                            <span class="text-muted">— Pick the old driver first, then click each task you want to swap.</span>
                        </small>
                    </div>

                    <div class="col-lg-6 mb-3">
                        <label class="required" for="driver_id">{{ trans('translation.swaprequest.fields.driver') }}</label>
                        <select class="form-control select2 swap-select2 {{ $errors->has('driver') ? 'is-invalid' : '' }}"
                            name="driver_id" id="driver_id" data-placeholder="Select new driver" required>
                            <option></option>
                            @foreach ($drivers as $id => $entry)
                                <option value="{{ $id }}" {{ (string) old('driver_id') === (string) $id ? 'selected' : '' }}>
                                    {{ $entry }}
                                </option>
                            @endforeach
                        </select>
                        @if ($errors->has('driver'))
                            <div class="invalid-feedback">{{ $errors->first('driver') }}</div>
                        @endif
                        <small class="help-block text-muted">{{ trans('translation.swaprequest.fields.driver_helper') }}</small>
                    </div>

                    <div class="col-lg-6 mb-3">
                        <label class="required" for="statuss">{{ trans('translation.swaprequest.fields.status') }}</label>
                        <select class="form-control {{ $errors->has('status') ? 'is-invalid' : '' }}" name="status"
                            id="statuss" required>
                            <option value="" disabled {{ old('status', null) === null ? 'selected' : '' }}>
                                {{ trans('translation.pleaseSelect') }}
                            </option>
                            @foreach (App\Models\Swap::STATUS_SELECT as $key => $label)
                                <option value="{{ $key }}" {{ old('status', 'new') === (string) $key ? 'selected' : '' }}>
                                    {{ $label }}
                                </option>
                            @endforeach
                        </select>
                        @if ($errors->has('status'))
                            <div class="invalid-feedback">{{ $errors->first('status') }}</div>
                        @endif
                        <small class="help-block text-muted">{{ trans('translation.swaprequest.fields.status_helper') }}</small>
                    </div>
                </div>

                <div class="col-lg-12 d-flex justify-content-end flex-wrap mt-2" style="gap: 10px;">
                    <a href="{{ route('admin.swaprequests.index') }}" class="btn btn-reset mb-1">
                        {{ trans('global.cancel') }}
                    </a>
                    <button class="btn btn-save mb-1" type="submit">
                        <i class="fas fa-save"></i> {{ trans('translation.save') }}
                    </button>
                </div>
            </form>
        </div>
    </div>
@endsection

@section('scripts')
    <script>
        $(document).ready(function () {
            // Initialize Select2 on this page's three dropdowns explicitly.
            // We use a custom class (`swap-select2`) so we know exactly which selects to wrap
            // and can re-init the multi-select cleanly after AJAX populates new options.
            $('.swap-select2').each(function () {
                var $sel = $(this);
                if ($sel.hasClass('select2-hidden-accessible')) {
                    $sel.select2('destroy');
                }
                $sel.select2({
                    placeholder: $sel.data('placeholder') || 'Select a record',
                    allowClear: !$sel.prop('required') || $sel.prop('multiple'),
                    width: '100%',
                    closeOnSelect: !$sel.prop('multiple')  // for multi: keep dropdown open while picking tasks
                });
            });

            // When the Old Driver changes, fetch their assignable tasks and populate the Task list.
            $('#driver_a').on('change', function () {
                var driverId = $(this).val();
                var $tasks   = $('#task_id');

                $tasks.empty().val(null).trigger('change');
                $('#task_message').empty().hide();

                if (!driverId) return;

                $.ajax({
                    url: '/api/swap/tasks/list',
                    type: "POST",
                    data: { "driver_id": driverId },
                    dataType: "json",
                    success: function (response) {
                        if (response.status && response.data) {
                            var taskCount = response.data.length;
                            if (taskCount > 0) {
                                $.each(response.data, function (index, item) {
                                    $tasks.append(
                                        '<option value="' + item.id + '">' + item.id + ' ' + item.from.name + '</option>'
                                    );
                                });
                                // Tell Select2 the underlying options changed
                                $tasks.trigger('change.select2');
                                $('#task_message').removeClass('alert-warning').addClass('alert-success')
                                    .html('We have ' + taskCount + ' task' + (taskCount === 1 ? '' : 's') + '.').show();
                            } else {
                                $('#task_message').removeClass('alert-success').addClass('alert-warning')
                                    .html('No tasks found for this driver.').show();
                            }
                        }
                    },
                    error: function (xhr, status, error) {
                        console.error("Error: " + status + " " + error);
                    }
                });
            });
        });
    </script>
@endsection
