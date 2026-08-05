<script setup>
import { ref, computed, reactive, watch } from 'vue';
import { router } from '@inertiajs/vue3';
import DataTable from '../../components/DataTable.vue';
import BaseButton from '../../components/BaseButton.vue';
import BaseAvatar from '../../components/BaseAvatar.vue';
import RouteCell from '../../components/RouteCell.vue';
import StatusBadge from '../../components/StatusBadge.vue';
import FormSelect from '../../components/FormSelect.vue';
import FormDate from '../../components/FormDate.vue';
import TabGroup from '../../components/TabGroup.vue';
import { usePermissions } from '../../composables/usePermissions';
import { useToast } from '../../composables/useToast';

const { can } = usePermissions();
const { push } = useToast();

const props = defineProps({
  rows: Array,
  total: Number,
  page: Number,
  pageSize: Number,
  filters: Object,
  drivers: Array,
  locations: Array,
  carriers: Array,
});

const DEFAULT_FILTERS = {
  keyword: '', status: '', carrier: '', driver_id: '', from_location: '', to_location: '',
  date_from: '', date_to: '', sort_by: '', sort_order: '',
};

const filters = reactive({ ...DEFAULT_FILTERS, ...props.filters });

const statusTabs = [
  { key: '',           label: 'All Statuses' },
  { key: 'Assigned',   label: 'Assigned',   activeClass: 'bg-danger text-white dark:bg-danger/90' },
  { key: 'confirmed',  label: 'Confirmed',  activeClass: 'bg-info text-white dark:bg-cyan-600' },
  { key: 'dispatched', label: 'Dispatched', activeClass: 'bg-warning text-white dark:bg-amber-600' },
  { key: 'delivered',  label: 'Delivered',  activeClass: 'bg-success text-white dark:bg-emerald-600' },
];

const carrierOpts = computed(() => [{ value: '', label: 'All Carriers' }, ...(props.carriers || [])]);
const driverOpts = computed(() => [{ value: '', label: 'All Drivers' }, ...(props.drivers || [])]);
const locOpts = computed(() => [{ value: '', label: 'All Locations' }, ...(props.locations || [])]);

const dateRange = ref(
  filters.date_from && filters.date_to
    ? `${filters.date_from} to ${filters.date_to}`
    : ''
);

watch(dateRange, (val) => {
  if (!val) {
    filters.date_from = '';
    filters.date_to = '';
  } else if (val.includes(' to ')) {
    const [from, to] = val.split(' to ');
    filters.date_from = from;
    filters.date_to = to;
  }
  doSearch();
});

const rows = computed(() => props.rows || []);
const total = computed(() => props.total || 0);
const loading = ref(false);
const showAdvanced = ref(false);

const columns = [
  { key: 'sequence',          label: '#',                 width: '60px' },
  { key: 'id',                label: 'ID',                width: '80px' },
  { key: 'reference_number',  label: 'Reference',         width: '120px' },
  { key: 'carrier',           label: 'Carrier',           width: '120px' },
  { key: 'route',             label: 'Route (From → To)', width: '280px', wrap: true },
  { key: 'driver_name',       label: 'Driver',            width: '160px' },
  { key: 'pickup_otp',        label: 'Pickup OTP',        width: '110px' },
  { key: 'dropoff_otp',       label: 'Dropoff OTP',       width: '110px' },
  { key: 'status_code',       label: 'Status',            width: '120px' },
  { key: 'batch',             label: 'Batch',             width: '100px' },
  { key: 'journey_type',      label: 'Journey',           width: '100px' },
  { key: 'sla_code',          label: 'SLA',               width: '90px' },
  { key: 'created_at',        label: 'Created At',        width: '140px' },
  { key: 'actions',           label: 'Actions',           sticky: 'end', width: '80px', align: 'center' },
];

function reload(extra = {}) {
  loading.value = true;
  router.get('/admin/shipments', { ...filters, ...extra }, {
    preserveState: true,
    preserveScroll: true,
    onFinish: () => (loading.value = false),
  });
}

function doSearch() {
  filters.page = 1;
  reload();
}

function doReset() {
  Object.assign(filters, DEFAULT_FILTERS);
  dateRange.value = '';
  reload();
}

function onQuery({ page, pageSize, sortBy, sortOrder }) {
  filters.page = page;
  filters.pageSize = pageSize;
  filters.sort_by = sortBy;
  filters.sort_order = sortOrder;
  reload();
}

