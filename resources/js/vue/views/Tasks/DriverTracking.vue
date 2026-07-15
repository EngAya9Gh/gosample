<script setup>
/**
 * views/Tasks/DriverTracking.vue — SPA rebuild of /admin/driver-tracking.
 * Logic mirrors the classic clientDashboard 1:1 (server side already filters:
 * TODAY's tasks, statuses not CLOSED/NO_SAMPLES, client-scoped unless admin,
 * ordered by route_order → poririty). This page renders one card per driver
 * with their ordered route steps:
 *   - completed step (COLLECTED/CLOSED): green check, struck-through client
 *   - next step (first non-completed after a completed/start): highlighted pin
 *   - later steps: numbered
 * plus the same "N Active" counter (tasks not COLLECTED/CLOSED), the instant
 * driver-name search and the Refresh action. Design follows the Tasks page.
 */
import { ref, computed } from 'vue';
import { router } from '@inertiajs/vue3';
import Breadcrumb from '../../components/Breadcrumb.vue';
import BaseButton from '../../components/BaseButton.vue';
import BaseAvatar from '../../components/BaseAvatar.vue';
import StatusBadge from '../../components/StatusBadge.vue';
import FormInput from '../../components/FormInput.vue';

const props = defineProps({
  drivers: { type: Array, default: () => [] },
});

/* ---------- instant search (client-side, like the classic page) ---------- */
const search = ref('');
const filteredDrivers = computed(() => {
  const q = search.value.trim().toLowerCase();
  if (!q) return props.drivers;
  return props.drivers.filter((d) => (d.name || '').toLowerCase().includes(q));
});

/* ---------- refresh (Inertia partial reload) ---------- */
const refreshing = ref(false);
function refresh() {
  refreshing.value = true;
  router.reload({ only: ['drivers'], onFinish: () => { refreshing.value = false; } });
}

/* ---------- classic step logic 1:1 ---------- */
const isCompleted = (t) => ['COLLECTED', 'CLOSED'].includes(t.status);
function isNext(tasks, index) {
  const t = tasks[index];
  if (isCompleted(t)) return false;
  return index === 0 || isCompleted(tasks[index - 1]);
}
const activeCount = (d) => (d.tasks || []).filter((t) => !isCompleted(t)).length;
const totalActive = computed(() => props.drivers.reduce((n, d) => n + activeCount(d), 0));
</script>

