# Audit Logs — Frontend Migration Plan

> Read [`01-foundation.md`](01-foundation.md) first. Conforms to & references the
> shared `/app/api` JSON layer, **Yajra server-side DataTable** contract,
> Gate reuse, `{data,meta}` envelope, and RTL/i18n bridge — not repeated.
> **Presentation only.** Audit Logs is **read-only** (index + show); there is no
> create/store/edit/update/destroy. Do not add write paths.

---

## 1. Module overview

"Audit Logs" — a read-only, server-side-paginated view of system activity
records (`AuditLog` model). Nav group **Audit Logs** (`nav.config.js`:
`{ label:'Audit Logs', route:'/admin/audit-logs', perm:'audit_access' }`).

> ⚠️ **Gate-name mismatch (flag):** `nav.config.js` uses `perm:'audit_access'`,
> but the **real backend gates** are **`audit_log_access`** (index) and
> **`audit_log_show`** (show), verified in `AuditLogsController`. The SPA route
> guard + permission seeding must use the real `audit_log_*` strings; the nav key
> should be reconciled (see §7).

---

## 2. Current implementation (Blade / Velzon)

### Routes (`routes/web.php` — registered **twice**, ~lines 59–60 and again ~183–184; both identical)
```php
Route::resource('audit-logs', 'AuditLogsController',
    ['except' => ['create','store','edit','update','destroy']]);
```
| Method | URI | Name | Action |
|---|---|---|---|
| GET | `/admin/audit-logs` | `admin.audit-logs.index` | `index` (also serves the **ajax JSON** for the table) |
| GET | `/admin/audit-logs/{audit_log}` | `admin.audit-logs.show` | `show` |

> Note: the resource is declared in two places — same name/URI, so effectively
> one route. No cleanup needed for the migration; just be aware.

### Controller (`AuditLogsController.php`)
- `index(Request)` — `Gate::denies('audit_log_access')` → 403.
  **Server-side Yajra** (`DataTables::of($query)` where
  `$query = AuditLog::query()->select('audit_logs.*')`), returned **only when
  `$request->ajax()`**; otherwise returns the Blade shell.
  - Adds `placeholder` + `actions` columns; `actions` renders
    `partials.datatablesActions` with `viewGate='audit_log_show'`,
    `editGate='audit_log_edit'`, `deleteGate='audit_log_delete'`,
    `crudRoutePart='audit-logs'` (edit/delete routes don't exist → only **view**
    is actionable in practice).
  - `editColumn` passthroughs for `id, description, subject_id, subject_type,
    user_id, host` (empty-string fallback). `rawColumns(['actions','placeholder'])`.
- `show(AuditLog)` — `Gate::denies('audit_log_show')` → 403; read-only detail.

### Blade views
| File | Purpose |
|---|---|
| `admin/auditLogs/index.blade.php` | Server-side ajax DataTable (`datatable-AuditLog`, `serverSide:true`, `ajax = route('admin.audit-logs.index')`). Columns: placeholder, **id, description, subject_id, subject_type, user_id, host, created_at**, actions. `order:[[1,'desc']]`, `pageLength:100`. |
| `admin/auditLogs/show.blade.php` | Read-only table: id, description, subject_id, subject_type, user_id, **properties**, host, created_at. |

### Special behaviors to PRESERVE
- **Server-side pagination/search/sort** (Yajra) — the dataset can be large.
- **Read-only**: no create/edit/delete. The `actions` cell effectively offers
  only **View** (edit/delete routes are excluded from the resource).
- `order:[[1,'desc']]` (newest id first), `pageLength:100`.
- `properties` field shown only on the detail screen.

---

## 3. Target design (Vue + Tailwind)

Directory `resources/js/vue/views/AuditLogs/`.

| Blade view | → Vue view | vue-build components |
|---|---|---|
| `index` | `AuditLogsList.vue` | `Breadcrumb`, `DataTable` **`:server-side="true"`** (uses foundation `useDataTable` + `{data,meta}`), `BaseButton`/icon for View, `EmptyState`, `usePermissions` |
| `show` | `AuditLogShow.vue` | `Breadcrumb`, `BaseCard` (read-only field rows incl. `properties`) |

- **`DataTable` in server-side mode** — it emits `@query
  {q,sortKey,sortDir,page,pageSize}`; bind to the foundation `useDataTable` →
  `GET /app/api/audit-logs`. Default sort `id desc`; default `pageSize` 100 (to
  match Blade `pageLength`).
- Columns: `id` (mono), `description`, `subject_id` (mono), `subject_type`,
  `user_id` (mono), `host` (mono, LTR), `created_at`. IDs/host get
  `mono` (LTR) per the component's bidi handling.
- **No bulk/select, no delete** — pass `:selectable="false"` and **no
  `bulkActions`**; row action = **View** only, gated by `can('audit_log_show')`.
