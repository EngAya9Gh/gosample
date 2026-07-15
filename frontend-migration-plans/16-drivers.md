# Drivers — Frontend Migration Plan

> Read [`01-foundation.md`](01-foundation.md) first. This plan **conforms to** the
> shared API layer (`/app/api`, `{data,meta}` envelope, server-side DataTable
> contract, 422→form-field mapping, permission seeding, RTL/i18n, exports link to
> existing routes). It does not repeat those rules; it only states what is
> Drivers-specific.

---

## 1. Module overview

Drivers is the fleet's core CRUD plus two operational sub-features that **must not
change logic**:
- **Driver task reordering** — drag-drop priority sort + "smart sort" (shortest
  route) for a single driver's active tasks, recalculating ETA via a queued job.
- **Shift & schedule management** — up to 3 shifts per driver, synced into
  `driver_shifts` on store/update (`DriversController::syncDriverShifts`).

Nav group: **Drivers**. Gate: `driver_access`. Blade routes under `/admin/drivers`.

The list is a **server-side (ajax) DataTable** — it is one of the big tables
named in the foundation, so it uses `DataTable.vue` in `serverSide` mode.

---

## 2. Current implementation (Blade / Velzon)

### Routes (all inside the `admin.` prefix/namespace group unless noted)

| Method | URI | Name | Controller@action |
|---|---|---|---|
| DELETE | `admin/drivers/destroy` | `admin.drivers.massDestroy` | `Admin\DriversController@massDestroy` |
| GET | `admin/drivers/{id}/get-shifts` | `admin.drivers.getShifts` | `Admin\DriversController@getShifts` |
| POST | `admin/drivers/{driver}/tasks/reorder` | `admin.drivers.tasks.reorder` | `DriverController@reorderTasks` |
| POST | `admin/drivers/{driver}/tasks/smart-sort` | `admin.drivers.tasks.smartSort` | `DriverController@smartSortTasks` |
| GET/POST/PUT/DELETE… | `admin/drivers` (resource) | `admin.drivers.*` | `Admin\DriversController` |
| GET | `admin/drivers/{driver}/tasks` | `admin.drivers.tasks` | `DriverController@showTasks` (declared **outside** the admin group, web.php ~248) |
| POST | `admin/drivers/{driver}/tasks/reorder` | `admin.drivers.tasks.reorder` | `DriverController@reorderTasks` (duplicate declaration, web.php ~251) |

> Note: there are TWO declarations of the reorder route (one in the admin group
> at web.php ~65, one standalone at ~251). The `tasks.blade.php` calls
> `route('admin.drivers.tasks.reorder', $driver->id)` and
> `route('admin.drivers.tasks.smartSort', $driver->id)`. Preserve both behaviors;
> the SPA will call ONE canonical API endpoint (see §4).

### Controller actions → data passed

- `index(Request)` — `Gate::denies('driver_access')` guard. On `ajax()` returns a
  Yajra `DataTables::of(Driver::withoutGlobalScope('enabled')->select('drivers.*'))`.
  Server-side filters: `date_from`+`date_to` (whereBetween `created_at`), `mobile`
  (exact), `status` (exact, `1`=Enabled/`2`=Disabled). Computed/edited columns:
  `id, name, status` (badge HTML), `username, mobile, email, language`
  (LANGUAGE_SELECT), `lat, lng, accepted_terms`, `actions` (show/edit gated +
  delete form gated by `can-delete`), plus a `view_tasks` column rendered
  client-side as a link to `/admin/drivers/{id}/tasks`. Non-ajax → `view('admin.drivers.index')`.
- `create()` — `driver_create`. Passes `$zones` (`Zone::pluck('name','id')` +
  pleaseSelect) and `$shiftTemplates` (`ShiftTemplate::all()`).
- `store(StoreDriverRequest)` — `Driver::create($request->all())` then
  `syncDriverShifts()`.
- `edit($id)` — `driver_edit`. Passes `$driver` (withoutGlobalScope enabled),
  `$zones`, `$shiftTemplates`.
- `update(UpdateDriverRequest,$id)` — updates, logs payload, `syncDriverShifts()`.
- `show($id)` — `driver_show`. `$driver->load('driverCarLinkHistories','driverTasks')`.
- `destroy($id)` / `massDestroy(MassDestroyDriverRequest)` — `authorize('can-delete')`.
- `getShifts($id)` — returns JSON of `driver->shifts()->where('is_active',true)`.
- **`DriverController@showTasks($driverId)`** — loads `$driver->activeTasks()`
  joined to `locations` (from/to names), ordered by `tasks.poririty`; renders
  `admin.drivers.tasks`.
