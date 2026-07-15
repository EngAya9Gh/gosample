# Dashboard (Analytics) — Frontend Migration Plan

> **✅ Implemented (Inertia).** Built as an Inertia page, not the JSON-API approach
> described below (foundation switched to Inertia.js). Live:
> `App\Http\Controllers\App\DashboardController@index` → `Inertia::render('Dashboard/Analytics')`
> with `stats` (same cached keys + client scoping as `HomeController@index`),
> `topDrivers`, `notifications` (client-scoped rail, gated by `client_access`), and
> `samplesReport` (the temperature donut, re-fetched on date-range change via an
> Inertia **partial reload** `only: ['samplesReport','range']`). Donut renders the
> real `{labels,values}` (inline-colored SVG; ApexCharts optional later). KPIs use
> the animated `StatCard`; deltas omitted (no real series). Area chart kept
> cosmetic (no backing series). **Deferred:** the global **emergency banner**
> polling (`/check-emergency`, admins only) — a layout-level concern shared with
> [`07-map.md`](07-map.md), to be done as a global `AppShell` feature.

> Read [`01-foundation.md`](01-foundation.md) first. This plan conforms to the
> shared SPA architecture, the thin JSON API layer at `/app/api`, the
> `DataTable`/options contracts, the boot-payload permission seeding, and the
> RTL/i18n bridge defined there. It does **not** repeat that content.

## 1. Module overview

The main operational landing dashboard. Greeting header, 4 KPI counter cards
(Tasks / Samples / Clients / Cars), a "Task Chart" placeholder block with KPI
stripe, a **Samples-Temperature donut** (ApexCharts), a **Top Drivers** table,
and (clients-scoped only) a **Recent Activity** notifications rail.

- **Nav group:** Dashboards → "Analytics".
- **Nav route:** `/dashboard` (see `nav.config.js` line 10), `perm: 'dashboard_access'`.
- **Read-only.** No CRUD on this screen.
- This is the screen `vue-build`'s `views/Dashboard/Analytics.vue` was modeled
  on — reuse it almost verbatim, wiring its mock arrays to real endpoints.

## 2. Current implementation (Blade / Velzon)

### Routes
| Method | URI | Name | Controller@action |
|---|---|---|---|
| GET | `/dashboard` | `home` | `HomeController@index` |
| POST | `/samples/types/report` | (none) | `SampleController@report` (donut data) |
| GET | `/check-emergency` | (none) | `EmergencyController@checkEmergency` (banner; global) |
| POST | `/clear-emergency` | (none) | `EmergencyController@clearEmergency` (banner; global) |

`HomeController@index` is **GET only** (no POST search variant).

### Controller data → view (`HomeController@index`, lines 245–373)
Passes to `dashboard.blade.php`:
- `tasks`, `samples`, `cars`, `drivers`, `users`, `locations`, `clients` —
  integer counts. **Heavily cached** (`Cache::remember`, 30 min) under keys
  `dashboard_stats_admin` / `dashboard_stats_clients_<md5>` and
  `top_drivers_admin` / `top_drivers_clients_<md5>`.
- `top_drivers` — collection of `{ driver_id, name, total }` (top 5 by task
  count, `drivers.status = 1`), cached.
- `notifications` — latest 10 `Notifications` with `client/driver/fromLocation/toLocation`
  eager-loaded, ordered `created_at desc`. **Only rendered for client-scoped users**
  in the Recent-Activity rail (`@can('client_access')`).
- **Client scoping:** when `auth()->user()->assigned_client_ids` is non-empty,
  every aggregate is filtered to those client ids; otherwise full counts.

### `SampleController@report` (lines 2176–2225) — the donut data
- POST `/samples/types/report` with `{ from, to }` (both nullable; ISO dates).
- Validates `from`/`to` nullable; if both filled, filters `samples.created_at`
  BETWEEN start-of-day(from) and end-of-day(to). Client-scoped users join
  `tasks` and filter `billing_client IN assigned_client_ids`.
