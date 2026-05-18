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


@section('css')
    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" type="text/css" />
@endsection

<div class="card modern-filter-card">
    <div class="card-header">
        <h4 class="card-title mb-0">{{ trans('translation.missing') }} {{ trans('translation.sample.title') }}</h4>
    </div>
    <div class="card-body">
        <div class="row">
            <div class="col-lg-8 mb-3">
                <label for="sample">Enter Sample</label>
                <input id="sample" class="form-control" type="text"
                    placeholder="Scan or type sample barcode..." autofocus>
            </div>

            @isset($recentMissingSamples)
                @if ($recentMissingSamples->count() > 0)
                    <div class="col-lg-12 mb-3">
                        <label class="d-block mb-2" style="font-size: 0.85rem; color: #64748b;">
                            <i class="ri-history-line"></i> Recently missing / pending samples
                            <span style="font-size: 0.75rem; opacity: 0.8;">— click to fill</span>
                        </label>
                        <div class="d-flex flex-wrap" style="gap: 6px;">
                            @foreach ($recentMissingSamples as $sample)
                                <button type="button"
                                    class="btn btn-sm sample-chip"
                                    data-barcode="{{ $sample->barcode_id }}"
                                    title="Sample #{{ $sample->id }} · {{ $sample->confirmed_by_client }}"
                                    style="background: rgba(13, 148, 136, 0.08); border: 1px solid rgba(13, 148, 136, 0.20); color: #0d9488; border-radius: 6px; padding: 4px 10px; font-size: 0.8rem; font-weight: 500;">
                                    <i class="ri-barcode-line"></i> {{ $sample->barcode_id }}
                                    @if ($sample->confirmed_by_client === 'LOST')
                                        <span class="badge ms-1" style="background: rgba(239, 68, 68, 0.15); color: #dc2626; font-size: 0.65rem;">LOST</span>
                                    @endif
                                </button>
                            @endforeach
                        </div>
                    </div>
                @endif
            @endisset

            <div class="col-lg-12 d-flex flex-wrap mt-2" style="gap: 10px;">
                @can('mark_as_lost')
                    <button type="button" id="mark_as_lost" class="btn btn-create mb-1"
                        style="background:linear-gradient(135deg,#ef4444 0%,#dc2626 100%);box-shadow:0 4px 12px rgba(239,68,68,0.28);">
                        <i class="ri-close-circle-line"></i> Mark As lost
                    </button>
                @endcan
                <button type="button" id="mark_as_confirmed" class="btn btn-create mb-1"
                    style="background:linear-gradient(135deg,#22c55e 0%,#16a34a 100%);box-shadow:0 4px 12px rgba(34,197,94,0.28);">
                    <i class="ri-checkbox-circle-line"></i> Mark As confirmed
                </button>
                @can('check_receiving_details')
                    <button type="button" id="get_details" class="btn btn-create mb-1"
                        style="background:linear-gradient(135deg,#3b82f6 0%,#2563eb 100%);box-shadow:0 4px 12px rgba(59,130,246,0.28);">
                        <i class="ri-information-line"></i> Get Details
                    </button>
                @endcan
            </div>
        </div>

        <div id="resultCard" class="mt-3"></div>
    </div>
</div>

