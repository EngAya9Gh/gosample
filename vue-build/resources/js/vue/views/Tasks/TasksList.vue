<script setup>
/**
 * views/Tasks/TasksList.vue — master task table.
 * Demonstrates the FilterBar + DataTable + StatusBadge + soft action buttons
 * + delete-confirm modal pattern used across every list screen.
 *
 * All 16 columns from the handoff are present; wide table scrolls horizontally,
 * Sequence/ID stick to the start, Actions sticks to the end.
 */
import { ref } from 'vue';
import Breadcrumb from '../../components/Breadcrumb.vue';
import FilterBar from '../../components/FilterBar.vue';
import DataTable from '../../components/DataTable.vue';
import FormInput from '../../components/FormInput.vue';
import FormSelect from '../../components/FormSelect.vue';
import StatusBadge from '../../components/StatusBadge.vue';
import BaseButton from '../../components/BaseButton.vue';
import BaseAvatar from '../../components/BaseAvatar.vue';
import BaseModal from '../../components/BaseModal.vue';
import { useToast } from '../../composables/useToast';
import { usePermissions } from '../../composables/usePermissions';

const { push } = useToast();
const { can, canDelete } = usePermissions();

const loading = ref(false);
const filters = ref({ keyword: '', status: '', driver: '', client: '', from: '', to: '' });

const statusOpts = ['NEW','COLLECTED','IN_CONTAINER','OUT_CONTAINER','CLOSED','NO_SAMPLES'].map((v) => ({ value: v, label: v.replace(/_/g,' ') }));
const driverOpts = ['Mohammed Al-Harbi','Fatimah Nasser','Khalid Otaibi','Sara Al-Dosari'].map((v) => ({ value: v, label: v }));
const clientOpts = ['King Faisal Lab','Al-Noor Clinic','Dallah Hospital','Saudi German'].map((v) => ({ value: v, label: v }));

const columns = [
  { key: 'seq',       label: '#',           sticky: 'start', width: '56px' },
  { key: 'id',        label: 'ID',          sortable: true, sticky: 'start', width: '84px' },
  { key: 'orderDate', label: 'Order Date',  sortable: true },
  { key: 'client',    label: 'Client' },
  { key: 'driver',    label: 'Driver' },
  { key: 'from',      label: 'From Location' },
  { key: 'to',        label: 'To Location' },
  { key: 'eta',       label: 'ETA',         align: 'center' },
  { key: 'collection',label: 'Collection',  sortable: true },
  { key: 'container', label: 'Container' },
  { key: 'containerOut', label: 'Container Out' },
  { key: 'close',     label: 'Close Date' },
  { key: 'status',    label: 'Status' },
  { key: 'type',      label: 'Task Type' },
  { key: 'addedBy',   label: 'Added By' },
  { key: 'hours',     label: 'Hours',       align: 'center' },
];

// mock rows
const STAT = ['NEW','COLLECTED','IN_CONTAINER','OUT_CONTAINER','CLOSED','NO_SAMPLES'];
const rows = ref(Array.from({ length: 38 }, (_, i) => {
  const st = STAT[i % STAT.length];
  return {
    id: 10428 - i, seq: i + 1, orderDate: '2026-06-' + String(27 - (i % 26)).padStart(2,'0'),
    client: clientOpts[i % 4].label, driver: driverOpts[i % 4].label,
    from: ['King Faisal Lab','Al-Noor Clinic','Dallah Hosp.','Saudi German'][i % 4],
    to: ['Central Hub','Lab East','Lab West','North Depot'][i % 4],
    eta: (12 + (i * 7) % 40) + 'm', collection: '08:' + String(10 + i % 49).padStart(2,'0'),
    container: '09:' + String(5 + i % 50).padStart(2,'0'), containerOut: '11:' + String(i % 59).padStart(2,'0'),
    close: st === 'CLOSED' ? '12:' + String(i % 59).padStart(2,'0') : '—',
    status: st, type: ['Pickup','Delivery','Round-trip'][i % 3],
    addedBy: ['System','Sara O.','Auto-sched'][i % 3], hours: (1 + (i % 6)) + '.' + (i % 9),
  };
}));

