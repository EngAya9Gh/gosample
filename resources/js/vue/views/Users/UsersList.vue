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
  roles: Object,
  clients: Array,
});

const { can } = usePermissions();

const DEFAULT_FILTERS = { keyword: '', sortBy: 'id', sortOrder: 'desc', role: '', client: '' };
const searchForm = ref({ ...DEFAULT_FILTERS });

const rows = ref([]);
const total = ref(0);
const loading = ref(false);

const columns = [
  { key: 'name',              label: 'Name',           sortable: true },
  { key: 'email',             label: 'Email',          sortable: true },
  { key: 'email_verified_at', label: 'Verified At',    sortable: true },
  { key: 'roles',             label: 'Roles' },
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
    if (searchForm.value.role) params.append('role', searchForm.value.role);
    if (searchForm.value.client) params.append('client', searchForm.value.client);
    
    params.append('sortBy', searchForm.value.sortBy);
    params.append('sortOrder', searchForm.value.sortOrder);
    params.append('page', page);
    params.append('pageSize', pageSize);

    const { data } = await axios.get('/admin/users', { params });
    rows.value = data.rows;
    total.value = data.total;
  } catch (err) {
    console.error('Error fetching users:', err);
  } finally {
    loading.value = false;
  }
}, 300);

const doReset = () => {
  searchForm.value = { ...DEFAULT_FILTERS };
  doSearch();
};

onMounted(() => {
  rows.value = props.initialRows || [];
  total.value = props.initialTotal || 0;
});

// Options Map
const roleOptions = computed(() => {
  return Object.entries(props.roles || {}).map(([id, name]) => ({
    value: String(id),
    label: name,
  }));
});

const clientOptions = computed(() => {
  return (props.clients || []).map((c) => ({
    value: String(c.id),
    label: c.english_name || c.arabic_name,
  }));
});

// Delete Modal
const deleteModalOpen = ref(false);
const itemToDelete = ref(null);

const confirmDelete = (item) => {
  itemToDelete.value = item;
  deleteModalOpen.value = true;
};

