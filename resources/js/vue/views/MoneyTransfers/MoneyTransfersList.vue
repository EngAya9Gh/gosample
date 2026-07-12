<script setup>
import { ref, computed, watch, onMounted } from 'vue';
import { router, usePage, useForm } from '@inertiajs/vue3';
import axios from 'axios';
import debounce from 'lodash/debounce';

import Breadcrumb from '../../components/Breadcrumb.vue';
import DataTable from '../../components/DataTable.vue';
import FilterBar from '../../components/FilterBar.vue';
import FormSelect from '../../components/FormSelect.vue';
import FormInput from '../../components/FormInput.vue';
import FormDate from '../../components/FormDate.vue';
import BaseAvatar from '../../components/BaseAvatar.vue';
import StatusBadge from '../../components/StatusBadge.vue';
import BaseModal from '../../components/BaseModal.vue';
import BaseButton from '../../components/BaseButton.vue';
import { useToast } from '../../composables/useToast';

const { push } = useToast();

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

// Single range picker (mirrors the Tasks page filter)
const dateRange = ref('');
const onDateRange = ({ from, to }) => {
  searchForm.value.date_from = from || '';
  searchForm.value.date_to = to || '';
};

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

/* ---------- Create modal (mirrors the Tasks page Add-Task popup) ----------
 * Fields = the classic /admin/money-transfers/create form; status + both OTPs
 * are set server-side by storePopup (status='new', generated 4-digit OTPs). */
const showCreate = ref(false);
const createForm = useForm({
  client_id: '', driver_id: '', from_location_id: '', to_location_id: '', amount: '',
});

function openCreate() {
  if (!can('money_transfer_create')) return;
  createForm.reset();
  createForm.clearErrors();
  showCreate.value = true;
}
function submitCreate() {
  createForm.post('/app/admin/money-transfers/popup', {
    preserveScroll: true,
    onSuccess: () => {
      showCreate.value = false;
      createForm.reset();
      push({ type: 'success', title: 'Created', message: 'Money transfer added successfully.' });
      doSearch();
    },
  });
}

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
  if (searchForm.value.date_from && searchForm.value.date_to) {
    dateRange.value = `${searchForm.value.date_from} to ${searchForm.value.date_to}`;
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
    : 'bg-surface border-slate-200 text-slate-500 hover:border-slate-300 dark:bg-white/5 dark:border-white/10 dark:text-slate-400';
};

const setStatusTab = (key) => {
  searchForm.value.status = key;
  doSearch();
};

/* ---------- DataTable toolbar exports (Copy / CSV / Excel / Print) ---------- */
const exportColumns = columns;
function cellText(r, c) {
  if (c.key === 'route') return `${r.from_location_name || ''} → ${r.to_location_name || ''}`;
  if (c.key === 'amount') return r.amount == null ? '' : `${r.amount} SAR`;
  return r[c.key] == null ? '' : String(r[c.key]);
}
function matrix() {
  const header = exportColumns.map((c) => c.label);
  const body = rows.value.map((r) => exportColumns.map((c) => cellText(r, c)));
  return { header, body };
}
function onExport(kind) {
  const { header, body } = matrix();
  if (!body.length) { push({ type: 'info', title: 'Nothing to export', message: 'No money transfers in the current view.' }); return; }

  if (kind === 'copy') {
    navigator.clipboard?.writeText([header.join('\t'), ...body.map((r) => r.join('\t'))].join('\n'));
    push({ type: 'success', title: 'Copied', message: `${body.length} rows copied to clipboard` });
  } else if (kind === 'csv') {
    const esc = (s) => `"${String(s).replace(/"/g, '""')}"`;
    const csv = [header.map(esc).join(','), ...body.map((r) => r.map(esc).join(','))].join('\n');
    const a = document.createElement('a');
    a.href = URL.createObjectURL(new Blob(['﻿' + csv], { type: 'text/csv;charset=utf-8' }));
    a.download = 'money-transfers.csv';
    a.click();
    URL.revokeObjectURL(a.href);
  } else if (kind === 'excel') {
    const th = header.map((h) => `<th>${h}</th>`).join('');
    const tr = body.map((r) => `<tr>${r.map((c) => `<td>${c}</td>`).join('')}</tr>`).join('');
    const html = `<html xmlns:x="urn:schemas-microsoft-com:office:excel"><head><meta charset="utf-8"></head><body><table border="1"><thead><tr>${th}</tr></thead><tbody>${tr}</tbody></table></body></html>`;
    const a = document.createElement('a');
    a.href = URL.createObjectURL(new Blob(['﻿' + html], { type: 'application/vnd.ms-excel' }));
    a.download = 'money-transfers.xls';
    a.click();
    URL.revokeObjectURL(a.href);
  } else if (kind === 'print') {
    const w = window.open('', '_blank');
    const th = header.map((h) => `<th>${h}</th>`).join('');
    const tr = body.map((r) => `<tr>${r.map((c) => `<td>${c}</td>`).join('')}</tr>`).join('');
    w.document.write(`<html dir="${document.documentElement.dir}"><head><title>Money Transfers</title><style>table{border-collapse:collapse;width:100%;font-family:Poppins,sans-serif;font-size:12px}th,td{border:1px solid #cbd5e1;padding:6px 8px;text-align:start}th{background:#005D69;color:#fff}</style></head><body><h3>Money Transfers</h3><table><thead><tr>${th}</tr></thead><tbody>${tr}</tbody></table></body></html>`);
    w.document.close(); w.focus(); w.print();
  }
}

