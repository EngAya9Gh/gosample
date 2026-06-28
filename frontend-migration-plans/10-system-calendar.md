# System Calendar — Frontend Migration Plan

> Read [`01-foundation.md`](01-foundation.md) first. This is a tiny read-only
> screen: one FullCalendar of open tasks. We add a single JSON endpoint that
> reuses `SystemCalendarController`'s event-building logic and render it with a
> FullCalendar wrapper in the SPA. No business logic changes.

---

## 1. Module overview

A month calendar showing every **open task** (status NOT in CLOSED/NO_SAMPLES)
plotted on its `created_at` date, each event linking to that task's edit screen.

- **Nav group:** Tasks (Settings/Reports area in some menus).
- **Route / gate:** `/admin/system-calendar` · `admin.systemCalendar`. The
  controller has **no `abort_if` gate** today (any authenticated user). Mirror
  that; gate rendering in the SPA only if desired (e.g. `task_access`).

---

## 2. Current implementation (Blade / Velzon)

### Route (controller = `Admin\SystemCalendarController`)

| Method | URI | Name | Action | Gate |
|---|---|---|---|---|
| GET | `admin/system-calendar` | `admin.systemCalendar` | `index` | none |

### What `index` passes

Iterates a `$sources` array (currently a single source: `App\Models\Task`,
`date_field=created_at`, `field=id`, `prefix='Task: '`, `route='admin.tasks.edit'`).
For every Task `whereNotIn('status',['CLOSED','NO_SAMPLES'])` it builds:
```php
[ 'title' => 'Task:  {id}', 'start' => $task->created_at, 'url' => route('admin.tasks.edit', $task->id) ]
```
Passes `$events` to the view.

### Blade view

- `resources/views/admin/calendar/calendar.blade.php` — **FullCalendar 3.1.0**
  (+ moment.js) on `#calendar`, `eventLimit:4`, events injected inline as JSON.
  No filter form, no DataTable, no other widgets (heavy custom CSS only).

### Behaviors to PRESERVE

- The open-task filter (`status NOT IN (CLOSED, NO_SAMPLES)`).
- Event `start = created_at`, `title = "Task:  {id}"`, and the **click → task
  edit** navigation.
- The `$sources` indirection (so more models can be added later).

---

## 3. Target design (Vue + Tailwind)

| Blade view | Vue view | components |
|---|---|---|
| `calendar/calendar.blade.php` | `views/SystemCalendar/SystemCalendar.vue` | Breadcrumb, BaseCard, (FullCalendar wrapper), EmptyState |

- `SystemCalendar.vue` mounts a FullCalendar (the SPA's bundled version),
  `eventLimit:4` (or `dayMaxEvents`), monthly grid.
- Each event's `url` opens the task edit route **inside the SPA**
  (`/admin/tasks/:id/edit` via `router.push`) instead of a full-page link, so
  it stays in the SPA shell.
- Loading spinner while fetching; `EmptyState` when no open tasks.

### Vue Router route

```
/admin/system-calendar → SystemCalendar/SystemCalendar.vue   (meta.perm optional: task_access)
```

`nav.config.js`: ensure a "System Calendar" entry points at `/admin/system-calendar`.

---

## 4. Data / API contract

Add `Api\SystemCalendarApiController` (or fold into a small calendar controller).

### Events — `GET /app/api/system-calendar`

Reuse the **exact `$sources` loop** from `SystemCalendarController@index`.
Return FullCalendar event objects:
```json
{ "data": [
  { "title": "Task:  10428", "start": "2026-06-27 08:10:00", "taskId": 10428 }
] }
```
- `start` = `created_at` (raw, parity with Blade).
- Replace the absolute `url` with `taskId` so the SPA builds the in-app route
  (`/admin/tasks/{taskId}/edit`); optionally also include `url` for parity.
- Filter: `status NOT IN (CLOSED, NO_SAMPLES)`.
- No params (matches current behavior). If perf becomes an issue, add an
  optional month-range filter later — out of scope for parity.

No Form Request (read-only, no input). No exports. No mutations.

---

## 5. Migration steps (ordered, checkable)

- [ ] Backend: `GET /app/api/system-calendar` in `routes/app_api.php` reusing the
      `$sources` event loop (same open-task filter, same title format).
- [ ] Frontend: `SystemCalendar.vue` with FullCalendar wrapper; fetch events on
      mount; event click → `router.push('/admin/tasks/'+taskId+'/edit')`.
- [ ] Wire router + nav.
- [ ] Parity test: same set of events, same titles/dates, click lands on the
      correct task edit; RTL month grid correct.
- [ ] Cutover nav; keep Blade for rollback.

---

## 6. Risks / must-not-break

- **Open-task filter** must stay `status NOT IN (CLOSED, NO_SAMPLES)` — widening
  it would flood the calendar.
- **Event → task edit** navigation must resolve to a real task (the migrated
  `TaskEdit.vue` route).
- Performance: this loads **all** open tasks with no pagination (same as Blade);
  acceptable for parity but flag if the open-task count is large.

---

## 7. Out of scope / open questions

- Adding filters or a date-range to the calendar (Blade has none).
- Migrating the heavy bespoke calendar CSS — use the SPA's FullCalendar theme
  + Tailwind tokens instead of porting Velzon CSS.
- Whether to add a render-perm (`task_access`) gate — backend currently has
  none; do not change backend auth as part of this presentation migration.
- The separate **scheduled-tasks** calendar (`index-schedule`) is covered in
  [`32-scheduled-tasks.md`](09-scheduled-tasks.md), not here.
