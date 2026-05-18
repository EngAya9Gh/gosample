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
        <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet"
            type="text/css" />
    @endsection

    <style>
        /* ===== Scan way pill toggle ===== */
        .scan-way-toggle {
            display: inline-flex;
            background: #f8fafc;
            border: 1.5px solid #e2e8f0;
            border-radius: 10px;
            padding: 4px;
            gap: 4px;
            width: 100%;
        }
        .scan-way-toggle .scan-way-option {
            flex: 1;
            background: transparent;
            border: 0;
            color: #475569;
            font-weight: 500;
            font-size: 0.88rem;
            padding: 8px 10px;
            border-radius: 7px;
            cursor: pointer;
            transition: background .15s ease, color .15s ease, box-shadow .2s ease;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 6px;
        }
        .scan-way-toggle .scan-way-option:hover { color: #0d9488; }
        .scan-way-toggle .scan-way-option.is-active {
            background: linear-gradient(135deg, #0ea5a4 0%, #0d9488 100%);
            color: #fff;
            box-shadow: 0 4px 10px rgba(13, 148, 136, 0.28);
        }
        .scan-way-toggle .scan-way-option i { font-size: 1rem; }

        /* ===== Batch Samples panel ===== */
        .batch-panel {
            background: #ffffff;
            border: 1.5px solid #e2e8f0;
            border-radius: 12px;
            min-height: 360px;
            display: flex;
            flex-direction: column;
            overflow: hidden;
        }
        .batch-panel__header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 12px 16px;
            background: linear-gradient(135deg, #0ea5a4 0%, #0d9488 100%);
            color: #fff;
        }
        .batch-panel__title {
            font-weight: 600;
            font-size: 0.95rem;
            letter-spacing: 0.02em;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            margin: 0;
            color: #ffffff !important;
        }
        .batch-panel__title i { color: #ffffff !important; }
        .batch-panel__count {
            background: rgba(255, 255, 255, 0.20);
            color: #fff;
            font-weight: 700;
            min-width: 32px;
            text-align: center;
            border-radius: 999px;
            padding: 2px 10px;
            font-size: 0.85rem;
        }
        .batch-panel__body {
            flex: 1;
            overflow-y: auto;
            padding: 12px;
            max-height: 360px;
        }
        .batch-panel__body::-webkit-scrollbar { width: 6px; }
        .batch-panel__body::-webkit-scrollbar-thumb { background: #cbd5e1; border-radius: 3px; }
        .batch-panel__empty {
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            text-align: center;
            color: #94a3b8;
            padding: 30px 16px;
            font-size: 0.85rem;
            height: 100%;
        }
        .batch-panel__empty i { font-size: 2.2rem; opacity: 0.5; margin-bottom: 8px; color: #94a3b8; }

        /* Barcode pills inside the batch list */
        #extracted-codes { display: flex; flex-direction: column; gap: 6px; }
        #extracted-codes p {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            margin: 0;
            padding: 8px 12px;
            background: rgba(13, 148, 136, 0.08) !important;
            border: 1px solid rgba(13, 148, 136, 0.20);
            border-radius: 8px;
            color: #0d9488 !important;
            font-family: 'Menlo', 'Courier New', monospace;
            font-size: 0.82rem;
            font-weight: 600;
            letter-spacing: 0.02em;
            transition: background .15s ease, transform .12s ease;
        }
        #extracted-codes p::before {
            content: "\\eb56"; /* ri-barcode-line */
            font-family: 'remixicon';
            font-size: 1rem;
            opacity: 0.7;
        }
        #extracted-codes p:hover {
            background: rgba(13, 148, 136, 0.14) !important;
            transform: translateX(2px);
        }

        /* Status message bar */
        #batch-created {
            display: flex;
            flex-direction: column;
            gap: 6px;
            margin-bottom: 12px;
        }
        #batch-created:empty { display: none; }
        #batch-created p {
            display: inline-flex;
            align-items: flex-start;
            gap: 8px;
            margin: 0;
            padding: 8px 12px;
            border-radius: 8px;
            font-size: 0.85rem;
            font-weight: 500;
            line-height: 1.4;
            animation: hintIn .18s ease-out;
            background: rgba(13, 148, 136, 0.08) !important;
            border: 1px solid rgba(13, 148, 136, 0.22);
            color: #0d9488 !important;
        }
        @keyframes hintIn {
            from { opacity: 0; transform: translateY(-3px); }
            to   { opacity: 1; transform: translateY(0); }
        }

        /* ===== Camera (Scandit) container ===== */
        .scan-camera-wrap {
            background: #0f172a;
            border-radius: 12px;
            min-height: 360px;
            overflow: hidden;
            position: relative;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        #scandit-barcode-picker {
            width: 100%;
            height: 100%;
            min-height: 360px;
        }
        .scan-camera-placeholder {
            position: absolute;
            top: 0; left: 0; right: 0; bottom: 0;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            text-align: center;
            color: #cbd5e1;
            padding: 30px;
            pointer-events: none;
        }
        .scan-camera-placeholder i {
            font-size: 3rem;
            opacity: 0.4;
            display: block;
            margin-bottom: 10px;
        }
    </style>

    {{-- ===================== FILTERS / CONTROLS CARD ===================== --}}
    <div class="card modern-filter-card">
        <div class="card-header">
            <h4 class="card-title mb-0">
                <i class="ri-qr-scan-2-line"></i>
                {{ trans('translation.scan') }} {{ trans('translation.sample.title_singular') }}
            </h4>
        </div>
        <div class="card-body">
            <div class="row">
                <div class="col-lg-4 mb-3">
                    <label for="to_location">{{ trans('translation.task.fields.to_location') }}</label>
                    <select class="form-control select2" name="to_location" id="to_location">
                        @foreach ($to_locations as $id => $entry)
                            <option value="{{ $id }}" {{ old('to_location') == $id ? 'selected' : '' }}>
                                {{ $entry }}
                            </option>
                        @endforeach
                    </select>
                    @if ($errors->has('to_location'))
                        <div class="invalid-feedback">{{ $errors->first('to_location') }}</div>
                    @endif
                </div>

                <div class="col-lg-4 mb-3">
                    <label for="driver_id">{{ trans('translation.task.fields.driver') }}</label>
                    <select class="form-control select2" name="driver_id" id="driver_id">
                        @foreach ($drivers as $id => $entry)
                            <option value="{{ $id }}" {{ old('driver_id') == $id ? 'selected' : '' }}>
                                {{ $entry }}
                            </option>
                        @endforeach
                    </select>
                    @if ($errors->has('driver'))
                        <div class="invalid-feedback">{{ $errors->first('driver') }}</div>
                    @endif
                </div>

                <div class="col-lg-4 mb-3">
                    <label for="scan_way">Scan way</label>
                    {{-- Hidden input keeps existing JS contract working — read via
                         document.getElementById("scan_way").value. Using <input type="hidden">
                         instead of <select> so nothing (e.g. global select2 init) can wrap it. --}}
                    <input type="hidden" name="scan_way" id="scan_way" value="reader">
                    {{-- Visible icon pill toggle --}}
                    <div class="scan-way-toggle" role="tablist">
                        <button type="button" class="scan-way-option is-active" data-value="reader">
                            <i class="ri-barcode-fill"></i> Reader
                        </button>
                        <button type="button" class="scan-way-option" data-value="manual">
                            <i class="ri-keyboard-line"></i> Manual
                        </button>
                        <button type="button" class="scan-way-option" data-value="camera">
                            <i class="ri-camera-line"></i> Camera
                        </button>
                    </div>
                </div>

                <div class="col-lg-12 d-flex justify-content-end flex-wrap mt-1">
                    <button class="btn btn-search mb-1" id="create-batch" type="button">
                        <i class="ri-download-2-line"></i> {{ trans('translation.getSamples') }}
                    </button>
                </div>
            </div>
        </div>
    </div>

    {{-- ===================== LIST SAMPLES CARD ===================== --}}
    <div class="card">
        <div class="card-header d-flex justify-content-between align-items-center flex-wrap">
            <h5 class="card-title mb-0">
                <i class="ri-list-check-2"></i>
                {{ trans('translation.list') }} {{ trans('translation.sample.title') }}
            </h5>
            @can('confirm_all')
                <button id="confirm-all-btn" class="btn btn-create mb-1" type="button" onclick="confirmAll()"
                    style="background:linear-gradient(135deg,#b07ab5 0%,#9560a0 100%);box-shadow:0 4px 12px rgba(149,96,160,0.28);">
                    <i class="ri-checkbox-multiple-line"></i> Confirm All
                </button>
            @endcan
        </div>

        <div class="card-body">
            <div id="batch-created"></div>

            <div class="row">
                <div class="col-lg-4 mb-3">
                    <div class="batch-panel">
                        <div class="batch-panel__header">
                            <h6 class="batch-panel__title">
                                <i class="ri-stack-line"></i> Batch Samples
                            </h6>
                            <span class="batch-panel__count" id="batch-count">0</span>
                        </div>
                        <div class="batch-panel__body">
                            <div id="extracted-codes"></div>
                            <div class="batch-panel__empty" id="batch-empty-state">
                                <i class="ri-inbox-line"></i>
                                <div>No samples yet</div>
                                <div style="font-size: 0.78rem; opacity: 0.8; margin-top: 4px;">
                                    Pick a location and driver, then click <strong>Get Samples</strong>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-lg-8 mb-3">
                    <div class="scan-camera-wrap">
                        <div id="scandit-barcode-picker"></div>
                        <div class="scan-camera-placeholder" id="scan-camera-placeholder">
                            <i class="ri-camera-off-line"></i>
                            <div>Camera not active</div>
                            <div style="font-size: 0.78rem; opacity: 0.7; margin-top: 4px;">
                                Pick <strong>Camera</strong> scan mode and click Get Samples to start
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection

