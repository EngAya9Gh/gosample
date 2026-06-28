# Money Transfers — Frontend Migration Plan

> Read [`01-foundation.md`](01-foundation.md) first. This plan conforms to the
> shared `/app/api` JSON layer, the `{data,meta}` envelope, the server-side
> `DataTable` contract, 422→form-field mapping, permission rendering, and
> RTL/i18n bridge defined there. It does **not** repeat them.

---

## 1. Module overview

Money Transfers record cash a driver moves between two locations for a client,
each leg guarded by an OTP. Full CRUD with a server-side list. Notably, **delete
is governed by the global `can-delete` Gate** (not a `money_transfer_delete`
gate, unlike most modules), and the OTPs/status are generated/managed by the
controller, not the create form.

- Nav group: **Tasks**.
- Access gate: **`money_transfer_access`**.

---

## 2. Current implementation (Blade / Velzon)

### Routes (`routes/web.php` 214–216)

| Method | URI | Name | Controller@action |
|---|---|---|---|
| DELETE | `/admin/money-transfers/destroy` | `admin.money-transfers.massDestroy` | `MoneyTransferController@massDestroy` |
| GET | `/admin/money-transfers` | `admin.money-transfers.index` | `MoneyTransferController@index` |
| GET | `/admin/money-transfers/create` | `admin.money-transfers.create` | `MoneyTransferController@create` |
| POST | `/admin/money-transfers` | `admin.money-transfers.store` | `MoneyTransferController@store` |
| GET | `/admin/money-transfers/{moneyTransfer}` | `admin.money-transfers.show` | `MoneyTransferController@show` |
| GET | `/admin/money-transfers/{moneyTransfer}/edit` | `admin.money-transfers.edit` | `MoneyTransferController@edit` |
| PUT/PATCH | `/admin/money-transfers/{moneyTransfer}` | `admin.money-transfers.update` | `MoneyTransferController@update` |
| DELETE | `/admin/money-transfers/{moneyTransfer}` | `admin.money-transfers.destroy` | `MoneyTransferController@destroy` |

> `massDestroy` declared before the resource (route-ordering — preserve).

### Controller actions + data passed

- **`index(Request)`** — `abort_if(Gate::denies('money_transfer_access'))`. Ajax →
  Yajra **server-side** DataTable of
  `MoneyTransfer::with(['driver','client','from_location','to_location'])`.
  `status` rendered as a **Bootstrap badge** (5-value map, see enum). Computed
  columns: `driver_name`, `client_english_name`, `from_location_name`,
  `to_location_name`. Non-ajax → `view('admin.moneyTransfers.index')`.
- **`create()`** — `abort_if(Gate::denies('money_transfer_create'))`; builds
  `drivers` (name by id), `clients` (`english_name` by id), `from_locations`,
  `to_locations` selects. → `admin.moneyTransfers.create`.
- **`store(StoreMoneyTransferRequest)`** — manual assignment; sets
  `status='new'`, and **generates both OTPs server-side**
  (`from_location_otp` / `to_location_otp` via `$moneyTransfer->generateOtp()`).
  → redirect index.
- **`edit(MoneyTransfer)`** — gate `money_transfer_edit`; same selects + loaded
  relations. → `admin.moneyTransfers.edit` (adds a **status** select).
- **`update(UpdateMoneyTransferRequest, MoneyTransfer)`** — sets client/driver/
  from/to/status/amount and saves. (Does **not** regenerate OTPs.)
- **`show(MoneyTransfer)`** — gate `money_transfer_show`; loads relations. →
  `admin.moneyTransfers.show`.
- **`destroy(MoneyTransfer)`** — **`$this->authorize('can-delete')`**; delete.
- **`massDestroy(MassDestroyMoneyTransferRequest)`** — **`$this->authorize('can-delete')`**;
  `MoneyTransfer::find(ids)` loop-delete → 204.

### Blade views

| View | Purpose |
|---|---|
| `moneyTransfers/index.blade.php` | server-side DataTable (`serverSide:true`); status badge |
| `moneyTransfers/create.blade.php` | create form (no status/otp fields) |
| `moneyTransfers/edit.blade.php` | edit form (adds **status** select) |
| `moneyTransfers/show.blade.php` | read-only detail |

### Index DataTable columns

`placeholder`, `id`, `driver_name` (`driver.name`), `client_english_name`
(`client.english_name`), `from_location_name` (`from_location.name`),
`to_location_name` (`to_location.name`), `status` (badge),
`from_location_otp`, `to_location_otp` (column `data:'to_location_otp'`; note the
list addColumn closures cover `from_location_otp` and `to_otp` — see open
questions), `amount`, `actions`.

### `status` enum / badge map (PRESERVE)

| value | badge class | label |
|---|---|---|
| `new` | `bg-primary` | New |
| `confirmed` | `bg-info` | Confirmed |
| `amount_received` | `bg-success` | Amount Received |
| `closed` | `bg-secondary` | Closed |
| `cancelled` | `bg-danger` | Cancelled |

