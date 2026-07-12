<script setup>
import { ref, watch, onMounted } from 'vue';
import { usePage } from '@inertiajs/vue3';
import axios from 'axios';
import debounce from 'lodash/debounce';

import Breadcrumb from '../../components/Breadcrumb.vue';
import DataTable from '../../components/DataTable.vue';
import BaseAvatar from '../../components/BaseAvatar.vue';
import BaseModal from '../../components/BaseModal.vue';
import BaseButton from '../../components/BaseButton.vue';
import FormDate from '../../components/FormDate.vue';

const props = defineProps({
  initialRows: Array,
  initialTotal: Number,
});

const DEFAULT_FILTERS = { keyword: '', action: '', date_from: '', date_to: '', sortBy: 'id', sortOrder: 'desc' };
const searchForm = ref({ ...DEFAULT_FILTERS });

const datePart = (s) => (s ? String(s).slice(0, 10) : '');
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
  { key: 'id',                label: 'ID',             sortable: true, width: '60px' },
  { key: 'driver',            label: 'Driver' },
  { key: 'car',               label: 'Car' },
  { key: 'action',            label: 'Action' },
  { key: 'created_at',        label: 'Date',           sortable: true },
  { key: 'actions',           label: '',               align: 'right' },
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
    if (searchForm.value.action) params.append('action', searchForm.value.action);
    if (searchForm.value.date_from) params.append('date_from', searchForm.value.date_from);
    if (searchForm.value.date_to) params.append('date_to', searchForm.value.date_to);
    
    params.append('sortBy', searchForm.value.sortBy);
    params.append('sortOrder', searchForm.value.sortOrder);
    params.append('page', page);
    params.append('pageSize', pageSize);

    const { data } = await axios.get(`/app/admin/car-link-histories?${params.toString()}`, {
      headers: { Accept: 'application/json' },
    });
    
    rows.value = data.rows;
    total.value = data.total;
  } catch (error) {
    console.error('Error fetching car link histories:', error);
  } finally {
    loading.value = false;
  }
}, 300);

