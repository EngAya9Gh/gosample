<script setup>
import { ref, computed } from 'vue';
import { router } from '@inertiajs/vue3';
import BaseCard from '../../components/BaseCard.vue';
import BaseModal from '../../components/BaseModal.vue';
import { useToast } from '../../composables/useToast';

const props = defineProps({
  driver: { type: Object, required: true },
  tasks:  { type: Array, default: () => [] },
});

const { push } = useToast();

const localTasks = ref([...props.tasks]);
const isDirty = ref(false);
const saving = ref(false);
const smartSorting = ref(false);
const showSmartSortModal = ref(false);
const dragIndex = ref(null);

const totalEta = computed(() =>
  localTasks.value.reduce((sum, t) => sum + (parseFloat(t.eta) || 0), 0)
);

const totalEtaFormatted = computed(() => {
  const m = Math.round(totalEta.value);
  if (!m) return '—';
  return m < 60 ? `${m} min` : `${Math.floor(m / 60)}h ${m % 60}m`;
});

function onDragStart(e, index) {
  dragIndex.value = index;
  if (e.dataTransfer) e.dataTransfer.effectAllowed = 'move';
}
function onDragEnter(e, toIndex) {
  if (dragIndex.value === null || dragIndex.value === toIndex) return;
  const item = localTasks.value[dragIndex.value];
  localTasks.value.splice(dragIndex.value, 1);
  localTasks.value.splice(toIndex, 0, item);
  dragIndex.value = toIndex;
  isDirty.value = true;
}
function onDrop() { dragIndex.value = null; }
function onDragEnd() { dragIndex.value = null; }

function saveOrder() {
  saving.value = true;
  const order = localTasks.value.map((t, i) => ({ id: t.id, priority: i + 1 }));
  router.post(`/admin/drivers/${props.driver.id}/tasks/reorder`, { order }, {
    onSuccess: () => {
      push({ type: 'success', title: 'Route Saved', message: 'Task order updated successfully.' });
      isDirty.value = false;
      saving.value = false;
    },
    onError: () => {
      push({ type: 'error', title: 'Error', message: 'Failed to update task order.' });
      saving.value = false;
    }
  });
}

function smartSort() { showSmartSortModal.value = true; }

function confirmSmartSort() {
  showSmartSortModal.value = false;
  smartSorting.value = true;
  router.post(`/admin/drivers/${props.driver.id}/tasks/smartSort`, {}, {
    onSuccess: () => {
      push({ type: 'success', title: 'AI Sort Done', message: 'Tasks have been optimally sorted.' });
      smartSorting.value = false;
      isDirty.value = false;
      localTasks.value = [...props.tasks];
    },
    onError: () => {
      push({ type: 'error', title: 'Error', message: 'Smart sort failed.' });
      smartSorting.value = false;
    }
  });
}

function goToTask(id) { router.visit(`/admin/tasks/${id}`); }
</script>

