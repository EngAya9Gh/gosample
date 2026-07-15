<script setup>
import { ref, reactive } from 'vue';
import { router, useForm } from '@inertiajs/vue3';
import axios from 'axios';
import Breadcrumb from '../../components/Breadcrumb.vue';
import FilterBar from '../../components/FilterBar.vue';
import DataTable from '../../components/DataTable.vue';
import FormInput from '../../components/FormInput.vue';
import FormSelect from '../../components/FormSelect.vue';
import FormDate from '../../components/FormDate.vue';
import BaseButton from '../../components/BaseButton.vue';
import BaseModal from '../../components/BaseModal.vue';
import BaseAvatar from '../../components/BaseAvatar.vue';
import StatusBadge from '../../components/StatusBadge.vue';
import DriverFormFields from './DriverFormFields.vue';
import { useToast } from '../../composables/useToast';
import { usePermissions } from '../../composables/usePermissions';

const props = defineProps({
  drivers:  { type: Object, default: () => ({ data: [], total: 0, page: 1, pageSize: 25 }) },
  filters:  { type: Object, default: () => ({}) },
  options:  { type: Object, default: () => ({ statuses: [] }) },
  zones:          { type: Array, default: () => [] },
  shiftTemplates: { type: Array, default: () => [] },
});

const { push } = useToast();
const { can, canDelete } = usePermissions();

/* ---------- Add / Edit Driver modals (fields in DriverFormFields.vue) ---------- */
// Build the form payload from a driver record (or defaults) — mirrors DriverForm.vue.
function driverFormData(d) {
  return {
    name: d?.name || '',
    username: d?.username || '',
    password: '',
    mobile: d?.mobile || '',
    email: d?.email || '',
    national_id: d?.national_id || '',
    language: d?.language || 'en',
    status: (d?.status !== undefined && d?.status !== null) ? String(d.status) : '1',
    zone_id: d?.zone_id || '',
    employment_type: d?.employment_type || 'full_time',
    shift_count: d?.shift_count ? String(d.shift_count) : '1',
    total_working_hours: d?.total_working_hours || 8,
    working_hours_start: d?.working_hours_start ? d.working_hours_start.slice(0, 5) : '',
    working_hours_end: d?.working_hours_end ? d.working_hours_end.slice(0, 5) : '',
    second_shift_working_hours_start: d?.second_shift_working_hours_start ? d.second_shift_working_hours_start.slice(0, 5) : '',
    second_shift_working_hours_end: d?.second_shift_working_hours_end ? d.second_shift_working_hours_end.slice(0, 5) : '',
    third_shift_working_hours_start: d?.third_shift_working_hours_start ? d.third_shift_working_hours_start.slice(0, 5) : '',
    third_shift_working_hours_end: d?.third_shift_working_hours_end ? d.third_shift_working_hours_end.slice(0, 5) : '',
  };
}

const driverModal = reactive({ open: false, isEdit: false, id: null, loading: false });
const driverForm = useForm(driverFormData(null));

function openCreateDriver() {
  if (!can('driver_create')) return;
  driverModal.isEdit = false;
  driverModal.id = null;
  driverModal.loading = false;
  Object.assign(driverForm, driverFormData(null));
  driverForm.clearErrors();
  driverModal.open = true;
}

async function openEditDriver(row) {
  if (!can('driver_edit')) return;
  driverModal.isEdit = true;
  driverModal.id = row.id;
  driverModal.loading = true;
  driverModal.open = true;
  driverForm.clearErrors();
  try {
    const { data } = await axios.get(`/admin/drivers/${row.id}/data`);
    Object.assign(driverForm, driverFormData(data));
  } catch (e) {
    driverModal.open = false;
    push({ type: 'error', title: 'Could not load', message: 'Failed to load driver for editing.' });
  } finally {
    driverModal.loading = false;
  }
}

function submitDriver() {
  const onSuccess = () => { driverModal.open = false; }; // backend flash → toast
  if (driverModal.isEdit) {
    driverForm.put(`/admin/drivers/${driverModal.id}`, { onSuccess });
  } else {
    driverForm.post('/admin/drivers', { onSuccess });
  }
}

