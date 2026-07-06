<script setup>
import { ref, computed, watch, onMounted } from 'vue';
import { router, usePage } from '@inertiajs/vue3';
import axios from 'axios';
import debounce from 'lodash/debounce';

import Breadcrumb from '../../components/Breadcrumb.vue';
import DataTable from '../../components/DataTable.vue';
import FormSelect from '../../components/FormSelect.vue';
import BaseAvatar from '../../components/BaseAvatar.vue';
import StatusBadge from '../../components/StatusBadge.vue';
import BaseModal from '../../components/BaseModal.vue';
import BaseButton from '../../components/BaseButton.vue';
import FormDate from '../../components/FormDate.vue';

const props = defineProps({
  initialRows: Array,
  initialTotal: Number,
  filters: Object,
  can: Object,
});

const pageCtx = usePage();
const can = (permission) => props.can?.[permission] ?? pageCtx.props.can?.[permission] ?? false;

const DEFAULT_FILTERS = {
  keyword: '', status: '', driver_id: '', imei: '', plate_number: '',
  date_from: '', date_to: '', sort_by: '', sort_order: '',
};
const searchForm = ref({ ...DEFAULT_FILTERS });

const datePart = (s) => (s ? String(s).slice(0, 10) : '');
const dateRange = ref(searchForm.value.date_from ? `${datePart(searchForm.value.date_from)} to ${datePart(searchForm.value.date_to)}` : '');

function onDateRange({ from, to }) {
  searchForm.value.date_from = from || '';
  searchForm.value.date_to = to || '';
}

// Quick Filter Tabs
const statusTabs = [
  { key: '',  label: 'All Statuses' },
  { key: '1', label: 'Enabled',  activeClass: 'bg-success text-white dark:bg-emerald-600' },
  { key: '2', label: 'Disabled', activeClass: 'bg-danger text-white dark:bg-danger/90' },
];

const onQuery = ({ page, pageSize, sortBy, sortOrder }) => {
  searchForm.value.sort_by = sortBy || '';
  searchForm.value.sort_order = sortOrder || '';
  doSearch(page, pageSize);
};

const doSearch = debounce(async (page = 1, pageSize = 25) => {
  loading.value = true;
  try {
    const params = new URLSearchParams();
    Object.entries(searchForm.value).forEach(([k, v]) => {
      if (v !== '' && v !== null && v !== undefined) params.append(k, v);
    });
    params.append('page', page);
    params.append('pageSize', pageSize);

    const { data } = await axios.get(`/app/admin/cars?${params.toString()}`, {
      headers: { Accept: 'application/json' },
    });
    
    rows.value = data.rows;
    total.value = data.total;
  } catch (error) {
    console.error('Error fetching cars:', error);
  } finally {
    loading.value = false;
  }
}, 300);

const deleteModalOpen = ref(false);
const itemToDelete = ref(null);
const deleting = ref(false);

const confirmDelete = (id) => {
  itemToDelete.value = id;
  deleteModalOpen.value = true;
};

