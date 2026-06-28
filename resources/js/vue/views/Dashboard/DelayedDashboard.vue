<script setup>
/**
 * views/Dashboard/DelayedDashboard.vue — alerts board for delayed tasks + lost samples.
 * Inertia page; data from App\Http\Controllers\App\DelayedDashboardController@index
 * (mirrors the classic alerts-dashboard.blade.php: server Gate `delayeddashboard`,
 * client_id scoping, 4-day window, 2-min lost-samples cache).
 *
 * ⚠️ KPI cross-wiring is preserved from the classic Blade (parity): the
 * "Collected Delayed" card shows the DELIVERED count and vice-versa.
 */
import { ref, computed, onMounted, onBeforeUnmount } from 'vue';
import Breadcrumb from '../../components/Breadcrumb.vue';
import StatCard from '../../components/StatCard.vue';
import BaseCard from '../../components/BaseCard.vue';
import BaseButton from '../../components/BaseButton.vue';
import EmptyState from '../../components/EmptyState.vue';

const props = defineProps({
  playSound:      { type: Number, default: 0 },
  counts:         { type: Object, default: () => ({}) },
  lostSamples:    { type: Array,  default: () => [] },
  pickupDelayed:  { type: Array,  default: () => [] },
  dropOffDelayed: { type: Array,  default: () => [] },
  collected:      { type: Array,  default: () => [] },
  closed:         { type: Array,  default: () => [] },
});

const kpis = computed(() => {
  const c = props.counts || {};
  return [
    { label: 'Lost Samples',     value: Number(c.lost_samples) || 0,     icon: 'ri-flask-line',          tone: 'danger' },
    { label: 'Pickup Delayed',   value: Number(c.pickup_delayed) || 0,   icon: 'ri-time-line',           tone: 'danger' },
    // cross-wired (see header note): card labelled Collected shows the delivered count
    { label: 'Collected Delayed', value: Number(c.in_freezer_card) || 0, icon: 'ri-archive-2-line',      tone: 'danger' },
    { label: 'Closed Delayed',   value: Number(c.delivered_card) || 0,   icon: 'ri-checkbox-circle-line', tone: 'danger' },
    { label: 'Drop-off Delayed', value: Number(c.drop_off_delayed) || 0, icon: 'ri-map-pin-line',        tone: 'danger' },
  ];
});

// helper to split "Y-m-d H:i:s" into day / weekday / time tile
function tile(dt) {
  if (!dt) return { day: '--', weekday: '', time: '' };
  const d = new Date(dt.replace(' ', 'T'));
  return {
    day: String(d.getDate()).padStart(2, '0'),
    weekday: d.toLocaleDateString('en-US', { weekday: 'long' }),
    time: dt.split(' ')[1] || '',
  };
}

// task list panels (label, rows, the date-field label)
const taskPanels = computed(() => [
  { key: 'pickup',   title: 'Pickup Delayed Tasks',   timeLabel: 'Pickup Time',     rows: props.pickupDelayed,  href: '/admin/pickupdelayed' },
  { key: 'dropoff',  title: 'Drop-off Delayed Tasks', timeLabel: 'Drop-off Time',   rows: props.dropOffDelayed, href: '/admin/dropdelayed' },
  { key: 'collected',title: 'Collected Delayed Tasks',timeLabel: 'Collection Time', rows: props.collected,      href: '/admin/collectedDelayed' },
  { key: 'closed',   title: 'Closed Delayed Tasks',   timeLabel: 'Freezer-out Time',rows: props.closed,         href: '/admin/outfreezerdelayed' },
]);

/* ---- alarm: autoplay loop when delays exist (classic war-room behavior), but
       mutable — the user can stop it and the choice is remembered. ---- */
const audio = ref(null);
const playing = ref(false);
const muted = ref(localStorage.getItem('mtc-alarm-muted') === '1');

function startAlarm() {
  if (!props.playSound || !audio.value || muted.value) return;
  audio.value.play().then(() => { playing.value = true; }).catch(() => { playing.value = false; });
}
function toggleAlarm() {
  if (!audio.value) return;
  if (playing.value) {
    audio.value.pause();
    playing.value = false;
    muted.value = true;
    localStorage.setItem('mtc-alarm-muted', '1');
  } else {
    muted.value = false;
    localStorage.setItem('mtc-alarm-muted', '0');
    startAlarm();
  }
}
onMounted(() => { if (props.playSound && !muted.value) startAlarm(); });
onBeforeUnmount(() => { audio.value?.pause(); });
</script>

