@extends('layouts.master')
@section('title')
    @lang('translation.attendances')
@endsection
@section('content')
    <style>
        .badge {
            font-weight: 600;
            padding: 5px 10px;
        }
    </style>
    @component('components.breadcrumb')
        @slot('li_1')
        @lang('translation.appname')
        @endslot
        @slot('title')
        @lang('translation.attendances')
        @endslot
    @endcomponent
<div class="card">
    <div class="card-header d-flex justify-content-between align-items-center flex-wrap">
        <h5 class="card-title mb-0">{{ trans('translation.attendance') }} {{ trans('translation.list') }}</h5>
        @can('attendance_create')
            <a class="btn btn-create mb-1" href="{{ route('admin.attendances.create') }}">
                <i class="ri-add-line"></i> {{ trans('translation.add') }} {{ trans('translation.attendance') }}
            </a>
        @endcan
    </div>

    <div class="card-body">
        <div class="table-responsive">
            <table class=" table table-bordered table-striped table-hover ajaxTable datatable datatable-Attendance">
                <thead>
                    <tr>
                        <th width="10">

                        </th>
                        <th>
                            {{ trans('translation.attendance.fields.id') }}
                        </th>
                        <th>
                            {{ trans('translation.attendance.fields.driver') }}
                        </th>
                        <th>
                            {{ trans('translation.driver.fields.mobile') }}
                        </th>
                        <th>
                            {{ trans('translation.attendance.fields.checkin_time') }}
                        </th>
                        <th>
                            {{ trans('translation.attendance.fields.checkout_time') }}
                        </th>
                        <th>Status</th>
                        <th>Delay (Min)</th>
                        <th>Overtime (Min)</th>
                        <th>Source</th>
                        <th>
                            &nbsp;
                        </th>
                    </tr>
                </thead>
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
  let deleteButtonTrans = '{{ trans('translation.datatables.delete') }}'
  let deleteButton = {
    text: deleteButtonTrans,
    url: "{{ route('admin.attendances.massDestroy') }}",
    className: 'btn-danger',
    action: function (e, dt, node, config) {
      var ids = $.map(dt.rows({ selected: true }).data(), function (entry) {
          return entry.id
      });

      if (ids.length === 0) {
        alert('{{ trans('translation.datatables.zero_selected') }}')

        return
      }

      if (confirm('{{ trans('translation.areYouSure') }}')) {
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

  let dtOverrideGlobals = {
    buttons: dtButtons,
    processing: true,
    serverSide: true,
    retrieve: true,
    aaSorting: [[ 1, 'desc' ]],
    ajax: "{{ route('admin.attendances.index') }}",
    columns: [
      { data: 'placeholder', name: 'placeholder' },
      { data: 'id', name: 'id' },
      { data: 'driver_name', name: 'driver.name' },
      { data: 'driver_mobile', name: 'driver.mobile' },
      { data: 'checkin_time', name: 'checkin_time' },
      { data: 'checkout_time', name: 'checkout_time' },
      { data: 'is_late', name: 'is_late' },
      { data: 'delay_minutes', name: 'delay_minutes' },
      { data: 'overtime_minutes', name: 'overtime_minutes' },
      { data: 'source', name: 'source' },
      { data: 'actions', name: '{{ trans('translation.actions') }}' }
    ],
    orderCellsTop: true,
    order: [[ 1, 'desc' ]],
    pageLength: 100,
  };
  let table = $('.datatable-Attendance').DataTable(dtOverrideGlobals);
  $('a[data-toggle="tab"]').on('shown.bs.tab click', function(e){
      $($.fn.dataTable.tables(true)).DataTable()
          .columns.adjust();
  });
  
});

</script>
@endsection