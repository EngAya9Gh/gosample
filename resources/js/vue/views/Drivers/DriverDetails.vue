<script setup>
import { ref, computed } from 'vue';
import Breadcrumb from '../../components/Breadcrumb.vue';
import BaseCard from '../../components/BaseCard.vue';
import BaseAvatar from '../../components/BaseAvatar.vue';
import TabGroup from '../../components/TabGroup.vue';
import DataTable from '../../components/DataTable.vue';
import StatusBadge from '../../components/StatusBadge.vue';

const props = defineProps({
  driver: { type: Object, required: true }
});

const rtab = ref('tasks');

const rtabs = [
  { key: 'tasks', label: 'Tasks History', icon: 'ri-task-line' },
  { key: 'cars', label: 'Car Link History', icon: 'ri-links-line' }
];

const taskCols = [
  { key: 'id', label: 'Task ID' },
  { key: 'status', label: 'Status' },
  { key: 'from_location', label: 'Pickup Location ID' },
  { key: 'to_location', label: 'Dropoff Location ID' },
  { key: 'created_at', label: 'Created At' }
];

const carCols = [
  { key: 'id', label: 'ID' },
  { key: 'car_id', label: 'Car ID', mono: true },
  { key: 'action', label: 'Action' },
  { key: 'created_at', label: 'Created At' }
];

const pScore = computed(() => props.driver?.punctuality_score || 0);
const sScore = computed(() => props.driver?.shift_completion_score || 0);

// Use real data arrays from props
const driverTasks = computed(() => props.driver?.driver_tasks || []);
const driverCarLinks = computed(() => props.driver?.driver_car_link_histories || []);
const shifts = computed(() => props.driver?.shifts || []);

// Total shift hours calculation
const totalShiftHours = computed(() => {
  return shifts.value.reduce((total, shift) => {
    if (!shift.start_time || !shift.end_time) return total;
    const start = new Date(`1970-01-01T${shift.start_time}`);
    let end = new Date(`1970-01-01T${shift.end_time}`);
    if (end < start) end.setDate(end.getDate() + 1);
    return total + ((end - start) / (1000 * 60 * 60));
  }, 0).toFixed(1);
});

// Calculate duration of a single shift
const getShiftDuration = (start_time, end_time) => {
    if (!start_time || !end_time) return '-';
    const start = new Date(`1970-01-01T${start_time}`);
    let end = new Date(`1970-01-01T${end_time}`);
    if (end < start) end.setDate(end.getDate() + 1);
    const diff = ((end - start) / (1000 * 60 * 60)).toFixed(1);
    return `${diff} hrs`;
};

const formatDate = (dateString) => {
  if (!dateString) return '-';
  const d = new Date(dateString);
  return d.toLocaleDateString('en-GB') + ' ' + d.toLocaleTimeString('en-GB', { hour: '2-digit', minute: '2-digit' });
};
</script>