<template>
  <div>
    <Breadcrumb title="Delayed Tasks" :trail="[{ label: 'Dashboards' }, { label: 'Delayed Tasks' }]">
      <template #actions>
        <BaseButton v-if="playSound" :variant="playing ? 'danger' : 'light'"
          :icon="playing ? 'ri-volume-up-line' : 'ri-volume-mute-line'" @click="toggleAlarm">
          {{ playing ? 'Mute alarm' : 'Play alarm' }}
        </BaseButton>
      </template>
    </Breadcrumb>

    <!-- alarm (autoplay loop when pickup/drop-off delays exist) -->
    <audio v-if="playSound" ref="audio" loop src="/emergency-alarm.mp3" class="hidden"></audio>

    <!-- KPI row -->
    <div class="grid grid-cols-2 lg:grid-cols-3 xl:grid-cols-5 gap-4 mb-5">
      <div v-for="(k, i) in kpis" :key="k.label" :style="{ animationDelay: i * 60 + 'ms' }" class="animate-fade-in-up">
        <StatCard v-bind="k" />
      </div>
    </div>

    <!-- Lost Samples panel -->
    <div class="grid grid-cols-1 xl:grid-cols-2 gap-5 mb-5">
      <BaseCard :title="`Lost Samples (${lostSamples.length})`" icon="ri-flask-line" :padded="false">
        <template #actions>
          <a href="/admin/lost"><BaseButton variant="light" size="sm" icon="ri-external-link-line">View all</BaseButton></a>
        </template>
        <div v-if="lostSamples.length" class="max-h-[360px] overflow-y-auto divide-y divide-slate-100 dark:divide-white/5">
          <div v-for="s in lostSamples" :key="s.id" class="flex items-center gap-4 px-5 py-3">
            <div class="text-center shrink-0 w-12">
              <div class="text-lg font-bold text-ink dark:text-slate-100">{{ tile(s.updated_at).day }}</div>
              <div class="text-[10px] text-slate-400">{{ tile(s.updated_at).weekday }}</div>
            </div>
            <div class="min-w-0 flex-1 text-[13px]">
              <p class="text-slate-500">Update at: <span class="text-ink dark:text-slate-200">{{ tile(s.updated_at).time }}</span></p>
              <p class="text-slate-500 font-mono" style="direction:ltr">{{ s.barcode_id }} · {{ s.bag_code }}</p>
              <p class="text-slate-500">Confirmed by: <span class="text-ink dark:text-slate-200">{{ s.confirmed_by }}</span></p>
            </div>
            <a :href="`/app/admin/tasks/${s.task_id}`" class="text-primary-700 dark:text-primary-300 font-semibold text-sm shrink-0">#{{ s.task_id }}</a>
          </div>
        </div>
        <EmptyState v-else icon="ri-checkbox-circle-line" title="No lost samples" message="All samples accounted for." />
      </BaseCard>

      <!-- First task panel (Pickup) shares the top row -->
      <BaseCard :title="`${taskPanels[0].title} (${taskPanels[0].rows.length})`" icon="ri-time-line" :padded="false">
        <template #actions>
          <a :href="taskPanels[0].href"><BaseButton variant="light" size="sm" icon="ri-external-link-line">View all</BaseButton></a>
        </template>
        <div v-if="taskPanels[0].rows.length" class="max-h-[360px] overflow-y-auto divide-y divide-slate-100 dark:divide-white/5">
          <div v-for="t in taskPanels[0].rows" :key="t.id" class="flex items-center gap-4 px-5 py-3">
            <div class="text-center shrink-0 w-12">
              <div class="text-lg font-bold text-ink dark:text-slate-100">{{ tile(t.date).day }}</div>
              <div class="text-[10px] text-slate-400">{{ tile(t.date).weekday }}</div>
            </div>
            <div class="min-w-0 flex-1 text-[13px]">
              <p class="text-slate-500">{{ taskPanels[0].timeLabel }}: <span class="text-ink dark:text-slate-200">{{ tile(t.date).time }}</span></p>
              <p class="text-slate-500 truncate">{{ t.to }}</p>
              <a v-if="t.driver" :href="`/app/admin/drivers/${t.driver.id}`" class="text-danger font-medium">{{ t.driver.name }}</a>
            </div>
            <a :href="`/app/admin/tasks/${t.id}`" class="text-primary-700 dark:text-primary-300 font-semibold text-sm shrink-0">#{{ t.id }}</a>
          </div>
        </div>
        <EmptyState v-else icon="ri-checkbox-circle-line" title="None delayed" message="No pickup delays." />
      </BaseCard>
    </div>

    <!-- remaining task panels -->
    <div class="grid grid-cols-1 xl:grid-cols-3 gap-5">
      <BaseCard v-for="p in taskPanels.slice(1)" :key="p.key" :title="`${p.title} (${p.rows.length})`" icon="ri-alarm-warning-line" :padded="false">
        <template #actions>
          <a :href="p.href"><BaseButton variant="light" size="sm" icon="ri-external-link-line">View</BaseButton></a>
        </template>
        <div v-if="p.rows.length" class="max-h-[360px] overflow-y-auto divide-y divide-slate-100 dark:divide-white/5">
          <div v-for="t in p.rows" :key="t.id" class="flex items-center gap-4 px-5 py-3">
            <div class="text-center shrink-0 w-12">
              <div class="text-lg font-bold text-ink dark:text-slate-100">{{ tile(t.date).day }}</div>
              <div class="text-[10px] text-slate-400">{{ tile(t.date).weekday }}</div>
            </div>
            <div class="min-w-0 flex-1 text-[13px]">
              <p class="text-slate-500">{{ p.timeLabel }}: <span class="text-ink dark:text-slate-200">{{ tile(t.date).time }}</span></p>
              <p class="text-slate-500 truncate">{{ t.to }}</p>
              <a v-if="t.driver" :href="`/app/admin/drivers/${t.driver.id}`" class="text-danger font-medium">{{ t.driver.name }}</a>
            </div>
            <a :href="`/app/admin/tasks/${t.id}`" class="text-primary-700 dark:text-primary-300 font-semibold text-sm shrink-0">#{{ t.id }}</a>
          </div>
        </div>
        <EmptyState v-else icon="ri-checkbox-circle-line" title="None delayed" message="Nothing here." />
      </BaseCard>
    </div>
  </div>
</template>
