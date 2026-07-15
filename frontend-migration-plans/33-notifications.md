# Notifications (+ Elm Notifications + User Alerts) — Frontend Migration Plan

> Read [`01-foundation.md`](01-foundation.md) first. API layer, `{data,meta}`,
> Yajra server-side `DataTable` contract, permissions and RTL/i18n live there.
> This file covers three related read-mostly screens:
> 1. **Notifications** — FCM/system notification log (Yajra server-side, read-only).
> 2. **Elm Notifications** — Elm integration notification log (paginated, read-only).
> 3. **User Alerts** — ⚠️ **ORPHANED**: views + Form Requests + lang exist but
>    there is **no controller and no routes** (see §7). Documented, not migrated.

---

## 1. Module overview

All three live under the `Settings`/notifications area. Notifications and Elm
Notifications are **read-only logs** (index + show only — `Route::resource` with
`except create/store/edit/update/destroy`). No dispatch happens from these
screens; FCM dispatch lives elsewhere in the app and **must not be touched**.

---

## 2. Current implementation (Blade / Velzon)

### 2a. Notifications — routes (`routes/web.php` ~186)
`Route::resource('notifications', 'NotificationsController', ['except' => ['create','store','edit','update','destroy']]);`
| Method | URI | Name | Action |
|---|---|---|---|
| GET | `/admin/notifications` | `admin.notifications.index` | `index` (Yajra ajax) |
| GET | `/admin/notifications/{notification}` | `admin.notifications.show` | `show` |

- `index(Request)` — gate `notification_access`. On `$request->ajax()`: **Yajra server-side** `DataTables::of(Notifications::with([task, fromLocation, toLocation, driver, billingClient]))`. Performance trick: total/filtered records come from `information_schema.TABLE_ROWS` cached 10 min (`notifications_total_count`) instead of `count(*)`; the real filtered count is only computed when a search value is present. **Preserve this — it is a deliberate perf optimization on a huge table.**
- Computed/added columns: `task_id` (task.id), `from_location_name`, `to_location_name`, `driver_name`, `billing_client_english_name`, `read_at`, plus `actions` (view/edit/delete via `partials.datatablesActions` with gates `notification_show`/`notification_edit`/`notification_delete`). Note: edit/delete routes don't exist in the resource (`except`), so those action buttons are effectively dead links — list is view-only in practice.
- `show(Notifications)` — gate `notification_show`, loads relations → detail view.

### 2b. Elm Notifications — routes (`routes/web.php` ~163–164)
`Route::resource('elm-notifications', 'ElmNotificationsController', ['except' => ['create','store','edit','update','destroy']]);`
| Method | URI | Name | Action |
|---|---|---|---|
| GET | `/admin/elm-notifications` | `admin.elm-notifications.index` | `index` |
| GET | `/admin/elm-notifications/{elm_notification}` | `admin.elm-notifications.show` | `show` |

- `index` — gate `elm_notification_access`; `ElmNotification::with('task')->paginate(50)` (**client/Laravel pagination**, not Yajra).
- `show` — gate `elm_notification_show`; loads `task`.

### Blade views
| File | Purpose |
|---|---|
| `admin/notifications/index.blade.php` | Yajra ajax DataTable; columns: id, task, from_location, to_location, driver, billing_client, read_at, actions (type/notifiable/data columns are commented out) |
| `admin/notifications/show.blade.php` | Detail |
| `admin/elmNotifications/index.blade.php` | Paginated list |
| `admin/elmNotifications/show.blade.php` | Detail |
| `admin/notifications/{create,edit}.blade.php`, `elmNotifications/{create,edit}.blade.php` | **Exist but unreachable** (no routes) |

### Gates
Notifications: `notification_access`, `notification_show` (+ `notification_edit`/`notification_delete` referenced in actions partial but routeless).
Elm: `elm_notification_access`, `elm_notification_show`.

### Form Requests (exist; mostly unused since no store/update routes)
- `StoreNotificationRequest`/`UpdateNotificationRequest` (`notification_create`/`notification_edit`, rules `[]`), `MassDestroyNotificationRequest`.
- `StoreElmNotificationRequest`/`UpdateElmNotificationRequest` (rules `[]`), `MassDestroyElmNotificationRequest`.

### Special behaviors to PRESERVE
- **`information_schema.TABLE_ROWS` count cache** for the Notifications table (10-min cache key `notifications_total_count`; filtered count only on search).
- Eager-loaded relation display columns (driver name, client english_name, etc.) pre-formatted to match Blade output.
- **No notification dispatch from these screens** — they are logs only.

---

## 3. Target design (Vue + Tailwind)

