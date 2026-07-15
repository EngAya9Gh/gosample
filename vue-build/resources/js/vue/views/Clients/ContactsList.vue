<script setup>
/** /admin/contacts — Contact list. Auto-faithful to handoff columns. Mock data; wire @query for server-side. */
import { ref } from 'vue';
import Breadcrumb from '../../components/Breadcrumb.vue';
import DataTable from '../../components/DataTable.vue';
import BaseButton from '../../components/BaseButton.vue';
import { useToast } from '../../composables/useToast';
import { usePermissions } from '../../composables/usePermissions';

const { push } = useToast();
const { can, canDelete } = usePermissions();
const loading = ref(false);
const opt = (a) => a.map(v => ({ value: v, label: v }));
const filters = ref({});
const columns = [{"key":"id","label":"ID","sortable":true,"sticky":"start"},{"key":"type","label":"Type"},{"key":"email","label":"Email"}];
const rows = ref([{"id":10428,"type":"Pickup","email":"user1@mtc.sa"},{"id":10427,"type":"Delivery","email":"user2@mtc.sa"},{"id":10426,"type":"Round-trip","email":"user3@mtc.sa"},{"id":10425,"type":"Pickup","email":"user4@mtc.sa"},{"id":10424,"type":"Delivery","email":"user5@mtc.sa"},{"id":10423,"type":"Round-trip","email":"user6@mtc.sa"},{"id":10422,"type":"Pickup","email":"user7@mtc.sa"},{"id":10421,"type":"Delivery","email":"user8@mtc.sa"},{"id":10420,"type":"Round-trip","email":"user9@mtc.sa"},{"id":10419,"type":"Pickup","email":"user10@mtc.sa"},{"id":10418,"type":"Delivery","email":"user11@mtc.sa"},{"id":10417,"type":"Round-trip","email":"user12@mtc.sa"},{"id":10416,"type":"Pickup","email":"user13@mtc.sa"},{"id":10415,"type":"Delivery","email":"user14@mtc.sa"},{"id":10414,"type":"Round-trip","email":"user15@mtc.sa"},{"id":10413,"type":"Pickup","email":"user16@mtc.sa"},{"id":10412,"type":"Delivery","email":"user17@mtc.sa"},{"id":10411,"type":"Round-trip","email":"user18@mtc.sa"},{"id":10410,"type":"Pickup","email":"user19@mtc.sa"},{"id":10409,"type":"Delivery","email":"user20@mtc.sa"},{"id":10408,"type":"Round-trip","email":"user21@mtc.sa"},{"id":10407,"type":"Pickup","email":"user22@mtc.sa"}]);

function doSearch(){ loading.value=true; setTimeout(()=>{loading.value=false; push({type:'success',title:'Filters applied',message:rows.value.length+' records'});},600); }
function doReset(){ filters.value={}; }
function bulkDelete(ids){ rows.value=rows.value.filter(r=>!ids.includes(r.id)); push({type:'success',title:'Bulk delete',message:ids.length+' removed'}); }
function onExport(k){ push({type:'info',title:'Export',message:'Generating '+k.toUpperCase()+'…'}); }
function del(row){ rows.value=rows.value.filter(r=>r!==row); push({type:'success',title:'Deleted'}); }
</script>

<template>
  <div>
    <Breadcrumb title="Contacts" :trail="[{ label: 'Contacts' }]">
      <template #actions>
        <BaseButton v-if="can('client_create')" variant="primary" icon="ri-add-line">Add Contact</BaseButton>
      </template>
    </Breadcrumb>

    <DataTable :columns="columns" :rows="rows" row-key="id" :loading="loading"
      :bulk-actions="canDelete() ? [{ label:'Delete', icon:'ri-delete-bin-line', tone:'danger', event:'bulk-delete' }] : []"
      @bulk-delete="bulkDelete" @export="onExport">
      <template #cell-id="{ value }"><span class="font-semibold text-primary-700 dark:text-primary-300">#{{ value }}</span></template>
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
