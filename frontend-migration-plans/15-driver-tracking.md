# Driver Tracking (Client Dashboard) — Frontend Migration Plan

> Read [`01-foundation.md`](01-foundation.md) first. This plan conforms to the
> shared `/app/api` JSON layer, the `{data,meta}` envelope, 422→form-field
> mapping, permission rendering, and RTL/i18n bridge defined there. It does
> **not** repeat them.

---

## 1. Module overview

A **read-only client-facing dashboard** that shows, for each driver carrying the
client's tasks **today**, the driver's task route as a vertical timeline. It is a
single page (no CRUD, no exports), with a live client-side name filter and a
manual refresh. **It is NOT a map** — there is no Google Maps / Leaflet render in
the Blade view; driver lat/lng are computed by the controller but only the route
list/timeline is displayed. (The controller derives `lat`/`lng` from
`car.carTracking` for a potential future map, but the current UI ignores them.)

- Nav group: **Tasks**.
- Access gate: **`driver_tracking_access`**.
- Route name: `admin.driver-tracking`.

---

## 2. Current implementation (Blade / Velzon)

### Routes (`routes/web.php` 141)

| Method | URI | Name | Controller@action |
|---|---|---|---|
| GET | `/admin/driver-tracking` | `admin.driver-tracking` | `DriverTrackingController@clientDashboard` |

Single GET; the "refresh" button is just a link back to the same route. The
`driver_name` filter is sent as a **query param** (`?driver_name=...`) and applied
server-side; the Blade also does a **client-side** show/hide filter in JS for
instant feedback.

### Controller action + data passed (`clientDashboard`)

- `abort_if(Gate::denies('driver_tracking_access'))`.
- Determines `isAdminUser` (roles `Admin`/`Super Admin`/`SuperAdmin` or
  `is_admin`) and `clientId = user->client_id`.
- If **not** admin and **no** `client_id` → `abort(403, 'This page is only
  accessible for clients.')`.
- Builds **today's** active tasks: `Task::whereBetween('pickup_time', [today
  start, today end])->whereNotIn('status', ['CLOSED','NO_SAMPLES'])`; if
  `clientId` set, scope `where('billing_client', $clientId)` (admins see all).
- Collects distinct `driver_id`s from those tasks.
- Loads drivers `Driver::with(['car.carTracking'])->whereIn('id', $driverIds)`;
  optional `driver_name` filter (`name`/`username` LIKE).
- For each driver, fetches **that driver's** today tasks with
  `['from','to','client']`, ordered by `route_order` then `poririty` (sic),
  excluding `CLOSED`/`NO_SAMPLES`. Builds `routeInfo` rows (pickup/destination,
  `eta_minutes` = `cumulative_eta ?? eta`, `estimated_arrival`,
  `belongs_to_client` flag, status). Also computes the driver's latest
  `lat`/`lng` from `car.carTracking` (unused by the view).
- Returns `view('admin.tasks.driver-tracking', compact('driverData'))` where
  each `driverData[]` = `['driver' => {id,name,lat,lng}, 'tasks' => $driverTasks]`.

### Blade view (`resources/views/admin/tasks/driver-tracking.blade.php`)

- Breadcrumb + a search box (`#driverSearchInput`, Arabic placeholder "بحث فورى
  باسم السائق...") + a **Refresh** link.
- A responsive grid of **driver cards** (`col-xl-4 col-md-6`). Each card:
  - header: avatar (first letter of name), driver name, and an **Active count
    badge** (`{{ $activeTaskCount }} Active`, yellow if >0 else green) where
    active = tasks not in `['COLLECTED','CLOSED']`.
  - body: a scrollable (`data-simplebar`, max-height 310px) **vertical task
    timeline** (`list-group`). Each task item shows a step state:
    - **completed** (`COLLECTED`/`CLOSED`) → green check, strikethrough title;
    - **next** (first non-completed / previous completed) → primary map-pin icon;
    - **upcoming** → numbered grey circle;
    - title = client english name + a status badge; rows for **من/إلى** (from/to)
      location, task `#id`, formatted `pickup_time` (`Y-m-d h:i A`), and **ETA**
      from `estimated_arrival_time` (`h:i A`) highlighted when "next".
  - empty card → "No tasks assigned".
- Page-level empty → a centered "No drivers with active tasks found." card.
- `@section('script')`: vanilla JS filters `.driver-card-container` by the
  `data-driver-name` attribute as the user types (instant, client-side).

### Permissions / Gates

- Access: **`driver_tracking_access`**.
- Additional runtime guard: client users must have a `client_id` (else 403);
  admins bypass. This is **row-level data scoping** (client sees only their
  `billing_client` tasks) and must be preserved.

### Form Requests

None (read-only GET; `driver_name` is an optional query param).

### Special behaviors to preserve