- **`DriverController@reorderTasks($driverId)`** — DB transaction: for each
  `order[]` item `{id, priority}` set `tasks.poririty = priority`, then
  `dispatch(new CalculateDriverETAJob($driverId))->afterResponse()`. Returns
  `{success:true}`.
- **`DriverController@smartSortTasks($driverId)`** — calls
  `app(DriverRouteService::class)->smartSortTasks($driverId)`. Returns `{success:true}`.

### Blade views

| View | Purpose |
|---|---|
| `admin/drivers/index.blade.php` | Filter card (date_from/date_to/mobile/status) + server-side DataTable; per-row "Tasks" button; mass-delete button |
| `admin/drivers/create.blade.php` | Create form: identity + Shift & Schedule (flatpickr times, shift template quick-fill, Select2 zone) |
| `admin/drivers/edit.blade.php` | Edit form (same fields, prefilled) |
| `admin/drivers/show.blade.php` | Detail + tabs for driverCarLinkHistories / driverTasks |
| `admin/drivers/tasks.blade.php` | **Drag-drop task reorder** (SortableJS) + smart-sort button (SweetAlert) |
| `admin/drivers/relationships/driverCarLinkHistories.blade.php` | Link-history table on show |
| `admin/drivers/relationships/driverTasks.blade.php` | Tasks table on show |

### Permissions / Gates

`driver_access`, `driver_create`, `driver_edit`, `driver_show`; delete via
**`can-delete`** in `destroy`/`massDestroy` (note: `MassDestroyDriverRequest`
itself checks `driver_delete`). Reorder/smart-sort routes carry no extra gate
beyond the `auth` + admin group.

### Form Request rules

- **StoreDriverRequest** (`driver_create`): `name` req string min1 max50;
  `password` req; `working_hours_start`/`working_hours_end` req (time string, no
  format rule); `status` req; `username`/`mobile`/`email` req string unique:drivers;
  `shift_count` int nullable; `employment_type` string nullable;
  `total_working_hours` int nullable; `second_shift_working_hours_start/end` nullable.
- **UpdateDriverRequest** (`driver_edit`): same but unique-ignore current id;
  `email` nullable. (`password` not required on update.)
- **MassDestroyDriverRequest** (`driver_delete`): `ids` req array, `ids.*` exists:drivers,id.

> Extra fields posted by the form but NOT in the Form Request (still saved via
> `$request->all()` / shift sync): `language`, `zone_id`, `national_id`,
> `third_shift_working_hours_start/end`. These must be passed through unchanged.

### Special behaviors to PRESERVE

- Server-side DataTable (ajax) — the index route IS the data source.
- `status` semantics `1=Enabled, 2=Disabled`; `language` via LANGUAGE_SELECT.
- flatpickr submits 24h `H:i` (display `h:i K`); shift template quick-fill.
- Shift 2/3 visibility tied to `shift_count`; `syncDriverShifts` deactivates
  prior active shifts then recreates 1..N — keep this exact payload contract.
- Drag-drop reorder payload `order:[{id, priority}]` → ETA job dispatched.
- Smart-sort triggers `DriverRouteService` (do not reimplement in JS).
- `withoutGlobalScope('enabled')` so disabled drivers still appear in admin lists.

---

## 3. Target design (Vue + Tailwind)

| Blade view | Vue view | vue-build components |
|---|---|---|
| `drivers/index` | `views/Drivers/DriversList.vue` | `Breadcrumb`, `FilterBar`, `DataTable` (serverSide), `StatusBadge`, `BaseButton`, `BaseModal` (delete confirm) |
| `drivers/create` + `edit` | `views/Drivers/DriverForm.vue` | `FormInput`, `FormSelect`, time inputs (FormInput type=time), `BaseCard`, `BaseButton`; a "Shift & Schedule" `BaseCard` section with shift-count-driven blocks |
| `drivers/show` + relationships | `views/Drivers/DriverShow.vue` | `Breadcrumb`, `BaseCard`, `TabGroup` (Link Histories / Tasks), inner `DataTable`s, `StatusBadge`, `BaseAvatar` |
| `drivers/tasks` | `views/Drivers/DriverTaskReorder.vue` | `Breadcrumb`, `BaseCard`, a drag-drop list (see below), `BaseButton`, `BaseModal` (smart-sort confirm), `useToast` |

