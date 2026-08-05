<script setup>
/**
 * views/Attendances/AttendancesList.vue — SPA rebuild of /admin/attendances.
 * Logic mirrors the classic page 1:1: columns (ID / Driver+Mobile / Check-in /
 * Check-out / Status / Delay / Overtime / Source), create = driver + optional
 * shift (loaded per driver from /admin/drivers/{id}/get-shifts) + check-in/out
 * times + delay/overtime minutes with source pinned to 'manual', edit = same
 * minus shift, both dispatch ProcessAttendanceKPIJob server-side; delete &
 * mass-delete via the classic /admin routes (can-delete gate).
 * Design follows the Tasks page: Breadcrumb + FilterBar + status pills +
 * DataTable + popups + the standard action buttons.
 */
import { ref, computed, onMounted } from 'vue';
import axios from 'axios';
import { useForm } from '@inertiajs/vue3';
import debounce from 'lodash/debounce';

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
  initialRows:  { type: Array,  default: () => [] },
  initialTotal: { type: Number, default: 0 },
  filters:      { type: Object, default: () => ({}) }, // { drivers: [{value,label}] }
});

const { push } = useToast();
const { can, canDelete } = usePermissions();
const csrf = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';

/* ---------- filters ---------- */
const DEFAULT_FILTERS = { keyword: '', driver_id: '', is_late: '', date_from: '', date_to: '', sort_by: '', sort_order: '' };
const searchForm = ref({ ...DEFAULT_FILTERS });

const driverOpts = computed(() => [{ value: '', label: 'Any Driver' }, ...(props.filters?.drivers || [])]);

const dateRange = ref('');
function onDateRange({ from, to }) {
  searchForm.value.date_from = from || '';
  searchForm.value.date_to = to || '';
}

// Status pills (Tasks page pattern): classic badges are Late / On Time.
const statusPills = [
  { value: '0', label: 'On Time', dot: 'bg-success', active: 'bg-success/10 border-success/40 text-success' },
  { value: '1', label: 'Late',    dot: 'bg-danger',  active: 'bg-danger/10 border-danger/40 text-danger' },
];
function toggleStatus(v) {
  searchForm.value.is_late = searchForm.value.is_late === v ? '' : v;
  doSearch(1);
}

/* ---------- data (server-side JSON reloads) ---------- */
const rows = ref([]);
const total = ref(0);
const loading = ref(false);

const doSearch = debounce(async (page = 1, pageSize = 25) => {
  loading.value = true;
  try {
    const params = new URLSearchParams();
    Object.entries(searchForm.value).forEach(([k, v]) => { if (v !== '' && v != null) params.append(k, v); });
    params.append('page', page);
    params.append('pageSize', pageSize);
    const { data } = await axios.get(`/admin/attendances?${params.toString()}`, {
      headers: { Accept: 'application/json' },
    });
    rows.value = data.rows;
    total.value = data.total;
  } catch (e) {
    push({ type: 'error', title: 'Error', message: 'Failed to load attendances.' });
  } finally {
    loading.value = false;
  }
}, 300);

function onQuery({ page, pageSize, sortKey, sortDir }) {
  searchForm.value.sort_by = sortKey || '';
  searchForm.value.sort_order = sortDir || '';
  doSearch(page, pageSize);
}
function doApply() { doSearch(1); }
function doReset() { searchForm.value = { ...DEFAULT_FILTERS }; dateRange.value = ''; doSearch(1); }

onMounted(() => {
  rows.value = props.initialRows || [];
  total.value = props.initialTotal || 0;
});

/* ---------- columns: classic index set 1:1 ---------- */
const columns = [
  { key: 'sequence',         label: '#',              sticky: 'start', width: '52px' },
  { key: 'id',               label: 'ID',             sticky: 'start', width: '80px', sortable: true },
  { key: 'driver_name',      label: 'Driver',         wrap: true, width: '190px' },
  { key: 'checkin_time',     label: 'Check-in',       ltr: true, sortable: true },
  { key: 'checkout_time',    label: 'Check-out',      ltr: true, sortable: true },
  { key: 'is_late',          label: 'Status' },
  { key: 'delay_minutes',    label: 'Delay (Min)',    align: 'center' },
  { key: 'overtime_minutes', label: 'Overtime (Min)', align: 'center' },
  { key: 'source',           label: 'Source' },
];

