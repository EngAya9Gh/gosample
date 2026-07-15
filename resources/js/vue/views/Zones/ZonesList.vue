<script setup>
/**
 * views/Zones/ZonesList.vue — SPA rebuild of /admin/zones.
 * Logic mirrors the classic pages 1:1: list (ID / Name), create & edit =
 * zone name + polygon drawn on a map (classic uses Google Maps DrawingManager;
 * here Leaflet — already the SPA's map library — with click-to-add vertices),
 * view = read-only polygon, delete/mass-delete via the classic /admin routes
 * (can-delete gate). Design follows the Tasks page: Breadcrumb + FilterBar +
 * DataTable + popups + the standard action buttons.
 */
import { ref, onMounted, onBeforeUnmount, nextTick } from 'vue';
import axios from 'axios';
import { useForm } from '@inertiajs/vue3';
import debounce from 'lodash/debounce';
import 'leaflet/dist/leaflet.css';
import L from 'leaflet';

import Breadcrumb from '../../components/Breadcrumb.vue';
import FilterBar from '../../components/FilterBar.vue';
import DataTable from '../../components/DataTable.vue';
import FormInput from '../../components/FormInput.vue';
import BaseButton from '../../components/BaseButton.vue';
import BaseModal from '../../components/BaseModal.vue';
import { useToast } from '../../composables/useToast';
import { usePermissions } from '../../composables/usePermissions';

const props = defineProps({
  initialRows:  { type: Array,  default: () => [] },
  initialTotal: { type: Number, default: 0 },
});

const { push } = useToast();
const { can, canDelete } = usePermissions();
const csrf = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';

// Riyadh — same default center/zoom as the classic zone pages.
const MAP_CENTER = [24.7156901, 46.6439257];
const MAP_ZOOM = 11;
const TILES = 'https://{s}.basemaps.cartocdn.com/rastertiles/voyager/{z}/{x}/{y}{r}.png';
const TILES_ATTR = '&copy; OpenStreetMap &copy; CARTO';

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
    const { data } = await axios.get(`/admin/zones?${params.toString()}`, {
      headers: { Accept: 'application/json' },
    });
    rows.value = data.rows;
    total.value = data.total;
  } catch (e) {
    push({ type: 'error', title: 'Error', message: 'Failed to load zones.' });
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

const columns = [
  { key: 'sequence',   label: '#',          sticky: 'start', width: '52px' },
  { key: 'id',         label: 'ID',         sticky: 'start', width: '80px', sortable: true },
  { key: 'name',       label: 'Name',       sortable: true },
  { key: 'points',     label: 'Area' },
  { key: 'created_at', label: 'Created At', sortable: true },
];

/* ---------- Leaflet editor (create/edit modal) ----------
 * Classic pages draw the polygon with Google's DrawingManager; the SPA uses
 * Leaflet with click-to-add vertices — same output: an ordered {lat,lng} list. */
let editMap = null;
let editLayer = null;
const editMapEl = ref(null);
const points = ref([]); // [{lat,lng}] — open ring; backend closes it

function redrawEditor() {
  if (!editMap) return;
  if (editLayer) { editLayer.remove(); editLayer = null; }
  const latlngs = points.value.map((p) => [p.lat, p.lng]);
  editLayer = L.layerGroup();
  latlngs.forEach((ll) => L.circleMarker(ll, { radius: 4, color: '#005D69', fillColor: '#0ab39c', fillOpacity: 1, weight: 2 }).addTo(editLayer));
  if (latlngs.length >= 2) L.polyline(latlngs, { color: '#005D69', weight: 2, dashArray: '4 4' }).addTo(editLayer);
  if (latlngs.length >= 3) L.polygon(latlngs, { color: '#005D69', weight: 2, fillColor: '#0ab39c', fillOpacity: 0.25 }).addTo(editLayer);
  editLayer.addTo(editMap);
}
function undoPoint() { points.value.pop(); redrawEditor(); }
function clearPoints() { points.value = []; redrawEditor(); }

function mountEditor() {
  nextTick(() => setTimeout(() => {
    if (!editMapEl.value) return;
    editMap = L.map(editMapEl.value).setView(MAP_CENTER, MAP_ZOOM);
    L.tileLayer(TILES, { attribution: TILES_ATTR }).addTo(editMap);
    editMap.on('click', (e) => {
      points.value.push({ lat: +e.latlng.lat.toFixed(6), lng: +e.latlng.lng.toFixed(6) });
      redrawEditor();
    });
    redrawEditor();
    if (points.value.length >= 3) {
      editMap.fitBounds(L.polygon(points.value.map((p) => [p.lat, p.lng])).getBounds(), { padding: [24, 24] });
    }
    editMap.invalidateSize();
  }, 150));
}
function destroyEditor() {
  if (editMap) { editMap.remove(); editMap = null; editLayer = null; }
}
onBeforeUnmount(() => { destroyEditor(); destroyViewer(); });

/* ---------- create / edit modal ---------- */
const showModal = ref(false);
const editingId = ref(null);
const form = useForm({ name: '', area: [] });

function openCreate() {
  if (!can('zone_create')) return;
  destroyEditor();
  editingId.value = null;
  form.reset();
  form.clearErrors();
  points.value = [];
  showModal.value = true;
  mountEditor();
}
function openEdit(row) {
  if (!can('zone_edit')) return;
  destroyEditor();
  editingId.value = row.id;
  form.clearErrors();
  form.name = row.name ?? '';
  // drop the duplicated closing vertex so editing starts from the open ring
  let pts = [...(row.points || [])];
  if (pts.length > 1 && pts[0].lat === pts[pts.length - 1].lat && pts[0].lng === pts[pts.length - 1].lng) pts.pop();
  points.value = pts;
  showModal.value = true;
  mountEditor();
}
function closeModal() { showModal.value = false; destroyEditor(); }

function submitForm() {
  form.area = points.value;
  const opts = {
    preserveScroll: true,
    onSuccess: () => {
      closeModal();
      push({ type: 'success', title: editingId.value ? 'Updated' : 'Created',
             message: editingId.value ? `Zone #${editingId.value} updated.` : 'Zone created successfully.' });
      form.reset();
      doSearch();
    },
  };
  if (editingId.value) form.put(`/admin/zones/${editingId.value}/popup`, opts);
  else form.post('/admin/zones/popup', opts);
}

/* ---------- view modal (read-only polygon, like the classic show page) ---------- */
let viewMap = null;
const viewMapEl = ref(null);
const showView = ref(false);
const viewTarget = ref(null);

function destroyViewer() { if (viewMap) { viewMap.remove(); viewMap = null; } }
function openView(row) {
  if (!can('zone_show')) return;
  destroyViewer();
  viewTarget.value = row;
  showView.value = true;
  nextTick(() => setTimeout(() => {
    if (!viewMapEl.value) return;
    viewMap = L.map(viewMapEl.value, { scrollWheelZoom: false }).setView(MAP_CENTER, MAP_ZOOM);
    L.tileLayer(TILES, { attribution: TILES_ATTR }).addTo(viewMap);
    const pts = (row.points || []).map((p) => [p.lat, p.lng]);
    if (pts.length >= 3) {
      const poly = L.polygon(pts, { color: '#005D69', weight: 2, fillColor: '#0ab39c', fillOpacity: 0.3 }).addTo(viewMap);
      viewMap.fitBounds(poly.getBounds(), { padding: [24, 24] });
    }
    viewMap.invalidateSize();
  }, 150));
}
function closeView() { showView.value = false; destroyViewer(); }

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
    const res = await webDelete('/admin/zones/' + delTarget.value.id);
    if (res.status === 403) push({ type: 'error', title: 'Forbidden', message: 'You are not allowed to delete.' });
    else { push({ type: 'success', title: 'Deleted', message: `Zone #${delTarget.value.id} removed` }); doSearch(); }
  } catch (e) { push({ type: 'error', title: 'Error', message: 'Delete failed.' }); }
  showDel.value = false;
}
async function bulkDelete(ids) {
  try {
    const res = await webDelete('/admin/zones/destroy', ids);
    if (res.status === 403) push({ type: 'error', title: 'Forbidden', message: 'You are not allowed to delete.' });
    else { push({ type: 'success', title: 'Bulk delete', message: `${ids.length} zones removed` }); doSearch(); }
  } catch (e) { push({ type: 'error', title: 'Error', message: 'Bulk delete failed.' }); }
}
</script>

