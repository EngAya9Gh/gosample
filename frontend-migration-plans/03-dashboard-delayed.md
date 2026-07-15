# Delayed (Alerts) Dashboard — Frontend Migration Plan

> **✅ Implemented (Inertia).** `App\Http\Controllers\App\DelayedDashboardController@index`
> → `Inertia::render('Dashboard/DelayedDashboard')`. Reuses the Task model delayed
> methods (`pickup_delayedTasks`/`drop_off_delayedTasks`/`delayed_tasks_in_freezer`/
> `delayed_tasks_delivered`), keeps the real **`delayeddashboard` Gate**, `client_id`
> scoping, the 4-day window, and the **2-min lost-samples cache** (same keys).
> 5 danger StatCards + 5 list panels + alarm `<audio loop>` (with an "Enable alarm"
> gesture fallback when autoplay is blocked). **KPI cross-wiring preserved** exactly
> (Collected card shows delivered count & vice-versa). Row links point at
> `/app/admin/tasks|drivers/{id}` and the legacy `/admin/*delayed` list screens.

> Read [`01-foundation.md`](01-foundation.md) first. Conforms to the shared SPA
> architecture, the `/app/api` JSON layer, boot-payload permissions, and the
> RTL/i18n bridge defined there.

## 1. Module overview

Real-time alerts board for delayed tasks and lost samples. Five KPI counter
cards + five list panels. Plays a looping **alarm sound** when any pickup/drop-off
delay exists. This is the "war-room" screen the operations desk leaves open.

- **Nav group:** Dashboards → "Delayed Dashboard".
- **Nav route:** `/delayeddashboard` (`nav.config.js` line 11), `perm: 'dashboard_access'`,
  carries a `badge: 'delayed'` (live count badge in the sidebar).
- **Server Gate:** `delayeddashboard` (enforced in the controller — see §2).
- Read-only.

## 2. Current implementation (Blade / Velzon)

### Routes
| Method | URI | Name | Controller@action |
|---|---|---|---|
| GET | `/delayeddashboard` | `delayeddashboard` | `DelayedDashboardController@index` |

> The README/title also references "alerts-dashboard": that is the **Blade view
> name** (`resources/views/alerts-dashboard.blade.php`) this controller renders —
> not a separate route.

### Controller data → view (`DelayedDashboardController@index`)
- `abort_if(Gate::denies('delayeddashboard'), 403)` — **real server Gate**.
- `clientId = auth()->user()->client_id` (note: scopes by `client_id`, **not**
  `assigned_client_ids` like the other dashboards — preserve this difference).
- Four delayed sets from `Task` model methods (all `created_at >= now()-4 days`):
  - `pickup_delayedTasks($clientId)` — `pickup_time < collection_date`.
  - `drop_off_delayedTasks($clientId)` — `dropoff_time < close_date`.
  - `delayed_tasks_in_freezer($clientId)` — `status=COLLECTED` &
    `TIMESTAMPDIFF(MIN, collection_date, NOW()) > 15`. **NB: client filter is
    commented out in the model — it is NOT scoped. Preserve as-is.**
  - `delayed_tasks_delivered($clientId)` — `status=OUT_FREEZER` &
    `TIMESTAMPDIFF(MIN, freezer_out_date, NOW()) > 15`, client-scoped.
- `lost_samples` — `Sample` join `tasks`, `confirmed_by_client='LOST'`,
  client-scoped; **cached 2 min** under `lost_samples_admin` / `lost_samples_client_<id>`.
- `play_sound` = 1 when `pickup` or `drop_off` delayed counts > 0.

### View (`alerts-dashboard.blade.php`)
- `@if($play_sound==1)` → `<audio autoplay loop>` `emergency-alarm.mp3`.
- 5 KPI cards: Lost Samples, Pickup Delayed, **(card 3 shows
  `count(delayed_tasks_delivered)` labeled "in_freezer")**, **(card 4 shows
  `count(delayed_tasks_in_freezer)` labeled "delivered")** — the two are
  cross-wired in the Blade; Drop-off Delayed.
  ⚠️ **Preserve the existing label↔count pairing exactly** (parity), even though
  it looks swapped — fixing it would change displayed behavior.
- 5 list panels (each an `<li>` list): Lost Samples (date, update time, barcode,
  bag_code, task_id link, confirmed_by), Pickup Delayed, Drop-off Delayed,
  Collected Delayed (`delayed_tasks_in_freezer`), Closed Delayed
  (`delayed_tasks_delivered`). Each row links to `admin/tasks/{id}` and
  `admin/drivers/{id}`.
- Deep links to legacy list screens: `admin/lost`, `admin/pickupdelayed`,
  `admin/dropdelayed`, `admin/collectedDelayed`, `admin/outfreezerdelayed`.

### Permissions / Gates
- Server Gate `delayeddashboard` (the real boundary).
- No `@can` inside the view.
- Nav perm key `dashboard_access` (rendering-only; mismatch with server Gate
  `delayeddashboard` — see §6).

### Special behaviors to PRESERVE
- **Alarm sound** when pickup/drop-off delays exist (autoplay loop).
- **2-min cache** on lost samples.
- **4-day lookback** window on all delayed sets.
- Per-set date field used for the calendar tile (pickup_time / dropoff_time /
  collection_date / freezer_out_date / updated_at).
- Sidebar **`badge:'delayed'`** live count.

## 3. Target design (Vue + Tailwind)

