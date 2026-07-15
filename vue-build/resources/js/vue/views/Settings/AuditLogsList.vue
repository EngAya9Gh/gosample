<script setup>
/** /admin/audit-logs — Audit Log list. Auto-faithful to handoff columns. Mock data; wire @query for server-side. */
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
const columns = [{"key":"id","label":"ID","sortable":true,"sticky":"start"},{"key":"description","label":"Description"},{"key":"subjectId","label":"Subject ID"},{"key":"subjectType","label":"Subject Type"},{"key":"userId","label":"User ID"},{"key":"host","label":"Host","mono":true},{"key":"created","label":"Created At"}];
const rows = ref([{"id":10428,"description":"—","subjectId":"—","subjectType":"Pickup","userId":"Item 1","host":"10.0.0.0","created":"2026-06-27"},{"id":10427,"description":"—","subjectId":"—","subjectType":"Delivery","userId":"Item 2","host":"10.0.1.1","created":"2026-06-26"},{"id":10426,"description":"—","subjectId":"—","subjectType":"Round-trip","userId":"Item 3","host":"10.0.2.2","created":"2026-06-25"},{"id":10425,"description":"—","subjectId":"—","subjectType":"Pickup","userId":"Item 4","host":"10.0.3.3","created":"2026-06-24"},{"id":10424,"description":"—","subjectId":"—","subjectType":"Delivery","userId":"Item 5","host":"10.0.4.4","created":"2026-06-23"},{"id":10423,"description":"—","subjectId":"—","subjectType":"Round-trip","userId":"Item 6","host":"10.0.5.5","created":"2026-06-22"},{"id":10422,"description":"—","subjectId":"—","subjectType":"Pickup","userId":"Item 7","host":"10.0.6.6","created":"2026-06-21"},{"id":10421,"description":"—","subjectId":"—","subjectType":"Delivery","userId":"Item 8","host":"10.0.7.7","created":"2026-06-20"},{"id":10420,"description":"—","subjectId":"—","subjectType":"Round-trip","userId":"Item 9","host":"10.0.8.8","created":"2026-06-19"},{"id":10419,"description":"—","subjectId":"—","subjectType":"Pickup","userId":"Item 10","host":"10.0.9.9","created":"2026-06-18"},{"id":10418,"description":"—","subjectId":"—","subjectType":"Delivery","userId":"Item 11","host":"10.0.10.10","created":"2026-06-17"},{"id":10417,"description":"—","subjectId":"—","subjectType":"Round-trip","userId":"Item 12","host":"10.0.11.11","created":"2026-06-16"},{"id":10416,"description":"—","subjectId":"—","subjectType":"Pickup","userId":"Item 13","host":"10.0.12.12","created":"2026-06-15"},{"id":10415,"description":"—","subjectId":"—","subjectType":"Delivery","userId":"Item 14","host":"10.0.13.13","created":"2026-06-14"},{"id":10414,"description":"—","subjectId":"—","subjectType":"Round-trip","userId":"Item 15","host":"10.0.14.14","created":"2026-06-13"},{"id":10413,"description":"—","subjectId":"—","subjectType":"Pickup","userId":"Item 16","host":"10.0.15.15","created":"2026-06-12"},{"id":10412,"description":"—","subjectId":"—","subjectType":"Delivery","userId":"Item 17","host":"10.0.16.16","created":"2026-06-11"},{"id":10411,"description":"—","subjectId":"—","subjectType":"Round-trip","userId":"Item 18","host":"10.0.17.17","created":"2026-06-10"},{"id":10410,"description":"—","subjectId":"—","subjectType":"Pickup","userId":"Item 19","host":"10.0.18.18","created":"2026-06-09"},{"id":10409,"description":"—","subjectId":"—","subjectType":"Delivery","userId":"Item 20","host":"10.0.19.19","created":"2026-06-08"},{"id":10408,"description":"—","subjectId":"—","subjectType":"Round-trip","userId":"Item 21","host":"10.0.20.20","created":"2026-06-07"},{"id":10407,"description":"—","subjectId":"—","subjectType":"Pickup","userId":"Item 22","host":"10.0.21.21","created":"2026-06-06"}]);

function doSearch(){ loading.value=true; setTimeout(()=>{loading.value=false; push({type:'success',title:'Filters applied',message:rows.value.length+' records'});},600); }
function doReset(){ filters.value={}; }
function bulkDelete(ids){ rows.value=rows.value.filter(r=>!ids.includes(r.id)); push({type:'success',title:'Bulk delete',message:ids.length+' removed'}); }
function onExport(k){ push({type:'info',title:'Export',message:'Generating '+k.toUpperCase()+'…'}); }
function del(row){ rows.value=rows.value.filter(r=>r!==row); push({type:'success',title:'Deleted'}); }
</script>

<template>
  <div>
    <Breadcrumb title="Audit Logs" :trail="[{ label: 'Audit Logs' }]">
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
