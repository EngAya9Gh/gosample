<script setup>
import { ref, watch, onMounted, computed } from 'vue';
import { usePage, Link, router, useForm } from '@inertiajs/vue3';
import axios from 'axios';
import debounce from 'lodash/debounce';

import Breadcrumb from '../../components/Breadcrumb.vue';
import DataTable from '../../components/DataTable.vue';
import BaseAvatar from '../../components/BaseAvatar.vue';
import BaseModal from '../../components/BaseModal.vue';
import BaseButton from '../../components/BaseButton.vue';
import FormDate from '../../components/FormDate.vue';
import StatusBadge from '../../components/StatusBadge.vue';
import FormSelect from '../../components/FormSelect.vue';

const props = defineProps({
  initialRows: Array,
  initialTotal: Number,
  drivers: { type: Object, default: () => ({}) },
  tasks: { type: Object, default: () => ({}) },
});

const DEFAULT_FILTERS = { keyword: '', date_from: '', date_to: '', sortBy: 'id', sortOrder: 'desc' };
const searchForm = ref({ ...DEFAULT_FILTERS });

const dateRange = ref('');
function onDateRange({ from, to }) {
  searchForm.value.date_from = from || '';
  searchForm.value.date_to = to || '';
}

function resetFilters() {
  searchForm.value = { ...DEFAULT_FILTERS };
  dateRange.value = '';
  doSearch(1, 25);
}

const rows = ref([]);
const total = ref(0);
const loading = ref(false);

const columns = [
  { key: 'id',          label: 'ID',             sortable: true, width: '60px' },
  { key: 'task_id',     label: 'Task ID' },
  { key: 'driverA',     label: 'From Driver' },
  { key: 'driver',      label: 'To Driver' },
  { key: 'status',      label: 'Status' },
  { key: 'created_at',  label: 'Created At',     sortable: true },
  { key: 'actions',     label: '',               align: 'right' },
];

const onQuery = ({ page, pageSize, sortKey, sortDir, q }) => {
  if (sortKey) searchForm.value.sortBy = sortKey;
  if (sortDir) searchForm.value.sortOrder = sortDir;
  if (q !== undefined) searchForm.value.keyword = q;
  doSearch(page, pageSize);
};

const doSearch = debounce(async (page = 1, pageSize = 25) => {
  loading.value = true;
  try {
    const params = new URLSearchParams();
    if (searchForm.value.keyword) params.append('keyword', searchForm.value.keyword);
    if (searchForm.value.date_from) params.append('date_from', searchForm.value.date_from);
    if (searchForm.value.date_to) params.append('date_to', searchForm.value.date_to);
    
    params.append('sortBy', searchForm.value.sortBy);
    params.append('sortOrder', searchForm.value.sortOrder);
    params.append('page', page);
    params.append('pageSize', pageSize);

    const { data } = await axios.get(`/admin/swaprequests?${params.toString()}`, {
      headers: { Accept: 'application/json' },
    });
    
    rows.value = data.rows;
    total.value = data.total;
  } catch (error) {
    console.error('Error fetching swap requests:', error);
  } finally {
    loading.value = false;
  }
}, 300);

watch(
  () => [searchForm.value.keyword, searchForm.value.date_from, searchForm.value.date_to],
  () => doSearch(1, 25),
  { deep: true }
);

onMounted(() => {
  rows.value = props.initialRows || [];
  total.value = props.initialTotal || 0;
  if (!rows.value.length && total.value > 0) {
    doSearch(1, 25);
  }
});

const viewModalOpen = ref(false);
const selectedSwap = ref(null);

const openViewModal = (swap) => {
  selectedSwap.value = swap;
  viewModalOpen.value = true;
};

const closeViewModal = () => {
  viewModalOpen.value = false;
  setTimeout(() => { selectedSwap.value = null; }, 300);
};

const deleteSwap = (id) => {
  if (confirm('Are you sure you want to delete this Swap Request?')) {
    router.delete(`/admin/swaprequests/${id}`, {
      onSuccess: () => doSearch()
    });
  }
};

const formatDate = (dateString) => {
  if (!dateString) return '—';
  const date = new Date(dateString);
  return date.toLocaleString('en-GB', { 
    year: 'numeric', month: 'short', day: 'numeric',
    hour: '2-digit', minute: '2-digit'
  });
};

const getStatusColor = (status) => {
  const map = {
    'new': 'blue',
    'accepted': 'emerald',
    'rejected': 'danger'
  };
  return map[status] || 'slate';
};

// Form logic for Create / Edit Modal
const formModalOpen = ref(false);
const isEdit = ref(false);
const editingId = ref(null);
const editingSwap = ref(null);

