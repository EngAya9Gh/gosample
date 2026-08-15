<script setup>
/**
 * views/DailyOperation/DailyOperationList.vue — master daily operations table (Inertia page).
 * Data arrives as props from App\Http\Controllers\App\DailyOperationController@index.
 * Designed purely for reporting with no action buttons (View Only), utilizing a background job for export.
 */
import { ref, reactive, computed } from 'vue';
import { router } from '@inertiajs/vue3';
import Breadcrumb from '../../components/Breadcrumb.vue';
import FilterBar from '../../components/FilterBar.vue';
import DataTable from '../../components/DataTable.vue';
import FormInput from '../../components/FormInput.vue';
import FormSelect from '../../components/FormSelect.vue';
import FormDate from '../../components/FormDate.vue';
import StatusBadge from '../../components/StatusBadge.vue';
import BaseButton from '../../components/BaseButton.vue';
import BaseAvatar from '../../components/BaseAvatar.vue';
import RouteCell from '../../components/RouteCell.vue';
import { useToast } from '../../composables/useToast';
import axios from 'axios';

const props = defineProps({
  rows:     { type: Array,  default: () => [] },
  total:    { type: Number, default: 0 },
  page:     { type: Number, default: 1 },
  pageSize: { type: Number, default: 25 },
  filters:  { type: Object, default: () => ({}) },
  options:  { type: Object, default: () => ({}) },
});

const { push } = useToast();
const csrf = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';

const DEFAULT_FILTERS = {
  keyword: '', status: '', delayed_reason: '', driver_id: '', billing_client: '',
  from_location: '', to_location: '', date_from: '', date_to: '',
  search_date: '', sort_by: '', sort_order: '',
};
const filters = reactive({ ...DEFAULT_FILTERS, ...props.filters });

const datePart = (s) => (s ? String(s).slice(0, 10) : '');
const dateRange = ref(filters.date_from ? `${datePart(filters.date_from)} to ${datePart(filters.date_to)}` : '');
function onDateRange({ from, to }) {
  filters.date_from = from || '';
  filters.date_to = to || '';
}

const statusPills = [
  { value: 'NEW',         label: 'New',           dot: 'bg-status-new',       active: 'bg-status-new/10 border-status-new/40 text-status-new' },
  { value: 'COLLECTED',   label: 'Collected',     dot: 'bg-status-collected', active: 'bg-status-collected/10 border-status-collected/40 text-status-collected' },
  { value: 'IN_FREEZER',  label: 'In Container',  dot: 'bg-status-container', active: 'bg-status-container/10 border-status-container/40 text-status-container' },
  { value: 'OUT_FREEZER', label: 'Out Container', dot: 'bg-status-container', active: 'bg-status-container/10 border-status-container/40 text-status-container' },
  { value: 'CLOSED',      label: 'Closed',        dot: 'bg-status-closed',    active: 'bg-status-closed/10 border-status-closed/40 text-status-closed' },
  { value: 'NO_SAMPLES',  label: 'No Samples',    dot: 'bg-status-none',      active: 'bg-status-none/15 border-status-none/40 text-status-none' },
];

function toggleStatus(v) {
  filters.status = filters.status === v ? '' : v;
  reload({ page: 1 });
}

const searchDateOpts = [
  { value: '',                label: '— Any —' },
  { value: 'collection_date', label: 'Collection Date' },
  { value: 'created_at',      label: 'Creation Date' },
];
const sortByOpts = [
  { value: '',                label: '— Default —' },
  { value: 'created_at',      label: 'Creation Date' },
  { value: 'updated_at',      label: 'Update Date' },
  { value: 'collection_date', label: 'Collection Date' },
];
const sortOrderOpts = [
  { value: '',     label: '— Default —' },
  { value: 'desc', label: 'Descending' },
  { value: 'asc',  label: 'Ascending' },
];

const delayReasonOpts = [
  { value: 'Accident', label: 'Accident' },
  { value: 'Car issues', label: 'Car issues' },
  { value: 'Check out point limit time', label: 'Check out point limit time' },
  { value: 'Driver App Issue', label: 'Driver App Issue' },
  { value: 'Heavy Traffic', label: 'Heavy Traffic' },
  { value: 'Other', label: 'Other' },
];

const driverOpts = computed(() => props.options?.drivers || []);
const clientOpts = computed(() => props.options?.clients || []);
const locationOpts = computed(() => props.options?.locations || []);
const rows = computed(() => props.rows || []);
const total = computed(() => props.total || 0);
const loading = ref(false);

