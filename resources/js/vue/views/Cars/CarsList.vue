<script setup>
import { ref, computed, watch, onMounted } from 'vue';
import { router, usePage, useForm } from '@inertiajs/vue3';
import axios from 'axios';
import debounce from 'lodash/debounce';

import Breadcrumb from '../../components/Breadcrumb.vue';
import DataTable from '../../components/DataTable.vue';
import FormSelect from '../../components/FormSelect.vue';
import FormInput from '../../components/FormInput.vue';
import BaseAvatar from '../../components/BaseAvatar.vue';
import StatusBadge from '../../components/StatusBadge.vue';
import BaseModal from '../../components/BaseModal.vue';
import BaseButton from '../../components/BaseButton.vue';
import FormDate from '../../components/FormDate.vue';
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

/* ---------- Add / Edit modal (same popup pattern as the Tasks page) ----------
 * One modal serves both: create posts to the existing /app/admin/cars store,
 * edit puts to /app/admin/cars/{id} — same fields as the CarForm page, and the
 * list rows already carry every editable value so no extra fetch is needed. */
const STATUS_OPTS = [
  { value: '1', label: 'Enable' },
  { value: '2', label: 'Disable' },
];
const AFAQI_OPTS = [
  { value: '0', label: 'No' },
  { value: '1', label: 'Yes' },
];

const showCarModal = ref(false);
const editingId = ref(null);
const carForm = useForm({
  driver_id: '', imei: '', plate_number: '', model: '', color: '',
  contact_person: '', status: '1', afaqi: '0', description: '',
});

function openCreate() {
  if (!can('car_create')) return;
  editingId.value = null;
  carForm.reset();
  carForm.clearErrors();
  showCarModal.value = true;
}
function openEdit(row) {
  if (!can('car_edit')) return;
  editingId.value = row.id;
  carForm.clearErrors();
  carForm.driver_id      = row.driver_id ?? '';
  carForm.imei           = row.imei ?? '';
  carForm.plate_number   = row.plate_number ?? '';
  carForm.model          = row.model ?? '';
  carForm.color          = row.color ?? '';
  carForm.contact_person = row.contact_person ?? '';
  carForm.status         = String(row.status ?? '1');
  carForm.afaqi          = String(Number(row.afaqi ?? 0));
  carForm.description    = row.description ?? '';
  showCarModal.value = true;
}
function submitCar() {
  const opts = {
    preserveScroll: true,
    onSuccess: () => {
      showCarModal.value = false;
      push({ type: 'success', title: editingId.value ? 'Updated' : 'Created',
             message: editingId.value ? `Car #${editingId.value} updated.` : 'Car added successfully.' });
      carForm.reset();
      doSearch();
    },
  };
  if (editingId.value) carForm.put(`/app/admin/cars/${editingId.value}`, opts);
  else carForm.post('/app/admin/cars', opts);
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
// Same list without the "Any …" filter entry — for the add/edit modal.
const modalDriverOpts = computed(() => props.filters?.drivers || []);

</script>

<template>
  <div class="space-y-6">
    <Breadcrumb title="Cars" :trail="[{ label: 'Drivers' }, { label: 'Cars' }]">
      <template #actions>
        <BaseButton v-if="can('car_create')" variant="primary" icon="ri-add-line" @click="openCreate">Add Car</BaseButton>
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
    <div v-show="showAdvanced" class="bg-surface dark:bg-surface-dark border dark:border-white/5 rounded-xl p-4 shadow-sm mb-4 transition-all">
      <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-4 gap-4">
        <FormSelect v-model="searchForm.driver_id" label="Driver" :options="driverOpts" placeholder="Any Driver" />
        <FormInput v-model="searchForm.imei" label="IMEI" placeholder="GPS device IMEI" icon="ri-focus-3-line" />
        <FormInput v-model="searchForm.plate_number" label="Plate Number" placeholder="Plate number" icon="ri-car-line" />
        <FormDate v-model="dateRange" label="Date Range" mode="range" placeholder="Select range" @range="onDateRange" />
      </div>
      <div class="mt-4 flex justify-end gap-2">
        <BaseButton variant="light" icon="ri-refresh-line" @click="resetFilters">Reset</BaseButton>
        <BaseButton variant="primary" icon="ri-search-line" :loading="loading" @click="applyFilters">Apply Filters</BaseButton>
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

      <template #row-actions="{ row }">
        <div class="inline-flex items-center gap-1">
          <button v-if="can('car_show')" @click="router.visit(`/app/admin/cars/${row.id}`)" class="grid place-items-center w-8 h-8 rounded-lg text-info hover:bg-info/10 transition" title="View"><i class="ri-eye-line"></i></button>
          <button v-if="can('car_edit')" @click="openEdit(row)" class="grid place-items-center w-8 h-8 rounded-lg text-primary-600 hover:bg-primary-50 dark:hover:bg-primary-500/10 transition" title="Edit"><i class="ri-pencil-line"></i></button>
          <button v-if="can('car_delete')" @click="confirmDelete(row.id)" class="grid place-items-center w-8 h-8 rounded-lg text-danger hover:bg-danger/10 transition" title="Delete"><i class="ri-delete-bin-line"></i></button>
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

    <!-- add / edit car (same modal pattern as the Tasks page popups) -->
    <BaseModal v-model="showCarModal" :title="editingId ? `Edit Car #${editingId}` : 'Add Car'"
      :icon="editingId ? 'ri-pencil-line' : 'ri-add-circle-line'" size="xl">
      <form @submit.prevent="submitCar" class="grid grid-cols-1 sm:grid-cols-2 gap-4">
        <FormSelect floating v-model="carForm.driver_id" label="Driver" :options="modalDriverOpts"
          placeholder="Select Driver" :error="carForm.errors.driver_id" />
        <FormSelect floating v-model="carForm.status" label="Status" :options="STATUS_OPTS" :searchable="false"
          required :error="carForm.errors.status" />
        <FormInput v-model="carForm.imei" label="IMEI" placeholder="GPS device IMEI" icon="ri-focus-3-line"
          required :error="carForm.errors.imei" />
        <FormInput v-model="carForm.plate_number" label="Plate Number" placeholder="Plate number" icon="ri-car-line"
          required :error="carForm.errors.plate_number" />
        <FormInput v-model="carForm.model" label="Model" placeholder="Car model" :error="carForm.errors.model" />
        <FormInput v-model="carForm.color" label="Color" placeholder="Car color" :error="carForm.errors.color" />
        <FormInput v-model="carForm.contact_person" label="Contact Person" placeholder="Contact person name"
          required :error="carForm.errors.contact_person" />
        <FormSelect floating v-model="carForm.afaqi" label="Afaqi" :options="AFAQI_OPTS" :searchable="false"
          required :error="carForm.errors.afaqi" />
        <div class="sm:col-span-2">
          <FormInput v-model="carForm.description" label="Description" type="textarea" :rows="3"
            placeholder="Optional notes about this car..." :error="carForm.errors.description" />
        </div>
      </form>
      <template #footer>
        <BaseButton variant="light" @click="showCarModal = false" :disabled="carForm.processing">Cancel</BaseButton>
        <BaseButton variant="primary" icon="ri-save-line" :loading="carForm.processing" @click="submitCar">
          {{ editingId ? 'Save Changes' : 'Save Car' }}
        </BaseButton>
      </template>
    </BaseModal>

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
