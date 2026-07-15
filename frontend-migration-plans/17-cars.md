# Cars — Frontend Migration Plan

> Read [`01-foundation.md`](01-foundation.md) first. Conforms to the shared
> `/app/api` layer, `{data,meta}` envelope, server-side DataTable contract,
> 422→form mapping, permission seeding, RTL/i18n, exports-to-existing-routes.

---

## 1. Module overview

Cars CRUD with driver linking and three relationship tabs on the detail screen
(link histories, tasks, GPS tracking). Linking a car to a driver triggers
**automatic link/unlink history side-effects** (`CarLinkHistory` rows) and detach
of the driver's previous car — this logic lives in `CarsController::update` and
**must not change**.

Nav group: **Drivers**. Gate: `car_access`. Blade routes `/admin/cars`.
List is a **server-side (ajax) DataTable**.

---

## 2. Current implementation (Blade / Velzon)

### Routes

| Method | URI | Name | Controller@action |
|---|---|---|---|
| DELETE | `admin/cars/destroy` | `admin.cars.massDestroy` | `Admin\CarsController@massDestroy` |
| resource | `admin/cars` | `admin.cars.*` | `Admin\CarsController` |

### Controller actions → data

- `index(Request)` — `car_access` guard. ajax → `DataTables::of(Car::withoutGlobalScope('enabled')->with('driver')->select('cars.*'))`.
  Server-side filters: `date_from`+`date_to` (whereBetween created_at), `imei`
  (exact), `plate_number` (exact), `status` (exact). Edited/added columns: `id`,
  `driver_name` (`driver->name`), `driver.mobile`, `imei`, `plate_number`,
  `model`, `color`, `contact_person`, `description`, `status` (badge), `actions`
  (rendered via `partials.datatablesActions` with gates `car_show`/`car_edit`/`car_delete`).
- `create()` — `car_create`. `$drivers = Driver::pluck('name','id')` + pleaseSelect.
- `store(StoreCarRequest)` — if a soft-deleted car shares the IMEI, suffix old
  imei with `_delete`; then `Car::create($request->all())`.
- `edit($id)` — `car_edit`. `$car` (withoutGlobalScope enabled, load driver), `$drivers`.
- `update(UpdateCarRequest,$id)` — soft-deleted-IMEI dedupe; `$car->update()`;
  **if driver changed**: detach previous cars of the new driver (+ `unlinked`
  history), write `unlinked` history for old driver and `linked` for new driver,
  flash an Arabic success message.
- `show($id)` — `car_show`. `$car->load('driver','carCarLinkHistories','carTasks')`.
  Detail view also lazy-uses `carTracking` and a delivery-photos tab.
- `destroy` / `massDestroy(MassDestroyCarRequest)` — `authorize('can-delete')`.

### Blade views

| View | Purpose |
|---|---|
| `admin/cars/index.blade.php` | Filter card (date/imei/plate/status) + server-side DataTable |
| `admin/cars/create.blade.php` | Create form |
| `admin/cars/edit.blade.php` | Edit form |
| `admin/cars/show.blade.php` | Detail + TabGroup: link histories / tasks / carTracking / delivery photos |
| `relationships/carCarLinkHistories.blade.php` | Link-history table |
| `relationships/carTasks.blade.php` | Tasks table |
| `relationships/carTracking.blade.php` | GPS temperature/tracking table |

### Permissions / Gates

`car_access`, `car_create`, `car_edit`, `car_show`; delete via **`can-delete`**
(`destroy`/`massDestroy`); `MassDestroyCarRequest` checks `car_delete`; the row
actions partial references `car_delete`.

### Form Request rules

- **StoreCarRequest** (`car_create`): `imei` req string `unique(cars)->whereNull(deleted_at)`;
  `plate_number` req string; `afaqi` req boolean; `model` nullable string; `color`
  nullable string; `contact_person` req string; `status` req `in:1,2`.
- **UpdateCarRequest** (`car_edit`): same, imei unique ignoring current id +
  whereNull deleted_at.
- **MassDestroyCarRequest** (`car_delete`): `ids` req array, `ids.*` exists:cars,id.

> Pass-through (saved via `$request->all()`, not in Form Request): `driver_id`,
> `description`. `afaqi` is posted as `0/1` from a select but validated `boolean`.

### Special behaviors to PRESERVE

- Server-side DataTable.
- IMEI soft-delete dedupe on store & update (suffix `_delete`).
- **Driver re-link side-effects** in `update` (history rows + detach + flash msg).
- `status` `1=Enabled,2=Disabled`; `afaqi` select No/Yes → `0/1`.
- `withoutGlobalScope('enabled')`.

---

## 3. Target design (Vue + Tailwind)

