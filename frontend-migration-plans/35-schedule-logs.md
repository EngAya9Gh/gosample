# Schedule Logs — Frontend Migration Plan

> Read [`01-foundation.md`](01-foundation.md) first. This is a tiny **read-only**
> screen that surfaces the contents of the Laravel `schedule` log channel. There
> is no model, no CRUD, no DB table — it reads in-memory log handler records.
> The scheduled-task generation that *writes* these logs lives elsewhere and
> **must not be touched**.

---

## 1. Module overview

Schedule Logs displays the entries from the `schedule` Monolog channel — used to
debug the scheduled-task cron (e.g. `attendance:calc-auto`, scheduled-task
generation). One action, one read. Single route, single view. Sits under
`Settings`/diagnostics. No gate is applied in the controller (see §6 risk).

---

## 2. Current implementation (Blade / Velzon)

### Routes (`routes/web.php` ~218)
| Method | URI | Name | Action |
|---|---|---|---|
| GET | `/admin/schedules/logs` | `admin.schedules.logs` | `ScheduleLogController@index` |

### Controller action → view data
`ScheduleLogController@index`:
- Reads `Log::channel('schedule')->getLogger()->getHandlers()[0]->getRecords()` (the **in-memory** records of the first handler), applies the handler's `LineFormatter` (with `includeStacktraces()`), and maps each record to `{ date, level, message }`.
- Returns `view('schedules.logs', ['logs' => $logs])` → resolves to `resources/views/admin/schedules/logs.blade.php`.
- **No Gate check** in the controller (only the `auth` middleware on the `admin` group protects it).

### Blade view
| File | Purpose |
|---|---|
| `resources/views/admin/schedules/logs.blade.php` | Plain Bootstrap `<table>` (Date / Level / Message), `@foreach($logs ...)`. No DataTable, no pagination, no JS. |

### Special behaviors to PRESERVE
- The data source is the **runtime in-memory handler records** for the current request's `schedule` channel — not a file, not a DB table. The output reflects whatever that handler holds at request time. (NB: this is unusual — see §7.)
- `message` includes formatted line + stack traces (LTR, monospace).

---

## 3. Target design (Vue + Tailwind)

### View mapping (under `resources/js/vue/views/ScheduleLogs/`)
| Blade view | Vue view | vue-build components |
|---|---|---|
| `schedules/logs` | `ScheduleLogs.vue` | `Breadcrumb`, `BaseCard`, `DataTable` (client-side; small dataset) **or** a plain styled table, `StatusBadge` (color by level), `EmptyState` |

- Columns: `date`, `level` (badge: INFO/WARNING/ERROR → tone), `message` (monospace, `dir="ltr"`, `whitespace-pre-wrap` so stack traces wrap).
- Read-only; no row actions, no add/edit/delete.

### Vue Router routes
- `/admin/schedules/logs` → `ScheduleLogs.vue`. Add a `meta.perm` only if a gate is introduced (see §6); otherwise rely on the auth-guarded SPA + backend `auth` middleware.

### nav.config.js
Confirm a `Settings → Schedule Logs` entry pointing at `/admin/schedules/logs`. Cutover only.

### States
Empty → `EmptyState` ("No schedule log records"). No loading skeleton needed (single fetch).

---

## 4. Data / API contract

| Method | Path | Purpose | Reuses |
|---|---|---|---|
| GET | `/app/api/schedule-logs` | full log list | `ScheduleLogController@index` mapping logic |

Response (no pagination — small, matches Blade which renders all):
```json
{ "data": [ { "date": "2026-06-27 03:00:01", "level": "INFO", "message": "..." } ] }
```
- The API controller must reuse the **exact same** record-reading + `LineFormatter`-with-stacktraces mapping so the `message` strings are identical to Blade.
- No request params, no `/options`, no mutations.

---

## 5. Migration steps (ordered, checkable)

- [ ] backend: `Api\ScheduleLogsApiController@index` returning `{ data:[...] }`, reusing the same channel-record mapping (extract the mapping if shared with the web controller).
- [ ] frontend: `ScheduleLogs.vue` (table + level badges + pre-wrapped LTR message).
- [ ] wire router (1 route) + nav.
- [ ] parity test vs `/admin/schedules/logs`: same rows, same level/message values, stack traces visible.
- [ ] cutover nav.
- [ ] mark status in `00-README.md`.

---

## 6. Risks / must-not-break

- **No gate today.** The Blade screen is protected only by the `admin` group's `auth` middleware. Keep the API endpoint behind the same `auth` group. Do **not** silently add a new permission gate as part of the migration (that would be a logic/authorization change) — if access tightening is desired, raise it as a separate decision (§7).
- **Do not trigger any scheduling/cron logic** — this is read-only diagnostics.
- The `getRecords()` source is request-scoped in-memory; do not "improve" it to read log files unless explicitly asked (would change what is shown — see §7).

---

## 7. Out of scope / open questions

- The current source — first handler's in-memory `getRecords()` — typically returns only records produced during the *current* request, so in practice this screen may show little/nothing unless a handler is configured to retain records. This is **pre-existing behavior**; do not "fix" it during the presentation migration. Flag to product owner: *is this screen meant to tail the schedule log file?* If yes, that is a backend change, separate from this plan.
- No gate exists — confirm whether one should be added (separate task).
- View path quirk: controller returns `view('schedules.logs')` which resolves to `admin/schedules/logs.blade.php`; route name is `admin.schedules.logs`. No action needed for the SPA.