### Form fields

- **Create** (`create.blade.php`): `driver_id` (select, required), `client_id`
  (select, required), `from_location_id` (select, required), `to_location_id`
  (select, optional), `amount` (number, step 0.01, required). **No** status / OTP
  inputs — those are server-generated/`'new'`.
- **Edit** (`edit.blade.php`): same + a **`status`** select (required).

### Permissions / Gates

- Access: **`money_transfer_access`**. Create: **`money_transfer_create`**.
  Edit: **`money_transfer_edit`**. Show: **`money_transfer_show`**.
- Delete (single + mass): **`can-delete`** (global gate via
  `$this->authorize('can-delete')`) — different from the typical
  `<module>_delete` pattern. The `MassDestroyMoneyTransferRequest::authorize()`
  checks `money_transfer_delete`, but the controller method **also** calls
  `authorize('can-delete')` — both gates effectively apply to mass delete.

### Form Requests (reuse verbatim)

- `StoreMoneyTransferRequest` / `UpdateMoneyTransferRequest` (identical rules):
  `driver_id required|integer`, `client_id required|integer`,
  `from_location_id required|integer`, `status required`,
  `from_location_otp required|string`, `to_otp required|string`,
  `amount required|numeric`.
  > ⚠️ **Mismatch:** the Form Request requires `status`, `from_location_otp`,
  > `to_otp`, but the **create form does not submit them** (controller sets
  > `status='new'` and generates the OTPs). With `Gate::allows(...)` authorize the
  > create currently relies on the rules being satisfied — yet the create form
  > omits those required fields. This means create validation as written would
  > fail unless these are injected. **Preserve current behavior**: do not change
  > the rules; the API store must replicate whatever the web flow does (set
  > status/OTPs before/within validation as the controller does). Flag this as a
  > pre-existing inconsistency to verify in parity testing.
- `MassDestroyMoneyTransferRequest` — `ids required|array`,
  `ids.* exists:money_transfers,id`; `authorize()` = `money_transfer_delete`.

### Special behaviors to preserve

- Server-side DataTable; status badge map.
- **Server-generated OTPs** (`generateOtp()`) on create; OTPs not regenerated on
  update.
- `status='new'` forced on create; status editable only on edit.
- `can-delete` gate for deletions.

---

## 3. Target design (Vue + Tailwind)

### Page mapping

| Blade view | New Vue view | Components |
|---|---|---|
| `moneyTransfers/index.blade.php` | `resources/js/vue/views/MoneyTransfers/MoneyTransfersList.vue` | `Breadcrumb`, `DataTable`, `StatusBadge`, `BaseButton`, `BaseModal`, `EmptyState` |
| `moneyTransfers/create.blade.php` | `resources/js/vue/views/MoneyTransfers/MoneyTransferForm.vue` (mode=create) | `Breadcrumb`, `BaseCard`, `FormSelect`, `FormInput`, `BaseButton` |
| `moneyTransfers/edit.blade.php` | same `MoneyTransferForm.vue` (mode=edit, shows status) | + status `FormSelect` |
| `moneyTransfers/show.blade.php` | `resources/js/vue/views/MoneyTransfers/MoneyTransferShow.vue` | `Breadcrumb`, `BaseCard`, `StatusBadge`, `BaseButton` |

- **List**: TasksList pattern — server-side `DataTable`, `status` → `StatusBadge`
  (5-value map), bulk delete gated by `canDelete()`, row View/Edit gated by
  `money_transfer_show`/`money_transfer_edit`, row Delete gated by `canDelete()`.
  Header "Add" gated by `can('money_transfer_create')`.
- **Form**: one `MoneyTransferForm.vue` with `mode` prop. Create hides status &
  OTPs (server sets them); Edit shows the `status` `FormSelect`. Selects fed by
  `/options`. 422 errors map onto fields (Blade error keys `driver`/`client`/
  `from_location`/`to_location` → corresponding `*_id` inputs).
- **Show**: detail card with relations + status badge + OTPs.

### Vue Router

```js
{ path: '/admin/money-transfers',          name: 'money-transfers.index',  component: () => import('../views/MoneyTransfers/MoneyTransfersList.vue'), meta: { perm: 'money_transfer_access' } }
{ path: '/admin/money-transfers/create',   name: 'money-transfers.create', component: () => import('../views/MoneyTransfers/MoneyTransferForm.vue'),  meta: { perm: 'money_transfer_create' } }
{ path: '/admin/money-transfers/:id/edit', name: 'money-transfers.edit',   component: () => import('../views/MoneyTransfers/MoneyTransferForm.vue'),  meta: { perm: 'money_transfer_edit' } }
{ path: '/admin/money-transfers/:id',      name: 'money-transfers.show',   component: () => import('../views/MoneyTransfers/MoneyTransferShow.vue'),  meta: { perm: 'money_transfer_show' } }
```

### nav.config.js

