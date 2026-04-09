@extends('layouts.master')

@section('css')
    <style>
        /* Scoped checkbox column styling for this table only */
        #scheduled-tasks-children-datatable th.child-select-col,
        #scheduled-tasks-children-datatable td.child-select-col {
            width: 36px;
            min-width: 36px;
            text-align: center;
            vertical-align: middle;
        }

        #scheduled-tasks-children-datatable input.scheduled-task-child-select,
        #scheduled-tasks-children-datatable input#scheduled-task-children-select-all {
            width: 16px;
            height: 16px;
            cursor: pointer;
        }
    </style>
@endsection

@section('content')
    @can('can-delete')
        <div style="margin-bottom: 10px;" class="row">
            <div class="col-lg-12">
                @if(empty($scheduledTask->parent_id))
                <a class="btn btn-danger" href="{{ route('admin.scheduled-tasks.deleteAllParent',[$scheduledTask]) }}" onclick="return confirm('Are you sure delete all?')">
                    Delete All <b>{{$scheduledTask->name}}</b> schedule
                </a>
                @endif
            </div>
        </div>
    @endcan
    <div class="card">
        <div class="card-header">
            {{ trans('global.show') }} {{ trans('cruds.scheduledTask.title') }}
        </div>
        <div class="card-body">
            <div class="form-group">
                <div class="form-group">
                    <a class="btn btn-default" href="{{ route('admin.scheduled-tasks.index') }}">
                        {{ trans('global.back_to_list') }}
                    </a>
                </div>
                <table
                    class=" table table-bordered table-striped table-hover ajaxTable datatable w-100" id="scheduled-tasks-children-datatable">
                    <thead>
                    <tr>
                        <th class="child-select-col">
                            <input type="checkbox" id="scheduled-task-children-select-all">
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
                        <th>
                            {{ trans('cruds.scheduledTask.fields.from_location') }}
                        </th>
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
                <div class="form-group">
                    <a class="btn btn-default" href="{{ route('admin.scheduled-tasks.index') }}">
                        {{ trans('global.back_to_list') }}
                    </a>
                </div>
            </div>
        </div>
    </div>
@endsection
@section('scripts')
    @parent
    <script>
        $(function() {
            let dtButtons = $.extend(true, [], $.fn.dataTable.defaults.buttons)
            @can('can-delete')
                let deleteButtonTrans = '{{ trans('global.datatables.delete') }}';
                let deleteButton = {
                    text: deleteButtonTrans,
                    url: "{{ route('admin.scheduled-tasks.childrenMassDestroy', [$scheduledTask]) }}",
                    className: 'btn-danger',
                    action: function(e, dt, node, config) {
                        var ids = $('#scheduled-tasks-children-datatable tbody input.scheduled-task-child-select:checked')
                            .map(function() { return $(this).val(); })
                            .get();

                        if (ids.length === 0) {
                            alert('{{ trans('global.datatables.zero_selected') }}')
                            return
                        }

                        if (confirm('{{ trans('global.areYouSure') }}')) {
                            $.ajax({
                                    headers: { 'x-csrf-token': _token },
                                    method: 'POST',
                                    url: config.url,
                                    data: { ids: ids, _method: 'DELETE' }
                                })
                                .done(function() {
                                    $('#scheduled-task-children-select-all').prop('checked', false)
                                    dt.ajax.reload(null, false)
                                })
                        }
                    }
                }
                dtButtons.push(deleteButton)
            @endcan

            let table = $('#scheduled-tasks-children-datatable').DataTable({
                buttons: dtButtons,
                processing: true,
                serverSide: true,
                searching: false,
                retrieve: true,
                aaSorting: [],
                ajax: {
                    url: "{{ route('admin.scheduled-tasks.show',[$scheduledTask]) }}",
                    data: function(d) {
                        d.client_id = $("#client_id option:selected").val();
                        d.to_location = $("#to_location option:selected").val();
                        d.driver_id = $("#driver_id option:selected").val();
                        d.from_location = $("#from_location option:selected").val();
                        d.date_from = $("#date_from").val();
                        d.date_to = $("#date_to").val();
                    }
                },

                columns: [{
                    data: 'select',
                    name: 'select',
                    orderable: false,
                    searchable: false,
                    className: 'child-select-col'
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
                    },
                    {
                        data: 'from_location_name',
                        name: 'from_location.name'
                    },
                    {
                        data: 'to_location_name',
                        name: 'to_location.name'
                    },
                    {
                        data: 'client_status',
                        name: 'client.name'
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

                    {
                        data: 'actions',
                        name: '{{ trans('global.actions') }}'
                    }
                ],
                orderCellsTop: true,
                order: [
                    [1, 'desc']
                ],
                pageLength: 100,
            });

            // Header "select all" checkbox (current page only)
            $('#scheduled-task-children-select-all').on('change', function() {
                var checked = $(this).is(':checked')
                $('#scheduled-tasks-children-datatable tbody input.scheduled-task-child-select')
                    .prop('checked', checked)
            })

            // When table redraws (pagination/filter), uncheck header if not all checked
            table.on('draw', function() {
                $('#scheduled-task-children-select-all').prop('checked', false)
            })

        });
    </script>
@endsection
