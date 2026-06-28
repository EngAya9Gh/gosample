<script setup>
/**
 * views/Dashboard/Analytics.vue — main operational dashboard (Inertia page).
 * Data via props from App\Http\Controllers\App\DashboardController@index (mirrors the
 * classic HomeController@index cached stats/top_drivers/notifications + the samples
 * donut). The samples donut re-fetches on date-range change via an Inertia partial reload.
 * The "Task Activity" area chart is cosmetic (no backing series exists in the system).
 */
import { reactive, computed, watch, ref, onMounted } from 'vue';
import { router, usePage } from '@inertiajs/vue3';
import { useCounter } from '../../composables/useCounter';
import Breadcrumb from '../../components/Breadcrumb.vue';
import BaseCard from '../../components/BaseCard.vue';
import BaseButton from '../../components/BaseButton.vue';
import StatCard from '../../components/StatCard.vue';
import BaseAvatar from '../../components/BaseAvatar.vue';
import FormDate from '../../components/FormDate.vue';
import EmptyState from '../../components/EmptyState.vue';
import VueApexCharts from 'vue3-apexcharts';
import { usePermissions } from '../../composables/usePermissions';

const props = defineProps({
  stats:         { type: Object, default: () => ({}) },
  topDrivers:    { type: Array,  default: () => [] },
  notifications: { type: Array,  default: () => [] },
  samplesReport: { type: Object, default: () => ({ labels: [], values: [] }) },
  range:         { type: Object, default: () => ({ from: '', to: '' }) },
  taskActivity:  { type: Object, default: () => ({ labels: [], values: [] }) },
});

// ---- Task Activity area chart (real monthly task volume, interactive) ----
const areaSeries = computed(() => [{ name: 'Tasks', data: props.taskActivity?.values || [] }]);
const areaOptions = computed(() => ({
  chart: {
    type: 'area', height: 260, toolbar: { show: false }, zoom: { enabled: false },
    fontFamily: 'Poppins, sans-serif',
    animations: { enabled: true, easing: 'easeinout', speed: 800 },
  },
  colors: ['#0d9488'],
  dataLabels: { enabled: false },
  stroke: { curve: 'smooth', width: 2.5 },
  fill: { type: 'gradient', gradient: { shadeIntensity: 1, opacityFrom: 0.32, opacityTo: 0.02, stops: [0, 90] } },
  grid: { borderColor: 'rgba(148,163,184,.18)', strokeDashArray: 4, padding: { left: 8, right: 8 } },
  xaxis: {
    categories: props.taskActivity?.labels || [],
    axisBorder: { show: false }, axisTicks: { show: false },
    labels: { style: { colors: '#94a3b8', fontSize: '11px' } },
  },
  yaxis: { labels: { style: { colors: '#94a3b8', fontSize: '11px' }, formatter: (v) => Math.round(v) } },
  markers: { size: 0, strokeColors: '#0d9488', hover: { size: 6 } },
  tooltip: { theme: 'light', y: { formatter: (v) => `${v} tasks` } },
}));

const { can } = usePermissions();
const page = usePage();
const firstName = computed(() => (page.props?.auth?.user?.name || '').split(' ')[0] || '');

const greeting = (() => {
  const h = new Date().getHours();
  return h < 12 ? 'Good Morning' : h < 18 ? 'Good Afternoon' : 'Good Evening';
})();
const today = new Date().toLocaleDateString('en-US', { weekday: 'long', month: 'long', day: 'numeric' });

// ---- KPIs (Clients card gated behind client_access, matching the classic @can) ----
const kpis = computed(() => {
  const s = props.stats || {};
  const list = [
    { label: 'Active Tasks',      value: Number(s.tasks)   || 0, icon: 'ri-task-line',      tone: 'primary', href: '/app/admin/tasks', featured: true },
    { label: 'Samples Collected', value: Number(s.samples) || 0, icon: 'ri-test-tube-line', tone: 'success', href: '/app/admin/samples' },
  ];
  if (can('client_access')) {
    list.push({ label: 'Active Clients', value: Number(s.clients) || 0, icon: 'ri-building-line', tone: 'info', href: '/app/admin/clients' });
  }
  list.push({ label: 'Cars On Route', value: Number(s.cars) || 0, icon: 'ri-car-line', tone: 'warning', href: '/app/admin/cars' });
  return list;
});

