@extends('layouts.master')

@section('title')
    مسار السائق
@endsection

@section('content')
    @component('components.breadcrumb')
        @slot('li_1')
            @lang('translation.appname')
        @endslot
        @slot('title')
            مسار السائق: {{ $driver->name }}
        @endslot
    @endcomponent

    <div class="row mb-3">
        <div class="col-12 text-end">
            <button type="button" id="smartSortTasks" class="btn btn-soft-info btn-sm me-2">
                <i class="ri-magic-line align-middle me-1"></i> الترتيب الذكي للأقرب
            </button>
            <button type="button" id="saveTaskOrder" class="btn btn-primary btn-sm" disabled>
                <i class="ri-save-line align-middle me-1"></i> حفظ الترتيب الجديد
            </button>
            <a href="{{ route('admin.drivers.index') }}" class="btn btn-light btn-sm ms-2">
                <i class="ri-arrow-go-back-line align-middle me-1"></i> رجوع
            </a>
        </div>
    </div>

    <div class="row">
        <div class="col-xl-8 offset-xl-2 col-lg-10 offset-lg-1">
            <div class="card">
                <div class="card-header align-items-center d-flex">
                    <h4 class="card-title mb-0 flex-grow-1">المهام المخصصة للترتيب</h4>
                </div><!-- end card header -->

                <div class="card-body p-0">
                    <ul id="driverTasksList" class="list-group list-group-flush">
                        @forelse($tasks as $task)
                            <li class="list-group-item sortable-item py-3" data-id="{{ $task->id }}">
                                <div class="d-flex align-items-center">
                                    <div class="flex-shrink-0 me-3 cursor-grab drag-handle text-muted" title="Drag to sort">
                                        <i class="ri-drag-move-2-line fs-20"></i>
                                    </div>
                                    
                                    <div class="flex-grow-1">
                                        <div class="row align-items-center">
                                            <div class="col-sm-2 mb-2 mb-sm-0">
                                                <p class="text-muted mb-1 fs-11 text-uppercase fw-semibold">Task ID</p>
                                                <h5 class="fs-14 mb-0 text-primary">#{{ $task->id }}</h5>
                                            </div>
                                            <div class="col-sm-6 mb-2 mb-sm-0">
                                                <div class="d-flex flex-column gap-2">
                                                    <div class="d-flex align-items-center">
                                                        <i class="ri-map-pin-line text-danger me-2 fs-15"></i>
                                                        <span class="fs-13">من: {{ $task->from_location_name ?? 'غير محدد' }}</span>
                                                    </div>
                                                    <div class="d-flex align-items-center">
                                                        <i class="ri-map-pin-fill text-success me-2 fs-15"></i>
                                                        <span class="fs-13">إلى: {{ $task->to_location_name ?? 'غير محدد' }}</span>
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="col-sm-3 text-sm-end mb-2 mb-sm-0">
                                                @if(isset($task->eta))
                                                    <span class="badge bg-primary-subtle text-primary px-2 py-1 fs-12">
                                                        <i class="ri-time-line align-middle me-1"></i>
                                                        {{ $task->eta }} دقيقة
                                                    </span>
                                                @endif
                                            </div>
                                            <div class="col-sm-1 text-sm-end">
                                                <div class="avatar-xs ms-sm-auto d-inline-block">
                                                    <div class="avatar-title bg-light text-muted rounded-circle fs-12 border">
                                                        {{ $task->poririty ?? '-' }}
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </li>
                        @empty
                            <li class="list-group-item text-center py-5 text-muted">
                                <i class="ri-car-line fs-24 mb-2 d-block text-primary"></i>
                                لا يوجد مهام مخصصة لهذا السائق.
                            </li>
                        @endforelse
                    </ul>
                </div><!-- end card body -->
            </div><!-- end card -->
        </div>
    </div>
@endsection

