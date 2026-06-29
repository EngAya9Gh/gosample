<script setup>
/** /delayeddashboard — real-time alerting board. Audible alarm when delays exist. */
import { ref, onMounted, onBeforeUnmount } from 'vue';
import StatCard from '../../components/StatCard.vue';
import BaseCard from '../../components/BaseCard.vue';
import EmptyState from '../../components/EmptyState.vue';

const kpis = [
  { label:'Lost Samples', value:2, icon:'ri-flask-line', tone:'danger' },
  { label:'Pickup Delayed', value:5, icon:'ri-time-line', tone:'danger' },
  { label:'Delayed in Freezer', value:3, icon:'ri-snowy-line', tone:'danger' },
  { label:'Delayed Delivered', value:4, icon:'ri-truck-line', tone:'danger' },
  { label:'Drop-off Delayed', value:6, icon:'ri-map-pin-time-line', tone:'danger' },
];
const lists = [
  { title:'Lost Samples', icon:'ri-flask-line', rows:[
    { date:'27 Jun', time:'08:42', a:'SM-8841-0052', b:'Bag BG-2231 · Task #10428', c:'Confirmed by King Faisal Lab' },
    { date:'27 Jun', time:'09:15', a:'SM-8841-0107', b:'Bag BG-2240 · Task #10421', c:'Confirmed by Al-Noor' } ] },
  { title:'Pickup Delayed', icon:'ri-time-line', rows:[
    { date:'27 Jun', time:'07:30', a:'Central Hub', b:'Task #10402', c:'Mohammed Al-Harbi' },
    { date:'27 Jun', time:'07:55', a:'Lab East', b:'Task #10406', c:'Sara Al-Dosari' } ] },
  { title:'Drop-off Delayed', icon:'ri-map-pin-time-line', rows:[
    { date:'27 Jun', time:'10:10', a:'North Depot', b:'Task #10377', c:'Khalid Otaibi' } ] },
  { title:'Collected Delayed', icon:'ri-inbox-archive-line', rows:[
    { date:'27 Jun', time:'09:40', a:'Olaya Branch', b:'Task #10390', c:'Yousef Qahtani' } ] },
  { title:'Freezer-out Delayed', icon:'ri-snowy-line', rows:[] },
  { title:'Closed Delayed', icon:'ri-checkbox-circle-line', rows:[
    { date:'27 Jun', time:'11:20', a:'Lab West', b:'Task #10355', c:'Noura Faisal' } ] },
];
const muted = ref(false);
let audio;
onMounted(()=>{ /* looping emergency alarm when delays present (mock — toggle muted to play) */ });
onBeforeUnmount(()=>{ audio && audio.pause(); });
</script>

<template>
  <div>
    <div class="flex flex-wrap items-center justify-between gap-3 mb-6">
      <div>
        <h1 class="text-2xl font-bold text-ink dark:text-slate-50 flex items-center gap-2"><i class="ri-alarm-warning-fill text-danger animate-pulse"></i> Delayed & Alerts</h1>
        <p class="text-sm text-slate-500 mt-1">Live operational alerting · auto-refreshing</p>
      </div>
      <button @click="muted=!muted" class="inline-flex items-center gap-2 h-10 px-3.5 rounded-xl border text-sm font-medium transition" :class="muted?'border-slate-200 text-slate-500':'border-danger/40 text-danger bg-danger/5'">
        <i :class="muted?'ri-volume-mute-line':'ri-volume-up-line'"></i>{{ muted?'Alarm muted':'Alarm on' }}
      </button>
    </div>

    <div class="grid grid-cols-2 md:grid-cols-3 xl:grid-cols-5 gap-4 mb-5">
      <div v-for="(k,i) in kpis" :key="k.label" class="animate-fade-in-up" :style="{ animationDelay: i*60+'ms' }"><StatCard v-bind="k" /></div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-2 gap-5">
      <BaseCard v-for="l in lists" :key="l.title" :title="l.title" :icon="l.icon" :padded="false">
        <div v-if="l.rows.length" class="divide-y divide-slate-100 dark:divide-white/5">
          <div v-for="(r,i) in l.rows" :key="i" class="flex items-center gap-3.5 px-5 py-3.5 hover:bg-surface-muted/40 dark:hover:bg-white/5 transition">
            <div class="grid place-items-center w-12 h-12 rounded-xl bg-danger/10 text-danger shrink-0 leading-none">
              <span class="text-[10px] font-semibold">{{ r.date.split(' ')[0] }}</span>
              <span class="text-[9px] uppercase">{{ r.date.split(' ')[1] }}</span>
            </div>
            <div class="min-w-0 flex-1">
              <p class="text-sm font-semibold text-ink dark:text-slate-100 font-mono" style="direction:ltr">{{ r.a }}</p>
              <p class="text-xs text-slate-500 truncate">{{ r.b }}</p>
              <p class="text-[11px] text-danger font-medium mt-0.5">{{ r.c }}</p>
            </div>
            <span class="text-xs font-mono text-slate-400 shrink-0" style="direction:ltr">{{ r.time }}</span>
          </div>
        </div>
        <EmptyState v-else icon="ri-checkbox-circle-line" title="All clear" message="No delays in this category." />
      </BaseCard>
    </div>
  </div>
</template>
