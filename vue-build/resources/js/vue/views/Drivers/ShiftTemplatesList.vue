<script setup>
/** /admin/shift-templates — Shift Template list. Auto-faithful to handoff columns. Mock data; wire @query for server-side. */
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
const columns = [{"key":"id","label":"ID","sortable":true,"sticky":"start"},{"key":"name","label":"Template Name"},{"key":"start","label":"Start Time"},{"key":"end","label":"End Time"}];
const rows = ref([{"id":10428,"name":"Sample Run","start":"2026-06-27","end":"2026-06-27"},{"id":10427,"name":"Morning Route","start":"2026-06-26","end":"2026-06-26"},{"id":10426,"name":"Lab Pickup","start":"2026-06-25","end":"2026-06-25"},{"id":10425,"name":"Cold Transfer","start":"2026-06-24","end":"2026-06-24"},{"id":10424,"name":"Sample Run","start":"2026-06-23","end":"2026-06-23"},{"id":10423,"name":"Morning Route","start":"2026-06-22","end":"2026-06-22"},{"id":10422,"name":"Lab Pickup","start":"2026-06-21","end":"2026-06-21"},{"id":10421,"name":"Cold Transfer","start":"2026-06-20","end":"2026-06-20"},{"id":10420,"name":"Sample Run","start":"2026-06-19","end":"2026-06-19"},{"id":10419,"name":"Morning Route","start":"2026-06-18","end":"2026-06-18"},{"id":10418,"name":"Lab Pickup","start":"2026-06-17","end":"2026-06-17"},{"id":10417,"name":"Cold Transfer","start":"2026-06-16","end":"2026-06-16"},{"id":10416,"name":"Sample Run","start":"2026-06-15","end":"2026-06-15"},{"id":10415,"name":"Morning Route","start":"2026-06-14","end":"2026-06-14"},{"id":10414,"name":"Lab Pickup","start":"2026-06-13","end":"2026-06-13"},{"id":10413,"name":"Cold Transfer","start":"2026-06-12","end":"2026-06-12"},{"id":10412,"name":"Sample Run","start":"2026-06-11","end":"2026-06-11"},{"id":10411,"name":"Morning Route","start":"2026-06-10","end":"2026-06-10"},{"id":10410,"name":"Lab Pickup","start":"2026-06-09","end":"2026-06-09"},{"id":10409,"name":"Cold Transfer","start":"2026-06-08","end":"2026-06-08"},{"id":10408,"name":"Sample Run","start":"2026-06-07","end":"2026-06-07"},{"id":10407,"name":"Morning Route","start":"2026-06-06","end":"2026-06-06"}]);

function doSearch(){ loading.value=true; setTimeout(()=>{loading.value=false; push({type:'success',title:'Filters applied',message:rows.value.length+' records'});},600); }
function doReset(){ filters.value={}; }
function bulkDelete(ids){ rows.value=rows.value.filter(r=>!ids.includes(r.id)); push({type:'success',title:'Bulk delete',message:ids.length+' removed'}); }
function onExport(k){ push({type:'info',title:'Export',message:'Generating '+k.toUpperCase()+'…'}); }
function del(row){ rows.value=rows.value.filter(r=>r!==row); push({type:'success',title:'Deleted'}); }
</script>

<template>
  <div>
    <Breadcrumb title="Shift Templates" :trail="[{ label: 'Shift Templates' }]">
      <template #actions>
        <BaseButton v-if="can('shift_create')" variant="primary" icon="ri-add-line">Add New Template</BaseButton>
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
