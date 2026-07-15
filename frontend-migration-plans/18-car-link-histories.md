# Car Link Histories + Car Drivers — Frontend Migration Plan

> Read [`01-foundation.md`](01-foundation.md) first. Conforms to the shared
> `/app/api` layer, `{data,meta}` envelope, DataTable contract, 422→form mapping,
> permissions, RTL/i18n. Two tightly-coupled pivot/audit modules in one plan.

---

## 1. Module overview

Two small CRUD modules that record the relationship between cars and drivers:

- **Car Link Histories** (`car_link_history` table) — an append-style audit log
  of `linked`/`unlinked` actions between a car and a driver. Rows are normally
  created automatically by `CarsController::update` and the mobile
  `DriverController::releaseCar`; these admin CRUD screens allow manual
  inspection/entry.
- **Car Drivers** (`car_drivers` pivot table) — explicit car↔driver assignment
  rows with an `is_linked` flag.

Both are **client-side DataTables** (controllers pass full collections, no ajax).
Nav group: **Drivers**. Gates: `car_link_history_access`, `car_driver_access`.

---

## 2. Current implementation (Blade / Velzon)

### Routes

| Method | URI | Name | Controller@action |
|---|---|---|---|
| DELETE | `admin/car-drivers/destroy` | `admin.car-drivers.massDestroy` | `Admin\CarDriverController@massDestroy` |
| resource | `admin/car-drivers` | `admin.car-drivers.*` | `Admin\CarDriverController` |
| DELETE | `admin/car-link-histories/destroy` | `admin.car-link-histories.massDestroy` | `Admin\CarLinkHistoryController@massDestroy` |
| resource | `admin/car-link-histories` | `admin.car-link-histories.*` | `Admin\CarLinkHistoryController` |

### CarLinkHistoryController actions → data

- `index()` — `car_link_history_access`. `CarLinkHistory::with('driver','car')->get()`
  → `admin.carLinkHistories.index` (client-side table).
- `create()` — `car_link_history_create`. `$drivers = Driver::pluck('name','id')`,
  `$cars = Car::pluck('imei','id')` (both prepended pleaseSelect).
- `store(StoreCarLinkHistoryRequest)` — `CarLinkHistory::create($request->all())`.
- `edit(CarLinkHistory)` — `car_link_history_edit`. Same option lists; loads driver+car.
- `update(UpdateCarLinkHistoryRequest, …)` / `show` (`car_link_history_show`).
- `destroy` / `massDestroy(MassDestroyCarLinkHistoryRequest)` — `authorize('can-delete')`.

### CarDriverController actions → data

- `index()` — `car_driver_access`. `CarDriver::with('car','driver')->get()`
  → `admin.carDrivers.index` (client-side table).
- `create()` — `car_driver_create`. `$cars = Car::pluck('imei','id')`,
  `$drivers = Driver::pluck('name','id')`.
- `store(StoreCarDriverRequest)` / `edit`/`update(UpdateCarDriverRequest)`/`show`.
- `destroy`/`massDestroy(MassDestroyCarDriverRequest)` — `authorize('can-delete')`.

### Blade views

| View | Purpose |
|---|---|
| `admin/carLinkHistories/{index,create,edit,show}.blade.php` | Link-history CRUD (client-side table) |
| `admin/carDrivers/{index,create,edit,show}.blade.php` | Car-driver pivot CRUD (client-side table) |

### Permissions / Gates

- Link history: `car_link_history_access/create/edit/show`; delete `can-delete`
  (MassDestroy request checks `car_link_history_delete`).
- Car driver: `car_driver_access/create/edit/show`; delete `can-delete`
  (MassDestroy request checks `car_driver_delete`).

### Form Request rules

- **StoreCarLinkHistoryRequest / UpdateCarLinkHistoryRequest**: `car_id` req int;
  `action` req. (`action` is one of CarLinkHistory::ACTION_SELECT =
  `linked`/`unlinked`. Note: `driver_id` is posted by the form but NOT validated.)
- **MassDestroyCarLinkHistoryRequest** (`car_link_history_delete`): `ids` array,
  `ids.*` exists:car_link_histories,id.
- **StoreCarDriverRequest / UpdateCarDriverRequest**: `car_id` req int; `driver_id`
  req int; `is_linked` req int (min -2147483648 / max 2147483647).
- **MassDestroyCarDriverRequest** (`car_driver_delete`): `ids` array, `ids.*`
  exists:car_drivers,id.

### Special behaviors to PRESERVE

- Both lists are **client-side** DataTables (full collection passed).
- Option lists: cars keyed by **`imei`** label (not plate number); drivers by `name`.
- `action` enum values `linked`/`unlinked`; `is_linked` is a raw integer flag.
- These manual entries do NOT replicate the automatic side-effects in
  `CarsController::update` — manual create just inserts a row.

---

## 3. Target design (Vue + Tailwind)