### Drag-drop reorder approach (`DriverTaskReorder.vue`)
- Use **`vuedraggable`** (Vue 3 wrapper over SortableJS — same lib the Blade page
  already uses) on a list of task cards, `handle=".drag-handle"`, `animation:150`,
  `ghost-class` styling to match the existing teal hover.
- Each card mirrors the Blade card: Task ID, from/to location names, ETA badge,
  current priority chip.
- "Save Order" button (disabled until list changes) → POST reorder API with the
  computed `order` array. "Smart Sort" button → confirm modal → POST smart-sort
  API → reload list.
- This is a **dedicated route**, not a modal (Blade renders a full page); keep it
  that way for parity. (The legacy in-modal variant in `index.blade.php` is dead
  commented code — ignore it.)

### Shift form (`DriverForm.vue`)
- `shift_count` select drives reactive rendering of Shift 2 / Shift 3 time pairs.
- "Quick Shift Selection" select (options = shift templates with `start`/`end`)
  fills Shift 1 start/end. Keep the 24h `H:i` submit contract.
- Zone select fed by `/options`.

### Vue Router routes
```
/admin/drivers                      → DriversList   meta:{perm:'driver_access'}
/admin/drivers/create               → DriverForm    meta:{perm:'driver_create'}
/admin/drivers/:id/edit             → DriverForm    meta:{perm:'driver_edit'}
/admin/drivers/:id                  → DriverShow    meta:{perm:'driver_show'}
/admin/drivers/:driver/tasks        → DriverTaskReorder  meta:{perm:'driver_access'}
```

### nav.config.js
Drivers entry exists (group "Drivers", perm `driver_access`). The Tasks-reorder
screen is reached from a per-row button, not the nav.

### Empty / loading / error
- List: `DataTable` skeleton + `EmptyState` (built in).
- Reorder: skeleton card list; `EmptyState` "no active tasks" mirroring the Blade
  empty branch.

---

## 4. Data / API contract

Base: `/app/api/drivers`. Standard CRUD per foundation table, plus the
driver-specific endpoints below.

| Method | Path | Purpose | Reuses |
|---|---|---|---|
| GET | `/app/api/drivers` | server-side list | index query + `driver_access` |
| GET | `/app/api/drivers/options` | `{ zones:[{value,label}], shiftTemplates:[{value,label,start,end}], statuses:[{value,label}], languages:[{value,label}] }` | create/edit query bits |
| GET | `/app/api/drivers/{id}` | detail (incl. loaded link-histories + tasks) | `driver_show` |
| POST | `/app/api/drivers` | create | **StoreDriverRequest** + `driver_create` |
| PUT | `/app/api/drivers/{id}` | update | **UpdateDriverRequest** + `driver_edit` |
| DELETE | `/app/api/drivers/{id}` | delete one | `can-delete` |
| DELETE | `/app/api/drivers` (`{ids:[]}`) | mass delete | **MassDestroyDriverRequest** + `can-delete` |
| GET | `/app/api/drivers/{id}/shifts` | active shifts | reuse `getShifts` logic |
| GET | `/app/api/drivers/{driver}/tasks` | active tasks for reorder screen | reuse `showTasks` query |
| POST | `/app/api/drivers/{driver}/tasks/reorder` | save order | reuse `reorderTasks` (DB tx + `CalculateDriverETAJob`) |
| POST | `/app/api/drivers/{driver}/tasks/smart-sort` | smart sort | reuse `smartSortTasks` (`DriverRouteService`) |

### List request params
`{ q, sortKey, sortDir, page, pageSize, date_from, date_to, mobile, status }`.
(Map `q`→Yajra global search; keep the three explicit filters identical.)

### List row shape (keys = `columns[].key`)
```json
{ "id": 42, "name": "…", "status": "Enabled", "username": "…",
  "mobile": "0500000000", "email": "…", "language": "Arabic",
  "tasksUrl": "/admin/drivers/42/tasks" }
```
- `status` pre-formatted to "Enabled"/"Disabled" (StatusBadge maps). Keep raw
  `status` int if the UI needs it for filtering.

