# Scheduled Tasks — Frontend Migration Plan

> Read [`01-foundation.md`](01-foundation.md) first. This plan conforms to the
> JSON API layer, the server-side `DataTable` contract, the `{data,meta}`
> envelope, the 422→field mapping, the `/options` shape, and RTL/i18n. We reuse
> `ScheduledTaskController`'s queries, the **`StoreScheduledTaskRequest` /
> `UpdateScheduledTaskRequest` / `MassDestroyScheduledTaskRequest`** Form
> Requests, and its Gates. **No business logic changes** — the parent/child
> recurrence generation, the per-from-location visit-hour expansion, and the
> parent-reparenting delete logic stay exactly as written.

---

## 1. Module overview

Scheduled (recurring) tasks: a "schedule" is a **parent** row plus generated
**child** rows (one per from-location × selected day). The system later
materializes real tasks from these. Includes a normal create wizard, a **quick**
create form, a **calendar view** (`index-schedule`), and parent/children delete.

- **Nav group:** Tasks.
- **Routes / gate:** `/admin/scheduled-tasks` · `scheduled_task_access`.
- **Sub-gates:** `scheduled_task_create`, `scheduled_task_edit`,
  `scheduled_task_show`, `scheduled_task_delete`, `scheduled_task_mass_delete`,
  and `can-delete` (API authorize on destroy/children-destroy).

---

## 2. Current implementation (Blade / Velzon)

### Routes (controller = `Admin\ScheduledTaskController`)

| Method | URI | Name | Action | Gate |
|---|---|---|---|---|
| GET | `admin/scheduled-tasks` | `admin.scheduled-tasks.index` | `index` (ajax, parents only) | `scheduled_task_access` |
| GET | `admin/scheduled-tasks/create` | `admin.scheduled-tasks.create` | `create` | `scheduled_task_create` |
| POST | `admin/scheduled-tasks` | `admin.scheduled-tasks.store` | `store(StoreScheduledTaskRequest)` | `scheduled_task_create` |
| GET | `admin/scheduled-tasks/{scheduledTask}` | `admin.scheduled-tasks.show` | `show` (ajax = children) | `scheduled_task_show` |
| GET | `admin/scheduled-tasks/{scheduledTask}/edit` | `admin.scheduled-tasks.edit` | `edit` | `scheduled_task_edit` |
| PUT/PATCH | `admin/scheduled-tasks/{scheduledTask}` | `admin.scheduled-tasks.update` | `update(UpdateScheduledTaskRequest)` | `scheduled_task_edit` |
| DELETE | `admin/scheduled-tasks/{scheduledTask}` | `admin.scheduled-tasks.destroy` | `destroy` (reparents children) | `can-delete` |
| DELETE | `admin/scheduled-tasks/destroy` | `admin.scheduled-tasks.massDestroy` | `massDestroy(MassDestroyScheduledTaskRequest)` | `scheduled_task_mass_delete` |
| DELETE | `admin/scheduled-tasks/{scheduledTask}/children/destroy` | `admin.scheduled-tasks.childrenMassDestroy` | `massDestroyChildren` | (inline validate; children-of-parent guard) |
| GET | `admin/schedule/quick` | `admin.scheduled-tasks.quick` | `quick` | `scheduled_task_create` |
| POST | `admin/schedule/quick` | `admin.scheduled-tasks.quickAction` | `quickAction(StoreScheduledTaskRequest)` | `scheduled_task_create` |
| GET | `admin/schedule/delete/{scheduledTask}/parents` | `admin.scheduled-tasks.deleteAllParent` | `deleteBasedOnParent` (parent + all children) | `can-delete` |
| GET | `admin/scheduled-tasks/search/drivers` | `admin.scheduled-tasks.searchDrivers` | `searchDrivers` (Select2 remote) | — |
| GET | `admin/scheduled-tasks/search/locations` | `admin.scheduled-tasks.searchLocations` | `searchLocations` (Select2 remote) | — |
| GET | `admin/scheduled-driver` | `admin.tasks.indexSchedule` | `indexSchedule` (FullCalendar) | — (no abort_if) |

### What each action passes