| Blade view | Vue view | Components |
|---|---|---|
| `carLinkHistories/index` | `views/CarLinkHistories/CarLinkHistoriesList.vue` | `Breadcrumb`, `DataTable` (client-side), `StatusBadge` (action), `BaseButton`, `BaseModal` |
| `carLinkHistories/create`+`edit` | `views/CarLinkHistories/CarLinkHistoryForm.vue` | `FormSelect` (car by imei / driver / action), `BaseCard`, `BaseButton` |
| `carLinkHistories/show` | `views/CarLinkHistories/CarLinkHistoryShow.vue` | `BaseCard`, key/value rows; optional `Timeline` |
| `carDrivers/index` | `views/CarDrivers/CarDriversList.vue` | `Breadcrumb`, `DataTable` (client-side), `StatusBadge` (is_linked), `BaseButton`, `BaseModal` |
| `carDrivers/create`+`edit` | `views/CarDrivers/CarDriverForm.vue` | `FormSelect` (car/driver), `FormToggle` or `FormSelect` for `is_linked`, `BaseCard`, `BaseButton` |
| `carDrivers/show` | `views/CarDrivers/CarDriverShow.vue` | `BaseCard` key/value |

Because both lists are small, pass `:rows` directly to `DataTable` in **client-side
mode** (no `serverSide`). DataTable handles search/sort/paginate in the browser —
matching today's behavior.

### Vue Router
```
/admin/car-link-histories            → CarLinkHistoriesList  meta:{perm:'car_link_history_access'}
/admin/car-link-histories/create     → CarLinkHistoryForm    meta:{perm:'car_link_history_create'}
/admin/car-link-histories/:id/edit   → CarLinkHistoryForm    meta:{perm:'car_link_history_edit'}
/admin/car-link-histories/:id        → CarLinkHistoryShow    meta:{perm:'car_link_history_show'}
/admin/car-drivers                   → CarDriversList        meta:{perm:'car_driver_access'}
/admin/car-drivers/create            → CarDriverForm         meta:{perm:'car_driver_create'}
/admin/car-drivers/:id/edit          → CarDriverForm         meta:{perm:'car_driver_edit'}
/admin/car-drivers/:id               → CarDriverShow         meta:{perm:'car_driver_show'}
```

### nav.config.js
Both entries exist under "Drivers" group with their perm keys.

### States
EmptyState when collection empty; delete-confirm `BaseModal`; toast on success.

---

## 4. Data / API contract

### Car Link Histories — base `/app/api/car-link-histories`
| Method | Path | Reuses |
|---|---|---|
| GET | `/app/api/car-link-histories` | index query (with driver,car) + `car_link_history_access` — return full list (client-side table) |
| GET | `/app/api/car-link-histories/options` | `{ cars:[{value:id,label:imei}], drivers:[{value:id,label:name}], actions:[{value:'linked',label},{value:'unlinked',label}] }` |
| GET | `/app/api/car-link-histories/{id}` | `car_link_history_show` |
| POST | … | **StoreCarLinkHistoryRequest** + `car_link_history_create` |
| PUT | `…/{id}` | **UpdateCarLinkHistoryRequest** + `car_link_history_edit` |
| DELETE | `…/{id}` / `…` (`{ids:[]}`) | `can-delete` + **MassDestroy** request |

List row shape:
```json
{ "id":12, "car_imei":"35…", "driver_name":"…", "action":"linked",
  "created_at":"2026-06-27 10:00" }
```

### Car Drivers — base `/app/api/car-drivers`
| Method | Path | Reuses |
|---|---|---|
| GET | `/app/api/car-drivers` | index query (with car,driver) + `car_driver_access` |
| GET | `/app/api/car-drivers/options` | `{ cars:[{value:id,label:imei}], drivers:[{value:id,label:name}] }` |
| GET | `/app/api/car-drivers/{id}` | `car_driver_show` |
| POST | … | **StoreCarDriverRequest** + `car_driver_create` |
| PUT | `…/{id}` | **UpdateCarDriverRequest** + `car_driver_edit` |
| DELETE | `…/{id}` / `…` (`{ids:[]}`) | `can-delete` + **MassDestroy** request |

List row shape:
```json
{ "id":5, "car_imei":"35…", "driver_name":"…", "is_linked":1,
  "created_at":"…" }
```

### Validation surfacing
- Link history form fields: `car_id`, `action` (+ `driver_id` pass-through).
- Car driver form fields: `car_id`, `driver_id`, `is_linked`. 422 mapped to each.

### Exports
No dedicated export routes; rely on `DataTable.vue` client-side export buttons.

---

## 5. Migration steps

- [ ] Backend: `Api\CarLinkHistoriesApiController` + `Api\CarDriversApiController`
      reusing each existing query + Form Request + gates.
- [ ] Routes in `routes/app_api.php`.
- [ ] Frontend: 6 views (List/Form/Show × 2 modules), client-side DataTable.
- [ ] Wire router + nav perms.
- [ ] Parity test: option labels (cars by **imei**), `action` enum, `is_linked`
      integer, delete gating.
- [ ] Cutover: flip both nav items.

## 6. Risks / must-not-break

- **Car option label is `imei`**, not plate number (differs from Containers,
  which uses plate_number). Don't "improve" it.
- Link-history `driver_id` is posted but not validated — include it in the POST body.
- `is_linked` is a wide-range integer flag, not a strict boolean — keep `int`.
- Table name is `car_link_history` (singular) but mass-delete validates against
  `car_link_histories` — mirror the existing request as-is; do not retune rules.
- Delete dual-gate (`can-delete` in controller vs `*_delete` in MassDestroy request).
- These manual screens must NOT trigger the automatic Car-update side-effects.

## 7. Out of scope / open questions

- Whether to merge Car Drivers into the Cars module UI (today it is a separate
  nav item with its own pivot table) — keep separate for parity.
- Confirm `created_at` display format expected (Blade prints raw model value).
