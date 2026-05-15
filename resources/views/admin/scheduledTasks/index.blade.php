@extends('layouts.master')
@section('content')
    <div class="row">
        <div class="col-lg-12">
            <div class="card modern-filter-card">
                <div class="card-header">
                    <h4 class="card-title mb-0">Filters</h4>
                </div>
                <form method="GET" action="#">
                    <div class="card-body">
                        <div class="row">
                            <div class="col-lg-3 mb-3">
                                <label for="date_from">{{ trans('global.date_from') }}</label>
                                <input class="form-control" type="datetime-local" name="date_from" id="date_from">
                            </div>
                            <div class="col-lg-3 mb-3">
                                <label for="date_to">{{ trans('global.date_to') }}</label>
                                <input class="form-control" type="datetime-local" name="date_to" id="date_to">
                            </div>
                            <div class="col-lg-3 mb-3">
                                <label for="driver_id">{{ trans('translation.task.fields.driver') }}</label>
                                <select class="form-control select2" name="driver_id" id="driver_id">
                                    <option value="">Select Driver</option>
                                    @if(request('driver_id'))
                                        @php
                                            $selectedDriver = \App\Models\Driver::find(request('driver_id'));
                                        @endphp
                                        @if($selectedDriver)
                                            <option value="{{ $selectedDriver->id }}" selected>{{ $selectedDriver->name }}</option>
                                        @endif
                                    @endif
                                </select>
                            </div>
                            <div class="col-lg-3 mb-3">
                                <label for="from_location">{{ trans('translation.task.fields.from_location') }}</label>
                                <select class="form-control select2" name="from_location" id="from_location">
                                    <option value="">Select Location</option>
                                    @if(request('from_location'))
                                        @php
                                            $selectedLocation = \App\Models\Location::find(request('from_location'));
                                        @endphp
                                        @if($selectedLocation)
                                            <option value="{{ $selectedLocation->id }}" selected>{{ $selectedLocation->name }}</option>
                                        @endif
                                    @endif
                                </select>
                            </div>
                            <div class="col-lg-3 mb-3">
                                <label for="to_location">{{ trans('translation.task.fields.to_location') }}</label>
                                <select class="form-control select2" name="to_location" id="to_location">
                                    <option value="">Select Location</option>
                                    @if(request('to_location'))
                                        @php
                                            $selectedLocation = \App\Models\Location::find(request('to_location'));
                                        @endphp
                                        @if($selectedLocation)
                                            <option value="{{ $selectedLocation->id }}" selected>{{ $selectedLocation->name }}</option>
                                        @endif
                                    @endif
                                </select>
                            </div>
                            @if(count($clients) > 1)
                            <div class="col-lg-3 mb-3">
                                <label for="client_id">{{ trans('translation.task.fields.billing_client') }}</label>
                                <select class="form-control select2" name="client_id" id="client_id">
                                    <option value="">Select Client</option>
                                    @foreach ($clients as $id => $entry)
                                        <option value="{{ $entry->id }}">{{ $entry->english_name }}</option>
                                    @endforeach
                                </select>
                            </div>
                            @else
                            <div class="col-lg-3 mb-3" style="display: none;">
                                <label for="client_id">{{ trans('translation.task.fields.billing_client') }}</label>
                                <select class="form-control select2" name="client_id" id="client_id">
                                    <option value="">Select Client</option>
                                    @foreach ($clients as $id => $entry)
                                        <option selected value="{{ $entry->id }}">{{ $entry->english_name }}</option>
                                    @endforeach
                                </select>
                            </div>
                            @endif

                            <div class="col-lg-12 d-flex justify-content-end mt-2 flex-wrap">
                                <button class="btn btn-reset mr-2 mb-1" type="reset" id="reset" name="reset">
                                    {{ trans('global.reset') }}
                                </button>
                                <button class="btn btn-search mb-1" type="button" id="search" name="search">
                                    <i class="fas fa-search"></i> {{ trans('global.search') }}
                                </button>
                            </div>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <div class="card">
        <div class="card-header d-flex justify-content-between align-items-center flex-wrap">
            <h5 class="card-title mb-0">{{ trans('cruds.scheduledTask.title_singular') }} {{ trans('global.list') }}</h5>
            @can('scheduled_task_create')
                <div class="d-flex flex-wrap" style="gap: 10px;">
                    <a class="btn btn-create mb-1" href="{{ route('admin.scheduled-tasks.create') }}"
                       style="background:linear-gradient(135deg,#22c55e 0%,#16a34a 100%);box-shadow:0 4px 12px rgba(34,197,94,0.28);">
                        <i class="ri-add-line"></i> {{ trans('global.add') }} {{ trans('cruds.scheduledTask.title_singular') }}
                    </a>
                    <a class="btn btn-create mb-1" href="{{ route('admin.scheduled-tasks.quick') }}"
                       style="background:linear-gradient(135deg,#6366f1 0%,#4f46e5 100%);box-shadow:0 4px 12px rgba(99,102,241,0.28);">
                        <i class="ri-add-line"></i> Add Quick Schedule Task
                    </a>
                </div>
            @endcan
        </div>

        <div class="card-body">
            <table
                class=" table table-bordered table-striped table-hover ajaxTable datatable datatable-ScheduledTask w-100">
                <thead>
                    <tr>
                        <th width="10">

                        </th>
                        <th>
                            {{ trans('translation.task.fields.sequence') }}
                        </th>
                        <th>
                            {{ trans('cruds.scheduledTask.fields.id') }}
                        </th>
                        <th>
                            {{ trans('cruds.scheduledTask.fields.name') }}
                        </th>
                        <th>
                            {{ trans('cruds.scheduledTask.fields.status') }}
                        </th>
                        <th>
                            {{ trans('cruds.scheduledTask.fields.start_date') }}
                        </th>
                        <th>
                            {{ trans('cruds.scheduledTask.fields.end_date') }}
                        </th>
                        <!-- <th>
                            {{ trans('cruds.scheduledTask.fields.from_location') }}
                        </th> -->
                        <th>
                            {{ trans('cruds.scheduledTask.fields.to_location') }}
                        </th>
                        <th>
                            {{ trans('cruds.scheduledTask.fields.client') }}
                        </th>
                        <th>
                            {{ trans('cruds.scheduledTask.fields.selected_hour') }}
                        </th>
                        <th>
                            {{ trans('cruds.scheduledTask.fields.task_type') }}
                        </th>
                        <th>
                            {{ trans('cruds.scheduledTask.fields.days') }}
                        </th>
                        <th>
                            {{ trans('cruds.scheduledTask.fields.added_by') }}
                        </th>
                        <th>
                            {{ trans('cruds.scheduledTask.fields.driver') }}
                        </th>
                        <th>
                            &nbsp;
                        </th>
                    </tr>
                </thead>
            </table>
        </div>
    </div>