<style>
    .result-hint {
        display: inline-flex;
        align-items: flex-start;
        gap: 8px;
        padding: 8px 14px;
        border-radius: 8px;
        font-size: 0.85rem;
        font-weight: 500;
        line-height: 1.45;
        max-width: 100%;
        box-shadow: 0 1px 2px rgba(15, 23, 42, 0.04);
        animation: resultHintIn .18s ease-out;
    }
    .result-hint .result-hint__icon {
        font-size: 1.05rem;
        line-height: 1;
        margin-top: 1px;
        flex-shrink: 0;
    }
    .result-hint--success {
        background: rgba(34, 197, 94, 0.10);
        border: 1px solid rgba(34, 197, 94, 0.25);
        color: #16a34a;
    }
    .result-hint--error {
        background: rgba(239, 68, 68, 0.10);
        border: 1px solid rgba(239, 68, 68, 0.25);
        color: #dc2626;
    }
    .result-hint--info {
        background: rgba(13, 148, 136, 0.08);
        border: 1px solid rgba(13, 148, 136, 0.22);
        color: #0d9488;
    }
    .result-hint__details {
        margin: 6px 0 0;
        padding: 0;
        list-style: none;
        font-size: 0.78rem;
        font-weight: 400;
        color: #475569;
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(180px, 1fr));
        gap: 4px 16px;
    }
    .result-hint__details li { display: flex; gap: 4px; }
    .result-hint__details li strong { color: #0f172a; font-weight: 600; }
    @keyframes resultHintIn {
        from { opacity: 0; transform: translateY(-3px); }
        to   { opacity: 1; transform: translateY(0); }
    }
</style>

<div class="hstack flex-wrap gap-2">
    <button type="button" hidden id="success-message" data-toast data-toast-text="Success" data-toast-gravity="top"
        data-toast-position="center" data-toast-className="success" data-toast-duration="3000"
        class="btn btn-light w-xs"></button>
    <button type="button" hidden id="failed-message" data-toast data-toast-text="Error" data-toast-gravity="top"
        data-toast-position="center" data-toast-className="danger" data-toast-duration="3000"
        class="btn btn-light w-xs"></button>
</div>
@endsection
@section('script')
<script src="https://cdn.jsdelivr.net/npm/scandit-sdk@5.x"></script>
<script src="https://ajax.googleapis.com/ajax/libs/jquery/3.5.1/jquery.min.js"></script>
<script>
    const authUserName = @json(Auth::user()->name);
</script>
<script>
    var user = {!! auth()->user()->toJson() !!};
    console.log(user);
    var username = user.email;
    var BatchSamples = [];

    // Click a recent-sample chip to fill the input and re-focus for the next scan/action.
    $(document).on('click', '.sample-chip', function () {
        var barcode = $(this).data('barcode');
        if (barcode !== undefined && barcode !== null) {
            $('#sample').val(barcode).trigger('focus');
        }
    });

    function escapeHtml(s) {
        return String(s == null ? '' : s)
            .replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;').replace(/'/g, '&#39;');
    }

    // Render the action response as a small inline hint (success / error / info with optional detail rows).
    function showResult(type, message, details) {
        var icons = {
            success: 'ri-checkbox-circle-fill',
            error:   'ri-error-warning-fill',
            info:    'ri-information-fill'
        };
        var icon = icons[type] || icons.info;
        var html = '<div class="result-hint result-hint--' + type + '">' +
                       '<i class="' + icon + ' result-hint__icon"></i>' +
                       '<div>' +
                           '<div>' + escapeHtml(message) + '</div>';
        if (details && Object.keys(details).length) {
            html += '<ul class="result-hint__details">';
            Object.keys(details).forEach(function (label) {
                var val = details[label];
                if (val === null || val === undefined || val === '') return;
                html += '<li><strong>' + escapeHtml(label) + ':</strong> ' + escapeHtml(val) + '</li>';
            });
            html += '</ul>';
        }
        html += '</div></div>';
        $('#resultCard').html(html);
    }
    $("#mark_as_lost").on("click", function() {
        $.ajax({
            type: "POST",
            url: "/api/client/samples/lost",
            data: JSON.stringify({
                'sample': $("#sample").val(),
                'marked_by': username
            }),
            dataType: 'json',
            contentType: "application/json; charset=utf-8",
        }).done(function(response) {
            showResult(response.status ? 'success' : 'error', response.message);
        }).fail(function(jqXHR) {
            showResult('error', (jqXHR.responseJSON && jqXHR.responseJSON.errorMessage) || 'Request failed');
        });
    });

    $("#get_details").on("click", function() {
        console.log('get')
        $.ajax({
            type: "POST",
            url: "/api/client/samples/details",
            data: JSON.stringify({
                'sample': $("#sample").val(),
                'username': username
            }),
            dataType: 'json',
            contentType: "application/json; charset=utf-8",
        }).done(function(response) {
            if (response.status) {
                var data = response.data || {};
                showResult('info', 'Sample details', {
                    'Barcode ID':           data.barcode_id,
                    'Task ID':              data.task_id,
                    'Temperature Type':     data.temperature_type,
                    'Confirmed By':         data.confirmed_by,
                    'Confirmed By Client':  data.confirmed_by_client,
                    'Sample Type':          data.sample_type
                });
            } else {
                showResult('error', response.data || response.message || 'No details found');
            }
        }).fail(function() {
            showResult('error', 'Error occurred during API request');
        });
    });

    $("#mark_as_confirmed").on("click", function() {
        // add to samples
        BatchSamples.push($("#sample").val());
        $.ajax({
            type: "POST",
            url: "/api/client/samples/confirm",
            data: JSON.stringify({
                'samples': BatchSamples,
                'confirmed_by': authUserName
            }),
            dataType: 'json',
            contentType: "application/json; charset=utf-8",
        }).done(function(response) {
            BatchSamples = [];
            showResult(response.status ? 'success' : 'error', response.message);
        }).fail(function(jqXHR) {
            BatchSamples = [];
            showResult('error', (jqXHR.responseJSON && jqXHR.responseJSON.errorMessage) || 'Request failed');
        });
    });
</script>

<script src="{{ URL::asset('/assets/js/app.min.js') }}"></script>
@endsection
