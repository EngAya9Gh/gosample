@extends('layouts.master')
@section('content')
    <style>
        #driverTasksList li {
            cursor: move;
        }
        .sortable-item {
            background-color: #fff;
            margin-bottom: 6px;
            border: 1px solid #ccc;
            border-radius: 6px;
            padding: 8px;
            transition: transform 0.15s ease;
        }

        .sortable-item.dragging {
            background-color: #f8f9fa;
            opacity: 0.8;
        }

        .ui-state-highlight {
            height: 70px;
            background: #e9f3ff;
            border: 2px dashed #007bff;
            border-radius: 5px;
            margin-bottom: 6px;
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
            <div class="card modern-filter-card">
                <div class="card-header">
                    <h4 class="card-title mb-0">Filters</h4>
                </div>
                <form action="{{ route('admin.reportExport') }}" method="POST">
                    @csrf
                    <div class="card-body">
                        <div class="row">
                            <div class="col-lg-3 mb-3">
                                <label for="date_from">{{ trans('translation.task.fields.date_from') }}</label>
                                <input class="form-control" type="datetime-local" name="date_from" id="date_from">
                            </div>
                            <div class="col-lg-3 mb-3">
                                <label for="date_to">{{ trans('translation.task.fields.date_to') }}</label>
                                <input class="form-control" type="datetime-local" name="date_to" id="date_to">
                            </div>
                            <div class="col-lg-3 mb-3">
                                <label for="mobile">{{ trans('cruds.driver.fields.mobile') }}</label>
                                <input class="form-control" type="text" name="mobile" id="mobile" placeholder="e.g. 05XXXXXXXX">
                            </div>
                            <div class="col-lg-3 mb-3">
                                <label for="statusInput">{{ trans('translation.task.fields.status') }}</label>
                                <select class="form-control" name="status" id="statusInput" data-placeholder="All statuses">
                                    <option value="1" data-color="#22c55e">Enabled</option>
                                    <option value="2" data-color="#ef4444">Disabled</option>
                                </select>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-lg-12 d-flex justify-content-end mt-2">
                                <button class="btn btn-reset mr-2" type="reset" id="reset-filters">
                                    Reset
                                </button>
                                <button class="btn btn-search" type="button" id="search">
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
        <div class="card-header">
            {{ trans('cruds.driver.title_singular') }} {{ trans('global.list') }}
        </div>




        <div class="card-body">
            <table class=" table table-bordered table-striped table-hover ajaxTable datatable datatable-Driver w-100">
                <thead>
                    <tr>
                        <th width="10"></th>
                        <th>{{ trans('cruds.driver.fields.id') }}</th>
                        <th>{{ trans('cruds.driver.fields.name') }}</th>
                        <th>{{ trans('cruds.driver.fields.status') }}</th>
                        <th>{{ trans('cruds.driver.fields.username') }}</th>
                        <th>{{ trans('cruds.driver.fields.mobile') }}</th>
                        <th>{{ trans('cruds.driver.fields.email') }}</th>
                        <th>Tasks</th>
                        <th>Actions</th>
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
            @can('can-delete')
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
                aaSorting: [[1, 'desc']],
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

                columns: [
                    { data: 'placeholder', name: 'placeholder' },
                    { data: 'id', name: 'id' },
                    { data: 'name', name: 'name' },
                    { 
                        data: 'status', 
                        name: 'status',
                        render: function(data, type, row) {
                            if (data === 'Enabled') {
                                return `
                                    <div class="d-flex align-items-center">
                                        <span class="badge badge-soft-success d-flex align-items-center rounded-pill px-3 py-1">
                                            <span class="bg-success rounded-circle mr-2" style="width: 8px; height: 8px; display: inline-block; box-shadow: 0 0 8px rgba(40, 167, 69, 0.5);"></span>
                                            <span class="fw-bold" style="font-size: 0.85rem;">Enabled</span>
                                        </span>
                                    </div>
                                `;
                            } else if (data === 'Disabled') {
                                return `
                                    <div class="d-flex align-items-center">
                                        <span class="badge badge-soft-danger d-flex align-items-center rounded-pill px-3 py-1">
                                            <span class="bg-danger rounded-circle mr-2" style="width: 8px; height: 8px; display: inline-block;"></span>
                                            <span class="fw-bold" style="font-size: 0.85rem;">Disabled</span>
                                        </span>
                                    </div>
                                `;
                            }
                            return data;
                        }
                    },
                    { data: 'username', name: 'username' },
                    { data: 'mobile', name: 'mobile' },
                    { data: 'email', name: 'email' },
                    {
                        data: 'view_tasks',
                        name: 'Tasks',
                        render: function (data, type, row) {
                            return `<a href="/admin/drivers/${row.id}/tasks" class="btn btn-xs btn-primary shadow-sm"><i class="fas fa-tasks"></i> Tasks</a>`;
                        },
                        orderable: false,
                        searchable: false
                    },
                    { data: 'actions', name: '{{ trans('global.actions') }}', orderable: false, searchable: false }
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
    <script>
        // Modal + Sortable Tasks Logic
        // $(document).on('click', '.view-tasks', function() {
        //     const driverId = $(this).data('id');
        //     $('#driverTasksModal').modal('show');
        //     $('#driverTasksList').html('<li class="list-group-item text-center">Loading...</li>');

        //     $.get(`/admin/drivers/${driverId}/tasks`, function(response) {
        //         let tasksHtml = '';
        //         response.tasks.forEach(task => {
        //             // tasksHtml += `
        //             //     <li class="list-group-item d-flex justify-content-between align-items-center" data-id="${task.id}">
        //             //         <div>
        //             //             <strong>${task.id}</strong><br>
        //             //             <strong>${task.from_location_name}</strong><br>
        //             //             <strong>${task.to_location_name}</strong><br>
        //             //             ETA: ${task.eta ?? '-'}
        //             //         </div>
        //             //         <span class="badge badge-info">#${task.priority ?? '-'}</span>
        //             //     </li>`;
        //             tasksHtml += `
        //             <li class="list-group-item d-flex align-items-center" data-id="${task.id}">
        //                 <span class="handle mr-2 text-muted"><i class="fas fa-bars"></i></span>
        //                 <div>
        //                     <strong>${task.id}</strong><br>
        //                     <strong>${task.from_location_name}</strong><br>
        //                     <strong>${task.to_location_name}</strong><br>
        //                     ETA: ${task.eta ?? '-'}
        //                 </div>
        //                 <span class="badge badge-info">#${task.priority ?? '-'}</span>
        //             </li>`;
        //         });
        //         $('#driverTasksList').html(tasksHtml);

        //         // Make the list sortable
        //         // $('#driverTasksList').sortable({
        //         //     placeholder: 'list-group-item bg-light',
        //         //     update: function(event, ui) {
        //         //         $('#saveTaskOrder').prop('disabled', false);
        //         //     }
        //         // });
        //         $('#driverTasksList').sortable({
        //             handle: '.handle',
        //             placeholder: 'list-group-item bg-light',
        //             update: function() {
        //                 $('#saveTaskOrder').prop('disabled', false);
        //             }
        //         });
        //     });
        // });

        // $('#saveTaskOrder').on('click', function() {
        //     const order = [];
        //     $('#driverTasksList li').each(function(index) {
        //         order.push({
        //             id: $(this).data('id'),
        //             priority: index + 1
        //         });
        //     });

        //     const driverId = $('.view-tasks[data-id]').data('id');

        //     $.ajax({
        //         url: `/admin/drivers/${driverId}/tasks/reorder`,
        //         method: 'POST',
        //         data: {
        //             _token: '{{ csrf_token() }}',
        //             order: order
        //         },
        //         success: function() {
        //             alert('Task order saved successfully!');
        //             $('#driverTasksModal').modal('hide');
        //         },
        //         error: function() {
        //             alert('Failed to save order.');
        //         }
        //     });
        // });

    </script>
    <script>
        $(document).on('click', '.view-tasks', function() {
            const driverId = $(this).data('id');
            $('#driverTasksModal').modal('show');
            $('#driverTasksList').html('<li class="list-group-item text-center">Loading...</li>');

            $.get(`/admin/drivers/${driverId}/tasks`, function(response) {
                let tasksHtml = '';

                response.tasks.forEach(task => {
                    tasksHtml += `
                        <li class="list-group-item sortable-item" data-id="${task.id}">
                            <div class="d-flex align-items-center justify-content-between">
                                <div class="handle text-muted mr-3" style="cursor: grab; width: 20px;">
                                    <i class="fas fa-bars fa-lg"></i>
                                </div>
                                <div class="flex-grow-1">
                                    <strong>ID:</strong> ${task.id}<br>
                                    <strong>From:</strong> ${task.from_location_name}<br>
                                    <strong>To:</strong> ${task.to_location_name}<br>
                                    ETA: ${task.eta ?? '-'}
                                </div>
                                <span class="badge badge-info">#${task.priority ?? '-'}</span>
                            </div>
                        </li>`;
                });

                $('#driverTasksList').html(tasksHtml);

                // نحذف أي sortable قديم
                if ($('#driverTasksList').data('ui-sortable')) {
                    $('#driverTasksList').sortable('destroy');
                }

                // نفعّل السحب فقط بعد فتح المودال فعلياً
                $('#driverTasksModal').off('shown.bs.modal').on('shown.bs.modal', function() {
                    $('#driverTasksList').sortable({
                        handle: '.handle',
                        placeholder: 'ui-state-highlight',
                        axis: 'y',
                        tolerance: 'pointer',
                        revert: 150,
                        start: function(e, ui) {
                            ui.placeholder.height(ui.item.outerHeight());
                            ui.item.addClass('dragging');
                        },
                        stop: function(e, ui) {
                            ui.item.removeClass('dragging');
                        },
                        update: function() {
                            $('#saveTaskOrder').prop('disabled', false);
                            console.log('✅ order changed');
                        }
                    }).disableSelection();

                    // منع الأيقونة من تعطيل السحب
                    $('.handle i').css('pointer-events', 'none');
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
                    alert('✅ Task order saved successfully!');
                    $('#driverTasksModal').modal('hide');
                },
                error: function() {
                    alert('❌ Failed to save order.');
                }
            });
        });
    // $(document).on('click', '.view-tasks', function() {
    //     const driverId = $(this).data('id');
    //     $('#driverTasksModal').modal('show');
    //     $('#driverTasksList').html('<li class="list-group-item text-center">Loading...</li>');

    //     $.get(`/admin/drivers/${driverId}/tasks`, function(response) {
    //         let tasksHtml = '';
    //         response.tasks.forEach(task => {
    //             tasksHtml += `
    //                 <li class="list-group-item d-flex align-items-center" data-id="${task.id}" style="cursor: grab;">
    //                     <span class="handle mr-3 text-muted" style="cursor: grab;">
    //                         <i class="fas fa-bars fa-lg"></i>
    //                     </span>
    //                     <div class="flex-grow-1">
    //                         <strong>ID:</strong> ${task.id}<br>
    //                         <strong>From:</strong> ${task.from_location_name}<br>
    //                         <strong>To:</strong> ${task.to_location_name}<br>
    //                         ETA: ${task.eta ?? '-'}
    //                     </div>
    //                     <span class="badge badge-info">#${task.priority ?? '-'}</span>
    //                 </li>`;
    //         });
    //         $('#driverTasksList').html(tasksHtml);

    //         // ✅ نضمن حذف أي sortable قديم قبل التهيئة
    //         if ($('#driverTasksList').data('ui-sortable')) {
    //             $('#driverTasksList').sortable('destroy');
    //         }

    //         // ✅ تفعيل السحب والإفلات
    //         $('#driverTasksList').sortable({
    //             handle: '.handle',
    //             placeholder: 'list-group-item bg-light',
    //             tolerance: 'pointer',
    //             axis: 'y',
    //             revert: 150,
    //             start: function(e, ui) {
    //                 ui.placeholder.height(ui.helper.outerHeight());
    //             },
    //             update: function() {
    //                 $('#saveTaskOrder').prop('disabled', false);
    //             }
    //         }).disableSelection();
    //     });
    // });

    // $('#saveTaskOrder').on('click', function() {
    //     const order = [];
    //     $('#driverTasksList li').each(function(index) {
    //         order.push({
    //             id: $(this).data('id'),
    //             priority: index + 1
    //         });
    //     });

    //     const driverId = $('.view-tasks[data-id]').data('id');

    //     $.ajax({
    //         url: `/admin/drivers/${driverId}/tasks/reorder`,
    //         method: 'POST',
    //         data: {
    //             _token: '{{ csrf_token() }}',
    //             order: order
    //         },
    //         success: function() {
    //             alert('✅ Task order saved successfully!');
    //             $('#driverTasksModal').modal('hide');
    //         },
    //         error: function() {
    //             alert('❌ Failed to save order.');
    //         }
    //     });
    // });
</script>
@endsection
