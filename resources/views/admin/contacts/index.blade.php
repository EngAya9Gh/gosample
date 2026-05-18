@extends('layouts.admin')
@section('content')
@can('contact_create')
    <div style="margin-bottom: 10px;" class="row">
        <div class="col-lg-12">
            <a class="btn btn-success" href="{{ route('admin.contacts.create') }}">
                {{ trans('translation.add') }} {{ trans('cruds.contact.title_singular') }}
            </a>
        </div>
    </div>
@endcan
<div class="card">
    <div class="card-header">
        {{ trans('cruds.contact.title_singular') }} {{ trans('translation.list') }}
    </div>

    <div class="card-body">
        <div class="table-responsive">
            <table class=" table table-bordered table-striped table-hover datatable datatable-Contact">
                <thead>
                    <tr>
                        <th width="10">

                        </th>
                        <th>
                            {{ trans('cruds.contact.fields.id') }}
                        </th>
                        <th>
                            {{ trans('cruds.contact.fields.type') }}
                        </th>
                        <th>
                            {{ trans('cruds.contact.fields.email') }}
                        </th>
                        <th>
                            &nbsp;
                        </th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($contacts as $key => $contact)
                        <tr data-entry-id="{{ $contact->id }}">
                            <td>

                            </td>
                            <td>
                                {{ $contact->id ?? '' }}
                            </td>
                            <td>
                                {{ $contact->type ?? '' }}
                            </td>
                            <td>
                                {{ $contact->email ?? '' }}
                            </td>
                            <td>
                                <div class="d-flex gap-1 justify-content-center">
                                    @can('contact_show')
                                        <a class="btn btn-soft-info btn-sm" href="{{ route('admin.contacts.show', $contact->id) }}" title="{{ trans('translation.view') }}">
                                            <i class="ri-eye-fill"></i>
                                        </a>
                                    @endcan

                                    @can('contact_edit')
                                        <a class="btn btn-soft-primary btn-sm" href="{{ route('admin.contacts.edit', $contact->id) }}" title="{{ trans('translation.edit') }}">
                                            <i class="ri-edit-2-fill"></i>
                                        </a>
                                    @endcan

                                    @can('can-delete')
                                        <form action="{{ route('admin.contacts.destroy', $contact->id) }}" method="POST" onsubmit="return confirm('{{ trans('translation.areYouSure') }}');" style="display: inline-block;">
                                            <input type="hidden" name="_method" value="DELETE">
                                            <input type="hidden" name="_token" value="{{ csrf_token() }}">
                                            <button type="submit" class="btn btn-soft-danger btn-sm" title="{{ trans('translation.delete') }}">
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
  let deleteButtonTrans = '{{ trans('translation.datatables.delete') }}'
  let deleteButton = {
    text: deleteButtonTrans,
    url: "{{ route('admin.contacts.massDestroy') }}",
    className: 'btn-danger',
    action: function (e, dt, node, config) {
      var ids = $.map(dt.rows({ selected: true }).nodes(), function (entry) {
          return $(entry).data('entry-id')
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

  $.extend(true, $.fn.dataTable.defaults, {
    orderCellsTop: true,
    order: [[ 1, 'desc' ]],
    pageLength: 100,
  });
  let table = $('.datatable-Contact:not(.ajaxTable)').DataTable({ buttons: dtButtons })
  $('a[data-toggle="tab"]').on('shown.bs.tab click', function(e){
      $($.fn.dataTable.tables(true)).DataTable()
          .columns.adjust();
  });
  
})

</script>
@endsection