const columns = [
  { key: 'id',                         label: 'ID',        sortable: false, width: '80px' },
  { key: 'client',                     label: 'Client',    sortable: false, width: '160px' },
  { key: 'route',                      label: 'Route (From → To)', sortable: false, wrap: true, width: '220px' },
  { key: 'driver_name',                label: 'Driver',    sortable: false, width: '150px' },
  { key: 'car_plate',                  label: 'Car Plate', sortable: false, width: '120px' },
  { key: 'status',                     label: 'Status',    sortable: false, width: '130px' },
  { key: 'from_location_arrival_time', label: 'Arrival Time', sortable: false, width: '120px' },
  { key: 'close_date',                 label: 'Close Date', sortable: false, width: '120px' },
  { key: 'hours',                      label: 'Duration',  sortable: false, width: '140px' },
  { key: 'collection_date',            label: 'Collection', sortable: false, width: '120px' },
  { key: 'freezer_date',               label: 'Freezer In', sortable: false, width: '120px' },
  { key: 'freezer_out_date',           label: 'Freezer Out', sortable: false, width: '120px' },
];

function reload(overrides = {}) {
  loading.value = true;
  const p = {
    page: props.page, pageSize: props.pageSize, ...filters, ...overrides,
  };
  Object.keys(p).forEach((k) => { if (!p[k] && p[k] !== 0) delete p[k]; });
  router.get('/app/daily-operation', p, {
    preserveState: true, replace: true, only: ['rows', 'total', 'page', 'pageSize', 'filters'],
    onFinish: () => { loading.value = false; }
  });
}

function doSearch() { reload({ page: 1 }); }
function doReset() {
  Object.assign(filters, DEFAULT_FILTERS);
  dateRange.value = '';
  reload({ page: 1, sort_by: '', sort_order: '' });
}
function onQuery({ page, pageSize, q }) { 
  if (q !== undefined) filters.keyword = q;
  reload({ page, pageSize }); 
}

const isExporting = ref(false);

async function checkExportStatus(token) {
  try {
    const res = await axios.get(`/app/daily-operation/export/status/${token}`);
    const data = res.data;
    
    if (data.status === 'ready') {
      isExporting.value = false;
      push({ type: 'success', title: 'Export Ready', message: 'Downloading your file automatically...' });
      window.location.href = data.download_url;
    } else if (data.status === 'error') {
      isExporting.value = false;
      push({ type: 'error', title: 'Export Failed', message: data.message || 'An error occurred during export.' });
    } else {
      // Still processing, poll again in 2 seconds
      setTimeout(() => checkExportStatus(token), 2000);
    }
  } catch (err) {
    isExporting.value = false;
    push({ type: 'error', title: 'Export Error', message: 'Failed to check export status.' });
  }
}

async function handleExportStart() {
  if (isExporting.value) return;
  isExporting.value = true;
  push({ type: 'info', title: 'Export Started', message: 'Your file is being generated in the background...' });

  try {
    const res = await axios.post('/app/daily-operation/export', filters);
    if (res.data.success && res.data.token) {
      setTimeout(() => checkExportStatus(res.data.token), 2000);
    } else {
      isExporting.value = false;
      push({ type: 'error', title: 'Export Failed', message: 'Could not start the export process.' });
    }
  } catch (err) {
    isExporting.value = false;
    push({ type: 'error', title: 'Export Error', message: 'An error occurred while starting the export.' });
  }
}

function onExport(type) {
  if (type === 'excel' || type === 'csv') {
    handleExportStart();
  } else if (type === 'copy') {
    const text = props.rows.map(r => Object.values(r).join('\t')).join('\n');
    navigator.clipboard.writeText(text);
    push({ type: 'success', title: 'Copied', message: 'Rows copied to clipboard' });
  } else if (type === 'print') {
    const w = window.open('', '_blank');
    w.document.write('<html dir="rtl"><head><title>Print Report</title></head><body style="font-family:sans-serif; padding:20px;"><h2>Daily Operation Report</h2><table border="1" style="width:100%; border-collapse:collapse; text-align:right;">');
    
    // Header
    w.document.write('<tr>');
    columns.forEach(c => w.document.write(`<th style="padding:8px; background:#f0f0f0;">${c.label}</th>`));
    w.document.write('</tr>');
    
    // Body
    props.rows.forEach(r => {
      w.document.write('<tr>');
      columns.forEach(c => w.document.write(`<td style="padding:8px;">${r[c.key] || ''}</td>`));
      w.document.write('</tr>');
    });

    w.document.write('</table></body></html>');
    w.document.close(); 
    w.focus();
    setTimeout(() => w.print(), 500);
  }
}
</script>

