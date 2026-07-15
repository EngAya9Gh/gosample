# Terms — Frontend Migration Plan

> Read [`01-foundation.md`](01-foundation.md) first. This plan only describes the
> Terms presentation layer; the JSON API layer, `{data,meta}` envelope, 422→form
> mapping, `DataTable` server-side contract, permission seeding and RTL/i18n
> bridge are all defined there and are **not repeated** here. Terms is the
> recommended **pilot CRUD module** (foundation §10) — it is the simplest screen
> in the whole panel.

---

## 1. Module overview

Terms is a tiny bilingual reference table (English text + Arabic text) used for
storing reusable term/clause strings. It is a textbook `Route::resource` CRUD
module under the `Settings` nav group. Gate: `term_access`. It carries no side
effects, no exports, no relations — ideal to prove the strangler-fig pattern end
to end.

---

## 2. Current implementation (Blade / Velzon)

### Routes (`routes/web.php` ~159–161, inside `prefix admin`, `as admin.`)
| Method | URI | Name | Controller@action |
|---|---|---|---|
| DELETE | `/admin/terms/destroy` | `admin.terms.massDestroy` | `TermsController@massDestroy` |
| GET | `/admin/terms` | `admin.terms.index` | `TermsController@index` |
| GET | `/admin/terms/create` | `admin.terms.create` | `TermsController@create` |
| POST | `/admin/terms` | `admin.terms.store` | `TermsController@store` |
| GET | `/admin/terms/{term}` | `admin.terms.show` | `TermsController@show` |
| GET | `/admin/terms/{term}/edit` | `admin.terms.edit` | `TermsController@edit` |
| PUT/PATCH | `/admin/terms/{term}` | `admin.terms.update` | `TermsController@update` |
| DELETE | `/admin/terms/{term}` | `admin.terms.destroy` | `TermsController@destroy` |

### Controller actions → view data
- `index` — `Term::all()` → `admin.terms.index` (**client-side** DataTable, all rows rendered).
- `create` — empty form → `admin.terms.create`.
- `store(StoreTermRequest)` — `Term::create($request->all())` → redirect index.
- `edit(Term)` → `admin.terms.edit`.
- `update(UpdateTermRequest, Term)` — `$term->update($request->all())` → redirect index.
- `show(Term)` → `admin.terms.show`.
- `destroy(Term)` — `authorize('can-delete')` then delete → `back()`.
- `massDestroy(MassDestroyTermRequest)` — `authorize('can-delete')`, `Term::whereIn('id', ids)->delete()` → 204.

### Blade views
| File | Purpose |
|---|---|
| `resources/views/admin/terms/index.blade.php` | List table (`datatable-Term`, client-side, order `[1,'desc']`, pageLength 100, bulk-delete button gated by `can-delete`) |
| `resources/views/admin/terms/create.blade.php` | Two-textarea form: `english_text` (LTR) + `arabic_text` (`dir="rtl"`), each with a helper line |
| `resources/views/admin/terms/edit.blade.php` | Same form, prefilled |
| `resources/views/admin/terms/show.blade.php` | Read-only detail |

### Gates
`term_access` (index), `term_create` (create/store), `term_edit` (edit/update),
`term_show` (show), `can-delete` (destroy/massDestroy — note `destroy` uses the
global gate, **not** `term_delete`). `MassDestroyTermRequest::authorize()` checks
`term_delete` *additionally* before the controller's `can-delete`.

### Form Requests + rules
- `StoreTermRequest` — `authorize: term_create`, `rules(): []` (**no field rules** — fields are nullable in DB).
- `UpdateTermRequest` — `authorize: term_edit`, `rules(): []`.
- `MassDestroyTermRequest` — `authorize: term_delete`, rules `ids required|array`, `ids.* exists:terms,id`.

### Special behaviors to PRESERVE
None. No exports, no AJAX, no Select2/flatpickr, no maps/charts. Pure CRUD.
Columns are: `id`, `english_text`, `arabic_text`.

---

## 3. Target design (Vue + Tailwind)