const executeDelete = async () => {
  if (!itemToDelete.value) return;
  deleting.value = true;
  try {
    await axios.delete(`/admin/cars/${itemToDelete.value}`, {
      headers: { 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') }
    });
    deleteModalOpen.value = false;
    doSearch(); // Refresh the table
  } catch (error) {
    console.error('Error deleting car:', error);
    alert('Failed to delete the item.');
  } finally {
    deleting.value = false;
  }
};

// Watch for keyword/status changes
watch(
  () => [searchForm.value.keyword, searchForm.value.status],
  () => doSearch(1, 25),
  { deep: true }
);

onMounted(() => {
  const urlParams = new URLSearchParams(window.location.search);
  let hasFilters = false;
  for (const [key, value] of urlParams.entries()) {
    if (searchForm.value[key] !== undefined) {
      searchForm.value[key] = value;
      hasFilters = true;
    }
  }
  if (hasFilters) {
    doSearch(1, 25);
  } else {
    rows.value = props.initialRows || [];
    total.value = props.initialTotal || 0;
  }
});

const rows = ref([]);
const total = ref(0);
const loading = ref(!props.initialRows);
const showAdvanced = ref(false);

const columns = [
  { key: 'id',                label: 'ID',             sortable: true, width: '60px' },
  { key: 'driver_name',       label: 'Driver' },
  { key: 'imei',              label: 'IMEI' },
  { key: 'plate_number',      label: 'Plate Number' },
  { key: 'model',             label: 'Model' },
  { key: 'color',             label: 'Color' },
  { key: 'status',            label: 'Status' },
  { key: 'created_at',        label: 'Created At',     sortable: true },
  { key: 'actions',           label: '',               align: 'right' },
];

const resetFilters = () => {
  searchForm.value = { ...DEFAULT_FILTERS };
  dateRange.value = '';
  showAdvanced.value = false;
  doSearch(1, 25);
};

const applyFilters = () => {
  doSearch(1, 25);
  showAdvanced.value = false;
};

const statusTabClasses = (tab) => {
  const isActive = searchForm.value.status === tab.key;
  return isActive
    ? tab.activeClass || 'bg-primary-500 text-white dark:bg-primary-600'
    : 'bg-white text-gray-600 border border-gray-200 hover:bg-gray-50 dark:bg-dark-800 dark:border-dark-700 dark:text-gray-300 dark:hover:bg-dark-700';
};

const setStatusTab = (key) => {
  searchForm.value.status = key;
  doSearch();
};

// Dropdown options
const driverOpts = computed(() => [{ value: '', label: 'Any Driver' }, ...(props.filters?.drivers || [])]);

</script>

<template>
  <div class="space-y-6">
    <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4">
      <Breadcrumb title="Cars" parent="Drivers" />
      <div v-if="can('car_create')" class="flex space-x-2 rtl:space-x-reverse">
        <button @click="router.visit('/app/admin/cars/create')" class="inline-flex items-center gap-2 px-4 py-2 bg-primary-600 text-white text-sm font-medium rounded-lg shadow-sm hover:bg-primary-700 focus:ring-2 focus:ring-primary-500/50 transition-colors">
          <i class="ri-add-line text-lg leading-none"></i>
          Add Car
        </button>
      </div>
    </div>

    <!-- Unified Toolbar -->
    <div class="flex flex-col lg:flex-row items-center gap-4 mb-4 bg-surface dark:bg-surface-dark p-3 rounded-xl border border-slate-100 dark:border-white/5 shadow-sm">
      <!-- Search -->
      <div class="relative w-full lg:w-72 shrink-0">
        <i class="ri-search-line absolute left-3 top-1/2 -translate-y-1/2 text-slate-400"></i>
        <input
          v-model="searchForm.keyword"
          @keyup.enter="applyFilters"
          placeholder="Search Driver, IMEI, or Plate..."
          class="w-full h-10 pl-9 pr-3 rounded-lg border-transparent bg-slate-100 dark:bg-black/20 text-sm focus:ring-2 focus:ring-primary-500/20 focus:border-primary-500 focus:bg-white dark:focus:bg-surface-dark-card transition-all dark:text-slate-200"
        />
      </div>

      <!-- Quick Filters (Tabs) -->
      <div class="flex flex-wrap items-center gap-2 flex-1">
        <button
          v-for="s in statusTabs" :key="s.key"
          @click="setStatusTab(s.key)"
          class="h-8 px-3.5 rounded-full text-[13px] font-semibold tracking-wide transition-all duration-200 flex items-center gap-1.5 border"
          :class="statusTabClasses(s)"
        >
          {{ s.label }}
        </button>
      </div>

      <!-- Advanced Toggle -->
      <div class="w-full lg:w-auto shrink-0 flex justify-end gap-2">
        <button @click="resetFilters" class="px-3 py-2 text-sm text-slate-500 hover:text-slate-700 dark:text-slate-400 dark:hover:text-slate-200">
          Reset
        </button>
        <button @click="showAdvanced = !showAdvanced" class="inline-flex items-center gap-2 px-3 py-2 bg-slate-50 dark:bg-white/5 border border-slate-200 dark:border-white/10 text-slate-600 dark:text-slate-300 text-sm font-medium rounded-lg hover:bg-slate-100 dark:hover:bg-white/10 transition-colors" :class="{'bg-primary-50 text-primary-700 border-primary-200 dark:bg-primary-500/10 dark:text-primary-300': showAdvanced}">
          <i class="ri-filter-3-line"></i> Advanced Filters
        </button>
      </div>
    </div>

    <!-- Advanced Filters (Collapsible) -->
    <div v-show="showAdvanced" class="bg-surface dark:bg-surface-dark border dark:border-surface-dark-border rounded-xl p-4 shadow-sm mb-4 transition-all">
      <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-4 gap-4">
        <div class="space-y-1.5">
          <label class="text-xs font-bold text-slate-600 dark:text-slate-300">Driver</label>
          <FormSelect v-model="searchForm.driver_id" :options="driverOpts" class="w-full" />
        </div>
        <div class="space-y-1.5">
          <label class="text-xs font-bold text-slate-600 dark:text-slate-300">IMEI</label>
          <input type="text" v-model="searchForm.imei" class="block w-full h-10 rounded-md border-gray-300 dark:border-dark-600 dark:bg-dark-800 shadow-sm focus:border-primary-500 focus:ring-primary-500 sm:text-sm">
        </div>
        <div class="space-y-1.5">
          <label class="text-xs font-bold text-slate-600 dark:text-slate-300">Plate Number</label>
          <input type="text" v-model="searchForm.plate_number" class="block w-full h-10 rounded-md border-gray-300 dark:border-dark-600 dark:bg-dark-800 shadow-sm focus:border-primary-500 focus:ring-primary-500 sm:text-sm">
        </div>
        <div class="space-y-1.5">
          <FormDate v-model="dateRange" label="Date Range" mode="range" placeholder="Select range" @range="onDateRange" />
        </div>
      </div>
      <div class="mt-4 flex justify-end">
        <button @click="applyFilters" class="px-4 py-2 bg-primary-600 text-white rounded-lg text-sm font-medium hover:bg-primary-700 transition-colors">
          Apply Filters
        </button>
      </div>
    </div>

    <!-- data table (server-side) -->
    <DataTable
      :columns="columns"
      :rows="rows"
      row-key="id"
      :total="total"
      :loading="loading"
      :server-side="true"
      :searchable="false"
      @query="onQuery"
    >
      <template #cell-id="{ value }">
        <span class="font-black text-[#0ab39c] dark:text-[#0ab39c]">#{{ value }}</span>
      </template>
      
      <template #cell-driver_name="{ row, value }">
        <div v-if="value" class="flex flex-col gap-0.5">
          <div class="flex items-center gap-2">
            <BaseAvatar :name="value" :size="26" />
            <span class="text-[12.5px] font-bold text-ink dark:text-slate-200 whitespace-nowrap">{{ value }}</span>
          </div>
          <span class="text-[11px] text-slate-500">{{ row.driver_mobile }}</span>
        </div>
        <span v-else class="text-slate-400">—</span>
      </template>

      <template #cell-imei="{ value }">
        <span class="font-mono text-xs text-slate-600 dark:text-slate-400">{{ value || '—' }}</span>
      </template>
      
      <template #cell-plate_number="{ value }">
        <span class="font-bold text-slate-700 dark:text-slate-300">{{ value || '—' }}</span>
      </template>
      
      <template #cell-model="{ value }">
        <span class="text-sm text-slate-600 dark:text-slate-400">{{ value || '—' }}</span>
      </template>

      <template #cell-status="{ value }">
        <span v-if="value == 1" class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-[11px] font-bold tracking-wide bg-emerald-50 text-emerald-600 border border-emerald-200 shadow-sm transition-all duration-300 hover:shadow-md hover:shadow-emerald-100 hover:-translate-y-0.5 dark:bg-emerald-500/10 dark:border-emerald-500/20 dark:text-emerald-400 dark:hover:shadow-emerald-500/10 cursor-default">
          <span class="relative flex h-2 w-2 shrink-0">
            <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-emerald-400 opacity-75"></span>
            <span class="relative inline-flex rounded-full h-2 w-2 bg-emerald-500"></span>
          </span>
          ENABLED
        </span>
        <span v-else-if="value == 2" class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-[11px] font-bold tracking-wide bg-danger/5 text-danger border border-danger/20 shadow-sm transition-all duration-300 hover:shadow-md hover:shadow-danger/10 hover:-translate-y-0.5 dark:bg-danger/10 dark:border-danger/20 dark:hover:shadow-danger/10 cursor-default">
          <i class="ri-forbid-2-line text-[13px]"></i>
          DISABLED
        </span>
        <span v-else class="text-slate-400">—</span>
      </template>

      <template #cell-actions="{ row }">
        <div class="flex justify-center items-center gap-1">
          <button v-if="can('car_show')" @click="router.visit(`/app/admin/cars/${row.id}`)" class="w-8 h-8 rounded hover:bg-primary-50 dark:hover:bg-primary-500/10 flex items-center justify-center text-primary-600 transition-colors" title="View">
            <i class="ri-eye-line text-lg"></i>
          </button>
          <button v-if="can('car_edit')" @click="router.visit(`/app/admin/cars/${row.id}/edit`)" class="w-8 h-8 rounded hover:bg-amber-50 dark:hover:bg-amber-500/10 flex items-center justify-center text-amber-600 transition-colors" title="Edit">
            <i class="ri-pencil-line text-lg"></i>
          </button>
          <button v-if="can('car_delete')" @click="confirmDelete(row.id)" class="w-8 h-8 rounded hover:bg-danger/10 dark:hover:bg-danger/20 flex items-center justify-center text-danger transition-colors" title="Delete">
            <i class="ri-delete-bin-line text-lg"></i>
          </button>
        </div>
      </template>

      <template #empty>
        <div class="py-12 flex flex-col items-center justify-center text-center">
          <div class="w-16 h-16 bg-slate-100 dark:bg-surface-dark-solid rounded-full flex items-center justify-center mb-4">
            <i class="ri-car-line text-2xl text-slate-400"></i>
          </div>
          <h3 class="text-sm font-semibold text-ink dark:text-white mb-1">No cars found</h3>
          <p class="text-sm text-slate-500 max-w-sm">There are no cars matching your filters.</p>
        </div>
      </template>
    </DataTable>

    <!-- Professional Delete Confirmation Modal -->
    <BaseModal v-model="deleteModalOpen" title="Delete Car" icon="ri-error-warning-line" tone="danger" size="sm">
      <div class="py-4 text-center text-slate-600 dark:text-slate-300">
        <div class="mb-4 text-danger/80">
          <i class="ri-delete-bin-line text-5xl"></i>
        </div>
        <p class="text-[15px]">Are you sure you want to delete this car?</p>
        <p class="text-sm mt-1 text-slate-400">This action cannot be undone.</p>
      </div>
      <template #footer>
        <div class="flex justify-end gap-3 w-full">
          <BaseButton variant="light" @click="deleteModalOpen = false" :disabled="deleting">Cancel</BaseButton>
          <BaseButton variant="danger" icon="ri-delete-bin-line" :loading="deleting" @click="executeDelete">Yes, Delete</BaseButton>
        </div>
      </template>
    </BaseModal>
  </div>
</template>
