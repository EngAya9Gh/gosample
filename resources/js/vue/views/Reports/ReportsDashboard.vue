<script setup>
import { ref, watch, computed } from 'vue';
import { router } from '@inertiajs/vue3';
import Breadcrumb from '../../components/Breadcrumb.vue';
import FilterBar from '../../components/FilterBar.vue';
import FormSelect from '../../components/FormSelect.vue';
import FormDate from '../../components/FormDate.vue';
import TabGroup from '../../components/TabGroup.vue';

const props = defineProps({
  tab: { type: String, default: 'performance' },
  reportData: { type: Object, default: () => ({}) },
  filters: { type: Object, default: () => ({}) },
  options: { type: Object, default: () => ({}) }
});

const currentTab = ref(props.tab);
const loading = ref(false);

const localFilters = ref({
  client_id: props.filters.client_id || '',
  driver_id: props.filters.driver_id || '',
  date_from: props.filters.date_from || '',
  date_to: props.filters.date_to || '',
  month: props.filters.month || '',
  search_date: props.filters.search_date || '',
});

const tabs = [
  { key: 'performance', label: 'KPI Performance', icon: 'ri-speed-up-line' },
  { key: 'weekly', label: 'Weekly Consistency', icon: 'ri-calendar-event-line' },
  { key: 'monthly', label: 'Monthly Evaluation', icon: 'ri-medal-line' },
  { key: 'daily', label: 'Daily Report', icon: 'ri-calendar-todo-line' }
];

const driverOpts = computed(() => props.options.drivers?.map(d => ({ label: d.name, value: d.id })) || []);
const clientOpts = computed(() => props.options.clients?.map(c => ({ label: c.english_name, value: c.id })) || []);

function reloadData() {
  loading.value = true;
  router.get('/app/reports', { tab: currentTab.value, ...localFilters.value }, {
    preserveState: true,
    preserveScroll: true,
    onFinish: () => { loading.value = false; }
  });
}

function doSearch() {
  reloadData();
}

function doReset() {
  localFilters.value = {
    client_id: '',
    driver_id: '',
    date_from: '',
    date_to: '',
    month: '',
    search_date: '',
  };
  reloadData();
}

const driverGradients = [
  'linear-gradient(135deg, #0ab39c 0%, #068170 100%)',
  'linear-gradient(135deg, #299cdb 0%, #1c71a3 100%)',
  'linear-gradient(135deg, #f7b84b 0%, #d49524 100%)',
  'linear-gradient(135deg, #005D69 0%, #00363d 100%)',
  'linear-gradient(135deg, #BD6BA7 0%, #8e4c7d 100%)'
];
const getDriverGradient = (id) => driverGradients[(id || 0) % driverGradients.length];

// Click-through: any driver card/row opens the driver profile page.
// `from=reports` makes the profile's Back button return HERE (not the drivers list).
function openDriver(id) {
  if (id) router.visit(`/app/admin/drivers/${id}?from=reports`);
}

const getPunctColor = (p) => p >= 90 ? '#0ab39c' : (p >= 75 ? '#f7b84b' : '#dc2626');
const getPunctBg = (p) => p >= 90 ? 'rgba(10,179,156,0.12)' : (p >= 75 ? 'rgba(247,184,75,0.12)' : 'rgba(220,38,38,0.12)');

const TAB_LABEL = {
  performance: 'KPI Performance', weekly: 'Weekly Consistency',
  monthly: 'Monthly Evaluation', daily: 'Daily Report',
};