### View mapping (under `resources/js/vue/views/Terms/`)
| Blade view | Vue view | vue-build components |
|---|---|---|
| `terms/index` | `TermsList.vue` | `Breadcrumb`, `DataTable`, `BaseButton`, `BaseModal` (delete confirm), `EmptyState`, `ToastHost` |
| `terms/create` + `terms/edit` | `TermForm.vue` (one form, create/edit by route param) | `Breadcrumb`, `BaseCard`, `FormInput` (textarea variant via `as="textarea"` — or two `FormInput`s), `BaseButton` |
| `terms/show` | `TermShow.vue` (or a read-only `BaseModal`/drawer from the list) | `Breadcrumb`, `BaseCard` |

Follow `TasksList.vue` exactly for the list (FilterBar optional — Terms has no
filters, so a keyword search box on `DataTable` is enough). Mirror the delete
confirm modal pattern. The `arabic_text` field must render with `dir="rtl"`; the
`english_text` field with `dir="ltr"`.

### Vue Router routes
- `/admin/terms` → `TermsList.vue` `meta: { perm: 'term_access' }`
- `/admin/terms/create` → `TermForm.vue` `meta: { perm: 'term_create' }`
- `/admin/terms/:id/edit` → `TermForm.vue` `meta: { perm: 'term_edit' }`
- `/admin/terms/:id` → `TermShow.vue` `meta: { perm: 'term_show' }`

### nav.config.js
Confirm a `Settings → Terms` entry pointing at `/admin/terms` with perm key
`term_access` (it exists in the Blade sidebar). No new nav item — just cutover.

### States
Empty list → `EmptyState` ("No terms yet" + Add button if `term_create`).
Loading → `DataTable :loading`. Save error → field errors from 422.

---

## 4. Data / API contract

Base: `/app/api/terms` (reuses foundation §2 standard CRUD shape).

| Method | Path | Purpose | Reuses |
|---|---|---|---|
| GET | `/app/api/terms` | list (server-side `q`/`sortKey`/`sortDir`/`page`/`pageSize`) | `Term::query()` + Gate `term_access` |
| GET | `/app/api/terms/{id}` | detail | Gate `term_show` |
| POST | `/app/api/terms` | create | **`StoreTermRequest`** |
| PUT | `/app/api/terms/{id}` | update | **`UpdateTermRequest`** |
| DELETE | `/app/api/terms/{id}` | delete one | `authorize('can-delete')` |
| DELETE | `/app/api/terms` | mass delete `{ids:[]}` | **`MassDestroyTermRequest`** |

Row shape (`data[i]`): `{ id, english_text, arabic_text }`.
Detail: `{ data: { id, english_text, arabic_text, created_at } }`.
No `/options` endpoint (no selects).
Validation: both Store/Update return `[]` rules → effectively always passes; the
API still type-hints them so the Gate (`authorize()`) runs identically.

> Note: the Blade list is client-side (`Term::all()`). The SPA should use the
> server-side `DataTable` mode for consistency even though the table is small —
> no logic change, just pagination moves to the API.

---

## 5. Migration steps (ordered, checkable)

- [ ] backend: `Api\TermsApiController` (index/show/store/update/destroy/massDestroy) in `routes/app_api.php`, reusing `StoreTermRequest`/`UpdateTermRequest`/`MassDestroyTermRequest` + gates + `can-delete`.
- [ ] frontend: build `TermsList.vue`, `TermForm.vue`, `TermShow.vue` from vue-build components.
- [ ] wire router routes (4) + perm guards.
- [ ] confirm nav `Settings → Terms` perm `term_access`.
- [ ] parity test vs `/admin/terms`: same columns, RTL arabic_text, bulk delete gated by `can-delete`, helper text present.
- [ ] cutover: flip nav `Terms` → `/app/admin/terms`.
- [ ] mark status in `00-README.md`.

---

## 6. Risks / must-not-break

- **`destroy` uses `can-delete`, not `term_delete`** — the delete button must be gated by `canDelete()` in the SPA, matching the Blade `@can('can-delete')`.
- **`massDestroy` requires BOTH `term_delete` (request authorize) AND `can-delete` (controller)** — keep both checks; do not collapse to one.
- Empty validation rules are intentional (DB columns nullable). Do not invent client-side required rules that the backend would not enforce.
- RTL: `arabic_text` must mirror; do not lose the per-field `dir`.

---

## 7. Out of scope / open questions

- The two helper strings (`*_helper`) come from `trans('translation.term.fields.*')`; ensure they are in the i18n bridge.
- No exports/side effects → nothing else to flag. Good pilot.
