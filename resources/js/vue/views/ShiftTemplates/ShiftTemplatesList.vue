<script setup>
/**
 * views/ShiftTemplates/ShiftTemplatesList.vue — SPA rebuild of /admin/shift-templates.
 * Logic mirrors the classic page 1:1: columns (ID / Template Name / Start Time /
 * End Time), create & edit = name + start/end times (required), every action
 * gated by attendance_access (same as the classic controller), delete &
 * mass-delete via the classic /admin routes. Design follows the Tasks page:
 * Breadcrumb + FilterBar + DataTable + popups + the standard action buttons.
 */
import { ref, onMounted } from 'vue';
import axios from 'axios';
import { useForm } from '@inertiajs/vue3';
import debounce from 'lodash/debounce';

import Breadcrumb from '../../components/Breadcrumb.vue';
import FilterBar from '../../components/FilterBar.vue';
import DataTable from '../../components/DataTable.vue';
import FormInput from '../../components/FormInput.vue';
import FormDate from '../../components/FormDate.vue';
import BaseButton from '../../components/BaseButton.vue';
import BaseModal from '../../components/BaseModal.vue';
import { useToast } from '../../composables/useToast';
import { usePermissions } from '../../composables/usePermissions';

const props = defineProps({
  initialRows:  { type: Array,  default: () => [] },
  initialTotal: { type: Number, default: 0 },
});

const { push } = useToast();
const { can } = usePermissions();
const csrf = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';

/* ---------- filters (classic page has none — keyword over ID/name) ---------- */
const DEFAULT_FILTERS = { keyword: '', sort_by: '', sort_order: '' };
const searchForm = ref({ ...DEFAULT_FILTERS });

/* ---------- data (server-side JSON reloads) ---------- */
const rows = ref([]);
const total = ref(0);
const loading = ref(false);

const doSearch = debounce(async (page = 1, pageSize = 25) => {
  loading.value = true;
  try {
    const params = new URLSearchParams();
    Object.entries(searchForm.value).forEach(([k, v]) => { if (v) params.append(k, v); });
    params.append('page', page);
    params.append('pageSize', pageSize);
    const { data } = await axios.get(`/admin/shift-templates?${params.toString()}`, {
      headers: { Accept: 'application/json' },
    });
    rows.value = data.rows;
    total.value = data.total;
  } catch (e) {
    push({ type: 'error', title: 'Error', message: 'Failed to load shift templates.' });
  } finally {
    loading.value = false;
  }
}, 300);

function onQuery({ page, pageSize, sortKey, sortDir, q }) {
  searchForm.value.sort_by = sortKey || '';
  searchForm.value.sort_order = sortDir || '';
  if (q !== undefined) searchForm.value.keyword = q;
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
  { key: 'sequence',   label: '#',             sticky: 'start', width: '52px' },
  { key: 'id',         label: 'ID',            sticky: 'start', width: '80px', sortable: true },
  { key: 'name',       label: 'Template Name', sortable: true },
  { key: 'start_time', label: 'Start Time',    ltr: true, sortable: true },
  { key: 'end_time',   label: 'End Time',      ltr: true, sortable: true },
  { key: 'created_at', label: 'Created At',    ltr: true },
];

/* ---------- create / edit popup (name + start/end times, all required) ---------- */
const showModal = ref(false);
const editingId = ref(null);
const form = useForm({ name: '', start_time: '', end_time: '' });

function openCreate() {
  if (!can('attendance_access')) return;
  editingId.value = null;
  form.reset();
  form.clearErrors();
  showModal.value = true;
}
function openEdit(row) {
  if (!can('attendance_access')) return;
  editingId.value = row.id;
  form.clearErrors();
  form.name       = row.name ?? '';
  form.start_time = row.start_time ?? '';
  form.end_time   = row.end_time ?? '';
  showModal.value = true;
}
function submitForm() {
  const opts = {
    preserveScroll: true,
    onSuccess: () => {
      showModal.value = false;
      push({ type: 'success', title: editingId.value ? 'Updated' : 'Created',
             message: editingId.value ? `Shift template #${editingId.value} updated.` : 'Shift template created successfully.' });
      form.reset();
      doSearch();
    },
  };
  if (editingId.value) form.put(`/admin/shift-templates/${editingId.value}/popup`, opts);
  else form.post('/admin/shift-templates/popup', opts);
}

/* ---------- delete via the EXISTING /admin routes (attendance_access gate) ---------- */
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
    const res = await webDelete('/admin/shift-templates/' + delTarget.value.id);
    if (res.status === 403) push({ type: 'error', title: 'Forbidden', message: 'You are not allowed to delete.' });
    else { push({ type: 'success', title: 'Deleted', message: `Template #${delTarget.value.id} removed` }); doSearch(); }
  } catch (e) { push({ type: 'error', title: 'Error', message: 'Delete failed.' }); }
  showDel.value = false;
}
async function bulkDelete(ids) {
  try {
    const res = await webDelete('/admin/shift-templates/destroy', ids);
    if (res.status === 403) push({ type: 'error', title: 'Forbidden', message: 'You are not allowed to delete.' });
    else { push({ type: 'success', title: 'Bulk delete', message: `${ids.length} templates removed` }); doSearch(); }
  } catch (e) { push({ type: 'error', title: 'Error', message: 'Bulk delete failed.' }); }
}
</script>

