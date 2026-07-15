/**
 * routes.config.js — every screen in the MTC redesign mapped to its view.
 * Used by the Vue Router and by preview.html. ':id' params are dynamic.
 *
 * In the project, build real routes:
 *   import { ROUTES } from './routes.config';
 *   const routes = ROUTES.map(r => ({
 *     path: r.path,
 *     component: () => import('./views/' + r.comp + '.vue'),
 *     meta: { noChrome: !!r.noChrome },
 *   }));
 * Wrap chrome routes in AppShell; render noChrome routes bare.
 */
export const ROUTES = [
  {
    "path": "/dashboard",
    "comp": "Dashboard/Analytics"
  },
  {
    "path": "/delayeddashboard",
    "comp": "Dashboard/DelayedDashboard"
  },
  {
    "path": "/car-dashboard",
    "comp": "Dashboard/CarDashboard"
  },
  {
    "path": "/tasks-dashboard",
    "comp": "Dashboard/TasksDashboard"
  },
  {
    "path": "/map",
    "comp": "Dashboard/LiveMap"
  },
  {
    "path": "/daily-operation",
    "comp": "Tasks/DailyOperation"
  },
  {
    "path": "/admin/system-calendar",
    "comp": "Dashboard/SystemCalendar"
  },
  {
    "path": "/admin/reports",
    "comp": "Reports/ReportsIndex"
  },
  {
    "path": "/admin/reports/daily",
    "comp": "Reports/DailyReport"
  },
  {
    "path": "/admin/reports/weekly",
    "comp": "Reports/WeeklyReport"
  },
  {
    "path": "/admin/reports/monthly",
    "comp": "Reports/MonthlyReport"
  },
  {
    "path": "/admin/reports/performance",
    "comp": "Reports/PerformanceDashboard"
  },
  {
    "path": "/admin/tasks",
    "comp": "Tasks/TasksList"
  },
  {
    "path": "/admin/tasks/create",
    "comp": "Tasks/TaskCreate"
  },
  {
    "path": "/admin/tasks/:id/edit",
    "comp": "Tasks/TaskEdit"
  },
  {
    "path": "/admin/tasks/:id",
    "comp": "Tasks/TaskShow"
  },
  {
    "path": "/admin/tasks/scan",
    "comp": "Tasks/ScanSamples"
  },
  {
    "path": "/admin/tasks/missing",
    "comp": "Tasks/MissingSamples"
  },
  {
    "path": "/admin/tasks/unused",
    "comp": "Tasks/UnusedTasks"
  },
  {
    "path": "/admin/tasks/export-pending",
    "comp": "Tasks/ExportPending"
  },
  {
    "path": "/admin/driver-tracking",
    "comp": "Tasks/DriverTracking"
  },
  {
    "path": "/admin/swap-tasks",
    "comp": "Tasks/SwapTasks"
  },
  {
    "path": "/admin/collectedDelayed",
    "comp": "Tasks/CollectedDelayed"
  },
  {
    "path": "/admin/dropdelayed",
    "comp": "Tasks/DropoffDelayed"
  },
  {
    "path": "/admin/pickupdelayed",
    "comp": "Tasks/PickupDelayed"
  },
  {
    "path": "/admin/outfreezerdelayed",
    "comp": "Tasks/OutFreezerDelayed"
  },
  {
    "path": "/admin/schedules/logs",
    "comp": "Tasks/ScheduleLogs"
  },
  {
    "path": "/admin/scheduled-tasks",
    "comp": "Scheduled/ScheduledTasksList"
  },
  {
    "path": "/admin/scheduled-tasks/create",
    "comp": "Scheduled/ScheduledTaskCreate"
  },
  {
    "path": "/admin/scheduled-tasks/quick",
    "comp": "Scheduled/ScheduledTaskQuickCreate"
  },
  {
    "path": "/admin/scheduled-tasks/:id",
    "comp": "Scheduled/ScheduledTaskShow"
  },
  {
    "path": "/admin/swaprequests",
    "comp": "Swap/SwapRequestsList"
  },
  {
    "path": "/admin/swaprequests/create",
    "comp": "Swap/SwapRequestsCreate"
  },
  {
    "path": "/admin/swaprequests/:id/edit",
    "comp": "Swap/SwapRequestsEdit"
  },
  {
    "path": "/admin/swaprequests/:id",
    "comp": "Swap/SwapRequestsShow"
  },
  {
    "path": "/admin/samples",
    "comp": "Samples/SamplesList"
  },
  {
    "path": "/admin/samples/:id",
    "comp": "Samples/SamplesShow"
  },
  {
    "path": "/admin/lost",
    "comp": "Samples/LostSamplesList"
  },
  {
    "path": "/admin/shipments",
    "comp": "Shipments/ShipmentsList"
  },
  {
    "path": "/admin/shipments/create",
    "comp": "Shipments/ShipmentsCreate"
  },
  {
    "path": "/admin/shipments/:id/edit",
    "comp": "Shipments/ShipmentsEdit"
  },
  {
    "path": "/admin/shipments/:id",
    "comp": "Shipments/ShipmentsShow"
  },
  {
    "path": "/admin/containers",
    "comp": "Containers/ContainersList"
  },
  {
    "path": "/admin/containers/create",
    "comp": "Containers/ContainersCreate"
  },
  {
    "path": "/admin/containers/:id/edit",
    "comp": "Containers/ContainersEdit"
  },
  {
    "path": "/admin/containers/:id",
    "comp": "Containers/ContainersShow"
  },
  {
    "path": "/admin/barcodes",
    "comp": "Barcodes/BarcodesList"
  },
  {
    "path": "/admin/barcodes/create",
    "comp": "Barcodes/BarcodesCreate"
  },
  {
    "path": "/admin/barcodes/:id/edit",
    "comp": "Barcodes/BarcodesEdit"
  },
  {
    "path": "/admin/barcodes/generate",
    "comp": "Barcodes/GenerateBarcodes"
  },
  {
    "path": "/admin/barcodes/:id",
    "comp": "Barcodes/BarcodesShow"
  },
  {
    "path": "/admin/money-transfers",
    "comp": "Money/MoneyTransfersList"
  },
  {
    "path": "/admin/money-transfers/create",
    "comp": "Money/MoneyTransfersCreate"
  },
  {
    "path": "/admin/money-transfers/:id/edit",
    "comp": "Money/MoneyTransfersEdit"
  },
  {
    "path": "/admin/money-transfers/:id",
    "comp": "Money/MoneyTransfersShow"
  },
  {
    "path": "/admin/drivers",
    "comp": "Drivers/DriversList"
  },
  {
    "path": "/admin/drivers/create",
    "comp": "Drivers/DriverCreate"
  },
  {
    "path": "/admin/drivers/:id/edit",
    "comp": "Drivers/DriverEdit"
  },
  {
    "path": "/admin/drivers/:id/tasks",
    "comp": "Drivers/DriverRoute"
  },
  {
    "path": "/admin/drivers/:id",
    "comp": "Drivers/DriverShow"
  },
  {
    "path": "/admin/attendances",
    "comp": "Drivers/AttendancesList"
  },
  {
    "path": "/admin/attendances/create",
    "comp": "Drivers/AttendancesCreate"
  },
  {
    "path": "/admin/attendances/:id/edit",
    "comp": "Drivers/AttendancesEdit"
  },
  {
    "path": "/admin/attendances/:id",
    "comp": "Drivers/AttendancesShow"
  },
  {
    "path": "/admin/shift-templates",
    "comp": "Drivers/ShiftTemplatesList"
  },
  {
    "path": "/admin/shift-templates/create",
    "comp": "Drivers/ShiftTemplatesCreate"
  },
  {
    "path": "/admin/shift-templates/:id/edit",
    "comp": "Drivers/ShiftTemplatesEdit"
  },
  {
    "path": "/admin/driver-schedules",
    "comp": "Drivers/DriverSchedulesList"
  },
  {
    "path": "/admin/driver-schedules/create",
    "comp": "Drivers/DriverSchedulesCreate"
  },
  {
    "path": "/admin/driver-schedules/:id/edit",
    "comp": "Drivers/DriverSchedulesEdit"
  },
  {
    "path": "/admin/driver-schedules/:id",
    "comp": "Drivers/DriverSchedulesShow"
  },
  {
    "path": "/admin/cars",
    "comp": "Cars/CarsList"
  },
  {
    "path": "/admin/cars/create",
    "comp": "Cars/CarsCreate"
  },
  {
    "path": "/admin/cars/:id/edit",
    "comp": "Cars/CarsEdit"
  },
  {
    "path": "/admin/cars/:id",
    "comp": "Cars/CarShow"
  },
  {
    "path": "/admin/car-drivers",
    "comp": "Cars/CarDriversList"
  },
  {
    "path": "/admin/car-drivers/create",
    "comp": "Cars/CarDriversCreate"
  },
  {
    "path": "/admin/car-drivers/:id/edit",
    "comp": "Cars/CarDriversEdit"
  },
  {
    "path": "/admin/car-drivers/:id",
    "comp": "Cars/CarDriversShow"
  },
  {
    "path": "/admin/car-link-histories",
    "comp": "Cars/CarLinkHistoriesList"
  },
  {
    "path": "/admin/car-link-histories/create",
    "comp": "Cars/CarLinkHistoriesCreate"
  },
  {
    "path": "/admin/car-link-histories/:id",
    "comp": "Cars/CarLinkHistoriesShow"
  },
  {
    "path": "/admin/zones",
    "comp": "Zones/ZonesList"
  },
  {
    "path": "/admin/zones/create",
    "comp": "Zones/ZoneCreate"
  },
  {
    "path": "/admin/zones/:id/edit",
    "comp": "Zones/ZoneEdit"
  },
  {
    "path": "/admin/zones/:id",
    "comp": "Zones/ZoneShow"
  },
  {
    "path": "/admin/clients",
    "comp": "Clients/ClientsList"
  },
  {
    "path": "/admin/clients/create",
    "comp": "Clients/ClientsCreate"
  },
  {
    "path": "/admin/clients/:id/edit",
    "comp": "Clients/ClientsEdit"
  },
  {
    "path": "/admin/clients/:id",
    "comp": "Clients/ClientsShow"
  },
  {
    "path": "/admin/client-accounts",
    "comp": "Clients/ClientAccountsList"
  },
  {
    "path": "/admin/client-accounts/create",
    "comp": "Clients/ClientAccountsCreate"
  },
  {
    "path": "/admin/client-accounts/:id/edit",
    "comp": "Clients/ClientAccountsEdit"
  },
  {
    "path": "/admin/client-accounts/:id",
    "comp": "Clients/ClientAccountsShow"
  },
  {
    "path": "/admin/client-drivers",
    "comp": "Clients/ClientDriversList"
  },
  {
    "path": "/admin/client-drivers/create",
    "comp": "Clients/ClientDriversCreate"
  },
  {
    "path": "/admin/client-drivers/:id/edit",
    "comp": "Clients/ClientDriversEdit"
  },
  {
    "path": "/admin/client-drivers/:id",
    "comp": "Clients/ClientDriversShow"
  },
  {
    "path": "/admin/client-locations",
    "comp": "Clients/ClientLocationsList"
  },
  {
    "path": "/admin/client-locations/create",
    "comp": "Clients/ClientLocationsCreate"
  },
  {
    "path": "/admin/client-locations/:id/edit",
    "comp": "Clients/ClientLocationsEdit"
  },
  {
    "path": "/admin/client-locations/:id",
    "comp": "Clients/ClientLocationsShow"
  },
  {
    "path": "/admin/contacts",
    "comp": "Clients/ContactsList"
  },
  {
    "path": "/admin/contacts/create",
    "comp": "Clients/ContactsCreate"
  },
  {
    "path": "/admin/contacts/:id/edit",
    "comp": "Clients/ContactsEdit"
  },
  {
    "path": "/admin/contacts/:id",
    "comp": "Clients/ContactsShow"
  },
  {
    "path": "/admin/locations",
    "comp": "Locations/LocationsList"
  },
  {
    "path": "/admin/locations/create",
    "comp": "Locations/LocationCreate"
  },
  {
    "path": "/admin/locations/:id/edit",
    "comp": "Locations/LocationEdit"
  },
  {
    "path": "/admin/locations/:id",
    "comp": "Locations/LocationShow"
  },
  {
    "path": "/admin/users",
    "comp": "Users/UsersList"
  },
  {
    "path": "/admin/users/create",
    "comp": "Users/UsersCreate"
  },
  {
    "path": "/admin/users/:id/edit",
    "comp": "Users/UsersEdit"
  },
  {
    "path": "/admin/users/:id",
    "comp": "Users/UsersShow"
  },
  {
    "path": "/admin/roles",
    "comp": "Users/RolesList"
  },
  {
    "path": "/admin/roles/create",
    "comp": "Users/RoleCreate"
  },
  {
    "path": "/admin/roles/:id/edit",
    "comp": "Users/RoleEdit"
  },
  {
    "path": "/admin/roles/:id",
    "comp": "Users/RoleShow"
  },
  {
    "path": "/admin/permissions",
    "comp": "Users/PermissionsList"
  },
  {
    "path": "/admin/permissions/create",
    "comp": "Users/PermissionsCreate"
  },
  {
    "path": "/admin/permissions/:id/edit",
    "comp": "Users/PermissionsEdit"
  },
  {
    "path": "/admin/permissions/:id",
    "comp": "Users/PermissionsShow"
  },
  {
    "path": "/admin/audit-logs",
    "comp": "Settings/AuditLogsList"
  },
  {
    "path": "/admin/audit-logs/:id",
    "comp": "Settings/AuditLogShow"
  },
  {
    "path": "/admin/terms",
    "comp": "Settings/TermsList"
  },
  {
    "path": "/admin/terms/create",
    "comp": "Settings/TermsCreate"
  },
  {
    "path": "/admin/terms/:id/edit",
    "comp": "Settings/TermsEdit"
  },
  {
    "path": "/admin/terms/:id",
    "comp": "Settings/TermsShow"
  },
  {
    "path": "/admin/notifications",
    "comp": "Settings/NotificationsList"
  },
  {
    "path": "/admin/notifications/:id",
    "comp": "Settings/NotificationsShow"
  },
  {
    "path": "/admin/elm-notifications",
    "comp": "Settings/ElmNotificationsList"
  },
  {
    "path": "/admin/elm-notifications/:id",
    "comp": "Settings/ElmNotificationsShow"
  },
  {
    "path": "/admin/api-ayenatis",
    "comp": "Settings/ApiAyenatiList"
  },
  {
    "path": "/admin/api-ayenatis/:id",
    "comp": "Settings/ApiAyenatiShow"
  },
  {
    "path": "/admin/user-alerts",
    "comp": "Settings/UserAlertsList"
  },
  {
    "path": "/admin/user-alerts/create",
    "comp": "Settings/UserAlertsCreate"
  },
  {
    "path": "/admin/user-alerts/:id",
    "comp": "Settings/UserAlertsShow"
  },
  {
    "path": "/admin/delete-permissions",
    "comp": "Settings/DeletePermissions"
  },
  {
    "path": "/admin/profile",
    "comp": "Auth/Profile",
    "chrome": true
  },
  {
    "path": "/admin/change-password",
    "comp": "Auth/ChangePassword",
    "chrome": true
  },
  {
    "path": "/login",
    "comp": "Auth/Login",
    "noChrome": true
  },
  {
    "path": "/register",
    "comp": "Auth/Register",
    "noChrome": true
  },
  {
    "path": "/auth/verify",
    "comp": "Auth/TwoStepVerify",
    "noChrome": true
  },
  {
    "path": "/auth/forgot",
    "comp": "Auth/ForgotPassword",
    "noChrome": true
  },
  {
    "path": "/auth/reset",
    "comp": "Auth/ResetPassword",
    "noChrome": true
  },
  {
    "path": "/auth/confirm",
    "comp": "Auth/ConfirmPassword",
    "noChrome": true
  },
  {
    "path": "/error/404",
    "comp": "Errors/Error404",
    "noChrome": true
  },
  {
    "path": "/error/500",
    "comp": "Errors/Error500",
    "noChrome": true
  },
  {
    "path": "/error/afaqy",
    "comp": "Errors/ErrorAfaqy",
    "noChrome": true
  }
];