- `show` is a read-only `BaseCard` (no edit affordance).
- Router routes: `/admin/audit-logs` (`meta.perm:'audit_log_access'`),
  `/admin/audit-logs/:id` (`meta.perm:'audit_log_show'`).
- nav entry exists (key `audit_access` — reconcile, see §7).
- States: skeleton (DataTable), `EmptyState`, toast on 403 via foundation
  interceptors.

---

## 4. Data / API contract

Base `/app/api/audit-logs` (new `Api\AuditLogsApiController`; **read-only**;
reuses the same query + Gates; **no web controller change**).

| Method | Path | Purpose | Reuses |
|---|---|---|---|
| GET | `/app/api/audit-logs` | server-side list (search/sort/paginate) | `audit_log_access`; same `AuditLog::query()->select('audit_logs.*')` |
| GET | `/app/api/audit-logs/{id}` | detail (show) | `audit_log_show` |

> **No options/store/update/destroy** — read-only module.

**List request** (foundation server-side params):
`?q=&sortKey=id&sortDir=desc&page=1&pageSize=100`.

**List response** (foundation `{data,meta}`):
```json
{ "data": [
    { "id": 1042, "description": "updated", "subject_id": 88,
      "subject_type": "App\\Models\\Task", "user_id": 7,
      "host": "10.0.0.3", "created_at": "2026-06-27 09:14:00" }
  ],
  "meta": { "total": 53210, "page": 1, "pageSize": 100 } }
```
> Keys match the DataTable `column.key`s. The api may reuse the Yajra builder
> internally (it already produces these columns + `created_at`) and reshape into
> `{data,meta}`, **or** run the equivalent `AuditLog::query()` with
> search/sort/paginate — either way the search/sort columns must match Blade
> (`id, description, subject_id, subject_type, user_id, host, created_at`).

**Detail response:**
```json
{ "data": { "id":1042, "description":"updated", "subject_id":88,
  "subject_type":"App\\Models\\Task", "user_id":7,
  "properties": { /* json */ }, "host":"10.0.0.3",
  "created_at":"2026-06-27 09:14:00" } }
```
> `properties` included **only** on detail (matches Blade). No form, so no `422`.

---

## 5. Migration steps (ordered, checkable)

- [ ] backend: `Api\AuditLogsApiController` with **index + show only**, reusing the existing `AuditLog::query()->select(...)` + `audit_log_access` / `audit_log_show` gates; emit `{data,meta}`; routes in `routes/app_api.php`.
- [ ] backend: support `q`/`sortKey`/`sortDir`/`page`/`pageSize`; default sort `id desc`, default pageSize 100 (parity).
- [ ] frontend: `AuditLogsList.vue` — `DataTable :server-side` via `useDataTable`, `:selectable="false"`, no bulkActions, View-only action.
- [ ] frontend: `AuditLogShow.vue` (read-only card incl. `properties`).
- [ ] wire router routes with **`audit_log_access` / `audit_log_show`** perms; reconcile nav key.
- [ ] parity test vs Blade: same columns, server-side search/sort/paginate, newest-first, 403 behavior, RTL; confirm **no** create/edit/delete affordances appear.
- [ ] cutover: point the Audit Logs nav item at `/app` route.

---

## 6. Risks / must-not-break

- **Read-only invariant:** never expose store/update/destroy here. The
  `actions` cell must offer **View only** (edit/delete routes don't exist).
- **Server-side data path:** the list must paginate/search/sort on the backend
  (Yajra parity) — do **not** load all audit rows client-side; the table can be
  huge.
- **Correct gates:** use `audit_log_access` (index) and `audit_log_show` (show) —
  NOT the nav's `audit_access`. Backend remains the authority.
- **Audit logging side effects:** this module *reads* the audit trail; ensure the
  api endpoints themselves don't generate spurious audit entries differently from
  the Blade GETs (read-only, no writes).
- Preserve `created_at` formatting and `properties` shown only on detail.

## 7. Out of scope / open questions

- **nav.config.js perm-key mismatch (flag):** `perm:'audit_access'` vs real
  `audit_log_access`/`audit_log_show`. Reconcile: update the nav key to
  `audit_log_access` (and seed that perm) so nav/route guards match the server.
  Decide in `01-foundation.md`; this plan assumes the real `audit_log_*` strings.
- The resource is registered twice in `web.php` (lines ~59–60 and ~183–184) — no
  functional impact; out of scope to clean up.
- **Permission-seeder gap (carry-over):** `audit_log_access`/`audit_log_show` may
  be among the ~36 missing UI-referenced permissions; test with a role that
  actually holds them. Don't "fix" the seeder inside this migration.
- `partials.datatablesActions` references `audit_log_edit`/`audit_log_delete`
  gates that have no routes — vestigial; the SPA simply omits those actions.
