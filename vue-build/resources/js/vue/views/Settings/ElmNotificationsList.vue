<script setup>
/** /admin/elm-notifications — ELM Notification list. Auto-faithful to handoff columns. Mock data; wire @query for server-side. */
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
const columns = [{"key":"id","label":"ID","sortable":true,"sticky":"start"},{"key":"task","label":"Task","mono":true},{"key":"type","label":"Type"},{"key":"response","label":"Response Body"}];
const rows = ref([{"id":10428,"task":"Item 1","type":"Pickup","response":"—"},{"id":10427,"task":"Item 2","type":"Delivery","response":"—"},{"id":10426,"task":"Item 3","type":"Round-trip","response":"—"},{"id":10425,"task":"Item 4","type":"Pickup","response":"—"},{"id":10424,"task":"Item 5","type":"Delivery","response":"—"},{"id":10423,"task":"Item 6","type":"Round-trip","response":"—"},{"id":10422,"task":"Item 7","type":"Pickup","response":"—"},{"id":10421,"task":"Item 8","type":"Delivery","response":"—"},{"id":10420,"task":"Item 9","type":"Round-trip","response":"—"},{"id":10419,"task":"Item 10","type":"Pickup","response":"—"},{"id":10418,"task":"Item 11","type":"Delivery","response":"—"},{"id":10417,"task":"Item 12","type":"Round-trip","response":"—"},{"id":10416,"task":"Item 13","type":"Pickup","response":"—"},{"id":10415,"task":"Item 14","type":"Delivery","response":"—"},{"id":10414,"task":"Item 15","type":"Round-trip","response":"—"},{"id":10413,"task":"Item 16","type":"Pickup","response":"—"},{"id":10412,"task":"Item 17","type":"Delivery","response":"—"},{"id":10411,"task":"Item 18","type":"Round-trip","response":"—"},{"id":10410,"task":"Item 19","type":"Pickup","response":"—"},{"id":10409,"task":"Item 20","type":"Delivery","response":"—"},{"id":10408,"task":"Item 21","type":"Round-trip","response":"—"},{"id":10407,"task":"Item 22","type":"Pickup","response":"—"}]);

function doSearch(){ loading.value=true; setTimeout(()=>{loading.value=false; push({type:'success',title:'Filters applied',message:rows.value.length+' records'});},600); }
function doReset(){ filters.value={}; }
function bulkDelete(ids){ rows.value=rows.value.filter(r=>!ids.includes(r.id)); push({type:'success',title:'Bulk delete',message:ids.length+' removed'}); }
function onExport(k){ push({type:'info',title:'Export',message:'Generating '+k.toUpperCase()+'…'}); }
function del(row){ rows.value=rows.value.filter(r=>r!==row); push({type:'success',title:'Deleted'}); }
</script>

<template>
  <div>
    <Breadcrumb title="ELM Notifications" :trail="[{ label: 'ELM Notifications' }]">
    </Breadcrumb>

    <DataTable :columns="columns" :rows="rows" row-key="id" :loading="loading"
      :bulk-actions="canDelete() ? [{ label:'Delete', icon:'ri-delete-bin-line', tone:'danger', event:'bulk-delete' }] : []"
      @bulk-delete="bulkDelete" @export="onExport">
      <template #cell-id="{ value }"><span class="font-semibold text-primary-700 dark:text-primary-300">#{{ value }}</span></template>
      <template #row-actions="{ row }">
        <div class="inline-flex items-center gap-1">
          <button class="grid place-items-center w-8 h-8 rounded-lg text-info hover:bg-info/10 transition" title="View"><i class="ri-eye-line"></i></button>
          
          <button v-if="canDelete()" @click="del(row)" class="grid place-items-center w-8 h-8 rounded-lg text-danger hover:bg-danger/10 transition" title="Delete"><i class="ri-delete-bin-line"></i></button>
        </div>
      </template>
    </DataTable>
  </div>
</template>
