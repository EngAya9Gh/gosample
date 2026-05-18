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
                                <label for="imei">{{ trans('cruds.car.fields.imei') }}</label>
                                <input class="form-control" type="text" name="imei" id="imei" placeholder="GPS tracker IMEI">
                            </div>
                            <div class="col-lg-4 mb-3">
                                <label for="plate_number">{{ trans('cruds.car.fields.plate_number') }}</label>
                                <input class="form-control" type="text" name="plate_number" id="plate_number" placeholder="e.g. ABC-1234">
                            </div>
                            <div class="col-lg-4 mb-3">
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
        <div class="card-header d-flex justify-content-between align-items-center flex-wrap">
            <h5 class="card-title mb-0">{{ trans('cruds.car.title_singular') }} {{ trans('global.list') }}</h5>
            @can('car_create')
                <a class="btn btn-create mb-1" href="{{ route('admin.cars.create') }}">
                    <i class="ri-add-line"></i> {{ trans('global.add') }} {{ trans('cruds.car.title_singular') }}
                </a>
            @endcan
        </div>

        <div class="card-body">
            <table class=" table table-bordered table-striped table-hover ajaxTable datatable datatable-Car w-100">
                <thead>
                    <tr>
                        <th width="10">

                        </th>
                        <th>
                            {{ trans('cruds.car.fields.id') }}
                        </th>
                        <th>
                            {{ trans('cruds.car.fields.driver') }}
                        </th>
                        <th>
                            {{ trans('cruds.driver.fields.mobile') }}
                        </th>
                        <th>
                            {{ trans('cruds.car.fields.imei') }}
                        </th>
                        <th>
                            {{ trans('cruds.car.fields.plate_number') }}
                        </th>
                        <th>
                            {{ trans('cruds.car.fields.model') }}
                        </th>
                        <th>
                            {{ trans('cruds.car.fields.color') }}
                        </th>
                        <th>
                            {{ trans('cruds.car.fields.contact_person') }}
                        </th>
                        <th>
                            {{ trans('translation.task.fields.status') }}
                        </th>
                        <th>
                            {{ trans('cruds.car.fields.description') }}
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
            @can('can-delete')
                let deleteButtonTrans = '{{ trans('global.datatables.delete') }}';
                let deleteButton = {
                    text: deleteButtonTrans,
                    url: "{{ route('admin.cars.massDestroy') }}",
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
                    url: "{{ route('admin.cars.index') }}",
                    data: function(d) {
                        d.date_from = $("#date_from").val();
                        d.date_to = $("#date_to").val();
                        d.imei = $('#imei').val();
                        d.plate_number = $('#plate_number').val();
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
                        data: 'driver_name',
                        name: 'driver.name'
                    },
                    {
                        data: 'driver.mobile',
                        name: 'driver.mobile'
                    },
                    {
                        data: 'imei',
                        name: 'imei'
                    },
                    {
                        data: 'plate_number',
                        name: 'plate_number'
                    },
                    {
                        data: 'model',
                        name: 'model'
                    },
                    {
                        data: 'color',
                        name: 'color'
                    },
                    {
                        data: 'contact_person',
                        name: 'contact_person'
                    },
                    {
                        data: 'status',
                        name: 'status'
                    },
                    {
                        data: 'description',
                        name: 'description'
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
            let table = $('.datatable-Car').DataTable(dtOverrideGlobals);
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
