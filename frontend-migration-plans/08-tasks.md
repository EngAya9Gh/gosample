# Tasks — Frontend Migration Plan

> Flagship module. Read [`01-foundation.md`](01-foundation.md) first — this plan
> uses its JSON API layer, the server-side `DataTable` query contract
> (`{q,sortKey,sortDir,page,pageSize}` + filters), the `{data,meta}` list
> envelope, the 422→form-field error mapping, the `/options` endpoint shape, the
> permission boot payload, and RTL/i18n bridge. Nothing here changes business
> logic — we add thin JSON endpoints that reuse the existing
> `TasksController` queries, the **`StoreTaskRequest` / `UpdateTaskRequest` /
> `MassDestroyTaskRequest`** Form Requests, the existing Gates, and the
> **existing export routes** (we do NOT reimplement exports).

The delivered showcase
[`vue-build/.../views/Tasks/TasksList.vue`](../vue-build/resources/js/vue/views/Tasks/TasksList.vue)
is the reference implementation for the main list (FilterBar + DataTable with all
16 columns + StatusBadge + soft row actions + delete-confirm modal). The Tasks
list view below is that file wired to the real API.

---

## 1. Module overview

The Tasks module is the operational core: create / list / edit / show transport
tasks (sample & box pickups/deliveries), plus four "delayed" monitoring reports,
an "unused tasks" report, a barcode **scan** screen, a **missing samples**
screen, and Excel/PDF exports.

- **Nav group:** Tasks.
- **Primary route / gate:** `/admin/tasks` · `task_access`.
- **Sub-gates:** `task_create`, `task_edit`, `task_show`, `task_delete`
  (delete is governed at the API by `can-delete` per foundation; `task_delete`
  is the render gate the Blade actions use), `task_scan`, `task_missing`,
  `unused_tasks`, `task_edit_times` (show-screen "Edit Times" modal).
- **Why it matters:** highest-traffic, highest-risk screen; status transitions,
  FCM notifications, ETA jobs, and integration logging all fire from here.

---

## 2. Current implementation (Blade / Velzon)

### Routes (controller = `App\Http\Controllers\Admin\TasksController`)

| Method | URI | Name | Action | Gate |
|---|---|---|---|---|
| GET | `admin/tasks` | `admin.tasks.index` | `index` (server-side ajax when `request()->ajax()`) | `task_access` |
| GET | `admin/tasks/create` | `admin.tasks.create` | `create` | `task_create` |
| POST | `admin/tasks/{task}` (resource store) | `admin.tasks.store` | `store(StoreTaskRequest)` | `task_create` |
| GET | `admin/tasks/{task}` | `admin.tasks.show` | `show` | `task_show` |
| GET | `admin/tasks/{task}/edit` | `admin.tasks.edit` | `edit` | `task_edit` |
| PUT/PATCH | `admin/tasks/{task}` | `admin.tasks.update` | `update(UpdateTaskRequest)` | `task_edit` |
| DELETE | `admin/tasks/{task}` | `admin.tasks.destroy` | `destroy` | `can-delete` |
| DELETE | `admin/tasks/destroy` | `admin.tasks.massDestroy` | `massDestroy(MassDestroyTaskRequest)` | `task_delete` + `can-delete` |
| GET | `admin/tasks/newshow/{id}` | (unnamed) | `newShow` | `task_show` |
| PUT | `admin/tasks/{task}/update-times` | `admin.tasks.updateTimes` | `updateTimes` | `task_edit_times` |
| GET | `admin/tasks/unused` | `admin.tasks.unused` | `unUsedTasks` (ajax) | `unused_tasks` |
| GET | `admin/tasks/scan` | `admin.tasks.scan` | `scan` | `task_scan` |
| GET | `admin/tasks/missing` | `admin.tasks.missing` | `missing` | `task_missing` |
| GET | `admin/pickupdelayed` | `admin.tasks.pickupdelayed` | `pickupdelayed` (ajax) | `task_access` |
| GET | `admin/dropdelayed` | `admin.tasks.dropdelayed` | `dropdelayed` (ajax) | `task_access` |
| GET | `admin/collectedDelayed` | `admin.tasks.collectedDelayed` | `collectedDelayed` (ajax) | `task_access` |
| GET | `admin/outfreezerdelayed` | `admin.tasks.outfreezerdelayed` | `outfreezerdelayed` (ajax) | `task_access` |
| POST | `admin/tasks` | `admin.tasks.export` | `export` (PDF, queued) | — |
| POST | `admin/task-report` | `admin.reportExport` | `export` (PDF, queued — alias used by filter forms) | — |
| GET | `admin/export-excel` | `admin.tasks.export-excel` | `exportExcelDetails` (Excel, queued) | — |
| GET | `admin/tasks/export-status/{token}` | `admin.tasks.export.status` | `exportStatus` (poll/download) | — |