// Build { title, headers, rows } for the current tab — mirrors the on-screen
// table so Print and Export show exactly what the user sees.
function buildMatrix() {
  const drivers = props.reportData?.drivers || [];
  switch (currentTab.value) {
    case 'weekly':
      return {
        title: TAB_LABEL.weekly,
        headers: ['Driver', 'Days Worked', 'Total Delays', 'Overtime (hrs)', 'Avg Punctuality %'],
        rows: drivers.map((d) => [d.name, d.days_worked, d.total_delays, (Number(d.overtime || 0) / 60).toFixed(1), `${d.punctuality}%`]),
      };
    case 'monthly': {
      const sorted = [...drivers].sort((a, b) => b.performance_score - a.performance_score);
      return {
        title: TAB_LABEL.monthly,
        headers: ['Rank', 'Driver', 'Present', 'Absent', 'Late Days', 'Violations', 'Delay (Min)', 'Hrs Balance', 'Score %'],
        rows: sorted.map((d, i) => [i + 1, d.name, d.days_present, d.days_absent, d.days_late, d.kpi_violations, d.total_delay, `${(Number(d.hrs_balance || 0) / 60).toFixed(1)}h`, `${d.performance_score}%`]),
      };
    }
    case 'daily':
      return {
        title: TAB_LABEL.daily,
        headers: ['Driver', 'Check-in', 'Check-out', 'Late (min)', 'Operational Delays', 'Status'],
        rows: drivers.map((d) => [d.name, d.check_in, d.check_out, d.is_late ? d.delay_mins : 0, d.delayed_tasks, d.has_attendance ? 'Present' : 'Absent']),
      };
    default:
      return {
        title: TAB_LABEL.performance,
        headers: ['Driver', 'Punctuality %', 'Operation Speed (min)', 'Violations'],
        rows: drivers.map((d) => [d.name, `${d.punctuality}%`, d.avg_speed_mins, d.delayed_tasks]),
      };
  }
}

// Human-readable summary of the active filters (printed on the report header).
function filterSummary() {
  const f = localFilters.value;
  const parts = [];
  const drv = driverOpts.value.find((o) => o.value === f.driver_id);
  if (drv) parts.push(`Driver: ${drv.label}`);
  if (currentTab.value === 'performance') {
    const cl = clientOpts.value.find((o) => o.value === f.client_id);
    if (cl) parts.push(`Client: ${cl.label}`);
  }
  if (f.date_from) parts.push(`From: ${f.date_from}`);
  if (f.date_to) parts.push(`To: ${f.date_to}`);
  if (currentTab.value === 'monthly' && f.month) parts.push(`Month: ${f.month}`);
  if (currentTab.value === 'daily' && f.search_date) parts.push(`Date: ${f.search_date}`);
  return parts.join('  ·  ') || 'All records';
}

