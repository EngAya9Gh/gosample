<script setup>
/**
 * views/Containers/ContainersList.vue — SPA rebuild of /admin/containers.
 * Mirrors the classic page's features 1:1 (columns: # / ID / Car / Sensor /
 * Type / Description / Status; actions: print barcode / view / edit / delete;
 * bulk delete) with the Tasks page design: Breadcrumb + FilterBar + status
 * pills + DataTable + create/edit popups. Create/edit reuse the parity-checked
 * container popup endpoints (storePopup / updatePopup); deletes reuse the
 * classic /admin/containers destroy routes (can-delete gate).
 */
import { ref, computed, onMounted, watch } from 'vue';
import axios from 'axios';
import { useForm } from '@inertiajs/vue3';
import debounce from 'lodash/debounce';

import Breadcrumb from '../../components/Breadcrumb.vue';
import FilterBar from '../../components/FilterBar.vue';
import DataTable from '../../components/DataTable.vue';
import FormInput from '../../components/FormInput.vue';
import FormSelect from '../../components/FormSelect.vue';
import BaseButton from '../../components/BaseButton.vue';
import BaseModal from '../../components/BaseModal.vue';
import StatusBadge from '../../components/StatusBadge.vue';
import { useToast } from '../../composables/useToast';
import { usePermissions } from '../../composables/usePermissions';

const props = defineProps({
  initialRows:  { type: Array,  default: () => [] },
  initialTotal: { type: Number, default: 0 },
  filters:      { type: Object, default: () => ({}) }, // { cars: [{value,label}] }
});

const { push } = useToast();
const { can, canDelete } = usePermissions();
const csrf = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';

/* ---------- filters (classic page has none — keyword + the row's own fields) ---------- */
const DEFAULT_FILTERS = { keyword: '', car_id: '', type: '', status: '', sort_by: '', sort_order: '' };
const searchForm = ref({ ...DEFAULT_FILTERS });

const TYPE_OPTS = ['ROOM', 'REFRIGERATE', 'FROZEN'].map((v) => ({ value: v, label: v }));
const carOpts = computed(() => [{ value: '', label: 'Any Car' }, ...(props.filters?.cars || [])]);

// Status as colored pills (Tasks page pattern) — Container::STATUS_SELECT.
const statusPills = [
  { value: '1', label: 'Enabled',  dot: 'bg-success', active: 'bg-success/10 border-success/40 text-success' },
  { value: '2', label: 'Disabled', dot: 'bg-danger',  active: 'bg-danger/10 border-danger/40 text-danger' },
];
function toggleStatus(v) {
  searchForm.value.status = searchForm.value.status === v ? '' : v;
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
    Object.entries(searchForm.value).forEach(([k, v]) => {
      if (v !== '' && v !== null && v !== undefined) params.append(k, v);
    });
    params.append('page', page);
    params.append('pageSize', pageSize);
    const { data } = await axios.get(`/app/admin/containers?${params.toString()}`, {
      headers: { Accept: 'application/json' },
    });
    rows.value = data.rows;
    total.value = data.total;
  } catch (e) {
    push({ type: 'error', title: 'Error', message: 'Failed to load containers.' });
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
function doReset() { searchForm.value = { ...DEFAULT_FILTERS }; doSearch(1); }

onMounted(() => {
  rows.value = props.initialRows || [];
  total.value = props.initialTotal || 0;
});

/* ---------- columns: classic index set 1:1 ---------- */
const columns = [
  { key: 'sequence',    label: '#',           sticky: 'start', width: '52px' },
  { key: 'id',          label: 'ID',          sticky: 'start', width: '80px', sortable: true },
  { key: 'car_name',    label: 'Car' },
  { key: 'imei',        label: 'Sensor' },
  { key: 'type',        label: 'Type' },
  { key: 'description', label: 'Description', wrap: true, width: '240px' },
  { key: 'status',      label: 'Status' },
];

/* ---------- create / edit popup (same parity-checked fields as the classic
 * create/edit forms: Car optional, Sensor/Model/Type/Status required, no
 * preselected values on create) ---------- */
const STATUS_OPTS = [
  { value: '1', label: 'enabled' },
  { value: '2', label: 'disabled' },
];

const showModal = ref(false);
const editingId = ref(null);
const form = useForm({ car_id: '', imei: '', type: '', model: '', status: '', description: '' });

function openCreate() {
  if (!can('container_create')) return;
  editingId.value = null;
  form.reset();
  form.clearErrors();
  showModal.value = true;
}
function openEdit(row) {
  if (!can('container_edit')) return;
  editingId.value = row.id;
  form.clearErrors();
  form.car_id      = row.car_id ?? '';
  form.imei        = row.imei ?? '';
  form.type        = row.type ?? '';
  form.model       = row.model ?? '';
  form.status      = String(row.status ?? '');
  form.description = row.description ?? '';
  showModal.value = true;
}
function submitForm() {
  const opts = {
    preserveScroll: true,
    onSuccess: () => {
      showModal.value = false;
      push({ type: 'success', title: editingId.value ? 'Updated' : 'Created',
             message: editingId.value ? `Container #${editingId.value} updated.` : 'Container created successfully.' });
      form.reset();
      doSearch();
    },
  };
  if (editingId.value) form.put(`/app/admin/containers/${editingId.value}/popup`, opts);
  else form.post('/app/admin/containers/popup', opts);
}

/* ---------- print barcode (classic printReport: opens + prints itself) ---------- */
function printBarcode(row) {
  window.open(`/app/admin/containers/${row.id}/barcode`, '_blank');
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
    const res = await webDelete('/admin/containers/' + delTarget.value.id);
    if (res.status === 403) push({ type: 'error', title: 'Forbidden', message: 'You are not allowed to delete.' });
    else { push({ type: 'success', title: 'Deleted', message: `Container #${delTarget.value.id} removed` }); doSearch(); }
  } catch (e) { push({ type: 'error', title: 'Error', message: 'Delete failed.' }); }
  showDel.value = false;
}
async function bulkDelete(ids) {
  try {
    const res = await webDelete('/admin/containers/destroy', ids);
    if (res.status === 403) push({ type: 'error', title: 'Forbidden', message: 'You are not allowed to delete.' });
    else { push({ type: 'success', title: 'Bulk delete', message: `${ids.length} containers removed` }); doSearch(); }
  } catch (e) { push({ type: 'error', title: 'Error', message: 'Bulk delete failed.' }); }
}
</script>

