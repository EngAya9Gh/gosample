# Attendances — Frontend Migration Plan

> Conforms to [`01-foundation.md`](01-foundation.md) (JSON API layer reusing Form
> Requests + Gates, strangler-fig cutover, `{data,meta}` DataTable contract,
> 422→form fields, permission rendering, RTL/i18n). This file only states what is
> **specific** to Attendances; it does not repeat foundation conventions.

## 1. Module overview

Driver attendance records (check-in / check-out + derived KPIs: delay, late flag,
overtime, early-leave, total worked). Nav group **Drivers** (`45-attendances.md`
in the index). Gate family: `attendance_access` / `attendance_create` /
`attendance_edit` / `attendance_show`; deletes via the global `can-delete` Gate
(note: `MassDestroyAttendanceRequest::authorize()` checks `attendance_delete`, see
§6). Route prefix `admin.attendances.*`.

**Critical context — auto-calculation must not change.** A scheduled command
`attendance:calc-auto` (`app/Console/Commands/CalculateAutoAttendanceCommand.php`,
run hourly in `app/Console/Kernel.php`) derives `checkin_time` / `checkout_time` /
`source` directly from task `collection_date` / `close_date`. **It writes ONLY
those three fields and never touches the KPI fields** (delay/is_late/overtime/
early_leave/total_worked stay 0). Separately, every manual `store`/`update` from
this screen dispatches `ProcessAttendanceKPIJob` which recomputes the KPI fields
from the entered times + the driver's shift/working hours. **The SPA only
displays/edits via the existing endpoints; it must reproduce both side effects
exactly (date-prefixing of `H:i` times + the KPI job dispatch) and must never
write KPI fields directly or alter how `calc-auto` works.**

## 2. Current implementation (Blade / Velzon)

### Routes (`routes/web.php` ~73–75, via `Route::resource`)
| Method | URI | Name | Action |
|---|---|---|---|
| GET | `/admin/attendances` | `admin.attendances.index` | `AttendancesController@index` (Yajra server-side when `request()->ajax()`) |
| GET | `/admin/attendances/create` | `admin.attendances.create` | `@create` |
| POST | `/admin/attendances` | `admin.attendances.store` | `@store` |
| GET | `/admin/attendances/{attendance}` | `admin.attendances.show` | `@show` |
| GET | `/admin/attendances/{attendance}/edit` | `admin.attendances.edit` | `@edit` |
| PUT/PATCH | `/admin/attendances/{attendance}` | `admin.attendances.update` | `@update` |
| DELETE | `/admin/attendances/{attendance}` | `admin.attendances.destroy` | `@destroy` |
| DELETE | `/admin/attendances/destroy` | `admin.attendances.massDestroy` | `@massDestroy` |

Supporting (owned by Drivers module, **reused** by the create/edit shift picker):
| GET | `/admin/drivers/{id}/get-shifts` | `admin.drivers.getShifts` | `DriversController@getShifts` |

### Controller actions / data
- `index`: Gate `attendance_access`. **Yajra server-side** (`serverSide:true`,
  `processing:true`, `pageLength:100`, default sort col index 1 (`id`) desc).
  Query: `Attendance::with('driver')`. Computed/added columns returned as JSON:
  `id`, `driver_name` (`driver.name`), `driver_mobile` (`driver.mobile`),
  `checkin_time`, `checkout_time`, `is_late` (HTML badge Late/On Time),
  `delay_minutes`, `overtime_minutes` (HTML: `+overtime` green, else `-early_leave`
  red, else 0), `source` (`ucfirst`, default `manual`), `actions` (HTML edit/delete
  buttons gated by `attendance_edit` / `can-delete`). `rawColumns`: actions,
  placeholder, is_late, overtime_minutes.
- `create`: Gate `attendance_create`. Passes `$drivers` (`Driver::pluck('name','id')`
  with a "please select" prepend).
- `store(StoreAttendanceRequest)`: takes `$request->all()`; if `checkin_time` /
  `checkout_time` present, **prefixes today's date** (`Y-m-d H:i:s`); `Attendance::create`;
  **dispatches `ProcessAttendanceKPIJob`**; redirect to index.
- `edit`: Gate `attendance_edit`. Passes `$attendance` (with `driver`) + `$drivers`.
  Times displayed as `H:i` via `Carbon::parse(...)->format('H:i')`.
- `update(UpdateAttendanceRequest, $attendance)`: `$request->all()`; date used =
  `$attendance->created_at` date (fallback today); prefixes that date onto
  `checkin_time` / `checkout_time` **only when the value is ≤8 chars** (i.e. a bare
  `H:i:s`); `$attendance->update`; **dispatches `ProcessAttendanceKPIJob`**; redirect.