const DEFAULT_FILTERS = {
  keyword: '', status: '', mobile: '', date_from: '', date_to: '',
  sort_by: '', sort_order: '',
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

const statusPills = [
  { value: '1', label: 'Enabled', dot: 'bg-green-500', active: 'bg-green-500/10 border-green-500/40 text-green-600 dark:text-green-400' },
  { value: '2', label: 'Disabled', dot: 'bg-red-500', active: 'bg-red-500/10 border-red-500/40 text-red-600 dark:text-red-400' }
];

function toggleStatus(v) {
  filters.status = filters.status === String(v) ? '' : String(v);
  doSearch();
}

const columns = [
  { key: 'id',       label: 'ID',       sortable: true, sticky: 'start', width: '84px' },
  { key: 'name',     label: 'Driver Name', sortable: true },
  { key: 'status',   label: 'Status',   sortable: true },
  { key: 'username', label: 'Username', sortable: true },
  { key: 'mobile',   label: 'Mobile',   sortable: true },
  { key: 'tasks',    label: 'Tasks',    align: 'center' },
];

const loading = ref(false);

function fetch() {
  loading.value = true;
  router.get('/admin/drivers', { ...filters }, {
    preserveState: true,
    preserveScroll: true,
    onFinish: () => loading.value = false,
  });
}

function doSearch() {
  filters.page = 1;
  fetch();
}
function doReset() {
  Object.assign(filters, DEFAULT_FILTERS);
  dateRange.value = '';
  fetch();
}
function doPage(p) {
  filters.page = p;
  fetch();
}
function doSort(col, order) {
  filters.sort_by = col;
  filters.sort_order = order;
  fetch();
}

const deleteModal = reactive({
  isOpen: false,
  driverId: null,
  isBulk: false,
  ids: []
});

function bulkDelete(ids) {
  if (!ids.length) return;
  deleteModal.isBulk = true;
  deleteModal.ids = ids;
  deleteModal.isOpen = true;
}

function singleDelete(id) {
  deleteModal.isBulk = false;
  deleteModal.driverId = id;
  deleteModal.isOpen = true;
}

function confirmDelete() {
  if (deleteModal.isBulk) {
    router.post('/admin/drivers/massDestroy', { ids: deleteModal.ids, _method: 'DELETE' }, {
      onSuccess: () => {
        push({ type: 'success', title: 'Deleted', message: `${deleteModal.ids.length} drivers removed.` });
        deleteModal.isOpen = false;
        fetch();
      }
    });
  } else {
    router.post(`/admin/drivers/${deleteModal.driverId}`, { _method: 'DELETE' }, {
      onSuccess: () => {
        push({ type: 'success', title: 'Deleted', message: `Driver #${deleteModal.driverId} removed.` });
        deleteModal.isOpen = false;
        fetch();
      }
    });
  }
}

function viewTasks(id) {
  router.visit(`/admin/drivers/${id}/tasks`);
}

function onExport(format) {
  push({ type: 'info', title: 'Exporting', message: `Exporting drivers to ${format.toUpperCase()}...` });
  // Implement real export logic here
}
</script>

<template>
  <div>
    <Breadcrumb title="Drivers" :trail="[{ label: 'Drivers' }]">
      <template #actions>
        <BaseButton variant="pdf" icon="ri-file-pdf-2-line" @click="onExport('pdf')">PDF</BaseButton>
        <BaseButton variant="excel" icon="ri-file-excel-2-line" @click="onExport('excel')">Excel</BaseButton>
        <BaseButton v-if="can('driver_create')" variant="primary" icon="ri-add-line" @click="openCreateDriver">Add Driver</BaseButton>
      </template>
    </Breadcrumb>

    <!-- Filter Bar -->
    <FilterBar :loading="loading" @search="doSearch" @reset="doReset">
      <FormInput v-model="filters.keyword" label="Keyword" placeholder="Name, Email, or Username" icon="ri-search-line" />
      <FormInput v-model="filters.mobile" label="Mobile" placeholder="e.g. 05XXXXXXXX" icon="ri-phone-line" />
      <FormDate v-model="dateRange" label="Date Range" mode="range" placeholder="Select range" @range="onDateRange" />

      <!-- Status as colored pills (replaces the dropdown) -->
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

    <!-- Data Table -->
    <DataTable
      :columns="columns"
      :rows="drivers.data"
      row-key="id"
      :loading="loading"
      :total="drivers.total"
      :page="drivers.page"
      :page-size="drivers.pageSize"
      :sort-by="filters.sort_by"
      :sort-order="filters.sort_order"
      :bulk-actions="canDelete() ? [{ label: 'Delete', icon: 'ri-delete-bin-line', tone: 'danger', event: 'bulk-delete' }] : []"
      @bulk-delete="bulkDelete"
      @page="doPage"
      @sort="doSort"
    >
      <!-- Custom rendering for ID column -->
      <template #cell-id="{ row }">
        <span class="font-bold text-primary-500 dark:text-primary-300">#{{ row.id }}</span>
      </template>

      <!-- Custom rendering for Name and Avatar -->
      <template #cell-name="{ row }">
        <div class="flex items-center gap-3">
          <BaseAvatar :name="row.name" :size="36" />
          <div class="flex flex-col">
            <span class="font-bold text-ink dark:text-slate-100 text-sm">{{ row.name }}</span>
            <span class="text-xs text-slate-500 dark:text-slate-400 font-medium mt-0.5">{{ row.email || 'No email' }}</span>
          </div>
        </div>
      </template>

      <!-- Custom rendering for Status column -->
      <template #cell-status="{ row }">
        <StatusBadge :status="row.status == 1 ? 'ENABLED' : 'DISABLED'" />
      </template>

      <!-- Custom rendering for Tasks column -->
      <template #cell-tasks="{ row }">
        <div class="flex items-center justify-center cursor-pointer" @click="viewTasks(row.id)">
          <span class="px-3 py-1 bg-slate-100 text-slate-700 dark:bg-white/5 dark:text-slate-300 rounded-[10px] font-extrabold text-sm shadow-sm transition-colors hover:bg-slate-200 dark:hover:bg-white/10">
            {{ row.tasks_count || 0 }}
          </span>
        </div>
      </template>

      <!-- Custom Actions column -->
      <template #row-actions="{ row }">
        <div class="inline-flex items-center gap-1">
          <button v-if="can('driver_show')" @click="router.visit(`/admin/drivers/${row.id}`)" class="grid place-items-center w-8 h-8 rounded-lg text-info hover:bg-info/10 transition" title="View"><i class="ri-eye-line"></i></button>
          <button v-if="can('driver_edit')" @click="openEditDriver(row)" class="grid place-items-center w-8 h-8 rounded-lg text-primary-600 hover:bg-primary-50 dark:hover:bg-primary-500/10 transition" title="Edit"><i class="ri-pencil-line"></i></button>
          <button v-if="can('driver_delete')" @click="singleDelete(row.id)" class="grid place-items-center w-8 h-8 rounded-lg text-danger hover:bg-danger/10 transition" title="Delete"><i class="ri-delete-bin-line"></i></button>
        </div>
      </template>
    </DataTable>

    <!-- Delete Confirmation Modal -->
    <BaseModal v-model="deleteModal.isOpen" size="sm">
      <div class="text-center py-4">
        <div class="w-[72px] h-[72px] bg-[#fdf2f8] text-[#db2777] rounded-full flex items-center justify-center mx-auto mb-5">
          <i class="ri-delete-bin-line text-[32px]"></i>
        </div>
        <h3 class="text-[22px] font-extrabold text-slate-800 dark:text-white mb-2">Are you sure?</h3>
        <p class="text-slate-600 dark:text-slate-300 font-medium text-base mb-1">هل أنت متأكد من رغبتك في إتمام عملية الحذف؟</p>
        <p class="text-[13px] text-slate-500 mt-2">
          {{ deleteModal.isBulk ? `${deleteModal.ids.length} selected records will be removed. This action cannot be undone.` : 'This record will be removed. This action cannot be undone.' }}
        </p>
      </div>
      <template #footer>
        <div class="flex gap-4 w-full px-2 pb-2">
          <button @click="deleteModal.isOpen = false" class="flex-1 h-11 rounded-[12px] bg-white border border-slate-200 text-slate-700 font-bold hover:bg-slate-50 transition-colors dark:bg-white/5 dark:border-white/10 dark:text-slate-200 dark:hover:bg-white/10">Cancel</button>
          <button @click="confirmDelete" class="flex-1 h-11 rounded-[12px] bg-[#b85b8c] text-white font-bold hover:bg-[#a34d7a] transition-colors shadow-md">Yes, delete</button>
        </div>
      </template>
    </BaseModal>

    <!-- Add / Edit Driver Modal -->
    <BaseModal v-model="driverModal.open" :title="driverModal.isEdit ? 'Edit Driver' : 'Add Driver'" :icon="driverModal.isEdit ? 'ri-pencil-line' : 'ri-user-add-line'" size="xl">
      <div v-if="driverModal.loading" class="grid place-items-center py-16 text-slate-400">
        <i class="ri-loader-4-line text-3xl animate-spin"></i>
        <p class="text-sm mt-2">Loading driver…</p>
      </div>
      <form v-else @submit.prevent="submitDriver">
        <DriverFormFields :form="driverForm" :zones="zones" :shift-templates="shiftTemplates" :is-edit="driverModal.isEdit" floating />
      </form>
      <template #footer>
        <BaseButton variant="light" @click="driverModal.open = false" :disabled="driverForm.processing">Cancel</BaseButton>
        <BaseButton variant="primary" icon="ri-save-line" :loading="driverForm.processing" @click="submitDriver">
          {{ driverModal.isEdit ? 'Save Changes' : 'Create Driver' }}
        </BaseButton>
      </template>
    </BaseModal>
  </div>
</template>
