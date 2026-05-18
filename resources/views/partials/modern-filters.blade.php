{{--
    Modern Filter Dropdown — built from scratch, inspired by Linear / Stripe / shadcn-ui / Tailwind UI
    Design principles applied:
    - White cards with soft, multi-layer shadows (Tailwind UI / Vercel)
    - 10-12px border-radius — friendly without being too pill-like (Linear / Stripe)
    - 1.5px borders + teal focus glow (shadcn-ui)
    - 150ms transitions everywhere (Material spec)
    - Color dot indicators for status (Stripe Dashboard / Linear)
    - Floating panel rendered to document.body → never clipped
    - Smooth slide-down animation on open (shadcn-ui)
    - Active state with translucent brand color, not solid (subtle, modern)
--}}
<style>
    /* ===== Filter card shell ===== */
    .modern-filter-card {
        border: 0;
        border-radius: 14px;
        box-shadow: 0 4px 24px rgba(15, 23, 42, 0.06), 0 1px 2px rgba(15, 23, 42, 0.04);
        background: #fff;
        overflow: visible;
    }
    .modern-filter-card > .card-header {
        background: transparent;
        border-bottom: 1px solid rgba(15, 23, 42, 0.06);
        padding: 1rem 1.25rem;
    }
    .modern-filter-card > .card-header h4,
    .modern-filter-card > .card-header .card-title {
        font-size: 0.95rem; font-weight: 600; letter-spacing: 0.02em;
        color: #0f172a; margin: 0;
    }
    .modern-filter-card > .card-header h4::before {
        content: ""; display: inline-block; width: 4px; height: 16px;
        background: linear-gradient(180deg, #0ea5a4 0%, #0d9488 100%);
        border-radius: 3px; vertical-align: -3px; margin-right: 8px;
    }
    .modern-filter-card .card-body { padding: 1.25rem 1.25rem 1rem; }
    .modern-filter-card label {
        font-weight: 500; font-size: 0.85rem; color: #475569;
        margin-bottom: 0.4rem; letter-spacing: 0.01em;
    }
    .modern-filter-card label.required::after { content: " *"; color: #ef4444; }

    /* ===== Select2 — trigger (closed state) when inside .modern-filter-card ===== */
    .modern-filter-card .select2-container--default .select2-selection--single {
        height: 44px !important;
        border: 1.5px solid #e2e8f0 !important;
        border-radius: 10px !important;
        background-color: #f8fafc !important;
        padding: 0 14px;
        display: flex !important;
        align-items: center;
        transition: border-color .15s ease, box-shadow .15s ease, background-color .15s ease;
    }
    .modern-filter-card .select2-container--default .select2-selection--single:hover {
        border-color: #cbd5e1 !important;
        background-color: #fff !important;
    }
    .modern-filter-card .select2-container--default.select2-container--open .select2-selection--single,
    .modern-filter-card .select2-container--default.select2-container--focus .select2-selection--single {
        border-color: #0d9488 !important;
        background-color: #fff !important;
        box-shadow: 0 0 0 4px rgba(13, 148, 136, 0.12);
    }
    .modern-filter-card .select2-container--default .select2-selection--single .select2-selection__rendered {
        line-height: 44px !important;
        padding: 0 !important;
        color: #0f172a;
        font-size: 0.95rem;
        font-weight: 500;
    }
    .modern-filter-card .select2-container--default .select2-selection--single .select2-selection__placeholder {
        color: #94a3b8;
        font-weight: 400;
    }
    /* Treat empty-value selections (where the option is "Select Driver", "Select X" etc.)
       as placeholder — gray italic-style text instead of black bold. */
    .modern-filter-card .select2-container--default.mf-select2-empty .select2-selection--single .select2-selection__rendered {
        color: #94a3b8 !important;
        font-weight: 400 !important;
    }

    /* ===== Select2 — multi-select (used in create/edit forms) ===== */
    .modern-filter-card .select2-container--default .select2-selection--multiple {
        min-height: 44px !important;
        border: 1.5px solid #e2e8f0 !important;
        border-radius: 10px !important;
        background-color: #f8fafc !important;
        padding: 4px 8px !important;
        transition: border-color .15s ease, box-shadow .15s ease, background-color .15s ease;
        cursor: text;
    }
    .modern-filter-card .select2-container--default .select2-selection--multiple:hover {
        border-color: #cbd5e1 !important;
        background-color: #fff !important;
    }
    .modern-filter-card .select2-container--default.select2-container--focus .select2-selection--multiple,
    .modern-filter-card .select2-container--default.select2-container--open .select2-selection--multiple {
        border-color: #0d9488 !important;
        background-color: #fff !important;
        box-shadow: 0 0 0 4px rgba(13, 148, 136, 0.12);
    }
    .modern-filter-card .select2-container--default .select2-selection--multiple .select2-selection__rendered {
        padding: 0 !important;
        margin: 0 !important;
        display: flex;
        flex-wrap: wrap;
        align-items: center;
        gap: 4px;
    }
    .modern-filter-card .select2-container--default .select2-selection--multiple .select2-selection__choice {
        background: rgba(13, 148, 136, 0.10) !important;
        border: 1px solid rgba(13, 148, 136, 0.20) !important;
        border-radius: 6px !important;
        color: #0d9488 !important;
        padding: 2px 10px 2px 8px !important;
        margin: 2px 4px 2px 0 !important;
        font-size: 0.85rem;
        font-weight: 500;
        line-height: 1.4;
    }
    .modern-filter-card .select2-container--default .select2-selection--multiple .select2-selection__choice__remove {
        color: #0d9488 !important;
        margin-right: 8px !important;
        margin-left: 0 !important;
        font-size: 1.15em;
        font-weight: bold;
        transition: color .12s ease;
        border: 0 !important;
        background: none !important;
        padding: 0 !important;
        float: left;
    }
    .modern-filter-card .select2-container--default .select2-selection--multiple .select2-selection__choice__remove:hover {
        color: #ef4444 !important;
        background: none !important;
    }
    [dir="rtl"] .modern-filter-card .select2-container--default .select2-selection--multiple .select2-selection__choice__remove {
        margin-right: 0 !important;
        margin-left: 8px !important;
        float: right;
    }
    .modern-filter-card .select2-container--default .select2-search--inline .select2-search__field {
        margin-top: 4px !important;
        color: #0f172a;
        font-size: 0.9rem;
        font-family: inherit;
        min-height: 28px;
    }
    [dir="rtl"] .modern-filter-card .select2-container--default .select2-selection--multiple .select2-selection__choice__remove {
        margin-right: 0 !important;
        margin-left: 6px !important;
    }
    .modern-filter-card .select2-container--default .select2-selection--single .select2-selection__arrow {
        height: 44px !important;
        right: 10px !important;
        top: 0 !important;
        width: 20px;
    }
    .modern-filter-card .select2-container--default .select2-selection--single .select2-selection__arrow b {
        border-color: #64748b transparent transparent transparent !important;
        border-width: 6px 5px 0 5px !important;
        margin-left: -5px;
        margin-top: -3px;
    }
    .modern-filter-card .select2-container--default.select2-container--open .select2-selection--single .select2-selection__arrow b {
        border-color: transparent transparent #64748b transparent !important;
        border-width: 0 5px 6px 5px !important;
    }
    /* Clear (×) button — absolutely positioned to the right of the trigger,
       just before the arrow. Default Select2 uses float:right which doesn't work
       because our trigger is display:flex. */
    .modern-filter-card .select2-container--default .select2-selection--single {
        position: relative !important;
    }
    .modern-filter-card .select2-container--default .select2-selection--single .select2-selection__clear {
        position: absolute !important;
        right: 36px !important;            /* leave room for the chevron arrow */
        top: 50% !important;
        transform: translateY(-50%);
        margin: 0 !important;
        padding: 2px 6px !important;
        color: #94a3b8 !important;
        font-weight: bold;
        font-size: 1.1rem;
        line-height: 1;
        cursor: pointer;
        z-index: 2;
        border-radius: 6px;
        background-color: transparent;
        transition: color .12s ease, background-color .12s ease;
    }
    .modern-filter-card .select2-container--default .select2-selection--single .select2-selection__clear:hover {
        color: #ef4444 !important;
        background-color: rgba(239, 68, 68, 0.08);
    }
    /* When a value is selected, reserve space inside the rendered text for the clear button */
    .modern-filter-card .select2-container--default .select2-selection--single .select2-selection__rendered {
        padding-right: 30px !important;
    }
    [dir="rtl"] .modern-filter-card .select2-container--default .select2-selection--single .select2-selection__clear {
        right: auto !important;
        left: 36px !important;
    }
    [dir="rtl"] .modern-filter-card .select2-container--default .select2-selection--single .select2-selection__rendered {
        padding-right: 0 !important;
        padding-left: 30px !important;
    }

    /* ===== Select2 — dropdown panel (lives on body, tagged via JS) ===== */
    .select2-container--modern-filter.select2-container--open .select2-dropdown {
        border: 1px solid rgba(15, 23, 42, 0.10) !important;
        border-radius: 12px !important;
        box-shadow: 0 20px 48px rgba(15, 23, 42, 0.20), 0 6px 12px rgba(15, 23, 42, 0.08) !important;
        background: #fff !important;
        overflow: hidden;
        margin-top: 6px;
        padding: 6px;
    }
    .select2-container--modern-filter.select2-container--open .select2-dropdown--above {
        margin-top: -6px;
        margin-bottom: 6px;
    }
    .select2-container--modern-filter .select2-search--dropdown {
        padding: 4px 4px 8px !important;
    }
    .select2-container--modern-filter .select2-search--dropdown .select2-search__field {
        height: 38px !important;
        padding: 0 12px !important;
        border: 1.5px solid #e2e8f0 !important;
        border-radius: 8px !important;
        font-size: 0.9rem !important;
        background: #f8fafc !important;
        outline: none !important;
        transition: border-color .12s ease, box-shadow .12s ease, background-color .12s ease;
        color: #0f172a;
    }
    .select2-container--modern-filter .select2-search--dropdown .select2-search__field:focus {
        border-color: #0d9488 !important;
        background: #fff !important;
        box-shadow: 0 0 0 3px rgba(13, 148, 136, 0.12);
    }
    /* Results list — capped height + scrollable so it never reaches the end of the page */
    .select2-container--modern-filter .select2-results__options {
        max-height: 280px !important;
        overflow-y: auto;
        padding: 2px;
    }
    .select2-container--modern-filter .select2-results__options::-webkit-scrollbar { width: 6px; }
    .select2-container--modern-filter .select2-results__options::-webkit-scrollbar-thumb {
        background: #cbd5e1; border-radius: 3px;
    }
    .select2-container--modern-filter .select2-results__options::-webkit-scrollbar-thumb:hover { background: #94a3b8; }
    .select2-container--modern-filter .select2-results__option {
        padding: 10px 12px !important;
        margin: 1px 0;
        border-radius: 8px !important;
        font-size: 0.92rem;
        font-weight: 500;
        color: #0f172a !important;
        background: transparent !important;
        cursor: pointer;
        line-height: 1.4;
        transition: background-color .12s ease, color .12s ease;
    }
    .select2-container--modern-filter .select2-results__option--highlighted[aria-selected],
    .select2-container--modern-filter .select2-results__option--highlighted {
        background: #f1f5f9 !important;
        color: #0d9488 !important;
    }
    .select2-container--modern-filter .select2-results__option[aria-selected="true"],
    .select2-container--modern-filter .select2-results__option--selected {
        background: rgba(13, 148, 136, 0.10) !important;
        color: #0d9488 !important;
        font-weight: 600;
    }
    .select2-container--modern-filter .select2-results__option--disabled,
    .select2-container--modern-filter .select2-results__message {
        color: #94a3b8 !important;
        background: transparent !important;
        font-style: italic;
        padding: 12px;
        text-align: center;
    }

    /* ===== Text/date inputs ===== */
    .modern-filter-card .form-control:not(.select2-hidden-accessible):not(textarea) {
        height: 44px;
        border: 1.5px solid #e2e8f0;
        border-radius: 10px;
        background: #f8fafc;
        padding: 0 14px;
        font-size: 0.95rem;
        color: #0f172a;
        transition: border-color .15s ease, box-shadow .15s ease, background .15s ease;
        width: 100%;
    }
    .modern-filter-card .form-control:not(.select2-hidden-accessible):not(textarea):hover { border-color: #cbd5e1; background-color: #fff; }
    .modern-filter-card .form-control:not(.select2-hidden-accessible):not(textarea):focus {
        border-color: #0d9488; background-color: #fff;
        box-shadow: 0 0 0 4px rgba(13, 148, 136, 0.12); outline: 0;
    }
    .modern-filter-card input[type="datetime-local"].form-control { padding-right: 8px; }

    /* ===== Textareas ===== */
    .modern-filter-card textarea.form-control {
        min-height: 92px;
        border: 1.5px solid #e2e8f0;
        border-radius: 10px;
        background: #f8fafc;
        padding: 12px 14px;
        font-size: 0.95rem;
        color: #0f172a;
        line-height: 1.5;
        resize: vertical;
        transition: border-color .15s ease, box-shadow .15s ease, background-color .15s ease;
        width: 100%;
        font-family: inherit;
    }
    .modern-filter-card textarea.form-control:hover { border-color: #cbd5e1; background-color: #fff; }
    .modern-filter-card textarea.form-control:focus {
        border-color: #0d9488; background-color: #fff;
        box-shadow: 0 0 0 4px rgba(13, 148, 136, 0.12); outline: 0;
    }

    /* ===== Helper text under fields ===== */
    .modern-filter-card .help-block {
        display: block;
        font-size: 0.78rem;
        color: #94a3b8;
        margin-top: 6px;
        line-height: 1.4;
    }

    /* ===== Input groups — handles both prepend (icon left) and append (suffix right) =====
       Position-based rules: first child gets rounded LEFT, last child gets rounded RIGHT.
       Borders between siblings are removed so they look like one continuous pill. */
    .modern-filter-card .input-group {
        display: flex !important;
        flex-wrap: nowrap !important;
        align-items: stretch;
        width: 100%;
        position: relative;
    }
    .modern-filter-card .input-group > * { border-radius: 0; }
    .modern-filter-card .input-group > :first-child {
        border-top-left-radius: 10px;
        border-bottom-left-radius: 10px;
    }
    .modern-filter-card .input-group > :last-child {
        border-top-right-radius: 10px;
        border-bottom-right-radius: 10px;
    }

    /* Form control inside an input-group: stretches, drops borders that meet a sibling */
    .modern-filter-card .input-group > .form-control,
    .modern-filter-card .input-group > input.form-control {
        flex: 1 1 auto !important;
        width: 1% !important;
        min-width: 0;
    }
    .modern-filter-card .input-group > .form-control:not(:first-child) {
        border-top-left-radius: 0 !important;
        border-bottom-left-radius: 0 !important;
    }
    .modern-filter-card .input-group > .form-control:not(:last-child) {
        border-top-right-radius: 0 !important;
        border-bottom-right-radius: 0 !important;
    }

    /* Icon/text addon */
    .modern-filter-card .input-group > .input-group-text,
    .modern-filter-card .input-group .input-group-text {
        display: inline-flex;
        align-items: center;
        height: 44px;
        padding: 0 14px;
        border: 1.5px solid #e2e8f0;
        background-color: #f1f5f9;
        color: #64748b;
        font-weight: 600;
        font-size: 0.9rem;
        letter-spacing: 0.02em;
        margin: 0;
        white-space: nowrap;
    }
    .modern-filter-card .input-group > .input-group-text:not(:first-child) { border-left: 0; }
    .modern-filter-card .input-group > .input-group-text:not(:last-child) { border-right: 0; }
    .modern-filter-card .input-group > .input-group-text i { font-size: 1rem; line-height: 1; }

    /* Bootstrap 4 wrapper variant (.input-group-prepend / .input-group-append) */
    .modern-filter-card .input-group-prepend,
    .modern-filter-card .input-group-append { display: flex; margin: 0; }
    .modern-filter-card .input-group-prepend .input-group-text,
    .modern-filter-card .input-group-append .input-group-text {
        height: 44px;
        padding: 0 14px;
        border: 1.5px solid #e2e8f0;
        background-color: #f1f5f9;
        color: #64748b;
        font-weight: 600;
        font-size: 0.9rem;
        display: inline-flex;
        align-items: center;
        white-space: nowrap;
    }
    .modern-filter-card .input-group-prepend .input-group-text {
        border-top-left-radius: 10px;
        border-bottom-left-radius: 10px;
        border-right: 0;
    }
    .modern-filter-card .input-group-append .input-group-text {
        border-top-right-radius: 10px;
        border-bottom-right-radius: 10px;
        border-left: 0;
    }
    .modern-filter-card .input-group-prepend + .form-control {
        border-top-left-radius: 0 !important;
        border-bottom-left-radius: 0 !important;
    }
    .modern-filter-card .form-control:has(+ .input-group-append) {
        border-top-right-radius: 0 !important;
        border-bottom-right-radius: 0 !important;
    }

    /* Focus-within: highlight the whole group */
    .modern-filter-card .input-group:focus-within > .input-group-text,
    .modern-filter-card .input-group:focus-within .input-group-prepend .input-group-text,
    .modern-filter-card .input-group:focus-within .input-group-append .input-group-text {
        border-color: #0d9488;
        background-color: rgba(13, 148, 136, 0.06);
        color: #0d9488;
    }
    .modern-filter-card .input-group:focus-within > .form-control {
        border-color: #0d9488 !important;
    }

    /* ===== Date picker — flatpickr-enhanced inputs =====
       NOTE: !important on bg properties is intentional — prevents any other rule's
       `background:` shorthand (which would reset bg-repeat to "repeat") from tiling
       the calendar icon across the field. */
    .modern-filter-card input.mf-datepicker {
        background-image: url("data:image/svg+xml;utf8,<svg xmlns='http://www.w3.org/2000/svg' width='14' height='14' viewBox='0 0 16 16' fill='none'><rect x='2' y='3' width='12' height='11' rx='1.5' stroke='%2364748b' stroke-width='1.5'/><path d='M2 6H14' stroke='%2364748b' stroke-width='1.5'/><path d='M5 1V3M11 1V3' stroke='%2364748b' stroke-width='1.5' stroke-linecap='round'/></svg>") !important;
        background-repeat: no-repeat !important;
        background-position: right 14px center !important;
        background-size: 14px 14px !important;
        padding-right: 40px !important;
        cursor: pointer;
    }
    [dir="rtl"] .modern-filter-card input.mf-datepicker {
        background-position: left 14px center !important;
        padding-right: 14px !important;
        padding-left: 40px !important;
    }
    .modern-filter-card input.mf-datepicker.is-active,
    .modern-filter-card input.mf-datepicker:focus {
        background-image: url("data:image/svg+xml;utf8,<svg xmlns='http://www.w3.org/2000/svg' width='14' height='14' viewBox='0 0 16 16' fill='none'><rect x='2' y='3' width='12' height='11' rx='1.5' stroke='%230d9488' stroke-width='1.5'/><path d='M2 6H14' stroke='%230d9488' stroke-width='1.5'/><path d='M5 1V3M11 1V3' stroke='%230d9488' stroke-width='1.5' stroke-linecap='round'/></svg>") !important;
        background-repeat: no-repeat !important;
        background-position: right 14px center !important;
        background-size: 14px 14px !important;
    }
    [dir="rtl"] .modern-filter-card input.mf-datepicker.is-active,
    [dir="rtl"] .modern-filter-card input.mf-datepicker:focus {
        background-position: left 14px center !important;
    }

    /* ===== Time picker — clock icon on the input ===== */
    .modern-filter-card input.mf-timepicker {
        background-image: url("data:image/svg+xml;utf8,<svg xmlns='http://www.w3.org/2000/svg' width='14' height='14' viewBox='0 0 16 16' fill='none'><circle cx='8' cy='8' r='6.5' stroke='%2364748b' stroke-width='1.5'/><path d='M8 4.5V8L10.5 9.5' stroke='%2364748b' stroke-width='1.5' stroke-linecap='round' stroke-linejoin='round'/></svg>") !important;
        background-repeat: no-repeat !important;
        background-position: right 14px center !important;
        background-size: 14px 14px !important;
        padding-right: 40px !important;
        cursor: pointer;
    }
    .modern-filter-card input.mf-timepicker.is-active,
    .modern-filter-card input.mf-timepicker:focus {
        background-image: url("data:image/svg+xml;utf8,<svg xmlns='http://www.w3.org/2000/svg' width='14' height='14' viewBox='0 0 16 16' fill='none'><circle cx='8' cy='8' r='6.5' stroke='%230d9488' stroke-width='1.5'/><path d='M8 4.5V8L10.5 9.5' stroke='%230d9488' stroke-width='1.5' stroke-linecap='round' stroke-linejoin='round'/></svg>") !important;
    }
    [dir="rtl"] .modern-filter-card input.mf-timepicker {
        background-position: left 14px center !important;
        padding-right: 14px !important;
        padding-left: 40px !important;
    }

    /* ===== Flatpickr time-only panel (no calendar above) ===== */
    .flatpickr-calendar.mf-flatpickr.mf-flatpickr--time-only {
        padding: 6px 8px 12px !important;
        min-width: 280px;
    }
    .flatpickr-calendar.mf-flatpickr.mf-flatpickr--time-only .flatpickr-time {
        border-top: 0 !important;
        margin-top: 0;
        padding: 30px 8px 8px !important;
    }
    .flatpickr-calendar.mf-flatpickr.mf-flatpickr--time-only .flatpickr-months,
    .flatpickr-calendar.mf-flatpickr.mf-flatpickr--time-only .flatpickr-weekdays,
    .flatpickr-calendar.mf-flatpickr.mf-flatpickr--time-only .flatpickr-days,
    .flatpickr-calendar.mf-flatpickr.mf-flatpickr--time-only .flatpickr-innerContainer {
        display: none !important;
    }

    /* ===== Flatpickr calendar panel — themed to match the dropdown ===== */
    .flatpickr-calendar.mf-flatpickr {
        background: #fff;
        border: 1px solid rgba(15, 23, 42, 0.08);
        border-radius: 12px;
        box-shadow:
            0 20px 48px rgba(15, 23, 42, 0.18),
            0 8px 16px rgba(15, 23, 42, 0.06);
        padding: 10px 10px 14px !important;
        font-family: inherit;
        margin-top: 6px;
        width: auto !important;
        min-width: 320px;
    }
    .flatpickr-calendar.mf-flatpickr.hasTime { padding-bottom: 16px !important; }
    .flatpickr-calendar.mf-flatpickr.arrowTop::before,
    .flatpickr-calendar.mf-flatpickr.arrowTop::after,
    .flatpickr-calendar.mf-flatpickr.arrowBottom::before,
    .flatpickr-calendar.mf-flatpickr.arrowBottom::after { display: none; }
    .flatpickr-calendar.mf-flatpickr .flatpickr-months {
        background-color: #fff !important;
        padding: 4px 0 8px !important;
        border-bottom: 1px solid rgba(15, 23, 42, 0.06);
        margin-bottom: 8px;
        display: flex;
        align-items: center;
        position: relative;
    }
    .flatpickr-calendar.mf-flatpickr .flatpickr-month {
        height: 40px !important;
        background: transparent !important;
        background-color: transparent !important;
        color: #0f172a !important;
        flex: 1;
        overflow: visible !important;
    }
    .flatpickr-calendar.mf-flatpickr .flatpickr-current-month {
        padding: 8px 0 !important;
        font-weight: 600 !important;
        font-size: 0.95rem !important;
        height: 40px !important;
        line-height: 24px;
        color: #0f172a !important;
    }
    .flatpickr-calendar.mf-flatpickr .flatpickr-current-month .cur-year,
    .flatpickr-calendar.mf-flatpickr .flatpickr-current-month input.cur-year {
        color: #0f172a !important;
        font-weight: 600 !important;
        font-size: 0.95rem !important;
        background: transparent !important;
        background-color: transparent !important;
        border: 0 !important;
        padding: 0 4px !important;
    }
    .flatpickr-calendar.mf-flatpickr .flatpickr-current-month .flatpickr-monthDropdown-months {
        color: #0f172a !important;
        font-weight: 600 !important;
        font-size: 0.95rem !important;
        background: transparent !important;
        background-color: transparent !important;
        border: 0 !important;
        padding: 4px !important;
        border-radius: 6px !important;
        cursor: pointer;
    }
    .flatpickr-calendar.mf-flatpickr .flatpickr-current-month .flatpickr-monthDropdown-months:hover {
        background-color: #f1f5f9 !important;
    }
    .flatpickr-calendar.mf-flatpickr .flatpickr-current-month .numInputWrapper:hover {
        background-color: #f1f5f9 !important;
        border-radius: 6px;
    }
    .flatpickr-calendar.mf-flatpickr .flatpickr-current-month .numInputWrapper span {
        border: 0 !important;
        opacity: 1 !important;
    }
    .flatpickr-calendar.mf-flatpickr .flatpickr-current-month .numInputWrapper span:hover {
        background: #e2e8f0 !important;
    }

    /* === Prev / Next month buttons — proper visible controls === */
    .flatpickr-calendar.mf-flatpickr .flatpickr-prev-month,
    .flatpickr-calendar.mf-flatpickr .flatpickr-next-month {
        position: relative !important;
        top: auto !important;
        background-color: #f8fafc !important;
        border: 1.5px solid #e2e8f0 !important;
        border-radius: 8px !important;
        width: 34px !important;
        height: 34px !important;
        padding: 0 !important;
        margin: 3px !important;
        display: flex !important;
        align-items: center !important;
        justify-content: center !important;
        cursor: pointer !important;
        transition: background-color .12s ease, border-color .12s ease, transform .12s ease;
        flex-shrink: 0;
        z-index: 2;
    }
    .flatpickr-calendar.mf-flatpickr .flatpickr-prev-month {
        order: 0;
    }
    .flatpickr-calendar.mf-flatpickr .flatpickr-next-month {
        order: 2;
    }
    .flatpickr-calendar.mf-flatpickr .flatpickr-month {
        order: 1;
    }
    .flatpickr-calendar.mf-flatpickr .flatpickr-prev-month:hover,
    .flatpickr-calendar.mf-flatpickr .flatpickr-next-month:hover {
        background-color: #0d9488 !important;
        border-color: #0d9488 !important;
        transform: translateY(-1px);
    }
    .flatpickr-calendar.mf-flatpickr .flatpickr-prev-month svg,
    .flatpickr-calendar.mf-flatpickr .flatpickr-next-month svg {
        width: 14px !important;
        height: 14px !important;
        fill: #475569 !important;
        transition: fill .12s ease;
    }
    .flatpickr-calendar.mf-flatpickr .flatpickr-prev-month:hover svg,
    .flatpickr-calendar.mf-flatpickr .flatpickr-next-month:hover svg {
        fill: #fff !important;
    }
    .flatpickr-calendar.mf-flatpickr .flatpickr-prev-month svg path,
    .flatpickr-calendar.mf-flatpickr .flatpickr-next-month svg path { fill: inherit !important; }

    .flatpickr-calendar.mf-flatpickr .flatpickr-weekdays {
        background: transparent;
        height: 32px;
    }
    .flatpickr-calendar.mf-flatpickr .flatpickr-weekday {
        color: #94a3b8;
        font-weight: 600;
        font-size: 0.75rem;
        text-transform: uppercase;
        letter-spacing: 0.05em;
        background: transparent;
    }
    .flatpickr-calendar.mf-flatpickr .dayContainer {
        padding: 4px 0;
    }
    .flatpickr-calendar.mf-flatpickr .flatpickr-day {
        max-width: 36px;
        height: 36px;
        line-height: 34px;
        margin: 2px;
        border-radius: 8px;
        color: #0f172a;
        border: 1px solid transparent;
        font-size: 0.88rem;
        font-weight: 500;
        transition: background-color .12s ease, color .12s ease, transform .12s ease;
    }
    .flatpickr-calendar.mf-flatpickr .flatpickr-day:hover {
        background: #f1f5f9;
        color: #0d9488;
        border-color: transparent;
    }
    .flatpickr-calendar.mf-flatpickr .flatpickr-day.today {
        border: 1.5px solid rgba(13, 148, 136, 0.3);
        background: transparent;
        font-weight: 700;
        color: #0d9488;
    }
    .flatpickr-calendar.mf-flatpickr .flatpickr-day.today:hover {
        background: rgba(13, 148, 136, 0.08);
    }
    .flatpickr-calendar.mf-flatpickr .flatpickr-day.selected,
    .flatpickr-calendar.mf-flatpickr .flatpickr-day.startRange,
    .flatpickr-calendar.mf-flatpickr .flatpickr-day.endRange {
        background: linear-gradient(135deg, #0ea5a4 0%, #0d9488 100%) !important;
        border-color: #0d9488 !important;
        color: #fff !important;
        font-weight: 600;
        box-shadow: 0 4px 10px rgba(13, 148, 136, 0.30);
    }
    .flatpickr-calendar.mf-flatpickr .flatpickr-day.inRange {
        background: rgba(13, 148, 136, 0.10);
        color: #0d9488;
        border-color: transparent;
        box-shadow: none;
    }
    .flatpickr-calendar.mf-flatpickr .flatpickr-day.flatpickr-disabled,
    .flatpickr-calendar.mf-flatpickr .flatpickr-day.prevMonthDay,
    .flatpickr-calendar.mf-flatpickr .flatpickr-day.nextMonthDay {
        color: #cbd5e1;
        font-weight: 400;
    }
    .flatpickr-calendar.mf-flatpickr .flatpickr-day.flatpickr-disabled:hover { background: transparent; cursor: not-allowed; }

    /* ===== Time picker — clean spinner buttons, prominent inputs ===== */
    .flatpickr-calendar.mf-flatpickr .flatpickr-time {
        border-top: 1px solid rgba(15, 23, 42, 0.06);
        padding: 30px 8px 12px !important;   /* room for top labels */
        margin-top: 8px;
        height: auto !important;
        min-height: 92px !important;
        display: flex !important;
        align-items: flex-end !important;
        justify-content: center !important;
        gap: 6px;
        overflow: visible !important;
    }
    .flatpickr-calendar.mf-flatpickr .flatpickr-time .numInputWrapper {
        background-color: #f8fafc !important;
        border: 1.5px solid #e2e8f0 !important;
        border-radius: 10px !important;
        height: 48px !important;
        width: 78px !important;
        overflow: visible !important;       /* labels need to show ABOVE */
        position: relative !important;
        transition: border-color .12s ease, background-color .12s ease;
    }

    /* "Hour" / "Minute" / "AM/PM" labels above the time controls */
    .flatpickr-calendar.mf-flatpickr .flatpickr-time .mf-time-label {
        position: absolute;
        top: -22px;
        left: 0;
        right: 0;
        text-align: center;
        font-size: 0.7rem;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: 0.08em;
        color: #94a3b8;
        pointer-events: none;
        line-height: 1;
    }
    .flatpickr-calendar.mf-flatpickr .flatpickr-time .numInputWrapper:hover {
        background-color: #fff !important;
        border-color: #0d9488 !important;
    }
    .flatpickr-calendar.mf-flatpickr .flatpickr-time input.flatpickr-hour,
    .flatpickr-calendar.mf-flatpickr .flatpickr-time input.flatpickr-minute,
    .flatpickr-calendar.mf-flatpickr .flatpickr-time input.flatpickr-second {
        background: transparent !important;
        background-color: transparent !important;
        border: 0 !important;
        border-radius: 0 !important;
        color: #0f172a !important;
        font-weight: 600 !important;
        font-size: 1.1rem !important;
        margin: 0 !important;
        padding: 0 26px 0 8px !important;
        height: 44px !important;
        line-height: 44px;
        outline: none !important;
        text-align: center !important;
        -moz-appearance: textfield;
    }
    .flatpickr-calendar.mf-flatpickr .flatpickr-time input::-webkit-outer-spin-button,
    .flatpickr-calendar.mf-flatpickr .flatpickr-time input::-webkit-inner-spin-button {
        -webkit-appearance: none;
        margin: 0;
    }

    /* === Spinner arrows — custom styled, clearly visible === */
    .flatpickr-calendar.mf-flatpickr .flatpickr-time .numInputWrapper span {
        position: absolute !important;
        right: 4px !important;
        width: 20px !important;
        height: 20px !important;
        padding: 0 !important;
        border: 0 !important;
        border-radius: 4px !important;
        background-color: transparent !important;
        opacity: 1 !important;
        cursor: pointer !important;
        display: flex !important;
        align-items: center !important;
        justify-content: center !important;
        transition: background-color .12s ease;
    }
    .flatpickr-calendar.mf-flatpickr .flatpickr-time .numInputWrapper span.arrowUp { top: 3px !important; }
    .flatpickr-calendar.mf-flatpickr .flatpickr-time .numInputWrapper span.arrowDown { top: 25px !important; }
    .flatpickr-calendar.mf-flatpickr .flatpickr-time .numInputWrapper span:hover {
        background-color: #0d9488 !important;
    }
    /* Replace the default SVG with a clean CSS triangle */
    .flatpickr-calendar.mf-flatpickr .flatpickr-time .numInputWrapper span svg { display: none !important; }
    .flatpickr-calendar.mf-flatpickr .flatpickr-time .numInputWrapper span::after {
        content: "";
        display: block;
        width: 0;
        height: 0;
        border-left: 5px solid transparent;
        border-right: 5px solid transparent;
        transition: border-color .12s ease;
    }
    .flatpickr-calendar.mf-flatpickr .flatpickr-time .numInputWrapper span.arrowUp::after {
        border-bottom: 6px solid #64748b;
    }
    .flatpickr-calendar.mf-flatpickr .flatpickr-time .numInputWrapper span.arrowDown::after {
        border-top: 6px solid #64748b;
    }
    .flatpickr-calendar.mf-flatpickr .flatpickr-time .numInputWrapper span.arrowUp:hover::after {
        border-bottom-color: #fff;
    }
    .flatpickr-calendar.mf-flatpickr .flatpickr-time .numInputWrapper span.arrowDown:hover::after {
        border-top-color: #fff;
    }
    /* Hide native after-pseudos from flatpickr's default arrows */
    .flatpickr-calendar.mf-flatpickr .flatpickr-time .numInputWrapper span:before { display: none !important; }

    /* Separator and AM/PM */
    .flatpickr-calendar.mf-flatpickr .flatpickr-time .flatpickr-time-separator {
        color: #94a3b8 !important;
        font-weight: 700 !important;
        font-size: 1.2rem !important;
        padding: 0 2px;
        height: auto !important;
        line-height: 1 !important;
        align-self: center;
    }
    .flatpickr-calendar.mf-flatpickr .flatpickr-time .flatpickr-am-pm {
        background-color: #f8fafc !important;
        border: 1.5px solid #e2e8f0 !important;
        border-radius: 10px !important;
        height: 48px !important;
        width: 64px !important;
        color: #0f172a !important;
        font-weight: 600 !important;
        font-size: 0.9rem !important;
        line-height: 44px !important;
        margin-left: 6px !important;
        cursor: pointer;
        transition: background-color .12s ease, border-color .12s ease, color .12s ease;
        outline: none !important;
        position: relative !important;       /* anchor for label */
        overflow: visible !important;        /* let label show above */
    }
    .flatpickr-calendar.mf-flatpickr .flatpickr-time .flatpickr-am-pm:hover,
    .flatpickr-calendar.mf-flatpickr .flatpickr-time .flatpickr-am-pm:focus {
        background-color: #0d9488 !important;
        border-color: #0d9488 !important;
        color: #fff !important;
    }

    /* ===== CUSTOM DROPDOWN — trigger button ===== */
    .mf-dropdown { position: relative; width: 100%; }
    .mf-dropdown__trigger {
        width: 100%;
        min-height: 44px;
        border: 1.5px solid #e2e8f0;
        border-radius: 10px;
        background: #f8fafc;
        padding: 0 14px;
        display: flex;
        align-items: center;
        justify-content: space-between;
        font-size: 0.95rem;
        color: #0f172a;
        cursor: pointer;
        transition: border-color .15s ease, box-shadow .15s ease, background .15s ease;
        text-align: left;
        font-weight: 500;
        font-family: inherit;
        line-height: 1.2;
    }
    .mf-dropdown__trigger:hover { border-color: #cbd5e1; background: #fff; }
    .mf-dropdown__trigger.is-open,
    .mf-dropdown__trigger:focus-visible {
        border-color: #0d9488; background: #fff;
        box-shadow: 0 0 0 4px rgba(13, 148, 136, 0.12); outline: 0;
    }
    .mf-dropdown__current {
        display: flex; align-items: center; flex: 1;
        overflow: hidden; text-overflow: ellipsis; white-space: nowrap;
    }
    .mf-dropdown__current--empty { color: #94a3b8; font-weight: 400; }
    .mf-dropdown__arrow {
        color: #64748b;
        transition: transform .2s ease, color .15s ease;
        flex-shrink: 0;
        margin-left: 10px;
    }
    .mf-dropdown__trigger.is-open .mf-dropdown__arrow { transform: rotate(180deg); color: #0d9488; }
    .mf-dropdown__clear {
        background: transparent;
        border: 0;
        color: #94a3b8;
        padding: 0 6px;
        margin-right: 4px;
        cursor: pointer;
        font-size: 1.1rem;
        line-height: 1;
        transition: color .12s ease;
        display: none;
    }
    .mf-dropdown__clear:hover { color: #ef4444; }
    .mf-dropdown.has-value .mf-dropdown__clear { display: inline-flex; }

    /* ===== CUSTOM DROPDOWN — floating panel (lives on document.body) ===== */
    .mf-panel {
        position: fixed;
        background: #fff;
        border: 1px solid rgba(15, 23, 42, 0.08);
        border-radius: 12px;
        box-shadow:
            0 20px 48px rgba(15, 23, 42, 0.18),
            0 8px 16px rgba(15, 23, 42, 0.06),
            0 0 0 1px rgba(15, 23, 42, 0.02);
        padding: 6px;
        max-height: 320px;
        overflow-y: auto;
        z-index: 99999;
        opacity: 0;
        transform: translateY(-8px) scale(0.98);
        pointer-events: none;
        transition: opacity .15s ease, transform .15s cubic-bezier(0.16, 1, 0.3, 1);
        visibility: hidden;
        min-width: 180px;
    }
    .mf-panel.is-open {
        opacity: 1;
        transform: translateY(0) scale(1);
        pointer-events: auto;
        visibility: visible;
    }
    .mf-panel::-webkit-scrollbar { width: 6px; }
    .mf-panel::-webkit-scrollbar-thumb { background: #cbd5e1; border-radius: 3px; }
    .mf-panel::-webkit-scrollbar-thumb:hover { background: #94a3b8; }

    /* ===== Items inside the panel ===== */
    .mf-panel__item {
        width: 100%;
        border: 0;
        background: transparent;
        padding: 10px 12px;
        margin: 1px 0;
        border-radius: 8px;
        font-size: 0.92rem;
        font-weight: 500;
        color: #0f172a;
        cursor: pointer;
        display: flex;
        align-items: center;
        text-align: left;
        line-height: 1.4;
        transition: background-color .12s ease, color .12s ease;
        font-family: inherit;
        position: relative;
    }
    .mf-panel__item:hover,
    .mf-panel__item.is-highlighted {
        background: #f1f5f9;
        color: #0d9488;
    }
    .mf-panel__item.is-active {
        background: rgba(13, 148, 136, 0.10);
        color: #0d9488;
        font-weight: 600;
    }
    .mf-panel__item.is-active::after {
        content: "✓";
        margin-left: auto;
        font-weight: 700;
        color: #0d9488;
        font-size: 0.9rem;
    }
    .mf-panel__empty {
        padding: 14px 12px;
        text-align: center;
        color: #94a3b8;
        font-size: 0.88rem;
        font-style: italic;
    }

    /* ===== Color dot indicators ===== */
    .mf-dot {
        display: inline-block;
        width: 10px;
        height: 10px;
        border-radius: 50%;
        margin-right: 10px;
        flex-shrink: 0;
        box-shadow: 0 0 0 2px rgba(255,255,255,0.7), 0 1px 2px rgba(0,0,0,0.15);
    }
    [dir="rtl"] .mf-dot { margin-right: 0; margin-left: 10px; }

    /* ===== Optional: search input inside panel ===== */
    .mf-panel__search {
        width: 100%;
        height: 36px;
        padding: 0 12px;
        border: 1px solid #e2e8f0;
        border-radius: 8px;
        font-size: 0.9rem;
        background: #f8fafc;
        color: #0f172a;
        outline: none;
        margin-bottom: 4px;
        transition: border-color .15s ease, box-shadow .15s ease;
    }
    .mf-panel__search:focus {
        border-color: #0d9488;
        background: #fff;
        box-shadow: 0 0 0 3px rgba(13, 148, 136, 0.12);
    }

    /* ===== Hidden native select (preserved for form submission) ===== */
    .mf-native {
        position: absolute !important;
        opacity: 0 !important;
        pointer-events: none !important;
        width: 1px !important;
        height: 1px !important;
        margin: 0 !important;
        padding: 0 !important;
        border: 0 !important;
        clip: rect(0 0 0 0);
        overflow: hidden;
    }

    /* ===== Filter actions — targets ONLY the buttons column (not its siblings) =====
       On pages like /admin/tasks the buttons share a row with filter fields, so we cannot
       style the whole row — that would push every label to the right. Instead we tag and
       style ONLY the specific col that contains the buttons. */
    .modern-filter-card .modern-filter-actions__col {
        display: flex !important;
        align-items: center;
        justify-content: flex-end;
        flex-wrap: wrap;
        gap: 16px;
        border-top: 1px solid rgba(15, 23, 42, 0.06);
        margin: 12px -1.25rem 0;
        padding: 16px 1.25rem 4px;
        flex-basis: 100%;
        max-width: 100%;
    }
    .modern-filter-card .modern-filter-actions__col .btn-reset,
    .modern-filter-card .modern-filter-actions__col .btn-search,
    .modern-filter-card .modern-filter-actions__col > a.btn { margin: 0 !important; }

    /* ===== Card-header CTA (e.g. "Add Tasks") — gentle pulsing glow draws the eye =====
       Scoped to .btn-card-action so it ONLY applies to dedicated CTAs, never filter
       buttons. Filter Search/Export buttons keep their .btn-search styling untouched. */
    .btn-card-action {
        background: linear-gradient(135deg, #0ea5a4 0%, #0d9488 100%);
        color: #fff !important;
        border: 0;
        height: 40px;
        padding: 0 22px;
        border-radius: 10px;
        font-weight: 600;
        font-size: 0.92rem;
        letter-spacing: 0.02em;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        gap: 8px;
        text-decoration: none;
        box-shadow: 0 4px 12px rgba(13, 148, 136, 0.28);
        transition: transform .18s ease, box-shadow .2s ease, filter .15s ease;
        animation: btn-card-action-pulse 2.6s ease-in-out infinite;
        cursor: pointer;
        position: relative;
        white-space: nowrap;
    }
    .btn-card-action i { font-size: 1.05rem; line-height: 1; }
    .btn-card-action:hover {
        transform: translateY(-2px);
        box-shadow: 0 8px 22px rgba(13, 148, 136, 0.45), 0 0 0 4px rgba(13, 148, 136, 0.12);
        filter: brightness(1.05);
        color: #fff !important;
        text-decoration: none;
        animation: none;   /* pause the pulse on hover so the lift feels intentional */
    }
    .btn-card-action:active { transform: translateY(0); }
    .btn-card-action:focus { outline: 0; box-shadow: 0 4px 12px rgba(13, 148, 136, 0.28), 0 0 0 4px rgba(13, 148, 136, 0.18); }

    @keyframes btn-card-action-pulse {
        0%, 100% { box-shadow: 0 4px 12px rgba(13, 148, 136, 0.28); }
        50%      { box-shadow: 0 4px 18px rgba(13, 148, 136, 0.55), 0 0 0 5px rgba(13, 148, 136, 0.10); }
    }

    /* Make the card header containing this button align nicely */
    .card-header.d-flex .btn-card-action { margin: 0; }

    /* Reduce-motion users: respect their preference and stop the pulse */
    @media (prefers-reduced-motion: reduce) {
        .btn-card-action { animation: none; }
    }

    /* ===== Button loading state — spinner inside the button on click ===== */
    .modern-filter-card .btn.is-loading {
        position: relative;
        color: transparent !important;
        text-shadow: none !important;
        pointer-events: none;
        cursor: wait;
    }
    .modern-filter-card .btn.is-loading > * { opacity: 0 !important; }
    .modern-filter-card .btn.is-loading::after {
        content: "";
        position: absolute;
        width: 16px;
        height: 16px;
        top: 50%;
        left: 50%;
        margin-top: -8px;
        margin-left: -8px;
        border: 2px solid rgba(255, 255, 255, 0.35);
        border-top-color: #fff;
        border-radius: 50%;
        animation: mf-btn-spin 0.7s linear infinite;
    }
    /* For light buttons (Reset / Cancel), use a darker spinner */
    .modern-filter-card .btn-reset.is-loading::after {
        border: 2px solid rgba(15, 23, 42, 0.20);
        border-top-color: #475569;
    }
    @keyframes mf-btn-spin {
        to { transform: rotate(360deg); }
    }

    /* ===== DataTables — modernize toolbar controls (Show entries / Search / export buttons) ===== */
    .dataTables_wrapper {
        padding: 0;
    }
    /* "Show N entries" dropdown */
    .dataTables_wrapper .dataTables_length {
        margin-bottom: 14px;
    }
    .dataTables_wrapper .dataTables_length label {
        font-weight: 500;
        font-size: 0.85rem;
        color: #475569;
        letter-spacing: 0.01em;
        display: inline-flex;
        align-items: center;
        gap: 8px;
        margin: 0;
    }
    /* Native select fallback (in case Choices.js fails) */
    .dataTables_wrapper .dataTables_length select:not(.choices__input) {
        height: 36px;
        min-width: 70px;
        padding: 0 30px 0 12px;
        border: 1.5px solid #e2e8f0;
        border-radius: 8px;
        background-color: #f8fafc;
        font-size: 0.88rem;
        font-weight: 500;
        color: #0f172a;
        cursor: pointer;
        appearance: none;
        -webkit-appearance: none;
        background-image: url("data:image/svg+xml;utf8,<svg xmlns='http://www.w3.org/2000/svg' width='10' height='6' viewBox='0 0 12 8'><path d='M1 1.5L6 6.5L11 1.5' stroke='%2364748b' stroke-width='2' stroke-linecap='round' stroke-linejoin='round' fill='none'/></svg>");
        background-repeat: no-repeat;
        background-position: right 10px center;
    }

    /* Choices.js–enhanced length dropdown */
    .mf-dt-length.choices {
        display: inline-block;
        min-width: 80px;
        margin: 0;
        font-size: 0.88rem;
    }
    .mf-dt-length .choices__inner {
        min-height: 36px !important;
        padding: 0 28px 0 12px !important;
        border: 1.5px solid #e2e8f0 !important;
        border-radius: 8px !important;
        background: #f8fafc !important;
        display: flex;
        align-items: center;
        font-weight: 500;
        color: #0f172a;
        transition: border-color .15s ease, background-color .15s ease, box-shadow .15s ease;
    }
    .mf-dt-length:hover .choices__inner { border-color: #cbd5e1 !important; background: #fff !important; }
    .mf-dt-length.is-focused .choices__inner,
    .mf-dt-length.is-open .choices__inner {
        border-color: #0d9488 !important;
        background: #fff !important;
        box-shadow: 0 0 0 3px rgba(13, 148, 136, 0.12) !important;
    }
    .mf-dt-length .choices__list--single { padding: 0; }
    .mf-dt-length .choices__list--single .choices__item { padding: 0; }
    .mf-dt-length[data-type*="select-one"]::after {
        border-color: #64748b transparent transparent;
        right: 12px;
    }
    .mf-dt-length.is-open[data-type*="select-one"]::after {
        border-color: transparent transparent #64748b;
    }
    .mf-dt-length .choices__list--dropdown,
    .mf-dt-length .choices__list[aria-expanded] {
        border: 1px solid rgba(15, 23, 42, 0.10) !important;
        border-radius: 10px !important;
        box-shadow: 0 12px 32px rgba(15, 23, 42, 0.15), 0 4px 8px rgba(15, 23, 42, 0.06) !important;
        background: #fff !important;
        padding: 4px !important;
        margin-top: 4px;
        z-index: 1050;
    }
    .mf-dt-length .choices__list--dropdown .choices__item,
    .mf-dt-length .choices__list[aria-expanded] .choices__item {
        padding: 8px 12px !important;
        margin: 1px 0;
        border-radius: 6px !important;
        font-size: 0.88rem;
        font-weight: 500;
        color: #0f172a !important;
        background: transparent !important;
    }
    .mf-dt-length .choices__list--dropdown .choices__item--selectable:hover,
    .mf-dt-length .choices__list--dropdown .choices__item--selectable.is-highlighted {
        background: #f1f5f9 !important;
        color: #0d9488 !important;
    }
    .mf-dt-length .choices__list--dropdown .choices__item--selectable[aria-selected="true"] {
        background: rgba(13, 148, 136, 0.10) !important;
        color: #0d9488 !important;
        font-weight: 600;
    }
    .mf-dt-length .choices__list--dropdown .choices__item--selectable::after { display: none !important; }

    /* Search box */
    .dataTables_wrapper .dataTables_filter {
        margin-bottom: 14px;
    }
    .dataTables_wrapper .dataTables_filter label {
        font-weight: 500;
        font-size: 0.85rem;
        color: #475569;
        display: inline-flex;
        align-items: center;
        gap: 10px;
        margin: 0;
    }
    .dataTables_wrapper .dataTables_filter input[type="search"] {
        height: 36px;
        min-width: 220px;
        padding: 0 14px 0 36px;
        border: 1.5px solid #e2e8f0;
        border-radius: 8px;
        background-color: #f8fafc;
        background-image: url("data:image/svg+xml;utf8,<svg xmlns='http://www.w3.org/2000/svg' width='14' height='14' viewBox='0 0 16 16' fill='none'><circle cx='7' cy='7' r='5' stroke='%2364748b' stroke-width='1.5'/><path d='M11 11L14 14' stroke='%2364748b' stroke-width='1.5' stroke-linecap='round'/></svg>");
        background-repeat: no-repeat;
        background-position: left 12px center;
        background-size: 14px 14px;
        font-size: 0.88rem;
        color: #0f172a;
        margin: 0;
        transition: border-color .15s ease, background-color .15s ease, box-shadow .15s ease;
    }
    .dataTables_wrapper .dataTables_filter input[type="search"]:hover {
        border-color: #cbd5e1;
        background-color: #fff;
    }
    .dataTables_wrapper .dataTables_filter input[type="search"]:focus {
        border-color: #0d9488;
        background-color: #fff;
        box-shadow: 0 0 0 3px rgba(13, 148, 136, 0.12);
        outline: 0;
    }

    /* Toolbar buttons (Copy, CSV, Excel, Print, Delete) — sized like filter Search/Export */
    .dataTables_wrapper .dt-buttons {
        display: inline-flex;
        gap: 10px;
        flex-wrap: wrap;
        margin-bottom: 14px;
    }
    .dataTables_wrapper .dt-button,
    .dataTables_wrapper button.dt-button,
    .dataTables_wrapper div.dt-button,
    .dataTables_wrapper a.dt-button {
        height: 40px !important;
        padding: 0 18px !important;
        border: 0 !important;
        border-radius: 10px !important;
        background: linear-gradient(135deg, #0ea5a4 0%, #0d9488 100%) !important;
        color: #ffffff !important;
        font-size: 0.88rem !important;
        font-weight: 600 !important;
        letter-spacing: 0.02em;
        text-transform: none !important;
        display: inline-flex;
        align-items: center;
        cursor: pointer;
        box-shadow: 0 4px 12px rgba(13, 148, 136, 0.22) !important;
        transition: transform .12s ease, box-shadow .15s ease, filter .15s ease;
        margin: 0 !important;
        text-shadow: none !important;
    }
    /* Force white text on EVERY descendant of a toolbar button — the DataTables Buttons
       extension nests <span><span>Label</span></span>, and the theme adds rules with high
       specificity that hit the inner span. We use the universal selector plus
       -webkit-text-fill-color (which overrides even Bootstrap's `.btn { color: ... }`
       inheritance and any anchor :visited rules). */
    .dataTables_wrapper .dt-button,
    .dataTables_wrapper .dt-button *,
    .dataTables_wrapper button.dt-button,
    .dataTables_wrapper button.dt-button *,
    .dataTables_wrapper a.dt-button,
    .dataTables_wrapper a.dt-button *,
    .dataTables_wrapper a.dt-button:visited,
    .dataTables_wrapper a.dt-button:link,
    .dataTables_wrapper .dt-button:focus,
    .dataTables_wrapper .dt-button:focus *,
    .dataTables_wrapper .dt-button:active,
    .dataTables_wrapper .dt-button:active * {
        color: #ffffff !important;
        -webkit-text-fill-color: #ffffff !important;
        text-shadow: none !important;
    }
    .dataTables_wrapper .dt-button:hover,
    .dataTables_wrapper button.dt-button:hover,
    .dataTables_wrapper div.dt-button:hover,
    .dataTables_wrapper a.dt-button:hover {
        transform: translateY(-1px);
        box-shadow: 0 6px 18px rgba(13, 148, 136, 0.32) !important;
        filter: brightness(1.05);
        color: #ffffff !important;
    }
    .dataTables_wrapper .dt-button:hover > *,
    .dataTables_wrapper .dt-button:hover span {
        color: #ffffff !important;
    }
    .dataTables_wrapper .dt-button:active {
        transform: translateY(0);
    }
    .dataTables_wrapper .dt-button:focus,
    .dataTables_wrapper button.dt-button:focus {
        outline: 0 !important;
    }

    /* Differentiate the export buttons by tone so they're distinguishable at a glance */
    .dataTables_wrapper .buttons-copy {
        background: linear-gradient(135deg, #64748b 0%, #475569 100%) !important;
        box-shadow: 0 4px 12px rgba(71, 85, 105, 0.22) !important;
    }
    .dataTables_wrapper .buttons-copy:hover {
        box-shadow: 0 6px 18px rgba(71, 85, 105, 0.32) !important;
    }
    .dataTables_wrapper .buttons-csv {
        background: linear-gradient(135deg, #6366f1 0%, #4f46e5 100%) !important;
        box-shadow: 0 4px 12px rgba(99, 102, 241, 0.22) !important;
    }
    .dataTables_wrapper .buttons-csv:hover {
        box-shadow: 0 6px 18px rgba(99, 102, 241, 0.32) !important;
    }
    .dataTables_wrapper .buttons-excel {
        background: linear-gradient(135deg, #22c55e 0%, #16a34a 100%) !important;
        box-shadow: 0 4px 12px rgba(34, 197, 94, 0.22) !important;
    }
    .dataTables_wrapper .buttons-excel:hover {
        box-shadow: 0 6px 18px rgba(34, 197, 94, 0.32) !important;
    }
    .dataTables_wrapper .buttons-print {
        background: linear-gradient(135deg, #0ea5e9 0%, #0284c7 100%) !important;
        box-shadow: 0 4px 12px rgba(14, 165, 233, 0.22) !important;
    }
    .dataTables_wrapper .buttons-print:hover {
        box-shadow: 0 6px 18px rgba(14, 165, 233, 0.32) !important;
    }

    /* Destructive (Delete) action keeps red identity */
    .dataTables_wrapper .dt-button.btn-danger,
    .dataTables_wrapper .btn-danger.dt-button,
    .dataTables_wrapper button.btn-danger {
        background: linear-gradient(135deg, #f97373 0%, #ef4444 100%) !important;
        box-shadow: 0 4px 12px rgba(239, 68, 68, 0.25) !important;
    }
    .dataTables_wrapper .dt-button.btn-danger:hover,
    .dataTables_wrapper button.btn-danger:hover {
        box-shadow: 0 6px 18px rgba(239, 68, 68, 0.35) !important;
    }

    /* ===== "Show N entries" — uses our mf-dropdown component, smaller variant ===== */
    .mf-dt-length {
        display: inline-block !important;
        width: auto !important;
        min-width: 76px;
        margin: 0 6px;
        vertical-align: middle;
    }
    .mf-dt-length-trigger {
        min-width: 76px !important;
        height: 36px !important;
        padding: 0 12px !important;
        font-size: 0.88rem !important;
    }
    .mf-dt-length-trigger .mf-dropdown__current {
        flex: 0 1 auto;
    }
    .mf-dt-length-trigger .mf-dropdown__arrow {
        margin-left: 6px;
    }
    .mf-dt-length-panel {
        min-width: 76px;
        padding: 4px !important;
        max-height: 240px;
    }
    .mf-dt-length-panel .mf-panel__item {
        padding: 8px 12px !important;
        margin: 1px 0;
        font-size: 0.88rem !important;
    }
    .mf-dt-length-panel .mf-panel__item.is-active::after {
        content: "";  /* hide the checkmark — too much for a tiny pagination dropdown */
    }

    /* ===== Legacy: Choices-wrapped length dropdown (kept for fallback) ===== */
    .choices.mf-dt-length {
        width: auto !important;
        min-width: 84px;
        margin: 0 6px;
        display: inline-block;
        vertical-align: middle;
    }
    .choices.mf-dt-length .choices__inner {
        min-height: 36px !important;
        padding: 6px 28px 6px 12px !important;
        background: #f8fafc;
        border: 1.5px solid #e2e8f0;
        border-radius: 8px;
        font-size: 0.88rem;
        font-weight: 600;
        color: #0f172a;
    }
    .choices.mf-dt-length .choices__list--single { padding: 0; }
    .choices.mf-dt-length .choices__list--single .choices__item { padding: 0; line-height: 1.4; }
    .choices.mf-dt-length:hover .choices__inner { border-color: #cbd5e1; background: #fff; }
    .choices.mf-dt-length.is-focused .choices__inner,
    .choices.mf-dt-length.is-open .choices__inner {
        border-color: #0d9488 !important;
        background: #fff;
        box-shadow: 0 0 0 3px rgba(13, 148, 136, 0.12);
    }
    .choices.mf-dt-length[data-type*="select-one"]::after {
        right: 10px;
        border-color: #64748b transparent transparent;
    }
    .choices.mf-dt-length.is-open[data-type*="select-one"]::after {
        border-color: transparent transparent #64748b;
    }
    .choices.mf-dt-length .choices__list--dropdown,
    .choices.mf-dt-length .choices__list[aria-expanded] {
        border: 1px solid rgba(15, 23, 42, 0.10) !important;
        border-radius: 10px !important;
        box-shadow: 0 16px 32px rgba(15, 23, 42, 0.18), 0 6px 12px rgba(15, 23, 42, 0.06) !important;
        margin-top: 4px;
        padding: 4px !important;
        background: #fff !important;
        z-index: 9999 !important;
    }
    .choices.mf-dt-length .choices__list--dropdown .choices__item,
    .choices.mf-dt-length .choices__list[aria-expanded] .choices__item {
        padding: 8px 12px !important;
        margin: 1px 0;
        border-radius: 6px !important;
        font-size: 0.88rem !important;
        font-weight: 500;
        color: #0f172a !important;
        background: transparent !important;
    }
    .choices.mf-dt-length .choices__list--dropdown .choices__item--selectable:hover,
    .choices.mf-dt-length .choices__list--dropdown .choices__item--selectable.is-highlighted {
        background: #f1f5f9 !important;
        color: #0d9488 !important;
    }
    .choices.mf-dt-length .choices__list--dropdown .choices__item--selectable[aria-selected="true"] {
        background: rgba(13, 148, 136, 0.10) !important;
        color: #0d9488 !important;
        font-weight: 600;
    }
    .choices.mf-dt-length .choices__list--dropdown .choices__item--selectable::after {
        display: none !important;
    }

    /* ============================================================
       Pagination — modern centered card-pill design
       Targets BOTH:
         • Bootstrap5-DataTables structure (.pagination > .page-item > .page-link)
           — what dataTables.bootstrap5.min.css actually renders here
         • Legacy DataTables structure (.paginate_button)
           — fallback for other pages/wrappers
       ============================================================ */

    /* --- Force any row/col wrapper around pagination to span full width
           so the centered bar centers across the whole table, not col-md-7. */
    .dataTables_wrapper .row > [class*="col-"]:has(> .dataTables_paginate),
    .dataTables_wrapper .row > [class*="col-"]:has(> .dataTables_info),
    .dataTables_wrapper .row > .col-sm-12.col-md-5,
    .dataTables_wrapper .row > .col-sm-12.col-md-7 {
        flex: 0 0 100% !important;
        max-width: 100% !important;
        width: 100% !important;
        text-align: center !important;
        display: flex !important;
        justify-content: center !important;
    }

    /* --- Info text ("Showing X to Y of Z") */
    .dataTables_wrapper .dataTables_info {
        display: block !important;
        width: 100% !important;
        float: none !important;
        text-align: center !important;
        font-size: 0.82rem;
        color: #64748b;
        font-weight: 500;
        padding: 16px 0 4px 0 !important;
        letter-spacing: 0.01em;
    }

    /* --- The outer paginate container — centered, no float */
    .dataTables_wrapper .dataTables_paginate {
        display: flex !important;
        justify-content: center !important;
        align-items: center;
        width: 100% !important;
        max-width: 100%;
        margin: 14px 0 10px !important;
        padding: 0 !important;
        float: none !important;
        text-align: center !important;
        background: transparent !important;
        border: 0 !important;
        box-shadow: none !important;
    }

    /* --- Bootstrap5 <ul.pagination> = the visible pill bar */
    .dataTables_wrapper .dataTables_paginate .pagination {
        display: inline-flex !important;
        flex-wrap: wrap;
        align-items: center;
        justify-content: center;
        gap: 4px;
        margin: 0 !important;
        padding: 6px;
        background: #ffffff;
        border: 1px solid #e2e8f0;
        border-radius: 14px;
        box-shadow:
            0 1px 2px rgba(15, 23, 42, 0.04),
            0 10px 25px -12px rgba(15, 23, 42, 0.10);
        list-style: none;
        white-space: nowrap;
    }
    .dataTables_wrapper .dataTables_paginate .pagination .page-item {
        margin: 0 !important;
        padding: 0 !important;
        background: transparent !important;
    }

    /* --- Individual buttons — rounded chips (covers BOTH structures) */
    .dataTables_wrapper .dataTables_paginate .pagination .page-item .page-link,
    .dataTables_wrapper .dataTables_paginate .paginate_button {
        min-width: 38px;
        height: 38px;
        padding: 0 14px !important;
        margin: 0 !important;
        border: 0 !important;
        border-radius: 10px !important;
        font-size: 0.88rem !important;
        font-weight: 600;
        color: #475569 !important;
        background: transparent !important;
        display: inline-flex !important;
        align-items: center;
        justify-content: center;
        line-height: 1;
        cursor: pointer;
        text-decoration: none !important;
        outline: 0 !important;
        box-shadow: none !important;
        transition:
            background-color .18s ease,
            color .18s ease,
            box-shadow .18s ease,
            transform .18s ease;
    }

    /* --- Hover */
    .dataTables_wrapper .dataTables_paginate .pagination .page-item:not(.active):not(.disabled) .page-link:hover,
    .dataTables_wrapper .dataTables_paginate .paginate_button:hover:not(.current):not(.disabled) {
        background: #f1f5f9 !important;
        color: #0d9488 !important;
        transform: translateY(-1px);
    }

    /* --- Active page — teal gradient + glow */
    .dataTables_wrapper .dataTables_paginate .pagination .page-item.active .page-link,
    .dataTables_wrapper .dataTables_paginate .pagination .page-item.active .page-link:hover,
    .dataTables_wrapper .dataTables_paginate .paginate_button.current,
    .dataTables_wrapper .dataTables_paginate .paginate_button.current:hover {
        background: linear-gradient(135deg, #14b8a6 0%, #0d9488 100%) !important;
        color: #ffffff !important;
        font-weight: 700;
        box-shadow:
            0 6px 14px -4px rgba(13, 148, 136, 0.45),
            inset 0 1px 0 rgba(255, 255, 255, 0.30) !important;
        border-color: transparent !important;
    }

    /* --- Disabled */
    .dataTables_wrapper .dataTables_paginate .pagination .page-item.disabled .page-link,
    .dataTables_wrapper .dataTables_paginate .paginate_button.disabled,
    .dataTables_wrapper .dataTables_paginate .paginate_button.disabled:hover {
        color: #cbd5e1 !important;
        background: transparent !important;
        cursor: not-allowed;
        opacity: 0.55;
        box-shadow: none !important;
        transform: none !important;
    }

    /* --- Previous / Next — text + chevron arrow */
    .dataTables_wrapper .dataTables_paginate .pagination .page-item.previous .page-link,
    .dataTables_wrapper .dataTables_paginate .pagination .page-item.next .page-link,
    .dataTables_wrapper .dataTables_paginate .paginate_button.previous,
    .dataTables_wrapper .dataTables_paginate .paginate_button.next {
        font-weight: 600;
        color: #334155 !important;
        padding: 0 16px !important;
        gap: 8px;
    }
    .dataTables_wrapper .dataTables_paginate .pagination .page-item.previous .page-link::before,
    .dataTables_wrapper .dataTables_paginate .paginate_button.previous::before,
    .dataTables_wrapper .dataTables_paginate .pagination .page-item.next .page-link::after,
    .dataTables_wrapper .dataTables_paginate .paginate_button.next::after {
        content: '';
        display: inline-block;
        width: 7px;
        height: 7px;
        border-style: solid;
        border-color: currentColor;
        border-width: 0;
        transition: transform .2s ease;
    }
    .dataTables_wrapper .dataTables_paginate .pagination .page-item.previous .page-link::before,
    .dataTables_wrapper .dataTables_paginate .paginate_button.previous::before {
        border-left-width: 2px;
        border-bottom-width: 2px;
        transform: rotate(45deg);
        margin-right: 4px;
    }
    .dataTables_wrapper .dataTables_paginate .pagination .page-item.next .page-link::after,
    .dataTables_wrapper .dataTables_paginate .paginate_button.next::after {
        border-right-width: 2px;
        border-top-width: 2px;
        transform: rotate(45deg);
        margin-left: 4px;
    }
    .dataTables_wrapper .dataTables_paginate .pagination .page-item.previous:not(.disabled) .page-link:hover::before,
    .dataTables_wrapper .dataTables_paginate .paginate_button.previous:not(.disabled):hover::before {
        transform: rotate(45deg) translate(-2px, 2px);
    }
    .dataTables_wrapper .dataTables_paginate .pagination .page-item.next:not(.disabled) .page-link:hover::after,
    .dataTables_wrapper .dataTables_paginate .paginate_button.next:not(.disabled):hover::after {
        transform: rotate(45deg) translate(2px, -2px);
    }

    /* --- Legacy span wrapper (numbers group) */
    .dataTables_wrapper .dataTables_paginate span {
        display: inline-flex;
        align-items: center;
        gap: 2px;
        padding: 0;
    }
    .dataTables_wrapper .dataTables_paginate .ellipsis {
        color: #94a3b8;
        padding: 0 6px;
        align-self: center;
        font-weight: 600;
    }

    /* --- Focus ring */
    .dataTables_wrapper .dataTables_paginate .pagination .page-item .page-link:focus,
    .dataTables_wrapper .dataTables_paginate .pagination .page-item .page-link:focus-visible,
    .dataTables_wrapper .dataTables_paginate .paginate_button:focus,
    .dataTables_wrapper .dataTables_paginate .paginate_button:focus-visible {
        outline: 0 !important;
        box-shadow: 0 0 0 3px rgba(13, 148, 136, 0.30) !important;
    }

    /* --- Reduce-motion */
    @media (prefers-reduced-motion: reduce) {
        .dataTables_wrapper .dataTables_paginate .pagination .page-item .page-link,
        .dataTables_wrapper .dataTables_paginate .paginate_button,
        .dataTables_wrapper .dataTables_paginate .pagination .page-item .page-link::before,
        .dataTables_wrapper .dataTables_paginate .pagination .page-item .page-link::after,
        .dataTables_wrapper .dataTables_paginate .paginate_button::before,
        .dataTables_wrapper .dataTables_paginate .paginate_button::after {
            transition: none !important;
        }
    }

    /* --- Mobile */
    @media (max-width: 575.98px) {
        .dataTables_wrapper .dataTables_paginate .pagination {
            padding: 4px;
            border-radius: 12px;
            gap: 2px;
        }
        .dataTables_wrapper .dataTables_paginate .pagination .page-item .page-link,
        .dataTables_wrapper .dataTables_paginate .paginate_button {
            min-width: 34px;
            height: 34px;
            padding: 0 10px !important;
            font-size: 0.82rem !important;
            border-radius: 9px !important;
        }
        .dataTables_wrapper .dataTables_paginate .pagination .page-item.previous .page-link,
        .dataTables_wrapper .dataTables_paginate .pagination .page-item.next .page-link,
        .dataTables_wrapper .dataTables_paginate .paginate_button.previous,
        .dataTables_wrapper .dataTables_paginate .paginate_button.next {
            padding: 0 12px !important;
        }
    }

    /* Sort arrows in column headers — make them visible and themed */
    table.dataTable thead th.sorting,
    table.dataTable thead th.sorting_asc,
    table.dataTable thead th.sorting_desc {
        cursor: pointer;
    }

    /* ============================================================
       Sticky pinned columns + floating horizontal scrollbar
       Applies to EVERY DataTable on the site (table.dataTable is
       auto-added by DataTables on init). Pins the first two columns
       (checkbox + first data column) to the left and the actions
       column to the right while the middle columns scroll under.

       Why: many admin lists have 10+ columns that overflow the
       viewport. The native horizontal scrollbar sits at the bottom
       of the table, often below the fold — users had to scroll the
       PAGE down to find it before scrolling the TABLE right. The
       companion JS in master.blade.php renders a floating proxy
       scrollbar pinned to the bottom of the viewport.
       ============================================================ */

    /* --- Pin first two and last column cells (body) and matching header cells.
           Gated on `.mf-has-overflow` (added by JS only when scrollBody overflows)
           so narrow tables that already fit get normal rendering. --- */
    .mf-has-overflow table.dataTable > tbody > tr > td:nth-child(1),
    .mf-has-overflow table.dataTable > tbody > tr > td:nth-child(2),
    .mf-has-overflow table.dataTable > tbody > tr > td:last-child,
    .mf-has-overflow table.dataTable > thead > tr > th:nth-child(1),
    .mf-has-overflow table.dataTable > thead > tr > th:nth-child(2),
    .mf-has-overflow table.dataTable > thead > tr > th:last-child {
        position: -webkit-sticky !important;
        position: sticky !important;
        background-color: #ffffff !important;
        z-index: 2 !important;
    }
    .mf-has-overflow table.dataTable > tbody > tr > td:nth-child(1),
    .mf-has-overflow table.dataTable > thead > tr > th:nth-child(1) {
        left: 0 !important;
        inset-inline-start: 0 !important;
    }
    .mf-has-overflow table.dataTable > tbody > tr > td:nth-child(2),
    .mf-has-overflow table.dataTable > thead > tr > th:nth-child(2) {
        left: var(--mf-sticky-col1-width, 36px) !important;
        inset-inline-start: var(--mf-sticky-col1-width, 36px) !important;
        box-shadow: 2px 0 0 #e2e8f0 !important;
    }
    .mf-has-overflow table.dataTable > tbody > tr > td:last-child,
    .mf-has-overflow table.dataTable > thead > tr > th:last-child {
        right: 0 !important;
        inset-inline-end: 0 !important;
        box-shadow: -2px 0 0 #e2e8f0 !important;
    }
    /* Header cells above body cells in the z-stack */
    .mf-has-overflow table.dataTable > thead > tr > th {
        z-index: 3 !important;
    }
    /* Striped odd rows — paint sticky cells so non-sticky cells scroll under */
    .mf-has-overflow table.dataTable.table-striped > tbody > tr:nth-of-type(odd) > td:nth-child(1),
    .mf-has-overflow table.dataTable.table-striped > tbody > tr:nth-of-type(odd) > td:nth-child(2),
    .mf-has-overflow table.dataTable.table-striped > tbody > tr:nth-of-type(odd) > td:last-child {
        background-color: #f8fafc !important;
    }
    /* Hover bg on sticky cells */
    .mf-has-overflow table.dataTable.table-hover > tbody > tr:hover > td:nth-child(1),
    .mf-has-overflow table.dataTable.table-hover > tbody > tr:hover > td:nth-child(2),
    .mf-has-overflow table.dataTable.table-hover > tbody > tr:hover > td:last-child {
        background-color: #f1f5f9 !important;
    }
    /* Selected row (DataTables select extension) */
    .mf-has-overflow table.dataTable > tbody > tr.selected > td:nth-child(1),
    .mf-has-overflow table.dataTable > tbody > tr.selected > td:nth-child(2),
    .mf-has-overflow table.dataTable > tbody > tr.selected > td:last-child {
        background-color: #ccfbf1 !important;
    }
    /* Mobile: disable sticky on phones so cells get full width */
    @media (max-width: 575.98px) {
        .mf-has-overflow table.dataTable > tbody > tr > td,
        .mf-has-overflow table.dataTable > thead > tr > th {
            position: static !important;
            box-shadow: none !important;
        }
    }

    /* --- Floating horizontal scrollbar (pinned to viewport bottom) --- */
    .mf-floating-hscroll {
        position: fixed;
        left: 0;
        bottom: 0;
        height: 14px;
        overflow-x: auto;
        overflow-y: hidden;
        background: rgba(255, 255, 255, 0.95);
        border-top: 1px solid #e2e8f0;
        -webkit-backdrop-filter: blur(6px);
        backdrop-filter: blur(6px);
        z-index: 1030;
        transition: opacity .2s ease;
    }
    .mf-floating-hscroll.is-hidden {
        opacity: 0;
        pointer-events: none;
    }
    .mf-floating-hscroll__filler { height: 1px; }
    @media (max-width: 575.98px) {
        .mf-floating-hscroll { height: 10px; }
    }

    /* ===== Back-to-top button: get out of the way when action buttons are visible ===== */
    #back-to-top {
        transition: opacity .25s ease, transform .25s ease, visibility 0s linear .25s;
    }
    #back-to-top.mf-hide-back-to-top {
        opacity: 0;
        transform: translateY(20px);
        pointer-events: none;
        visibility: hidden;
        transition: opacity .2s ease, transform .2s ease, visibility 0s linear .2s;
    }

    /* ===== PRIMARY action — Search button (filled, prominent) ===== */
    .modern-filter-card .btn-search {
        background: linear-gradient(135deg, #0ea5a4 0%, #0d9488 100%);
        border: 0; color: #fff;
        height: 44px; padding: 0 28px;
        border-radius: 10px;
        font-weight: 600; font-size: 0.95rem; letter-spacing: 0.02em;
        box-shadow: 0 4px 12px rgba(13, 148, 136, 0.28), inset 0 1px 0 rgba(255,255,255,0.15);
        transition: transform .12s ease, box-shadow .15s ease, filter .15s ease;
        display: inline-flex; align-items: center; justify-content: center; gap: 8px;
        min-width: 120px;
    }
    .modern-filter-card .btn-search:hover {
        transform: translateY(-1px);
        box-shadow: 0 6px 20px rgba(13, 148, 136, 0.38), inset 0 1px 0 rgba(255,255,255,0.2);
        filter: brightness(1.05); color: #fff;
    }
    .modern-filter-card .btn-search:active { transform: translateY(0); }
    .modern-filter-card .btn-search i { margin-right: 4px; }

    /* ===== SECONDARY action — Reset button (ghost/subtle, lower visual weight) ===== */
    .modern-filter-card .btn-reset {
        background: transparent;
        border: 0;
        color: #64748b;
        height: 44px; padding: 0 14px;
        border-radius: 10px;
        font-weight: 500; font-size: 0.9rem;
        letter-spacing: 0.01em;
        transition: all .12s ease;
        display: inline-flex; align-items: center; justify-content: center; gap: 6px;
        cursor: pointer;
    }
    .modern-filter-card .btn-reset::before {
        content: "↻";
        font-size: 1rem;
        line-height: 1;
        opacity: 0.7;
        transition: transform .3s ease;
    }
    .modern-filter-card .btn-reset:hover {
        background: #f1f5f9;
        color: #0f172a;
    }
    .modern-filter-card .btn-reset:hover::before { transform: rotate(-180deg); opacity: 1; }
    .modern-filter-card .btn-reset:active { background: #e2e8f0; }

    /* ===== "Create new" / "Add" CTA — same teal as Search button, with attention pulse ===== */
    .btn-create,
    .btn-create:hover,
    .btn-create:focus,
    .btn-create:active,
    .btn-create:visited,
    .btn-create:link {
        color: #ffffff !important;
        -webkit-text-fill-color: #ffffff !important;
        text-decoration: none !important;
    }
    .btn-create > *,
    .btn-create:hover > *,
    .btn-create * {
        color: #ffffff !important;
        -webkit-text-fill-color: #ffffff !important;
    }
    .btn-create {
        background: linear-gradient(135deg, #0ea5a4 0%, #0d9488 100%);
        border: 0;
        height: 40px;
        padding: 0 22px;
        border-radius: 10px;
        font-weight: 600;
        font-size: 0.92rem;
        letter-spacing: 0.02em;
        box-shadow: 0 4px 12px rgba(13, 148, 136, 0.28);
        transition: transform .15s ease, box-shadow .2s ease, filter .15s ease;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        gap: 6px;
        position: relative;
    }
    .btn-create:hover {
        transform: translateY(-2px);
        box-shadow: 0 8px 20px rgba(13, 148, 136, 0.42);
        filter: brightness(1.05);
    }
    .btn-create:active {
        transform: translateY(0);
        box-shadow: 0 2px 6px rgba(13, 148, 136, 0.3);
    }
    .btn-create i {
        font-size: 1.1em;
        line-height: 1;
        animation: mfCreatePulse 2.4s ease-in-out 1.5s 2;
    }
    @keyframes mfCreatePulse {
        0%, 100% { transform: scale(1); }
        50%      { transform: scale(1.18); }
    }

    [dir="rtl"] .modern-filter-card > .card-header h4::before { margin-right: 0; margin-left: 8px; }
    [dir="rtl"] .modern-filter-card .btn-search i { margin-right: 0; margin-left: 4px; }
    [dir="rtl"] .mf-dropdown__arrow { margin-left: 0; margin-right: 10px; }
    [dir="rtl"] .mf-panel__item.is-active::after { margin-left: 0; margin-right: auto; }

    /* ===== File input — modern teal "Choose file" button + placeholder text ===== */
    .modern-filter-card input[type="file"].form-control {
        box-sizing: border-box;
        height: 44px !important;
        padding: 0 !important;
        border: 1.5px solid #e2e8f0 !important;
        border-radius: 10px !important;
        background-color: #f8fafc !important;
        color: #64748b;
        font-size: 0.9rem;
        line-height: 41px;        /* inner content height = 44 - 1.5*2 borders */
        overflow: hidden;
        transition: border-color .15s ease, box-shadow .15s ease, background-color .15s ease;
        cursor: pointer;
    }
    .modern-filter-card input[type="file"].form-control:hover {
        border-color: #cbd5e1 !important;
        background-color: #ffffff !important;
    }
    .modern-filter-card input[type="file"].form-control:focus {
        outline: none !important;
        border-color: #0d9488 !important;
        background-color: #ffffff !important;
        box-shadow: 0 0 0 4px rgba(13, 148, 136, 0.12) !important;
    }
    .modern-filter-card input[type="file"].form-control::file-selector-button {
        box-sizing: border-box;
        height: 41px;             /* fills the input's inner height exactly */
        padding: 0 18px;
        margin: 0 12px 0 0;
        border: 0;
        background: linear-gradient(135deg, #0ea5a4 0%, #0d9488 100%);
        color: #ffffff;
        font-weight: 600;
        font-size: 0.88rem;
        letter-spacing: 0.02em;
        cursor: pointer;
        vertical-align: top;
        transition: filter .15s ease, box-shadow .15s ease;
    }
    .modern-filter-card input[type="file"].form-control:hover::file-selector-button {
        filter: brightness(1.05);
        box-shadow: 0 2px 6px rgba(13, 148, 136, 0.22) inset;
    }
    /* Older WebKit fallback */
    .modern-filter-card input[type="file"].form-control::-webkit-file-upload-button {
        box-sizing: border-box;
        height: 41px;
        padding: 0 18px;
        margin: 0 12px 0 0;
        border: 0;
        background: linear-gradient(135deg, #0ea5a4 0%, #0d9488 100%);
        color: #ffffff;
        font-weight: 600;
        font-size: 0.88rem;
        cursor: pointer;
        vertical-align: top;
    }
    .modern-filter-card input[type="file"].form-control.is-invalid {
        border-color: #ef4444 !important;
        box-shadow: 0 0 0 4px rgba(239, 68, 68, 0.10) !important;
    }

    /* ===== Save button — mauve gradient, used on every create form ===== */
    .btn-save,
    .btn-save:hover,
    .btn-save:focus,
    .btn-save:active,
    .btn-save:visited,
    .btn-save:link {
        color: #ffffff !important;
        -webkit-text-fill-color: #ffffff !important;
        text-decoration: none !important;
    }
    .btn-save > *,
    .btn-save:hover > *,
    .btn-save * {
        color: #ffffff !important;
        -webkit-text-fill-color: #ffffff !important;
    }
    .btn-save {
        background: linear-gradient(135deg, #b07ab5 0%, #9560a0 100%) !important;
        background-image: linear-gradient(135deg, #b07ab5 0%, #9560a0 100%) !important;
        border: 0 !important;
        height: 40px;
        padding: 0 22px;
        border-radius: 10px !important;
        font-weight: 600;
        font-size: 0.92rem;
        letter-spacing: 0.02em;
        box-shadow: 0 4px 12px rgba(149, 96, 160, 0.28) !important;
        transition: transform .15s ease, box-shadow .2s ease, filter .15s ease;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        gap: 6px;
        position: relative;
        overflow: hidden;
        cursor: pointer;
    }
    .btn-save:hover {
        transform: translateY(-2px);
        box-shadow: 0 8px 20px rgba(149, 96, 160, 0.42) !important;
        filter: brightness(1.05);
    }
    .btn-save:active {
        transform: translateY(0);
        box-shadow: 0 2px 6px rgba(149, 96, 160, 0.30) !important;
    }
    .btn-save i {
        font-size: 1.05em;
        line-height: 1;
    }
    .btn-save::after {
        content: "";
        position: absolute;
        inset: 0;
        background: radial-gradient(circle at center, rgba(255,255,255,0.32) 0%, rgba(255,255,255,0) 60%);
        opacity: 0;
        transition: opacity .25s ease;
        pointer-events: none;
    }
    .btn-save:active::after { opacity: 1; transition: none; }
</style>

<script>
/*
 * Custom dropdown — vanilla JS, no library.
 * Design inspired by shadcn/ui Select + Linear + Stripe Dashboard.
 *
 * Architecture:
 * - The original <select> is preserved in the DOM (so form submission works unchanged).
 * - A custom <button> trigger replaces it visually.
 * - The dropdown <div> panel is rendered to document.body (escapes ALL clipping).
 * - All event handling is direct (no third-party event capture interference).
 */
(function () {
    'use strict';

    let openInstance = null;

    function escapeHtml(s) {
        return String(s).replace(/[&<>"']/g, function (c) {
            return { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c];
        });
    }

    function init(select) {
        if (select.dataset.mfInit === '1') return;
        if (select.classList.contains('select2') || select.classList.contains('select2-hidden-accessible')) return;
        select.dataset.mfInit = '1';

        const placeholder = select.dataset.placeholder || 'All';
        const realOptions = Array.from(select.options).filter(o => o.value !== '');
        const enableSearch = realOptions.length > 7;

        // If the HTML didn't explicitly mark any option as selected, default to empty
        // (so the placeholder shows). Browser auto-selects the first option otherwise.
        const hasExplicitSelection = Array.from(select.options).some(o => o.defaultSelected);
        if (!hasExplicitSelection) {
            select.value = '';
        }

        // ------- Build wrapper + trigger -------
        const wrapper = document.createElement('div');
        wrapper.className = 'mf-dropdown';
        select.parentNode.insertBefore(wrapper, select);
        wrapper.appendChild(select);
        select.classList.add('mf-native');

        const trigger = document.createElement('button');
        trigger.type = 'button';
        trigger.className = 'mf-dropdown__trigger';
        trigger.setAttribute('aria-haspopup', 'listbox');
        trigger.setAttribute('aria-expanded', 'false');
        trigger.innerHTML =
            '<span class="mf-dropdown__current"></span>' +
            '<span style="display:flex;align-items:center">' +
                '<button type="button" class="mf-dropdown__clear" aria-label="Clear">&times;</button>' +
                '<svg class="mf-dropdown__arrow" width="12" height="8" viewBox="0 0 12 8" fill="none">' +
                    '<path d="M1 1.5L6 6.5L11 1.5" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>' +
                '</svg>' +
            '</span>';
        wrapper.appendChild(trigger);

        // ------- Build floating panel (on body) -------
        const panel = document.createElement('div');
        panel.className = 'mf-panel';
        panel.setAttribute('role', 'listbox');
        document.body.appendChild(panel);

        let searchInput = null;
        if (enableSearch) {
            searchInput = document.createElement('input');
            searchInput.type = 'text';
            searchInput.className = 'mf-panel__search';
            searchInput.placeholder = 'Search...';
            panel.appendChild(searchInput);
        }

        const itemsContainer = document.createElement('div');
        panel.appendChild(itemsContainer);

        function renderItems(filterText) {
            itemsContainer.innerHTML = '';
            const term = (filterText || '').toLowerCase();
            const matching = realOptions.filter(o => !term || o.textContent.toLowerCase().includes(term));
            if (matching.length === 0) {
                itemsContainer.innerHTML = '<div class="mf-panel__empty">No results</div>';
                return;
            }
            matching.forEach(opt => {
                const item = document.createElement('button');
                item.type = 'button';
                item.className = 'mf-panel__item';
                item.setAttribute('role', 'option');
                item.dataset.value = opt.value;
                if (opt.value === select.value) item.classList.add('is-active');
                const color = opt.dataset.color;
                item.innerHTML = (color ? '<span class="mf-dot" style="background:' + color + '"></span>' : '') +
                    escapeHtml(opt.textContent.trim());
                item.addEventListener('click', function (e) {
                    e.preventDefault();
                    e.stopPropagation();
                    select.value = opt.value;
                    select.dispatchEvent(new Event('change', { bubbles: true }));
                    updateTrigger();
                    close();
                });
                itemsContainer.appendChild(item);
            });
        }
        renderItems('');

        if (searchInput) {
            searchInput.addEventListener('input', function () { renderItems(searchInput.value); });
            searchInput.addEventListener('click', function (e) { e.stopPropagation(); });
        }

        // ------- Sync trigger from native select state -------
        const currentEl = trigger.querySelector('.mf-dropdown__current');
        function updateTrigger() {
            const value = select.value;
            const opt = realOptions.find(o => o.value === value);
            if (opt && value !== '') {
                const color = opt.dataset.color;
                currentEl.innerHTML = (color ? '<span class="mf-dot" style="background:' + color + '"></span>' : '') +
                    escapeHtml(opt.textContent.trim());
                currentEl.classList.remove('mf-dropdown__current--empty');
                wrapper.classList.add('has-value');
            } else {
                currentEl.innerHTML = escapeHtml(placeholder);
                currentEl.classList.add('mf-dropdown__current--empty');
                wrapper.classList.remove('has-value');
            }
            itemsContainer.querySelectorAll('.mf-panel__item').forEach(function (item) {
                item.classList.toggle('is-active', item.dataset.value === value);
            });
        }
        updateTrigger();

        // ------- Position panel -------
        function positionPanel() {
            const rect = trigger.getBoundingClientRect();
            const panelHeight = panel.offsetHeight;
            const viewportHeight = window.innerHeight;
            const spaceBelow = viewportHeight - rect.bottom;
            const spaceAbove = rect.top;
            let top;
            if (spaceBelow >= panelHeight + 10 || spaceBelow >= spaceAbove) {
                top = rect.bottom + 6;
            } else {
                top = rect.top - panelHeight - 6;
            }
            panel.style.top = top + 'px';
            panel.style.left = rect.left + 'px';
            panel.style.width = rect.width + 'px';
        }

        // ------- Open / close -------
        function open() {
            if (openInstance && openInstance !== api) openInstance.close();
            panel.classList.add('is-open');
            trigger.classList.add('is-open');
            trigger.setAttribute('aria-expanded', 'true');
            positionPanel();
            // Reposition after panel is rendered (so we know its actual height)
            requestAnimationFrame(positionPanel);
            if (searchInput) {
                searchInput.value = '';
                renderItems('');
                setTimeout(function () { searchInput.focus(); }, 50);
            }
            openInstance = api;
        }
        function close() {
            panel.classList.remove('is-open');
            trigger.classList.remove('is-open');
            trigger.setAttribute('aria-expanded', 'false');
            if (openInstance === api) openInstance = null;
        }

        const api = { open: open, close: close, positionPanel: positionPanel };

        trigger.addEventListener('click', function (e) {
            // Allow the clear-X button inside the trigger to fire its own handler first
            if (e.target.closest('.mf-dropdown__clear')) return;
            e.preventDefault();
            e.stopPropagation();
            if (panel.classList.contains('is-open')) close(); else open();
        });

        trigger.querySelector('.mf-dropdown__clear').addEventListener('click', function (e) {
            e.preventDefault();
            e.stopPropagation();
            select.value = '';
            select.dispatchEvent(new Event('change', { bubbles: true }));
            updateTrigger();
            close();
        });

        // Sync if select value changes externally (e.g. Reset button)
        select.addEventListener('change', updateTrigger);

        wrapper._mfApi = api;
    }

    // ------- Global handlers (one set total, not per-instance) -------
    document.addEventListener('mousedown', function (e) {
        if (!openInstance) return;
        const wrapper = e.target.closest('.mf-dropdown');
        const panel = e.target.closest('.mf-panel');
        if (wrapper && wrapper._mfApi === openInstance) return;
        if (panel) return;
        openInstance.close();
    });

    document.addEventListener('keydown', function (e) {
        if (!openInstance) return;
        if (e.key === 'Escape') { openInstance.close(); return; }
    });

    function repositionOpen() { if (openInstance) openInstance.positionPanel(); }
    window.addEventListener('scroll', repositionOpen, true);
    window.addEventListener('resize', repositionOpen);

    function initTimePickers() {
        if (typeof flatpickr === 'undefined') return;
        document.querySelectorAll('.modern-filter-card input[type="time"], .modern-filter-card input[data-mf-time]').forEach(function (input) {
            if (input.dataset.mfTimeInit === '1') return;
            input.dataset.mfTimeInit = '1';

            const currentValue = input.value;
            input.setAttribute('type', 'text');
            if (currentValue) input.value = currentValue;
            input.classList.add('mf-timepicker');

            flatpickr(input, {
                enableTime: true,
                noCalendar: true,
                time_24hr: false,
                dateFormat: 'H:i',
                altInput: true,
                altFormat: 'h:i K',
                altInputClass: 'form-control mf-timepicker',
                placeholder: 'Select time',
                allowInput: true,
                disableMobile: true,
                onReady: function (selectedDates, dateStr, instance) {
                    instance.calendarContainer.classList.add('mf-flatpickr', 'mf-flatpickr--time-only');
                    if (instance.altInput) {
                        instance.altInput.setAttribute('placeholder', 'Select time');
                        instance.altInput.addEventListener('focus', function () { input.classList.add('is-active'); instance.altInput.classList.add('is-active'); });
                        instance.altInput.addEventListener('blur',  function () { input.classList.remove('is-active'); instance.altInput.classList.remove('is-active'); });
                    }
                    // Inject "Hour" / "Minute" / "AM/PM" labels
                    if (instance.timeContainer) {
                        const labelFor = function (selector, text) {
                            const el = instance.timeContainer.querySelector(selector);
                            if (!el) return;
                            const wrapper = el.closest('.numInputWrapper') || el;
                            if (wrapper.querySelector('.mf-time-label')) return;
                            const label = document.createElement('span');
                            label.className = 'mf-time-label';
                            label.textContent = text;
                            wrapper.appendChild(label);
                        };
                        labelFor('.flatpickr-hour', 'Hour');
                        labelFor('.flatpickr-minute', 'Minute');
                        const ampm = instance.timeContainer.querySelector('.flatpickr-am-pm');
                        if (ampm && !ampm.querySelector('.mf-time-label')) {
                            const label = document.createElement('span');
                            label.className = 'mf-time-label';
                            label.textContent = 'AM/PM';
                            ampm.appendChild(label);
                        }
                    }
                },
                onOpen: function (selectedDates, dateStr, instance) {
                    input.classList.add('is-active');
                    if (instance.altInput) instance.altInput.classList.add('is-active');
                },
                onClose: function (selectedDates, dateStr, instance) {
                    input.classList.remove('is-active');
                    if (instance.altInput) instance.altInput.classList.remove('is-active');
                },
            });
        });
    }

    function initDatePickers() {
        if (typeof flatpickr === 'undefined') return;
        document.querySelectorAll('.modern-filter-card input[type="datetime-local"], .modern-filter-card input[data-mf-date]').forEach(function (input) {
            if (input.dataset.mfDateInit === '1') return;
            input.dataset.mfDateInit = '1';

            // Preserve current value when changing type
            const currentValue = input.value;
            const isDateTime = (input.type === 'datetime-local') || (input.dataset.mfDate === 'datetime');
            input.setAttribute('type', 'text');
            if (currentValue) input.value = currentValue;
            input.classList.add('mf-datepicker');

            // altInput shows a user-friendly format (e.g. "May 22, 2025 · 12:00 PM")
            // dateFormat keeps the backend-compatible ISO format (e.g. "2025-05-22T12:00")
            // on the original input that gets submitted.
            flatpickr(input, {
                enableTime: isDateTime,
                time_24hr: false,
                dateFormat: isDateTime ? 'Y-m-d\\TH:i' : 'Y-m-d',
                altInput: true,
                altFormat: isDateTime ? 'd/m/Y h:i K' : 'd/m/Y',
                altInputClass: 'form-control mf-datepicker',
                placeholder: isDateTime ? 'Select date & time' : 'Select date',
                allowInput: true,
                disableMobile: true,
                monthSelectorType: 'static',
                onReady: function (selectedDates, dateStr, instance) {
                    instance.calendarContainer.classList.add('mf-flatpickr');
                    if (instance.altInput) {
                        instance.altInput.setAttribute('placeholder', isDateTime ? 'Select date & time' : 'Select date');
                        instance.altInput.addEventListener('focus', function () { input.classList.add('is-active'); instance.altInput.classList.add('is-active'); });
                        instance.altInput.addEventListener('blur',  function () { input.classList.remove('is-active'); instance.altInput.classList.remove('is-active'); });
                    }
                    // Inject "Hour" / "Minute" / "AM/PM" labels above each time control
                    if (isDateTime && instance.timeContainer) {
                        const labelFor = function (selector, text) {
                            const el = instance.timeContainer.querySelector(selector);
                            if (!el) return;
                            const wrapper = el.closest('.numInputWrapper') || el;
                            if (wrapper.querySelector('.mf-time-label')) return;
                            const label = document.createElement('span');
                            label.className = 'mf-time-label';
                            label.textContent = text;
                            wrapper.appendChild(label);
                        };
                        labelFor('.flatpickr-hour', 'Hour');
                        labelFor('.flatpickr-minute', 'Minute');
                        const ampm = instance.timeContainer.querySelector('.flatpickr-am-pm');
                        if (ampm && !ampm.querySelector('.mf-time-label')) {
                            const label = document.createElement('span');
                            label.className = 'mf-time-label';
                            label.textContent = 'AM/PM';
                            ampm.appendChild(label);
                        }
                    }
                },
                onOpen: function (selectedDates, dateStr, instance) {
                    input.classList.add('is-active');
                    if (instance.altInput) instance.altInput.classList.add('is-active');
                },
                onClose: function (selectedDates, dateStr, instance) {
                    input.classList.remove('is-active');
                    if (instance.altInput) instance.altInput.classList.remove('is-active');
                },
            });
        });
    }

    function clearButtonLoading(btn) {
        btn.classList.remove('is-loading');
        if (btn._mfLoadingTimer) {
            clearTimeout(btn._mfLoadingTimer);
            btn._mfLoadingTimer = null;
        }
    }

    function bindButtonLoading() {
        // Buttons that should show a loading spinner when clicked.
        // NOTE: export buttons are intentionally EXCLUDED — they trigger file downloads
        // which don't navigate away from the page, so the spinner would linger.
        const buttonSelectors = '.modern-filter-card #search, .modern-filter-card button[type="submit"].btn-search:not(#export):not(#export-excel-link)';
        document.querySelectorAll(buttonSelectors).forEach(function (btn) {
            if (btn.dataset.mfLoadingBound) return;
            btn.dataset.mfLoadingBound = '1';

            btn.addEventListener('click', function (e) {
                if (btn.classList.contains('is-loading')) {
                    // Already loading — prevent double-submit
                    e.preventDefault();
                    return;
                }
                btn.classList.add('is-loading');
                // Short safety auto-clear (most actions finish well under this)
                btn._mfLoadingTimer = setTimeout(function () { clearButtonLoading(btn); }, 10000);
            });
        });

        // Clear Search button loading state as soon as DataTables AJAX completes.
        if (typeof jQuery !== 'undefined') {
            jQuery(document).off('xhr.dt.mfLoading').on('xhr.dt.mfLoading', function () {
                document.querySelectorAll('.modern-filter-card #search.is-loading').forEach(clearButtonLoading);
            });
            jQuery(document).off('draw.dt.mfLoading').on('draw.dt.mfLoading', function () {
                document.querySelectorAll('.modern-filter-card #search.is-loading').forEach(clearButtonLoading);
            });
        }

        // Clear any in-flight loading state on browser back/forward (bfcache restore)
        window.addEventListener('pageshow', function () {
            document.querySelectorAll('.modern-filter-card .btn.is-loading').forEach(clearButtonLoading);
        });
    }

    function applySelect2EmptyState($sel) {
        const instance = $sel.data('select2');
        if (!instance || !instance.$container) return;
        const v = $sel.val();
        const isEmpty = v === '' || v === null || (Array.isArray(v) && v.length === 0);
        instance.$container.toggleClass('mf-select2-empty', isEmpty);
    }

    function enhanceSelect2() {
        if (typeof jQuery === 'undefined' || !jQuery.fn.select2) return;

        // STEP 1: Initialize Select2 on any .select2 select that hasn't been initialized yet.
        // Some pages tag selects with .select2 but never run the init script, so the browser
        // falls back to the native dropdown. We bootstrap Select2 here so they all get the
        // modern themed widget.
        jQuery('.modern-filter-card select.select2').each(function () {
            const $sel = jQuery(this);
            if ($sel.hasClass('select2-hidden-accessible')) return; // already init'd
            if (this.dataset.mfInit === '1') return;                // our custom dropdown owns it

            const placeholder = $sel.attr('data-placeholder') || 'Select an option';
            try {
                $sel.select2({
                    placeholder: placeholder,
                    allowClear: true,
                    width: '100%',
                });
            } catch (err) {
                // Select2 might not be ready yet — next retry will catch it
            }
        });

        // STEP 2: Theme every initialized Select2 instance (ours + page's own).
        // The .select2-hidden-accessible class is added by Select2 AFTER init.
        jQuery('.modern-filter-card select.select2-hidden-accessible').each(function () {
            const $sel = jQuery(this);
            const instance = $sel.data('select2');
            if (!instance || !instance.$container) return;

            // Always (re)apply the container theme class — cheap, idempotent
            instance.$container.addClass('select2-container--modern-filter');
            applySelect2EmptyState($sel);

            // Bind the change handler ONCE per select
            if (!$sel.data('mfSelect2Themed')) {
                $sel.data('mfSelect2Themed', true);
                $sel.on('change', function () { applySelect2EmptyState($sel); });
                $sel.on('select2:opening select2:open', function () {
                    instance.$container.addClass('select2-container--modern-filter');
                    applySelect2EmptyState($sel);
                    setTimeout(function () {
                        document.querySelectorAll('.select2-container--open').forEach(function (el) {
                            el.classList.add('select2-container--modern-filter');
                        });
                    }, 0);
                });
            }
        });
    }

    function buildLengthDropdown(select) {
        if (!select || select.dataset.mfDtInit === '1') return;
        select.dataset.mfDtInit = '1';

        // Wrap the native select with our own trigger/panel UI
        var wrapper = document.createElement('div');
        wrapper.className = 'mf-dropdown mf-dt-length';
        select.parentNode.insertBefore(wrapper, select);
        wrapper.appendChild(select);
        select.classList.add('mf-native');

        var trigger = document.createElement('button');
        trigger.type = 'button';
        trigger.className = 'mf-dropdown__trigger mf-dt-length-trigger';
        trigger.innerHTML =
            '<span class="mf-dropdown__current"></span>' +
            '<svg class="mf-dropdown__arrow" width="10" height="6" viewBox="0 0 12 8" fill="none">' +
                '<path d="M1 1.5L6 6.5L11 1.5" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>' +
            '</svg>';
        wrapper.appendChild(trigger);

        var panel = document.createElement('div');
        panel.className = 'mf-panel mf-dt-length-panel';
        document.body.appendChild(panel);

        function renderItems() {
            panel.innerHTML = '';
            Array.from(select.options).forEach(function (opt) {
                var item = document.createElement('button');
                item.type = 'button';
                item.className = 'mf-panel__item';
                item.dataset.value = opt.value;
                if (opt.value === select.value) item.classList.add('is-active');
                item.textContent = opt.textContent;
                item.addEventListener('click', function (e) {
                    e.preventDefault();
                    e.stopPropagation();
                    select.value = opt.value;
                    select.dispatchEvent(new Event('change', { bubbles: true }));
                    updateTrigger();
                    closePanel();
                });
                panel.appendChild(item);
            });
        }

        function updateTrigger() {
            var current = trigger.querySelector('.mf-dropdown__current');
            var opt = Array.from(select.options).find(function (o) { return o.value === select.value; });
            current.textContent = opt ? opt.textContent : (select.value || '');
            panel.querySelectorAll('.mf-panel__item').forEach(function (item) {
                item.classList.toggle('is-active', item.dataset.value === select.value);
            });
        }

        function positionPanel() {
            var rect = trigger.getBoundingClientRect();
            panel.style.top = (rect.bottom + 6) + 'px';
            panel.style.left = rect.left + 'px';
            panel.style.minWidth = rect.width + 'px';
        }

        function openPanel() {
            renderItems();
            panel.classList.add('is-open');
            trigger.classList.add('is-open');
            positionPanel();
            requestAnimationFrame(positionPanel);
        }
        function closePanel() {
            panel.classList.remove('is-open');
            trigger.classList.remove('is-open');
        }

        trigger.addEventListener('click', function (e) {
            e.preventDefault();
            e.stopPropagation();
            if (panel.classList.contains('is-open')) closePanel(); else openPanel();
        });
        document.addEventListener('mousedown', function (e) {
            if (!panel.classList.contains('is-open')) return;
            if (trigger.contains(e.target) || panel.contains(e.target)) return;
            closePanel();
        });
        document.addEventListener('keydown', function (e) {
            if (e.key === 'Escape' && panel.classList.contains('is-open')) closePanel();
        });
        select.addEventListener('change', updateTrigger);
        window.addEventListener('scroll', function () {
            if (panel.classList.contains('is-open')) positionPanel();
        }, true);
        window.addEventListener('resize', function () {
            if (panel.classList.contains('is-open')) positionPanel();
        });

        renderItems();
        updateTrigger();
    }

    function enhanceDataTablesControls() {
        // Modernize the "Show N entries" length dropdown that DataTables generates.
        // We use our OWN mf-dropdown component (not Choices.js) because the length select
        // is wrapped in a <label>, which Choices.js handles unreliably.
        document.querySelectorAll('.dataTables_length select').forEach(buildLengthDropdown);

        // Force white text on toolbar buttons (Copy/CSV/Excel/Print/Delete).
        // Some theme rule keeps winning over our CSS `color: #fff !important`, so we set
        // inline styles with !important via setProperty — that beats every other rule.
        forceWhiteOnDtButtons();
    }

    function forceWhiteOnDtButtons(root) {
        var scope = root || document;
        scope.querySelectorAll && scope.querySelectorAll('.dataTables_wrapper .dt-button').forEach(function (btn) {
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

    // MutationObserver: any time DataTables re-renders the toolbar (or any future code adds
    // new buttons), immediately re-apply the white text. This makes the fix permanent
    // — works on initial load, on table redraws, on paginated views, everywhere.
    function startDtButtonGuard() {
        if (!('MutationObserver' in window)) return;
        if (window.__mfDtGuardStarted) return;
        window.__mfDtGuardStarted = true;
        var observer = new MutationObserver(function (mutations) {
            for (var i = 0; i < mutations.length; i++) {
                var m = mutations[i];
                if (m.type === 'childList' && m.addedNodes.length) {
                    for (var j = 0; j < m.addedNodes.length; j++) {
                        var node = m.addedNodes[j];
                        if (node.nodeType !== 1) continue;
                        if (node.matches && node.matches('.dt-button')) {
                            forceWhiteOnDtButtons(node.parentNode || document);
                        } else if (node.querySelector && node.querySelector('.dt-button')) {
                            forceWhiteOnDtButtons(node);
                        }
                    }
                }
            }
        });
        observer.observe(document.body, { childList: true, subtree: true });
    }

    function initAll() {
        document.querySelectorAll('.modern-filter-card select').forEach(init);
        initDatePickers();
        initTimePickers();
        enhanceSelect2();
        enhanceDataTablesControls();
        // Tag ONLY the column that holds the action buttons, so CSS can style it as an
        // actions bar without affecting sibling field columns in the same row.
        document.querySelectorAll('.modern-filter-card .btn-search').forEach(function (btn) {
            const col = btn.closest('[class*="col-"]');
            if (col) col.classList.add('modern-filter-actions__col');
        });

        // Wire up loading-spinner state for action buttons.
        // The spinner shows on click and clears either when DataTables AJAX finishes
        // (for Search/Reset), or after a safety timeout (for Export buttons that trigger
        // server-side file generation / page navigation).
        bindButtonLoading();

        // When the form's action buttons scroll into view, hide the global back-to-top button
        // (otherwise the back-to-top widget overlaps Save/Search and looks broken).
        const backToTop = document.getElementById('back-to-top');
        const actionCols = document.querySelectorAll('.modern-filter-card .modern-filter-actions__col');
        if (backToTop && actionCols.length > 0 && 'IntersectionObserver' in window) {
            const visibleCols = new Set();
            const observer = new IntersectionObserver(function (entries) {
                entries.forEach(function (entry) {
                    if (entry.isIntersecting) visibleCols.add(entry.target);
                    else visibleCols.delete(entry.target);
                });
                backToTop.classList.toggle('mf-hide-back-to-top', visibleCols.size > 0);
            }, { threshold: 0.1, rootMargin: '0px 0px -40px 0px' });
            actionCols.forEach(function (col) { observer.observe(col); });
        }
    }

    function bindReset() {
        document.querySelectorAll('.modern-filter-card [type="reset"]').forEach(function (btn) {
            if (btn.dataset.resetBound) return;
            btn.dataset.resetBound = '1';
            btn.addEventListener('click', function () {
                setTimeout(function () {
                    const card = btn.closest('.modern-filter-card');
                    if (!card) return;
                    card.querySelectorAll('select').forEach(function (sel) {
                        sel.value = '';
                        sel.dispatchEvent(new Event('change', { bubbles: true }));
                    });
                    if (typeof jQuery !== 'undefined') {
                        jQuery(card).find('select.select2').val('').trigger('change');
                    }
                    // Clear flatpickr date/time inputs
                    card.querySelectorAll('input.mf-datepicker, input.mf-timepicker').forEach(function (input) {
                        if (input._flatpickr) input._flatpickr.clear();
                        else input.value = '';
                    });
                    const searchBtn = card.querySelector('#search');
                    if (searchBtn) searchBtn.click();
                }, 0);
            });
        });
    }

    function boot() {
        initAll();
        bindReset();
        startDtButtonGuard();
        // Page-specific scripts that init Select2 / DataTables may run AFTER us.
        // Re-scan a few times so we catch them whenever they finish initializing.
        function rescan() { enhanceSelect2(); enhanceDataTablesControls(); }
        setTimeout(rescan, 100);
        setTimeout(rescan, 300);
        setTimeout(rescan, 700);
        setTimeout(rescan, 1500);
        setTimeout(rescan, 3000);
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', boot);
    } else {
        boot();
    }
})();
</script>
