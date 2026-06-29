<script setup>
/**
 * views/Tasks/TasksList.vue — master task table (Inertia page).
 * Data arrives as props from App\Http\Controllers\App\TasksController@index
 * (mirrors the classic Admin\TasksController filters/scope/sort). Filtering &
 * pagination use Inertia partial reloads. Exports reuse the EXISTING /admin
 * export routes (queued PDF/Excel) so files are identical to the classic page.
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
import BaseModal from '../../components/BaseModal.vue';
import { useToast } from '../../composables/useToast';
import { usePermissions } from '../../composables/usePermissions';

const props = defineProps({
  rows:     { type: Array,  default: () => [] },
  total:    { type: Number, default: 0 },
  page:     { type: Number, default: 1 },
  pageSize: { type: Number, default: 25 },
  filters:  { type: Object, default: () => ({}) },
  options:  { type: Object, default: () => ({}) },
});

const { push } = useToast();
const { can, canDelete } = usePermissions();
const csrf = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';

// Filter params mirror the classic /admin/tasks filter card 1:1 (same field names).
const DEFAULT_FILTERS = {
  keyword: '', status: '', driver_id: '', billing_client: '',
  from_location: '', to_location: '', date_from: '', date_to: '',
  search_date: '', sort_by: '', sort_order: '',
};
const filters = reactive({ ...DEFAULT_FILTERS, ...props.filters });

// Single date-range field (replaces Date From/To). The picker emits {from,to};
// we mirror those into the existing date_from/date_to backend filters.
const datePart = (s) => (s ? String(s).slice(0, 10) : '');
const dateRange = ref(filters.date_from ? `${datePart(filters.date_from)} to ${datePart(filters.date_to)}` : '');
function onDateRange({ from, to }) {
  filters.date_from = from || '';
  filters.date_to = to || '';
}

// Status as colored pills (toggle-select) — same status tokens StatusBadge uses.
// Class strings are static literals so Tailwind's scanner keeps them.
const statusPills = [
  { value: 'NEW',         label: 'New',           dot: 'bg-status-new',       active: 'bg-status-new/10 border-status-new/40 text-status-new' },
  { value: 'COLLECTED',   label: 'Collected',     dot: 'bg-status-collected', active: 'bg-status-collected/10 border-status-collected/40 text-status-collected' },
  { value: 'IN_FREEZER',  label: 'In Container',  dot: 'bg-status-container', active: 'bg-status-container/10 border-status-container/40 text-status-container' },
  { value: 'OUT_FREEZER', label: 'Out Container', dot: 'bg-status-container', active: 'bg-status-container/10 border-status-container/40 text-status-container' },
  { value: 'CLOSED',      label: 'Closed',        dot: 'bg-status-closed',    active: 'bg-status-closed/10 border-status-closed/40 text-status-closed' },
  { value: 'NO_SAMPLES',  label: 'No Samples',    dot: 'bg-status-none',      active: 'bg-status-none/15 border-status-none/40 text-status-none' },
];
// Clicking a status pill filters immediately (auto-search), no Search click needed.
function toggleStatus(v) {
  filters.status = filters.status === v ? '' : v;
  reload({ page: 1 });
}

// EAT minutes color by threshold: fast=green, medium=amber, slow=red, none=gray.
function etaColor(v) {
  const n = Number(v);
  if (!n) return 'text-slate-400';   // 0 / empty / non-numeric
  if (n <= 30) return 'text-success';
  if (n <= 40) return 'text-amber-500';
  return 'text-red-500';
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

const driverOpts = computed(() => props.options?.drivers || []);
const clientOpts = computed(() => props.options?.clients || []);
const locationOpts = computed(() => props.options?.locations || []);
const rows = computed(() => props.rows || []);
const total = computed(() => props.total || 0);
const loading = ref(false);

// Columns map 1:1 to the API row fields (same data keys the classic DataTable uses).
const columns = [
  { key: 'sequence',          label: '#',                 sticky: 'start', width: '56px' },
  { key: 'id',                label: 'ID',                sticky: 'start', width: '84px' },
  { key: 'created_at',        label: 'Order Date',        ltr: true },
  { key: 'client',            label: 'Client' },
  { key: 'driver_name',       label: 'Driver' },
  { key: 'route',             label: 'From → To' },
  { key: 'eta',               label: 'EAT (in Minutes)',  align: 'center' },
  { key: 'collection_date',   label: 'Collection Date',   ltr: true },
  { key: 'freezer_date',      label: 'Container Date',    ltr: true },
  { key: 'freezer_out_date',  label: 'Container Out Date',ltr: true },
  { key: 'close_date',        label: 'Close Date',        ltr: true },
  { key: 'status',            label: 'Status' },
  { key: 'task_type',         label: 'Task Type' },
  { key: 'added_by',          label: 'Added By' },
  { key: 'hours',             label: 'Hours',             align: 'center' },
];

/* ---------- data: Inertia partial reloads ---------- */
function reload(extra = {}) {
  loading.value = true;
  router.get('/app/admin/tasks', { ...filters, ...extra }, {
    preserveState: true,
    preserveScroll: true,
    only: ['rows', 'total', 'page', 'pageSize', 'filters'],
    onFinish: () => { loading.value = false; },
  });
}
function doSearch() { reload({ page: 1 }); }
function doReset() { Object.assign(filters, DEFAULT_FILTERS); dateRange.value = ''; reload({ page: 1 }); }
function onQuery(q) { reload({ page: q.page, pageSize: q.pageSize }); }