/* ---------- DataTable toolbar exports (Copy / CSV / Excel / Print) ---------- */
const exportColumns = columns.filter((c) => c.key !== 'actions');
function cellText(r, c) {
  if (c.key === 'route') return `${r.from_location_name || ''} → ${r.to_location_name || ''}`;
  return r[c.key] == null ? '' : String(r[c.key]);
}
function matrix() {
  const header = exportColumns.map((c) => c.label);
  const body = rows.value.map((r) => exportColumns.map((c) => cellText(r, c)));
  return { header, body };
}
function onExport(kind) {
  const { header, body } = matrix();
  if (!body.length) { push({ type: 'info', title: 'Nothing to export', message: 'No shipments in the current view.' }); return; }

  if (kind === 'copy') {
    navigator.clipboard?.writeText([header.join('\t'), ...body.map((r) => r.join('\t'))].join('\n'));
    push({ type: 'success', title: 'Copied', message: `${body.length} rows copied to clipboard` });
  } else if (kind === 'csv') {
    const esc = (s) => `"${String(s).replace(/"/g, '""')}"`;
    const csv = [header.map(esc).join(','), ...body.map((r) => r.map(esc).join(','))].join('\n');
    const a = document.createElement('a');
    a.href = URL.createObjectURL(new Blob(['﻿' + csv], { type: 'text/csv;charset=utf-8' }));
    a.download = 'shipments.csv';
    a.click();
    URL.revokeObjectURL(a.href);
  } else if (kind === 'excel') {
    const th = header.map((h) => `<th>${h}</th>`).join('');
    const tr = body.map((r) => `<tr>${r.map((c) => `<td>${c}</td>`).join('')}</tr>`).join('');
    const html = `<html xmlns:x="urn:schemas-microsoft-com:office:excel"><head><meta charset="utf-8"></head><body><table border="1"><thead><tr>${th}</tr></thead><tbody>${tr}</tbody></table></body></html>`;
    const a = document.createElement('a');
    a.href = URL.createObjectURL(new Blob(['﻿' + html], { type: 'application/vnd.ms-excel' }));
    a.download = 'shipments.xls';
    a.click();
    URL.revokeObjectURL(a.href);
  } else if (kind === 'print') {
    const w = window.open('', '_blank');
    const th = header.map((h) => `<th>${h}</th>`).join('');
    const tr = body.map((r) => `<tr>${r.map((c) => `<td>${c}</td>`).join('')}</tr>`).join('');
    w.document.write(`<html dir="${document.documentElement.dir}"><head><title>Shipments</title><style>table{border-collapse:collapse;width:100%;font-family:Poppins,sans-serif;font-size:12px}th,td{border:1px solid #cbd5e1;padding:6px 8px;text-align:start}th{background:#005D69;color:#fff}</style></head><body><h3>Shipments</h3><table><thead><tr>${th}</tr></thead><tbody>${tr}</tbody></table></body></html>`);
    w.document.close(); w.focus(); w.print();
  }
}
</script>

