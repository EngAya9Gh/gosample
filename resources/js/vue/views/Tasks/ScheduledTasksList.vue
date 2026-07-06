<script setup>
import { ref, computed, reactive, watch } from 'vue';
import { router } from '@inertiajs/vue3';
import DataTable from '../../components/DataTable.vue';
import BaseButton from '../../components/BaseButton.vue';
import BaseModal from '../../components/BaseModal.vue';
import BaseAvatar from '../../components/BaseAvatar.vue';
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
  clients: Array,
  locations: Array,
  drivers: Array,
});

const DEFAULT_FILTERS = {
  name: '', status: '', task_type: '', driver_id: '', client_id: '', from_location: '', to_location: '',
  date_from: '', date_to: '', sort_by: '', sort_order: '',
};

const filters = reactive({ ...DEFAULT_FILTERS, ...props.filters });

const statusTabs = [
  { key: '',         label: 'All Statuses' },
  { key: 'enabled',  label: 'Enabled', activeClass: 'bg-emerald-500 text-white dark:bg-emerald-600' },
  { key: 'disabled', label: 'Disabled', activeClass: 'bg-danger text-white dark:bg-red-600' },
];

const taskTypeTabs = [
  { key: '',       label: 'All Types' },
  { key: 'SAMPLE', label: 'Sample', activeClass: 'bg-indigo-500 text-white dark:bg-indigo-600' },
  { key: 'BOX',    label: 'Box', activeClass: 'bg-amber-500 text-white dark:bg-amber-600' },
];

const driverOpts = computed(() => [{ value: '', label: 'All Drivers' }, ...(props.drivers || [])]);
const clientOpts = computed(() => [{ value: '', label: 'All Clients' }, ...(props.clients || [])]);
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
  { key: 'name',              label: 'Name',              width: '180px', wrap: true },
  { key: 'status',            label: 'Status',            width: '100px' },
  { key: 'start_date',        label: 'Start Date',        width: '110px' },
  { key: 'end_date',          label: 'End Date',          width: '110px' },
  { key: 'to_location_name',  label: 'To Location',       width: '160px', wrap: true },
  { key: 'client_name',       label: 'Client',            width: '160px', wrap: true },
  { key: 'selected_hour',     label: 'Selected Hour',     width: '110px' },
  { key: 'task_type',         label: 'Task Type',         width: '90px' },
  { key: 'selected_days',     label: 'Days',              width: '140px', wrap: true },
  { key: 'added_by',          label: 'Added By',          width: '120px' },
  { key: 'driver_name',       label: 'Driver',            width: '160px' },
  { key: 'actions',           label: 'Actions',           sticky: 'end', width: '100px', align: 'center' },
];

function reload(extra = {}) {
  loading.value = true;
  router.get('/app/admin/scheduled-tasks', { ...filters, ...extra }, {
    preserveState: true,
    preserveScroll: true,
    only: ['rows', 'total', 'page', 'pageSize', 'filters', 'clients', 'locations', 'drivers'],
    onFinish: () => { loading.value = false; },
  });
}
function doSearch() { reload({ page: 1 }); }
function doReset() { Object.assign(filters, DEFAULT_FILTERS); dateRange.value = ''; reload({ page: 1 }); }
function onQuery(q) { reload({ page: q.page, pageSize: q.pageSize }); }

const delTarget = ref(null);
const showDel = ref(false);
function askDelete(row) { delTarget.value = row; showDel.value = true; }

async function webDelete(url) {
  const csrf = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
  const body = new FormData();
  body.set('_method', 'DELETE');
  body.set('_token', csrf);
  return fetch(url, {
    method: 'POST', credentials: 'same-origin',
    headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json' },
    body,
  });
}

async function confirmDelete() {
  try {
    const res = await webDelete('/admin/scheduled-tasks/' + delTarget.value.id);
    if (res.status === 403) push({ type: 'error', title: 'Forbidden', message: 'You are not allowed to delete.' });
    else { push({ type: 'success', title: 'Deleted', message: `Scheduled Task #${delTarget.value.id} removed` }); reload(); }
  } catch (e) { push({ type: 'error', title: 'Error', message: 'Delete failed.' }); }
  showDel.value = false;
}

function formatDays(daysStr) {
  if (!daysStr) return '';
  const days = daysStr.split(',');
  return days.map(d => d.substring(0, 3)).join(', ');
}

