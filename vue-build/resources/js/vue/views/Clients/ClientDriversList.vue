<script setup>
/** /admin/client-drivers — Client Driver list. Auto-faithful to handoff columns. Mock data; wire @query for server-side. */
import { ref } from 'vue';
import Breadcrumb from '../../components/Breadcrumb.vue';
import DataTable from '../../components/DataTable.vue';
import BaseButton from '../../components/BaseButton.vue';
import { useToast } from '../../composables/useToast';
import { usePermissions } from '../../composables/usePermissions';
import BaseAvatar from '../../components/BaseAvatar.vue';

const { push } = useToast();
const { can, canDelete } = usePermissions();
const loading = ref(false);
const opt = (a) => a.map(v => ({ value: v, label: v }));
const filters = ref({});
const columns = [{"key":"id","label":"ID","sortable":true,"sticky":"start"},{"key":"driver","label":"Driver"},{"key":"client","label":"Client"}];
const rows = ref([{"id":10428,"driver":"Mohammed Al-Harbi","client":"King Faisal Lab"},{"id":10427,"driver":"Fatimah Nasser","client":"Al-Noor Clinic"},{"id":10426,"driver":"Khalid Otaibi","client":"Dallah Hospital"},{"id":10425,"driver":"Sara Al-Dosari","client":"Saudi German"},{"id":10424,"driver":"Yousef Qahtani","client":"Mouwasat Lab"},{"id":10423,"driver":"Noura Faisal","client":"Habib Medical"},{"id":10422,"driver":"Abdullah Zahrani","client":"King Faisal Lab"},{"id":10421,"driver":"Layla Ghamdi","client":"Al-Noor Clinic"},{"id":10420,"driver":"Mohammed Al-Harbi","client":"Dallah Hospital"},{"id":10419,"driver":"Fatimah Nasser","client":"Saudi German"},{"id":10418,"driver":"Khalid Otaibi","client":"Mouwasat Lab"},{"id":10417,"driver":"Sara Al-Dosari","client":"Habib Medical"},{"id":10416,"driver":"Yousef Qahtani","client":"King Faisal Lab"},{"id":10415,"driver":"Noura Faisal","client":"Al-Noor Clinic"},{"id":10414,"driver":"Abdullah Zahrani","client":"Dallah Hospital"},{"id":10413,"driver":"Layla Ghamdi","client":"Saudi German"},{"id":10412,"driver":"Mohammed Al-Harbi","client":"Mouwasat Lab"},{"id":10411,"driver":"Fatimah Nasser","client":"Habib Medical"},{"id":10410,"driver":"Khalid Otaibi","client":"King Faisal Lab"},{"id":10409,"driver":"Sara Al-Dosari","client":"Al-Noor Clinic"},{"id":10408,"driver":"Yousef Qahtani","client":"Dallah Hospital"},{"id":10407,"driver":"Noura Faisal","client":"Saudi German"}]);

function doSearch(){ loading.value=true; setTimeout(()=>{loading.value=false; push({type:'success',title:'Filters applied',message:rows.value.length+' records'});},600); }
function doReset(){ filters.value={}; }
function bulkDelete(ids){ rows.value=rows.value.filter(r=>!ids.includes(r.id)); push({type:'success',title:'Bulk delete',message:ids.length+' removed'}); }
function onExport(k){ push({type:'info',title:'Export',message:'Generating '+k.toUpperCase()+'…'}); }
function del(row){ rows.value=rows.value.filter(r=>r!==row); push({type:'success',title:'Deleted'}); }
</script>

<template>
  <div>
    <Breadcrumb title="Client Drivers" :trail="[{ label: 'Client Drivers' }]">
      <template #actions>
        <BaseButton v-if="can('client_create')" variant="primary" icon="ri-add-line">Add Assignment</BaseButton>
      </template>
    </Breadcrumb>

    <DataTable :columns="columns" :rows="rows" row-key="id" :loading="loading"
      :bulk-actions="canDelete() ? [{ label:'Delete', icon:'ri-delete-bin-line', tone:'danger', event:'bulk-delete' }] : []"
      @bulk-delete="bulkDelete" @export="onExport">
      <template #cell-id="{ value }"><span class="font-semibold text-primary-700 dark:text-primary-300">#{{ value }}</span></template>
      <template #cell-driver="{ value }"><span class="inline-flex items-center gap-2"><BaseAvatar :name="value" :size="26" /><span class="truncate">{{ value }}</span></span></template>
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
