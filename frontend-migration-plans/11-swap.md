# Swap (Swap Requests + Task Swap) — Frontend Migration Plan

> Read [`01-foundation.md`](01-foundation.md) first. Conforms to the JSON API
> layer, server-side `DataTable` contract, `{data,meta}` envelope, 422→field
> mapping, `/options`, and RTL/i18n. We reuse the controllers' queries, the
> **`StoreSwaprequestRequest` / `UpdateSwaprequestRequest` /
> `MassDestroySwaprequestRequest`** Form Requests, the Gates, and the
> **existing swap export routes**. No business logic changes — the swap-creation
> fan-out and the swapped-task report SQL stay as-is.

This file covers **two** controllers:
1. `Admin\SwaprequestController` — CRUD over **swap requests** (reassign tasks
   from one driver to another; entity = `App\Models\Swap`, table `swap_requests`).
2. `Admin\TaskSwapController` — a **read-only report** of tasks that were swapped
   (`tasks.is_swap = true`) + its Excel/PDF exports.

---

## 1. Module overview

- **Swap requests:** list / create / edit / show / delete reassignment requests.
  A request moves one or more tasks from `driver_a` (old) to `driver_id` (new),
  with a `status` (new / accepted / rejected).
- **Task swap report:** a filtered list of tasks already swapped, with PDF + Excel
  export — analogous to the Tasks report but scoped to `is_swap=1`.

- **Nav group:** Tasks.
- **Routes / gates:**
  - Swap requests: `/admin/swaprequests` · `swaprequest_access`
    (+ `swaprequest_create/edit/show/delete`).
  - Task swap report: `/admin/swap-tasks` · gate `task_access` (reused).

---

## 2. Current implementation (Blade / Velzon)

### Routes

**SwaprequestController** (`swaprequests` resource):

| Method | URI | Name | Action | Gate |
|---|---|---|---|---|
| GET | `admin/swaprequests` | `admin.swaprequests.index` | `index` (ajax) | `swaprequest_access` |
| GET | `admin/swaprequests/create` | `admin.swaprequests.create` | `create` | `swaprequest_create` |
| POST | `admin/swaprequests` | `admin.swaprequests.store` | `store(StoreSwaprequestRequest)` | `swaprequest_create` |
| GET | `admin/swaprequests/{swaprequest}` | `admin.swaprequests.show` | `show` | `swaprequest_show` |
| GET | `admin/swaprequests/{swaprequest}/edit` | `admin.swaprequests.edit` | `edit` | `swaprequest_edit` |
| PUT/PATCH | `admin/swaprequests/{swaprequest}` | `admin.swaprequests.update` | `update(UpdateSwaprequestRequest)` | `swaprequest_edit` |
| DELETE | `admin/swaprequests/{swaprequest}` | `admin.swaprequests.destroy` | `destroy` | `swaprequest_delete` |
| DELETE | `admin/swaprequests/destroy` | `admin.swaprequests.massDestroy` | `massDestroy(MassDestroySwaprequestRequest)` | `swaprequest_delete` |

**TaskSwapController** (swapped-task report):

| Method | URI | Name | Action | Gate |
|---|---|---|---|---|
| GET | `admin/swap-tasks` | `admin.swapTask.index` | `index` (ajax) | `task_access` |
| GET | `admin/swap-tasks/{taks}` | `admin.swapTask.show` | `index` (same; param ignored) | `task_access` |
| GET | `admin/swap-export-excel` | `admin.swapTask.export-excel` | `exportExcelDetails` (Excel direct) | — |
| POST | `admin/swap-task-report` | `admin.swapReportExport` | `export` (PDF, dompdf direct) | — |

> Note: the live `index()` in TaskSwapController is the **second** definition
> (the first is `index_old`, unused). The live one uses raw joins
> (`drivers/cars status=1`, `is_swap=true`) and returns Yajra with **no column
> transforms** — the Blade builds columns client-side from the raw model fields.
> `show($task)` in TaskSwapController exists but the route maps `swapTask.show`
> to `index` (so it just re-lists). There is also `exportExcel` (a heavy
> synchronous GROUP_CONCAT export) that is **not routed** — dead code.

### What each action passes

