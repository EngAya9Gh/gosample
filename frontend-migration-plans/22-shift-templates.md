# Shift Templates — Frontend Migration Plan

> Conforms to [`01-foundation.md`](01-foundation.md) (JSON API layer reusing Gates,
> strangler-fig cutover, `{data,meta}` DataTable contract, 422→form fields,
> permission rendering, RTL/i18n). Only Shift-Template specifics are below.

## 1. Module overview

Reusable shift definitions (name + start/end time) used as the baseline for
attendance KPI calculation and the cascading shift picker on the Attendances
create screen. Nav group **Drivers**. **There is no dedicated permission family
for this module** — every action is gated by `attendance_access` (it is treated as
a sub-screen of Attendances). There is **no `show` view** (index / create / edit
only, plus destroy/massDestroy).

> Note: `ShiftTemplatesController@show` exists and renders
> `admin.shiftTemplates.show`, but **that Blade file does not exist** and nothing
> links to it. Do not build a Show view (per the task scope).

## 2. Current implementation (Blade / Velzon)

### Routes (`routes/web.php` ~77–79, via `Route::resource`)
| Method | URI | Name | Action |
|---|---|---|---|
| GET | `/admin/shift-templates` | `admin.shift-templates.index` | `@index` |
| GET | `/admin/shift-templates/create` | `admin.shift-templates.create` | `@create` |
| POST | `/admin/shift-templates` | `admin.shift-templates.store` | `@store` |
| GET | `/admin/shift-templates/{shiftTemplate}/edit` | `admin.shift-templates.edit` | `@edit` |
| PUT/PATCH | `/admin/shift-templates/{shiftTemplate}` | `admin.shift-templates.update` | `@update` |
| DELETE | `/admin/shift-templates/{shiftTemplate}` | `admin.shift-templates.destroy` | `@destroy` |
| DELETE | `/admin/shift-templates/destroy` | `admin.shift-templates.massDestroy` | `@massDestroy` |
| (GET) | `/admin/shift-templates/{shiftTemplate}` | `admin.shift-templates.show` | `@show` — **no Blade; do not migrate** |

### Controller actions / data — `App\Http\Controllers\Admin\ShiftTemplatesController`
- **Every action is gated by `Gate::denies('attendance_access')`** — including
  create/store/edit/update/destroy/massDestroy. There are NO `attendance_create` /
  `_edit` / `can-delete` checks here. Reproduce exactly: a user with
  `attendance_access` can do everything on this module.
- `index`: `ShiftTemplate::all()` → **client-side** DataTable (no Yajra/ajax).
- `create` / `edit`: just render the form (`edit` passes `$shiftTemplate`).
- `store` / `update`: **inline validation, no Form Request** —
  `['name'=>'required','start_time'=>'required','end_time'=>'required']`.
  `ShiftTemplate::create($request->all())` / `->update($request->all())`; redirect
  to index.
- `destroy`: gated by `attendance_access` (NOT `can-delete`); `delete()`; `back()`.
- `massDestroy`: `whereIn('id', ids)->delete()` (no Form Request, no gate beyond
  the route's `attendance_access` middleware context — note the method itself has
  no abort guard, unlike the others).

### Model — `App\Models\ShiftTemplate`
`$fillable = ['name','start_time','end_time']`. No SoftDeletes. `start_time` /
`end_time` stored as time/datetime; Blade renders via `Carbon::parse(...)->format('H:i')`.

### Blade views
| File | Purpose |
|---|---|
| `admin/shiftTemplates/index.blade.php` | **client-side** DataTable (`pageLength:25`, sort col 0 desc, `select:false`, no export buttons); delete via **SweetAlert2** confirm → hidden per-row DELETE form |
| `admin/shiftTemplates/create.blade.php` | Form: `name` (text, required), `start_time` (`type=time`, required), `end_time` (`type=time`, required) |
| `admin/shiftTemplates/edit.blade.php` | Same fields prefilled (times formatted `H:i`) |

### Behaviors to preserve
- **Client-side** list (small dataset), not server-side Yajra.
- Action buttons (Edit / Delete) are **always rendered** in Blade — no per-button
  `@can`. The whole screen is already behind `attendance_access`. Keep that; do
  not introduce `can('shift_*')` gates that don't exist.
- Inline validation rules (name/start/end required) — preserve, no Form Request.

## 3. Target design (Vue + Tailwind)

Views under `resources/js/vue/views/ShiftTemplates/`:

| Blade view | Vue view | vue-build components |
|---|---|---|
| `index.blade.php` | `ShiftTemplatesList.vue` | `Breadcrumb`, `DataTable` (**client-side mode**: pass full `rows`), `StatusBadge`/pill for times, `BaseButton`, `BaseModal` (delete confirm, replaces SweetAlert2), `EmptyState` |
| `create.blade.php` | `ShiftTemplateCreate.vue` | `Breadcrumb`, `BaseCard`, `FormInput` (name), `FormInput type=time` ×2, `BaseButton` |
| `edit.blade.php` | `ShiftTemplateEdit.vue` | same as create, prefilled |

