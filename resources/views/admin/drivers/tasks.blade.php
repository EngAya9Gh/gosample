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
    }

    .sortable-item:hover {
        background-color: #f8f9fa;
    }

    .task-controls button {
        border: none;
        background: transparent;
        color: #007bff;
        font-size: 18px;
        cursor: pointer;
        padding: 0 4px;
    }

    .task-controls button:disabled {
        color: #aaa;
        cursor: not-allowed;
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
                    <div>
                        <strong>ID:</strong> {{ $task->id }}<br>
                        <strong>From:</strong> {{ $task->fromLocation->name ?? '-' }}<br>
                        <strong>To:</strong> {{ $task->toLocation->name ?? '-' }}<br>
                        ETA: {{ $task->eta ?? '-' }}
                    </div>
                    <div class="d-flex align-items-center">
                        <span class="badge badge-info mr-3">#{{ $task->priority ?? '-' }}</span>
                        <div class="task-controls">
                            <button class="move-up" title="Move Up">↑</button>
                            <button class="move-down" title="Move Down">↓</button>
                        </div>
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
<script>
$(function() {
    const $list = $('#driverTasksList');
    const $saveBtn = $('#saveTaskOrder');

    function refreshButtons() {
        // تعطيل السهم للأول والأخير
        $list.find('.move-up, .move-down').prop('disabled', false);
        $list.find('li:first .move-up').prop('disabled', true);
        $list.find('li:last .move-down').prop('disabled', true);
    }

    refreshButtons();

    // تحريك للأعلى
    $list.on('click', '.move-up', function() {
        const $li = $(this).closest('li');
        $li.prev().before($li);
        $saveBtn.prop('disabled', false);
        refreshButtons();
    });

    // تحريك للأسفل
    $list.on('click', '.move-down', function() {
        const $li = $(this).closest('li');
        $li.next().after($li);
        $saveBtn.prop('disabled', false);
        refreshButtons();
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
