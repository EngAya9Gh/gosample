# Tasks Dashboard — Frontend Migration Plan

> Read [`01-foundation.md`](01-foundation.md) first. Conforms to the shared SPA
> architecture, the `/app/api` JSON layer, the `/options` select contract,
> boot-payload permissions, and the RTL/i18n bridge.

## 1. Module overview

Aggregated **tasks-by-client bar chart** with a filter form (driver, from/to
location, date range). Single grouped bar chart: total / closed / pending tasks
per client (`clients.arabic_name`). Read-only analytics.

- **Nav group:** Dashboards → "Tasks Dashboard".
- **Nav route:** `/tasks-dashboard` (`nav.config.js` line 14), `perm: 'dashboard_access'`.
- Read-only.

## 2. Current implementation (Blade / Velzon)

### Routes
| Method | URI | Name | Controller@action |
|---|---|---|---|
| GET | `/tasks-dashboard` | `tasksdashboard` | `HomeController@tasksdashboard` |
| POST | `/tasks-dashboard` | `tasksdashboard.search` | `HomeController@tasksdashboard` (same method, filtered) |

### Controller (`HomeController@tasksdashboard`, lines 509–571)
- Single aggregate query: `Task` join `clients ON clients.id = billing_client`,
  selecting `clients.arabic_name`, `COUNT(*) as total`,
  `SUM(status='CLOSED') as closed_total`,
  `SUM(status NOT IN ('CLOSED','NO_SAMPLES')) as pending_total`, grouped by
  `clients.arabic_name`, ordered by `total desc`.
- Optional filters via `when()`: `status`, `driver_id`, `billing_client`,
  `from_location`, `to_location`, and `date_from + date_to` (whereBetween on
  `tasks.created_at`, start-of-day → end-of-day).
- Builds an **Akaunting/ApexCharts** `Chart` (`type:'bar'`, title
  `translation.Tasks_Clients`, subtitle `translation.All_Data`), with categories =
  client arabic names and 3 datasets: Tasks / Closed Tasks / Pending Tasks.
- Also passes `clients = Client::all()`, `drivers = Driver::all()`,
  `locations = Location::all()` for the filter selects.
- **NO Gate** in this method (only `auth` middleware via `__construct`).
- **NOT client-scoped** in the current (active) implementation — the commented-out
  older version had scoping, but the live code aggregates across all clients.

### View (`tasks-dashboard.blade.php`)
- Filter form POSTs to `tasksdashboard.search`: `driver_id` (select2),
  `date_from`, `date_to` (date inputs), `from_location`, `to_location` (select2).
  (The `billing_client` and `status` selects are commented out in this view.)
- Renders the server-built chart via `@apexchartsScripts` + `$chart->container()`
  + `$chart->script()` (Akaunting helper emits ApexCharts JS inline).

### Permissions / Gates
- None server-side. Nav perm `dashboard_access` is rendering-only.
- Filter selects always populated from full `Client/Driver/Location` lists.

### Special behaviors to PRESERVE
- **Server-side aggregation** (one grouped query — keep it; don't move grouping
  to the client).
- **ApexCharts grouped bar** with 3 series (total/closed/pending) keyed by client
  arabic name.
- Filter semantics, including the date-range start/end-of-day handling.

## 3. Target design (Vue + Tailwind)

### Page mapping
| Blade view | Target Vue view | vue-build components |
|---|---|---|
| `tasks-dashboard.blade.php` | `views/Dashboard/TasksDashboard.vue` | `Breadcrumb`, `BaseCard`, `FilterBar`, `FormSelect` ×3, `FormInput` (dates), `BaseButton`, `EmptyState` |

- `FilterBar` with: Driver (`FormSelect`), From Location (`FormSelect`), To
  Location (`FormSelect`), Date From / Date To (date `FormInput`s), Search button.
  (Mirror the active Blade filters; the commented `billing_client`/`status` can be
  added optionally since the controller already supports them — but keep parity by
  default.)
