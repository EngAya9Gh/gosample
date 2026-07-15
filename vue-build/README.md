# MTC / GoSample — Vue 3 + Tailwind Front-End (Drop-in)

Production source for the MTC redesign, built to the **DELIVERY CONTRACT**: Vue 3
`<script setup>` SFCs, Tailwind-only styling, locked brand tokens, `ri-*` icons,
RTL-first with logical utilities, mock data (no API calls).

**This package now contains the COMPLETE screen inventory** — every screen in the
handoff (Section 5.A → 5.AA) is present as a real `.vue` view: ~150 routes across
dashboards, reports, tasks, drivers, cars, clients, users, settings, auth and
errors. They are wired in `routes.config.js` and browsable via `preview.html`.

---

## File map

```
tailwind.config.js                         # LOCKED brand tokens — merge into project config
resources/js/vue/
├── layouts/
│   ├── AppShell.vue                        # the one shell: sidebar+topbar+footer, dir/dark/lang, <slot> = <router-view>
│   ├── Sidebar.vue                         # dark, RTL, permission-gated nav tree (collapsible)
│   ├── Topbar.vue                          # hamburger, quick-create, 3-tab notifications, lang, dark, user menu
│   ├── Footer.vue
│   └── nav.config.js                       # full menu tree (groups → items, icon + route + perm + badge)
├── components/
│   ├── DataTable.vue                       # ⭐ search · sort · paginate(10–1000) · row-select · bulk · export · sticky cols · skeleton · empty
│   ├── FilterBar.vue                       # the ONE filter card (replaces the two competing styles)
│   ├── FormInput.vue                       # text/email/password(show-hide)/number(unit)/textarea/date/time
│   ├── FormSelect.vue                      # single + multi, search, Select-All / Clear, chips
│   ├── FormToggle.vue                      # iOS-style teal switch
│   ├── BaseButton.vue                      # one button system (variants + sizes + loading + icon-only)
│   ├── StatusBadge.vue                     # color-coded pill + glowing dot (task/sample/enabled statuses)
│   ├── StatCard.vue                        # KPI card w/ animated counter + delta
│   ├── BaseCard.vue                        # content card (header/actions/footer slots)
│   ├── TabGroup.vue                        # pills + underline tab variants
│   ├── BaseModal.vue                       # dialog/drawer w/ transitions (confirm, detail, edit)
│   ├── ToastHost.vue                       # slide-in toasts (mount once in AppShell)
│   ├── Timeline.vue                        # vertical step timeline (task lifecycle / driver route)
│   ├── BaseAvatar.vue                      # deterministic gradient initials avatar
│   ├── Breadcrumb.vue                      # page title + trail + actions slot
│   └── EmptyState.vue
├── composables/
│   ├── useToast.js                         # toast store — push()/dismiss()
│   ├── usePermissions.js                   # can(perm) / canDelete() gate (seed with real perms at boot)
│   └── useCounter.js                       # animated counter, respects prefers-reduced-motion
└── views/
    ├── Dashboard/Analytics.vue             # showcase: greeting · 4 KPIs · area chart · donut · top drivers · activity rail
    └── Tasks/TasksList.vue                 # showcase: FilterBar + DataTable (all 16 cols) + delete-confirm modal
```

---

## Wiring into the project

1. **Tokens** — merge `tailwind.config.js` `theme.extend` into your project config.
   No component uses an inline hex; everything references these tokens, so the
   brand stays locked and centralized.

2. **Icons** — ensure Remix Icon is globally available (already assumed):
   `import 'remixicon/fonts/remixicon.css'`.

3. **Shell + router** — render screens through the shell:
   ```vue
   <!-- App.vue -->
   <AppShell :current="$route.path" @navigate="$router.push($event)">
     <router-view />
   </AppShell>
   ```
   Screens do **not** re-include the shell.

4. **Permissions** — at boot, seed the real user permissions:
   ```js
   import { setPermissions } from '@/vue/composables/usePermissions';
   setPermissions(user.permissions, user.can_delete);
   ```
   Components call `can('task_create')` / `canDelete()` and render actions
   gracefully (hidden, never broken) when denied.

5. **Toasts** — `<ToastHost />` is already mounted inside `AppShell`. Anywhere:
   ```js
   const { push } = useToast();
   push({ type:'success', title:'Saved', message:'Task #10428 updated' });
   ```

---

## RTL & language

- `AppShell` sets `dir` from `lang` (`ar` → `rtl`, `en` → `ltr`) on the root and
  persists both `lang` and dark mode to `localStorage`.
- All spacing/positioning uses **logical utilities** (`ps-/pe-`, `ms-/me-`,
  `start-/end-`, `text-start/end`) so the entire UI mirrors automatically — no
  hardcoded left/right. LTR data (IDs, coordinates, IMEIs, barcodes, plates) is
  forced `direction:ltr` where it appears.