// split "Y-m-d H:i:s" into date + time for a two-line cell
function splitDT(v) {
  if (!v) return null;
  const [d, t] = String(v).split(' ');
  return { d, t: (t || '').slice(0, 5) };
}

/* ---------- create / edit popup ---------- */
const showModal = ref(false);
const editingId = ref(null);
const form = useForm({
  driver_id: '', shift_id: '', checkin_time: '', checkout_time: '',
  delay_minutes: 0, overtime_minutes: 0,
});

// Shift options load per driver — same /admin/drivers/{id}/get-shifts the
// classic create page calls. Create-only (the classic edit form has no shift).
const shiftOpts = ref([]);
const shiftsLoading = ref(false);
async function loadShifts(driverId) {
  shiftOpts.value = [];
  form.shift_id = '';
  if (!driverId || editingId.value) return;
  shiftsLoading.value = true;
  try {
    const { data } = await axios.get(`/admin/drivers/${driverId}/get-shifts`);
    shiftOpts.value = (data || []).map((s) => ({
      value: s.id,
      label: `Shift ${s.shift_number ?? s.id} (${s.start_time} - ${s.end_time})`,
    }));
  } catch (e) {
    shiftOpts.value = [];
  } finally {
    shiftsLoading.value = false;
  }
}

function openCreate() {
  if (!can('attendance_create')) return;
  editingId.value = null;
  form.reset();
  form.clearErrors();
  shiftOpts.value = [];
  showModal.value = true;
}
function openEdit(row) {
  if (!can('attendance_edit')) return;
  editingId.value = row.id;
  form.clearErrors();
  form.driver_id        = row.driver_id ?? '';
  form.shift_id         = '';
  form.checkin_time     = row.checkin_hm ?? '';
  form.checkout_time    = row.checkout_hm ?? '';
  form.delay_minutes    = row.delay_minutes ?? 0;
  form.overtime_minutes = row.overtime_minutes ?? 0;
  showModal.value = true;
}
function submitForm() {
  const opts = {
    preserveScroll: true,
    onSuccess: () => {
      showModal.value = false;
      push({ type: 'success', title: editingId.value ? 'Updated' : 'Created',
             message: editingId.value ? `Attendance #${editingId.value} updated.` : 'Attendance created successfully.' });
      form.reset();
      doSearch();
    },
  };
  if (editingId.value) form.put(`/admin/attendances/${editingId.value}/popup`, opts);
  else form.post('/admin/attendances/popup', opts);
}

/* ---------- delete via the EXISTING /admin destroy routes (can-delete gate) ---------- */
const showDel = ref(false);
const delTarget = ref(null);
function askDelete(row) { delTarget.value = row; showDel.value = true; }

async function webDelete(url, ids) {
  const body = new URLSearchParams();
  body.set('_method', 'DELETE');
  body.set('_token', csrf);
  if (ids) ids.forEach((id) => body.append('ids[]', id));
  return fetch(url, {
    method: 'POST', credentials: 'same-origin',
    headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json' },
    body,
  });
}
async function confirmDelete() {
  try {
    const res = await webDelete('/admin/attendances/' + delTarget.value.id);
    if (res.status === 403) push({ type: 'error', title: 'Forbidden', message: 'You are not allowed to delete.' });
    else { push({ type: 'success', title: 'Deleted', message: `Attendance #${delTarget.value.id} removed` }); doSearch(); }
  } catch (e) { push({ type: 'error', title: 'Error', message: 'Delete failed.' }); }
  showDel.value = false;
}
async function bulkDelete(ids) {
  try {
    const res = await webDelete('/admin/attendances/destroy', ids);
    if (res.status === 403) push({ type: 'error', title: 'Forbidden', message: 'You are not allowed to delete.' });
    else { push({ type: 'success', title: 'Bulk delete', message: `${ids.length} attendances removed` }); doSearch(); }
  } catch (e) { push({ type: 'error', title: 'Error', message: 'Bulk delete failed.' }); }
}
</script>

