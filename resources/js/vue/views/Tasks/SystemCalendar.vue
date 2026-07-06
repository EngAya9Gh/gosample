<script setup>
/**
 * views/Tasks/SystemCalendar.vue — System Calendar (Inertia page).
 * Design ported from the MTC reference (month grid, event pills, colors:
 * tasks #0d9488 / swaps #BD6BA7); data is real, derived server-side by
 * App\SystemCalendarController (active tasks + swaps grouped per day).
 * Month / Week / Day views with prev/next/today navigation + Excel export.
 */
import { computed } from 'vue';
import { router } from '@inertiajs/vue3';
import Breadcrumb from '../../components/Breadcrumb.vue';
import BaseButton from '../../components/BaseButton.vue';

const props = defineProps({
  view:     { type: String, default: 'month' },
  anchor:   { type: String, default: '' },        // Y-m-d
  label:    { type: String, default: '' },
  prev:     { type: String, default: '' },
  next:     { type: String, default: '' },
  today:    { type: String, default: '' },
  events:   { type: Object, default: () => ({}) }, // { 'Y-m-d': [ {type,label,count,color} ] }
  dayItems: { type: Array,  default: () => [] },
});

const WEEKDAYS = ['Sun', 'Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat'];
const VIEWS = ['month', 'week', 'day'];

// Event pill backgrounds use the app's brand gradients (same teal as the primary
// buttons / "Today" marker), not the flat accent — so the calendar reads on-brand.
// Reference palette: task = teal, scheduled = blue (#299cdb), swap = mauve, today = orange (#e89e2b).
const TYPE_BG = {
  task:      'linear-gradient(135deg, #0d9488 0%, #005D69 100%)',
  scheduled: 'linear-gradient(135deg, #3aaee0 0%, #1c71a3 100%)',
  swap:      'linear-gradient(135deg, #c77db3 0%, #9560a0 100%)',
  today:     'linear-gradient(135deg, #f7b84b 0%, #e89e2b 100%)',
};
const LEGEND = [['task', 'Tasks'], ['scheduled', 'Scheduled'], ['swap', 'Swaps'], ['today', 'Today']];
const pillBg = (ev) => TYPE_BG[ev.type] || ev.color;

function go(date, view) {
  router.get('/app/admin/system-calendar', { date, view }, { preserveState: true, preserveScroll: true });
}
const goPrev = () => go(props.prev, props.view);
const goNext = () => go(props.next, props.view);
const goToday = () => go(props.today, props.view);
const setView = (v) => go(props.anchor, v);
const openDay = (date) => go(date, 'day');

function doExport() {
  window.location.href = `/app/admin/system-calendar/export?view=${props.view}&date=${props.anchor}`;
}
function addEvent() {
  // A calendar "event" is a task in this system — create it on the Tasks page.
  router.visit('/app/admin/tasks');
}

const key = (d) => `${d.getFullYear()}-${String(d.getMonth() + 1).padStart(2, '0')}-${String(d.getDate()).padStart(2, '0')}`;

// ---- Month grid: leading blanks (Sun-first) + the month's days ----
const monthCells = computed(() => {
  const a = new Date(props.anchor + 'T00:00:00');
  const y = a.getFullYear(); const m = a.getMonth();
  const lead = new Date(y, m, 1).getDay();          // 0 = Sunday
  const days = new Date(y, m + 1, 0).getDate();
  const cells = [];
  for (let i = 0; i < lead; i++) cells.push({ blank: true, key: `b${i}` });
  for (let d = 1; d <= days; d++) {
    const dk = `${y}-${String(m + 1).padStart(2, '0')}-${String(d).padStart(2, '0')}`;
    cells.push({ blank: false, key: dk, day: d, date: dk, isToday: dk === props.today, events: props.events[dk] || [] });
  }
  return cells;
});

// ---- Week: the 7 days (Sun-Sat) around the anchor ----
const weekCells = computed(() => {
  const a = new Date(props.anchor + 'T00:00:00');
  const start = new Date(a); start.setDate(a.getDate() - a.getDay());
  return Array.from({ length: 7 }, (_, i) => {
    const dt = new Date(start); dt.setDate(start.getDate() + i);
    const dk = key(dt);
    return { key: dk, day: dt.getDate(), date: dk, weekday: WEEKDAYS[i], isToday: dk === props.today, events: props.events[dk] || [] };
  });
});