watch(
  () => [searchForm.value.keyword, searchForm.value.action, searchForm.value.date_from, searchForm.value.date_to],
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
const selectedHistory = ref(null);

const openViewModal = (history) => {
  selectedHistory.value = history;
  viewModalOpen.value = true;
};

const closeViewModal = () => {
  viewModalOpen.value = false;
  setTimeout(() => { selectedHistory.value = null; }, 300);
};

const formatDate = (dateString) => {
  if (!dateString) return '—';
  const date = new Date(dateString);
  return date.toLocaleString('en-GB', { 
    year: 'numeric', month: 'short', day: 'numeric',
    hour: '2-digit', minute: '2-digit'
  });
};
</script>

<template>
  <div class="space-y-6">
    <!-- Header -->
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
      <div>
        <h1 class="text-2xl font-bold text-slate-900 dark:text-white tracking-tight">Car Link Histories</h1>
        <Breadcrumb class="mt-1" :items="[{ label: 'Admin' }, { label: 'Car Link Histories' }]" />
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
              placeholder="Search driver, IMEI, plate..."
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

          <div class="flex items-center rounded-xl p-1 bg-slate-100 dark:bg-slate-900 border border-slate-200 dark:border-white/10">
            <button 
              @click="searchForm.action = ''"
              :class="['px-3 py-1.5 text-xs font-semibold rounded-lg transition-all', searchForm.action === '' ? 'bg-white dark:bg-slate-800 text-slate-800 dark:text-white shadow-sm' : 'text-slate-500 hover:text-slate-700 dark:hover:text-slate-300']"
            >All</button>
            <button 
              @click="searchForm.action = 'linked'"
              :class="['px-3 py-1.5 text-xs font-semibold rounded-lg transition-all', searchForm.action === 'linked' ? 'bg-emerald-500 text-white shadow-sm' : 'text-slate-500 hover:text-slate-700 dark:hover:text-slate-300']"
            >Linked</button>
            <button
              @click="searchForm.action = 'unlinked'"
              :class="['px-3 py-1.5 text-xs font-semibold rounded-lg transition-all', searchForm.action === 'unlinked' ? 'bg-danger text-white shadow-sm' : 'text-slate-500 hover:text-slate-700 dark:hover:text-slate-300']"
            >Unlinked</button>
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
        <template #cell-driver="{ row }">
          <div class="flex items-center gap-2">
            <BaseAvatar :name="row.driver?.name || 'Unknown'" :size="28" />
            <span class="font-medium text-slate-900 dark:text-slate-100">{{ row.driver?.name || 'Unknown' }}</span>
          </div>
        </template>
        
        <template #cell-car="{ row }">
          <div v-if="row.car" class="flex flex-col">
            <span class="font-bold text-slate-800 dark:text-slate-200">{{ row.car.imei }}</span>
            <span class="text-[11px] text-slate-500">{{ row.car.plate_number }}</span>
          </div>
          <span v-else class="text-slate-400">—</span>
        </template>
        
        <template #cell-action="{ row }">
          <span v-if="row.action === 'linked'" class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded text-xs font-bold bg-emerald-50 text-emerald-600 dark:bg-emerald-500/10 dark:text-emerald-400">
            <i class="ri-link-m"></i> Linked
          </span>
          <span v-else class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded text-xs font-bold bg-danger/5 text-danger dark:bg-danger/10 dark:text-danger-400">
            <i class="ri-link-unlink-m"></i> Unlinked
          </span>
        </template>

        <template #cell-created_at="{ row }">
          <span class="text-sm text-slate-500 font-medium">{{ formatDate(row.created_at) }}</span>
        </template>

        <template #cell-actions="{ row }">
          <div class="flex justify-end gap-1">
            <button @click="openViewModal(row)" class="w-8 h-8 inline-flex items-center justify-center rounded-lg hover:bg-info/10 text-info transition-colors" title="View Details">
              <i class="ri-eye-line text-lg"></i>
            </button>
          </div>
        </template>
      </DataTable>
    </div>

    <!-- View Modal -->
    <BaseModal v-model="viewModalOpen" max-width="md">
      <div v-if="selectedHistory" class="p-6">
        <div class="flex items-center justify-between mb-6">
          <h3 class="text-xl font-bold text-slate-900 dark:text-white flex items-center gap-2">
            <div class="w-10 h-10 rounded-xl bg-info/10 flex items-center justify-center text-info">
              <i class="ri-history-line text-xl"></i>
            </div>
            History Record Details
          </h3>
          <button @click="closeViewModal" class="text-slate-400 hover:text-slate-600 dark:hover:text-slate-200">
            <i class="ri-close-line text-2xl"></i>
          </button>
        </div>

        <div class="space-y-4">
          <!-- ID -->
          <div class="flex items-center justify-between p-3 rounded-lg bg-slate-50 dark:bg-white/5">
            <span class="text-sm font-medium text-slate-500 dark:text-slate-400">ID</span>
            <span class="text-sm font-bold text-slate-900 dark:text-white">#{{ selectedHistory.id }}</span>
          </div>

          <!-- Driver -->
          <div class="flex items-center justify-between p-3 rounded-lg bg-slate-50 dark:bg-white/5">
            <span class="text-sm font-medium text-slate-500 dark:text-slate-400">Driver</span>
            <div class="flex items-center gap-2 text-right">
              <span class="text-sm font-bold text-slate-900 dark:text-white">{{ selectedHistory.driver?.name || 'Unknown' }}</span>
              <BaseAvatar :name="selectedHistory.driver?.name || 'Unknown'" :size="24" />
            </div>
          </div>

          <!-- Car -->
          <div class="flex items-center justify-between p-3 rounded-lg bg-slate-50 dark:bg-white/5">
            <span class="text-sm font-medium text-slate-500 dark:text-slate-400">Car</span>
            <div class="text-right">
              <div class="text-sm font-bold text-slate-900 dark:text-white">{{ selectedHistory.car?.imei || 'Unknown' }}</div>
              <div class="text-xs text-slate-500">{{ selectedHistory.car?.plate_number || '' }}</div>
            </div>
          </div>

          <!-- Action -->
          <div class="flex items-center justify-between p-3 rounded-lg bg-slate-50 dark:bg-white/5">
            <span class="text-sm font-medium text-slate-500 dark:text-slate-400">Action</span>
            <div>
              <span v-if="selectedHistory.action === 'linked'" class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded text-xs font-bold bg-emerald-50 text-emerald-600 dark:bg-emerald-500/10 dark:text-emerald-400">
                <i class="ri-link-m"></i> Linked
              </span>
              <span v-else class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded text-xs font-bold bg-danger/5 text-danger dark:bg-danger/10 dark:text-danger-400">
                <i class="ri-link-unlink-m"></i> Unlinked
              </span>
            </div>
          </div>

          <!-- Date -->
          <div class="flex items-center justify-between p-3 rounded-lg bg-slate-50 dark:bg-white/5">
            <span class="text-sm font-medium text-slate-500 dark:text-slate-400">Date</span>
            <span class="text-sm font-bold text-slate-900 dark:text-white">{{ formatDate(selectedHistory.created_at) }}</span>
          </div>
        </div>

        <div class="mt-6 flex justify-end">
          <BaseButton @click="closeViewModal" variant="secondary">Close</BaseButton>
        </div>
      </div>
    </BaseModal>
  </div>
</template>