### Detail shape
```json
{ "data": { "id":42, "name":"…", "username":"…", "mobile":"…", "email":"…",
  "language":"ar", "status":1, "zone_id":3, "national_id":"…",
  "working_hours_start":"08:00", "working_hours_end":"16:00",
  "second_shift_working_hours_start":null, "second_shift_working_hours_end":null,
  "third_shift_working_hours_start":null, "third_shift_working_hours_end":null,
  "shift_count":1, "employment_type":"full_time", "total_working_hours":8,
  "shifts":[…], "driverCarLinkHistories":[…], "driverTasks":[…] } }
```

### Reorder request / response
```
POST /app/api/drivers/{driver}/tasks/reorder
{ "order": [ { "id": 101, "priority": 1 }, { "id": 102, "priority": 2 } ] }
→ 200 { "success": true }      // ETA recalculation dispatched server-side
```
### Smart-sort
```
POST /app/api/drivers/{driver}/tasks/smart-sort   → 200 { "success": true }
```
### Reorder list row
```json
{ "id":101, "from_location_name":"…", "to_location_name":"…",
  "eta":12, "poririty":1 }
```

### Validation surfacing
422 `{message, errors:{field:[..]}}` mapped onto `FormInput`/`FormSelect`
`:error`. Note field names match the form inputs (`name`, `username`, `mobile`,
`email`, `working_hours_start`, etc.). The non-validated pass-through fields
(`language`, `zone_id`, `national_id`, `third_shift_*`) must be included in the
POST/PUT body.

### Exports
Drivers index "search" form posts to `admin.reportExport` (`TasksController@export`).
The SPA's filter "export" links to that EXISTING route (do not reimplement).

---

## 5. Migration steps (ordered, checkable)

- [ ] Backend: `Api\DriversApiController` — list (Yajra→`{data,meta}` adapter, same
      3 filters), options, show, store (StoreDriverRequest), update
      (UpdateDriverRequest), destroy/massDestroy (can-delete + MassDestroy req).
- [ ] Backend: shifts, tasks(list), tasks/reorder, tasks/smart-sort endpoints
      delegating to existing `getShifts` / `showTasks` / `reorderTasks` /
      `smartSortTasks` logic (extract query/logic if needed, do not change it).
- [ ] Backend: register routes in `routes/app_api.php`.
- [ ] Frontend: `DriversList.vue` (serverSide DataTable + FilterBar).
- [ ] Frontend: `DriverForm.vue` (identity + shift section + zone select + quick
      template fill; flatpickr-equivalent time inputs submitting `H:i`).
- [ ] Frontend: `DriverShow.vue` (TabGroup: Link Histories, Tasks).
- [ ] Frontend: `DriverTaskReorder.vue` (vuedraggable + save/smart-sort).
- [ ] Wire router routes + per-row Tasks button + nav perm.
- [ ] Parity test vs Blade: same filters, same status/lang labels, same shift
      sync result in `driver_shifts`, reorder writes `poririty` + dispatches ETA
      job, smart-sort runs `DriverRouteService`.
- [ ] Cutover: flip Drivers nav to `/app/admin/drivers`.

---

## 6. Risks / must-not-break

- **Shift sync contract**: the exact set of posted shift fields drives
  `syncDriverShifts`; missing one silently drops a shift. Send all of
  `shift_count, working_hours_start/end, second/third_shift_working_hours_start/end`.
- **Pass-through fields**: `zone_id`, `national_id`, `language` are saved via
  `$request->all()` but are NOT in the Form Request — easy to forget in the API body.
- **Reorder field name**: column is misspelled **`poririty`** in DB/queries; the
  request key is `priority`. Keep both exactly as-is.
- **ETA job**: reorder must keep dispatching `CalculateDriverETAJob` afterResponse;
  smart-sort must keep calling `DriverRouteService` — both are the real logic.
- **`withoutGlobalScope('enabled')`**: admin lists/show must include disabled drivers.
- **Two reorder route declarations / `smartSort` route name** (`admin.drivers.tasks.smartSort`).
- **Delete dual-gate**: `destroy` uses `can-delete`; MassDestroy request uses
  `driver_delete`. Mirror both.

## 7. Out of scope / open questions

- The mobile-facing `DriverController` API methods (login/tasks/checkin/checkout
  etc.) are NOT part of this admin migration.
- Confirm whether `lat/lng/accepted_terms` columns should appear in the new list
  (Blade defines edit-columns for them but the visible table omits them).
- Confirm the canonical reorder/smart-sort route to expose under `/app/api`
  (logic is identical regardless of which legacy declaration it mirrors).