### Page mapping
| Blade view | Target Vue view | vue-build components |
|---|---|---|
| `alerts-dashboard.blade.php` | `views/Dashboard/DelayedDashboard.vue` | `Breadcrumb`, `StatCard` ×5, `BaseCard` ×5, `Timeline` (or list rows), `StatusBadge`, `EmptyState` |

- 5 `StatCard`s (tone `danger`) for the counts; keep the exact label↔count
  pairing from the Blade.
- 5 `BaseCard` panels each rendering a list. `Timeline.vue` fits the
  date-tile + detail row layout; otherwise plain rows inside `BaseCard`.
- Alarm: an `<audio loop autoplay>` element shown when `play_sound`; respect
  browser autoplay rules — gate behind a one-time user-gesture "enable sound"
  toggle if autoplay is blocked.
- Row links use SPA routes (`/app/admin/tasks/{id}`, `/app/admin/drivers/{id}`).

### Vue Router route
`{ path: '/delayeddashboard', name: 'delayed-dashboard', component: () => import('views/Dashboard/DelayedDashboard.vue'), meta: { perm: 'dashboard_access' } }`

### nav.config.js
Exists (line 11) with `badge:'delayed'`. Feed the badge from the summary counts.

### Empty/loading/error states
- Each panel: `EmptyState` when its list is empty.
- 403 view when the `delayeddashboard` Gate denies (handled by API + router guard).

## 4. Data / API contract

### `GET /app/api/dashboard/delayed`
Reuses `DelayedDashboardController@index` logic 1:1, including the
`delayeddashboard` Gate (`Gate::denies` → 403 JSON), the `client_id` scoping,
the 4-day window, and the 2-min lost-samples cache.

Response:
```json
{
  "data": {
    "play_sound": 1,
    "counts": {
      "lost_samples": 4,
      "pickup_delayed": 7,
      "drop_off_delayed": 3,
      "in_freezer": 5,
      "delivered": 2
    },
    "lost_samples": [
      { "id": 9, "barcode_id": "BC123", "bag_code": "BG7", "task_id": 10428,
        "confirmed_by": "Ahmad", "updated_at": "2026-06-27 08:11:00" } ],
    "pickup_delayed": [
      { "id": 10428, "pickup_time": "2026-06-27 07:00:00",
        "to": "Central Hub", "driver": { "id": 12, "name": "Mohammed A." } } ],
    "drop_off_delayed": [ { "id": ..., "dropoff_time": "...", "to": "...", "driver": {...} } ],
    "in_freezer":  [ { "id": ..., "collection_date": "...", "to": "...", "driver": {...} } ],
    "delivered":   [ { "id": ..., "freezer_out_date": "...", "to": "...", "driver": {...} } ]
  }
}
```
- `counts` keys feed the 5 KPIs — **map them to the same labels the Blade uses**
  (preserve the cross-wired pairing: the "in_freezer"-labeled card shows the
  `delivered` count and vice-versa, per Blade lines 113–163). Document the exact
  mapping in code comments so it is intentional, not a bug to be "fixed".
- `play_sound` drives the alarm element.
- Each row carries the raw `id`/`driver.id` for the SPA links.

> Optional: a tiny `GET /app/api/dashboard/delayed/badge` returning just
> `{ count }` for the sidebar badge polling, to avoid re-pulling all lists.

### Validation
None (read-only, no inputs).

## 5. Migration steps (ordered, checkable)

- [ ] backend: `Api\DashboardApiController@delayed` reusing the Task model
      delayed methods + lost-samples cache + the `delayeddashboard` Gate.
- [ ] backend: ensure `play_sound` and the 5 counts are computed identically.
- [ ] frontend: build `DelayedDashboard.vue` (5 StatCards + 5 list panels +
      alarm audio), preserving the label↔count pairing.
- [ ] frontend: wire sidebar `badge:'delayed'` to the count.
- [ ] wire router `/delayeddashboard`; nav entry present.
- [ ] parity test vs Blade: identical counts, lists, alarm trigger, scoping.
- [ ] flip nav to `/app/delayeddashboard` (cutover).

## 6. Risks / must-not-break

- **Gate name mismatch:** server enforces `delayeddashboard`; nav uses
  `dashboard_access`. The API **must keep `delayeddashboard`** as the real check.
  Do not change either; the SPA `meta.perm` is rendering-only.
- **Client scoping uses `client_id`, not `assigned_client_ids`** here — different
  from the Analytics/Tasks dashboards. Preserve.
- **`delayed_tasks_in_freezer` is intentionally NOT client-scoped** (commented
  out in the model). Do not "add" the filter.
- **Cross-wired KPI labels** must be reproduced exactly for parity.
- **Alarm autoplay** — browsers may block autoplay without a user gesture; ensure
  the alarm still works (gesture-enable fallback) so the desk is not silently
  un-alerted.
- **2-min lost-samples cache** — keep the same key/TTL to avoid divergence.
- **4-day lookback** is the data window; do not widen/narrow.

## 7. Out of scope / open questions

- The legacy deep-link list screens (`admin/lost`, `admin/pickupdelayed`, etc.)
  are separate modules (Tasks/Samples plans) — link to their `/app` routes once
  those are migrated; until then link to the legacy `/admin/*` Blade routes.
- Should the page auto-refresh (the Blade does not, except via user action)? The
  Car dashboard uses a 15s meta-refresh; this one does not — match current
  behavior unless product wants polling. Consider a manual refresh + the badge poll.
