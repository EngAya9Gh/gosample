# Profile / Change Password / Login — Frontend Migration Plan

> Read [`01-foundation.md`](01-foundation.md) first. These screens live **outside**
> the `/admin` resource group: profile is under `/profile` (`Auth\ChangePasswordController`),
> and login is plain Laravel `Auth::routes(['register' => false])`. Auth/password
> change touch credentials and session — the migration is **presentation-only**
> and must not alter the auth flow. See §3 for the **keep-Blade vs migrate**
> decision (recommendation: **keep login + password-reset in Blade; migrate the
> in-app Profile/Change-Password screen into the SPA**).

---

## 1. Module overview

Two concerns:
1. **Login & password reset** — pre-auth pages (`/login`, forgot/reset/confirm),
   registered by `Auth::routes(['register' => false])`. Served *before* the SPA
   exists for the session.
2. **Profile / Change Password** — post-auth, in-app settings under `/profile`:
   update name+email, change password, and (soft-)delete own account. This is the
   natural SPA "Account Settings" screen.

---

## 2. Current implementation (Blade / Velzon)

### Routes
**Auth (`routes/web.php` line 43):** `Auth::routes(['register' => false]);` → registers (among others):
| Method | URI | Name |
|---|---|---|
| GET | `/login` | `login` |
| POST | `/login` | `login` (`LoginController`) |
| POST | `/logout` | `logout` |
| GET | `/password/reset` | `password.request` |
| POST | `/password/email` | `password.email` |
| GET | `/password/reset/{token}` | `password.reset` |
| POST | `/password/reset` | `password.update` |
| GET | `/password/confirm` | `password.confirm` |

- `LoginController` = stock `AuthenticatesUsers` trait; `guest` middleware except `logout`; redirects to `RouteServiceProvider::HOME` after login.

**Profile group (`routes/web.php` ~233–241):** `prefix profile`, `as profile.`, `namespace Auth`, `middleware auth`. Wrapped in a `file_exists(ChangePasswordController)` guard.
| Method | URI | Name | Action |
|---|---|---|---|
| GET | `/profile/password` | `profile.password.edit` | `ChangePasswordController@edit` |
| POST | `/profile/password` | `profile.password.update` | `ChangePasswordController@update` |
| POST | `/profile/profile` | `profile.password.updateProfile` | `ChangePasswordController@updateProfile` |
| POST | `/profile/profile/destroy` | `profile.password.destroyProfile` | `ChangePasswordController@destroy` |

### Controller actions → data
- `edit()` — gate `profile_password_edit` → **`view('auth.passwords.edit')`** (single combined profile + password screen).
- `update(UpdatePasswordRequest)` — `auth()->user()->update($request->validated())` (sets new password) → redirect `profile.password.edit` with `translation.change_password_success`.
- `updateProfile(UpdateProfileRequest)` — `auth()->user()->update(validated())` (name+email) → redirect with `translation.update_profile_success`.
- `destroy()` — `authorize('can-delete')`; anonymizes email (`time()_email`), soft-deletes own user → redirect `login` with `translation.delete_account_success`.

### Blade views
| File | Purpose | Status |
|---|---|---|
| `auth/login.blade.php` | Login form: `email` (labeled username), `password` (show/hide), Remember me; posts `route('login')` | exists |
| `auth/passwords/{email,reset,confirm}.blade.php` | Forgot / reset / confirm password | exist |
| `auth/passwords/edit.blade.php` | **Profile + change-password screen** — **⚠️ MISSING** (`edit()` returns this view but the file does not exist; see §7) | **MISSING** |
| `auth/register.blade.php`, `auth/verify.blade.php` | present but register is disabled | vestigial |

### Gates
- `profile_password_edit` — guards `edit`, `update`, `updateProfile` (controller + both Form Requests `authorize()`).
- `can-delete` — guards `destroy` (self-account deletion).

### Form Requests + rules
- `UpdatePasswordRequest` (`authorize: profile_password_edit`): `password required|string|min:8|confirmed`.
- `UpdateProfileRequest` (`authorize: profile_password_edit`): `name required|string|max:255`, `email required|email|max:255|unique:users,email,{auth id}`.
- `destroy` has no Form Request (uses `authorize('can-delete')`).

### Special behaviors to PRESERVE
- **Auth flow**: session login via `AuthenticatesUsers`, CSRF, Remember me, throttling, `guest` middleware, redirect-to-HOME. **Do not touch.**
- **Self-delete anonymizes email** before soft-delete (prevents unique-email collision on re-create). Must run server-side identically.
- Success flash messages use `translation.*` keys.
- `email` field is presented as "username" in the login UI (label only).

---

## 3. Target design (Vue + Tailwind) — migrate vs keep-Blade

### Decision
- **KEEP in Blade:** `/login` and `/password/*` (reset/forgot/confirm). They are
  **pre-authentication** — the SPA (and its `auth`-gated `/app/api`) isn't usable
  until the session exists. Re-skinning the login page is a low-value, higher-risk
  change to the credential flow. Recommendation: **leave the Velzon login/reset
  pages as-is.** (Optional later: a standalone Vue login is possible but is a
  separate, security-reviewed effort — out of scope here.)
- **MIGRATE into the SPA:** the in-app **Profile / Change Password** screen
  (`/profile/password`) → a `views/Profile/AccountSettings.vue` reached from the
  AppShell user menu. This is post-auth, lives naturally in the SPA, and is the
  one screen here that benefits from the new UI.

