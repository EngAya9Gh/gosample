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
  barcode_id: '', confirmed_by_client: 'LOST', date_from: '', date_to: '',
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

const confirmedByClientOpts = [
  { value: 'ALL',   label: 'All' },
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
  { key: 'location_name',     label: 'Location',          wrap: true, width: '200px' },
  { key: 'task_id',           label: 'Task ID',           width: '100px' },
  { key: 'container_imei',    label: 'Container',         width: '140px' },
  { key: 'sample_type',       label: 'Sample Type',       width: '140px' },
  { key: 'temperature_type',  label: 'Temp Type',         width: '140px' },
  { key: 'bag_code',          label: 'Bag Code',          width: '140px' },
  { key: 'confirmed_by_client',label: 'Status',           width: '140px' },
  { key: 'created_at',        label: 'Time',              ltr: true },
  { key: 'actions',           label: 'Actions',           sticky: 'end', width: '80px', align: 'center' },
];

function reload(extra = {}) {
  loading.value = true;
  router.get('/app/admin/lost', { ...filters, ...extra }, {
    preserveState: true,
    preserveScroll: true,
    only: ['rows', 'total', 'page', 'pageSize', 'filters'],
    onFinish: () => { loading.value = false; },
  });
}
function doSearch() { reload({ page: 1 }); }
function doReset() { Object.assign(filters, DEFAULT_FILTERS); dateRange.value = ''; reload({ page: 1 }); }
function onQuery(q) { reload({ page: q.page, pageSize: q.pageSize }); }

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
    <Breadcrumb title="Lost Samples" :trail="[{ label: 'Samples' }, { label: 'Lost Samples' }]" />

    <FilterBar :loading="loading" subtitle="filter lost samples" @search="doSearch" @reset="doReset">
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
      @update="onQuery"
    >
      <template #cell-id="{ value }">
        <span class="font-black text-[#0ab39c] dark:text-[#0ab39c]">#{{ value }}</span>
      </template>

      <template #cell-task_id="{ row }">
        <a v-if="row.task_id" :href="`/app/admin/tasks/${row.task_id}`" class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-md bg-[#005D69]/10 border border-[#005D69]/20 text-[12px] font-black text-[#0ab39c] hover:bg-[#005D69]/20 transition-colors">
          <i class="ri-hashtag"></i>
          {{ row.task_id }}
        </a>
        <span v-else class="text-slate-400">—</span>
      </template>

      <template #cell-confirmed_by_client="{ row }">
        <StatusBadge :status="row.confirmed_by_client === 'YES' ? 'RECEIVED' : (row.confirmed_by_client === 'NO' ? 'PENDING' : 'LOST')" />
      </template>

      <template #cell-sample_type="{ row }">
        <div class="flex items-center gap-2">
          <div class="w-6 h-6 rounded-full bg-indigo-50 dark:bg-indigo-500/10 flex items-center justify-center border border-indigo-100 dark:border-indigo-500/20 shrink-0">
            <i class="ri-test-tube-line text-[10px] text-indigo-500 dark:text-indigo-400"></i>
          </div>
          <span class="text-sm font-medium text-slate-700 dark:text-slate-300">{{ row.sample_type || '—' }}</span>
        </div>
      </template>

      <template #cell-temperature_type="{ row }">
        <div class="flex items-center gap-2">
          <div :class="[
            'w-6 h-6 rounded-full flex items-center justify-center border shrink-0',
            String(row.temperature_type).toLowerCase().includes('room') ? 'bg-amber-50 border-amber-100 text-amber-500 dark:bg-amber-500/10 dark:border-amber-500/20 dark:text-amber-400' :
            String(row.temperature_type).toLowerCase().includes('frozen') ? 'bg-cyan-50 border-cyan-100 text-cyan-500 dark:bg-cyan-500/10 dark:border-cyan-500/20 dark:text-cyan-400' :
            'bg-blue-50 border-blue-100 text-blue-500 dark:bg-blue-500/10 dark:border-blue-500/20 dark:text-blue-400'
          ]">
            <i :class="[
              'text-[10px]',
              String(row.temperature_type).toLowerCase().includes('room') ? 'ri-sun-line' :
              String(row.temperature_type).toLowerCase().includes('frozen') ? 'ri-snowflake-line' :
              'ri-temp-cold-line'
            ]"></i>
          </div>
          <span class="text-sm text-slate-600 dark:text-slate-400">{{ row.temperature_type || '—' }}</span>
        </div>
      </template>

      <template #cell-actions="{ row }">
        <button v-if="can('sample_delete')" @click="askDelete(row)" class="grid place-items-center w-8 h-8 rounded-lg text-danger hover:bg-danger/10 transition" title="Delete">
          <i class="ri-delete-bin-line"></i>
        </button>
      </template>

      <template #empty>
        <div class="py-12 flex flex-col items-center justify-center text-center">
          <div class="w-16 h-16 bg-slate-100 dark:bg-surface-dark-solid rounded-full flex items-center justify-center mb-4">
            <i class="ri-flask-line text-2xl text-slate-400"></i>
          </div>
          <h3 class="text-base font-bold text-slate-900 dark:text-white mb-1">No Lost Samples Found</h3>
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
