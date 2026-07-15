# Locations (lat/lng map picker) — Frontend Migration Plan

> Read [`01-foundation.md`](01-foundation.md) first. This plan **conforms to** the
> foundation's `/app/api` JSON layer, `{ data, meta }` envelope, **server-side
> `DataTable`** contract (Locations is a Yajra/ajax table), `422 → form fields`
> mapping, `usePermissions` rendering layer, and RTL/i18n bridge. It does not
> repeat those mechanics — only what is specific to Locations.

---

## 1. Module overview

**Locations** are the physical places tasks pick up from / drop off at. Each
location has bilingual names, a city (from a fixed `SAUDI_CITIES` map), a
neighborhood, lat/lng coordinates (set via a **Google Maps click/search
picker**), waiting-time fields, audit (`createdBy`/`updatedBy`), and a soft
`enabled` global scope. A location relates to clients via the
`locationsClients` pivot. Nav group: **Clients** (sibling of the Clients hub).

Route base `locations`; access gate `location_access`. The list is a **Yajra
server-side DataTable**, unlike the client-side Clients list — this is the main
reason Locations is its own plan.

---

## 2. Current implementation (Blade / Velzon)

### 2.1 Routes (`routes/web.php`, prefix `admin`, name prefix `admin.`)

| Method | URI | Name | Controller@action |
|---|---|---|---|
| DELETE | `locations/destroy` | `locations.massDestroy` | `LocationsController@massDestroy` |
| (resource) | `locations` | `locations.{index,create,store,show,edit,update,destroy}` | `LocationsController` |

The `index` route doubles as the **ajax data source** (`request()->ajax()`
branch returns `DataTables::of(...)->make(true)`).

### 2.2 Controller actions & data

- **index(Request)** — `abort_if(Gate::denies('location_access'))`. On ajax:
  `Location::withoutGlobalScope('enabled')->with(['createdBy','updatedBy'])->select('locations.*')`,
  then **server-side filters**: `date_from`+`date_to` (whereBetween created_at),
  `driver_id`, `status`, `city`; plus **portal scoping**: if
  `$user->assigned_client_ids` non-empty, `whereHas('locationsClients', clients.id
  IN assigned_client_ids)`. Yajra builds columns (see 2.3). Non-ajax → `locations/index` shell.
