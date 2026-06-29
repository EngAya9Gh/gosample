<script setup>
import { ref, computed } from 'vue';
import { router } from '@inertiajs/vue3';
import Breadcrumb from '../../components/Breadcrumb.vue';
import BaseButton from '../../components/BaseButton.vue';

const props = defineProps({
  driver: { type: Object, required: true }
});

const activeTab = ref('tasks'); // 'tasks', 'car'

// Computed Stats
const initials = computed(() => {
  if (!props.driver?.name) return 'KM';
  return props.driver.name.split(' ').map(n => n[0]).join('').substring(0, 2).toUpperCase();
});

const pScore = computed(() => props.driver?.punctuality_score || 0);
const sScore = computed(() => props.driver?.shift_completion_score || 0);

const pClass = computed(() => {
  if (pScore.value >= 80) return 'text-[#10b981] bg-[#10b981]';
  if (pScore.value >= 50) return 'text-[#f59e0b] bg-[#f59e0b]';
  return 'text-[#ef4444] bg-[#ef4444]';
});

const sClass = computed(() => {
  if (sScore.value >= 80) return 'text-primary-500 bg-primary-500';
  if (sScore.value >= 50) return 'text-[#f59e0b] bg-[#f59e0b]';
  return 'text-[#ef4444] bg-[#ef4444]';
});

function goBack() {
  router.visit('/app/admin/drivers');
}

function formatTime(timeString) {
  if (!timeString) return '';
  return timeString.substring(0, 5); // Just HH:mm
}

// Map status strings to colors for Tasks History
function getTaskStatusStyle(status) {
  const s = String(status || '').toUpperCase();
  if (s.includes('NEW')) return 'bg-amber-50 text-amber-600 border border-amber-200 dark:bg-amber-500/10 dark:border-amber-500/20 dark:text-amber-400';
  if (s.includes('COLLECTED')) return 'bg-blue-50 text-blue-600 border border-blue-200 dark:bg-blue-500/10 dark:border-blue-500/20 dark:text-blue-400';
  if (s.includes('CONTAINER')) return 'bg-purple-50 text-purple-600 border border-purple-200 dark:bg-purple-500/10 dark:border-purple-500/20 dark:text-purple-400';
  if (s.includes('CLOSE')) return 'bg-green-50 text-green-600 border border-green-200 dark:bg-green-500/10 dark:border-green-500/20 dark:text-green-400';
  return 'bg-slate-50 text-slate-600 border border-slate-200 dark:bg-slate-500/10 dark:border-slate-500/20 dark:text-slate-400';
}
</script>

