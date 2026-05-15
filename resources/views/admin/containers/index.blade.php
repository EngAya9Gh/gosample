@extends('layouts.master')
@section('title')
    @lang('translation.containers')
@endsection
@section('content')
    @component('components.breadcrumb')
        @slot('li_1')
            @lang('translation.appname')
        @endslot
        @slot('title')
            @lang('translation.containers')
        @endslot
    @endcomponent

    <div class="card">
        <div class="card-header d-flex justify-content-between align-items-center flex-wrap">
            <h5 class="card-title mb-0">{{ trans('translation.container.title_singular') }} {{ trans('translation.list') }}</h5>
            @can('container_create')
                <a class="btn btn-create mb-1" href="{{ route('admin.containers.create') }}">
                    <i class="ri-add-line"></i> {{ trans('translation.add') }} {{ trans('translation.container.title_singular') }}
                </a>
            @endcan
        </div>

        <div class="card-body">
            <div class="table-responsive">
                <table class=" table table-bordered table-striped table-hover datatable datatable-Container w-100">
                    <thead>
                        <tr>
                            <th width="10">

                            </th>

                            <th width="10">

                                {{ trans('translation.task.fields.sequence') }}
                            </th>
                            <th>
                                {{ trans('translation.container.fields.id') }}
                            </th>
                            <th>
                                {{ trans('translation.container.fields.car') }}
                            </th>
                            <th>
                                {{ trans('translation.container.fields.sensor') }}
                            </th>
                            <th>
                                {{ trans('translation.container.fields.type') }}
                            </th>
                            <th>
                                {{ trans('translation.container.fields.description') }}
                            </th>
                            <th>
                                {{ trans('translation.container.fields.status') }}
                            </th>
                            <th>
                                &nbsp;
                            </th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($containers as $key => $container)
                            <tr data-entry-id="{{ $container->id }}">
                                <td>

                                </td>
                                <td>
                                    {{ $loop->iteration }}
                                </td>
                                <td>
                                    {{ $container->id ?? '' }}
                                </td>
                                <td>
                                    {{ $container->car->plate_number ?? '' }}
                                </td>
                                <td>
                                    {{ $container->imei ?? '' }}
                                </td>
                                <td>
                                    {{ App\Models\Container::TYPE_SELECT[$container->type] ?? '' }}
                                </td>
                                <td>
                                    {{ $container->description ?? '' }}
                                </td>
                                <td>
                                    @if ($container->status == 1)
                                        <span class="badge bg-success">Enabled</span>
                                    @elseif ($container->status == 2)
                                        <span class="badge bg-danger">Disabled</span>
                                    @endif
                                </td>
                                <td>
                                    <div class="d-flex gap-1 justify-content-center">
                                        @can('container_show')
                                            <a class="btn btn-soft-info btn-sm"
                                                href="{{ route('admin.containers.show', $container->id) }}"
                                                title="{{ trans('translation.view') }}">
                                                <i class="ri-eye-fill"></i>
                                            </a>
                                        @endcan

                                        @can('container_edit')
                                            <a class="btn btn-soft-primary btn-sm"
                                                href="{{ route('admin.containers.edit', $container->id) }}"
                                                title="{{ trans('translation.edit') }}">
                                                <i class="ri-edit-2-fill"></i>
                                            </a>
                                        @endcan

                                        @can('can-delete')
                                            <form action="{{ route('admin.containers.destroy', $container->id) }}" method="POST"
                                                onsubmit="return confirm('{{ trans('translation.areYouSure') }}');"
                                                style="display: inline-block;">
                                                <input type="hidden" name="_method" value="DELETE">
                                                <input type="hidden" name="_token" value="{{ csrf_token() }}">
                                                <button type="submit" class="btn btn-soft-danger btn-sm"
                                                    title="{{ trans('translation.delete') }}">
                                                    <i class="ri-delete-bin-fill"></i>
                                                </button>
                                            </form>
                                        @endcan
                                    </div>
                                </td>

                            </tr>
                        @endforeach
                    </tbody>
                </table>
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
                let deleteButtonTrans = '{{ trans('translation.datatables.delete') }}'
                let deleteButton = {
                    text: deleteButtonTrans,
                    url: "{{ route('admin.containers.massDestroy') }}",
                    className: 'btn-danger',
                    action: function(e, dt, node, config) {
                        var ids = $.map(dt.rows({
                            selected: true
                        }).nodes(), function(entry) {
                            return $(entry).data('entry-id')
                        });

                        if (ids.length === 0) {
                            alert('{{ trans('translation.datatables.zero_selected') }}')

                            return
                        }

                        if (confirm('{{ trans('translation.areYouSure') }}')) {
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

            $.extend(true, $.fn.dataTable.defaults, {
                orderCellsTop: true,
                order: [
                    [1, 'asc']
                ],
                pageLength: 100,
            });
            let table = $('.datatable-Container:not(.ajaxTable)').DataTable({
                buttons: dtButtons
            })
            $('a[data-toggle="tab"]').on('shown.bs.tab click', function(e) {
                $($.fn.dataTable.tables(true)).DataTable()
                    .columns.adjust();
            });

        })
    </script>
@endsection
