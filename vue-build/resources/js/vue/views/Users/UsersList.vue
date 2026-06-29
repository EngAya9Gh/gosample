<script setup>
/** /admin/users — User list. Auto-faithful to handoff columns. Mock data; wire @query for server-side. */
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
const columns = [{"key":"id","label":"ID","sortable":true,"sticky":"start"},{"key":"name","label":"Name"},{"key":"email","label":"Email"},{"key":"verified","label":"Email Verified At"},{"key":"roles","label":"Roles"}];
const rows = ref([{"id":10428,"name":"Sample Run","email":"user1@mtc.sa","verified":"Item 1","roles":"Item 1"},{"id":10427,"name":"Morning Route","email":"user2@mtc.sa","verified":"Item 2","roles":"Item 2"},{"id":10426,"name":"Lab Pickup","email":"user3@mtc.sa","verified":"Item 3","roles":"Item 3"},{"id":10425,"name":"Cold Transfer","email":"user4@mtc.sa","verified":"Item 4","roles":"Item 4"},{"id":10424,"name":"Sample Run","email":"user5@mtc.sa","verified":"Item 5","roles":"Item 5"},{"id":10423,"name":"Morning Route","email":"user6@mtc.sa","verified":"Item 6","roles":"Item 6"},{"id":10422,"name":"Lab Pickup","email":"user7@mtc.sa","verified":"Item 7","roles":"Item 7"},{"id":10421,"name":"Cold Transfer","email":"user8@mtc.sa","verified":"Item 8","roles":"Item 8"},{"id":10420,"name":"Sample Run","email":"user9@mtc.sa","verified":"Item 9","roles":"Item 9"},{"id":10419,"name":"Morning Route","email":"user10@mtc.sa","verified":"Item 10","roles":"Item 10"},{"id":10418,"name":"Lab Pickup","email":"user11@mtc.sa","verified":"Item 11","roles":"Item 11"},{"id":10417,"name":"Cold Transfer","email":"user12@mtc.sa","verified":"Item 12","roles":"Item 12"},{"id":10416,"name":"Sample Run","email":"user13@mtc.sa","verified":"Item 13","roles":"Item 13"},{"id":10415,"name":"Morning Route","email":"user14@mtc.sa","verified":"Item 14","roles":"Item 14"},{"id":10414,"name":"Lab Pickup","email":"user15@mtc.sa","verified":"Item 15","roles":"Item 15"},{"id":10413,"name":"Cold Transfer","email":"user16@mtc.sa","verified":"Item 16","roles":"Item 16"},{"id":10412,"name":"Sample Run","email":"user17@mtc.sa","verified":"Item 17","roles":"Item 17"},{"id":10411,"name":"Morning Route","email":"user18@mtc.sa","verified":"Item 18","roles":"Item 18"},{"id":10410,"name":"Lab Pickup","email":"user19@mtc.sa","verified":"Item 19","roles":"Item 19"},{"id":10409,"name":"Cold Transfer","email":"user20@mtc.sa","verified":"Item 20","roles":"Item 20"},{"id":10408,"name":"Sample Run","email":"user21@mtc.sa","verified":"Item 21","roles":"Item 21"},{"id":10407,"name":"Morning Route","email":"user22@mtc.sa","verified":"Item 22","roles":"Item 22"}]);

function doSearch(){ loading.value=true; setTimeout(()=>{loading.value=false; push({type:'success',title:'Filters applied',message:rows.value.length+' records'});},600); }
function doReset(){ filters.value={}; }
function bulkDelete(ids){ rows.value=rows.value.filter(r=>!ids.includes(r.id)); push({type:'success',title:'Bulk delete',message:ids.length+' removed'}); }
function onExport(k){ push({type:'info',title:'Export',message:'Generating '+k.toUpperCase()+'…'}); }
function del(row){ rows.value=rows.value.filter(r=>r!==row); push({type:'success',title:'Deleted'}); }
</script>

<template>
  <div>
    <Breadcrumb title="Users" :trail="[{ label: 'Users' }]">
      <template #actions>
        <BaseButton v-if="can('user_create')" variant="primary" icon="ri-add-line">Add User</BaseButton>
      </template>
    </Breadcrumb>

    <DataTable :columns="columns" :rows="rows" row-key="id" :loading="loading"
      :bulk-actions="canDelete() ? [{ label:'Delete', icon:'ri-delete-bin-line', tone:'danger', event:'bulk-delete' }] : []"
      @bulk-delete="bulkDelete" @export="onExport">
      <template #cell-id="{ value }"><span class="font-semibold text-primary-700 dark:text-primary-300">#{{ value }}</span></template>
      <template #cell-name="{ value }"><span class="inline-flex items-center gap-2"><BaseAvatar :name="value" :size="26" /><span class="truncate">{{ value }}</span></span></template>
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
