<script setup>
import { ref, reactive, computed } from 'vue';
import { router } from '@inertiajs/vue3';
import Breadcrumb from '../../components/Breadcrumb.vue';
import FilterBar from '../../components/FilterBar.vue';
import DataTable from '../../components/DataTable.vue';
import FormInput from '../../components/FormInput.vue';
import FormSelect from '../../components/FormSelect.vue';
import FormDate from '../../components/FormDate.vue';
import BaseModal from '../../components/BaseModal.vue';
import BaseAvatar from '../../components/BaseAvatar.vue';
import StatusBadge from '../../components/StatusBadge.vue';
import { usePermissions } from '../../composables/usePermissions';
import { useToast } from '../../composables/useToast';

const { can } = usePermissions();
const { push } = useToast();
const csrf = document.querySelector('meta[name="csrf-token"]')?.content;

const props = defineProps({
  rows:     { type: Array,  default: () => [] },
  total:    { type: Number, default: 0 },
  page:     { type: Number, default: 1 },
  pageSize: { type: Number, default: 25 },
  filters:  { type: Object, default: () => ({}) },
});

const DEFAULT_FILTERS = {
  barcode_id: '', confirmed_by_client: '', date_from: '', date_to: '',
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
  { value: 'samples.created_at', label: 'Creation Date' },
];
const sortOrderOpts = [
  { value: '',     label: '— Default —' },
  { value: 'desc', label: 'Descending' },
  { value: 'asc',  label: 'Ascending' },
];

const confirmedByClientOpts = [
  { value: '',      label: 'All' },
  { value: 'LOST',  label: 'LOST' },
  { value: 'YES',   label: 'RECEIVED' },
  { value: 'NO',    label: 'PENDING' },
];

const rows = computed(() => props.rows || []);
const total = computed(() => props.total || 0);
const loading = ref(false);

const columns = [
  { key: 'sequence',          label: '#',                 sticky: 'start', width: '52px' },
  { key: 'id',                label: 'ID',                sticky: 'start', width: '80px' },
  { key: 'barcode_id',        label: 'Barcode',           ltr: true },
  { key: 'location_name',     label: 'From Location',     wrap: true, width: '300px' },
  { key: 'to_location_name',  label: 'To Location',       wrap: true, width: '300px' },
  { key: 'task_id',           label: 'Task',              width: '90px' },
  { key: 'driver_name',       label: 'Driver',            width: '140px' },
  { key: 'collection_date',   label: 'Collection Date',   ltr: true, width: '130px' },
  { key: 'close_date',        label: 'Close Date',        ltr: true, width: '130px' },
  { key: 'confirmed_by_client',label: 'Confirmed by client', width: '150px' },
  { key: 'actions',           label: 'Actions',           sticky: 'end', width: '80px', align: 'center' },
];

