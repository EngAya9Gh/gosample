# Shipments — Frontend Migration Plan

> Read [`01-foundation.md`](01-foundation.md) first. This plan conforms to the
> shared `/app/api` JSON layer, the `{data,meta}` envelope, the server-side
> `DataTable` contract, 422→form-field mapping, permission rendering, and
> RTL/i18n bridge defined there. It does **not** repeat them.

---

## 1. Module overview

Shipments are Ayenati/Lean courier handoffs created from an existing **NEW** task.
The resource is **create-only**: `Route::resource('shipments', …, ['except' =>
['edit','update','destroy']])` — there is **no edit, no update, no resource
destroy, and no massDestroy**. The high-value behavior lives in three custom POST
actions on the **show** page that call the external Ayenati API and mutate task +
shipment state (assign driver, deliver, update notification).

- Nav group: **Tasks**.
- Access gate: **`shipment_access`**. Create gate: **`task_create`** (shipments
  reuse the task create gate, not a `shipment_create` gate — see §2).

---

## 2. Current implementation (Blade / Velzon)

### Routes (`routes/web.php` 189, 220–222)

| Method | URI | Name | Controller@action |
|---|---|---|---|
| GET | `/admin/shipments` | `admin.shipments.index` | `ShipmentsController@index` |
| GET | `/admin/shipments/create` | `admin.shipments.create` | `ShipmentsController@create` |
| POST | `/admin/shipments` | `admin.shipments.store` | `ShipmentsController@store` |
| GET | `/admin/shipments/{shipment}` | `admin.shipments.show` | `ShipmentsController@show` |
| POST | `/admin/shipments/{shipment}/update-notification` | `admin.shipments.updateNotification` | `ShipmentsController@updateAyenatiNotification` |
| POST | `/admin/shipments/{shipment}/assign-driver` | `admin.shipments.assignDriver` | `ShipmentsController@assignDriver` |
| POST | `/admin/shipments/{shipment}/deliver` | `admin.shipments.deliver` | `ShipmentsController@deliver` |

> `except => ['edit','update','destroy']` ⇒ only `index/create/store/show`.

> ⚠️ **The `updateNotification` route points to `ShipmentsController@updateAyenatiNotification`,
> a method that does NOT exist in the controller** (verified: not defined). The
> show Blade does not render a form to it either. Treat this route as currently
> dead — do **not** build SPA UI for it, and do not add the missing method as
> part of this presentation migration (logic change). Flag to the team.

### Controller actions + data passed

