<script setup>
/** /tasks-dashboard — tasks-per-client grouped bar chart + filters. */
import { ref } from 'vue';
import Breadcrumb from '../../components/Breadcrumb.vue';
import FilterBar from '../../components/FilterBar.vue';
import BaseCard from '../../components/BaseCard.vue';
import FormSelect from '../../components/FormSelect.vue';
import FormInput from '../../components/FormInput.vue';
const opt=a=>a.map(v=>({value:v,label:v}));
const f=ref({driver:'',from:'',to:'',fromLoc:'',toLoc:''});
const clients=['King Faisal Lab','Al-Noor Clinic','Dallah Hosp.','Saudi German','Mouwasat','Habib'];
const data=[ [42,28],[35,22],[48,31],[29,18],[38,25],[44,30] ]; // [done, pending]
const max=Math.max(...data.flat());
</script>
<template>
  <div>
    <Breadcrumb title="Tasks Dashboard" :trail="[{label:'Dashboards'},{label:'Tasks'}]" />
    <FilterBar @search="()=>{}" @reset="()=>f={driver:'',from:'',to:'',fromLoc:'',toLoc:''}">
      <FormSelect v-model="f.driver" label="Driver" :options="opt(['Mohammed Al-Harbi','Fatimah Nasser','Khalid Otaibi'])" placeholder="Any driver" />
      <FormInput v-model="f.from" label="Date From" type="date" />
      <FormInput v-model="f.to" label="Date To" type="date" />
      <FormSelect v-model="f.fromLoc" label="From Location" :options="opt(['Central Hub','Lab East'])" placeholder="Any" />
      <FormSelect v-model="f.toLoc" label="To Location" :options="opt(['Lab West','North Depot'])" placeholder="Any" />
    </FilterBar>
    <BaseCard title="Tasks / Clients" subtitle="Completed vs pending per client" icon="ri-bar-chart-grouped-line">
      <div class="flex items-end justify-around gap-4 h-72 pt-4" data-om-raster>
        <div v-for="(d,i) in data" :key="i" class="flex flex-col items-center gap-2 flex-1 min-w-0">
          <div class="flex items-end gap-1.5 h-56">
            <div class="w-5 rounded-t-md bg-gradient-to-t from-primary-600 to-primary-400 transition-all duration-700" :style="{ height: (d[0]/max*100)+'%' }" :title="'Completed: '+d[0]"></div>
            <div class="w-5 rounded-t-md bg-gradient-to-t from-warning to-amber-300 transition-all duration-700" :style="{ height: (d[1]/max*100)+'%' }" :title="'Pending: '+d[1]"></div>
          </div>
          <span class="text-[11px] text-slate-500 text-center truncate w-full">{{ clients[i] }}</span>
        </div>
      </div>
      <div class="flex items-center justify-center gap-5 mt-4 text-xs text-slate-500">
        <span class="inline-flex items-center gap-1.5"><span class="w-3 h-3 rounded bg-primary-500"></span>Completed</span>
        <span class="inline-flex items-center gap-1.5"><span class="w-3 h-3 rounded bg-warning"></span>Pending</span>
      </div>
    </BaseCard>
  </div>
</template>
