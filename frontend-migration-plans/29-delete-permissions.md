# Delete Permissions — Frontend Migration Plan

> Read [`01-foundation.md`](01-foundation.md) first. Conforms to & references the
> shared `/app/api` JSON layer, Form-Request/Gate reuse, strangler-fig cutover,
> the `422` form-error contract, and RTL/i18n bridge — not repeated.
> **Presentation only.** This screen administers the global **`can-delete`** gate
> referenced by every module's delete; its authorization model is special and
> **MUST NOT be weakened** (see §6).

---

## 1. Module overview

"Delete Permissions" is the **admin screen that controls the global `can-delete`
gate** — i.e. which user ids are allowed to see/perform delete actions across the
whole application. The foundation calls this out: every module's
`destroy`/`massDestroy` does `authorize('can-delete')`, and the allow-list is
administered **here**.

Nav group **Users** (`nav.config.js`:
`{ label:'Delete Permissions', route:'/admin/delete-permissions', perm:'superadmin' }`).

> ⚠️ **Important authorization note:** unlike every other module, this screen is
> **NOT guarded by a Spatie gate**. `nav.config.js` labels it `perm:'superadmin'`,
> but the real backend rule is **`auth()->id() === 1`** (the owner) enforced in
> `DeletePermissionsService::canManage()`. The nav `perm` is for **rendering
> only**; the SPA must additionally gate this item on "is user id 1" and the
> backend stays the sole authority. See §6/§7.

---

## 2. Current implementation (Blade / Velzon)

### Routes (`routes/web.php` ~lines 227–230) — **NOT a resource**: index + store + destroy only
| Method | URI | Name | Action |
|---|---|---|---|
| GET | `/admin/delete-permissions` | `admin.delete-permissions.index` | `index` |
| POST | `/admin/delete-permissions` | `admin.delete-permissions.store` | `store` |
| DELETE | `/admin/delete-permissions/{userId}` | `admin.delete-permissions.destroy` | `destroy` |

### Controller (`DeletePermissionsController.php`) + service (`app/Services/DeletePermissionsService.php`)
- Constructor-injects `DeletePermissionsService`.
- **Every action begins with** `if (!$service->canManage()) abort(403)` where
  `canManage()` = `auth()->check() && auth()->id() === 1`.
- `index` — `getAllowedUserIds()`; loads `User::withTrashed()->whereIn(ids)` →
  `$allowedUsers` keyed by id (shows soft-deleted users with a "Deleted" badge);
  `$usersForSelect = User::orderBy('name')->get()` (for the Add dropdown).
- `store(Request)` — `validate(['user_id' => 'required|integer|exists:users,id'])`;
  `$service->addUser((int)$request->user_id)`; redirect + flash `message`.
