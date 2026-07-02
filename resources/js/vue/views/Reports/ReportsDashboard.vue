<script setup>
import { ref, watch, computed } from 'vue';
import { router } from '@inertiajs/vue3';
import Breadcrumb from '../../components/Breadcrumb.vue';
import FilterBar from '../../components/FilterBar.vue';
import FormSelect from '../../components/FormSelect.vue';
import FormDate from '../../components/FormDate.vue';
import TabGroup from '../../components/TabGroup.vue';

const props = defineProps({
  tab: { type: String, default: 'performance' },
  reportData: { type: Object, default: () => ({}) },
  filters: { type: Object, default: () => ({}) },
  options: { type: Object, default: () => ({}) }
});

const currentTab = ref(props.tab);
const loading = ref(false);

const localFilters = ref({
  client_id: props.filters.client_id || '',
  driver_id: props.filters.driver_id || '',
  date_from: props.filters.date_from || '',
  date_to: props.filters.date_to || '',
  month: props.filters.month || '',
  search_date: props.filters.search_date || '',
});

const tabs = [
  { key: 'performance', label: 'KPI Performance', icon: 'ri-speed-up-line' },
  { key: 'weekly', label: 'Weekly Consistency', icon: 'ri-calendar-event-line' },
  { key: 'monthly', label: 'Monthly Evaluation', icon: 'ri-medal-line' },
  { key: 'daily', label: 'Daily Report', icon: 'ri-calendar-todo-line' }
];

const driverOpts = computed(() => props.options.drivers?.map(d => ({ label: d.name, value: d.id })) || []);
const clientOpts = computed(() => props.options.clients?.map(c => ({ label: c.english_name, value: c.id })) || []);

function reloadData() {
  loading.value = true;
  router.get('/app/reports', { tab: currentTab.value, ...localFilters.value }, {
    preserveState: true,
    preserveScroll: true,
    onFinish: () => { loading.value = false; }
  });
}

function doSearch() {
  reloadData();
}

function doReset() {
  localFilters.value = {
    client_id: '',
    driver_id: '',
    date_from: '',
    date_to: '',
    month: '',
    search_date: '',
  };
  reloadData();
}

const driverGradients = [
  'from-emerald-400 to-teal-600 shadow-teal-500/20',
  'from-sky-400 to-blue-600 shadow-blue-500/20',
  'from-amber-400 to-orange-500 shadow-orange-500/20',
  'from-primary to-primary-800 shadow-primary/20',
  'from-indigo-400 to-purple-600 shadow-purple-500/20',
  'from-rose-400 to-red-600 shadow-red-500/20'
];
const getDriverGradient = (id) => driverGradients[(id || 0) % driverGradients.length];

watch(currentTab, (newTab) => {
  // Clear filters that don't apply to the new tab, if desired
  if (newTab !== 'performance') {
    localFilters.value.client_id = '';
  }
  reloadData();
});

function onDateRange(range) {
  if (range && range.start && range.end) {
    localFilters.value.date_from = range.start.toISOString().split('T')[0];
    localFilters.value.date_to = range.end.toISOString().split('T')[0];
  } else {
    localFilters.value.date_from = '';
    localFilters.value.date_to = '';
  }
}
</script>

