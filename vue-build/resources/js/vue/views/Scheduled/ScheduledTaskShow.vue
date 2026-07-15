<script setup>
import Breadcrumb from '../../components/Breadcrumb.vue';
import DataTable from '../../components/DataTable.vue';
import BaseButton from '../../components/BaseButton.vue';
import StatusBadge from '../../components/StatusBadge.vue';
const columns=[{key:'seq',label:'#',sticky:'start',width:'56px'},{key:'id',label:'ID',sortable:true},{key:'name',label:'Name'},{key:'status',label:'Status'},{key:'start',label:'Start'},{key:'end',label:'End'},{key:'from',label:'From'},{key:'to',label:'To'},{key:'client',label:'Client'},{key:'hour',label:'Selected Hour'},{key:'type',label:'Task Type'},{key:'days',label:'Days'},{key:'driver',label:'Driver'}];
const rows=Array.from({length:8},(_,i)=>({seq:i+1,id:5200+i,name:'Morning Route',status:i%3?'NEW':'CLOSED',start:'2026-06-0'+(i%9+1),end:'2026-07-0'+(i%9+1),from:'Central Hub',to:'Lab East',client:'King Faisal Lab',hour:'08:0'+(i%9),type:'Pickup',days:'Sun, Tue, Thu',driver:'Mohammed Al-Harbi'}));
</script>
<template>
  <div>
    <Breadcrumb title="Schedule: Morning Route" :trail="[{label:'Scheduled Tasks',href:'#/admin/scheduled-tasks'},{label:'Occurrences'}]">
      <template #actions><BaseButton variant="danger" icon="ri-delete-bin-line">Delete All</BaseButton></template>
    </Breadcrumb>
    <DataTable :columns="columns" :rows="rows" row-key="id">
      <template #cell-id="{ value }"><span class="font-semibold text-primary-700 dark:text-primary-300">#{{ value }}</span></template>
      <template #cell-status="{ value }"><StatusBadge :status="value" /></template>
      <template #row-actions><button class="grid place-items-center w-8 h-8 rounded-lg text-info hover:bg-info/10"><i class="ri-eye-line"></i></button></template>
    </DataTable>
  </div>
</template>
