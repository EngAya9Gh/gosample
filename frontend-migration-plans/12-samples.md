# Samples + Lost Samples — Frontend Migration Plan

> Read [`01-foundation.md`](01-foundation.md) first. This plan conforms to the
> shared `/app/api` JSON layer, the `{data,meta}` envelope, the server-side
> `DataTable` contract, 422→form-field mapping, permission rendering, and
> RTL/i18n bridge defined there. It does **not** repeat them.

---

## 1. Module overview

Samples are the lab specimens attached to tasks. The module is **read-heavy**:
two server-side DataTables (all samples + lost samples) plus a detail view.
`create`/`edit`/`store`/`update`/`destroy` routes exist on the resource but the
list UI does not surface Add/Edit (its row actions point at `sample_showw` /
`sample_editw` gates which are typically not granted); the primary surfaced
operations are **list → show**, **lost list**, and **mass delete**.

- Nav group: **Tasks** (`Tasks` nav group, alongside tasks/swap/shipments).
- Routes live under `/admin` prefix, name prefix `admin.`.
- Gate for access: **`sample_access`**.

---

## 2. Current implementation (Blade / Velzon)

### Routes (`routes/web.php` ~147–156)

| Method | URI | Name | Controller@action |
|---|---|---|---|
| DELETE | `/admin/samples/destroy` | `admin.samples.massDestroy` | `SamplesController@massDestroy` |
| GET | `/admin/samples` | `admin.samples.index` | `SamplesController@index` |
| GET | `/admin/samples/create` | `admin.samples.create` | `SamplesController@create` |
| POST | `/admin/samples` | `admin.samples.store` | `SamplesController@store` |
| GET | `/admin/samples/{sample}` | `admin.samples.show` | `SamplesController@show` |
| GET | `/admin/samples/{sample}/edit` | `admin.samples.edit` | `SamplesController@edit` |
| PUT/PATCH | `/admin/samples/{sample}` | `admin.samples.update` | `SamplesController@update` |
| DELETE | `/admin/samples/{sample}` | `admin.samples.destroy` | `SamplesController@destroy` |
| GET | `/admin/lost` | `admin.samples.lost` | `SamplesController@lost` |

> `massDestroy` is declared **before** the resource so `/samples/destroy` is not
> swallowed by `/samples/{sample}`. Preserve that ordering when adding API routes.

> NOTE: `samples/types/report` and the `pickupdelayed`/`dropdelayed`/etc. routes
> nearby belong to **TasksController** — out of scope for this plan.

### Controller actions + data passed

- **`index(Request)`** — `abort_if(Gate::denies('sample_access'))`. On
  `$request->ajax()`: builds a Yajra **server-side** DataTable from
  `Sample::with(['location','task','container','task.driver'])` left-joined to
  `tasks`. Applies the logged-in user's `assigned_client_ids` scope
  (`whereIn('billing_client', …)`). Filters: `date_from`+`date_to` (between
  `samples.created_at`), `barcode_id` (exact), `confirmed_by_client` (exact),
  `task_id` (exact). Caches the **total count** per user for 10 min
  (`samples_total_count_user_{id}`, `Cache::remember(…, 600)`) and calls
  `setTotalRecords($totalRecords)`. Non-ajax → `view('admin.samples.index')`.
- **`lost(Request)`** — same gate; ajax DataTable of
  `Sample::where('samples.confirmed_by_client','LOST')` (the lost filter is
  hardcoded), same `assigned_client_ids` scope + same optional filters. Non-ajax
  → `view('admin.samples.lost')`.
- **`show(Sample)`** — `abort_if(Gate::denies('sample_show'))`; loads
  `location, task, container`; `view('admin.samples.show')`.
- **`create` / `edit`** — gates `sample_create` / `sample_edit`; build
  `locations`, `tasks` (Task `collect_lat` plucked by id — odd but verbatim),
  `containers` selects.
- **`store(StoreSampleRequest)`** — `Sample::create($request->all())` → redirect.
- **`update(UpdateSampleRequest, Sample)`** — `$sample->update($request->all())`.
- **`destroy(Sample)`** — `abort_if(Gate::denies('sample_delete'))`; `delete()`.
- **`massDestroy(MassDestroySampleRequest)`** — `abort_if(Gate::denies('sample_delete'))`;
  `Sample::whereIn('id', request('ids'))->delete()` → 204.

### Blade views

| View | Purpose |
|---|---|
| `resources/views/admin/samples/index.blade.php` | All-samples list: filter form (date_from/date_to flatpickr `data-mf-date`, `barcode_id` text, `confirmed_by_client` Select2 from `RECEIVING_STATUS_SELECT`) + server-side DataTable (`serverSide:true`) |
| `resources/views/admin/samples/lost.blade.php` | Lost-samples list (same shape, `confirmed_by_client='LOST'` enforced server-side) |
| `resources/views/admin/samples/show.blade.php` | Read-only detail table |
| `resources/views/admin/samples/create.blade.php` | Create form (low priority — see overview) |
| `resources/views/admin/samples/edit.blade.php` | Edit form (low priority) |

