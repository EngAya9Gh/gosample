# Roles — Frontend Migration Plan

> Read [`01-foundation.md`](01-foundation.md) first. Conforms to & references the
> shared `/app/api` JSON layer, Form-Request/Gate reuse, strangler-fig cutover,
> `422` form-error contract, options-endpoint shape, and RTL/i18n bridge defined
> there — not repeated here. **Presentation only**; no change to Spatie
> permission assignment, `guard_name` handling, or authorization.

---

## 1. Module overview

Access-control "Roles" — Spatie roles, each owning a many-to-many set of
**permissions**. Nav group **Users** (`nav.config.js`:
`{ label:'Roles', route:'/admin/roles', perm:'role_access' }`). Drives what every
role can do app-wide.

Gates (verified in `RolesController` + Form Requests):
`role_access`, `role_create`, `role_edit`, `role_show`, `role_delete`
(mass-destroy) + global `can-delete`.

---

## 2. Current implementation (Blade / Velzon)

### Routes (`routes/web.php` ~lines 51–53)
| Method | URI | Name | Action |
|---|---|---|---|
| DELETE | `/admin/roles/destroy` | `admin.roles.massDestroy` | `massDestroy` (before resource) |
| GET | `/admin/roles` | `admin.roles.index` | `index` |
| GET | `/admin/roles/create` | `admin.roles.create` | `create` |
| POST | `/admin/roles` | `admin.roles.store` | `store` |
| GET | `/admin/roles/{role}` | `admin.roles.show` | `show` |
| GET | `/admin/roles/{role}/edit` | `admin.roles.edit` | `edit` |
| PUT/PATCH | `/admin/roles/{role}` | `admin.roles.update` | `update` |
| DELETE | `/admin/roles/{role}` | `admin.roles.destroy` | `destroy` |

> **Parallel resource exists:** `admin.client-roles.*` (same controller pattern,
> `guard_name = client_users`). Blade switches routes by `Auth::guard('web')` vs
> `client_users`. The admin SPA targets the **`web`** guard / `admin.roles.*`
> only; client-roles is out of scope for this plan.

### Controller actions (`RolesController.php`)
- `index` — `role_access`; `Role::with(['permissions'])->get()` → view.
- `create` — `role_create`; `$permissions = Permission::pluck('name','id')`.
- `store(StoreRoleRequest)` — `Role::create($request->all())`;
  `permissions()->sync(input('permissions',[]))`; **`Cache::forget('spatie.permission.cache')`**.
- `edit(Role)` — `role_edit`; same `$permissions`; `$role->load('permissions')`.
- `update(UpdateRoleRequest, Role)` — `update`; re-sync permissions; clear cache.
- `show(Role)` — `role_show`; `$role->load('permissions')`.
- `destroy(Role)` — `authorize('can-delete')`; `$role->delete()`.
- `massDestroy(MassDestroyRoleRequest)` — `authorize('can-delete')`; bulk delete.

### Blade views
| File | Purpose |
|---|---|
| `admin/roles/index.blade.php` | **Not a DataTable** — a responsive **card grid**. Each card: deterministic gradient avatar w/ initials, role name, id badge, permission **count + progress bar** (vs max), `Admin` crown tag for privileged names, view/edit/delete actions. Header **stat strip** (total roles, total permission assignments) + **client-side search** filter (`#role-filter`). Empty + no-results states. |
| `admin/roles/create.blade.php` | name (req) + hidden `guard_name` (`web`/`client_users`) + **permission picker grid**: toggle-switch chips, **Select-All / Deselect-All**, **filter box**, live **counter** `selected / total`. |
| `admin/roles/edit.blade.php` | Same form; permissions preselected from `$role`. |
| `admin/roles/show.blade.php` | Read-only: id, name, permissions list (sorted, badges). |

### Form Request rules (reuse verbatim)
**`StoreRoleRequest`** (`role_create`): `name` required|string;
`permissions` **required|array**; `permissions.*` integer.
**`UpdateRoleRequest`** (`role_edit`): identical to Store.
**`MassDestroyRoleRequest`** (`role_delete`): `ids` required|array; `ids.*`
exists:roles,id.

### Special behaviors to PRESERVE
- **`guard_name` hidden field** sent on create/update (`web` for admin). It is
  passed straight into `Role::create($request->all())` — keep sending it.
- **`Cache::forget('spatie.permission.cache')`** after every store/update — must
  run identically or permission checks go stale.
- Card-grid index + client-side search; permission toggle grid with
  Select-All/Deselect-All/filter/counter.
- Deterministic avatar gradients + "Admin" crown tag (presentation; can re-derive
  in Vue from the role name).

---

## 3. Target design (Vue + Tailwind)

Directory `resources/js/vue/views/Roles/`.

| Blade view | → Vue view | vue-build components |
|---|---|---|
| `index` | `RolesList.vue` | `Breadcrumb`, `StatCard` (total roles / total perm assignments), `FormInput` (search), grid of `BaseCard` + `BaseAvatar` (initials) + permission count/progress, `StatusBadge` ("Admin"), `BaseButton`, `BaseModal` (delete), `EmptyState` |
| `create` + `edit` | `RoleForm.vue` (mode prop) | `Breadcrumb`, `BaseCard`, `FormInput` (name), **`FormSelect` `:multiple`** for permissions (Select-all/Clear + search + counter built in) **or** a `FormToggle` grid to mirror the toggle look |
| `show` | `RoleShow.vue` | `Breadcrumb`, `BaseCard`, `StatusBadge`/chips for permissions |