- **Today-only** task window + `whereNotIn(['CLOSED','NO_SAMPLES'])`.
- **Client scoping** by `billing_client = client_id`; admin bypass.
- Driver task ordering `route_order` then `poririty`.
- Active-count badge logic (`not in COLLECTED/CLOSED`).
- Timeline step states (completed/next/upcoming) and the strikethrough on done.
- Instant name filter (client-side) + server-side `driver_name` filter.
- **No map** — do not introduce one in the migration (the lat/lng are unused;
  could power a future map but that's out of scope).

---

## 3. Target design (Vue + Tailwind)

### Page mapping

| Blade view | New Vue view | Components |
|---|---|---|
| `admin/tasks/driver-tracking.blade.php` | `resources/js/vue/views/DriverTracking/DriverTrackingDashboard.vue` | `Breadcrumb`, `FormInput` (search), `BaseButton` (refresh), `BaseCard`, `BaseAvatar`, `StatusBadge`, `Timeline`, `EmptyState` |

- Reuse `vue-build`'s **`Timeline`** component for each driver's task sequence
  (maps cleanly to the list-group step UI: completed/next/upcoming states).
- A responsive grid of `BaseCard`s (one per driver). Card header: `BaseAvatar`
  (initial), name, and an Active-count `StatusBadge`/pill (yellow if >0 else
  green). Card body: a scrollable `Timeline` of tasks; each node = client name +
  status badge, from/to (RTL labels من/إلى), task id (force `dir=ltr`), pickup
  time, ETA.
- **Search**: `FormInput` with `ri-search-line`. Implement instant client-side
  filtering on the already-loaded `driverData` (mirrors the Blade JS) — no need to
  refetch per keystroke; optionally also pass `driver_name` to the API on
  Refresh.
- **Refresh**: `BaseButton` that re-calls the API (replaces the link reload).
- Empty states: per-card "No tasks assigned"; page-level `EmptyState` "No drivers
  with active tasks found."

### Vue Router

```js
{ path: '/admin/driver-tracking', name: 'driver-tracking',
  component: () => import('../views/DriverTracking/DriverTrackingDashboard.vue'),
  meta: { perm: 'driver_tracking_access' } }
```

### nav.config.js

Driver Tracking under the **Tasks** group, perm `driver_tracking_access`. Reuse
existing entry; confirm key.

### Empty / loading / error

- Loading: skeleton cards or `BaseCard` placeholders while fetching.
- 403 (non-client without `client_id`) → the API returns 403; route guard +
  backend gate; show a friendly "clients only" message matching the Blade abort.
- Error: toast + retry.

---

## 4. Data / API contract

One read endpoint (no CRUD).

| Method | Path | Purpose | Reuses |
|---|---|---|---|
| GET | `/app/api/driver-tracking` | Client driver-tracking dashboard data | `clientDashboard` logic + `driver_tracking_access` + client scoping |

### Request params

- `driver_name` (optional) — same server-side LIKE filter as today.

### Response shape

Not a paginated list (no `{data,meta}` table envelope needed) — return the
per-driver structure the page renders:

```json
{
  "data": [
    {
      "driver": { "id": 7, "name": "Mohammed Al-Harbi", "lat": 24.71, "lng": 46.67 },
      "active_count": 3,
      "tasks": [
        {
          "id": 10428,
          "client": "King Faisal Lab",
          "from": "Lab East",
          "to": "Central Hub",
          "status": "NEW",
          "pickup_time": "2026-06-27 09:10 AM",
          "estimated_arrival_time": "09:35 AM",
          "eta_minutes": 25,
          "belongs_to_client": true,
          "step": "next"            // "completed" | "next" | "upcoming"
        }
      ]
    }
  ],
  "meta": { "is_admin": false, "client_id": 3 }
}
```

- Compute `active_count` and `step` server-side (parity with the Blade logic:
  completed = `COLLECTED`/`CLOSED`; next = first non-completed; else upcoming) so
  the SPA renders identically. Keep `lat`/`lng` in the payload (future map) even
  though the current UI ignores them.
- Pre-format `pickup_time` (`Y-m-d h:i A`) and `estimated_arrival_time` (`h:i A`)
  exactly as Blade.

### Validation

None (read-only). The 403 client/admin rule is enforced in the controller logic
and must be preserved in the API method.

---

## 5. Migration steps (ordered, checkable)

- [ ] backend: add `Api\DriverTrackingApiController@clientDashboard` returning the
      JSON above, reusing the **exact** today-window + client-scoping + ordering +
      step/active-count logic from `DriverTrackingController` (extract the
      per-driver builder so both share it, or call the same code path). Keep the
      `driver_tracking_access` gate and the client/admin 403 rule.
- [ ] backend: register the GET route in `routes/app_api.php`.
- [ ] frontend: build `DriverTrackingDashboard.vue` (grid of `BaseCard` +
      `Timeline`), instant client-side name filter, Refresh button.
- [ ] wire router + Tasks-group nav + perm.
- [ ] parity test vs Blade: same drivers/tasks for a client user, same active
      count, same step states/strikethrough, same RTL labels, same empty states.
- [ ] flip nav Driver Tracking to `/app` route (cutover).
- [ ] mark status in `00-README.md`.

---

## 6. Risks / must-not-break

- **Client data scoping** — a client must see only `billing_client = client_id`
  tasks; admins see all. Security-critical; preserve the abort-403 for non-admin
  users with no `client_id`.
- **Today-only window + status exclusion** (`CLOSED`/`NO_SAMPLES`) — must match.
- **Step / active-count logic** — completed (`COLLECTED`/`CLOSED`), next,
  upcoming; active = not in `COLLECTED`/`CLOSED`. Don't drift.
- **Ordering** — `route_order` then `poririty` (note the misspelled column; use it
  verbatim).
- **No map** — do not add a map; current scope is the timeline only.
- **Date formats** — `Y-m-d h:i A` / `h:i A` must match Blade output.

---

## 7. Out of scope / open questions

- A live driver **map** using the already-computed `lat`/`lng` is a natural future
  enhancement but is **not** in the current Blade UI — out of scope here.
- Auto-refresh/polling: today it's a manual link reload; consider an interval
  refresh later (not required for parity).
- Confirm the `driver_tracking_access` permission actually exists in the seeder /
  is granted to client roles (carry-over permission-gap risk from the foundation
  notes); test with a role that has it.
