<script setup>
import Breadcrumb from '../../components/Breadcrumb.vue';
import BaseCard from '../../components/BaseCard.vue';
import TabGroup from '../../components/TabGroup.vue';
import DataTable from '../../components/DataTable.vue';
import { ref } from 'vue';
const tab=ref('info');
const tabs=[{key:'info',label:'Details',icon:'ri-information-line'},{key:'clients',label:'Clients',icon:'ri-briefcase-line'}];
const info=[['Name','Central Hub'],['Arabic Name','المركز الرئيسي'],['Description','Main collection hub'],['Lat','24.71360'],['Lng','46.67530'],['Mobile','+966 50 100 0000'],['City','Riyadh — الرياض'],['Neighborhood','Al-Olaya'],['Status','Active']];
</script>
<template>
  <div>
    <Breadcrumb title="Central Hub" :trail="[{label:'Locations',href:'#/admin/locations'},{label:'Details'}]" />
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-5">
      <BaseCard :padded="false" class="lg:col-span-2">
        <template #header><TabGroup :tabs="tabs" v-model:active="tab" /></template>
        <div class="p-5">
          <dl v-if="tab==='info'">
            <div v-for="r in info" :key="r[0]" class="flex gap-4 py-2.5 border-b border-slate-100 dark:border-white/5 last:border-0"><dt class="w-40 text-sm text-slate-500">{{ r[0] }}</dt><dd class="text-sm font-medium" :class="['Lat','Lng','Mobile'].includes(r[0])?'font-mono':''" :style="['Lat','Lng','Mobile'].includes(r[0])?'direction:ltr':''">{{ r[1] }}</dd></div>
          </dl>
          <DataTable v-else :columns="[{key:'id',label:'ID'},{key:'name',label:'Client'},{key:'email',label:'Email'}]" :rows="[{id:1,name:'King Faisal Lab',email:'kf@lab.sa'},{id:2,name:'Al-Noor Clinic',email:'noor@clinic.sa'}]" :selectable="false" :exportable="false" :searchable="false" />
        </div>
      </BaseCard>
      <BaseCard title="Barcode" icon="ri-barcode-line">
        <div class="text-center">
          <span class="grid place-items-center w-12 h-12 rounded-xl bg-primary-700 text-white font-bold text-xl mx-auto mb-3">M</span>
          <p class="text-sm font-medium text-ink dark:text-slate-100 mb-2">Central Hub</p>
          <div class="flex items-end justify-center gap-px h-16 mb-2">
            <span v-for="n in 40" :key="n" class="bg-ink dark:bg-slate-200" :style="{ width:'2px', height:(40+(n*37%60))+'%' }"></span>
          </div>
          <p class="font-mono text-xs text-slate-400" style="direction:ltr">LOC-0001</p>
        </div>
      </BaseCard>
    </div>
  </div>
</template>
