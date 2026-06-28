# Live Map — Frontend Migration Plan

> Read [`01-foundation.md`](01-foundation.md) first. Conforms to the `/app/api`
> JSON layer, the `/options` select contract, boot-payload permissions, and the
> RTL/i18n bridge. This is the **Google Maps live-tracking** screen — the
> foundation notes that `vue-build` ships map placeholders to be swapped for the
> project's real map lib (Google Maps JS API here).

## 1. Module overview

Live driver/vehicle tracking on a Google Map. A filter form (driver / IMEI /
plate number) and a full-height map of vehicle markers. Marker icon encodes task
state (delayed / active / no-task); marker label = active task count. Clicking a
marker opens a **tabbed detail modal** (Driver / Tasks / Car Details / Car
Tracking). Coordinates come from each car's latest `car_tracking` record.

- **Nav group:** Dashboards → "Map".
- **Nav route:** `/map` (`nav.config.js` line 13), `perm: 'map_access'`.
- Read-only.

## 2. Current implementation (Blade / Velzon)

### Routes
| Method | URI | Name | Controller@action |
|---|---|---|---|
| GET | `/map` | `map` | `HomeController@map` (renders the page) |
| POST | `/map` | `map.search` | `HomeController@map` |
| POST | `/map/filter` | `map.filter` | `HomeController@filterMap` (**marker JSON**) |
| GET | `/driver-locations` | (none) | `HomeController@getDriverLocations` (JSON; polling-capable) |

### Controller (`HomeController@map`, `filterMap`, `getDriverLocations`)
- **`map()`** — GET render path returns the page with `drivers` (id,name) and
  `plateNumbers` (distinct non-empty `cars.plate_number`) for the filter selects.
  (The method has unreachable legacy code after the first `return view('map',...)`
  — ignore it.) Client-scoped via `client_id`/`client_driver` when applicable.
- **`filterMap()`** — the live data endpoint the page actually calls. Returns JSON
  array of `Driver` rows joined to `cars` (`cars.lat NOT NULL`, `cars.status=1`),
  eager-loading `driverActiveTasks` (+ `from`,`to`,`samples`),
  `driverActiveDelayedTasks`, `car`, `car.carTracking`. Optional filters:
  `driver_id`, `imei`, `plate_number`. Then it overwrites each row's `lat`/`lng`
  with the **latest `car.carTracking`** record (sorted by `created_at desc`).
  Client-scoped when `client_id` set.
- **`getDriverLocations()`** — GET, essentially the same dataset as `filterMap`
  (driver_id/imei/plate filters), returns JSON. Used for polling/refresh.
- Relationship definitions: `driverActiveTasks` = tasks `status NOT IN
  (CLOSED,NO_SAMPLES)`; `driverActiveDelayedTasks` = same + `delayed_reason <> ''`.
- **NO Gate** in any of these methods (only `auth` middleware).

### View (`map.blade.php`)
- Loads **Google Maps JS API** (`maps.googleapis.com/maps/api/js?key=…&callback=initMap`)
  + jQuery + select2.
- Filter form (`#filter-form`, AJAX, no native POST): `driver_id` (select2),
  `imei` (text), `plate_number` (select2), Search + Reset.
- `#map` div (height 800px), default center `{lat:24.7597608, lng:46.7141881}`
  (Riyadh), zoom 12, POI labels hidden.
- `loadLocations(filters)` POSTs to `map.filter` and renders markers:
  - **icon** by state: `pin-delayed.png` (has delayed tasks) → `pin-active.png`
    (has active tasks) → `pin-no-task.png` (none).
  - **label** = active task count (string), bold; hover shows driver name.
  - On click → `populateModal(value)` fills the modal tabs.
  - `map.fitBounds(...)` to all markers (or recenters to default when none).
- **Detail modal** (`#exampleModalgrid`) tabs:
  - Driver: name, mobile, email, plate, IMEI.
  - Tasks: table of active tasks (id, from.name, to.name, status, samples count).
  - Car Details: id, plate_number, model.
  - Car Tracking: rows of id, address(=lat,lng), temp5–temp8.
