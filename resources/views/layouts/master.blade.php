<!doctype html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" data-layout="vertical" data-topbar="light" data-sidebar="dark"
    data-sidebar-size="lg" data-sidebar-image="none" data-preloader="disable">

<head>
    <meta charset="utf-8" />
    <title>@yield('title')| MTC</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta content="MTC" name="description" />
    <meta content="MTC" name="author" />
    <link href="https://cdnjs.cloudflare.com/ajax/libs/dropzone/5.9.2/min/dropzone.min.css" rel="stylesheet">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <script>
        // Some pages expect a global `_token` for AJAX CSRF headers.
        window._token = window._token || document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
    </script>

    <!-- App favicon -->
    <link rel="shortcut icon" href="{{ URL::asset('build/images/favicon.ico') }}">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/choices.js/public/assets/styles/choices.min.css" />
    {{-- @vite(['resources/scss/app.scss', 'resources/js/app.js']) --}}

    @include('layouts.head-css')
</head>
{{-- @vite('resources/js/app.js') --}}
@section('body')
    @include('layouts.body')
@show



<!-- Begin page -->
<div id="layout-wrapper">
    @include('layouts.topbar')
    @include('layouts.sidebar')
    <!-- ============================================================== -->
    <!-- Start right Content here -->
    <!-- ============================================================== -->
    <div class="main-content">
        <div class="page-content">
            <div class="container-fluid">
                @yield('content')
            </div>
            <!-- container-fluid -->
        </div>
        <!-- End Page-content -->
        @include('layouts.footer')
    </div>
    {{-- <script src="https://code.jquery.com/jquery-3.6.0.min.js"
        integrity="sha256-/xUj+3OJU5yExlq6GSYGSHk7tPXikynS7ogEvDej/m4=" crossorigin="anonymous"></script> --}}

    <!--datatable js-->

</div>

{{-- <iframe src="https://www.chatbase.co/chatbot-iframe/xDF49r8Xag2USDU1n8so6" width="100%" height="700"
    frameborder="0"></iframe> --}}
<!-- END layout-wrapper -->

<script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>
<script src="https://cdn.jsdelivr.net/npm/choices.js/public/assets/scripts/choices.min.js"></script>
<script src="https://unpkg.com/feather-icons@4.29.2/dist/feather.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/dropzone/5.9.2/min/dropzone.min.js"></script>
{{-- <script src="assets/libs/dropzone/dropzone-min.js"></script> --}}


<script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.3.1/jquery.min.js"></script>
<script src="https://stackpath.bootstrapcdn.com/bootstrap/4.1.1/js/bootstrap.min.js"></script>    
<script src="https://cdnjs.cloudflare.com/ajax/libs/popper.js/1.14.3/umd/popper.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/jquery.perfect-scrollbar/1.5.0/perfect-scrollbar.min.js"></script>
<script src="https://unpkg.com/@coreui/coreui@3.2/dist/js/coreui.min.js"></script>
<script src="//cdn.datatables.net/1.10.19/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/1.10.19/js/dataTables.bootstrap4.min.js"></script>
<script src="//cdn.datatables.net/buttons/1.2.4/js/dataTables.buttons.min.js"></script>
<script src="//cdn.datatables.net/buttons/1.2.4/js/buttons.flash.min.js"></script>
<script src="https://cdn.datatables.net/buttons/1.2.4/js/buttons.html5.min.js"></script>
<script src="https://cdn.datatables.net/buttons/1.2.4/js/buttons.print.min.js"></script>
<script src="https://cdn.datatables.net/buttons/1.2.4/js/buttons.colVis.min.js"></script>
<script src="https://cdn.rawgit.com/bpampuch/pdfmake/0.1.18/build/pdfmake.min.js"></script>
<script src="https://cdn.rawgit.com/bpampuch/pdfmake/0.1.18/build/vfs_fonts.js"></script>
<script src="//cdnjs.cloudflare.com/ajax/libs/jszip/2.5.0/jszip.min.js"></script>
<script src="https://cdn.datatables.net/select/1.3.0/js/dataTables.select.min.js"></script>
<script src="https://cdn.ckeditor.com/ckeditor5/16.0.0/classic/ckeditor.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/moment.js/2.22.2/moment.min.js"></script>
<script
    src="https://cdnjs.cloudflare.com/ajax/libs/bootstrap-datetimepicker/4.17.47/js/bootstrap-datetimepicker.min.js">
