<script setup>
/** /admin/client-locations — Client Location list. Auto-faithful to handoff columns. Mock data; wire @query for server-side. */
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
const columns = [{"key":"id","label":"ID","sortable":true,"sticky":"start"},{"key":"client","label":"Client"},{"key":"location","label":"Location"}];
const rows = ref([{"id":10428,"client":"King Faisal Lab","location":"Central Hub"},{"id":10427,"client":"Al-Noor Clinic","location":"Lab East"},{"id":10426,"client":"Dallah Hospital","location":"Lab West"},{"id":10425,"client":"Saudi German","location":"North Depot"},{"id":10424,"client":"Mouwasat Lab","location":"King Faisal Lab"},{"id":10423,"client":"Habib Medical","location":"Al-Noor Clinic"},{"id":10422,"client":"King Faisal Lab","location":"Dallah Hosp."},{"id":10421,"client":"Al-Noor Clinic","location":"Olaya Branch"},{"id":10420,"client":"Dallah Hospital","location":"Central Hub"},{"id":10419,"client":"Saudi German","location":"Lab East"},{"id":10418,"client":"Mouwasat Lab","location":"Lab West"},{"id":10417,"client":"Habib Medical","location":"North Depot"},{"id":10416,"client":"King Faisal Lab","location":"King Faisal Lab"},{"id":10415,"client":"Al-Noor Clinic","location":"Al-Noor Clinic"},{"id":10414,"client":"Dallah Hospital","location":"Dallah Hosp."},{"id":10413,"client":"Saudi German","location":"Olaya Branch"},{"id":10412,"client":"Mouwasat Lab","location":"Central Hub"},{"id":10411,"client":"Habib Medical","location":"Lab East"},{"id":10410,"client":"King Faisal Lab","location":"Lab West"},{"id":10409,"client":"Al-Noor Clinic","location":"North Depot"},{"id":10408,"client":"Dallah Hospital","location":"King Faisal Lab"},{"id":10407,"client":"Saudi German","location":"Al-Noor Clinic"}]);

function doSearch(){ loading.value=true; setTimeout(()=>{loading.value=false; push({type:'success',title:'Filters applied',message:rows.value.length+' records'});},600); }
function doReset(){ filters.value={}; }
function bulkDelete(ids){ rows.value=rows.value.filter(r=>!ids.includes(r.id)); push({type:'success',title:'Bulk delete',message:ids.length+' removed'}); }
function onExport(k){ push({type:'info',title:'Export',message:'Generating '+k.toUpperCase()+'…'}); }
function del(row){ rows.value=rows.value.filter(r=>r!==row); push({type:'success',title:'Deleted'}); }
</script>

<template>
  <div>
    <Breadcrumb title="Client Locations" :trail="[{ label: 'Client Locations' }]">
      <template #actions>
        <BaseButton v-if="can('client_create')" variant="primary" icon="ri-add-line">Add Assignment</BaseButton>
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
