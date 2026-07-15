# Clients (+ Accounts, Contacts, Client-Locations, Client-Drivers) — Frontend Migration Plan

> Read [`01-foundation.md`](01-foundation.md) first. This plan **conforms to** the
> foundation's `/app/api` JSON layer, `{ data, meta }` envelope, server-side
> `DataTable` contract, `422 → form fields` error mapping, `usePermissions`
> rendering layer, RTL/i18n bridge, and strangler-fig cutover. It does **not**
> repeat those mechanics — only what is specific to the Clients hub.

---

## 1. Module overview

**Clients** is the central reference entity of the platform: a client has many
**locations** and **drivers** (pivots), plus three satellite resources that are
managed on their **own list screens** (not nested under the client): **Client
Accounts** (portal credentials), **Contacts** (generic email/type rows), and
**Client-Drivers** (an explicit driver↔client link table, separate from the
clients↔drivers sync done on the client form). Nav group: **Clients**.

Five distinct CRUD resources are covered here, each with its own controller,
routes, Form Requests, gates, and Blade view set:

| Sub-resource | Controller | Route base | Access gate |
|---|---|---|---|
| Clients | `ClientsController` | `clients` | `client_access` |
| Client Accounts | `ClientAccountsController` | `client-accounts` | `client_account_access` |
| Contacts | `ContactsController` | `contacts` | `contact_access` |
| Client-Locations | `ClientLocationController` | `client-locations` | `client_location_access` |
| Client-Drivers | `ClientDriverController` | `client-drivers` | `client_driver_access` |

> **How the related sub-resources surface (decision):** In Blade today they are
> **independent top-level list screens**, NOT tabs on the client show page
> (`clients/show.blade.php` is a flat attribute table with no relationship tabs).
> To preserve parity we keep them as **separate Vue list views** under
> `views/Clients/`. We MAY *additionally* add read-only tabs on the client show
> page using `TabGroup` (accounts / contacts / locations / drivers for that
> client) as an enhancement — but that needs new scoped API params and is listed
> as out-of-scope/open below so we don't drift from current behavior.

---

## 2. Current implementation (Blade / Velzon)

### 2.1 Routes (`routes/web.php`, prefix `admin`, name prefix `admin.`)

| Method | URI | Name | Controller@action |
|---|---|---|---|
| DELETE | `clients/destroy` | `clients.massDestroy` | `ClientsController@massDestroy` |
| POST | `clients/media` | `clients.storeMedia` | `ClientsController@storeMedia` ⚠️ |
| POST | `clients/ckmedia` | `clients.storeCKEditorImages` | `ClientsController@storeCKEditorImages` ⚠️ |
| (resource) | `clients` | `clients.{index,create,store,show,edit,update,destroy}` | `ClientsController` |
| DELETE | `client-accounts/destroy` | `client-accounts.massDestroy` | `ClientAccountsController@massDestroy` |
| (resource) | `client-accounts` | `client-accounts.*` | `ClientAccountsController` |
| DELETE | `contacts/destroy` | `contacts.massDestroy` | `ContactsController@massDestroy` |
| (resource) | `contacts` | `contacts.*` | `ContactsController` |
| DELETE | `client-locations/destroy` | `client-locations.massDestroy` | `ClientLocationController@massDestroy` |
| (resource) | `client-locations` | `client-locations.*` | `ClientLocationController` |
| DELETE | `client-drivers/destroy` | `client-drivers.massDestroy` | `ClientDriverController@massDestroy` |
| (resource) | `client-drivers` | `client-drivers.*` | `ClientDriverController` |

> ⚠️ **`clients.storeMedia` / `clients.storeCKEditorImages` are dead routes.**
> The methods are **not defined** in `ClientsController` (it has only
> index/create/store/edit/update/show/destroy/massDestroy), and
> `app/Http/Controllers/Admin/Traits/MediaUploadingTrait.php` is **entirely
> commented out**. **No spatie media library, no CKEditor field** is used by any
> Clients-cluster view. The client **logo** is the only upload, handled inline in
> `store()`/`update()` via a plain `$request->file('logo')->move(public_path('clients/logos'), …)` and stored as a string path `/clients/logos/<file>`. → **Do not migrate the CK/media routes; migrate only the logo upload.**