/* ---------- exports: reuse the EXISTING /admin routes (identical files) ---------- */
function exportPdf() {
  const form = document.createElement('form');
  form.method = 'POST';
  form.action = '/admin/task-report';
  form.target = '_blank';
  const add = (k, v) => { const i = document.createElement('input'); i.type = 'hidden'; i.name = k; i.value = v ?? ''; form.appendChild(i); };
  add('_token', csrf);
  add('report_type', 'pdf');
  ['status', 'date_from', 'date_to', 'billing_client', 'from_location', 'to_location', 'driver_id', 'search_date']
    .forEach((k) => add(k, filters[k]));
  document.body.appendChild(form);
  form.submit();
  form.remove();
}
function exportExcel() {
  const p = new URLSearchParams();
  ['status', 'date_from', 'date_to', 'billing_client', 'from_location', 'to_location', 'driver_id']
    .forEach((k) => { if (filters[k]) p.set(k, filters[k]); });
  window.open('/admin/export-excel?' + p.toString(), '_blank');
}

/* ---------- DataTable toolbar (Copy / CSV / Print = current page; Excel = backend) ---------- */
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
  if (kind === 'excel') return exportExcel();
  const { header, body } = matrix();
  if (kind === 'copy') {
    navigator.clipboard?.writeText([header.join('\t'), ...body.map((r) => r.join('\t'))].join('\n'));
    push({ type: 'success', title: 'Copied', message: `${body.length} rows copied to clipboard` });
  } else if (kind === 'csv') {
    const esc = (s) => `"${String(s).replace(/"/g, '""')}"`;
    const csv = [header.map(esc).join(','), ...body.map((r) => r.map(esc).join(','))].join('\n');
    const a = document.createElement('a');
    a.href = URL.createObjectURL(new Blob(['﻿' + csv], { type: 'text/csv;charset=utf-8' }));
    a.download = 'tasks.csv';
    a.click();
    URL.revokeObjectURL(a.href);
  } else if (kind === 'print') {
    const w = window.open('', '_blank');
    const th = header.map((h) => `<th>${h}</th>`).join('');
    const tr = body.map((r) => `<tr>${r.map((c) => `<td>${c}</td>`).join('')}</tr>`).join('');
    w.document.write(`<html dir="${document.documentElement.dir}"><head><title>Tasks</title><style>table{border-collapse:collapse;width:100%;font-family:Poppins,sans-serif;font-size:12px}th,td{border:1px solid #cbd5e1;padding:6px 8px;text-align:start}th{background:#005D69;color:#fff}</style></head><body><h3>Tasks</h3><table><thead><tr>${th}</tr></thead><tbody>${tr}</tbody></table></body></html>`);
    w.document.close(); w.focus(); w.print();
  }
}

