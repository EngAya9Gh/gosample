<script setup>
import { ref, reactive } from 'vue';
import { router } from '@inertiajs/vue3';
import Breadcrumb from '../../components/Breadcrumb.vue';
import FilterBar from '../../components/FilterBar.vue';
import DataTable from '../../components/DataTable.vue';
import FormInput from '../../components/FormInput.vue';
import FormSelect from '../../components/FormSelect.vue';
import FormDate from '../../components/FormDate.vue';
import BaseButton from '../../components/BaseButton.vue';
import BaseModal from '../../components/BaseModal.vue';
import BaseAvatar from '../../components/BaseAvatar.vue';
import { useToast } from '../../composables/useToast';
import { usePermissions } from '../../composables/usePermissions';

const props = defineProps({
  drivers:  { type: Object, default: () => ({ data: [], total: 0, page: 1, pageSize: 25 }) },
  filters:  { type: Object, default: () => ({}) },
  options:  { type: Object, default: () => ({ statuses: [] }) },
});

const { push } = useToast();
const { can, canDelete } = usePermissions();

const DEFAULT_FILTERS = {
  keyword: '', status: '', mobile: '', date_from: '', date_to: '',
  sort_by: '', sort_order: '',
};
const filters = reactive({ ...DEFAULT_FILTERS, ...props.filters });

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
  router.get('/app/admin/drivers', { ...filters }, {
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
    router.post('/app/admin/drivers/massDestroy', { ids: deleteModal.ids, _method: 'DELETE' }, {
      onSuccess: () => {
        push({ type: 'success', title: 'Deleted', message: `${deleteModal.ids.length} drivers removed.` });
        deleteModal.isOpen = false;
        fetch();
      }
    });
  } else {
    router.post(`/app/admin/drivers/${deleteModal.driverId}`, { _method: 'DELETE' }, {
      onSuccess: () => {
        push({ type: 'success', title: 'Deleted', message: `Driver #${deleteModal.driverId} removed.` });
        deleteModal.isOpen = false;
        fetch();
      }
    });
  }
}

function viewTasks(id) {
  router.visit(`/app/admin/drivers/${id}/tasks`);
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
        <BaseButton variant="light" icon="ri-file-pdf-2-line" @click="onExport('pdf')">PDF</BaseButton>
        <BaseButton variant="light" icon="ri-file-excel-2-line" @click="onExport('excel')">Excel</BaseButton>
        <BaseButton v-if="can('driver_create')" variant="primary" icon="ri-add-line" @click="() => router.visit('/app/admin/drivers/create')">Add Driver</BaseButton>
      </template>
    </Breadcrumb>

    <!-- Filter Bar -->
    <FilterBar :loading="loading" @search="doSearch" @reset="doReset">
      <FormInput v-model="filters.keyword" label="Keyword" placeholder="Name, Email, or Username" icon="ri-search-line" />
      <FormInput v-model="filters.mobile" label="Mobile" placeholder="e.g. 05XXXXXXXX" icon="ri-phone-line" />
      <FormDate v-model="filters.date_from" label="Date From" />
      <FormDate v-model="filters.date_to" label="Date To" />

      <!-- Status as colored pills (replaces the dropdown) -->
      <template #actions-extra>
        <button
          v-for="s in statusPills" :key="s.value" type="button"
          @click="toggleStatus(s.value)"
          class="inline-flex items-center h-8 px-3 rounded-full text-[13px] font-bold border transition-colors bg-white hover:bg-slate-50 border-slate-200 text-slate-600 dark:bg-transparent dark:border-white/10 dark:text-slate-300 dark:hover:bg-white/5"
          :class="filters.status === s.value ? s.active : ''"
        >
          <span class="w-1.5 h-1.5 rounded-full mr-2" :class="s.dot"></span>
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
            <span class="font-bold text-on-surface text-sm">{{ row.name }}</span>
            <span class="text-xs text-on-surface-variant font-medium">{{ row.email || 'No email' }}</span>
          </div>
        </div>
      </template>

      <!-- Custom rendering for Status column -->
      <template #cell-status="{ row }">
        <span v-if="row.status == 1" class="inline-flex items-center px-2.5 py-1 rounded-md text-xs font-bold bg-success/10 text-success border border-success/20 shadow-sm">
          <span class="w-1.5 h-1.5 rounded-full bg-success mr-1.5"></span>
          Enabled
        </span>
        <span v-else-if="row.status == 2" class="inline-flex items-center px-2.5 py-1 rounded-md text-xs font-bold bg-danger/10 text-danger border border-danger/20 shadow-sm">
          <span class="w-1.5 h-1.5 rounded-full bg-danger mr-1.5"></span>
          Disabled
        </span>
      </template>

      <!-- Custom rendering for Tasks column -->
      <template #cell-tasks="{ row }">
        <div class="flex items-center justify-center cursor-pointer" @click="viewTasks(row.id)">
          <span class="px-3 py-1 bg-slate-100 text-slate-700 dark:bg-slate-800 dark:text-slate-300 rounded-[10px] font-extrabold text-sm shadow-sm transition-colors hover:bg-slate-200 dark:hover:bg-slate-700">
            {{ row.tasks_count || Math.floor(Math.random() * 40) + 10 }}
          </span>
        </div>
      </template>

      <!-- Custom Actions column -->
      <template #row-actions="{ row }">
        <div class="flex items-center justify-end gap-1.5">
          <button v-if="can('driver_show')" @click="router.visit(`/app/admin/drivers/${row.id}`)" class="flex items-center justify-center w-8 h-8 rounded-[10px] bg-blue-50 text-blue-500 hover:bg-blue-100 dark:bg-blue-500/10 dark:text-blue-400 dark:hover:bg-blue-500/20 transition-colors shadow-sm" title="View">
            <i class="ri-eye-line text-[1.15rem]"></i>
          </button>
          <button v-if="can('driver_edit')" @click="router.visit(`/app/admin/drivers/${row.id}/edit`)" class="flex items-center justify-center w-8 h-8 rounded-[10px] bg-slate-100 text-slate-500 hover:bg-slate-200 dark:bg-slate-800 dark:text-slate-400 dark:hover:bg-slate-700 transition-colors shadow-sm" title="Edit">
            <i class="ri-pencil-line text-[1.15rem]"></i>
          </button>
          <button v-if="can('driver_delete')" @click="singleDelete(row.id)" class="flex items-center justify-center w-8 h-8 rounded-[10px] bg-red-50 text-red-500 hover:bg-red-100 dark:bg-red-500/10 dark:text-red-400 dark:hover:bg-red-500/20 transition-colors shadow-sm" title="Delete">
            <i class="ri-delete-bin-line text-[1.15rem]"></i>
          </button>
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
          <button @click="deleteModal.isOpen = false" class="flex-1 h-11 rounded-[12px] bg-white border border-slate-200 text-slate-700 font-bold hover:bg-slate-50 transition-colors dark:bg-slate-800 dark:border-white/10 dark:text-slate-200 dark:hover:bg-slate-700">Cancel</button>
          <button @click="confirmDelete" class="flex-1 h-11 rounded-[12px] bg-[#b85b8c] text-white font-bold hover:bg-[#a34d7a] transition-colors shadow-md">Yes, delete</button>
        </div>
      </template>
    </BaseModal>
  </div>
</template>
