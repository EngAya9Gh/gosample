<script setup>
/**
 * views/Dashboard/Analytics.vue — main operational overview.
 * Greeting + date range + Create Task · 4 KPI cards · area chart w/ range
 * toggles · samples-temperature donut · top-drivers table · recent-activity rail.
 *
 * Charts: this build ships lightweight inline SVG/CSS placeholders so the file
 * runs with zero chart deps. In the project, swap <ChartArea>/<ChartDonut>
 * for the ApexCharts Vue wrapper (vue3-apexcharts) — data shape is annotated.
 */
import { ref } from 'vue';
import Breadcrumb from '../../components/Breadcrumb.vue';
import BaseCard from '../../components/BaseCard.vue';
import BaseButton from '../../components/BaseButton.vue';
import StatCard from '../../components/StatCard.vue';
import BaseAvatar from '../../components/BaseAvatar.vue';
import StatusBadge from '../../components/StatusBadge.vue';

const ranges = ['ALL', '1M', '6M', '1Y'];
const range = ref('6M');

// --- mock data (wire to API later) ---
const kpis = [
  { label: 'Tasks',   value: 12480, delta: 8.2,  icon: 'ri-task-line',            tone: 'primary', href: '#/admin/tasks' },
  { label: 'Samples', value: 38211, delta: 12.4, icon: 'ri-test-tube-line',       tone: 'info',    href: '#/admin/samples' },
  { label: 'Clients', value: 142,   delta: 2.1,  icon: 'ri-building-line',        tone: 'success', href: '#/admin/clients' },
  { label: 'Cars',    value: 86,    delta: -1.4, icon: 'ri-car-line',             tone: 'warning', href: '#/admin/cars' },
];

// area chart series (ApexCharts: { name, data:[...] })
const area = [38, 52, 47, 63, 58, 74, 69, 82, 78, 91, 86, 97];
const areaMax = Math.max(...area);
const points = area.map((v, i) => `${(i / (area.length - 1)) * 100},${100 - (v / areaMax) * 92}`).join(' ');

// donut: samples temperature distribution
const donut = [
  { label: 'Refrigerate +2→+8', value: 58, color: 'text-info' },
  { label: 'Frozen 0→−18',      value: 27, color: 'text-secondary' },
  { label: 'Room +15→+25',      value: 15, color: 'text-warning' },
];
let acc = 0;
const donutSeg = donut.map((d) => { const seg = { ...d, offset: acc }; acc += d.value; return seg; });

const topDrivers = [
  { name: 'Mohammed Al-Harbi', tasks: 248 },
  { name: 'Fatimah Nasser',    tasks: 221 },
  { name: 'Khalid Otaibi',     tasks: 205 },
  { name: 'Sara Al-Dosari',    tasks: 198 },
  { name: 'Yousef Qahtani',    tasks: 187 },
];

const activity = [
  { title: 'Task #10428 collected', from: 'King Faisal Lab', to: 'Central Hub', driver: 'Mohammed A.', time: '2 min ago', status: 'COLLECTED' },
  { title: 'Task #10427 closed',    from: 'Al-Noor Clinic', to: 'Lab East',    driver: 'Sara D.',     time: '9 min ago', status: 'CLOSED' },
  { title: 'Task #10425 in container', from: 'Dallah Hosp.', to: 'Central Hub', driver: 'Khalid O.',  time: '17 min ago', status: 'IN_CONTAINER' },
  { title: 'Task #10421 new',       from: 'Saudi German',   to: 'Lab West',    driver: 'Yousef Q.',   time: '24 min ago', status: 'NEW' },
];

const hour = new Date().getHours();
const greeting = hour < 12 ? 'Good Morning' : hour < 18 ? 'Good Afternoon' : 'Good Evening';
const today = new Date().toLocaleDateString('en-US', { weekday: 'long', month: 'long', day: 'numeric' });
</script>