### 2.2 Controller actions & data passed to views

- **Clients** — `index`: if the logged-in user has `client_id` set, only
  `Client::whereIn('id', $user->assigned_client_ids)` else `Client::all()` →
  `clients/index` (collection; **client-side DataTable**). `create`/`edit`: pass
  `drivers` = `Driver::pluck('name','id')` and `locations` =
  `Location::pluck('name','id')` (prepended "please select"). `store`/`update`:
  handle optional `logo` file, `Client::create/update($data)`, then
  `->locations()->sync($request->input('locations', []))` and
  `->drivers()->sync($request->input('drivers', []))`. `show`: flat view.
- **Client Accounts** — `index`: `ClientAccount::with('client')->get()` (client-side).
  `create`/`edit`: `clients = Client::pluck('status','id')` *(note: the option
  LABEL is the client **status**, an existing quirk — preserve it)*. CRUD plain.
- **Contacts** — `index`: `Contact::all()` (client-side). `create`/`edit`: no
  options. Fields: `type`, `email`.
- **Client-Locations** — `index`: `ClientLocation::with('client','location')->get()`.
  `create`/`edit`: `clients = Client::pluck('status','id')` (status-as-label
  quirk again), `locations = Location::pluck('name','id')`.
- **Client-Drivers** — `index`: `ClientDriver::with('driver','client')->get()`.
  `create`/`edit`: `drivers = Driver::pluck('name','id')`, `clients =
  Client::pluck('english_name','id')`. Note: the create form posts `drivers[]`
  (multi) + single `client_id`, but `StoreClientDriverRequest` validates single
  `driver_id`+`client_id` and `store()` does a plain `create($request->all())` —
  **a pre-existing mismatch; replicate the request's contract (single driver_id),
  do not "fix" it here.**

### 2.3 Blade views → purpose

| View | Purpose |
|---|---|
| `clients/index.blade.php` | client-side DataTable (`datatable-Client`), columns below |
| `clients/create.blade.php` | create form (logo file, status select, drivers[]/locations[] multi-select2 with Select-All/Deselect-All) |
| `clients/edit.blade.php` | same as create, pre-filled |
| `clients/show.blade.php` | flat attribute table incl. logo image |
| `clientAccounts/{index,create,edit,show}.blade.php` | CRUD |
| `contacts/{index,create,edit,show}.blade.php` | CRUD |
| `clientLocations/{index,create,edit,show}.blade.php` | CRUD |
| `clientDrivers/{index,create,edit,show}.blade.php` | CRUD |

**Clients list columns** (client-side): `id`, `status` (badge Enabled/Disabled),
`arabic_name`, `english_name`, `email`, `address`, `logo` (thumbnail), actions.

### 2.4 Permissions / Gates (verified in controllers & Form Requests)

| Action | Clients | Accounts | Contacts | Client-Loc | Client-Drv |
|---|---|---|---|---|---|
| access | `client_access` | `client_account_access` | `contact_access` | `client_location_access` | `client_driver_access` |
| create | `client_create` | `client_account_create` | `contact_create` | `client_location_create` | `client_driver_create` |
| edit | `client_edit` | `client_account_edit` | `contact_edit` | `client_location_edit` | `client_driver_edit` |
| show | `client_show` | `client_account_show` | `contact_show` | `client_location_show` | `client_driver_show` |
| delete | `can-delete` (global) | `can-delete` | `can-delete` | `can-delete` | `can-delete` |

All `destroy`/`massDestroy` use `$this->authorize('can-delete')`. (Per foundation:
`client_audit_access` etc. are not used by this cluster.)

### 2.5 Form Request rules (authoritative — reuse verbatim)

- **StoreClientRequest** / UpdateClientRequest: `status` required; `arabic_name`,
  `english_name`, `email`, `address` nullable string; `drivers` required array;
  `locations` required array. *(No rule for `logo` — it is optional in store, and
  on create the Blade marks the file input `required` but the rule does not; the
  HTML5 `required` is the only enforcement. Keep logo optional server-side.)*