// "TUESDAY · 24 JUNE 2026" for the hero eyebrow line.
const _d = new Date();
const todayUpper = `${_d.toLocaleDateString('en-US', { weekday: 'long' }).toUpperCase()} · ${_d.getDate()} ${_d.toLocaleDateString('en-US', { month: 'long' }).toUpperCase()} ${_d.getFullYear()}`;

// ---- Donut: samples by temperature ----
const PALETTE = ['#299cdb', '#3577f1', '#f7b84b', '#0ab39c', '#0d9488', '#BD6BA7'];
const donutTotal = computed(() => (props.samplesReport?.values || []).reduce((a, b) => a + Number(b || 0), 0));
const donutSeg = computed(() => {
  const labels = props.samplesReport?.labels || [];
  const values = props.samplesReport?.values || [];
  const total = donutTotal.value || 1;
  let acc = 0;
  return labels.map((label, i) => {
    const value = Number(values[i] || 0);
    const pct = (value / total) * 100;
    const seg = { label, value, pct, offset: acc, color: PALETTE[i % PALETTE.length] };
    acc += pct;
    return seg;
  });
});

// Animation: the ring "draws" in (prog 0→1) and the centre number counts up.
// Re-runs whenever the donut data changes (e.g. date-range partial reload).
const prog = ref(0);
const { display: donutDisplay } = useCounter(() => donutTotal.value);
const reduceMotion = typeof window !== 'undefined' && window.matchMedia
  && window.matchMedia('(prefers-reduced-motion: reduce)').matches;
function animateRing() {
  if (reduceMotion) { prog.value = 1; return; }
  prog.value = 0;
  const start = performance.now();
  const tick = (now) => {
    const p = Math.min(1, (now - start) / 900);
    prog.value = 1 - Math.pow(1 - p, 3); // easeOutCubic
    if (p < 1) requestAnimationFrame(tick);
    else prog.value = 1;
  };
  requestAnimationFrame(tick);
}
onMounted(animateRing);
watch(() => props.samplesReport, animateRing, { deep: true });

// ---- Top drivers ----
const drivers = computed(() => props.topDrivers || []);
const driversMax = computed(() => Math.max(1, ...drivers.value.map((d) => Number(d.total) || 0)));

// ---- Recent activity (client-scoped only) ----
const showActivity = computed(() => can('client_access'));

// ---- Date range → partial reload of the donut ----
const range = reactive({ from: props.range?.from || '', to: props.range?.to || '' });
const reloading = ref(false);
// Re-fetch the donut when BOTH bounds are set (the meaningful case) or when both
// are cleared (reset → show all). Avoids half-range reloads while the user is still
// picking the second date.
watch(() => [range.from, range.to], ([from, to]) => {
  if ((from && to) || (!from && !to)) {
    reloading.value = true;
    router.get('/app/dashboard', { from, to }, {
      only: ['samplesReport', 'range'],
      preserveState: true,
      preserveScroll: true,
      onFinish: () => { reloading.value = false; },
    });
  }
});

// Single range picker (mirrors the classic dashboard #daterange). Fetches only when
// both bounds are chosen; the @range event carries { from, to }.
const rangeStr = ref(props.range?.from && props.range?.to ? `${props.range.from} to ${props.range.to}` : '');
function onRange({ from, to }) { range.from = from; range.to = to; }
function clearRange() { rangeStr.value = ''; range.from = ''; range.to = ''; }
</script>