- `show`: Gate `attendance_show`. Read-only table: id, driver name, checkin, checkout.
- `destroy`: `authorize('can-delete')`; `delete()` (SoftDeletes); `back()`.
- `massDestroy(MassDestroyAttendanceRequest)`: `authorize('can-delete')` (and the
  request itself re-checks `attendance_delete`); `whereIn('id', ids)->delete()`.

### Blade views
| File | Purpose |
|---|---|
| `admin/attendances/index.blade.php` | Yajra server-side DataTable + bulk-delete button (gated `can-delete`) |
| `admin/attendances/create.blade.php` | Create form: driver (Select2), **shift (cascading Select2 from `get-shifts`)**, checkin/checkout (`type=time`), delay_minutes, overtime_minutes, hidden `source=manual` |
| `admin/attendances/edit.blade.php` | Edit form: driver, checkin/checkout, delay_minutes, overtime_minutes, hidden `source=manual` (**no shift picker on edit**) |
| `admin/attendances/show.blade.php` | Read-only detail (id, driver, checkin, checkout) |

### Form fields submitted vs validated (IMPORTANT divergence — preserve as-is)
The create form posts `driver_id`, `shift_id`, `checkin_time`, `checkout_time`,
`delay_minutes`, `overtime_minutes`, `source`. **`StoreAttendanceRequest` /
`UpdateAttendanceRequest` only validate `checkin_time` (string, nullable) and
`checkout_time` (string, nullable).** Everything else is mass-assigned via
`$request->all()` against the model's `$fillable`. Do **not** add validation rules
in the API — reuse the Form Request unchanged. Manually-entered `delay_minutes` /
`overtime_minutes` may be **overwritten** by `ProcessAttendanceKPIJob` when a
shift/working-hours baseline exists (the job computes delay & overtime; see
`app/Jobs/ProcessAttendanceKPIJob.php`). Parity = same behavior.

### Cascading shift picker
On create, selecting a driver fires `GET /admin/drivers/{driverId}/get-shifts`
returning shift rows `{ id, shift_number, start_time, end_time }`; rendered as
`Shift #<n> (start - end)`. Empty list ⇒ disabled + "no shifts" hint.

## 3. Target design (Vue + Tailwind)

Views under `resources/js/vue/views/Attendances/`:

| Blade view | Vue view | vue-build components |
|---|---|---|
| `index.blade.php` | `AttendancesList.vue` | `Breadcrumb`, `DataTable` (server-side via `useDataTable`), `StatusBadge` (Late/On-Time), `BaseButton`, `BaseModal` (delete confirm), `EmptyState` |
| `create.blade.php` | `AttendanceCreate.vue` | `Breadcrumb`, `BaseCard`, `FormSelect` (driver), `FormSelect` (shift, dependent), `FormInput type=time` ×2, `FormInput type=number` (delay/overtime), `BaseButton` |
| `edit.blade.php` | `AttendanceEdit.vue` | same as create **minus shift picker** |
| `show.blade.php` | `AttendanceShow.vue` | `Breadcrumb`, `BaseCard` (definition list) |

- **List:** server-side DataTable. Columns mirror the JSON keys: `id`, `driver_name`,
  `driver_mobile`, `checkin_time`, `checkout_time` (force `dir=ltr` on times),
  `is_late` → `StatusBadge` (danger "Late" / success "On Time"), `delay_minutes`,
  `overtime_minutes` (custom cell: `+overtime` green / `-early_leave` red / `0`),
  `source` (capitalized). Row actions: Edit (`can('attendance_edit')`),
  Delete (`canDelete()`). Bulk delete (`canDelete()`). Default sort `id` desc,
  page size 100 (to match Blade) but allow user change.
- **Create/Edit:** the cascading shift `FormSelect` repopulates via the options
  endpoint (`?driver=<id>`); checkin/checkout are `type=time` (submit bare `H:i`,
  backend prefixes the date). Hidden `source=manual` sent on both. Show field
  errors from the 422 payload mapped onto `checkin_time` / `checkout_time`.
- **States:** loading skeleton on table; `EmptyState` when no rows; toast on save/
  delete; 403 view if perm guard denies.

### Router / nav
- Routes: `/admin/attendances` (`meta.perm:'attendance_access'`),
  `/admin/attendances/create` (`attendance_create`),
  `/admin/attendances/:id/edit` (`attendance_edit`),
  `/admin/attendances/:id` (`attendance_show`). Mirror existing `nav.config.js`
  `route` values (foundation §3 recommendation (a)).

## 4. Data / API contract

Base `/app/api/attendances` (new `Api\AttendancesApiController`, delegating to the
existing query + Form Requests + Gates). Reuse the Drivers module's options where
possible.

