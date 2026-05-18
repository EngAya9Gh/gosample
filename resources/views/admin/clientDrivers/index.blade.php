
@extends('layouts.master')
@section('title')
    @lang('translation.drivers')
@endsection
@section('content')
    @component('components.breadcrumb')
        @slot('li_1')
        @lang('translation.appname')
        @endslot
        @slot('title')
        @lang('translation.drivers')
        @endslot
    @endcomponent
@can('client_driver_create')
    <div style="margin-bottom: 10px;" class="row">
        <div class="col-lg-12">
            <a class="btn btn-success" href="{{ route('admin.client-drivers.create') }}">
                {{ trans('global.add') }} {{ trans('cruds.clientDriver.title_singular') }}
            </a>
        </div>
    </div>
@endcan
<div class="card">
    <div class="card-header">
        {{ trans('cruds.clientDriver.title_singular') }} {{ trans('global.list') }}
    </div>

    <div class="card-body">
        <div class="table-responsive">
            <table class=" table table-bordered table-striped table-hover datatable datatable-ClientDriver">
                <thead>
                    <tr>
                        <th width="10">

                        </th>
                        <th>
                            {{ trans('cruds.clientDriver.fields.id') }}
                        </th>
                        <th>
                            {{ trans('cruds.clientDriver.fields.driver') }}
                        </th>
                        <th>
                            {{ trans('cruds.clientDriver.fields.client') }}
                        </th>
                        <th>
                            &nbsp;
                        </th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($clientDrivers as $key => $clientDriver)
                        <tr data-entry-id="{{ $clientDriver->id }}">
                            <td>

                            </td>
                            <td>
                                {{ $clientDriver->id ?? '' }}
                            </td>
                            <td>
                                {{ $clientDriver->driver->name ?? '' }}
                            </td>
                            <td>
                                {{ $clientDriver->client->english_name ?? '' }}
                            </td>
                            <td>
                                <div class="d-flex gap-1 justify-content-center">
                                    @can('client_driver_show')
                                        <a class="btn btn-soft-info btn-sm" href="{{ route('admin.client-drivers.show', $clientDriver->id) }}" title="{{ trans('global.view') }}">
                                            <i class="ri-eye-fill"></i>
                                        </a>
                                    @endcan

                                    @can('client_driver_edit')
                                        <a class="btn btn-soft-primary btn-sm" href="{{ route('admin.client-drivers.edit', $clientDriver->id) }}" title="{{ trans('global.edit') }}">
                                            <i class="ri-edit-2-fill"></i>
                                        </a>
                                    @endcan

                                    @can('can-delete')
                                        <form action="{{ route('admin.client-drivers.destroy', $clientDriver->id) }}" method="POST" onsubmit="return confirm('{{ trans('global.areYouSure') }}');" style="display: inline-block;">
                                            <input type="hidden" name="_method" value="DELETE">
                                            <input type="hidden" name="_token" value="{{ csrf_token() }}">
                                            <button type="submit" class="btn btn-soft-danger btn-sm" title="{{ trans('global.delete') }}">
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
    $(function () {
  let dtButtons = $.extend(true, [], $.fn.dataTable.defaults.buttons)
@can('can-delete')
  let deleteButtonTrans = '{{ trans('global.datatables.delete') }}'
  let deleteButton = {
    text: deleteButtonTrans,
    url: "{{ route('admin.client-drivers.massDestroy') }}",
    className: 'btn-danger',
    action: function (e, dt, node, config) {
      var ids = $.map(dt.rows({ selected: true }).nodes(), function (entry) {
          return $(entry).data('entry-id')
      });

      if (ids.length === 0) {
        alert('{{ trans('global.datatables.zero_selected') }}')

        return
      }

      if (confirm('{{ trans('global.areYouSure') }}')) {
        $.ajax({
          headers: {'x-csrf-token': _token},
          method: 'POST',
          url: config.url,
          data: { ids: ids, _method: 'DELETE' }})
          .done(function () { location.reload() })
      }
    }
  }
  dtButtons.push(deleteButton)
@endcan

  $.extend(true, $.fn.dataTable.defaults, {
    orderCellsTop: true,
    order: [[ 1, 'desc' ]],
    pageLength: 100,
  });
  let table = $('.datatable-ClientDriver:not(.ajaxTable)').DataTable({ buttons: dtButtons })
  $('a[data-toggle="tab"]').on('shown.bs.tab click', function(e){
      $($.fn.dataTable.tables(true)).DataTable()
          .columns.adjust();
  });
  
})

</script>
@endsection