---

## Charts & maps (placeholders to swap)

To keep this bundle dependency-free, `Analytics.vue` ships the area chart and
donut as inline SVG. Replace with the project's chart libs — the data shapes are
annotated in comments:
- **ApexCharts** (`vue3-apexcharts`) — line/area + donut.
- **Chart.js** — temperature line on the task report.
- **FullCalendar** — calendar screens.
- **Google Maps JS API** — live map, zone polygon draw, location lat/lng picker.

---

## Screen inventory (all present)

Every route is mapped in `routes.config.js` → `views/<Module>/<Screen>.vue`:

- **Dashboard/** — Analytics, DelayedDashboard, CarDashboard, TasksDashboard, LiveMap, SystemCalendar
- **Reports/** — ReportsIndex, DailyReport, WeeklyReport, MonthlyReport (leaderboard), PerformanceDashboard
- **Tasks/** — TasksList, TaskCreate, TaskEdit, TaskShow (printable journey report + timeline + temp chart), ScanSamples, MissingSamples, ExportPending, DriverTracking, DailyOperation, UnusedTasks, SwapTasks, ScheduleLogs, CollectedDelayed, DropoffDelayed, PickupDelayed, OutFreezerDelayed
- **Scheduled/** — ScheduledTasksList, ScheduledTaskCreate, ScheduledTaskQuickCreate, ScheduledTaskShow
- **Swap/** — SwapRequests List/Create/Edit/Show
- **Samples/** — SamplesList, SamplesShow, LostSamplesList
- **Shipments/** · **Containers/** · **Barcodes/** (+ GenerateBarcodes print sheet) · **Money/** — List/Create/Edit/Show each
- **Drivers/** — DriversList, DriverCreate, DriverEdit (shift scheduler), DriverShow (profile w/ tabs + KPI bars), DriverRoute (drag-drop), Attendances ·, ShiftTemplates ·, DriverSchedules · CRUD
- **Cars/** — CarsList, Create/Edit, CarShow (tabs + delivery-photo gallery + tracking), CarDrivers ·, CarLinkHistories · CRUD
- **Zones/** — List + Create/Edit/Show with polygon-draw map
- **Clients/** — Clients, ClientAccounts, ClientDrivers, ClientLocations, Contacts · CRUD
- **Locations/** — List + Create/Edit (Places map picker) + Show (with barcode)
- **Users/** — Users CRUD, Roles (card grid + toggle-switch permission editor), Permissions CRUD
- **Settings/** — AuditLogs (+ old/new diff Show), Terms, Notifications (read-only), ElmNotifications, ApiAyenati, UserAlerts, DeletePermissions
- **Auth/** — Login, Register, TwoStepVerify, ForgotPassword, ResetPassword, ConfirmPassword, ChangePassword, Profile
- **Errors/** — Error404, Error500, ErrorAfaqy

**Generated vs hand-built:** standard CRUD list/create/edit/show views were
produced from a column/field spec (faithful to the handoff's columns, fields and
filters, with realistic mock rows); the showcase/special screens (dashboards,
reports, task report, scan, driver profile/route, car gallery, maps, roles,
barcodes, audit diff, auth, errors) are hand-built. All compose the same shared
components, so visual language is uniform. Refine any generated screen by editing
its column/field arrays.

---

## Recomposition patterns

Every screen is a thin composition you can clone and adjust:

- **List** = `Breadcrumb` + `FilterBar` + `DataTable` (define `columns`, pass
  `rows`, slot custom cells + row actions) + `BaseModal` delete-confirm.
- **Create/Edit** = `BaseCard` + two-column grid of `FormInput`/`FormSelect`/
  `FormToggle` + `BaseButton` Save/Cancel.
- **Show** = `BaseCard` + `TabGroup` + definition rows (+ `Timeline`, `BaseAvatar`).
- **Dashboards** = `StatCard` grid + `BaseCard`-wrapped charts.

Set `serverSide` on `DataTable` for the big lists (Tasks, Daily Operation,
Samples, Audit Logs, Notifications) and handle its `@query` event with your AJAX.

---

## Preview harness

`preview.html` (project root) mounts `AppShell` + the two showcase views in the
browser via `vue3-sfc-loader` + Tailwind Play CDN — open it to see the components
live without a build step. It's a **dev aid only**, not part of the deliverable;
the `.vue`/`.js` files under `resources/` are what you paste into the project.

---

## Quality bar covered

Card hover lifts · skeleton table loaders · animated KPI counters · progress-bar
fills · smooth modal/drawer/dropdown transitions · toast slide-ins · sticky table
columns + horizontal scroll · empty states · full dark-mode (`dark:` variants
throughout) · `prefers-reduced-motion` respected in every animation.