- One `BaseCard` titled `translation.Tasks_Clients` containing the chart.
- **Swap the Akaunting server-rendered chart for `vue3-apexcharts`** grouped bar:
  `series = [{name:'Tasks',data},{name:'Closed Tasks',data},{name:'Pending Tasks',data}]`,
  `xaxis.categories = client arabic names`. Force `dir="ltr"` on the chart.
- Re-fetch the chart series on filter "Search" (no full page POST).

### Vue Router route
`{ path: '/tasks-dashboard', name: 'tasks-dashboard', component: () => import('views/Dashboard/TasksDashboard.vue'), meta: { perm: 'dashboard_access' } }`

### nav.config.js
Exists (line 14). No change.

### Empty/loading/error states
- Chart card: `EmptyState` ("no tasks match these filters") when categories empty;
  spinner while fetching.

## 4. Data / API contract

### `GET /app/api/dashboard/tasks-chart`
Reuses `HomeController@tasksdashboard`'s aggregate query verbatim (same grouping,
same `when()` filters, same date start/end-of-day handling). Returns chart-ready
arrays instead of a server-rendered chart.

Request params (all optional):
```
GET /app/api/dashboard/tasks-chart?status=CLOSED&driver_id=12&billing_client=4
    &from_location=3&to_location=9&date_from=2026-06-01&date_to=2026-06-27
```
Response:
```json
{
  "data": {
    "categories": ["مدلاب", "العياناتي", "..."],
    "series": {
      "total":   [1280, 940, 610],
      "closed":  [1100, 700, 520],
      "pending": [ 180, 240,  90]
    }
  }
}
```
- `categories[i]` aligns index-wise with each `series.*[i]` (same order the query
  returns — `total desc`).
- The SPA maps `series.total/closed/pending` to the 3 ApexCharts series named
  Tasks / Closed Tasks / Pending Tasks.

### `GET /app/api/dashboard/tasks-chart/options`
Filter select data (foundation `/options` shape), bundled:
```json
{ "data": {
    "drivers":   [ { "value": 12, "label": "Mohammed A." } ],
    "clients":   [ { "value": 4,  "label": "MDLab" } ],
    "locations": [ { "value": 3,  "label": "Central Hub" } ]
} }
```
Reuses the same `Driver::all()` / `Client::all()` / `Location::all()` the Blade
loads (label = `name` / `english_name` / `name` respectively).

### Validation
None (filters are optional, `when()`-guarded). No Form Request.

## 5. Migration steps (ordered, checkable)

- [ ] backend: `Api\DashboardApiController@tasksChart` reusing the grouped
      aggregate query + `when()` filters; return `categories` + `series`.
- [ ] backend: `@tasksChartOptions` returning driver/client/location option lists.
- [ ] frontend: build `TasksDashboard.vue` with `FilterBar` + `vue3-apexcharts`
      grouped bar; fetch on mount and on Search.
- [ ] wire router `/tasks-dashboard`; nav entry present.
- [ ] parity test vs Blade: same bars per client, same totals under each filter
      combo, same date-range behavior.
- [ ] flip nav to `/app/tasks-dashboard` (cutover).

## 6. Risks / must-not-break

- **Not client-scoped today** — the active controller aggregates across all
  clients. Do **not** add `assigned_client_ids` scoping (that would change the
  numbers). If product wants scoping, that's a separate logic change, flagged.
- **Date range** uses `Carbon::parse(from)->startOfDay()` →
  `parse(to)->endOfDay()` only when **both** are present; preserve.
- **`pending_total`** = `status NOT IN ('CLOSED','NO_SAMPLES')` — keep the exact
  set so "pending" matches.
- **Category key is `clients.arabic_name`** (Arabic) — render LTR-safe but keep
  Arabic labels; do not switch to english_name.
- **No Gate** server-side — keep it that way; SPA `meta.perm` is rendering-only.

## 7. Out of scope / open questions

- The commented-out `billing_client` and `status` filters: the controller already
  honors them (`when()`), so exposing them in the SPA is low-risk and additive —
  decide whether to surface them now or keep strict parity (default: parity).
- Chart range toggles (ALL/1M/6M/1Y) seen on the Analytics dashboard do **not**
  exist here — do not add.