/* ---------- delete via the EXISTING /admin destroy routes (keeps can-delete gate) ---------- */
const delTarget = ref(null);
const showDel = ref(false);
function askDelete(row) { delTarget.value = row; showDel.value = true; }

async function webDelete(url, ids) {
  const body = new URLSearchParams();
  body.set('_method', 'DELETE');
  body.set('_token', csrf);
  if (ids) ids.forEach((id) => body.append('ids[]', id));
  return fetch(url, {
    method: 'POST', credentials: 'same-origin',
    headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json' },
    body,
  });
}
async function confirmDelete() {
  try {
    const res = await webDelete('/admin/tasks/' + delTarget.value.id);
    if (res.status === 403) push({ type: 'error', title: 'Forbidden', message: 'You are not allowed to delete.' });
    else { push({ type: 'success', title: 'Deleted', message: `Task #${delTarget.value.id} removed` }); reload(); }
  } catch (e) { push({ type: 'error', title: 'Error', message: 'Delete failed.' }); }
  showDel.value = false;
}
async function bulkDelete(ids) {
  try {
    const res = await webDelete('/admin/tasks/destroy', ids);
    if (res.status === 403) push({ type: 'error', title: 'Forbidden', message: 'You are not allowed to delete.' });
    else { push({ type: 'success', title: 'Bulk delete', message: `${ids.length} tasks removed` }); reload(); }
  } catch (e) { push({ type: 'error', title: 'Error', message: 'Bulk delete failed.' }); }
}
</script>