- **index (ajax):** parents only (`parent_id IS NULL`), eager `from_location,
  to_location,client`; Yajra computed: `status` (`STATUS_SELECT`),
  `to_location_name`, `client_status` (`client.english_name`), `task_type`
  (`TASK_TYPE_SELECT`), `driver_name`, `sequence`. Filters: `date_from/date_to`
  (on `created_at`), `driver_id`, `client_id`, `from_location`, `to_location`.
  Non-ajax: passes `clients,locations,drivers` (client-scoped).
- **show (ajax):** rows where `parent_id = id OR id = id` (the parent + its
  children); adds a `row_select` checkbox column + `actions`.
- **store:** expands `from_location_id[]` × `days[]`, assigns
  `selected_hour = visit_hours[from_location_id]`; first row becomes parent,
  rest get `parent_id`. Auto-names `"{driver} - {start_date}"`. Validates each
  from-location has a visit hour.
- **quickAction:** expands `days[] × visit_hours[]` for a single
  `from_location_id`; first row = parent.
- **destroy:** if deleting a parent, promotes one child to parent and reparents
  the rest, then redirects to the surviving schedule; **PRESERVE this graph
  surgery.**
- **deleteBasedOnParent:** deletes parent + all children.
- **indexSchedule:** builds FullCalendar events via `formatScheduleForCalendar`
  (expands each schedule's day-of-week across start→end within current month).
- **searchDrivers/searchLocations:** Select2 remote JSON
  `{results:[{id,text}]}`, client-scoped, `?q=` search, limit 50.

### Blade views (`resources/views/admin/scheduledTasks/`)

| File | Purpose | Table |
|---|---|---|
| `index.blade.php` | Parents list + filters + mass delete | server-side ajax |
| `show.blade.php` | Parent + children grid + children mass-delete + "delete all" | server-side ajax |
| `create.blade.php` | Multi from-location + per-location Flatpickr visit hours | — |
| `quick.blade.php` | Single location + N repeatable Flatpickr visit hours | — |
| `edit.blade.php` | Edit one row (native time input) | — |
| `index-schedule.blade.php` | FullCalendar 3.1 + filter form | calendar |

### Form Request rules (PRESERVE — reuse via API)

- **`StoreScheduledTaskRequest`** (`scheduled_task_create`): `start_date`
  required, `end_date` required, `from_location_id` required, `to_location_id`
  required integer, `client_id` required integer, `driver_id` required integer,
  `task_type` required, `visit_hours` required. (Used by both store + quickAction.)
- **`UpdateScheduledTaskRequest`** (`scheduled_task_edit`): `name` required
  string, `start_date`/`end_date` required, `from_location_id`/`to_location_id`/
  `client_id`/`driver_id` required integer, `task_type` required.
- **`MassDestroyScheduledTaskRequest`** (`scheduled_task_mass_delete`): `ids`
  required array, `ids.*` exists. `massDestroyChildren` uses an inline validator
  (`ids` array of `exists:scheduled_tasks,id`) + a guard that every id is a
  child of the given parent (422 otherwise).

### Enums (`App\Models\ScheduledTask`)
- `STATUS_SELECT`: enabled, disabled. `TASK_TYPE_SELECT`: SAMPLE, BOX.
- `days`: Monday…Sunday (literal weekday names).

### Behaviors to PRESERVE
- Parent/child graph (generation + reparent-on-delete).
- Visit-hour binding (`visit_hours[locationId]` in create; positional in quick).
- Flatpickr time pickers; `data-mf-date` date inputs; Select2 remote driver/
  location search.
- FullCalendar 3.1 calendar view.

---

## 3. Target design (Vue + Tailwind)

All under `resources/js/vue/views/ScheduledTasks/`.

| Blade view | Vue view | vue-build components |
|---|---|---|
| `index.blade.php` | `ScheduledTasksList.vue` | Breadcrumb, FilterBar, DataTable, FormSelect, FormInput, StatusBadge, BaseButton, BaseModal |
| `show.blade.php` | `ScheduledTaskShow.vue` | Breadcrumb, BaseCard, DataTable (children + row checkboxes), BaseButton, BaseModal |
| `create.blade.php` | `ScheduledTaskCreate.vue` | BaseCard, FormSelect (multiple), FormInput, FormToggle, BaseButton (dynamic visit-hour rows) |
| `quick.blade.php` | `ScheduledTaskQuick.vue` | BaseCard, FormSelect, FormInput, BaseButton (repeatable visit-hour rows) |
| `edit.blade.php` | `ScheduledTaskEdit.vue` | BaseCard, FormSelect, FormInput, BaseButton |
| `index-schedule.blade.php` | `ScheduledTasksCalendar.vue` | Breadcrumb, FilterBar, (FullCalendar wrapper) |

- **Visit hours:** time inputs (`<input type="time">` or a small Tailwind time
  field) replace Flatpickr — same `HH:mm` payload. Create binds one row per
  selected from-location (`visit_hours[locId]`); quick binds a growing array
  (`visit_hours[]`).
- **Select2 remote** → `FormSelect` in async mode backed by the existing
  `searchDrivers`/`searchLocations` endpoints (returns `{id,text}`; adapt to
  `{value,label}` in the composable).
- **Calendar:** wrap FullCalendar (v6 in the SPA, or keep v3 as a one-off) in
  `ScheduledTasksCalendar.vue`; events come from the calendar API below.

### Vue Router routes

```
/admin/scheduled-tasks            → ScheduledTasks/ScheduledTasksList.vue   meta.perm: scheduled_task_access
/admin/scheduled-tasks/create     → ScheduledTasks/ScheduledTaskCreate.vue  meta.perm: scheduled_task_create
/admin/schedule/quick             → ScheduledTasks/ScheduledTaskQuick.vue   meta.perm: scheduled_task_create
/admin/scheduled-tasks/:id        → ScheduledTasks/ScheduledTaskShow.vue    meta.perm: scheduled_task_show
/admin/scheduled-tasks/:id/edit   → ScheduledTasks/ScheduledTaskEdit.vue    meta.perm: scheduled_task_edit
/admin/scheduled-driver           → ScheduledTasks/ScheduledTasksCalendar.vue
```

---

## 4. Data / API contract

Base `/app/api/scheduled-tasks`. Add `Api\ScheduledTaskApiController`.

### List — `GET /app/api/scheduled-tasks`
Params: `q`, `sortKey`, `sortDir`, `page`, `pageSize`, `date_from`, `date_to`,
`driver_id`, `client_id`, `from_location`, `to_location`. Reuse `index()` query
(parents only). Gate `scheduled_task_access`. `data[]` keys: `seq, id, name,
status, start_date, end_date, to_location_name, client_status, selected_hour,
task_type, days, added_by, driver_name` (+ raw ids). `{data,meta}`.

### Children — `GET /app/api/scheduled-tasks/{id}/children`
Reuse `show()` ajax query (`parent_id=id OR id=id`). Same columns + a `selected`
flag for row checkboxes. Gate `scheduled_task_show`.

### Detail — `GET /app/api/scheduled-tasks/{id}`
Reuse `show()` non-ajax payload (the parent model). Gate `scheduled_task_show`.

### Options — `GET /app/api/scheduled-tasks/options`
`{ clients, drivers, from_locations, to_locations, days, statuses, task_types }`
as `[{value,label}]` (client-scoped). `days` = Monday…Sunday.

### Remote search (reuse existing endpoints as-is)
- `GET admin.scheduled-tasks.searchDrivers?q=` → `{results:[{id,text}]}`
- `GET admin.scheduled-tasks.searchLocations?q=` → `{results:[{id,text}]}`
The async-select composable maps `{id,text}`→`{value,label}`.

### Create — `POST /app/api/scheduled-tasks` → **`StoreScheduledTaskRequest`**, gate `scheduled_task_create`
Body: `from_location_id[]`, `to_location_id`, `client_id`, `driver_id`,
`task_type`, `status`, `start_date`, `end_date`, `days[]`,
`visit_hours{locId:HH:mm}`. API calls the **same `store()` expansion** (parent/
child + auto-name + per-location visit-hour check → 422 on `visit_hours`).
`201 {data:{parent_id}}`.

### Quick — `POST /app/api/scheduled-tasks/quick` → **`StoreScheduledTaskRequest`**, gate `scheduled_task_create`
Body: `name`, `from_location_id`, `to_location_id`, `client_id`, `driver_id`,
`task_type`, `status`, `start_date`, `end_date`, `days[]`, `visit_hours[]`.
Calls the **same `quickAction()` expansion** (days×hours).

### Update — `PUT /app/api/scheduled-tasks/{id}` → **`UpdateScheduledTaskRequest`**, gate `scheduled_task_edit`
Body: `name`, `start_date`, `end_date`, `from_location_id`, `to_location_id`,
`client_id`, `driver_id`, `task_type`, `status`, `day`, `selected_hour`,
`added_by`. `200`.

### Delete (preserve graph logic)
- `DELETE /app/api/scheduled-tasks/{id}` — calls **`destroy()`** (reparenting).
  `can-delete`. Response should tell the SPA which schedule survived so it can
  redirect (return `{data:{redirect_id}}`).
- `DELETE /app/api/scheduled-tasks` `{ids:[]}` — **`MassDestroyScheduledTaskRequest`**
  (also deletes children of any parent in the set).
- `DELETE /app/api/scheduled-tasks/{id}/children` `{ids:[]}` — children-only
  mass delete; reuse `massDestroyChildren` guard (422 if an id isn't a child).
- `POST/DELETE /app/api/scheduled-tasks/{id}/delete-all-parents` —
  `deleteBasedOnParent` (parent + all children). `can-delete`.

### Calendar — `GET /app/api/scheduled-tasks/calendar`
Reuse `indexSchedule()` filter logic + `formatScheduleForCalendar()`. Params:
`driver_id`, `billing_client`, `from_location`, `to_location`. Returns
`[{title,start,end}]` (FullCalendar event shape). Also expose options for the
filter bar (or reuse `/options`).

### Validation surfacing
422 maps to fields per foundation. `visit_hours` errors surface on the relevant
visit-hour row(s).

---

## 5. Migration steps (ordered, checkable)

- [ ] Backend: `Api\ScheduledTaskApiController` + routes — list, children,
      detail, options, create (reuse `StoreScheduledTaskRequest` + `store()`
      expansion), quick (same FR + `quickAction()`), update
      (`UpdateScheduledTaskRequest`), destroy/mass/children/delete-all (reuse
      existing methods + guards), calendar (reuse `formatScheduleForCalendar`).
      Keep using `searchDrivers`/`searchLocations` as-is.
- [ ] Frontend: build the 6 views; visit-hour rows; async driver/location selects.
- [ ] Frontend: calendar wrapper (FullCalendar) fed by the calendar endpoint.
- [ ] Wire router + perms + nav.
- [ ] Parity test: parent/child counts after create/quick match Blade; delete
      reparenting matches; children mass-delete guard works; calendar events
      identical.
- [ ] Cutover nav; keep Blade for rollback.

---

## 6. Risks / must-not-break

- **Parent/child generation math** (create: locations×days with
  `visit_hours[locId]`; quick: days×hours) — must produce identical row counts
  and the same parent assignment.
- **Reparent-on-delete** in `destroy()` — deleting a parent must promote a child
  and re-link siblings, not orphan/delete them. The API must call the same code
  and tell the SPA where to redirect.
- **Children mass-delete guard** — only children of the given parent may be
  deleted (422 otherwise).
- **Auto-name** `"{driver} - {start_date}"` on store — keep.
- **visit_hours required-per-location** validation in `store()` (separate from
  the Form Request) — keep returning a `visit_hours` error.
- **Calendar event expansion** (weekday match within month, start/end clamping)
  — identical output.
- `indexSchedule` has **no `abort_if`** gate today; mirror that (or add the
  render-perm guard in the SPA only) but do not tighten/loosen backend auth as
  part of presentation migration.

---

## 7. Out of scope / open questions

- FullCalendar version: SPA likely ships v6; confirm acceptable vs the legacy
  v3 used in Blade (event shape is compatible).
- `indexSchedule` is named `admin.tasks.indexSchedule` (Tasks-namespaced) but
  lives in this controller — keep the route name to avoid breaking links.
- Whether `selected_hour` should be a time field everywhere (edit uses native
  `time`, create/quick use Flatpickr) — unify to `<input type="time">` (same
  `HH:mm` payload).
- No date-range default here (unlike Tasks); large schedules paginate via
  DataTable server-side — confirm acceptable.