- **StoreClientAccountRequest**: `username`, `password`, `name` nullable string.
  *(`status`/`client_id` are posted but unvalidated → mass-assigned; preserve.)*
- **StoreContactRequest**: `type`, `email` nullable string.
- **StoreClientLocationRequest**: `is_linked` required integer (int32 range).
  *(But the create form posts `client_id`+`location_id`, NOT `is_linked` — a
  pre-existing gap; `create($request->all())` still saves client/location.
  Replicate exactly: send client_id+location_id; the rule will only check
  is_linked if present. **Do not change.**)*
- **StoreClientDriverRequest**: `driver_id` required integer, `client_id`
  required integer.
- All `MassDestroy*Request`: `ids` array (standard).

### 2.6 Special behaviors to PRESERVE

- Clients list is **client-side** DataTable (`Client::all()` collection). It can
  stay client-side in Vue (small dataset) OR move to server-side; **default to
  server-side** for consistency with the foundation contract, but it is the one
  list here that could remain client-side if dataset is tiny.
- The `assigned_client_ids` scoping on the clients index (portal users see only
  their clients) **must be reproduced in the API list query.**
- Logo upload (`move()` to `public/clients/logos`, string path) and the
  `sync(locations)` + `sync(drivers)` pivots on store/update.
- Multi-select **Select-All / Deselect-All** UX (drivers, locations).
- Bulk-delete via massDestroy (DataTable selection → `{ ids:[] }`).

---

## 3. Target design (Vue + Tailwind)

All views live under `resources/js/vue/views/Clients/`.

### Blade → Vue mapping

| Blade | Vue view | vue-build components |
|---|---|---|
| `clients/index` | `Clients/ClientsList.vue` | `Breadcrumb`, `DataTable`, `StatusBadge`, `BaseButton`, `BaseModal` (delete) |
| `clients/create` + `edit` | `Clients/ClientForm.vue` | `FormInput`, `FormSelect` (status single; drivers+locations `multiple`), **logo uploader (GAP — see §3.1)**, `BaseButton`, `BaseCard` |
| `clients/show` | `Clients/ClientShow.vue` | `BaseCard`, `BaseAvatar`/`<img>` for logo, `StatusBadge` |
| `clientAccounts/*` | `Clients/AccountsList.vue` + `Clients/AccountForm.vue` + `Clients/AccountShow.vue` | `DataTable`, `FormInput`, `FormSelect` (client), `BaseModal` |
| `contacts/*` | `Clients/ContactsList.vue` + `Clients/ContactForm.vue` + `Clients/ContactShow.vue` | `DataTable`, `FormInput`, `BaseModal` |
| `clientLocations/*` | `Clients/ClientLocationsList.vue` + `Clients/ClientLocationForm.vue` + `Clients/ClientLocationShow.vue` | `DataTable`, `FormSelect` (client, location), `BaseModal` |
| `clientDrivers/*` | `Clients/ClientDriversList.vue` + `Clients/ClientDriverForm.vue` + `Clients/ClientDriverShow.vue` | `DataTable`, `FormSelect` (driver, client), `BaseModal` |

Follow `views/Tasks/TasksList.vue` as the list/CRUD reference: `Breadcrumb` +
header create button (gated by `can(...)`) + `DataTable` with `#cell-status` →
`StatusBadge`, `#row-actions` (view/edit gated, delete via `canDelete()` +
`BaseModal` confirm), and `:bulk-actions` for massDestroy.

`FormSelect` (verified) supports `multiple` with built-in **Select all / Clear**
— this directly replaces the Blade select2 + Select-All/Deselect-All buttons for
clients' `drivers`/`locations`. Status selects use single-mode `FormSelect`.

All client text fields are single-line `<input type="text">` → `FormInput`
covers them (no `FormTextarea` needed for this cluster).

### 3.1 Component GAP — logo file upload