<template>
  <div class="px-4 py-6 md:px-6 lg:px-8 max-w-7xl mx-auto space-y-6">
    <Breadcrumb title="Operation Report" :trail="[{ label: 'Dashboards', href: '/dashboard' }, { label: 'Operation Report' }]">
      <template #actions>
        <BaseButton variant="primary" icon="ri-file-excel-2-line" @click="handleExportStart" :disabled="isExporting">
          <template v-if="isExporting">
            <i class="ri-loader-4-line animate-spin me-2"></i> Exporting...
          </template>
          <template v-else>
            Export Excel
          </template>
        </BaseButton>
      </template>
    </Breadcrumb>

    <FilterBar :loading="loading" subtitle="refine the operation report" @search="doSearch" @reset="doReset">
      <FormInput  v-model="filters.keyword"        label="Task ID"        placeholder="Task ID" icon="ri-search-line" />
      <FormSelect v-model="filters.driver_id"      label="Driver"         :options="driverOpts"     placeholder="Select Driver" />
      <FormSelect v-model="filters.billing_client" label="Client"         :options="clientOpts"     placeholder="Select Client" />
      <FormSelect v-model="filters.from_location"  label="From Location"  :options="locationOpts"   placeholder="Select Location" />
      <FormSelect v-model="filters.to_location"    label="To Location"    :options="locationOpts"   placeholder="Select Location" />
      <FormSelect v-model="filters.delayed_reason" label="Delay Reason"   :options="delayReasonOpts" placeholder="Any Reason" />
      <FormDate   v-model="dateRange" label="Date Range" mode="range" placeholder="Select range" @range="onDateRange" />
      <FormSelect v-model="filters.search_date"    label="Search Date"    :options="searchDateOpts" :searchable="false" placeholder="Search by date" />
      <FormSelect v-model="filters.sort_by"        label="Sort By"        :options="sortByOpts"     :searchable="false" placeholder="Default order" />
      <FormSelect v-model="filters.sort_order"     label="Sort Order"     :options="sortOrderOpts"  :searchable="false" placeholder="Default" />

      <template #actions-extra>
        <button
          v-for="s in statusPills" :key="s.value" type="button"
          @click="toggleStatus(s.value)"
          class="inline-flex items-center gap-1.5 ps-2 pe-2.5 h-7 rounded-full border text-[11px] font-bold transition"
          :class="filters.status === s.value
            ? s.active
            : 'bg-surface dark:bg-white/5 border-slate-200 dark:border-white/10 text-slate-500 dark:text-slate-400 hover:border-slate-300'"
        >
          <span class="w-1.5 h-1.5 rounded-full" :class="s.dot"></span>
          {{ s.label }}
        </button>
      </template>
    </FilterBar>

    <div class="overflow-x-auto">
      <DataTable
        title="Operations Log"
        :columns="columns" :rows="rows" row-key="id"
        :loading="loading" :server-side="true" :total="total" :searchable="false"
        @query="onQuery"
        @export="onExport"
      >
        <template #cell-id="{ value }">
          <span class="font-black text-primary-500 dark:text-primary-300">#{{ value }}</span>
        </template>
        <template #cell-client="{ value }">
          <span class="font-semibold text-ink dark:text-slate-100 whitespace-normal leading-snug">{{ value || '—' }}</span>
        </template>
        <template #cell-route="{ row }">
          <RouteCell :from="row.from_location_name" :to="row.to_location_name" />
        </template>
        <template #cell-driver_name="{ value }">
          <span v-if="value" class="inline-flex items-center gap-1.5">
            <BaseAvatar :name="value" :size="22" class="-mt-px" /><span class="font-medium whitespace-normal leading-snug">{{ value }}</span>
          </span>
          <span v-else class="text-slate-400">—</span>
        </template>
        <template #cell-car_plate="{ value }">
          <span v-if="value" class="font-mono bg-surface-muted dark:bg-white/5 border border-slate-200 dark:border-white/10 px-2 py-0.5 rounded text-xs">
            {{ value }}
          </span>
          <span v-else class="text-slate-400">—</span>
        </template>
        <template #cell-status="{ value }">
          <StatusBadge :status="value" />
        </template>
        <template #cell-hours="{ value }">
          <span v-if="value" class="font-bold text-amber-600 dark:text-amber-500">{{ value }}</span>
          <span v-else class="text-slate-400">—</span>
        </template>
        
        <template #cell-from_location_arrival_time="{ value }">
          <span v-if="value" class="text-slate-600 dark:text-slate-300">{{ value }}</span>
          <span v-else class="text-slate-400">—</span>
        </template>
        <template #cell-close_date="{ value }">
          <span v-if="value" class="text-slate-600 dark:text-slate-300">{{ value }}</span>
          <span v-else class="text-slate-400">—</span>
        </template>
        <template #cell-collection_date="{ value }">
          <span v-if="value" class="text-slate-600 dark:text-slate-300">{{ value }}</span>
          <span v-else class="text-slate-400">—</span>
        </template>
        <template #cell-freezer_date="{ value }">
          <span v-if="value" class="text-slate-600 dark:text-slate-300">{{ value }}</span>
          <span v-else class="text-slate-400">—</span>
        </template>
        <template #cell-freezer_out_date="{ value }">
          <span v-if="value" class="text-slate-600 dark:text-slate-300">{{ value }}</span>
          <span v-else class="text-slate-400">—</span>
        </template>

      </DataTable>
    </div>
  </div>
</template>
