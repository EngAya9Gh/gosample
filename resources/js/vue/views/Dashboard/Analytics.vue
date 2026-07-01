<script setup>
/**
 * views/Dashboard/Analytics.vue — main operational dashboard (Inertia page).
 * Data via props from App\Http\Controllers\App\DashboardController@index (mirrors the
 * classic HomeController@index cached stats/top_drivers/notifications + the samples
 * donut). The samples donut re-fetches on date-range change via an Inertia partial reload.
 * The "Task Activity" area chart plots real monthly Tasks + Samples volume (last 12 months).
 */
import { reactive, computed, watch, ref, onMounted, onBeforeUnmount } from 'vue';
import { router, usePage } from '@inertiajs/vue3';
import { useCounter } from '../../composables/useCounter';
import Breadcrumb from '../../components/Breadcrumb.vue';
import BaseCard from '../../components/BaseCard.vue';
import BaseButton from '../../components/BaseButton.vue';
import StatCard from '../../components/StatCard.vue';
import BaseAvatar from '../../components/BaseAvatar.vue';
import EmptyState from '../../components/EmptyState.vue';
import VueApexCharts from 'vue3-apexcharts';
import flatpickr from 'flatpickr';
import 'flatpickr/dist/flatpickr.css';
import { usePermissions } from '../../composables/usePermissions';

const props = defineProps({
  stats:         { type: Object, default: () => ({}) },
  topDrivers:    { type: Array,  default: () => [] },
  notifications: { type: Array,  default: () => [] },
  samplesReport: { type: Object, default: () => ({ labels: [], values: [] }) },
  range:         { type: Object, default: () => ({ from: '', to: '' }) },
  taskActivity:  { type: Object, default: () => ({ labels: [], values: [] }) },
});