- **`index(Request)`** — `abort_if(Gate::denies('shipment_access'))`. Ajax →
  Yajra **server-side** DataTable of `Shipment::with(['task'])`. `status_code`
  column rendered as a **Bootstrap badge** via a hardcoded map (see enum below).
  `to_location`/`from_location` resolve from `task->to->name` / `task->from->name`.
  Row actions use gates `shipment_show`, `shipment_edit`, `shipment_delete`
  (edit/delete actions are dead since the routes don't exist). Non-ajax →
  `view('admin.shipments.index')`.
- **`create()`** — `abort_if(Gate::denies('task_create'))`. Builds `from_locations`,
  `to_locations`, `drivers` (scoped by `assigned_client_ids` when present), and
  `tasks = Task::where('status','NEW')->pluck('id')`. → `admin.shipments.create`.
- **`store(StoreShipmentRequest)`** — manual field assignment (NOT mass-assign):
  generates `pickup_otp = rand(1000,9999)`, sets `journey_type=0`,
  `sla_code="STAT"`, `status_code="Assigned"`, persists task/from/to/driver. The
  driver FCM notification line is **commented out** in store. → redirect index.
- **`show(Shipment)`** — `abort_if(Gate::denies('shipment_show'))`; loads `task`,
  `drivers = Driver::all()`, `task`. → `admin.shipments.show` (detail table +
  Assign-Driver form + Deliver form).
- **`assignDriver(Request, $shipmentId)`** — reads `driver` input; finds the
  latest `AyenatiToken`; calls `updateNotificationCall(...)` (Ayenati
  `updateNotificationDetails`). On success: generates `dropoff_otp`, sets
  `driver_id`, `status_code='confirmed'`; **updates the linked Task**
  (`driver_id`, `pickup_time=now()`, save) and **sends FCM** `sendNotification('New Task'…)`;
  then chains `dispatched` → `delivered` notification calls + `updateDropOffOTP`.
  Every Ayenati call logs an `ApiAyenati` row and may dispatch
  `GenerateAtenatiTokenJob` on failure. Redirects back with flash on error.
- **`deliver(Request, $shipmentId)`** — requires an assigned driver; calls
  `deliverCall(...)` (Ayenati `updateNotificationDetails` status `delivered`); on
  success sets `status_code='delivered'`. Redirects back.
- Helper methods (`updateNotificationCall`, `deliverCall`, `dispatchshipment`,
  `updateDropOffOTP`, `updateDropOffOTPNew`) hit `https://api.lean.sa/p-ayenati/...`.

### Blade views

| View | Purpose |
|---|---|
| `shipments/index.blade.php` | server-side DataTable (`serverSide:true`); `status_code` badge |
| `shipments/create.blade.php` | create form (fields below) |
| `shipments/show.blade.php` | detail table + **Assign Driver** form (POST assignDriver, driver `<select>`) + **Deliver** form (POST deliver); shows `session('error')` alert |
| `shipments/edit.blade.php` | exists in views dir but **no route** — ignore |

### Index DataTable columns (visible; several commented out)

`placeholder`, `id`, `carrier`, `from_location` (`task.from.name`), `to_location`
(`task.to.name`), `reference_number`, `pickup_otp`, `status_code` (badge),
`dropoff_otp`, `batch`, `journey_type`, `sla_code`, `task`, `created_at`,
`actions`. (sender_*/receiver_* columns are commented out in Blade.)

### `status_code` enum / badge map (PRESERVE)

| value | badge class | label |
|---|---|---|
| `Assigned` | `bg-primary` | Assigned |
| `confirmed` | `bg-info` | Confirmed |
| `dispatched` | `bg-warning` | Dispatched |
| `delivered` | `bg-success` | Delivered |

### Create form fields

`sender_name, sender_long, sender_lat, sender_mobile` (text, optional),
`receiver_name, receiver_long, receiver_lat, receiver_mobile` (text, optional),
`carrier` **(required)**, `reference_number` **(required in UI)**, `batch`
**(required)**, `task` **(required select, only NEW tasks)**, `from_location`
**(required select)**, `to_location` (select, must differ from from_location),
`driver_id` (select; field name is `driver_id`, error key in Blade is `driver`).

### Permissions / Gates

- Access: **`shipment_access`** (index). Show: **`shipment_show`**.
- Create: **`task_create`** (both `create()` and `StoreShipmentRequest::authorize()`).
- `assignDriver`/`deliver`/`updateNotification`: **no explicit Gate** in the
  controller methods today — they rely only on the `auth` middleware + the
  show-page gate. (Document this; the API methods should at minimum keep
  `shipment_show`/`shipment_access` to avoid weakening, but do not add new
  business gates as part of presentation migration.)

### Form Request (reuse verbatim)

`StoreShipmentRequest` — `authorize()` = `task_create`. Rules:
`from_location required`, `to_location required|different:from_location`,
`task required`, `driver_id required|numeric`, `batch required`; all `sender_*`,
`receiver_*`, `carrier`, `reference_number` are `nullable`.

> Note: `carrier`/`reference_number` are marked `required` in the **Blade UI**
> but only `nullable` in the Form Request. Keep the Form Request as the
> authoritative validator (foundation rule); the Vue form may show them as
> required for UX parity, but must not block on rules the backend doesn't enforce.

### Special behaviors to preserve

- **Ayenati external API calls** + `ApiAyenati` logging + `GenerateAtenatiTokenJob`
  dispatch on token failure.
- **assignDriver side effects**: shipment `dropoff_otp`/`status_code`/`driver_id`,
  **task `driver_id` + `pickup_time`**, **FCM `sendNotification`**, and the
  chained dispatched→delivered→updateDropOffOTP sequence.
- **store**: server-generated `pickup_otp`, fixed `journey_type/sla_code/status_code`.
- Server-side DataTable; `status_code` badge; `assigned_client_ids` scoping in
  `create()`.
- `session('error')` flash on the show page.

---

## 3. Target design (Vue + Tailwind)

### Page mapping

| Blade view | New Vue view | Components |
|---|---|---|
| `shipments/index.blade.php` | `resources/js/vue/views/Shipments/ShipmentsList.vue` | `Breadcrumb`, `DataTable`, `StatusBadge`, `BaseButton`, `EmptyState` |
| `shipments/create.blade.php` | `resources/js/vue/views/Shipments/ShipmentForm.vue` | `Breadcrumb`, `BaseCard`, `FormInput`, `FormSelect`, `BaseButton` |
| `shipments/show.blade.php` | `resources/js/vue/views/Shipments/ShipmentShow.vue` | `Breadcrumb`, `BaseCard`, `StatusBadge`, `FormSelect`, `BaseButton`, `BaseModal`, `ToastHost` |

- **List**: server-side `DataTable` (TasksList pattern). `status_code` →
  `StatusBadge` using the four-value map above. Row actions: View only
  (`shipment_show`); no Edit/Delete (no routes). Header "Add Shipment" button
  gated by `can('task_create')`.
- **Form**: locations/tasks/drivers as `FormSelect` (`options` endpoint). `task`
  options = NEW tasks only. `to_location` ≠ `from_location` (UX validation; server
  enforces via Form Request). 422 errors map onto fields (`driver` error key →
  `driver_id` field).
- **Show**: detail card mirroring the Blade table (id, carrier, sender_*,
  receiver_*, reference_number, pickup_otp, notes, batch, dropoff_otp, sla_code,
  linked task/driver/from/to, status_code badge). Below it, two **action panels**:
  - **Assign Driver**: `FormSelect` of drivers + Assign button → POST assignDriver.
    Use a `BaseModal`/confirm because of the heavy side effects; on success show a
    success toast and refresh the detail; on failure show the `error` message
    (the API should return the same flash text as JSON).
  - **Deliver**: a single confirm Deliver button → POST deliver.
- **No Notification panel** — the `updateNotification` route is dead (see §2).

### Vue Router

```js
{ path: '/admin/shipments',        name: 'shipments.index',  component: () => import('../views/Shipments/ShipmentsList.vue'), meta: { perm: 'shipment_access' } }
{ path: '/admin/shipments/create', name: 'shipments.create', component: () => import('../views/Shipments/ShipmentForm.vue'),  meta: { perm: 'task_create' } }
{ path: '/admin/shipments/:id',    name: 'shipments.show',   component: () => import('../views/Shipments/ShipmentShow.vue'),  meta: { perm: 'shipment_show' } }
```

### nav.config.js

Shipments under the **Tasks** group, perm `shipment_access`. Reuse existing entry.

### Empty / loading / error

- `EmptyState` on empty list; `DataTable :loading`.
- Show-page action results surface via `useToast` (success) and an inline
  error/alert region (parity with `session('error')`).

---

## 4. Data / API contract

| Method | Path | Purpose | Reuses |
|---|---|---|---|
| GET | `/app/api/shipments` | List (server-side) | `index` query + `shipment_access` |
| GET | `/app/api/shipments/{id}` | Detail (+ drivers for the assign select) | `show` load + `shipment_show` |
| GET | `/app/api/shipments/options` | locations/tasks(NEW)/drivers for create | `create` plucks + `task_create` |
| POST | `/app/api/shipments` | Create | `StoreShipmentRequest` + `task_create` |
| POST | `/app/api/shipments/{id}/assign-driver` | Assign driver (full Ayenati chain) | `assignDriver` logic verbatim |
| POST | `/app/api/shipments/{id}/deliver` | Deliver | `deliver` logic verbatim |

> No DELETE / mass-delete / update endpoints — the resource excludes them. Do not
> add them.

### List request params

Foundation `q, sortKey, sortDir, page, pageSize` (no module-specific filters in
the current Blade index).

### List response (envelope)

```json
{ "data": [{
  "id": 55, "carrier": "Aramex", "from_location": "Lab East", "to_location": "Central Hub",
  "reference_number": "REF-901", "pickup_otp": "4821", "status_code": "Assigned",
  "dropoff_otp": "", "batch": "B12", "journey_type": 0, "sla_code": "STAT",
  "task": 10428, "created_at": "2026-06-27 09:10"
}], "meta": { "total": 120, "page": 1, "pageSize": 25 } }
```

`status_code` raw value drives `StatusBadge`; pre-format `created_at` as Blade does.

### Detail response

```json
{ "data": { "id":55,"carrier":"…","sender_name":"…","sender_long":"…","sender_lat":"…",
  "sender_mobile":"…","receiver_name":"…","receiver_long":"…","receiver_lat":"…",
  "receiver_mobile":"…","reference_number":"…","pickup_otp":"…","notes":"…","batch":"…",
  "dropoff_otp":"…","sla_code":"…","status_code":"Assigned",
  "task":{"id":10428,"driver_id":7,"driver":{"name":"…"},"from":{"name":"…"},"to":{"name":"…"}},
  "drivers":[{"value":7,"label":"Mohammed Al-Harbi"}] } }
```

### Options response (create)

```json
{ "from_locations":[{"value":1,"label":"…"}], "to_locations":[…],
  "drivers":[…], "tasks":[{"value":10428,"label":10428}] }
```

### Action responses

- `assign-driver` / `deliver`: success → `{ "data": { ...updated shipment... } }`
  (200). Failure (no token / Ayenati down / driver missing) → return the same
  message the controller flashes, e.g. `422`/`409` `{ "message": "unable to access api." }`
  / `"No access token available."` / `"Please assign driver first."`, so the SPA
  shows the identical text.

### Validation surfacing

Create reuses `StoreShipmentRequest` → 422 mapped onto fields. The Blade `driver`
error key maps to the `driver_id` input.

---

## 5. Migration steps (ordered, checkable)

- [ ] backend: add `Api\ShipmentsApiController` (`index`, `show`, `options`,
      `store`, `assignDriver`, `deliver`) — **delegating to the exact existing
      logic** (extract the Ayenati helper sequence so the api action calls the
      same code path, or call the controller methods' logic directly). Reuse
      `StoreShipmentRequest` + the same gates.
- [ ] backend: register routes in `routes/app_api.php` (no edit/update/destroy/
      massDestroy; include `assign-driver` + `deliver`).
- [ ] frontend: build `ShipmentsList.vue`, `ShipmentForm.vue`, `ShipmentShow.vue`.
- [ ] frontend: assign-driver confirm modal + deliver confirm + toasts + error
      surfacing matching the flash text.
- [ ] wire router + Tasks-group nav.
- [ ] parity test: list badges, create validation, assign-driver side effects
      (task driver/pickup_time, FCM, Ayenati logs, OTP/status chain), deliver.
- [ ] flip nav Shipments to `/app` route (cutover).
- [ ] mark status in `00-README.md`.

---

## 6. Risks / must-not-break

- **assignDriver side effects are the whole point** — the api action MUST run the
  identical chain: Ayenati `updateNotificationDetails` (confirmed → dispatched →
  delivered) + `updateDropOffOTP`, set `dropoff_otp`/`status_code='confirmed'`/
  `driver_id`, update the **Task** `driver_id` + `pickup_time`, and **send the FCM
  `New Task` notification**. Skipping any of these breaks operations.
- **Ayenati token handling** — must still fall back to `GenerateAtenatiTokenJob`
  on failure and log every call to `ApiAyenati`.
- **store generated fields** — `pickup_otp`, `journey_type=0`, `sla_code='STAT'`,
  `status_code='Assigned'` must be produced server-side, never trusted from JSON.
- **Create-only resource** — do not introduce edit/update/delete; the dead
  `edit.blade.php` view is not a feature.
- **Dead `updateNotification` route** — don't build UI for it; flag the missing
  `updateAyenatiNotification` method separately.
- **status_code badge map** must match Blade colors/labels exactly.
- **`task_create` gate** is the create gate (not `shipment_create`); preserve.

---

## 7. Out of scope / open questions

- Should `assignDriver`/`deliver` gain an explicit Gate? They have none today;
  adding one is a logic change — defer to the team.
- Confirm whether `assign-driver`/`deliver` should be sync (blocking on the
  external Ayenati calls, as today) or queued; current behavior is synchronous —
  preserve unless told otherwise (note the up-to-120s HTTP timeouts → SPA needs a
  loading state / generous client timeout).
- The missing `updateAyenatiNotification` method + dead route: fix or remove?
  (Out of scope for presentation migration.)
