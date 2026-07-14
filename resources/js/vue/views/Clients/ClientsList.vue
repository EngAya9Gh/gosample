<script setup>
import { ref, watch, onMounted, computed } from 'vue';
import { usePage, Link, router, useForm } from '@inertiajs/vue3';
import axios from 'axios';
import debounce from 'lodash/debounce';

import Breadcrumb from '../../components/Breadcrumb.vue';
import DataTable from '../../components/DataTable.vue';
import BaseAvatar from '../../components/BaseAvatar.vue';
import StatusBadge from '../../components/StatusBadge.vue';
import BaseButton from '../../components/BaseButton.vue';
import BaseModal from '../../components/BaseModal.vue';
import FormInput from '../../components/FormInput.vue';
import FormSelect from '../../components/FormSelect.vue';
import FilterBar from '../../components/FilterBar.vue';
import { usePermissions } from '../../composables/usePermissions';

const props = defineProps({
  initialRows: Array,
  initialTotal: Number,
  drivers: { type: Object, default: () => ({}) },
  locations: { type: Object, default: () => ({}) },
});

const { can } = usePermissions();

const DEFAULT_FILTERS = { keyword: '', sortBy: 'id', sortOrder: 'desc', status: '', location_id: '', driver_id: '' };
const searchForm = ref({ ...DEFAULT_FILTERS });

const rows = ref([]);
const total = ref(0);
const loading = ref(false);

const columns = [
  { key: 'logo',         label: 'Logo',          width: '60px' },
  { key: 'arabic_name',  label: 'Arabic Name',   sortable: true },
  { key: 'english_name', label: 'English Name',  sortable: true },
  { key: 'email',        label: 'Email',         sortable: true },
  { key: 'address',      label: 'Address' },
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
    if (searchForm.value.location_id) params.append('location_id', searchForm.value.location_id);
    if (searchForm.value.driver_id) params.append('driver_id', searchForm.value.driver_id);
    
    params.append('sortBy', searchForm.value.sortBy);
    params.append('sortOrder', searchForm.value.sortOrder);
    params.append('page', page);
    params.append('pageSize', pageSize);

    const { data } = await axios.get(`/admin/clients?${params.toString()}`, {
      headers: { Accept: 'application/json' },
    });
    
    rows.value = data.rows;
    total.value = data.total;
  } catch (error) {
    console.error('Error fetching clients:', error);
  } finally {
    loading.value = false;
  }
}, 300);

watch(
  () => [searchForm.value.keyword, searchForm.value.status, searchForm.value.location_id, searchForm.value.driver_id],
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
  { value: '1', label: 'Enabled', dot: 'bg-green-500', active: 'bg-green-500/10 border-green-500/40 text-green-600 dark:text-green-400' },
  { value: '2', label: 'Disabled', dot: 'bg-red-500', active: 'bg-red-500/10 border-red-500/40 text-red-600 dark:text-red-400' }
];

function toggleStatus(v) {
  searchForm.value.status = searchForm.value.status === String(v) ? '' : String(v);
}

const getStatusColor = (status) => {
  if (status == 1 || String(status) === '1' || status === 'Enabled') return 'emerald';
  if (status == 2 || String(status) === '2' || status === 'Disabled') return 'slate';
  return 'blue';
};

// Delete Modal
const deleteModalOpen = ref(false);
const clientToDelete = ref(null);

const confirmDelete = (client) => {
  clientToDelete.value = client;
  deleteModalOpen.value = true;
};

const executeDelete = () => {
  if (!clientToDelete.value) return;
  router.delete(`/admin/clients/${clientToDelete.value.id}`, {
    onSuccess: () => {
      deleteModalOpen.value = false;
      clientToDelete.value = null;
      doSearch();
    }
  });
};

// View Modal
const viewModalOpen = ref(false);
const selectedClient = ref(null);

const openViewModal = (client) => {
  selectedClient.value = client;
  viewModalOpen.value = true;
};
const closeViewModal = () => {
  viewModalOpen.value = false;
  setTimeout(() => { selectedClient.value = null; }, 300);
};

// Form Modal
const formModalOpen = ref(false);
const isEdit = ref(false);
const editingId = ref(null);

const driverOptions = computed(() => {
  return Object.entries(props.drivers || {})
    .filter(([value, label]) => value !== '')
    .map(([value, label]) => ({ value: String(value), label }));
});
const locationOptions = computed(() => {
  return Object.entries(props.locations || {})
    .filter(([value, label]) => value !== '')
    .map(([value, label]) => ({ value: String(value), label }));
});
const statusOptions = [
  { value: '1', label: 'Enabled' },
  { value: '2', label: 'Disabled' }
];

