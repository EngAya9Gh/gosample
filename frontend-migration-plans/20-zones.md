# Zones — Frontend Migration Plan

> Read [`01-foundation.md`](01-foundation.md) first. Conforms to the shared
> `/app/api` layer, `{data,meta}` envelope, DataTable contract, 422→form mapping,
> permissions, RTL/i18n. **Recommended pilot module** (simple CRUD + one bespoke
> widget: a Google-Maps polygon drawer).

---

## 1. Module overview

Zones are named geographic polygons (used for driver assignment). Each zone stores
a single MySQL/MariaDB **spatial Polygon** (`MatanYadaev\EloquentSpatial`). The
only non-trivial UI is drawing/editing the polygon on a Google Map.

Nav group: **Drivers**. Gate: `zone_access`. Blade routes `/admin/zones`.
List is a **client-side DataTable** (`Zone::all()` passed to the view).

---

## 2. Current implementation (Blade / Velzon)

### Routes

| Method | URI | Name | Controller@action |
|---|---|---|---|
| DELETE | `admin/zones/destroy` | `admin.zones.massDestroy` | `Admin\ZonesController@massDestroy` |
| resource | `admin/zones` | `admin.zones.*` | `Admin\ZonesController` |

### Controller actions → data

- `index()` — `zone_access`. `Zone::all()` → `admin.zones.index` (client-side
  table; only columns shown are `id` and `name` — `area` column is commented out).
- `create()` — `zone_create`. `view('admin.zones.create')` (no data; map drawn client-side).
- **`store(StoreZoneRequest)`** — manual polygon build (logic to PRESERVE):
  ```php
  $polygon = json_decode($request->area, true);   // [{lat,lng}, ...]
  $area = [];
  foreach ($polygon as $point) { $area[] = new Point($point['lat'], $point['lng']); }
  $area[] = new Point($polygon[0]['lat'], $polygon[0]['lng']);   // close ring
  $zone->name = $request->name;
  $zone->area = new Polygon([ new LineString($area) ]);
  $zone->save();
  ```
- `edit(Zone)` — `zone_edit`. `view('admin.zones.edit', ['zone'])`.
- **`update(UpdateZoneRequest, Zone)`** — `$zone->update($request->all())`.
  ⚠️ Inconsistency to note: `store` decodes `area` JSON into a `Polygon`, but
  `update` passes the raw `area` string straight through `$request->all()` to the
  `Polygon`-cast column. The edit Blade re-submits `area` as the same
  `JSON.stringify([{lat,lng}])` string. **Do not change this logic** — mirror
  whatever the controller does for each verb in the API.
- `show(Zone)` — `zone_show`. `view('admin.zones.show', ['zone'])`.
- `destroy` / `massDestroy(MassDestroyZoneRequest)` — `authorize('can-delete')`.

### Blade views

| View | Purpose |
|---|---|
| `admin/zones/index.blade.php` | Client-side DataTable (id, name, actions); mass-delete |
| `admin/zones/create.blade.php` | Name input + Google-Maps **DrawingManager** polygon draw; hidden `#area` filled with `JSON.stringify([{lat,lng}])` on submit |
| `admin/zones/edit.blade.php` | Same, plus renders existing polygon from `$zone->area->toJson()` (GeoJSON `coordinates`) and a "Reset Map" button |
| `admin/zones/show.blade.php` | Detail (name + map of the polygon) |

### Google Maps polygon drawing (the bespoke widget)

- Script: `maps.googleapis.com/maps/api/js?key=…&libraries=drawing&callback=initMap`.
- `initMap()` creates a map centered `{lat:24.7156901, lng:46.6439257}`, zoom 12,
  with a `DrawingManager` (polygon mode, editable polygons).
- On `polygoncomplete`, iterates `polygon.getPath()`, each vertex
  `getAt(i).toUrlValue(6)` → `"lat,lng"` (6-decimal precision) → pushes
  `{lat, lng}` to `polygonArray`.