// Bulk delete logic
const showBulkDel = ref(false);
const bulkDelTargets = ref([]);
function handleBulkDelete(ids) {
  if (!ids || !ids.length) return;
  bulkDelTargets.value = ids;
  showBulkDel.value = true;
}
async function confirmBulkDelete() {
  try {
    const csrf = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
    const body = new FormData();
    body.set('_method', 'DELETE');
    body.set('_token', csrf);
    bulkDelTargets.value.forEach(id => body.append('ids[]', id));
    
    const res = await fetch('/admin/scheduled-tasks/massDestroy', {
      method: 'POST', credentials: 'same-origin',
      headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json' },
      body,
    });
    
    if (res.status === 403) push({ type: 'error', title: 'Forbidden', message: 'You are not allowed to delete.' });
    else { 
      push({ type: 'success', title: 'Deleted', message: `Successfully deleted ${bulkDelTargets.value.length} tasks` }); 
      reload(); 
    }
  } catch (e) { push({ type: 'error', title: 'Error', message: 'Bulk Delete failed.' }); }
  showBulkDel.value = false;
}
</script>

<template>
  <div class="space-y-6">
    <div class="flex flex-col md:flex-row md:items-center justify-between gap-4">
      <h1 class="text-2xl font-bold tracking-tight text-ink dark:text-white">Scheduled Tasks</h1>
      
      <div v-if="can('scheduled_task_create')" class="flex flex-wrap items-center gap-2">
        <BaseButton variant="primary" icon="ri-add-line" href="/admin/scheduled-tasks/create" as="a">
          Add Scheduled Task
        </BaseButton>
        <BaseButton variant="secondary" icon="ri-add-line" href="/admin/scheduled-tasks/quick" as="a" class="bg-indigo-600 hover:bg-indigo-700 text-white border-none shadow-sm shadow-indigo-600/20">
          Add Quick Schedule Task
        </BaseButton>
      </div>
    </div>

    <!-- Unified Toolbar -->
    <div class="flex flex-col lg:flex-row items-center gap-4 mb-4 bg-surface dark:bg-surface-dark p-3 rounded-xl border border-slate-100 dark:border-white/5 shadow-sm">
      <!-- Search -->
      <div class="relative w-full lg:w-72 shrink-0">
        <i class="ri-search-line absolute left-3 top-1/2 -translate-y-1/2 text-slate-400"></i>
        <input
          v-model="filters.name"
          @keyup.enter="doSearch"
          placeholder="Search scheduled tasks..."
          class="w-full h-10 pl-9 pr-3 rounded-lg border-transparent bg-slate-100 dark:bg-black/20 text-sm focus:ring-2 focus:ring-primary-500/20 focus:border-primary-500 focus:bg-white dark:focus:bg-surface-dark-card transition-all dark:text-slate-200"
        />
      </div>

      <!-- Quick Filters (Tabs) -->
      <div class="flex flex-wrap items-center gap-4 flex-1">
        <TabGroup :tabs="statusTabs" v-model:active="filters.status" variant="pills" @update:active="doSearch" />
        <div class="hidden sm:block w-px h-6 bg-slate-200 dark:bg-white/10 shrink-0"></div>
        <TabGroup :tabs="taskTypeTabs" v-model:active="filters.task_type" variant="pills" @update:active="doSearch" />
      </div>

      <!-- Advanced Toggle -->
      <div class="w-full lg:w-auto shrink-0 flex justify-end">
        <BaseButton variant="light" size="md" icon="ri-filter-3-line" @click="showAdvanced = !showAdvanced" :class="{'bg-primary-50 text-primary-700 border-primary-200 dark:bg-primary-500/10 dark:text-primary-300': showAdvanced}">
          Advanced Filters
        </BaseButton>
      </div>
    </div>

    <!-- Advanced Filters (Collapsible) -->
    <div v-show="showAdvanced" class="bg-surface dark:bg-surface-dark border dark:border-surface-dark-border rounded-xl p-4 shadow-sm mb-4 transition-all">
      <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-5 gap-4">
        <!-- Date Range -->
        <div class="space-y-1.5">
          <label class="text-xs font-bold text-slate-600 dark:text-slate-300">Date Range</label>
          <FormDate v-model="dateRange" mode="range" placeholder="Select dates..." class="w-full" />
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

        <!-- Client -->
        <div class="space-y-1.5">
          <label class="text-xs font-bold text-slate-600 dark:text-slate-300">Client</label>
          <FormSelect v-model="filters.client_id" :options="clientOpts" class="w-full" />
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
      :total="total"
      :server-side="true"
      :page="page"
      :page-size="pageSize"
      :loading="loading"
      :selectable="true"
      @update="onQuery"
      @bulk-delete="handleBulkDelete"
    >
      <template #cell-sequence="{ index }">
        <span class="font-bold text-slate-500">{{ (page - 1) * pageSize + index + 1 }}</span>
      </template>

      <template #cell-id="{ value }">
        <span class="font-black text-[#0ab39c] dark:text-[#0ab39c]">#{{ value }}</span>
      </template>

      <template #cell-name="{ value }">
        <span class="font-semibold text-slate-800 dark:text-slate-200">{{ value }}</span>
      </template>

      <template #cell-status="{ value }">
        <StatusBadge :status="value" />
      </template>

      <template #cell-start_date="{ value }">
        <span class="text-slate-600 dark:text-slate-400">{{ value || '—' }}</span>
      </template>

      <template #cell-end_date="{ value }">
        <span class="text-slate-600 dark:text-slate-400">{{ value || '—' }}</span>
      </template>

      <template #cell-to_location_name="{ value }">
        <span class="font-medium inline-flex items-center gap-1.5"><i class="ri-map-pin-fill text-green-500 text-[11px] shrink-0"></i> {{ value || '—' }}</span>
      </template>

      <template #cell-client_name="{ value }">
        <span class="font-extrabold text-slate-800 dark:text-white">{{ value || '—' }}</span>
      </template>

      <template #cell-selected_hour="{ value }">
        <span class="font-medium text-slate-700 dark:text-slate-300"><i class="ri-time-line text-primary-500 mr-1"></i>{{ value || '—' }}</span>
      </template>

      <template #cell-task_type="{ value }">
        <span class="px-2 py-1 bg-slate-100 dark:bg-surface-dark-solid border dark:border-surface-dark-border rounded text-[11px] font-semibold text-slate-600 dark:text-slate-300">
          {{ value || '—' }}
        </span>
      </template>

      <template #cell-selected_days="{ value }">
        <span class="text-[11px] font-semibold text-primary-600 dark:text-primary-400">{{ formatDays(value) || '—' }}</span>
      </template>

      <template #cell-added_by="{ value }">
        <span class="text-slate-600 dark:text-slate-400">{{ value || '—' }}</span>
      </template>

      <template #cell-driver_name="{ value }">
        <div v-if="value" class="flex items-center gap-2">
          <BaseAvatar :name="value" :size="26" />
          <span class="text-[12.5px] font-medium text-ink dark:text-slate-200 whitespace-nowrap">{{ value }}</span>
        </div>
        <span v-else class="text-slate-400">—</span>
      </template>

      <template #cell-actions="{ row }">
        <div class="flex items-center justify-center gap-1">
          <a v-if="can('scheduled_task_edit')" :href="`/admin/scheduled-tasks/${row.id}/edit`" class="w-7 h-7 rounded bg-primary-50 text-primary-600 hover:bg-primary-100 dark:bg-primary-500/10 dark:text-primary-400 dark:hover:bg-primary-500/20 transition grid place-items-center"><i class="ri-pencil-line"></i></a>
          <button v-if="can('scheduled_task_delete')" @click="askDelete(row)" class="w-7 h-7 rounded bg-danger/10 text-danger hover:bg-danger/20 transition grid place-items-center"><i class="ri-delete-bin-line"></i></button>
        </div>
      </template>
    </DataTable>

    <!-- delete confirm -->
    <BaseModal v-model="showDel" title="Confirm delete" icon="ri-error-warning-line" tone="danger" size="sm">
      <p class="text-sm text-slate-600 dark:text-slate-300">
        Are you sure you want to delete <span class="font-semibold text-ink dark:text-slate-100">Scheduled Task #{{ delTarget?.id }}</span>?
        This action cannot be undone.
      </p>
      <p class="text-sm text-slate-400 mt-1.5" dir="rtl">هل أنت متأكد من رغبتك في إتمام عملية الحذف؟</p>
      <template #footer>
        <BaseButton variant="light" @click="showDel = false">Cancel</BaseButton>
        <BaseButton variant="danger" @click="confirmDelete">Yes, delete it</BaseButton>
      </template>
    </BaseModal>

    <!-- bulk delete confirm -->
    <BaseModal v-model="showBulkDel" title="Confirm Bulk Delete" icon="ri-error-warning-line" tone="danger" size="md">
      <p class="text-sm text-slate-600 dark:text-slate-300">
        Are you sure you want to delete <span class="font-semibold text-ink dark:text-slate-100">{{ bulkDelTargets.length }} Scheduled Tasks</span>?
        This action cannot be undone.
      </p>
      
      <!-- List of Task IDs -->
      <div class="mt-3 max-h-32 overflow-y-auto p-2 bg-slate-50 dark:bg-black/20 rounded-lg border border-slate-100 dark:border-white/5 flex flex-wrap gap-1.5">
        <span v-for="id in bulkDelTargets" :key="id" class="px-2 py-0.5 bg-danger/10 text-danger rounded text-[11px] font-mono font-bold">
          #{{ id }}
        </span>
      </div>

      <p class="text-sm text-slate-500 mt-4 font-medium" dir="rtl">هل أنت متأكد من رغبتك في إتمام عملية الحذف المتعدد لهذه المهام؟</p>
      <template #footer>
        <BaseButton variant="light" @click="showBulkDel = false">Cancel</BaseButton>
        <BaseButton variant="danger" @click="confirmBulkDelete">Yes, delete them</BaseButton>
      </template>
    </BaseModal>
  </div>
</template>
