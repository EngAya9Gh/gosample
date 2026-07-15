# Daily Operation — Frontend Migration Plan

> Read [`01-foundation.md`](01-foundation.md) first. This screen is the one
> **dashboard with a server-side DataTable**, so it leans on the foundation's
> `DataTable` server-side contract (`{ q, sortKey, sortDir, page, pageSize }` +
> `{ data, meta }` envelope) and the `useDataTable` composable. Conforms to the
> `/app/api` layer, `/options`, permissions, and RTL/i18n bridges.

## 1. Module overview

Operational tasks worklist for the current day's monitoring. A rich filter form
+ a **server-side Yajra DataTable** of tasks (from/to location, client, driver,
car, status, timestamps, computed trip "hours"). Reuses the same Task query the
Tasks module uses, but framed as a daily-operations view. Read-only list (no
inline CRUD actions are wired in the active view — the actions column is defined
in the controller but commented out of the table).

- **Nav group:** Dashboards → "Daily Operation".
- **Nav route:** `/daily-operation` (`nav.config.js` line 15), `perm: 'dashboard_access'`.
- **Server Gate:** `task_access` (enforced in controller — see §2).

## 2. Current implementation (Blade / Velzon)

### Routes
| Method | URI | Name | Controller@action |
|---|---|---|---|
| GET | `/daily-operation` | `operation` | `DailyOperationController@index` |
| POST | `/daily-operation` | `operation.search` | `DailyOperationController@index` (DataTables ajax) |

(`DailyOperationController@export` exists but is **not routed** to this screen — a
dead/hard-coded PDF method; ignore for migration.)

### Controller (`DailyOperationController@index`)
- `abort_if(Gate::denies('task_access'), 403)` — **real server Gate**.
- **Client scoping:** when `assigned_client_ids` non-empty, `whereIn('billing_client', …)`.
- **AJAX branch** (`$request->ajax()`): Yajra `DataTables::of(Task::with(from,to,client,driver,car))`
  with a `->filter()` closure reading POST params:
  - `status`, `driver_id`, `billing_client`, `from_location`, `to_location`,
    `keyword` (exact `tasks.id`), `delayed_reason`,
  - `date_from + date_to` (whereBetween on `created_at`), each alone (`>=`),
  - `task_date` (whereBetween start/end of that day).
- Computed/edited columns: `from_location_name`, `to_location_name`,
  `billing_client_status` (client english_name), `driver_name`, `plate_number`,
  `close_date`, `delayed_reason`, **`hours`** (= `Period::make(from_location_arrival_time,
  close_date)` formatted "%02d Hours, %02d Minutes" via `parent::hoursandmins`),
  and enum-label columns via `Task::*_SELECT` maps (`type`, `task_type`,
  `confirmed_by_client`, `ayenati`, `takasi`, `status`). Plus an `actions` column
  rendered from `partials.datatablesActions` (gates `task_show/edit/delete`).
- **Non-AJAX branch:** loads `clients`, `locations`, `drivers` (client-scoped
  when applicable) for the filter selects, renders `daily-operation.blade.php`.

### View (`daily-operation.blade.php`)
- Filter form (no submit POST; values fed into DataTables `ajax.data`):
  `billing_client` (select2), `driver_id` (select2), `task_date` (date),
  `status` (native select: NEW/COLLECTED/IN_FREEZER/OUT_FREEZER/CLOSED/NO_SAMPLES),
  `from_location` (select2), `to_location` (select2), Search button (`#search` →
  `table.draw()`).
- DataTable (`serverSide: true`, ajax → `operation.search`), `pageLength: 100`,
  `order: [[1,'desc']]`. Columns (in order):
  `placeholder, id, from_location_name, to_location_name, billing_client_status,
  driver_name, plate_number, status, from_location_arrival_time, close_date,
  hours, collection_date, freezer_date, freezer_out_date, created_at`.
  (type/task_type/added_by/delayed_reason/actions columns are commented out.)
- `@can('can-delete')` builds a mass-delete button to `admin.tasks.massDestroy`,
  but it is **not pushed** into the toolbar (`dtButtons.push` commented) — so no
  active bulk action on this screen.

### Permissions / Gates
- Server Gate `task_access` (real boundary).
- `partials.datatablesActions` references `task_show/task_edit/task_delete` (only
  relevant if the actions column is re-enabled).
- `can-delete` referenced for the (inactive) mass-delete button.
- Nav perm `dashboard_access` (rendering-only; mismatch with server Gate
  `task_access` — see §6).

### Special behaviors to PRESERVE
- **Server-side DataTable** (Yajra) — pagination/sort/filter all server-side.
- **Computed `hours`** column (trip duration from arrival→close).
- **Enum→label mapping** via `Task::*_SELECT` so cells read identically.
- **Client scoping** by `assigned_client_ids`.
- Filter param semantics, esp. `keyword` = exact task id and the date handling.

## 3. Target design (Vue + Tailwind)

### Page mapping
| Blade view | Target Vue view | vue-build components |
|---|---|---|
| `daily-operation.blade.php` | `views/Dashboard/DailyOperation.vue` | `Breadcrumb`, `FilterBar`, `FormSelect` ×5, `FormInput` (date), `BaseButton`, `DataTable`, `StatusBadge`, `EmptyState` |

- `FilterBar` with: Client, Driver, Status (select), From Location, To Location
  (`FormSelect`s), Task Date (`FormInput type=date`), Search.
- `DataTable` in **server-side mode** (`useDataTable` composable) bound to
  `GET /app/api/daily-operation`. Columns mirror the active Blade column set, in
  the same order; `status` rendered via `StatusBadge`; force `dir=ltr` on id,
  times, plate.
