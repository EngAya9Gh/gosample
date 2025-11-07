@extends('layouts.master')

@section('content')
<style>
    #driverTasksList li {
        cursor: move;
    }
    .sortable-item {
        background-color: #fff;
        margin-bottom: 6px;
        border: 1px solid #ccc;
        border-radius: 6px;
        padding: 8px;
        transition: transform 0.15s ease;
    }
    .sortable-item.dragging {
        background-color: #f8f9fa;
        opacity: 0.8;
    }
    .ui-state-highlight {
        height: 70px;
        background: #e9f3ff;
        border: 2px dashed #007bff;
        border-radius: 5px;
        margin-bottom: 6px;
    }
        /* تأكد إن كل شيء فوق ممكن يظهر أثناء السحب */
    .card,
    .card-body,
    .container,
    .content,
    .content-wrapper,
    .modal,
    body {
        overflow: visible !important;
    }

    /* خلي العنصر المنسحب فوق كل شي */
    .ui-sortable-helper {
        z-index: 99999 !important;
    }

    /* غلاف التحريك */
    .ui-state-highlight {
        height: 65px !important;
        background: #dff0ff !important;
        border: 2px dashed #007bff !important;
        border-radius: 8px;
        margin-bottom: 6px;
    }

</style>

<div class="card">
    <div class="card-header d-flex justify-content-between align-items-center">
        <h4>Tasks for Driver: {{ $driver->name }}</h4>
        <a href="{{ route('admin.drivers.index') }}" class="btn btn-secondary">← Back</a>
    </div>

    <div class="card-body">
        <ul id="driverTasksList" class="list-group">
            @foreach($tasks as $task)
                <li class="list-group-item sortable-item d-flex align-items-center justify-content-between" data-id="{{ $task->id }}">
                    <div class="handle text-muted mr-3" style="cursor: grab;">
                        <i class="fas fa-bars fa-lg"></i>
                    </div>
                    <div class="flex-grow-1">
                        <strong>ID:</strong> {{ $task->id }}<br>
                        <strong>From:</strong> {{ $task->fromLocation->name ?? '-' }}<br>
                        <strong>To:</strong> {{ $task->toLocation->name ?? '-' }}<br>
                        ETA: {{ $task->eta ?? '-' }}
                    </div>
                    <span class="badge badge-info">#{{ $task->priority ?? '-' }}</span>
                </li>
            @endforeach
        </ul>

        <div class="mt-4 d-flex justify-content-end">
            <button type="button" id="saveTaskOrder" class="btn btn-success" disabled>Save Order</button>
        </div>
    </div>
</div>
@endsection

@section('scripts')
@parent
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://code.jquery.com/ui/1.13.2/jquery-ui.min.js"></script>
<script>
$(document).ready(function() {
    console.log('✅ jQuery UI Sortable loaded:', !!$.ui?.sortable);

    const $list = $('#driverTasksList');

    if (!$list.length) {
        console.error('❌ driverTasksList not found');
        return;
    }

    // تفعيل السحب والإفلات بعد تحميل الصفحة فعليًا
    setTimeout(() => {
        $list.sortable({
            handle: '.handle',
            placeholder: 'ui-state-highlight',
            axis: 'y',
            revert: 150,
            tolerance: 'pointer',
            start: function(e, ui) {
                ui.placeholder.height(ui.item.outerHeight());
                ui.item.addClass('dragging');
            },
            stop: function(e, ui) {
                ui.item.removeClass('dragging');
            },
            update: function() {
                console.log('✅ order changed');
                $('#saveTaskOrder').prop('disabled', false);
            }
        }).disableSelection();
    }, 300);

    $('#saveTaskOrder').click(function() {
        const order = [];
        $list.find('li').each(function(index) {
            order.push({
                id: $(this).data('id'),
                priority: index + 1
            });
        });

        console.log('📦 Sending order:', order);

        $.ajax({
            url: "{{ route('admin.drivers.tasks.reorder', $driver->id) }}",
            type: 'POST',
            data: {
                _token: '{{ csrf_token() }}',
                order: order
            },
            success: function() {
                alert('✅ Task order saved successfully!');
                $('#saveTaskOrder').prop('disabled', true);
            },
            error: function(err) {
                console.error('❌ AJAX error:', err);
                alert('❌ Failed to save order.');
            }
        });
    });
});
</script>
@endsection
