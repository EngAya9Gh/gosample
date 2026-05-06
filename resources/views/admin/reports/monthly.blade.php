@extends('layouts.master')
@section('title') Monthly Evaluation - {{ $month }} @endsection
@section('content')
    @component('components.breadcrumb')
        @slot('li_1') @lang('translation.reports') @endslot
        @slot('title') Monthly Performance Evaluation @endslot
    @endcomponent

    <div class="row">
        <div class="col-12">
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-transparent border-bottom d-flex align-items-center py-3">
                    <h5 class="card-title mb-0 flex-grow-1">
                        <i class="ri-award-fill me-1 align-middle text-warning"></i> 
                        Driver Rankings (<span id="display-month">{{ Carbon\Carbon::parse($month)->format('F Y') }}</span>)
                        <small class="text-muted ms-2">| Expected: <span id="display-expected">{{ $expectedDays }}</span> working days</small>
                    </h5>
                    <div class="flex-shrink-0 d-flex gap-3 align-items-center">
                        <div class="d-flex align-items-center">
                            <label class="me-2 mb-0 text-muted small">Filter Month:</label>
                            <input type="month" id="month-picker" class="form-control form-control-sm" value="{{ $month }}">
                        </div>
                        <div class="vr"></div>
                        <a href="{{ route('admin.reports.exportMonthly', ['month' => $month]) }}" id="export-link" class="btn btn-soft-primary btn-sm">
                            <i class="ri-file-excel-2-line me-1"></i> Export
                        </a>
                        <button class="btn btn-soft-success btn-sm" onclick="window.print()">
                            <i class="ri-printer-line me-1"></i> Print
                        </button>
                    </div>
                </div>
                <div class="card-body p-0 position-relative">
                    <!-- Loading Overlay -->
                    <div id="loading-overlay" class="position-absolute top-0 start-0 w-100 h-100 d-none d-flex align-items-center justify-content-center bg-white bg-opacity-75" style="z-index: 10;">
                        <div class="spinner-border text-primary" role="status">
                            <span class="visually-hidden">Loading...</span>
                        </div>
                    </div>

                    <div id="report-table-container">
                        @include('admin.reports.partials.monthly_table')
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection

@section('script')
<script>
$(document).ready(function() {
    $('#month-picker').on('change', function() {
        const month = $(this).val();
        const url = window.location.pathname + '?month=' + month;
        const exportUrl = "{{ route('admin.reports.exportMonthly') }}?month=" + month;
        
        // Show loading
        $('#loading-overlay').removeClass('d-none');
        
        $.ajax({
            url: url,
            type: 'GET',
            success: function(response) {
                $('#report-table-container').html(response);
                
                // Update UI elements
                const date = new Date(month + '-01');
                const monthName = date.toLocaleString('default', { month: 'long', year: 'numeric' });
                $('#display-month').text(monthName);
                $('#export-link').attr('href', exportUrl);
                
                // History update
                window.history.pushState({}, '', url);
                
                $('#loading-overlay').addClass('d-none');
            },
            error: function() {
                alert('Error loading data. Please try again.');
                $('#loading-overlay').addClass('d-none');
            }
        });
    });
});
</script>
@endsection