### Index DataTable columns (order as rendered)

`placeholder` (checkbox), `id`, `location_name` (`location.name`), `to_location`
(`task.to.name`), `task_id` (rendered as a link button to `admin.tasks.show`),
`barcode_id`, `driver_id` (`task.driver.name`), `collection_date`
(`task.collection_date`), `close_date` (`task.close_date`),
`confirmed_by_client`, `actions`.

Lost DataTable also exposes: `container_imei`, `box_count`, `sample_count`,
`confirmed_by`, `status` (via `Sample::STATUS_SELECT`), `sample_type`,
`temperature_type`, `bag_code`.

### Permissions / Gates

- Access (index/lost): **`sample_access`**.
- Show: **`sample_show`**. Mass delete + destroy: **`sample_delete`**.
- Row-action gates in the list partial are `sample_showw`, `sample_editw`,
  `sample_delete` (note the doubled `w` — preserve verbatim; do not "fix").
- Mass-delete checkbox column is gated in Blade by **`@can('can-delete')`**.

### Form Requests (reuse verbatim)

- `StoreSampleRequest` / `UpdateSampleRequest` — all fields `string|nullable`:
  `barcode, box_count, sample_count, confirmed_by_client, confirmed_by,
  sample_type, temperature_type, bag_code`. (Note: form posts `barcode` but list
  filters on `barcode_id` — distinct columns.)
- `MassDestroySampleRequest` — `ids required|array`, `ids.* exists:samples,id`,
  `authorize()` = `sample_delete`.

### Status enums (PRESERVE exactly)

- `Sample::STATUS_SELECT = ['1'=>'enabled','2'=>'disabled']` (used in the `status`
  column display).
- `Sample::RECEIVING_STATUS_SELECT = ['YES'=>'RECEIVED','NO'=>'PENDING','LOST'=>'LOST']`
  (drives the `confirmed_by_client` filter dropdown). The lost list filters on
  the **raw** value `'LOST'`.

### Special behaviors to preserve

- **Server-side DataTables** (both lists) — must paginate/search/sort on the
  backend.
- **`assigned_client_ids` row-level scoping** — a client user only sees their
  samples. This is a security boundary, not cosmetic.
- **10-minute total-count cache** keyed per user.
- No exports, no maps, no charts in this module.

---

## 3. Target design (Vue + Tailwind)

### Page mapping

| Blade view | New Vue view | Components |
|---|---|---|
| `samples/index.blade.php` | `resources/js/vue/views/Samples/SamplesList.vue` | `Breadcrumb`, `FilterBar`, `FormInput`, `FormSelect`, `DataTable`, `StatusBadge`, `BaseButton`, `BaseModal`, `EmptyState` |
| `samples/lost.blade.php` | `resources/js/vue/views/Samples/LostSamplesList.vue` | same set (reuse a shared `<SamplesTable>` sub-component) |
| `samples/show.blade.php` | `resources/js/vue/views/Samples/SampleShow.vue` | `Breadcrumb`, `BaseCard`, `StatusBadge`, `BaseButton` |
| `samples/create/edit` | `resources/js/vue/views/Samples/SampleForm.vue` (optional, low priority) | `Breadcrumb`, `BaseCard`, `FormInput`, `FormSelect`, `BaseButton` |

Follow `views/Tasks/TasksList.vue` as the list pattern: `Breadcrumb` + `FilterBar`
(slotted filter inputs) + server-side `DataTable` with `#cell-*` slots,
`#row-actions` gated by `usePermissions().can(...)`, and a `BaseModal` delete
confirm. Use `useDataTable` (foundation §6) to bridge `@query` → API.

- `LostSamplesList` is `SamplesList` minus the status filter and with the lost
  columns; prefer one shared table component parameterized by `mode: 'all' | 'lost'`.

### Custom cells

- `task_id` → render a router-link/`BaseButton` to the tasks show route (parity
  with the Blade link button).
- `confirmed_by_client` → `StatusBadge` mapping `RECEIVED`/`PENDING`/`LOST`.
- `status` → label via `STATUS_SELECT`.

### Vue Router

```js
{ path: '/admin/samples',      name: 'samples.index', component: () => import('../views/Samples/SamplesList.vue'),     meta: { perm: 'sample_access' } }
{ path: '/admin/lost',         name: 'samples.lost',  component: () => import('../views/Samples/LostSamplesList.vue'), meta: { perm: 'sample_access' } }
{ path: '/admin/samples/:id',  name: 'samples.show',  component: () => import('../views/Samples/SampleShow.vue'),      meta: { perm: 'sample_show' } }
```

### nav.config.js

Samples + Lost entries live under the **Tasks** nav group (perm `sample_access`).
Confirm the exact keys exist in the delivered `nav.config.js`; reuse, don't
invent.

### Empty / loading / error

- `DataTable :loading` while fetching; `EmptyState` when `meta.total === 0`
  (e.g. "No samples" / "No lost samples").
- 403 → route guard + backend gate; 422 only on the optional form.

---

