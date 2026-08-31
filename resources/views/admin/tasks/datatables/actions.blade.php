<div class="d-flex gap-1 justify-content-center">
    @can('task_show')
        <a href="javascript:void(0)" data-url="{{ route('admin.tasks.show', $id) }}" class="btn btn-soft-info btn-sm action-btn-menu" title="View"><i class="ri-eye-fill"></i></a>
    @endcan
    @can('task_edit')
        <a href="javascript:void(0)" data-url="{{ route('admin.tasks.edit', $id) }}" class="btn btn-soft-primary btn-sm action-btn-menu" title="Edit"><i class="ri-edit-2-fill"></i></a>
    @endcan
    @can('task_delete')
        <form action="{{ route('admin.tasks.destroy', $id) }}" method="POST" style="display:inline-block;" onsubmit="return confirm('هل أنت متأكد من الحذف؟')">
            @csrf
            @method('DELETE')
            <button type="submit" class="btn btn-soft-danger btn-sm" title="Delete"><i class="ri-delete-bin-fill"></i></button>
        </form>
    @endcan
</div>
