<script setup>
import { ref, computed } from 'vue';
import BaseModal from '../../components/BaseModal.vue';

const props = defineProps({
  modelValue: Boolean,
  driver: Object
});
const emit = defineEmits(['update:modelValue']);

const activeTab = ref('driver'); // 'driver', 'tasks', 'car'

// Dummy data for visual design based on the user's images
const tasks = [
  { id: 4821, client: 'GeneCare', status: 'In Container', statusColor: 'bg-[#ffedd5] text-[#c2410c] dark:bg-orange-500/20 dark:text-orange-400', dot: 'bg-[#f97316]' },
  { id: 4806, client: 'BioRiyadh', status: 'Closed', statusColor: 'bg-[#dcfce7] text-[#15803d] dark:bg-green-500/20 dark:text-green-400', dot: 'bg-[#22c55e]' },
  { id: 4799, client: 'Al-Mukhtabar', status: 'Collected', statusColor: 'bg-[#e0f2fe] text-[#0369a1] dark:bg-blue-500/20 dark:text-blue-400', dot: 'bg-[#0ea5e9]' },
];

const initials = computed(() => {
  if (!props.driver?.name) return 'KM';
  return props.driver.name.split(' ').map(n => n[0]).join('').substring(0, 2).toUpperCase();
});

function close() {
  emit('update:modelValue', false);
}
</script>

<template>
  <BaseModal :model-value="modelValue" @update:modelValue="e => emit('update:modelValue', e)" size="md">
    <!-- Header Section (Breaks out of BaseModal's p-5 using negative margins) -->
    <div class="-m-5 bg-[#0d9488] px-6 py-8 rounded-t-[15px] relative overflow-hidden shadow-sm">
      <!-- Close button overlay -->
      <button @click="close" class="absolute top-4 left-4 w-8 h-8 rounded-full bg-black/10 hover:bg-black/20 flex items-center justify-center text-white transition-colors">
        <i class="ri-close-line text-lg"></i>
      </button>

      <!-- Avatar & Info -->
      <div class="flex items-center gap-4 mt-4">
        <div class="w-[72px] h-[72px] rounded-full bg-white/20 flex items-center justify-center text-white font-black text-[28px] shrink-0 border-2 border-white/20 backdrop-blur-md shadow-inner">
          {{ initials }}
        </div>
        <div>
          <h2 class="text-[22px] font-black text-white mb-1 tracking-wide">{{ driver?.name || 'Khalid Mansour' }}</h2>
          <div class="flex items-center text-teal-50 text-[13px] font-medium">
            <span class="w-2 h-2 rounded-full bg-[#4ade80] mr-2 shadow-[0_0_8px_rgba(74,222,128,0.6)]"></span>
            On route · {{ driver?.tasks_count || 6 }} active tasks
          </div>
        </div>
      </div>
    </div>

    <!-- Body Content -->
    <div class="mt-8 px-1 pb-2">
      <!-- Tabs -->
      <div class="flex bg-slate-50 dark:bg-white/5 p-1.5 rounded-[14px] mb-8 border border-slate-100 dark:border-white/5">
        <button 
          v-for="tab in ['driver', 'tasks', 'car']" 
          :key="tab"
          @click="activeTab = tab"
          class="flex-1 py-2.5 text-[15px] font-bold rounded-xl transition-all capitalize"
          :class="activeTab === tab ? 'bg-white text-[#0d9488] shadow-[0_1px_3px_rgba(0,0,0,0.1)] dark:bg-white/10 dark:text-white' : 'text-slate-500 hover:text-slate-700 dark:text-slate-400 dark:hover:text-slate-200'"
        >
          {{ tab }}
        </button>
      </div>

      <!-- Tab Panels -->
      <div v-if="activeTab === 'driver'" class="grid grid-cols-2 gap-y-8 gap-x-4">
        <div>
          <p class="text-[13.5px] text-slate-500 mb-1.5 font-medium">Mobile</p>
          <p class="font-black text-slate-800 dark:text-white text-[16px]">{{ driver?.mobile || '+966 50 412 7788' }}</p>
        </div>
        <div>
          <p class="text-[13.5px] text-slate-500 mb-1.5 font-medium">Email</p>
          <p class="font-black text-slate-800 dark:text-white text-[16px]">{{ driver?.email || driver?.username || 'khalid@mtc.sa' }}</p>
        </div>
        <div>
          <p class="text-[13.5px] text-slate-500 mb-1.5 font-medium">Plate</p>
          <p class="font-black text-slate-800 dark:text-white text-[16px]">{{ driver?.plate || 'RUH 4821' }}</p>
        </div>
        <div>
          <p class="text-[13.5px] text-slate-500 mb-1.5 font-medium">IMEI</p>
          <p class="font-black text-slate-800 dark:text-white text-[16px]">{{ driver?.imei || '8675 3091 2244' }}</p>
        </div>
      </div>

      <div v-else-if="activeTab === 'tasks'" class="space-y-3.5">
        <div v-for="t in tasks" :key="t.id" class="flex items-center justify-between px-4 py-3.5 rounded-[14px] bg-slate-50/70 dark:bg-surface-dark-card transition-all hover:bg-slate-100 hover:shadow-[0_2px_8px_rgba(0,0,0,0.04)]">
          <div class="font-black text-[15px] text-slate-800 dark:text-white tracking-wide">#{{ t.id }} · {{ t.client }}</div>
          <div class="px-3.5 py-1.5 rounded-full text-[13px] font-black flex items-center gap-2" :class="t.statusColor">
            <span class="w-2 h-2 rounded-full" :class="t.dot"></span>
            {{ t.status }}
          </div>
        </div>
      </div>

      <div v-else-if="activeTab === 'car'" class="space-y-6">
        <div class="grid grid-cols-2 gap-4">
          <div>
            <p class="text-[13.5px] text-slate-500 mb-1.5 font-medium">Model</p>
            <p class="font-black text-slate-800 dark:text-white text-[16px]">Toyota Hiace 2023</p>
          </div>
          <div>
            <p class="text-[13.5px] text-slate-500 mb-1.5 font-medium">Containers</p>
            <p class="font-black text-slate-800 dark:text-white text-[16px]">3 · Room / Refrig / Frozen</p>
          </div>
        </div>
        <div class="grid grid-cols-2 gap-3.5 mt-2">
          <div class="p-5 rounded-[16px] bg-[#f0fdf4] border border-[#dcfce7] dark:bg-green-500/10 dark:border-green-500/20">
            <p class="text-[13px] text-slate-500 font-medium mb-1.5">Frozen</p>
            <p class="text-[22px] font-black text-[#10b981] tracking-tight">-18.2°C</p>
          </div>
          <div class="p-5 rounded-[16px] bg-[#fffbeb] border border-[#fef3c7] dark:bg-orange-500/10 dark:border-orange-500/20">
            <p class="text-[13px] text-slate-500 font-medium mb-1.5">Refrigerate</p>
            <p class="text-[22px] font-black text-[#f59e0b] tracking-tight">5.4°C</p>
          </div>
        </div>
      </div>
    </div>
  </BaseModal>
</template>
