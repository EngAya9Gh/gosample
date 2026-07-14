<script setup>
import { ref, computed, watch } from 'vue';
import { router } from '@inertiajs/vue3';
import debounce from 'lodash/debounce';

import Breadcrumb from '../../components/Breadcrumb.vue';
import BaseButton from '../../components/BaseButton.vue';
import BaseModal from '../../components/BaseModal.vue';

const props = defineProps({
  logs:         Object, // paginated
  descriptions: Array,
  filters:      Object,
});

// ─── Search state ───────────────────────────────────────────────────────────
const search      = ref(props.filters?.search      ?? '');
const description = ref(props.filters?.description ?? '');
const loading     = ref(false);

const applyFilters = debounce(() => {
  loading.value = true;
  router.get('/admin/audit-logs', {
    search:      search.value      || undefined,
    description: description.value || undefined,
  }, {
    preserveScroll: true,
    preserveState:  true,
    replace:        true,
    onFinish:       () => { loading.value = false; },
  });
}, 350);

watch([search, description], () => applyFilters());

const doReset = () => {
  search.value      = '';
  description.value = '';
  applyFilters();
};

// ─── Log detail modal ────────────────────────────────────────────────────────
const selectedLog  = ref(null);
const detailOpen   = ref(false);

const openDetail = (log) => {
  selectedLog.value = log;
  detailOpen.value  = true;
};

// ─── Helpers ─────────────────────────────────────────────────────────────────
const descriptionMeta = {
  created: { icon: 'ri-add-circle-line',   color: '#0ab39c', bg: 'rgba(10,179,156,.12)' },
  updated: { icon: 'ri-edit-2-line',        color: '#299cdb', bg: 'rgba(41,156,219,.12)' },
  deleted: { icon: 'ri-delete-bin-line',    color: '#dc2626', bg: 'rgba(220,38,38,.10)' },
  login:   { icon: 'ri-login-box-line',     color: '#BD6BA7', bg: 'rgba(189,107,167,.12)' },
  logout:  { icon: 'ri-logout-box-r-line',  color: '#f7b84b', bg: 'rgba(247,184,75,.12)' },
};

const getDescMeta = (desc) => {
  const lower = (desc || '').toLowerCase();
  for (const [key, val] of Object.entries(descriptionMeta)) {
    if (lower.includes(key)) return val;
  }
  return { icon: 'ri-history-line', color: '#0d9488', bg: 'rgba(13,148,136,.12)' };
};

const shortModel = (type) => {
  if (!type) return '—';
  const parts = type.split('\\');
  return parts[parts.length - 1];
};

const formatDate = (d) => {
  if (!d) return '—';
  return new Date(d).toLocaleString('en-GB', {
    day: '2-digit', month: 'short', year: 'numeric',
    hour: '2-digit', minute: '2-digit',
  });
};

const formatProperties = (props) => {
  if (!props) return null;
  if (typeof props === 'string') {
    try { return JSON.parse(props); } catch { return props; }
  }
  return props;
};
</script>