| Blade view | Vue view | Components |
|---|---|---|
| `cars/index` | `views/Cars/CarsList.vue` | `Breadcrumb`, `FilterBar`, `DataTable` (serverSide), `StatusBadge`, `BaseButton`, `BaseModal` |
| `cars/create`+`edit` | `views/Cars/CarForm.vue` | `FormInput`, `FormSelect` (driver/status/afaqi), textarea, `BaseCard`, `BaseButton` |
| `cars/show`+relationships | `views/Cars/CarShow.vue` | `Breadcrumb`, `BaseCard`, `TabGroup` (Link Histories / Tasks / Tracking / Delivery Photos), inner `DataTable`s, `Timeline` (optional for link history), `BaseAvatar` for driver |

### Vue Router
```
/admin/cars           → CarsList   meta:{perm:'car_access'}
/admin/cars/create    → CarForm    meta:{perm:'car_create'}
/admin/cars/:id/edit  → CarForm    meta:{perm:'car_edit'}
/admin/cars/:id       → CarShow    meta:{perm:'car_show'}
```

### nav.config.js
Cars entry exists under "Drivers" group, perm `car_access`.

### States
List: DataTable skeleton + EmptyState. Show tabs: per-tab EmptyState when a
relationship collection is empty. Delivery-photos tab: image grid (reuse
`getCarPhotos`-style payload) — render thumbnails; empty state if none.

---

## 4. Data / API contract

Base `/app/api/cars`. Standard CRUD per foundation.

| Method | Path | Purpose | Reuses |
|---|---|---|---|
| GET | `/app/api/cars` | server-side list | index query + `car_access` |
| GET | `/app/api/cars/options` | `{ drivers:[{value,label}], statuses, afaqi }` | create/edit bits |
| GET | `/app/api/cars/{id}` | detail incl. driver + relationships | `car_show` |
| POST | `/app/api/cars` | create (IMEI dedupe) | **StoreCarRequest** + `car_create` |
| PUT | `/app/api/cars/{id}` | update (+ re-link side-effects) | **UpdateCarRequest** + `car_edit` |
| DELETE | `/app/api/cars/{id}` | delete one | `can-delete` |
| DELETE | `/app/api/cars` (`{ids:[]}`) | mass delete | **MassDestroyCarRequest** + `can-delete` |

### List request params
`{ q, sortKey, sortDir, page, pageSize, date_from, date_to, imei, plate_number, status }`.

### List row shape
```json
{ "id":7, "driver_name":"…", "driver_mobile":"05…", "imei":"35…",
  "plate_number":"ABC-1234", "model":"…", "color":"…",
  "contact_person":"…", "description":"…", "status":"Enabled" }
```

### Detail shape
```json
{ "data": { "id":7, "driver_id":3, "driver":{…}, "imei":"…","plate_number":"…",
  "afaqi":0,"model":"…","color":"…","contact_person":"…","description":"…",
  "status":1, "carCarLinkHistories":[…], "carTasks":[…], "carTracking":[…],
  "deliveryPhotos":{…} } }
```

### Update body (driver-change side effects rely on these)
Include `driver_id` plus all validated fields; the API must run the SAME re-link
logic as `CarsController::update` (history rows, detach previous, flash/message).
Return the Arabic success message string in `{data, message}` so the SPA toast
can show it (parity with the Blade flash).

### Validation surfacing
422 mapped to fields: `imei, plate_number, afaqi, contact_person, status` (+
nullable `model/color`). `driver_id`/`description` are pass-through.

### Exports
No dedicated car export route in `web.php`; the DataTable's Copy/CSV/Excel/Print
buttons are client-side over the current page (yajra serverSide). Keep
`DataTable.vue`'s built-in export buttons (client-side) — do NOT invent a backend
car export.

---

## 5. Migration steps

- [ ] Backend: `Api\CarsApiController` — list (filters: date/imei/plate/status),
      options, show (with relationships + photos), store/update (reuse Form
      Requests + IMEI dedupe + **re-link side-effects** verbatim), destroy/massDestroy.
- [ ] Routes in `routes/app_api.php`.
- [ ] Frontend: `CarsList.vue`, `CarForm.vue`, `CarShow.vue` (4-tab TabGroup).
- [ ] Wire router + nav perm.
- [ ] Parity test: filters, status/afaqi labels, IMEI dedupe, driver re-link
      creates correct `linked`/`unlinked` history + detaches old car + shows
      Arabic message.
- [ ] Cutover: flip Cars nav.

## 6. Risks / must-not-break

- **Driver re-link side-effects**: the most logic-heavy path; reuse the exact
  controller code. Breaking it corrupts `car_link_history` (table name is
  `car_link_history`, singular).
- **IMEI soft-delete dedupe** on both store and update.
- `afaqi` posted `0/1` but validated `boolean`; `status` `in:1,2`.
- Pass-through `driver_id`/`description`.
- `withoutGlobalScope('enabled')` everywhere admin reads cars.
- Delete dual-gate (`can-delete` vs `car_delete` in MassDestroy request / partial).

## 7. Out of scope / open questions

- Delivery-photos upload happens via the mobile `DriverController@uploadPhotos`
  API; the admin Show tab is read-only display — confirm the read endpoint shape
  (reuse `getCarPhotos` logic for the SPA tab).
- `carTracking` data source (third-party GPS) — display only; confirm the
  relationship payload columns to surface.
