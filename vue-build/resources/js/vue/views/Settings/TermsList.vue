<script setup>
/** /admin/terms — Term list. Auto-faithful to handoff columns. Mock data; wire @query for server-side. */
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
const columns = [{"key":"id","label":"ID","sortable":true,"sticky":"start"},{"key":"english","label":"English Text"},{"key":"arabic","label":"Arabic Text"}];
const rows = ref([{"id":10428,"english":"Item 1","arabic":"Item 1"},{"id":10427,"english":"Item 2","arabic":"Item 2"},{"id":10426,"english":"Item 3","arabic":"Item 3"},{"id":10425,"english":"Item 4","arabic":"Item 4"},{"id":10424,"english":"Item 5","arabic":"Item 5"},{"id":10423,"english":"Item 6","arabic":"Item 6"},{"id":10422,"english":"Item 7","arabic":"Item 7"},{"id":10421,"english":"Item 8","arabic":"Item 8"},{"id":10420,"english":"Item 9","arabic":"Item 9"},{"id":10419,"english":"Item 10","arabic":"Item 10"},{"id":10418,"english":"Item 11","arabic":"Item 11"},{"id":10417,"english":"Item 12","arabic":"Item 12"},{"id":10416,"english":"Item 13","arabic":"Item 13"},{"id":10415,"english":"Item 14","arabic":"Item 14"},{"id":10414,"english":"Item 15","arabic":"Item 15"},{"id":10413,"english":"Item 16","arabic":"Item 16"},{"id":10412,"english":"Item 17","arabic":"Item 17"},{"id":10411,"english":"Item 18","arabic":"Item 18"},{"id":10410,"english":"Item 19","arabic":"Item 19"},{"id":10409,"english":"Item 20","arabic":"Item 20"},{"id":10408,"english":"Item 21","arabic":"Item 21"},{"id":10407,"english":"Item 22","arabic":"Item 22"}]);

function doSearch(){ loading.value=true; setTimeout(()=>{loading.value=false; push({type:'success',title:'Filters applied',message:rows.value.length+' records'});},600); }
function doReset(){ filters.value={}; }
function bulkDelete(ids){ rows.value=rows.value.filter(r=>!ids.includes(r.id)); push({type:'success',title:'Bulk delete',message:ids.length+' removed'}); }
function onExport(k){ push({type:'info',title:'Export',message:'Generating '+k.toUpperCase()+'…'}); }
function del(row){ rows.value=rows.value.filter(r=>r!==row); push({type:'success',title:'Deleted'}); }
</script>

<template>
  <div>
    <Breadcrumb title="Terms" :trail="[{ label: 'Terms' }]">
      <template #actions>
        <BaseButton v-if="can('term_create')" variant="primary" icon="ri-add-line">Add Term</BaseButton>
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
