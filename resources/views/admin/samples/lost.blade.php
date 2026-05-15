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
                                <label for="barcode_id">{{ trans('cruds.sample.fields.barcode') }}</label>
                                <input class="form-control" type="text" name="barcode_id" id="barcode_id" placeholder="Barcode ID">
                            </div>
                            <div class="col-lg-3 mb-3">
                                <label for="confirmed_by_client">{{ trans('cruds.sample.fields.status') }}</label>
                                <select class="form-control select2" name="confirmed_by_client" id="confirmed_by_client">
                                    <option value="LOST" {{ old('confirmed_by_client') == 'LOST' ? 'selected' : '' }}>LOST</option>
                                    <option value="YES" {{ old('confirmed_by_client') == 'YES' ? 'selected' : '' }}>RECEIVED</option>
                                    <option value="NO" {{ old('confirmed_by_client') == 'NO' ? 'selected' : '' }}>PENDING</option>
                                </select>
                            </div>

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
            <h5 class="card-title mb-0">{{ trans('cruds.sample.title_singular') }} {{ trans('global.list') }}</h5>
        </div>

        <div class="card-body">
            <table class=" table table-bordered table-striped table-hover ajaxTable datatable datatable-Sample">
                <thead>
                    <tr>
                        <th width="10">

                        </th>
                        <th>
                            {{ trans('cruds.sample.fields.id') }}
                        </th>
                        <th>
                            {{ trans('cruds.sample.fields.barcode') }}
                        </th>
                        <th>
                            {{ trans('cruds.sample.fields.location') }}
                        </th>
                        <th>
                            {{ trans('cruds.sample.fields.task') }}
                        </th>
                        <th>
                            {{ trans('cruds.sample.fields.container') }}
                        </th>
                        {{-- <th>
                            {{ trans('cruds.task.fields.driver') }}
                        </th> --}}
                        <!-- <th>
                                                                            {{ trans('cruds.sample.fields.box_count') }}
                                                                        </th>
                                                                        <th>
                                                                            {{ trans('cruds.sample.fields.sample_count') }}
                                                                        </th> -->

                        <!-- <th>
                                                                            {{ trans('cruds.sample.fields.status') }}
                                                                        </th> -->
                        <th>
                            {{ trans('cruds.sample.fields.sample_type') }}
                        </th>
                        <th>
                            {{ trans('cruds.sample.fields.temperature_type') }}
                        </th>
                        <th>
                            {{ trans('cruds.sample.fields.bag_code') }}
                        </th>
                        <th>
                            {{ trans('cruds.sample.fields.confirmed_by_client') }}
                        </th>
                        <th>
                            {{ trans('cruds.sample.fields.confirmed_by') }}
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
                    url: "{{ route('admin.samples.massDestroy') }}",
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
                //   dtButtons.push(deleteButton)
            @endcan

            let dtOverrideGlobals = {
                buttons: dtButtons,
                processing: true,
                serverSide: true,
                retrieve: true,
                aaSorting: [],
                ajax: {
                    url: "{{ route('admin.samples.lost') }}",
                    data: function(d) {
                        d.confirmed_by_client = $("#confirmed_by_client option:selected").val();
                        d.barcode_id = $("#barcode_id").val();
                        d.task_id = $("#task_id").val();
                        // d.task_id = $("#task_id option:selected").val();
                        d.date_from = $("#date_from").val();
                        d.date_to = $("#date_to").val();
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
                        data: 'barcode_id',
                        name: 'barcode_id'
                    },
                    {
                        data: 'location_name',
                        name: 'location.name'
                    },
                    {
                        data: 'task_id',
                        name: 'task_id'
                    },
                    {
                        data: 'container_imei',
                        name: 'container.imei'
                    },
                    // {
                    //     data: 'driver',
                    //     name: 'task.driver.name'
                    // },
                    // { data: 'box_count', name: 'box_count' },
                    // { data: 'sample_count', name: 'sample_count' },

                    // { data: 'status', name: 'status' },
                    {
                        data: 'sample_type',
                        name: 'sample_type'
                    },
                    {
                        data: 'temperature_type',
                        name: 'temperature_type'
                    },
                    {
                        data: 'bag_code',
                        name: 'bag_code'
                    },

                    {
                        data: 'confirmed_by_client',
                        name: 'confirmed_by_client'
                    },
                    {
                        data: 'confirmed_by',
                        name: 'confirmed_by'
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
            let table = $('.datatable-Sample').DataTable(dtOverrideGlobals);
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