@section('script')
    <script src="{{ asset('assets/js/onscan.min.js') }}"></script>
    <script src="{{ URL::asset('/assets/js/app.min.js') }}"></script>
    <script src="https://cdn.jsdelivr.net/npm/scandit-sdk@5.x"></script>

    <script>
        const authUserName = @json(Auth::user()->name);
    </script>

    <script>
        // ============== Helpers (visual only — no logic change) ==============
        // Keep the empty-state shown when there are 0 sample barcodes.
        function refreshEmptyState() {
            var hasItems = $('#extracted-codes').children().length > 0;
            $('#batch-empty-state').toggle(!hasItems);
        }
        // Re-check after any DOM change to the codes list.
        new MutationObserver(refreshEmptyState).observe(
            document.getElementById('extracted-codes'),
            { childList: true }
        );

        // Hide the camera placeholder when scandit injects its own UI.
        new MutationObserver(function () {
            var picker = document.getElementById('scandit-barcode-picker');
            var hasContent = picker && picker.children.length > 0;
            $('#scan-camera-placeholder').toggle(!hasContent);
        }).observe(document.getElementById('scandit-barcode-picker'), { childList: true });

        // Scan-way pill toggle → just stores the value in the hidden <select>.
        // Scanner is initialised only when the user clicks "Get Samples".
        $(document).on('click', '.scan-way-option', function () {
            $('.scan-way-option').removeClass('is-active');
            $(this).addClass('is-active');
            $('#scan_way').val($(this).data('value'));
        });

        // ============== Original logic — unchanged behavior ==============
        function confirmAll() {
            if (confirm("Are you sure you want to confirm all samples in the batch?")) {
                $.ajax({
                    type: "POST",
                    url: "/api/task/sample/confirmall",
                    data: JSON.stringify({
                        'driver_id': $("#driver_id option:selected").val(),
                        'to_location': $("#to_location option:selected").val(),
                        'confirm_by': authUserName,
                    }),
                    dataType: 'json',
                    contentType: "application/json; charset=utf-8",
                }).done(function(data, textStatus, xhr) {
                    console.log(data);
                    if (data.status) {
                        console.log('succss');
                        location.reload();
                    } else {
                        console.log('failed');
                        alert(data.message);
                    }
                }).fail(function(jqXHR, textStatus, errorThrown) {
                    $("#batch-created").prepend("<p style='color:palevioletred'>" + jqXHR.responseJSON.message +
                        "</p>");
                });
            }
        }

        var user = {!! auth()->user()->toJson() !!};

        var username = user.username;
        var BatchSamples = [];
        var BatchTotal = 0;
        var scanditToken =
            "AcvB7K6BOkafNr8EEhzGnk0BjSadL6zs3WzHFGUMOnCnRfH4y3u5G21mmZ14cIxKOXY/znlQ+ABgeCJMm2ApWqtEX/A0a1O4jCzdQLdrzBgqYZn5cVXKSVhIbOsebE+lswu8ragddRSGNRaYngV+kMYpJcWvJ7bmd+1YofZZhXeehiabYMwbAG5pCAyQjKAMZw/wCBZ3D6C5MLfRPT5yFbYyRCjwFTCjlEWkcUFZkueSoywVBJM/M/HSUdCSmCgBlNnM9yVlB0wM+IsYiTDLGLAl5kdLc+d5wHVfQLKNz6Dvkew55fZNy/MtrHq087CIcEny/HOt0FKcNVMkJgkb7Sg476PlBWyxvMpQZvxlz4lDXawtHoB6D0PevIVw3hV0tSwIinnqP9lVW6vqyp2EE4dk2v5vZBy9L85kng6PZdnktiYPp2yuR4B9tKrtSoY+DXDRp05HyTYHNQaXONfE2fa3KQ7IxlAJjCWb6qkK+M+nUS4XiloPAOMvXWc7p49umgHkqNhHuVHZ8qZar5D/mr4BNGK/T+cTGrM96OTegYTRxF2q/ibRrd4XZtyCWCvQ+/I2f2qL0N1C3NUcq2fuhPLHqGPgKkrLeHy4BLLR+qx+qRo0xl/yU0oHrSCFlYdJ8jog8mwY9yDY6Q5TRSPwLJ0OInwal9xb+axEc5Ziw2KGTA6wC5CBFo/alYle8PcIUQzvNs2tudFAOhZNSA5ipQlahAWH7cqfmcGaZALUUoQeuiAMekb3SDG0H7altefmhxNma5wBX7zJ2wrHxVAT4h+knTA64iwktiiVufh+lat8HJlknPQooSMQsSuipdGSmAENdGwX"

        var scanditPicker = null;
        function configure() {
            // Guard: don't create a second Scandit instance (stacked video panels).
            if (scanditPicker) return;
            var pickerDiv = document.getElementById("scandit-barcode-picker");
            if (pickerDiv && pickerDiv.children.length > 0) return;

            ScanditSDK.configure(scanditToken, {
                engineLocation: "https://cdn.jsdelivr.net/npm/scandit-sdk@5.x/build/",
            }).then(() => {
                return ScanditSDK.BarcodePicker.create(document.getElementById("scandit-barcode-picker"), {
                    playSoundOnScan: true,
                    scanSettings: new ScanditSDK.ScanSettings({
                        enabledSymbologies: ["ean13"],
                        codeDuplicateFilter: 2000
                    }),
                });
            }).then((barcodePicker) => {
                scanditPicker = barcodePicker;
                barcodePicker.on("scan", (scanResult) => {
                    var task = location.href.split('/')[6];
                    code = scanResult.barcodes[0].data;
                    code = code.replace(/^0+/, '');
                    code = code.substring(0, code.length);
                    if (BatchSamples.includes(code)) {
                        $.ajax({
                            type: "POST",
                            url: "/api/task/sample/check",
                            data: JSON.stringify({
                                'task_id': task,
                                'sample_id': code,
                                'confirmed_by': user.username
                            }),
                            dataType: 'json',
                            contentType: "application/json; charset=utf-8",
                        }).done(function(data, textStatus, xhr) {
                            if (data.status) {
                                if (BatchSamples.includes(code)) {
                                    BatchSamples.splice(BatchSamples.indexOf(code), 1);

                                    $("#extracted-codes").empty();
                                    $.each(BatchSamples, function(i) {
                                        $("#extracted-codes").prepend(
                                            "<p style='color:palevioletred'>" +
                                            BatchSamples[i] + "</p>");
                                    });
                                    $("#batch-created").html("Samples is removed successfully");

                                    $("#batch-count").html(BatchSamples.length);

                                } else {
                                    $("#batch-created").empty();
                                    $("#extracted-codes").prepend(
                                        "<p style='color:palevioletred'>Sample is not in the batch</p>"
                                    );
                                }
                            } else {

                                $("#batch-created").prepend("<p style='color:palevioletred'>" + data
                                    .message + "</p>");

                            }
                        }).fail(function(jqXHR, textStatus, errorThrown) {
                            $("#batch-created").prepend("<p style='color:palevioletred'>" + jqXHR
                                .responseJSON.message + "</p>");
                        });
                    } else {
                        $("#batch-created").empty();
                        $("#batch-created").prepend(
                            "<p style='color:palevioletred'>Sample is not in the batch</p>");
                    }
                });
            });
        }


        $("#confirm-batch").click(function() {
            alert("Handler for .click() called.");
        });

        $("#confirm-batch").on("click", function() {
            $.ajax({
                type: "POST",
                url: "/api/client/samples/confirm",
                data: JSON.stringify({
                    'samples': BatchSamples,
                    'confirmed_by': username
                }),
                dataType: 'json',
                contentType: "application/json; charset=utf-8",
            }).done(function(data, textStatus, xhr) {
                if (data.status) {
                    $("#batch-created").html("Samples are confirmed successfully");
                    BatchSamples = [];
                    $("#batch-count").html(BatchSamples.length);
                    $("#extracted-codes").empty();
                } else {
                    $("#batch-created").html(data.message);
                }
            }).fail(function(jqXHR, textStatus, errorThrown) {
                $("#batch-created").html(jqXHR.responseJSON.errorMessage);
            });
        });

        $("#create-batch").on("click", function() {
            console.log('create-batch');
            var task = 57165;
            BatchSamples = [];
            $("#extracted-codes").empty();
            $.ajax({
                type: "POST",
                url: "/api/driver/samples/valid/check",
                data: JSON.stringify({
                    'task_id': task,
                    'driver_id': $("#driver_id option:selected").val(),
                    'location_id': $("#to_location option:selected").val(),
                }),
                dataType: 'json',
                contentType: "application/json; charset=utf-8",
            }).done(function(data, textStatus, xhr) {
                if (data.status) {
                    if (data.data.length > 0) {
                        var scanWay = document.getElementById("scan_way").value;
                        if (scanWay == 'manual') {
                            // destroyOnScan();
                        }


                        if (scanWay == 'reader') {
                            initOnScan();
                        }
                        if (scanWay == 'camera') {
                            configure(); // init camera
                        }
                        $("#batch-created").html("Samples are retrieved successfully");
                        $.each(data.data, function(i) {
                            $("#extracted-codes").prepend("<p style='color:palevioletred'>" + data
                                .data[i].barcode_id + "</p>");
                            BatchSamples.push(data.data[i].barcode_id);
                        });
                        if (BatchSamples.length > 0) {
                            $("#confirm-batch").show();
                        }
                    } else {
                        $("#batch-created").html("No Samples with selected driver");

                    }


                    $("#batch-count").html(BatchSamples.length);
                } else {
                    $("#batch-created").html(data.message);
                }
            }).fail(function(jqXHR, textStatus, errorThrown) {
                $("#batch-created").html(jqXHR.responseJSON.errorMessage);
            });
        });

        $(document).ready(function() {
            $("#confirm-batch").hide();
            refreshEmptyState();
        });
    </script>


    <script type="text/javascript">
        var onScanAttached = false;
        function initOnScan() {
            // Guard: don't re-attach onScan + duplicate scanHandler listeners.
            if (onScanAttached) {
                console.log("onScan already attached — skipping.");
                return;
            }

            var options = {
                timeBeforeScanTest: 200,
                avgTimeByChar: 60,
                minLength: 6,
                scanButtonLongPressTime: 500,
                singleScanQty: 1,

            }

            try {
                onScan.attachTo(document, options);
                console.log("onScan Started!");
                document.addEventListener('scan', scanHandler);
                document.addEventListener('scanError', scanErrorHandler);
                onScanAttached = true;
            } catch (e) {
                console.log(e)
                console.log("onScansettings changed!");
            }


        }

        function destroyOnScan() {
            console.log("onScan destroyed!");
            onScan.detachFrom(document);
        }

        function scanWayChange() {
            var scanWay = document.getElementById("scan_way").value;
            console.log(scanWay);
            if (scanWay == 'manual') {
                // destroyOnScan();
            }

            if (scanWay == 'reader') {
                initOnScan(); // init barcode reader
            }
            if (scanWay == 'camera') {
                configure(); // init camera
            }

        }

        function scanHandler(e) {

            console.log('scanHandler')
            console.log(e.detail)
            if (e.detail.scanCode == undefined) return;

            var task = location.href.split('/')[6];
            code = e.detail.scanCode;
            console.log('code:' + code);


            if (BatchSamples.includes(code)) {
                $.ajax({
                    type: "POST",
                    url: "/api/task/sample/check",
                    data: JSON.stringify({
                        'task_id': task,
                        'sample_id': code
                    }),
                    dataType: 'json',
                    contentType: "application/json; charset=utf-8",
                }).done(function(data, textStatus, xhr) {
                    if (data.status) {
                        if (BatchSamples.includes(code)) {
                            BatchSamples.splice(BatchSamples.indexOf(code), 1);

                            $("#extracted-codes").empty();
                            $.each(BatchSamples, function(i) {
                                $("#extracted-codes").prepend("<p style='color:palevioletred'>" +
                                    BatchSamples[i] + "</p>");
                            });
                            $("#batch-created").html("Samples is removed successfully");

                            $("#batch-count").html(BatchSamples.length);

                        } else {
                            $("#batch-created").empty();
                            $("#extracted-codes").prepend(
                                "<p style='color:palevioletred'>Sample is not in the batch</p>");
                        }
                    } else {

                        $("#batch-created").prepend("<p style='color:palevioletred'>" + data.message + "</p>");

                    }
                }).fail(function(jqXHR, textStatus, errorThrown) {
                    $("#batch-created").prepend("<p style='color:palevioletred'>" + jqXHR.responseJSON.message +
                        "</p>");
                });
            } else {
                console.log('barcode is not reading well..')
                $("#batch-created").empty();
                $("#batch-created").prepend("<p style='color:palevioletred'>Sample is not in the batch</p>");
            }
        }

        function scanErrorHandler(e) {
            var sFormatedErrorString = "Error Details: {\n";
            for (var i in e.detail) {
                sFormatedErrorString += '    ' + i + ': ' + e.detail[i] + ",\n";
            }
            sFormatedErrorString = sFormatedErrorString.trim().replace(/,$/, '') + "\n}";
            console.log("[scanErrorHandler]: " + sFormatedErrorString);
        }

        function getonScanSettings() {
            var sFormatedErrorString = "Scanner Settings: \n";
            var aJSONArray = JSON.stringify(onScan.getOptions(document)).split(",");
            for (prop = 0; prop < aJSONArray.length - 1; prop++) {
                if (aJSONArray[prop + 1][0] == '\"') {
                    sFormatedErrorString += aJSONArray[prop] + "," + "\n";
                } else {
                    sFormatedErrorString += aJSONArray[prop] + ",";
                }
            }
            sFormatedErrorString += aJSONArray[aJSONArray.length - 1];

            console.log(sFormatedErrorString);


        }
    </script>
    <script>
        $(document).ready(function() {
            $('.select2').select2();
        });
    </script>
@endsection