<template>
  <div>
    <!-- ── PAGE HEADER ─────────────────────────────── -->
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 mb-6">
      <div>
        <div class="flex items-center gap-2 text-sm text-slate-500 dark:text-slate-400 mb-1">
          <button @click="router.visit('/admin/drivers')" class="hover:text-[#0ab39c] transition-colors flex items-center gap-1">
            <i class="ri-team-line text-base"></i> Drivers
          </button>
          <i class="ri-arrow-right-s-line"></i>
          <span class="text-slate-700 dark:text-slate-200 font-semibold">{{ driver.name }}</span>
          <i class="ri-arrow-right-s-line"></i>
          <span>Route</span>
        </div>
        <h1 class="text-xl font-bold text-ink dark:text-slate-100">Route Planning</h1>
      </div>

      <div class="flex items-center gap-2">
        <button
          @click="router.visit('/admin/drivers')"
          class="flex items-center gap-1.5 px-3.5 py-2 rounded-xl text-sm font-semibold text-slate-600 dark:text-slate-300 bg-slate-100 dark:bg-white/5 hover:bg-slate-200 dark:hover:bg-white/10 border border-slate-200 dark:border-white/10 transition-all"
        >
          <i class="ri-arrow-left-line"></i> Back
        </button>

        <button
          :disabled="smartSorting"
          @click="smartSort"
          class="flex items-center gap-1.5 px-3.5 py-2 rounded-xl text-sm font-semibold text-white bg-[#0ab39c] hover:bg-[#099c87] disabled:opacity-50 disabled:cursor-not-allowed border border-[#099c87] transition-all shadow-sm"
        >
          <i class="ri-sparkling-line" v-if="!smartSorting"></i>
          <i class="ri-loader-4-line animate-spin" v-else></i>
          {{ smartSorting ? 'Calculating…' : 'AI Sort' }}
        </button>

        <button
          :disabled="!isDirty || saving"
          @click="saveOrder"
          class="flex items-center gap-1.5 px-4 py-2 rounded-xl text-sm font-semibold text-white bg-[#005D69] hover:bg-[#004d57] disabled:opacity-40 disabled:cursor-not-allowed border border-[#004d57] transition-all shadow-sm"
        >
          <i class="ri-save-3-line" v-if="!saving"></i>
          <i class="ri-loader-4-line animate-spin" v-else></i>
          {{ saving ? 'Saving…' : 'Save Route' }}
        </button>
      </div>
    </div>

    <!-- ── SUMMARY STRIP ───────────────────────────── -->
    <div class="grid grid-cols-3 gap-4 mb-6">
      <BaseCard :padded="false">
        <div class="flex items-center gap-3 px-4 py-3">
          <div class="w-9 h-9 rounded-xl bg-gradient-to-br from-[#0ea5a4] to-[#005D69] text-white flex items-center justify-center shrink-0 shadow-sm">
            <i class="ri-map-pin-line text-base"></i>
          </div>
          <div>
            <p class="text-xs text-slate-500 dark:text-slate-400 font-medium">Total Stops</p>
            <p class="text-lg font-bold text-ink dark:text-slate-100 leading-tight">{{ localTasks.length }}</p>
          </div>
        </div>
      </BaseCard>

      <BaseCard :padded="false">
        <div class="flex items-center gap-3 px-4 py-3">
          <div class="w-9 h-9 rounded-xl bg-gradient-to-br from-[#0ea5a4] to-[#005D69] text-white flex items-center justify-center shrink-0 shadow-sm">
            <i class="ri-timer-line text-base"></i>
          </div>
          <div>
            <p class="text-xs text-slate-500 dark:text-slate-400 font-medium">Est. Duration</p>
            <p class="text-lg font-bold text-ink dark:text-slate-100 leading-tight">{{ totalEtaFormatted }}</p>
          </div>
        </div>
      </BaseCard>

      <BaseCard :padded="false">
        <div class="flex items-center gap-3 px-4 py-3">
          <div class="w-9 h-9 rounded-xl bg-gradient-to-br from-[#0ea5a4] to-[#005D69] text-white flex items-center justify-center shrink-0 shadow-sm">
            <i class="ri-steering-2-line text-base"></i>
          </div>
          <div>
            <p class="text-xs text-slate-500 dark:text-slate-400 font-medium">Driver</p>
            <p class="text-sm font-bold text-ink dark:text-slate-100 leading-tight truncate max-w-[120px]">{{ driver.name }}</p>
          </div>
        </div>
      </BaseCard>
    </div>

    <!-- ── TASK LIST ────────────────────────────────── -->
    <BaseCard :padded="false">
      <!-- Card header -->
      <div class="flex items-center justify-between px-5 py-4 border-b border-slate-100 dark:border-white/5">
        <div class="flex items-center gap-2.5">
          <span class="grid place-items-center w-8 h-8 rounded-xl bg-gradient-to-br from-[#0ea5a4] to-[#005D69] text-white shadow-sm">
            <i class="ri-route-line text-base"></i>
          </span>
          <div>
            <h3 class="font-bold text-ink dark:text-slate-100 text-[15px]">Route Order</h3>
            <p class="text-xs text-slate-500 dark:text-slate-400">Drag to reorder · Click to view details</p>
          </div>
        </div>
        <span v-if="isDirty" class="inline-flex items-center gap-1 text-xs font-semibold text-amber-600 dark:text-amber-400 bg-amber-50 dark:bg-amber-500/10 border border-amber-200 dark:border-amber-500/20 rounded-full px-2.5 py-1">
          <i class="ri-edit-line"></i> Unsaved
        </span>
      </div>

      <!-- Tasks -->
      <div v-if="localTasks.length > 0" class="divide-y divide-slate-100 dark:divide-white/5">
        <div
          v-for="(task, index) in localTasks"
          :key="task.id"
          draggable="true"
          @dragstart="onDragStart($event, index)"
          @dragenter.prevent="onDragEnter($event, index)"
          @dragover.prevent
          @drop="onDrop"
          @dragend="onDragEnd"
          @click="goToTask(task.id)"
          :class="[
            dragIndex === index ? 'opacity-40 bg-slate-50 dark:bg-white/3' : 'hover:bg-slate-50 dark:hover:bg-white/3',
            'group flex items-center gap-4 px-5 py-4 cursor-pointer transition-all duration-150'
          ]"
        >
          <!-- Drag handle -->
          <div @click.stop class="flex items-center justify-center text-slate-300 dark:text-slate-600 group-hover:text-[#0ab39c] transition-colors cursor-grab active:cursor-grabbing shrink-0">
            <i class="ri-menu-line text-lg pointer-events-none"></i>
          </div>

          <!-- Priority badge -->
          <div class="w-8 h-8 rounded-xl flex items-center justify-center text-xs font-bold shrink-0 transition-all"
            :class="index === 0
              ? 'bg-[#0ab39c] text-white shadow-sm'
              : 'bg-slate-100 dark:bg-white/5 text-slate-500 dark:text-slate-400 group-hover:bg-[#0ab39c]/10 group-hover:text-[#0ab39c]'"
          >
            {{ index + 1 }}
          </div>

          <!-- Task content -->
          <div class="flex-1 grid grid-cols-1 md:grid-cols-2 gap-3 min-w-0">
            <!-- Locations -->
            <div class="flex flex-col gap-2 min-w-0">
              <div class="flex items-center gap-2 min-w-0">
                <div class="w-5 h-5 rounded-md bg-amber-50 dark:bg-amber-500/10 text-amber-500 flex items-center justify-center shrink-0">
                  <i class="ri-store-2-line text-xs"></i>
                </div>
                <span class="text-xs text-slate-500 dark:text-slate-400 shrink-0">From</span>
                <span class="text-sm font-semibold text-slate-700 dark:text-slate-200 truncate">{{ task.from_location_name || '—' }}</span>
              </div>

              <div class="flex items-center gap-2 min-w-0">
                <div class="w-5 h-5 rounded-md bg-emerald-50 dark:bg-emerald-500/10 text-emerald-500 flex items-center justify-center shrink-0">
                  <i class="ri-microscope-line text-xs"></i>
                </div>
                <span class="text-xs text-slate-500 dark:text-slate-400 shrink-0">To</span>
                <span class="text-sm font-semibold text-slate-700 dark:text-slate-200 truncate">{{ task.to_location_name || '—' }}</span>
              </div>
            </div>

            <!-- Meta -->
            <div class="flex items-center justify-between md:justify-end gap-4">
              <div class="flex items-center gap-1.5 text-xs font-medium text-slate-400 bg-slate-50 dark:bg-white/5 border border-slate-100 dark:border-white/5 rounded-lg px-2.5 py-1.5">
                <i class="ri-hashtag text-slate-300 dark:text-slate-600 text-[11px]"></i>
                <span class="font-mono font-bold text-slate-600 dark:text-slate-300">{{ task.id }}</span>
              </div>

              <div v-if="task.eta" class="flex items-center gap-1 text-xs font-semibold text-[#0ab39c] bg-[#0ab39c]/8 border border-[#0ab39c]/15 rounded-lg px-2.5 py-1.5 dark:bg-[#0ab39c]/5 dark:border-[#0ab39c]/10">
                <i class="ri-timer-line"></i>
                {{ task.eta }} min
              </div>

              <i class="ri-arrow-right-s-line text-slate-300 dark:text-slate-600 group-hover:text-[#0ab39c] group-hover:translate-x-0.5 transition-all text-lg shrink-0"></i>
            </div>
          </div>
        </div>

        <!-- Route footer -->
        <div class="flex items-center gap-3 px-5 py-3 bg-slate-50 dark:bg-white/2">
          <i class="ri-flag-2-line text-[#0ab39c] text-base"></i>
          <span class="text-xs font-semibold text-slate-500 dark:text-slate-400">
            Route ends · {{ localTasks.length }} stop{{ localTasks.length !== 1 ? 's' : '' }}
            <span v-if="totalEta > 0">· ~{{ totalEtaFormatted }}</span>
          </span>
        </div>
      </div>

      <!-- Empty state -->
      <div v-else class="flex flex-col items-center justify-center py-20 text-center">
        <div class="w-14 h-14 rounded-2xl bg-slate-100 dark:bg-white/5 flex items-center justify-center mb-4">
          <i class="ri-route-line text-2xl text-slate-400 dark:text-slate-500"></i>
        </div>
        <h3 class="text-base font-bold text-slate-700 dark:text-slate-200 mb-1">No Tasks Assigned</h3>
        <p class="text-sm text-slate-400 dark:text-slate-500 max-w-xs">
          This driver has no active tasks. Assign tasks to start building a route.
        </p>
      </div>
    </BaseCard>

    <!-- ── SMART SORT MODAL ──────────────────────────── -->
    <BaseModal v-model="showSmartSortModal" title="AI Route Optimization" size="sm">
      <div class="p-6 text-center">
        <div class="w-14 h-14 rounded-2xl bg-gradient-to-br from-[#0ea5a4] to-[#005D69] text-white flex items-center justify-center mx-auto mb-5 shadow-md">
          <i class="ri-sparkling-2-line text-2xl"></i>
        </div>

        <h3 class="text-base font-bold text-ink dark:text-slate-100 mb-2">Optimize This Route?</h3>
        <p class="text-sm text-slate-500 dark:text-slate-400 mb-5 leading-relaxed">
          The AI will analyze all locations and recalculate the most efficient order to minimize total travel time.
        </p>

        <div class="flex items-start gap-2.5 bg-amber-50 dark:bg-amber-500/8 border border-amber-200/60 dark:border-amber-500/15 rounded-xl p-3.5 mb-6 text-left">
          <i class="ri-information-line text-amber-500 shrink-0 mt-0.5"></i>
          <p class="text-xs text-amber-700 dark:text-amber-400 font-medium leading-relaxed">
            This will overwrite your current manual order and cannot be undone.
          </p>
        </div>

        <div class="flex gap-3">
          <button
            @click="showSmartSortModal = false"
            class="flex-1 py-2.5 rounded-xl text-sm font-semibold bg-slate-100 dark:bg-white/5 hover:bg-slate-200 dark:hover:bg-white/10 text-slate-700 dark:text-slate-300 border border-slate-200 dark:border-white/10 transition-colors"
          >
            Cancel
          </button>
          <button
            @click="confirmSmartSort"
            class="flex-1 py-2.5 rounded-xl text-sm font-semibold text-white bg-[#0ab39c] hover:bg-[#099c87] border border-[#099c87] transition-colors shadow-sm flex items-center justify-center gap-1.5"
          >
            <i class="ri-sparkling-line"></i> Optimize Route
          </button>
        </div>
      </div>
    </BaseModal>
  </div>
</template>

<style scoped>
div[draggable] { user-select: none; }
</style>