<template>
  <div class="px-4 py-6 md:px-6 lg:px-8 max-w-screen-2xl mx-auto">
    <!-- Header -->
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 mb-6">
      <div>
        <h1 class="text-2xl font-black text-ink dark:text-white tracking-tight">Shipments</h1>
        <p class="text-sm text-slate-500 mt-1">Manage and track all shipments efficiently.</p>
      </div>
      <div class="flex items-center gap-3">
        <BaseButton
          v-if="can('shipment_create')"
          as="a"
          href="/admin/shipments/create"
          variant="primary"
          icon="ri-add-line"
        >
          Add Shipment
        </BaseButton>
      </div>
    </div>

    <!-- Unified Toolbar -->
    <div class="flex flex-col lg:flex-row items-center gap-4 mb-4 bg-surface dark:bg-surface-dark p-3 rounded-xl border border-slate-100 dark:border-white/5 shadow-sm">
      <!-- Search -->
      <div class="relative w-full lg:w-72 shrink-0">
        <i class="ri-search-line absolute left-3 top-1/2 -translate-y-1/2 text-slate-400"></i>
        <input
          v-model="filters.keyword"
          @keyup.enter="doSearch"
          placeholder="Search ref or carrier..."
          class="w-full h-10 pl-9 pr-3 rounded-lg border-transparent bg-slate-100 dark:bg-black/20 text-sm focus:ring-2 focus:ring-primary-500/20 focus:border-primary-500 focus:bg-white dark:focus:bg-surface-dark-card transition-all dark:text-slate-200"
        />
      </div>

      <!-- Quick Filters (Tabs) -->
      <div class="flex flex-wrap items-center gap-4 flex-1">
        <TabGroup :tabs="statusTabs" v-model:active="filters.status" variant="pills" @update:active="doSearch" />
      </div>

      <!-- Advanced Toggle -->
      <div class="w-full lg:w-auto shrink-0 flex justify-end">
        <BaseButton variant="light" size="md" icon="ri-filter-3-line" @click="showAdvanced = !showAdvanced" :class="{'bg-primary-50 text-primary-700 border-primary-200 dark:bg-primary-500/10 dark:text-primary-300': showAdvanced}">
          Advanced Filters
        </BaseButton>
      </div>
    </div>

    <!-- Advanced Filters (Collapsible) -->
    <div v-show="showAdvanced" class="bg-surface dark:bg-surface-dark border dark:border-white/5 rounded-xl p-4 shadow-sm mb-4 transition-all">
      <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-5 gap-4">
        <!-- Date Range -->
        <div class="space-y-1.5">
          <label class="text-xs font-bold text-slate-600 dark:text-slate-300">Date Range</label>
          <FormDate v-model="dateRange" mode="range" placeholder="Select dates..." class="w-full" />
        </div>
        
        <!-- Carrier -->
        <div class="space-y-1.5">
          <label class="text-xs font-bold text-slate-600 dark:text-slate-300">Carrier</label>
          <FormSelect v-model="filters.carrier" :options="carrierOpts" class="w-full" />
        </div>

        <!-- Driver -->
        <div class="space-y-1.5">
          <label class="text-xs font-bold text-slate-600 dark:text-slate-300">Driver</label>
          <FormSelect v-model="filters.driver_id" :options="driverOpts" class="w-full" />
        </div>

        <!-- From Location -->
        <div class="space-y-1.5">
          <label class="text-xs font-bold text-slate-600 dark:text-slate-300">From Location</label>
          <FormSelect v-model="filters.from_location" :options="locOpts" class="w-full" />
        </div>

        <!-- To Location -->
        <div class="space-y-1.5">
          <label class="text-xs font-bold text-slate-600 dark:text-slate-300">To Location</label>
          <FormSelect v-model="filters.to_location" :options="locOpts" class="w-full" />
        </div>
      </div>

      <!-- Action Row -->
      <div class="mt-4 flex items-center gap-3 border-t border-slate-100 dark:border-white/5 pt-4">
        <BaseButton variant="light" size="md" @click="doReset">Clear</BaseButton>
        <BaseButton variant="primary" size="md" @click="doSearch">Apply Filters</BaseButton>
      </div>
    </div>

    <!-- Table -->
    <DataTable
      :columns="columns"
      :rows="rows"
      row-key="id"
      :total="total"
      :loading="loading"
      :server-side="true"
      :searchable="false"
      @query="onQuery"
      @export="onExport"
    >
      <template #cell-id="{ value }">
        <span class="font-black text-[#0ab39c] dark:text-[#0ab39c]">#{{ value }}</span>
      </template>
      
      <template #cell-reference_number="{ value }">
        <span class="font-bold text-slate-800 dark:text-white">{{ value || '—' }}</span>
      </template>
      
      <template #cell-carrier="{ value }">
        <span class="font-extrabold text-slate-800 dark:text-white">{{ value || '—' }}</span>
      </template>

      <template #cell-route="{ row }">
        <RouteCell :from="row.from_location_name" :to="row.to_location_name" />
      </template>

      <template #cell-driver_name="{ value }">
        <div v-if="value" class="flex items-center gap-2">
          <BaseAvatar :name="value" :size="26" class="-mt-[3px]" />
          <span class="text-[12.5px] font-medium text-ink dark:text-slate-200 whitespace-nowrap">{{ value }}</span>
        </div>
        <span v-else class="text-slate-400">—</span>
      </template>

      <template #cell-status_code="{ value }">
        <StatusBadge :status="value" />
      </template>

      <template #cell-actions="{ row }">
        <div class="flex justify-center items-center gap-1">
          <a v-if="can('shipment_show')" :href="`/admin/shipments/${row.id}`" class="grid place-items-center w-8 h-8 rounded-lg text-info hover:bg-info/10 transition" title="View">
            <i class="ri-eye-line"></i>
          </a>
        </div>
      </template>

      <template #empty>
        <div class="py-12 flex flex-col items-center justify-center text-center">
          <div class="w-16 h-16 bg-slate-100 dark:bg-surface-dark-solid rounded-full flex items-center justify-center mb-4">
            <i class="ri-truck-line text-2xl text-slate-400"></i>
          </div>
          <h3 class="text-sm font-semibold text-ink dark:text-white mb-1">No shipments found</h3>
          <p class="text-sm text-slate-500 max-w-sm">There are no shipments matching your filters. Try adjusting your search criteria.</p>
        </div>
      </template>
    </DataTable>
  </div>
</template>
