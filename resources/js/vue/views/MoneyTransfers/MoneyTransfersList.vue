<script setup>
import { ref, computed, watch, onMounted } from 'vue';
import { router, usePage } from '@inertiajs/vue3';
import axios from 'axios';
import debounce from 'lodash/debounce';

import Breadcrumb from '../../components/Breadcrumb.vue';
import DataTable from '../../components/DataTable.vue';
import FilterBar from '../../components/FilterBar.vue';
import FormSelect from '../../components/FormSelect.vue';
import BaseAvatar from '../../components/BaseAvatar.vue';
import StatusBadge from '../../components/StatusBadge.vue';
import BaseModal from '../../components/BaseModal.vue';
import BaseButton from '../../components/BaseButton.vue';

const props = defineProps({
  initialRows: Array,
  initialTotal: Number,
  filters: Object,
  can: Object,
});

const pageCtx = usePage();
const can = (permission) => props.can?.[permission] ?? pageCtx.props.can?.[permission] ?? false;

const DEFAULT_FILTERS = {
  keyword: '', status: '', client_id: '', driver_id: '', from_location: '', to_location: '',
  date_from: '', date_to: '', sort_by: '', sort_order: '',
};
const searchForm = ref({ ...DEFAULT_FILTERS });

// Quick Filter Tabs
const statusTabs = [
  { key: '',                label: 'All Statuses' },
  { key: 'new',             label: 'New',             activeClass: 'bg-primary-500 text-white dark:bg-primary-600' },
  { key: 'confirmed',       label: 'Confirmed',       activeClass: 'bg-info text-white dark:bg-cyan-600' },
  { key: 'amount_received', label: 'Amount Received', activeClass: 'bg-success text-white dark:bg-emerald-600' },
  { key: 'closed',          label: 'Closed',          activeClass: 'bg-secondary text-white dark:bg-secondary/90' },
  { key: 'cancelled',       label: 'Cancelled',       activeClass: 'bg-danger text-white dark:bg-danger/90' },
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

    const { data } = await axios.get(`/app/admin/money-transfers?${params.toString()}`, {
      headers: { Accept: 'application/json' },
    });
    
    rows.value = data.rows;
    total.value = data.total;
  } catch (error) {
    console.error('Error fetching money transfers:', error);
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
    await axios.delete(`/admin/money-transfers/${itemToDelete.value}`, {
      headers: { 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') }
    });
    deleteModalOpen.value = false;
    doSearch(); // Refresh the table
  } catch (error) {
    console.error('Error deleting money transfer:', error);
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
const loading = ref(false);
const showAdvanced = ref(false);

const columns = [
  { key: 'sequence',          label: '#',                 width: '60px' },
  { key: 'id',                label: 'ID',                sortable: true },
  { key: 'client_name',       label: 'Client' },
  { key: 'driver_name',       label: 'Driver' },
  { key: 'route',             label: 'Route' },
  { key: 'amount',            label: 'Amount' },
  { key: 'from_location_otp', label: 'Pickup OTP' },
  { key: 'to_otp',            label: 'Dropoff OTP' },
  { key: 'status',            label: 'Status' },
  { key: 'created_at',        label: 'Created At',        sortable: true },
  { key: 'actions',           label: '',                  align: 'right' },
];

const resetFilters = () => {
  searchForm.value = { ...DEFAULT_FILTERS };
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
const clientOpts = computed(() => [{ value: '', label: 'Any Client' }, ...(props.filters?.clients || [])]);
const locationOpts = computed(() => [{ value: '', label: 'Any Location' }, ...(props.filters?.locations || [])]);

</script>

<template>
  <div class="space-y-6">
    <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4">
      <Breadcrumb title="Money Transfers" parent="Operations" />
      <div v-if="can('money_transfer_create')" class="flex space-x-2 rtl:space-x-reverse">
        <a href="/admin/money-transfers/create" class="inline-flex items-center gap-2 px-4 py-2 bg-primary-600 text-white text-sm font-medium rounded-lg shadow-sm hover:bg-primary-700 focus:ring-2 focus:ring-primary-500/50 transition-colors">
          <i class="ri-add-line text-lg leading-none"></i>
          Add Money Transfer
        </a>
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
          placeholder="Search by ID, Client, or Driver..."
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
          <label class="text-xs font-bold text-slate-600 dark:text-slate-300">Client</label>
          <FormSelect v-model="searchForm.client_id" :options="clientOpts" class="w-full" />
        </div>
        <div class="space-y-1.5">
          <label class="text-xs font-bold text-slate-600 dark:text-slate-300">Driver</label>
          <FormSelect v-model="searchForm.driver_id" :options="driverOpts" class="w-full" />
        </div>
        <div class="space-y-1.5">
          <label class="text-xs font-bold text-slate-600 dark:text-slate-300">From Location</label>
          <FormSelect v-model="searchForm.from_location" :options="locationOpts" class="w-full" />
        </div>
        <div class="space-y-1.5">
          <label class="text-xs font-bold text-slate-600 dark:text-slate-300">To Location</label>
          <FormSelect v-model="searchForm.to_location" :options="locationOpts" class="w-full" />
        </div>
        <div class="space-y-1.5">
          <label class="text-xs font-bold text-slate-600 dark:text-slate-300">Date From</label>
          <input type="date" v-model="searchForm.date_from" class="block w-full h-10 rounded-md border-gray-300 dark:border-dark-600 dark:bg-dark-800 shadow-sm focus:border-primary-500 focus:ring-primary-500 sm:text-sm">
        </div>
        <div class="space-y-1.5">
          <label class="text-xs font-bold text-slate-600 dark:text-slate-300">Date To</label>
          <input type="date" v-model="searchForm.date_to" class="block w-full h-10 rounded-md border-gray-300 dark:border-dark-600 dark:bg-dark-800 shadow-sm focus:border-primary-500 focus:ring-primary-500 sm:text-sm">
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
      
      <template #cell-client_name="{ value }">
        <span class="font-extrabold text-slate-800 dark:text-white">{{ value || '—' }}</span>
      </template>
      
      <template #cell-driver_name="{ value }">
        <div v-if="value" class="flex items-center gap-2">
          <BaseAvatar :name="value" :size="26" />
          <span class="text-[12.5px] font-medium text-ink dark:text-slate-200 whitespace-nowrap">{{ value }}</span>
        </div>
        <span v-else class="text-slate-400">—</span>
      </template>

      <template #cell-route="{ row }">
        <div class="flex flex-wrap items-center gap-2">
          <span v-if="row.from_location_name" class="inline-flex items-center gap-1 px-2.5 py-1 rounded-md bg-emerald-50 text-emerald-700 dark:bg-emerald-500/10 dark:text-emerald-400 text-[12.5px] font-bold truncate max-w-[130px] border border-emerald-100 dark:border-emerald-500/20" :title="row.from_location_name">
            <i class="ri-map-pin-fill text-red-500 text-[11px] shrink-0"></i>
            <span class="truncate">{{ row.from_location_name }}</span>
          </span>
          <span v-else class="text-slate-400">—</span>

          <i class="ri-arrow-right-line text-slate-400 shrink-0"></i>

          <span v-if="row.to_location_name" class="inline-flex items-center gap-1 px-2.5 py-1 rounded-md bg-sky-50 text-sky-700 dark:bg-sky-500/10 dark:text-sky-400 text-[12.5px] font-bold truncate max-w-[130px] border border-sky-100 dark:border-sky-500/20" :title="row.to_location_name">
            <i class="ri-map-pin-fill text-green-500 text-[11px] shrink-0"></i>
            <span class="truncate">{{ row.to_location_name }}</span>
          </span>
          <span v-else class="text-slate-400">—</span>
        </div>
      </template>
      
      <template #cell-amount="{ value }">
        <span class="font-black text-amber-600">{{ value }} SAR</span>
      </template>

      <template #cell-status="{ value }">
        <StatusBadge :status="value" />
      </template>

      <template #cell-actions="{ row }">
        <div class="flex justify-center items-center gap-1">
          <a v-if="can('money_transfer_show')" :href="`/admin/money-transfers/${row.id}`" class="w-8 h-8 rounded hover:bg-primary-50 dark:hover:bg-primary-500/10 flex items-center justify-center text-primary-600 transition-colors" title="View">
            <i class="ri-eye-line text-lg"></i>
          </a>
          <a v-if="can('money_transfer_edit')" :href="`/admin/money-transfers/${row.id}/edit`" class="w-8 h-8 rounded hover:bg-amber-50 dark:hover:bg-amber-500/10 flex items-center justify-center text-amber-600 transition-colors" title="Edit">
            <i class="ri-pencil-line text-lg"></i>
          </a>
          <button v-if="can('money_transfer_delete')" @click="confirmDelete(row.id)" class="w-8 h-8 rounded hover:bg-danger/10 dark:hover:bg-danger/20 flex items-center justify-center text-danger transition-colors" title="Delete">
            <i class="ri-delete-bin-line text-lg"></i>
          </button>
        </div>
      </template>

      <template #empty>
        <div class="py-12 flex flex-col items-center justify-center text-center">
          <div class="w-16 h-16 bg-slate-100 dark:bg-surface-dark-solid rounded-full flex items-center justify-center mb-4">
            <i class="ri-money-dollar-circle-line text-2xl text-slate-400"></i>
          </div>
          <h3 class="text-sm font-semibold text-ink dark:text-white mb-1">No money transfers found</h3>
          <p class="text-sm text-slate-500 max-w-sm">There are no money transfers matching your filters.</p>
        </div>
      </template>
    </DataTable>

    <!-- Professional Delete Confirmation Modal -->
    <BaseModal v-model="deleteModalOpen" title="Delete Money Transfer" icon="ri-error-warning-line" tone="danger" size="sm">
      <div class="py-4 text-center text-slate-600 dark:text-slate-300">
        <div class="mb-4 text-danger/80">
          <i class="ri-delete-bin-line text-5xl"></i>
        </div>
        <p class="text-[15px]">Are you sure you want to delete this money transfer?</p>
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
