<script setup>
import { ref, reactive, computed } from 'vue';
import { router } from '@inertiajs/vue3';
import Breadcrumb from '../../components/Breadcrumb.vue';
import FilterBar from '../../components/FilterBar.vue';
import DataTable from '../../components/DataTable.vue';
import FormSelect from '../../components/FormSelect.vue';
import FormDate from '../../components/FormDate.vue';
import BaseAvatar from '../../components/BaseAvatar.vue';
import RouteCell from '../../components/RouteCell.vue';
import { useToast } from '../../composables/useToast';

const { push } = useToast();

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
  router.get('/admin/tasks/unused', { pageSize: props.pageSize, ...filters, ...extra }, {
    preserveState: true,
    preserveScroll: true,
    only: ['rows', 'total', 'page', 'pageSize', 'filters'],
    onFinish: () => { loading.value = false; },
  });
}
function doSearch() { reload({ page: 1 }); }
function doReset() { Object.assign(filters, DEFAULT_FILTERS); dateRange.value = ''; reload({ page: 1 }); }
function onQuery(q) { reload({ page: q.page, pageSize: q.pageSize }); }

/* ---------- DataTable toolbar exports (Copy / CSV / Excel / Print) ---------- */
function cellText(r, c) {
  if (c.key === 'route') return `${r.from_location_name || ''} → ${r.to_location_name || ''}`;
  return r[c.key] == null ? '' : String(r[c.key]);
}
function matrix() {
  const header = columns.map((c) => c.label);
  const body = rows.value.map((r) => columns.map((c) => cellText(r, c)));
  return { header, body };
}
function onExport(kind) {
  const { header, body } = matrix();
  if (!body.length) { push({ type: 'info', title: 'Nothing to export', message: 'No unused tasks in the current view.' }); return; }

  if (kind === 'copy') {
    navigator.clipboard?.writeText([header.join('\t'), ...body.map((r) => r.join('\t'))].join('\n'));
    push({ type: 'success', title: 'Copied', message: `${body.length} rows copied to clipboard` });
  } else if (kind === 'csv') {
    const esc = (s) => `"${String(s).replace(/"/g, '""')}"`;
    const csv = [header.map(esc).join(','), ...body.map((r) => r.map(esc).join(','))].join('\n');
    const a = document.createElement('a');
    a.href = URL.createObjectURL(new Blob(['﻿' + csv], { type: 'text/csv;charset=utf-8' }));
    a.download = 'unused-tasks.csv';
    a.click();
    URL.revokeObjectURL(a.href);
  } else if (kind === 'excel') {
    const th = header.map((h) => `<th>${h}</th>`).join('');
    const tr = body.map((r) => `<tr>${r.map((c) => `<td>${c}</td>`).join('')}</tr>`).join('');
    const html = `<html xmlns:x="urn:schemas-microsoft-com:office:excel"><head><meta charset="utf-8"></head><body><table border="1"><thead><tr>${th}</tr></thead><tbody>${tr}</tbody></table></body></html>`;
    const a = document.createElement('a');
    a.href = URL.createObjectURL(new Blob(['﻿' + html], { type: 'application/vnd.ms-excel' }));
    a.download = 'unused-tasks.xls';
    a.click();
    URL.revokeObjectURL(a.href);
  } else if (kind === 'print') {
    const w = window.open('', '_blank');
    const th = header.map((h) => `<th>${h}</th>`).join('');
    const tr = body.map((r) => `<tr>${r.map((c) => `<td>${c}</td>`).join('')}</tr>`).join('');
    w.document.write(`<html dir="${document.documentElement.dir}"><head><title>Unused Tasks</title><style>table{border-collapse:collapse;width:100%;font-family:Poppins,sans-serif;font-size:12px}th,td{border:1px solid #cbd5e1;padding:6px 8px;text-align:start}th{background:#005D69;color:#fff}</style></head><body><h3>Unused Tasks</h3><table><thead><tr>${th}</tr></thead><tbody>${tr}</tbody></table></body></html>`);
    w.document.close(); w.focus(); w.print();
  }
}
</script>

<template>
  <div class="px-4 py-6 md:px-6 lg:px-8 max-w-[1600px] mx-auto space-y-6">
    <Breadcrumb title="Unused Tasks" :trail="[{ label: 'Tasks', href: '/admin/tasks' }, { label: 'Unused Tasks' }]" />

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
      @query="onQuery"
      @export="onExport"
    >
      <template #cell-driver_name="{ value }">
        <div v-if="value" class="flex items-center gap-2">
          <BaseAvatar :name="value" :size="26" class="-mt-[3px]" />
          <span class="text-[12.5px] font-medium text-ink dark:text-slate-200 whitespace-nowrap">{{ value }}</span>
        </div>
        <span v-else class="text-slate-400">—</span>
      </template>

      <template #cell-route="{ row }">
        <RouteCell :from="row.from_location_name" :to="row.to_location_name" />
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