Money Transfers under the **Tasks** group, perm `money_transfer_access`. Reuse
existing entry.

### Empty / loading / error

`EmptyState` on empty list; `DataTable :loading`; 422 on forms; delete confirm via
`BaseModal`; success toasts via `useToast`.

---

## 4. Data / API contract

| Method | Path | Purpose | Reuses |
|---|---|---|---|
| GET | `/app/api/money-transfers` | List (server-side) | `index` query + `money_transfer_access` |
| GET | `/app/api/money-transfers/options` | drivers/clients/locations + status enum | `create`/`edit` plucks |
| GET | `/app/api/money-transfers/{id}` | Detail | `show` load + `money_transfer_show` |
| POST | `/app/api/money-transfers` | Create | `StoreMoneyTransferRequest` + `money_transfer_create` (sets status='new', generateOtp x2) |
| PUT | `/app/api/money-transfers/{id}` | Update | `UpdateMoneyTransferRequest` + `money_transfer_edit` |
| DELETE | `/app/api/money-transfers/{id}` | Delete one | `authorize('can-delete')` |
| DELETE | `/app/api/money-transfers` | Mass delete `{ids:[]}` | `MassDestroyMoneyTransferRequest` + `can-delete` |

### List request params

Foundation `q, sortKey, sortDir, page, pageSize` (no module-specific filters in
the current Blade index).

### List response (envelope)

```json
{ "data": [{
  "id": 12, "driver_name": "Mohammed Al-Harbi", "client_english_name": "King Faisal Lab",
  "from_location_name": "Lab East", "to_location_name": "Central Hub",
  "status": "new", "from_location_otp": "4821", "to_location_otp": "9012", "amount": "1500.00"
}], "meta": { "total": 88, "page": 1, "pageSize": 25 } }
```

`status` raw value drives `StatusBadge`.

### Detail response

```json
{ "data": { "id":12,"amount":"1500.00","status":"new",
  "from_location_otp":"4821","to_location_otp":"9012",
  "driver":{"id":7,"name":"…"},"client":{"id":3,"english_name":"…"},
  "from_location":{"id":1,"name":"…"},"to_location":{"id":2,"name":"…"} } }
```

### Options response

```json
{ "drivers":[{"value":7,"label":"…"}], "clients":[{"value":3,"label":"…"}],
  "from_locations":[…], "to_locations":[…],
  "statuses":[{"value":"new","label":"New"},{"value":"confirmed","label":"Confirmed"},
    {"value":"amount_received","label":"Amount Received"},{"value":"closed","label":"Closed"},
    {"value":"cancelled","label":"Cancelled"}] }
```

### Validation surfacing

Create/Update reuse the Form Requests → 422 mapped onto fields. Handle the
status/OTP-required quirk per §2 (replicate web flow).

---

## 5. Migration steps (ordered, checkable)

- [ ] backend: add `Api\MoneyTransferApiController` (`index`, `options`, `show`,
      `store`, `update`, `destroy`, `massDestroy`) reusing the existing queries +
      the three Form Requests + the same gates (`can-delete` for deletes); store
      sets `status='new'` + `generateOtp()` x2 exactly as the web flow.
- [ ] backend: register routes in `routes/app_api.php` (`DELETE /money-transfers`
      before `/money-transfers/{id}`).
- [ ] frontend: build `MoneyTransfersList.vue`, `MoneyTransferForm.vue`
      (create/edit modes), `MoneyTransferShow.vue`.
- [ ] frontend: status badge map, bulk delete (canDelete), delete confirm, toasts.
- [ ] wire router + Tasks-group nav.
- [ ] parity test: list badges, OTP generation on create, status editing on edit,
      `can-delete` gating, mass delete.
- [ ] flip nav Money Transfers to `/app` route (cutover).
- [ ] mark status in `00-README.md`.

---

## 6. Risks / must-not-break

- **OTP generation** — `from_location_otp`/`to_location_otp` are produced by
  `generateOtp()` on create and must never be set from the client; update must
  not regenerate them.
- **`status='new'` on create** — forced server-side; only editable via update.
- **`can-delete` gate** — deletes use the global gate, not
  `money_transfer_delete`. Render delete actions with `canDelete()` and keep the
  backend `authorize('can-delete')`.
- **Form-Request vs form mismatch** (status/OTP required but not submitted on
  create) — replicate current behavior; do not "fix" rules in this presentation
  migration.
- **status badge map** must match Blade colors/labels exactly.
- **Route ordering** — keep `massDestroy` before `{moneyTransfer}` wildcard.

---

## 7. Out of scope / open questions

- The list closures reference both `from_location_otp` and `to_otp` while the
  column config uses `to_location_otp`; confirm the canonical column key in the
  list response (`to_location_otp` per the DataTable `columns` config) during
  parity testing.
- The Store/Update validation requiring `status`/`from_location_otp`/`to_otp` that
  the create form never submits is a pre-existing inconsistency — verify how the
  web flow currently passes validation and mirror it; flag to the team.