| Method | Path | Purpose | Reuses |
|---|---|---|---|
| GET | `/app/api/attendances` | List (server-side) | index query + Gate `attendance_access` |
| GET | `/app/api/attendances/options` | `{ drivers:[{value,label}] }` for the form | `Driver::pluck` from `create`/`edit` |
| GET | `/app/api/attendances/shifts?driver=<id>` | Shift options for cascading picker | **delegate to `DriversController@getShifts`** (do not reimplement) |
| GET | `/app/api/attendances/{id}` | Detail (show) | Gate `attendance_show` |
| POST | `/app/api/attendances` | Create | **`StoreAttendanceRequest`** + Gate `attendance_create`; same date-prefix + **dispatch `ProcessAttendanceKPIJob`** |
| PUT | `/app/api/attendances/{id}` | Update | **`UpdateAttendanceRequest`** + Gate `attendance_edit`; same ≤8-char date-prefix + **dispatch `ProcessAttendanceKPIJob`** |
| DELETE | `/app/api/attendances/{id}` | Delete one | `authorize('can-delete')` |
| DELETE | `/app/api/attendances` | Mass delete `{ids:[]}` | **`MassDestroyAttendanceRequest`** + `can-delete` |

### List request params
`{ q, sortKey, sortDir, page, pageSize }` (foundation contract). Optional filters
none in Blade today — keep parity (no extra filters) unless explicitly added later.

### List row shape (keys = DataTable column keys, pre-formatted like Blade)
```json
{ "id": 42, "driver_name": "Mohammed Al-Harbi", "driver_mobile": "0500000000",
  "checkin_time": "2026-06-27 08:05:00", "checkout_time": "2026-06-27 16:10:00",
  "is_late": true, "delay_minutes": 5, "overtime_minutes": 0,
  "early_leave_minutes": 0, "source": "Manual" }
```
Return raw `is_late` (bool) + raw minute ints so the Vue cells render the badge/
color exactly as Blade did (do **not** ship the Blade HTML strings).

### Detail / store / update bodies
- Detail: `{ "data": { id, driver:{id,name,mobile}, checkin_time, checkout_time,
  delay_minutes, overtime_minutes, source } }`.
- Create body: `{ driver_id, shift_id, checkin_time:"08:05", checkout_time:"16:10",
  delay_minutes, overtime_minutes, source:"manual" }`.
- Update body: same minus `shift_id`.
- Validation 422 surfaces on `checkin_time` / `checkout_time` only (matches the
  Form Request). Required-driver is enforced only in the UI today (`required` attr),
  not server-side — keep that parity; do not add a server rule.

## 5. Migration steps (ordered, checkable)
- [ ] Backend: `Api\AttendancesApiController` with list/options/shifts/show/store/
      update/destroy/massDestroy; reuse `Store/Update/MassDestroyAttendanceRequest`
      + Gates; **replicate date-prefix logic and `ProcessAttendanceKPIJob::dispatch`
      in store & update exactly**; `shifts` delegates to `DriversController@getShifts`.
- [ ] Register routes in `routes/app_api.php`.
- [ ] Frontend: build `AttendancesList / AttendanceCreate / AttendanceEdit /
      AttendanceShow` from vue-build components; wire `useDataTable`.
- [ ] Implement cascading shift `FormSelect` (refetch on driver change).
- [ ] Wire router routes + perms; confirm `nav.config.js` entry.
- [ ] Parity test vs Blade: identical columns/badges/sort/page-size; KPI fields
      after save match the job's output; deletes hidden without `can-delete`.
- [ ] Flip nav to `/app` route (Blade stays for rollback).

## 6. Risks / must-not-break
- **Auto-calc semantics:** never change `attendance:calc-auto` or write KPI fields
  from the SPA. The SPA edits the same fields the manual Blade form did and lets
  the **same** `ProcessAttendanceKPIJob` recompute KPIs.
- **KPI job dispatch:** store & update *must* dispatch the job, or delay/overtime/
  is_late stop updating — a silent data regression.
- **Date-prefix rules:** store prefixes *today*; update prefixes the record's
  `created_at` date and only when value ≤8 chars. Mis-replicating this corrupts
  stored datetimes.
- **Gate divergence:** `MassDestroyAttendanceRequest` authorizes on
  `attendance_delete` while the controller also calls `authorize('can-delete')`.
  Keep BOTH checks. SPA bulk-delete button shown by `canDelete()`.
- **Validation parity:** only `checkin_time`/`checkout_time` are validated; don't
  add rules for driver/shift/minutes.
- **SoftDeletes:** Attendance soft-deletes — list query must keep default scope.

## 7. Out of scope / open questions
- Should the SPA expose `expected_start/expected_end/total_worked_minutes` (present
  in `$fillable`/job output) as read-only columns? Blade does not show them — keep
  parity unless product asks.
- `source` filter / date-range filter on the list: not present in Blade; defer.
- The cascading shift picker depends on Drivers' `getShifts`; coordinate cutover so
  that endpoint is reachable from `/app/api` (thin proxy is fine; no logic change).
