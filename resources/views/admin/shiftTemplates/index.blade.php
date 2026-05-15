@extends('layouts.master')
@section('title')
    Shift Templates
@endsection
@section('content')
    @component('components.breadcrumb')
        @slot('li_1')
            @lang('translation.appname')
        @endslot
        @slot('title')
            Shift Templates
        @endslot
    @endcomponent

    <div class="card">
        <div class="card-header d-flex justify-content-between align-items-center flex-wrap">
            <h5 class="card-title mb-0"><i class="ri-list-settings-line me-2"></i> Shift Templates List</h5>
            <a class="btn btn-create mb-1" href="{{ route('admin.shift-templates.create') }}">
                <i class="ri-add-line"></i> Add New Template
            </a>
        </div>

        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-bordered table-striped table-hover datatable datatable-ShiftTemplate">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Template Name</th>
                            <th>Start Time</th>
                            <th>End Time</th>
                            <th width="150">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($shiftTemplates as $key => $shiftTemplate)
                            <tr data-entry-id="{{ $shiftTemplate->id }}">
                                <td>{{ $shiftTemplate->id ?? '' }}</td>
                                <td><strong>{{ $shiftTemplate->name ?? '' }}</strong></td>
                                <td><span class="badge bg-soft-success text-success fs-13">{{ \Carbon\Carbon::parse($shiftTemplate->start_time)->format('H:i') }}</span></td>
                                <td><span class="badge bg-soft-danger text-danger fs-13">{{ \Carbon\Carbon::parse($shiftTemplate->end_time)->format('H:i') }}</span></td>
                                <td>
                                    <div class="d-flex gap-1 justify-content-center">
                                        <a class="btn btn-soft-primary btn-sm"
                                            href="{{ route('admin.shift-templates.edit', $shiftTemplate->id) }}"
                                            title="Edit">
                                            <i class="ri-edit-2-fill"></i>
                                        </a>

                                        <button type="button" class="btn btn-soft-danger btn-sm delete-btn"
                                            data-id="{{ $shiftTemplate->id }}" title="Delete">
                                            <i class="ri-delete-bin-fill"></i>
                                        </button>
                                    </div>

                                    <form id="delete-form-{{ $shiftTemplate->id }}" action="{{ route('admin.shift-templates.destroy', $shiftTemplate->id) }}" method="POST" style="display: none;">
                                        @method('DELETE')
                                        @csrf
                                    </form>
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
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script>
        $(function() {
            $.extend(true, $.fn.dataTable.defaults, {
                orderCellsTop: true,
                order: [
                    [0, 'desc']
                ],
                pageLength: 25,
                select: false
            });
            
            $('.datatable-ShiftTemplate').DataTable({
                buttons: []
            })

            // Modern Delete Dialog (SweetAlert2)
            $(document).on('click', '.delete-btn', function() {
                const id = $(this).data('id');
                Swal.fire({
                    title: 'Are you sure?',
                    text: "You won't be able to revert this!",
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonColor: '#f06548', // Red-ish to match theme
                    cancelButtonColor: '#3085d6',
                    confirmButtonText: 'Yes, delete it!',
                    cancelButtonText: 'Cancel',
                    background: '#fff',
                    customClass: {
                        confirmButton: 'btn btn-danger px-4',
                        cancelButton: 'btn btn-light border px-4'
                    },
                    buttonsStyling: false
                }).then((result) => {
                    if (result.isConfirmed) {
                        $(`#delete-form-${id}`).submit();
                    }
                })
            });
        })
    </script>
@endsection