const driverOptions = computed(() => {
  return Object.entries(props.drivers || {})
    .filter(([value, label]) => value !== '')
    .map(([value, label]) => ({ value: Number(value), label }));
});

const form = useForm({
  driver_a: '',
  driver_id: '',
  task_id: [],
  status: 'new',
});

const dynamicTasks = ref([]);
const tasksLoading = ref(false);

watch(() => form.driver_a, async (newDriverId) => {
  if (!newDriverId) {
    dynamicTasks.value = [];
    return;
  }
  tasksLoading.value = true;
  try {
    const { data } = await axios.post('/api/swap/tasks/list', { driver_id: newDriverId });
    if (data.status) {
      dynamicTasks.value = data.data || [];
    }
  } catch (error) {
    console.error('Failed to fetch tasks for driver', error);
  } finally {
    tasksLoading.value = false;
  }
});

const taskOptions = computed(() => {
  const options = dynamicTasks.value.map(task => ({
    value: task.id,
    label: `#${task.id} — ${task.from?.name || 'Unknown Client'}`
  }));
  
  // If editing and the selected task isn't in the dynamically loaded list, add it
  if (isEdit.value && editingSwap.value?.task) {
    const taskId = editingSwap.value.task.id;
    if (!options.some(o => o.value === taskId)) {
      options.push({
        value: taskId,
        label: `#${taskId} — ${editingSwap.value.task.from?.name || 'Unknown Client'}`
      });
    }
  }
  
  return options;
});

const statusOptions = [
  { value: 'new', label: 'New' },
  { value: 'accepted', label: 'Accepted' },
  { value: 'rejected', label: 'Rejected' }
];

const openFormModal = (swap = null) => {
  form.clearErrors();
  dynamicTasks.value = [];
  if (swap) {
    isEdit.value = true;
    editingId.value = swap.id;
    editingSwap.value = swap;
    form.driver_a = swap.driver_a;
    form.driver_id = swap.driver_id;
    form.task_id = swap.task_id;
    form.status = swap.status || 'new';
  } else {
    isEdit.value = false;
    editingId.value = null;
    editingSwap.value = null;
    form.reset();
  }
  formModalOpen.value = true;
};

const closeFormModal = () => {
  formModalOpen.value = false;
  setTimeout(() => { form.reset(); }, 300);
};

const submitForm = () => {
  if (isEdit.value) {
    form.put(`/admin/swaprequests/${editingId.value}`, {
      preserveState: true,
      preserveScroll: true,
      onSuccess: () => {
        closeFormModal();
        doSearch(1); // refresh list to show changes
      }
    });
  } else {
    form.post('/admin/swaprequests', {
      preserveState: true,
      preserveScroll: true,
      onSuccess: () => {
        closeFormModal();
        doSearch(1);
      }
    });
  }
};
</script>