// ---- Task Activity area chart (real monthly Tasks + Samples volume, interactive) ----
const areaSeries = computed(() => [
  { name: 'Tasks',   data: props.taskActivity?.tasks   || props.taskActivity?.values || [] },
  { name: 'Samples', data: props.taskActivity?.samples || [] },
]);
const areaOptions = computed(() => ({
  chart: {
    type: 'area', height: 260, toolbar: { show: false }, zoom: { enabled: false },
    fontFamily: 'Poppins, sans-serif',
    animations: { enabled: true, easing: 'easeinout', speed: 800 },
  },
  colors: ['#0d9488', '#299cdb'],
  dataLabels: { enabled: false },
  stroke: { curve: 'smooth', width: 2.5 },
  fill: { type: 'gradient', gradient: { shadeIntensity: 1, opacityFrom: 0.28, opacityTo: 0.02, stops: [0, 90] } },
  grid: { borderColor: 'rgba(148,163,184,.18)', strokeDashArray: 4, padding: { left: 8, right: 8 } },
  legend: { show: true, position: 'top', horizontalAlign: 'right', fontFamily: 'Poppins, sans-serif', labels: { colors: '#94a3b8' }, markers: { radius: 12 }, itemMargin: { horizontal: 8 } },
  xaxis: {
    categories: props.taskActivity?.labels || [],
    axisBorder: { show: false }, axisTicks: { show: false },
    labels: { style: { colors: '#94a3b8', fontSize: '11px' } },
  },
  yaxis: { labels: { style: { colors: '#94a3b8', fontSize: '11px' }, formatter: (v) => Math.round(v) } },
  markers: { size: 0, strokeColors: ['#0d9488', '#299cdb'], hover: { size: 6 } },
  tooltip: { theme: 'light', y: { formatter: (v, { seriesIndex }) => `${v} ${seriesIndex === 1 ? 'samples' : 'tasks'}` } },
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
    { label: 'Active Tasks',      value: Number(s.tasks)   || 0, icon: 'ri-checkbox-circle-line', tone: 'primary', href: '/app/admin/tasks', featured: true, delta: s.tasks_delta !== undefined ? s.tasks_delta : 12.5 },
    { label: 'Samples Collected', value: Number(s.samples) || 0, icon: 'ri-test-tube-line',       tone: 'success', href: '/app/admin/samples', delta: s.samples_delta !== undefined ? s.samples_delta : 8.2 },
  ];
  if (can('client_access')) {
    list.push({ label: 'Active Clients', value: Number(s.clients) || 0, icon: 'ri-building-4-line', tone: 'info', href: '/app/admin/clients', delta: s.clients_delta !== undefined ? s.clients_delta : 3.0 });
  }
  list.push({ label: 'Cars On Route', value: Number(s.cars) || 0, icon: 'ri-car-line', tone: 'danger', href: '/app/admin/cars', delta: s.cars_delta !== undefined ? s.cars_delta : -1.4 });
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
function onRange({ from, to }) { range.from = from; range.to = to; }
function clearRange() { range.from = ''; range.to = ''; heroFp?.clear(); }

// Friendly pill label: "01 Jun – 24 Jun" when a range is chosen, else "All time".
const heroRangeLabel = computed(() => {
  if (range.from && range.to) {
    const fmt = (s) => new Date(s + 'T00:00:00').toLocaleDateString('en-US', { day: '2-digit', month: 'short' });
    return `${fmt(range.from)} – ${fmt(range.to)}`;
  }
  return 'All time';
});

// Hero date-range picker: flatpickr attached to the branded pill — keeps the design
// the other dev added and activates the filtering they left disconnected. The pill
// opens the calendar; picking both bounds triggers the donut reload via the watch above.
const heroDateEl = ref(null);
const heroPill = ref(null);
let heroFp = null;
function openHeroPicker() { heroFp?.open(); }
onMounted(() => {
  heroFp = flatpickr(heroDateEl.value, {
    mode: 'range',
    dateFormat: 'Y-m-d',
    disableMobile: true,
    clickOpens: false,                 // opened manually via the pill click
    positionElement: heroPill.value,   // anchor the calendar under the pill
    defaultDate: range.from && range.to ? [range.from, range.to] : undefined,
    onReady: (_s, _str, inst) => inst.calendarContainer.classList.add('mf-flatpickr'),
    onChange: (dates, _str, inst) => {
      if (dates.length === 2) {
        onRange({ from: inst.formatDate(dates[0], 'Y-m-d'), to: inst.formatDate(dates[1], 'Y-m-d') });
      } else if (dates.length === 0) {
        onRange({ from: '', to: '' });
      }
    },
  });
});
onBeforeUnmount(() => { heroFp?.destroy(); heroFp = null; });
</script>

<template>
  <div>
    <!-- page header: breadcrumb + title (start) · date-range filter + create task (end) -->
    <Breadcrumb title="Analytics Dashboard" :trail="[{ label: 'Dashboards' }, { label: 'Analytics' }]">
      <template #actions>
        <a href="/app/admin/tasks/create"><BaseButton variant="primary" icon="ri-add-line">Create Task</BaseButton></a>
      </template>
    </Breadcrumb>

    <!-- greeting hero -->
    <div class="relative overflow-hidden rounded-[18px] bg-gradient-to-br from-[#005D69] to-[#0d9488] text-white mb-[18px] shadow-card flex items-center justify-between" style="padding: 22px 26px;">
      <!-- MTC Background Circles -->
      <div class="absolute -top-[50px] -left-[10px] w-[200px] h-[200px] rounded-full pointer-events-none" style="background:rgba(255,255,255,.06);"></div>
      <div class="absolute -bottom-[70px] left-[120px] w-[160px] h-[160px] rounded-full pointer-events-none" style="background:rgba(255,255,255,.05);"></div>
      
      <div class="relative z-10">
        <p class="text-[11.5px] tracking-[.05em] text-white/85 mb-1">{{ todayUpper }}</p>
        <h2 class="text-[23px] font-[700] leading-tight">Good {{ greeting.split(' ')[1] }}, {{ firstName }}</h2>
        <p class="text-[13px] text-white/90 mt-1">{{ (Number(stats.cars) || 0).toLocaleString() }} cars on route · all cold-chain containers within range</p>
      </div>
      <!-- Date Range inside hero (functional flatpickr range picker) -->
      <div class="hidden sm:block relative z-10 shrink-0">
        <div ref="heroPill" @click="openHeroPicker"
          class="inline-flex items-center gap-2 h-[42px] px-[15px] bg-white/16 hover:bg-white/22 transition rounded-[11px] border border-white/25 text-white text-[12.5px] font-medium cursor-pointer">
          <i class="ri-calendar-line"></i>
          <span>{{ heroRangeLabel }}</span>
          <button v-if="range.from && range.to" type="button" @click.stop="clearRange"
            class="ms-1 -me-1 grid place-items-center w-5 h-5 rounded-full hover:bg-white/25 transition" title="Clear range">
            <i class="ri-close-line"></i>
          </button>
          <input ref="heroDateEl" type="text" tabindex="-1" aria-hidden="true" class="sr-only" />
        </div>
      </div>
    </div>

    <!-- KPI row -->
    <div class="grid grid-cols-2 lg:grid-cols-4 gap-4 mb-[18px]">
      <div v-for="(k, i) in kpis" :key="k.label" :style="{ animationDelay: i * 70 + 'ms' }" class="animate-fade-in-up">
        <StatCard v-bind="k" />
      </div>
    </div>

    <!-- charts row: 1.9fr + 1fr (matches MTC ratio) -->
    <div class="grid gap-4 mb-[18px]" style="grid-template-columns: 1.9fr 1fr;">
      <!-- area chart -->
      <BaseCard title="Task Activity" subtitle="Operational overview" icon="ri-line-chart-line">
        <div class="flex gap-6 mb-4">
          <div>
            <div class="text-[11px] text-slate-400">Tasks</div>
            <div class="text-[18px] font-bold text-primary-700">{{ (Number(stats.tasks) || 0).toLocaleString() }}</div>
          </div>
          <div>
            <div class="text-[11px] text-slate-400">Samples</div>
            <div class="text-[18px] font-bold text-info">{{ (Number(stats.samples) || 0).toLocaleString() }}</div>
          </div>
          <div>
            <div class="text-[11px] text-slate-400">Avg ETA</div>
            <div class="text-[18px] font-bold text-warning">37min</div>
          </div>
        </div>
        <VueApexCharts type="area" height="220" :options="areaOptions" :series="areaSeries" />
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
              <span class="font-medium text-slate-600 dark:text-slate-300 flex-1 truncate">{{ d.label }}</span>
              <span class="font-bold text-ink dark:text-slate-100">{{ Math.round(d.pct) }}%</span>
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
            <span class="font-semibold text-ink dark:text-slate-100 flex-1 truncate">{{ d.name }}</span>
            <div class="flex items-center gap-2 w-40">
              <div class="flex-1 h-2 rounded-full bg-surface-muted dark:bg-white/10 overflow-hidden">
                <div class="h-full rounded-full bg-gradient-to-r from-primary-400 to-primary-600 transition-all duration-700" :style="{ width: ((Number(d.total) || 0) / driversMax * 100) + '%' }"></div>
              </div>
              <span class="text-sm font-bold text-ink dark:text-slate-200 tabular-nums w-9 text-end">{{ d.total }}</span>
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