</script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/select2/4.0.5/js/select2.full.min.js"></script>
{{-- <script src="https://cdnjs.cloudflare.com/ajax/libs/dropzone/5.5.1/min/dropzone.min.js"></script> --}}
{{-- <script src="{{ asset('build/js/app.js') }}"></script> --}}

<script>
    $(function() {

        // Wrap each label in a span with inline white color — this forces readable text
        // on the colored toolbar buttons. Inline color on the rendered <span> wins over
        // every other CSS rule because it's an attribute on the actual DOM node DataTables
        // creates.
        let copyButtonTrans  = '<span style="color:#fff;-webkit-text-fill-color:#fff">Copy</span>';
        let csvButtonTrans   = '<span style="color:#fff;-webkit-text-fill-color:#fff">CSV</span>';
        let excelButtonTrans = '<span style="color:#fff;-webkit-text-fill-color:#fff">Excel</span>';
        let pdfButtonTrans   = '<span style="color:#fff;-webkit-text-fill-color:#fff">PDF</span>';
        let printButtonTrans = '<span style="color:#fff;-webkit-text-fill-color:#fff">Print</span>';
        // let colvisButtonTrans = 'columns'
        // let selectAllButtonTrans = 'Select All'
        // let selectNoneButtonTrans = 'Deselect All'
        // let copyButtonTrans = '{{ trans('cruds.datatables.copy') }}'
        // let csvButtonTrans = '{{ trans('translation.datatables.csv') }}'
        // let excelButtonTrans = '{{ trans('translation.datatables.excel') }}'
        // let pdfButtonTrans = '{{ trans('translation.datatables.pdf') }}'
        // let printButtonTrans = '{{ trans('translation.datatables.print') }}'
        // let colvisButtonTrans = '{{ trans('translation.datatables.colvis') }}'
        // let selectAllButtonTrans = '{{ trans('translation.select_all') }}'
        // let selectNoneButtonTrans = '{{ trans('translation.deselect_all') }}'

        let languages = {
            'en': '/English.json'
        };

        $.extend(true, $.fn.dataTable.Buttons.defaults.dom.button, {
            className: 'btn'
        })
        $.extend(true, $.fn.dataTable.defaults, {
            language: {
                url: languages['{{ app()->getLocale() }}']
            },
            columnDefs: [{
                orderable: false,
                className: 'select-checkbox',
                targets: 0
            }, {
                orderable: false,
                searchable: false,
                targets: -1
            }],
            select: {
                style: 'multi+shift',
                selector: 'td:first-child'
            },
            order: [],
            scrollX: true,
            pageLength: 10,
            dom: 'lBfrtip<"actions">',
            buttons: [
                // {
                //     extend: 'selectAll',
                //     className: 'btn-primary',
                //     text: selectAllButtonTrans,
                //     exportOptions: {
                //         columns: ':visible'
                //     },
                //     action: function(e, dt) {
                //         e.preventDefault()
                //         dt.rows().deselect();
                //         dt.rows({ search: 'applied' }).select();
                //     }
                // },
                // {
                //     extend: 'selectNone',
                //     className: 'btn-primary',
                //     text: selectNoneButtonTrans,
                //     exportOptions: {
                //         columns: ':visible'
                //     }
                // },
                {
                    extend: 'copy',
                    className: 'btn-default',
                    text: copyButtonTrans,
                    exportOptions: {
                        columns: ':visible'
                    }
                },
                {
                    extend: 'csv',
                    className: 'btn-default',
                    text: csvButtonTrans,
                    exportOptions: {
                        columns: ':visible'
                    }
                },
                {
                    extend: 'excel',
                    className: 'btn-default',
                    text: excelButtonTrans,
                    exportOptions: {
                        columns: ':visible'
                    }
                },
                // {
                //     extend: 'pdf',
                //     className: 'btn-default',
                //     text: pdfButtonTrans,
                //     exportOptions: {
                //         columns: ':visible'

                //     }
                // },
                {
                    extend: 'print',
                    className: 'btn-default',
                    text: printButtonTrans,
                    exportOptions: {
                        columns: ':visible'
                    }
                },
                // {
                //     extend: 'colvis',
                //     className: 'btn-default',
                //     text: colvisButtonTrans,
                //     exportOptions: {
                //         columns: ':visible'
                //     }
                // }
            ]
        });

        $.fn.dataTable.ext.classes.sPageButton = '';

        /* -------------------------------------------------------------------
         * Force WHITE text on every DataTables Buttons toolbar button.
         * Bootstrap/Velzon rules with `button.btn span` specificity were beating
         * our stylesheet rules, so we set inline styles with the !important flag
         * via setProperty — that's the absolute top of the CSS cascade and no
         * other rule can override it. Runs on init AND on every redraw, so it
         * survives pagination, sort, search, etc.
         * -----------------------------------------------------------------*/
        function _mfForceWhiteDtButtons() {
            document.querySelectorAll('.dataTables_wrapper .dt-button').forEach(function (btn) {
                btn.style.setProperty('color', '#ffffff', 'important');
                btn.style.setProperty('-webkit-text-fill-color', '#ffffff', 'important');
                btn.style.setProperty('text-shadow', 'none', 'important');
                btn.querySelectorAll('*').forEach(function (child) {
                    child.style.setProperty('color', '#ffffff', 'important');
                    child.style.setProperty('-webkit-text-fill-color', '#ffffff', 'important');
                    child.style.setProperty('text-shadow', 'none', 'important');
                });
            });
        }
        // Run repeatedly until DataTables has finished rendering its toolbar
        var _mfTries = 0;
        var _mfInterval = setInterval(function () {
            _mfForceWhiteDtButtons();
            if (++_mfTries > 20) clearInterval(_mfInterval); // stop after ~5s
        }, 250);
        // Re-apply on every DataTables draw (covers re-rendered toolbars)
        $(document).on('draw.dt init.dt buttons-init.dt', function () {
            setTimeout(_mfForceWhiteDtButtons, 0);
            setTimeout(_mfForceWhiteDtButtons, 100);
        });

        /* -------------------------------------------------------------------
         * Floating horizontal scrollbar for wide DataTables.
         * Pairs with the sticky-pinned-columns CSS in modern-filters.blade.php
         * to fix the "scrollbar lives below the fold" UX. Iterates every
         * .dataTables_wrapper on the page so multi-table pages are covered.
         * Per wrapper:
         *   - Creates one position:fixed proxy scrollbar at viewport bottom
         *   - Two-way syncs proxy.scrollLeft ↔ scrollBody.scrollLeft
         *   - Auto-hides when the table doesn't overflow, when the native
         *     scrollbar is already visible on-screen, or when the wrapper
         *     is hidden (e.g. inactive tab)
         *   - Updates the --mf-sticky-col1-width CSS variable so the second
         *     sticky column lines up against the actual rendered width of
         *     the first column (DataTables computes column widths at runtime)
         * -----------------------------------------------------------------*/
        function _mfHScrollInitOne(wrapperEl) {
            // Dedup via data attribute — survives even if WeakSet is unsupported.
            if (wrapperEl.getAttribute('data-mf-hscroll') === '1') return;
            var scrollBody = wrapperEl.querySelector('.dataTables_scrollBody');
            if (!scrollBody) return;
            wrapperEl.setAttribute('data-mf-hscroll', '1');

            var proxy  = document.createElement('div');
            var filler = document.createElement('div');
            proxy.className  = 'mf-floating-hscroll is-hidden';
            filler.className = 'mf-floating-hscroll__filler';
            proxy.appendChild(filler);
            document.body.appendChild(proxy);

            var syncing = false;
            proxy.addEventListener('scroll', function () {
                if (syncing) { syncing = false; return; }
                syncing = true;
                scrollBody.scrollLeft = proxy.scrollLeft;
            });
            scrollBody.addEventListener('scroll', function () {
                if (syncing) { syncing = false; return; }
                syncing = true;
                proxy.scrollLeft = scrollBody.scrollLeft;
            });

            var rafId = 0;
            function update() {
                rafId = 0;
                var rect = scrollBody.getBoundingClientRect();
                var overflows  = scrollBody.scrollWidth > scrollBody.clientWidth + 1;
                var bottomSeen = rect.bottom <= window.innerHeight;
                var hidden     = rect.width === 0;
                var shouldShow = overflows && !bottomSeen && !hidden;

                // Gate sticky-columns CSS on actual overflow: narrow tables that
                // already fit the viewport shouldn't have first/last columns
                // pinned (would be visually weird for 3-4 column tables).
                wrapperEl.classList.toggle('mf-has-overflow', overflows);

                if (shouldShow) {
                    proxy.classList.remove('is-hidden');
                    proxy.style.left   = rect.left + 'px';
                    proxy.style.width  = rect.width + 'px';
                    filler.style.width = scrollBody.scrollWidth + 'px';
                    proxy.scrollLeft   = scrollBody.scrollLeft;
                } else {
                    proxy.classList.add('is-hidden');
                }

                var firstCell = scrollBody.querySelector('tbody tr td:nth-child(1)');
                if (firstCell) {
                    wrapperEl.style.setProperty(
                        '--mf-sticky-col1-width',
                        firstCell.offsetWidth + 'px'
                    );
                }
            }
            function schedule() {
                if (!rafId) rafId = window.requestAnimationFrame(update);
            }

            window.addEventListener('scroll', schedule, { passive: true });
            window.addEventListener('resize', schedule, { passive: true });
            $(document).on('draw.dt column-sizing.dt', schedule);

            if (window.ResizeObserver) {
                new ResizeObserver(schedule).observe(scrollBody);
            }
            schedule();
        }
        function _mfHScrollInitAll() {
            document.querySelectorAll('.dataTables_wrapper').forEach(_mfHScrollInitOne);
        }
        _mfHScrollInitAll();
        $(document).on('init.dt draw.dt', _mfHScrollInitAll);
        var _mfHsTries = 0;
        var _mfHsRetry = setInterval(function () {
            _mfHScrollInitAll();
            if (++_mfHsTries > 20) clearInterval(_mfHsRetry); // stop after ~5s
        }, 250);
    });