const form = useForm({
  _method: 'post',
  arabic_name: '',
  english_name: '',
  email: '',
  address: '',
  status: '1',
  logo: null,
  drivers: [],
  locations: [],
});

const logoPreview = ref(null);
const handleLogoChange = (e) => {
  const file = e.target.files[0];
  form.logo = file || null;
  if (file) {
    logoPreview.value = URL.createObjectURL(file);
  } else {
    logoPreview.value = selectedClient.value?.logo ? (selectedClient.value.logo.startsWith('/') ? selectedClient.value.logo : '/' + selectedClient.value.logo) : null;
  }
};

const openFormModal = async (client = null) => {
  form.clearErrors();
  if (client) {
    isEdit.value = true;
    editingId.value = client.id;
    selectedClient.value = client;
    
    // We need to fetch the client's drivers and locations since they aren't eager loaded in the index list
    try {
      const { data } = await axios.get(`/admin/clients/${client.id}/relations`);
      form.drivers = (data.drivers || []).map(String);
      form.locations = (data.locations || []).map(String);
    } catch (e) {
      // If the API isn't built yet, we'll gracefully fallback to empty
      form.drivers = [];
      form.locations = [];
    }

    form._method = 'put';
    form.arabic_name = client.arabic_name || '';
    form.english_name = client.english_name || '';
    form.email = client.email || '';
    form.address = client.address || '';
    form.status = String(client.status);
    form.logo = null;
    logoPreview.value = client.logo ? (client.logo.startsWith('/') ? client.logo : '/' + client.logo) : null;
  } else {
    isEdit.value = false;
    editingId.value = null;
    selectedClient.value = null;
    form.reset();
    form._method = 'post';
    form.status = '1';
    logoPreview.value = null;
  }
  formModalOpen.value = true;
};

const submitForm = () => {
  const url = isEdit.value ? `/admin/clients/${editingId.value}` : '/admin/clients';
  form.post(url, {
    onSuccess: () => {
      formModalOpen.value = false;
      doSearch();
    }
  });
};
</script>

