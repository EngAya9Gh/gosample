# Permissions — Frontend Migration Plan

> Read [`01-foundation.md`](01-foundation.md) first. Conforms to & references the
> shared `/app/api` JSON layer, Form-Request/Gate reuse, strangler-fig cutover,
> the `{data,meta}` list envelope, the `422` form-error contract, and RTL/i18n
> bridge defined there — not repeated. **Presentation only**; no change to Spatie
> assignment, `guard_name` handling, or authorization.

---

## 1. Module overview

Access-control "Permissions" — the catalog of Spatie permission strings that
roles are built from. Nav group **Users** (`nav.config.js`:
`{ label:'Permissions', route:'/admin/permissions', perm:'permission_access' }`).
Simplest CRUD of the cluster: a permission has only a **name** (+ guard).

Gates (verified in `PermissionsController` + Form Requests):
`permission_access`, `permission_create`, `permission_edit`, `permission_show`,
`permission_delete` (mass-destroy) + global `can-delete`.

---

## 2. Current implementation (Blade / Velzon)

### Routes (`routes/web.php` ~lines 47–49)
| Method | URI | Name | Action |
|---|---|---|---|
| DELETE | `/admin/permissions/destroy` | `admin.permissions.massDestroy` | `massDestroy` (before resource) |
| GET | `/admin/permissions` | `admin.permissions.index` | `index` |
| GET | `/admin/permissions/create` | `admin.permissions.create` | `create` |
| POST | `/admin/permissions` | `admin.permissions.store` | `store` |
| GET | `/admin/permissions/{permission}` | `admin.permissions.show` | `show` |
| GET | `/admin/permissions/{permission}/edit` | `admin.permissions.edit` | `edit` |
| PUT/PATCH | `/admin/permissions/{permission}` | `admin.permissions.update` | `update` |
| DELETE | `/admin/permissions/{permission}` | `admin.permissions.destroy` | `destroy` |

> Parallel `admin.client-permissions.*` resource exists (client guard); Blade
> branches on `Auth::guard`. Admin SPA targets **`web`** / `admin.permissions.*`
> only — client-permissions out of scope.

### Controller actions (`PermissionsController.php`)
- `index` — `permission_access`; `Permission::all()` → **client-side DataTable**.
- `create` — `permission_create`; plain form.
- `store(StorePermissionRequest)` — `Permission::create($request->all())`;
  **`Cache::forget('spatie.permission.cache')`**.
- `edit(Permission)` — `permission_edit`.
- `update(UpdatePermissionRequest, Permission)` — update; clear cache.
- `show(Permission)` — `permission_show`.
- `destroy(Permission)` — `authorize('can-delete')`; delete.
- `massDestroy(MassDestroyPermissionRequest)` — `authorize('can-delete')`; bulk.

### Blade views
| File | Purpose |
|---|---|
| `admin/permissions/index.blade.php` | Client-side `datatable-Permission` (cols: ⬚, id, name, **guard_name**, actions). Bulk-delete button defined but **commented out**. |
| `admin/permissions/create.blade.php` | name (req) + hidden `guard_name` (`web`/`client_users`). |
| `admin/permissions/edit.blade.php` | Same single-field form. |
| `admin/permissions/show.blade.php` | Read-only name/guard. |

### Form Request rules (reuse verbatim)
**`StorePermissionRequest`** (`permission_create`): `name` required|string.
**`UpdatePermissionRequest`** (`permission_edit`): `name` required|string.
**`MassDestroyPermissionRequest`** (`permission_delete`): `ids` required|array;
`ids.*` exists:permissions,id.

### Special behaviors to PRESERVE
- Hidden **`guard_name`** sent on create/update → into `Permission::create(all())`.
- **`Cache::forget('spatie.permission.cache')`** after store/update.
- Client-side DataTable; `guard_name` column visible.

---

## 3. Target design (Vue + Tailwind)

Directory `resources/js/vue/views/Permissions/`.