</script>
{{-- 
<script>
    window.chatbaseConfig = {
        chatbotId: "xDF49r8Xag2USDU1n8so6",
    }
</script>
<script src="https://www.chatbase.co/embed.min.js" id="xDF49r8Xag2USDU1n8so6" defer></script> --}}
@yield('scripts')

@include('partials.modern-filters')
@include('layouts.customizer')

<!-- JAVASCRIPT -->
<script>
    // Some vendor theme scripts expect a global `removeItem` helper.
    // Define it safely to avoid breaking page initialization.
    window.removeItem = window.removeItem || function (key) {
        try {
            window.localStorage && window.localStorage.removeItem(key);
        } catch (e) {
            // ignore
        }
    };
</script>
@include('layouts.vendor-scripts')
    <script>
        const AUTH_CLIENT_ID = {{ auth()->check() ? (auth()->user()->client_id ?? 'null') : 'null' }};
    </script>
    <script>
        async function checkEmergency() {
            if (AUTH_CLIENT_ID !== null) return;

            try {
                const res = await fetch('/check-emergency');
                const data = await res.json();

                if (data.active) {
                    showEmergencyAlert(data.message);
                }
            } catch (err) {
                console.error('Error checking emergency:', err);
            }
        }

        function showEmergencyAlert(message) {
            // لا تكرر التنبيه إذا ظاهر مسبقاً
            if (document.getElementById('emergencyAlert')) return;

            const alertBox = document.createElement('div');
            alertBox.id = 'emergencyAlert';
            alertBox.style = `
                position: fixed;
                top: 20px;
                right: 20px;
                background: #dc3545;
                color: #fff;
                padding: 20px 25px;
                border-radius: 10px;
                z-index: 999999;
                font-size: 16px;
                font-weight: bold;
                box-shadow: 0 4px 12px rgba(0,0,0,0.3);
                width: 320px;
            `;

            alertBox.innerHTML = `
                <div style="margin-bottom:10px;">${message}</div>
                <button onclick="clearEmergency()" style="
                    background:#fff;
                    color:#dc3545;
                    border:none;
                    border-radius:5px;
                    padding:6px 12px;
                    cursor:pointer;
                    font-weight:bold;
                ">إغلاق التنبيه</button>
            `;

            document.body.appendChild(alertBox);
        }

        async function clearEmergency() {
            if (AUTH_CLIENT_ID !== null) return;
            
            try {
                await fetch('/clear-emergency', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                    },
                });

                const box = document.getElementById('emergencyAlert');
                if (box) box.remove();
            } catch (err) {
                console.error('Error clearing emergency:', err);
            }
        }
        if (AUTH_CLIENT_ID === null) {
            setInterval(checkEmergency, 60000);
            checkEmergency();
        }
    </script>


</html>
<style>

</style>
