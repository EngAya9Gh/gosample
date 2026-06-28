# Reports (Daily / Weekly / Monthly / Performance + Header Notifications) — Frontend Migration Plan

> Read [`01-foundation.md`](01-foundation.md) first. API layer, `{data,meta}`,
> chart-placeholder→ApexCharts swap, exports-link-to-existing-routes, and
> RTL/i18n live there. This module is the **dashboard/report pattern** (model it
> on `views/Dashboard/Analytics.vue`). All aggregation MUST stay server-side and
> identical — we only re-present it. **No gates are applied in `ReportsController`**
> (auth-group only — see §6).

---

## 1. Module overview

Reports is the analytics hub (`Reports` nav group). A landing page links to four
report types, all driven by `ReportsController` aggregations over Drivers /
Attendances / Tasks / Samples / Swaps:
- **Daily** — per-driver check-in/out, lateness, operational delays for one date.
- **Weekly** — per-driver attendance summary over a date range (cards).
- **Monthly** — per-driver performance scoring + ranking; AJAX-refreshable table; **Excel export** + print.
- **Performance** — per-driver KPI cards (punctuality, avg speed, violations).
- **Header notifications** — the bell-dropdown feed (delayed tasks, lost samples, new tasks/swaps, system notifications) consumed by the global topbar, not a page.

---

## 2. Current implementation (Blade / Velzon)

### Routes (`routes/web.php` ~191–198)
| Method | URI | Name | Action |
|---|---|---|---|
| GET | `/admin/reports` | `admin.reports.index` | `index` (landing) |
| GET | `/admin/reports/daily` | `admin.reports.daily` | `daily` |
| GET | `/admin/reports/weekly` | `admin.reports.weekly` | `weekly` |
| GET | `/admin/reports/monthly` | `admin.reports.monthly` | `monthly` (returns partial HTML on ajax) |
| GET | `/admin/reports/monthly/export` | `admin.reports.exportMonthly` | `exportMonthly` (**Excel download**) |
| GET | `/admin/reports/performance` | `admin.reports.performance` | `performance` |
| GET | `/admin/header/notifications-data` | `admin.header.notifications` | `getHeaderNotifications` (**JSON**) |

### Controller actions → data & aggregation (PRESERVE EXACTLY)
- **`index()`** → `admin.reports.index` (static landing: three forms posting GET to daily/weekly/monthly with date/range/month pickers).
- **`daily(Request)`** — `date` (default today). `Driver::with(attendances[for day], tasks[for day])->get()->map(...)`: sets `day_attendance` (first attendance), `delayed_tasks_count` (tasks where `delayed_reason <> ''`). View shows per-driver: check-in, check-out, delay-start badge, operational delays, status (Finished/In Service/Offline). Header tiles: active drivers count, total delays sum.
- **`weekly(Request)`** — `start_date`/`end_date` (default this week). `Driver::with(attendances[range])->get()`. View renders per-driver cards: daysWorked, totalLate, `punctuality_score` (model attr), overtime hrs.
- **`monthly(Request)`** — `month` (default `Y-m`). **Cached 300s** per `monthly_report_{month}_{userId}`. Computes `expectedDays` (non-Friday days), then per driver: `days_present`, `days_absent`, `days_late`, `total_delay`, `total_overtime`, `total_early_leave`, `kpi_violations`, and a weighted **`performance_score`** (punctuality·0.5 + completion·0.4 + 10 base − violations·2, clamped ≥0). **On `$request->ajax()` returns the rendered `admin.reports.partials.monthly_table` HTML** (used by the month-picker live refresh); otherwise the full page. Has Export + Print buttons.
- **`exportMonthly(Request)`** — `Excel::download(new App\Exports\MonthlyPerformanceExport($month), "Performance-Report-{month}.xlsx")`.
- **`performance(Request)`** — `Driver::with(tasks[with both arrival times])->get()->map(...)`: `kpi_punctuality` (= `$driver->punctuality_score`), `kpi_avg_speed` (avg minutes between from/to arrival over completed tasks), `kpi_violations`. View renders per-driver KPI cards (progress bar + speed + violations badge).
- **`getHeaderNotifications()`** — **cached 120s** per `header_notifications_{userId}`. Builds (each limited to 5, scoped by `user->client_id` where relevant): `newTasks` (NEW, no driver), `newSwapTasks`, `pickup_delayedTasks`, `drop_off_delayedTasks`, `delayed_tasks_in_freezer`, `delayed_tasks_delivered`, `lost_samples`, `systemNotifications` (user unread). Renders `layouts.partials.notifications_dropdown` HTML and returns JSON `{ html, delayed_count, newTasksCount, newSwapTasksCount, lost_samples_count }`.

