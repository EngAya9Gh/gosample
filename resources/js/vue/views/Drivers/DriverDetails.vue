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
  if (sScore.value >= 80) return 'text-[#0ea5e9] bg-[#0ea5e9]';
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
</script>

<template>
  <div class="pb-12">
    <!-- Top Breadcrumb -->
    <Breadcrumb 
      :title="driver?.name || 'Driver Profile'" 
      :trail="[{ label: 'Drivers', href: '/app/admin/drivers' }, { label: driver?.name || 'Profile' }]"
    >
      <template #actions>
        <BaseButton variant="light" icon="ri-arrow-left-line" @click="goBack" class="mr-2">Back to List</BaseButton>
        <BaseButton variant="warning" icon="ri-edit-2-line" @click="router.visit(`/app/admin/drivers/${driver?.id}/edit`)">Edit Driver</BaseButton>
      </template>
    </Breadcrumb>

    <!-- Main Dashboard Container -->
    <div class="max-w-6xl mx-auto mt-6">
      
      <!-- Layout Grid -->
      <div class="flex flex-col lg:flex-row gap-6">
        
        <!-- Left Column: Profile & KPIs -->
        <div class="w-full lg:w-[35%] space-y-6">
          
          <!-- Profile Card -->
          <div class="bg-white dark:bg-slate-900 rounded-[24px] shadow-sm border border-slate-100 dark:border-white/5 overflow-hidden">
            <div class="bg-[#0d9488] px-6 py-8 flex flex-col items-center text-center relative overflow-hidden">
              <div class="w-24 h-24 rounded-full bg-white/20 flex items-center justify-center text-white font-black text-[36px] border-4 border-white/20 backdrop-blur-md shadow-inner mb-4">
                {{ initials }}
              </div>
              <h2 class="text-[24px] font-black text-white mb-1 tracking-wide">{{ driver?.name }}</h2>
              <p class="text-teal-100/80 mb-4">{{ driver?.username }}</p>
              
              <div class="flex items-center gap-2">
                <span v-if="driver?.status == 1" class="px-3 py-1.5 rounded-full bg-[#4ade80]/20 text-[#4ade80] border border-[#4ade80]/30 text-[12px] font-bold flex items-center gap-1.5 backdrop-blur-sm">
                  <i class="ri-checkbox-circle-line"></i> Active
                </span>
                <span v-else class="px-3 py-1.5 rounded-full bg-red-500/20 text-red-300 border border-red-500/30 text-[12px] font-bold flex items-center gap-1.5 backdrop-blur-sm">
                  <i class="ri-close-circle-line"></i> Inactive
                </span>
                <span class="px-3 py-1.5 rounded-full bg-white/10 text-white border border-white/20 text-[12px] font-bold flex items-center gap-1.5 backdrop-blur-sm">
                  <i class="ri-global-line"></i> {{ (driver?.language || 'EN').toUpperCase() }}
                </span>
              </div>
            </div>
            
            <div class="p-6">
              <div class="space-y-5">
                <div class="flex items-start gap-4">
                  <div class="w-10 h-10 rounded-full bg-slate-50 dark:bg-slate-800 flex items-center justify-center text-slate-400 shrink-0">
                    <i class="ri-phone-line text-[18px]"></i>
                  </div>
                  <div>
                    <p class="text-[12px] text-slate-400 font-bold uppercase tracking-wider mb-0.5">Mobile</p>
                    <p class="font-bold text-slate-800 dark:text-white">{{ driver?.mobile || 'N/A' }}</p>
                  </div>
                </div>
                
                <div class="flex items-start gap-4">
                  <div class="w-10 h-10 rounded-full bg-slate-50 dark:bg-slate-800 flex items-center justify-center text-slate-400 shrink-0">
                    <i class="ri-mail-line text-[18px]"></i>
                  </div>
                  <div>
                    <p class="text-[12px] text-slate-400 font-bold uppercase tracking-wider mb-0.5">Email</p>
                    <p class="font-bold text-slate-800 dark:text-white">{{ driver?.email || 'N/A' }}</p>
                  </div>
                </div>

                <div class="flex items-start gap-4">
                  <div class="w-10 h-10 rounded-full bg-slate-50 dark:bg-slate-800 flex items-center justify-center text-slate-400 shrink-0">
                    <i class="ri-map-pin-line text-[18px]"></i>
                  </div>
                  <div>
                    <p class="text-[12px] text-slate-400 font-bold uppercase tracking-wider mb-0.5">Zone</p>
                    <p class="font-bold text-slate-800 dark:text-white">{{ driver?.zone?.name || 'N/A' }}</p>
                  </div>
                </div>
                
                <div class="flex items-start gap-4">
                  <div class="w-10 h-10 rounded-full bg-slate-50 dark:bg-slate-800 flex items-center justify-center text-slate-400 shrink-0">
                    <i class="ri-id-card-line text-[18px]"></i>
                  </div>
                  <div>
                    <p class="text-[12px] text-slate-400 font-bold uppercase tracking-wider mb-0.5">National ID</p>
                    <p class="font-bold text-slate-800 dark:text-white">{{ driver?.national_id || 'N/A' }}</p>
                  </div>
                </div>

                <div class="flex items-start gap-4">
                  <div class="w-10 h-10 rounded-full bg-red-50 dark:bg-red-500/10 flex items-center justify-center text-red-500 shrink-0">
                    <i class="ri-map-pin-2-fill text-[18px]"></i>
                  </div>
                  <div>
                    <p class="text-[12px] text-slate-400 font-bold uppercase tracking-wider mb-0.5">Current Location</p>
                    <a v-if="driver?.lat && driver?.lng" :href="`https://www.google.com/maps?q=${driver.lat},${driver.lng}`" target="_blank" class="font-bold text-blue-500 hover:text-blue-600 underline decoration-blue-200 underline-offset-2">
                      View on Google Maps
                    </a>
                    <p v-else class="font-bold text-slate-500">No coordinates set</p>
                  </div>
                </div>
              </div>
            </div>
          </div>

          <!-- Attendance KPI Card -->
          <div class="bg-white dark:bg-slate-900 p-6 rounded-[24px] shadow-sm border border-slate-100 dark:border-white/5">
            <h3 class="flex items-center gap-2 text-[16px] font-black text-slate-800 dark:text-white mb-6">
              <i class="ri-calendar-check-line text-blue-500"></i> Attendance KPI
            </h3>
            
            <div class="space-y-5">
              <div>
                <div class="flex justify-between items-center mb-2">
                  <span class="text-[13px] font-bold text-slate-600 dark:text-slate-300">Punctuality Score</span>
                  <span class="text-[13px] font-black" :class="pClass">{{ pScore }}%</span>
                </div>
                <div class="h-2.5 w-full bg-slate-100 dark:bg-slate-800 rounded-full overflow-hidden">
                  <div class="h-full rounded-full transition-all duration-1000" :class="pClass" :style="`width: ${pScore}%`"></div>
                </div>
              </div>

              <div>
                <div class="flex justify-between items-center mb-2">
                  <span class="text-[13px] font-bold text-slate-600 dark:text-slate-300">Shift Completion</span>
                  <span class="text-[13px] font-black" :class="sClass">{{ sScore }}%</span>
                </div>
                <div class="h-2.5 w-full bg-slate-100 dark:bg-slate-800 rounded-full overflow-hidden">
                  <div class="h-full rounded-full transition-all duration-1000" :class="sClass" :style="`width: ${sScore}%`"></div>
                </div>
              </div>
            </div>
          </div>

        </div>

        <!-- Right Column: Shifts & Tabs -->
        <div class="w-full lg:w-[65%] flex flex-col gap-6">
          
          <!-- Operational Schedule Card -->
          <div class="bg-white dark:bg-slate-900 p-6 rounded-[24px] shadow-sm border border-slate-100 dark:border-white/5">
            <div class="flex items-center justify-between mb-6">
              <div>
                <h3 class="text-[18px] font-black text-slate-800 dark:text-white">Operational Schedule</h3>
                <p class="text-[13px] text-slate-500 mt-1">Overview of assigned working shifts and total hours.</p>
              </div>
              <div class="px-4 py-2 rounded-[12px] bg-blue-50 dark:bg-blue-500/10 text-blue-600 dark:text-blue-400 font-bold text-[14px] flex items-center gap-2 border border-blue-100 dark:border-blue-500/20">
                <i class="ri-timer-2-line"></i> Total: {{ driver?.total_working_hours || '8' }} hrs/day
              </div>
            </div>

            <!-- Shifts Grid -->
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
              <!-- Shift 1 -->
              <div class="p-5 rounded-[20px] bg-blue-50/50 dark:bg-blue-900/10 border border-blue-100 dark:border-blue-900/30 relative overflow-hidden group hover:border-blue-300 dark:hover:border-blue-700 transition-colors">
                <div class="flex items-center gap-3 mb-4">
                  <div class="w-10 h-10 rounded-full bg-blue-100 dark:bg-blue-800 flex items-center justify-center text-blue-600 dark:text-blue-300">
                    <i class="ri-sun-line text-[18px]"></i>
                  </div>
                  <div>
                    <h4 class="font-black text-slate-800 dark:text-white text-[15px]">Shift 1</h4>
                    <p class="text-[12px] text-slate-500 font-medium">Primary Shift</p>
                  </div>
                </div>
                <div class="text-center bg-white dark:bg-slate-800 py-3 rounded-xl shadow-sm border border-slate-100 dark:border-white/5">
                  <span class="font-black text-blue-600 dark:text-blue-400 text-[18px]">{{ formatTime(driver?.working_hours_start) || '--:--' }}</span>
                  <span class="text-slate-400 text-[12px] font-bold mx-2">TO</span>
                  <span class="font-black text-blue-600 dark:text-blue-400 text-[18px]">{{ formatTime(driver?.working_hours_end) || '--:--' }}</span>
                </div>
              </div>

              <!-- Shift 2 -->
              <div v-if="driver?.shift_count >= 2" class="p-5 rounded-[20px] bg-orange-50/50 dark:bg-orange-900/10 border border-orange-100 dark:border-orange-900/30 relative overflow-hidden group hover:border-orange-300 dark:hover:border-orange-700 transition-colors">
                <div class="flex items-center gap-3 mb-4">
                  <div class="w-10 h-10 rounded-full bg-orange-100 dark:bg-orange-800 flex items-center justify-center text-orange-600 dark:text-orange-300">
                    <i class="ri-moon-clear-line text-[18px]"></i>
                  </div>
                  <div>
                    <h4 class="font-black text-slate-800 dark:text-white text-[15px]">Shift 2</h4>
                    <p class="text-[12px] text-slate-500 font-medium">Secondary Shift</p>
                  </div>
                </div>
                <div class="text-center bg-white dark:bg-slate-800 py-3 rounded-xl shadow-sm border border-slate-100 dark:border-white/5">
                  <span class="font-black text-orange-600 dark:text-orange-400 text-[18px]">{{ formatTime(driver?.second_shift_working_hours_start) || '--:--' }}</span>
                  <span class="text-slate-400 text-[12px] font-bold mx-2">TO</span>
                  <span class="font-black text-orange-600 dark:text-orange-400 text-[18px]">{{ formatTime(driver?.second_shift_working_hours_end) || '--:--' }}</span>
                </div>
              </div>

              <!-- Shift 3 -->
              <div v-if="driver?.shift_count >= 3" class="p-5 rounded-[20px] bg-purple-50/50 dark:bg-purple-900/10 border border-purple-100 dark:border-purple-900/30 relative overflow-hidden group hover:border-purple-300 dark:hover:border-purple-700 transition-colors">
                <div class="flex items-center gap-3 mb-4">
                  <div class="w-10 h-10 rounded-full bg-purple-100 dark:bg-purple-800 flex items-center justify-center text-purple-600 dark:text-purple-300">
                    <i class="ri-flashlight-line text-[18px]"></i>
                  </div>
                  <div>
                    <h4 class="font-black text-slate-800 dark:text-white text-[15px]">Shift 3</h4>
                    <p class="text-[12px] text-slate-500 font-medium">Night Shift</p>
                  </div>
                </div>
                <div class="text-center bg-white dark:bg-slate-800 py-3 rounded-xl shadow-sm border border-slate-100 dark:border-white/5">
                  <span class="font-black text-purple-600 dark:text-purple-400 text-[18px]">{{ formatTime(driver?.third_shift_working_hours_start) || '--:--' }}</span>
                  <span class="text-slate-400 text-[12px] font-bold mx-2">TO</span>
                  <span class="font-black text-purple-600 dark:text-purple-400 text-[18px]">{{ formatTime(driver?.third_shift_working_hours_end) || '--:--' }}</span>
                </div>
              </div>
            </div>
          </div>

          <!-- History Tabs Card -->
          <div class="bg-white dark:bg-slate-900 p-6 rounded-[24px] shadow-sm border border-slate-100 dark:border-white/5 flex-grow">
            <!-- Tabs -->
            <div class="flex bg-slate-50 dark:bg-slate-800/50 p-1.5 rounded-[16px] mb-6 border border-slate-100 dark:border-white/5">
              <button 
                v-for="tab in ['tasks', 'car']" 
                :key="tab"
                @click="activeTab = tab"
                class="flex-1 py-3 text-[15px] font-bold rounded-[12px] transition-all"
                :class="activeTab === tab ? 'bg-white text-blue-600 shadow-sm dark:bg-slate-700 dark:text-white' : 'text-slate-500 hover:text-slate-700 dark:text-slate-400 dark:hover:text-slate-200'"
              >
                <i v-if="tab === 'tasks'" class="ri-task-line mr-1.5 align-middle"></i>
                <i v-if="tab === 'car'" class="ri-car-line mr-1.5 align-middle"></i>
                {{ tab === 'tasks' ? 'Tasks History' : 'Car Link History' }}
              </button>
            </div>

            <!-- Tab Panels -->
            <div class="mt-4">
              <!-- Tasks History -->
              <div v-if="activeTab === 'tasks'">
                <div v-if="driver?.driverTasks?.length > 0" class="space-y-3">
                  <div v-for="t in driver.driverTasks" :key="t.id" class="flex items-center justify-between p-4 rounded-[16px] bg-slate-50 dark:bg-slate-800/50 border border-slate-100 dark:border-white/5 hover:border-slate-300 transition-colors">
                    <div>
                      <div class="font-black text-[15px] text-slate-800 dark:text-white mb-1">Task #{{ t.id }}</div>
                      <div class="text-[13px] text-slate-500 flex items-center gap-2">
                        <i class="ri-calendar-line"></i> {{ new Date(t.created_at).toLocaleDateString() }}
                      </div>
                    </div>
                    <div class="px-4 py-1.5 rounded-full text-[12px] font-black bg-blue-50 text-blue-600 dark:bg-blue-500/20 dark:text-blue-400">
                      {{ t.status || 'Completed' }}
                    </div>
                  </div>
                </div>
                <div v-else class="text-center py-10">
                  <div class="w-16 h-16 rounded-full bg-slate-50 dark:bg-slate-800 flex items-center justify-center text-slate-300 mx-auto mb-4">
                    <i class="ri-file-list-3-line text-[24px]"></i>
                  </div>
                  <h4 class="text-slate-800 dark:text-white font-bold mb-1">No tasks history</h4>
                  <p class="text-slate-500 text-[14px]">This driver has not been assigned any tasks yet.</p>
                </div>
              </div>

              <!-- Car History -->
              <div v-else-if="activeTab === 'car'">
                <div v-if="driver?.driverCarLinkHistories?.length > 0" class="space-y-4">
                  <div v-for="car in driver.driverCarLinkHistories" :key="car.id" class="p-5 rounded-[16px] bg-slate-50 dark:bg-slate-800/50 border border-slate-100 dark:border-white/5 flex items-center justify-between">
                    <div class="flex items-center gap-4">
                      <div class="w-12 h-12 rounded-xl bg-teal-50 dark:bg-teal-900/30 flex items-center justify-center text-teal-600 dark:text-teal-400 shrink-0">
                        <i class="ri-truck-line text-[22px]"></i>
                      </div>
                      <div>
                        <h4 class="font-black text-slate-800 dark:text-white text-[15px] mb-1">Vehicle #{{ car.car_id || 'N/A' }}</h4>
                        <div class="text-[13px] text-slate-500 flex items-center gap-2">
                          <i class="ri-calendar-line"></i> Linked: {{ new Date(car.created_at).toLocaleDateString() }}
                        </div>
                      </div>
                    </div>
                  </div>
                </div>
                <div v-else class="text-center py-10">
                  <div class="w-16 h-16 rounded-full bg-slate-50 dark:bg-slate-800 flex items-center justify-center text-slate-300 mx-auto mb-4">
                    <i class="ri-truck-line text-[24px]"></i>
                  </div>
                  <h4 class="text-slate-800 dark:text-white font-bold mb-1">No vehicle history</h4>
                  <p class="text-slate-500 text-[14px]">This driver hasn't been linked to any vehicles.</p>
                </div>
              </div>
            </div>

          </div>

        </div>

      </div>
    </div>
  </div>
</template>
