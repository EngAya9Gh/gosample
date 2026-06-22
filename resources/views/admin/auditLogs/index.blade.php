@extends('layouts.master')
@section('title')
    @lang('translation.coupons')
@endsection
@section('content')
    @component('components.breadcrumb')
        @slot('li_1')
        @lang('translation.appname')
        @endslot
        @slot('title')
        @lang('translation.audit-logs')
        @endslot
    @endcomponent

<div class="card">
    <div class="card-header d-flex justify-content-between align-items-center flex-wrap">
        <h5 class="card-title mb-0">{{ trans('translation.auditLog') }} {{ trans('translation.list') }}</h5>
    </div>

    <div class="card-body">
        <div class="table-responsive">
            <table class=" table table-bordered table-striped table-hover datatable datatable-AuditLog">
                <thead>
                    <tr>
                        <th width="10">

                        </th>
                        <th>
                            {{ trans('translation.auditLog.fields.id') }}
                        </th>
                        <th>
                            {{ trans('translation.auditLog.fields.description') }}
                        </th>
                        <th>
                            {{ trans('translation.auditLog.fields.subject_id') }}
                        </th>
                        <th>
                            {{ trans('translation.auditLog.fields.subject_type') }}
                        </th>
                        <th>
                            {{ trans('translation.auditLog.fields.user_id') }}
                        </th>
                        <th>
                            {{ trans('translation.auditLog.fields.host') }}
                        </th>
                        <th>
                            {{ trans('translation.auditLog.fields.created_at') }}
                        </th>
                        <th>
                            &nbsp;
                        </th>
                    </tr>
                </thead>
                    <!-- AJAX table will be rendered here -->
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
  
  let dtOverrideGlobals = {
    buttons: dtButtons,
    processing: true,
    serverSide: true,
    retrieve: true,
    aaSorting: [],
    ajax: "{{ route('admin.audit-logs.index') }}",
    columns: [
      { data: 'placeholder', name: 'placeholder' },
      { data: 'id', name: 'id' },
      { data: 'description', name: 'description' },
      { data: 'subject_id', name: 'subject_id' },
      { data: 'subject_type', name: 'subject_type' },
      { data: 'user_id', name: 'user_id' },
      { data: 'host', name: 'host' },
      { data: 'created_at', name: 'created_at' },
      { data: 'actions', name: '{{ trans('global.actions') }}' }
    ],
    orderCellsTop: true,
    order: [[ 1, 'desc' ]],
    pageLength: 100,
  };
  let table = $('.datatable-AuditLog').DataTable(dtOverrideGlobals);
  $('a[data-toggle="tab"]').on('shown.bs.tab click', function(e){
      $($.fn.dataTable.tables(true)).DataTable()
          .columns.adjust();
  });
  
})

</script>
@endsection