<template>
  <div>
    <!-- top controls: date-range filter (samples donut) + create task — ABOVE the hero -->
    <div class="flex flex-wrap items-end justify-end gap-2 mb-4">
      <div class="w-64"><FormDate v-model="rangeStr" mode="range" label="Date Range" placeholder="Select date range" @range="onRange" /></div>
      <button v-if="rangeStr" @click="clearRange"
        class="grid place-items-center w-10 h-11 rounded-xl text-slate-400 hover:text-danger hover:bg-danger/5 transition" title="Clear date range">
        <i class="ri-close-circle-line text-lg"></i>
      </button>
      <a href="/app/admin/tasks/create"><BaseButton variant="primary" icon="ri-add-line">Create Task</BaseButton></a>
    </div>

    <!-- greeting hero -->
    <div class="relative overflow-hidden rounded-2xl bg-gradient-to-br from-primary-700 via-primary-700 to-primary-500 text-white p-6 mb-5 shadow-card">
      <p class="text-[11px] uppercase tracking-[.2em] text-white/70">{{ todayUpper }}</p>
      <h1 class="text-2xl font-bold mt-1.5">{{ greeting }}{{ firstName ? ', ' + firstName : '' }} 👋</h1>
      <p class="text-sm text-white/80 mt-1">{{ (Number(stats.cars) || 0).toLocaleString() }} cars on route · cold-chain overview</p>
    </div>

    <!-- KPI row -->
    <div class="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-4 gap-4 mb-5">
      <div v-for="(k, i) in kpis" :key="k.label" :style="{ animationDelay: i * 70 + 'ms' }" class="animate-fade-in-up">
        <StatCard v-bind="k" />
      </div>
    </div>

    <!-- charts row -->
    <div class="grid grid-cols-1 xl:grid-cols-3 gap-5 mb-5">
      <!-- area chart (cosmetic) -->
      <BaseCard class="xl:col-span-2" title="Task Activity" subtitle="Operational overview" icon="ri-line-chart-line">
        <div class="flex gap-6 mb-4">
          <div>
            <div class="text-xs text-slate-400">Tasks</div>
            <div class="text-lg font-bold text-primary-700">{{ (Number(stats.tasks) || 0).toLocaleString() }}</div>
          </div>
          <div>
            <div class="text-xs text-slate-400">Samples</div>
            <div class="text-lg font-bold text-info">{{ (Number(stats.samples) || 0).toLocaleString() }}</div>
          </div>
          <div>
            <div class="text-xs text-slate-400">Cars</div>
            <div class="text-lg font-bold text-warning">{{ (Number(stats.cars) || 0).toLocaleString() }}</div>
          </div>
        </div>
        <VueApexCharts type="area" height="260" :options="areaOptions" :series="areaSeries" />
        <p class="text-[11px] text-slate-400 mt-1 text-center">Tasks created per month (last 12 months)</p>
      </BaseCard>

      <!-- donut -->
      <BaseCard title="Samples Temperature" subtitle="Distribution by range" icon="ri-temp-cold-line">
        <div v-if="donutTotal" class="flex flex-col items-center" :class="reloading ? 'opacity-60 transition-opacity' : ''">
          <div class="relative w-44 h-44">
            <svg viewBox="0 0 36 36" class="w-full h-full -rotate-90">
              <circle cx="18" cy="18" r="15.9155" fill="none" class="stroke-slate-100 dark:stroke-white/5" stroke-width="3.6" />
              <circle v-for="(s, i) in donutSeg" :key="i" cx="18" cy="18" r="15.9155" fill="none"
                :style="{ color: s.color }" stroke="currentColor" stroke-width="3.6"
                :stroke-dasharray="`${s.pct * prog} ${100 - s.pct * prog}`" :stroke-dashoffset="-(s.offset * prog)" stroke-linecap="round" />
            </svg>
            <div class="absolute inset-0 grid place-items-center">
              <div class="text-center">
                <div class="text-2xl font-bold text-ink dark:text-slate-50">{{ donutDisplay.toLocaleString() }}</div>
                <div class="text-[11px] text-slate-400">samples</div>
              </div>
            </div>
          </div>
          <div class="w-full mt-4 space-y-2">
            <div v-for="d in donutSeg" :key="d.label" class="flex items-center gap-2.5 text-sm">
              <span class="w-2.5 h-2.5 rounded-full shrink-0" :style="{ background: d.color }"></span>
              <span class="text-slate-600 dark:text-slate-300 flex-1 truncate">{{ d.label }}</span>
              <span class="font-semibold text-ink dark:text-slate-100">{{ Math.round(d.pct) }}%</span>
            </div>
          </div>
        </div>
        <EmptyState v-else icon="ri-test-tube-line" title="No samples" message="No samples found for the selected range." />
      </BaseCard>
    </div>

    <!-- bottom row -->
    <div class="grid gap-5" :class="showActivity ? 'grid-cols-1 xl:grid-cols-3' : 'grid-cols-1'">
      <!-- top drivers -->
      <BaseCard :class="showActivity ? 'xl:col-span-2' : ''" title="Top Drivers" subtitle="By completed tasks" icon="ri-user-star-line" :padded="false">
        <div v-if="drivers.length" class="divide-y divide-slate-100 dark:divide-white/5">
          <div v-for="(d, i) in drivers" :key="d.driver_id ?? d.name" class="flex items-center gap-3 px-5 py-3.5 hover:bg-surface-muted/50 dark:hover:bg-white/5 transition">
            <span class="grid place-items-center w-6 h-6 rounded-lg text-xs font-bold shrink-0"
              :class="i === 0 ? 'bg-amber-100 text-amber-600' : i === 1 ? 'bg-slate-100 text-slate-500' : i === 2 ? 'bg-orange-100 text-orange-600' : 'bg-surface-muted text-slate-400'">{{ i + 1 }}</span>
            <BaseAvatar :name="d.name || '—'" :size="36" />
            <span class="font-medium text-ink dark:text-slate-100 flex-1 truncate">{{ d.name }}</span>
            <div class="flex items-center gap-2 w-40">
              <div class="flex-1 h-2 rounded-full bg-surface-muted dark:bg-white/10 overflow-hidden">
                <div class="h-full rounded-full bg-gradient-to-r from-primary-400 to-primary-600 transition-all duration-700" :style="{ width: ((Number(d.total) || 0) / driversMax * 100) + '%' }"></div>
              </div>
              <span class="text-sm font-semibold text-ink dark:text-slate-200 tabular-nums w-9 text-end">{{ d.total }}</span>
            </div>
          </div>
        </div>
        <EmptyState v-else icon="ri-user-star-line" title="No drivers" message="No driver activity yet." />
      </BaseCard>

      <!-- recent activity rail (client-scoped only) -->
      <BaseCard v-if="showActivity" title="Recent Activity" icon="ri-pulse-line" :padded="false">
        <div v-if="notifications.length" class="max-h-[360px] overflow-y-auto px-5 py-2">
          <div v-for="(a, i) in notifications" :key="i" class="relative ps-5 pb-5 last:pb-2">
            <span class="absolute inset-inline-start-0 top-1.5 w-2.5 h-2.5 rounded-full bg-primary-500 ring-4 ring-primary-50 dark:ring-primary-500/10" style="inset-inline-start:0"></span>
            <span v-if="i < notifications.length - 1" class="absolute top-4 w-px h-full bg-slate-100 dark:bg-white/10" style="inset-inline-start:4px"></span>
            <p class="text-[13px] font-semibold text-ink dark:text-slate-100">{{ a.title }}</p>
            <p v-if="a.task_id" class="text-[11px] text-warning">Task #{{ a.task_id }}</p>
            <p class="text-xs text-slate-500 mt-0.5"><span class="text-slate-400">{{ a.from }}</span> → <span class="text-slate-400">{{ a.to }}</span></p>
            <div class="flex items-center gap-2 mt-1.5">
              <span v-if="a.driver" class="text-[11px] text-danger font-medium">{{ a.driver }}</span>
              <span class="text-[11px] text-slate-400 ms-auto">{{ a.time }}</span>
            </div>
          </div>
        </div>
        <EmptyState v-else icon="ri-notification-3-line" title="No activity" message="No recent notifications." />
      </BaseCard>
    </div>
  </div>
</template>
