<script setup>
/** /admin/barcodes — Barcode list. Auto-faithful to handoff columns. Mock data; wire @query for server-side. */
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
const columns = [{"key":"id","label":"ID","sortable":true,"sticky":"start"},{"key":"type","label":"Type"},{"key":"last","label":"Last Number","mono":true}];
const rows = ref([{"id":10428,"type":"Pickup","last":"Item 1"},{"id":10427,"type":"Delivery","last":"Item 2"},{"id":10426,"type":"Round-trip","last":"Item 3"},{"id":10425,"type":"Pickup","last":"Item 4"},{"id":10424,"type":"Delivery","last":"Item 5"},{"id":10423,"type":"Round-trip","last":"Item 6"},{"id":10422,"type":"Pickup","last":"Item 7"},{"id":10421,"type":"Delivery","last":"Item 8"},{"id":10420,"type":"Round-trip","last":"Item 9"},{"id":10419,"type":"Pickup","last":"Item 10"},{"id":10418,"type":"Delivery","last":"Item 11"},{"id":10417,"type":"Round-trip","last":"Item 12"},{"id":10416,"type":"Pickup","last":"Item 13"},{"id":10415,"type":"Delivery","last":"Item 14"},{"id":10414,"type":"Round-trip","last":"Item 15"},{"id":10413,"type":"Pickup","last":"Item 16"},{"id":10412,"type":"Delivery","last":"Item 17"},{"id":10411,"type":"Round-trip","last":"Item 18"},{"id":10410,"type":"Pickup","last":"Item 19"},{"id":10409,"type":"Delivery","last":"Item 20"},{"id":10408,"type":"Round-trip","last":"Item 21"},{"id":10407,"type":"Pickup","last":"Item 22"}]);

function doSearch(){ loading.value=true; setTimeout(()=>{loading.value=false; push({type:'success',title:'Filters applied',message:rows.value.length+' records'});},600); }
function doReset(){ filters.value={}; }
function bulkDelete(ids){ rows.value=rows.value.filter(r=>!ids.includes(r.id)); push({type:'success',title:'Bulk delete',message:ids.length+' removed'}); }
function onExport(k){ push({type:'info',title:'Export',message:'Generating '+k.toUpperCase()+'…'}); }
function del(row){ rows.value=rows.value.filter(r=>r!==row); push({type:'success',title:'Deleted'}); }
</script>

<template>
  <div>
    <Breadcrumb title="Barcodes" :trail="[{ label: 'Barcodes' }]">
      <template #actions>
        <BaseButton v-if="can('barcode_create')" variant="primary" icon="ri-add-line">Add Barcode</BaseButton>
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
