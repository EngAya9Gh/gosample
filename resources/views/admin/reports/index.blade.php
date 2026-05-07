@extends('layouts.master')
@section('title') @lang('translation.reports') @endsection
@section('content')
    @component('components.breadcrumb')
        @slot('li_1') @lang('translation.appname') @endslot
        @slot('title') Reporting & Analytics @endslot
    @endcomponent

    <div class="row">
        <div class="col-12">
            <div class="card border-0 shadow-sm bg-primary-subtle">
                <div class="card-body p-4">
                    <div class="row align-items-center">
                        <div class="col-sm-8">
                            <h3 class="fw-bold text-primary mb-1">Operational Insights</h3>
                            <p class="text-muted mb-0">Select a report type to analyze performance and attendance metrics.</p>
                        </div>
                        <div class="col-sm-4 text-sm-end mt-3 mt-sm-0">
                            <i class="ri-pie-chart-2-fill display-4 text-primary opacity-25"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="row mt-4">
        <!-- Daily Report -->
        <div class="col-md-4">
            <div class="card card-animate border-0 shadow-sm">
                <div class="card-body p-4 text-center">
                    <div class="avatar-md mx-auto mb-4">
                        <div class="avatar-title bg-info-subtle text-info rounded-circle fs-24">
                            <i class="ri-calendar-event-line"></i>
                        </div>
                    </div>
                    <h5 class="fw-bold">Daily Report</h5>
                    <p class="text-muted">Analyze day-to-day operations, delays, and task completion.</p>
                    <form action="{{ route('admin.reports.daily') }}" method="GET" class="mt-3">
                        <input type="date" name="date" class="form-control mb-3" value="{{ date('Y-m-d') }}">
                        <button type="submit" class="btn btn-info w-100">Generate Daily</button>
                    </form>
                </div>
            </div>
        </div>

        <!-- Weekly Report -->
        <div class="col-md-4">
            <div class="card card-animate border-0 shadow-sm">
                <div class="card-body p-4 text-center">
                    <div class="avatar-md mx-auto mb-4">
                        <div class="avatar-title bg-success-subtle text-success rounded-circle fs-24">
                            <i class="ri-line-chart-line"></i>
                        </div>
                    </div>
                    <h5 class="fw-bold">Weekly Performance</h5>
                    <p class="text-muted">Review weekly trends and driver consistency over time.</p>
                    <form action="{{ route('admin.reports.weekly') }}" method="GET" class="mt-3">
                        <div class="input-group mb-3">
                            <input type="date" name="start_date" class="form-control" value="{{ date('Y-m-d', strtotime('monday this week')) }}">
                            <input type="date" name="end_date" class="form-control" value="{{ date('Y-m-d') }}">
                        </div>
                        <button type="submit" class="btn btn-success w-100">View Weekly</button>
                    </form>
                </div>
            </div>
        </div>

        <!-- Monthly Evaluation -->
        <div class="col-md-4">
            <div class="card card-animate border-0 shadow-sm">
                <div class="card-body p-4 text-center">
                    <div class="avatar-md mx-auto mb-4">
                        <div class="avatar-title bg-warning-subtle text-warning rounded-circle fs-24">
                            <i class="ri-award-line"></i>
                        </div>
                    </div>
                    <h5 class="fw-bold">Monthly Evaluation</h5>
                    <p class="text-muted">Comprehensive scoring and ranking for all drivers.</p>
                    <form action="{{ route('admin.reports.monthly') }}" method="GET" class="mt-3">
                        <input type="month" name="month" class="form-control mb-3" value="{{ date('Y-m') }}">
                        <button type="submit" class="btn btn-warning text-white w-100">Monthly Ranking</button>
                    </form>
                </div>
            </div>
        </div>
    </div>
@endsection
