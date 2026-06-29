<script setup>
import { ref } from 'vue';
import Breadcrumb from '../../components/Breadcrumb.vue';
import BaseCard from '../../components/BaseCard.vue';
import BaseAvatar from '../../components/BaseAvatar.vue';
const date=ref('2026-06-27');
const rows=[
  { name:'Mohammed Al-Harbi', mob:'+966 50 111 2233', in:'07:58', out:'16:05', delay:'On Time', ops:0, status:'Finished' },
  { name:'Fatimah Nasser', mob:'+966 55 222 3344', in:'08:14', out:'—', delay:'14 min late', ops:2, status:'In Service' },
  { name:'Khalid Otaibi', mob:'+966 53 333 4455', in:'—', out:'—', delay:'Not Started', ops:0, status:'Offline' },
  { name:'Sara Al-Dosari', mob:'+966 56 444 5566', in:'07:45', out:'16:20', delay:'On Time', ops:1, status:'Finished' },
];
const dCls=d=>d==='On Time'?'bg-success/10 text-success':d==='Not Started'?'bg-slate-100 text-slate-500':'bg-danger/10 text-danger animate-pulse';
const sCls=s=>s==='Finished'?'bg-success/10 text-success':s==='In Service'?'bg-info/10 text-info':'bg-slate-100 text-slate-500';
</script>
<template>
  <div>
    <Breadcrumb title="Daily Operations Report" :trail="[{label:'Reports'},{label:'Daily'}]">
      <template #actions><input v-model="date" type="date" class="h-10 px-3 rounded-xl border border-slate-200 dark:border-white/10 bg-surface dark:bg-slate-900/40 text-sm focus:ring-2 focus:ring-primary-500/30 focus:outline-none" /></template>
    </Breadcrumb>
    <div class="grid grid-cols-2 gap-4 mb-5 max-w-md">
      <div class="bg-surface dark:bg-slate-800/60 rounded-2xl shadow-card border border-slate-100 dark:border-white/5 p-5"><div class="text-3xl font-bold text-primary-700 dark:text-primary-300">3</div><div class="text-sm text-slate-500 mt-1">Active Drivers</div></div>
      <div class="bg-surface dark:bg-slate-800/60 rounded-2xl shadow-card border border-slate-100 dark:border-white/5 p-5"><div class="text-3xl font-bold text-danger">2</div><div class="text-sm text-slate-500 mt-1">Total Delays</div></div>
    </div>
    <BaseCard title="Operational Logs" icon="ri-file-list-3-line" :padded="false">
      <div class="overflow-x-auto"><table class="w-full text-sm">
        <thead><tr class="bg-surface-muted/60 dark:bg-white/5 text-slate-500 text-[12px] uppercase tracking-wider">
          <th class="text-start px-5 py-3 font-semibold">Driver</th><th class="text-start px-3 py-3 font-semibold">Check-in</th><th class="text-start px-3 py-3 font-semibold">Check-out</th><th class="text-start px-3 py-3 font-semibold">Delay Start</th><th class="text-center px-3 py-3 font-semibold">Op. Delays</th><th class="text-start px-3 py-3 font-semibold">Status</th><th class="text-end px-5 py-3 font-semibold">Action</th>
        </tr></thead>
        <tbody>
          <tr v-for="r in rows" :key="r.name" class="border-t border-slate-100 dark:border-white/5 hover:bg-surface-muted/40 dark:hover:bg-white/5">
            <td class="px-5 py-3"><div class="flex items-center gap-2.5"><BaseAvatar :name="r.name" :size="34" /><div><div class="font-medium text-ink dark:text-slate-100">{{ r.name }}</div><div class="text-xs text-slate-400 font-mono" style="direction:ltr">{{ r.mob }}</div></div></div></td>
            <td class="px-3 py-3 font-mono" style="direction:ltr">{{ r.in }}</td>
            <td class="px-3 py-3 font-mono" style="direction:ltr">{{ r.out }}</td>
            <td class="px-3 py-3"><span class="inline-flex items-center px-2.5 h-6 rounded-full text-[11.5px] font-semibold" :class="dCls(r.delay)">{{ r.delay }}</span></td>
            <td class="px-3 py-3 text-center"><span class="inline-flex items-center px-2 h-6 rounded-full text-[11.5px] font-semibold" :class="r.ops?'bg-danger/10 text-danger':'bg-slate-100 text-slate-500'">{{ r.ops }}</span></td>
            <td class="px-3 py-3"><span class="inline-flex items-center px-2.5 h-6 rounded-full text-[11.5px] font-semibold" :class="sCls(r.status)">{{ r.status }}</span></td>
            <td class="px-5 py-3 text-end"><button class="grid place-items-center w-8 h-8 rounded-lg text-info hover:bg-info/10 ms-auto"><i class="ri-eye-line"></i></button></td>
          </tr>
        </tbody>
      </table></div>
    </BaseCard>
  </div>
</template>
