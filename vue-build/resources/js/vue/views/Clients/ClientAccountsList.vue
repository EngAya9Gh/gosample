<script setup>
/** /admin/client-accounts — Client Account list. Auto-faithful to handoff columns. Mock data; wire @query for server-side. */
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
const columns = [{"key":"id","label":"ID","sortable":true,"sticky":"start"},{"key":"client","label":"Client"},{"key":"username","label":"Username","mono":true},{"key":"password","label":"Password","mono":true},{"key":"name","label":"Name"},{"key":"status","label":"Status"}];
const rows = ref([{"id":10428,"client":"King Faisal Lab","username":"Sample Run","password":"Item 1","name":"Sample Run","status":"DISABLED"},{"id":10427,"client":"Al-Noor Clinic","username":"Morning Route","password":"Item 2","name":"Morning Route","status":"ENABLED"},{"id":10426,"client":"Dallah Hospital","username":"Lab Pickup","password":"Item 3","name":"Lab Pickup","status":"ENABLED"},{"id":10425,"client":"Saudi German","username":"Cold Transfer","password":"Item 4","name":"Cold Transfer","status":"ENABLED"},{"id":10424,"client":"Mouwasat Lab","username":"Sample Run","password":"Item 5","name":"Sample Run","status":"DISABLED"},{"id":10423,"client":"Habib Medical","username":"Morning Route","password":"Item 6","name":"Morning Route","status":"ENABLED"},{"id":10422,"client":"King Faisal Lab","username":"Lab Pickup","password":"Item 7","name":"Lab Pickup","status":"ENABLED"},{"id":10421,"client":"Al-Noor Clinic","username":"Cold Transfer","password":"Item 8","name":"Cold Transfer","status":"ENABLED"},{"id":10420,"client":"Dallah Hospital","username":"Sample Run","password":"Item 9","name":"Sample Run","status":"DISABLED"},{"id":10419,"client":"Saudi German","username":"Morning Route","password":"Item 10","name":"Morning Route","status":"ENABLED"},{"id":10418,"client":"Mouwasat Lab","username":"Lab Pickup","password":"Item 11","name":"Lab Pickup","status":"ENABLED"},{"id":10417,"client":"Habib Medical","username":"Cold Transfer","password":"Item 12","name":"Cold Transfer","status":"ENABLED"},{"id":10416,"client":"King Faisal Lab","username":"Sample Run","password":"Item 13","name":"Sample Run","status":"DISABLED"},{"id":10415,"client":"Al-Noor Clinic","username":"Morning Route","password":"Item 14","name":"Morning Route","status":"ENABLED"},{"id":10414,"client":"Dallah Hospital","username":"Lab Pickup","password":"Item 15","name":"Lab Pickup","status":"ENABLED"},{"id":10413,"client":"Saudi German","username":"Cold Transfer","password":"Item 16","name":"Cold Transfer","status":"ENABLED"},{"id":10412,"client":"Mouwasat Lab","username":"Sample Run","password":"Item 17","name":"Sample Run","status":"DISABLED"},{"id":10411,"client":"Habib Medical","username":"Morning Route","password":"Item 18","name":"Morning Route","status":"ENABLED"},{"id":10410,"client":"King Faisal Lab","username":"Lab Pickup","password":"Item 19","name":"Lab Pickup","status":"ENABLED"},{"id":10409,"client":"Al-Noor Clinic","username":"Cold Transfer","password":"Item 20","name":"Cold Transfer","status":"ENABLED"},{"id":10408,"client":"Dallah Hospital","username":"Sample Run","password":"Item 21","name":"Sample Run","status":"DISABLED"},{"id":10407,"client":"Saudi German","username":"Morning Route","password":"Item 22","name":"Morning Route","status":"ENABLED"}]);

function doSearch(){ loading.value=true; setTimeout(()=>{loading.value=false; push({type:'success',title:'Filters applied',message:rows.value.length+' records'});},600); }
function doReset(){ filters.value={}; }
function bulkDelete(ids){ rows.value=rows.value.filter(r=>!ids.includes(r.id)); push({type:'success',title:'Bulk delete',message:ids.length+' removed'}); }
function onExport(k){ push({type:'info',title:'Export',message:'Generating '+k.toUpperCase()+'…'}); }
function del(row){ rows.value=rows.value.filter(r=>r!==row); push({type:'success',title:'Deleted'}); }
</script>

<template>
  <div>
    <Breadcrumb title="Client Accounts" :trail="[{ label: 'Client Accounts' }]">
      <template #actions>
        <BaseButton v-if="can('client_create')" variant="primary" icon="ri-add-line">Add Account</BaseButton>
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
