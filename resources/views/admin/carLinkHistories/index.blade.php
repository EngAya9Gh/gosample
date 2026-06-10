@extends('layouts.master')
@section('content')

<div class="card">
    <div class="card-header">
        {{ trans('cruds.carLinkHistory.title_singular') }} {{ trans('translation.list') }}
    </div>

    <div class="card-body">
        <div class="table-responsive">
            <table class=" table table-bordered table-striped table-hover datatable datatable-CarLinkHistory">
                <thead>
                    <tr>
                        <th width="10">

                        </th>
                        <th>
                            {{ trans('cruds.carLinkHistory.fields.id') }}
                        </th>
                        <th>
                            {{ trans('cruds.carLinkHistory.fields.driver') }}
                        </th>
                        <th>
                            {{ trans('cruds.carLinkHistory.fields.car') }}
                        </th>
                        <th>
                            {{ trans('cruds.carLinkHistory.fields.action') }}
                        </th>
                        <th>
                            &nbsp;
                        </th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($carLinkHistories as $key => $carLinkHistory)
                        <tr data-entry-id="{{ $carLinkHistory->id }}">
                            <td>

                            </td>
                            <td>
                                {{ $carLinkHistory->id ?? '' }}
                            </td>
                            <td>
                                {{ $carLinkHistory->driver->name ?? '' }}
                            </td>
                            <td>
                                {{ $carLinkHistory->car->imei ?? '' }}
                            </td>
                            <td>
                                {{ App\Models\CarLinkHistory::ACTION_SELECT[$carLinkHistory->action] ?? '' }}
                            </td>
                            <td>
                                <div class="d-flex gap-1 justify-content-center">
                                    @can('car_link_history_show')
                                        <a class="btn btn-soft-info btn-sm" href="{{ route('admin.car-link-histories.show', $carLinkHistory->id) }}" title="{{ trans('translation.view') }}">
                                            <i class="ri-eye-fill"></i>
                                        </a>
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


  $.extend(true, $.fn.dataTable.defaults, {
    orderCellsTop: true,
    order: [[ 1, 'desc' ]],
    pageLength: 100,
  });
  let table = $('.datatable-CarLinkHistory:not(.ajaxTable)').DataTable({ buttons: dtButtons })
  $('a[data-toggle="tab"]').on('shown.bs.tab click', function(e){
      $($.fn.dataTable.tables(true)).DataTable()
          .columns.adjust();
  });
  
})

</script>
@endsection