- **Permissions multi-select → `FormSelect` `:multiple="true"`** (its panel gives
  Select-all / Clear / search; chips show selection; counter = `length`). This
  cleanly reproduces the Blade toggle-grid behavior with one component. If exact
  toggle-switch styling is desired, render `FormToggle` rows inside a grid bound
  to an id array — either way `v-model` is an **array of permission ids**.
- **`guard_name`** is **not** a user-editable field; the SPA sends it as a fixed
  hidden value `"web"` in the create/update payload (admin SPA = `web` guard).
- Index keeps the **card-grid + search** look (not `DataTable`); derive avatar
  gradient/initials/crown in Vue from role name (pure presentation).
- Router routes: `/admin/roles`, `/admin/roles/create`, `/admin/roles/:id/edit`,
  `/admin/roles/:id` with `meta.perm` = the matching `role_*` gate.
- nav entry exists (`perm:'role_access'`).
- States: loading skeletons in cards, `EmptyState` (no roles), no-results message
  on search, toasts via foundation interceptors.

---

## 4. Data / API contract

Base `/app/api/roles` (new `Api\RolesApiController`; reuses queries + Form
Requests + Gates; **no web controller change**).

| Method | Path | Purpose | Reuses |
|---|---|---|---|
| GET | `/app/api/roles` | list (cards) | `role_access`; `Role::with('permissions')` |
| GET | `/app/api/roles/options` | permission option list | `Permission::pluck('name','id')` |
| GET | `/app/api/roles/{id}` | detail (show + edit prefill) | `role_show` |
| POST | `/app/api/roles` | create | **`StoreRoleRequest`** + `role_create` |
| PUT | `/app/api/roles/{id}` | update | **`UpdateRoleRequest`** + `role_edit` |
| DELETE | `/app/api/roles/{id}` | delete one | `authorize('can-delete')` |
| DELETE | `/app/api/roles` | mass delete `{ids:[]}` | **`MassDestroyRoleRequest`** (`role_delete`) + `can-delete` |

**List row shape** (drives the cards):
```json
{ "id": 2, "name": "Manager", "permissions_count": 18,
  "permissions": [{ "id": 5, "name": "task_access" }] }
```
> Include `permissions_count` (the card's number/progress) and the names for the
> chips. `max` for the progress bar is computed client-side from the list.

**Options shape:** `{ "permissions": [{ "value": 5, "label": "task_access" }] }`.

**Detail shape:** `{ "data": { "id":2, "name":"Manager", "permission_ids":[5,6,7] } }`.

**Create/Update payload:** `{ name, guard_name: "web", permissions: [ids] }`.
The api method must mirror the controller: `Role::create/update($data)` (so
`guard_name` is written), `permissions()->sync(ids)`, then
`Cache::forget('spatie.permission.cache')`. Errors → foundation `422` mapping
(`name`, `permissions`).

---

## 5. Migration steps (ordered, checkable)

- [ ] backend: `Api\RolesApiController` (index/options/show/store/update/destroy/massDestroy) reusing `Store/Update/MassDestroyRoleRequest` + Gates; routes in `routes/app_api.php`.
- [ ] backend: ensure store/update write `guard_name`, sync permissions, and call `Cache::forget('spatie.permission.cache')`.
- [ ] frontend: `RolesList.vue` (StatCards + search + BaseCard grid + delete modal).
- [ ] frontend: `RoleForm.vue` (name + multi `FormSelect`/toggle grid of permissions) using `/options`; send fixed `guard_name:"web"`.
- [ ] frontend: `RoleShow.vue`.
- [ ] wire router + `meta.perm`; confirm nav entry.
- [ ] parity test vs Blade: same card stats, search, permission-count/progress, validation errors (`permissions required|array`), permission-hidden actions, RTL.
- [ ] cutover: point Roles nav item at `/app` route.

---

## 6. Risks / must-not-break

- **`Cache::forget('spatie.permission.cache')`** after every write — omitting it
  leaves stale gates; the api MUST replicate it.
- **`guard_name`** must be persisted (`web` for admin). Spatie keys
  role↔permission by guard; a missing/wrong guard breaks `sync` and checks.
- **`permissions` required|array** — a role with zero permissions is rejected by
  the Form Request; keep that (don't allow empty in JS only to fail server-side
  silently).
- Authorization layering: `role_*` gates on read/write; `can-delete` on
  delete/massDestroy; `MassDestroyRoleRequest` keeps its own `role_delete` gate.
- Permission **ids** (not names) are synced — FormSelect/toggle `value` must be id.
- Do **not** touch the parallel `client-roles` flow.

## 7. Out of scope / open questions

- `client-roles.*` resource (client guard) — separate migration.
- **guard_name oddity (carry-over):** roles in this DB use
  `guard_name = <role name>` per the known data issue; the SPA still sends
  `"web"` exactly as Blade does — don't "fix" the data here.
- Index stays a **card grid** (not the foundation server-side DataTable); roles
  are few. Revisit only if the count grows.
- Permission-seeder gap may mean `role_delete` is absent in the seeder; test with
  a role that holds the gates.
