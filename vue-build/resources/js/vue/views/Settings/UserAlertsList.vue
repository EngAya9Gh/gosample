<script setup>
/** /admin/user-alerts — User Alert list. Auto-faithful to handoff columns. Mock data; wire @query for server-side. */
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
const columns = [{"key":"id","label":"ID","sortable":true,"sticky":"start"},{"key":"text","label":"Alert Text"},{"key":"link","label":"Alert Link","mono":true},{"key":"user","label":"User"},{"key":"created","label":"Created At"}];
const rows = ref([{"id":10428,"text":"—","link":"Item 1","user":"Mohammed Al-Harbi","created":"2026-06-27"},{"id":10427,"text":"—","link":"Item 2","user":"Fatimah Nasser","created":"2026-06-26"},{"id":10426,"text":"—","link":"Item 3","user":"Khalid Otaibi","created":"2026-06-25"},{"id":10425,"text":"—","link":"Item 4","user":"Sara Al-Dosari","created":"2026-06-24"},{"id":10424,"text":"—","link":"Item 5","user":"Yousef Qahtani","created":"2026-06-23"},{"id":10423,"text":"—","link":"Item 6","user":"Noura Faisal","created":"2026-06-22"},{"id":10422,"text":"—","link":"Item 7","user":"Abdullah Zahrani","created":"2026-06-21"},{"id":10421,"text":"—","link":"Item 8","user":"Layla Ghamdi","created":"2026-06-20"},{"id":10420,"text":"—","link":"Item 9","user":"Mohammed Al-Harbi","created":"2026-06-19"},{"id":10419,"text":"—","link":"Item 10","user":"Fatimah Nasser","created":"2026-06-18"},{"id":10418,"text":"—","link":"Item 11","user":"Khalid Otaibi","created":"2026-06-17"},{"id":10417,"text":"—","link":"Item 12","user":"Sara Al-Dosari","created":"2026-06-16"},{"id":10416,"text":"—","link":"Item 13","user":"Yousef Qahtani","created":"2026-06-15"},{"id":10415,"text":"—","link":"Item 14","user":"Noura Faisal","created":"2026-06-14"},{"id":10414,"text":"—","link":"Item 15","user":"Abdullah Zahrani","created":"2026-06-13"},{"id":10413,"text":"—","link":"Item 16","user":"Layla Ghamdi","created":"2026-06-12"},{"id":10412,"text":"—","link":"Item 17","user":"Mohammed Al-Harbi","created":"2026-06-11"},{"id":10411,"text":"—","link":"Item 18","user":"Fatimah Nasser","created":"2026-06-10"},{"id":10410,"text":"—","link":"Item 19","user":"Khalid Otaibi","created":"2026-06-09"},{"id":10409,"text":"—","link":"Item 20","user":"Sara Al-Dosari","created":"2026-06-08"},{"id":10408,"text":"—","link":"Item 21","user":"Yousef Qahtani","created":"2026-06-07"},{"id":10407,"text":"—","link":"Item 22","user":"Noura Faisal","created":"2026-06-06"}]);

function doSearch(){ loading.value=true; setTimeout(()=>{loading.value=false; push({type:'success',title:'Filters applied',message:rows.value.length+' records'});},600); }
function doReset(){ filters.value={}; }
function bulkDelete(ids){ rows.value=rows.value.filter(r=>!ids.includes(r.id)); push({type:'success',title:'Bulk delete',message:ids.length+' removed'}); }
function onExport(k){ push({type:'info',title:'Export',message:'Generating '+k.toUpperCase()+'…'}); }
function del(row){ rows.value=rows.value.filter(r=>r!==row); push({type:'success',title:'Deleted'}); }
</script>

<template>
  <div>
    <Breadcrumb title="User Alerts" :trail="[{ label: 'User Alerts' }]">
      <template #actions>
        <BaseButton v-if="can('user_create')" variant="primary" icon="ri-add-line">Add Alert</BaseButton>
      </template>
    </Breadcrumb>

    <DataTable :columns="columns" :rows="rows" row-key="id" :loading="loading"
      :bulk-actions="canDelete() ? [{ label:'Delete', icon:'ri-delete-bin-line', tone:'danger', event:'bulk-delete' }] : []"
      @bulk-delete="bulkDelete" @export="onExport">
      <template #cell-id="{ value }"><span class="font-semibold text-primary-700 dark:text-primary-300">#{{ value }}</span></template>
      <template #cell-user="{ value }"><span class="inline-flex items-center gap-2"><BaseAvatar :name="value" :size="26" /><span class="truncate">{{ value }}</span></span></template>
      <template #row-actions="{ row }">
        <div class="inline-flex items-center gap-1">
          <button class="grid place-items-center w-8 h-8 rounded-lg text-info hover:bg-info/10 transition" title="View"><i class="ri-eye-line"></i></button>
          
          <button v-if="canDelete()" @click="del(row)" class="grid place-items-center w-8 h-8 rounded-lg text-danger hover:bg-danger/10 transition" title="Delete"><i class="ri-delete-bin-line"></i></button>
        </div>
      </template>
    </DataTable>
  </div>
</template>
