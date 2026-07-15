# Containers — Frontend Migration Plan

> Read [`01-foundation.md`](01-foundation.md) first. Conforms to the shared
> `/app/api` layer, `{data,meta}` envelope, DataTable contract, 422→form mapping,
> permissions, RTL/i18n. Good pilot-grade simple CRUD.

---

## 1. Module overview

Containers are the temperature-controlled boxes (ROOM / REFRIGERATE / FROZEN)
optionally assigned to a car. CRUD only. The Show screen can additionally list the
bags/samples currently inside the container, gated by a custom permission.

Nav group: **Drivers**. Gate: `container_access`. Blade routes `/admin/containers`.
List is a **client-side DataTable** (controller passes the full collection).

---

## 2. Current implementation (Blade / Velzon)

### Routes

| Method | URI | Name | Controller@action |
|---|---|---|---|
| DELETE | `admin/containers/destroy` | `admin.containers.massDestroy` | `Admin\ContainersController@massDestroy` |
| resource | `admin/containers` | `admin.containers.*` | `Admin\ContainersController` |

### Controller actions → data

- `index()` — `container_access`. `Container::with('car')->get()` →
  `admin.containers.index` (client-side table).
- `create()` — `container_create`. `$cars = Car::pluck('plate_number','id')` +
  pleaseSelect (`trans('translation.pleaseSelect')`).
- `store(StoreContainerRequest)` — `Container::create($request->all())`.
- `edit(Container)` — `container_edit`. `$cars`, loads `car`.
- `update(UpdateContainerRequest, Container)` — `$container->update()`.
- `show(Container)` — `container_show`. Loads `car`; **if Gate
  `view_bag_container_details` allows**, builds `$bags = Sample::where('container_id',$id)->get()->groupBy('bag_code')`.
- `destroy` / `massDestroy(MassDestroyContainerRequest)` — `authorize('can-delete')`.

### Blade views

| View | Purpose |
|---|---|
| `admin/containers/index.blade.php` | Client-side DataTable |
| `admin/containers/create.blade.php` | Create form: car (Select2 by plate), imei (sensor), model, type, description, status |
| `admin/containers/edit.blade.php` | Edit form |
| `admin/containers/show.blade.php` | Detail + (gated) bags-grouped-by-bag_code list |

### Permissions / Gates

`container_access`, `container_create`, `container_edit`, `container_show`;
custom **`view_bag_container_details`** (controls bag listing on Show); delete
via **`can-delete`** (`destroy`/`massDestroy`); `MassDestroyContainerRequest`
checks `container_delete`.

### Form Request rules

- **StoreContainerRequest** (`container_create`): `imei` req string; `type` req;
  `model` req; `status` req.
- **UpdateContainerRequest** (`container_edit`): `imei` req string; `model` req;
  `type` req; `status` req.
- **MassDestroyContainerRequest** (`container_delete`): `ids` array, `ids.*`
  exists:containers,id.

> Pass-through (saved via `$request->all()`, not validated): `car_id`, `description`.

### Model SELECTs
- `Container::TYPE_SELECT = ['ROOM'=>'ROOM','REFRIGERATE'=>'REFRIGERATE','FROZEN'=>'FROZEN']`
- `Container::STATUS_SELECT = ['1'=>'enabled','2'=>'disabled']`

### Special behaviors to PRESERVE

- Client-side DataTable.
- Car option label = **`plate_number`** (differs from car-link/car-driver which
  use imei).
- `type` enum + `status` `1/2` strings.
- Gated bag listing on Show (`view_bag_container_details`).

---

## 3. Target design (Vue + Tailwind)

| Blade view | Vue view | Components |
|---|---|---|
| `containers/index` | `views/Containers/ContainersList.vue` | `Breadcrumb`, `DataTable` (client-side), `StatusBadge` (status + type), `BaseButton`, `BaseModal` |
| `containers/create`+`edit` | `views/Containers/ContainerForm.vue` | `FormSelect` (car by plate / type / status), `FormInput` (imei, model), textarea (description), `BaseCard`, `BaseButton` |
| `containers/show` | `views/Containers/ContainerShow.vue` | `BaseCard` key/value; **gated bags section** — grouped cards/`DataTable` per `bag_code`; `EmptyState` if no bags or permission denied |

### Vue Router
```
/admin/containers           → ContainersList  meta:{perm:'container_access'}
/admin/containers/create    → ContainerForm   meta:{perm:'container_create'}
/admin/containers/:id/edit  → ContainerForm   meta:{perm:'container_edit'}
/admin/containers/:id       → ContainerShow   meta:{perm:'container_show'}
```

### nav.config.js
Containers entry exists under "Drivers" group, perm `container_access`.

### States
EmptyState for empty list and empty/denied bag section; delete confirm modal; toast.

---

## 4. Data / API contract

Base `/app/api/containers`.

| Method | Path | Reuses |
|---|---|---|
| GET | `/app/api/containers` | index query (with car) + `container_access` — return full list (client-side) |
| GET | `/app/api/containers/options` | `{ cars:[{value:id,label:plate_number}], types:[{value,label}], statuses:[{value,label}] }` |
| GET | `/app/api/containers/{id}` | detail + (gated) `bags` | `container_show` |
| POST | `/app/api/containers` | **StoreContainerRequest** + `container_create` |
| PUT | `/app/api/containers/{id}` | **UpdateContainerRequest** + `container_edit` |
| DELETE | `/app/api/containers/{id}` / `…` (`{ids:[]}`) | `can-delete` + **MassDestroy** request |

### List row shape
```json
{ "id":3, "imei":"35…", "car_plate":"ABC-1234", "model":"…",
  "type":"FROZEN", "status":"enabled", "description":"…" }
```

### Detail shape
```json
{ "data": { "id":3, "car_id":7, "car":{…}, "imei":"…","model":"…",
  "type":"FROZEN","status":"1","description":"…",
  "canViewBags": true,
  "bags": { "BAG-001": [ {sample…}, … ], "BAG-002":[…] } } }
```
- `canViewBags` reflects the `view_bag_container_details` gate (rendering only;
  the API still gates the data server-side). When false, omit/empty `bags`.

### Validation surfacing
422 mapped to: `imei, type, model, status` (+ pass-through `car_id`, `description`).

### Exports
No dedicated export route; use `DataTable.vue` client-side export buttons.

---

## 5. Migration steps

- [ ] Backend: `Api\ContainersApiController` — list (with car), options, show
      (reuse gated-bags logic), store/update (Form Requests), destroy/massDestroy.
- [ ] Routes in `routes/app_api.php`.
- [ ] Frontend: `ContainersList.vue`, `ContainerForm.vue`, `ContainerShow.vue`.
- [ ] Wire router + nav perm.
- [ ] Parity test: type/status labels, car option by plate_number, gated bag
      listing matches `view_bag_container_details`.
- [ ] Cutover: flip Containers nav. (Good early pilot alongside Zones.)

## 6. Risks / must-not-break

- **Car option label is `plate_number`** here (vs imei elsewhere).
- Pass-through `car_id`/`description` not in Form Request.
- `status` values are STRING `'1'`/`'2'` keyed; type enum exact-cased.
- Gated bag section (`view_bag_container_details`) must stay server-enforced.
- Delete dual-gate (`can-delete` vs `container_delete`).

## 7. Out of scope / open questions

- Bag display formatting (grouping by `bag_code`, which sample fields to show) —
  confirm against `containers/show.blade.php` bag markup before finalizing the
  detail payload.
