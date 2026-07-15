# Users — Frontend Migration Plan

> Read [`01-foundation.md`](01-foundation.md) first. This plan **conforms to and
> references** the shared architecture there (the `/app/api` JSON layer that
> reuses Form Requests + Gates, the strangler-fig cutover, the `{data,meta}`
> list envelope, the `422 → {errors:{field:[]}}` form contract, options-endpoint
> shape, permission seeding, RTL/i18n bridge). It does **not** repeat those
> mechanics. **Presentation layer only** — no business-logic, validation, Spatie
> assignment, or authorization changes.

---

## 1. Module overview

Access-control "Users" CRUD. Nav group **Users** (`nav.config.js`:
`{ label:'Users', route:'/admin/users', perm:'user_access' }`). Manages user
accounts and their **roles** (Spatie, many-to-many) plus their assigned
**clients** (`client_user` pivot). It matters because roles drive every Gate in
the app, and the `clients` pivot scopes data for client-facing roles.

Gates (verified in `UsersController` + Form Requests):
`user_access`, `user_create`, `user_edit`, `user_show`, `user_delete`
(mass-destroy authorize) and the global `can-delete` (single + back-end delete).

---

## 2. Current implementation (Blade / Velzon)

### Routes (`routes/web.php` ~lines 55–57, inside `admin.` group, `auth` mw)
| Method | URI | Name | Action |
|---|---|---|---|
| DELETE | `/admin/users/destroy` | `admin.users.massDestroy` | `UsersController@massDestroy` (declared **before** the resource) |
| GET | `/admin/users` | `admin.users.index` | `index` |
| GET | `/admin/users/create` | `admin.users.create` | `create` |
| POST | `/admin/users` | `admin.users.store` | `store` |
| GET | `/admin/users/{user}` | `admin.users.show` | `show` |
| GET | `/admin/users/{user}/edit` | `admin.users.edit` | `edit` |
| PUT/PATCH | `/admin/users/{user}` | `admin.users.update` | `update` |
| DELETE | `/admin/users/{user}` | `admin.users.destroy` | `destroy` |

### Controller actions (`app/Http/Controllers/Admin/UsersController.php`)
- `index` — `Gate::denies('user_access')`; `User::with(['roles'])->get()` → view.
  **Client-side DataTable** (`datatable-User`, not ajax; `pageLength:100`,
  default sort col 1 desc).
- `create` — `user_create`; `$clients = Client::all()`,
  `$roles = Role::pluck('name','id')`.
- `store(StoreUserRequest)` — `$data = $request->all()`; **force
  `$data['client_id'] = null`** (gradual-migration: clients live only in the
  pivot now); `User::create($data)`; `roles()->sync(...)`; `clients()->sync(...)`;
  redirect to index.
- `edit(User)` — `user_edit`; same `$roles`/`$clients`; `$user->load(['roles','clients'])`.
- `update(UpdateUserRequest, User)` — same `client_id=null` rule;
  `$user->update($data)`; re-sync roles & clients.
- `show(User)` — `user_show`; `$user->load('roles')`.
- `destroy(User)` — `authorize('can-delete')`; soft? `$user->delete()`.
- `massDestroy(MassDestroyUserRequest)` — `authorize('can-delete')`;
  `User::whereIn('id', ids)->delete()`.

### Model facts to PRESERVE (`app/Models/User.php`)
- **Password is auto-hashed by a `password` mutator** (`needsRehash` →
  `Hash::make`). The api must pass the **raw** password through `create/update`
  exactly like the controllers — do **not** hash in the API.
- `roles()` = `belongsToMany(Role, 'model_has_roles', 'model_id', 'role_id')`.
- `clients()` = `belongsToMany(Client, 'client_user', 'user_id', 'client_id')`.
- `getAssignedClientIdsAttribute()` merges pivot client ids + legacy `client_id`
  → use this for the edit screen's preselected client values.