- **Swaprequest index (ajax):** `Swap::with(task,driver,driverA)`; Yajra
  computed: `actions`, `task_id`, `task.status`, `driver_name`, `driverA`
  (=`driverA.name`), and a **status badge** (new→bg-primary, accepted→bg-success,
  rejected→bg-danger). Filters: `date_from/date_to` (created_at), `driver_id`,
  `task_id`. Non-ajax: `admin.swaprequests.index`.
- **Swaprequest create:** passes `drivers` (pluck name/id). The form's task list
  is loaded **via AJAX** when `driver_a` changes (`POST /api/swap/tasks/list`).
- **Swaprequest store:** forces `status='new'`; rejects if
  `driver_id == driver_a` (re-renders create with error); normalizes `task_id`
  to an array and **creates one Swap row per task_id**. PRESERVE the fan-out.
- **Swaprequest edit/update:** edit single-task select; update is a plain
  `$swaprequest->update($request->all())`.
- **Swaprequest destroy/massDestroy:** authorize `swaprequest_delete`; delete.
- **TaskSwap index (ajax):** raw-join query, `is_swap=true`, `drivers.status=1`,
  `cars.status=1`, client-scoped; filters: `status, driver_id, billing_client,
  from_location, to_location, keyword (=tasks.id), search_date, date_from,
  date_to`; sort whitelist `created_at|updated_at|collection_date`.
- **TaskSwap exportExcelDetails:** `Excel::download(new
  TaskSwapTimeReportExport(status,date_from,date_to,billing_client,
  from_location,to_location,driver_id))` → `task_time_report.xlsx`. Synchronous.
- **TaskSwap export:** builds a GROUP_CONCAT SQL over `is_swap=1` tasks, computes
  temperature/bag summaries, renders `report_template` → **dompdf** A3 landscape
  stream. Synchronous.

### Blade views

| File | Purpose | Table |
|---|---|---|
| `swaprequests/index.blade.php` | Swap requests list + filters + mass delete (SweetAlert2) | server-side ajax |
| `swaprequests/create.blade.php` | Create: `driver_a`→AJAX task list→`task_id[]`→`driver_id`→`status` | — |
| `swaprequests/edit.blade.php` | Edit (plain selects: task_id, driver_a, driver_id, status) | — |
| `swaprequests/show.blade.php` | Read-only key/value (id, task, driver, status) | — |
| `tasks/swap.blade.php` | Swapped-task report + filters + PDF/Excel export | server-side ajax (`admin.swapTask.index`) |

### Form Request rules (PRESERVE — reuse via API)

- **`StoreSwaprequestRequest`** (`swaprequest_create`): `task_id` required
  **array**, `task_id.*` integer; `driver_id` required integer; `status`
  required. (`store()` additionally forces `status='new'` and the
  `driver_id != driver_a` business check.)
- **`UpdateSwaprequestRequest`** (`swaprequest_edit`): `task_id` required
  integer (single); `driver_id` required integer; `status` required.
- **`MassDestroySwaprequestRequest`** (`swaprequest_delete`): `ids` required
  array, `ids.*` exists:swap_requests,id.

### Enums (`App\Models\Swap`)
- `STATUS_SELECT`: (new / accepted / rejected) — used in the create select and
  the index badge map.

### Behaviors to PRESERVE
- **One Swap row per task_id** on create; the `driver_id != driver_a` guard.
- The **AJAX driver→tasks** population (`POST /api/swap/tasks/list`) — keep
  calling it.
- TaskSwap report scoping (`is_swap`, `status=1` joins) + the two exports
  (Excel via maatwebsite, PDF via dompdf). Files must stay identical.

---

## 3. Target design (Vue + Tailwind)

### Swap requests — `resources/js/vue/views/Swaprequests/`

| Blade view | Vue view | components |
|---|---|---|
| `swaprequests/index.blade.php` | `SwaprequestsList.vue` | Breadcrumb, FilterBar, DataTable, FormInput, StatusBadge, BaseButton, BaseModal |
| `swaprequests/create.blade.php` | `SwaprequestCreate.vue` | BaseCard, FormSelect (driver_a), FormSelect multiple (task_id), FormSelect (driver_id), FormSelect (status), BaseButton |
| `swaprequests/edit.blade.php` | `SwaprequestEdit.vue` | BaseCard, FormSelect ×4, BaseButton |
| `swaprequests/show.blade.php` | `SwaprequestShow.vue` | BaseCard, StatusBadge, Breadcrumb |

