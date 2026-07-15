# API Ayenati — Frontend Migration Plan

> Read [`01-foundation.md`](01-foundation.md) first. Yajra server-side
> `DataTable` contract, `{data,meta}`, permissions and RTL/i18n live there.
> This is a **read-only log** of calls to the external Ayenati integration. The
> Ayenati API integration itself (request building, response handling) lives
> elsewhere and **must not be touched** — this screen only displays the log.

---

## 1. Module overview

API Ayenati shows the audit log of outbound requests to the Ayenati shipment
service: the URL called, the raw response, and a response-flag status. It is a
`Route::resource` with `except create/store/edit/update/destroy` → **index +
show only**, backed by a **Yajra server-side** DataTable. Gate:
`api_ayenati_access`. Sits in `Settings`/integrations.

---

## 2. Current implementation (Blade / Velzon)

### Routes (`routes/web.php` ~224–225)
`Route::resource('api-ayenatis', 'ApiAyenatiController', ['except' => ['create','store','edit','update','destroy']]);`
| Method | URI | Name | Action |
|---|---|---|---|
| GET | `/admin/api-ayenatis` | `admin.api-ayenatis.index` | `index` (Yajra ajax) |
| GET | `/admin/api-ayenatis/{api_ayenati}` | `admin.api-ayenatis.show` | `show` |

### Controller actions → view data
- `index(Request)` — gate `api_ayenati_access`. On ajax: **Yajra server-side** `DataTables::of(ApiAyenati::query()->select('api_ayenatis.*'))`. Columns:
  - `id`
  - `api_url`
  - `response`
  - `response_flag` → mapped via `ApiAyenati::RESPONSE_FLAG_SELECT[$flag]` (enum→label)
  - `actions` (view/edit/delete via `partials.datatablesActions`, gates `api_ayenati_show`/`api_ayenati_edit`/`api_ayenati_delete` — but edit/delete routes don't exist due to `except`, so only `view` is live).
  - Standard order `[1,'desc']`, pageLength 100.
- `show(ApiAyenati)` — gate `api_ayenati_show` → detail.

### Blade views
| File | Purpose |
|---|---|
| `admin/apiAyenatis/index.blade.php` | Yajra ajax DataTable (`datatable-ApiAyenati`, `serverSide:true`) — columns: placeholder, id, api_url, response, response_flag, actions |
| `admin/apiAyenatis/show.blade.php` | Detail |
| `admin/apiAyenatis/{create,edit}.blade.php` | Exist but routeless (vestigial) |

### Gates
`api_ayenati_access` (index), `api_ayenati_show` (show). (`api_ayenati_edit`/`_delete` referenced in actions partial but routeless.)

### Form Requests
`StoreApiAyenatiRequest` / `UpdateApiAyenatiRequest` (`api_ayenati_create`/`_edit`, rules `[]`), `MassDestroyApiAyenatiRequest` — **all unused** in scope (no store/update/destroy routes registered).

### Special behaviors to PRESERVE
- **Server-side Yajra** pagination/search (large log table).
- `response_flag` enum → label mapping (`RESPONSE_FLAG_SELECT`) — must render the same labels.
- `api_url` and `response` are intrinsically LTR (URLs / JSON) → force `dir="ltr"`.
- **Read-only** — no actions that re-trigger Ayenati calls.

---

## 3. Target design (Vue + Tailwind)

### View mapping (under `resources/js/vue/views/ApiAyenati/`)
| Blade view | Vue view | vue-build components |
|---|---|---|
| `apiAyenatis/index` | `ApiAyenatiList.vue` | `Breadcrumb`, `FilterBar` (keyword), `DataTable` (server-side), `StatusBadge` (for response_flag), `BaseButton` (view), `EmptyState` |
| `apiAyenatis/show` | `ApiAyenatiShow.vue` | `BaseCard` (pretty-print response, LTR) |

- List is read-only: only a `view` row action; no Add/Edit/Delete (matches routeless reality).
- `response_flag` → `StatusBadge` driven by the same label set.
- `api_url`/`response` cells force `style="direction:ltr"`.

### Vue Router routes
- `/admin/api-ayenatis` → `ApiAyenatiList.vue` `{ perm: 'api_ayenati_access' }`
- `/admin/api-ayenatis/:id` → `ApiAyenatiShow.vue` `{ perm: 'api_ayenati_show' }`

### nav.config.js
Confirm `Settings → API Ayenati` (perm `api_ayenati_access`); cutover only.

### States
Empty → `EmptyState` ("No Ayenati calls logged"). Loading → `DataTable :loading`.

---

## 4. Data / API contract

| Method | Path | Purpose | Reuses |
|---|---|---|---|
| GET | `/app/api/api-ayenatis` | server-side list (`q`/`sortKey`/`sortDir`/`page`/`pageSize`) | `ApiAyenati::query()` + `api_ayenati_access` |
| GET | `/app/api/api-ayenatis/{id}` | detail | `api_ayenati_show` |

Row shape: `{ id, api_url, response, response_flag, response_flag_label }`
- `response_flag` = raw value; `response_flag_label` = `RESPONSE_FLAG_SELECT[flag]` (pre-mapped, identical to Blade).
- Detail: `{ data: { id, api_url, response, response_flag, response_flag_label, created_at } }`.

No `/options`, no mutation endpoints (none exist in Blade).

---

## 5. Migration steps (ordered, checkable)

- [ ] backend: `Api\ApiAyenatiApiController` (index/show) — reuse the same query + `RESPONSE_FLAG_SELECT` mapping + `api_ayenati_access`/`api_ayenati_show` gates.
- [ ] frontend: `ApiAyenatiList.vue` (server-side DataTable) + `ApiAyenatiShow.vue`.
- [ ] wire router (2 routes) + confirm nav perm.
- [ ] parity test: same columns, same response_flag labels, LTR url/response, server-side search.
- [ ] cutover nav.
- [ ] mark status in `00-README.md`.

---

## 6. Risks / must-not-break

- **Do not re-trigger Ayenati API calls** from this screen — it is a log viewer only. No "retry"/"resend" buttons (would be net-new and could double-call the external service / re-dispatch shipment notifications).
- Preserve server-side pagination (table can be large).
- Keep `response_flag` → label mapping identical; do not hardcode a different status enum in the SPA.
- LTR direction on URL/JSON cells.

---

## 7. Out of scope / open questions

- `apiAyenatis/create.blade.php` + `edit.blade.php` and the Store/Update/MassDestroy Form Requests exist but are routeless — vestigial. Migrating them would be net-new write functionality against an external-integration log table; out of scope. Confirm with product owner whether manual creation/editing of Ayenati log rows is ever intended (likely not).
- The shipment-side Ayenati actions (`shipments.updateNotification`, `assignDriver`, `deliver` in `routes/web.php`) are part of the **Shipments** module, not this one — see the Shipments plan.
