# Driver Schedules — Frontend Migration Plan

> Conforms to [`01-foundation.md`](01-foundation.md) (JSON API layer reusing Form
> Requests + Gates, strangler-fig cutover, `{data,meta}` DataTable contract,
> 422→form fields, permission rendering, RTL/i18n). Only Driver-Schedule
> specifics are below.

## 1. Module overview

Per-driver route/plan records: a **from-location → to-location** pairing for a
driver, with an optional note and plate number. Nav group **Drivers**. Gate
family: `driver_schedule_access` / `driver_schedule_create` / `driver_schedule_edit`
/ `driver_schedule_show`; deletes via the global `can-delete` Gate. Route prefix
`admin.driver-schedules.*`. Full CRUD incl. a Show view. Underlying table is
`driver_schedule` (singular; **no SoftDeletes** on this model).

## 2. Current implementation (Blade / Velzon)

### Routes (`routes/web.php` ~166–168, via `Route::resource`)
| Method | URI | Name | Action |
|---|---|---|---|
| GET | `/admin/driver-schedules` | `admin.driver-schedules.index` | `@index` |
| GET | `/admin/driver-schedules/create` | `admin.driver-schedules.create` | `@create` |
| POST | `/admin/driver-schedules` | `admin.driver-schedules.store` | `@store` |
| GET | `/admin/driver-schedules/{driverSchedule}` | `admin.driver-schedules.show` | `@show` |
| GET | `/admin/driver-schedules/{driverSchedule}/edit` | `admin.driver-schedules.edit` | `@edit` |
| PUT/PATCH | `/admin/driver-schedules/{driverSchedule}` | `admin.driver-schedules.update` | `@update` |
| DELETE | `/admin/driver-schedules/{driverSchedule}` | `admin.driver-schedules.destroy` | `@destroy` |
| DELETE | `/admin/driver-schedules/destroy` | `admin.driver-schedules.massDestroy` | `@massDestroy` |

### Controller actions / data — `App\Http\Controllers\Admin\DriverScheduleController`
- `index`: Gate `driver_schedule_access`. `DriverSchedule::with(['from','to','driver'])->get()`
  → **client-side** DataTable (no Yajra/ajax; `pageLength:100`, sort col 1 desc).
- `create`: Gate `driver_schedule_create`. Passes `$from_locations`,
  `$to_locations` (both `Location::pluck('name','id')` with "please select"),
  `$drivers` (`Driver::pluck('name','id')`).
- `store(StoreDriverScheduleRequest)`: `DriverSchedule::create($request->all())`;
  redirect to index.
- `edit`: Gate `driver_schedule_edit`. Same option lists + `$driverSchedule`
  (eager-loads `from`,`to`,`driver`).
- `update(UpdateDriverScheduleRequest, $driverSchedule)`: `->update($request->all())`;
  redirect.
- `show`: Gate `driver_schedule_show`. Read-only detail table (id, from, to, driver,
  note, plate_number).
- `destroy`: `authorize('can-delete')`; `delete()`; `back()`.
- `massDestroy(MassDestroyDriverScheduleRequest)`: `authorize('can-delete')`;
  `whereIn('id', ids)->delete()`.

### Model — `App\Models\DriverSchedule`
Table `driver_schedule`. `$fillable = ['from_location','to_location','driver_id',
'note','plate_number','created_at','updated_at']`. Relations: `from()` →
`Location` (FK `from_location`), `to()` → `Location` (FK `to_location`),
`driver()` → `Driver`. **No SoftDeletes.**

### Form Requests (reuse unchanged)
- `StoreDriverScheduleRequest` — `authorize`: `Gate::allows('driver_schedule_create')`;
  `rules`: **only `plate_number` => string|nullable**.
- `UpdateDriverScheduleRequest` — `authorize`: `driver_schedule_edit`; **only
  `plate_number` => string|nullable**.