## 4. Data / API contract

All under `/app/api`, auth+CSRF group (foundation §1). Reuse the **existing
Eloquent queries verbatim**, including the `assigned_client_ids` scope and the
count cache.

| Method | Path | Purpose | Reuses |
|---|---|---|---|
| GET | `/app/api/samples` | All-samples list (server-side) | `index` query + `sample_access` |
| GET | `/app/api/samples/lost` | Lost-samples list (server-side) | `lost` query + `sample_access` |
| GET | `/app/api/samples/{id}` | Detail | `show` load + `sample_show` |
| GET | `/app/api/samples/options` | selects for the optional form | `create`/`edit` plucks |
| POST | `/app/api/samples` | Create (optional) | `StoreSampleRequest` + `sample_create` |
| PUT | `/app/api/samples/{id}` | Update (optional) | `UpdateSampleRequest` + `sample_edit` |
| DELETE | `/app/api/samples/{id}` | Delete one | `sample_delete` |
| DELETE | `/app/api/samples` | Mass delete `{ids:[]}` | `MassDestroySampleRequest` + `sample_delete` |

### List request params

`q, sortKey, sortDir, page, pageSize` (foundation contract) **plus**:
`date_from`, `date_to`, `barcode_id`, `confirmed_by_client`, `task_id`.
(`/samples/lost` ignores `confirmed_by_client` — it is fixed to `LOST` server-side.)

### List response (envelope)

```json
{
  "data": [{
    "id": 123,
    "location_name": "King Faisal Lab",
    "to_location": "Central Hub",
    "task_id": 10428,
    "barcode_id": "BC-0098",
    "driver_id": "Mohammed Al-Harbi",
    "collection_date": "2026-06-27",
    "close_date": "2026-06-27 12:10",
    "confirmed_by_client": "RECEIVED"
  }],
  "meta": { "total": 1280, "page": 1, "pageSize": 25 }
}
```

Lost rows add: `container_imei, box_count, sample_count, confirmed_by, status,
sample_type, temperature_type, bag_code`. Keys must equal the Vue `columns[].key`.
Pre-format dates/labels exactly as the Blade closures do.

### Detail response

```json
{ "data": { "id":123, "barcode":"…", "barcode_id":"…", "box_count":"…",
  "sample_count":"…", "confirmed_by_client":"RECEIVED", "confirmed_by":"…",
  "status":"enabled", "sample_type":"…", "temperature_type":"…", "bag_code":"…",
  "location":{"id":…,"name":"…"}, "task":{"id":…,…}, "container":{"id":…,"imei":"…"} } }
```

### Validation surfacing

Optional form reuses `StoreSampleRequest`/`UpdateSampleRequest` → Laravel 422
`{message, errors:{field:[…]}}` mapped onto `Form*` `error` props.

---

## 5. Migration steps (ordered, checkable)

- [ ] backend: add `Api\SamplesApiController` (`index`, `lost`, `show`,
      `options`, `store`, `update`, `destroy`, `massDestroy`) reusing the
      existing queries + `assigned_client_ids` scope + count cache + the four
      Form Requests + the same gates.
- [ ] backend: register routes in `routes/app_api.php` with `/samples/lost` and
      `DELETE /samples` declared **before** `/samples/{id}` (ordering parity).
- [ ] frontend: build `SamplesList.vue` + `LostSamplesList.vue` (shared table) +
      `SampleShow.vue` from `vue-build` components.
- [ ] frontend: wire `useDataTable`, filters, `StatusBadge` enum maps, task link.
- [ ] wire router routes + confirm Tasks-group nav entries + perms.
- [ ] parity test vs Blade: same rows, same client scoping, same filters, same
      count, same gates hiding the same actions, RTL correct.
- [ ] flip nav Samples + Lost from `/admin/...` Blade to `/app` routes (cutover).
- [ ] mark status in `00-README.md`.

---

## 6. Risks / must-not-break

- **`assigned_client_ids` scoping** — must be applied identically; dropping it
  leaks other clients' samples. Security-critical.
- **Total-count cache** — reuse the same key/TTL so paginator totals match;
  don't recompute differently.
- **Lost filter** — `confirmed_by_client='LOST'` is enforced server-side; the
  SPA must never weaken it.
- **Gate names verbatim** — `sample_showw`/`sample_editw` (double `w`) and the
  `@can('can-delete')` checkbox gate; do not "correct" them.
- **Enum displays** — `STATUS_SELECT` and `RECEIVING_STATUS_SELECT` labels must
  match Blade output exactly.
- **Route ordering** — keep `massDestroy`/`lost` before the `{sample}` wildcard.

---

## 7. Out of scope / open questions

- Create/Edit forms: surfaced only if `sample_create`/`sample_editw` are actually
  granted to any role. Default plan treats them as optional/low-priority.
- `barcode` (form) vs `barcode_id` (list/filter): confirm whether the SPA form
  should write `barcode_id` too — keep current behavior unless product says
  otherwise.
- Confirm whether the Tasks nav already links Samples + Lost or if a new entry is
  required.
