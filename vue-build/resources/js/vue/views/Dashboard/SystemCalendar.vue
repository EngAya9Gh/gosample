<script setup>
/** /admin/system-calendar — FullCalendar month grid (placeholder). Mount FullCalendar in production. */
import { ref, computed } from 'vue';
import BaseCard from '../../components/BaseCard.vue';
const month=ref(new Date(2026,5,1));
const dow=['Sun','Mon','Tue','Wed','Thu','Fri','Sat'];
const events={ 3:[{t:'Morning Route',c:'primary'}], 8:[{t:'Lab Pickup',c:'info'},{t:'Cold Transfer',c:'warning'}], 12:[{t:'Sample Run',c:'primary'}], 17:[{t:'Round-trip',c:'success'},{t:'Pickup',c:'info'},{t:'Delivery',c:'danger'}], 22:[{t:'Morning Route',c:'primary'}], 27:[{t:'Lab Pickup',c:'info'}] };
const cells=computed(()=>{ const y=month.value.getFullYear(),m=month.value.getMonth(); const first=new Date(y,m,1).getDay(); const days=new Date(y,m+1,0).getDate(); const arr=[]; for(let i=0;i<first;i++)arr.push(null); for(let d=1;d<=days;d++)arr.push(d); return arr; });
const cmap={primary:'bg-primary-100 text-primary-700',info:'bg-info/15 text-info',warning:'bg-warning/20 text-amber-700',success:'bg-success/15 text-success',danger:'bg-danger/15 text-danger'};
const title=computed(()=>month.value.toLocaleDateString('en-US',{month:'long',year:'numeric'}));
function step(n){ month.value=new Date(month.value.getFullYear(),month.value.getMonth()+n,1); }
</script>
<template>
  <div>
    <BaseCard :padded="false">
      <template #header>
        <div class="flex items-center gap-3">
          <button @click="step(-1)" class="grid place-items-center w-9 h-9 rounded-lg hover:bg-surface-muted dark:hover:bg-white/10 text-slate-500"><i class="ri-arrow-right-s-line rtl:hidden"></i><i class="ri-arrow-left-s-line hidden rtl:block"></i></button>
          <h3 class="font-semibold text-ink dark:text-slate-100 min-w-40 text-center">{{ title }}</h3>
          <button @click="step(1)" class="grid place-items-center w-9 h-9 rounded-lg hover:bg-surface-muted dark:hover:bg-white/10 text-slate-500"><i class="ri-arrow-left-s-line rtl:hidden"></i><i class="ri-arrow-right-s-line hidden rtl:block"></i></button>
        </div>
      </template>
      <template #actions><span class="text-xs text-slate-400">Scheduled tasks & events</span></template>
      <div class="grid grid-cols-7 border-t border-s border-slate-100 dark:border-white/5">
        <div v-for="d in dow" :key="d" class="px-3 py-2.5 text-xs font-semibold text-slate-400 uppercase tracking-wider border-e border-b border-slate-100 dark:border-white/5 bg-surface-muted/50 dark:bg-white/5">{{ d }}</div>
        <div v-for="(c,i) in cells" :key="i" class="min-h-28 p-2 border-e border-b border-slate-100 dark:border-white/5 transition hover:bg-primary-50/30 dark:hover:bg-white/5" :class="!c?'bg-surface-muted/30 dark:bg-white/0':''">
          <template v-if="c">
            <span class="text-sm font-medium text-slate-500">{{ c }}</span>
            <div class="mt-1 space-y-1">
              <div v-for="(e,j) in (events[c]||[]).slice(0,2)" :key="j" class="text-[10px] font-medium px-1.5 py-0.5 rounded truncate" :class="cmap[e.c]">{{ e.t }}</div>
              <div v-if="(events[c]||[]).length>2" class="text-[10px] text-primary-600 font-medium">+{{ events[c].length-2 }} more</div>
            </div>
          </template>
        </div>
      </div>
    </BaseCard>
  </div>
</template>
