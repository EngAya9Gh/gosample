<script setup>
import { ref } from 'vue';
import Breadcrumb from '../../components/Breadcrumb.vue';
import FilterBar from '../../components/FilterBar.vue';
import FormInput from '../../components/FormInput.vue';
import BaseCard from '../../components/BaseCard.vue';
import BaseAvatar from '../../components/BaseAvatar.vue';
const f=ref({from:'',to:''});
const rows=[
  { name:'Mohammed Al-Harbi', days:6, delays:1, ot:120, punct:94 },
  { name:'Fatimah Nasser', days:5, delays:4, ot:45, punct:62 },
  { name:'Khalid Otaibi', days:6, delays:0, ot:200, punct:98 },
  { name:'Sara Al-Dosari', days:4, delays:6, ot:0, punct:41 },
];
const pCls=p=>p>=80?'bg-success/10 text-success':p>=50?'bg-warning/15 text-amber-600':'bg-danger/10 text-danger';
</script>
<template>
  <div>
    <Breadcrumb title="Weekly Performance" :trail="[{label:'Reports'},{label:'Weekly'}]" />
    <FilterBar @search="()=>{}" @reset="()=>f={from:'',to:''}">
      <FormInput v-model="f.from" label="Start Date" type="date" />
      <FormInput v-model="f.to" label="End Date" type="date" />
    </FilterBar>
    <BaseCard title="Driver Consistency Log" icon="ri-line-chart-line" :padded="false">
      <div class="overflow-x-auto"><table class="w-full text-sm">
        <thead><tr class="bg-surface-muted/60 dark:bg-white/5 text-slate-500 text-[12px] uppercase tracking-wider">
          <th class="text-start px-5 py-3 font-semibold">Driver</th><th class="text-center px-3 py-3 font-semibold">Days Worked</th><th class="text-center px-3 py-3 font-semibold">Total Delays</th><th class="text-center px-3 py-3 font-semibold">Overtime (min)</th><th class="text-start px-3 py-3 font-semibold">Avg Punctuality</th><th class="text-end px-5 py-3 font-semibold">Details</th>
        </tr></thead>
        <tbody>
          <tr v-for="r in rows" :key="r.name" class="border-t border-slate-100 dark:border-white/5 hover:bg-surface-muted/40 dark:hover:bg-white/5">
            <td class="px-5 py-3"><div class="flex items-center gap-2.5"><BaseAvatar :name="r.name" :size="34" /><span class="font-medium text-ink dark:text-slate-100">{{ r.name }}</span></div></td>
            <td class="px-3 py-3 text-center tabular-nums">{{ r.days }}</td>
            <td class="px-3 py-3 text-center tabular-nums">{{ r.delays }}</td>
            <td class="px-3 py-3 text-center tabular-nums">{{ r.ot }}</td>
            <td class="px-3 py-3"><span class="inline-flex items-center px-2.5 h-6 rounded-full text-[11.5px] font-semibold" :class="pCls(r.punct)">{{ r.punct }}% Consistent</span></td>
            <td class="px-5 py-3 text-end"><button class="grid place-items-center w-8 h-8 rounded-lg text-info hover:bg-info/10 ms-auto"><i class="ri-eye-line"></i></button></td>
          </tr>
        </tbody>
      </table></div>
    </BaseCard>
  </div>
</template>
