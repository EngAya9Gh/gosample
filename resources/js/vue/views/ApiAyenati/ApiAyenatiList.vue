<script setup>
import { ref, computed } from 'vue';
import { router } from '@inertiajs/vue3';
import { usePermissions } from '../../composables/usePermissions';

import Breadcrumb from '../../components/Breadcrumb.vue';
import DataTable from '../../components/DataTable.vue';
import StatusBadge from '../../components/StatusBadge.vue';
import BaseModal from '../../components/BaseModal.vue';
import BaseButton from '../../components/BaseButton.vue';

const props = defineProps({
  logs: Object,
  filters: Object,
});

const { can } = usePermissions();

const columns = [
  { key: 'id', label: 'ID', width: '80px' },
  { key: 'created_at', label: 'Date', width: '180px' },
  { key: 'response_flag', label: 'Status', width: '120px' },
  { key: 'api_url', label: 'API URL' },
];

function fetchPage(page, search) {
  router.get('/app/admin/api-ayenatis', { page, search }, {
    preserveState: true,
    replace: true,
  });
}

const showModal = ref(false);
const activeLog = ref(null);

function viewLog(row) {
  activeLog.value = row;
  showModal.value = true;
}

const formattedResponse = computed(() => {
  if (!activeLog.value?.response) return '';
  try {
    const obj = JSON.parse(activeLog.value.response);
    return JSON.stringify(obj, null, 2);
  } catch (e) {
    return activeLog.value.response;
  }
});
</script>

<template>
  <div>
    <Breadcrumb title="API Ayenati Logs" :trail="[{ label: 'API Ayenati' }]" />

    <DataTable
      title="API Logs"
      :columns="columns"
      :rows="logs.data"
      row-key="id"
      :server-side="true"
      :total="logs.total"
      :current-page="logs.current_page"
      :last-page="logs.last_page"
      :searchable="true"
      :initial-search="filters?.search || ''"
      @query="({ page, search }) => fetchPage(page, search)"
    >
      <template #cell-created_at="{ value }">
        <span class="text-xs text-slate-500">{{ value }}</span>
      </template>

      <template #cell-response_flag="{ value }">
        <StatusBadge :status="value === 'success' ? 'active' : 'inactive'">
          {{ value || 'unknown' }}
        </StatusBadge>
      </template>

      <template #cell-api_url="{ value }">
        <div class="truncate max-w-[300px] text-sm text-ink dark:text-slate-200" :title="value">
          {{ value }}
        </div>
      </template>

      <template #row-actions="{ row }">
        <div class="inline-flex items-center gap-1">
          <button v-if="can('api_ayenati_show')" @click="viewLog(row)" class="grid place-items-center w-8 h-8 rounded-lg text-info hover:bg-info/10 transition" title="View Response">
            <i class="ri-eye-line"></i>
          </button>
        </div>
      </template>
    </DataTable>

    <!-- Details Modal -->
    <BaseModal v-model="showModal" title="API Log Details" icon="ri-cloud-line" size="lg">
      <div v-if="activeLog" class="space-y-4">
        
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
          <div>
            <span class="block text-xs font-semibold text-slate-500 mb-1">ID</span>
            <span class="text-sm text-ink dark:text-slate-200">{{ activeLog.id }}</span>
          </div>
          <div>
            <span class="block text-xs font-semibold text-slate-500 mb-1">Date</span>
            <span class="text-sm text-ink dark:text-slate-200">{{ activeLog.created_at }}</span>
          </div>
          <div class="md:col-span-2">
            <span class="block text-xs font-semibold text-slate-500 mb-1">Status</span>
            <StatusBadge :status="activeLog.response_flag === 'success' ? 'active' : 'inactive'">
              {{ activeLog.response_flag }}
            </StatusBadge>
          </div>
          <div class="md:col-span-2">
            <span class="block text-xs font-semibold text-slate-500 mb-1">API URL</span>
            <div class="bg-surface-muted dark:bg-white/5 px-3 py-2 rounded-lg text-sm font-mono text-primary-600 dark:text-primary-400 break-all">
              {{ activeLog.api_url }}
            </div>
          </div>
          <div class="md:col-span-2">
            <span class="block text-xs font-semibold text-slate-500 mb-1">Response Payload</span>
            <div class="bg-slate-900 text-slate-300 p-4 rounded-xl text-xs font-mono overflow-auto max-h-[400px]">
              <pre>{{ formattedResponse }}</pre>
            </div>
          </div>
        </div>

      </div>
      <template #footer>
        <BaseButton variant="light" @click="showModal = false">Close</BaseButton>
      </template>
    </BaseModal>

  </div>
</template>
