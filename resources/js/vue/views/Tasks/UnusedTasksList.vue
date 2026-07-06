<script setup>
import { ref, reactive, computed } from 'vue';
import { router } from '@inertiajs/vue3';
import Breadcrumb from '../../components/Breadcrumb.vue';
import FilterBar from '../../components/FilterBar.vue';
import DataTable from '../../components/DataTable.vue';
import FormSelect from '../../components/FormSelect.vue';
import FormDate from '../../components/FormDate.vue';
import BaseAvatar from '../../components/BaseAvatar.vue';

const props = defineProps({
  rows:     { type: Array,  default: () => [] },
  total:    { type: Number, default: 0 },
  page:     { type: Number, default: 1 },
  pageSize: { type: Number, default: 25 },
  filters:  { type: Object, default: () => ({}) },
  options:  { type: Object, default: () => ({}) },
});

const DEFAULT_FILTERS = {
  client_id: '', driver_id: '', date_from: '', date_to: '',
  sort_by: '', sort_order: '',
};
const filters = reactive({ ...DEFAULT_FILTERS, ...props.filters });

const datePart = (s) => (s ? String(s).slice(0, 10) : '');
const dateRange = ref(filters.date_from ? `${datePart(filters.date_from)} to ${datePart(filters.date_to)}` : '');
function onDateRange({ from, to }) {
  filters.date_from = from || '';
  filters.date_to = to || '';
}

const sortByOpts = [
  { value: '',                label: '— Default —' },
  { value: 'created_at',      label: 'Creation Date' },
];
const sortOrderOpts = [
  { value: '',     label: '— Default —' },
  { value: 'desc', label: 'Descending' },
  { value: 'asc',  label: 'Ascending' },
];

const driverOpts = computed(() => props.options?.drivers || []);
const clientOpts = computed(() => props.options?.clients || []);
const rows = computed(() => props.rows || []);
const total = computed(() => props.total || 0);
const loading = ref(false);

const columns = [
  { key: 'sequence',          label: '#',                 sticky: 'start', width: '52px' },
  { key: 'id',                label: 'ID',                sticky: 'start', width: '80px' },
  { key: 'created_at',        label: 'Order Date',        ltr: true },
  { key: 'client',            label: 'Client',            wrap: true, width: '200px' },
  { key: 'driver_name',       label: 'Driver',            wrap: true, width: '200px' },
  { key: 'route',             label: 'From → To',        wrap: true, width: '300px' },
];

function reload(extra = {}) {
  loading.value = true;
  router.get('/app/admin/tasks/unused', { ...filters, ...extra }, {
    preserveState: true,
    preserveScroll: true,
    only: ['rows', 'total', 'page', 'pageSize', 'filters'],
    onFinish: () => { loading.value = false; },
  });
}
function doSearch() { reload({ page: 1 }); }
function doReset() { Object.assign(filters, DEFAULT_FILTERS); dateRange.value = ''; reload({ page: 1 }); }
function onQuery(q) { reload({ page: q.page, pageSize: q.pageSize }); }
</script>

<template>
  <div class="px-4 py-6 md:px-6 lg:px-8 max-w-[1600px] mx-auto space-y-6">
    <Breadcrumb title="Unused Tasks" :trail="[{ label: 'Tasks', href: '/app/admin/tasks' }, { label: 'Unused Tasks' }]" />

    <FilterBar :loading="loading" subtitle="filter unused tasks" @search="doSearch" @reset="doReset">
      <FormSelect v-model="filters.driver_id"      label="Driver"         :options="driverOpts"     placeholder="Select Driver" />
      <FormSelect v-model="filters.client_id"      label="Client" :options="clientOpts"     placeholder="Select Client" />
      <FormDate   v-model="dateRange" label="Date Range" mode="range" placeholder="Select range" @range="onDateRange" />
      <FormSelect v-model="filters.sort_by"        label="Sort By"        :options="sortByOpts"     :searchable="false" placeholder="Default order" />
      <FormSelect v-model="filters.sort_order"     label="Sort Order"     :options="sortOrderOpts"  :searchable="false" placeholder="Default" />
    </FilterBar>

    <DataTable
      :rows="rows"
      :columns="columns"
      :total="total"
      :server-side="true"
      :page="page"
      :page-size="pageSize"
      :loading="loading"
      @update="onQuery"
    >
      <template #cell-driver_name="{ value }">
        <div v-if="value" class="flex items-center gap-2">
          <BaseAvatar :name="value" :size="26" />
          <span class="text-[12.5px] font-medium text-ink dark:text-slate-200 whitespace-nowrap">{{ value }}</span>
        </div>
        <span v-else class="text-slate-400">—</span>
      </template>

      <template #cell-route="{ row }">
        <div class="flex items-center gap-2">
          <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-md bg-emerald-50 text-emerald-700 dark:bg-emerald-500/10 dark:text-emerald-400 text-[12.5px] font-bold truncate max-w-[130px] border border-emerald-100 dark:border-emerald-500/20" :title="row.from_location_name">
            <i class="ri-map-pin-fill text-red-500 text-[11px] shrink-0"></i>
            <span class="truncate">{{ row.from_location_name || '—' }}</span>
          </span>
          <i class="ri-arrow-right-line text-slate-400 shrink-0"></i>
          <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-md bg-sky-50 text-sky-700 dark:bg-sky-500/10 dark:text-sky-400 text-[12.5px] font-bold truncate max-w-[130px] border border-sky-100 dark:border-sky-500/20" :title="row.to_location_name">
            <i class="ri-map-pin-fill text-green-500 text-[11px] shrink-0"></i>
            <span class="truncate">{{ row.to_location_name || '—' }}</span>
          </span>
        </div>
      </template>

      <template #cell-id="{ value }">
        <span class="font-black text-[#0ab39c] dark:text-[#0ab39c]">#{{ value }}</span>
      </template>

      <template #cell-client="{ value }">
        <span class="font-extrabold text-slate-800 dark:text-white">{{ value || '—' }}</span>
      </template>

      <template #empty>
        <div class="py-12 flex flex-col items-center justify-center text-center">
          <div class="w-16 h-16 bg-slate-100 dark:bg-surface-dark-solid rounded-full flex items-center justify-center mb-4">
            <i class="ri-inbox-unarchive-line text-2xl text-slate-400"></i>
          </div>
          <h3 class="text-base font-bold text-slate-900 dark:text-white mb-1">No Unused Tasks Found</h3>
          <p class="text-sm text-slate-500 max-w-sm">Try adjusting your filters or date range.</p>
        </div>
      </template>
    </DataTable>
  </div>
</template>
