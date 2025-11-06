@extends('layouts.master')
@section('content')
    <style>
        #driverTasksList li {
            cursor: move;
        }
    </style>
    @can('driver_create')
        <div style="margin-bottom: 10px;" class="row">
            <div class="col-lg-12">
                <a class="btn btn-success" href="{{ route('admin.drivers.create') }}">
                    {{ trans('global.add') }} {{ trans('cruds.driver.title_singular') }}
                </a>
            </div>
        </div>
    @endcan
    <div class="row">
        <div class="col-lg-12">
            <div class="card">
                <div class="card-header">
                    <h4 class="card-title mb-0">Filters</h4>
                </div>
                <form action="{{ route('admin.reportExport') }}" method="POST">
                    @csrf
                    <div class="card-body">
                        <div class="row">
                            <div class="col-lg-3">
                                <label class="required"
                                    for="date_from">{{ trans('translation.task.fields.date_from') }}</label>
                                <input class="form-control" type="datetime-local" name="date_from" id="date_from">
                            </div>
                            <div class="col-lg-3">
                                <label class="required"
                                    for="date_to">{{ trans('translation.task.fields.date_to') }}</label>
                                <input class="form-control" type="datetime-local" name="date_to" id="date_to">
                            </div>
                            <div class="col-lg-3">
                                <label for="mobile">{{ trans('cruds.driver.fields.mobile') }}</label>
                                <input class="form-control" type="text" name="mobile" id="mobile">
                            </div>
                            <div class="col-lg-3">
                                <label for="statusInput">{{ trans('translation.task.fields.status') }}</label>
                                <select class="form-control" name="status" id="statusInput">
                                    <option value="1">Enable</option>
                                    <option value="0">Disable</option>
                                </select>
                            </div>

                        </div>

                        <div class="row">

                            <div class="col-lg-12 d-flex justify-content-between mt-2">
                                <button class="btn btn-danger" type="button" id="search">
                                    {{ trans('translation.search') }}
                                </button>

                            </div>


                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>


    <div class="card">
        <div class="card-header">
            {{ trans('cruds.driver.title_singular') }} {{ trans('global.list') }}
        </div>




        <div class="card-body">
            <table class=" table table-bordered table-striped table-hover ajaxTable datatable datatable-Driver w-100">
                <thead>
                    <tr>
                        <th width="10">

                        </th>
                        <th>
                            {{ trans('cruds.driver.fields.id') }}
                        </th>
                        <th>
                            {{ trans('cruds.driver.fields.name') }}
                        </th>
                        <th>
                            {{ trans('cruds.driver.fields.status') }}
                        </th>
                        <th>
                            {{ trans('cruds.driver.fields.username') }}
                        </th>
                        <th>
                            {{ trans('cruds.driver.fields.mobile') }}
                        </th>
                        <th>
                            {{ trans('cruds.driver.fields.email') }}
                        </th>
                        <th>
                            {{ trans('cruds.driver.fields.language') }}
                        </th>
                        <th>
                            {{ trans('cruds.driver.fields.lat') }}
                        </th>
                        <th>
                            {{ trans('cruds.driver.fields.lng') }}
                        </th>
                        <th>
                            {{ trans('cruds.driver.fields.accepted_terms') }}
                        </th>
                        <th>
                            &nbsp;
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
            let dtButtons = $.extend(true, [], $.fn.dataTable.defaults.buttons)
            @can('driver_delete')
                let deleteButtonTrans = '{{ trans('global.datatables.delete') }}';
                let deleteButton = {
                    text: deleteButtonTrans,
                    url: "{{ route('admin.drivers.massDestroy') }}",
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

            let dtOverrideGlobals = {
                buttons: dtButtons,
                processing: true,
                serverSide: true,
                retrieve: true,
                aaSorting: [],
                ajax: {
                    url: "{{ route('admin.drivers.index') }}",
                    data: function(d) {
                        d.date_from = $("#date_from").val();
                        d.date_to = $("#date_to").val();
                        d.keyword = $('#keyword').val();
                        d.status = $('#statusInput').val();
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
                        data: 'name',
                        name: 'name'
                    },
                    {
                        data: 'status',
                        name: 'status'
                    },
                    {
                        data: 'username',
                        name: 'username'
                    },
                    {
                        data: 'mobile',
                        name: 'mobile'
                    },
                    {
                        data: 'email',
                        name: 'email'
                    },
                    {
                        data: 'language',
                        name: 'language'
                    },
                    {
                        data: 'lat',
                        name: 'lat'
                    },
                    {
                        data: 'lng',
                        name: 'lng'
                    },
                    {
                        data: 'accepted_terms',
                        name: 'accepted_terms'
                    },
                    {
                        data: 'view_tasks',
                        name: '{{ trans('global.actions') }}',
                        render: function (data, type, row) {
                            return `
                                <button class="btn btn-sm btn-primary view-tasks" data-id="${row.id}">
                                    <i class="fas fa-tasks"></i> Tasks
                                </button>
                            `;
                        }
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
            let table = $('.datatable-Driver').DataTable(dtOverrideGlobals);
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
    <!-- Driver Tasks Modal -->
    <div class="modal fade" id="driverTasksModal" tabindex="-1" aria-labelledby="driverTasksModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="driverTasksModalLabel">Driver Tasks</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                <span>&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <ul id="driverTasksList" class="list-group">
                    <!-- tasks will be loaded here -->
                </ul>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">{{ trans('global.cancel') }}</button>
                <button type="button" class="btn btn-success" id="saveTaskOrder">Save Order</button>
            </div>
            </div>
        </div>
    </div>
    <!-- <script src="https://code.jquery.com/ui/1.13.2/jquery-ui.min.js"></script> -->
    <!-- <link rel="stylesheet" href="https://code.jquery.com/ui/1.13.2/themes/base/jquery-ui.css"> -->
    <script>
        // Modal + Sortable Tasks Logic
        $(document).on('click', '.view-tasks', function() {
            const driverId = $(this).data('id');
            $('#driverTasksModal').modal('show');
            $('#driverTasksList').html('<li class="list-group-item text-center">Loading...</li>');

            $.get(`/admin/drivers/${driverId}/tasks`, function(response) {
                let tasksHtml = '';
                response.tasks.forEach(task => {
                    // tasksHtml += `
                    //     <li class="list-group-item d-flex justify-content-between align-items-center" data-id="${task.id}">
                    //         <div>
                    //             <strong>${task.id}</strong><br>
                    //             <strong>${task.from_location_name}</strong><br>
                    //             <strong>${task.to_location_name}</strong><br>
                    //             ETA: ${task.eta ?? '-'}
                    //         </div>
                    //         <span class="badge badge-info">#${task.priority ?? '-'}</span>
                    //     </li>`;
                    tasksHtml += `
                    <li class="list-group-item d-flex justify-content-between align-items-center" data-id="${task.id}">
                        <span class="handle mr-2 text-muted"><i class="fas fa-bars"></i></span>
                        <div>
                            <strong>${task.id}</strong><br>
                            <strong>${task.from_location_name}</strong><br>
                            <strong>${task.to_location_name}</strong><br>
                            ETA: ${task.eta ?? '-'}
                        </div>
                        <span class="badge badge-info">#${task.priority ?? '-'}</span>
                    </li>`;
                });
                $('#driverTasksList').html(tasksHtml);

                // Make the list sortable
                // $('#driverTasksList').sortable({
                //     placeholder: 'list-group-item bg-light',
                //     update: function(event, ui) {
                //         $('#saveTaskOrder').prop('disabled', false);
                //     }
                // });
                $('#driverTasksList').sortable({
                    handle: '.handle',
                    placeholder: 'list-group-item bg-light',
                    update: function() {
                        $('#saveTaskOrder').prop('disabled', false);
                    }
                });
            });
        });

        $('#saveTaskOrder').on('click', function() {
            const order = [];
            $('#driverTasksList li').each(function(index) {
                order.push({
                    id: $(this).data('id'),
                    priority: index + 1
                });
            });

            const driverId = $('.view-tasks[data-id]').data('id');

            $.ajax({
                url: `/admin/drivers/${driverId}/tasks/reorder`,
                method: 'POST',
                data: {
                    _token: '{{ csrf_token() }}',
                    order: order
                },
                success: function() {
                    alert('Task order saved successfully!');
                    $('#driverTasksModal').modal('hide');
                },
                error: function() {
                    alert('Failed to save order.');
                }
            });
        });

    </script>
@endsection
