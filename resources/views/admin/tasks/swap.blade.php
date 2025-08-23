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
            Swap Tasks
        @endslot
    @endcomponent


    <div class="row">
        <div class="col-lg-12">
            <div class="card">
                <div class="card-header">
                    <h4 class="card-title mb-0">Filters</h4>
                </div>
                <form class="form-swap-submit" action="{{ route('admin.swapReportExport') }}" method="POST">
                    @csrf
                    <div class="card-body">
                        <div class="row">
                            <div class="col-lg-4">
                                <label class="required" for="keyword">{{ trans('translation.search') }}</label>
                                <input class="form-control" type="text" name="keyword" id="keyword">
                            </div>

                            <div class="col-lg-4">
                                <label class="required" for="status">{{ trans('translation.task.fields.status') }}</label>
                                <select class="form-control" name="status" id="statuss">
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
                                <label for="driver_id">{{ trans('translation.task.fields.driver') }}</label>
                                <select class="form-control select2" name="driver_id" id="driver_id">
                                    <option value="">Select Driver</option>
                                    @foreach ($drivers as $id => $entry)
                                        <option value="{{ $entry->id }}">{{ $entry->name }}</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-lg-4">
                                <label class="required"
                                    for="date_from">{{ trans('translation.task.fields.date_from') }}</label>
                                <input class="form-control" type="datetime-local" name="date_from" id="date_from">
                            </div>
                            <div class="col-lg-4">
                                <label class="required"
                                    for="date_to">{{ trans('translation.task.fields.date_to') }}</label>
                                <input class="form-control" type="datetime-local" name="date_to" id="date_to">
                            </div>
                            <div class="col-lg-4">
                                <label for="billing_client">{{ trans('translation.task.fields.billing_client') }}</label>
                                <select class="form-control select2" name="billing_client" id="billing_client">
                                    <option value="">Select Client</option>
                                    @foreach ($clients as $id => $entry)
                                        <option value="{{ $entry->id }}">{{ $entry->english_name }}</option>
                                    @endforeach
                                </select>
                            </div>

                        </div>

                        <div class="row">
                            <div class="col-lg-4">
                                <label for="from_location">{{ trans('translation.task.fields.from_location') }}</label>
                                <select class="form-control select2" name="from_location" id="from_location">
                                    <option value="">Select Location</option>
                                    @foreach ($locations as $id => $entry)
                                        <option value="{{ $entry->id }}">{{ $entry->name }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-lg-4">
                                <label for="to_location">{{ trans('translation.task.fields.to_location') }}</label>
                                <select class="form-control select2" name="to_location" id="to_location">
                                    <option value="">Select Location</option>
                                    @foreach ($locations as $id => $entry)
                                        <option value="{{ $entry->id }}">{{ $entry->name }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-lg-4">
                                <label for="search_date">{{ trans('translation.task.fields.search_date') }}</label>
                                <select class="form-control" name="search_date" id="search_date">
                                    {{-- <option value="">Select Date</option> --}}
                                    <option value="collection_date">collection Date</option>
                                    <option value="created_at">Creation Date</option>
                                </select>
                            </div>

                            <div class="col-lg-4">
                                <label for="report_type">{{ trans('translation.export') }}</label>
                                <select class="form-control" name="report_type" id="report_type">
                                    {{-- <option value="">Select Report Type </option> --}}
                                    <option value="pdf">PDF</option>
                                    <option value="excel">EXCEL</option>
                                </select>
                            </div>
                            <div class="col-lg-4">
                                <label for="sort_by">{{ trans('global.sort_by') }}</label>
                                <select class="form-control" name="sort_by" id="sort_by">
                                    <option value="created_at">{{ trans('translation.task.fields.created_at') }}</option>
                                    <option value="updated_at">{{ trans('translation.task.fields.updated_at') }}</option>
                                    <option value="collection_date">{{ trans('translation.task.fields.collection_date') }}
                                    </option>
                                </select>
                            </div>
                            <div class="col-lg-4">
                                <label for="sort_order">{{ trans('global.sort_order') }}</label>
                                <select class="form-control" name="sort_order" id="sort_order">
                                    <option value="desc">Desc</option>
                                    <option value="asc">Asc</option>
                                    </option>
                                </select>
                            </div>

                            <div class="col-lg-12 d-flex justify-content-between mt-2">
                                <button class="btn btn-danger" type="button" id="search">
                                    {{ trans('translation.search') }}
                                </button>

                                <a href="#" id="export-excel-link" class="btn btn-success">Export Excel Report</a>


                                <button class="btn btn-danger" type="submit" id="export">
                                    {{ trans('translation.export') }}
                                </button>
                            </div>


                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>


    <div class="card">
        <div class="card-header">
            {{ trans('translation.tasks') }} {{ trans('translation.list') }}
        </div>

        <div class="card-body">
            <table class=" table table-bordered table-striped table-hover ajaxTable datatable datatable-task-swap" width="100%">
            </table>
        </div>
    </div>
@endsection
@section('scripts')
    @parent
    <script>
        $(document).ready(function() {
            $('.select2').select2();
        });
    </script>

    <script>
        $(function() {
            let dtButtons = $.extend(true, [], $.fn.dataTable.defaults.buttons)

            let dtOverrideGlobals = {
                buttons: dtButtons,
                processing: true,
                serverSide: true,
                retrieve: true,
                aaSorting: [],
                autoWidth:false,
                responsive: true,
                ajax: {
                    url: "{{ route('admin.swapTask.index') }}",
                    data: function(d) {
                        d.search_date = $("#search_date option:selected").val();
                        d.status = $("#statuss option:selected").val();
                        d.driver_id = $("#driver_id option:selected").val();
                        d.billing_client = $("#billing_client option:selected").val();
                        d.to_location = $("#to_location option:selected").val();
                        d.from_location = $("#from_location option:selected").val();
                        d.date_from = $("#date_from").val();
                        d.date_to = $("#date_to").val();
                        d.keyword = $('#keyword').val();
                        d.sort_by = $('#sort_by option:selected').val();
                        d.sort_order = $('#sort_order option:selected').val();
                        // d.delayed_reason = $('#delayed_reason').val();
                    }
                },
                columns: [
                    {
                        data: 'id',
                        name: 'id',
                        title : 'ID'
                    },
                    {
                        data: 'created_at',
                        name: 'created_at',
                        title: 'Order Date'
                    },
                    {
                        data: 'client_name',
                        name: 'clients.english_name',
                        title: 'Client'
                    },
                    {
                        data: 'from_location_name',
                        name: 'from.name',
                        title:'From Location'
                    },
                    {
                        data: 'to_location_name',
                        name: 'to.name',
                        title:'To Location'
                    },
                    {
                        data: 'old_driver_name',
                        name: 'old.name',
                        title:'Old Driver'
                    },
                    {
                        data: 'driver_name',
                        name: 'drivers.name',
                        title:'Driver'
                    },
                    {
                        data: 'collection_date',
                        name: 'collection_date',
                        title:'Collection Date'
                    },
                    {
                        data: 'freezer_date',
                        name: 'freezer_date',
                        title:'Freezer Date'
                    },
                    {
                        data: 'swap_freezer_in',
                        name: 'swap_freezer_in',
                        title:'Swap Freezer Date'
                    },
                    {
                        data: 'freezer_out_date',
                        name: 'freezer_out_date',
                        title:'Freezer Out'
                    },
                    {
                        data: 'swap_freezer_out',
                        name: 'swap_freezer_out',
                        title:'Swap Freezer Out'
                    },
                    {
                        data: 'close_date',
                        name: 'close_date',
                        title:'Close Date'
                    },
                    {
                        data: 'status',
                        name: 'status',
                        title:'Status'
                    }
                ],
                orderCellsTop: true,
                order: [
                    [3, 'desc']
                ],
                pageLength: 100,
            };
            let table = $('.datatable-task-swap').DataTable(dtOverrideGlobals);





            $("#search").click(function() {
                // alert("button");
                table.draw();
            });
            $("#export1").click(function() {
                // alert('export');
                var d = {};
                d.search_date = $("#search_date option:selected").val();
                d.status = $("#statuss option:selected").val();
                d.driver_id = $("#driver_id option:selected").val();
                d.billing_client = $("#billing_client option:selected").val();
                d.to_location = $("#to_location option:selected").val();
                d.from_location = $("#from_location option:selected").val();
                d.date_from = $("#date_from").val();
                d.date_to = $("#date_to").val();
                d.keyword = $('#keyword').val();

                d.delayed_reason = $('#delayed_reason').val();
                $.ajax({
                    type: "POST",
                    url: "/api/task/report/export",
                    data: JSON.stringify(d),
                    dataType: 'json',
                    contentType: "application/json; charset=utf-8",
                }).done(function(data, textStatus, xhr) {
                    BatchSamples = [];
                    if (data.status) {
                        alert('succss');
                    } else {
                        alert('succss');
                    }
                }).fail(function(jqXHR, textStatus, errorThrown) {
                    BatchSamples = [];
                    alert('f');
                });
            });
        });
    </script>
    <script>
        document.addEventListener("DOMContentLoaded", function() {
            // Get a reference to the "Export Excel Report" link
            var exportExcelLink = document.getElementById("export-excel-link");

            // Add a click event listener to the link
            exportExcelLink.addEventListener("click", function(event) {
                event.preventDefault(); // Prevent the default link behavior

                // Get form field values
                var keyword = document.getElementById("keyword").value;
                var status = document.getElementById("statuss").value;
                var driverId = document.getElementById("driver_id").value;
                var dateFrom = document.getElementById("date_from").value;
                var dateTo = document.getElementById("date_to").value;
                var billingClient = document.getElementById("billing_client").value;
                var fromLocation = document.getElementById("from_location").value;
                var toLocation = document.getElementById("to_location").value;
                var searchDate = document.getElementById("search_date").value;
                var reportType = document.getElementById("report_type").value;
                var sortBy = document.getElementById("sort_by").value;
                var sortOrder = document.getElementById("sort_order").value;

                // Construct the export URL with the form values as query parameters
                var exportUrl = "{{ route('admin.swapTask.export-excel') }}?" +
                    "keyword=" + keyword +
                    "&status=" + status +
                    "&driver_id=" + driverId +
                    "&date_from=" + dateFrom +
                    "&date_to=" + dateTo +
                    "&billing_client=" + billingClient +
                    "&from_location=" + fromLocation +
                    "&to_location=" + toLocation +
                    "&search_date=" + searchDate +
                    "&report_type=" + reportType +
                    "&sort_by=" + sortBy +
                    "&sort_order=" + sortOrder;

                // Redirect to the export URL
                window.location.href = exportUrl;
            });
        });
    </script>
@endsection