- Create: on `driver_a` change, call the swap-tasks lookup to populate the
  `task_id` multi-select (shows count, like `#task_message`). Block submit when
  `driver_id == driver_a` (client UX) — backend still enforces.

### Task swap report — `resources/js/vue/views/Tasks/`

| Blade view | Vue view | components |
|---|---|---|
| `tasks/swap.blade.php` | `TaskSwapReport.vue` | Breadcrumb, FilterBar, DataTable, FormSelect, FormInput, StatusBadge, BaseButton |

Columns (from swap.blade): ID, Order Date, Client, From Location, To Location,
Old Driver, Driver, Collection Date, Freezer Date, Swap Freezer Date, Freezer
Out, Swap Freezer Out, Close Date, Status. Export buttons (PDF/Excel) link/POST
to the **existing** routes.

### Vue Router routes

```
/admin/swaprequests          → Swaprequests/SwaprequestsList.vue   meta.perm: swaprequest_access
/admin/swaprequests/create   → Swaprequests/SwaprequestCreate.vue  meta.perm: swaprequest_create
/admin/swaprequests/:id      → Swaprequests/SwaprequestShow.vue    meta.perm: swaprequest_show
/admin/swaprequests/:id/edit → Swaprequests/SwaprequestEdit.vue    meta.perm: swaprequest_edit
/admin/swap-tasks            → Tasks/TaskSwapReport.vue            meta.perm: task_access
```

---

## 4. Data / API contract

### Swap requests — base `/app/api/swaprequests` (`Api\SwaprequestApiController`)

- **List** `GET /app/api/swaprequests` — params `q`, `sortKey`, `sortDir`,
  `page`, `pageSize`, `date_from`, `date_to`, `driver_id`, `task_id`. Reuse
  `index()` query. Gate `swaprequest_access`. `data[]`: `id, task_id,
  task_status, driver_name, driverA, status` (+ raw ids). `{data,meta}`.
- **Options** `GET /app/api/swaprequests/options` — `{ drivers, statuses }`
  (`[{value,label}]`).
