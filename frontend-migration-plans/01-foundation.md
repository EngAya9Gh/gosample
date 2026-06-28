# Foundation — SPA Architecture, API Layer & Migration Workflow

> ## ⚠️ ARCHITECTURE UPDATE — now **Inertia.js** (not Vue Router + JSON API)
> Per the project spec (Vue.js + Tailwind + **Inertia.js**), the stack was switched
> from the Vue-Router-SPA + JSON-API approach described below to **Inertia.js**.
> What this changes in practice:
> - **No JSON API layer.** Each `/app` screen is a Laravel route returning
>   `Inertia::render('Name', $props)`. The old `/app/api/*`, `routes/app_api.php`,
>   `App.vue`, and `router/index.js` were removed.
> - **Routing is server-side** (Laravel web routes under `prefix('app')`); there is
>   no `vue-router`. Client navigation uses Inertia's `router.visit` / `<Link>`.
> - **Data via props**, not axios. Tables filter/paginate with Inertia *partial
>   reloads* (`router.get(url, params, { only: ['rows','total'] })`).
> - **Shared auth/permissions** come from `App\Http\Middleware\HandleInertiaRequests`
>   (not a `window.__MTC_BOOT__` payload).
> - Per-module backend controllers live in `App\Http\Controllers\App\`.
> - **Unchanged:** the strangler-fig coexistence, locked brand tokens, reusing
>   existing Form Requests + Gates + **exports (the `/admin` export routes)**, RTL/i18n,
>   the `vue-build` components, and the §8 cutover roadmap. Sections below that mention
>   "JSON API / axios / vue-router" are superseded by this note; the *intent* (reuse
>   backend logic, presentation-only) still holds.

This is the backbone every module plan builds on. It is **not** a screen; it is
the plumbing that lets us migrate one module at a time without breaking the
running platform. Build this first, prove it with one pilot CRUD module (Zones or
Terms), then fan out.

---

## 0. Current state (what we're migrating from)

- **Stack:** Laravel + Velzon admin theme (Bootstrap 5 + jQuery), Blade views.
- **~35 resource modules** under `/admin`, namespace `App\Http\Controllers\Admin`,
  almost all registered with `Route::resource(...)` → standard
  `index/create/store/show/edit/update/destroy` + a `massDestroy` (DELETE) route.
- **Lists:** jQuery **DataTables** (`yajra/laravel-datatables-oracle`). Two
  flavors in the codebase:
  - *Client-side* — controller passes a full collection (e.g. `Zone::all()`),
    Blade renders all rows, DataTables paginates in the browser.
  - *Server-side (ajax)* — the `index` route returns JSON when `request()->ajax()`
    via a Yajra `DataTables::of(...)` builder (used by the big tables: Tasks,
    Samples, Drivers, Cars, Locations, Attendances, Notifications, Money
    Transfers, Audit Logs, Swaprequests, ApiAyenati).
- **Validation:** dedicated **Form Request** classes per module —
  `Store*Request`, `Update*Request`, `MassDestroy*Request` (in
  `app/Http/Requests/`). These hold the authoritative rules.
- **Authorization:** **Spatie permissions** surfaced as Laravel **Gates**.
  Canonical gate names per module: `<module>_access`, `<module>_create`,
  `<module>_edit`, `<module>_show`. **Deletion is governed by a single global
  Gate `can-delete`** (administered by the *Delete Permissions* module), used as
  `$this->authorize('can-delete')` in `destroy`/`massDestroy`.
- **Filters:** the `resources/views/partials/modern-filters.blade.php` partial
  (Select2 + flatpickr, teal-themed). Row actions: `partials/datatablesActions`.
- **Exports:** `maatwebsite/excel` (xlsx/csv) + `dompdf/dompdf` (PDF), often via
  POST routes (e.g. `tasks.export`) and dedicated export classes.
- **Special widgets:** Google Maps (map, zones polygon, location picker),
  ApexCharts (dashboards), FullCalendar (system calendar), barcode scan,
  drag-drop task reordering (drivers).
- **SPA scaffold already wired:** `/app/{any?}` → `spa.blade.php` →
  `resources/js/vue/main.js` (Vue 3 + Pinia + Vue Router + Tailwind + remixicon).
  Auth-gated by the same `auth` middleware as the rest of the panel.

---

## 1. Integration strategy — "Strangler Fig" + thin JSON API

We do **not** rewrite the backend and we do **not** adopt Inertia. We keep every
controller, Form Request, Gate, model, and export exactly as-is, and add a
**parallel JSON API** that the Vue SPA consumes. The Blade app keeps running the
whole time; we flip modules over one screen at a time.

```
┌─────────────────────────┐         ┌──────────────────────────────┐
│  Existing Blade panel    │         │  New Vue SPA  (/app/*)        │
│  /admin/* (Velzon)       │         │  resources/js/vue/*           │
│  — stays 100% working    │         │  — built from vue-build/      │
└───────────┬─────────────┘         └───────────────┬──────────────┘
            │ uses                                   │ calls (JSON)
            ▼                                        ▼
┌──────────────────────────────────────────────────────────────────┐
│  SHARED backend core (UNCHANGED): Models · Form Requests · Gates · │
│  business logic · Excel/PDF exports · queries                      │
└──────────────────────────────────────────────────────────────────┘
```

### Why a separate API layer (not "make controllers return JSON")
- Existing controller actions return **redirects + flash** (web semantics). The
  SPA needs **JSON + validation-error payloads** (api semantics). Bolting
  `wantsJson()` branches onto 35 controllers is invasive and easy to break.
- A dedicated `App\Http\Controllers\Api\<Module>ApiController` keeps the new
  surface isolated and reviewable, while **delegating to the same logic**:
  - reuse the **same Form Request** for validation (type-hint it in the api
    method — it validates identically and returns a 422 JSON payload
    automatically);
  - reuse the **same Eloquent queries** (extract shared query builders into a
    model scope or a thin service if a query is duplicated between web + api);
  - reuse the **same Gate checks** (`Gate::denies(...)` / `authorize('can-delete')`).

### Routing for the API
- Add an authenticated, namespaced group in `routes/web.php` (keeps the existing
  session/`auth` middleware and CSRF — simplest, since the SPA is same-origin and
  already cookie-authenticated):

  ```php
  Route::middleware(['auth'])
      ->prefix('app/api')->as('app.api.')
      ->namespace('App\\Http\\Controllers\\Api')
      ->group(base_path('routes/app_api.php'));
  ```
  Put the per-module API routes in a new `routes/app_api.php` (so we don't churn
  the big `web.php`). Same-origin + session cookie ⇒ no token handling; include
  the CSRF token (already in `spa.blade.php`'s `<meta name="csrf-token">`) on
  mutating requests.

> Alternative considered: `routes/api.php` with Sanctum. Rejected for now — the
> SPA is served from the same origin under the same `auth` session, so reusing
> the web guard is simpler and changes nothing about how auth works. Revisit only
> if a token-based mobile client needs the same endpoints.

---

## 2. Standard API contract (every CRUD module conforms)

Per module `<m>` (e.g. `zones`), expose under `/app/api/<m>`:

| Method | Path | Purpose | Reuses |
|---|---|---|---|
| GET | `/app/api/<m>` | List (server-side: search/sort/paginate) | existing index query + Gate `<m>_access` |
| GET | `/app/api/<m>/options` | Lookup lists for selects (only if the screen needs them) | existing `create`/`edit` query bits |
| GET | `/app/api/<m>/{id}` | Detail (show) | Gate `<m>_show` |
| POST | `/app/api/<m>` | Create | **`Store<M>Request`** + Gate `<m>_create` |
| PUT/PATCH | `/app/api/<m>/{id}` | Update | **`Update<M>Request`** + Gate `<m>_edit` |
| DELETE | `/app/api/<m>/{id}` | Delete one | `authorize('can-delete')` |
| DELETE | `/app/api/<m>` | Mass delete (`{ids:[]}`) | **`MassDestroy<M>Request`** + `can-delete` |

### List request (matches `vue-build`'s `DataTable` server-side mode)
`DataTable.vue` emits `@query` with: `{ q, sortKey, sortDir, page, pageSize }`.
The list endpoint accepts exactly these (plus module-specific filters), e.g.:

```
GET /app/api/tasks?q=10428&sortKey=id&sortDir=desc&page=2&pageSize=25
    &status=NEW&driver=12&client=4&from=2026-06-01&to=2026-06-27
```

### List response envelope (standardize this once)
```json
{
  "data": [ { /* one row, keyed by the DataTable column.key values */ } ],
  "meta": { "total": 1280, "page": 2, "pageSize": 25 }
}
```
- `data[i]` keys must match the `columns[].key` defined in the Vue view.
- Pre-format display values the backend already formats in Blade (dates, status
  labels, computed columns) so the SPA shows identical output. Keep raw ids too
  where the UI needs them (links, row actions).

### Detail / store / update
- Detail: `{ "data": { ...model fields the show screen needs... } }`.
- Store/Update success: `201`/`200` with `{ "data": {...} }`.
- Validation failure: Laravel's default **`422` with `{ "message", "errors": { field: [..] } }`** — `vue-build` form components render `error` per field; map `errors` straight onto them.

### Options endpoint
For create/edit selects (drivers, clients, locations, statuses…), return
`[{ "value": <id>, "label": <name> }]` — the exact shape `FormSelect.vue`/Select
options expect. One `/options` call per screen (bundle all needed lists into one
keyed object when there are several): `{ drivers:[...], clients:[...], ... }`.

---

## 3. SPA application shell & routing

### Directory layout (target, under `resources/js/vue/`)
```
resources/js/vue/
├── main.js                 # already exists — mounts App, imports tailwind + remixicon
├── App.vue                 # WRAP <router-view> in <AppShell> (see below)
├── router/
│   └── index.js            # add the real routes, lazy-loaded per module
├── layouts/                # ← copied from vue-build (AppShell, Sidebar, Topbar, Footer, nav.config.js)
├── components/             # ← copied from vue-build (DataTable, Form*, Base*, etc.)
├── composables/            # ← copied from vue-build (useToast, usePermissions, useCounter) + add useApi, useDataTable
├── lib/
│   ├── api.js              # axios instance: baseURL '/app/api', XSRF/CSRF, 401/403/422 interceptors
│   └── i18n.js             # locale + trans() bridge (see §5)
├── stores/                 # Pinia stores (auth/user, ui prefs) as needed
└── views/
    ├── Dashboard/Analytics.vue   # ← from vue-build
    ├── Tasks/TasksList.vue       # ← from vue-build
    └── <Module>/...              # built per module plan