### Blade views
| File | Purpose | Charts? |
|---|---|---|
| `admin/reports/index.blade.php` | Landing: 3 report-type cards w/ date pickers | none (icons only: `ri-pie-chart`, `ri-calendar-event`, `ri-line-chart`, `ri-award`) |
| `admin/reports/daily.blade.php` | Date filter + summary tiles + per-driver **table** (check-in/out, delay badges, status) | none (table + badges) |
| `admin/reports/weekly.blade.php` | Range filter + per-driver **cards** (daysWorked/late/punctuality/overtime) | none (progress/stat cards) |
| `admin/reports/monthly.blade.php` | Month picker (live ajax) + Export/Print + ranking **table** partial | none in shell (table); progress bars in partial |
| `admin/reports/partials/monthly_table.blade.php` | The ranking table body (ajax-swapped) | progress bars |
| `admin/reports/performance.blade.php` | Per-driver **KPI cards** (punctuality progress bar, avg speed, violations) | progress bars (candidate for ApexCharts radial) |

> **Finding: none of the existing report views use ApexCharts.** They use tables,
> Bootstrap progress bars, and stat tiles. The migration may *optionally* upgrade
> progress bars to ApexCharts (radial/bar) per the Analytics.vue pattern, but the
> **numbers must match the server aggregation exactly** — charts are presentation
> only. Header notifications is a dropdown feed, not a chart.

### Gates
**None.** `ReportsController` has no `Gate::denies(...)` calls — access is governed
only by the `admin` group `auth` middleware. (`report_access` mentioned in the
brief does **not** appear in this controller — verify in `nav`/sidebar; do not
invent it on the API unless the sidebar enforces it.)

### Form Requests
None — all reads use plain `Request` query params.