- `MassDestroyDriverScheduleRequest` — verify file; expected `ids` required|array
  + `ids.* exists`. (Confirm the gate it authorizes; controller also calls
  `authorize('can-delete')`.)

> Like Attendances, the validated set is tiny (`plate_number` only). `from_location`,
> `to_location`, `driver_id`, `note` are mass-assigned via `$request->all()` with
> **no server-side rules** — keep that parity; don't add rules in the API.

### Blade views
| File | Purpose |
|---|---|
| `admin/driverSchedules/index.blade.php` | **client-side** DataTable; rows show from/to/driver names, note, plate; row actions Show/Edit/Delete each `@can`-gated; bulk-delete button gated `can-delete` (reads `data-entry-id` from rows) |
| `admin/driverSchedules/create.blade.php` | Form: from_location (Select2), to_location (Select2), driver_id (Select2), plate_number (text), note (textarea) |
| `admin/driverSchedules/edit.blade.php` | Same fields, prefilled |
| `admin/driverSchedules/show.blade.php` | Read-only detail (id, from, to, driver, note, plate_number) |

### Behaviors to preserve
- **Client-side** list (uses `:not(.ajaxTable)` selector — explicitly NOT server-side).
- Per-row actions individually gated: Show (`driver_schedule_show`), Edit
  (`driver_schedule_edit`), Delete (`can-delete`). Bulk delete gated `can-delete`.
- Select2 driver/location pickers. No flatpickr/date filters on this screen.

## 3. Target design (Vue + Tailwind)

Views under `resources/js/vue/views/DriverSchedules/`:

| Blade view | Vue view | vue-build components |
|---|---|---|
| `index.blade.php` | `DriverSchedulesList.vue` | `Breadcrumb`, `DataTable` (**client-side**: pass full `rows`), `BaseButton`, `BaseModal` (delete confirm), `EmptyState` |
| `create.blade.php` | `DriverScheduleCreate.vue` | `Breadcrumb`, `BaseCard`, `FormSelect` (from / to / driver), `FormInput` (plate_number), `FormInput`/textarea (note), `BaseButton` |
| `edit.blade.php` | `DriverScheduleEdit.vue` | same as create, prefilled |
| `show.blade.php` | `DriverScheduleShow.vue` | `Breadcrumb`, `BaseCard` (definition list) |

- **List:** client-side DataTable. Columns: `id`, `from_location` (name),
  `to_location` (name), `driver_name`, `note`, `plate_number` (`dir=ltr`), actions.
  Row actions: Show (`can('driver_schedule_show')`), Edit
  (`can('driver_schedule_edit')`), Delete (`canDelete()`). Bulk delete
  (`canDelete()`). Default sort `id` desc, page size 100.
- **Create/Edit:** three `FormSelect`s (locations come from the `/options`
  endpoint), plate text, note textarea. Validation 422 surfaces on `plate_number`
  (the only validated field) — map it; other fields stay error-free server-side.
- **Show:** read-only definition list of the six fields.
- **States:** `EmptyState` when empty; toast on save/delete; perm guard → 403.

### Router / nav
- Routes: `/admin/driver-schedules` (`driver_schedule_access`),
  `/admin/driver-schedules/create` (`driver_schedule_create`),
  `/admin/driver-schedules/:id` (`driver_schedule_show`),
  `/admin/driver-schedules/:id/edit` (`driver_schedule_edit`). Mirror
  `nav.config.js` route values.

## 4. Data / API contract

Base `/app/api/driver-schedules` (new `Api\DriverSchedulesApiController`,
delegating to the existing query + Form Requests + Gates).