// Dropdown options
const driverOpts = computed(() => [{ value: '', label: 'Any Driver' }, ...(props.filters?.drivers || [])]);
const clientOpts = computed(() => [{ value: '', label: 'Any Client' }, ...(props.filters?.clients || [])]);
const locationOpts = computed(() => [{ value: '', label: 'Any Location' }, ...(props.filters?.locations || [])]);

// Same lists without the "Any …" filter entries — for the create modal.
const modalDriverOpts   = computed(() => props.filters?.drivers || []);
const modalClientOpts   = computed(() => props.filters?.clients || []);
const modalLocationOpts = computed(() => props.filters?.locations || []);

</script>

<template>
  <div class="space-y-6">
    <Breadcrumb title="Money Transfers" :trail="[{ label: 'Operations' }, { label: 'Money Transfers' }]">
      <template #actions>
        <BaseButton v-if="can('money_transfer_create')" variant="primary" icon="ri-add-line" @click="openCreate">Add Money Transfer</BaseButton>
      </template>
    </Breadcrumb>

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
    <div v-show="showAdvanced" class="bg-surface dark:bg-surface-dark border dark:border-white/5 rounded-xl p-4 shadow-sm mb-4 transition-all">
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
        <div class="space-y-1.5 sm:col-span-2">
          <label class="text-xs font-bold text-slate-600 dark:text-slate-300">Date Range</label>
          <FormDate v-model="dateRange" mode="range" placeholder="Select range" @range="onDateRange" />
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
      @export="onExport"
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

      <template #row-actions="{ row }">
        <div class="inline-flex items-center gap-1">
          <a v-if="can('money_transfer_show')" :href="`/admin/money-transfers/${row.id}`" class="grid place-items-center w-8 h-8 rounded-lg text-info hover:bg-info/10 transition" title="View"><i class="ri-eye-line"></i></a>
          <a v-if="can('money_transfer_edit')" :href="`/admin/money-transfers/${row.id}/edit`" class="grid place-items-center w-8 h-8 rounded-lg text-primary-600 hover:bg-primary-50 dark:hover:bg-primary-500/10 transition" title="Edit"><i class="ri-pencil-line"></i></a>
          <button v-if="can('money_transfer_delete')" @click="confirmDelete(row.id)" class="grid place-items-center w-8 h-8 rounded-lg text-danger hover:bg-danger/10 transition" title="Delete"><i class="ri-delete-bin-line"></i></button>
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

    <!-- create money transfer (same modal pattern as the Tasks page Add Task) -->
    <BaseModal v-model="showCreate" title="Add Money Transfer" icon="ri-add-circle-line" size="xl">
      <form @submit.prevent="submitCreate" class="grid grid-cols-1 sm:grid-cols-2 gap-4">
        <FormSelect floating v-model="createForm.client_id" label="Client" :options="modalClientOpts"
          placeholder="Select Client" required :error="createForm.errors.client_id" />
        <FormSelect floating v-model="createForm.driver_id" label="Driver" :options="modalDriverOpts"
          placeholder="Select Driver" required :error="createForm.errors.driver_id" />
        <FormSelect floating v-model="createForm.from_location_id" label="From Location" :options="modalLocationOpts"
          placeholder="Select Location" required :error="createForm.errors.from_location_id" />
        <FormSelect floating v-model="createForm.to_location_id" label="To Location" :options="modalLocationOpts"
          placeholder="Select Location" :error="createForm.errors.to_location_id" />
        <div class="sm:col-span-2">
          <FormInput v-model="createForm.amount" label="Amount" type="number" unit="SAR" icon="ri-money-dollar-circle-line"
            placeholder="0.00" required :error="createForm.errors.amount" />
        </div>
        <p class="sm:col-span-2 text-[12px] text-slate-400 dark:text-slate-500 flex items-center gap-1.5">
          <i class="ri-information-line"></i>
          Status starts as <span class="font-semibold">New</span>; pickup &amp; dropoff OTPs are generated automatically.
        </p>
      </form>
      <template #footer>
        <BaseButton variant="light" @click="showCreate = false" :disabled="createForm.processing">Cancel</BaseButton>
        <BaseButton variant="primary" icon="ri-save-line" :loading="createForm.processing" @click="submitCreate">Save</BaseButton>
      </template>
    </BaseModal>

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
