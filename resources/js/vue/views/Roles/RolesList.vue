<script setup>
import { ref, watch, onMounted, computed } from 'vue';
import { usePage, Link, router, useForm } from '@inertiajs/vue3';
import axios from 'axios';
import debounce from 'lodash/debounce';

import Breadcrumb from '../../components/Breadcrumb.vue';
import StatusBadge from '../../components/StatusBadge.vue';
import BaseButton from '../../components/BaseButton.vue';
import BaseModal from '../../components/BaseModal.vue';
import FormInput from '../../components/FormInput.vue';
import { usePermissions } from '../../composables/usePermissions';

const props = defineProps({
  initialRows: Array,
  permissions: Array, // [{ id, name }]
});

const { can } = usePermissions();

const keyword = ref('');
const rows = ref([]);
const loading = ref(false);

const doSearch = debounce(async () => {
  loading.value = true;
  try {
    const params = new URLSearchParams();
    if (keyword.value) params.append('keyword', keyword.value);

    const { data } = await axios.get('/app/admin/roles', { params });
    rows.value = data.rows;
  } catch (err) {
    console.error('Error fetching roles:', err);
  } finally {
    loading.value = false;
  }
}, 300);

const doReset = () => {
  keyword.value = '';
  doSearch();
};

onMounted(() => {
  rows.value = props.initialRows || [];
});

// Stats Calculations
const totalRoles = computed(() => rows.value.length);
const totalPermissionAssignments = computed(() => {
  return rows.value.reduce((acc, row) => acc + (row.permissions?.length || 0), 0);
});

// Group permissions helper (e.g., client_access, client_create -> Client)
const getCategoryName = (permName) => {
  const parts = permName.split('_');
  const prefix = parts[0] || 'system';
  
  // Custom readable categories
  const mapping = {
    client: 'Clients & Locations',
    location: 'Clients & Locations',
    user: 'Users & Access Control',
    role: 'Users & Access Control',
    permission: 'Users & Access Control',
    driver: 'Drivers & Operations',
    car: 'Drivers & Operations',
    task: 'Tasks & Shipments',
    sample: 'Tasks & Shipments',
    shipment: 'Tasks & Shipments',
    money: 'Financials',
    audit: 'System logs',
    barcode: 'System logs',
    attendance: 'Attendance',
    zone: 'Geofencing & Zones',
  };
  
  return mapping[prefix] || (prefix.charAt(0).toUpperCase() + prefix.slice(1) + ' Module');
};

