<script setup>
/** /admin/api-ayenatis — API Ayenati list. Auto-faithful to handoff columns. Mock data; wire @query for server-side. */
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
const columns = [{"key":"id","label":"ID","sortable":true,"sticky":"start"},{"key":"url","label":"API URL","mono":true},{"key":"response","label":"Response"},{"key":"flag","label":"Response Flag"}];
const rows = ref([{"id":10428,"url":"—","response":"—","flag":"Item 1"},{"id":10427,"url":"—","response":"—","flag":"Item 2"},{"id":10426,"url":"—","response":"—","flag":"Item 3"},{"id":10425,"url":"—","response":"—","flag":"Item 4"},{"id":10424,"url":"—","response":"—","flag":"Item 5"},{"id":10423,"url":"—","response":"—","flag":"Item 6"},{"id":10422,"url":"—","response":"—","flag":"Item 7"},{"id":10421,"url":"—","response":"—","flag":"Item 8"},{"id":10420,"url":"—","response":"—","flag":"Item 9"},{"id":10419,"url":"—","response":"—","flag":"Item 10"},{"id":10418,"url":"—","response":"—","flag":"Item 11"},{"id":10417,"url":"—","response":"—","flag":"Item 12"},{"id":10416,"url":"—","response":"—","flag":"Item 13"},{"id":10415,"url":"—","response":"—","flag":"Item 14"},{"id":10414,"url":"—","response":"—","flag":"Item 15"},{"id":10413,"url":"—","response":"—","flag":"Item 16"},{"id":10412,"url":"—","response":"—","flag":"Item 17"},{"id":10411,"url":"—","response":"—","flag":"Item 18"},{"id":10410,"url":"—","response":"—","flag":"Item 19"},{"id":10409,"url":"—","response":"—","flag":"Item 20"},{"id":10408,"url":"—","response":"—","flag":"Item 21"},{"id":10407,"url":"—","response":"—","flag":"Item 22"}]);

function doSearch(){ loading.value=true; setTimeout(()=>{loading.value=false; push({type:'success',title:'Filters applied',message:rows.value.length+' records'});},600); }
function doReset(){ filters.value={}; }
function bulkDelete(ids){ rows.value=rows.value.filter(r=>!ids.includes(r.id)); push({type:'success',title:'Bulk delete',message:ids.length+' removed'}); }
function onExport(k){ push({type:'info',title:'Export',message:'Generating '+k.toUpperCase()+'…'}); }
function del(row){ rows.value=rows.value.filter(r=>r!==row); push({type:'success',title:'Deleted'}); }
</script>

<template>
  <div>
    <Breadcrumb title="API Ayenati" :trail="[{ label: 'API Ayenati' }]">
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