- **create** — gate `location_create`; returns empty form.
- **store(StoreLocationRequest)** — `Location::create($request->all())`; then, if
  the user has `assigned_client_ids`, creates a `ClientLocation` pivot row per
  client id (auto-links the new location to the portal user's clients).
- **edit($id)** — gate `location_edit`; `Location::withoutGlobalScope('enabled')->findOrFail`.
- **update(UpdateLocationRequest,$id)** — `withoutGlobalScope('enabled')->findOrFail`
  then `update($request->all())`.
- **show($id)** — gate `location_show`; loads `locationsClients`; the show view
  has a **tab listing related clients**.
- **destroy / massDestroy** — `authorize('can-delete')`; massDestroy iterates
  `Location::find(ids)` and `->delete()` each.

### 2.3 Server-side DataTable columns (Yajra — must reproduce keys exactly)

`placeholder` (checkbox col), `id`, `name`, `arabic_name`, `description`, `lat`,
`lng`, **`coordinates`** (HTML: hidden Google-Maps link + "Copy" button), `mobile`,
**`city`** (formatted `en — ar` from `SAUDI_CITIES`), `neighborhood`, **`status`**
(badge Active/Not Active), and — only when `@can('location_audit_access')` —
`created_by`, `updated_by`; finally `actions`. `rawColumns`: actions,
placeholder, coordinates, status, created_by, updated_by.

> Note: `coordinates` is defined **twice** in the controller (the second wins) —
> a copy-to-clipboard helper. Reproduce as a simple "Copy coords" affordance.

### 2.4 Blade views → purpose

| View | Purpose |
|---|---|
| `locations/index.blade.php` | filter card (date_from/date_to/status/city) + **server-side** `datatable-Location` (ajax to `locations.index`) + `copyToClipboard` JS |
| `locations/create.blade.php` | form + **Google Maps picker** (click sets lat/lng; Places search box; `gm_authFailure` fallback panel) |
| `locations/edit.blade.php` | same form, pre-filled |
| `locations/show.blade.php` | attributes + **tab** of related clients (`relationships/locationsClients.blade.php`) |
| `locations/relationships/locationsClients.blade.php` | client-side table of the location's linked clients |

**Create/edit form fields:** `name`*, `arabic_name`*, `description`*, `city`*
(SAUDI_CITIES select), `neighborhood`, `status` (select), `lat`, `lng` (text,
set by map), `pickup_waiting_time`, `drop_off_waiting_time`, and audit
created/updated-by display (read-only, `@can('location_audit_access')`). The map
section calls `initMap()`; map click and Places search write `#lat`/`#lng`.

### 2.5 Permissions / Gates (verified)

`location_access` (index), `location_create` (create/store via
StoreLocationRequest::authorize), `location_edit` (edit/update), `location_show`
(show), and **`location_delete`** referenced only in the Yajra actions partial
(`$deleteGate = 'location_delete'`) for the row delete button — but the actual
`destroy`/`massDestroy` enforce the global **`can-delete`** gate. Audit columns &
created/updated-by display are gated by **`location_audit_access`**.

> Carry-over to SPA: render the row Delete button by `canDelete()` (the real
> backend gate), and optionally also respect a client-side `location_delete`
> flag for parity with the partial. Backend remains authoritative (`can-delete`).

### 2.6 Form Request rules (reuse verbatim — `Store`/`UpdateLocationRequest`)

- `name` required string; `arabic_name` required string; `description` required
  string; `lat` numeric *(no `required`/`nullable` → "sometimes-ish"; replicate)*;
  `lng` nullable string; `city` required string `in:<keys of SAUDI_CITIES>`;
  `neighborhood` nullable string max 255. (`mobile` rule is **commented out** —
  not validated; the column still exists/displays.) `pickup_waiting_time` /
  `drop_off_waiting_time` are posted but **unvalidated** (mass-assigned) —
  preserve.

### 2.7 Special behaviors to PRESERVE

- **Server-side ajax DataTable** (large table) with the exact filter params
  `date_from`, `date_to`, `status`, `city` (and the portal `assigned_client_ids`
  scoping applied server-side).
- **`withoutGlobalScope('enabled')`** everywhere (index/edit/update/show/destroy)
  so disabled locations remain visible/editable. The API must do the same.
- **City formatting** `en — ar` and the **`in:SAUDI_CITIES` keys** validation.
- **Google Maps lat/lng picker** + Places search + `gm_authFailure` fallback.
- **Auto-link pivot** on store for portal users (`assigned_client_ids` →
  `ClientLocation` rows).
- **Copy-coordinates** clipboard affordance.
- Audit columns / created-by display gated by `location_audit_access`.
- Bulk delete via massDestroy (`{ ids:[] }`, `can-delete`).

---

## 3. Target design (Vue + Tailwind)

Views under `resources/js/vue/views/Locations/`.

| Blade | Vue view | vue-build components |
|---|---|---|
| `locations/index` | `Locations/LocationsList.vue` | `Breadcrumb`, `FilterBar` (date_from/date_to/status/city via `FormInput type=date` + `FormSelect`), `DataTable` (server-side via `useDataTable`), `StatusBadge`, `BaseButton`, `BaseModal` |
| `locations/create` + `edit` | `Locations/LocationForm.vue` | `FormInput` (name/arabic_name/description/neighborhood/lat/lng/waiting times), `FormSelect` (city, status), **map picker (GAP — §3.1)**, `BaseCard`, `BaseButton` |
| `locations/show` + `relationships/locationsClients` | `Locations/LocationShow.vue` | `BaseCard`, `TabGroup` (Details / Clients), `DataTable` or simple table for linked clients, `StatusBadge` |

Use `views/Tasks/TasksList.vue` as the reference: `FilterBar` emits `@search`/`@reset`;
`DataTable` server-side `@query` → `useDataTable` → `GET /app/api/locations`;
`#cell-status` → `StatusBadge`; `#cell-coordinates` → a small "Copy" button
(replaces the Blade hidden-input/`copyToClipboard` hack using the Clipboard API);
`#row-actions` view/edit gated by `can('location_show'|'location_edit')`, delete
by `canDelete()` + `BaseModal`; `:bulk-actions` for massDestroy.

City options come from a `SAUDI_CITIES` list served by the API (label `en — ar`,
value = key) so the `in:` validation stays satisfied. Audit columns render only
when the boot permission set includes `location_audit_access`.

The `show` page's related-clients list maps the existing
`locationsClients.blade.php` table → a `TabGroup` "Clients" tab (read-only;
matches current behavior).

### 3.1 Component GAP — Google Maps lat/lng picker

vue-build has **no map component**. The locations create/edit form needs the
Google Maps click-to-set + Places-search picker (currently inline JS using key
`AIzaSyD...`, with a `gm_authFailure` fallback to manual lat/lng entry).

**Minimal proposed addition (design only, do not build now):** a
`MapLatLngPicker.vue` that:
- loads the Google Maps JS API (key from config/env, not hardcoded), shows a map
  + Places SearchBox, places/moves a marker on click, and `v-model`s `{ lat, lng }`
  back into the form's `lat`/`lng` `FormInput`s;
- on auth/load failure shows the same **"Map unavailable — enter lat/lng manually"**
  fallback so the form still works (parity with `gm_authFailure`).

Until it exists, `LocationForm.vue` can ship with **manual lat/lng `FormInput`s
only** (the rules allow that: `lat` numeric, `lng` nullable) and the map screen
stays on Blade — note this at cutover. `lat`/`lng` inputs are intrinsically-LTR
(force `dir=ltr`).

### Router & nav

Routes mirror `/admin/locations`, `/admin/locations/create`,
`/admin/locations/:id/edit`, `/admin/locations/:id`; `meta.perm = 'location_access'`.
Confirm the `nav.config.js` **Clients**-group Locations entry + perm key.

States: `EmptyState` for no rows, DataTable `:loading`, error toasts, map
fallback. RTL verified (names mirror; lat/lng/IDs forced LTR).

---

## 4. Data / API contract

Under `/app/api` (foundation envelope; `422` → field errors).

| Method | Path | Purpose | Reuses |
|---|---|---|---|
| GET | `/app/api/locations` | server-side list: `q, sortKey, sortDir, page, pageSize` **+ filters `date_from, date_to, status, city`** + portal `assigned_client_ids` scope; `withoutGlobalScope('enabled')` | index query + `location_access` |
| GET | `/app/api/locations/options` | `{ cities:[{value,label:"en — ar"}], statuses:[...], drivers:[...] }` | `SAUDI_CITIES`, status const |
| GET | `/app/api/locations/{id}` | show detail (+ `locationsClients` for the Clients tab); `withoutGlobalScope('enabled')` | `location_show` |
| POST | `/app/api/locations` | create; **also auto-link `ClientLocation` pivots** for portal users | **StoreLocationRequest** + `location_create` |
| PUT/PATCH | `/app/api/locations/{id}` | update; `withoutGlobalScope('enabled')->findOrFail` | **UpdateLocationRequest** + `location_edit` |
| DELETE | `/app/api/locations/{id}` | delete | `can-delete` |
| DELETE | `/app/api/locations` | mass delete `{ids:[]}` | **MassDestroyLocationRequest** + `can-delete` |

**List row shape** (keys = DataTable column keys): `{ id, name, arabic_name,
description, lat, lng, mobile, city, city_label, neighborhood, status,
status_label, created_by, updated_by }` — pre-format `city_label` (`en — ar`) and
`status_label` (Active/Not Active) so the SPA renders identical text; include raw
`status`/`city` for edit. `created_by`/`updated_by` only when caller has
`location_audit_access`.

**Detail shape:** the above + `pickup_waiting_time`, `drop_off_waiting_time`, and
`clients` (linked clients for the show tab: `[{ id, english_name, arabic_name,
status }]`).

**Options:** `cities` from `Location::SAUDI_CITIES` as `{ value:key,
label:"<en> — <ar>" }`; `statuses` from the status const.

**Validation surfacing:** type-hint `StoreLocationRequest`/`UpdateLocationRequest`
→ automatic `422 { message, errors }`; Vue maps onto `name`, `arabic_name`,
`description`, `city`, `lat`, `lng`, `neighborhood` fields. The `city`
`in:SAUDI_CITIES` rule is satisfied because the select is populated from the same
source.

---

## 5. Migration steps (ordered, checkable)

- [ ] **Backend:** `Api\LocationsApiController` — list (server-side filters +
      `assigned_client_ids` scope + `withoutGlobalScope('enabled')`), options
      (cities/statuses), show (+ `locationsClients`), store (reuse
      **StoreLocationRequest** + the portal pivot auto-link), update (reuse
      **UpdateLocationRequest** + `withoutGlobalScope`), destroy/massDestroy
      (`can-delete`). Add routes to `routes/app_api.php`.
- [ ] **Frontend:** `LocationsList.vue` (FilterBar + server-side DataTable via
      `useDataTable`, copy-coords cell, audit columns gated), `LocationForm.vue`,
      `LocationShow.vue` (TabGroup Details/Clients).
- [ ] **Frontend gap:** add `MapLatLngPicker.vue` (or defer to manual lat/lng +
      keep Blade map screen).
- [ ] **Wire** router + `nav.config.js` (`location_access`).
- [ ] **Parity test:** filters produce same rows; `withoutGlobalScope` shows
      disabled locations; city/status formatting identical; map sets lat/lng;
      portal pivot auto-link on create; bulk delete; perms hide same actions;
      audit columns gated; RTL.
- [ ] **Cutover** nav `/admin/locations` → `/app/...`.
- [ ] Update status in `00-README.md`.

---

## 6. Risks / must-not-break

- **`withoutGlobalScope('enabled')`** must be applied in list/show/edit/update/
  delete API paths, or disabled locations vanish from the SPA (behavior drift).
- **Server-side filters & portal scoping** — `date_from/date_to/status/city` and
  the `assigned_client_ids` `whereHas('locationsClients')` scope must match the
  Yajra query exactly (same rows, same counts).
- **City validation/formatting** — keep `in:SAUDI_CITIES` and the `en — ar` label
  so saved values and displayed text stay identical.
- **Store-time pivot auto-link** for portal users (creates `ClientLocation` rows).
- **Map picker** is a real GAP; if deferred, lat/lng must remain manually
  editable and the Blade map screen must remain reachable. Do not hardcode the
  Maps API key in committed Vue (move to config).
- **`lat` numeric / `lng` nullable** quirk and the **unvalidated** `mobile` /
  waiting-time fields — replicate; don't tighten rules.
- **Delete gating:** real gate is `can-delete` (not `location_delete`).

---

## 7. Out of scope / open questions

- **`MapLatLngPicker.vue`** — proposed new component (design only); build under the
  foundation component effort. Decide whether v1 of Locations ships with manual
  lat/lng (map stays on Blade) or waits for the picker.
- **Google Maps API key** sourcing for the SPA (config/env + referrer
  restrictions) — needs product/ops input; the current Blade key is inline.
- Confirm the Locations list should remain server-side ajax (it should — large
  table) and matches the foundation server-side `DataTable` contract.
- Should the SPA also honor a client-side `location_delete` flag for row-button
  parity with the Yajra actions partial, while the backend keeps `can-delete`?
- Confirm `nav.config.js` Locations entry + `location_access` perm key exist.