### View mapping (under `resources/js/vue/views/Profile/`)
| Blade screen | Vue view | vue-build components |
|---|---|---|
| `auth/passwords/edit` (profile + password) | `AccountSettings.vue` | `Breadcrumb`, `TabGroup` (Profile / Security), `BaseCard`, `FormInput` (name, email; password, password_confirmation), `FormToggle` (optional), `BaseButton`, `BaseModal` (delete-account confirm), `ToastHost` |
| `auth/login` | **stays Blade** | — |
| `auth/passwords/*` | **stays Blade** | — |

- Two tabs (`TabGroup`): **Profile** (name + email → `updateProfile`) and **Security** (password + confirmation → `update`). A danger-zone "Delete my account" → `BaseModal` confirm → `destroy` (only shown if `canDelete()`).
- Password field forces LTR; success → toast using the same `translation.*` message keys.

### Vue Router route
- `/profile/password` → `AccountSettings.vue` `meta: { perm: 'profile_password_edit' }` (defense-in-depth; backend gate is authoritative). Linked from the AppShell user/avatar menu (replaces the Blade menu link).

### nav.config.js / shell
Wire the user-menu "Profile/Settings" + "Logout" in `AppShell`. **Logout** must
POST to the existing `route('logout')` (Blade) — do not reimplement session
teardown in JS; submit a hidden form / `window.location` to the logout POST.

### States
Validation errors per field from 422; success toast; delete-account confirm modal
with the same RTL "are you sure" copy used elsewhere.

---

## 4. Data / API contract

Only the **in-app profile** screen needs API endpoints. Login/reset stay on their
existing Blade POST routes.

| Method | Path | Purpose | Reuses |
|---|---|---|---|
| GET | `/app/api/profile` | current user `{ name, email }` | `auth()->user()` + `profile_password_edit` |
| POST/PUT | `/app/api/profile` | update name+email | **`UpdateProfileRequest`** |
| POST/PUT | `/app/api/profile/password` | change password | **`UpdatePasswordRequest`** (sends `password` + `password_confirmation`) |
| DELETE | `/app/api/profile` | self-delete (anonymize email + soft delete) | reuse `ChangePasswordController@destroy` logic + `can-delete` |

- Reuse the exact Form Requests (so `min:8|confirmed` and `unique:users,email,{id}` behave identically; 422 → field errors).
- `destroy` must reuse the **email-anonymization-then-soft-delete** code path, then the SPA redirects to `/login` (full navigation, session is gone).
- **Logout is NOT an API call** — POST to the existing `route('logout')`.

> Alternative (lower effort): keep the profile screen on its existing Blade POST
> routes too and only restyle. But since it's post-auth and form-shaped, the SPA
> API endpoints above give a consistent in-app experience. Either is acceptable;
> this plan recommends the SPA endpoints.

---

## 5. Migration steps (ordered, checkable)

- [ ] decision sign-off: keep login/reset Blade; migrate in-app profile.
- [ ] backend: **create the missing `auth/passwords/edit.blade.php`** OR (preferred) build the SPA screen so the broken route is replaced — see §7 (this is a pre-existing bug to resolve as part of cutover).
- [ ] backend: `Api\ProfileApiController` (`show`/`updateProfile`/`updatePassword`/`destroy`) reusing `UpdateProfileRequest`, `UpdatePasswordRequest`, and the destroy anonymization + `can-delete`.
- [ ] frontend: `AccountSettings.vue` (TabGroup Profile/Security + danger zone).
- [ ] wire router `/profile/password` + AppShell user-menu (Profile link + Logout POST).
- [ ] parity test: name/email update, password change (8/confirmed), self-delete anonymizes email + redirects to login, success flash messages match.
- [ ] cutover the user-menu Profile link → `/app/profile/password`; leave `/login` + `/password/*` Blade untouched.
- [ ] mark status in `00-README.md`.

---

## 6. Risks / must-not-break

- **Auth flow is sacred** — login, logout, throttle, Remember me, CSRF, redirect-to-HOME, `guest` middleware. Keep these in Blade; only the in-app profile screen changes.
- **Self-delete anonymization** (`time()_email` then soft delete) must run server-side exactly — skipping it breaks unique-email re-registration.
- **Password change** must keep `min:8|confirmed`; do not relax in JS. The field is hashed via the model mutator on `update()` — reuse the same `update(validated())` path.
- **Email uniqueness** rule excludes the current user (`unique:users,email,{id}`) — reuse the Form Request so the `auth()->id()` exclusion is correct.
- **Logout via POST** only — never a GET/JS-only teardown.
- Gate `profile_password_edit` guards all profile mutations; `can-delete` guards self-delete. Keep both.

---

## 7. Out of scope / open questions

- **⚠️ PRE-EXISTING BUG:** `ChangePasswordController@edit()` returns `view('auth.passwords.edit')`, but **`resources/views/auth/passwords/edit.blade.php` does not exist** — so `/profile/password` currently throws a `View not found` error in Blade. The SPA migration is the opportunity to fix this (build `AccountSettings.vue` to serve `/profile/password`). Flag to product owner: confirm the profile screen is expected to work and whether name/email/password edit + self-delete are all desired.
- **Login redesign deferred:** migrating `/login` to Vue is possible but is a separate security-reviewed task (it would need its own non-SPA mount, pre-session). Not in this plan.
- `register` is disabled (`Auth::routes(['register' => false])`) and `auth/register.blade.php`/`verify.blade.php` are vestigial — do not migrate.
- The `file_exists(ChangePasswordController)` guard around the profile routes is a deploy-safety check; keep it.