<template>
  <div>
    <!-- greeting header -->
    <div class="flex flex-wrap items-end justify-between gap-4 mb-6">
      <div>
        <h1 class="text-2xl font-bold text-ink dark:text-slate-50 tracking-tight">{{ greeting }}, Sara! 👋</h1>
        <p class="text-sm text-slate-500 dark:text-slate-400 mt-1">{{ today }} · Here's your operations overview</p>
      </div>
      <div class="flex items-center gap-2">
        <button class="inline-flex items-center gap-2 h-10 px-3.5 rounded-xl bg-surface dark:bg-slate-800 border border-slate-200 dark:border-white/10 text-sm text-slate-600 dark:text-slate-300 hover:border-primary-400 transition">
          <i class="ri-calendar-line text-primary-600"></i>
          <span class="font-mono text-[13px]" style="direction:ltr">Jan 1 – Jun 27, 2026</span>
        </button>
        <BaseButton variant="primary" icon="ri-add-line">Create Task</BaseButton>
      </div>
    </div>

    <!-- KPI row -->
    <div class="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-4 gap-4 mb-5">
      <div v-for="(k, i) in kpis" :key="k.label" :style="{ animationDelay: i * 70 + 'ms' }" class="animate-fade-in-up">
        <StatCard v-bind="k" />
      </div>
    </div>

    <!-- charts row -->
    <div class="grid grid-cols-1 xl:grid-cols-3 gap-5 mb-5">
      <!-- area chart -->
      <BaseCard class="xl:col-span-2" title="Task Activity" subtitle="Tasks · Samples · Cars" icon="ri-line-chart-line">
        <template #actions>
          <div class="inline-flex gap-1 p-1 rounded-xl bg-surface-muted dark:bg-white/5">
            <button v-for="r in ranges" :key="r" @click="range = r"
              class="px-3 h-8 rounded-lg text-xs font-semibold transition"
              :class="range === r ? 'bg-surface dark:bg-slate-700 text-primary-700 dark:text-primary-300 shadow-sm' : 'text-slate-500 hover:text-ink'">
              {{ r }}
            </button>
          </div>
        </template>

        <div class="flex gap-6 mb-4">
          <div v-for="s in [['Tasks','12,480','text-primary-700'],['Samples','38,211','text-info'],['Cars','86','text-warning']]" :key="s[0]">
            <div class="text-xs text-slate-400">{{ s[0] }}</div>
            <div class="text-lg font-bold" :class="s[2]">{{ s[1] }}</div>
          </div>
        </div>

        <!-- SVG area placeholder (swap for ApexCharts area) -->
        <div class="relative h-52" data-om-raster>
          <svg viewBox="0 0 100 100" preserveAspectRatio="none" class="w-full h-full overflow-visible">
            <defs>
              <linearGradient id="areaFill" x1="0" y1="0" x2="0" y2="1">
                <stop offset="0%" stop-color="#0d9488" stop-opacity="0.28" />
                <stop offset="100%" stop-color="#0d9488" stop-opacity="0" />
              </linearGradient>
            </defs>
            <polygon :points="`0,100 ${points} 100,100`" fill="url(#areaFill)" />
            <polyline :points="points" fill="none" stroke="#0d9488" stroke-width="1.6" vector-effect="non-scaling-stroke" stroke-linejoin="round" stroke-linecap="round" />
          </svg>
          <div class="absolute inset-x-0 bottom-0 flex justify-between text-[10px] text-slate-400 pt-2">
            <span v-for="m in ['Jan','Feb','Mar','Apr','May','Jun']" :key="m">{{ m }}</span>
          </div>
        </div>
      </BaseCard>

      <!-- donut -->
      <BaseCard title="Samples Temperature" subtitle="Distribution by range" icon="ri-temp-cold-line">
        <div class="flex flex-col items-center" data-om-raster>
          <div class="relative w-44 h-44">
            <svg viewBox="0 0 36 36" class="w-full h-full -rotate-90">
              <circle cx="18" cy="18" r="15.9155" fill="none" class="stroke-slate-100 dark:stroke-white/5" stroke-width="3.6" />
              <circle v-for="(s, i) in donutSeg" :key="i" cx="18" cy="18" r="15.9155" fill="none"
                :class="s.color" stroke="currentColor" stroke-width="3.6"
                :stroke-dasharray="`${s.value} ${100 - s.value}`" :stroke-dashoffset="-s.offset" stroke-linecap="round" />
            </svg>
            <div class="absolute inset-0 grid place-items-center">
              <div class="text-center"><div class="text-2xl font-bold text-ink dark:text-slate-50">38.2k</div><div class="text-[11px] text-slate-400">samples</div></div>
            </div>
          </div>
          <div class="w-full mt-4 space-y-2">
            <div v-for="d in donut" :key="d.label" class="flex items-center gap-2.5 text-sm">
              <span class="w-2.5 h-2.5 rounded-full" :class="d.color.replace('text-','bg-')"></span>
              <span class="text-slate-600 dark:text-slate-300 flex-1 truncate">{{ d.label }}</span>
              <span class="font-semibold text-ink dark:text-slate-100">{{ d.value }}%</span>
            </div>
          </div>
        </div>
      </BaseCard>
    </div>

    <!-- bottom row -->
    <div class="grid grid-cols-1 xl:grid-cols-3 gap-5">
      <!-- top drivers -->
      <BaseCard class="xl:col-span-2" title="Top Drivers" subtitle="By completed tasks" icon="ri-user-star-line" :padded="false">
        <div class="divide-y divide-slate-100 dark:divide-white/5">
          <div v-for="(d, i) in topDrivers" :key="d.name" class="flex items-center gap-3 px-5 py-3.5 hover:bg-surface-muted/50 dark:hover:bg-white/5 transition">
            <span class="grid place-items-center w-6 h-6 rounded-lg text-xs font-bold shrink-0"
              :class="i === 0 ? 'bg-amber-100 text-amber-600 dark:bg-amber-500/15 dark:text-amber-400' : i === 1 ? 'bg-slate-100 text-slate-500 dark:bg-white/10 dark:text-slate-300' : i === 2 ? 'bg-orange-100 text-orange-600 dark:bg-orange-500/15 dark:text-orange-400' : 'bg-surface-muted text-slate-400 dark:bg-white/5 dark:text-slate-500'">{{ i + 1 }}</span>
            <BaseAvatar :name="d.name" :size="36" />
            <span class="font-medium text-ink dark:text-slate-100 flex-1 truncate">{{ d.name }}</span>
            <div class="flex items-center gap-2 w-40">
              <div class="flex-1 h-2 rounded-full bg-surface-muted dark:bg-white/10 overflow-hidden">
                <div class="h-full rounded-full bg-gradient-to-r from-primary-400 to-primary-600 transition-all duration-700" :style="{ width: (d.tasks / topDrivers[0].tasks * 100) + '%' }"></div>
              </div>
              <span class="text-sm font-semibold text-ink dark:text-slate-200 tabular-nums w-9 text-end">{{ d.tasks }}</span>
            </div>
          </div>
        </div>
      </BaseCard>

      <!-- recent activity rail -->
      <BaseCard title="Recent Activity" icon="ri-pulse-line" :padded="false">
        <div class="max-h-[360px] overflow-y-auto px-5 py-2">
          <div v-for="(a, i) in activity" :key="i" class="relative ps-5 pb-5 last:pb-2">
            <span class="absolute inset-inline-start-0 top-1.5 w-2.5 h-2.5 rounded-full bg-primary-500 ring-4 ring-primary-50 dark:ring-primary-500/10" style="inset-inline-start:0"></span>
            <span v-if="i < activity.length - 1" class="absolute top-4 w-px h-full bg-slate-100 dark:bg-white/10" style="inset-inline-start:4px"></span>
            <p class="text-[13px] font-semibold text-ink dark:text-slate-100">{{ a.title }}</p>
            <p class="text-xs text-slate-500 mt-0.5"><span class="text-slate-400">{{ a.from }}</span> → <span class="text-slate-400">{{ a.to }}</span></p>
            <div class="flex items-center gap-2 mt-1.5">
              <StatusBadge :status="a.status" :dot="false" />
              <span class="text-[11px] text-danger font-medium">{{ a.driver }}</span>
              <span class="text-[11px] text-slate-400 ms-auto">{{ a.time }}</span>
            </div>
          </div>
        </div>
      </BaseCard>
    </div>
  </div>
</template>
