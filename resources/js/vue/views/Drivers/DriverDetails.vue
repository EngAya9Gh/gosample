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

const tab = ref('shifts'); 
const rtab = ref('tasks');

const tabs = [
  { key: 'shifts', label: 'Working Shifts', icon: 'ri-time-line' },
  { key: 'personal', label: 'Personal Info', icon: 'ri-profile-line' }
];

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

const formatDate = (dateString) => {
  if (!dateString) return '-';
  const d = new Date(dateString);
  return d.toLocaleDateString('en-GB') + ' ' + d.toLocaleTimeString('en-GB', { hour: '2-digit', minute: '2-digit' });
};
</script>

<template>
  <div>
    <Breadcrumb :title="driver.name" :trail="[{label:'Drivers',href:'#/admin/drivers'},{label:'Profile'}]" />
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-5">
      <!-- left -->
      <div class="space-y-5">
        <BaseCard>
          <div class="flex flex-col items-center text-center">
            <BaseAvatar :name="driver.name" :size="88" />
            <h2 class="text-lg font-bold text-ink dark:text-slate-50 mt-3">{{ driver.name }}</h2>
            <p class="text-sm text-slate-400 font-mono" style="direction:ltr">{{ driver.username }}</p>
            <div class="flex items-center gap-2 mt-3">
              <StatusBadge :status="driver.status == 1 ? 'ENABLED' : 'DISABLED'" :label="driver.status == 1 ? 'Active' : 'Inactive'" />
              <span class="inline-flex items-center px-2.5 h-6 rounded-full bg-info/10 text-info text-[11.5px] font-semibold">
                {{ driver.zone ? driver.zone.name_en : 'No Zone' }}
              </span>
            </div>
          </div>
          <div class="grid grid-cols-2 gap-3 mt-5">
            <div class="bg-surface-muted dark:bg-white/5 rounded-xl p-3 text-center">
              <div class="text-xs text-slate-400">Mobile</div>
              <div class="text-sm font-mono font-medium mt-0.5" style="direction:ltr">{{ driver.mobile }}</div>
            </div>
            <div class="bg-surface-muted dark:bg-white/5 rounded-xl p-3 text-center">
              <div class="text-xs text-slate-400">Email</div>
              <div class="text-sm font-medium mt-0.5 truncate px-1" :title="driver.email">{{ driver.email || '-' }}</div>
            </div>
          </div>
        </BaseCard>

        <BaseCard title="Attendance" icon="ri-pie-chart-line">
          <div class="space-y-4">
            <div>
              <div class="flex justify-between text-xs mb-1">
                <span class="text-slate-500">Punctuality Score</span>
                <span class="font-semibold">{{ pScore }}%</span>
              </div>
              <div class="h-2 rounded-full bg-surface-muted dark:bg-white/10 overflow-hidden">
                <div class="h-full rounded-full bg-success transition-all duration-500" :style="{ width: pScore + '%' }"></div>
              </div>
            </div>
            <div>
              <div class="flex justify-between text-xs mb-1">
                <span class="text-slate-500">Shift Completion</span>
                <span class="font-semibold">{{ sScore }}%</span>
              </div>
              <div class="h-2 rounded-full bg-surface-muted dark:bg-white/10 overflow-hidden">
                <div class="h-full rounded-full bg-primary-600 transition-all duration-500" :style="{ width: sScore + '%' }"></div>
              </div>
            </div>
          </div>
        </BaseCard>
      </div>

      <!-- right -->
      <div class="lg:col-span-2 space-y-5">
        <BaseCard :padded="false">
          <template #header><TabGroup :tabs="tabs" v-model:active="tab" /></template>
          <div class="p-5">
            <div v-if="tab==='shifts'">
              <div class="flex items-center justify-between mb-3">
                <h4 class="font-semibold text-ink dark:text-slate-100">Operational Schedule</h4>
                <span class="inline-flex items-center px-2.5 h-6 rounded-full bg-primary-50 text-primary-700 dark:bg-primary-500/15 dark:text-primary-300 text-xs font-semibold">{{ totalShiftHours }} hrs total</span>
              </div>
              <div v-if="shifts.length > 0" class="grid sm:grid-cols-2 gap-3">
                <div v-for="s in shifts" :key="s.id" class="p-4 rounded-xl border border-slate-100 dark:border-white/10">
                  <div class="text-xs text-slate-400 mb-1">Shift {{ s.shift_number }}</div>
                  <div class="text-lg font-mono font-semibold text-ink dark:text-slate-100" style="direction:ltr">{{ s.start_time }} – {{ s.end_time }}</div>
                </div>
              </div>
              <div v-else class="text-center py-6 text-slate-500">
                No active shifts assigned.
              </div>
            </div>
            
            <dl v-else class="space-y-1">
              <div class="flex gap-4 py-2.5 border-b border-slate-100 dark:border-white/5">
                <dt class="w-40 text-sm text-slate-500">National ID</dt>
                <dd class="text-sm font-medium font-mono" style="direction:ltr">{{ driver.national_id || '-' }}</dd>
              </div>
              <div class="flex gap-4 py-2.5 border-b border-slate-100 dark:border-white/5">
                <dt class="w-40 text-sm text-slate-500">Employment Type</dt>
                <dd class="text-sm font-medium">{{ driver.employment_type || '-' }}</dd>
              </div>
              <div class="flex gap-4 py-2.5 border-b border-slate-100 dark:border-white/5">
                <dt class="w-40 text-sm text-slate-500">Language</dt>
                <dd class="text-sm font-medium font-mono" style="direction:ltr">{{ driver.language === 'en' ? 'English' : (driver.language === 'ar' ? 'Arabic' : driver.language || '-') }}</dd>
              </div>
              <div class="flex gap-4 py-2.5">
                <dt class="w-40 text-sm text-slate-500">Join Date</dt>
                <dd class="text-sm font-medium">{{ formatDate(driver.created_at) }}</dd>
              </div>
            </dl>
          </div>
        </BaseCard>

        <BaseCard :padded="false">
          <template #header><TabGroup :tabs="rtabs" v-model:active="rtab" variant="pills" /></template>
          <div class="p-5">
            <DataTable 
              v-if="rtab==='tasks'" 
              :columns="taskCols" 
              :rows="driverTasks" 
              :selectable="false" 
              :exportable="false" 
              :searchable="false"
            >
              <template #cell-id="{ value }"><span class="font-semibold text-primary-700">#{{ value }}</span></template>
              <template #cell-status="{ value }"><StatusBadge :status="value" /></template>
              <template #cell-created_at="{ value }">
                <span class="text-sm">{{ formatDate(value) }}</span>
              </template>
              <template #empty>
                <div class="text-center py-6 text-slate-500">No tasks history available.</div>
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
              <template #cell-id="{ value }"><span class="font-semibold text-slate-600">#{{ value }}</span></template>
              <template #cell-action="{ value }">
                <span class="px-2.5 py-1 rounded-md text-xs font-semibold" :class="value === 'LINK' ? 'bg-success/10 text-success' : 'bg-danger/10 text-danger'">
                  {{ value }}
                </span>
              </template>
              <template #cell-created_at="{ value }">
                <span class="text-sm">{{ formatDate(value) }}</span>
              </template>
              <template #empty>
                <div class="text-center py-6 text-slate-500">No car link history available.</div>
              </template>
            </DataTable>
          </div>
        </BaseCard>
      </div>
    </div>
  </div>
</template>