<template>
  <div>
    <Breadcrumb title="Shift Templates" :trail="[{ label: 'Drivers' }, { label: 'Shift Templates' }]">
      <template #actions>
        <BaseButton v-if="can('attendance_access')" variant="primary" icon="ri-add-line" @click="openCreate">Add Shift Template</BaseButton>
      </template>
    </Breadcrumb>

    <!-- filter bar (Tasks page design) -->
    <FilterBar :loading="loading" subtitle="refine the template list" @search="doApply" @reset="doReset">
      <FormInput v-model="searchForm.keyword" label="Keyword" placeholder="Template ID or name…" icon="ri-search-line" />
    </FilterBar>

    <!-- data table (server-side) -->
    <DataTable
      title="Shift Templates"
      :columns="columns" :rows="rows" row-key="id"
      :loading="loading" :server-side="true" :total="total" :searchable="false"
      :bulk-actions="can('attendance_access') ? [{ label: 'Delete', icon: 'ri-delete-bin-line', tone: 'danger', event: 'bulk-delete' }] : []"
      @query="onQuery" @bulk-delete="bulkDelete"
    >
      <template #cell-id="{ value }">
        <span class="font-black text-[#0ab39c] dark:text-[#0ab39c]">#{{ value }}</span>
      </template>
      <template #cell-name="{ value }">
        <span class="font-extrabold text-slate-800 dark:text-white">{{ value || '—' }}</span>
      </template>
      <template #cell-start_time="{ value }">
        <!-- classic: green start badge / red end badge -->
        <span v-if="value" class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-md text-[12px] font-bold bg-success/10 text-success">
          <i class="ri-login-circle-line"></i>{{ value }}
        </span>
        <span v-else class="text-slate-400">—</span>
      </template>
      <template #cell-end_time="{ value }">
        <span v-if="value" class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-md text-[12px] font-bold bg-danger/10 text-danger">
          <i class="ri-logout-circle-r-line"></i>{{ value }}
        </span>
        <span v-else class="text-slate-400">—</span>
      </template>

      <template #row-actions="{ row }">
        <div class="inline-flex items-center gap-1">
          <button v-if="can('attendance_access')" @click="openEdit(row)" class="grid place-items-center w-8 h-8 rounded-lg text-primary-600 hover:bg-primary-50 dark:hover:bg-primary-500/10 transition" title="Edit"><i class="ri-pencil-line"></i></button>
          <button v-if="can('attendance_access')" @click="askDelete(row)" class="grid place-items-center w-8 h-8 rounded-lg text-danger hover:bg-danger/10 transition" title="Delete"><i class="ri-delete-bin-line"></i></button>
        </div>
      </template>

      <template #empty>
        <div class="py-12 flex flex-col items-center justify-center text-center">
          <div class="w-16 h-16 bg-slate-100 dark:bg-surface-dark-solid rounded-full flex items-center justify-center mb-4">
            <i class="ri-calendar-check-line text-2xl text-slate-400"></i>
          </div>
          <h3 class="text-sm font-semibold text-ink dark:text-white mb-1">No shift templates found</h3>
          <p class="text-sm text-slate-500 max-w-sm">There are no shift templates matching your filters.</p>
        </div>
      </template>
    </DataTable>

    <!-- create / edit template (same modal pattern as the Tasks page popups) -->
    <BaseModal v-model="showModal" :title="editingId ? `Edit Shift Template #${editingId}` : 'Add Shift Template'"
      :icon="editingId ? 'ri-pencil-line' : 'ri-add-circle-line'" size="lg">
      <form @submit.prevent="submitForm" class="grid grid-cols-1 sm:grid-cols-2 gap-4">
        <div class="sm:col-span-2">
          <FormInput v-model="form.name" label="Template Name" placeholder="e.g., Morning Shift" icon="ri-calendar-check-line"
            required :error="form.errors.name" />
        </div>
        <FormDate v-model="form.start_time" label="Start Time" mode="time" floating
          required :error="form.errors.start_time" />
        <FormDate v-model="form.end_time" label="End Time" mode="time" floating
          required :error="form.errors.end_time" />
      </form>
      <template #footer>
        <BaseButton variant="light" @click="showModal = false" :disabled="form.processing">Cancel</BaseButton>
        <BaseButton variant="primary" icon="ri-save-line" :loading="form.processing" @click="submitForm">
          {{ editingId ? 'Save Changes' : 'Save Template' }}
        </BaseButton>
      </template>
    </BaseModal>

    <!-- delete confirm -->
    <BaseModal v-model="showDel" title="Confirm delete" icon="ri-error-warning-line" tone="danger" size="sm">
      <p class="text-sm text-slate-600 dark:text-slate-300">
        Are you sure you want to delete <span class="font-semibold text-ink dark:text-slate-100">{{ delTarget?.name }} (#{{ delTarget?.id }})</span>?
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
