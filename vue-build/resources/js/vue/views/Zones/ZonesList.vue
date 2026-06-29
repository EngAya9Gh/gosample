<script setup>
import { ref } from 'vue';
import Breadcrumb from '../../components/Breadcrumb.vue';
import DataTable from '../../components/DataTable.vue';
import BaseButton from '../../components/BaseButton.vue';
import { usePermissions } from '../../composables/usePermissions';
const { canDelete }=usePermissions();
const rows=ref(Array.from({length:6},(_,i)=>({id:i+1,name:['North Riyadh','South Riyadh','East Zone','West Zone','Olaya','Diplomatic Quarter'][i]})));
</script>
<template>
  <div>
    <Breadcrumb title="Zones" :trail="[{label:'Zones'}]"><template #actions><BaseButton variant="primary" icon="ri-add-line">Add Zone</BaseButton></template></Breadcrumb>
    <DataTable :columns="[{key:'id',label:'ID',sortable:true},{key:'name',label:'Name'}]" :rows="rows" row-key="id" :bulk-actions="canDelete()?[{label:'Delete',icon:'ri-delete-bin-line',tone:'danger',event:'bulk-delete'}]:[]">
      <template #cell-id="{ value }"><span class="font-semibold text-primary-700">#{{ value }}</span></template>
      <template #row-actions><div class="inline-flex gap-1"><button class="grid place-items-center w-8 h-8 rounded-lg text-info hover:bg-info/10"><i class="ri-eye-line"></i></button><button class="grid place-items-center w-8 h-8 rounded-lg text-primary-600 hover:bg-primary-50"><i class="ri-pencil-line"></i></button><button class="grid place-items-center w-8 h-8 rounded-lg text-danger hover:bg-danger/10"><i class="ri-delete-bin-line"></i></button></div></template>
    </DataTable>
  </div>
</template>