> `exportExcel` (the direct synchronous `Excel::download`) exists in the
> controller but is **not routed** in the Tasks block — the live Excel path is
> `exportExcelDetails` (queued). Keep that.

### What `index` passes / returns

- **AJAX (server-side):** `Yajra DataTables::of($query)` with eager-loaded
  `from,to,client,driver,car`. Adds computed columns: `sequence`, `actions`
  (`partials.datatablesActions`), `from_location_name`, `to_location_name`,
  `client` (=`client.english_name`), `driver_name`, `car_imei`, `hours`
  (Period from `collection_date`→`close_date`), confirmation badges, and a
  **status badge** (`NEW`→bg-primary, `COLLECTED`→bg-info, `IN_FREEZER`/
  `OUT_FREEZER`→bg-warning, `CLOSED`→bg-success, `NO_SAMPLES`→bg-secondary).
- **Fail-closed client scoping (PRESERVE):** if user has `assigned_client_ids`,
  rows are `whereIn('billing_client', …)`. A non-admin user with no client sees
  nothing.
- **Default 30-day window (PRESERVE):** when no date range and no keyword, the
  query defaults to last 30 days for performance.
- **Sorting:** only `created_at|updated_at|collection_date` are accepted
  (`sort_by` + `sort_order`); anything else → `created_at desc`.
- **Total count** is cached per-user 10 min (`setTotalRecords`).
- **Non-AJAX (page load):** returns `admin.tasks.index` with `clients`,
  `locations`, `drivers` for the filter selects (client-scoped if applicable).

### Blade views (`resources/views/admin/tasks/`)

| File | Purpose | Table mode |
|---|---|---|
| `index.blade.php` | Main list + filter panel + exports | server-side ajax (`admin.tasks.index`) |
| `create.blade.php` | Create form (multi from-location) | — |
| `edit.blade.php` | Edit form (adds `status`) | — |
| `show.blade.php` | Printable journey report + bags table + temp chart + Edit-Times modal | — |
| `newshow.blade.php` | Alt show (same data, `newshow` route) | — |
| `scan.blade.php` | Barcode scan / confirm samples | — |
| `missing.blade.php` | Missing/lost sample lookup | — |
| `un_used.blade.php` | Unused tasks report | server-side ajax (`admin.tasks.unused`) |
| `pickupdelayed/dropdelayed/collectedDelayed/outfreezerdelayed.blade.php` | 4 delayed reports (copy-paste twins) | server-side ajax (one route each) |
| `export-pending.blade.php` | Async export polling screen | — |

### Form Request rules (PRESERVE exactly — reuse via API)

- **`StoreTaskRequest`** (`task_create`): `from_location` required;
  `to_location` required + `different:from_location`; `billing_client` required;
  `driver_id` required numeric; `time_of_visit` required numeric `gt:0 lt:50`;
  `type` required string; `task_type` required string; `dropoff_time` required
  date; `pickup_time` required date.
