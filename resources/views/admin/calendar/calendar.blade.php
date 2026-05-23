@extends('layouts.master')
@section('content')
<h3 class="page-title">{{ trans('global.systemCalendar') }}</h3>
<div class="card">
    <div class="card-header">
        {{ trans('global.systemCalendar') }}
    </div>

    <div class="card-body">
        <link rel='stylesheet' href='https://cdnjs.cloudflare.com/ajax/libs/fullcalendar/3.1.0/fullcalendar.min.css' />

        <style>
            /* ===== Modernized FullCalendar header buttons ===== */
            #calendar .fc-toolbar.fc-header-toolbar { margin-bottom: 1.25rem; }
            #calendar .fc-toolbar h2 {
                font-size: 1.25rem;
                font-weight: 600;
                color: #0f172a;
                letter-spacing: 0.01em;
            }

            #calendar .fc-button,
            #calendar .fc-button-group > .fc-button {
                background: linear-gradient(135deg, #0ea5a4 0%, #0d9488 100%) !important;
                background-image: linear-gradient(135deg, #0ea5a4 0%, #0d9488 100%) !important;
                border: 0 !important;
                color: #ffffff !important;
                text-shadow: none !important;
                height: 52px;
                min-width: 52px;
                padding: 0 26px !important;
                border-radius: 14px !important;
                font-weight: 600;
                font-size: 1.05rem;
                letter-spacing: 0.02em;
                box-shadow: 0 6px 16px rgba(13, 148, 136, 0.30);
                transition: transform .15s ease, box-shadow .2s ease, filter .15s ease, background .2s ease;
                display: inline-flex;
                align-items: center;
                justify-content: center;
                gap: 6px;
                text-transform: capitalize;
                cursor: pointer;
                position: relative;
                overflow: hidden;
            }
            #calendar .fc-button:hover {
                transform: translateY(-2px);
                box-shadow: 0 8px 20px rgba(13, 148, 136, 0.42) !important;
                filter: brightness(1.06);
            }
            #calendar .fc-button:active,
            #calendar .fc-button.fc-state-active,
            #calendar .fc-button.fc-state-down {
                transform: translateY(0);
                box-shadow: 0 2px 6px rgba(13, 148, 136, 0.3) !important;
                filter: brightness(0.95);
            }
            #calendar .fc-button:focus { outline: none; box-shadow: 0 0 0 4px rgba(13, 148, 136, 0.18) !important; }

            /* Disabled (e.g. "Today" when already on current month) */
            #calendar .fc-button.fc-state-disabled,
            #calendar .fc-button[disabled] {
                background: #e2e8f0 !important;
                background-image: none !important;
                color: #94a3b8 !important;
                box-shadow: none !important;
                cursor: not-allowed;
                transform: none;
                filter: none;
            }
            #calendar .fc-button.fc-state-disabled:hover,
            #calendar .fc-button[disabled]:hover {
                transform: none;
                box-shadow: none !important;
                filter: none;
            }

            /* Prev/Next icon buttons — square-ish & a touch larger */
            #calendar .fc-prev-button,
            #calendar .fc-next-button {
                width: 52px;
                padding: 0 !important;
            }
            #calendar .fc-prev-button .fc-icon,
            #calendar .fc-next-button .fc-icon {
                font-size: 1.45em;
                line-height: 1;
                color: #ffffff;
            }
            #calendar .fc-prev-button:hover .fc-icon { animation: fcArrowL 0.5s ease; }
            #calendar .fc-next-button:hover .fc-icon { animation: fcArrowR 0.5s ease; }
            @keyframes fcArrowL {
                0%   { transform: translateX(0); }
                50%  { transform: translateX(-3px); }
                100% { transform: translateX(0); }
            }
            @keyframes fcArrowR {
                0%   { transform: translateX(0); }
                50%  { transform: translateX(3px); }
                100% { transform: translateX(0); }
            }

            /* Spacing between the button group on the right and the Today button */
            #calendar .fc-button-group { gap: 10px; display: inline-flex; }
            #calendar .fc-button-group > .fc-button { border-radius: 14px !important; margin: 0 !important; }
            #calendar .fc-right > .fc-button + .fc-button,
            #calendar .fc-right > .fc-button-group + .fc-button,
            #calendar .fc-right > .fc-button + .fc-button-group { margin-left: 12px !important; }

            /* Ripple-style click feedback */
            #calendar .fc-button::after {
                content: "";
                position: absolute;
                inset: 0;
                background: radial-gradient(circle at center, rgba(255,255,255,0.35) 0%, rgba(255,255,255,0) 60%);
                opacity: 0;
                transition: opacity .25s ease;
                pointer-events: none;
            }
            #calendar .fc-button:active::after { opacity: 1; transition: none; }

            /* ====================================================== */
            /* ===== Modernized FullCalendar grid + day cells   ===== */
            /* ====================================================== */
            #calendar {
                font-family: inherit;
            }
            #calendar .fc-view-container {
                border-radius: 16px;
                overflow: hidden;
                background: #ffffff;
                box-shadow: 0 4px 24px rgba(15, 23, 42, 0.06), 0 1px 2px rgba(15, 23, 42, 0.04);
            }
            #calendar .fc-view,
            #calendar .fc-view > table,
            #calendar .fc-row,
            #calendar .fc-day-grid-container {
                border: 0 !important;
            }
            #calendar table { border-collapse: separate !important; border-spacing: 0; }

            /* Day-of-week header row */
            #calendar .fc-head {
                background: #f8fafc;
            }
            #calendar .fc-day-header {
                padding: 14px 8px !important;
                font-size: 0.78rem !important;
                font-weight: 600 !important;
                letter-spacing: 0.12em;
                color: #64748b !important;
                text-transform: uppercase;
                border: 0 !important;
                border-bottom: 1px solid rgba(15, 23, 42, 0.08) !important;
                background: transparent !important;
            }
            /* Soft accent on weekend headers */
            #calendar .fc-day-header.fc-sun,
            #calendar .fc-day-header.fc-sat { color: #0d9488 !important; }

            /* Day cells */
            #calendar .fc-day-grid .fc-row {
                border: 0 !important;
                min-height: 120px;
            }
            #calendar td.fc-day,
            #calendar td.fc-day-top {
                border: 0 !important;
                border-right: 1px solid rgba(15, 23, 42, 0.06) !important;
                border-bottom: 1px solid rgba(15, 23, 42, 0.06) !important;
                transition: background-color .15s ease;
            }
            #calendar tr td:last-child { border-right: 0 !important; }
            #calendar .fc-row:last-child td { border-bottom: 0 !important; }

            #calendar td.fc-day:hover { background-color: #f8fafc; }

            /* Day numbers */
            #calendar .fc-day-number {
                padding: 8px 12px !important;
                font-size: 0.85rem;
                font-weight: 600;
                color: #334155;
                float: right;
                opacity: 0.9;
            }
            /* Days from previous/next month */
            #calendar .fc-other-month {
                background: #fafbfc !important;
            }
            #calendar .fc-other-month .fc-day-number {
                color: #cbd5e1;
                font-weight: 500;
            }

            /* Today highlight — soft teal tint instead of pale yellow */
            #calendar .fc-day.fc-today,
            #calendar td.fc-today {
                background: linear-gradient(180deg, rgba(13, 148, 136, 0.06) 0%, rgba(13, 148, 136, 0.02) 100%) !important;
                position: relative;
            }
            #calendar .fc-day-top.fc-today .fc-day-number {
                background: linear-gradient(135deg, #0ea5a4 0%, #0d9488 100%);
                color: #ffffff;
                border-radius: 999px;
                width: 28px;
                height: 28px;
                line-height: 28px;
                text-align: center;
                padding: 0 !important;
                margin: 6px 8px;
                box-shadow: 0 4px 10px rgba(13, 148, 136, 0.30);
            }

            /* ====================================================== */
            /* ===== Event "pills" — rectangular, modern, lively ===== */
            /* ====================================================== */
            #calendar .fc-event,
            #calendar .fc-event-container > .fc-event {
                background: linear-gradient(135deg, #0ea5a4 0%, #0d9488 100%) !important;
                border: 0 !important;
                border-left: 3px solid #115e59 !important;
                color: #ffffff !important;
                border-radius: 8px !important;
                padding: 5px 10px !important;
                margin: 3px 6px !important;
                font-size: 0.78rem !important;
                font-weight: 500;
                letter-spacing: 0.01em;
                box-shadow: 0 2px 6px rgba(13, 148, 136, 0.22);
                cursor: pointer;
                transition: transform .15s ease, box-shadow .2s ease, filter .15s ease;
                position: relative;
                overflow: hidden;
            }
            #calendar .fc-event:hover {
                transform: translateY(-1px) scale(1.015);
                box-shadow: 0 6px 14px rgba(13, 148, 136, 0.35);
                filter: brightness(1.05);
                z-index: 5;
            }
            #calendar .fc-event:active { transform: translateY(0) scale(1); }

            #calendar .fc-event .fc-time {
                font-weight: 700;
                margin-right: 4px;
                opacity: 0.95;
            }
            #calendar .fc-event .fc-title {
                font-weight: 500;
                opacity: 0.95;
            }
            #calendar .fc-event .fc-content {
                display: flex;
                align-items: center;
                gap: 4px;
            }

            /* Subtle pulse on the latest events row — purely cosmetic */
            #calendar .fc-event::after {
                content: "";
                position: absolute;
                top: 0; left: -120%;
                width: 60%;
                height: 100%;
                background: linear-gradient(120deg, transparent 0%, rgba(255,255,255,0.18) 50%, transparent 100%);
                transform: skewX(-20deg);
                transition: left .6s ease;
            }
            #calendar .fc-event:hover::after { left: 130%; }

            /* "More events" link inside packed day cells */
            #calendar .fc-more {
                color: #0d9488 !important;
                font-weight: 600;
                font-size: 0.75rem;
                padding: 2px 8px;
                border-radius: 6px;
                background: rgba(13, 148, 136, 0.08);
                margin: 2px 6px;
                display: inline-block;
                transition: background .15s ease;
            }
            #calendar .fc-more:hover { background: rgba(13, 148, 136, 0.18); text-decoration: none; }

            /* Popover for "more" events */
            #calendar .fc-popover {
                border-radius: 12px;
                border: 0;
                box-shadow: 0 12px 32px rgba(15, 23, 42, 0.14);
                overflow: hidden;
                max-width: 280px;
            }
            #calendar .fc-popover .fc-header {
                background: linear-gradient(135deg, #0ea5a4 0%, #0d9488 100%);
                color: #fff;
                padding: 8px 12px;
                position: sticky;
                top: 0;
                z-index: 2;
            }
            #calendar .fc-popover .fc-header .fc-close {
                color: #fff;
                opacity: 0.9;
            }
            /* Make popover body scrollable when many events overflow the viewport */
            #calendar .fc-popover .fc-body {
                max-height: 60vh;
                overflow-y: auto;
                overflow-x: hidden;
                -webkit-overflow-scrolling: touch;
            }
            #calendar .fc-popover .fc-body::-webkit-scrollbar { width: 8px; }
            #calendar .fc-popover .fc-body::-webkit-scrollbar-track {
                background: #f1f5f9;
                border-radius: 4px;
            }
            #calendar .fc-popover .fc-body::-webkit-scrollbar-thumb {
                background: linear-gradient(135deg, #0ea5a4 0%, #0d9488 100%);
                border-radius: 4px;
            }
            #calendar .fc-popover .fc-body::-webkit-scrollbar-thumb:hover {
                background: #0d9488;
            }
        </style>

        <div id='calendar'></div>
    </div>
</div>



@endsection

@section('scripts')
@parent
<script src='https://cdnjs.cloudflare.com/ajax/libs/moment.js/2.17.1/moment.min.js'></script>
<script src='https://cdnjs.cloudflare.com/ajax/libs/fullcalendar/3.1.0/fullcalendar.min.js'></script>
<script>
    $(document).ready(function () {
            // page is now ready, initialize the calendar...
            events={!! json_encode($events) !!};
            $('#calendar').fullCalendar({
                // put your options and callbacks here
                events: events,
                eventLimit: 4,
                eventLimitText: 'more'

            })
        });
</script>
@stop