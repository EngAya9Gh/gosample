<script setup>
import { ref, computed, onMounted, watch } from 'vue';
import { useForm, router } from '@inertiajs/vue3';
import debounce from 'lodash/debounce';

import Breadcrumb from '../../components/Breadcrumb.vue';
import BaseButton from '../../components/BaseButton.vue';
import BaseModal from '../../components/BaseModal.vue';
import FormInput from '../../components/FormInput.vue';
import { usePermissions } from '../../composables/usePermissions';

const props = defineProps({
  permissions: Array,
});

const { can } = usePermissions();

const keyword = ref('');
const loading = ref(false);
const rows = ref([]);

onMounted(() => {
  rows.value = props.permissions || [];
});

watch(() => props.permissions, (newVal) => {
  rows.value = newVal || [];
}, { deep: true });

const doSearch = debounce(() => {
  loading.value = true;
  setTimeout(() => { loading.value = false; }, 200);
}, 300);

const doReset = () => {
  keyword.value = '';
  doSearch();
};

const filteredPermissions = computed(() => {
  if (!keyword.value) return rows.value;
  const lower = keyword.value.toLowerCase();
  return rows.value.filter(p => p.name.toLowerCase().includes(lower));
});

const getCategoryName = (permName) => {
  const parts = permName.split('_');
  const prefix = parts[0] || 'system';
  
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

const groupedPermissions = computed(() => {
  const groups = {};
  filteredPermissions.value.forEach((p) => {
    const cat = getCategoryName(p.name);
    if (!groups[cat]) groups[cat] = [];
    groups[cat].push(p);
  });
  return groups;
});

const isModalOpen = ref(false);
const editingId = ref(null);

const form = useForm({
  name: '',
});

const openCreateModal = () => {
  editingId.value = null;
  form.reset();
  form.clearErrors();
  isModalOpen.value = true;
};

const openEditModal = (perm) => {
  editingId.value = perm.id;
  form.name = perm.name;
  form.clearErrors();
  isModalOpen.value = true;
};

const submitForm = () => {
  if (editingId.value) {
    form.put(`/admin/permissions/${editingId.value}`, {
      preserveScroll: true,
      onSuccess: () => {
        isModalOpen.value = false;
        router.reload({ only: ['permissions'] });
      }
    });
  } else {
    form.post('/admin/permissions', {
      preserveScroll: true,
      onSuccess: () => {
        isModalOpen.value = false;
        router.reload({ only: ['permissions'] });
      }
    });
  }
};

// Delete Confirmation State
const isDeleteModalOpen = ref(false);
const permissionToDelete = ref(null);

const deletePermission = (perm) => {
  permissionToDelete.value = perm;
  isDeleteModalOpen.value = true;
};

const confirmDelete = () => {
  if (!permissionToDelete.value) return;
  router.delete(`/admin/permissions/${permissionToDelete.value.id}`, {
    preserveScroll: true,
    onSuccess: () => {
      isDeleteModalOpen.value = false;
      permissionToDelete.value = null;
      router.reload({ only: ['permissions'] });
    }
  });
};
</script>

<template>
  <div class="space-y-6">
    <div class="flex items-center justify-between mb-4">
      <Breadcrumb title="System Permissions" :trail="[{ label: 'Permissions' }]">
        <template #actions>
          <BaseButton v-if="can('permission_create')" @click="openCreateModal" variant="primary" icon="ri-add-line">
            Add Permission
          </BaseButton>
        </template>
      </Breadcrumb>
    </div>

    <!-- Inline Search Bar -->
    <div class="flex flex-wrap items-center gap-3 bg-white dark:bg-slate-900 border border-slate-200 dark:border-white/10 rounded-2xl p-4 mb-6 shadow-sm">
      <div class="relative flex-1 min-w-[240px]">
        <i class="ri-search-line absolute top-1/2 -translate-y-1/2 left-3.5 text-slate-400 text-[15px]"></i>
        <input 
          v-model="keyword" 
          @input="doSearch"
          type="text" 
          placeholder="Search permissions..." 
          class="w-full h-11 pl-10 pr-4 rounded-xl bg-slate-50 dark:bg-white/5 border border-transparent focus:border-primary-500 text-[13px] text-ink dark:text-slate-200 focus:outline-none focus:ring-2 focus:ring-primary-500/20 transition-all duration-200"
        />
      </div>
      <BaseButton variant="white" icon="ri-refresh-line" @click="doReset" class="h-11">Reset</BaseButton>
    </div>

    <!-- Grid View: Grouped by Category -->
    <div v-if="!loading" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-6">
      <div 
        v-for="(perms, category) in groupedPermissions" 
        :key="category"
        class="bg-white dark:bg-slate-900 rounded-2xl border border-slate-200 dark:border-white/10 shadow-sm overflow-hidden flex flex-col"
      >
        <!-- Card Header -->
        <div class="px-5 py-4 border-b border-slate-100 dark:border-white/5 bg-slate-50/50 dark:bg-slate-800/50 flex items-center gap-3">
          <div class="w-8 h-8 rounded-lg bg-gradient-to-br from-[#BD6BA7] to-[#a85390] text-white flex items-center justify-center shrink-0 shadow-sm">
            <i class="ri-shield-keyhole-line text-base"></i>
          </div>
          <div>
            <h3 class="font-bold text-ink dark:text-white text-sm">{{ category }}</h3>
            <p class="text-xs text-slate-500 dark:text-slate-400">{{ perms.length }} permissions</p>
          </div>
        </div>

        <!-- Permissions List -->
        <div class="p-4 flex-1">
          <div class="flex flex-wrap gap-2">
            <div 
              v-for="perm in perms" 
              :key="perm.id"
              class="group relative inline-flex items-center gap-2 px-3 py-1.5 bg-slate-100 dark:bg-slate-800 text-slate-700 dark:text-slate-300 rounded-lg text-xs font-medium border border-transparent hover:border-slate-300 dark:hover:border-slate-600 transition-colors"
            >
              <span>{{ perm.name }}</span>
              
              <!-- Quick Actions overlay on hover -->
              <div class="absolute right-1 hidden group-hover:flex items-center bg-slate-100 dark:bg-slate-800 pl-2 rounded gap-1.5 border-l border-slate-200 dark:border-slate-700 h-full top-0">
                <button v-if="can('permission_edit')" @click="openEditModal(perm)" class="text-primary-600 hover:text-primary-700" title="Edit">
                  <i class="ri-edit-2-line"></i>
                </button>
                <button v-if="can('permission_delete')" @click="deletePermission(perm)" class="text-red-500 hover:text-red-600 mr-1" title="Delete">
                  <i class="ri-delete-bin-line"></i>
                </button>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Empty State -->
    <div v-if="!loading && Object.keys(groupedPermissions).length === 0" class="text-center py-20 bg-white dark:bg-slate-900 rounded-2xl border border-slate-200 dark:border-white/10 shadow-sm">
      <i class="ri-search-eye-line text-4xl text-slate-300 dark:text-slate-600 mb-3 block"></i>
      <h3 class="text-lg font-bold text-ink dark:text-white mb-1">No permissions found</h3>
      <p class="text-slate-500 dark:text-slate-400 text-sm">Try adjusting your search filters.</p>
    </div>

    <!-- Create/Edit Modal -->
    <BaseModal v-model="isModalOpen" :title="editingId ? 'Edit Permission' : 'Add Permission'" @close="isModalOpen = false">
      <form @submit.prevent="submitForm" class="space-y-4">
        <FormInput 
          v-model="form.name" 
          label="Permission Name" 
          placeholder="e.g. task_create" 
          :error="form.errors.name"
          required
        />
        
        <div class="flex justify-end gap-3 pt-4 border-t border-slate-100 dark:border-white/10">
          <BaseButton type="button" variant="white" @click="isModalOpen = false">Cancel</BaseButton>
          <BaseButton type="submit" variant="primary" :loading="form.processing">
            {{ editingId ? 'Save Changes' : 'Create Permission' }}
          </BaseButton>
        </div>
      </form>
    </BaseModal>

    <!-- Delete Confirmation Modal -->
    <BaseModal v-model="isDeleteModalOpen" title="Delete Permission" icon="ri-error-warning-line" tone="danger" size="md" @close="isDeleteModalOpen = false">
      <div class="space-y-4">
        <p class="text-[13px] text-slate-600 dark:text-slate-300 leading-relaxed">
          Are you sure you want to delete the permission <strong class="text-ink dark:text-white px-1.5 py-0.5 bg-slate-100 dark:bg-white/10 rounded">{{ permissionToDelete?.name }}</strong>?
        </p>
        <div class="p-3 bg-orange-50 dark:bg-orange-500/10 border border-orange-200 dark:border-orange-500/20 rounded-xl flex items-start gap-3 text-orange-800 dark:text-orange-400">
          <i class="ri-alert-line text-lg shrink-0 mt-0.5"></i>
          <div class="text-[12px] leading-relaxed">
            <strong class="block mb-1">Impact Warning:</strong>
            Deleting this permission will permanently remove it from the system. Any roles or users currently assigned to this permission will lose it, which may immediately revoke access to related system features.
          </div>
        </div>
      </div>
      
      <template #footer>
        <BaseButton variant="white" @click="isDeleteModalOpen = false">Cancel</BaseButton>
        <BaseButton variant="danger" icon="ri-delete-bin-line" @click="confirmDelete">Yes, Delete Permission</BaseButton>
      </template>
    </BaseModal>
  </div>
</template>