- **`UpdateTaskRequest`** (`task_edit`): `from_location` required;
  `to_location` required + `different:from_location`; `billing_client` required;
  `driver_id` required numeric; `task_type` required. (No time-of-visit/dates.)
- **`MassDestroyTaskRequest`** (`task_delete`): `ids` required array,
  `ids.*` exists:tasks,id.

### Enums (from `App\Models\Task`)

- `STATUS_SELECT`: NEW, COLLECTED, CLOSED, IN_FREEZER, NO_SAMPLES, OUT_FREEZER.
- `TYPE_SELECT`: one_time, scheduled.
- `TASK_TYPE_SELECT`: SAMPLE, BOX.
- `TAKASI_SELECT`: NO, YES. `AYENATI_SELECT`: YES, NO.
- `CONFIRMED_BY_CLIENT_SELECT`: YES, NO, PARTIAL.

### Special behaviors to PRESERVE

- **Server-side DataTables** for index + 4 delayed + unused.
- **create:** `from_location[]` is a **Select2 multiple**; store loops
  `time_of_visit` × each from-location → multiple Task rows; fires
  `Driver::sendNotification` + `sendGeneralNotification` (FCM) per task; then
  dispatches `CalculateDriverETAJob`. **Do not change this loop or the jobs.**
- **update:** setting status→`CLOSED` stamps `close_date`,
  `to_location_confirmation_timestamp`, `closed_by='admin'`, and dispatches
  `LogData` (integration/Blazma) when the from-location has an integration
  branch. Model `booted()` dispatches `CalculateDriverETAJob` on any status
  change. **PRESERVE — these run inside `update()`.**
- **show:** bags grouped by `bag_code`, bag/sample counts, `car_tracking`
  temp aggregates (temp5/6/7) → Chart.js line chart; client-side print; Edit
  Times modal → `updateTimes` (gate `task_edit_times`, validates
  `freezer_out_date`/`close_date` dates).
- **scan:** `onscan.js` + **Scandit SDK v5** camera; posts to the existing
  driver/sample APIs (`/api/driver/samples/valid/check`,
  `/api/task/sample/check`, `/api/task/sample/confirmall`,
  `/api/client/samples/confirm`). These are **out of scope** to rebuild — keep
  calling the same endpoints.
- **missing:** plain barcode input → `/api/client/samples/lost`,
  `/api/client/samples/confirm`, `/api/client/samples/details` (gates
  `mark_as_lost`, `check_receiving_details`, `…_advanced`).
- **exports:** all three are **queued + token-polled**. PDF →
  `GenerateTaskReportJob`; Excel → `GenerateTaskExportJob`; both redirect to
  `admin.tasks.export.status?token=…`. The polling screen JSON-polls
  `?status=1` and auto-downloads. **Reuse these routes verbatim.**

---

## 3. Target design (Vue + Tailwind)

All views under `resources/js/vue/views/Tasks/`.

| Blade view | Vue view | vue-build components |
|---|---|---|
| `index.blade.php` | `TasksList.vue` (port of vue-build showcase, wired to API) | Breadcrumb, FilterBar, DataTable, FormInput, FormSelect, StatusBadge, BaseButton, BaseAvatar, BaseModal, ToastHost |
| `create.blade.php` | `TaskCreate.vue` | BaseCard, FormSelect (multiple), FormInput, FormSelect, BaseButton |
| `edit.blade.php` | `TaskEdit.vue` | BaseCard, FormSelect, FormInput, BaseButton |
| `show.blade.php` / `newshow.blade.php` | `TaskShow.vue` | BaseCard, StatusBadge, Timeline, StatCard, BaseModal (Edit Times), Breadcrumb |
| `scan.blade.php` | `TaskScan.vue` | BaseCard, FormSelect, BaseButton, StatusBadge, EmptyState |
| `missing.blade.php` | `TaskMissing.vue` | BaseCard, FormInput, BaseButton, StatusBadge |
| `un_used.blade.php` | `TasksUnused.vue` | Breadcrumb, FilterBar, DataTable, FormSelect, FormInput |
| 4 delayed views | `TasksDelayed.vue` (one reusable view, `:variant` prop = pickup/drop/collected/outfreezer) | Breadcrumb, FilterBar, DataTable, FormSelect, FormInput, StatusBadge |
| `export-pending.blade.php` | `TaskExportStatus.vue` | BaseCard, EmptyState, BaseButton |