- An **AJAX loader overlay** shown during fetch.

### Permissions / Gates
- None server-side (only `auth`). Nav perm `map_access` is **rendering-only**
  (no `map_access` Gate is defined/enforced in these methods today — see §6).

### Special behaviors to PRESERVE
- **Google Maps markers** with state-based icons + active-count labels + hover
  driver name + fitBounds.
- **lat/lng = latest `car_tracking`** record (overrides `cars.lat/lng`).
- **`filterMap` POST** as the marker data source; **`/driver-locations` GET** for
  polling/refresh.
- **Detail modal** with the 4 tabs and exact field sets.
- **Client scoping** (`client_id`) on the dataset.
- Default Riyadh center + zoom, POI labels off.

## 3. Target design (Vue + Tailwind)

### Page mapping
| Blade view | Target Vue view | vue-build components |
|---|---|---|
| `map.blade.php` | `views/Dashboard/MapTracking.vue` | `Breadcrumb`, `FilterBar`, `FormSelect` (driver, plate), `FormInput` (imei), `BaseButton` (Search/Reset), `BaseCard` (map container), `BaseModal` + `TabGroup` (detail), `EmptyState` |

- **Map:** the `vue-build` map placeholder is swapped for the **Google Maps JS
  API**. Load the API via a loader (e.g. `@googlemaps/js-api-loader`) so it works
  inside the SPA (the `&callback=initMap` global pattern doesn't fit Vue). Build a
  small `useGoogleMap` composable: init map (Riyadh center, zoom 12, POI off),
  `setMarkers(rows)`, `clearMarkers()`, `fitBounds()`.
- **Markers:** same 3 icons (`pin-delayed/active/no-task.png`), same label =
  active-task count, hover → driver name, click → open `BaseModal`.
- **Detail modal:** `BaseModal` + `TabGroup` with the 4 tabs (Driver / Tasks /
  Car Details / Car Tracking), same fields; force `dir=ltr` on IMEI/plate/coords.
- **Filters:** `FilterBar` (driver `FormSelect`, imei `FormInput`, plate
  `FormSelect`) → re-fetch markers; Reset clears + reloads all.
- **Loader overlay** while fetching markers.

### Vue Router route
`{ path: '/map', name: 'map', component: () => import('views/Dashboard/MapTracking.vue'), meta: { perm: 'map_access' } }`

### nav.config.js
Exists (line 13), `perm: 'map_access'`. No change.

### Empty/loading/error states
- No vehicles with coords → recenter to default + `EmptyState`/toast.
- Loading overlay during fetch. API-key/load failure → error card.

## 4. Data / API contract

### `GET /app/api/map/options`
Reuses `HomeController@map`'s `drivers` + `plateNumbers` lists (client-scoped):
```json
{ "data": {
    "drivers": [ { "value": 12, "label": "Mohammed A." } ],
    "plate_numbers": [ "ABC-1234", "XYZ-9876" ]
} }
```

### `GET /app/api/map/locations`
Reuses **`getDriverLocations`** (and the identical `filterMap`) query verbatim,
including client scoping and the latest-`car_tracking` lat/lng override. GET so it
is **pollable** (matches `/driver-locations`). Optional params: `driver_id`,
`imei`, `plate_number`.

Response (array of vehicle/driver rows — keep the relation field names the modal
reads; current code returns Laravel snake_case relations):
```json
{
  "data": [
    {
      "id": 12, "name": "Mohammed A.", "mobile": "...", "email": "...",
      "imei": "356938035643809", "plate_number": "ABC-1234", "model": "Van",
      "lat": 24.71, "lng": 46.67,
      "driver_active_tasks": [
        { "id": 10428, "status": "COLLECTED",
          "from": { "name": "King Faisal Lab" }, "to": { "name": "Central Hub" },
          "samples": [ { "id": 1 } ] } ],
      "driver_active_delayed_tasks": [ { "id": 10421 } ],
      "car": {
        "id": 5, "plate_number": "ABC-1234", "model": "Van",
        "imei": "356938035643809",
        "car_tracking": [ { "id": 99, "lat": 24.71, "lng": 46.67,
                            "temp5": 6.2, "temp6": null, "temp7": null, "temp8": null,
                            "created_at": "2026-06-27 09:30:00" } ]
      }
    }
  ]
}
```
- Marker icon/label logic in the SPA reads `driver_active_delayed_tasks.length`
  and `driver_active_tasks.length` exactly as the Blade does.
- `lat`/`lng` already overridden server-side to the latest tracking point; the SPA
  may keep the Blade's fallback (derive from `car.car_tracking` newest) for safety.

> Reuse note: `filterMap` (POST) and `getDriverLocations` (GET) are duplicate
> queries. Extract one shared builder and back both `/map/locations` and the
> legacy routes with it (no behavior change).

### Validation
None (all filters optional). No Form Request.

## 5. Migration steps (ordered, checkable)

- [ ] backend: `Api\MapApiController@options` (drivers + plate_numbers, client-scoped).
- [ ] backend: `Api\MapApiController@locations` (GET) reusing the
      `getDriverLocations`/`filterMap` query + client scoping + latest-tracking
      lat/lng override; return `{data:[...]}` with the same relation field names.
- [ ] frontend: `useGoogleMap` composable (Google Maps loader, markers, fitBounds).
- [ ] frontend: build `MapTracking.vue` (FilterBar + map + BaseModal/TabGroup),
      same icons/labels/hover/modal fields.
- [ ] frontend: optional polling on `/map/locations` for live refresh.
- [ ] wire router `/map` (perm `map_access`); nav entry present.
- [ ] parity test vs Blade: same markers/icons/labels for the same fleet, same
      modal data, same filters/reset, same default center/zoom, client scoping.
- [ ] flip nav to `/app/map` (cutover).

## 6. Risks / must-not-break

- **Google Maps API key** is hard-coded in `map.blade.php`
  (`AIzaSyDf1ht01vFyWcfWS33mmdfd30qm5-uyWhM`). For the SPA, load it from config/env
  via the boot payload rather than re-hard-coding. Ensure the key's HTTP-referrer
  restrictions allow the `/app` origin. (Key exposure is pre-existing; tightening
  it is out of scope but flag it.)
- **No server Gate today** — `map`/`filterMap`/`getDriverLocations` rely only on
  `auth`. Keep `map_access` as **rendering-only** in the SPA (foundation §4); do
  not add a backend Gate during the presentation migration.
- **Marker state logic** (delayed → active → no-task) and **label = active count**
  must match exactly.
- **lat/lng from latest `car_tracking`** (not raw `cars.lat`) — preserve the
  override and the `created_at desc` selection.
- **Relation field naming:** the modal reads snake_case (`driver_active_tasks`,
  `car.car_tracking`); keep the JSON serialization consistent so the modal binds.
- **`cars.status=1` + `cars.lat NOT NULL`** are the inclusion filters — preserve.
- **Client scoping** by `client_id` on the dataset.

### Global must-not-break (carried here because it's app-wide)
- **Emergency banner polling** lives in `layouts/master.blade.php`:
  `GET /check-emergency` every **60s** + immediate call, **admins only**
  (`AUTH_CLIENT_ID === null`), with a "close" `POST /clear-emergency` (CSRF).
  This must be reproduced once at the SPA shell level (`AppShell`/global
  composable), not per-page, so it keeps firing across all `/app` routes. See also
  [`10-dashboard-analytics.md`](02-dashboard-analytics.md) §6.

## 7. Out of scope / open questions

- The unreachable legacy code in `map()` and the `map_old()` method are dead — do
  not port.
- `temp5–temp8` on `car_tracking` are surfaced in the modal; confirm these columns
  exist/are populated (some rows show blanks). Render blanks as the Blade does.
- Polling interval for live tracking is **not set** in the Blade (it loads once +
  on filter). Decide whether to add periodic `/map/locations` polling (e.g. 30s)
  for true "live" tracking, or match current load-on-action behavior.
- Marker clustering for large fleets is not done today — out of scope unless perf
  requires it.