### View mapping
| Blade view | Vue view | vue-build components |
|---|---|---|
| `notifications/index` | `views/Notifications/NotificationsList.vue` | `Breadcrumb`, `FilterBar` (keyword), `DataTable` (server-side), `StatusBadge`, `BaseButton`, `EmptyState` |
| `notifications/show` | `views/Notifications/NotificationShow.vue` | `BaseCard`, `Timeline` (optional) |
| `elmNotifications/index` | `views/Notifications/ElmNotificationsList.vue` | `Breadcrumb`, `DataTable`, `EmptyState` |
| `elmNotifications/show` | `views/Notifications/ElmNotificationShow.vue` | `BaseCard` |

- NotificationsList uses the **server-side** `DataTable` mode (foundation §2 query) — read-only, no Add/Edit/Delete buttons (matching the routeless reality; only `view` action survives).
- ElmNotificationsList: server-side `DataTable` for consistency (replaces Laravel `paginate(50)`).

### Vue Router routes
- `/admin/notifications` `{ perm: 'notification_access' }`, `/admin/notifications/:id` `{ perm: 'notification_show' }`
- `/admin/elm-notifications` `{ perm: 'elm_notification_access' }`, `/admin/elm-notifications/:id` `{ perm: 'elm_notification_show' }`

### nav.config.js
Confirm `Settings → Notifications` (`notification_access`) and `Settings → Elm Notifications` (`elm_notification_access`). Cutover only.

---

## 4. Data / API contract

### Notifications (read-only)
| Method | Path | Purpose | Reuses |
|---|---|---|---|
| GET | `/app/api/notifications` | server-side list | same eager-loaded query + TABLE_ROWS count cache + `notification_access` |
| GET | `/app/api/notifications/{id}` | detail | `notification_show` |

Row shape: `{ id, task_id, from_location_name, to_location_name, driver_name, billing_client_english_name, read_at }`. **Reuse the exact count strategy** — the API list must return `meta.total` from the cached `TABLE_ROWS` value (not `count(*)`), and the real filtered count only when `q` is present.

### Elm Notifications (read-only)
| Method | Path | Purpose | Reuses |
|---|---|---|---|
| GET | `/app/api/elm-notifications` | server-side list | `ElmNotification::with('task')` + `elm_notification_access` |
| GET | `/app/api/elm-notifications/{id}` | detail | `elm_notification_show` |

### Validation
None needed (no create/update endpoints in scope — they don't exist in Blade either).

---

## 5. Migration steps (ordered, checkable)

- [ ] backend: `Api\NotificationsApiController` (index/show) — reuse eager query **and the TABLE_ROWS count cache logic** (extract to a shared method so web Yajra + API agree).
- [ ] backend: `Api\ElmNotificationsApiController` (index/show).
- [ ] frontend: `NotificationsList.vue` + `NotificationShow.vue`, `ElmNotificationsList.vue` + `ElmNotificationShow.vue`.
- [ ] wire router (4 routes) + confirm nav perms.
- [ ] parity test: same columns, same fast total count behavior, same relation display values, no edit/delete buttons.
- [ ] cutover nav.
- [ ] mark status in `00-README.md`.

---

## 6. Risks / must-not-break

- **Do not trigger any FCM/notification dispatch** from these screens — they are pure logs. No controller logic beyond reads.
- **Preserve the `TABLE_ROWS` count optimization.** Replacing it with `count(*)` on the notifications table will reintroduce 500ms+ queries the team deliberately removed.
- The actions partial references `notification_edit`/`notification_delete`, but no such routes exist. Do **not** build edit/delete in the SPA — that would be net-new functionality, out of the presentation-only mandate.
- Elm list moves from Laravel pagination to server-side DataTable — data shape unchanged, just transport.

---

## 7. Out of scope / open questions — ⚠️ USER ALERTS ORPHAN

**User Alerts is orphaned and is NOT migrated by this plan.** Evidence:
- Views exist: `resources/views/admin/userAlerts/{index,create,edit,show}.blade.php`.
- Form Requests exist: `StoreUserAlertRequest` (`user_alert_create`; rules `alert_text required|string`, `alert_link nullable|string`, `users array`, `users.* integer`), `UpdateUserAlertRequest`, `MassDestroyUserAlertRequest`.
- Lang keys exist (`cruds.userAlert.fields.*`).
- **But:** `grep` finds **no `UserAlertsController`** in `app/Http/Controllers/` and **no `user-alerts` / `userAlert` routes** in `routes/`.
- The orphaned `userAlerts/index.blade.php` references `route('admin.user-alerts.create')`, `route('admin.user-alerts.index')`, `route('admin.user-alerts.massDestroy')` — these route names **do not resolve** (would throw). The screen is currently dead.

**Open questions for the product owner before any User Alerts migration:**
1. Was User Alerts intentionally removed, or is the controller/route registration missing by mistake?
2. If it should exist, the model is `App\Models\UserAlert` with a `users` many-to-many (per the request rules) — does that table/pivot exist?
3. Do not invent endpoints. If revived, it would be a **new** module plan (CRUD: `user_alert_access/create/edit/show` gates) — out of scope here.

- Notification `create/edit` Blade views also exist but are routeless (vestigial) — ignore, same reasoning.
