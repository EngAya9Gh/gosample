# Barcodes (+ Generate / Bulk-Generate) — Frontend Migration Plan

> Read [`01-foundation.md`](01-foundation.md) first. API layer, `{data,meta}`,
> 422→form mapping, `DataTable` contract, permissions and RTL/i18n live there.
> This module is **CRUD + a special generation action** (the barcode generate
> screen). The generation logic MUST be preserved exactly — it is the only
> place that mutates the `last_number` counter.

---

## 1. Module overview

Barcodes manages the running barcode counters used to print sample/bag labels.
A `barcodes` row is essentially `{ type, last_number }` — one row per type
(`bag`, `sample`, and historically `location`/`container`). Two concerns:
1. **CRUD** over the counter rows (Settings nav, gate `barcode_access`).
2. **Generate screen** — picks a type + count, renders a strip of printable
   `C128` barcode SVGs (server-rendered via `DNS1D`), and **advances the
   `last_number` counter** by the requested range on POST.

---

## 2. Current implementation (Blade / Velzon)

### Routes (`routes/web.php` ~81–85) — order matters (`generate` before resource)
| Method | URI | Name | Controller@action |
|---|---|---|---|
| GET | `/admin/barcodes/generate` | `admin.barcodes.generate` | `BarcodesController@generate` |
| POST | `/admin/barcodes/generate` | `admin.barcodes.generateBarcodes` | `BarcodesController@generateBarcodes` |
| DELETE | `/admin/barcodes/destroy` | `admin.barcodes.massDestroy` | `BarcodesController@massDestroy` |
| GET | `/admin/barcodes` | `admin.barcodes.index` | `BarcodesController@index` |
| GET | `/admin/barcodes/create` | `admin.barcodes.create` | `BarcodesController@create` |
| POST | `/admin/barcodes` | `admin.barcodes.store` | `BarcodesController@store` |
| GET | `/admin/barcodes/{barcode}` | `admin.barcodes.show` | `BarcodesController@show` |
| GET | `/admin/barcodes/{barcode}/edit` | `admin.barcodes.edit` | `BarcodesController@edit` |
| PUT/PATCH | `/admin/barcodes/{barcode}` | `admin.barcodes.update` | `BarcodesController@update` |
| DELETE | `/admin/barcodes/{barcode}` | `admin.barcodes.destroy` | `BarcodesController@destroy` |

### Controller actions → view data
- `index` — `Barcode::all()` → `admin.barcodes.index` (**client-side** DataTable).
- `create` / `store(StoreBarcodeRequest)` / `edit` / `update(UpdateBarcodeRequest)` — standard CRUD.
- `show(Barcode)` → detail.
- `destroy` / `massDestroy(MassDestroyBarcodeRequest)` — `authorize('can-delete')`.
- **`generate()`** — defaults `type='bag'`, `start = max(last_number where type='sample') + 1`, `sequence = 10`, `show = false` → `admin.barcodes.generate`. (NB: start is seeded off the *sample* counter regardless of default type — preserve this quirk.)
- **`generateBarcodes(Request)`** — reads `type`, `range`; `start = max(last_number where type=$type) + 1`; `sequence = range ?: 10`; then **`$record = Barcode::where('type',$type)->first(); $record->last_number += $request->range; $record->save();`** — i.e. mutates the counter. Re-renders `generate` with `show=true`.

### Blade views
| File | Purpose |
|---|---|
| `barcodes/index.blade.php` | Counter list (client-side DataTable) |
| `barcodes/create.blade.php` / `edit.blade.php` | Form: `type` (string), `last_number` (nullable int) |
| `barcodes/show.blade.php` | Detail |
| `barcodes/generate.blade.php` | Generate form (`range` number, `type` select bag/sample) + a `#barcode_area` strip of `DNS1D::getBarcodeSVG(...)` images + a client-side **Print** button (`printReport()` opens a new window and prints the SVG strip) |

### Gates
`barcode_access` / `barcode_create` (also guards both `generate` GET and POST) /
`barcode_edit` / `barcode_show` / `can-delete` (destroy/massDestroy).
`MassDestroyBarcodeRequest` authorizes on `barcode_delete`.

### Form Requests + rules
- `StoreBarcodeRequest` (`barcode_create`): `type required|string`, `last_number nullable|integer|min:-2147483648|max:2147483647`.
- `UpdateBarcodeRequest` (`barcode_edit`): same rules.
- `MassDestroyBarcodeRequest` (`barcode_delete`): `ids required|array`, `ids.* exists:barcodes,id`.

### Special behaviors to PRESERVE
- **Counter mutation on generate** — `last_number += range`. This is the single source of barcode uniqueness. Must run server-side, exactly once per generate POST.
- **`DNS1D` server-side SVG rendering** — barcodes are rendered as `C128` SVG. Per-type rendering rules in the Blade loop:
  - `bag` → `getBarcodeSVG($i . '-bag', 'C128', 6, 280)` per number in `[start, start+sequence)`.
  - `sample`/else → `getBarcodeSVG(str_pad($i,10,'0',...), 'C128', 4, 55)`.
  - `location`/`container` → single SVG of `$sequence-$type`.
- **Print** — browser print of the strip.

---

## 3. Target design (Vue + Tailwind)