| Method | Path | Purpose | Reuses |
|---|---|---|---|
| GET | `/app/api/driver-schedules` | List (client-side: return all with relations) | index query + Gate `driver_schedule_access` |
| GET | `/app/api/driver-schedules/options` | `{ from_locations, to_locations, drivers }` for selects | `Location::pluck` / `Driver::pluck` from create/edit |
| GET | `/app/api/driver-schedules/{id}` | Detail (show + edit prefill) | Gate `driver_schedule_show` (use a relaxed read gate for edit prefill if needed; verify) |
| POST | `/app/api/driver-schedules` | Create | **`StoreDriverScheduleRequest`** + Gate `driver_schedule_create` |
| PUT | `/app/api/driver-schedules/{id}` | Update | **`UpdateDriverScheduleRequest`** + Gate `driver_schedule_edit` |
| DELETE | `/app/api/driver-schedules/{id}` | Delete one | `authorize('can-delete')` |
| DELETE | `/app/api/driver-schedules` | Mass delete `{ids:[]}` | **`MassDestroyDriverScheduleRequest`** + `can-delete` |

> Client-side list: the list endpoint returns `{ "data": [ ... ] }` (all rows with
> resolved relation names) and `DataTable` paginates locally — matches Blade.

### List row shape (resolve relation names like Blade)
```json
{ "id": 7, "from_location": "King Faisal Lab", "to_location": "Central Hub",
  "driver_name": "Mohammed Al-Harbi", "note": "AM run", "plate_number": "ABC-1234",
  "from_location_id": 12, "to_location_id": 3, "driver_id": 5 }
```
Keep raw FK ids alongside names so the edit form can preselect the `FormSelect`s.

### Detail / options bodies
- Detail: `{ "data": { id, from_location_id, to_location_id, driver_id,
  from_location, to_location, driver_name, note, plate_number } }`.
- Options: `{ "from_locations":[{value,label}], "to_locations":[{value,label}],
  "drivers":[{value,label}] }` (FormSelect shape).

### Validation
Only `plate_number` (string, nullable) is validated → 422 mapped onto that field.
No rules for from/to/driver/note (parity).

## 5. Migration steps (ordered, checkable)
- [ ] Backend: `Api\DriverSchedulesApiController` (list/options/show/store/update/
      destroy/massDestroy); reuse `Store/Update/MassDestroyDriverScheduleRequest`
      + Gates; resolve `from/to/driver` names in list/detail responses.
- [ ] Register routes in `routes/app_api.php`.
- [ ] Frontend: `DriverSchedulesList` (client-side DataTable) + Create/Edit/Show
      views from vue-build components.
- [ ] Wire router routes + per-action perms; confirm `nav.config.js` entry.
- [ ] Parity test vs Blade: relation names render, per-action buttons hide by gate,
      bulk delete gated `can-delete`, client-side paging/sort match.
- [ ] Flip nav to `/app` route (Blade stays for rollback).

## 6. Risks / must-not-break
- **Per-action gates:** preserve `driver_schedule_show/edit` on individual row
  buttons and `can-delete` on delete + bulk delete. Don't collapse to a single gate.
- **Validation parity:** only `plate_number` is validated; do not add rules for
  from/to/driver/note (the Blade form treats them as optional server-side).
- **No SoftDeletes:** deletes are permanent here (unlike Attendances). The bulk
  `whereIn(...)->delete()` is a hard delete — keep behavior identical.
- **Table name:** model maps to `driver_schedule` (singular). Any raw query in the
  API must use the model, not a guessed `driver_schedules` table.
- **Client-side list:** don't convert to server-side Yajra.
- **`MassDestroyDriverScheduleRequest` gate:** verify which gate it authorizes; the
  controller additionally calls `authorize('can-delete')`. Keep both checks.

## 7. Out of scope / open questions
- The `note` field is free text; render `dir=auto` (mixed AR/EN). Plate numbers
  forced `dir=ltr`.
- No date/time fields on a schedule beyond `created_at`/`updated_at` — this is a
  static from→to assignment, **not** a recurring calendar. If product expects
  time-based recurrence, that is a new feature, not a migration concern.
- Edit prefill currently relies on `driver_schedule_show` not being required;
  confirm the API uses an appropriate read gate for `GET /{id}` so an
  edit-but-not-show user can still load the form (match Blade behavior, where
  `edit` is reachable with `driver_schedule_edit` regardless of `_show`).