@endsection
@section('scripts')
    @parent
    <script>
        $(function() {
            // Initialize Select2 with AJAX for driver_id
            $('#driver_id').select2({
                placeholder: 'Select Driver',
                allowClear: true,
                ajax: {
                    url: "{{ route('admin.scheduled-tasks.searchDrivers') }}",
                    dataType: 'json',
                    delay: 250,
                    data: function (params) {
                        return {
                            q: params.term,
                            page: params.page || 1
                        };
                    },
                    processResults: function (data) {
                        return {
                            results: data.results
                        };
                    },
                    cache: true
                },
                minimumInputLength: 0
            });

            // Initialize Select2 with AJAX for from_location
            $('#from_location').select2({
                placeholder: 'Select Location',
                allowClear: true,
                ajax: {
                    url: "{{ route('admin.scheduled-tasks.searchLocations') }}",
                    dataType: 'json',
                    delay: 250,
                    data: function (params) {
                        return {
                            q: params.term,
                            page: params.page || 1
                        };
                    },
                    processResults: function (data) {
                        return {
                            results: data.results
                        };
                    },
                    cache: true
                },
                minimumInputLength: 0
            });

            // Initialize Select2 with AJAX for to_location
            $('#to_location').select2({
                placeholder: 'Select Location',
                allowClear: true,
                ajax: {
                    url: "{{ route('admin.scheduled-tasks.searchLocations') }}",
                    dataType: 'json',
                    delay: 250,
                    data: function (params) {
                        return {
                            q: params.term,
                            page: params.page || 1
                        };
                    },
                    processResults: function (data) {
                        return {
                            results: data.results
                        };
                    },
                    cache: true
                },
                minimumInputLength: 0
            });

            let dtButtons = $.extend(true, [], $.fn.dataTable.defaults.buttons)
            @can('can-delete')
                let deleteButtonTrans = '{{ trans('global.datatables.delete') }}';
                let deleteButton = {
                    text: deleteButtonTrans,
                    url: "{{ route('admin.scheduled-tasks.massDestroy') }}",
                    className: 'btn-danger',
                    action: function(e, dt, node, config) {
                        var ids = $.map(dt.rows({
                            selected: true
                        }).data(), function(entry) {
                            return entry.id
                        });

                        if (ids.length === 0) {
                            alert('{{ trans('global.datatables.zero_selected') }}')

                            return
                        }

                        if (confirm('{{ trans('global.areYouSure') }}')) {
                            $.ajax({
                                    headers: {
                                        'x-csrf-token': _token
                                    },
                                    method: 'POST',
                                    url: config.url,
                                    data: {
                                        ids: ids,
                                        _method: 'DELETE'
                                    }
                                })
                                .done(function() {
                                    location.reload()
                                })
                        }
                    }
                }
                // dtButtons.push(deleteButton)
            @endcan
            var scheduleRoute = "{{ route('admin.scheduled-tasks.show', ['__ID__']) }}";
            let dtOverrideGlobals = {
                buttons: dtButtons,
                processing: true,
                serverSide: true,
                searching: false,
                retrieve: true,
                aaSorting: [],
                ajax: {
                    url: "{{ route('admin.scheduled-tasks.index') }}",
                    data: function(d) {
                        d.client_id = $("#client_id").val();
                        d.to_location = $("#to_location").val();
                        d.driver_id = $("#driver_id").val();
                        d.from_location = $("#from_location").val();
                        d.date_from = $("#date_from").val();
                        d.date_to = $("#date_to").val();
                    }
                },

                columns: [{
                        data: 'placeholder',
                        name: 'placeholder'
                    },
                    {
                        data: 'sequence',
                        name: 'sequence'
                    },
                    {
                        data: 'id',
                        name: 'id'
                    },
                    {
                        data: 'name',
                        name: 'name'
                    },
                    {
                        data: 'status',
                        name: 'status'
                    },
                    {
                        data: 'start_date',
                        name: 'start_date'
                    },
                    {
                        data: 'end_date',
                        name: 'end_date'
                    },/*
                    {
                        data: 'from_location_name',
                        name: 'from_location.name'
                    },*/
                    {
                        data: 'to_location_name',
                        name: 'to_location.name'
                    },
                    {
                        data: 'client_status',
                        name: 'client.english_name'
                    },
                    {
                        data: 'selected_hour',
                        name: 'selected_hour'
                    },
                    {
                        data: 'task_type',
                        name: 'task_type'
                    },
                    {
                        data: 'day',
                        name: 'day'
                    },
                    {
                        data: 'added_by',
                        name: 'added_by'
                    },
                    {
                        data: 'driver_name',
                        name: 'driver.name'
                    },
                    { data: 'id', name: 'action',title:'action',render: function (data,type,row)
                        {
                            var url = scheduleRoute.replace('__ID__', row['id']);
                            return '<div class="d-flex gap-1 justify-content-center">' +
                                '<a href="' + url + '" class="btn btn-soft-info btn-sm" title="{{ trans('global.view') }}">' +
                                '<i class="ri-eye-fill"></i>' +
                                '</a></div>';
                        }
                    }/*,

                    {
                        data: 'actions',
                        name: '{{ trans('global.actions') }}'
                    }*/
                ],
                orderCellsTop: true,
                order: [
                    [1, 'desc']
                ],
                pageLength: 100,
            };
            let table = $('.datatable-ScheduledTask').DataTable(dtOverrideGlobals);
            $('a[data-toggle="tab"]').on('shown.bs.tab click', function(e) {
                $($.fn.dataTable.tables(true)).DataTable()
                    .columns.adjust();
            });

            $("#search").click(function() {
                // alert("button");
                table.draw();
            });

        });
    </script>
@endsection