### Blade views
| File | Purpose |
|---|---|
| `admin/users/index.blade.php` | Client-side DataTable; cols: ⬚, id, name, email, email_verified_at, roles (badges), actions. Bulk-delete button defined but **commented out** (`// dtButtons.push(deleteButton)`). |
| `admin/users/create.blade.php` | Form: name (req), email (req), password (req), **clients** Select2 multi (optional, labels = `client.english_name`), **roles** Select2 multi (req) with Select-All / Deselect-All buttons + password show/hide toggle. |
| `admin/users/edit.blade.php` | Same form; password "leave blank to keep"; clients preselected from `$user->assigned_client_ids`; roles preselected via `$user->roles->contains($id)`. |
| `admin/users/show.blade.php` | Read-only table: id, name, email, email_verified_at, roles (disabled checkboxes). |

### Form Request rules (authoritative — reuse verbatim)
**`StoreUserRequest`** (`authorize`= `user_create`):
- `name` required|string
- `email` required|**unique:users**
- `password` **required**
- `roles` required|array; `roles.*` integer
- `clients` `Rule::requiredIf( !in_array(1, roles) )` (i.e. **required unless a
  SuperAdmin role id=1 is selected**), array, nullable; `clients.*` integer
**`UpdateUserRequest`** (`authorize`= `user_edit`):
- `name` required|string
- `email` required|`unique:users,email,{currentId}`
- *(no `password` rule → optional on update; blank keeps current)*
- `roles` required|array; `roles.*` integer
- `clients` array, nullable; `clients.*` integer
**`MassDestroyUserRequest`** (`authorize`= **`user_delete`**): `ids` required|array,
`ids.*` exists:users,id.

### Special behaviors to PRESERVE
- Client-side DataTable (no ajax). Roles rendered as info badges.
- Select2 multi-selects for **roles** (required) and **clients** (optional) with
  explicit Select-All / Deselect-All.
- Password reveal toggle (UX only).
- The `client_id = null` write rule and dual roles+clients `sync()`.

---

## 3. Target design (Vue + Tailwind)

Directory `resources/js/vue/views/Users/`.

| Blade view | → Vue view | vue-build components |
|---|---|---|
| `index` | `UsersList.vue` | `Breadcrumb`, `DataTable` (client-side: small dataset, keep parity; or server-side if list grows), `StatusBadge`/chips for roles, `BaseButton`, `BaseModal` (delete confirm), `EmptyState`, `ToastHost`, `usePermissions` |
| `create` + `edit` | `UserForm.vue` (mode prop) | `Breadcrumb`, `BaseCard`, `FormInput` (name/email/password), `FormSelect` **multiple** (roles, required, Select-All built in via the multi panel), `FormSelect` **multiple** (clients), `BaseButton` |
| `show` | `UserShow.vue` | `Breadcrumb`, `BaseCard`, `StatusBadge`/chips for roles |

- **Roles & clients multi-select → `FormSelect` `:multiple="true"`.** Its
  built-in **Select all / Clear** panel buttons + searchable chips replace the
  Blade Select-All buttons and Select2. `v-model` is an **array of role/client
  ids** — matches the `roles[]` / `clients[]` payload the Form Request expects.
- Password field: `FormInput type="password"`. On **create** it is required; on
  **edit** show helper "leave blank to keep current" and **omit it from the
  payload when empty** (so the optional Update rule + mutator behave identically).
- Delete uses `BaseModal` confirm; bulk-delete only when `canDelete()`.
- Vue Router routes: `/admin/users`, `/admin/users/create`,
  `/admin/users/:id/edit`, `/admin/users/:id` (each `meta.perm` =
  `user_access`/`user_create`/`user_edit`/`user_show`).
- `nav.config.js` entry already exists (`perm:'user_access'`).
- States: loading skeleton (DataTable built-in), `EmptyState` when no users,
  toast on 403/422/success per foundation interceptors.

---

## 4. Data / API contract

Base: `/app/api/users` (reuses existing queries + the **same Form Requests** +
Gates). New `Api\UsersApiController`; **no web controller changes**.