- **Tasks-for-driver** — reuse the existing `POST /api/swap/tasks/list`
  (`{driver_id}` → task list); map to `[{value:id,label:id}]` for the multi-select.
  (Do not rebuild; it's the same endpoint the Blade uses.)
- **Detail** `GET /app/api/swaprequests/{id}` — reuse `show()` (task, driver,
  status). Gate `swaprequest_show`.
- **Create** `POST /app/api/swaprequests` → **`StoreSwaprequestRequest`**, gate
  `swaprequest_create`. Body: `task_id[]`, `driver_a`, `driver_id`, `status`.
  API must run the same `store()` logic: force `status='new'`, reject
  `driver_id==driver_a` (return 422 on a `driver` key to match the Blade
  `withErrors(['driver'=>...])`), create **one Swap per task_id**. `201`.
- **Update** `PUT /app/api/swaprequests/{id}` → **`UpdateSwaprequestRequest`**,
  gate `swaprequest_edit`. Body: `task_id`, `driver_id`, `status`. `200`.
- **Delete** `DELETE /app/api/swaprequests/{id}` (`swaprequest_delete`) ·
  **Mass delete** `DELETE /app/api/swaprequests` `{ids:[]}` →
  **`MassDestroySwaprequestRequest`** (`swaprequest_delete`).

> Deletion here authorizes `swaprequest_delete` (NOT the global `can-delete`) —
> match the existing controller exactly; do not swap it to `can-delete`.

### Task swap report — base `/app/api/swap-tasks` (`Api\TaskSwapApiController`)

- **List** `GET /app/api/swap-tasks` — params `q`(=tasks.id), `sortKey`
  (whitelist `created_at|updated_at|collection_date`), `sortDir`, `page`,
  `pageSize`, `status`, `driver_id`, `billing_client`, `from_location`,
  `to_location`, `search_date`, `date_from`, `date_to`. Reuse the live
  `index()` raw-join query (`is_swap=true`, `drivers.status=1`, `cars.status=1`,
  client scoping). Gate `task_access`. `data[]` keys per the swap.blade columns
  (id, order_date, client_name, from_location_name, to_location_name,
  old_driver_name, driver_name, collection_date, freezer_date, swap_freezer_date,
  freezer_out_date, swap_freezer_out, close_date, status) + raw ids. `{data,meta}`.
- **Options** `GET /app/api/swap-tasks/options` — `{ clients, drivers,
  from_locations, to_locations, statuses }` (client-scoped, like Tasks).
- **Exports — DO NOT reimplement.** Link/POST to the existing routes:
  - **Excel:** `GET admin.swapTask.export-excel?<filters>` (incl. sort_by/
    sort_order) → `TaskSwapTimeReportExport` xlsx (synchronous download).
  - **PDF:** `POST admin.swapReportExport` with the filter set → dompdf A3 stream
    (synchronous). Use a native form/new-tab submit so the file downloads
    directly, identical to today.

No mutations on the swap report (read-only). No detail endpoint needed —
`swapTask.show` just re-lists.

### Validation surfacing
422 `{message,errors}` → fields. The `driver_id==driver_a` business rule must
surface as a field error (recommend key `driver` to mirror Blade); document the
chosen key so the form binds it.

---

## 5. Migration steps (ordered, checkable)

- [ ] Backend: `Api\SwaprequestApiController` — list, options, detail, create
      (reuse `StoreSwaprequestRequest` + the `store()` fan-out + driver guard),
      update (`UpdateSwaprequestRequest`), destroy/massDestroy
      (`MassDestroySwaprequestRequest`). Keep `POST /api/swap/tasks/list` as-is.
- [ ] Backend: `Api\TaskSwapApiController` — list (reuse live `index()` query) +
      options. Confirm export routes reachable; no export changes.
- [ ] Frontend: `SwaprequestsList/Create/Edit/Show.vue` (create with
      driver→tasks AJAX + multi-select) and `TaskSwapReport.vue`.
- [ ] Wire router + perms + nav.
- [ ] Parity test: one Swap row per task on create; `driver_id==driver_a`
      blocked with the right error; swap report rows/columns match;
      PDF + Excel files byte-identical.
- [ ] Cutover nav; keep Blade for rollback.

---

## 6. Risks / must-not-break

- **Swap create fan-out:** N task_ids → N Swap rows; missing this silently
  drops swaps. Multi-select must post an array.
- **`driver_id != driver_a` guard** + forced `status='new'` on create — keep in
  the API method (Form Request doesn't enforce these).
- **Swap delete uses `swaprequest_delete`**, not `can-delete` — preserve.
- **TaskSwap report scoping:** `is_swap=true` + `drivers.status=1` +
  `cars.status=1` joins (the live `index`, not `index_old`) — using the wrong
  variant changes the row set.
- **Sort whitelist** on the swap report (3 columns) — map `sortKey`.
- **Exports stay synchronous + identical** (Excel `TaskSwapTimeReportExport`,
  PDF dompdf A3 `report_template`, incl. the mdlab `billing_client==26` summary
  branch). Do not rebuild the SQL.
- `swapTask.show` route → `index` (no real detail) — don't add a fake detail
  screen that breaks the existing behavior.

---

## 7. Out of scope / open questions

- **Swap acceptance side effects:** does changing a Swap `status` to
  `accepted` trigger task reassignment elsewhere (driver app / job)? The web
  `update()` is a plain `update($request->all())` with no visible side effect —
  confirm no observer/job keys off `swap_requests.status` before relying on it.
- `index_old` (TaskSwapController) and `exportExcel` (synchronous GROUP_CONCAT)
  are **dead/unrouted** — not migrated; confirm before deletion.
- The swap report PDF/Excel are synchronous and can be heavy; unlike the main
  Tasks exports they are **not** queued. Out of scope to change, but flag if
  timeouts appear — could later adopt the Tasks token-poll pattern.
- `report_template` and the export classes are shared with the main Tasks report
  area — any future change there affects both; keep presentation-only.
