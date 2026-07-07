<script setup>
import { ref, watch, onMounted, computed } from 'vue';
import { usePage, Link, router, useForm } from '@inertiajs/vue3';
import axios from 'axios';
import debounce from 'lodash/debounce';

import Breadcrumb from '../../components/Breadcrumb.vue';
import DataTable from '../../components/DataTable.vue';
import StatusBadge from '../../components/StatusBadge.vue';
import BaseButton from '../../components/BaseButton.vue';
import BaseModal from '../../components/BaseModal.vue';
import FormInput from '../../components/FormInput.vue';
import FormSelect from '../../components/FormSelect.vue';
import FilterBar from '../../components/FilterBar.vue';
import LeafletMapPicker from '../../components/LeafletMapPicker.vue';
import { usePermissions } from '../../composables/usePermissions';

const props = defineProps({
  initialRows: Array,
  initialTotal: Number,
  saudiCities: { type: Object, default: () => ({}) },
});

const { can } = usePermissions();

const DEFAULT_FILTERS = { keyword: '', sortBy: 'id', sortOrder: 'desc', status: '', city: '' };
const searchForm = ref({ ...DEFAULT_FILTERS });

const rows = ref([]);
const total = ref(0);
const loading = ref(false);

const columns = [
  { key: 'name',         label: 'English Name',  sortable: true },
  { key: 'arabic_name',  label: 'Arabic Name',   sortable: true },
  { key: 'city',         label: 'City',          sortable: true },
  { key: 'neighborhood', label: 'Neighborhood' },
  { key: 'mobile',       label: 'Mobile' },
  { key: 'description',  label: 'Description' },
  { key: 'coordinates',  label: 'Coordinates',   width: '140px' },
  { key: 'status',       label: 'Status' },
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
    if (searchForm.value.status !== '') params.append('status', searchForm.value.status);
    if (searchForm.value.city) params.append('city', searchForm.value.city);
    
    params.append('sortBy', searchForm.value.sortBy);
    params.append('sortOrder', searchForm.value.sortOrder);
    params.append('page', page);
    params.append('pageSize', pageSize);

    const { data } = await axios.get(`/app/admin/locations?${params.toString()}`, {
      headers: { Accept: 'application/json' },
    });
    
    rows.value = data.rows;
    total.value = data.total;
  } catch (error) {
    console.error('Error fetching locations:', error);
  } finally {
    loading.value = false;
  }
}, 300);