| Blade view | → Vue view | vue-build components |
|---|---|---|
| `index` | `PermissionsList.vue` | `Breadcrumb`, `DataTable` (client-side; cols id, name, guard_name), `BaseButton`, `BaseModal` (delete), `EmptyState`, `usePermissions` |
| `create` + `edit` | `PermissionForm.vue` (mode prop) | `Breadcrumb`, `BaseCard`, `FormInput` (name) |
| `show` | `PermissionShow.vue` | `Breadcrumb`, `BaseCard` |

- Single field (`name`); `guard_name` is fixed `"web"` (hidden) — not edited.
- `DataTable` columns: `id` (mono), `name`, `guard_name` (mono, LTR).
- Delete via `BaseModal`; bulk-delete only when `canDelete()`.
- Router routes: `/admin/permissions`, `/create`, `/:id/edit`, `/:id` with
  `meta.perm` = matching `permission_*` gate.
- nav entry exists (`perm:'permission_access'`).
- States: skeleton, `EmptyState`, toast on success/422/403.

---

## 4. Data / API contract

Base `/app/api/permissions` (new `Api\PermissionsApiController`; reuse + Gates;
**no web controller change**).

| Method | Path | Purpose | Reuses |
|---|---|---|---|
| GET | `/app/api/permissions` | list | `permission_access`; `Permission::all()` |
| GET | `/app/api/permissions/{id}` | detail (show/edit prefill) | `permission_show` |
| POST | `/app/api/permissions` | create | **`StorePermissionRequest`** + `permission_create` |
| PUT | `/app/api/permissions/{id}` | update | **`UpdatePermissionRequest`** + `permission_edit` |
| DELETE | `/app/api/permissions/{id}` | delete one | `authorize('can-delete')` |
| DELETE | `/app/api/permissions` | mass delete `{ids:[]}` | **`MassDestroyPermissionRequest`** (`permission_delete`) + `can-delete` |

> No `/options` endpoint — this module has no relationship selects.

**List row shape:** `{ "id": 5, "name": "task_access", "guard_name": "web" }`.
**Detail shape:** `{ "data": { "id":5, "name":"task_access", "guard_name":"web" } }`.
**Create/Update payload:** `{ name, guard_name: "web" }`. The api method mirrors
the controller: `Permission::create/update($data)` then
`Cache::forget('spatie.permission.cache')`. Errors → foundation `422` onto
`FormInput.error` (`name`).

---

## 5. Migration steps (ordered, checkable)

- [ ] backend: `Api\PermissionsApiController` (index/show/store/update/destroy/massDestroy) reusing `Store/Update/MassDestroyPermissionRequest` + Gates; routes in `routes/app_api.php`.
- [ ] backend: ensure store/update call `Cache::forget('spatie.permission.cache')` and persist `guard_name`.
- [ ] frontend: `PermissionsList.vue` (DataTable + delete modal).
- [ ] frontend: `PermissionForm.vue` (single name field; fixed `guard_name:"web"`).
- [ ] frontend: `PermissionShow.vue`.
- [ ] wire router + `meta.perm`; confirm nav entry.
- [ ] parity test vs Blade: columns incl. guard_name, validation (`name required`), permission-hidden actions, RTL.
- [ ] cutover: point Permissions nav item at `/app` route.

---

## 6. Risks / must-not-break

- **`Cache::forget('spatie.permission.cache')`** after writes — replicate it.
- **`guard_name`** persisted as `web` (admin) exactly as Blade.
- Authorization layering: `permission_*` on read/write; `can-delete` on
  delete/massDestroy; `MassDestroyPermissionRequest` keeps its own
  `permission_delete` gate.
- Deleting/renaming a permission affects roles that reference it — same risk as
  Blade; no new guard needed, just don't bypass `can-delete`.

## 7. Out of scope / open questions

- `client-permissions.*` (client guard) — separate migration.
- guard_name oddity (carry-over) — SPA sends `"web"` like Blade; don't "fix" data.
- Index stays client-side DataTable (small catalog); revisit only if it grows.
- Permission-seeder gap: `permission_delete` may be absent; test with a role that
  holds the gates.