// Group all permissions for form category selectors
const groupedAllPermissions = computed(() => {
  const groups = {};
  (props.permissions || []).forEach((p) => {
    const cat = getCategoryName(p.name);
    if (!groups[cat]) groups[cat] = [];
    groups[cat].push(p);
  });
  return groups;
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
  router.delete(`/app/admin/roles/${itemToDelete.value.id}`, {
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

const groupedSelectedPermissions = computed(() => {
  if (!selectedItem.value) return {};
  const groups = {};
  (selectedItem.value.permissions || []).forEach((p) => {
    const cat = getCategoryName(p.name);
    if (!groups[cat]) groups[cat] = [];
    groups[cat].push(p.name);
  });
  return groups;
});

// Form Modal
const formModalOpen = ref(false);
const isEdit = ref(false);
const editingId = ref(null);

const form = useForm({
  name: '',
  permissions: [], // Array of permission IDs
});

const openFormModal = (item = null) => {
  form.clearErrors();
  if (item) {
    isEdit.value = true;
    editingId.value = item.id;
    form.name = item.name || '';
    form.permissions = (item.permissions || []).map((p) => p.id);
  } else {
    isEdit.value = false;
    editingId.value = null;
    form.reset();
  }
  formModalOpen.value = true;
};

// Category select all / deselect all helper inside form
const isCategoryAllSelected = (permsInCat) => {
  return permsInCat.every(p => form.permissions.includes(p.id));
};

const toggleCategoryPermissions = (permsInCat) => {
  const allSelected = isCategoryAllSelected(permsInCat);
  if (allSelected) {
    // Remove all
    const idsToRemove = permsInCat.map(p => p.id);
    form.permissions = form.permissions.filter(id => !idsToRemove.includes(id));
  } else {
    // Add missing
    const currentSet = new Set(form.permissions);
    permsInCat.forEach(p => currentSet.add(p.id));
    form.permissions = [...currentSet];
  }
};

const submitForm = () => {
  const url = isEdit.value ? `/app/admin/roles/${editingId.value}` : '/app/admin/roles';
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

// Gradient mapping for Role Cards based on name or ID rotation
const getRoleGradient = (role) => {
  const lower = (role.name || '').toLowerCase();
  if (lower.includes('admin')) {
    return 'from-[#BD6BA7] to-[#d38cb8] dark:from-[#a85390] dark:to-[#BD6BA7]'; // MTC Pink (#BD6BA7)
  }
  if (lower.includes('driver')) {
    return 'from-[#0d9488] to-[#005D69] dark:from-[#005D69] dark:to-[#00424b]'; // MTC Green/Teal (#005D69)
  }
  if (lower.includes('client')) {
    return 'from-[#299cdb] to-[#1d7cb3] dark:from-[#1d7cb3] dark:to-[#125880]'; // MTC Blue (#299cdb)
  }
  
  // Rotating fallback gradients using MTC design tokens
  const gradients = [
    'from-[#BD6BA7] to-[#d38cb8] dark:from-[#a85390] dark:to-[#BD6BA7]',       // Pink (#BD6BA7)
    'from-[#0d9488] to-[#005D69] dark:from-[#005D69] dark:to-[#00424b]',       // Green (#005D69)
    'from-[#299cdb] to-[#1d7cb3] dark:from-[#1d7cb3] dark:to-[#125880]',       // Blue (#299cdb)
    'from-[#f7b84b] to-[#e89e2b] dark:from-[#e89e2b] dark:to-[#b87614]',       // Orange (#f7b84b)
  ];
  return gradients[role.id % gradients.length];
};
</script>

<template>
  <div class="space-y-6">
    <div class="flex items-center justify-between mb-4">
      <Breadcrumb title="Roles & Permissions" :trail="[{ label: 'Roles' }]">
        <template #actions>
          <BaseButton v-if="can('role_create')" @click="openFormModal()" variant="primary" icon="ri-add-line">Add Role</BaseButton>
        </template>
      </Breadcrumb>
    </div>

    <!-- Stats Summary Row -->
    <div class="flex flex-wrap gap-4 mb-2">
      <!-- Total Roles Stat Card -->
      <div class="inline-flex items-center gap-3 px-5 py-3.5 bg-white dark:bg-slate-900 border border-slate-200 dark:border-white/10 rounded-2xl shadow-sm min-w-[200px]">
        <span class="w-9 h-9 rounded-xl bg-gradient-to-br from-[#0d9488] to-[#005D69] text-white flex items-center justify-center shrink-0">
          <i class="ri-shield-user-line text-lg"></i>
        </span>
        <div class="flex flex-col">
          <span class="text-[11px] text-slate-500 dark:text-slate-400 font-bold uppercase tracking-wider">Total Roles</span>
          <span class="text-base font-extrabold text-ink dark:text-white mt-0.5">{{ totalRoles }}</span>
        </div>
      </div>

      <!-- Permission Assignments Stat Card -->
      <div class="inline-flex items-center gap-3 px-5 py-3.5 bg-white dark:bg-slate-900 border border-slate-200 dark:border-white/10 rounded-2xl shadow-sm min-w-[240px]">
        <span class="w-9 h-9 rounded-xl bg-gradient-to-br from-[#BD6BA7] to-[#a85390] text-white flex items-center justify-center shrink-0">
          <i class="ri-key-2-line text-lg"></i>
        </span>
        <div class="flex flex-col">
          <span class="text-[11px] text-slate-500 dark:text-slate-400 font-bold uppercase tracking-wider">Permission Assignments</span>
          <span class="text-base font-extrabold text-ink dark:text-white mt-0.5">{{ totalPermissionAssignments }}</span>
        </div>
      </div>
    </div>

    <!-- Compact Inline Search Bar -->
    <div class="flex flex-wrap items-center gap-3 bg-white dark:bg-slate-900 border border-slate-200 dark:border-white/10 rounded-2xl p-4 mb-6 shadow-sm">
      <div class="relative flex-1 min-w-[240px]">
        <i class="ri-search-line absolute top-1/2 -translate-y-1/2 left-3.5 text-slate-400 text-[15px]"></i>
        <input 
          v-model="keyword" 
          @input="doSearch"
          type="text" 
          placeholder="Search role name..." 
          class="w-full h-11 pl-10 pr-4 rounded-xl bg-slate-50 dark:bg-white/5 border border-transparent focus:border-primary-500 text-[13px] text-ink dark:text-slate-200 focus:outline-none focus:ring-2 focus:ring-primary-500/20 transition-all duration-200"
        />
      </div>
      <BaseButton variant="white" icon="ri-refresh-line" @click="doReset" class="h-11">Reset</BaseButton>
      <BaseButton variant="primary" icon="ri-search-line" :loading="loading" @click="doSearch" class="h-11">Search</BaseButton>
    </div>

    <!-- Cards Grid -->
    <div v-if="loading" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
      <div v-for="n in 6" :key="n" class="border border-slate-100 dark:border-white/5 bg-white dark:bg-slate-900 rounded-2xl p-6 space-y-4 animate-pulse">
        <div class="h-8 bg-slate-200 dark:bg-white/10 rounded w-1/2"></div>
        <div class="space-y-2">
          <div class="h-4 bg-slate-200 dark:bg-white/10 rounded w-full"></div>
          <div class="h-4 bg-slate-200 dark:bg-white/10 rounded w-3/4"></div>
        </div>
        <div class="flex gap-2">
          <div class="h-6 bg-slate-200 dark:bg-white/10 rounded w-16" v-for="i in 3" :key="i"></div>
        </div>
      </div>
    </div>

    <div v-else-if="rows.length" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
      <div 
        v-for="role in rows" 
        :key="role.id" 
        class="group relative border border-slate-200/80 dark:border-white/10 bg-white dark:bg-slate-900 rounded-2xl shadow-sm hover:shadow-xl hover:border-primary-500/30 transition-all duration-300 overflow-hidden flex flex-col justify-between"
      >
        <!-- Top Gradient Banner -->
        <div class="h-2 w-full bg-gradient-to-r" :class="getRoleGradient(role)"></div>
        
        <div class="p-6 space-y-4 flex-1 flex flex-col justify-between">
          <div class="space-y-3">
            <div class="flex items-center justify-between">
              <h4 class="text-base font-extrabold text-ink dark:text-white tracking-tight group-hover:text-primary-600 dark:group-hover:text-primary-400 transition-colors">
                {{ role.name }}
              </h4>
              <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-semibold bg-primary-50 text-primary-700 dark:bg-primary-500/15 dark:text-primary-300">
                {{ role.users_count || 0 }} Users
              </span>
            </div>

            <p class="text-xs text-slate-500 dark:text-slate-400">
              Contains <span class="font-bold text-slate-700 dark:text-slate-200">{{ role.permissions?.length || 0 }}</span> active system permissions.
            </p>
          </div>

          <!-- Permissions Badges Preview -->
          <div class="space-y-2">
            <span class="block text-[10px] font-bold uppercase tracking-wider text-slate-400 dark:text-slate-500">Key Permissions</span>
            <div class="flex flex-wrap gap-1">
              <span 
                v-for="perm in (role.permissions || []).slice(0, 4)" 
                :key="perm.id"
                class="px-2 py-0.5 rounded-md bg-slate-50 dark:bg-white/5 border border-slate-100 dark:border-white/5 text-[11px] text-slate-600 dark:text-slate-400 font-medium truncate max-w-[120px]"
              >
                {{ perm.name }}
              </span>
              <span 
                v-if="role.permissions?.length > 4"
                class="px-2 py-0.5 rounded-md bg-primary-50/50 dark:bg-primary-500/10 text-[11px] text-primary-600 dark:text-primary-400 font-semibold"
              >
                +{{ role.permissions.length - 4 }} More
              </span>
            </div>
          </div>
        </div>

        <!-- Action Area -->
        <div class="px-6 py-4 border-t border-slate-100 dark:border-white/5 bg-slate-50/50 dark:bg-white/5 flex items-center justify-end gap-2 shrink-0">
          <button 
            v-if="can('role_show')" 
            @click="openViewModal(role)" 
            class="h-8 px-3 inline-flex items-center gap-1 rounded-lg text-xs font-semibold text-slate-600 hover:text-info hover:bg-info/10 dark:text-slate-400 dark:hover:text-info transition"
            title="View Details"
          >
            <i class="ri-eye-line text-sm"></i>
            Details
          </button>
          
          <button 
            v-if="can('role_edit')" 
            @click="openFormModal(role)" 
            class="h-8 px-3 inline-flex items-center gap-1 rounded-lg text-xs font-semibold text-slate-600 hover:text-primary-600 hover:bg-primary-50 dark:text-slate-400 dark:hover:text-primary-400 dark:hover:bg-primary-500/10 transition"
            title="Edit Role"
          >
            <i class="ri-pencil-line text-sm"></i>
            Edit
          </button>

          <button 
            v-if="can('can-delete') && role.id !== 1" 
            @click="confirmDelete(role)" 
            class="h-8 px-3 inline-flex items-center gap-1 rounded-lg text-xs font-semibold text-slate-600 hover:text-danger hover:bg-danger/10 dark:text-slate-400 dark:hover:text-danger transition"
            title="Delete Role"
          >
            <i class="ri-delete-bin-line text-sm"></i>
            Delete
          </button>
        </div>
      </div>
    </div>

    <!-- Empty State -->
    <div v-else class="flex flex-col items-center justify-center p-12 text-center border border-dashed border-slate-200 dark:border-white/10 rounded-2xl">
      <div class="w-16 h-16 rounded-full bg-slate-100 dark:bg-white/10 text-slate-400 flex items-center justify-center mb-4">
        <i class="ri-shield-line text-3xl"></i>
      </div>
      <h3 class="text-base font-semibold text-slate-700 dark:text-slate-300">No Roles Found</h3>
      <p class="text-sm text-slate-500 max-w-[280px] mt-1">Try refining your keyword search or add a new role.</p>
    </div>

    <!-- Delete Confirmation Modal -->
    <BaseModal v-model="deleteModalOpen" max-width="sm">
      <div class="p-6 text-center space-y-4">
        <div class="mx-auto flex items-center justify-center h-12 w-12 rounded-full bg-danger/10 mb-4">
          <i class="ri-alert-line text-2xl text-danger"></i>
        </div>
        <h3 class="text-xl font-semibold text-ink dark:text-white">Delete Role</h3>
        <p class="text-sm text-slate-500 leading-relaxed">
          Are you sure you want to delete role <span class="font-semibold text-slate-800 dark:text-slate-200">"{{ itemToDelete?.name }}"</span>? This will detach it from all users.
        </p>
        <div class="flex items-center gap-3 justify-center pt-2">
          <BaseButton variant="white" @click="deleteModalOpen = false">Cancel</BaseButton>
          <BaseButton variant="danger" @click="executeDelete">Yes, Delete</BaseButton>
        </div>
      </div>
    </BaseModal>

    <!-- View Details Modal (Dynamic category grouping) -->
    <BaseModal v-model="viewModalOpen" max-width="xl">
      <div v-if="selectedItem" class="p-6 space-y-6">
        <div class="flex items-start justify-between">
          <div class="space-y-1">
            <div class="flex items-center gap-2">
              <span class="w-2.5 h-2.5 rounded-full" :class="'bg-gradient-to-r ' + getRoleGradient(selectedItem)"></span>
              <h3 class="text-lg font-bold text-ink dark:text-white">{{ selectedItem.name }}</h3>
            </div>
            <p class="text-xs text-slate-500">Currently assigned to <span class="font-bold text-slate-800 dark:text-slate-200">{{ selectedItem.users_count || 0 }}</span> active users.</p>
          </div>
          <button @click="viewModalOpen = false" class="text-slate-400 hover:text-slate-600 dark:hover:text-slate-200">
            <i class="ri-close-line text-2xl"></i>
          </button>
        </div>

        <div class="space-y-5 max-h-[calc(100vh-250px)] overflow-y-auto">
          <div 
            v-for="(perms, category) in groupedSelectedPermissions" 
            :key="category"
            class="border border-slate-100 dark:border-white/5 rounded-xl p-4 bg-slate-50/50 dark:bg-white/5 space-y-3"
          >
            <h5 class="text-[12px] font-bold text-primary-600 dark:text-primary-400 uppercase tracking-wider">
              {{ category }}
            </h5>
            <div class="flex flex-wrap gap-1.5">
              <span 
                v-for="p in perms" 
                :key="p"
                class="px-2.5 py-0.5 rounded-md bg-white dark:bg-slate-800 border border-slate-200/60 dark:border-white/10 text-xs text-slate-600 dark:text-slate-300 font-medium"
              >
                {{ p }}
              </span>
            </div>
          </div>
          <div v-if="!selectedItem.permissions?.length" class="text-center p-6 text-slate-400 text-sm">
            No permissions active for this role.
          </div>
        </div>
      </div>
    </BaseModal>

    <!-- Create/Edit Form Modal -->
    <BaseModal v-model="formModalOpen" max-width="xl">
      <div class="p-6 border-b border-slate-100 dark:border-white/5 flex items-center justify-between">
        <div>
          <h3 class="text-lg font-semibold text-ink dark:text-white">
            {{ isEdit ? 'Edit Role Details' : 'Create New Role' }}
          </h3>
          <p class="text-sm text-slate-500 mt-1">
            Specify the role name and select the authorized permissions.
          </p>
        </div>
        <button @click="formModalOpen = false" class="text-slate-400 hover:text-slate-600 dark:hover:text-slate-200">
          <i class="ri-close-line text-2xl"></i>
        </button>
      </div>

      <form @submit.prevent="submitForm">
        <div class="p-6 space-y-6 max-h-[calc(100vh-250px)] overflow-y-auto">
          
          <FormInput 
            v-model="form.name" 
            label="Role Name" 
            placeholder="e.g. Operation Supervisor" 
            required 
            :error="form.errors.name" 
          />

          <!-- Grouped Permissions Selector -->
          <div class="space-y-4">
            <label class="block text-[13px] font-bold text-slate-800 dark:text-slate-200">
              Assigned Permissions <span class="text-danger">*</span>
            </label>
            <div v-if="form.errors.permissions" class="text-danger text-xs mt-1 mb-2">{{ form.errors.permissions }}</div>
            
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
              <div 
                v-for="(perms, category) in groupedAllPermissions" 
                :key="category"
                class="border border-slate-200 dark:border-white/10 rounded-xl p-4 bg-white dark:bg-slate-900 space-y-3"
              >
                <div class="flex items-center justify-between pb-2 border-b border-slate-100 dark:border-white/5">
                  <h5 class="text-[12px] font-bold text-ink dark:text-slate-200 uppercase tracking-wider">
                    {{ category }}
                  </h5>
                  <!-- Category Checkbox control -->
                  <button 
                    type="button" 
                    @click="toggleCategoryPermissions(perms)"
                    class="text-[11px] font-semibold text-primary-500 hover:text-primary-700 select-none"
                  >
                    {{ isCategoryAllSelected(perms) ? 'Deselect All' : 'Select All' }}
                  </button>
                </div>

                <div class="space-y-2 max-h-48 overflow-y-auto">
                  <label 
                    v-for="p in perms" 
                    :key="p.id"
                    class="flex items-center gap-2 cursor-pointer text-xs text-slate-600 dark:text-slate-300 hover:text-ink select-none"
                  >
                    <input 
                      type="checkbox"
                      :value="p.id"
                      v-model="form.permissions"
                      class="rounded border-slate-300 text-primary-600 focus:ring-primary-500"
                    />
                    {{ p.name }}
                  </label>
                </div>
              </div>
            </div>
          </div>
        </div>

        <div class="flex items-center justify-end gap-3 p-6 border-t border-slate-100 dark:border-white/5">
          <BaseButton type="button" variant="white" @click="formModalOpen = false">Cancel</BaseButton>
          <BaseButton type="submit" variant="primary" :loading="form.processing">
            {{ isEdit ? 'Save Changes' : 'Create Role' }}
          </BaseButton>
        </div>
      </form>
    </BaseModal>
  </div>
</template>