function reload(extra = {}) {
  loading.value = true;
  router.get('/admin/samples', { ...filters, ...extra }, {
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
const exportColumns = columns.filter((c) => c.key !== 'actions');
function cellText(r, c) {
  if (c.key === 'confirmed_by_client')
    return r.confirmed_by_client === 'YES' ? 'RECEIVED' : (r.confirmed_by_client === 'NO' ? 'PENDING' : 'LOST');
  return r[c.key] == null ? '' : String(r[c.key]);
}
function matrix() {
  const header = exportColumns.map((c) => c.label);
  const body = rows.value.map((r) => exportColumns.map((c) => cellText(r, c)));
  return { header, body };
}
function onExport(kind) {
  const { header, body } = matrix();
  if (!body.length) { push({ type: 'info', title: 'Nothing to export', message: 'No samples in the current view.' }); return; }

  if (kind === 'copy') {
    navigator.clipboard?.writeText([header.join('\t'), ...body.map((r) => r.join('\t'))].join('\n'));
    push({ type: 'success', title: 'Copied', message: `${body.length} rows copied to clipboard` });
  } else if (kind === 'csv') {
    const esc = (s) => `"${String(s).replace(/"/g, '""')}"`;
    const csv = [header.map(esc).join(','), ...body.map((r) => r.map(esc).join(','))].join('\n');
    const a = document.createElement('a');
    a.href = URL.createObjectURL(new Blob(['﻿' + csv], { type: 'text/csv;charset=utf-8' }));
    a.download = 'samples.csv';
    a.click();
    URL.revokeObjectURL(a.href);
  } else if (kind === 'excel') {
    const th = header.map((h) => `<th>${h}</th>`).join('');
    const tr = body.map((r) => `<tr>${r.map((c) => `<td>${c}</td>`).join('')}</tr>`).join('');
    const html = `<html xmlns:x="urn:schemas-microsoft-com:office:excel"><head><meta charset="utf-8"></head><body><table border="1"><thead><tr>${th}</tr></thead><tbody>${tr}</tbody></table></body></html>`;
    const a = document.createElement('a');
    a.href = URL.createObjectURL(new Blob(['﻿' + html], { type: 'application/vnd.ms-excel' }));
    a.download = 'samples.xls';
    a.click();
    URL.revokeObjectURL(a.href);
  } else if (kind === 'print') {
    const w = window.open('', '_blank');
    const th = header.map((h) => `<th>${h}</th>`).join('');
    const tr = body.map((r) => `<tr>${r.map((c) => `<td>${c}</td>`).join('')}</tr>`).join('');
    w.document.write(`<html dir="${document.documentElement.dir}"><head><title>Samples</title><style>table{border-collapse:collapse;width:100%;font-family:Poppins,sans-serif;font-size:12px}th,td{border:1px solid #cbd5e1;padding:6px 8px;text-align:start}th{background:#005D69;color:#fff}</style></head><body><h3>Samples</h3><table><thead><tr>${th}</tr></thead><tbody>${tr}</tbody></table></body></html>`);
    w.document.close(); w.focus(); w.print();
  }
}

const delTarget = ref(null);
const showDel = ref(false);
function askDelete(row) { delTarget.value = row; showDel.value = true; }

async function confirmDelete() {
  try {
    const body = new URLSearchParams();
    body.set('_method', 'DELETE');
    body.set('_token', csrf);
    const res = await fetch('/admin/samples/' + delTarget.value.id, {
      method: 'POST', credentials: 'same-origin',
      headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json' },
      body,
    });
    if (res.status === 403) push({ type: 'error', title: 'Forbidden', message: 'You are not allowed to delete this sample.' });
    else { push({ type: 'success', title: 'Deleted', message: `Sample #${delTarget.value.id} removed completely.` }); reload(); }
  } catch (e) { push({ type: 'error', title: 'Error', message: 'Delete failed.' }); }
  showDel.value = false;
}
</script>

<template>
  <div class="px-4 py-6 md:px-6 lg:px-8 max-w-[1600px] mx-auto space-y-6">
    <Breadcrumb title="Samples" :trail="[{ label: 'Samples' }, { label: 'Samples List' }]" />

    <FilterBar :loading="loading" subtitle="filter samples list" @search="doSearch" @reset="doReset">
      <FormInput  v-model="filters.barcode_id"          label="Barcode"        placeholder="Enter barcode" />
      <FormDate   v-model="dateRange" label="Date Range" mode="range" placeholder="Select range" @range="onDateRange" />
      <FormSelect v-model="filters.sort_by"        label="Sort By"        :options="sortByOpts"     :searchable="false" placeholder="Default order" />
      <FormSelect v-model="filters.sort_order"     label="Sort Order"     :options="sortOrderOpts"  :searchable="false" placeholder="Default" />

      <template #actions-extra>
        <div class="flex flex-wrap gap-2 w-full md:w-auto mt-4 md:mt-0">
          <button v-for="opt in confirmedByClientOpts" :key="opt.value"
            @click="filters.confirmed_by_client = opt.value; doSearch()"
            :class="[
              'px-3 py-1.5 rounded-full text-[11px] font-bold border transition-colors',
              filters.confirmed_by_client === opt.value
                ? (opt.value === 'LOST' ? 'bg-[#BD6BA7] text-white border-[#BD6BA7]' :
                   opt.value === 'YES'  ? 'bg-[#22c55e] text-white border-[#22c55e]' :
                   opt.value === 'NO'   ? 'bg-[#e89e2b] text-white border-[#e89e2b]' :
                   'bg-[#005D69] text-white border-[#005D69]')
                : (opt.value === 'LOST' ? 'bg-[#BD6BA7]/10 text-[#BD6BA7] border-[#BD6BA7]/20 hover:bg-[#BD6BA7]/20' :
                   opt.value === 'YES'  ? 'bg-[#22c55e]/10 text-[#22c55e] border-[#22c55e]/20 hover:bg-[#22c55e]/20' :
                   opt.value === 'NO'   ? 'bg-[#e89e2b]/10 text-[#e89e2b] border-[#e89e2b]/20 hover:bg-[#e89e2b]/20' :
                   'bg-white text-slate-600 border-slate-200 hover:bg-slate-50 dark:bg-surface-dark dark:text-slate-300 dark:border-white/5 dark:hover:bg-surface-dark-solid')
            ]">
            {{ opt.label }}
          </button>
        </div>
      </template>
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
      <template #cell-id="{ value }">
        <span class="font-black text-[#0ab39c] dark:text-[#0ab39c]">#{{ value }}</span>
      </template>

      <template #cell-location_name="{ value }">
        <span v-if="value" class="inline-flex items-start gap-1 px-2.5 py-1 rounded-md bg-emerald-50 text-emerald-700 dark:bg-emerald-500/10 dark:text-emerald-400 text-[12.5px] font-bold w-full whitespace-normal break-words border border-emerald-100 dark:border-emerald-500/20">
          <i class="ri-map-pin-fill text-red-500 text-[13px] shrink-0 mt-[2px]"></i>
          <span>{{ value }}</span>
        </span>
        <span v-else class="text-slate-400">—</span>
      </template>

      <template #cell-to_location_name="{ value }">
        <span v-if="value" class="inline-flex items-start gap-1 px-2.5 py-1 rounded-md bg-sky-50 text-sky-700 dark:bg-sky-500/10 dark:text-sky-400 text-[12.5px] font-bold w-full whitespace-normal break-words border border-sky-100 dark:border-sky-500/20">
          <i class="ri-map-pin-fill text-green-500 text-[13px] shrink-0 mt-[2px]"></i>
          <span>{{ value }}</span>
        </span>
        <span v-else class="text-slate-400">—</span>
      </template>

      <template #cell-task_id="{ row }">
        <a v-if="row.task_id" :href="`/admin/tasks/${row.task_id}`" class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-md bg-[#005D69]/10 border border-[#005D69]/20 text-[12px] font-black text-[#0ab39c] hover:bg-[#005D69]/20 transition-colors">
          <i class="ri-hashtag"></i>
          {{ row.task_id }}
        </a>
        <span v-else class="text-slate-400">—</span>
      </template>

      <template #cell-driver_name="{ value }">
        <div v-if="value" class="flex items-center gap-2">
          <BaseAvatar :name="value" :size="26" />
          <span class="text-[12.5px] font-medium text-ink dark:text-slate-200 whitespace-nowrap">{{ value }}</span>
        </div>
        <span v-else class="text-slate-400">—</span>
      </template>

      <template #cell-confirmed_by_client="{ row }">
        <span v-if="row.confirmed_by_client === 'YES'" class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-md bg-emerald-50 text-emerald-700 dark:bg-emerald-500/10 dark:text-emerald-400 text-xs font-bold border border-emerald-100 dark:border-emerald-500/20">
          <i class="ri-check-line text-sm"></i> Yes
        </span>
        <span v-else-if="row.confirmed_by_client === 'NO'" class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-md bg-amber-50 text-amber-700 dark:bg-amber-500/10 dark:text-amber-400 text-xs font-bold border border-amber-100 dark:border-amber-500/20">
          <i class="ri-time-line text-sm"></i> No
        </span>
        <span v-else-if="row.confirmed_by_client === 'LOST'" class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-md bg-rose-50 text-rose-700 dark:bg-rose-500/10 dark:text-rose-400 text-xs font-bold border border-rose-100 dark:border-rose-500/20">
          <i class="ri-close-circle-line text-sm"></i> Lost
        </span>
        <span v-else class="text-slate-400">—</span>
      </template>

      <template #cell-actions="{ row }">
        <button v-if="can('sample_delete')" @click="askDelete(row)" class="grid place-items-center w-8 h-8 rounded-lg text-danger hover:bg-danger/10 transition" title="Delete">
          <i class="ri-delete-bin-line"></i>
        </button>
      </template>

      <template #empty>
        <div class="py-12 flex flex-col items-center justify-center text-center">
          <div class="w-16 h-16 bg-slate-100 dark:bg-surface-dark-solid rounded-full flex items-center justify-center mb-4">
            <i class="ri-test-tube-line text-2xl text-slate-400"></i>
          </div>
          <h3 class="text-base font-bold text-slate-900 dark:text-white mb-1">No Samples Found</h3>
          <p class="text-sm text-slate-500 max-w-sm">Try adjusting your filters or date range.</p>
        </div>
      </template>
    </DataTable>

    <BaseModal v-model="showDel" title="Confirm Delete" icon="ri-error-warning-line" iconClass="text-red-500 bg-red-100 dark:bg-red-500/20" maxWidth="sm">
      <div class="p-6">
        <h3 class="text-lg font-bold text-slate-900 dark:text-white mb-2">Delete Sample #{{ delTarget?.id }}?</h3>
        <p class="text-sm text-slate-500 dark:text-slate-400 mb-6">
          Are you sure you want to completely delete this sample? This action is permanent and cannot be undone.
        </p>
        <div class="flex justify-end gap-3">
          <button @click="showDel = false" class="px-4 py-2 text-sm font-medium text-slate-700 bg-white border border-slate-300 rounded-lg hover:bg-slate-50 dark:bg-surface-dark dark:text-slate-300 dark:border-white/5 dark:hover:bg-surface-dark-solid transition-colors">
            Cancel
          </button>
          <button @click="confirmDelete" class="px-4 py-2 text-sm font-medium text-white bg-red-600 rounded-lg hover:bg-red-700 shadow-sm shadow-red-600/20 transition-all">
            Yes, Delete Permanently
          </button>
        </div>
      </div>
    </BaseModal>
  </div>
</template>
