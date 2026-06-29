<script setup>
/** /car-dashboard — live fleet temperature monitor. Auto-refresh every 15s (mock). */
import { ref, onMounted, onBeforeUnmount } from 'vue';
import BaseCard from '../../components/BaseCard.vue';

function sensors(seed){ return [
  { name:'Room', val: 22+(seed%4) },
  { name:'Refrigerate', val: 5+(seed%5) },
  { name:'Frozen', val: -16+(seed%6) },
  { name:'Cabin', val: 24+(seed%3) },
];}
const cars = ref(Array.from({length:8},(_,i)=>({
  name:'Vehicle '+(i+1), imei:'8649'+(20451000+i*7),
  type:['Van','Pickup','SUV'][i%3], plate:['RUH','JED','DMM'][i%3]+' '+(2000+i*13),
  cap: 120+i*10, seats: 2+(i%3), sensors: sensors(i),
})));
function tone(v){ return v>=30?'bg-status-lost':v>=20?'bg-warning':'bg-status-closed'; }
function pct(v){ return Math.max(4, Math.min(100, ((v+20)/60)*100)); }
let timer;
onMounted(()=>{ timer=setInterval(()=>{ cars.value.forEach(c=>c.sensors.forEach(s=>s.val+= (Math.random()<.5?-1:1))); },15000); });
onBeforeUnmount(()=>clearInterval(timer));
</script>

<template>
  <div>
    <div class="flex items-center justify-between gap-3 mb-6">
      <div><h1 class="text-2xl font-bold text-ink dark:text-slate-50">Car Dashboard</h1><p class="text-sm text-slate-500 mt-1">Live cold-chain temperature monitor · refreshes every 15s</p></div>
      <span class="inline-flex items-center gap-2 text-xs font-medium text-success"><span class="w-2 h-2 rounded-full bg-success animate-pulse-ring"></span>Live</span>
    </div>
    <div class="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-4 gap-4">
      <BaseCard v-for="c in cars" :key="c.imei" hover>
        <div class="flex items-start justify-between mb-3">
          <div><h3 class="font-semibold text-ink dark:text-slate-100">{{ c.name }}</h3><p class="text-[11px] font-mono text-slate-400" style="direction:ltr">{{ c.imei }}</p></div>
          <span class="grid place-items-center w-10 h-10 rounded-xl bg-primary-50 text-primary-700 dark:bg-primary-500/15 dark:text-primary-300"><i class="ri-car-line text-lg"></i></span>
        </div>
        <div class="grid grid-cols-2 gap-2 mb-3 text-[11px]">
          <div class="bg-surface-muted dark:bg-white/5 rounded-lg px-2.5 py-1.5"><span class="text-slate-400 block">Type</span><span class="font-medium text-ink dark:text-slate-200">{{ c.type }}</span></div>
          <div class="bg-surface-muted dark:bg-white/5 rounded-lg px-2.5 py-1.5"><span class="text-slate-400 block">Plate</span><span class="font-medium font-mono text-ink dark:text-slate-200" style="direction:ltr">{{ c.plate }}</span></div>
          <div class="bg-surface-muted dark:bg-white/5 rounded-lg px-2.5 py-1.5"><span class="text-slate-400 block">Capacity</span><span class="font-medium text-ink dark:text-slate-200">{{ c.cap }}</span></div>
          <div class="bg-surface-muted dark:bg-white/5 rounded-lg px-2.5 py-1.5"><span class="text-slate-400 block">Seats</span><span class="font-medium text-ink dark:text-slate-200">{{ c.seats }}</span></div>
        </div>
        <div class="space-y-2.5">
          <div v-for="s in c.sensors" :key="s.name">
            <div class="flex items-center justify-between text-xs mb-1"><span class="text-slate-500">{{ s.name }}</span><span class="font-semibold tabular-nums" :class="s.val>=30?'text-status-lost':s.val>=20?'text-amber-600':'text-status-closed'">{{ s.val }}°C</span></div>
            <div class="h-2 rounded-full bg-surface-muted dark:bg-white/10 overflow-hidden"><div class="h-full rounded-full transition-all duration-700" :class="tone(s.val)" :style="{ width: pct(s.val)+'%' }"></div></div>
          </div>
        </div>
      </BaseCard>
    </div>
  </div>
</template>