<template>
  <div>
    <Breadcrumb title="Zones" :trail="[{ label: 'Zones' }]">
      <template #actions>
        <BaseButton v-if="can('zone_create')" variant="primary" icon="ri-add-line" @click="openCreate">Add Zone</BaseButton>
      </template>
    </Breadcrumb>

    <!-- filter bar (Tasks page design) -->
    <FilterBar :loading="loading" subtitle="refine the zone list" @search="doApply" @reset="doReset">
      <FormInput v-model="searchForm.keyword" label="Keyword" placeholder="Zone ID or name…" icon="ri-search-line" />
    </FilterBar>

    <!-- data table (server-side) -->
    <DataTable
      title="Zones"
      :columns="columns" :rows="rows" row-key="id"
      :loading="loading" :server-side="true" :total="total" :searchable="false"
      :bulk-actions="canDelete() ? [{ label: 'Delete', icon: 'ri-delete-bin-line', tone: 'danger', event: 'bulk-delete' }] : []"
      @query="onQuery" @bulk-delete="bulkDelete"
    >
      <template #cell-id="{ value }">
        <span class="font-black text-[#0ab39c] dark:text-[#0ab39c]">#{{ value }}</span>
      </template>
      <template #cell-name="{ value }">
        <span class="font-extrabold text-slate-800 dark:text-white">{{ value || '—' }}</span>
      </template>
      <template #cell-points="{ value }">
        <span v-if="value?.length" class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-md text-[11.5px] font-bold border bg-primary-50 text-primary-700 border-primary-100 dark:bg-primary-500/10 dark:text-primary-300 dark:border-primary-500/20">
          <i class="ri-shape-2-line"></i>{{ value.length }} points
        </span>
        <span v-else class="text-slate-400">—</span>
      </template>

      <template #row-actions="{ row }">
        <div class="inline-flex items-center gap-1">
          <button v-if="can('zone_show')" @click="openView(row)" class="grid place-items-center w-8 h-8 rounded-lg text-info hover:bg-info/10 transition" title="View"><i class="ri-eye-line"></i></button>
          <button v-if="can('zone_edit')" @click="openEdit(row)" class="grid place-items-center w-8 h-8 rounded-lg text-primary-600 hover:bg-primary-50 dark:hover:bg-primary-500/10 transition" title="Edit"><i class="ri-pencil-line"></i></button>
          <button v-if="canDelete()" @click="askDelete(row)" class="grid place-items-center w-8 h-8 rounded-lg text-danger hover:bg-danger/10 transition" title="Delete"><i class="ri-delete-bin-line"></i></button>
        </div>
      </template>

      <template #empty>
        <div class="py-12 flex flex-col items-center justify-center text-center">
          <div class="w-16 h-16 bg-slate-100 dark:bg-surface-dark-solid rounded-full flex items-center justify-center mb-4">
            <i class="ri-shape-2-line text-2xl text-slate-400"></i>
          </div>
          <h3 class="text-sm font-semibold text-ink dark:text-white mb-1">No zones found</h3>
          <p class="text-sm text-slate-500 max-w-sm">There are no zones matching your filters.</p>
        </div>
      </template>
    </DataTable>

    <!-- create / edit zone (same modal pattern as the Tasks page popups) -->
    <BaseModal :model-value="showModal" @update:model-value="closeModal"
      :title="editingId ? `Edit Zone #${editingId}` : 'Create Zone'"
      :icon="editingId ? 'ri-pencil-line' : 'ri-add-circle-line'" size="xl">
      <form @submit.prevent="submitForm" class="space-y-4">
        <FormInput v-model="form.name" label="Name" placeholder="Zone name" icon="ri-shape-2-line"
          required :error="form.errors.name" />

        <div>
          <div class="flex items-center justify-between mb-1.5">
            <label class="block text-[13px] font-bold text-slate-800 dark:text-slate-200">
              Area <span class="text-danger">*</span>
              <span class="ms-2 text-[11px] font-medium text-slate-400">click the map to add points ({{ points.length }} added)</span>
            </label>
            <div class="flex items-center gap-1.5">
              <button type="button" @click="undoPoint" :disabled="!points.length"
                class="h-7 px-2.5 rounded-lg text-[11.5px] font-bold border border-slate-200 dark:border-white/10 text-slate-500 dark:text-slate-400 hover:border-slate-300 disabled:opacity-40 transition">
                <i class="ri-arrow-go-back-line"></i> Undo
              </button>
              <button type="button" @click="clearPoints" :disabled="!points.length"
                class="h-7 px-2.5 rounded-lg text-[11.5px] font-bold border border-danger/30 text-danger hover:bg-danger/5 disabled:opacity-40 transition">
                <i class="ri-eraser-line"></i> Clear
              </button>
            </div>
          </div>
          <div ref="editMapEl" class="h-[380px] rounded-xl overflow-hidden border z-0"
            :class="form.errors.area ? 'border-danger/60' : 'border-slate-200 dark:border-white/10'"></div>
          <p v-if="form.errors.area" class="flex items-center gap-1.5 text-danger text-sm mt-2">
            <i class="ri-error-warning-line text-[15px] shrink-0"></i>{{ form.errors.area }}
          </p>
        </div>
      </form>
      <template #footer>
        <BaseButton variant="light" @click="closeModal" :disabled="form.processing">Cancel</BaseButton>
        <BaseButton variant="primary" icon="ri-save-line" :loading="form.processing" @click="submitForm">
          {{ editingId ? 'Save Changes' : 'Save Zone' }}
        </BaseButton>
      </template>
    </BaseModal>

    <!-- view zone (read-only polygon, like the classic show page) -->
    <BaseModal :model-value="showView" @update:model-value="closeView"
      :title="`Zone #${viewTarget?.id} — ${viewTarget?.name || ''}`" icon="ri-eye-line" size="xl">
      <div ref="viewMapEl" class="h-[420px] rounded-xl overflow-hidden border border-slate-200 dark:border-white/10 z-0"></div>
      <template #footer>
        <BaseButton variant="light" @click="closeView">Close</BaseButton>
        <BaseButton v-if="can('zone_edit')" variant="primary" icon="ri-pencil-line"
          @click="closeView(); openEdit(viewTarget)">Edit Zone</BaseButton>
      </template>
    </BaseModal>

    <!-- delete confirm -->
    <BaseModal v-model="showDel" title="Confirm delete" icon="ri-error-warning-line" tone="danger" size="sm">
      <p class="text-sm text-slate-600 dark:text-slate-300">
        Are you sure you want to delete <span class="font-semibold text-ink dark:text-slate-100">Zone #{{ delTarget?.id }} — {{ delTarget?.name }}</span>?
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
