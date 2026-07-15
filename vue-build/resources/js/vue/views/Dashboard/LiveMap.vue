<script setup>
/** /map — Google Map of drivers/cars with status pins + detail modal.
 *  Map is a styled placeholder; mount Google Maps JS API here in production. */
import { ref } from 'vue';
import FilterBar from '../../components/FilterBar.vue';
import FormSelect from '../../components/FormSelect.vue';
import FormInput from '../../components/FormInput.vue';
import BaseModal from '../../components/BaseModal.vue';
import TabGroup from '../../components/TabGroup.vue';
import BaseAvatar from '../../components/BaseAvatar.vue';
const opt=a=>a.map(v=>({value:v,label:v}));
const f=ref({driver:'',imei:'',plate:''});
const pins=[
  { name:'Mohammed Al-Harbi', top:'34%', left:'42%', count:3, state:'delayed' },
  { name:'Fatimah Nasser', top:'52%', left:'58%', count:2, state:'active' },
  { name:'Khalid Otaibi', top:'45%', left:'30%', count:0, state:'idle' },
  { name:'Sara Al-Dosari', top:'62%', left:'48%', count:4, state:'active' },
  { name:'Yousef Qahtani', top:'28%', left:'63%', count:1, state:'delayed' },
];
const open=ref(false); const sel=ref(null); const tab=ref('driver');
const tabs=[{key:'driver',label:'Driver',icon:'ri-user-line'},{key:'tasks',label:'Tasks',icon:'ri-task-line'},{key:'car',label:'Car Details',icon:'ri-car-line'},{key:'track',label:'Car Tracking',icon:'ri-route-line'}];
function show(p){ sel.value=p; tab.value='driver'; open.value=true; }
const pinColor=s=>s==='delayed'?'bg-status-lost':s==='active'?'bg-primary-600':'bg-slate-400';
</script>
<template>
  <div>
    <FilterBar title="Map filters" @search="()=>{}" @reset="()=>f={driver:'',imei:'',plate:''}">
      <FormSelect v-model="f.driver" label="Driver" :options="opt(['Mohammed Al-Harbi','Fatimah Nasser'])" placeholder="Any driver" />
      <FormInput v-model="f.imei" label="IMEI" icon="ri-cpu-line" />
      <FormInput v-model="f.plate" label="Plate Number" icon="ri-car-line" />
    </FilterBar>
    <div class="relative rounded-2xl overflow-hidden shadow-card border border-slate-100 dark:border-white/5 h-[600px]" style="background:linear-gradient(135deg,#dfeef0,#eef4f5)">
      <div class="absolute inset-0 opacity-40" style="background-image:radial-gradient(circle,#0d948822 1px,transparent 1px);background-size:34px 34px"></div>
      <div class="absolute top-4 inset-inline-start-4 bg-surface/90 backdrop-blur rounded-xl shadow-card px-3.5 py-2.5 text-xs">
        <p class="font-semibold text-ink mb-1.5">Driver status</p>
        <div class="flex flex-col gap-1">
          <span class="inline-flex items-center gap-2 text-slate-600"><span class="w-2.5 h-2.5 rounded-full bg-status-lost"></span>Delayed</span>
          <span class="inline-flex items-center gap-2 text-slate-600"><span class="w-2.5 h-2.5 rounded-full bg-primary-600"></span>Active</span>
          <span class="inline-flex items-center gap-2 text-slate-600"><span class="w-2.5 h-2.5 rounded-full bg-slate-400"></span>Idle</span>
        </div>
      </div>
      <button v-for="p in pins" :key="p.name" @click="show(p)" class="absolute -translate-x-1/2 -translate-y-full group" :style="{ top:p.top, left:p.left }">
        <span class="relative grid place-items-center w-9 h-9 rounded-full text-white text-xs font-bold shadow-lg ring-2 ring-white transition-transform group-hover:scale-110" :class="pinColor(p.state)">
          {{ p.count }}
          <span class="absolute -bottom-1 w-2.5 h-2.5 rotate-45" :class="pinColor(p.state)"></span>
        </span>
        <span class="absolute top-full mt-1 left-1/2 -translate-x-1/2 whitespace-nowrap text-[10px] font-medium bg-surface px-1.5 py-0.5 rounded shadow opacity-0 group-hover:opacity-100 transition">{{ p.name }}</span>
      </button>
      <div class="absolute bottom-4 inset-inline-end-4 text-[10px] text-slate-400 bg-surface/70 px-2 py-1 rounded">Google Maps mounts here</div>
    </div>

    <BaseModal v-model="open" :title="sel?.name" icon="ri-user-location-line" size="lg">
      <TabGroup :tabs="tabs" v-model:active="tab" variant="pills" class="mb-4" />
      <div v-if="tab==='driver'" class="flex items-center gap-4">
        <BaseAvatar :name="sel?.name||''" :size="56" />
        <dl class="grid grid-cols-2 gap-x-6 gap-y-2 text-sm flex-1">
          <div><dt class="text-slate-400 text-xs">Full Name</dt><dd class="font-medium">{{ sel?.name }}</dd></div>
          <div><dt class="text-slate-400 text-xs">Mobile</dt><dd class="font-mono" style="direction:ltr">+966 50 123 4567</dd></div>
          <div><dt class="text-slate-400 text-xs">Email</dt><dd>driver@mtc.sa</dd></div>
          <div><dt class="text-slate-400 text-xs">Plate</dt><dd class="font-mono" style="direction:ltr">RUH 2014</dd></div>
        </dl>
      </div>
      <div v-else-if="tab==='tasks'" class="text-sm text-slate-500">{{ sel?.count }} active task(s) — Id, From, To, Status, Samples count.</div>
      <div v-else-if="tab==='car'" class="text-sm text-slate-500">Car #4821 · Plate RUH 2014 · Model Hiace 2023.</div>
      <div v-else class="text-sm text-slate-500">Live telemetry: lat/lng + Temp5–8.</div>
    </BaseModal>
  </div>
</template>