### View mapping (under `resources/js/vue/views/Barcodes/`)
| Blade view | Vue view | vue-build components |
|---|---|---|
| `barcodes/index` | `BarcodesList.vue` | `Breadcrumb`, `DataTable`, `BaseButton`, `BaseModal`, `EmptyState` |
| `barcodes/create`+`edit` | `BarcodeForm.vue` | `BaseCard`, `FormInput` (`type`, `last_number`), `BaseButton` |
| `barcodes/show` | `BarcodeShow.vue` | `BaseCard` |
| `barcodes/generate` | `BarcodeGenerate.vue` | `Breadcrumb`, `BaseCard`, `FormInput` (range), `FormSelect` (type bag/sample), `BaseButton` (Generate, Print) |

**`BarcodeGenerate.vue`** is the special one:
- A form (count + type). On submit it POSTs to the generate action which returns
  the list of barcode values + their rendered SVG markup (see §4). The SVGs are
  injected into a print strip area (`v-html` of sanitized server SVG, or render
  client-side with a JS barcode lib that produces identical `C128` output —
  **prefer server-rendered SVG to guarantee byte-identical labels**).
- A **Print** button replicates `printReport()`: open print window with the strip
  + the `@page` margin styles. Keep the exact margin CSS from the Blade for label
  alignment on the physical printer.
- Add the `Generate` action to the list `Breadcrumb` actions and as a nav item.

### Vue Router routes
- `/admin/barcodes` → `BarcodesList.vue` `{ perm: 'barcode_access' }`
- `/admin/barcodes/generate` → `BarcodeGenerate.vue` `{ perm: 'barcode_create' }`
- `/admin/barcodes/create` `{ perm: 'barcode_create' }`, `/:id/edit` `{ perm: 'barcode_edit' }`, `/:id` `{ perm: 'barcode_show' }`

### nav.config.js
`Settings → Barcodes` (`barcode_access`) and `Settings → Generate Barcode`
(`barcode_create`) — confirm both exist; cutover only.

### States
Generate: before first submit, render the default 10-row preview (matches Blade
`show=false`). After submit, render the generated strip + a success toast.

---

## 4. Data / API contract

### CRUD (foundation §2) — base `/app/api/barcodes`
| Method | Path | Purpose | Reuses |
|---|---|---|---|
| GET | `/app/api/barcodes` | list | `Barcode::query()` + `barcode_access` |
| GET | `/app/api/barcodes/{id}` | detail | `barcode_show` |
| POST | `/app/api/barcodes` | create | **`StoreBarcodeRequest`** |
| PUT | `/app/api/barcodes/{id}` | update | **`UpdateBarcodeRequest`** |
| DELETE | `/app/api/barcodes/{id}` | delete | `can-delete` |
| DELETE | `/app/api/barcodes` | mass delete | **`MassDestroyBarcodeRequest`** |

Row shape: `{ id, type, last_number }`.

### Generate action endpoints (special — preserve generation logic)
| Method | Path | Purpose | Reuses |
|---|---|---|---|
| GET | `/app/api/barcodes/generate` | initial preview: `{ type:'bag', start, sequence:10, show:false, barcodes:[{value, svg}] }` | `BarcodesController@generate` logic + `barcode_create` |
| POST | `/app/api/barcodes/generate` | generate + **advance counter**: body `{ type, range }`; returns `{ type, start, sequence, show:true, barcodes:[{value, svg}] }` | `BarcodesController@generateBarcodes` logic + `barcode_create` |

- The API controller MUST call the **same counter-mutation + same `DNS1D::getBarcodeSVG` rendering** as the web controller. Extract that into a shared private method/service so web Blade and API produce identical SVGs and the counter advances once.
- `barcodes[].svg` = the server-rendered `C128` SVG string for each number (LTR). `barcodes[].value` = the encoded string (e.g. `10231-bag`).

### Validation
Generate has **no Form Request** today (`Request` only). Keep it loose: `type` in
`[bag,sample,...]`, `range` integer (defaults to 10). Do not add stricter rules
that would reject inputs the Blade accepts.

---

## 5. Migration steps (ordered, checkable)

- [ ] backend: extract counter-advance + DNS1D rendering into a shared method; build `Api\BarcodesApiController` CRUD + `generate`/`generateBarcodes` JSON endpoints reusing it + the 3 Form Requests.
- [ ] frontend: `BarcodesList.vue`, `BarcodeForm.vue`, `BarcodeShow.vue`.
- [ ] frontend: `BarcodeGenerate.vue` (form → POST → render SVG strip + Print).
- [ ] wire router (5 routes) + nav (Barcodes + Generate).
- [ ] parity test: generated SVGs byte-identical to Blade; counter advances by exactly `range` once; print layout matches.
- [ ] cutover nav.
- [ ] mark status in `00-README.md`.

---

## 6. Risks / must-not-break

- **Double-advance of `last_number`** — if the SPA retries the POST (network/double-click), the counter advances twice and burns barcode numbers. Disable the Generate button while in-flight; the backend logic itself stays single-write (do not make the API idempotent in a way that changes counts).
- **`generate()` seeds `start` off the `sample` counter** even when default type is `bag` — preserve this exact quirk in the GET preview.
- **SVG parity** — must use the same `DNS1D` widths/heights per type. Do not swap for a JS lib that renders visually-different C128.
- Generate is gated by `barcode_create` (not a separate generate gate) — keep.
- `massDestroy` needs both `barcode_delete` (request) and `can-delete` (controller).

---

## 7. Out of scope / open questions

- The generate type select only offers `bag`/`sample` in the UI, but the render loop handles `location`/`container` too. Keep the loop branches server-side in case other types are passed; do not hardcode bag/sample only in the renderer.
- Decide whether to render SVG server-side (recommended, byte-identical) or client-side (risk of drift). This plan assumes server-side.
- Print fidelity depends on the exact `@page` margins in `printReport()` — carry them over verbatim.
