@extends('layouts.master')
@section('title') Preparing Export @endsection
@section('content')
    <div class="row justify-content-center">
        <div class="col-lg-8 col-xl-6">
            <div class="card modern-filter-card mt-4">
                <div class="card-header">
                    <h4 class="card-title mb-0">Preparing your task report</h4>
                </div>
                <div class="card-body text-center py-5">
                    <div id="mf-export-pending">
                        <div class="mb-4">
                            <div class="spinner-border" role="status" style="width:3rem;height:3rem;color:#0d9488">
                                <span class="visually-hidden">Loading...</span>
                            </div>
                        </div>
                        <h5 class="mb-2">Building your report file...</h5>
                        <p class="text-muted mb-1" id="mf-export-message">This may take up to a few minutes for large exports.</p>
                        <p class="text-muted small">You can leave this page open — your download will start automatically.</p>
                    </div>

                    <div id="mf-export-ready" class="d-none">
                        <div class="mb-3" style="font-size:3rem">✅</div>
                        <h5 class="mb-3">Your file is ready</h5>
                        <p class="text-muted mb-4" id="mf-export-count"></p>
                        <a id="mf-export-download" href="#" class="btn btn-search">
                            <i class="ri-download-2-line"></i> Download report
                        </a>
                        <div class="mt-3">
                            <a href="{{ route('admin.tasks.index') }}" class="btn btn-reset">Back to tasks</a>
                        </div>
                    </div>

                    <div id="mf-export-error" class="d-none">
                        <div class="mb-3" style="font-size:3rem">⚠️</div>
                        <h5 class="mb-2 text-danger">Export failed</h5>
                        <p class="text-muted small" id="mf-export-error-message"></p>
                        <a href="{{ route('admin.tasks.index') }}" class="btn btn-reset mt-3">Back to tasks</a>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
@section('scripts')
    @parent
    <script>
        (function () {
            const statusUrl = "{{ route('admin.tasks.export.status', ['token' => $token]) }}" + '?status=1';
            const pendingEl  = document.getElementById('mf-export-pending');
            const readyEl    = document.getElementById('mf-export-ready');
            const errorEl    = document.getElementById('mf-export-error');
            const downloadEl = document.getElementById('mf-export-download');
            const countEl    = document.getElementById('mf-export-count');
            const errorMsgEl = document.getElementById('mf-export-error-message');
            const messageEl  = document.getElementById('mf-export-message');

            let started = Date.now();
            let downloaded = false;

            function poll() {
                fetch(statusUrl, { headers: { 'Accept': 'application/json' } })
                    .then(r => r.json())
                    .then(data => {
                        if (data.state === 'ready') {
                            pendingEl.classList.add('d-none');
                            readyEl.classList.remove('d-none');
                            countEl.textContent = (data.count || 0).toLocaleString() + ' tasks included';
                            downloadEl.href = data.download;
                            if (!downloaded) {
                                downloaded = true;
                                // Auto-start the download
                                window.location.href = data.download;
                            }
                        } else if (data.state === 'error') {
                            pendingEl.classList.add('d-none');
                            errorEl.classList.remove('d-none');
                            errorMsgEl.textContent = data.error || 'Unknown error';
                        } else {
                            const seconds = Math.floor((Date.now() - started) / 1000);
                            messageEl.textContent = 'Still processing... (' + seconds + 's)';
                            setTimeout(poll, 3000);
                        }
                    })
                    .catch(() => setTimeout(poll, 5000));
            }
            poll();
        })();
    </script>
@endsection
