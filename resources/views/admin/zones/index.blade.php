
@extends('layouts.master')
@section('title')
    @lang('translation.zones')
@endsection
@section('content')
    @component('components.breadcrumb')
        @slot('li_1')
        @lang('translation.appname')
        @endslot
        @slot('title')
            @lang('translation.zones')
        @endslot
    @endcomponent


<div class="card">
    <div class="card-header d-flex justify-content-between align-items-center flex-wrap">
        <h5 class="card-title mb-0">{{ trans('cruds.zone.title_singular') }} {{ trans('global.list') }}</h5>
        @can('zone_create')
            <a class="btn btn-create mb-1" href="{{ route('admin.zones.create') }}">
                <i class="ri-add-line"></i> {{ trans('global.add') }} {{ trans('cruds.zone.title_singular') }}
            </a>
        @endcan
    </div>

    <div class="card-body">
        <div class="table-responsive">
            <table class=" table table-bordered table-striped table-hover datatable datatable-Zone">
                <thead>
                    <tr>
                        <th width="10">

                        </th>
                        <th>
                            {{ trans('cruds.zone.fields.id') }}
                        </th>
                        <!-- <th>
                            {{ trans('cruds.zone.fields.area') }}
                        </th> -->
                        <th>
                            {{ trans('cruds.zone.fields.name') }}
                        </th>
                        <th>
                            &nbsp;
                        </th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($zones as $key => $zone)
                        <tr data-entry-id="{{ $zone->id }}">
                            <td>

                            </td>
                            <td>
                                {{ $zone->id ?? '' }}
                            </td>
                            <!-- <td>
                                {{ $zone->area ?? '' }}
                            </td> -->
                            <td>
                                {{ $zone->name ?? '' }}
                            </td>
                            <td>
                                <div class="d-flex gap-1 justify-content-center">
                                    @can('zone_show')
                                        <a class="btn btn-soft-info btn-sm"
                                            href="{{ route('admin.zones.show', $zone->id) }}"
                                            title="{{ trans('global.view') }}">
                                            <i class="ri-eye-fill"></i>
                                        </a>
                                    @endcan

                                    @can('zone_edit')
                                        <a class="btn btn-soft-primary btn-sm"
                                            href="{{ route('admin.zones.edit', $zone->id) }}"
                                            title="{{ trans('global.edit') }}">
                                            <i class="ri-edit-2-fill"></i>
                                        </a>
                                    @endcan

                                    @can('can-delete')
                                        <form action="{{ route('admin.zones.destroy', $zone->id) }}" method="POST"
                                            onsubmit="return confirm('{{ trans('global.areYouSure') }}');"
                                            style="display: inline-block;">
                                            <input type="hidden" name="_method" value="DELETE">
                                            <input type="hidden" name="_token" value="{{ csrf_token() }}">
                                            <button type="submit" class="btn btn-soft-danger btn-sm"
                                                title="{{ trans('global.delete') }}">
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
    url: "{{ route('admin.zones.massDestroy') }}",
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
  let table = $('.datatable-Zone:not(.ajaxTable)').DataTable({ buttons: dtButtons })
  $('a[data-toggle="tab"]').on('shown.bs.tab click', function(e){
      $($.fn.dataTable.tables(true)).DataTable()
          .columns.adjust();
  });
  
})

</script>
@endsection