- Groups by `samples.temperature_type`, orders by count desc.
- **Response shape** (custom `$this->response(true,'success',$results)`):
  ```json
  { "status": true, "message": "success",
    "data": { "labels": ["ROOM","REFRIGERATE","FROZEN"], "values": [120,80,40] } }
  ```
- The dashboard calls this on load (`fetchSamplesReport('','')`) and again on
  every flatpickr date-range change (`#daterange`, `mode:"range"`, `Y-m-d`).

### Permissions / Gates
- `HomeController@index` itself has **NO Gate** — only `auth` middleware.
- In-view `@can('client_access')` gates the Clients KPI card + Recent-Activity rail.
- `@can('car_access')` gates the "list cars" link.
- `nav.config.js` assigns `perm: 'dashboard_access'` to this nav item — this is a
  **rendering-only** perm (no server Gate enforces it today). See §6.

### Special behaviors to PRESERVE
- **ApexCharts donut** (`#sample-source`) fed by `/samples/types/report`; re-fetches
  on date-range change; reuses chart instance via `updateOptions/updateSeries`.
- The big "customer_impression_charts" area chart div exists in the Blade but its
  series is **not wired** (init JS is the Velzon ecommerce demo). Treat the area
  chart as cosmetic — match Analytics.vue's placeholder; do not invent a series.
- `counter-value` count-up animation on KPIs (vue-build `useCounter`).
- **Emergency banner polling** — global, lives in `layouts/master.blade.php`
  (lines 380–454): `setInterval(checkEmergency, 60000)` + immediate call, **only
  when `AUTH_CLIENT_ID === null`** (admin users). Shows a fixed red box; "close"
  POSTs `/clear-emergency`. This is layout-level, not dashboard-specific.

## 3. Target design (Vue + Tailwind)

### Page mapping
| Blade view | Target Vue view | vue-build components |
|---|---|---|
| `dashboard.blade.php` | `views/Dashboard/Analytics.vue` (already exists in `vue-build`) | `Breadcrumb`, `StatCard` ×4, `BaseCard`, `BaseAvatar`, `StatusBadge`, `BaseButton` |

Reuse `vue-build/.../views/Dashboard/Analytics.vue` directly. Replace its mock
constants with API data:
- `kpis[]` ← `GET /app/api/dashboard/summary` (tasks/samples/clients/cars +
  drivers/users/locations). Gate the **Clients** KPI behind `can('client_access')`
  via `usePermissions`.
- donut ← `POST /app/api/dashboard/samples-report` (see §4). **Swap the inline SVG
  donut for `vue3-apexcharts`** `type:"donut"` — `series = values`, `labels = labels`.
  Wire a `FilterBar`/date-range control (flatpickr-equivalent) → re-fetch on change.
- `topDrivers[]` ← `summary.top_drivers` (or its own field).
- `activity[]` (Recent Activity rail) ← `summary.notifications`, rendered **only
  when `can('client_access')`** (matches the Blade `@can`).
- The "Task Activity" area chart stays a placeholder (no backing series exists).

### Vue Router route
`{ path: '/dashboard', name: 'dashboard', component: () => import('views/Dashboard/Analytics.vue'), meta: { perm: 'dashboard_access' } }`

### nav.config.js
Exists (line 10), `perm: 'dashboard_access'`. No change.

### Empty/loading/error states
- KPIs show skeleton until `/summary` resolves.
- Donut: `EmptyState` ("no samples in range") when `values` empty; spinner while loading.
- Recent Activity: `EmptyState` when `notifications` empty.

## 4. Data / API contract

All under the foundation's `/app/api` auth group. **Read-only.**

### `GET /app/api/dashboard/summary`
Reuses `HomeController@index`'s cached aggregates **verbatim** (same cache keys,
same client-scoping). Move the query block into a shared service/static so both
the web action and the API call the identical code (no logic change).

