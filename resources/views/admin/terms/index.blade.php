@extends('layouts.master')
@section('title')
    @lang('translation.terms')
@endsection
@section('content')
    @component('components.breadcrumb')
        @slot('li_1')
        @lang('translation.appname')
        @endslot
        @slot('title')
        @lang('translation.terms')
        @endslot
    @endcomponent
    
<div class="card">
    <div class="card-header d-flex justify-content-between align-items-center flex-wrap">
        <h5 class="card-title mb-0">{{ trans('translation.term.title_singular') }} {{ trans('translation.list') }}</h5>
        @can('term_create')
            <a class="btn btn-create mb-1" href="{{ route('admin.terms.create') }}">
                <i class="ri-add-line"></i> {{ trans('translation.add') }} {{ trans('translation.term.title_singular') }}
            </a>
        @endcan
    </div>

    <div class="card-body">
        <div class="table-responsive">
            <table class=" table table-bordered table-striped table-hover datatable datatable-Term">
                <thead>
                    <tr>
                        <th width="10">

                        </th>
                        <th>
                            {{ trans('translation.term.fields.id') }}
                        </th>
                        <th>
                            {{ trans('translation.term.fields.english_text') }}
                        </th>
                        <th>
                            {{ trans('translation.term.fields.arabic_text') }}
                        </th>
                        <th>
                            &nbsp;
                        </th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($terms as $key => $term)
                        <tr data-entry-id="{{ $term->id }}">
                            <td>

                            </td>
                            <td>
                                {{ $term->id ?? '' }}
                            </td>
                            <td>
                                {{ $term->english_text ?? '' }}
                            </td>
                            <td>
                                {{ $term->arabic_text ?? '' }}
                            </td>
                            <td>
                                <div class="d-flex gap-1 justify-content-center">
                                    @can('term_show')
                                        <a class="btn btn-soft-info btn-sm"
                                            href="{{ route('admin.terms.show', $term->id) }}"
                                            title="{{ trans('translation.view') }}">
                                            <i class="ri-eye-fill"></i>
                                        </a>
                                    @endcan

                                    @can('term_edit')
                                        <a class="btn btn-soft-primary btn-sm"
                                            href="{{ route('admin.terms.edit', $term->id) }}"
                                            title="{{ trans('translation.edit') }}">
                                            <i class="ri-edit-2-fill"></i>
                                        </a>
                                    @endcan

                                    @can('can-delete')
                                        <form action="{{ route('admin.terms.destroy', $term->id) }}" method="POST"
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
    $(function () {
  let dtButtons = $.extend(true, [], $.fn.dataTable.defaults.buttons)
@can('can-delete')
  let deleteButtonTrans = '{{ trans('translation.datatables.delete') }}'
  let deleteButton = {
    text: deleteButtonTrans,
    url: "{{ route('admin.terms.massDestroy') }}",
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
  let table = $('.datatable-Term:not(.ajaxTable)').DataTable({ buttons: dtButtons })
  $('a[data-toggle="tab"]').on('shown.bs.tab click', function(e){
      $($.fn.dataTable.tables(true)).DataTable()
          .columns.adjust();
  });
  
})

</script>
@endsection