<template>
  <div class="space-y-6">
    <div class="flex items-center justify-between mb-4">
      <Breadcrumb title="Clients" :trail="[{ label: 'Clients' }]">
        <template #actions>
          <BaseButton v-if="can('client_create')" @click="openFormModal()" variant="primary" icon="ri-add-line">Add Client</BaseButton>
        </template>
      </Breadcrumb>
    </div>

    <!-- Filter Bar -->
    <FilterBar :loading="loading" @search="doSearch(1, 25)" @reset="doReset">
      <FormInput v-model="searchForm.keyword" label="Keyword" placeholder="Name, Email, or Address..." icon="ri-search-line" />
      
      <FormSelect
        v-model="searchForm.driver_id"
        label="Driver"
        :options="driverOptions"
        placeholder="All Drivers"
      />
      <FormSelect
        v-model="searchForm.location_id"
        label="Location"
        :options="locationOptions"
        placeholder="All Locations"
      />

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
      <!-- Logo -->
      <template #cell-logo="{ row }">
        <BaseAvatar 
          :src="row.logo ? (row.logo.startsWith('/') ? row.logo : '/' + row.logo) : null" 
          :name="row.english_name || row.arabic_name" 
          :size="36" 
          rounded 
        />
      </template>

      <!-- Bold Names -->
      <template #cell-arabic_name="{ row }">
        <span class="font-bold text-ink dark:text-slate-100">{{ row.arabic_name }}</span>
      </template>
      <template #cell-english_name="{ row }">
        <span class="font-bold text-ink dark:text-slate-100">{{ row.english_name }}</span>
      </template>

      <!-- Status -->
      <template #cell-status="{ row }">
        <StatusBadge :status="row.status == 1 ? 'ENABLED' : 'DISABLED'" />
      </template>

      <!-- Actions -->
      <template #row-actions="{ row }">
        <div class="inline-flex items-center gap-1">
          <button v-if="can('client_show')" @click="openViewModal(row)" class="grid place-items-center w-8 h-8 rounded-lg text-info hover:bg-info/10 transition" title="View"><i class="ri-eye-line"></i></button>
          <button v-if="can('client_edit')" @click="openFormModal(row)" class="grid place-items-center w-8 h-8 rounded-lg text-primary-600 hover:bg-primary-50 dark:hover:bg-primary-500/10 transition" title="Edit"><i class="ri-pencil-line"></i></button>
          <button v-if="can('client_delete')" @click="confirmDelete(row)" class="grid place-items-center w-8 h-8 rounded-lg text-danger hover:bg-danger/10 transition" title="Delete"><i class="ri-delete-bin-line"></i></button>
        </div>
      </template>
    </DataTable>

    <!-- Delete Confirmation Modal -->
    <BaseModal v-model="deleteModalOpen" max-width="sm">
      <div class="p-6 text-center space-y-4">
        <div class="mx-auto flex items-center justify-center h-12 w-12 rounded-full bg-danger/10 mb-4">
          <i class="ri-alert-line text-2xl text-danger"></i>
        </div>
        <h3 class="text-xl font-semibold text-ink dark:text-white">Delete Client</h3>
        <p class="text-slate-500 dark:text-slate-400 text-sm">
          Are you sure you want to delete <span class="font-bold text-ink dark:text-white">{{ clientToDelete?.arabic_name }}</span>? 
          <br><br>
          <span class="text-xs opacity-75">(This is a soft delete, meaning the record remains in the database but will be hidden from the system).</span>
        </p>
        <div class="pt-4 flex items-center justify-center gap-3">
          <BaseButton @click="deleteModalOpen = false" variant="secondary">Cancel</BaseButton>
          <BaseButton @click="executeDelete" variant="danger">Yes, Delete it</BaseButton>
        </div>
      </div>
    </BaseModal>

    <!-- View Modal -->
    <BaseModal v-model="viewModalOpen" max-width="md">
      <div v-if="selectedClient" class="p-6">
        <div class="flex items-center justify-between mb-6">
          <h3 class="text-xl font-semibold text-ink dark:text-white">Client Details</h3>
          <button @click="closeViewModal" class="text-slate-400 hover:text-slate-600 dark:hover:text-slate-200">
            <i class="ri-close-line text-2xl"></i>
          </button>
        </div>
        
        <div class="flex flex-col items-center mb-6 text-center">
          <BaseAvatar 
            :src="selectedClient.logo ? (selectedClient.logo.startsWith('/') ? selectedClient.logo : '/' + selectedClient.logo) : null" 
            :text="selectedClient.english_name || selectedClient.arabic_name" 
            size="xl" 
            rounded 
            class="mb-3"
          />
          <h4 class="text-lg font-bold text-ink dark:text-white">{{ selectedClient.arabic_name }}</h4>
          <p class="text-slate-500">{{ selectedClient.english_name }}</p>
          <div class="mt-2">
            <StatusBadge :status="selectedClient.status == 1 ? 'ENABLED' : 'DISABLED'" />
          </div>
        </div>

        <div class="bg-slate-50 dark:bg-slate-800/50 rounded-xl p-4 space-y-3">
          <div class="flex justify-between border-b border-slate-200 dark:border-white/5 pb-3">
            <span class="text-sm text-slate-500">Email</span>
            <span class="text-sm font-medium text-ink dark:text-slate-200">{{ selectedClient.email || '—' }}</span>
          </div>
          <div class="flex justify-between">
            <span class="text-sm text-slate-500">Address</span>
            <span class="text-sm font-medium text-ink dark:text-slate-200 text-right max-w-[200px]">{{ selectedClient.address || '—' }}</span>
          </div>
        </div>
      </div>
    </BaseModal>

    <!-- Form Modal (Create / Edit) -->
    <BaseModal v-model="formModalOpen" max-width="xl">
      <div class="p-6 border-b border-slate-100 dark:border-white/5 flex items-center justify-between">
        <div>
          <h3 class="text-lg font-semibold text-ink dark:text-white">
            {{ isEdit ? 'Edit Client Details' : 'Create New Client' }}
          </h3>
          <p class="text-sm text-slate-500 mt-1">
            Fill in the information below to {{ isEdit ? 'update the' : 'create a' }} client profile.
          </p>
        </div>
        <button @click="formModalOpen = false" class="text-slate-400 hover:text-slate-600 dark:hover:text-slate-200">
          <i class="ri-close-line text-2xl"></i>
        </button>
      </div>

      <div class="p-6 space-y-6 max-h-[calc(100vh-200px)] overflow-y-auto">
        <!-- Logo Upload -->
        <div class="flex flex-col sm:flex-row gap-6 items-start">
          <div class="shrink-0">
            <div 
              class="w-24 h-24 rounded-2xl border border-dashed border-slate-300 dark:border-white/20 bg-slate-50 dark:bg-slate-800 flex items-center justify-center overflow-hidden relative group"
            >
              <img v-if="logoPreview" :src="logoPreview" class="w-full h-full object-cover" />
              <i v-else class="ri-image-add-line text-3xl text-slate-400"></i>
              
              <!-- Hover Overlay -->
              <label 
                class="absolute inset-0 bg-black/50 opacity-0 group-hover:opacity-100 transition flex items-center justify-center cursor-pointer text-white text-sm"
              >
                Upload
                <input type="file" class="hidden" accept="image/*" @change="handleLogoChange" />
              </label>
            </div>
          </div>
          <div class="flex-1 space-y-1 pt-2">
            <label class="block text-sm font-medium text-ink dark:text-slate-200">Company Logo</label>
            <p class="text-xs text-slate-500">Upload a square image, ideally 256x256px or larger. Max 2MB.</p>
            <div v-if="form.errors.logo" class="text-danger text-xs mt-1">{{ form.errors.logo }}</div>
          </div>
        </div>

        <hr class="border-slate-100 dark:border-white/5" />

        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
          <!-- Arabic Name -->
          <div>
            <label class="block text-sm font-medium text-ink dark:text-slate-200 mb-1">Arabic Name *</label>
            <input 
              v-model="form.arabic_name" 
              type="text" 
              required
              class="w-full h-11 px-3.5 rounded-xl border border-slate-300 dark:border-white/10 bg-white dark:bg-slate-900 text-ink dark:text-slate-200 outline-none transition focus:ring-2 focus:ring-primary-500/20 focus:border-primary-500"
              placeholder="الاسم العربي"
            />
            <div v-if="form.errors.arabic_name" class="text-danger text-xs mt-1">{{ form.errors.arabic_name }}</div>
          </div>

          <!-- English Name -->
          <div>
            <label class="block text-sm font-medium text-ink dark:text-slate-200 mb-1">English Name *</label>
            <input 
              v-model="form.english_name" 
              type="text" 
              required
              class="w-full h-11 px-3.5 rounded-xl border border-slate-300 dark:border-white/10 bg-white dark:bg-slate-900 text-ink dark:text-slate-200 outline-none transition focus:ring-2 focus:ring-primary-500/20 focus:border-primary-500"
              placeholder="English name"
            />
            <div v-if="form.errors.english_name" class="text-danger text-xs mt-1">{{ form.errors.english_name }}</div>
          </div>

          <!-- Email -->
          <div>
            <label class="block text-sm font-medium text-ink dark:text-slate-200 mb-1">Email</label>
            <input 
              v-model="form.email" 
              type="email" 
              class="w-full h-11 px-3.5 rounded-xl border border-slate-300 dark:border-white/10 bg-white dark:bg-slate-900 text-ink dark:text-slate-200 outline-none transition focus:ring-2 focus:ring-primary-500/20 focus:border-primary-500"
              placeholder="name@example.com"
            />
            <div v-if="form.errors.email" class="text-danger text-xs mt-1">{{ form.errors.email }}</div>
          </div>

          <!-- Status -->
          <FormSelect
            v-model="form.status"
            label="Status *"
            :options="statusOptions"
            :error="form.errors.status"
            required
            floating
          />

          <!-- Address -->
          <div class="md:col-span-2">
            <label class="block text-sm font-medium text-ink dark:text-slate-200 mb-1">Address</label>
            <input 
              v-model="form.address" 
              type="text" 
              class="w-full h-11 px-3.5 rounded-xl border border-slate-300 dark:border-white/10 bg-white dark:bg-slate-900 text-ink dark:text-slate-200 outline-none transition focus:ring-2 focus:ring-primary-500/20 focus:border-primary-500"
              placeholder="Street, city..."
            />
            <div v-if="form.errors.address" class="text-danger text-xs mt-1">{{ form.errors.address }}</div>
          </div>

          <!-- Drivers -->
          <div class="md:col-span-2">
            <FormSelect
              v-model="form.drivers"
              label="Assigned Drivers *"
              :options="driverOptions"
              :error="form.errors.drivers"
              placeholder="Select drivers"
              multiple
              searchable
              required
              floating
            />
          </div>

          <!-- Locations -->
          <div class="md:col-span-2">
            <FormSelect
              v-model="form.locations"
              label="Assigned Locations *"
              :options="locationOptions"
              :error="form.errors.locations"
              placeholder="Select locations"
              multiple
              searchable
              required
              floating
            />
          </div>

        </div>
      </div>

      <div class="px-6 py-4 bg-slate-50/50 dark:bg-slate-800/50 border-t border-slate-100 dark:border-white/5 flex justify-end gap-3 rounded-b-2xl">
        <BaseButton @click="formModalOpen = false" variant="secondary">Cancel</BaseButton>
        <BaseButton @click="submitForm" variant="primary" :loading="form.processing">
          {{ isEdit ? 'Save Changes' : 'Create Client' }}
        </BaseButton>
      </div>
    </BaseModal>
  </div>
</template>
