<script setup>
import { ref } from 'vue';
import { router } from '@inertiajs/vue3';
import Breadcrumb from '../../components/Breadcrumb.vue';
import BaseButton from '../../components/BaseButton.vue';
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

// Drag and drop state
const dragIndex = ref(null);

function onDragStart(e, index) {
  dragIndex.value = index;
  if (e.dataTransfer) {
    e.dataTransfer.effectAllowed = 'move';
    e.dataTransfer.dropEffect = 'move';
  }
}

function onDragEnter(e, toIndex) {
  if (dragIndex.value === null || dragIndex.value === toIndex) return;
  const movedItem = localTasks.value[dragIndex.value];
  localTasks.value.splice(dragIndex.value, 1);
  localTasks.value.splice(toIndex, 0, movedItem);
  dragIndex.value = toIndex;
  isDirty.value = true;
}

function onDrop() {
  dragIndex.value = null;
}

function onDragEnd() {
  dragIndex.value = null;
}

function saveOrder() {
  saving.value = true;
  const order = localTasks.value.map((task, index) => ({
    id: task.id,
    priority: index + 1,
  }));

  router.post(`/app/admin/drivers/${props.driver.id}/tasks/reorder`, { order }, {
    onSuccess: () => {
      push({ type: 'success', title: 'Saved', message: 'Task order updated successfully.' });
      isDirty.value = false;
      saving.value = false;
    },
    onError: () => {
      push({ type: 'error', title: 'Error', message: 'Failed to update task order.' });
      saving.value = false;
    }
  });
}

function smartSort() {
  if (!confirm('Are you sure you want to smartly reorder these tasks? This will overwrite your current order.')) {
    return;
  }
  
  smartSorting.value = true;
  router.post(`/app/admin/drivers/${props.driver.id}/tasks/smartSort`, {}, {
    onSuccess: () => {
      push({ type: 'success', title: 'Smart Sort', message: 'Tasks have been sorted by AI.' });
      smartSorting.value = false;
      isDirty.value = false;
      // Inertia automatically updates the props.tasks array from the server response
      localTasks.value = [...props.tasks]; 
    },
    onError: () => {
      push({ type: 'error', title: 'Error', message: 'Smart sort failed.' });
      smartSorting.value = false;
    }
  });
}
</script>

<template>
  <div>
    <Breadcrumb :title="`Driver Route: ${driver.name}`" :trail="[{ label: 'Drivers', href: '/app/admin/drivers' }, { label: 'Route' }]">
      <template #actions>
        <BaseButton variant="light" icon="ri-arrow-go-back-line" @click="() => router.visit('/app/admin/drivers')">Back</BaseButton>
        <BaseButton :disabled="smartSorting" variant="light" class="text-info border-info/30 hover:bg-info/10" icon="ri-magic-line" @click="smartSort">
          {{ smartSorting ? 'Calculating...' : 'Smart Sort (AI)' }}
        </BaseButton>
        <BaseButton :disabled="!isDirty || saving" variant="primary" icon="ri-save-line" @click="saveOrder">
          {{ saving ? 'Saving...' : 'Save Order' }}
        </BaseButton>
      </template>
    </Breadcrumb>

    <div class="max-w-4xl mx-auto mt-lg">
      <div class="bg-surface rounded-xl shadow-sm border border-outline-variant overflow-hidden">
        <div class="px-lg py-md border-b border-outline-variant flex items-center justify-between bg-surface-container-lowest">
          <h2 class="font-headline-sm text-on-surface">Tasks assigned for sorting</h2>
          <span class="text-body-sm text-on-surface-variant">{{ localTasks.length }} Tasks</span>
        </div>

        <div class="p-md bg-surface-container-lowest min-h-[300px]">
          <ul v-if="localTasks.length > 0" class="space-y-sm">
            <li v-for="(task, index) in localTasks" :key="task.id"
                draggable="true"
                @dragstart="onDragStart($event, index)"
                @dragenter.prevent="onDragEnter($event, index)"
                @dragover.prevent
                @drop="onDrop"
                @dragend="onDragEnd"
                :class="{'opacity-40 border-dashed bg-surface-variant': dragIndex === index}"
                class="bg-surface p-md rounded-lg border border-outline-variant shadow-sm transition-all flex items-center gap-md group">
              
              <div class="text-on-surface-variant/50 group-hover:text-primary transition-colors cursor-grab active:cursor-grabbing p-2 -ml-2 rounded hover:bg-surface-variant">
                <i class="ri-drag-move-2-line text-2xl"></i>
              </div>

              <div class="flex-1 grid grid-cols-1 md:grid-cols-12 gap-4 items-center">
                <div class="md:col-span-2">
                  <p class="text-[10px] uppercase tracking-wider text-on-surface-variant mb-1 font-semibold">Task ID</p>
                  <h5 class="font-headline-sm text-primary">#{{ task.id }}</h5>
                </div>
                
                <div class="md:col-span-6 flex flex-col gap-2">
                  <div class="flex items-start gap-2">
                    <i class="ri-map-pin-line text-error mt-0.5"></i>
                    <span class="text-body-sm text-on-surface">From: <span class="font-medium">{{ task.from_location_name || 'Not specified' }}</span></span>
                  </div>
                  <div class="flex items-start gap-2">
                    <i class="ri-map-pin-fill text-success mt-0.5"></i>
                    <span class="text-body-sm text-on-surface">To: <span class="font-medium">{{ task.to_location_name || 'Not specified' }}</span></span>
                  </div>
                </div>

                <div class="md:col-span-3 flex justify-end">
                  <span v-if="task.eta" class="inline-flex items-center px-2.5 py-1 rounded-md text-xs font-medium bg-primary/10 text-primary">
                    <i class="ri-time-line mr-1.5"></i>
                    {{ task.eta }} min
                  </span>
                </div>

                <div class="md:col-span-1 flex justify-end">
                  <div class="w-8 h-8 rounded-full border border-outline-variant flex items-center justify-center text-body-xs font-semibold text-on-surface-variant bg-surface-variant">
                    {{ index + 1 }}
                  </div>
                </div>
              </div>
            </li>
          </ul>

          <div v-else class="text-center py-20">
            <div class="w-16 h-16 rounded-full bg-primary/10 text-primary flex items-center justify-center mx-auto mb-4">
              <i class="ri-car-line text-3xl"></i>
            </div>
            <h3 class="text-headline-sm text-on-surface mb-2">No assigned tasks</h3>
            <p class="text-body-md text-on-surface-variant">This driver currently has no active tasks to sort.</p>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<style scoped>
/* Prevent text selection while dragging */
li {
  user-select: none;
}
</style>