vue-build has **no file-upload component** (`FormInput` cannot do `type=file`
uploads with multipart). **Minimal proposed addition (do not build now):** a
small `FormImageUpload.vue` (file picker + preview + clear) that emits a `File`;
`ClientForm.vue` submits the create/update as `multipart/form-data` so the API
controller's `$request->file('logo')` path is unchanged. Until that exists, the
client form can ship without logo edit (logo optional server-side) and fall back
to the Blade screen for logo changes — call this out at cutover.

### Vue Router routes (mirror `/admin/*` per foundation)

`/admin/clients`, `/admin/clients/create`, `/admin/clients/:id/edit`,
`/admin/clients/:id`; and the analogous paths for `client-accounts`, `contacts`,
`client-locations`, `client-drivers`. Each route `meta.perm = '<access gate>'`.

### nav.config.js

Confirm/add the **Clients** group entries (Clients, Client Accounts, Contacts,
Client Locations, Client Drivers) with the matching `*_access` perm keys.

### States

Empty (`EmptyState`), loading (DataTable `:loading`), error (toast via
`useToast`) per foundation. RTL: arabic_name/email/IDs forced LTR where intrinsic.

---

## 4. Data / API contract

Under `/app/api` (foundation envelope `{ data, meta }`; `422` → field errors).

### Clients

| Method | Path | Purpose | Reuses |
|---|---|---|---|
| GET | `/app/api/clients` | list (q/sort/page/pageSize) **+ apply `assigned_client_ids` scope** | index query + `client_access` |
| GET | `/app/api/clients/options` | `{ drivers:[{value,label}], locations:[{value,label}] }` | `Driver::pluck`, `Location::pluck` |
| GET | `/app/api/clients/{id}` | show detail (incl. synced `drivers[]`, `locations[]` ids, logo url) | `client_show` |
| POST | `/app/api/clients` | create (multipart: fields + `logo` + `drivers[]` + `locations[]`); runs logo `move()` + both `sync()` | **StoreClientRequest** + `client_create` |
| POST | `/app/api/clients/{id}` (`_method=PUT`) | update (multipart) | **UpdateClientRequest** + `client_edit` |
| DELETE | `/app/api/clients/{id}` | delete | `can-delete` |
| DELETE | `/app/api/clients` | mass delete `{ids:[]}` | **MassDestroyClientRequest** + `can-delete` |

List row shape (keys = DataTable column keys): `{ id, status, status_label,
arabic_name, english_name, email, address, logo }` (logo = absolute asset URL;
`status_label` = Enabled/Disabled for the badge; keep raw `status` for edit).

> Multipart note: because PUT can't carry files cleanly, update uses
> `POST … ?_method=PUT` (Laravel method spoofing) so the **same UpdateClientRequest**
> validates and the controller's `$request->file('logo')` logic is reused.

### Client Accounts

| Method | Path | Reuses |
|---|---|---|
| GET | `/app/api/client-accounts` | list (with client name) + `client_account_access` |
| GET | `/app/api/client-accounts/options` | `{ clients:[{value,label}] }` *(preserve the status-as-label quirk → label = client status)* |
| GET | `/app/api/client-accounts/{id}` | `client_account_show` |
| POST | `/app/api/client-accounts` | **StoreClientAccountRequest** + `client_account_create` |
| PUT | `/app/api/client-accounts/{id}` | **UpdateClientAccountRequest** + `client_account_edit` |
| DELETE | `/app/api/client-accounts/{id}` / (bulk) | `can-delete` (+ MassDestroy req) |

Row: `{ id, client, client_id, username, name, status, status_label }`.

### Contacts

`GET/POST/PUT/DELETE /app/api/contacts` (+ bulk). Reuse **StoreContactRequest /
UpdateContactRequest / MassDestroyContactRequest**, gates `contact_*` /
`can-delete`. Row: `{ id, type, email }`. No options endpoint needed.

### Client-Locations