| Method | Path | Purpose | Reuses |
|---|---|---|---|
| GET | `/app/api/users` | list | `user_access`; `User::with('roles')` |
| GET | `/app/api/users/options` | role + client option lists | `Role::pluck('name','id')`, `Client::all()` |
| GET | `/app/api/users/{id}` | detail (show + edit prefill) | `user_show` (edit guarded by `user_edit` server-side via Update FR) |
| POST | `/app/api/users` | create | **`StoreUserRequest`** + `user_create` |
| PUT | `/app/api/users/{id}` | update | **`UpdateUserRequest`** + `user_edit` |
| DELETE | `/app/api/users/{id}` | delete one | `authorize('can-delete')` |
| DELETE | `/app/api/users` | mass delete `{ids:[]}` | **`MassDestroyUserRequest`** (`user_delete`) + `authorize('can-delete')` |

**List row shape** (keys = DataTable `column.key`):
```json
{ "id": 7, "name": "Sara O.", "email": "sara@x.com",
  "email_verified_at": "2026-06-01 09:00:00",
  "roles": [{ "id": 2, "name": "Manager" }] }
```
**Options shape** (foundation `{value,label}`):
```json
{ "roles":   [{ "value": 2, "label": "Manager" }],
  "clients": [{ "value": 5, "label": "King Faisal Lab" }] }
```
> `clients[].label` MUST use `english_name` (the Blade option label).
> `roles` options come from `pluck('name','id')` → `{value:id, label:name}`.

**Detail shape** (drives edit prefill):
```json
{ "data": { "id": 7, "name": "...", "email": "...",
  "role_ids": [2,3], "client_ids": [5,9] } }
```
> `role_ids` = `$user->roles->pluck('id')`; `client_ids` =
> `$user->assigned_client_ids` (preserves the merge of pivot + legacy `client_id`).

**Create/Update payload:** `{ name, email, password?, roles:[ids], clients:[ids] }`.
The api method must replicate the controller exactly: set `client_id = null`,
`User::create/update($data)` (mutator hashes password), `roles()->sync`,
`clients()->sync`. **On update, drop `password` from `$data` if blank.**
Validation errors surface via the foundation `422` mapping straight onto
`FormInput.error` / `FormSelect.error` (`roles`, `clients`, `email`, `password`).

---

## 5. Migration steps (ordered, checkable)

- [ ] backend: add `Api\UsersApiController` (index/options/show/store/update/destroy/massDestroy) reusing `Store/Update/MassDestroyUserRequest` + Gates; register in `routes/app_api.php`.
- [ ] backend: confirm `store/update` keep `client_id=null`, raw-password mutator, and dual `roles/clients` sync; drop blank password on update.
- [ ] frontend: `UsersList.vue` (DataTable + role chips + delete modal + bulk when `canDelete()`).
- [ ] frontend: `UserForm.vue` (FormInput + two multi `FormSelect`s; password optional on edit) using `/options`.
- [ ] frontend: `UserShow.vue`.
- [ ] wire router routes + `meta.perm`; confirm nav entry.
- [ ] parity test vs Blade: same columns, role badges, validation errors (esp. `email unique`, `clients required unless SuperAdmin role id 1`, `password required on create`), permission-hidden actions, RTL.
- [ ] cutover: point the Users nav item at the `/app` route.

---

## 6. Risks / must-not-break

- **Password hashing** happens in the model mutator — never hash in the API and
  never send a blank password on update (would wipe the hash if a rule changed).
- **`client_id = null` on every write** + dual `roles`/`clients` `sync()` — keep
  identical or you break the client-scoping migration.
- **`clients` conditional-required rule** (required unless role id **1** present)
  must come from `StoreUserRequest` — don't reimplement in JS.
- **`email` uniqueness** ignoring the current id on update.
- **Authorization:** keep `user_*` gates on read/write and `can-delete` on
  delete/massDestroy; `MassDestroyUserRequest` keeps its own `user_delete`
  authorize gate (two layers — do not remove either).
- Role/client **ids** (not names) are the payload — FormSelect `value` must be id.

## 7. Out of scope / open questions

- Known **permission-seeder gap**: `user_delete` (and others) may be missing from
  the seeder, and roles use `guard_name = <role name>`. Don't "fix" here; test
  with a user that actually holds the gates.
- Keep the Users index **client-side** (small table) for parity; switch to the
  foundation server-side `{data,meta}` contract only if the user count grows.
- `email_verified_at` is shown raw; SPA should display the same formatted string.
