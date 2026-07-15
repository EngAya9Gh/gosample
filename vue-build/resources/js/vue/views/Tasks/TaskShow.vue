<script setup>
/** /admin/tasks/{id} — printable single-task journey report with lifecycle timeline + temp chart. */
import { ref } from 'vue';
import BaseButton from '../../components/BaseButton.vue';
import BaseModal from '../../components/BaseModal.vue';
import Timeline from '../../components/Timeline.vue';
import FormInput from '../../components/FormInput.vue';
const editOpen=ref(false);
const steps=[
  { title:'Arrival at Pickup Location', time:'2026-06-27 07:54', meta:'—', state:'done' },
  { title:'Task Information', time:'2026-06-27 07:58', meta:'4 min', state:'done' },
  { title:'Collection Information', time:'2026-06-27 08:12', meta:'14 min', state:'done' },
  { title:'Sample Placement (Container)', time:'2026-06-27 08:30', meta:'18 min', state:'done' },
  { title:'Sample Delivery', time:'2026-06-27 09:05', meta:'35 min', state:'current' },
];
const info=[['Requestor','King Faisal Lab'],['Billed To','King Faisal Lab'],['Pickup Location','Central Hub'],['Delivery Location','Lab East'],['Creation Date','2026-06-27 07:40'],['Type','Round-trip'],['Bag QTY','3'],['Receiving Date','2026-06-27 08:12'],['Driver','Mohammed Al-Harbi'],['Sample QTY','18']];
const bags=[
  { code:'BG-2231', bags:1, samples:7, type:'Blood', temp:'+5°C', container:'Refrigerate' },
  { code:'BG-2240', bags:1, samples:6, type:'Tissue', temp:'-16°C', container:'Frozen' },
  { code:'BG-2255', bags:1, samples:5, type:'Urine', temp:'+21°C', container:'Room' },
];
const temp=[6,5,5,4,5,6,5,4,5,5];
const tmax=Math.max(...temp), tmin=Math.min(...temp);
const pts=temp.map((v,i)=>(i/(temp.length-1))*100+','+(100-((v-tmin)/((tmax-tmin)||1))*80-10)).join(' ');
</script>
<template>
  <div class="max-w-4xl mx-auto">
    <div class="flex items-center justify-between mb-5 print:hidden">
      <h1 class="text-xl font-bold text-ink dark:text-slate-50">Task Report #10428</h1>
      <div class="flex gap-2">
        <BaseButton variant="light" icon="ri-time-line" @click="editOpen=true">Edit Times</BaseButton>
        <BaseButton variant="primary" icon="ri-printer-line" @click="() => window.print()">Print</BaseButton>
      </div>
    </div>

    <div class="bg-surface dark:bg-slate-800/60 rounded-2xl shadow-card border border-slate-100 dark:border-white/5 p-8 print:shadow-none print:border-0">
      <!-- report header -->
      <div class="flex items-center justify-between pb-5 border-b-2 border-primary-700">
        <div class="flex items-center gap-3">
          <span class="grid place-items-center w-12 h-12 rounded-xl bg-primary-700 text-white font-bold text-xl">M</span>
          <div><div class="font-serif text-2xl font-bold text-primary-700">MTC</div><div class="text-[11px] text-slate-400 uppercase tracking-[.2em]">Cold-Chain Logistics</div></div>
        </div>
        <div class="text-end text-sm"><div class="font-semibold text-ink dark:text-slate-100">Journey Report</div><div class="text-slate-400 font-mono" style="direction:ltr">#10428 · 2026-06-27</div></div>
      </div>

      <!-- info grid -->
      <dl class="grid grid-cols-2 sm:grid-cols-3 gap-x-6 gap-y-3 py-6">
        <div v-for="r in info" :key="r[0]"><dt class="text-[11px] text-slate-400 uppercase tracking-wide">{{ r[0] }}</dt><dd class="text-sm font-medium text-ink dark:text-slate-100">{{ r[1] }}</dd></div>
      </dl>

      <!-- timeline -->
      <h2 class="font-serif text-lg font-bold text-ink dark:text-slate-100 mb-4">Lifecycle</h2>
      <Timeline :steps="steps" class="mb-8" />

      <!-- bags table -->
      <h2 class="font-serif text-lg font-bold text-ink dark:text-slate-100 mb-3">Bags & Samples</h2>
      <div class="overflow-x-auto mb-8"><table class="w-full text-sm border border-slate-200 dark:border-white/10 rounded-lg overflow-hidden">
        <thead><tr class="bg-surface-muted dark:bg-white/5 text-slate-500 text-[12px] uppercase tracking-wide"><th class="text-start px-3 py-2.5">Bag Code</th><th class="text-center px-3 py-2.5">Bags</th><th class="text-center px-3 py-2.5">Samples</th><th class="text-start px-3 py-2.5">Type</th><th class="text-center px-3 py-2.5">Temp</th><th class="text-start px-3 py-2.5">Container</th></tr></thead>
        <tbody>
          <tr v-for="b in bags" :key="b.code" class="border-t border-slate-100 dark:border-white/5">
            <td class="px-3 py-2.5 font-mono font-semibold text-primary-700" style="direction:ltr">{{ b.code }}</td>
            <td class="px-3 py-2.5 text-center">{{ b.bags }}</td><td class="px-3 py-2.5 text-center">{{ b.samples }}</td>
            <td class="px-3 py-2.5">{{ b.type }}</td>
            <td class="px-3 py-2.5 text-center font-mono" style="direction:ltr">{{ b.temp }}</td>
            <td class="px-3 py-2.5">{{ b.container }}</td>
          </tr>
        </tbody>
      </table></div>

      <!-- temp chart -->
      <h2 class="font-serif text-lg font-bold text-ink dark:text-slate-100 mb-3">Temperature Log</h2>
      <div class="relative h-40 border border-slate-100 dark:border-white/10 rounded-lg p-3" data-om-raster>
        <svg viewBox="0 0 100 100" preserveAspectRatio="none" class="w-full h-full overflow-visible">
          <polyline :points="pts" fill="none" stroke="#0d9488" stroke-width="1.5" vector-effect="non-scaling-stroke" stroke-linejoin="round" />
        </svg>
        <div class="absolute top-1 inset-inline-end-2 text-[10px] text-slate-400">Refrigeration band +2→+8°C</div>
      </div>
    </div>

    <BaseModal v-model="editOpen" title="Edit Times" icon="ri-time-line" size="sm">
      <div class="space-y-4">
        <FormInput label="Sample Out" type="datetime-local" />
        <FormInput label="Sample Delivery" type="datetime-local" />
      </div>
      <template #footer><BaseButton variant="light" @click="editOpen=false">Cancel</BaseButton><BaseButton variant="primary" icon="ri-save-line" @click="editOpen=false">Save</BaseButton></template>
    </BaseModal>
  </div>
</template>