`/app/api/client-locations` (+ options `{ clients, locations }`, same client
status-as-label quirk for clients; locations label = name). Reuse
**Store/Update/MassDestroyClientLocationRequest**, gates `client_location_*`.
Row: `{ id, client, location, client_id, location_id, is_linked }`. Send
`client_id`+`location_id` on create (matches current behavior).

### Client-Drivers

`/app/api/client-drivers` (+ options `{ drivers (label=name), clients
(label=english_name) }`). Reuse **Store/Update/MassDestroyClientDriverRequest**,
gates `client_driver_*`. Row: `{ id, driver, client, driver_id, client_id }`.
Form posts single `driver_id` + `client_id` (per the Form Request contract).

### Validation surfacing

Type-hint the existing Form Request in each api method → automatic `422 { message,
errors }`; Vue maps `errors.<field>[0]` onto each `FormInput`/`FormSelect`
`:error` prop (foundation §2). E.g. `errors.drivers`, `errors.locations`,
`errors.status` on the client form; `errors.driver_id`/`errors.client_id` on
client-driver.

---

## 5. Migration steps (ordered, checkable)

- [ ] **Backend — Clients:** `Api\ClientsApiController` (list w/ `assigned_client_ids`
      scope, options, show, store/update reusing logo `move()` + `sync()` +
      **Store/UpdateClientRequest**, destroy/massDestroy w/ `can-delete`); add
      routes to `routes/app_api.php`. **Do NOT touch** `storeMedia`/`ckmedia`.
- [ ] **Backend — Accounts/Contacts/Client-Locations/Client-Drivers:** one
      `Api\*ApiController` each, delegating to existing queries + Form Requests +
      gates; preserve the status-as-label option quirks and the
      client_id/location_id create behavior.
- [ ] **Frontend — Clients:** `ClientsList.vue`, `ClientForm.vue`, `ClientShow.vue`
      from vue-build (TasksList pattern). Use `FormSelect multiple` for
      drivers/locations.
- [ ] **Frontend — gap:** add `FormImageUpload.vue` (logo) or defer logo edit.
- [ ] **Frontend — sub-resources:** List/Form/Show trio for each of the four
      satellites.
- [ ] **Wire** router routes + `nav.config.js` perms for all five resources.
- [ ] **Parity test** each screen vs Blade: columns, validation 422 mapping,
      pivots actually synced, logo saved to `/clients/logos`, bulk delete, perms
      hide the same buttons, RTL.
- [ ] **Cutover** nav items `/admin/*` → `/app/...` per resource (independent).
- [ ] Update status in `00-README.md`.

---

## 6. Risks / must-not-break

- **Logo upload path & format** — must keep writing to
  `public/clients/logos/<time>_<sanitized>` and storing the `/clients/logos/...`
  string; on update, **omit `logo` from `$data` when no new file** (Blade does
  `unset($data['logo'])` to avoid wiping the existing logo). Multipart-PUT
  spoofing required.
- **Pivot sync** — `locations()->sync()` and `drivers()->sync()` must run on both
  store and update with the posted id arrays (empty array clears them).
- **`assigned_client_ids` scoping** on the clients list (portal-user isolation).
- **Status-as-label quirks** (Accounts & Client-Locations client options) and the
  **Client-Driver / Client-Location request-vs-form field mismatches** — replicate
  exactly; do not "correct" them in this migration.
- **`can-delete`** governs all deletes; never rely on client-side gating.
- **Dead media/CKEditor routes** — leave untouched; do not expose them in the SPA.

---

## 7. Out of scope / open questions

- **Related-resource tabs on the client show page** (TabGroup of this client's
  accounts/contacts/locations/drivers): a nice enhancement but requires new
  client-scoped API filters and diverges from current Blade (flat show). Defer.
- **`FormImageUpload.vue`** is a proposed new component — design only; build under
  the foundation component work, not in this plan.
- Should the small **Clients list stay client-side** (parity with `Client::all()`)
  or adopt server-side? Recommend server-side; confirm dataset size.
- Confirm `nav.config.js` already contains all five Clients-group entries and the
  exact perm keys (`client_access`, `client_account_access`, `contact_access`,
  `client_location_access`, `client_driver_access`).