const executeDelete = () => {
  if (!itemToDelete.value) return;
  router.delete(`/admin/users/${itemToDelete.value.id}`, {
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

const form = useForm({
  name: '',
  email: '',
  password: '',
  roles: [],
  clients: [],
});

const openFormModal = (item = null) => {
  form.clearErrors();
  if (item) {
    isEdit.value = true;
    editingId.value = item.id;
    selectedItem.value = item;

    form.name = item.name || '';
    form.email = item.email || '';
    form.password = '';
    form.roles = (item.roles || []).map((r) => String(r.id));
    form.clients = (item.clients || []).map((c) => String(c.id));
  } else {
    isEdit.value = false;
    editingId.value = null;
    selectedItem.value = null;
    form.reset();
  }
  formModalOpen.value = true;
};

const submitForm = () => {
  form.transform((data) => {
    const transformed = {
      ...data,
      roles: (data.roles || []).map(Number),
      clients: (data.clients || []).map(Number),
    };
    if (isEdit.value && !transformed.password) {
      delete transformed.password;
    }
    return transformed;
  });

  const url = isEdit.value ? `/admin/users/${editingId.value}` : '/admin/users';
  
  if (isEdit.value) {
    form.put(url, {
      onSuccess: () => {
        formModalOpen.value = false;
        doSearch();
      }
    });
  } else {
    form.post(url, {
      onSuccess: () => {
        formModalOpen.value = false;
        doSearch();
      }
    });
  }
};
</script>

<template>
  <div class="space-y-6">
    <Breadcrumb title="Users" :trail="[{ label: 'Users' }]">
      <template #actions>
        <BaseButton v-if="can('user_create')" @click="openFormModal()" variant="primary" icon="ri-add-line">Add User</BaseButton>
      </template>
    </Breadcrumb>

    <!-- Filter Bar -->
    <FilterBar :loading="loading" @search="doSearch(1, 25)" @reset="doReset">
      <FormInput v-model="searchForm.keyword" label="Keyword" placeholder="Name or email..." icon="ri-search-line" />
      
      <FormSelect
        v-model="searchForm.role"
        label="Role"
        :options="roleOptions"
        placeholder="All Roles"
      />
      <FormSelect
        v-model="searchForm.client"
        label="Client"
        :options="clientOptions"
        placeholder="All Clients"
      />
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
      <!-- Bold Name -->
      <template #cell-name="{ row }">
        <div class="flex items-center gap-3">
          <BaseAvatar :name="row.name" :size="32" rounded />
          <span class="font-bold text-ink dark:text-slate-100 whitespace-nowrap">{{ row.name }}</span>
        </div>
      </template>

      <!-- Email -->
      <template #cell-email="{ row }">
        <span class="whitespace-nowrap">{{ row.email }}</span>
      </template>

      <!-- Verified At -->
      <template #cell-email_verified_at="{ row }">
        <span class="text-slate-500 whitespace-nowrap">{{ row.email_verified_at || '—' }}</span>
      </template>

      <!-- Roles Badges -->
      <template #cell-roles="{ row }">
        <div class="flex flex-wrap gap-1">
          <span 
            v-for="role in row.roles" 
            :key="role.id" 
            class="px-2 py-0.5 rounded-full text-xs font-semibold bg-primary-50 dark:bg-primary-500/10 text-primary-700 dark:text-primary-300 whitespace-nowrap"
          >
            {{ role.name }}
          </span>
        </div>
      </template>

      <!-- Row Actions -->
      <template #row-actions="{ row }">
        <div class="inline-flex items-center gap-1 whitespace-nowrap">
          <button
            v-if="can('user_show')"
            @click="openViewModal(row)"
            class="w-8 h-8 inline-flex items-center justify-center rounded-lg text-info hover:bg-info/10 transition-colors"
            title="View Details"
          >
            <i class="ri-eye-line text-lg"></i>
          </button>
          <button
            v-if="can('user_edit')"
            @click="openFormModal(row)"
            class="w-8 h-8 inline-flex items-center justify-center rounded-lg text-primary-600 hover:bg-primary-50 dark:hover:bg-primary-500/10 transition-colors"
            title="Edit"
          >
            <i class="ri-pencil-line text-lg"></i>
          </button>
          <button
            v-if="can('can-delete')"
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
      <div class="p-6 text-center space-y-4">
        <div class="mx-auto flex items-center justify-center h-12 w-12 rounded-full bg-danger/10 mb-4">
          <i class="ri-alert-line text-2xl text-danger"></i>
        </div>
        <h3 class="text-xl font-semibold text-ink dark:text-white">Delete User</h3>
        <p class="text-sm text-slate-500 leading-relaxed">
          Are you sure you want to delete user <span class="font-semibold text-slate-800 dark:text-slate-200">"{{ itemToDelete?.name }}"</span>? This action cannot be undone.
        </p>
        <div class="flex items-center gap-3 justify-center pt-2">
          <BaseButton variant="white" @click="deleteModalOpen = false">Cancel</BaseButton>
          <BaseButton variant="danger" @click="executeDelete">Yes, Delete</BaseButton>
        </div>
      </div>
    </BaseModal>

    <!-- View Modal -->
    <BaseModal v-model="viewModalOpen" max-width="lg">
      <div v-if="selectedItem" class="p-6 space-y-6">
        <div class="flex items-start justify-between">
          <div>
            <h3 class="text-lg font-bold text-ink dark:text-white">{{ selectedItem.name }}</h3>
            <p class="text-sm text-slate-400 mt-0.5">{{ selectedItem.email }}</p>
          </div>
          <button @click="closeViewModal" class="text-slate-400 hover:text-slate-600 dark:hover:text-slate-200">
            <i class="ri-close-line text-2xl"></i>
          </button>
        </div>

        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 text-[13px]">
          <div>
            <span class="block font-semibold text-slate-500 dark:text-slate-400">ID</span>
            <span class="text-ink dark:text-slate-200">{{ selectedItem.id }}</span>
          </div>
          <div>
            <span class="block font-semibold text-slate-500 dark:text-slate-400">Email Verified At</span>
            <span class="text-ink dark:text-slate-200">{{ selectedItem.email_verified_at || 'Never' }}</span>
          </div>
          <div class="sm:col-span-2">
            <span class="block font-semibold text-slate-500 dark:text-slate-400 mb-1.5">Roles</span>
            <div class="flex flex-wrap gap-1">
              <span 
                v-for="role in selectedItem.roles" 
                :key="role.id" 
                class="px-2 py-0.5 rounded-full text-xs font-semibold bg-primary-50 dark:bg-primary-500/10 text-primary-700 dark:text-primary-300"
              >
                {{ role.name }}
              </span>
            </div>
          </div>
          <div class="sm:col-span-2">
            <span class="block font-semibold text-slate-500 dark:text-slate-400 mb-1.5">Assigned Clients</span>
            <div v-if="selectedItem.clients && selectedItem.clients.length" class="flex flex-wrap gap-1">
              <span 
                v-for="client in selectedItem.clients" 
                :key="client.id" 
                class="px-2 py-0.5 rounded-full text-xs font-semibold bg-slate-100 dark:bg-white/10 text-slate-700 dark:text-slate-300"
              >
                {{ client.english_name || client.arabic_name }}
              </span>
            </div>
            <span v-else class="text-slate-400">No clients assigned (Full Admin Access)</span>
          </div>
        </div>
      </div>
    </BaseModal>

    <!-- Create/Edit Form Modal -->
    <BaseModal v-model="formModalOpen" max-width="lg">
      <div class="p-6 border-b border-slate-100 dark:border-white/5 flex items-center justify-between">
        <div>
          <h3 class="text-lg font-semibold text-ink dark:text-white">
            {{ isEdit ? 'Edit User Details' : 'Create New User' }}
          </h3>
          <p class="text-sm text-slate-500 mt-1">
            Fill in the details below to {{ isEdit ? 'update the' : 'create a' }} user account.
          </p>
        </div>
        <button @click="formModalOpen = false" class="text-slate-400 hover:text-slate-600 dark:hover:text-slate-200">
          <i class="ri-close-line text-2xl"></i>
        </button>
      </div>

      <form @submit.prevent="submitForm">
        <div class="p-6 space-y-5 max-h-[calc(100vh-250px)] overflow-y-auto">
          
          <FormInput 
            v-model="form.name" 
            label="Name" 
            placeholder="Full Name" 
            required 
            :error="form.errors.name" 
          />

          <FormInput 
            v-model="form.email" 
            type="email"
            label="Email" 
            placeholder="email@example.com" 
            required 
            :error="form.errors.email" 
          />

          <FormInput 
            v-model="form.password" 
            type="password"
            label="Password" 
            :placeholder="isEdit ? 'Leave blank to keep current' : 'Minimum 8 characters'" 
            :required="!isEdit" 
            :error="form.errors.password" 
          />

          <FormSelect
            v-model="form.roles"
            label="Roles"
            :options="roleOptions"
            placeholder="Select Role(s)"
            multiple
            required
            floating
            :error="form.errors.roles"
          />

          <FormSelect
            v-model="form.clients"
            label="Clients"
            :options="clientOptions"
            placeholder="Select Client(s) (Optional)"
            multiple
            floating
            :error="form.errors.clients"
          />
        </div>

        <div class="flex items-center justify-end gap-3 p-6 border-t border-slate-100 dark:border-white/5">
          <BaseButton type="button" variant="white" @click="formModalOpen = false">Cancel</BaseButton>
          <BaseButton type="submit" variant="primary" :loading="form.processing">
            {{ isEdit ? 'Save Changes' : 'Create User' }}
          </BaseButton>
        </div>
      </form>
    </BaseModal>
  </div>
</template>