> The 4 delayed Blade views are identical except the ajax URL → build **one**
> `TasksDelayed.vue` parameterized by a `variant` that maps to its API path.

### Vue Router routes (mirror nav `route` values; base `/app`)

```
/admin/tasks                 → Tasks/TasksList.vue       meta.perm: task_access
/admin/tasks/create          → Tasks/TaskCreate.vue      meta.perm: task_create
/admin/tasks/:id             → Tasks/TaskShow.vue        meta.perm: task_show
/admin/tasks/:id/edit        → Tasks/TaskEdit.vue        meta.perm: task_edit
/admin/tasks/scan            → Tasks/TaskScan.vue        meta.perm: task_scan
/admin/tasks/missing         → Tasks/TaskMissing.vue     meta.perm: task_missing
/admin/tasks/unused          → Tasks/TasksUnused.vue     meta.perm: unused_tasks
/admin/pickupdelayed         → Tasks/TasksDelayed.vue (variant=pickup)        meta.perm: task_access
/admin/dropdelayed           → Tasks/TasksDelayed.vue (variant=drop)          meta.perm: task_access
/admin/collectedDelayed      → Tasks/TasksDelayed.vue (variant=collected)     meta.perm: task_access
/admin/outfreezerdelayed     → Tasks/TasksDelayed.vue (variant=outfreezer)    meta.perm: task_access
/admin/tasks/export-status/:token → Tasks/TaskExportStatus.vue                 (no perm)
```

`nav.config.js`: Tasks entries already exist for the showcase; confirm the four
delayed + scan + missing + unused sub-items map to the routes above.

### Empty / loading / error states

- DataTable `:loading` driven by `useDataTable`; `EmptyState` when `meta.total=0`
  (the default 30-day window means an "empty" state should hint at widening the
  date filter).
- Forms: disable submit while pending; surface 422 errors on each field.
- Scan/missing: clear success/failure toasts mirroring the Blade alert boxes.

---

## 4. Data / API contract

Base: `/app/api/tasks`. Add `Api\TasksApiController`.

### List — `GET /app/api/tasks`
Params (from `DataTable.@query` + filters): `q` (→ exact `tasks.id`, matches the
Blade `keyword`), `sortKey` (mapped to `created_at|updated_at|collection_date`
only; default `created_at`), `sortDir`, `page`, `pageSize`, plus
`status`, `driver_id`, `billing_client`, `from_location`, `to_location`,
`search_date` (`tasks.created_at|collection_date`), `date_from`, `date_to`.
Reuse the **exact `index()` query** (client scoping, 30-day default, cached
count). Gate `task_access`.

Response (`{data, meta}`); `data[i]` keys match the showcase columns:
```json
{ "data": [ {
  "seq": 1, "id": 10428, "orderDate": "2026-06-27 08:10",
  "client": "King Faisal Lab", "driver": "Mohammed Al-Harbi",
  "from": "King Faisal Lab", "to": "Central Hub", "eta": "12m",
  "collection": "08:10", "container": "09:05", "containerOut": "11:00",
  "close": "12:30", "status": "NEW", "type": "SAMPLE",
  "addedBy": "system@mtc", "hours": "01:20",
  "driver_id": 12, "from_id": 4, "to_id": 9, "billing_client": 4
} ],
  "meta": { "total": 1280, "page": 1, "pageSize": 25 } }
```
Pre-format dates/hours/status server-side (parity with Yajra output). Keep raw
ids for row links/actions.