- On form submit: `$('#area').val(JSON.stringify(polygonArray))`.
- **Edit**: `var locations = <?= json_encode($zone->area->toJson()) ?>` →
  `JSON.parse(locations).coordinates` → `coordinates[0]` is `[lng, lat]` pairs
  (GeoJSON order) → rendered as a red filled `google.maps.Polygon`. (Note the
  axis swap: GeoJSON is `[lng,lat]`, the draw payload is `{lat,lng}`.)

### Permissions / Gates

`zone_access`, `zone_create`, `zone_edit`, `zone_show`; delete via **`can-delete`**
(`destroy`/`massDestroy`); `MassDestroyZoneRequest` checks `zone_delete`.

### Form Request rules

- **StoreZoneRequest** (`zone_create`): `area` req string; `name` req string.
- **UpdateZoneRequest** (`zone_edit`): `area` req string; `name` req string.
- **MassDestroyZoneRequest** (`zone_delete`): `ids` array, `ids.*` exists:zones,id.

### Special behaviors to PRESERVE

- Client-side DataTable.
- `area` is submitted as a **JSON string** of `[{lat,lng}]`; backend `store`
  decodes it into a closed `Polygon`/`LineString`/`Point`. The ring is
  auto-closed by appending the first point.
- 6-decimal coordinate precision (`toUrlValue(6)`).
- GeoJSON read-back uses `[lng,lat]` ordering — the Vue map must swap correctly.

---

## 3. Target design (Vue + Tailwind)

| Blade view | Vue view | Components |
|---|---|---|
| `zones/index` | `views/Zones/ZonesList.vue` | `Breadcrumb`, `DataTable` (client-side: id, name), `BaseButton`, `BaseModal` (delete) |
| `zones/create`+`edit` | `views/Zones/ZoneForm.vue` | `FormInput` (name), **`ZonePolygonMap.vue`** widget, `BaseCard`, `BaseButton` |
| `zones/show` | `views/Zones/ZoneShow.vue` | `BaseCard` (name) + read-only `ZonePolygonMap` (display mode) |

### `ZonePolygonMap.vue` (bespoke widget)
- Loads the Google Maps JS API (`libraries=drawing`) once (lazy loader so it isn't
  duplicated across SPA navigations). Reuse the same API key the Blade uses
  (move it to a config/env-exposed value rather than hardcoding in JS).
- Props: `modelValue` = `[{lat, lng}]` (the area), `readonly` (Boolean).
- Edit mode: `DrawingManager` in polygon mode (editable). On `polygoncomplete`,
  emit `update:modelValue` with `[{lat:Number, lng:Number}]` at 6-decimal
  precision. Provide a **"Reset Map"** button (parity with edit Blade).
- Display/preview mode: render the existing polygon (red fill, matching the
  current style) and disable drawing.
- On load with an existing area, draw the saved polygon (convert GeoJSON
  `[lng,lat]` → `{lat,lng}`).
- Center `{lat:24.7156901, lng:46.6439257}`, zoom 12.

`ZoneForm.vue` keeps `area` as a reactive `[{lat,lng}]` array and **serializes it
to a JSON string** (`JSON.stringify`) when POST/PUT-ing, because the Form Request
expects `area` as a `string` and `store` does `json_decode`. Keep this contract.

### Vue Router
```
/admin/zones           → ZonesList  meta:{perm:'zone_access'}
/admin/zones/create    → ZoneForm   meta:{perm:'zone_create'}
/admin/zones/:id/edit  → ZoneForm   meta:{perm:'zone_edit'}
/admin/zones/:id       → ZoneShow   meta:{perm:'zone_show'}
```

### nav.config.js
Zones entry exists under "Drivers" group, perm `zone_access`.

### States
List EmptyState; map loading spinner while Google JS loads; "draw a polygon to
continue" hint + inline `area` error (422) under the map.

---

## 4. Data / API contract

Base `/app/api/zones`.