### Special behaviors to PRESERVE
- **Monthly cache (300s)** and **header-notifications cache (120s)** keyed by user.
- **`expectedDays` = non-Friday days** in month-to-date logic.
- **`performance_score` weighting** (0.5/0.4/+10/−2·violations, clamp ≥0) — do not recompute in JS.
- **`MonthlyPerformanceExport` Excel** — link to the existing route; do not reimplement.
- **`client_id` scoping** of header notifications (client-portal users see only their client's items).
- Monthly ajax returns **partial HTML** today; the SPA will instead consume JSON (see §4) — but the *numbers* must be identical.

---

## 3. Target design (Vue + Tailwind)

### View mapping (under `resources/js/vue/views/Reports/`)
| Blade view | Vue view | vue-build components |
|---|---|---|
| `reports/index` | `ReportsHome.vue` | `Breadcrumb`, `BaseCard` ×3 (report-type cards), `FormInput` (date/month), `BaseButton` |
| `reports/daily` | `ReportsDaily.vue` | `Breadcrumb`, `FilterBar`/date input, `StatCard` ×2 (active drivers, total delays), `DataTable` (drivers), `StatusBadge` (on-time/late/status), `EmptyState` |
| `reports/weekly` | `ReportsWeekly.vue` | `Breadcrumb`, date-range inputs, grid of `BaseCard`/`StatCard` per driver (daysWorked/late/punctuality/overtime) |
| `reports/monthly` | `ReportsMonthly.vue` | `Breadcrumb`, month picker, `BaseButton` (Export, Print), `DataTable` ranking (progress-bar cell → optional ApexCharts), loading overlay |
| `reports/performance` | `ReportsPerformance.vue` | `Breadcrumb`, grid of `BaseCard` KPI cards (punctuality progress/radial, avg speed, violations badge) |
| `layouts.partials.notifications_dropdown` | **AppShell topbar dropdown** (foundation/topbar) | bell dropdown consuming `/header/notifications-data` JSON |

- **Charts:** keep tables/progress bars to start (parity). Optionally swap monthly/performance progress to `vue3-apexcharts` radial bars later — data shape from API (§4) is chart-ready (`{ name, value }`).
- **Monthly live refresh:** month-picker `@change` → re-fetch JSON → re-render the `DataTable` (replaces the Blade ajax-partial-HTML swap). Show the loading overlay during fetch.
- **Header notifications:** the topbar bell polls `/app/api/reports/header-notifications` and renders the feed natively in Vue (do NOT inject server HTML into the SPA — re-render from the JSON counts + items). Keep the same poll/refresh cadence as the Blade header.

### Vue Router routes
- `/admin/reports` `ReportsHome.vue`
- `/admin/reports/daily` `ReportsDaily.vue`
- `/admin/reports/weekly` `ReportsWeekly.vue`
- `/admin/reports/monthly` `ReportsMonthly.vue`
- `/admin/reports/performance` `ReportsPerformance.vue`
- `meta.perm` only if the sidebar gates Reports (verify; controller has none).

### nav.config.js
Confirm `Reports` group entries (index/daily/weekly/monthly/performance). Cutover only.

### States
Daily/Monthly empty → `EmptyState` ("No operational data for this date/month"). Loading → overlay/skeleton on the table/cards.

---

## 4. Data / API contract

All read-only GET; reuse the controller aggregations verbatim.

| Method | Path | Purpose | Reuses |
|---|---|---|---|
| GET | `/app/api/reports/daily?date=` | daily driver rows | `ReportsController@daily` query/map |
| GET | `/app/api/reports/weekly?start_date=&end_date=` | weekly driver summaries | `@weekly` |
| GET | `/app/api/reports/monthly?month=` | ranking rows + meta | `@monthly` (reuse the **cache** + scoring; return JSON instead of partial HTML) |
| GET | `/app/api/reports/performance` | KPI rows | `@performance` |
| GET | `/app/api/reports/header-notifications` | bell feed | `@getHeaderNotifications` (reuse cache + client scoping; return JSON **counts + items**, not server HTML) |

Existing routes the SPA links to directly (do **not** reimplement):
- **Excel export:** `GET /admin/reports/monthly/export?month=YYYY-MM` (`admin.reports.exportMonthly`) — `ReportsMonthly.vue` Export button is a plain link/`window.location` to this URL.
- **Print:** client-side `window.print()` (Monthly/Performance) — replicate in Vue.

### Response shapes
- **daily** `{ data:[ { id, name, mobile, checkin_time, checkout_time, is_late, delay_minutes, delayed_tasks_count, status } ], meta:{ active_drivers, total_delays, date } }`
- **weekly** `{ data:[ { id, name, username, days_worked, total_late, punctuality_score, overtime_hours } ], meta:{ start, end } }`
- **monthly** `{ data:[ { id, name, days_present, days_absent, days_late, total_delay, total_overtime, total_early_leave, kpi_violations, performance_score } ], meta:{ month, expected_days } }` (pre-sorted/ranked as Blade does)
- **performance** `{ data:[ { id, name, kpi_punctuality, kpi_avg_speed, kpi_violations } ] }`
- **header-notifications** `{ data:{ delayed_count, newTasksCount, newSwapTasksCount, lost_samples_count, items:{ newTasks:[...], newSwapTasks:[...], pickup_delayedTasks:[...], drop_off_delayedTasks:[...], delayed_tasks_in_freezer:[...], delayed_tasks_delivered:[...], lost_samples:[...], systemNotifications:[...] } } }` — each item array carries the minimal fields the Blade dropdown shows (ids, timestamps, barcode_id for lost samples). **Reuse the same queries + caches; just emit JSON instead of the rendered `notifications_dropdown` HTML.**

### Validation
None (query params with controller defaults). Keep defaults identical (today / this week / current month).

---

## 5. Migration steps (ordered, checkable)

- [ ] backend: `Api\ReportsApiController` with `daily/weekly/monthly/performance/headerNotifications` — **call the same aggregation code** (extract shared methods from `ReportsController` so web + api compute identically, preserving the 300s/120s caches and scoring).
- [ ] backend: ensure `exportMonthly` route untouched (SPA links to it).
- [ ] frontend: `ReportsHome/Daily/Weekly/Monthly/Performance.vue` from Analytics.vue + DataTable/StatCard patterns.
- [ ] frontend: topbar bell consuming `header-notifications` JSON (native render, not HTML injection).
- [ ] (optional) swap progress bars → ApexCharts radial, keeping numbers identical.
- [ ] wire router (5 routes) + confirm nav (+ perm only if sidebar gates).
- [ ] parity test vs each Blade report: identical numbers (performance_score, expectedDays, delays), Export produces the same xlsx, header feed counts match.
- [ ] cutover nav per report.
- [ ] mark status in `00-README.md`.

---

## 6. Risks / must-not-break

- **Aggregation parity is the whole risk.** `performance_score` weighting, `expectedDays` (Friday excluded), avg-speed minutes, delay counts (`delayed_reason <> ''`), days_present unique-date logic — all must come from the **shared server method**, never reimplemented in JS.
- **Caches:** keep the monthly (300s, per user) and header-notifications (120s, per user) cache keys/TTLs. Don't bypass them.
- **`client_id` scoping** of header notifications must persist — client-portal users must not see other clients' items.
- **Export must be byte-identical** — link to the existing `MonthlyPerformanceExport` route; do not regenerate in the API.
- **No gates today** — keep the API behind the `auth` group; do not invent `report_access` unless the sidebar already enforces it (verify before adding `meta.perm`).
- Monthly currently returns **partial HTML on ajax**; the SPA replaces that transport with JSON — ensure the rendered table matches the partial exactly (same columns/order/ranking).
- Header notifications must NOT inject the Blade `notifications_dropdown` HTML into the SPA (style/Bootstrap leakage) — re-render natively from JSON.

---

## 7. Out of scope / open questions

- **No existing ApexCharts in reports** — confirm whether the team wants charts added now or strict table/bar parity first. This plan defaults to parity, charts optional.
- Verify whether the sidebar applies any permission to the Reports group (controller does not). If it does, mirror it as `meta.perm`; otherwise leave ungated behind auth.
- Daily/Weekly/Performance are not cached today (only Monthly + header). Do not add caching as part of the presentation migration.
- `getHeaderNotifications` is consumed by the global header across the whole panel — coordinate its JSON cutover with the AppShell/topbar (foundation), not just the Reports pages.
