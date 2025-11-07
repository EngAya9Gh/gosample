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
<script>
$(function() {
    // تفعيل السحب والإفلات
    $('#driverTasksList').sortable({
        handle: '.handle',
        placeholder: 'ui-state-highlight',
        axis: 'y',
        revert: 150,
        start: function(e, ui) {
            ui.placeholder.height(ui.item.outerHeight());
            ui.item.addClass('dragging');
        },
        stop: function(e, ui) {
            ui.item.removeClass('dragging');
        },
        update: function() {
            $('#saveTaskOrder').prop('disabled', false);
        }
    }).disableSelection();

    $('#saveTaskOrder').click(function() {
        const order = [];
        $('#driverTasksList li').each(function(index) {
            order.push({
                id: $(this).data('id'),
                priority: index + 1
            });
        });

        $.ajax({
            url: "{{ route('admin.drivers.tasks.reorder', $driver->id) }}",
            method: 'POST',
            data: {
                _token: '{{ csrf_token() }}',
                order: order
            },
            success: function() {
                alert('✅ Task order saved successfully!');
                $('#saveTaskOrder').prop('disabled', true);
            },
            error: function() {
                alert('❌ Failed to save order.');
            }
        });
    });
});
</script>
@endsection