| Method | Path | Reuses |
|---|---|---|
| GET | `/app/api/zones` | `Zone::all()` + `zone_access` — return list (client-side table) |
| GET | `/app/api/zones/{id}` | detail incl. polygon as `[{lat,lng}]` | `zone_show` |
| POST | `/app/api/zones` | **StoreZoneRequest** + `zone_create` — body `{ name, area:"<json string>" }`; reuse the manual Point/LineString/Polygon build |
| PUT | `/app/api/zones/{id}` | **UpdateZoneRequest** + `zone_edit` — mirror existing `update` behavior |
| DELETE | `/app/api/zones/{id}` / `…` (`{ids:[]}`) | `can-delete` + **MassDestroyZoneRequest** |

### Area data shape
- **Sent to API** (create/update): `area` is a **JSON string** of
  `[{ "lat": 24.715690, "lng": 46.643925 }, …]` (6-decimal), NOT auto-closed
  (the backend closes the ring). This matches `JSON.stringify(polygonArray)`.
- **Returned by API** (list/detail): expose the polygon as a decoded
  `area: [{lat, lng}]` array so the Vue map can draw it directly without the SPA
  parsing GeoJSON. Derive it server-side from `$zone->area` (convert the spatial
  Polygon's points to `{lat,lng}`), absorbing the GeoJSON `[lng,lat]` swap on the
  backend. (Alternatively return raw `area->toJson()` GeoJSON and swap in the
  widget — but pre-shaping to `{lat,lng}` keeps the SPA dumb. Pick one and
  document; **recommendation: backend returns `[{lat,lng}]`**.)

### List row shape
```json
{ "id": 4, "name": "North Riyadh" }
```
### Detail shape
```json
{ "data": { "id":4, "name":"North Riyadh",
  "area": [ {"lat":24.715690,"lng":46.643925}, … ] } }
```

### Validation surfacing
422 → `name` under the name input; `area` under the map. The map must mark itself
invalid until a polygon is drawn (UX only; backend Form Request is authoritative).

### Exports
No export route — `DataTable.vue` client-side buttons only.

---

## 5. Migration steps

- [ ] Backend: `Api\ZonesApiController` — list, show (decode polygon →
      `[{lat,lng}]`), store (reuse Point/LineString/Polygon build from
      `ZonesController::store`), update (mirror existing), destroy/massDestroy.
- [ ] Backend: expose Google Maps API key via config/boot payload (stop
      hardcoding in JS); register routes in `routes/app_api.php`.
- [ ] Frontend: `ZonePolygonMap.vue` (draw + display + reset, lazy GMaps loader).
- [ ] Frontend: `ZonesList.vue`, `ZoneForm.vue`, `ZoneShow.vue`.
- [ ] Wire router + nav perm.
- [ ] Parity test: draw → save creates a closed Polygon identical to Blade;
      edit loads the saved polygon, reset clears it, re-save updates correctly;
      6-decimal precision preserved; `area` 422 surfaces under the map.
- [ ] Cutover: flip Zones nav. (Pilot — proves the bespoke-widget pattern.)

## 6. Risks / must-not-break

- **Spatial geometry**: `store` builds Points from `{lat,lng}` and appends the
  first point to close the ring. Reuse verbatim — a malformed ring throws at the
  DB layer.
- **Axis order**: draw payload is `{lat,lng}`; GeoJSON read-back is `[lng,lat]`.
  Get the swap right (do it on the backend per recommendation).
- **store vs update divergence**: store decodes JSON; update mass-assigns the raw
  `area` string onto the Polygon-cast column. Mirror each verb's actual behavior;
  do not "unify" them.
- **6-decimal precision** (`toUrlValue(6)`) — keep when emitting coordinates.
- Google Maps key currently hardcoded in Blade; avoid leaking a second copy —
  centralize.
- Delete dual-gate (`can-delete` vs `zone_delete`).
- Don't broaden Tailwind `content` for the map widget; keep SPA-scoped.

## 7. Out of scope / open questions

- The store/update `area`-handling inconsistency: confirm with backend whether
  `update` should also decode+rebuild the Polygon like `store` does. **Out of
  scope to fix** (logic change) — flagged for the team; the migration mirrors
  current behavior.
- Multi-ring / hole polygons are not supported by the current UI (single outer
  ring only) — keep single-ring.
- Confirm preferred area-payload direction (backend-shaped `[{lat,lng}]` vs raw
  GeoJSON) before building the widget.