// Clean print — renders ONLY the report (title, filters, table) in a fresh
// window, so the sidebar/topbar/filter chrome is never printed.
function doPrint() {
  const { title, headers, rows } = buildMatrix();
  const esc = (s) => String(s ?? '').replace(/[&<>"]/g, (c) => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;' }[c]));
  const th = headers.map((h) => `<th>${esc(h)}</th>`).join('');
  const tr = rows.length
    ? rows.map((r) => `<tr>${r.map((c) => `<td>${esc(c)}</td>`).join('')}</tr>`).join('')
    : `<tr><td colspan="${headers.length}" style="text-align:center;color:#94a3b8;padding:24px">No data for this selection.</td></tr>`;
  const w = window.open('', '_blank');
  if (!w) return;
  w.document.write(`<!doctype html><html><head><meta charset="utf-8"><title>${esc(title)}</title>
    <style>
      *{font-family:Poppins,Arial,sans-serif;box-sizing:border-box}
      body{margin:24px;color:#16282b}
      .head{display:flex;justify-content:space-between;align-items:flex-end;border-bottom:2px solid #005D69;padding-bottom:12px}
      h1{font-size:20px;margin:0;color:#005D69}
      .sub{font-size:12px;color:#7e9094;margin-top:2px}
      .meta{font-size:11px;color:#7e9094;text-align:right;line-height:1.5}
      .filters{font-size:12px;color:#52656a;margin:12px 0 16px}
      table{width:100%;border-collapse:collapse;font-size:12px}
      th{background:#005D69;color:#fff;text-align:left;padding:8px 10px;font-weight:600}
      td{border:1px solid #e3eaea;padding:7px 10px}
      tr:nth-child(even) td{background:#f6f9f9}
      @media print{body{margin:0}}
    </style></head><body>
    <div class="head">
      <div><h1>Performance Tracking</h1><div class="sub">${esc(title)}</div></div>
      <div class="meta"><b>MTC · GoSample</b><br>${esc(new Date().toLocaleString('en-GB'))}</div>
    </div>
    <div class="filters"><b>Filters:</b> ${esc(filterSummary())}</div>
    <table><thead><tr>${th}</tr></thead><tbody>${tr}</tbody></table>
  </body></html>`);
  w.document.close();
  w.focus();
  setTimeout(() => w.print(), 300);
}

// Clean Excel — real .xlsx from the backend, current tab + active filters.
function doExport() {
  const f = localFilters.value;
  const params = new URLSearchParams({ tab: currentTab.value });
  ['driver_id', 'client_id', 'date_from', 'date_to', 'month', 'search_date']
    .forEach((k) => { if (f[k]) params.set(k, f[k]); });
  window.location.href = `/app/reports/export?${params.toString()}`;
}

watch(currentTab, (newTab) => {
  // Clear filters that don't apply to the new tab, if desired
  if (newTab !== 'performance') {
    localFilters.value.client_id = '';
  }
  reloadData();
});

function onDateRange(range) {
  if (range && range.start && range.end) {
    localFilters.value.date_from = range.start.toISOString().split('T')[0];
    localFilters.value.date_to = range.end.toISOString().split('T')[0];
  } else {
    localFilters.value.date_from = '';
    localFilters.value.date_to = '';
  }
}
</script>

<template>
  <div class="px-4 py-6 md:px-6 lg:px-8 max-w-7xl mx-auto space-y-6">
    <Breadcrumb title="Reports & Analytics" :trail="[{ label: 'Dashboards', href: '/app/dashboard' }, { label: 'Reports' }]" />

    <!-- Header Section matching MTC -->
    <div class="flex flex-col sm:flex-row sm:justify-between sm:items-center gap-4 mb-2">
      <div>
        <h1 class="text-[24px] font-extrabold text-[#16282b] dark:text-[#e8f0f0] mb-1">Performance Tracking</h1>
        <div class="text-[13.5px] font-medium text-[#7e9094] dark:text-[#7b8d8f]">Monitor carrier performance, delays, and task execution.</div>
      </div>
      <div class="flex items-center gap-3">
        <button class="h-[38px] px-4 rounded-[10px] border border-[#e3eaea] dark:border-[#1d2c2e] bg-white dark:bg-[#0f1c1e] text-[#16282b] dark:text-[#e8f0f0] text-[13.5px] font-semibold flex items-center gap-2 hover:bg-slate-50 dark:hover:bg-[#13201f] transition-colors" @click="doPrint">
          <i class="ri-printer-line text-[16px]"></i> Print
        </button>
        <button class="h-[38px] px-4 rounded-[10px] border-none text-white text-[13.5px] font-semibold flex items-center gap-2 shadow-[0_8px_18px_rgba(0,93,105,0.22)] hover:opacity-90 transition-opacity" style="background:linear-gradient(135deg,#0d9488,#005D69);" @click="doExport">
          <i class="ri-file-excel-2-line text-[16px]"></i> Export Excel
        </button>
      </div>
    </div>

    <!-- Tabs and Filters independent of the cards -->
    <div class="flex items-center gap-2 mb-4">
      <TabGroup :tabs="tabs" v-model:active="currentTab" variant="pills" />
    </div>

    <!-- Positioning wrapper only (z-10 keeps the filter dropdowns above the results);
         FilterBar renders its own card, so no card styling here — avoids a double card. -->
    <div class="relative z-10">
      <FilterBar :loading="loading" subtitle="Refine your report data" @search="doSearch" @reset="doReset">
        <!-- Shared Driver Filter -->
        <FormSelect v-model="localFilters.driver_id" label="Driver" :options="driverOpts" placeholder="All Drivers" />
        
        <!-- KPI Performance Filters -->
        <template v-if="currentTab === 'performance'">
          <FormSelect v-model="localFilters.client_id" label="Client" :options="clientOpts" placeholder="All Clients" />
          <FormDate label="Date Range" mode="range" placeholder="Select range" @range="onDateRange" />
        </template>

        <!-- Weekly Filters -->
        <template v-if="currentTab === 'weekly'">
          <FormDate label="Date Range" mode="range" placeholder="Select week range" @range="onDateRange" />
        </template>

        <!-- Monthly Filters -->
        <template v-if="currentTab === 'monthly'">
          <FormDate v-model="localFilters.month" label="Month" mode="month" placeholder="Select month" />
        </template>

        <!-- Daily Filters -->
        <template v-if="currentTab === 'daily'">
          <FormDate v-model="localFilters.search_date" label="Date" mode="date" placeholder="Select date" />
        </template>
      </FilterBar>
    </div>

    <div class="relative min-h-[300px]">
      <div v-if="loading" class="absolute inset-0 bg-surface/50 dark:bg-surface-dark-card/50 backdrop-blur-sm z-10 flex items-center justify-center rounded-2xl">
        <div class="flex flex-col items-center gap-3">
          <i class="ri-loader-4-line text-3xl animate-spin text-primary"></i>
          <span class="text-sm font-medium text-slate-500">Loading Report...</span>
        </div>
      </div>

      <!-- PERFORMANCE TAB -->
      <div v-if="currentTab === 'performance'" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
        <div v-if="!reportData.drivers?.length" class="col-span-full py-12 text-center text-slate-500">
          No KPI data available for this selection.
        </div>
        <div v-for="d in reportData.drivers" :key="d.id" @click="openDriver(d.id)" title="Open driver profile"
          class="bg-white dark:bg-[#0f1c1e] border border-[#e3eaea] dark:border-[#1d2c2e] rounded-[16px] p-[18px] cursor-pointer transition-all duration-200 hover:shadow-md hover:-translate-y-0.5 hover:ring-2 hover:ring-primary-500/30">
          <div class="flex items-center gap-[11px] mb-[16px]">
            <div class="w-[42px] h-[42px] rounded-full text-white flex items-center justify-center text-[13px] font-bold shrink-0" :style="{ background: getDriverGradient(d.id) }">
              {{ d.name.substring(0, 1).toUpperCase() }}
            </div>
            <div class="flex-1 min-w-0">
              <div class="text-[13.5px] font-bold text-[#16282b] dark:text-[#e8f0f0] truncate">{{ d.name }}</div>
              <span class="inline-flex items-center gap-1.5 text-[10px] font-semibold text-[#0ab39c] bg-[#0ab39c]/12 px-2 py-[3px] rounded-[7px] mt-0.5 ring-1 ring-[#0ab39c]/30 shadow-[0_0_8px_rgba(10,179,156,0.3)]">
                <span class="relative flex h-1.5 w-1.5">
                  <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-[#0ab39c] opacity-75"></span>
                  <span class="relative inline-flex rounded-full h-1.5 w-1.5 bg-[#0ab39c]"></span>
                </span>
                Active Carrier
              </span>
            </div>
          </div>

          <div class="mb-[14px]">
            <div class="flex justify-between mb-[6px]">
              <span class="text-[12px] text-[#52656a] dark:text-[#a9babc]">Punctuality</span>
              <span class="text-[12.5px] font-bold" :style="{ color: getPunctColor(d.punctuality) }">
                {{ d.punctuality }}%
              </span>
            </div>
            <div class="h-2 rounded-[5px] bg-[#eef1f5] dark:bg-[#162426] overflow-hidden">
              <div class="h-full rounded-[5px] transition-all duration-1000" :style="{ background: getPunctColor(d.punctuality), width: d.punctuality + '%' }"></div>
            </div>
          </div>

          <div class="flex gap-[10px]">
            <div class="flex-1 bg-[#f6f9f9] dark:bg-[#0c1719] rounded-[11px] p-[11px_13px]">
              <div class="text-[10.5px] text-[#7e9094] dark:text-[#7b8d8f]">Operation speed</div>
              <div class="text-[15px] font-bold text-[#16282b] dark:text-[#e8f0f0] mt-1">{{ d.avg_speed_mins }} <span class="text-[11px] font-medium text-[#7e9094] dark:text-[#7b8d8f]">min</span></div>
            </div>
            <div class="flex-1 bg-[#f6f9f9] dark:bg-[#0c1719] rounded-[11px] p-[11px_13px]">
              <div class="text-[10.5px] text-[#7e9094] dark:text-[#7b8d8f]">Violations</div>
              <div class="text-[15px] font-bold mt-1" :style="{ color: d.delayed_tasks > 0 ? getPunctColor(d.punctuality) : '#16282b' }">{{ d.delayed_tasks }} <span v-if="d.delayed_tasks > 0" class="text-[11px] font-medium text-[#7e9094] dark:text-[#7b8d8f]">delays</span></div>
            </div>
          </div>
        </div>
      </div>

      <!-- WEEKLY TAB -->
      <div v-if="currentTab === 'weekly'" class="bg-surface dark:bg-surface-dark-card border border-slate-200 dark:border-white/10 rounded-[16px] overflow-hidden">
        <div class="p-[16px_18px] border-b border-slate-200 dark:border-white/10 text-[14px] font-bold text-slate-900 dark:text-white">Driver Consistency Log</div>
        <div class="overflow-x-auto">
          <div class="min-w-[760px]">
            <div class="grid grid-cols-[minmax(200px,1.6fr)_130px_130px_150px_160px] items-center bg-surface-muted dark:bg-surface-dark-solid border-b border-slate-200 dark:border-white/10">
              <div class="p-[12px_16px] text-left text-[11px] font-bold uppercase tracking-[.04em] text-slate-500 dark:text-slate-400">Driver</div>
              <div class="p-[12px_14px] text-left text-[11px] font-bold uppercase tracking-[.04em] text-slate-500 dark:text-slate-400">Days Worked</div>
              <div class="p-[12px_14px] text-left text-[11px] font-bold uppercase tracking-[.04em] text-slate-500 dark:text-slate-400">Total Delays</div>
              <div class="p-[12px_14px] text-left text-[11px] font-bold uppercase tracking-[.04em] text-slate-500 dark:text-slate-400">Overtime</div>
              <div class="p-[12px_16px] text-left text-[11px] font-bold uppercase tracking-[.04em] text-slate-500 dark:text-slate-400">Avg Punctuality</div>
            </div>
            <div v-if="!reportData.drivers?.length" class="py-12 text-center text-slate-500">No weekly data available.</div>
            <div v-for="w in reportData.drivers" :key="w.id" @click="openDriver(w.id)" title="Open driver profile"
              class="grid grid-cols-[minmax(200px,1.6fr)_130px_130px_150px_160px] items-center border-t border-slate-200 dark:border-white/10 hover:bg-surface-muted dark:hover:bg-surface-dark-solid transition-colors cursor-pointer">
              <div class="p-[11px_16px]">
                <div class="flex items-center gap-[10px]">
                  <span class="w-[32px] h-[32px] rounded-full text-white flex items-center justify-center text-[11px] font-bold shrink-0" :style="{ background: getDriverGradient(w.id) }">{{ w.name.substring(0, 1).toUpperCase() }}</span>
                  <span class="text-[13px] font-semibold text-[#16282b] dark:text-[#e8f0f0]">{{ w.name }}</span>
                </div>
              </div>
              <div class="p-[11px_14px] text-left text-[13px] font-semibold text-slate-900 dark:text-white">{{ w.days_worked }} days</div>
              <div class="p-[11px_14px] text-left text-[13px] font-bold text-rose-500">{{ w.total_delays }} times</div>
              <div class="p-[11px_14px] text-left text-[13px] font-semibold text-emerald-500">{{ (w.overtime / 60).toFixed(1) }} hrs</div>
              <div class="p-[11px_16px] text-left">
                <span class="inline-flex items-center h-[24px] px-[8px] rounded-[12px] text-[11px] font-bold"
                  :style="{ color: getPunctColor(w.punctuality), background: getPunctBg(w.punctuality) }"
                >
                  {{ w.punctuality }}% Consistent
                </span>
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- MONTHLY TAB -->
      <div v-if="currentTab === 'monthly'" class="bg-surface dark:bg-surface-dark-card border border-slate-200 dark:border-white/10 rounded-[16px] overflow-hidden">
        <div class="p-[16px_18px] border-b border-slate-200 dark:border-white/10 flex justify-between items-center bg-surface-muted dark:bg-surface-dark-solid">
          <span class="text-[13px] font-bold text-slate-700 dark:text-slate-300">Expected Working Days: <span class="text-primary ml-1">{{ reportData.expected_days || 0 }}</span></span>
        </div>
        <div class="overflow-x-auto">
          <div class="min-w-[960px]">
            <div class="flex items-center bg-surface dark:bg-surface-dark-card border-b border-slate-200 dark:border-white/10">
              <div class="w-[80px] p-[12px_16px] text-[11px] font-bold uppercase tracking-[.04em] text-slate-500 dark:text-slate-400">Rank</div>
              <div class="flex-1 p-[12px_16px] text-[11px] font-bold uppercase tracking-[.04em] text-slate-500 dark:text-slate-400">Driver Name</div>
              <div class="w-[100px] text-center p-[12px_14px] text-[11px] font-bold uppercase tracking-[.04em] text-slate-500 dark:text-slate-400">Present</div>
              <div class="w-[100px] text-center p-[12px_14px] text-[11px] font-bold uppercase tracking-[.04em] text-slate-500 dark:text-slate-400">Absent</div>
              <div class="w-[100px] text-center p-[12px_14px] text-[11px] font-bold uppercase tracking-[.04em] text-slate-500 dark:text-slate-400">Late Days</div>
              <div class="w-[100px] text-center p-[12px_14px] text-[11px] font-bold uppercase tracking-[.04em] text-slate-500 dark:text-slate-400">Violations</div>
              <div class="w-[110px] text-center p-[12px_14px] text-[11px] font-bold uppercase tracking-[.04em] text-slate-500 dark:text-slate-400">Delay (Min)</div>
              <div class="w-[110px] text-center p-[12px_14px] text-[11px] font-bold uppercase tracking-[.04em] text-slate-500 dark:text-slate-400">Hrs Balance</div>
              <div class="w-[150px] text-center p-[12px_16px] text-[11px] font-bold uppercase tracking-[.04em] text-slate-500 dark:text-slate-400">Score</div>
            </div>
            <div v-if="!reportData.drivers?.length" class="py-12 text-center text-slate-500">No monthly data available.</div>
            <div v-for="(d, idx) in [...(reportData.drivers || [])].sort((a,b) => b.performance_score - a.performance_score)" :key="d.id" @click="openDriver(d.id)" title="Open driver profile"
              class="flex items-center border-t border-slate-200 dark:border-white/10 hover:bg-surface-muted dark:hover:bg-surface-dark-solid transition-colors cursor-pointer">
              <div class="w-[80px] p-[11px_16px]">
                <span v-if="idx === 0" class="inline-flex px-[6px] py-[2px] rounded text-[11px] font-bold bg-amber-100 text-amber-700">🥇 1st</span>
                <span v-else-if="idx === 1" class="inline-flex px-[6px] py-[2px] rounded text-[11px] font-bold bg-slate-100 text-slate-600">🥈 2nd</span>
                <span v-else-if="idx === 2" class="inline-flex px-[6px] py-[2px] rounded text-[11px] font-bold bg-orange-50 text-orange-700">🥉 3rd</span>
                <span v-else class="text-slate-500 text-[12px] font-bold">{{ idx + 1 }}th</span>
              </div>
              <div class="flex-1 p-[11px_16px] text-[13px] font-semibold text-slate-900 dark:text-white">{{ d.name }}</div>
              <div class="w-[100px] p-[11px_14px] text-center text-[13px] font-semibold text-emerald-500">{{ d.days_present }}</div>
              <div class="w-[100px] p-[11px_14px] text-center text-[13px] font-bold text-amber-500">{{ d.days_absent }}</div>
              <div class="w-[100px] p-[11px_14px] text-center text-[13px] font-semibold text-rose-500">{{ d.days_late }}</div>
              <div class="w-[100px] p-[11px_14px] text-center">
                <span class="inline-flex items-center justify-center px-[8px] py-[2px] rounded-full text-[11px] font-bold" :class="d.kpi_violations > 0 ? 'bg-rose-50 text-rose-600' : 'bg-emerald-50 text-emerald-600'">{{ d.kpi_violations }}</span>
              </div>
              <div class="w-[110px] p-[11px_14px] text-center text-[13px] font-semibold text-slate-700 dark:text-slate-300">{{ d.total_delay }}</div>
              <div class="w-[110px] p-[11px_14px] text-center text-[13px] font-bold" :class="d.hrs_balance >= 0 ? 'text-emerald-500' : 'text-rose-500'">
                {{ d.hrs_balance >= 0 ? '+' : '' }}{{ (d.hrs_balance / 60).toFixed(1) }}h
              </div>
              <div class="w-[150px] p-[11px_16px] text-center">
                <div class="h-[8px] rounded-[4px] bg-[#eef1f5] dark:bg-[#162426] overflow-hidden w-[80%] mx-auto">
                  <div class="h-full rounded-[4px] transition-all" :style="{ background: getPunctColor(d.performance_score), width: d.performance_score + '%' }"></div>
                </div>
                <div class="text-[11px] font-bold mt-1 text-center" :style="{ color: getPunctColor(d.performance_score) }">{{ d.performance_score }}% Score</div>
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- DAILY TAB -->
      <div v-if="currentTab === 'daily'" class="bg-surface dark:bg-surface-dark-card border border-slate-200 dark:border-white/10 rounded-[16px] overflow-hidden">
        <div class="overflow-x-auto">
          <div class="min-w-[800px]">
            <div class="flex items-center bg-surface-muted dark:bg-surface-dark-solid border-b border-slate-200 dark:border-white/10">
              <div class="flex-1 p-[12px_16px] text-[11px] font-bold uppercase tracking-[.04em] text-slate-500 dark:text-slate-400">Driver</div>
              <div class="w-[120px] text-center p-[12px_14px] text-[11px] font-bold uppercase tracking-[.04em] text-slate-500 dark:text-slate-400">Check-in</div>
              <div class="w-[120px] text-center p-[12px_14px] text-[11px] font-bold uppercase tracking-[.04em] text-slate-500 dark:text-slate-400">Check-out</div>
              <div class="w-[130px] text-center p-[12px_14px] text-[11px] font-bold uppercase tracking-[.04em] text-slate-500 dark:text-slate-400">Delay Start</div>
              <div class="w-[130px] text-center p-[12px_14px] text-[11px] font-bold uppercase tracking-[.04em] text-slate-500 dark:text-slate-400">Operational Delays</div>
              <div class="w-[120px] text-center p-[12px_16px] text-[11px] font-bold uppercase tracking-[.04em] text-slate-500 dark:text-slate-400">Status</div>
            </div>
            <div v-if="!reportData.drivers?.length" class="py-12 text-center text-slate-500">No daily data available.</div>
            <div v-for="d in reportData.drivers" :key="d.id" @click="openDriver(d.id)" title="Open driver profile"
              class="flex items-center border-t border-slate-200 dark:border-white/10 hover:bg-surface-muted dark:hover:bg-surface-dark-solid transition-colors cursor-pointer">
              <div class="flex-1 p-[11px_16px]">
                <div class="flex items-center gap-[10px]">
                  <span class="w-[32px] h-[32px] rounded-full text-white flex items-center justify-center text-[11px] font-bold shrink-0" :style="{ background: getDriverGradient(d.id) }">{{ d.name.substring(0, 1).toUpperCase() }}</span>
                  <span class="text-[13px] font-semibold text-[#16282b] dark:text-[#e8f0f0]">{{ d.name }}</span>
                </div>
              </div>
              <div class="w-[120px] p-[11px_14px] text-center text-[13px] font-semibold text-slate-700 dark:text-slate-300">{{ d.check_in }}</div>
              <div class="w-[120px] p-[11px_14px] text-center text-[13px] font-semibold text-slate-700 dark:text-slate-300">{{ d.check_out }}</div>
              <div class="w-[130px] p-[11px_14px] text-center">
                <span v-if="d.is_late" class="inline-flex px-[6px] py-[2px] rounded text-[11px] font-bold bg-rose-50 text-rose-600 animate-pulse">{{ d.delay_mins }} min late</span>
                <span v-else-if="d.has_attendance" class="inline-flex px-[6px] py-[2px] rounded text-[11px] font-bold bg-emerald-50 text-emerald-600">On Time</span>
                <span v-else class="text-[13px] text-slate-400">-</span>
              </div>
              <div class="w-[130px] p-[11px_14px] text-center">
                <span class="text-[13px] font-bold" :class="d.delayed_tasks > 0 ? 'text-rose-500' : 'text-slate-400'">{{ d.delayed_tasks }}</span>
              </div>
              <div class="w-[120px] p-[11px_16px] text-center">
                <span class="inline-flex items-center gap-[4px] px-[8px] py-[2px] rounded-md text-[11px] font-bold"
                  :class="d.has_attendance ? 'bg-emerald-50 text-emerald-600 dark:bg-emerald-500/10' : 'bg-surface-muted dark:bg-surface-dark-solid text-slate-500 dark:text-slate-400'"
                >
                  <span class="w-[6px] h-[6px] rounded-full" :class="d.has_attendance ? 'bg-emerald-500' : 'bg-slate-400'"></span>
                  {{ d.has_attendance ? 'Present' : 'Absent' }}
                </span>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>
