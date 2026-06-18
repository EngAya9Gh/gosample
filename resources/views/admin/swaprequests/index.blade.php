@extends('layouts.master')
@section('content')
    <div class="row">
        <div class="col-lg-12">
            <div class="card modern-filter-card">
                <div class="card-header">
                    <h4 class="card-title mb-0">Filters</h4>
                </div>
                <form action="{{ route('admin.reportExport') }}" method="POST">
                    @csrf
                    <div class="card-body">
                        <div class="row">
                            <div class="col-lg-4 mb-3">
                                <label for="date_from">{{ trans('translation.task.fields.date_from') }}</label>
                                <input class="form-control" type="datetime-local" name="date_from" id="date_from">
                            </div>
                            <div class="col-lg-4 mb-3">
                                <label for="date_to">{{ trans('translation.task.fields.date_to') }}</label>
                                <input class="form-control" type="datetime-local" name="date_to" id="date_to">
                            </div>
                            <div class="col-lg-4 mb-3">
                                <label for="task_id">Task ID</label>
                                <input class="form-control" type="text" name="task_id" id="task_id" placeholder="Task ID">
                            </div>

                            <div class="col-lg-12 d-flex justify-content-end mt-2 flex-wrap">
                                <button class="btn btn-reset mr-2 mb-1" type="reset" id="reset">
                                    {{ trans('global.reset') }}
                                </button>
                                <button class="btn btn-search mb-1" type="button" id="search">
                                    <i class="fas fa-search"></i> {{ trans('translation.search') }}
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
            <h5 class="card-title mb-0">{{ trans('cruds.swaprequest.title_singular') }} {{ trans('global.list') }}</h5>
            @can('swaprequest_create')
                <a class="btn btn-create mb-1" href="{{ route('admin.swaprequests.create') }}">
                    <i class="ri-add-line"></i> {{ trans('global.add') }} {{ trans('cruds.swaprequest.title_singular') }}
                </a>
            @endcan
        </div>

        <div class="card-body">
            <table class=" table table-bordered table-striped table-hover ajaxTable datatable datatable-Swaprequest w-100">
                <thead>
                    <tr>
                        <th width="10">

                        </th>
                        <th>
                            {{ trans('cruds.swaprequest.fields.id') }}
                        </th>
                        <th>
                            {{ trans('cruds.swaprequest.fields.task') }}
                        </th>
                        <th>
                            {{ trans('translation.task.fields.status') }}
                        </th>
                        <th>
                            {{ trans('translation.swaprequest.fields.driver_a') }}
                        </th>
                        <th>
                            {{ trans('cruds.swaprequest.fields.driver') }}
                        </th>
                        <th>
                            {{ trans('cruds.swaprequest.fields.status') }}
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
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script>
        $(function() {
            let dtButtons = $.extend(true, [], $.fn.dataTable.defaults.buttons)
            @can('swaprequest_delete')
                let deleteButtonTrans = '{{ trans('global.datatables.delete') }}';
                let deleteButton = {
                    text: deleteButtonTrans,
                    url: "{{ route('admin.swaprequests.massDestroy') }}",
                    className: 'btn-danger',
                    action: function(e, dt, node, config) {
                        var selectedData = dt.rows('.selected').data().toArray();
                        
                        if (selectedData.length === 0) {
                            Swal.fire({
                                icon: 'warning',
                                title: '{{ trans('global.datatables.zero_selected') }}',
                                text: 'Please select at least one row by checking the checkbox in the first column.',
                                showConfirmButton: true,
                            });
                            return;
                        }

                        var ids = selectedData.map(function(entry) { return entry.id; });
                        var taskIds = selectedData.map(function(entry) { return entry.task_id; });
                        
                        var displayTasks = taskIds.slice(0, 10).join(' - ');
                        if (taskIds.length > 10) {
                            displayTasks += ' ... and ' + (taskIds.length - 10) + ' more';
                        }

                        Swal.fire({
                            title: '{{ trans('global.areYouSure') }}',
                            html: 'Are you sure you want to delete the swap requests for the following Tasks?<br><br><b style="color:#d33;">' + displayTasks + '</b>',
                            icon: 'warning',
                            showCancelButton: true,
                            confirmButtonColor: '#d33',
                            cancelButtonColor: '#3085d6',
                            confirmButtonText: 'Yes, delete them!',
                            cancelButtonText: 'Cancel'
                        }).then((result) => {
                            if (result.isConfirmed) {
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
                                        Swal.fire(
                                            'Deleted!',
                                            'The selected swap requests have been deleted.',
                                            'success'
                                        ).then(() => {
                                            location.reload();
                                        });
                                    })
                                    .fail(function() {
                                        Swal.fire(
                                            'Error!',
                                            'An error occurred while deleting the requests.',
                                            'error'
                                        );
                                    });
                            }
                        });
                    }
                }
                dtButtons.push(deleteButton)
            @endcan

            let dtOverrideGlobals = {
                buttons: dtButtons,
                processing: true,
                serverSide: true,
                retrieve: true,
                aaSorting: [],
                select: {
                    style: 'multi+shift',
                    selector: 'td:first-child'
                },
                columnDefs: [
                    {
                        orderable: false,
                        className: 'select-checkbox',
                        targets: 0
                    },
                    {
                        orderable: false,
                        searchable: false,
                        targets: -1
                    }
                ],
                // ajax: "{{ route('admin.swaprequests.index') }}",

                ajax: {
                    url: "{{ route('admin.swaprequests.index') }}",
                    data: function(d) {
                        d.date_from = $("#date_from").val();
                        d.date_to = $("#date_to").val();
                        d.task_id = $('#task_id').val();
                        // d.delayed_reason = $('#delayed_reason').val();
                    }
                },
                columns: [{
                        data: 'placeholder',
                        name: 'placeholder'
                    },
                    {
                        data: 'id',
                        name: 'id'
                    },
                    {
                        data: 'task_id',
                        name: 'task_id'
                    },
                    {
                        data: 'task.status',
                        name: 'task.status'
                    },
                    {
                        data: 'driverA',
                        name: 'driverA.name'
                    },
                    {
                        data: 'driver_name',
                        name: 'driver.name'
                    },

                    {
                        data: 'status',
                        name: 'status'
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
            };
            let table = $('.datatable-Swaprequest').DataTable(dtOverrideGlobals);
            $('a[data-toggle="tab"]').on('shown.bs.tab click', function(e) {
                $($.fn.dataTable.tables(true)).DataTable()
                    .columns.adjust();
            });
            $("#search").click(function() {
                // alert("button");
                table.draw();
            });
            $("#reset").click(function() {
                $("#date_from").val('');
                $("#date_to").val('');
                $("#task_id").val('');
                table.draw();
            });

        });
    </script>
@endsection