<template>
  <div>
    <Breadcrumb title="Attendances" :trail="[{ label: 'Drivers' }, { label: 'Attendances' }]">
      <template #actions>
        <BaseButton v-if="can('attendance_create')" variant="primary" icon="ri-add-line" @click="openCreate">Add Attendance</BaseButton>
      </template>
    </Breadcrumb>

    <!-- filter bar (Tasks page design) -->
    <FilterBar :loading="loading" subtitle="refine the attendance list" @search="doApply" @reset="doReset">
      <FormInput  v-model="searchForm.keyword" label="Keyword" placeholder="ID, driver name or mobile…" icon="ri-search-line" />
      <FormSelect v-model="searchForm.driver_id" label="Driver" :options="driverOpts" placeholder="Any Driver" />
      <FormDate   v-model="dateRange" label="Date Range" mode="range" placeholder="Select range" @range="onDateRange" />

      <!-- Status as colored pills (same pattern as Tasks) -->
      <template #actions-extra>
        <button
          v-for="s in statusPills" :key="s.value" type="button"
          @click="toggleStatus(s.value)"
          class="inline-flex items-center gap-1.5 ps-2 pe-2.5 h-7 rounded-full border text-[11px] font-bold transition"
          :class="searchForm.is_late === s.value
            ? s.active
            : 'bg-surface dark:bg-white/5 border-slate-200 dark:border-white/10 text-slate-500 dark:text-slate-400 hover:border-slate-300'"
        >
          <span class="w-1.5 h-1.5 rounded-full" :class="s.dot"></span>
          {{ s.label }}
        </button>
      </template>
    </FilterBar>

    <!-- data table (server-side) -->
    <DataTable
      title="Attendances"
      :columns="columns" :rows="rows" row-key="id"
      :loading="loading" :server-side="true" :total="total" :searchable="false"
      :bulk-actions="canDelete() ? [{ label: 'Delete', icon: 'ri-delete-bin-line', tone: 'danger', event: 'bulk-delete' }] : []"
      @query="onQuery" @bulk-delete="bulkDelete"
    >
      <template #cell-id="{ value }">
        <span class="font-black text-[#0ab39c] dark:text-[#0ab39c]">#{{ value }}</span>
      </template>
      <template #cell-driver_name="{ row, value }">
        <div v-if="value" class="flex items-center gap-2">
          <BaseAvatar :name="value" :size="26" class="-mt-[3px]" />
          <div class="min-w-0">
            <div class="text-[12.5px] font-bold text-ink dark:text-slate-200 truncate">{{ value }}</div>
            <div class="text-[11px] text-slate-500 truncate" dir="ltr">{{ row.driver_mobile || '' }}</div>
          </div>
        </div>
        <span v-else class="text-slate-400">—</span>
      </template>
      <template #cell-checkin_time="{ value }">
        <div v-if="splitDT(value)" class="leading-tight whitespace-nowrap">
          <span class="font-bold text-success">{{ splitDT(value).t }}</span>
          <span class="text-[11px] text-slate-400 ms-1.5">{{ splitDT(value).d }}</span>
        </div>
        <span v-else class="text-slate-400">—</span>
      </template>
      <template #cell-checkout_time="{ value }">
        <div v-if="splitDT(value)" class="leading-tight whitespace-nowrap">
          <span class="font-bold text-danger">{{ splitDT(value).t }}</span>
          <span class="text-[11px] text-slate-400 ms-1.5">{{ splitDT(value).d }}</span>
        </div>
        <span v-else class="text-slate-400">—</span>
      </template>
      <template #cell-is_late="{ value }">
        <span v-if="value" class="inline-flex items-center gap-1.5 ps-2 pe-2.5 h-6 rounded-full text-[11.5px] font-semibold tracking-wide whitespace-nowrap bg-danger/10 text-danger">
          <span class="w-1.5 h-1.5 rounded-full animate-pulse-ring bg-danger"></span>Late
        </span>
        <span v-else class="inline-flex items-center gap-1.5 ps-2 pe-2.5 h-6 rounded-full text-[11.5px] font-semibold tracking-wide whitespace-nowrap bg-success/10 text-success">
          <span class="w-1.5 h-1.5 rounded-full animate-pulse-ring bg-success"></span>On Time
        </span>
      </template>
      <template #cell-delay_minutes="{ value }">
        <span v-if="value > 0" class="font-bold text-amber-500">{{ value }}</span>
        <span v-else class="text-slate-400">0</span>
      </template>
      <template #cell-overtime_minutes="{ row }">
        <span v-if="row.overtime_minutes > 0" class="font-bold text-success">+{{ row.overtime_minutes }}</span>
        <span v-else-if="row.early_leave_minutes > 0" class="font-bold text-danger">-{{ row.early_leave_minutes }}</span>
        <span v-else class="text-slate-400">0</span>
      </template>
      <template #cell-source="{ value }">
        <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-md text-[11.5px] font-bold border"
          :class="String(value).toLowerCase() === 'manual'
            ? 'bg-slate-50 text-slate-600 border-slate-200 dark:bg-white/5 dark:text-slate-300 dark:border-white/10'
            : 'bg-primary-50 text-primary-700 border-primary-100 dark:bg-primary-500/10 dark:text-primary-300 dark:border-primary-500/20'">
          <i :class="String(value).toLowerCase() === 'manual' ? 'ri-hand-coin-line' : 'ri-robot-2-line'"></i>{{ value }}
        </span>
      </template>

      <template #row-actions="{ row }">
        <div class="inline-flex items-center gap-1">
          <button v-if="can('attendance_edit')" @click="openEdit(row)" class="grid place-items-center w-8 h-8 rounded-lg text-primary-600 hover:bg-primary-50 dark:hover:bg-primary-500/10 transition" title="Edit"><i class="ri-pencil-line"></i></button>
          <button v-if="canDelete()" @click="askDelete(row)" class="grid place-items-center w-8 h-8 rounded-lg text-danger hover:bg-danger/10 transition" title="Delete"><i class="ri-delete-bin-line"></i></button>
        </div>
      </template>

      <template #empty>
        <div class="py-12 flex flex-col items-center justify-center text-center">
          <div class="w-16 h-16 bg-slate-100 dark:bg-surface-dark-solid rounded-full flex items-center justify-center mb-4">
            <i class="ri-time-line text-2xl text-slate-400"></i>
          </div>
          <h3 class="text-sm font-semibold text-ink dark:text-white mb-1">No attendances found</h3>
          <p class="text-sm text-slate-500 max-w-sm">There are no attendance records matching your filters.</p>
        </div>
      </template>
    </DataTable>

    <!-- create / edit attendance (same modal pattern as the Tasks page popups) -->
    <BaseModal v-model="showModal" :title="editingId ? `Edit Attendance #${editingId}` : 'Add Attendance'"
      :icon="editingId ? 'ri-pencil-line' : 'ri-add-circle-line'" size="lg">
      <form @submit.prevent="submitForm" class="grid grid-cols-1 sm:grid-cols-2 gap-4">
        <FormSelect floating v-model="form.driver_id" label="Driver" :options="props.filters?.drivers || []"
          placeholder="Select driver" required :error="form.errors.driver_id"
          @update:modelValue="loadShifts" />
        <FormSelect v-if="!editingId" floating v-model="form.shift_id" label="Shift"
          :options="shiftOpts" :searchable="false" :disabled="!form.driver_id || shiftsLoading"
          :placeholder="shiftsLoading ? 'Loading shifts…' : (form.driver_id ? (shiftOpts.length ? 'Select shift' : 'No active shifts') : 'Select a driver first')"
          :error="form.errors.shift_id" />
        <FormDate v-model="form.checkin_time" label="Check-in Time" mode="time" floating
          :error="form.errors.checkin_time" />
        <FormDate v-model="form.checkout_time" label="Check-out Time" mode="time" floating
          :error="form.errors.checkout_time" />
        <FormInput v-model="form.delay_minutes" label="Delay Minutes" type="number" unit="min"
          :error="form.errors.delay_minutes" />
        <FormInput v-model="form.overtime_minutes" label="Overtime" type="number" unit="min"
          :error="form.errors.overtime_minutes" />
        <p class="sm:col-span-2 text-[12px] text-slate-400 dark:text-slate-500 flex items-center gap-1.5">
          <i class="ri-information-line"></i>
          Source is recorded as <span class="font-semibold">Manual</span>; late/overtime KPIs recalculate automatically after saving.
        </p>
      </form>
      <template #footer>
        <BaseButton variant="light" @click="showModal = false" :disabled="form.processing">Cancel</BaseButton>
        <BaseButton variant="primary" icon="ri-save-line" :loading="form.processing" @click="submitForm">
          {{ editingId ? 'Save Changes' : 'Save Attendance' }}
        </BaseButton>
      </template>
    </BaseModal>

    <!-- delete confirm -->
    <BaseModal v-model="showDel" title="Confirm delete" icon="ri-error-warning-line" tone="danger" size="sm">
      <p class="text-sm text-slate-600 dark:text-slate-300">
        Are you sure you want to delete <span class="font-semibold text-ink dark:text-slate-100">Attendance #{{ delTarget?.id }}</span>
        <template v-if="delTarget?.driver_name"> for <span class="font-semibold text-ink dark:text-slate-100">{{ delTarget.driver_name }}</span></template>?
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