const delTarget = ref(null);
const showDel = ref(false);

function doSearch() {
  loading.value = true;
  setTimeout(() => { loading.value = false; push({ type: 'success', title: 'Filters applied', message: rows.value.length + ' tasks found' }); }, 700);
}
function doReset() { filters.value = { keyword: '', status: '', driver: '', client: '', from: '', to: '' }; }
function askDelete(row) { delTarget.value = row; showDel.value = true; }
function confirmDelete() {
  rows.value = rows.value.filter((r) => r.id !== delTarget.value.id);
  showDel.value = false;
  push({ type: 'success', title: 'Deleted', message: `Task #${delTarget.value.id} removed` });
}
function bulkDelete(ids) {
  rows.value = rows.value.filter((r) => !ids.includes(r.id));
  push({ type: 'success', title: 'Bulk delete', message: `${ids.length} tasks removed` });
}
function onExport(kind) { push({ type: 'info', title: 'Export', message: `Generating ${kind.toUpperCase()}…` }); }
</script>

<template>
  <div>
    <Breadcrumb title="Tasks" :trail="[{ label: 'Tasks' }]">
      <template #actions>
        <BaseButton variant="light" icon="ri-file-pdf-2-line" @click="onExport('pdf')">PDF</BaseButton>
        <BaseButton variant="light" icon="ri-file-excel-2-line" @click="onExport('excel')">Excel</BaseButton>
        <BaseButton v-if="can('task_create')" variant="primary" icon="ri-add-line">Add Task</BaseButton>
      </template>
    </Breadcrumb>

    <!-- filter bar -->
    <FilterBar :loading="loading" @search="doSearch" @reset="doReset">
      <FormInput v-model="filters.keyword" label="Keyword" placeholder="Task ID or keyword" icon="ri-search-line" />
      <FormSelect v-model="filters.status" label="Status" :options="statusOpts" placeholder="Any status" />
      <FormSelect v-model="filters.driver" label="Driver" :options="driverOpts" placeholder="Any driver" />
      <FormSelect v-model="filters.client" label="Billing Client" :options="clientOpts" placeholder="Any client" />
      <FormInput v-model="filters.from" label="Date From" type="date" />
      <FormInput v-model="filters.to" label="Date To" type="date" />
    </FilterBar>

    <!-- data table -->
    <DataTable
      :columns="columns" :rows="rows" row-key="id"
      :loading="loading"
      :bulk-actions="canDelete() ? [{ label: 'Delete', icon: 'ri-delete-bin-line', tone: 'danger', event: 'bulk-delete' }] : []"
      @bulk-delete="bulkDelete" @export="onExport"
    >
      <!-- custom cells -->
      <template #cell-id="{ value }">
        <span class="font-semibold text-primary-700 dark:text-primary-300">#{{ value }}</span>
      </template>
      <template #cell-driver="{ value }">
        <span class="inline-flex items-center gap-2">
          <BaseAvatar :name="value" :size="26" /><span class="truncate">{{ value }}</span>
        </span>
      </template>
      <template #cell-eta="{ value }">
        <span class="inline-flex items-center justify-center min-w-12 h-6 px-2 rounded-full bg-primary-50 text-primary-700 dark:bg-primary-500/15 dark:text-primary-300 text-xs font-semibold">{{ value }}</span>
      </template>
      <template #cell-status="{ value }">
        <StatusBadge :status="value" />
      </template>

      <!-- row actions -->
      <template #row-actions="{ row }">
        <div class="inline-flex items-center gap-1">
          <button v-if="can('task_show')" class="grid place-items-center w-8 h-8 rounded-lg text-info hover:bg-info/10 transition" title="View"><i class="ri-eye-line"></i></button>
          <button v-if="can('task_edit')" class="grid place-items-center w-8 h-8 rounded-lg text-primary-600 hover:bg-primary-50 dark:hover:bg-primary-500/10 transition" title="Edit"><i class="ri-pencil-line"></i></button>
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