@section('scripts')
@parent
<style>
    .cursor-grab { cursor: grab; padding: 10px; border-radius: 4px; transition: background 0.2s; }
    .cursor-grab:hover { background: #f3f6f9; color: #405189 !important; }
    .cursor-grab:active { cursor: grabbing; }
    .sortable-ghost { opacity: 0.5; background-color: #f8f9fa; border: 1px dashed #ced4da; }
    .sortable-drag { background-color: #ffffff; box-shadow: 0 5px 15px rgba(0,0,0,0.08); }
    @keyframes spin { 100% { transform: rotate(360deg); } }
</style>
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/Sortable/1.14.0/Sortable.min.js"></script>
<script>
$(function() {
    const $list = $('#driverTasksList');
    const $saveBtn = $('#saveTaskOrder');

    if ($list.find('.sortable-item').length > 0) {
        const sortable = new Sortable($list[0], {
            handle: '.drag-handle',
            animation: 150,
            ghostClass: 'sortable-ghost',
            dragClass: 'sortable-drag',
            onEnd: function(evt) {
                $saveBtn.prop('disabled', false);
            }
        });
    }

    $saveBtn.on('click', function() {
        const order = [];
        $list.find('li.sortable-item').each(function(index) {
            order.push({
                id: $(this).data('id'),
                priority: index + 1
            });
        });

        const $btn = $(this);
        $btn.prop('disabled', true).html('<i class="ri-loader-4-line align-middle me-1" style="animation: spin 1s linear infinite;"></i> جاري الحفظ...');

        $.ajax({
            url: "{{ route('admin.drivers.tasks.reorder', $driver->id) }}",
            type: 'POST',
            data: {
                _token: '{{ csrf_token() }}',
                order: order
            },
            success: function() {
                Swal.fire({
                    title: 'تم الحفظ!',
                    text: 'تم حفظ الترتيب بنجاح',
                    icon: 'success',
                    timer: 2000,
                    showConfirmButton: false
                }).then(() => {
                    location.reload();
                });
            },
            error: function(err) {
                Swal.fire('خطأ!', 'فشل في حفظ الترتيب.', 'error');
                $btn.prop('disabled', false);
            }
        });
    });

    $('#smartSortTasks').on('click', function() {
        Swal.fire({
            title: 'الترتيب الذكي',
            text: "هل أنت متأكد من رغبتك في إعادة ترتيب مهام هذا السائق ذكياً حسب الأقرب؟ هذا سيلغي الترتيب الحالي.",
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#3085d6',
            cancelButtonColor: '#d33',
            confirmButtonText: 'نعم، رتب ذكياً!',
            cancelButtonText: 'إلغاء'
        }).then((result) => {
            if (result.isConfirmed) {
                const $btn = $('#smartSortTasks');
                $btn.prop('disabled', true);

                Swal.fire({
                    title: 'جاري الحساب...',
                    text: 'نظام الذكاء الاصطناعي يقوم بترتيب المهام للحصول على أقصر مسار، الرجاء الانتظار.',
                    allowOutsideClick: false,
                    didOpen: () => {
                        Swal.showLoading()
                    }
                });

                $.ajax({
                    url: "{{ route('admin.drivers.tasks.smartSort', $driver->id) }}",
                    type: 'POST',
                    data: {
                        _token: '{{ csrf_token() }}'
                    },
                    success: function() {
                        Swal.fire({
                            title: 'نجاح!',
                            text: 'تم ترتيب المهام ذكياً بنجاح!',
                            icon: 'success',
                            timer: 2000,
                            showConfirmButton: false
                        }).then(() => {
                            location.reload();
                        });
                    },
                    error: function(err) {
                        Swal.fire('خطأ!', 'فشل في الترتيب الذكي. تأكد من توفر صلاحيات أو بيانات كافية.', 'error');
                        $btn.prop('disabled', false);
                    }
                });
            }
        });
    });
});
</script>
@endsection