<template>
  <div>
    <Breadcrumb :title="driver.name" :trail="[{label:'Drivers',href:'#/admin/drivers'},{label:'Profile'}]" />
    <div class="grid grid-cols-1 lg:grid-cols-12 gap-6">
      
      <!-- Left Column (Span 4) -->
      <div class="lg:col-span-4 space-y-6">
        
        <!-- Premium Green Avatar Card -->
        <div class="rounded-2xl shadow-card border border-slate-100 dark:border-white/5 overflow-hidden transition-all duration-300 hover:shadow-card-hover hover:-translate-y-0.5">
          <!-- Green Gradient Header Area -->
          <div class="bg-gradient-to-br from-[#005D69] to-[#0d9488] px-6 py-8 flex flex-col items-center text-center relative overflow-hidden">
            <!-- Decorative circles -->
            <div class="absolute top-0 right-0 -mr-8 -mt-8 w-32 h-32 rounded-full bg-white/10 blur-xl"></div>
            <div class="absolute bottom-0 left-0 -ml-8 -mb-8 w-24 h-24 rounded-full bg-black/10 blur-lg"></div>

            <BaseAvatar :name="driver.name" :size="96" class="border-4 border-white/20 shadow-xl mb-4" />
            <h2 class="text-xl font-extrabold text-white mb-1">{{ driver.name }}</h2>
            <p class="text-sm text-teal-100 font-mono mb-4" style="direction:ltr">@{{ driver.username }}</p>
            
            <div class="flex items-center gap-2">
              <span class="inline-flex items-center px-3 py-1 rounded-full bg-white/20 text-white text-xs font-bold backdrop-blur-sm border border-white/30">
                <span class="w-1.5 h-1.5 rounded-full bg-white mr-1.5 animate-pulse" v-if="driver.status == 1"></span>
                {{ driver.status == 1 ? 'Active' : 'Inactive' }}
              </span>
              <span class="inline-flex items-center px-3 py-1 rounded-full bg-black/20 text-white text-xs font-bold backdrop-blur-sm border border-black/10">
                <i class="ri-map-pin-line mr-1 opacity-80"></i>
                {{ driver.zone ? driver.zone.name_en : 'No Zone' }}
              </span>
            </div>
          </div>
          <!-- Quick Contact -->
          <div class="bg-surface dark:bg-surface-dark-card p-4 grid grid-cols-2 gap-4">
            <div class="flex flex-col items-center justify-center p-3 rounded-xl bg-slate-50 dark:bg-white/5 border border-slate-100 dark:border-white/5">
              <i class="ri-phone-line text-[#0ab39c] text-lg mb-1"></i>
              <div class="text-xs font-mono font-medium text-slate-700 dark:text-slate-300" style="direction:ltr">{{ driver.mobile }}</div>
            </div>
            <div class="flex flex-col items-center justify-center p-3 rounded-xl bg-slate-50 dark:bg-white/5 border border-slate-100 dark:border-white/5" :title="driver.email">
              <i class="ri-mail-line text-[#0ab39c] text-lg mb-1"></i>
              <div class="text-xs font-medium text-slate-700 dark:text-slate-300 truncate w-full text-center">{{ driver.email || 'No email' }}</div>
            </div>
          </div>
          
          <!-- Personal Info inside the same card -->
          <div class="bg-surface dark:bg-surface-dark-card px-5 pb-5">
            <dl class="space-y-1 mt-1">
              <div class="flex items-center justify-between py-3 border-b border-slate-100 dark:border-white/5">
                <dt class="text-sm text-slate-500 font-medium">National ID</dt>
                <dd class="text-sm font-bold text-slate-800 dark:text-slate-200 font-mono" style="direction:ltr">{{ driver.national_id || '-' }}</dd>
              </div>
              <div class="flex items-center justify-between py-3 border-b border-slate-100 dark:border-white/5">
                <dt class="text-sm text-slate-500 font-medium">Employment Type</dt>
                <dd class="text-sm font-bold text-slate-800 dark:text-slate-200">{{ driver.employment_type || '-' }}</dd>
              </div>
              <div class="flex items-center justify-between py-3 border-b border-slate-100 dark:border-white/5">
                <dt class="text-sm text-slate-500 font-medium">Language</dt>
                <dd class="text-sm font-bold text-slate-800 dark:text-slate-200">
                  <span class="inline-flex items-center px-2 py-0.5 rounded bg-slate-100 dark:bg-white/5 text-xs">
                    {{ driver.language === 'en' ? 'English' : (driver.language === 'ar' ? 'Arabic' : driver.language || '-') }}
                  </span>
                </dd>
              </div>
              <div class="flex items-center justify-between py-3">
                <dt class="text-sm text-slate-500 font-medium">Join Date</dt>
                <dd class="text-sm font-bold text-slate-800 dark:text-slate-200">{{ formatDate(driver.created_at) }}</dd>
              </div>
            </dl>
          </div>
        </div>

      </div>

      <!-- Right Column (Span 8) -->
      <div class="lg:col-span-8 space-y-6">
        
        <!-- Shifts Section (Always visible, modern cards) -->
        <section>
          <div class="flex items-center justify-between mb-4">
            <h3 class="text-lg font-extrabold text-slate-800 dark:text-white flex items-center gap-2">
              <i class="ri-time-line text-[#0ab39c]"></i> Operational Shifts
            </h3>
            <span class="inline-flex items-center px-3 py-1 rounded-full bg-[#0ab39c]/10 text-[#0ab39c] text-xs font-bold border border-[#0ab39c]/20">
              {{ totalShiftHours }} hrs / day
            </span>
          </div>

          <div v-if="shifts.length > 0" class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div v-for="s in shifts" :key="s.id" class="group relative bg-surface dark:bg-surface-dark-card rounded-2xl p-5 border border-slate-200/60 dark:border-white/5 shadow-sm hover:shadow-md transition-all hover:border-[#0ab39c]/30">
              <!-- Shift Number Badge -->
              <div class="absolute top-4 right-4 w-8 h-8 rounded-full bg-slate-50 dark:bg-white/5 flex items-center justify-center text-xs font-bold text-slate-500 group-hover:bg-[#0ab39c]/10 group-hover:text-[#0ab39c] transition-colors">
                #{{ s.shift_number }}
              </div>
              
              <div class="flex items-center gap-4 mb-4">
                <div class="w-12 h-12 rounded-2xl bg-gradient-to-br from-slate-100 to-slate-200 dark:from-white/5 dark:to-white/10 flex items-center justify-center group-hover:from-[#0ab39c]/20 group-hover:to-[#0ab39c]/5 transition-colors">
                  <i class="ri-calendar-event-line text-xl text-slate-500 group-hover:text-[#0ab39c] transition-colors"></i>
                </div>
                <div>
                  <h4 class="text-sm font-bold text-slate-800 dark:text-slate-100">Shift {{ s.shift_number }}</h4>
                  <p class="text-xs text-slate-500 font-medium mt-0.5">Duration: {{ getShiftDuration(s.start_time, s.end_time) }}</p>
                </div>
              </div>
              
              <div class="flex items-center justify-between p-3 rounded-xl bg-slate-50 dark:bg-white/5 border border-slate-100 dark:border-white/5">
                <div class="text-center flex-1">
                  <span class="block text-[10px] uppercase tracking-wider text-slate-400 font-bold mb-1">Start</span>
                  <span class="text-lg font-mono font-bold text-slate-700 dark:text-slate-200">{{ s.start_time }}</span>
                </div>
                <div class="px-2 text-slate-300 dark:text-slate-600">
                  <i class="ri-arrow-right-line"></i>
                </div>
                <div class="text-center flex-1">
                  <span class="block text-[10px] uppercase tracking-wider text-slate-400 font-bold mb-1">End</span>
                  <span class="text-lg font-mono font-bold text-slate-700 dark:text-slate-200">{{ s.end_time }}</span>
                </div>
              </div>
            </div>
          </div>
          
          <div v-else class="bg-surface dark:bg-surface-dark-card rounded-2xl p-8 border border-slate-100 dark:border-white/5 text-center flex flex-col items-center justify-center shadow-sm">
            <div class="w-16 h-16 rounded-full bg-slate-50 dark:bg-white/5 flex items-center justify-center mb-3">
              <i class="ri-timer-flash-line text-2xl text-slate-400"></i>
            </div>
            <h4 class="text-slate-700 dark:text-slate-300 font-bold mb-1">No Shifts Assigned</h4>
            <p class="text-sm text-slate-500">This driver doesn't have any active operational shifts yet.</p>
          </div>
        </section>

        <!-- KPI / Attendance Row -->
        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
          <div class="bg-surface dark:bg-surface-dark-card rounded-2xl p-5 border border-slate-100 dark:border-white/5 shadow-sm relative overflow-hidden group">
            <div class="absolute top-0 right-0 w-24 h-24 bg-success/5 rounded-full -mr-10 -mt-10 group-hover:scale-150 transition-transform duration-500"></div>
            <div class="flex items-center justify-between mb-4 relative z-10">
              <div class="flex items-center gap-2">
                <div class="w-8 h-8 rounded-lg bg-success/10 flex items-center justify-center text-success"><i class="ri-focus-2-line"></i></div>
                <span class="text-sm font-bold text-slate-700 dark:text-slate-200">Punctuality Score</span>
              </div>
              <span class="text-xl font-extrabold text-success">{{ pScore }}%</span>
            </div>
            <div class="h-2.5 rounded-full bg-slate-100 dark:bg-white/5 overflow-hidden relative z-10">
              <div class="h-full rounded-full bg-success transition-all duration-1000 ease-out" :style="{ width: pScore + '%' }"></div>
            </div>
          </div>
          
          <div class="bg-surface dark:bg-surface-dark-card rounded-2xl p-5 border border-slate-100 dark:border-white/5 shadow-sm relative overflow-hidden group">
            <div class="absolute top-0 right-0 w-24 h-24 bg-blue-500/5 rounded-full -mr-10 -mt-10 group-hover:scale-150 transition-transform duration-500"></div>
            <div class="flex items-center justify-between mb-4 relative z-10">
              <div class="flex items-center gap-2">
                <div class="w-8 h-8 rounded-lg bg-blue-500/10 flex items-center justify-center text-blue-500"><i class="ri-check-double-line"></i></div>
                <span class="text-sm font-bold text-slate-700 dark:text-slate-200">Shift Completion</span>
              </div>
              <span class="text-xl font-extrabold text-blue-500">{{ sScore }}%</span>
            </div>
            <div class="h-2.5 rounded-full bg-slate-100 dark:bg-white/5 overflow-hidden relative z-10">
              <div class="h-full rounded-full bg-blue-500 transition-all duration-1000 ease-out" :style="{ width: sScore + '%' }"></div>
            </div>
          </div>
        </div>

        <!-- History Tabs Card -->
        <BaseCard :padded="false">
          <template #header><TabGroup :tabs="rtabs" v-model:active="rtab" variant="pills" /></template>
          <div class="p-5 min-h-[300px]">
            <DataTable 
              v-if="rtab==='tasks'" 
              :columns="taskCols" 
              :rows="driverTasks" 
              :selectable="false" 
              :exportable="false" 
              :searchable="false"
            >
              <template #cell-id="{ value }"><span class="font-bold text-slate-700 dark:text-slate-300">#{{ value }}</span></template>
              <template #cell-status="{ value }"><StatusBadge :status="value" /></template>
              <template #cell-created_at="{ value }">
                <span class="text-sm text-slate-500 font-medium">{{ formatDate(value) }}</span>
              </template>
              <template #empty>
                <div class="text-center py-10 flex flex-col items-center justify-center">
                  <div class="w-16 h-16 bg-slate-50 dark:bg-white/5 rounded-full flex items-center justify-center mb-3">
                    <i class="ri-file-list-3-line text-2xl text-slate-400"></i>
                  </div>
                  <h4 class="text-slate-600 dark:text-slate-300 font-bold mb-1">No Task History</h4>
                  <p class="text-sm text-slate-500 max-w-sm">There are no tasks recorded for this driver yet.</p>
                </div>
              </template>
            </DataTable>

            <DataTable 
              v-else 
              :columns="carCols" 
              :rows="driverCarLinks" 
              :selectable="false" 
              :exportable="false" 
              :searchable="false"
            >
              <template #cell-id="{ value }"><span class="font-bold text-slate-700 dark:text-slate-300">#{{ value }}</span></template>
              <template #cell-action="{ value }">
                <span class="px-2.5 py-1 rounded-md text-xs font-extrabold" :class="value === 'LINK' ? 'bg-[#0ab39c]/10 text-[#0ab39c] border border-[#0ab39c]/20' : 'bg-red-500/10 text-red-500 border border-red-500/20'">
                  {{ value }}
                </span>
              </template>
              <template #cell-created_at="{ value }">
                <span class="text-sm text-slate-500 font-medium">{{ formatDate(value) }}</span>
              </template>
              <template #empty>
                <div class="text-center py-10 flex flex-col items-center justify-center">
                  <div class="w-16 h-16 bg-slate-50 dark:bg-white/5 rounded-full flex items-center justify-center mb-3">
                    <i class="ri-car-line text-2xl text-slate-400"></i>
                  </div>
                  <h4 class="text-slate-600 dark:text-slate-300 font-bold mb-1">No Car Links</h4>
                  <p class="text-sm text-slate-500 max-w-sm">This driver hasn't been linked to any cars yet.</p>
                </div>
              </template>
            </DataTable>
          </div>
        </BaseCard>

      </div>
    </div>
  </div>
</template>
