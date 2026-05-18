@extends('layouts.master')
@section('content')
    <div class="card">
        <div class="card-header d-flex justify-content-between align-items-center flex-wrap">
            <h5 class="card-title mb-0">{{ trans('cruds.permission.title_singular') }} {{ trans('global.list') }}</h5>
            @can('permission_create')
                @if (Auth::guard('web')->check())
                    <a class="btn btn-create mb-1" href="{{ route('admin.permissions.create') }}">
                        <i class="ri-add-line"></i> {{ trans('global.add') }} {{ trans('cruds.permission.title_singular') }}
                    </a>
                @elseif(Auth::guard('client_users')->check())
                    <a class="btn btn-create mb-1" href="{{ route('admin.client-permissions.create') }}">
                        <i class="ri-add-line"></i> {{ trans('global.add') }} {{ trans('cruds.permission.title_singular') }}
                    </a>
                @endif
            @endcan
        </div>

        <div class="card-body">
            <div class="table-responsive">
                <table class=" table table-bordered table-striped table-hover datatable datatable-Permission"
                    width="100%">
                    <thead>
                        <tr>
                            <th width="10">

                            </th>
                            <th>
                                {{ trans('cruds.permission.fields.id') }}
                            </th>
                            <th>
                                {{ trans('cruds.permission.fields.name') }}
                            </th>
                            <th>
                                {{ trans('cruds.permission.fields.guard_name') }}
                            </th>
                            <th>
                                &nbsp;
                            </th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($permissions as $key => $permission)
                            <tr data-entry-id="{{ $permission->id }}">
                                <td>

                                </td>
                                <td>
                                    {{ $permission->id ?? '' }}
                                </td>
                                <td>
                                    {{ $permission->name ?? '' }}
                                </td>
                                <td>
                                    {{ $permission->guard_name ?? '' }}
                                </td>
                                <td>
                                    <div class="d-flex gap-1 justify-content-center">
                                        @can('permission_show')
                                            @if (Auth::guard('web')->check())
                                                <a class="btn btn-soft-info btn-sm view-item-btn"
                                                    href="{{ route('admin.permissions.show', $permission->id) }}"
                                                    title="{{ trans('global.view') }}">
                                                    <i class="ri-eye-fill"></i>
                                                </a>
                                            @elseif(Auth::guard('client_users')->check())
                                                <a class="btn btn-soft-info btn-sm view-item-btn"
                                                    href="{{ route('admin.client-permissions.show', $permission->id) }}"
                                                    title="{{ trans('global.view') }}">
                                                    <i class="ri-eye-fill"></i>
                                                </a>
                                            @endif
                                        @endcan

                                        @can('permission_edit')
                                            @if (Auth::guard('web')->check())
                                                <a class="btn btn-soft-primary btn-sm edit-item-btn"
                                                    href="{{ route('admin.permissions.edit', $permission->id) }}"
                                                    title="{{ trans('global.edit') }}">
                                                    <i class="ri-edit-2-fill"></i>
                                                </a>
                                            @elseif(Auth::guard('client_users')->check())
                                                <a class="btn btn-soft-primary btn-sm edit-item-btn"
                                                    href="{{ route('admin.client-permissions.edit', $permission->id) }}"
                                                    title="{{ trans('global.edit') }}">
                                                    <i class="ri-edit-2-fill"></i>
                                                </a>
                                            @endif
                                        @endcan

                                        @can('can-delete')
                                            @if (Auth::guard('web')->check())
                                                <form action="{{ route('admin.permissions.destroy', $permission->id) }}"
                                                    method="POST" onsubmit="return confirm('{{ trans('global.areYouSure') }}');"
                                                    style="display: inline-block;">
                                                    <input type="hidden" name="_method" value="DELETE">
                                                    <input type="hidden" name="_token" value="{{ csrf_token() }}">
                                                    <button type="submit" class="btn btn-soft-danger btn-sm remove-item-btn"
                                                        title="{{ trans('global.delete') }}">
                                                        <i class="ri-delete-bin-fill"></i>
                                                    </button>
                                                </form>
                                            @elseif(Auth::guard('client_users')->check())
                                                <form action="{{ route('admin.client-permissions.destroy', $permission->id) }}"
                                                    method="POST" onsubmit="return confirm('{{ trans('global.areYouSure') }}');"
                                                    style="display: inline-block;">
                                                    <input type="hidden" name="_method" value="DELETE">
                                                    <input type="hidden" name="_token" value="{{ csrf_token() }}">
                                                    <button type="submit" class="btn btn-soft-danger btn-sm remove-item-btn"
                                                        title="{{ trans('global.delete') }}">
                                                        <i class="ri-delete-bin-fill"></i>
                                                    </button>
                                                </form>
                                            @endif
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
                let deleteButtonTrans = '{{ trans('global.datatables.delete') }}'
                let deleteButton = {
                    text: deleteButtonTrans,
                    url: "{{ route('admin.permissions.massDestroy') }}",
                    className: 'btn-danger',
                    action: function(e, dt, node, config) {
                        var ids = $.map(dt.rows({
                            selected: true
                        }).nodes(), function(entry) {
                            return $(entry).data('entry-id')
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

            $.extend(true, $.fn.dataTable.defaults, {
                orderCellsTop: true,
                order: [
                    [1, 'desc']
                ],
                pageLength: 100,
            });
            let table = $('.datatable-Permission:not(.ajaxTable)').DataTable({
                buttons: dtButtons
            })
            $('a[data-toggle="tab"]').on('shown.bs.tab click', function(e) {
                $($.fn.dataTable.tables(true)).DataTable()
                    .columns.adjust();
            });

        })
    </script>
@endsection
