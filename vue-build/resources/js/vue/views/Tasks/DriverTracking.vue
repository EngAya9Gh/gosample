<script setup>
import { ref, computed } from 'vue';
import Breadcrumb from '../../components/Breadcrumb.vue';
import BaseAvatar from '../../components/BaseAvatar.vue';
import StatusBadge from '../../components/StatusBadge.vue';
import BaseButton from '../../components/BaseButton.vue';
const q=ref('');
const drivers=[
  { name:'Mohammed Al-Harbi', tasks:[
    { id:10428, client:'King Faisal Lab', from:'Central Hub', to:'Lab East', num:1, time:'07:54', eta:14, status:'CLOSED', state:'done' },
    { id:10431, client:'Al-Noor Clinic', from:'Lab East', to:'North Depot', num:2, time:'09:10', eta:22, status:'COLLECTED', state:'current' },
    { id:10435, client:'Dallah Hosp.', from:'North Depot', to:'Central Hub', num:3, time:'10:30', eta:18, status:'NEW', state:'pending' } ] },
  { name:'Fatimah Nasser', tasks:[
    { id:10429, client:'Saudi German', from:'Olaya Branch', to:'Lab West', num:1, time:'08:00', eta:16, status:'COLLECTED', state:'current' },
    { id:10433, client:'Mouwasat', from:'Lab West', to:'Central Hub', num:2, time:'09:45', eta:25, status:'NEW', state:'pending' } ] },
];
const filtered=computed(()=>drivers.filter(d=>d.name.toLowerCase().includes(q.value.toLowerCase())));
const icon=s=>s==='done'?'ri-check-line':s==='current'?'ri-map-pin-2-fill':'';
</script>
<template>
  <div>
    <Breadcrumb title="Driver Tracking" :trail="[{label:'Tasks'},{label:'Driver Tracking'}]">
      <template #actions><BaseButton variant="light" icon="ri-refresh-line">Refresh</BaseButton></template>
    </Breadcrumb>
    <div class="relative max-w-sm mb-5">
      <i class="ri-search-line absolute top-1/2 -translate-y-1/2 inset-inline-start-3 text-slate-400" style="inset-inline-start:.75rem"></i>
      <input v-model="q" placeholder="Search by driver name…" class="w-full h-11 ps-10 pe-3 rounded-xl border border-slate-200 dark:border-white/10 bg-surface dark:bg-slate-900/40 text-sm focus:ring-2 focus:ring-primary-500/30 focus:outline-none" />
    </div>
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-5">
      <div v-for="d in filtered" :key="d.name" class="bg-surface dark:bg-slate-800/60 rounded-2xl shadow-card border border-slate-100 dark:border-white/5 overflow-hidden">
        <div class="flex items-center gap-3 px-5 py-4 border-b border-slate-100 dark:border-white/5">
          <BaseAvatar :name="d.name" :size="40" />
          <h3 class="font-semibold text-ink dark:text-slate-100 flex-1">{{ d.name }}</h3>
          <span class="inline-flex items-center gap-1.5 text-xs font-medium text-primary-700 bg-primary-50 dark:bg-primary-500/15 dark:text-primary-300 px-2.5 h-6 rounded-full">{{ d.tasks.filter(t=>t.state!=='done').length }} Active</span>
        </div>
        <ol class="p-5 space-y-0">
          <li v-for="(t,i) in d.tasks" :key="t.id" class="relative flex gap-4 pb-5 last:pb-0">
            <span v-if="i<d.tasks.length-1" class="absolute top-7 w-px h-full bg-slate-200 dark:bg-white/10" style="inset-inline-start:13px"></span>
            <span class="relative z-10 grid place-items-center w-7 h-7 rounded-full shrink-0 text-xs font-bold ring-4 ring-surface dark:ring-slate-800" :class="t.state==='done'?'bg-success text-white':t.state==='current'?'bg-primary-600 text-white animate-pulse-ring':'bg-surface-muted text-slate-400 dark:bg-white/10'"><i v-if="icon(t.state)" :class="icon(t.state)"></i><template v-else>{{ t.num }}</template></span>
            <div class="flex-1 min-w-0 -mt-0.5">
              <div class="flex items-center justify-between gap-2"><p class="font-medium text-ink dark:text-slate-100 text-sm" :class="t.state==='done'?'line-through decoration-slate-300':''">{{ t.client }}</p><StatusBadge :status="t.status" :dot="false" /></div>
              <p class="text-xs text-slate-500 mt-1"><span class="text-danger">من: {{ t.from }}</span> · <span class="text-success">إلى: {{ t.to }}</span></p>
              <p class="text-[11px] text-slate-400 mt-0.5">رقم: #{{ t.id }} · {{ t.time }} · <span class="text-primary-600 font-medium">{{ t.eta }} دقيقة</span></p>
            </div>
          </li>
        </ol>
      </div>
    </div>
  </div>
</template>