<template>
  <div class="pb-12">
    <!-- Top Breadcrumb -->
    <Breadcrumb 
      :title="driver?.name || 'Driver Profile'" 
      :trail="[{ label: 'Drivers', href: '/app/admin/drivers' }, { label: driver?.name || 'Profile' }]"
    >
      <template #actions>
        <BaseButton variant="light" icon="ri-arrow-left-line" @click="goBack" class="mr-2 hover:-translate-y-0.5 transition-transform">Back to List</BaseButton>
        <BaseButton variant="warning" icon="ri-edit-2-line" @click="router.visit(`/app/admin/drivers/${driver?.id}/edit`)" class="hover:-translate-y-0.5 transition-transform shadow-[0_4px_12px_rgba(245,158,11,0.2)]">Edit Driver</BaseButton>
      </template>
    </Breadcrumb>

    <!-- Main Dashboard Container -->
    <div class="max-w-6xl mx-auto mt-6">
      
      <!-- Layout Grid -->
      <div class="flex flex-col lg:flex-row gap-6">
        
        <!-- Left Column: Profile & KPIs -->
        <div class="w-full lg:w-[35%] space-y-6">
          
          <!-- Profile Card with Glassmorphism & Gradient -->
          <div class="bg-white dark:bg-slate-900 rounded-[24px] shadow-card border border-slate-100 dark:border-white/5 overflow-hidden group hover:shadow-[0_8px_30px_rgba(0,0,0,0.04)] transition-shadow duration-300">
            <!-- Premium Hero Banner -->
            <div class="bg-gradient-to-br from-primary-600 via-primary-700 to-primary-900 px-6 py-10 flex flex-col items-center text-center relative overflow-hidden">
              <!-- Decorative background elements -->
              <div class="absolute -top-12 -right-12 w-40 h-40 bg-white/10 rounded-full blur-2xl"></div>
              <div class="absolute -bottom-10 -left-10 w-32 h-32 bg-primary-400/20 rounded-full blur-xl"></div>
              
              <!-- Avatar with deep glow -->
              <div class="relative mb-5">
                <div class="absolute inset-0 bg-white/30 rounded-full blur-md transform scale-110"></div>
                <div class="w-28 h-28 relative rounded-full bg-white/20 flex items-center justify-center text-white font-black text-[40px] border-[3px] border-white/30 backdrop-blur-md shadow-[0_8px_20px_rgba(0,0,0,0.2)] transition-transform duration-500 group-hover:scale-105">
                  {{ initials }}
                </div>
              </div>
              
              <h2 class="text-[26px] font-black text-white mb-1.5 tracking-wide drop-shadow-md">{{ driver?.name }}</h2>
              <p class="text-primary-100/90 font-medium mb-5 text-[15px]">{{ driver?.username }}</p>
              
              <div class="flex items-center gap-2 z-10">
                <span v-if="driver?.status == 1" class="px-3 py-1.5 rounded-full bg-[#4ade80]/20 text-[#4ade80] border border-[#4ade80]/40 text-[12px] font-bold flex items-center gap-1.5 backdrop-blur-md shadow-[0_4px_10px_rgba(74,222,128,0.2)]">
                  <i class="ri-checkbox-circle-fill"></i> Active
                </span>
                <span v-else class="px-3 py-1.5 rounded-full bg-red-500/20 text-red-300 border border-red-500/40 text-[12px] font-bold flex items-center gap-1.5 backdrop-blur-md shadow-[0_4px_10px_rgba(239,68,68,0.2)]">
                  <i class="ri-close-circle-fill"></i> Inactive
                </span>
                <span class="px-3 py-1.5 rounded-full bg-white/10 text-white border border-white/20 text-[12px] font-bold flex items-center gap-1.5 backdrop-blur-md">
                  <i class="ri-global-line"></i> {{ (driver?.language || 'EN').toUpperCase() }}
                </span>
              </div>
            </div>
            
            <div class="p-7 bg-slate-50/50 dark:bg-slate-800/30">
              <div class="space-y-6">
                <div class="flex items-start gap-4 group/item hover:translate-x-1 transition-transform">
                  <div class="w-11 h-11 rounded-xl bg-primary-50 dark:bg-primary-500/10 flex items-center justify-center text-primary-600 dark:text-primary-400 shrink-0 shadow-sm border border-primary-100 dark:border-primary-500/20">
                    <i class="ri-phone-line text-[20px]"></i>
                  </div>
                  <div>
                    <p class="text-[12px] text-slate-400 font-bold uppercase tracking-wider mb-1">Mobile</p>
                    <p class="font-black text-slate-800 dark:text-white text-[15px]">{{ driver?.mobile || 'N/A' }}</p>
                  </div>
                </div>
                
                <div class="flex items-start gap-4 group/item hover:translate-x-1 transition-transform">
                  <div class="w-11 h-11 rounded-xl bg-primary-50 dark:bg-primary-500/10 flex items-center justify-center text-primary-600 dark:text-primary-400 shrink-0 shadow-sm border border-primary-100 dark:border-primary-500/20">
                    <i class="ri-mail-line text-[20px]"></i>
                  </div>
                  <div>
                    <p class="text-[12px] text-slate-400 font-bold uppercase tracking-wider mb-1">Email</p>
                    <p class="font-black text-slate-800 dark:text-white text-[15px] break-all">{{ driver?.email || 'N/A' }}</p>
                  </div>
                </div>

                <div class="flex items-start gap-4 group/item hover:translate-x-1 transition-transform">
                  <div class="w-11 h-11 rounded-xl bg-primary-50 dark:bg-primary-500/10 flex items-center justify-center text-primary-600 dark:text-primary-400 shrink-0 shadow-sm border border-primary-100 dark:border-primary-500/20">
                    <i class="ri-map-pin-user-line text-[20px]"></i>
                  </div>
                  <div>
                    <p class="text-[12px] text-slate-400 font-bold uppercase tracking-wider mb-1">Zone</p>
                    <p class="font-black text-slate-800 dark:text-white text-[15px]">{{ driver?.zone?.name || 'N/A' }}</p>
                  </div>
                </div>
                
                <div class="flex items-start gap-4 group/item hover:translate-x-1 transition-transform">
                  <div class="w-11 h-11 rounded-xl bg-primary-50 dark:bg-primary-500/10 flex items-center justify-center text-primary-600 dark:text-primary-400 shrink-0 shadow-sm border border-primary-100 dark:border-primary-500/20">
                    <i class="ri-id-card-line text-[20px]"></i>
                  </div>
                  <div>
                    <p class="text-[12px] text-slate-400 font-bold uppercase tracking-wider mb-1">National ID</p>
                    <p class="font-black text-slate-800 dark:text-white text-[15px]">{{ driver?.national_id || 'N/A' }}</p>
                  </div>
                </div>

                <div class="flex items-start gap-4 group/item hover:translate-x-1 transition-transform">
                  <div class="w-11 h-11 rounded-xl bg-rose-50 dark:bg-rose-500/10 flex items-center justify-center text-rose-500 shrink-0 shadow-sm border border-rose-100 dark:border-rose-500/20">
                    <i class="ri-map-pin-2-fill text-[20px]"></i>
                  </div>
                  <div>
                    <p class="text-[12px] text-slate-400 font-bold uppercase tracking-wider mb-1">Current Location</p>
                    <a v-if="driver?.lat && driver?.lng" :href="`https://www.google.com/maps?q=${driver.lat},${driver.lng}`" target="_blank" class="font-black text-primary-600 hover:text-primary-700 underline decoration-primary-300 underline-offset-4 transition-colors">
                      View on Google Maps
                    </a>
                    <p v-else class="font-black text-slate-500">No coordinates set</p>
                  </div>
                </div>
              </div>
            </div>
          </div>

          <!-- Attendance KPI Card -->
          <div class="bg-white dark:bg-slate-900 p-7 rounded-[24px] shadow-card border border-slate-100 dark:border-white/5 hover:shadow-[0_8px_30px_rgba(0,0,0,0.04)] transition-shadow">
            <h3 class="flex items-center gap-2 text-[17px] font-black text-slate-800 dark:text-white mb-6">
              <div class="w-8 h-8 rounded-lg bg-primary-50 dark:bg-primary-500/10 flex items-center justify-center text-primary-600 dark:text-primary-400">
                <i class="ri-calendar-check-line text-[18px]"></i>
              </div>
              Attendance KPI
            </h3>
            
            <div class="space-y-6">
              <div class="group/kpi cursor-default">
                <div class="flex justify-between items-center mb-2.5">
                  <span class="text-[14px] font-bold text-slate-600 dark:text-slate-300">Punctuality Score</span>
                  <span class="text-[15px] font-black group-hover:scale-110 transition-transform origin-right" :class="pClass">{{ pScore }}%</span>
                </div>
                <div class="h-3 w-full bg-slate-100 dark:bg-slate-800 rounded-full overflow-hidden shadow-inner relative">
                  <div class="absolute inset-0 bg-gradient-to-r from-transparent to-white/20 z-10"></div>
                  <div class="h-full rounded-full transition-all duration-1000 ease-out shadow-[0_0_10px_currentColor]" :class="pClass" :style="`width: ${pScore}%`"></div>
                </div>
              </div>

              <div class="group/kpi cursor-default">
                <div class="flex justify-between items-center mb-2.5">
                  <span class="text-[14px] font-bold text-slate-600 dark:text-slate-300">Shift Completion</span>
                  <span class="text-[15px] font-black group-hover:scale-110 transition-transform origin-right" :class="sClass">{{ sScore }}%</span>
                </div>
                <div class="h-3 w-full bg-slate-100 dark:bg-slate-800 rounded-full overflow-hidden shadow-inner relative">
                  <div class="absolute inset-0 bg-gradient-to-r from-transparent to-white/20 z-10"></div>
                  <div class="h-full rounded-full transition-all duration-1000 ease-out shadow-[0_0_10px_currentColor]" :class="sClass" :style="`width: ${sScore}%`"></div>
                </div>
              </div>
            </div>
          </div>

        </div>

        <!-- Right Column: Shifts & Tabs -->
        <div class="w-full lg:w-[65%] flex flex-col gap-6">
          
          <!-- Operational Schedule Card -->
          <div class="bg-white dark:bg-slate-900 p-7 rounded-[24px] shadow-card border border-slate-100 dark:border-white/5 hover:shadow-[0_8px_30px_rgba(0,0,0,0.04)] transition-shadow">
            <div class="flex items-center justify-between mb-7">
              <div>
                <h3 class="text-[20px] font-black text-slate-800 dark:text-white tracking-tight">Operational Schedule</h3>
                <p class="text-[14px] text-slate-500 mt-1 font-medium">Overview of assigned working shifts and total hours.</p>
              </div>
              <div class="px-5 py-2.5 rounded-[14px] bg-primary-50 dark:bg-primary-500/10 text-primary-700 dark:text-primary-400 font-black text-[15px] flex items-center gap-2 border border-primary-100 dark:border-primary-500/20 shadow-[0_2px_10px_rgba(0,93,105,0.05)]">
                <i class="ri-timer-2-line text-[18px]"></i> Total: {{ driver?.total_working_hours || '8' }} hrs/day
              </div>
            </div>

            <!-- Shifts Grid -->
            <div class="grid grid-cols-1 md:grid-cols-3 gap-5">
              <!-- Shift 1 -->
              <div class="p-6 rounded-[20px] bg-primary-50/40 dark:bg-primary-900/10 border border-primary-100/50 dark:border-primary-900/30 relative overflow-hidden group hover:border-primary-300 dark:hover:border-primary-700 transition-colors hover:-translate-y-1 hover:shadow-md">
                <div class="flex items-center gap-3.5 mb-5">
                  <div class="w-12 h-12 rounded-[14px] bg-white dark:bg-primary-800 flex items-center justify-center text-primary-600 dark:text-primary-300 shadow-sm border border-primary-100 dark:border-primary-700">
                    <i class="ri-sun-line text-[22px]"></i>
                  </div>
                  <div>
                    <h4 class="font-black text-slate-800 dark:text-white text-[16px]">Shift 1</h4>
                    <p class="text-[13px] text-slate-500 font-bold">Primary Shift</p>
                  </div>
                </div>
                <div class="text-center bg-white/80 dark:bg-slate-800/80 backdrop-blur-sm py-3.5 rounded-xl shadow-sm border border-white dark:border-white/5">
                  <span class="font-black text-primary-600 dark:text-primary-400 text-[20px]">{{ formatTime(driver?.working_hours_start) || '--:--' }}</span>
                  <span class="text-slate-400 text-[12px] font-black mx-2 opacity-50">TO</span>
                  <span class="font-black text-primary-600 dark:text-primary-400 text-[20px]">{{ formatTime(driver?.working_hours_end) || '--:--' }}</span>
                </div>
              </div>

              <!-- Shift 2 -->
              <div v-if="driver?.shift_count >= 2" class="p-6 rounded-[20px] bg-amber-50/40 dark:bg-amber-900/10 border border-amber-100/50 dark:border-amber-900/30 relative overflow-hidden group hover:border-amber-300 dark:hover:border-amber-700 transition-colors hover:-translate-y-1 hover:shadow-md">
                <div class="flex items-center gap-3.5 mb-5">
                  <div class="w-12 h-12 rounded-[14px] bg-white dark:bg-amber-800 flex items-center justify-center text-amber-600 dark:text-amber-300 shadow-sm border border-amber-100 dark:border-amber-700">
                    <i class="ri-moon-clear-line text-[22px]"></i>
                  </div>
                  <div>
                    <h4 class="font-black text-slate-800 dark:text-white text-[16px]">Shift 2</h4>
                    <p class="text-[13px] text-slate-500 font-bold">Secondary Shift</p>
                  </div>
                </div>
                <div class="text-center bg-white/80 dark:bg-slate-800/80 backdrop-blur-sm py-3.5 rounded-xl shadow-sm border border-white dark:border-white/5">
                  <span class="font-black text-amber-600 dark:text-amber-400 text-[20px]">{{ formatTime(driver?.second_shift_working_hours_start) || '--:--' }}</span>
                  <span class="text-slate-400 text-[12px] font-black mx-2 opacity-50">TO</span>
                  <span class="font-black text-amber-600 dark:text-amber-400 text-[20px]">{{ formatTime(driver?.second_shift_working_hours_end) || '--:--' }}</span>
                </div>
              </div>

              <!-- Shift 3 -->
              <div v-if="driver?.shift_count >= 3" class="p-6 rounded-[20px] bg-indigo-50/40 dark:bg-indigo-900/10 border border-indigo-100/50 dark:border-indigo-900/30 relative overflow-hidden group hover:border-indigo-300 dark:hover:border-indigo-700 transition-colors hover:-translate-y-1 hover:shadow-md">
                <div class="flex items-center gap-3.5 mb-5">
                  <div class="w-12 h-12 rounded-[14px] bg-white dark:bg-indigo-800 flex items-center justify-center text-indigo-600 dark:text-indigo-300 shadow-sm border border-indigo-100 dark:border-indigo-700">
                    <i class="ri-flashlight-line text-[22px]"></i>
                  </div>
                  <div>
                    <h4 class="font-black text-slate-800 dark:text-white text-[16px]">Shift 3</h4>
                    <p class="text-[13px] text-slate-500 font-bold">Night Shift</p>
                  </div>
                </div>
                <div class="text-center bg-white/80 dark:bg-slate-800/80 backdrop-blur-sm py-3.5 rounded-xl shadow-sm border border-white dark:border-white/5">
                  <span class="font-black text-indigo-600 dark:text-indigo-400 text-[20px]">{{ formatTime(driver?.third_shift_working_hours_start) || '--:--' }}</span>
                  <span class="text-slate-400 text-[12px] font-black mx-2 opacity-50">TO</span>
                  <span class="font-black text-indigo-600 dark:text-indigo-400 text-[20px]">{{ formatTime(driver?.third_shift_working_hours_end) || '--:--' }}</span>
                </div>
              </div>
            </div>
          </div>

          <!-- History Tabs Card -->
          <div class="bg-white dark:bg-slate-900 p-7 rounded-[24px] shadow-card border border-slate-100 dark:border-white/5 flex-grow hover:shadow-[0_8px_30px_rgba(0,0,0,0.04)] transition-shadow flex flex-col">
            <!-- Premium Tab Navigation -->
            <div class="flex bg-slate-100/80 dark:bg-slate-800/80 p-1.5 rounded-[16px] mb-7 border border-slate-200/50 dark:border-white/5 shadow-inner">
              <button 
                v-for="tab in ['tasks', 'car']" 
                :key="tab"
                @click="activeTab = tab"
                class="flex-1 py-3.5 text-[15px] font-black rounded-[12px] transition-all duration-300 flex items-center justify-center gap-2"
                :class="activeTab === tab ? 'bg-white text-primary-600 shadow-[0_2px_8px_rgba(0,0,0,0.08)] dark:bg-slate-700 dark:text-white transform scale-[1.02]' : 'text-slate-500 hover:text-slate-700 dark:text-slate-400 dark:hover:text-slate-200 hover:bg-slate-200/50 dark:hover:bg-slate-700/50'"
              >
                <i v-if="tab === 'tasks'" class="ri-task-line text-[18px]"></i>
                <i v-if="tab === 'car'" class="ri-car-line text-[18px]"></i>
                {{ tab === 'tasks' ? 'Tasks History' : 'Car Link History' }}
              </button>
            </div>

            <!-- Tab Panels (Scrollable area for large histories) -->
            <div class="flex-grow overflow-y-auto max-h-[400px] pr-2 custom-scrollbar">
              
              <!-- Tasks History (FIXED JSON KEY: driver_tasks) -->
              <div v-if="activeTab === 'tasks'">
                <div v-if="driver?.driver_tasks?.length > 0" class="space-y-3.5">
                  <div v-for="t in driver.driver_tasks" :key="t.id" class="group flex items-center justify-between p-4 rounded-[18px] bg-white dark:bg-slate-800/50 border border-slate-100 dark:border-white/5 hover:border-primary-300 dark:hover:border-primary-500/30 hover:shadow-md transition-all duration-300 hover:-translate-y-0.5">
                    <div class="flex items-center gap-4">
                      <div class="w-12 h-12 rounded-full bg-slate-50 dark:bg-slate-800 flex items-center justify-center text-slate-400 group-hover:text-primary-500 group-hover:bg-primary-50 dark:group-hover:bg-primary-500/10 transition-colors border border-slate-100 dark:border-white/5">
                        <i class="ri-hashtag text-[18px]"></i>
                      </div>
                      <div>
                        <div class="font-black text-[16px] text-slate-800 dark:text-white mb-0.5 tracking-wide">Task {{ t.id }}</div>
                        <div class="text-[13px] text-slate-500 font-medium flex items-center gap-1.5">
                          <i class="ri-calendar-line"></i> {{ new Date(t.created_at).toLocaleDateString() }}
                        </div>
                      </div>
                    </div>
                    <div class="px-4 py-1.5 rounded-full text-[12px] font-black uppercase tracking-wider" :class="getTaskStatusStyle(t.status)">
                      {{ t.status || 'COMPLETED' }}
                    </div>
                  </div>
                </div>
                <!-- Empty State -->
                <div v-else class="text-center py-16 px-4 bg-slate-50/50 dark:bg-slate-800/30 rounded-[20px] border border-dashed border-slate-200 dark:border-white/10">
                  <div class="w-20 h-20 rounded-full bg-white dark:bg-slate-800 flex items-center justify-center text-slate-300 shadow-sm border border-slate-100 dark:border-white/5 mx-auto mb-5 relative">
                    <div class="absolute inset-0 rounded-full border-2 border-slate-200/50 dark:border-slate-700 animate-ping opacity-20"></div>
                    <i class="ri-file-list-3-line text-[32px]"></i>
                  </div>
                  <h4 class="text-slate-800 dark:text-white font-black text-[18px] mb-2">No tasks history</h4>
                  <p class="text-slate-500 text-[15px] font-medium max-w-sm mx-auto">This driver has not been assigned any tasks yet. Once tasks are assigned, they will appear here.</p>
                </div>
              </div>

              <!-- Car History (FIXED JSON KEY: driver_car_link_histories) -->
              <div v-else-if="activeTab === 'car'">
                <div v-if="driver?.driver_car_link_histories?.length > 0" class="relative pl-6 space-y-8 before:absolute before:inset-y-0 before:left-[19px] before:w-[2px] before:bg-slate-100 dark:before:bg-slate-800">
                  <div v-for="(car, idx) in driver.driver_car_link_histories" :key="car.id" class="relative group">
                    <!-- Timeline Dot -->
                    <div class="absolute -left-[30px] top-1.5 w-[14px] h-[14px] rounded-full bg-white dark:bg-slate-900 border-[3px] transition-colors" :class="idx === 0 ? 'border-primary-500' : 'border-slate-300 dark:border-slate-600 group-hover:border-primary-400'"></div>
                    
                    <div class="p-5 rounded-[18px] bg-white dark:bg-slate-800/50 border border-slate-100 dark:border-white/5 hover:shadow-md hover:border-primary-200 dark:hover:border-primary-500/30 transition-all duration-300 hover:-translate-y-0.5">
                      <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
                        <div class="flex items-center gap-4">
                          <div class="w-12 h-12 rounded-[14px] bg-primary-50 dark:bg-primary-900/30 flex items-center justify-center text-primary-600 dark:text-primary-400 shrink-0 border border-primary-100 dark:border-primary-500/20 shadow-[0_2px_8px_rgba(0,93,105,0.1)]">
                            <i class="ri-truck-line text-[22px]"></i>
                          </div>
                          <div>
                            <h4 class="font-black text-slate-800 dark:text-white text-[16px] mb-1">Vehicle #{{ car.car_id || 'N/A' }}</h4>
                            <div class="text-[13px] text-slate-500 font-medium">
                              Linked on <span class="font-bold text-slate-700 dark:text-slate-300">{{ new Date(car.created_at).toLocaleDateString() }}</span>
                            </div>
                          </div>
                        </div>
                        <div class="px-4 py-1.5 rounded-full text-[12px] font-black bg-slate-100 text-slate-600 dark:bg-slate-700 dark:text-slate-300 self-start sm:self-auto">
                          {{ idx === 0 ? 'CURRENT' : 'HISTORICAL' }}
                        </div>
                      </div>
                    </div>
                  </div>
                </div>
                <!-- Empty State -->
                <div v-else class="text-center py-16 px-4 bg-slate-50/50 dark:bg-slate-800/30 rounded-[20px] border border-dashed border-slate-200 dark:border-white/10">
                  <div class="w-20 h-20 rounded-full bg-white dark:bg-slate-800 flex items-center justify-center text-slate-300 shadow-sm border border-slate-100 dark:border-white/5 mx-auto mb-5 relative">
                    <div class="absolute inset-0 rounded-full border-2 border-slate-200/50 dark:border-slate-700 animate-ping opacity-20"></div>
                    <i class="ri-truck-line text-[32px]"></i>
                  </div>
                  <h4 class="text-slate-800 dark:text-white font-black text-[18px] mb-2">No vehicle history</h4>
                  <p class="text-slate-500 text-[15px] font-medium max-w-sm mx-auto">This driver hasn't been linked to any vehicles yet. Vehicle assignments will be tracked here.</p>
                </div>
              </div>
            </div>

          </div>

        </div>

      </div>
    </div>
  </div>
</template>

<style scoped>
/* Custom scrollbar for the history tab area */
.custom-scrollbar::-webkit-scrollbar {
  width: 6px;
}
.custom-scrollbar::-webkit-scrollbar-track {
  background: transparent; 
}
.custom-scrollbar::-webkit-scrollbar-thumb {
  background: #cbd5e1; 
  border-radius: 10px;
}
.dark .custom-scrollbar::-webkit-scrollbar-thumb {
  background: #475569; 
}
.custom-scrollbar::-webkit-scrollbar-thumb:hover {
  background: #94a3b8; 
}
</style>