- `destroy(int $userId)` — **if `$userId === 1` → redirect with `error`** ("User
  ID 1 cannot be removed"); else `$service->removeUser($userId)` + flash.

### Service storage model (PRESERVE behavior)
- Allow-list persisted to **`storage/app/delete_permissions.json`**
  (`{ "allowed_user_ids": [int,...] }`); seeded from
  `config('delete_permissions.allowed_user_ids', [1])` on first read.
- `addUser` is idempotent + sorts; `removeUser` filters out the id; **user id 1
  is always present / never removable**.
- This is **file-based, not DB** — no Eloquent model, no `can-delete` gate check
  here (the gate is *defined elsewhere* against this allow-list; this screen only
  edits the list).

### Blade view (`admin/delete-permissions/index.blade.php`)
- Two cards. Left: **list** of allowed users (`name (email)`, "Deleted" badge for
  trashed, **"Owner" badge + no Remove button for id 1**, Remove form for others).
  Right: **Add User** — a `<select>` of users **not already** in the list +
  "Add User" button; helper text when all users are already added.
- Session flash `message` (success) / `error` (danger) alerts.

### Special behaviors to PRESERVE
- **`auth()->id() === 1` gate on all three actions** (server-side).
- **id 1 immutable**: shown as Owner, never removable; destroy of id 1 is a no-op
  with an error flash.
- Soft-deleted users still listed (`withTrashed`) with a Deleted badge.
- Add dropdown excludes already-allowed users.
- No create/edit/show/resource verbs — only index + store + destroy.

---

## 3. Target design (Vue + Tailwind)

Directory `resources/js/vue/views/DeletePermissions/` — a **single screen**.

| Blade view | → Vue view | vue-build components |
|---|---|---|
| `index` | `DeletePermissions.vue` | `Breadcrumb`, two `BaseCard`s, list rows with `BaseAvatar`/`StatusBadge` ("Deleted"/"Owner"), `FormSelect` (single, searchable; users not yet allowed), `BaseButton` (Add / Remove), `BaseModal` (remove confirm), `EmptyState`, `ToastHost` |

- **Left card — allowed users:** list from `GET …/allowed`; each row shows
  name + email; `StatusBadge` "Deleted" if `trashed`; for id 1 show "Owner" badge
  and **render no Remove control**; others get a Remove button → `BaseModal`
  confirm → `DELETE`.
- **Right card — add user:** `FormSelect` (single) of `availableUsers`
  (`{value:id,label:"name (email)"}`); "Add User" → `POST`. Show helper
  "All users are already in the list" when the option list is empty.
- **No DataTable/forms beyond this** — it is not CRUD-resource shaped.
- Router route: `/admin/delete-permissions`, `meta.perm` — see §6 (gate on user
  id 1, not a Spatie perm).
- nav entry exists; the SPA should additionally hide it unless the booted user is
  id 1 (the nav `perm:'superadmin'` won't reflect the real rule).
- States: loading skeleton in cards, `EmptyState` ("No users configured"),
  toast/flash for add/remove success + the id-1 error.

---

## 4. Data / API contract

Base `/app/api/delete-permissions` (new `Api\DeletePermissionsApiController`
delegating to the **same `DeletePermissionsService`**; **no web controller
change**). **Every endpoint re-runs `$service->canManage()` → 403** (the
`id===1` rule is the authority).

| Method | Path | Purpose | Reuses |
|---|---|---|---|
| GET | `/app/api/delete-permissions` | allowed users + available users | `canManage()`; `getAllowedUserIds()`, `User::withTrashed()…`, `User::orderBy('name')` |
| POST | `/app/api/delete-permissions` | add a user `{user_id}` | `canManage()`; `validate(user_id required\|integer\|exists:users,id)`; `service->addUser` |
| DELETE | `/app/api/delete-permissions/{userId}` | remove a user | `canManage()`; **id-1 guard** + `service->removeUser` |

> No `Store*Request` class exists for this module — the controller validates
> inline. The api reuses that **same inline rule** (`user_id` required|integer|
> exists:users,id). Do not invent a Form Request that changes behavior.

**GET response:**
```json
{ "data": {
    "allowed": [
      { "id": 1, "name": "Owner", "email": "owner@x.com", "trashed": false, "owner": true },
      { "id": 9, "name": "Sara", "email": "sara@x.com", "trashed": true,  "owner": false }
    ],
    "available": [ { "value": 12, "label": "Khalid (khalid@x.com)" } ]
} }
```
> `allowed[].owner` = `(id === 1)` so the UI hides Remove + shows the Owner badge.
> `available` already excludes allowed ids (mirrors the Blade dropdown).

**POST** `{ "user_id": 12 }` → `200 { data: {…refreshed lists…} }`; validation →
foundation `422` (`user_id`).
**DELETE `/{userId}`** → if `userId === 1`, return a **non-destructive** response
mirroring Blade's error flash (e.g. `409`/`422` with
`{ message: "User ID 1 cannot be removed from delete permissions." }`); else
`200`/`204` and the SPA refreshes. **Server still no-ops id 1 regardless of
client.**

---

## 5. Migration steps (ordered, checkable)

- [ ] backend: `Api\DeletePermissionsApiController` (index/store/destroy) delegating to `DeletePermissionsService`; routes in `routes/app_api.php`. **Re-check `canManage()` (id===1) in every method.**
- [ ] backend: keep inline `user_id` validation; keep id-1 immutable no-op on destroy; return id/email/trashed/owner + available list.
- [ ] frontend: `DeletePermissions.vue` (two cards, allowed list w/ Owner+Deleted badges, add `FormSelect`, remove `BaseModal`).
- [ ] frontend: hide the nav item + route unless booted user id === 1 (rendering only).
- [ ] wire router; confirm nav entry (note the perm-key mismatch in §7).
- [ ] parity test vs Blade: id-1 Owner/non-removable, Deleted badge for trashed, add-dropdown excludes existing, success/error flashes, 403 for non-owner, RTL.
- [ ] cutover: point the Delete Permissions nav item at `/app` route.

---

## 6. Risks / must-not-break

- **DO NOT weaken authorization.** The only authority is
  `DeletePermissionsService::canManage()` → **`auth()->id() === 1`**, re-checked
  in **every** api method (the nav `perm:'superadmin'` is cosmetic and does NOT
  enforce anything). A client check is never sufficient.
- **User id 1 is immutable** — never removable; destroy of id 1 must stay a
  no-op with an error response, both server- and client-side.
- This screen edits the **global `can-delete` allow-list** that gates **every
  module's delete**. A regression here silently enables/disables deletes app-wide
  — treat changes as high-risk and parity-test deletes in another module after.
- **File-backed storage** (`storage/app/delete_permissions.json`), not DB — the
  api must go through the **same service** so the same file is read/written;
  don't reimplement the store.
- `withTrashed()` listing must be preserved (soft-deleted allowed users stay
  visible with a badge).

## 7. Out of scope / open questions

- **nav.config.js perm mismatch (flag):** the entry uses `perm:'superadmin'`, but
  there is **no `superadmin` Spatie gate** for this screen — the backend uses
  `id===1`. Decide whether to (a) leave `'superadmin'` as a label and have the
  SPA additionally check `boot.user.id === 1`, or (b) expose an
  `isOwner`/`canManageDeletePermissions` flag in the boot payload and gate on
  that. **Recommended: (b)** — make the boot payload carry an explicit
  `canManageDeletePermissions` boolean (from `canManage()`) so nav + route guard
  match the server exactly. Document the final choice in `01-foundation.md`.
- No `Store/Update/MassDestroy` Form Requests exist for this module (inline
  validation only) — intentional; don't add one.
- `config('delete_permissions.allowed_user_ids')` seeding default `[1]` is the
  bootstrap source; unchanged.
