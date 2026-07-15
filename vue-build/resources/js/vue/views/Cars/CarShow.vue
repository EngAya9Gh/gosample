<script setup>
import { ref } from 'vue';
import Breadcrumb from '../../components/Breadcrumb.vue';
import BaseCard from '../../components/BaseCard.vue';
import TabGroup from '../../components/TabGroup.vue';
import DataTable from '../../components/DataTable.vue';
import BaseButton from '../../components/BaseButton.vue';
import StatusBadge from '../../components/StatusBadge.vue';
const tab=ref('photos');
const tabs=[{key:'photos',label:'Delivery Photos',icon:'ri-image-line'},{key:'history',label:'Car Link History',icon:'ri-links-line'},{key:'tasks',label:'Tasks',icon:'ri-task-line'},{key:'track',label:'Car Tracking',icon:'ri-route-line'}];
const photos=['Signature','Front','Back','Right','Left','Inside 1','Inside 2'];
const enlarge=ref(null);
const histCols=[{key:'id',label:'ID'},{key:'driver',label:'Driver'},{key:'action',label:'Action'},{key:'created',label:'Created At'}];
const histRows=Array.from({length:4},(_,i)=>({id:i+1,driver:'Mohammed Al-Harbi',action:i%2?'Linked':'Unlinked',created:'2026-06-0'+(i+1)}));
const trackCols=[{key:'id',label:'ID'},{key:'addr',label:'Address'},{key:'t5',label:'Temp5'},{key:'t6',label:'Temp6'},{key:'t7',label:'Temp7'},{key:'t8',label:'Temp8'},{key:'created',label:'Created At'}];
const trackRows=Array.from({length:5},(_,i)=>({id:i+1,addr:'24.71'+i+', 46.67'+i,t5:5+i,t6:6+i,t7:-15+i,t8:22+i,created:'08:'+(10+i)}));
</script>
<template>
  <div>
    <Breadcrumb title="Car #4821 — RUH 2014" :trail="[{label:'Cars',href:'#/admin/cars'},{label:'Details'}]">
      <template #actions><BaseButton variant="primary" icon="ri-pencil-line">Edit</BaseButton></template>
    </Breadcrumb>
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-5 mb-5">
      <BaseCard title="Vehicle" icon="ri-car-line">
        <dl class="space-y-2.5 text-sm">
          <div class="flex justify-between"><dt class="text-slate-500">Driver</dt><dd class="font-medium">Mohammed Al-Harbi</dd></div>
          <div class="flex justify-between"><dt class="text-slate-500">IMEI</dt><dd class="font-mono" style="direction:ltr">864920451000</dd></div>
          <div class="flex justify-between"><dt class="text-slate-500">Plate</dt><dd class="font-mono" style="direction:ltr">RUH 2014</dd></div>
          <div class="flex justify-between"><dt class="text-slate-500">Model</dt><dd class="font-medium">Hiace 2023</dd></div>
          <div class="flex justify-between"><dt class="text-slate-500">Color</dt><dd class="font-medium">White</dd></div>
        </dl>
      </BaseCard>
      <BaseCard title="Containers" icon="ri-archive-2-line" class="lg:col-span-2" :padded="false">
        <template #actions><BaseButton variant="light" size="sm" icon="ri-add-line">Add</BaseButton></template>
        <DataTable :columns="[{key:'id',label:'ID'},{key:'type',label:'Type'},{key:'model',label:'Model'},{key:'status',label:'Status'}]" :rows="[{id:1,type:'Refrigerate',model:'CT-200',status:'ENABLED'},{id:2,type:'Frozen',model:'CT-300',status:'ENABLED'},{id:3,type:'Room',model:'CT-100',status:'DISABLED'}]" :selectable="false" :exportable="false" :searchable="false">
          <template #cell-status="{ value }"><StatusBadge :status="value" /></template>
        </DataTable>
      </BaseCard>
    </div>
    <BaseCard :padded="false">
      <template #header><TabGroup :tabs="tabs" v-model:active="tab" /></template>
      <div class="p-5">
        <div v-if="tab==='photos'" class="grid grid-cols-2 sm:grid-cols-4 gap-3">
          <button v-for="p in photos" :key="p" @click="enlarge=p" class="group aspect-square rounded-xl overflow-hidden border border-slate-100 dark:border-white/10 bg-surface-muted dark:bg-white/5 grid place-items-center hover:border-primary-400 transition">
            <div class="text-center text-slate-400 group-hover:text-primary-600"><i class="ri-image-line text-2xl"></i><p class="text-[11px] mt-1">{{ p }}</p></div>
          </button>
        </div>
        <DataTable v-else-if="tab==='history'" :columns="histCols" :rows="histRows" :selectable="false" :exportable="false" :searchable="false" />
        <DataTable v-else-if="tab==='tasks'" :columns="[{key:'id',label:'ID'},{key:'from',label:'From'},{key:'to',label:'To'},{key:'client',label:'Client'},{key:'status',label:'Status'}]" :rows="[{id:10428,from:'Central Hub',to:'Lab East',client:'King Faisal Lab',status:'CLOSED'}]" :selectable="false" :exportable="false" :searchable="false"><template #cell-status="{ value }"><StatusBadge :status="value" /></template></DataTable>
        <DataTable v-else :columns="trackCols" :rows="trackRows" :selectable="false" :exportable="false" :searchable="false">
          <template #cell-addr="{ value }"><a href="#" class="inline-flex items-center gap-1 text-primary-600 font-mono text-xs" style="direction:ltr"><i class="ri-map-pin-line"></i>{{ value }}</a></template>
        </DataTable>
      </div>
    </BaseCard>
  </div>
</template>