```

### App.vue (integration shape — from `vue-build` README)
```vue
<script setup>
import AppShell from './layouts/AppShell.vue';
import { useRoute, useRouter } from 'vue-router';
const route = useRoute();
const router = useRouter();
</script>
<template>
  <AppShell :current="route.path" @navigate="router.push($event)">
    <router-view v-slot="{ Component }">
      <transition name="page" mode="out-in"><component :is="Component" /></transition>
    </router-view>
  </AppShell>
</template>
```

### Router
- `createWebHistory('/app')` (already set in the scaffold).
- One lazy route per migrated screen; route `path`s should **mirror the
  `route` values in `nav.config.js`** (e.g. `/admin/tasks`, `/dashboard`) so the
  sidebar's active-state matching and `@navigate` work unchanged.
- Add a `meta: { perm: '<gate>' }` per route and a global `beforeEach` guard that
  checks `usePermissions().can(meta.perm)` → redirect to a 403 view if denied
  (defense-in-depth; the backend Gate is still the real gate).
- Keep a catch-all `/:pathMatch(.*)*` → NotFound view.

> Note on route namespacing: nav routes currently point at `/admin/*` and
> `/dashboard`. The SPA lives under `/app`, so `/app/admin/tasks` is the real URL.
> Decide ONE of: (a) keep nav `route` values as-is and let the `/app` history
> base prefix them (simplest — `nav.config.js` stays untouched), or (b) strip the
> `/admin` prefix in the SPA. **Recommendation: (a)** — least churn, matches the
> delivered `nav.config.js`. Document the final choice here once picked.

---

## 4. Permissions in the SPA

- The backend remains the **only** security boundary (every api method keeps its
  Gate check). The SPA permission layer is for **rendering only** (hide buttons,
  filter nav, pre-empt obviously-denied navigation).
- Seed real permissions at boot. Expose the current user's permissions +
  `can-delete` flag to the SPA. Two options:
  - **Bootstrap payload (preferred):** inject into `spa.blade.php` as JSON
    (`window.__MTC_BOOT__ = { user, permissions, canDelete, locale }`) so the SPA
    has them on first paint with no extra request.
  - Or a `GET /app/api/me` endpoint called before the first render.
- In `main.js`, before `app.mount`, call
  `setPermissions(boot.permissions, boot.canDelete)` from the `usePermissions`
  composable (already in `vue-build`).
- Gate-name mapping is 1:1 with the Blade `@can(...)` keys. The plans list the
  exact gate per action. `canDelete()` ⇒ the global `can-delete` Gate.

> ⚠️ Known data issue (carry-over): the permission seeder is missing ~36
> UI-referenced permissions and roles use `guard_name = <role name>`. This does
> **not** change the migration approach (we read whatever permissions the user
> has), but test with a role that actually has the gates, and don't "fix"
> permissions inside this migration.

---

## 5. RTL, i18n & localized labels

- `AppShell` already drives `dir`/`lang` from a `lang` ref (ar ⇒ rtl) and
  persists to `localStorage`. Arabic is primary → default `lang = 'ar'`.
- The Blade app uses `trans('cruds.*')`, `trans('global.*')`, `trans('translation.*')`.
  To avoid hardcoding strings in Vue, **bridge the translations**:
  - Export the needed PHP lang arrays to JSON the SPA can import (build step or a
    `GET /app/api/i18n/{locale}` endpoint returning the merged `cruds/global/translation` keys), and provide a `t('cruds.zone.fields.name')` helper (`lib/i18n.js`).
  - Module plans reference label keys, not literal strings, so AR/EN both work.
- Force `direction:ltr` on intrinsically-LTR data (IDs, barcodes, IMEIs, plates,
  coordinates, times) — `vue-build` components already do this where relevant.

---

## 6. Shared data-fetching helpers (build once, in `composables/`)

- `lib/api.js` — a configured `axios` instance:
  - `baseURL: '/app/api'`, `withCredentials: true`, send `X-CSRF-TOKEN` from the
    `<meta>` tag on mutating requests;
  - response interceptor: `401` → redirect to `/login`; `403` → toast + route to
    403; `422` → reject with `error.response.data.errors` for forms to consume.
- `composables/useDataTable.js` — wraps `DataTable.vue`'s `@query` →
  `api.get('/<m>', { params })` → `{ rows, total, loading }`, with debounce on
  `q`. Every list screen uses this; module plans just pass the columns + filters.
- `composables/useCrud.js` (optional) — `fetchOne/create/update/remove` against a
  module base path, surfacing 422 errors to forms and success toasts.

---

## 7. Per-module migration workflow (repeat for each plan)

1. **Backend:** add `Api\<Module>ApiController` + routes in `routes/app_api.php`,
   delegating to the existing query + **the existing Form Request** + Gates.
   Add the list/detail/options/CRUD endpoints the plan specifies. No web
   controller changes.
2. **Frontend:** build the Vue view(s) under `views/<Module>/` from `vue-build`
   components (the plan maps each Blade view → Vue view → components).
3. **Wire:** add the router route(s); confirm the `nav.config.js` entry + perm.
4. **Parity test:** compare against the Blade screen — same data, same columns,
   same validation errors, same permissions hiding the same actions, same
   exports, RTL correct. (Use `/verify` or manual.)
5. **Cutover:** flip the nav item / link from `/admin/<m>` to the `/app` route.
   Blade route stays available for rollback.
6. **Mark status** in `00-README.md`.

---

## 8. Cutover, rollback & coexistence

- Both UIs share the same session + DB, so a user can be sent to either at any
  time. Cutover = changing where the menu points.
- Keep the legacy Blade routes registered until the **whole** module set is
  migrated and signed off; only then consider removing Velzon assets.
- The two Tailwind/Bootstrap CSS worlds are already isolated: Tailwind `content`
  is scoped to the SPA only, so neither resets the other. **Do not broaden
  Tailwind `content` to scan Blade files.**

### Agreed roadmap & final URL decision (locked with stakeholder)

The `/app/...` (screens) and `/app/api/...` (JSON) routes are a **temporary
staging area** used only during migration so the new UI runs safely alongside
the live system. The agreed sequence is:

1. **Build + verify each module under `/app/`** while the classic panel keeps running.
2. **Review the design together** (look/feel, RTL, behavior) screen by screen.
3. **Fix bugs together** while the Blade fallback is still live.
4. **Final cutover (Option A — reclaim original URLs):** once approved, point the
   SPA at the **main routes** (`/admin/...`, `/dashboard`), retire the legacy
   Blade routes, and **remove the temporary `/app` prefix/testing routes**.
   - Mechanics: change Vue Router base `createWebHistory('/app')` → `'/'` (or the
     chosen base), repoint the catch-all in `web.php`, redirect/retire the Blade
     routes. Config-level only — no business logic touched, fully reversible.
   - **Do not perform the final cutover without explicit stakeholder approval.**

---

## 9. Cross-cutting things that MUST NOT break

- **Authorization:** every api endpoint keeps the same Gate as its Blade twin;
  deletes keep `can-delete`. Never rely on the client check alone.
- **Validation parity:** always reuse the existing Form Request — do not
  re-implement rules in JS (client-side validation is UX-only, additive).
- **Exports:** Excel/PDF must keep producing identical files. Easiest path: the
  SPA links/POSTs to the **existing** export routes (they already return files);
  don't reimplement exports in the API unless a plan says so.
- **Side effects:** notifications (FCM), audit logging, status transitions,
  scheduled-task generation, emergency polling — all live in controllers/services
  and must run identically. If a screen triggers one, the api method must call
  the same code path.
- **Data shape:** column meanings, status enums, date formats, and computed
  fields must match the Blade output exactly (parity test catches drift).

---

## 10. Foundation deliverables checklist

> Updated for the **Inertia.js** architecture (see banner at top).

- [x] Inertia installed: `inertiajs/inertia-laravel` v1 (Laravel 9) + `@inertiajs/vue3` **v1.3** (must stay v1 to match the server adapter).
- [x] `HandleInertiaRequests` middleware (Kernel `web` group) shares `auth.{user,permissions,canDelete}` (lazy), `locale`, `flash` — replaces the boot payload.
- [x] Root view `resources/views/app.blade.php` (`@inertia`, `@inertiaHead`, `@vite`, csrf meta, `dir="rtl"`).
- [x] `/app` Inertia routes in `web.php` (`prefix('app')`): `/dashboard`, `/admin/tasks` → `App\Http\Controllers\App\TasksController`, catch-all → `system/ComingSoon`.
- [x] `vue-build` `layouts/`, `components/`, `composables/` copied into `resources/js/vue/`.
- [x] `main.js` → `createInertiaApp` (pages from `views/`, persistent `AppShell` layout); `App.vue`/`router/index.js`/`lib/api.js`/`useDataTable.js`/`app_api.php`/`Api/` removed.
- [x] Permission render-gating seeded from shared props; `nav.config.js` perms match the classic `@can` gates exactly.
- [x] Visual identity matched: sidebar `#005D69` + real logos, CTA teal-gradient buttons (animated icons), full Velzon palette, classic `mf-flatpickr` date/time picker.
- [x] **Pilot proven end-to-end via Tasks** (`/app/admin/tasks`): real filters, server-side pagination, options, exports (existing `/admin` routes), delete — the pattern is validated.
- [x] **i18n: English-first** (decision) — English is the default; `lib/i18n.js` `t()` helper is in place for future localization. The Arabic PHP→JSON bridge is intentionally NOT needed.
- [x] Styled Inertia **error pages** — `app/Exceptions/Handler@render` returns `views/system/Error.vue` (403/404/419/500/503) for Inertia requests; classic Blade error handling untouched.
- [x] Global **flash → toast** — `AppShell` watches the shared `flash` prop and surfaces success/error toasts.
- [ ] *(belongs to plan 37, not foundation)* Real **logout** (POST `/logout`) + in-app profile; topbar logout currently navigates to `/login`.

**✅ Foundation 01 is complete.** (The only open item, real logout, is owned by plan `37-profile-and-auth.md`.)
```