### Delayed lists — `GET /app/api/tasks/delayed/{variant}`
`variant ∈ pickup|drop|collected|outfreezer`. Reuse the matching controller
method's `whereRaw` filter verbatim:
- pickup → `pickup_time < collection_date`
- drop → `dropoff_time < close_date`
- collected → `TIMESTAMPDIFF(MINUTE, collection_date, NOW()) > 10 AND status='COLLECTED'`
- outfreezer → `TIMESTAMPDIFF(MINUTE, freezer_out_date, NOW()) > 5 AND status='OUT_FREEZER'`
Same filter params + extra `delayed_reason`. Columns add: Task Type, Added By,
Close/Collection Date, Hours, Freezer/Freezer-Out Date, Delayed Reason,
Created At. Gate `task_access`.

### Unused — `GET /app/api/tasks/unused`
Reuse `unUsedTasks()` query (`is_unused=true`, driver `status=1`). Params:
`client_id`, `driver_id`, `date_from`, `date_to`. Columns: ID, Order Date,
Client, Driver, From, To. Gate `unused_tasks`.

### Options — `GET /app/api/tasks/options`
Returns `{ clients, drivers, from_locations, to_locations, cars, statuses,
types, task_types }` as `[{value,label}]`, client-scoped exactly as
`create()`/`index()` build them. `from_locations` uses the controller's
`formatLocationOption()` (name — city — neighborhood).

### Detail — `GET /app/api/tasks/{id}`
Reuse `show()` data: task (+from/to/client/driver/car), `bags` (grouped by
`bag_code`), `bag_count`, `sample_count`, `carTracking` aggregate, and the chart
series `{labels, temp1, temp2, temp3}`. Gate `task_show`. (Covers both `show`
and `newshow`.)

### Create — `POST /app/api/tasks`  → reuse **`StoreTaskRequest`**, gate `task_create`
Body: `from_location[]`, `to_location`, `billing_client`, `driver_id`, `type`,
`task_type`, `pickup_time`, `dropoff_time`, `takasi`, `time_of_visit`.
The API method must call the **same store body** (the visit×location loop +
FCM + `CalculateDriverETAJob`). Success `201 {data:{created:n}}`. 422 → field
errors.

### Update — `PUT /app/api/tasks/{id}`  → reuse **`UpdateTaskRequest`**, gate `task_edit`
Body: `from_location`, `to_location`, `billing_client`, `driver_id`, `task_type`,
`status`, `takasi`. Must run the **same CLOSED-transition side effects + LogData
dispatch**. `200 {data:{...}}`.

### Update times — `PUT /app/api/tasks/{id}/times`  → gate `task_edit_times`
Body: `freezer_out_date?`, `close_date?` (nullable dates). Mirrors `updateTimes`.

### Delete — `DELETE /app/api/tasks/{id}` (`can-delete`) ·
Mass delete — `DELETE /app/api/tasks` `{ids:[]}` → **`MassDestroyTaskRequest`** +
`can-delete`.

### Exports — DO NOT reimplement
The SPA **links/POSTs to the existing routes** and then routes the user to
`TaskExportStatus.vue` (`/admin/tasks/export-status/:token`):
- **PDF:** `POST admin.reportExport` (or `admin.tasks.export`) with the current
  filter set → server redirects to the status URL; SPA reads the token from the
  redirect Location (or do the POST as a normal browser form submit and let the
  existing `export-pending` flow run, then return to SPA). Simplest: native form
  POST in a hidden iframe/new tab to keep the queued flow untouched.
- **Excel:** `GET admin.tasks.export-excel?<filters>` → same status redirect.
- **Status polling:** `TaskExportStatus.vue` polls
  `GET admin.tasks.export.status?token=…&status=1` (returns
  `{state: pending|ready|error, download, count}`) and, on `ready`, navigates to
  the `download` URL. Reuses the **exact** controller JSON contract.