const dayEvents = computed(() => props.events[props.anchor] || []);
</script>

<template>
  <div>
    <Breadcrumb title="System Calendar" :trail="[{ label: 'Tasks' }, { label: 'Calendar' }]">
      <template #actions>
        <BaseButton variant="light" icon="ri-download-2-line" @click="doExport">Export</BaseButton>
        <BaseButton variant="primary" icon="ri-add-line" @click="addEvent">Add Event</BaseButton>
      </template>
    </Breadcrumb>

    <div class="bg-surface dark:bg-surface-dark-card border border-slate-200 dark:border-white/10 rounded-2xl shadow-card p-5">
      <!-- toolbar -->
      <div class="flex items-center justify-between flex-wrap gap-3 mb-4">
        <div class="flex items-center gap-3">
          <button @click="goPrev" class="w-9 h-9 grid place-items-center rounded-[10px] border border-slate-200 dark:border-white/10 text-slate-500 dark:text-slate-400 hover:bg-surface-muted dark:hover:bg-white/5 transition"><i class="ri-arrow-left-s-line text-xl"></i></button>
          <div class="text-[17px] font-bold text-ink dark:text-slate-100 min-w-[140px] text-center">{{ label }}</div>
          <button @click="goNext" class="w-9 h-9 grid place-items-center rounded-[10px] border border-slate-200 dark:border-white/10 text-slate-500 dark:text-slate-400 hover:bg-surface-muted dark:hover:bg-white/5 transition"><i class="ri-arrow-right-s-line text-xl"></i></button>
          <button @click="goToday" class="h-9 px-3 rounded-[10px] border border-slate-200 dark:border-white/10 text-[12.5px] font-semibold text-slate-600 dark:text-slate-300 hover:bg-surface-muted dark:hover:bg-white/5 transition">Today</button>
        </div>
        <!-- view toggle -->
        <div class="inline-flex gap-1 p-[3px] rounded-[9px] bg-surface-muted dark:bg-white/5">
          <button v-for="v in VIEWS" :key="v" @click="setView(v)"
            class="text-[11.5px] font-semibold px-[13px] py-[6px] rounded-[7px] capitalize transition"
            :class="view === v ? 'bg-surface dark:bg-surface-dark-solid text-primary-700 dark:text-primary-300 shadow-sm' : 'text-slate-400 dark:text-slate-500 hover:text-ink dark:hover:text-slate-300'">
            {{ v }}
          </button>
        </div>
      </div>

      <!-- MONTH -->
      <template v-if="view === 'month'">
        <div class="grid grid-cols-7 gap-2 mb-2">
          <div v-for="w in WEEKDAYS" :key="w" class="text-center text-[11px] font-bold uppercase tracking-[.05em] text-slate-400 dark:text-slate-500">{{ w }}</div>
        </div>
        <div class="grid grid-cols-7 gap-2">
          <div v-for="c in monthCells" :key="c.key"
            class="min-h-[98px] rounded-[11px] border p-2 transition"
            :class="c.blank
              ? 'border-transparent'
              : 'border-slate-100 dark:border-white/5 bg-surface-muted/40 dark:bg-white/[.03] hover:bg-surface-muted dark:hover:bg-white/[.06] cursor-pointer'"
            @click="!c.blank && openDay(c.date)">
            <template v-if="!c.blank">
              <div class="flex mb-1.5">
                <span v-if="c.isToday" class="min-w-[24px] h-[24px] px-1 inline-flex items-center justify-center rounded-full text-[12.5px] font-extrabold text-white bg-primary-700">{{ c.day }}</span>
                <span v-else class="text-[12.5px] font-semibold text-ink dark:text-slate-200">{{ c.day }}</span>
              </div>
              <div class="flex flex-col gap-1">
                <div v-for="(ev, i) in c.events" :key="i"
                  class="text-[10px] font-semibold text-white px-[7px] py-[2px] rounded-[6px] whitespace-nowrap overflow-hidden text-ellipsis"
                  :style="{ background: pillBg(ev) }">{{ ev.label }}</div>
              </div>
            </template>
          </div>
        </div>
      </template>

      <!-- WEEK -->
      <template v-else-if="view === 'week'">
        <div class="grid grid-cols-7 gap-2">
          <div v-for="c in weekCells" :key="c.key"
            class="min-h-[220px] rounded-[11px] border border-slate-100 dark:border-white/5 bg-surface-muted/40 dark:bg-white/[.03] hover:bg-surface-muted dark:hover:bg-white/[.06] cursor-pointer p-2.5 transition"
            @click="openDay(c.date)">
            <div class="text-center mb-2">
              <div class="text-[10px] font-bold uppercase tracking-[.05em] text-slate-400 dark:text-slate-500">{{ c.weekday }}</div>
              <div class="mt-0.5">
                <span v-if="c.isToday" class="min-w-[26px] h-[26px] px-1 inline-flex items-center justify-center rounded-full text-[13px] font-extrabold text-white bg-primary-700">{{ c.day }}</span>
                <span v-else class="text-[15px] font-bold text-ink dark:text-slate-200">{{ c.day }}</span>
              </div>
            </div>
            <div class="flex flex-col gap-1">
              <div v-for="(ev, i) in c.events" :key="i"
                class="text-[10.5px] font-semibold text-white px-[7px] py-[3px] rounded-[6px]"
                :style="{ background: pillBg(ev) }">{{ ev.label }}</div>
            </div>
          </div>
        </div>
      </template>

      <!-- DAY -->
      <template v-else>
        <div class="max-w-[640px] mx-auto">
          <div class="flex flex-wrap gap-2 mb-4">
            <div v-if="!dayEvents.length" class="text-sm text-slate-400 dark:text-slate-500">No calendar events on this day.</div>
            <div v-for="(ev, i) in dayEvents" :key="i"
              class="text-[12px] font-semibold text-white px-3 py-1.5 rounded-lg"
              :style="{ background: pillBg(ev) }">{{ ev.label }}</div>
          </div>

          <div class="rounded-[12px] border border-slate-100 dark:border-white/5 overflow-hidden">
            <div class="px-4 py-3 bg-surface-muted dark:bg-surface-dark-solid text-[13px] font-bold text-ink dark:text-slate-100">
              Tasks <span class="text-slate-400 dark:text-slate-500">({{ dayItems.length }})</span>
            </div>
            <div v-if="!dayItems.length" class="py-10 text-center text-sm text-slate-400 dark:text-slate-500">No active tasks on this day.</div>
            <div v-else class="divide-y divide-slate-100 dark:divide-white/5">
              <a v-for="it in dayItems" :key="it.id" :href="`/app/admin/tasks/${it.id}`"
                class="flex items-center gap-3 px-4 py-3 hover:bg-surface-muted/60 dark:hover:bg-white/[.03] transition">
                <span class="w-2 h-2 rounded-full shrink-0" :style="{ background: TYPE_BG.task }"></span>
                <span class="font-mono text-[13px] font-semibold text-ink dark:text-slate-100">{{ it.title }}</span>
                <span class="text-[11px] text-slate-400 dark:text-slate-500">{{ it.type }} · {{ it.status }}</span>
                <span class="ms-auto text-[11px] text-slate-400 dark:text-slate-500">{{ it.time }}</span>
                <i class="ri-arrow-right-s-line rtl:rotate-180 text-slate-300 dark:text-slate-600"></i>
              </a>
            </div>
          </div>
        </div>
      </template>

      <!-- legend -->
      <div class="flex items-center gap-4 mt-4 pt-4 border-t border-slate-100 dark:border-white/5 text-[11.5px] text-slate-500 dark:text-slate-400">
        <span v-for="[t, lbl] in LEGEND" :key="t" class="inline-flex items-center gap-1.5"><span class="w-2.5 h-2.5 rounded-[3px]" :style="{ background: TYPE_BG[t] }"></span>{{ lbl }}</span>
      </div>
    </div>
  </div>
</template>