<template>
  <div>
    <Breadcrumb title="Tasks" :trail="[{ label: 'Tasks' }]">
      <template #actions>
        <BaseButton variant="pdf" icon="ri-file-pdf-2-line" @click="exportPdf">PDF</BaseButton>
        <BaseButton variant="excel" icon="ri-file-excel-2-line" @click="exportExcel">Excel</BaseButton>
        <a v-if="can('task_create')" href="/admin/tasks/create">
          <BaseButton variant="primary" icon="ri-add-line">Add Task</BaseButton>
        </a>
      </template>
    </Breadcrumb>

    <!-- filter bar — mirrors the classic /admin/tasks filter card -->
    <FilterBar :loading="loading" subtitle="refine the task list" @search="doSearch" @reset="doReset">
      <FormInput  v-model="filters.keyword"        label="Keyword"        placeholder="Task ID or keyword" icon="ri-search-line" />
      <FormSelect v-model="filters.driver_id"      label="Driver"         :options="driverOpts"     placeholder="Select Driver" />
      <FormSelect v-model="filters.billing_client" label="Billing Client" :options="clientOpts"     placeholder="Select Client" />
      <FormSelect v-model="filters.from_location"  label="From Location"  :options="locationOpts"   placeholder="Select Location" />
      <FormSelect v-model="filters.to_location"    label="To Location"    :options="locationOpts"   placeholder="Select Location" />
      <FormDate   v-model="dateRange" label="Date Range" mode="range" placeholder="Select range" @range="onDateRange" />
      <FormSelect v-model="filters.search_date"    label="Search Date"    :options="searchDateOpts" :searchable="false" placeholder="Search by date" />
      <FormSelect v-model="filters.sort_by"        label="Sort By"        :options="sortByOpts"     :searchable="false" placeholder="Default order" />
      <FormSelect v-model="filters.sort_order"     label="Sort Order"     :options="sortOrderOpts"  :searchable="false" placeholder="Default" />

      <!-- Status as colored pills (replaces the dropdown; same filter value) -->
      <template #actions-extra>
        <button
          v-for="s in statusPills" :key="s.value" type="button"
          @click="toggleStatus(s.value)"
          class="inline-flex items-center gap-2 ps-3 pe-3.5 h-9 rounded-full border text-sm font-bold transition"
          :class="filters.status === s.value
            ? s.active
            : 'bg-surface dark:bg-white/5 border-slate-200 dark:border-white/10 text-slate-600 dark:text-slate-300 hover:border-slate-300 dark:hover:border-white/20'"
        >
          <span class="w-2 h-2 rounded-full" :class="s.dot"></span>
          {{ s.label }}
        </button>
      </template>
    </FilterBar>

    <!-- data table (server-side) -->
    <DataTable
      title="Tasks"
      :columns="columns" :rows="rows" row-key="id"
      :loading="loading" :server-side="true" :total="total" :searchable="false"
      :bulk-actions="canDelete() ? [{ label: 'Delete', icon: 'ri-delete-bin-line', tone: 'danger', event: 'bulk-delete' }] : []"
      @query="onQuery" @bulk-delete="bulkDelete" @export="onExport"
    >
      <template #cell-id="{ value }">
        <span class="font-black text-primary-500 dark:text-primary-300">#{{ value }}</span>
      </template>
      <template #cell-client="{ value }">
        <span class="font-semibold text-ink dark:text-slate-100">{{ value || '—' }}</span>
      </template>
      <template #cell-route="{ row }">
        <div dir="ltr" class="inline-flex items-center gap-2 whitespace-nowrap">
          <span class="inline-flex items-center gap-1.5">
            <i class="ri-map-pin-fill text-red-500"></i>
            <span class="truncate font-medium">{{ row.from_location_name || '—' }}</span>
          </span>
          <i class="ri-arrow-right-line text-slate-400 shrink-0"></i>
          <span class="inline-flex items-center gap-1.5">
            <i class="ri-map-pin-fill text-green-500"></i>
            <span class="truncate font-medium">{{ row.to_location_name || '—' }}</span>
          </span>
        </div>
      </template>
      <template #cell-driver_name="{ value }">
        <span v-if="value" class="inline-flex items-center gap-2">
          <BaseAvatar :name="value" :size="26" /><span class="truncate font-medium">{{ value }}</span>
        </span>
        <span v-else class="text-slate-400">—</span>
      </template>
      <template #cell-eta="{ value }">
        <span v-if="value != null && value !== ''" class="whitespace-nowrap">
          <span class="font-bold" :class="etaColor(value)">{{ value }}</span>
          <span class="text-slate-400 ms-1">min</span>
        </span>
        <span v-else class="text-slate-400">—</span>
      </template>
      <template #cell-status="{ value }">
        <StatusBadge :status="value" />
      </template>

      <template #row-actions="{ row }">
        <div class="inline-flex items-center gap-1">
          <a v-if="can('task_show')" :href="`/admin/tasks/${row.id}`" class="grid place-items-center w-8 h-8 rounded-lg text-info hover:bg-info/10 transition" title="View"><i class="ri-eye-line"></i></a>
          <a v-if="can('task_edit')" :href="`/admin/tasks/${row.id}/edit`" class="grid place-items-center w-8 h-8 rounded-lg text-primary-600 hover:bg-primary-50 dark:hover:bg-primary-500/10 transition" title="Edit"><i class="ri-pencil-line"></i></a>
          <button v-if="canDelete()" @click="askDelete(row)" class="grid place-items-center w-8 h-8 rounded-lg text-danger hover:bg-danger/10 transition" title="Delete"><i class="ri-delete-bin-line"></i></button>
        </div>
      </template>
    </DataTable>

    <!-- delete confirm -->
    <BaseModal v-model="showDel" title="Confirm delete" icon="ri-error-warning-line" tone="danger" size="sm">
      <p class="text-sm text-slate-600 dark:text-slate-300">
        Are you sure you want to delete <span class="font-semibold text-ink dark:text-slate-100">Task #{{ delTarget?.id }}</span>?
        This action cannot be undone.
      </p>
      <p class="text-sm text-slate-400 mt-1.5" dir="rtl">هل أنت متأكد من رغبتك في إتمام عملية الحذف؟</p>
      <template #footer>
        <BaseButton variant="light" @click="showDel = false">Cancel</BaseButton>
        <BaseButton variant="danger" icon="ri-delete-bin-line" @click="confirmDelete">Delete</BaseButton>
      </template>
    </BaseModal>
  </div>
</template>
