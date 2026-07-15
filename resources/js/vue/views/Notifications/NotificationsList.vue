<script setup>
import { ref, computed, reactive, watch } from 'vue';
import { router } from '@inertiajs/vue3';
import { usePermissions } from '../../composables/usePermissions';

import Breadcrumb from '../../components/Breadcrumb.vue';
import DataTable from '../../components/DataTable.vue';
import BaseButton from '../../components/BaseButton.vue';
import BaseModal from '../../components/BaseModal.vue';
import FilterBar from '../../components/FilterBar.vue';
import FormInput from '../../components/FormInput.vue';
import BaseAvatar from '../../components/BaseAvatar.vue';

const props = defineProps({
  rows: Array,
  total: Number,
  page: Number,
  pageSize: Number,
  filters: Object,
});

const { can } = usePermissions();

const DEFAULT_FILTERS = { keyword: '', sort_by: 'id', sort_order: 'desc' };
const filters = reactive({ ...DEFAULT_FILTERS, ...props.filters });

const columns = [
  { key: 'id', label: 'ID', width: '80px' },
  { key: 'task_id', label: 'Task ID' },
  { key: 'from_location_name', label: 'From Location' },
  { key: 'to_location_name', label: 'To Location' },
  { key: 'driver_name', label: 'Driver' },
  { key: 'billing_client_name', label: 'Client' },
  { key: 'read_at', label: 'Read At', ltr: true },
];

const loading = ref(false);

function reload(extra = {}) {
  loading.value = true;
  router.get('/admin/notifications', { ...filters, ...extra }, {
    preserveState: true,
    preserveScroll: true,
    only: ['rows', 'total', 'page', 'pageSize', 'filters'],
    onFinish: () => { loading.value = false; },
  });
}

function doSearch() { reload({ page: 1 }); }
function doReset() { Object.assign(filters, DEFAULT_FILTERS); reload({ page: 1 }); }
function onQuery(q) { reload({ page: q.page, pageSize: q.pageSize }); }

/* --- Show Details Modal --- */
const showDetails = ref(false);
const detailTarget = ref(null);

function viewDetails(row) {
  detailTarget.value = row;
  showDetails.value = true;
}
</script>

