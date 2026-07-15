<script setup>
/** /admin/clients — Client list. Auto-faithful to handoff columns. Mock data; wire @query for server-side. */
import { ref } from 'vue';
import Breadcrumb from '../../components/Breadcrumb.vue';
import DataTable from '../../components/DataTable.vue';
import BaseButton from '../../components/BaseButton.vue';
import { useToast } from '../../composables/useToast';
import { usePermissions } from '../../composables/usePermissions';
import StatusBadge from '../../components/StatusBadge.vue';

const { push } = useToast();
const { can, canDelete } = usePermissions();
const loading = ref(false);
const opt = (a) => a.map(v => ({ value: v, label: v }));
const filters = ref({});
const columns = [{"key":"id","label":"ID","sortable":true,"sticky":"start"},{"key":"status","label":"Status"},{"key":"arabicName","label":"Arabic Name"},{"key":"name","label":"English Name"},{"key":"email","label":"Email"},{"key":"address","label":"Address"}];
const rows = ref([{"id":10428,"status":"DISABLED","arabicName":"Sample Run","name":"Sample Run","email":"user1@mtc.sa","address":"Item 1"},{"id":10427,"status":"ENABLED","arabicName":"Morning Route","name":"Morning Route","email":"user2@mtc.sa","address":"Item 2"},{"id":10426,"status":"ENABLED","arabicName":"Lab Pickup","name":"Lab Pickup","email":"user3@mtc.sa","address":"Item 3"},{"id":10425,"status":"ENABLED","arabicName":"Cold Transfer","name":"Cold Transfer","email":"user4@mtc.sa","address":"Item 4"},{"id":10424,"status":"DISABLED","arabicName":"Sample Run","name":"Sample Run","email":"user5@mtc.sa","address":"Item 5"},{"id":10423,"status":"ENABLED","arabicName":"Morning Route","name":"Morning Route","email":"user6@mtc.sa","address":"Item 6"},{"id":10422,"status":"ENABLED","arabicName":"Lab Pickup","name":"Lab Pickup","email":"user7@mtc.sa","address":"Item 7"},{"id":10421,"status":"ENABLED","arabicName":"Cold Transfer","name":"Cold Transfer","email":"user8@mtc.sa","address":"Item 8"},{"id":10420,"status":"DISABLED","arabicName":"Sample Run","name":"Sample Run","email":"user9@mtc.sa","address":"Item 9"},{"id":10419,"status":"ENABLED","arabicName":"Morning Route","name":"Morning Route","email":"user10@mtc.sa","address":"Item 10"},{"id":10418,"status":"ENABLED","arabicName":"Lab Pickup","name":"Lab Pickup","email":"user11@mtc.sa","address":"Item 11"},{"id":10417,"status":"ENABLED","arabicName":"Cold Transfer","name":"Cold Transfer","email":"user12@mtc.sa","address":"Item 12"},{"id":10416,"status":"DISABLED","arabicName":"Sample Run","name":"Sample Run","email":"user13@mtc.sa","address":"Item 13"},{"id":10415,"status":"ENABLED","arabicName":"Morning Route","name":"Morning Route","email":"user14@mtc.sa","address":"Item 14"},{"id":10414,"status":"ENABLED","arabicName":"Lab Pickup","name":"Lab Pickup","email":"user15@mtc.sa","address":"Item 15"},{"id":10413,"status":"ENABLED","arabicName":"Cold Transfer","name":"Cold Transfer","email":"user16@mtc.sa","address":"Item 16"},{"id":10412,"status":"DISABLED","arabicName":"Sample Run","name":"Sample Run","email":"user17@mtc.sa","address":"Item 17"},{"id":10411,"status":"ENABLED","arabicName":"Morning Route","name":"Morning Route","email":"user18@mtc.sa","address":"Item 18"},{"id":10410,"status":"ENABLED","arabicName":"Lab Pickup","name":"Lab Pickup","email":"user19@mtc.sa","address":"Item 19"},{"id":10409,"status":"ENABLED","arabicName":"Cold Transfer","name":"Cold Transfer","email":"user20@mtc.sa","address":"Item 20"},{"id":10408,"status":"DISABLED","arabicName":"Sample Run","name":"Sample Run","email":"user21@mtc.sa","address":"Item 21"},{"id":10407,"status":"ENABLED","arabicName":"Morning Route","name":"Morning Route","email":"user22@mtc.sa","address":"Item 22"}]);

function doSearch(){ loading.value=true; setTimeout(()=>{loading.value=false; push({type:'success',title:'Filters applied',message:rows.value.length+' records'});},600); }
function doReset(){ filters.value={}; }
function bulkDelete(ids){ rows.value=rows.value.filter(r=>!ids.includes(r.id)); push({type:'success',title:'Bulk delete',message:ids.length+' removed'}); }
function onExport(k){ push({type:'info',title:'Export',message:'Generating '+k.toUpperCase()+'…'}); }
function del(row){ rows.value=rows.value.filter(r=>r!==row); push({type:'success',title:'Deleted'}); }
</script>

<template>
  <div>
    <Breadcrumb title="Clients" :trail="[{ label: 'Clients' }]">
      <template #actions>
        <BaseButton v-if="can('client_create')" variant="primary" icon="ri-add-line">Add Client</BaseButton>
      </template>
    </Breadcrumb>

    <DataTable :columns="columns" :rows="rows" row-key="id" :loading="loading"
      :bulk-actions="canDelete() ? [{ label:'Delete', icon:'ri-delete-bin-line', tone:'danger', event:'bulk-delete' }] : []"
      @bulk-delete="bulkDelete" @export="onExport">
      <template #cell-id="{ value }"><span class="font-semibold text-primary-700 dark:text-primary-300">#{{ value }}</span></template>
      <template #cell-status="{ value }"><StatusBadge :status="value" /></template>
      <template #row-actions="{ row }">
        <div class="inline-flex items-center gap-1">
          <button class="grid place-items-center w-8 h-8 rounded-lg text-info hover:bg-info/10 transition" title="View"><i class="ri-eye-line"></i></button>
          <button class="grid place-items-center w-8 h-8 rounded-lg text-primary-600 hover:bg-primary-50 dark:hover:bg-primary-500/10 transition" title="Edit"><i class="ri-pencil-line"></i></button>
          <button v-if="canDelete()" @click="del(row)" class="grid place-items-center w-8 h-8 rounded-lg text-danger hover:bg-danger/10 transition" title="Delete"><i class="ri-delete-bin-line"></i></button>
        </div>
      </template>
    </DataTable>
  </div>
</template>
