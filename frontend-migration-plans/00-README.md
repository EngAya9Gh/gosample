# Frontend Migration Plans — MTC / GoSample → Vue 3 + Tailwind

This folder holds the **module-by-module migration plans** for rebuilding the
GoSample admin frontend (currently Velzon + Bootstrap + Blade) into the new
**Vue 3 + Tailwind SPA** using the component library delivered in
[`vue-build/`](../vue-build/).

> **Golden rule for the whole migration:** *We change the **presentation layer
> only**. We do not change business logic, validation rules, permissions, data
> shapes, or side effects, and we delete nothing.* Every Blade screen is
> re-expressed in Vue against the same data and the same backend behavior. If a
> plan ever requires touching controller logic beyond adding a JSON response,
> that is called out explicitly as a risk.

---

## How to read this folder

1. **Start with [`01-foundation.md`](01-foundation.md).** It defines the shared
   architecture every module plan depends on: how the SPA talks to the backend
   (the JSON API layer), the router, `AppShell` integration, permission seeding,
   RTL/i18n, the DataTable server-side contract, and the page-by-page migration
   workflow + cutover/rollback strategy. **No module work starts until the
   foundation is in place.**

2. **Then pick a module plan.** Each numbered file is one module (or a tightly
   coupled cluster). Every plan is self-contained and follows the same template
   (see below), so a developer can be handed a single `.md` and execute it
   without reading the others.

3. **Execution order** is suggested by the numbering, but modules are largely
   independent after the foundation. Recommended pilot order: a simple CRUD
   module ([31-terms.md](31-terms.md) or [20-zones.md](20-zones.md)) first to
   prove the pattern end-to-end, then the high-value screens (Tasks, Dashboards).

---

## Module index

> Status legend: ⬜ not started · 🟦 in progress · ✅ done

### Foundation
| # | File | Scope | Status |
|---|---|---|---|
| 01 | [01-foundation.md](01-foundation.md) | Inertia.js architecture, shell, permissions, RTL, visual identity, conventions | ✅ done (logout → plan 37) |

### Dashboards (`Dashboards` nav group)
| # | File | Module | Routes/Controllers | Status |
|---|---|---|---|---|
| 02 | [02-dashboard-analytics.md](02-dashboard-analytics.md) | Main analytics dashboard | `HomeController@index` | ✅ (Inertia; emergency-banner = global, deferred) |
| 03 | [03-dashboard-delayed.md](03-dashboard-delayed.md) | Delayed (alerts) dashboard | `DelayedDashboardController` | ✅ (Inertia; KPI cross-wiring preserved) |
| 04 | [04-dashboard-car.md](04-dashboard-car.md) | Car (temperature) dashboard | `CarDashboardController` | ✅ (Inertia; reuses Afaqy path; 15s poll; data live-only) |
| 05 | [05-dashboard-tasks.md](05-dashboard-tasks.md) | Tasks dashboard | `HomeController@tasksdashboard` | ⬜ |
| 06 | [06-daily-operation.md](06-daily-operation.md) | Daily operation | `DailyOperationController` | ⬜ |
| 07 | [07-map.md](07-map.md) | Live map | `HomeController@map/filterMap` | ⬜ |

### Tasks & operations (`Tasks` nav group)
| # | File | Module | Routes/Controllers | Status |
|---|---|---|---|---|
| 08 | [08-tasks.md](08-tasks.md) | Tasks (CRUD + scan/missing/unused/delayed/export) | `TasksController` | 🟦 list done (filters/exports/delete); create/edit/show/scan/missing/unused/delayed pending |
| 09 | [09-scheduled-tasks.md](09-scheduled-tasks.md) | Scheduled tasks + quick schedule | `ScheduledTaskController` | ⬜ |
| 10 | [10-system-calendar.md](10-system-calendar.md) | System calendar | `SystemCalendarController` | ⬜ |
| 11 | [11-swap.md](11-swap.md) | Swap requests + task swap | `SwaprequestController`, `TaskSwapController` | ⬜ |
| 12 | [12-samples.md](12-samples.md) | Samples + lost samples | `SamplesController` | ⬜ |
| 13 | [13-shipments.md](13-shipments.md) | Shipments | `ShipmentsController` | ⬜ |
| 14 | [14-money-transfers.md](14-money-transfers.md) | Money transfers | `MoneyTransferController` | ⬜ |
| 15 | [15-driver-tracking.md](15-driver-tracking.md) | Driver tracking | `DriverTrackingController` | ⬜ |

### Fleet & drivers (`Drivers` nav group)
| # | File | Module | Routes/Controllers | Status |
|---|---|---|---|---|
| 16 | [16-drivers.md](16-drivers.md) | Drivers (+ task reorder, shifts) | `DriversController`, `DriverController` | ⬜ |
| 17 | [17-cars.md](17-cars.md) | Cars | `CarsController` | ⬜ |
| 18 | [18-car-link-histories.md](18-car-link-histories.md) | Car link histories + car-drivers | `CarLinkHistoryController`, `CarDriverController` | ⬜ |
| 19 | [19-containers.md](19-containers.md) | Containers | `ContainersController` | ⬜ |
| 20 | [20-zones.md](20-zones.md) | Zones (map polygon draw) | `ZonesController` | ⬜ |
| 21 | [21-attendances.md](21-attendances.md) | Attendances | `AttendancesController` | ⬜ |
| 22 | [22-shift-templates.md](22-shift-templates.md) | Shift templates | `ShiftTemplatesController` | ⬜ |
| 23 | [23-driver-schedules.md](23-driver-schedules.md) | Driver schedules | `DriverScheduleController` | ⬜ |