- **List:** client-side DataTable. Columns: `id`, `name`, `start_time` (green pill,
  `dir=ltr`), `end_time` (red pill, `dir=ltr`), actions (Edit + Delete). Default
  sort `id` desc, page size 25. No bulk-delete in the Blade UI today, but the
  `massDestroy` route exists — **optional**: add a `canDelete()`-gated bulk action
  only if product wants it; otherwise keep single-row delete to match Blade.
- **Create/Edit:** three fields; `BaseModal` replaces the SweetAlert2 dialog for
  delete confirmation. Errors from 422 mapped onto `name`/`start_time`/`end_time`.
- **States:** `EmptyState` when no templates; toast on save/delete.

### Router / nav
- Routes: `/admin/shift-templates`, `/admin/shift-templates/create`,
  `/admin/shift-templates/:id/edit` — all `meta.perm:'attendance_access'`.
- nav.config.js: entry exists under Drivers (verify); perm key `attendance_access`.

## 4. Data / API contract

Base `/app/api/shift-templates` (new `Api\ShiftTemplatesApiController`).
**No Form Request exists** — replicate the inline validation in the api method via
`$request->validate([...])` (same three required rules). This is the one module
where there is no Form Request to reuse; the rules are trivial and must match
exactly.

| Method | Path | Purpose | Gate |
|---|---|---|---|
| GET | `/app/api/shift-templates` | List (client-side: return all) | `attendance_access` |
| GET | `/app/api/shift-templates/{id}` | Detail (for edit prefill) | `attendance_access` |
| POST | `/app/api/shift-templates` | Create | `attendance_access` + inline validate |
| PUT | `/app/api/shift-templates/{id}` | Update | `attendance_access` + inline validate |
| DELETE | `/app/api/shift-templates/{id}` | Delete one | `attendance_access` |
| DELETE | `/app/api/shift-templates` | Mass delete `{ids:[]}` (only if UI adds it) | `attendance_access` |

> Because the list is client-side, the list endpoint may return a plain
> `{ "data": [ ... ] }` (all rows, no `meta` paging) — `DataTable` in client mode
> paginates locally. Keep it simple; do not bolt server-side paging onto a screen
> that never had it.

### Row / detail shape (pre-format times to `H:i` like Blade)
```json
{ "id": 3, "name": "Morning", "start_time": "08:00", "end_time": "16:00" }
```

### Validation
`name` required, `start_time` required, `end_time` required → standard 422
`{message,errors}`; map onto the three form fields.

## 5. Migration steps (ordered, checkable)
- [ ] Backend: `Api\ShiftTemplatesApiController` (list/show/store/update/destroy
      [+ massDestroy if UI keeps it]); gate **all** methods on `attendance_access`;
      inline-validate `name/start_time/end_time` required (no Form Request).
- [ ] Pre-format `start_time`/`end_time` to `H:i` in responses.
- [ ] Register routes in `routes/app_api.php`.
- [ ] Frontend: `ShiftTemplatesList` (client-side DataTable) + Create/Edit forms;
      replace SweetAlert2 delete with `BaseModal`.
- [ ] Wire router + nav (perm `attendance_access`).
- [ ] Parity test vs Blade: client-side paging, time pills, delete confirm, same
      gate behavior (everyone with `attendance_access` can CRUD).
- [ ] Flip nav to `/app` route.

## 6. Risks / must-not-break
- **Gate model is unusual:** the ENTIRE module (create/edit/delete) is gated only
  by `attendance_access`, NOT `can-delete` and NOT a `shift_*` family. Do not
  "harden" deletes with `can-delete` — that would change who can delete.
- **No Form Request:** don't invent one; replicate the inline rules verbatim. If a
  future refactor adds a `Store/UpdateShiftTemplateRequest`, switch to it then.
- **Client-side list:** don't convert to server-side — keep parity & simplicity.
- **Downstream coupling:** these templates feed the Attendances shift picker and
  `ProcessAttendanceKPIJob` baseline. Changing field names/shape would break
  attendance KPI calc. Keep `name/start_time/end_time` exactly.
- **`massDestroy` has no abort guard** in the controller; if the SPA never exposes
  bulk delete, this endpoint stays unused (same as today's Blade, which also never
  calls it). Do not add an unguarded bulk path on the API without the same scoping.

## 7. Out of scope / open questions
- **No Show view** — confirmed not migrated (orphan `show` action + missing Blade).
- Recurrence / day-of-week scheduling: **the model has none** (only name + start +
  end). There is no per-day or recurring-pattern UI to migrate; a shift template is
  a flat time window. Flag if product expects recurrence — it is not present today.
- Should bulk delete be surfaced? Blade never does; default to single-row delete.