<template>
  <div class="px-4 py-6 md:px-6 lg:px-8 max-w-7xl mx-auto space-y-6">
    <Breadcrumb title="Reports & Analytics" :trail="[{ label: 'Dashboards', href: '/app/dashboard' }, { label: 'Reports' }]" />

    <!-- Tabs and Filters independent of the cards -->
    <div class="flex items-center gap-2 mb-4">
      <TabGroup :tabs="tabs" v-model:active="currentTab" variant="pills" />
    </div>

    <div class="bg-surface dark:bg-surface-dark-card border border-slate-200 dark:border-white/10 rounded-2xl shadow-sm mb-6 relative z-10">
      <FilterBar :loading="loading" subtitle="Refine your report data" @search="doSearch" @reset="doReset">
        <!-- Shared Driver Filter -->
        <FormSelect v-model="localFilters.driver_id" label="Driver" :options="driverOpts" placeholder="All Drivers" />
        
        <!-- KPI Performance Filters -->
        <template v-if="currentTab === 'performance'">
          <FormSelect v-model="localFilters.client_id" label="Client" :options="clientOpts" placeholder="All Clients" />
          <FormDate label="Date Range" mode="range" placeholder="Select range" @range="onDateRange" />
        </template>

        <!-- Weekly Filters -->
        <template v-if="currentTab === 'weekly'">
          <FormDate label="Date Range" mode="range" placeholder="Select week range" @range="onDateRange" />
        </template>

        <!-- Monthly Filters -->
        <template v-if="currentTab === 'monthly'">
          <div class="space-y-1">
            <label class="block text-xs font-semibold text-slate-700 dark:text-slate-300 uppercase tracking-wider">Month</label>
            <input type="month" v-model="localFilters.month" class="w-full h-10 px-3 border border-slate-300 dark:border-white/10 rounded-xl bg-surface dark:bg-surface-dark-card text-sm text-slate-700 dark:text-slate-200 focus:ring-2 focus:ring-primary/20 focus:border-primary outline-none transition-all shadow-sm" />
          </div>
        </template>

        <!-- Daily Filters -->
        <template v-if="currentTab === 'daily'">
          <div class="space-y-1">
            <label class="block text-xs font-semibold text-slate-700 dark:text-slate-300 uppercase tracking-wider">Date</label>
            <input type="date" v-model="localFilters.search_date" class="w-full h-10 px-3 border border-slate-300 dark:border-white/10 rounded-xl bg-surface dark:bg-surface-dark-card text-sm text-slate-700 dark:text-slate-200 focus:ring-2 focus:ring-primary/20 focus:border-primary outline-none transition-all shadow-sm" />
          </div>
        </template>
      </FilterBar>
    </div>

    <div class="relative min-h-[300px]">
      <div v-if="loading" class="absolute inset-0 bg-surface/50 dark:bg-surface-dark-card/50 backdrop-blur-sm z-10 flex items-center justify-center rounded-2xl">
        <div class="flex flex-col items-center gap-3">
          <i class="ri-loader-4-line text-3xl animate-spin text-primary"></i>
          <span class="text-sm font-medium text-slate-500">Loading Report...</span>
        </div>
      </div>

      <!-- PERFORMANCE TAB -->
      <div v-if="currentTab === 'performance'" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
        <div v-if="!reportData.drivers?.length" class="col-span-full py-12 text-center text-slate-500">
          No KPI data available for this selection.
        </div>
        <div v-for="d in reportData.drivers" :key="d.id" class="bg-surface dark:bg-surface-dark-card border border-slate-200 dark:border-white/10 rounded-[16px] p-[18px] hover:shadow-md transition-shadow">
          <div class="flex items-center gap-[11px] mb-[16px]">
            <div :class="['w-[42px] h-[42px] rounded-full text-white flex items-center justify-center text-[13px] font-bold shrink-0 shadow-sm bg-gradient-to-br', getDriverGradient(d.id)]">
              {{ d.name.substring(0, 1).toUpperCase() }}
            </div>
            <div class="flex-1 min-w-0">
              <div class="text-[13.5px] font-bold text-slate-900 dark:text-white truncate">{{ d.name }}</div>
              <span class="inline-flex items-center text-[10px] font-semibold text-emerald-500 bg-emerald-500/10 px-2 py-[2px] rounded-md mt-0.5">Active Carrier</span>
            </div>
          </div>

          <div class="mb-[14px]">
            <div class="flex justify-between mb-[6px]">
              <span class="text-[12px] text-slate-500 dark:text-slate-400">Punctuality (Time Commitment)</span>
              <span class="text-[12.5px] font-bold" :class="d.punctuality >= 80 ? 'text-emerald-500' : (d.punctuality >= 50 ? 'text-amber-500' : 'text-rose-500')">
                {{ d.punctuality }}%
              </span>
            </div>
            <div class="h-2 rounded-full bg-slate-100 dark:bg-white/5 overflow-hidden">
              <div class="h-full rounded-full transition-all duration-1000" :class="d.punctuality >= 80 ? 'bg-emerald-500' : (d.punctuality >= 50 ? 'bg-amber-500' : 'bg-rose-500')" :style="{ width: d.punctuality + '%' }"></div>
            </div>
          </div>

          <div class="flex gap-[10px]">
            <div class="flex-1 bg-surface-muted dark:bg-surface-dark-solid rounded-[11px] p-[11px_13px]">
              <div class="text-[10.5px] text-slate-500 dark:text-slate-400">Operation Speed</div>
              <div class="text-[15px] font-bold text-slate-900 dark:text-white mt-1">{{ d.avg_speed_mins }} <span class="text-[11px] font-medium text-slate-500 dark:text-slate-400">min</span></div>
            </div>
            <div class="flex-1 bg-surface-muted dark:bg-surface-dark-solid rounded-[11px] p-[11px_13px]">
              <div class="text-[10.5px] text-slate-500 dark:text-slate-400">Violations</div>
              <div class="text-[15px] font-bold mt-1" :class="d.punctuality >= 80 ? 'text-emerald-500' : (d.punctuality >= 50 ? 'text-amber-500' : 'text-rose-500')">{{ d.delayed_tasks }} <span v-if="d.delayed_tasks > 0" class="text-[11px] font-medium text-slate-500 dark:text-slate-400">delays</span></div>
            </div>
          </div>
        </div>
      </div>

      <!-- WEEKLY TAB -->
      <div v-if="currentTab === 'weekly'" class="bg-surface dark:bg-surface-dark-card border border-slate-200 dark:border-white/10 rounded-[16px] overflow-hidden">
        <div class="p-[16px_18px] border-b border-slate-200 dark:border-white/10 text-[14px] font-bold text-slate-900 dark:text-white">Driver Consistency Log</div>
        <div class="overflow-x-auto">
          <div class="min-w-[760px]">
            <div class="grid grid-cols-[minmax(200px,1.6fr)_130px_130px_150px_160px] items-center bg-surface-muted dark:bg-surface-dark-solid border-b border-slate-200 dark:border-white/10">
              <div class="p-[12px_16px] text-left text-[11px] font-bold uppercase tracking-[.04em] text-slate-500 dark:text-slate-400">Driver</div>
              <div class="p-[12px_14px] text-left text-[11px] font-bold uppercase tracking-[.04em] text-slate-500 dark:text-slate-400">Days Worked</div>
              <div class="p-[12px_14px] text-left text-[11px] font-bold uppercase tracking-[.04em] text-slate-500 dark:text-slate-400">Total Delays</div>
              <div class="p-[12px_14px] text-left text-[11px] font-bold uppercase tracking-[.04em] text-slate-500 dark:text-slate-400">Overtime</div>
              <div class="p-[12px_16px] text-left text-[11px] font-bold uppercase tracking-[.04em] text-slate-500 dark:text-slate-400">Avg Punctuality</div>
            </div>
            <div v-if="!reportData.drivers?.length" class="py-12 text-center text-slate-500">No weekly data available.</div>
            <div v-for="w in reportData.drivers" :key="w.id" class="grid grid-cols-[minmax(200px,1.6fr)_130px_130px_150px_160px] items-center border-t border-slate-200 dark:border-white/10 hover:bg-surface-muted dark:hover:bg-surface-dark-solid transition-colors">
              <div class="p-[11px_16px]">
                <div class="flex items-center gap-[10px]">
                  <span :class="['w-[32px] h-[32px] rounded-full text-white flex items-center justify-center text-[11px] font-bold shrink-0 shadow-sm bg-gradient-to-br', getDriverGradient(w.id)]">{{ w.name.substring(0, 1).toUpperCase() }}</span>
                  <span class="text-[13px] font-semibold text-slate-900 dark:text-white">{{ w.name }}</span>
                </div>
              </div>
              <div class="p-[11px_14px] text-left text-[13px] font-semibold text-slate-900 dark:text-white">{{ w.days_worked }} days</div>
              <div class="p-[11px_14px] text-left text-[13px] font-bold text-rose-500">{{ w.total_delays }} times</div>
              <div class="p-[11px_14px] text-left text-[13px] font-semibold text-emerald-500">{{ (w.overtime / 60).toFixed(1) }} hrs</div>
              <div class="p-[11px_16px] text-left">
                <span class="inline-flex items-center px-2 py-1 rounded text-[11px] font-bold"
                  :class="w.punctuality >= 80 ? 'bg-emerald-50 text-emerald-600 dark:bg-emerald-500/10' : (w.punctuality >= 50 ? 'bg-amber-50 text-amber-600 dark:bg-amber-500/10' : 'bg-rose-50 text-rose-600 dark:bg-rose-500/10')"
                >
                  {{ w.punctuality }}% Consistent
                </span>
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- MONTHLY TAB -->
      <div v-if="currentTab === 'monthly'" class="bg-surface dark:bg-surface-dark-card border border-slate-200 dark:border-white/10 rounded-[16px] overflow-hidden">
        <div class="p-[16px_18px] border-b border-slate-200 dark:border-white/10 flex justify-between items-center bg-surface-muted dark:bg-surface-dark-solid">
          <span class="text-[13px] font-bold text-slate-700 dark:text-slate-300">Expected Working Days: <span class="text-primary ml-1">{{ reportData.expected_days || 0 }}</span></span>
        </div>
        <div class="overflow-x-auto">
          <div class="min-w-[960px]">
            <div class="flex items-center bg-surface dark:bg-surface-dark-card border-b border-slate-200 dark:border-white/10">
              <div class="w-[80px] p-[12px_16px] text-[11px] font-bold uppercase tracking-[.04em] text-slate-500 dark:text-slate-400">Rank</div>
              <div class="flex-1 p-[12px_16px] text-[11px] font-bold uppercase tracking-[.04em] text-slate-500 dark:text-slate-400">Driver Name</div>
              <div class="w-[100px] text-center p-[12px_14px] text-[11px] font-bold uppercase tracking-[.04em] text-slate-500 dark:text-slate-400">Present</div>
              <div class="w-[100px] text-center p-[12px_14px] text-[11px] font-bold uppercase tracking-[.04em] text-slate-500 dark:text-slate-400">Absent</div>
              <div class="w-[100px] text-center p-[12px_14px] text-[11px] font-bold uppercase tracking-[.04em] text-slate-500 dark:text-slate-400">Late Days</div>
              <div class="w-[100px] text-center p-[12px_14px] text-[11px] font-bold uppercase tracking-[.04em] text-slate-500 dark:text-slate-400">Violations</div>
              <div class="w-[110px] text-center p-[12px_14px] text-[11px] font-bold uppercase tracking-[.04em] text-slate-500 dark:text-slate-400">Delay (Min)</div>
              <div class="w-[110px] text-center p-[12px_14px] text-[11px] font-bold uppercase tracking-[.04em] text-slate-500 dark:text-slate-400">Hrs Balance</div>
              <div class="w-[150px] text-center p-[12px_16px] text-[11px] font-bold uppercase tracking-[.04em] text-slate-500 dark:text-slate-400">Score</div>
            </div>
            <div v-if="!reportData.drivers?.length" class="py-12 text-center text-slate-500">No monthly data available.</div>
            <div v-for="(d, idx) in [...(reportData.drivers || [])].sort((a,b) => b.performance_score - a.performance_score)" :key="d.id" class="flex items-center border-t border-slate-200 dark:border-white/10 hover:bg-surface-muted dark:hover:bg-surface-dark-solid transition-colors">
              <div class="w-[80px] p-[11px_16px]">
                <span v-if="idx === 0" class="inline-flex px-[6px] py-[2px] rounded text-[11px] font-bold bg-amber-100 text-amber-700">🥇 1st</span>
                <span v-else-if="idx === 1" class="inline-flex px-[6px] py-[2px] rounded text-[11px] font-bold bg-slate-100 text-slate-600">🥈 2nd</span>
                <span v-else-if="idx === 2" class="inline-flex px-[6px] py-[2px] rounded text-[11px] font-bold bg-orange-50 text-orange-700">🥉 3rd</span>
                <span v-else class="text-slate-500 text-[12px] font-bold">{{ idx + 1 }}th</span>
              </div>
              <div class="flex-1 p-[11px_16px] text-[13px] font-semibold text-slate-900 dark:text-white">{{ d.name }}</div>
              <div class="w-[100px] p-[11px_14px] text-center text-[13px] font-semibold text-emerald-500">{{ d.days_present }}</div>
              <div class="w-[100px] p-[11px_14px] text-center text-[13px] font-bold text-amber-500">{{ d.days_absent }}</div>
              <div class="w-[100px] p-[11px_14px] text-center text-[13px] font-semibold text-rose-500">{{ d.days_late }}</div>
              <div class="w-[100px] p-[11px_14px] text-center">
                <span class="inline-flex items-center justify-center px-[8px] py-[2px] rounded-full text-[11px] font-bold" :class="d.kpi_violations > 0 ? 'bg-rose-50 text-rose-600' : 'bg-emerald-50 text-emerald-600'">{{ d.kpi_violations }}</span>
              </div>
              <div class="w-[110px] p-[11px_14px] text-center text-[13px] font-semibold text-slate-700 dark:text-slate-300">{{ d.total_delay }}</div>
              <div class="w-[110px] p-[11px_14px] text-center text-[13px] font-bold" :class="d.hrs_balance >= 0 ? 'text-emerald-500' : 'text-rose-500'">
                {{ d.hrs_balance >= 0 ? '+' : '' }}{{ (d.hrs_balance / 60).toFixed(1) }}h
              </div>
              <div class="w-[150px] p-[11px_16px] text-center">
                <div class="flex items-center justify-center gap-[6px]">
                  <div class="h-[6px] w-[60px] bg-slate-100 dark:bg-white/5 rounded-full overflow-hidden">
                    <div class="h-full rounded-full" :class="d.performance_score >= 80 ? 'bg-emerald-500' : (d.performance_score >= 50 ? 'bg-amber-500' : 'bg-rose-500')" :style="{ width: d.performance_score + '%' }"></div>
                  </div>
                  <span class="text-[12px] font-bold" :class="d.performance_score >= 80 ? 'text-emerald-600' : (d.performance_score >= 50 ? 'text-amber-600' : 'text-rose-600')">{{ d.performance_score }}%</span>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- DAILY TAB -->
      <div v-if="currentTab === 'daily'" class="bg-surface dark:bg-surface-dark-card border border-slate-200 dark:border-white/10 rounded-[16px] overflow-hidden">
        <div class="overflow-x-auto">
          <div class="min-w-[800px]">
            <div class="flex items-center bg-surface-muted dark:bg-surface-dark-solid border-b border-slate-200 dark:border-white/10">
              <div class="flex-1 p-[12px_16px] text-[11px] font-bold uppercase tracking-[.04em] text-slate-500 dark:text-slate-400">Driver</div>
              <div class="w-[120px] text-center p-[12px_14px] text-[11px] font-bold uppercase tracking-[.04em] text-slate-500 dark:text-slate-400">Check-in</div>
              <div class="w-[120px] text-center p-[12px_14px] text-[11px] font-bold uppercase tracking-[.04em] text-slate-500 dark:text-slate-400">Check-out</div>
              <div class="w-[130px] text-center p-[12px_14px] text-[11px] font-bold uppercase tracking-[.04em] text-slate-500 dark:text-slate-400">Delay Start</div>
              <div class="w-[130px] text-center p-[12px_14px] text-[11px] font-bold uppercase tracking-[.04em] text-slate-500 dark:text-slate-400">Operational Delays</div>
              <div class="w-[120px] text-center p-[12px_16px] text-[11px] font-bold uppercase tracking-[.04em] text-slate-500 dark:text-slate-400">Status</div>
            </div>
            <div v-if="!reportData.drivers?.length" class="py-12 text-center text-slate-500">No daily data available.</div>
            <div v-for="d in reportData.drivers" :key="d.id" class="flex items-center border-t border-slate-200 dark:border-white/10 hover:bg-surface-muted dark:hover:bg-surface-dark-solid transition-colors">
              <div class="flex-1 p-[11px_16px]">
                <div class="flex items-center gap-[10px]">
                  <span :class="['w-[32px] h-[32px] rounded-full text-white flex items-center justify-center text-[11px] font-bold shrink-0 shadow-sm bg-gradient-to-br', getDriverGradient(d.id)]">{{ d.name.substring(0, 1).toUpperCase() }}</span>
                  <span class="text-[13px] font-semibold text-slate-900 dark:text-white">{{ d.name }}</span>
                </div>
              </div>
              <div class="w-[120px] p-[11px_14px] text-center text-[13px] font-semibold text-slate-700 dark:text-slate-300">{{ d.check_in }}</div>
              <div class="w-[120px] p-[11px_14px] text-center text-[13px] font-semibold text-slate-700 dark:text-slate-300">{{ d.check_out }}</div>
              <div class="w-[130px] p-[11px_14px] text-center">
                <span v-if="d.is_late" class="inline-flex px-[6px] py-[2px] rounded text-[11px] font-bold bg-rose-50 text-rose-600 animate-pulse">{{ d.delay_mins }} min late</span>
                <span v-else-if="d.has_attendance" class="inline-flex px-[6px] py-[2px] rounded text-[11px] font-bold bg-emerald-50 text-emerald-600">On Time</span>
                <span v-else class="text-[13px] text-slate-400">-</span>
              </div>
              <div class="w-[130px] p-[11px_14px] text-center">
                <span class="text-[13px] font-bold" :class="d.delayed_tasks > 0 ? 'text-rose-500' : 'text-slate-400'">{{ d.delayed_tasks }}</span>
              </div>
              <div class="w-[120px] p-[11px_16px] text-center">
                <span class="inline-flex items-center gap-[4px] px-[8px] py-[2px] rounded-md text-[11px] font-bold"
                  :class="d.has_attendance ? 'bg-emerald-50 text-emerald-600 dark:bg-emerald-500/10' : 'bg-surface-muted dark:bg-surface-dark-solid text-slate-500 dark:text-slate-400'"
                >
                  <span class="w-[6px] h-[6px] rounded-full" :class="d.has_attendance ? 'bg-emerald-500' : 'bg-slate-400'"></span>
                  {{ d.has_attendance ? 'Present' : 'Absent' }}
                </span>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>