watch(
  () => [searchForm.value.keyword, searchForm.value.status, searchForm.value.city],
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

function doReset() {
  searchForm.value = { ...DEFAULT_FILTERS };
  doSearch(1, 25);
}

const statusPills = [
  { value: '1', label: 'Active', dot: 'bg-green-500', active: 'bg-green-500/10 border-green-500/40 text-green-600 dark:text-green-400' },
  { value: '0', label: 'Inactive', dot: 'bg-red-500', active: 'bg-red-500/10 border-red-500/40 text-red-600 dark:text-red-400' }
];

function toggleStatus(v) {
  searchForm.value.status = searchForm.value.status === String(v) ? '' : String(v);
}

// Delete Modal
const deleteModalOpen = ref(false);
const itemToDelete = ref(null);

const confirmDelete = (item) => {
  itemToDelete.value = item;
  deleteModalOpen.value = true;
};

const executeDelete = () => {
  if (!itemToDelete.value) return;
  router.delete(`/app/admin/locations/${itemToDelete.value.id}`, {
    onSuccess: () => {
      deleteModalOpen.value = false;
      itemToDelete.value = null;
      doSearch();
    }
  });
};

// View Modal
const viewModalOpen = ref(false);
const selectedItem = ref(null);

const openViewModal = (item) => {
  selectedItem.value = item;
  viewModalOpen.value = true;
};
const closeViewModal = () => {
  viewModalOpen.value = false;
  setTimeout(() => { selectedItem.value = null; }, 300);
};

// Form Modal
const formModalOpen = ref(false);
const isEdit = ref(false);
const editingId = ref(null);

const cityOptions = computed(() => {
  return Object.entries(props.saudiCities || {})
    .map(([key, labels]) => ({ value: key, label: `${labels.en} — ${labels.ar}` }));
});

const statusOptions = [
  { value: '1', label: 'Active' },
  { value: '0', label: 'Inactive' }
];

const form = useForm({
  _method: 'post',
  name: '',
  arabic_name: '',
  city: '',
  neighborhood: '',
  mobile: '',
  lat: '',
  lng: '',
  pickup_waiting_time: '',
  drop_off_waiting_time: '',
  description: '',
  status: '1',
});

const openFormModal = (item = null) => {
  form.clearErrors();
  if (item) {
    isEdit.value = true;
    editingId.value = item.id;
    selectedItem.value = item;

    form._method = 'put';
    form.name = item.name || '';
    form.arabic_name = item.arabic_name || '';
    form.city = item.city || '';
    form.neighborhood = item.neighborhood || '';
    form.mobile = item.mobile || '';
    form.lat = item.lat || '';
    form.lng = item.lng || '';
    form.pickup_waiting_time = item.pickup_waiting_time || '';
    form.drop_off_waiting_time = item.drop_off_waiting_time || '';
    form.description = item.description || '';
    form.status = String(item.status);
  } else {
    isEdit.value = false;
    editingId.value = null;
    selectedItem.value = null;
    form.reset();
    form._method = 'post';
    form.status = '1';
  }
  formModalOpen.value = true;
};

const submitForm = () => {
  // Ensure coordinates are strings as required by Laravel validation
  if (form.lat !== null && form.lat !== '') form.lat = String(form.lat);
  if (form.lng !== null && form.lng !== '') form.lng = String(form.lng);

  const url = isEdit.value ? `/app/admin/locations/${editingId.value}` : '/app/admin/locations';
  form.post(url, {
    onSuccess: () => {
      formModalOpen.value = false;
      doSearch();
    }
  });
};

const copyToClipboard = async (text) => {
  try {
    await navigator.clipboard.writeText(text);
    // Optional: show a small toast here if available
  } catch (err) {
    console.error('Failed to copy text: ', err);
  }
};
</script>

<template>
  <div class="space-y-6">
    <div class="flex items-center justify-between mb-4">
      <Breadcrumb title="Locations" :trail="[{ label: 'Locations' }]">
        <template #actions>
          <BaseButton v-if="can('location_create')" @click="openFormModal()" variant="primary" icon="ri-add-line">Add Location</BaseButton>
        </template>
      </Breadcrumb>
    </div>

    <!-- Filter Bar -->
    <FilterBar :loading="loading" @search="doSearch(1, 25)" @reset="doReset">
      <FormInput v-model="searchForm.keyword" label="Keyword" placeholder="Name, City, or Neighborhood..." icon="ri-search-line" />
      
      <FormSelect
        v-model="searchForm.city"
        label="City"
        :options="cityOptions"
        placeholder="All Cities"
        icon="ri-building-2-line"
      />

      <!-- Extra Actions: Status Pills -->
      <template #actions-extra>
        <div class="flex items-center gap-2 border-s border-slate-200 dark:border-white/10 ps-4">
          <button
            v-for="p in statusPills" :key="p.value"
            type="button"
            @click="toggleStatus(p.value)"
            class="h-10 px-3.5 inline-flex items-center gap-2 rounded-xl text-sm font-medium transition-colors border bg-surface dark:bg-surface-dark"
            :class="searchForm.status === p.value ? p.active : 'border-slate-200 dark:border-white/10 text-slate-600 dark:text-slate-300 hover:bg-slate-50 dark:hover:bg-white/5'"
          >
            <span class="w-2 h-2 rounded-full" :class="p.dot"></span>
            {{ p.label }}
          </button>
        </div>
      </template>
    </FilterBar>

    <!-- Data Table -->
    <DataTable
      :rows="rows"
      :columns="columns"
      row-key="id"
      :total="total"
      :loading="loading"
      :sort-by="searchForm.sortBy"
      :sort-order="searchForm.sortOrder"
      @query="onQuery"
    >
      <!-- Bold Names -->
      <template #cell-name="{ row }">
        <span class="font-bold text-ink dark:text-slate-100 whitespace-nowrap">{{ row.name }}</span>
      </template>
      <template #cell-arabic_name="{ row }">
        <span class="font-bold text-ink dark:text-slate-100 whitespace-nowrap">{{ row.arabic_name }}</span>
      </template>

      <!-- City Format -->
      <template #cell-city="{ row }">
        <span class="whitespace-nowrap">
          {{ saudiCities[row.city] ? `${saudiCities[row.city].en} — ${saudiCities[row.city].ar}` : (row.city || '—') }}
        </span>
      </template>
      
      <!-- Neighborhood and Mobile -->
      <template #cell-neighborhood="{ row }">
        <span class="whitespace-nowrap">{{ row.neighborhood || '—' }}</span>
      </template>
      <template #cell-mobile="{ row }">
        <span class="whitespace-nowrap">{{ row.mobile || '—' }}</span>
      </template>

      <!-- Description Format -->
      <template #cell-description="{ row }">
        <div class="max-w-[200px] truncate text-slate-500" :title="row.description">
          {{ row.description || '—' }}
        </div>
      </template>

      <!-- Coordinates Format -->
      <template #cell-coordinates="{ row }">
        <div class="flex items-center gap-2">
          <span class="text-xs font-mono text-slate-500 truncate w-32" :title="`${row.lat}, ${row.lng}`">
            {{ row.lat ? `${row.lat}, ${row.lng}` : '—' }}
          </span>
          <button v-if="row.lat" @click="copyToClipboard(`https://www.google.com/maps/place/${row.lat},${row.lng}`)" class="text-primary-500 hover:text-primary-700 transition" title="Copy Google Maps Link">
            <i class="ri-map-pin-line text-lg"></i>
          </button>
        </div>
      </template>

      <!-- Status Badge -->
      <template #cell-status="{ row }">
        <StatusBadge :status="row.status == 1 ? 'ENABLED' : 'DISABLED'" />
      </template>

      <!-- Row Actions -->
      <template #row-actions="{ row }">
        <div class="inline-flex items-center gap-1 whitespace-nowrap">
          <button
            v-if="can('location_show')"
            @click="openViewModal(row)"
            class="w-8 h-8 inline-flex items-center justify-center rounded-lg text-info hover:bg-info/10 transition-colors"
            title="View Details"
          >
            <i class="ri-eye-line text-lg"></i>
          </button>
          <button
            v-if="can('location_edit')"
            @click="openFormModal(row)"
            class="w-8 h-8 inline-flex items-center justify-center rounded-lg text-primary-600 hover:bg-primary-50 dark:hover:bg-primary-500/10 transition-colors"
            title="Edit"
          >
            <i class="ri-pencil-line text-lg"></i>
          </button>
          <button
            v-if="can('location_delete')"
            @click="confirmDelete(row)"
            class="w-8 h-8 inline-flex items-center justify-center rounded-lg text-danger hover:bg-danger/10 transition-colors"
            title="Delete"
          >
            <i class="ri-delete-bin-line text-lg"></i>
          </button>
        </div>
      </template>
    </DataTable>

    <!-- Delete Confirmation Modal -->
    <BaseModal v-model="deleteModalOpen" max-width="sm">
      <div class="p-6 text-center">
        <div class="w-16 h-16 rounded-full bg-danger/10 text-danger flex items-center justify-center mx-auto mb-4">
          <i class="ri-error-warning-line text-3xl"></i>
        </div>
        <h3 class="text-xl font-bold text-ink dark:text-white mb-2">Delete Location?</h3>
        <p class="text-slate-500 mb-6">Are you sure you want to delete "{{ itemToDelete?.name }}"? This action cannot be undone.</p>
        <div class="flex gap-3 justify-center">
          <BaseButton variant="white" @click="deleteModalOpen = false">Cancel</BaseButton>
          <BaseButton variant="danger" @click="executeDelete">Yes, Delete</BaseButton>
        </div>
      </div>
    </BaseModal>

    <!-- View Modal -->
    <BaseModal v-model="viewModalOpen" max-width="md" @close="closeViewModal">
      <div v-if="selectedItem">
        <div class="p-6 border-b border-slate-100 dark:border-white/5 flex items-center gap-4">
          <div class="w-16 h-16 rounded-2xl bg-primary-50 text-primary-600 dark:bg-primary-500/20 flex items-center justify-center shrink-0">
            <i class="ri-map-pin-2-fill text-3xl"></i>
          </div>
          <div>
            <h4 class="text-lg font-bold text-ink dark:text-white">{{ selectedItem.arabic_name || selectedItem.name }}</h4>
            <p class="text-slate-500">{{ selectedItem.name }}</p>
            <div class="mt-2">
              <StatusBadge :status="selectedItem.status == 1 ? 'ENABLED' : 'DISABLED'" />
            </div>
          </div>
        </div>

        <div class="p-6 bg-slate-50 dark:bg-slate-800/50 space-y-3">
          <div class="flex justify-between border-b border-slate-200 dark:border-white/5 pb-3">
            <span class="text-sm text-slate-500">City</span>
            <span class="text-sm font-medium text-ink dark:text-slate-200">
              {{ saudiCities[selectedItem.city] ? `${saudiCities[selectedItem.city].en} — ${saudiCities[selectedItem.city].ar}` : (selectedItem.city || '—') }}
            </span>
          </div>
          <div class="flex justify-between border-b border-slate-200 dark:border-white/5 pb-3">
            <span class="text-sm text-slate-500">Neighborhood</span>
            <span class="text-sm font-medium text-ink dark:text-slate-200">{{ selectedItem.neighborhood || '—' }}</span>
          </div>
          <div class="flex justify-between border-b border-slate-200 dark:border-white/5 pb-3">
            <span class="text-sm text-slate-500">Mobile</span>
            <span class="text-sm font-medium text-ink dark:text-slate-200">{{ selectedItem.mobile || '—' }}</span>
          </div>
          <div class="flex justify-between border-b border-slate-200 dark:border-white/5 pb-3">
            <span class="text-sm text-slate-500">Coordinates</span>
            <span class="text-sm font-mono text-ink dark:text-slate-200 flex items-center gap-2">
              {{ selectedItem.lat ? `${selectedItem.lat}, ${selectedItem.lng}` : '—' }}
              <button v-if="selectedItem.lat" @click="copyToClipboard(`https://www.google.com/maps/place/${selectedItem.lat},${selectedItem.lng}`)" class="text-primary-500 hover:text-primary-700" title="Copy Google Maps Link">
                <i class="ri-links-line"></i>
              </button>
            </span>
          </div>
          <div class="flex justify-between border-b border-slate-200 dark:border-white/5 pb-3">
            <span class="text-sm text-slate-500">Pickup Waiting Time</span>
            <span class="text-sm font-medium text-ink dark:text-slate-200">{{ selectedItem.pickup_waiting_time || '—' }} min</span>
          </div>
          <div class="flex justify-between">
            <span class="text-sm text-slate-500">Drop Off Waiting Time</span>
            <span class="text-sm font-medium text-ink dark:text-slate-200">{{ selectedItem.drop_off_waiting_time || '—' }} min</span>
          </div>
          <div v-if="selectedItem.description" class="pt-3 border-t border-slate-200 dark:border-white/5">
            <span class="block text-sm text-slate-500 mb-1">Description</span>
            <p class="text-sm text-ink dark:text-slate-200 leading-relaxed">{{ selectedItem.description }}</p>
          </div>
        </div>
      </div>
    </BaseModal>

    <!-- Form Modal (Create / Edit) -->
    <BaseModal v-model="formModalOpen" max-width="2xl">
      <div class="p-6 border-b border-slate-100 dark:border-white/5 flex items-center justify-between">
        <div>
          <h3 class="text-lg font-semibold text-ink dark:text-white">
            {{ isEdit ? 'Edit Location' : 'Create New Location' }}
          </h3>
          <p class="text-sm text-slate-500 mt-1">
            {{ isEdit ? 'Update the details for this location.' : 'Fill in the details to add a new location.' }}
          </p>
        </div>
        <button @click="formModalOpen = false" class="w-8 h-8 flex items-center justify-center rounded-full hover:bg-slate-100 dark:hover:bg-white/10 text-slate-500 transition-colors">
          <i class="ri-close-line text-lg"></i>
        </button>
      </div>

      <form @submit.prevent="submitForm" class="p-6">
        <div class="grid grid-cols-1 md:grid-cols-2 gap-5 mb-6">
          <FormInput v-model="form.name" label="English Name" required :error="form.errors.name" />
          <FormInput v-model="form.arabic_name" label="Arabic Name" :error="form.errors.arabic_name" />
          
          <FormSelect v-model="form.city" :options="cityOptions" label="City" required :error="form.errors.city" />
          <FormInput v-model="form.neighborhood" label="Neighborhood" :error="form.errors.neighborhood" />
          
          <FormInput v-model="form.mobile" label="Mobile" :error="form.errors.mobile" />
          <FormSelect v-model="form.status" :options="statusOptions" label="Status" required :error="form.errors.status" />

          <FormInput v-model="form.lat" label="Latitude" :error="form.errors.lat" />
          <FormInput v-model="form.lng" label="Longitude" :error="form.errors.lng" />

          <FormInput v-model="form.pickup_waiting_time" type="number" label="Pickup Waiting Time (min)" :error="form.errors.pickup_waiting_time" />
          <FormInput v-model="form.drop_off_waiting_time" type="number" label="Drop Off Waiting Time (min)" :error="form.errors.drop_off_waiting_time" />
        </div>
        
        <div class="mb-6">
          <label class="block text-[13px] font-bold text-slate-800 dark:text-slate-200 mb-1.5">Map Location</label>
          <LeafletMapPicker v-model:lat="form.lat" v-model:lng="form.lng" />
          <p class="mt-1.5 text-[11px] text-slate-500">Click on the map or search to pin the location automatically. The Latitude and Longitude fields will be updated.</p>
        </div>

        <div class="mb-6">
          <FormInput 
            v-model="form.description" 
            type="textarea" 
            label="Description" 
            placeholder="Enter additional details..." 
            :rows="3"
            required
            :error="form.errors.description" 
          />
        </div>

        <div class="flex items-center justify-end gap-3 pt-6 border-t border-slate-100 dark:border-white/5">
          <BaseButton type="button" variant="white" @click="formModalOpen = false">Cancel</BaseButton>
          <BaseButton type="submit" variant="primary" :loading="form.processing">
            {{ isEdit ? 'Save Changes' : 'Create Location' }}
          </BaseButton>
        </div>
      </form>
    </BaseModal>
  </div>
</template>
