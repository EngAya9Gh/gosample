# Car (Temperature) Dashboard — Frontend Migration Plan

> Read [`01-foundation.md`](01-foundation.md) first. Conforms to the shared SPA
> architecture, the `/app/api` JSON layer, boot-payload permissions, and the
> RTL/i18n bridge.

## 1. Module overview

Live fleet **temperature monitoring**. One card per vehicle showing IMEI, profile
(vehicle type / plate / max capacity / seats) and a list of **temperature sensors**
with colored progress bars (green/yellow/red by °C). Data comes from the external
**Afaqy** GPS/telemetry API (`api.afaqy.sa`), not the local DB. The page
**auto-refreshes every 15 seconds**.

- **Nav group:** Dashboards → "Car Dashboard".
- **Nav route:** `/car-dashboard` (`nav.config.js` line 12), `perm: 'dashboard_access'`.
- **Server Gate:** `car-dashboard` (enforced in controller — see §2).
- Read-only.

## 2. Current implementation (Blade / Velzon)

### Routes
| Method | URI | Name | Controller@action |
|---|---|---|---|
| GET | `/car-dashboard` | `cardashboard` | `CarDashboardController@index` |

### Controller flow (`CarDashboardController@index`)
- `abort_if(Gate::denies('car-dashboard'), 403)` — **real server Gate**.
- Builds the IMEI list from local `cars`:
  - admin: `cars` where `afaqi=1`, `status=1`, `deleted_at NULL`, `imei NOT NULL`.
  - client (`client_id` set): same, joined through `drivers`/`client_driver` to
    the user's `client_id`.
- `generateAndSaveToken()` — fetches/caches an Afaqy auth token (one per day,
  stored in the `Afaqi` model). Credentials are hard-coded in the controller.
- `getVehicleDataCustom($token, $imeis)` — POSTs `https://api.afaqy.sa/units/lists`
  with `filters.imei = {value:[imeis], op:'in'}`, projection
  `[basic,last_update,sensors_last_val,counters,sensors]`, `limit 1000`,
  `withoutVerifying()` (TLS verification disabled).
- For each returned vehicle, keeps `{ id, name(=n), i, profile, sensors }` and
  **filters `sensors` to `t === 'temperature'` only**.
- On Afaqy error/empty: flashes "Afaqi service is not available now." and renders
  `error.error-afaqy` view.
- Passes `cars` (array) to `car-dashboard.blade.php`.

### View (`car-dashboard.blade.php`)
- `<meta http-equiv="refresh" content="15">` — **full-page reload every 15s**.
- Grid of `col-md-3` cards; each: name, `Vehicle IMEI: i`, profile fields
  (vehicle_type / plate_number / max_capacity / seats — each `@isset`), and a
  sensor `<ul>`: `n: last_val.value_full` + a Bootstrap `progress-bar`:
  - `>= 30` → `bg-danger`, `>= 20` → `bg-warning`, else `bg-success`.
  - bar width = `value_full`% , label = `value_full °C`.

### Permissions / Gates
- Server Gate `car-dashboard` (real boundary).
- No `@can` inside the view.
- Nav perm key `dashboard_access` (rendering-only; mismatch — see §6).

### Special behaviors to PRESERVE
- **Afaqy integration** (token caching, IMEI filtering, `op:'in'`, TLS off,
  temperature-only sensor filter) — this is business-critical telemetry; keep the
  code path identical (call the same controller method from the API).
- **15-second refresh** of live temperatures.
- **°C → color thresholds** (30 / 20).
- **Graceful degradation** when Afaqy is down.

## 3. Target design (Vue + Tailwind)

### Page mapping
| Blade view | Target Vue view | vue-build components |
|---|---|---|
| `car-dashboard.blade.php` | `views/Dashboard/CarDashboard.vue` | `Breadcrumb`, `BaseCard` (per vehicle), `StatusBadge`, `EmptyState` |
| `error/error-afaqy.blade.php` | inline `EmptyState` (Afaqy-down state) in the same view | `EmptyState` |

- Render a responsive grid of `BaseCard`s, one per vehicle.
- Each sensor: a temperature value + a Tailwind progress bar; map the
  green/yellow/red thresholds (≥30 danger / ≥20 warning / else success) to the
  locked tone tokens. Force `direction:ltr` on IMEI and numeric °C.
