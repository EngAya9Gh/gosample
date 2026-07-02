<script setup>
/**
 * /app/tasks-dashboard — Tasks / Clients bar chart + compact filter row.
 * Logic mirrors the classic /tasks-dashboard 1:1: same filters (driver,
 * from/to location, date range — applied only when BOTH bounds are set)
 * and the same three series (Tasks / Closed / Pending per billing client).
 * Layout follows the MTC System reference design: a single-row filter card
 * and one "Tasks / Clients" chart card with value labels above the bars.
 */
import { reactive, ref, computed } from 'vue';
import { router } from '@inertiajs/vue3';
import VueApexCharts from 'vue3-apexcharts';
import Breadcrumb from '../../components/Breadcrumb.vue';
import BaseCard from '../../components/BaseCard.vue';
import BaseButton from '../../components/BaseButton.vue';
import FormSelect from '../../components/FormSelect.vue';
import FormDate from '../../components/FormDate.vue';
import EmptyState from '../../components/EmptyState.vue';

const props = defineProps({
  categories: { type: Array, default: () => [] },
  totals:     { type: Array, default: () => [] },
  closed:     { type: Array, default: () => [] },
  pending:    { type: Array, default: () => [] },
  drivers:    { type: Array, default: () => [] },
  locations:  { type: Array, default: () => [] },
  filters:    { type: Object, default: () => ({}) },
});

/* ---------- filters (echoed back by the controller so state survives reloads) ---------- */
const filters = reactive({
  driver_id:     props.filters?.driver_id || '',
  from_location: props.filters?.from_location || '',
  to_location:   props.filters?.to_location || '',
  date_from:     props.filters?.date_from || '',
  date_to:       props.filters?.date_to || '',
});
const dateRange = ref(filters.date_from && filters.date_to ? `${filters.date_from} to ${filters.date_to}` : '');
function onDateRange({ from, to }) { filters.date_from = from; filters.date_to = to; }

const driverOpts   = computed(() => props.drivers.map((d) => ({ value: String(d.id), label: d.name })));
const locationOpts = computed(() => props.locations.map((l) => ({ value: String(l.id), label: l.name })));

const loading = ref(false);
function doSearch() {
  loading.value = true;
  const query = Object.fromEntries(Object.entries(filters).filter(([, v]) => v !== '' && v != null));
  router.get('/app/tasks-dashboard', query, {
    preserveState: true,
    preserveScroll: true,
    onFinish: () => { loading.value = false; },
  });
}

/* ---------- chart — the same three series as the classic page ---------- */
const series = computed(() => [
  { name: 'Tasks',         data: props.totals },
  { name: 'Closed Tasks',  data: props.closed },
  { name: 'Pending Tasks', data: props.pending },
]);

// Value labels above the bars (per the reference design) only while they stay readable.
const showValues = computed(() => props.categories.length > 0 && props.categories.length <= 12);

const chartOptions = computed(() => ({
  chart: { type: 'bar', height: 380, toolbar: { show: false }, background: 'transparent' },
  colors: ['#005D69', '#0ab39c', '#f7b84b'],
  plotOptions: { bar: { columnWidth: '60%', borderRadius: 5, borderRadiusApplication: 'end', dataLabels: { position: 'top' } } },
  dataLabels: showValues.value
    ? { enabled: true, offsetY: -18, style: { fontSize: '11px', fontWeight: 700, colors: ['#64748b'] } }
    : { enabled: false },
  stroke: { show: true, width: 2, colors: ['transparent'] },
  xaxis: {
    categories: props.categories,
    labels: { rotate: -45, rotateAlways: props.categories.length > 8, trim: true, hideOverlappingLabels: true, style: { colors: '#94a3b8', fontSize: '11px' } },
  },
  yaxis: { labels: { style: { colors: '#94a3b8' } } },
  grid: { borderColor: 'rgba(148, 163, 184, 0.1)', strokeDashArray: 4 },
  legend: { position: 'bottom', labels: { colors: '#94a3b8' } },
  theme: { mode: 'light' },
}));

</script>

<template>
  <div>
    <Breadcrumb title="Tasks Dashboard" :trail="[{ label: 'Dashboards' }, { label: 'Tasks / Clients' }]" />

    <!-- compact single-row filter card (reference design) -->
    <BaseCard class="mb-5">
      <div class="flex flex-wrap items-center gap-3">
        <div class="flex-1 min-w-[160px]">
          <FormSelect v-model="filters.driver_id" :options="driverOpts" placeholder="All drivers" />
        </div>
        <div class="flex-1 min-w-[160px]">
          <FormSelect v-model="filters.from_location" :options="locationOpts" placeholder="From location" icon="ri-map-pin-line" icon-class="text-danger" />
        </div>
        <div class="flex-1 min-w-[160px]">
          <FormSelect v-model="filters.to_location" :options="locationOpts" placeholder="To location" icon="ri-map-pin-fill" icon-class="text-success" />
        </div>
        <div class="flex-1 min-w-[180px]">
          <FormDate v-model="dateRange" mode="range" placeholder="Date range" @range="onDateRange" />
        </div>
        <BaseButton variant="primary" icon="ri-search-line" :loading="loading" @click="doSearch">Search</BaseButton>
      </div>
    </BaseCard>

    <!-- Tasks / Clients chart -->
    <BaseCard title="Tasks / Clients" subtitle="Task volume per billing client in the selected range" icon="ri-bar-chart-2-line">
      <VueApexCharts v-if="categories.length" type="bar" height="380" :options="chartOptions" :series="series" />
      <EmptyState v-else icon="ri-bar-chart-2-line" title="No tasks" message="No tasks found for the selected filters." />
    </BaseCard>
  </div>
</template>
