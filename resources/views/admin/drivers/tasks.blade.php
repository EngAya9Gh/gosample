@extends('layouts.master')

@section('content')
<style>
    .sortable-item {
        background-color: #fff;
        margin-bottom: 6px;
        border: 1px solid #ccc;
        border-radius: 6px;
        padding: 10px;
        display: flex;
        align-items: center;
        justify-content: space-between;
        transition: background 0.2s;
        cursor: move;
    }

    .sortable-item:hover {
        background-color: #f8f9fa;
    }

    .sortable-item.sortable-ghost {
        opacity: 0.4;
        background-color: #e9ecef;
    }

    .sortable-item.sortable-drag {
        opacity: 0.8;
        box-shadow: 0 4px 8px rgba(0,0,0,0.2);
    }

    .drag-handle {
        cursor: grab;
        color: #6c757d;
        font-size: 20px;
        margin-right: 10px;
        user-select: none;
    }

    .drag-handle:active {
        cursor: grabbing;
    }

    .drag-handle::before {
        content: "☰";
        display: inline-block;
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
                <li class="list-group-item sortable-item" data-id="{{ $task->id }}">
                    <div class="d-flex align-items-center flex-grow-1">
                        <span class="drag-handle"></span>
                        <div>
                            <strong>ID:</strong> {{ $task->id }}<br>
                            <strong>From:</strong> {{ $task->from_location_name ?? '-' }}<br>
                            <strong>To:</strong> {{ $task->to_location_name ?? '-' }}<br>
                            ETA: {{ $task->eta ?? '-' }}
                        </div>
                    </div>
                    <div class="d-flex align-items-center">
                        <span class="badge badge-info mr-3">#{{ $task->poririty ?? '-' }}</span>
                    </div>
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
<script src="https://cdn.jsdelivr.net/npm/sortablejs@latest/Sortable.min.js"></script>
<script>
$(function() {
    const $list = $('#driverTasksList');
    const $saveBtn = $('#saveTaskOrder');

    // Initialize SortableJS
    const sortable = new Sortable($list[0], {
        handle: '.drag-handle',
        animation: 150,
        ghostClass: 'sortable-ghost',
        dragClass: 'sortable-drag',
        onEnd: function(evt) {
            // Enable save button when order changes
            $saveBtn.prop('disabled', false);
        }
    });

    // حفظ الترتيب
    $saveBtn.on('click', function() {
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
                $saveBtn.prop('disabled', true);
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
