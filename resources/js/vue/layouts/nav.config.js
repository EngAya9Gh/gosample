/**
 * Sidebar navigation tree — full permission-gated menu.
 * Each item: { label, icon (ri-*), route, perm?, badge? }
 * Groups: { label, icon, items[] }
 * Filtered against usePermissions().can(perm) at render time.
 *
 * IMPORTANT: `perm` values mirror the EXACT Gate names used by the classic
 * sidebar (resources/views/layouts/sidebar.blade.php @can('...')), so the new
 * sidebar shows exactly what the user sees in the main system. Items with no
 * `perm` are always visible (the classic menu doesn't gate them).
 */
export const NAV = [
  {
    label: 'Dashboards', icon: 'ri-dashboard-3-line', items: [
      { label: 'Analytics',         icon: 'ri-line-chart-line',     route: '/dashboard',        perm: 'dashboards' },
      { label: 'Delayed Tasks',     icon: 'ri-alarm-warning-line',  route: '/delayeddashboard', perm: 'delayeddashboard', badge: 'delayed' },
      { label: 'Car Dashboard',     icon: 'ri-temp-cold-line',      route: '/car-dashboard',    perm: 'car-dashboard' },
      { label: 'Map',               icon: 'ri-map-pin-line',        route: '/map',              perm: 'map' },
      { label: 'Tasks Dashboard',   icon: 'ri-bar-chart-grouped-line', route: '/tasks-dashboard', perm: 'tasks-dashboard' },
      { label: 'Operation Report',  icon: 'ri-file-list-3-line',    route: '/daily-operation',  perm: 'daily-operation' },
      { label: 'Reports',           icon: 'ri-line-chart-line',     route: '/reports',          perm: 'report_access' },
    ],
  },
  {
    label: 'Tasks', icon: 'ri-task-line', items: [
      { label: 'Tasks',            icon: 'ri-list-check-2',        route: '/admin/tasks',            perm: 'task_access' },
      { label: 'Driver Tracking',  icon: 'ri-route-line',          route: '/admin/driver-tracking',  perm: 'driver_tracking_access' },
      { label: 'Scheduled Tasks',  icon: 'ri-calendar-schedule-line', route: '/admin/scheduled-tasks', perm: 'scheduled_task_access' },
      { label: 'System Calendar',  icon: 'ri-calendar-2-line',     route: '/admin/system-calendar',  perm: 'scheduled_task_access' },
      { label: 'Create Task',      icon: 'ri-add-circle-line',     route: '/admin/tasks/create',     perm: 'task_create' },
      { label: 'Lost Samples',     icon: 'ri-flask-line',          route: '/admin/lost',             perm: 'task_missing', badge: 'lost' },
      { label: 'Scan Sample',      icon: 'ri-scan-2-line',         route: '/admin/tasks/scan',  perm: 'task_scan' },
      { label: 'Unused Tasks',     icon: 'ri-inbox-unarchive-line', route: '/admin/tasks/unused',    perm: 'unused_tasks' },
      { label: 'Samples',          icon: 'ri-test-tube-line',      route: '/admin/samples',          perm: 'sample_access' },
      { label: 'Shipments',        icon: 'ri-truck-line',          route: '/admin/shipments',        perm: 'shipment_access' },
      { label: 'Money Transfers',  icon: 'ri-money-dollar-circle-line', route: '/admin/money-transfers', perm: 'money_transfer_access' },
    ],
  },
  {
    label: 'Drivers', icon: 'ri-steering-2-line', items: [
      { label: 'Drivers',            icon: 'ri-user-star-line',    route: '/admin/drivers',            perm: 'driver_access' },
      { label: 'Cars',               icon: 'ri-car-line',          route: '/admin/cars',               perm: 'car_access' },
      { label: 'Car Link Histories', icon: 'ri-links-line',        route: '/admin/car-link-histories', perm: 'car_link_history_access' },
      { label: 'Containers',         icon: 'ri-archive-2-line',    route: '/admin/containers',         perm: 'container_access' },
      { label: 'Zones',              icon: 'ri-shape-2-line',      route: '/admin/zones',              perm: 'zone_access' },
      { label: 'Attendances',        icon: 'ri-time-line',         route: '/admin/attendances',        perm: 'attendance_access' },
      { label: 'Shift Templates',    icon: 'ri-calendar-check-line', route: '/admin/shift-templates',  perm: 'attendance_access' },
      { label: 'Swap Requests',      icon: 'ri-swap-line',         route: '/admin/swaprequests',       perm: 'swaprequest_access', badge: 'swap' },
    ],
  },
  {
    label: 'Clients', icon: 'ri-building-line', items: [
      { label: 'Clients',   icon: 'ri-briefcase-line', route: '/admin/clients',   perm: 'client_access' },
      { label: 'Locations', icon: 'ri-map-pin-2-line', route: '/admin/locations', perm: 'location_access' },
    ],
  },
  {
    label: 'Users', icon: 'ri-shield-user-line', items: [
      { label: 'Users',       icon: 'ri-group-line',     route: '/admin/users',       perm: 'user_access' },
      { label: 'Roles',       icon: 'ri-vip-crown-line', route: '/admin/roles',       perm: 'role_access' },
      { label: 'Permissions', icon: 'ri-key-2-line',     route: '/admin/permissions', perm: 'permission_access' },
    ],
  },
  {
    label: 'Settings', icon: 'ri-settings-3-line', items: [
      { label: 'Audit Logs',        icon: 'ri-history-line',       route: '/admin/audit-logs',        perm: 'audit_log_access' },
      { label: 'Terms',             icon: 'ri-translate-2',        route: '/admin/terms',             perm: 'term_access' },
      { label: 'Barcodes',          icon: 'ri-barcode-line',       route: '/admin/barcodes',          perm: 'barcode_access' },
      { label: 'Generate Barcodes', icon: 'ri-barcode-box-line',   route: '/admin/barcodes/generate', perm: 'barcode_access' },
      { label: 'Notifications',     icon: 'ri-notification-3-line', route: '/admin/notifications',    perm: 'notification_access' },
      { label: 'API Ayenati',       icon: 'ri-cloud-line',         route: '/admin/api-ayenatis',      perm: 'api_ayenati_access' },
    ],
  },
];