- Default sort `id desc`, page size 100 (match `pageLength`).
- Actions column: omit by default (parity with the active view). If product wants
  row actions, gate view/edit/delete via `usePermissions` per the Tasks plan.

### Vue Router route
`{ path: '/daily-operation', name: 'daily-operation', component: () => import('views/Dashboard/DailyOperation.vue'), meta: { perm: 'task_access' } }`

### nav.config.js
Exists (line 15). No change.

### Empty/loading/error states
- `DataTable` empty → `EmptyState`. Loading spinner via `useDataTable`. 403 via
  router guard + API Gate.

## 4. Data / API contract

### `GET /app/api/daily-operation` (server-side list)
Reuses `DailyOperationController@index`'s AJAX query + `->filter()` logic and the
`task_access` Gate. Translate Yajra's request into the foundation's DataTable
contract; keep the module-specific filters as extra params.

Request:
```
GET /app/api/daily-operation
    ?q=&sortKey=id&sortDir=desc&page=1&pageSize=100
    &status=CLOSED&driver_id=12&billing_client=4
    &from_location=3&to_location=9&keyword=10428&delayed_reason=traffic
    &date_from=2026-06-01&date_to=2026-06-27&task_date=2026-06-27
```
- `keyword` = **exact** `tasks.id` match (preserve; not a fuzzy search).
- date handling identical to the controller (`date_from+date_to` whereBetween;
  each alone `>=`; `task_date` whole-day range).

Response (foundation envelope; keys match the DataTable columns):
```json
{
  "data": [
    {
      "id": 10428,
      "from_location_name": "King Faisal Lab",
      "to_location_name": "Central Hub",
      "billing_client_status": "MDLab",
      "driver_name": "Mohammed A.",
      "plate_number": "ABC-1234",
      "status": "CLOSED",
      "from_location_arrival_time": "2026-06-27 07:10:00",
      "close_date": "2026-06-27 09:40:00",
      "hours": "02 Hours, 30 Minutes",
      "collection_date": "2026-06-27 07:25:00",
      "freezer_date": "2026-06-27 08:00:00",
      "freezer_out_date": "2026-06-27 09:00:00",
      "created_at": "2026-06-27 06:55:00"
    }
  ],
  "meta": { "total": 1280, "page": 1, "pageSize": 100 }
}
```
- Pre-format display values server-side exactly as the controller's editColumns
  do (the `hours` string, the `Task::*_SELECT` enum labels) so cells are identical.
- Keep raw `id` for links/row actions if re-enabled.

### `GET /app/api/daily-operation/options`
Filter selects (foundation `/options` shape), bundled and **client-scoped** like
the controller's non-ajax branch:
```json
{ "data": {
    "clients":   [ { "value": 4, "label": "MDLab" } ],
    "drivers":   [ { "value": 12, "label": "Mohammed A." } ],
    "locations": [ { "value": 3, "label": "Central Hub" } ],
    "statuses":  [ { "value": "NEW", "label": "NEW" }, ... ]
} }
```
(`statuses` = the fixed list in the Blade select.)

### Validation
None on read. (No Form Request; reuse the controller filter closure.)

## 5. Migration steps (ordered, checkable)

- [ ] backend: `Api\DailyOperationApiController@index` reusing the Yajra query +
      `->filter()` logic + `task_access` Gate; return `{data, meta}` mapped to the
      DataTable contract (translate `q/sort/page` ↔ Yajra params).
- [ ] backend: reproduce the computed `hours` + enum-label columns identically.
- [ ] backend: `@options` (client-scoped) for the filter selects + statuses.
- [ ] frontend: build `DailyOperation.vue` with `FilterBar` + server-side
      `DataTable` (`useDataTable`); 100/page, id-desc default.
- [ ] wire router `/daily-operation` (perm `task_access`); nav entry present.
- [ ] parity test vs Blade: same rows/order/paging, same filter results
      (esp. `keyword` exact-id, `task_date` whole-day), same `hours`, same enum
      labels, same client scoping.
- [ ] flip nav to `/app/daily-operation` (cutover).

## 6. Risks / must-not-break

- **Gate mismatch:** server enforces `task_access`; nav uses `dashboard_access`.
  Keep `task_access` as the real API check.
- **`keyword` = exact id**, not LIKE — don't turn it into the DataTable's free
  `q` search by accident. Map the DataTable `q` carefully (or leave `q` unused and
  expose `keyword` as a dedicated id field) to avoid changing match semantics.
- **Client scoping** by `assigned_client_ids` on both the list and the options.
- **Computed `hours`** depends on `from_location_arrival_time` + `close_date`
  (null-safe); reproduce the exact format and the empty-when-no-close-date rule.
- **Enum labels** must come from `Task::*_SELECT` (server-side), not re-mapped in JS.
- **No active row/bulk actions** in the current view — do not silently add destructive
  buttons; if added, they must go through the same Gates + `can-delete`.
- **`export()`** is dead/hard-coded — do not wire it into the SPA.

## 7. Out of scope / open questions

- This list overlaps heavily with the main **Tasks** module ([30-tasks.md]) which
  has the same Task DataTable + real exports/actions. Coordinate the row shape so
  both reuse one shared API/list builder where possible (DRY without changing
  meaning). Daily Operation is the "today's ops" framing of that same data.
- Confirm whether ops wants the (currently commented) actions/mass-delete columns
  enabled in the SPA, or whether parity (read-only) is the target.
- `freezer_date` is in the column list but not explicitly edited in the controller
  — confirm it serializes the raw model attribute as the Blade does.