Response:
```json
{
  "data": {
    "stats": { "tasks": 12480, "samples": 38211, "clients": 142, "cars": 86,
               "drivers": 210, "users": 35, "locations": 540 },
    "top_drivers": [ { "driver_id": 12, "name": "Mohammed Al-Harbi", "total": 248 } ],
    "notifications": [ { "title": "TaskCreated", "task_id": 10428,
                         "from_location": "King Faisal Lab", "to_location": "Central Hub",
                         "driver": "Mohammed A.", "created_at": "2026-06-27 09:10:00" } ]
  }
}
```
- `notifications[].title` = the Blade `explode('\\',$type)[2]` value — pre-compute
  server-side so the SPA shows the identical label.
- `clients` count + `notifications` may be returned always but **rendered only**
  when `can('client_access')`.

### `POST /app/api/dashboard/samples-report`
Reuses `SampleController@report` logic 1:1. Body `{ from, to }` (nullable).
Response (normalize the legacy envelope into the foundation shape):
```json
{ "data": { "labels": ["ROOM","REFRIGERATE","FROZEN"], "values": [120,80,40] } }
```
Keep the existing client-scoping (`billing_client IN assigned_client_ids`).
> Simplest non-invasive option: the SPA may also POST the **existing**
> `/samples/types/report` route directly and read `data.data.{labels,values}`.
> Prefer wrapping under `/app/api` for a consistent envelope, but either works.

### Validation
`SampleController@report` uses an inline `Validator` (`from`/`to` nullable) — keep
it. No Form Request involved on this dashboard.

## 5. Migration steps (ordered, checkable)

- [ ] backend: add `Api\DashboardApiController@summary` reusing the cached
      stats/top_drivers/notifications block from `HomeController@index` (extract
      to a shared method; **do not change cache keys or scoping**).
- [ ] backend: add `Api\DashboardApiController@samplesReport` delegating to the
      same query as `SampleController@report`; return foundation envelope.
- [ ] frontend: copy `Analytics.vue`; wire `/summary` + `/samples-report`.
- [ ] frontend: swap inline donut SVG for `vue3-apexcharts` donut.
- [ ] frontend: gate Clients KPI + Recent Activity behind `can('client_access')`.
- [ ] wire router `/dashboard` (perm `dashboard_access`); nav entry already present.
- [ ] parity test vs Blade: same counts, same donut segments per range, same
      top-5 drivers, notifications identical for a client-scoped user.
- [ ] flip nav "Analytics" to `/app/dashboard` (cutover).

## 6. Risks / must-not-break

- **Caching:** counts are 30-min cached per client-set. Reuse the **same cache
  keys** so the SPA and Blade never show divergent numbers; do not shorten TTLs.
- **Client scoping:** `assigned_client_ids` filtering must be preserved on every
  aggregate and on the samples-report; a client user must not see global totals.
- **Donut envelope:** legacy returns `{status,message,data:{labels,values}}`;
  if you rewrap, keep `labels`/`values` arrays aligned and ordered by count desc.
- **Emergency banner polling** (`/check-emergency` every 60s, admins only) is a
  **layout-level** concern — must be reproduced in `AppShell`/a global composable,
  not lost when the dashboard moves. POST `/clear-emergency` carries CSRF. Track
  this in [`15-map.md`](07-map.md) §6 too since it's global. **Must keep the
  `AUTH_CLIENT_ID === null` (admin-only) condition.**
- **No Gate on `index`:** do not "add" a Gate during migration; the SPA route's
  `meta.perm: 'dashboard_access'` is rendering-only (foundation §4).
- The area "Task Chart" has no real series today — do not fabricate data.

## 7. Out of scope / open questions

- The "Download/Export/Import" dropdowns on the donut/top-drivers cards are dead
  links in Blade — leave as no-ops unless product asks.
- `drivers`/`users` counts are only present in the admin (non-client) cache
  branch; for client-scoped users they fall back to `Driver::count()`/`User::count()`
  in the view. Mirror that fallback exactly.
- Should `summary` and `samples-report` be merged into one call? Keeping them
  separate matches the current two-request behavior (and the date-range re-fetch).
