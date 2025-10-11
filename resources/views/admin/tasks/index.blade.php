@extends('layouts.master')
@section('title')
    @lang('translation.tasks')
@endsection
@section('content')
    @component('components.breadcrumb')
        @slot('li_1')
            @lang('translation.appname')
        @endslot
        @slot('title')
            @lang('translation.tasks')
        @endslot
    @endcomponent
<style>
    /* تصغير حجم pagination */
.pagination {
    font-size: 0.875rem; /* حجم الخط أصغر */
    margin: 0; /* إزالة المسافة الزائدة */
}

.pagination li {
    margin: 0 2px; /* مسافة صغيرة بين الأزرار */
}

.pagination li a,
.pagination li span {
    padding: 4px 8px; /* تصغير البادينغ */
    min-width: 32px; /* عرض ثابت صغير */
    height: 32px; /* ارتفاع ثابت */
    line-height: 24px; /* محاذاة النص */
    border-radius: 4px; /* حواف دائرية صغيرة */
}

.pagination li.active span {
    background-color: #0d6efd; /* لون الخلفية للصفحة النشطة */
    color: #fff;
    border-color: #0d6efd;
}

.pagination li a:hover {
    background-color: #e2e6ea; /* لون عند المرور على الرابط */
    color: #000;
}

</style>
    <div class="row">
        <div class="col-lg-12">
            <div class="card">
                <div class="card-header">
                    <h4 class="card-title mb-0">Filters</h4>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-lg-4">
                            <label for="keyword">{{ trans('translation.search') }}</label>
                            <input class="form-control" type="text" id="keyword">
                        </div>
                        <div class="col-lg-4">
                            <label for="status_filter">{{ trans('translation.task.fields.status') }}</label>
                            <select class="form-control" id="status_filter">
                                <option value="">Select Status</option>
                                <option value="NEW">NEW</option>
                                <option value="COLLECTED">COLLECTED</option>
                                <option value="IN_FREEZER">IN_FREEZER</option>
                                <option value="OUT_FREEZER">OUT_FREEZER</option>
                                <option value="CLOSED">CLOSED</option>
                                <option value="NO_SAMPLES">NO_SAMPLES</option>
                            </select>
                        </div>
                        <div class="col-lg-4">
                            <label for="driver_filter">{{ trans('translation.task.fields.driver') }}</label>
                            <select class="form-control select2" id="driver_filter">
                                <option value="">Select Driver</option>
                                @foreach ($drivers as $driver)
                                    <option value="{{ $driver->id }}">{{ $driver->name }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                    <div class="row mt-2">
                        <div class="col-lg-4">
                            <label for="from_date">{{ trans('translation.task.fields.date_from') }}</label>
                            <input class="form-control" type="datetime-local" id="from_date">
                        </div>
                        <div class="col-lg-4">
                            <label for="to_date">{{ trans('translation.task.fields.date_to') }}</label>
                            <input class="form-control" type="datetime-local" id="to_date">
                        </div>
                        <div class="col-lg-4">
                            <label for="client_filter">{{ trans('translation.task.fields.billing_client') }}</label>
                            <select class="form-control select2" id="client_filter">
                                <option value="">Select Client</option>
                                @foreach ($clients as $client)
                                    <option value="{{ $client->id }}">{{ $client->english_name }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>

                    <div class="row mt-3">
                        <div class="col-lg-12 d-flex justify-content-between">
                            <button class="btn btn-danger" id="search">Search</button>
                            <button class="btn btn-success" id="export_excel">Export Excel</button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="card mt-3">
        <div class="card-header">
            {{ trans('translation.tasks') }} {{ trans('translation.list') }}
        </div>
        <div class="card-body">
            <!-- <table class="table table-bordered" id="tasksTable">
                <thead>
                    <tr>
                        <th>#</th>
                        <th>Client</th>
                        <th>Driver</th>
                        <th>Car</th>
                        <th>Status</th>
                        <th>Collection Date</th>
                        <th>Close Date</th>
                        <th>Hours</th>
                    </tr>
                </thead>
                <tbody></tbody>
            </table>
            <div class="d-flex justify-content-between align-items-center mt-3">
                <div id="pagination-info"></div>
                <ul class="pagination" id="pagination"></ul>
            </div> -->
            <table class="table table-bordered table-striped">
                <thead>
                    <tr>
                        <th>#</th>
                        <th>ID</th>
                        <th>Order Date</th>
                        <th>Client</th>
                        <th>Driver</th>
                        <th>From Location</th>
                        <th>To Location</th>
                        <th>ETA</th>
                        <th>Collection Date</th>
                        <th>Close Date</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($tasks as $index => $task)
                    <tr>
                        <td>{{ $index + $tasks->firstItem() }}</td>
                        <td>{{ $task->id }}</td>
                        <td>{{ $task->created_at }}</td>
                        <td>{{ optional($task->client)->english_name }}</td>
                        <td>{{ optional($task->driver)->name }}</td>
                        <td>{{ optional($task->from)->name }}</td>
                        <td>{{ optional($task->to)->name }}</td>
                        <td>{{ $task->eta }}</td>
                        <td>{{ $task->collection_date }}</td>
                        <td>{{ $task->close_date }}</td>
                        <td>{{ $task->status }}</td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="11" class="text-center">No tasks found</td>
                    </tr>
                    @endforelse
                </tbody>
            </table>

            <div class="mt-2">
                {{ $tasks->links() }} {{-- Pagination links --}}
            </div>

        </div>
    </div>
@endsection

@section('scripts')
    @parent
    <script>
        $(document).ready(function() {
            $('.select2').select2();

            let currentPage = 1;

            function loadTasks(page = 1) {
                currentPage = page;
                const params = {
                    status: $('#status_filter').val(),
                    client_id: $('#client_filter').val(),
                    driver_id: $('#driver_filter').val(),
                    from_date: $('#from_date').val(),
                    to_date: $('#to_date').val(),
                    keyword: $('#keyword').val(),
                    page: page
                };

                $.ajax({
                    url: "{{ route('admin.tasks.index') }}",
                    data: params,
                    beforeSend: function() {
                        $('#tasksTable tbody').html('<tr><td colspan="8" class="text-center">Loading...</td></tr>');
                    },
                    success: function(res) {
                        const rows = res.data.map((t, i) => `
                            <tr>
                                <td>${t.id}</td>
                                <td>${t.client ?? '-'}</td>
                                <td>${t.driver_name ?? '-'}</td>
                                <td>${t.car_imei ?? '-'}</td>
                                <td>${t.status ?? '-'}</td>
                                <td>${t.collection_date ?? '-'}</td>
                                <td>${t.close_date ?? '-'}</td>
                                <td>${t.hours ?? '-'}</td>
                            </tr>
                        `).join('');

                        $('#tasksTable tbody').html(rows || '<tr><td colspan="8" class="text-center">No data</td></tr>');

                        // Pagination
                        let pagHtml = '';
                        for (let i = 1; i <= res.pagination.last_page; i++) {
                            pagHtml += `<li class="page-item ${i === res.pagination.current_page ? 'active' : ''}">
                                <a class="page-link" href="#" onclick="loadTasks(${i})">${i}</a>
                            </li>`;
                        }
                        $('#pagination').html(pagHtml);
                        $('#pagination-info').text(`Page ${res.pagination.current_page} of ${res.pagination.last_page} — Total ${res.pagination.total}`);
                    }
                });
            }

            $('#search').on('click', function() {
                // loadTasks(1);
            });

            // Initial load
            // loadTasks();

            $('#export_excel').on('click', function() {
                const query = $.param({
                    status: $('#status_filter').val(),
                    client_id: $('#client_filter').val(),
                    driver_id: $('#driver_filter').val(),
                    from_date: $('#from_date').val(),
                    to_date: $('#to_date').val(),
                    keyword: $('#keyword').val()
                });
                window.location.href = `/tasks/export/excel?${query}`;
            });
        });
    </script>
@endsection