<template>
  <div class="space-y-5">
    <!-- Header -->
    <Breadcrumb title="Audit Logs" :trail="[{ label: 'Audit Logs' }]" />

    <!-- Stats strip -->
    <div class="flex flex-wrap gap-4">
      <div class="inline-flex items-center gap-3 px-5 py-3.5 bg-white dark:bg-slate-900 border border-slate-200 dark:border-white/10 rounded-2xl shadow-sm">
        <span class="w-9 h-9 rounded-xl bg-gradient-to-br from-[#0d9488] to-[#005D69] text-white flex items-center justify-center shrink-0">
          <i class="ri-history-line text-lg"></i>
        </span>
        <div>
          <p class="text-[11px] text-slate-500 dark:text-slate-400 font-bold uppercase tracking-wider">Total Entries</p>
          <p class="text-base font-extrabold text-ink dark:text-white mt-0.5">{{ logs.total?.toLocaleString('en-US') }}</p>
        </div>
      </div>
      <div class="inline-flex items-center gap-3 px-5 py-3.5 bg-white dark:bg-slate-900 border border-slate-200 dark:border-white/10 rounded-2xl shadow-sm">
        <span class="w-9 h-9 rounded-xl bg-gradient-to-br from-[#BD6BA7] to-[#a85390] text-white flex items-center justify-center shrink-0">
          <i class="ri-pages-line text-lg"></i>
        </span>
        <div>
          <p class="text-[11px] text-slate-500 dark:text-slate-400 font-bold uppercase tracking-wider">Showing Page</p>
          <p class="text-base font-extrabold text-ink dark:text-white mt-0.5">{{ logs.current_page }} / {{ logs.last_page }}</p>
        </div>
      </div>
    </div>

    <!-- Filter bar -->
    <div class="flex flex-wrap items-center gap-3 bg-white dark:bg-slate-900 border border-slate-200 dark:border-white/10 rounded-2xl p-4 shadow-sm">
      <!-- Search -->
      <div class="relative flex-1 min-w-[220px]">
        <i class="ri-search-line absolute top-1/2 -translate-y-1/2 left-3.5 text-slate-400 text-[15px]"></i>
        <input
          v-model="search"
          type="text"
          placeholder="Search description, host, user…"
          class="w-full h-11 pl-10 pr-4 rounded-xl bg-slate-50 dark:bg-white/5 border border-transparent focus:border-primary-500 text-[13px] text-ink dark:text-slate-200 focus:outline-none focus:ring-2 focus:ring-primary-500/20 transition-all"
        />
      </div>

      <!-- Description filter -->
      <select
        v-model="description"
        class="h-11 px-3 pr-8 rounded-xl bg-slate-50 dark:bg-white/5 border border-transparent focus:border-primary-500 text-[13px] text-slate-600 dark:text-slate-300 focus:outline-none focus:ring-2 focus:ring-primary-500/20 transition-all min-w-[180px]"
      >
        <option value="">All Actions</option>
        <option v-for="d in descriptions" :key="d" :value="d">{{ d }}</option>
      </select>

      <BaseButton variant="white" icon="ri-refresh-line" @click="doReset">Reset</BaseButton>
    </div>

    <!-- Table -->
    <div class="bg-white dark:bg-slate-900 rounded-2xl border border-slate-200 dark:border-white/10 shadow-sm overflow-hidden">
      <!-- Loading overlay -->
      <div v-if="loading" class="flex items-center justify-center py-16">
        <i class="ri-loader-4-line text-3xl text-primary-500 animate-spin"></i>
      </div>

      <template v-else>
        <!-- Table header -->
        <div class="grid grid-cols-[40px_1fr_140px_100px_130px_110px] gap-4 px-5 py-3 border-b border-slate-100 dark:border-white/5 bg-slate-50/60 dark:bg-slate-800/40 text-[11px] font-bold uppercase tracking-wider text-slate-500 dark:text-slate-400">
          <div>#</div>
          <div>Description</div>
          <div>Model</div>
          <div>Subject ID</div>
          <div>User ID</div>
          <div>Time</div>
        </div>

        <!-- Rows -->
        <div v-if="logs.data?.length">
          <div
            v-for="log in logs.data"
            :key="log.id"
            @click="openDetail(log)"
            class="grid grid-cols-[40px_1fr_140px_100px_130px_110px] gap-4 px-5 py-3.5 border-b border-slate-50 dark:border-white/[0.04] last:border-0 cursor-pointer hover:bg-slate-50/80 dark:hover:bg-white/[0.03] transition-colors items-center group"
          >
            <!-- Icon -->
            <div
              class="w-8 h-8 rounded-lg flex items-center justify-center shrink-0"
              :style="{ background: getDescMeta(log.description).bg }"
            >
              <i :class="getDescMeta(log.description).icon" class="text-sm" :style="{ color: getDescMeta(log.description).color }"></i>
            </div>

            <!-- Description -->
            <div class="min-w-0">
              <p class="text-[13px] font-semibold text-ink dark:text-slate-100 truncate">{{ log.description || '—' }}</p>
              <p class="text-[11px] text-slate-400 mt-0.5 truncate">{{ log.host || 'unknown host' }}</p>
            </div>

            <!-- Model type -->
            <div class="text-[12px] text-slate-500 dark:text-slate-400 font-medium truncate">
              <span class="px-2 py-0.5 bg-slate-100 dark:bg-white/10 rounded-md text-[11px]">{{ shortModel(log.subject_type) }}</span>
            </div>

            <!-- Subject ID -->
            <div class="text-[12px] font-mono text-slate-500 dark:text-slate-400">{{ log.subject_id ?? '—' }}</div>

            <!-- User ID -->
            <div class="text-[12px] font-mono text-slate-500 dark:text-slate-400">
              <span v-if="log.user_id" class="inline-flex items-center gap-1.5">
                <i class="ri-user-3-line text-[#BD6BA7]"></i>{{ log.user_id }}
              </span>
              <span v-else class="text-slate-300 dark:text-slate-600">System</span>
            </div>

            <!-- Time -->
            <div class="text-[11.5px] text-slate-400 dark:text-slate-500 whitespace-nowrap">{{ formatDate(log.created_at) }}</div>
          </div>
        </div>

        <!-- Empty -->
        <div v-else class="text-center py-16">
          <i class="ri-file-list-3-line text-4xl text-slate-300 dark:text-slate-600 mb-3 block"></i>
          <p class="text-sm font-bold text-ink dark:text-white mb-1">No logs found</p>
          <p class="text-xs text-slate-400">Try clearing the filters.</p>
        </div>
      </template>
    </div>

    <!-- Pagination -->
    <div v-if="logs.last_page > 1" class="flex items-center justify-between">
      <p class="text-[12px] text-slate-500 dark:text-slate-400">
        Showing <strong>{{ logs.from }}–{{ logs.to }}</strong> of <strong>{{ logs.total }}</strong> entries
      </p>
      <div class="flex items-center gap-1">
        <template v-for="link in logs.links" :key="link.label">
          <button
            v-if="link.url"
            @click="router.get(link.url, {}, { preserveScroll: true })"
            :class="[
              'h-8 min-w-[32px] px-2.5 rounded-lg text-[12px] font-semibold transition-colors',
              link.active
                ? 'bg-gradient-to-br from-[#0d9488] to-[#005D69] text-white shadow-sm'
                : 'bg-white dark:bg-slate-900 border border-slate-200 dark:border-white/10 text-slate-600 dark:text-slate-400 hover:bg-slate-50 dark:hover:bg-white/5'
            ]"
            v-html="link.label"
          />
          <span v-else class="h-8 min-w-[32px] px-2.5 rounded-lg text-[12px] font-semibold text-slate-300 dark:text-slate-600 flex items-center justify-center" v-html="link.label" />
        </template>
      </div>
    </div>

    <!-- Detail Modal -->
    <BaseModal v-model="detailOpen" title="Log Entry Details" icon="ri-history-line" size="lg" tone="primary" @close="detailOpen = false">
      <div v-if="selectedLog" class="space-y-4">
        <!-- Meta grid -->
        <div class="grid grid-cols-2 gap-3">
          <div class="bg-slate-50 dark:bg-white/5 rounded-xl p-3">
            <p class="text-[10px] font-bold uppercase tracking-widest text-slate-400 mb-1">ID</p>
            <p class="text-sm font-mono font-bold text-ink dark:text-white">{{ selectedLog.id }}</p>
          </div>
          <div class="bg-slate-50 dark:bg-white/5 rounded-xl p-3">
            <p class="text-[10px] font-bold uppercase tracking-widest text-slate-400 mb-1">Description</p>
            <p class="text-sm font-semibold text-ink dark:text-white">{{ selectedLog.description }}</p>
          </div>
          <div class="bg-slate-50 dark:bg-white/5 rounded-xl p-3">
            <p class="text-[10px] font-bold uppercase tracking-widest text-slate-400 mb-1">Model</p>
            <p class="text-sm font-mono text-slate-600 dark:text-slate-300">{{ selectedLog.subject_type || '—' }}</p>
          </div>
          <div class="bg-slate-50 dark:bg-white/5 rounded-xl p-3">
            <p class="text-[10px] font-bold uppercase tracking-widest text-slate-400 mb-1">Subject ID</p>
            <p class="text-sm font-mono text-ink dark:text-white">{{ selectedLog.subject_id ?? '—' }}</p>
          </div>
          <div class="bg-slate-50 dark:bg-white/5 rounded-xl p-3">
            <p class="text-[10px] font-bold uppercase tracking-widest text-slate-400 mb-1">User ID</p>
            <p class="text-sm font-mono text-ink dark:text-white">{{ selectedLog.user_id ?? 'System' }}</p>
          </div>
          <div class="bg-slate-50 dark:bg-white/5 rounded-xl p-3">
            <p class="text-[10px] font-bold uppercase tracking-widest text-slate-400 mb-1">Host</p>
            <p class="text-sm font-mono text-slate-600 dark:text-slate-300">{{ selectedLog.host || '—' }}</p>
          </div>
        </div>

        <!-- Timestamp -->
        <div class="flex items-center gap-2 text-[12px] text-slate-500 dark:text-slate-400">
          <i class="ri-time-line"></i>
          {{ formatDate(selectedLog.created_at) }}
        </div>

        <!-- Properties payload -->
        <div v-if="selectedLog.properties && Object.keys(formatProperties(selectedLog.properties) || {}).length" class="space-y-2">
          <p class="text-[11px] font-bold uppercase tracking-wider text-slate-500 dark:text-slate-400">Changed Properties</p>
          <div class="bg-slate-900 dark:bg-black/40 rounded-xl p-4 overflow-x-auto max-h-64">
            <pre class="text-[12px] text-emerald-300 leading-relaxed font-mono">{{ JSON.stringify(formatProperties(selectedLog.properties), null, 2) }}</pre>
          </div>
        </div>
      </div>
    </BaseModal>
  </div>
</template>