### Scan / missing — call existing sample APIs unchanged
`TaskScan.vue` / `TaskMissing.vue` post to the same `/api/...` sample endpoints
the Blade screens use. Barcode capture: reuse onscan.js/Scandit, or accept
manual keyboard entry (camera scanning can be a fast-follow). No new backend.

### Validation surfacing
422 `{message, errors:{field:[...]}}` mapped onto `FormInput`/`FormSelect`
`error` props (foundation §2). Note `to_location` `different:from_location`
and `time_of_visit` bounds will appear as field errors.

---

## 5. Migration steps (ordered, checkable)

- [ ] Backend: `Api\TasksApiController` + routes in `routes/app_api.php`:
      list, delayed/{variant}, unused, options, show, store (reuse
      `StoreTaskRequest`), update (reuse `UpdateTaskRequest`), times, destroy,
      massDestroy (reuse `MassDestroyTaskRequest`). Reuse existing queries +
      Gates + the store/update side-effect code paths verbatim.
- [ ] Backend: confirm export routes (`admin.reportExport`,
      `admin.tasks.export-excel`, `admin.tasks.export.status`) are reachable
      from the SPA origin; no changes.
- [ ] Frontend: port `TasksList.vue` from vue-build → wire `useDataTable`,
      options fetch, delete/bulk-delete to API, export buttons to existing routes.
- [ ] Frontend: `TaskCreate.vue` (multi from-location), `TaskEdit.vue`
      (with status), `TaskShow.vue` (timeline + Chart.js temps + Edit-Times
      modal), `TasksDelayed.vue` (variant), `TasksUnused.vue`,
      `TaskExportStatus.vue`, `TaskScan.vue`, `TaskMissing.vue`.
- [ ] Wire router routes + perms + nav entries.
- [ ] Parity test vs each Blade screen: same columns/order, same 30-day default,
      same client scoping, same status badges, same validation errors, same
      exports producing identical files, RTL correct.
- [ ] Cutover: flip nav from `/admin/...` Blade routes to `/app/...`. Keep Blade
      for rollback.

---

## 6. Risks / must-not-break

- **Status transitions:** the CLOSED side effects (close_date stamp,
  `closed_by`, `to_location_confirmation_timestamp`, `LogData`/Blazma dispatch)
  and the model `booted()` ETA job must run from the API `update`. Easiest:
  have the API method call into the same logic, not a re-implementation.
- **Create loop + FCM + ETA job:** preserve the `time_of_visit × from_location[]`
  multiplication and per-task notifications. Multi-select `from_location` must
  post an array.
- **Client scoping (fail-closed):** non-admin/no-client users must see/export
  nothing. Don't drop the `assigned_client_ids` / admin-role checks in the API.
- **30-day default window:** removing it would scan the whole table; keep it for
  list + both exports.
- **Sort whitelist:** only the 3 allowed columns; map `sortKey` accordingly.
- **Exports stay queued + token-polled** — do not switch to synchronous; do not
  rebuild the report SQL/dompdf. Files must be byte-identical.
- **Scan/missing** keep calling the existing sample APIs and gates; barcode
  hardware behavior (onscan/Scandit) must keep working for warehouse readers.
- **`newshow`** is an alternate show route still linked elsewhere — keep an API
  path that serves the same payload.

---

## 7. Out of scope / open questions

- Rebuilding the **Scandit camera** scanner in Vue (license + SDK wiring) —
  recommend reusing the existing JS or deferring; manual entry covers parity.
- **Google Maps** create-screen location picker is commented out in Blade —
  treat as not-present unless reactivated.
- Should the SPA POST exports via hidden form (keeps queued redirect intact) or
  should we add a tiny JSON endpoint that returns just the `token`? Decide during
  foundation; either reuses the same job + status route.
- `exportExcel` (synchronous) is unrouted/dead — confirm before deleting; not
  migrated.
- Confirm whether `task_delete` (render gate) vs `can-delete` (API authorize)
  should both gate the delete button; foundation says deletion = `can-delete`.