- **Replace the 15s `<meta refresh>` with a Vue polling timer** (`setInterval`,
  15000ms) that re-calls the API and patches the cards — no full page reload.
  Clear the interval on `onUnmounted`.
- When Afaqy is unavailable, show an `EmptyState` ("Afaqy service is not available
  now") instead of crashing — mirrors the `error.error-afaqy` fallback.

### Vue Router route
`{ path: '/car-dashboard', name: 'car-dashboard', component: () => import('views/Dashboard/CarDashboard.vue'), meta: { perm: 'dashboard_access' } }`

### nav.config.js
Exists (line 12). No change.

### Empty/loading/error states
- Loading: skeleton cards.
- Empty IMEI set / no vehicles: `EmptyState`.
- Afaqy down: `EmptyState` with retry (next poll).

## 4. Data / API contract

### `GET /app/api/dashboard/cars`
Reuses `CarDashboardController@index` logic 1:1 — same IMEI selection, same
`car-dashboard` Gate, same Afaqy token + `getVehicleDataCustom()` call, same
temperature-only sensor filter. Return JSON instead of a view.

Response:
```json
{
  "data": {
    "available": true,
    "cars": [
      {
        "id": "afaqy-123",
        "name": "Truck 7",
        "i": "356938035643809",
        "profile": { "vehicle_type": "Van", "plate_number": "ABC-1234",
                     "max_capacity": "1000kg", "seats": "2" },
        "sensors": [
          { "t": "temperature", "n": "Cargo Temp",
            "last_val": { "value_full": 6.4 } }
        ]
      }
    ]
  }
}
```
- `available: false` (+ empty `cars`) when Afaqy errors — drives the down-state.
- Keep `i` (IMEI), `n`/`name`, `profile.*`, and the temperature-only `sensors`
  exactly as the controller already shapes them.

### Polling
SPA polls `GET /app/api/dashboard/cars` every **15s** (matching the old meta
refresh). Consider a short server-side cache (≤15s) to avoid hammering Afaqy if
multiple desks watch the board.

### Validation
None (read-only).

## 5. Migration steps (ordered, checkable)

- [ ] backend: `Api\CarDashboardApiController@index` reusing the Gate + IMEI
      query + `generateAndSaveToken()` + `getVehicleDataCustom()`; return JSON
      with `available`/`cars`. **No change to the Afaqy code path.**
- [ ] frontend: build `CarDashboard.vue` grid of `BaseCard`s + temp progress bars.
- [ ] frontend: 15s polling timer (clear on unmount); Afaqy-down `EmptyState`.
- [ ] wire router `/car-dashboard`; nav entry present.
- [ ] parity test vs Blade: same vehicles, same sensor values/colors, same
      down-state behavior, refresh cadence.
- [ ] flip nav to `/app/car-dashboard` (cutover).

## 6. Risks / must-not-break

- **Gate mismatch:** server enforces `car-dashboard`; nav uses `dashboard_access`.
  Keep `car-dashboard` as the real API check.
- **Afaqy token caching** (1/day via `Afaqi` model) and **hard-coded credentials**
  live in the controller — do not duplicate or relocate; call the same method.
  (Credentials in source are a pre-existing security concern, out of scope here.)
- **`withoutVerifying()` (TLS off)** is intentional for the Afaqy host — preserve.
- **`op:'in'` IMEI filter + `limit 1000`** — keep; large fleets rely on it.
- **Temperature-only sensor filter** (`t === 'temperature'`) — other sensor types
  must stay hidden.
- **15s refresh** — replace meta-refresh with polling but keep the cadence; ensure
  the timer is cleared on navigation away (no leaked intervals / Afaqy spam).
- **Graceful Afaqy-down handling** must remain (no white screen).

## 7. Out of scope / open questions

- The `error.error-afaqy` Blade becomes an inline `EmptyState`; confirm no other
  screen depends on that Blade view.
- Should temperature thresholds (30/20) be configurable? Currently hard-coded in
  Blade — keep hard-coded for parity.
- `value_full` is used both as the displayed °C **and** as the bar width % — for
  values >100 or negative the bar clamps oddly in Blade; reproduce the same
  clamping (or clamp 0–100) but do not change the displayed number.
- Per-day token rotation: if the SPA polls before midnight rollover the token
  refresh is handled server-side — no client concern.