<template>
  <div>
    <Breadcrumb title="Driver Tracking" :trail="[{ label: 'Tasks' }, { label: 'Driver Tracking' }]">
      <template #actions>
        <BaseButton variant="light" icon="ri-refresh-line" :loading="refreshing" @click="refresh">Refresh</BaseButton>
      </template>
    </Breadcrumb>

    <!-- toolbar: instant driver search + live totals -->
    <div class="flex flex-col sm:flex-row sm:items-center gap-3 mb-5 bg-surface dark:bg-surface-dark-card p-3 rounded-xl border border-slate-100 dark:border-white/5 shadow-sm">
      <div class="w-full sm:max-w-xs">
        <FormInput v-model="search" placeholder="بحث فوري باسم السائق…" icon="ri-search-line" />
      </div>
      <div class="flex items-center gap-2 sm:ms-auto text-[12px]">
        <span class="inline-flex items-center gap-1.5 ps-2 pe-2.5 h-7 rounded-full border border-slate-200 dark:border-white/10 text-slate-500 dark:text-slate-400 font-bold">
          <i class="ri-steering-2-line"></i>{{ filteredDrivers.length }} drivers
        </span>
        <span class="inline-flex items-center gap-1.5 ps-2 pe-2.5 h-7 rounded-full border font-bold bg-warning/10 border-warning/40 text-amber-600 dark:text-amber-400">
          <span class="w-1.5 h-1.5 rounded-full bg-warning animate-pulse-ring"></span>{{ totalActive }} active tasks
        </span>
      </div>
    </div>

    <!-- driver cards grid -->
    <div v-if="filteredDrivers.length" class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-5">
      <div v-for="d in filteredDrivers" :key="d.id"
        class="bg-surface dark:bg-surface-dark-card rounded-xl border border-slate-100 dark:border-white/5 shadow-sm flex flex-col overflow-hidden">
        <!-- card header: avatar + name + active counter -->
        <div class="flex items-center gap-3 px-4 py-3.5 border-b border-slate-100 dark:border-white/5">
          <BaseAvatar :name="d.name" :size="34" />
          <div class="flex-1 min-w-0">
            <p class="text-[13.5px] font-bold text-ink dark:text-slate-100 truncate">{{ d.name }}</p>
          </div>
          <span class="inline-flex items-center gap-1.5 ps-2 pe-2.5 h-6 rounded-full text-[11px] font-bold whitespace-nowrap"
            :class="activeCount(d) > 0 ? 'bg-warning/15 text-amber-600 dark:text-amber-400' : 'bg-success/10 text-success'">
            <span class="w-1.5 h-1.5 rounded-full" :class="activeCount(d) > 0 ? 'bg-warning animate-pulse-ring' : 'bg-success'"></span>
            {{ activeCount(d) }} Active
          </span>
        </div>

        <!-- route steps (scrollable, like the classic 310px list) -->
        <div class="max-h-[320px] overflow-y-auto divide-y divide-dashed divide-slate-100 dark:divide-white/5">
          <div v-for="(t, i) in d.tasks" :key="t.id" class="flex items-start gap-3 px-4 py-3">
            <!-- step marker: completed ✓ / next pin / numbered -->
            <div class="shrink-0 mt-0.5">
              <div v-if="isCompleted(t)" class="w-7 h-7 rounded-full bg-success/10 text-success grid place-items-center text-[15px]">
                <i class="ri-checkbox-circle-fill"></i>
              </div>
              <div v-else-if="isNext(d.tasks, i)" class="w-7 h-7 rounded-full bg-primary-600 text-white grid place-items-center text-[14px] shadow-sm">
                <i class="ri-map-pin-time-fill"></i>
              </div>
              <div v-else class="w-7 h-7 rounded-full bg-slate-100 dark:bg-white/10 text-slate-500 dark:text-slate-400 grid place-items-center text-[12px] font-bold">
                {{ i + 1 }}
              </div>
            </div>

            <div class="flex-1 min-w-0">
              <div class="flex items-start justify-between gap-2">
                <p class="text-[12.5px] font-bold leading-snug"
                  :class="isCompleted(t) ? 'text-slate-400 line-through' : 'text-ink dark:text-slate-100'">
                  {{ t.client_name || 'Unknown Client' }}
                </p>
                <StatusBadge :status="t.status" class="shrink-0" />
              </div>

              <p class="text-[11.5px] text-slate-500 dark:text-slate-400 mt-1.5 leading-snug">
                <i class="ri-map-pin-user-line text-red-500 me-1"></i>
                <span class="font-semibold">من:</span> {{ t.from_name || 'غير محدد' }}
              </p>
              <p class="text-[11.5px] text-slate-500 dark:text-slate-400 mt-1 leading-snug">
                <i class="ri-map-pin-fill text-green-500 me-1"></i>
                <span class="font-semibold">إلى:</span> {{ t.to_name || 'غير محدد' }}
              </p>

              <div class="flex flex-wrap items-center gap-x-3 gap-y-1 mt-2 text-[11px] text-slate-400 dark:text-slate-500">
                <span class="whitespace-nowrap"><i class="ri-hashtag me-0.5"></i>رقم: <span class="font-bold text-[#0ab39c]">{{ t.id }}</span></span>
                <span v-if="t.pickup_time" class="whitespace-nowrap" dir="ltr"><i class="ri-calendar-event-line me-0.5"></i>{{ t.pickup_time }}</span>
              </div>
              <p v-if="t.estimated_arrival_time" class="text-[11px] mt-1 whitespace-nowrap"
                :class="isNext(d.tasks, i) ? 'text-primary-600 dark:text-primary-300 font-semibold' : 'text-slate-400'">
                <i class="ri-time-line me-0.5"></i>ETA: <span dir="ltr">{{ t.estimated_arrival_time }}</span>
              </p>
            </div>
          </div>

          <div v-if="!d.tasks?.length" class="py-8 text-center text-slate-400 text-sm">
            <i class="ri-checkbox-circle-line text-2xl block mb-1.5"></i>
            No tasks assigned
          </div>
        </div>
      </div>
    </div>

    <!-- global empty state -->
    <div v-else class="bg-surface dark:bg-surface-dark-card rounded-xl border border-slate-100 dark:border-white/5 shadow-sm py-16 flex flex-col items-center justify-center text-center">
      <div class="w-16 h-16 bg-slate-100 dark:bg-surface-dark-solid rounded-full flex items-center justify-center mb-4">
        <i class="ri-car-line text-2xl text-slate-400"></i>
      </div>
      <h3 class="text-sm font-semibold text-ink dark:text-white mb-1">
        {{ search ? 'No drivers match your search' : 'No drivers with active tasks found' }}
      </h3>
      <p class="text-sm text-slate-500 max-w-sm">
        {{ search ? 'Try a different driver name.' : "Drivers appear here once they have tasks scheduled for today." }}
      </p>
    </div>
  </div>
</template>