<template>
  <div class="space-y-6">
    <!-- Header -->
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
      <div>
        <h1 class="text-2xl font-bold text-slate-900 dark:text-white tracking-tight">Swap Requests</h1>
        <Breadcrumb class="mt-1" :items="[{ label: 'Admin' }, { label: 'Swap Requests' }]" />
      </div>
      <div class="flex items-center gap-3">
        <BaseButton variant="primary" icon="ri-add-line" @click="openFormModal(null)">Add Swap Request</BaseButton>
      </div>
    </div>

    <!-- Main Card -->
    <div class="bg-surface dark:bg-surface-dark-card rounded-2xl shadow-card border border-slate-200/60 dark:border-white/5 overflow-hidden flex flex-col">
      <!-- Search & Filters -->
      <div class="p-5 border-b border-slate-100 dark:border-white/5 bg-slate-50/50 dark:bg-black/20 flex flex-wrap gap-4 items-center justify-between">
        <div class="flex items-center gap-4 flex-1 flex-wrap">
          <div class="relative w-full max-w-xs">
            <i class="ri-search-line absolute left-3 top-1/2 -translate-y-1/2 text-slate-400"></i>
            <input 
              v-model="searchForm.keyword"
              type="text" 
              placeholder="Search driver, task ID..."
              class="w-full pl-9 pr-4 py-2 bg-white dark:bg-slate-900 border border-slate-200 dark:border-white/10 rounded-xl focus:ring-2 focus:ring-primary-500/20 focus:border-primary-500 transition-all text-sm dark:text-white placeholder-slate-400"
            />
          </div>

          <!-- Date Range Picker -->
          <div class="w-full max-w-[240px]">
            <FormDate
              v-model="dateRange"
              mode="range"
              placeholder="Filter by Date Range"
              icon="ri-calendar-line"
              @update:range="onDateRange"
              class="w-full"
            />
          </div>
        </div>

        <BaseButton variant="light" icon="ri-refresh-line" @click="resetFilters">Reset</BaseButton>
      </div>
      
      <!-- Table -->
      <DataTable
        :columns="columns"
        :rows="rows"
        :loading="loading"
        :total="total"
        server-side
        @query="onQuery"
      >
        <template #cell-task_id="{ row }">
          <span class="font-semibold text-slate-800 dark:text-slate-200">#{{ row.task_id }}</span>
          <div v-if="row.task" class="text-xs text-slate-500 mt-0.5">
            <span class="px-1.5 py-0.5 rounded border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-800">{{ row.task.status }}</span>
          </div>
        </template>
        
        <template #cell-driverA="{ row }">
          <div class="flex items-center gap-2">
            <BaseAvatar :name="row.driver_a?.name || 'Unknown'" :size="28" />
            <span class="font-medium text-slate-900 dark:text-slate-100">{{ row.driver_a?.name || 'Unknown' }}</span>
          </div>
        </template>

        <template #cell-driver="{ row }">
          <div class="flex items-center gap-2">
            <BaseAvatar :name="row.driver?.name || 'Unknown'" :size="28" />
            <span class="font-medium text-slate-900 dark:text-slate-100">{{ row.driver?.name || 'Unknown' }}</span>
          </div>
        </template>

        <template #cell-status="{ row }">
          <StatusBadge :status="row.status || 'unknown'" :color="getStatusColor(row.status)" />
        </template>

        <template #cell-created_at="{ row }">
          <span class="text-sm text-slate-500 font-medium">{{ formatDate(row.created_at) }}</span>
        </template>

        <template #cell-actions="{ row }">
          <div class="flex justify-end gap-1">
            <button @click="openViewModal(row)" class="w-8 h-8 inline-flex items-center justify-center rounded-lg hover:bg-info/10 text-info transition-colors" title="View Details">
              <i class="ri-eye-line text-lg"></i>
            </button>
            <button @click="openFormModal(row)" class="grid place-items-center w-8 h-8 rounded-lg text-primary-600 hover:bg-primary-50 dark:hover:bg-primary-500/10 transition" title="Edit">
              <i class="ri-pencil-line text-lg"></i>
            </button>
            <button @click="deleteSwap(row.id)" class="w-8 h-8 inline-flex items-center justify-center rounded-lg hover:bg-danger/10 text-danger transition-colors" title="Delete">
              <i class="ri-delete-bin-line text-lg"></i>
            </button>
          </div>
        </template>
      </DataTable>
    </div>

    <!-- View Modal -->
    <BaseModal v-model="viewModalOpen" max-width="md">
      <div v-if="selectedSwap" class="p-6">
        <div class="flex items-center justify-between mb-6">
          <h3 class="text-xl font-bold text-slate-900 dark:text-white flex items-center gap-2">
            <div class="w-10 h-10 rounded-xl bg-info/10 flex items-center justify-center text-info">
              <i class="ri-exchange-line text-xl"></i>
            </div>
            Swap Request Details
          </h3>
          <button @click="closeViewModal" class="text-slate-400 hover:text-slate-600 dark:hover:text-slate-200">
            <i class="ri-close-line text-2xl"></i>
          </button>
        </div>

        <div class="space-y-4">
          <!-- ID -->
          <div class="flex items-center justify-between p-3 rounded-lg bg-slate-50 dark:bg-white/5">
            <span class="text-sm font-medium text-slate-500 dark:text-slate-400">ID</span>
            <span class="text-sm font-bold text-slate-900 dark:text-white">#{{ selectedSwap.id }}</span>
          </div>

          <!-- Task -->
          <div class="flex items-center justify-between p-3 rounded-lg bg-slate-50 dark:bg-white/5">
            <span class="text-sm font-medium text-slate-500 dark:text-slate-400">Task</span>
            <div class="text-right">
              <div class="text-sm font-bold text-slate-900 dark:text-white">#{{ selectedSwap.task_id }}</div>
              <div class="text-xs text-slate-500" v-if="selectedSwap.task">{{ selectedSwap.task.status }}</div>
            </div>
          </div>

          <!-- From Driver -->
          <div class="flex items-center justify-between p-3 rounded-lg bg-slate-50 dark:bg-white/5">
            <span class="text-sm font-medium text-slate-500 dark:text-slate-400">From Driver</span>
            <div class="flex items-center gap-2 text-right">
              <span class="text-sm font-bold text-slate-900 dark:text-white">{{ selectedSwap.driver_a?.name || 'Unknown' }}</span>
              <BaseAvatar :name="selectedSwap.driver_a?.name || 'Unknown'" :size="24" />
            </div>
          </div>

          <!-- To Driver -->
          <div class="flex items-center justify-between p-3 rounded-lg bg-slate-50 dark:bg-white/5">
            <span class="text-sm font-medium text-slate-500 dark:text-slate-400">To Driver</span>
            <div class="flex items-center gap-2 text-right">
              <span class="text-sm font-bold text-slate-900 dark:text-white">{{ selectedSwap.driver?.name || 'Unknown' }}</span>
              <BaseAvatar :name="selectedSwap.driver?.name || 'Unknown'" :size="24" />
            </div>
          </div>

          <!-- Status -->
          <div class="flex items-center justify-between p-3 rounded-lg bg-slate-50 dark:bg-white/5">
            <span class="text-sm font-medium text-slate-500 dark:text-slate-400">Status</span>
            <StatusBadge :status="selectedSwap.status" :color="getStatusColor(selectedSwap.status)" />
          </div>

          <!-- Date -->
          <div class="flex items-center justify-between p-3 rounded-lg bg-slate-50 dark:bg-white/5">
            <span class="text-sm font-medium text-slate-500 dark:text-slate-400">Date</span>
            <span class="text-sm font-bold text-slate-900 dark:text-white">{{ formatDate(selectedSwap.created_at) }}</span>
          </div>
        </div>

        <div class="mt-6 flex justify-end gap-3">
          <button @click="openFormModal(selectedSwap)" class="px-4 py-2 bg-primary-600 text-white rounded-xl text-sm font-semibold hover:bg-primary-700 transition">Edit</button>
          <BaseButton @click="closeViewModal" variant="secondary">Close</BaseButton>
        </div>
      </div>
    </BaseModal>

    <!-- Form Modal -->
    <BaseModal v-model="formModalOpen" max-width="lg">
      <div class="p-6">
        <div class="flex items-center justify-between mb-6">
          <h3 class="text-xl font-bold text-slate-900 dark:text-white flex items-center gap-2">
            <div class="w-10 h-10 rounded-xl bg-primary-500/10 flex items-center justify-center text-primary-600">
              <i class="ri-exchange-box-line text-xl"></i>
            </div>
            {{ isEdit ? 'Edit Swap Request' : 'Create Swap Request' }}
          </h3>
          <button @click="closeFormModal" class="text-slate-400 hover:text-slate-600 dark:hover:text-slate-200">
            <i class="ri-close-line text-2xl"></i>
          </button>
        </div>
        
        <form @submit.prevent="submitForm">
          <!-- Error Alerts -->
          <div v-if="Object.keys(form.errors).length > 0" class="mb-6 p-4 rounded-xl bg-danger/10 border border-danger/20 flex gap-3 items-start">
            <i class="ri-error-warning-fill text-danger text-xl"></i>
            <div>
              <h4 class="text-sm font-bold text-danger">Please correct the following errors:</h4>
              <ul class="mt-1 text-sm text-danger/80 list-disc list-inside">
                <li v-for="(error, key) in form.errors" :key="key">{{ error }}</li>
              </ul>
            </div>
          </div>

          <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <FormSelect
              v-model="form.driver_a"
              label="From Driver (Driver A)"
              :options="driverOptions"
              :error="form.errors.driver_a"
              placeholder="Select From Driver"
              required
              searchable
              floating
            />

            <FormSelect
              v-model="form.driver_id"
              label="To Driver (Driver B)"
              :options="driverOptions"
              :error="form.errors.driver_id"
              placeholder="Select To Driver"
              required
              searchable
              floating
            />

            <FormSelect
              v-model="form.task_id"
              label="Task(s)"
              :options="taskOptions"
              :error="form.errors.task_id"
              placeholder="Select Task (Choose Driver A first)"
              :multiple="!isEdit"
              required
              searchable
              floating
            />

            <FormSelect
              v-model="form.status"
              label="Status"
              :options="statusOptions"
              :error="form.errors.status"
              required
              floating
            />
          </div>

          <div class="mt-8 pt-6 border-t border-slate-100 dark:border-white/5 flex justify-end gap-3">
            <BaseButton @click="closeFormModal" variant="secondary" type="button">Cancel</BaseButton>
            <BaseButton 
              type="submit" 
              :loading="form.processing" 
              variant="primary"
            >
              {{ isEdit ? 'Update Request' : 'Create Request' }}
            </BaseButton>
          </div>
        </form>
      </div>
    </BaseModal>
  </div>
</template>