<template>
  <div>
    <Breadcrumb title="Notifications" :trail="[{ label: 'Notifications' }]" />

    <FilterBar :loading="loading" subtitle="Search and filter notifications" @search="doSearch" @reset="doReset">
      <FormInput v-model="filters.keyword" label="Search" placeholder="Search by ID, Task ID, Location, Driver..." icon="ri-search-line" />
    </FilterBar>

    <DataTable
      title="Notifications"
      :columns="columns" :rows="rows || []" row-key="id"
      :loading="loading" :server-side="true" :total="total || 0" :searchable="false"
      @query="onQuery"
    >
      <template #cell-id="{ value }">
        <span class="font-black text-[#0ab39c]">#{{ value }}</span>
      </template>

      <template #cell-driver_name="{ value }">
        <div v-if="value" class="flex items-center gap-2">
          <BaseAvatar :name="value" :size="26" />
          <span class="text-[12.5px] font-medium text-ink dark:text-slate-200 whitespace-nowrap">{{ value }}</span>
        </div>
        <span v-else class="text-slate-400">—</span>
      </template>
      
      <template #cell-read_at="{ value }">
        <span v-if="value" class="text-slate-600 dark:text-slate-300 font-medium whitespace-nowrap">
          <i class="ri-check-double-line text-primary me-1"></i> {{ value }}
        </span>
        <span v-else class="text-slate-400 italic">Unread</span>
      </template>

      <template #row-actions="{ row }">
        <div class="inline-flex items-center gap-1">
          <button v-if="can('notification_show')" @click="viewDetails(row)" class="grid place-items-center w-8 h-8 rounded-lg text-info hover:bg-info/10 transition" title="View Details">
            <i class="ri-eye-line"></i>
          </button>
        </div>
      </template>
    </DataTable>

    <!-- Details Modal -->
    <BaseModal v-model="showDetails" title="Notification Details" icon="ri-notification-3-line" size="md">
      <div v-if="detailTarget" class="space-y-4">
        <div class="grid grid-cols-2 gap-4">
          <div class="bg-slate-50 dark:bg-slate-800/50 p-3 rounded-lg">
            <span class="block text-xs text-slate-500 mb-1">Notification ID</span>
            <span class="font-bold text-ink dark:text-white">#{{ detailTarget.id }}</span>
          </div>
          <div class="bg-slate-50 dark:bg-slate-800/50 p-3 rounded-lg">
            <span class="block text-xs text-slate-500 mb-1">Task ID</span>
            <span class="font-bold text-ink dark:text-white">{{ detailTarget.task_id || '—' }}</span>
          </div>
        </div>

        <div class="bg-slate-50 dark:bg-slate-800/50 p-3 rounded-lg">
          <span class="block text-xs text-slate-500 mb-1">Locations</span>
          <div class="flex items-center gap-2 text-sm font-medium">
            <span class="text-slate-700 dark:text-slate-300">{{ detailTarget.from_location_name || '—' }}</span>
            <i class="ri-arrow-right-line text-slate-400"></i>
            <span class="text-slate-700 dark:text-slate-300">{{ detailTarget.to_location_name || '—' }}</span>
          </div>
        </div>

        <div class="grid grid-cols-2 gap-4">
          <div class="bg-slate-50 dark:bg-slate-800/50 p-3 rounded-lg">
            <span class="block text-xs text-slate-500 mb-1">Driver</span>
            <span class="font-medium text-ink dark:text-white">{{ detailTarget.driver_name || '—' }}</span>
          </div>
          <div class="bg-slate-50 dark:bg-slate-800/50 p-3 rounded-lg">
            <span class="block text-xs text-slate-500 mb-1">Billing Client</span>
            <span class="font-medium text-ink dark:text-white">{{ detailTarget.billing_client_name || '—' }}</span>
          </div>
        </div>

        <div class="grid grid-cols-2 gap-4">
          <div class="bg-slate-50 dark:bg-slate-800/50 p-3 rounded-lg">
            <span class="block text-xs text-slate-500 mb-1">Type</span>
            <span class="font-medium text-ink dark:text-white break-all">{{ detailTarget.type || '—' }}</span>
          </div>
          <div class="bg-slate-50 dark:bg-slate-800/50 p-3 rounded-lg">
            <span class="block text-xs text-slate-500 mb-1">Notifiable Type</span>
            <span class="font-medium text-ink dark:text-white break-all">{{ detailTarget.notifiable_type || '—' }}</span>
          </div>
        </div>

        <div class="bg-slate-50 dark:bg-slate-800/50 p-3 rounded-lg">
          <span class="block text-xs text-slate-500 mb-1">Data</span>
          <pre class="text-xs font-mono text-slate-700 dark:text-slate-300 whitespace-pre-wrap">{{ detailTarget.data || '—' }}</pre>
        </div>

        <div class="grid grid-cols-2 gap-4">
          <div class="bg-slate-50 dark:bg-slate-800/50 p-3 rounded-lg">
            <span class="block text-xs text-slate-500 mb-1">Created At</span>
            <span class="text-sm text-slate-700 dark:text-slate-300">{{ detailTarget.created_at || '—' }}</span>
          </div>
          <div class="bg-slate-50 dark:bg-slate-800/50 p-3 rounded-lg">
            <span class="block text-xs text-slate-500 mb-1">Read At</span>
            <span class="text-sm" :class="detailTarget.read_at ? 'text-primary font-medium' : 'text-slate-400 italic'">
              {{ detailTarget.read_at || 'Unread' }}
            </span>
          </div>
        </div>

      </div>
      <template #footer>
        <BaseButton variant="light" @click="showDetails = false">Close</BaseButton>
      </template>
    </BaseModal>
  </div>
</template>
