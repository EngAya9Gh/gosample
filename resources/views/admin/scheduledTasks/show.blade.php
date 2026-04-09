@extends('layouts.master')

@section('css')
    <style>
        /* Scope checkbox styling to this table only */
        #scheduled-tasks-children-datatable td.select-checkbox {
            position: relative;
            cursor: pointer;
            vertical-align: middle;
        }

        #scheduled-tasks-children-datatable td.select-checkbox::before {
            content: '';
            display: inline-block;
            width: 16px;
            height: 16px;
            border: 2px solid #6c757d !important;
            border-radius: 3px;
            background: #fff !important;
            box-sizing: border-box;
        }

        /* DataTables Select marks selection on the row (`tr.selected`), but some themes mark cells too. */
        #scheduled-tasks-children-datatable tr.selected td.select-checkbox::before,
        #scheduled-tasks-children-datatable td.select-checkbox.selected::before {
            background: #0d6efd !important;
            border-color: #0d6efd !important;
        }

        #scheduled-tasks-children-datatable tr.selected td.select-checkbox::after,
        #scheduled-tasks-children-datatable td.select-checkbox.selected::after {
            content: '';
            position: absolute;
            left: 7px;
            top: 50%;
            width: 6px;
            height: 10px;
            border: solid #fff;
            border-width: 0 2px 2px 0;
            transform: translateY(-60%) rotate(45deg);
            pointer-events: none;
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
                        var ids = $.map(dt.rows({ selected: true }).data(), function(entry) {
                            return entry.id
                        });

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
                                    $('#scheduled-tasks-children-datatable').DataTable().ajax.reload()
                                })
                        }
                    }
                }
                dtButtons.push(deleteButton)
            @endcan

            $('#scheduled-tasks-children-datatable').DataTable({
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

        });
    </script>
@endsection