<template>
  <div>
    <Breadcrumb title="Containers" :trail="[{ label: 'Containers' }]">
      <template #actions>
        <BaseButton v-if="can('container_create')" variant="primary" icon="ri-add-line" @click="openCreate">Add Container</BaseButton>
      </template>
    </Breadcrumb>

    <!-- filter bar (Tasks page design) -->
    <FilterBar :loading="loading" subtitle="refine the container list" @search="doApply" @reset="doReset">
      <FormInput  v-model="searchForm.keyword" label="Keyword" placeholder="ID, sensor IMEI, model, car plate…" icon="ri-search-line" />
      <FormSelect v-model="searchForm.car_id" label="Car" :options="carOpts" placeholder="Any Car" />
      <FormSelect v-model="searchForm.type" label="Type" :options="[{ value: '', label: '— Any —' }, ...TYPE_OPTS]" :searchable="false" placeholder="Any Type" />

      <!-- Status as colored pills (same pattern as Tasks) -->
      <template #actions-extra>
        <button
          v-for="s in statusPills" :key="s.value" type="button"
          @click="toggleStatus(s.value)"
          class="inline-flex items-center gap-1.5 ps-2 pe-2.5 h-7 rounded-full border text-[11px] font-bold transition"
          :class="searchForm.status === s.value
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
      title="Containers"
      :columns="columns" :rows="rows" row-key="id"
      :loading="loading" :server-side="true" :total="total" :searchable="false"
      :bulk-actions="canDelete() ? [{ label: 'Delete', icon: 'ri-delete-bin-line', tone: 'danger', event: 'bulk-delete' }] : []"
      @query="onQuery" @bulk-delete="bulkDelete"
    >
      <template #cell-id="{ value }">
        <span class="font-black text-[#0ab39c] dark:text-[#0ab39c]">#{{ value }}</span>
      </template>
      <template #cell-car_name="{ value }">
        <span v-if="value" class="inline-flex items-center gap-1.5 font-bold text-slate-700 dark:text-slate-300">
          <i class="ri-car-line text-slate-400"></i>{{ value }}
        </span>
        <span v-else class="text-slate-400">—</span>
      </template>
      <template #cell-imei="{ value }">
        <span class="font-mono text-xs text-slate-600 dark:text-slate-400">{{ value || '—' }}</span>
      </template>
      <template #cell-type="{ value }">
        <span v-if="value" class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-md text-[11.5px] font-bold border"
          :class="value === 'FROZEN'
            ? 'bg-sky-50 text-sky-700 border-sky-200 dark:bg-sky-500/10 dark:text-sky-400 dark:border-sky-500/20'
            : value === 'REFRIGERATE'
              ? 'bg-cyan-50 text-cyan-700 border-cyan-200 dark:bg-cyan-500/10 dark:text-cyan-400 dark:border-cyan-500/20'
              : 'bg-amber-50 text-amber-700 border-amber-200 dark:bg-amber-500/10 dark:text-amber-400 dark:border-amber-500/20'">
          <i :class="value === 'ROOM' ? 'ri-home-4-line' : 'ri-temp-cold-line'"></i>{{ value }}
        </span>
        <span v-else class="text-slate-400">—</span>
      </template>
      <template #cell-description="{ value }">
        <span class="text-sm text-slate-500 dark:text-slate-400 whitespace-normal leading-snug">{{ value || '—' }}</span>
      </template>
      <template #cell-status="{ value }">
        <StatusBadge v-if="value == 1" status="ENABLED" label="Enabled" />
        <StatusBadge v-else-if="value == 2" status="DISABLED" label="Disabled" />
        <span v-else class="text-slate-400">—</span>
      </template>

      <template #row-actions="{ row }">
        <div class="inline-flex items-center gap-1">
          <button @click="printBarcode(row)" class="grid place-items-center w-8 h-8 rounded-lg text-success hover:bg-success/10 transition" title="Print Barcode"><i class="ri-printer-line"></i></button>
          <a v-if="can('container_show')" :href="`/admin/containers/${row.id}`" class="grid place-items-center w-8 h-8 rounded-lg text-info hover:bg-info/10 transition" title="View"><i class="ri-eye-line"></i></a>
          <button v-if="can('container_edit')" @click="openEdit(row)" class="grid place-items-center w-8 h-8 rounded-lg text-primary-600 hover:bg-primary-50 dark:hover:bg-primary-500/10 transition" title="Edit"><i class="ri-pencil-line"></i></button>
          <button v-if="canDelete()" @click="askDelete(row)" class="grid place-items-center w-8 h-8 rounded-lg text-danger hover:bg-danger/10 transition" title="Delete"><i class="ri-delete-bin-line"></i></button>
        </div>
      </template>

      <template #empty>
        <div class="py-12 flex flex-col items-center justify-center text-center">
          <div class="w-16 h-16 bg-slate-100 dark:bg-surface-dark-solid rounded-full flex items-center justify-center mb-4">
            <i class="ri-archive-2-line text-2xl text-slate-400"></i>
          </div>
          <h3 class="text-sm font-semibold text-ink dark:text-white mb-1">No containers found</h3>
          <p class="text-sm text-slate-500 max-w-sm">There are no containers matching your filters.</p>
        </div>
      </template>
    </DataTable>

    <!-- create / edit container (same modal pattern as the Tasks page popups) -->
    <BaseModal v-model="showModal" :title="editingId ? `Edit Container #${editingId}` : 'Create Container'"
      :icon="editingId ? 'ri-pencil-line' : 'ri-add-circle-line'" size="lg">
      <form @submit.prevent="submitForm" class="grid grid-cols-1 sm:grid-cols-2 gap-4">
        <FormSelect floating v-model="form.car_id" label="Car" :options="props.filters?.cars || []"
          placeholder="Select car" :error="form.errors.car_id" />
        <FormInput v-model="form.imei" label="Sensor" placeholder="Enter sensor IMEI" icon="ri-focus-3-line"
          required :error="form.errors.imei" />
        <FormInput v-model="form.model" label="Model" placeholder="Enter model"
          required :error="form.errors.model" />
        <FormSelect floating v-model="form.type" label="Type" :options="TYPE_OPTS" :searchable="false"
          placeholder="Select type" required :error="form.errors.type" />
        <FormInput v-model="form.description" label="Description" type="textarea" :rows="3"
          placeholder="Optional notes" :error="form.errors.description" />
        <FormSelect floating v-model="form.status" label="Status" :options="STATUS_OPTS" :searchable="false"
          placeholder="Select status" required :error="form.errors.status" />
      </form>
      <template #footer>
        <BaseButton variant="light" @click="showModal = false" :disabled="form.processing">Cancel</BaseButton>
        <BaseButton variant="primary" icon="ri-save-line" :loading="form.processing" @click="submitForm">
          {{ editingId ? 'Save Changes' : 'Save Container' }}
        </BaseButton>
      </template>
    </BaseModal>

    <!-- delete confirm -->
    <BaseModal v-model="showDel" title="Confirm delete" icon="ri-error-warning-line" tone="danger" size="sm">
      <p class="text-sm text-slate-600 dark:text-slate-300">
        Are you sure you want to delete <span class="font-semibold text-ink dark:text-slate-100">Container #{{ delTarget?.id }}</span>?
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