### Clients (`Clients` nav group)
| # | File | Module | Routes/Controllers | Status |
|---|---|---|---|---|
| 24 | [24-clients.md](24-clients.md) | Clients (+ accounts, contacts, client-locations, client-drivers) | `ClientsController` et al. | ⬜ |
| 25 | [25-locations.md](25-locations.md) | Locations (lat/lng picker) | `LocationsController` | ⬜ |

### Access control (`Users` nav group)
| # | File | Module | Routes/Controllers | Status |
|---|---|---|---|---|
| 26 | [26-users.md](26-users.md) | Users | `UsersController` | ⬜ |
| 27 | [27-roles.md](27-roles.md) | Roles | `RolesController` | ⬜ |
| 28 | [28-permissions.md](28-permissions.md) | Permissions | `PermissionsController` | ⬜ |
| 29 | [29-delete-permissions.md](29-delete-permissions.md) | Delete permissions (`can-delete` gate admin) | `DeletePermissionsController` | ⬜ |
| 30 | [30-audit-logs.md](30-audit-logs.md) | Audit logs | `AuditLogsController` | ⬜ |

### Settings & reports (`Settings` / `Reports` nav groups)
| # | File | Module | Routes/Controllers | Status |
|---|---|---|---|---|
| 31 | [31-terms.md](31-terms.md) | Terms | `TermsController` | ⬜ |
| 32 | [32-barcodes.md](32-barcodes.md) | Barcodes + generate | `BarcodesController` | ⬜ |
| 33 | [33-notifications.md](33-notifications.md) | Notifications (+ elm-notifications, user alerts) | `NotificationsController` et al. | ⬜ |
| 34 | [34-api-ayenati.md](34-api-ayenati.md) | API Ayenati | `ApiAyenatiController` | ⬜ |
| 35 | [35-schedule-logs.md](35-schedule-logs.md) | Schedule logs | `ScheduleLogController` | ⬜ |
| 36 | [36-reports.md](36-reports.md) | Reports (daily/weekly/monthly/performance) | `ReportsController` | ⬜ |
| 37 | [37-profile-and-auth.md](37-profile-and-auth.md) | Profile / change password / login | `Auth\*` | ⬜ |

---

## Plan template (every module file follows this)

```
# <Module> — Frontend Migration Plan

## 1. Module overview
What it does, where it sits in the nav (group, route, permission gate), why it matters.

## 2. Current implementation (Blade / Velzon)
- Routes: method · URI · name · controller@action  (full table)
- Controller actions + what data each passes to its view
- Blade views: file → purpose
- Permissions/Gates used (access/create/edit/show/delete + any custom)
- Form Request validation classes + the exact field rules
- Special behaviors to PRESERVE: AJAX endpoints, DataTables (client vs server),
  exports (Excel/PDF), Select2/flatpickr, maps/charts/calendars, drag-drop, etc.

## 3. Target design (Vue + Tailwind)
- Page-by-page mapping: Blade view → new Vue view file under resources/js/vue/views/<Module>/
- Which vue-build components each page uses
- Vue Router routes to add
- nav.config.js entry (exists? perm key)
- Empty/loading/error states

## 4. Data / API contract
- Endpoints the SPA needs (reuse foundation conventions): method · path · purpose
- Request params (incl. DataTable server-side query for big lists)
- Response JSON shapes (list row shape, detail shape, option lists for selects)
- Validation reused (which Form Request) and how errors surface in the UI

## 5. Migration steps (ordered, checkable)
- [ ] backend: add JSON endpoint(s) reusing existing query + Form Request
- [ ] frontend: build view(s) from vue-build components
- [ ] wire router + nav
- [ ] parity test against the Blade screen
- [ ] flip nav to /app route (cutover)

## 6. Risks / must-not-break
Concrete list of logic/behaviors that the migration could accidentally break.

## 7. Out of scope / open questions
```

---

## Conventions used across all plans

- **Backend stays authoritative.** The SPA is a new presentation client. We add
  thin JSON endpoints that **reuse the existing Eloquent queries, Form Request
  validation, Gates, and exports**. We do not duplicate business rules in JS, and
  we do not modify or delete existing controllers, routes, or views.
- **Strangler-fig cutover.** Blade screens keep working. A module's nav item is
  flipped from its `/admin/...` Blade route to the `/app/...` Vue route only
  when the Vue screen reaches parity. Instant rollback = flip the nav back.
- **Permissions are never enforced in the client for security** — only for
  *rendering*. The backend Gates remain the source of truth (see foundation).
- **No brand drift.** All styling comes from the locked Tailwind tokens already
  merged into the project config; components come from `vue-build/`. No new hex.
- **RTL + i18n first.** Arabic is the primary language; every screen must mirror
  correctly and pull labels from the existing `trans()` keys (exposed to the SPA
  per the foundation plan).
```
