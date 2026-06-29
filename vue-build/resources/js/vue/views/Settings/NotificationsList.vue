<script setup>
/** /admin/notifications — Notification list. Auto-faithful to handoff columns. Mock data; wire @query for server-side. */
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
const columns = [{"key":"id","label":"ID","sortable":true,"sticky":"start"},{"key":"task","label":"Task","mono":true},{"key":"from","label":"From Location"},{"key":"to","label":"To Location"},{"key":"driver","label":"Driver"},{"key":"client","label":"Billing Client"},{"key":"readAt","label":"Read At"}];
const rows = ref([{"id":10428,"task":"Item 1","from":"Central Hub","to":"Central Hub","driver":"Mohammed Al-Harbi","client":"King Faisal Lab","readAt":"Item 1"},{"id":10427,"task":"Item 2","from":"Lab East","to":"Lab East","driver":"Fatimah Nasser","client":"Al-Noor Clinic","readAt":"Item 2"},{"id":10426,"task":"Item 3","from":"Lab West","to":"Lab West","driver":"Khalid Otaibi","client":"Dallah Hospital","readAt":"Item 3"},{"id":10425,"task":"Item 4","from":"North Depot","to":"North Depot","driver":"Sara Al-Dosari","client":"Saudi German","readAt":"Item 4"},{"id":10424,"task":"Item 5","from":"King Faisal Lab","to":"King Faisal Lab","driver":"Yousef Qahtani","client":"Mouwasat Lab","readAt":"Item 5"},{"id":10423,"task":"Item 6","from":"Al-Noor Clinic","to":"Al-Noor Clinic","driver":"Noura Faisal","client":"Habib Medical","readAt":"Item 6"},{"id":10422,"task":"Item 7","from":"Dallah Hosp.","to":"Dallah Hosp.","driver":"Abdullah Zahrani","client":"King Faisal Lab","readAt":"Item 7"},{"id":10421,"task":"Item 8","from":"Olaya Branch","to":"Olaya Branch","driver":"Layla Ghamdi","client":"Al-Noor Clinic","readAt":"Item 8"},{"id":10420,"task":"Item 9","from":"Central Hub","to":"Central Hub","driver":"Mohammed Al-Harbi","client":"Dallah Hospital","readAt":"Item 9"},{"id":10419,"task":"Item 10","from":"Lab East","to":"Lab East","driver":"Fatimah Nasser","client":"Saudi German","readAt":"Item 10"},{"id":10418,"task":"Item 11","from":"Lab West","to":"Lab West","driver":"Khalid Otaibi","client":"Mouwasat Lab","readAt":"Item 11"},{"id":10417,"task":"Item 12","from":"North Depot","to":"North Depot","driver":"Sara Al-Dosari","client":"Habib Medical","readAt":"Item 12"},{"id":10416,"task":"Item 13","from":"King Faisal Lab","to":"King Faisal Lab","driver":"Yousef Qahtani","client":"King Faisal Lab","readAt":"Item 13"},{"id":10415,"task":"Item 14","from":"Al-Noor Clinic","to":"Al-Noor Clinic","driver":"Noura Faisal","client":"Al-Noor Clinic","readAt":"Item 14"},{"id":10414,"task":"Item 15","from":"Dallah Hosp.","to":"Dallah Hosp.","driver":"Abdullah Zahrani","client":"Dallah Hospital","readAt":"Item 15"},{"id":10413,"task":"Item 16","from":"Olaya Branch","to":"Olaya Branch","driver":"Layla Ghamdi","client":"Saudi German","readAt":"Item 16"},{"id":10412,"task":"Item 17","from":"Central Hub","to":"Central Hub","driver":"Mohammed Al-Harbi","client":"Mouwasat Lab","readAt":"Item 17"},{"id":10411,"task":"Item 18","from":"Lab East","to":"Lab East","driver":"Fatimah Nasser","client":"Habib Medical","readAt":"Item 18"},{"id":10410,"task":"Item 19","from":"Lab West","to":"Lab West","driver":"Khalid Otaibi","client":"King Faisal Lab","readAt":"Item 19"},{"id":10409,"task":"Item 20","from":"North Depot","to":"North Depot","driver":"Sara Al-Dosari","client":"Al-Noor Clinic","readAt":"Item 20"},{"id":10408,"task":"Item 21","from":"King Faisal Lab","to":"King Faisal Lab","driver":"Yousef Qahtani","client":"Dallah Hospital","readAt":"Item 21"},{"id":10407,"task":"Item 22","from":"Al-Noor Clinic","to":"Al-Noor Clinic","driver":"Noura Faisal","client":"Saudi German","readAt":"Item 22"}]);

function doSearch(){ loading.value=true; setTimeout(()=>{loading.value=false; push({type:'success',title:'Filters applied',message:rows.value.length+' records'});},600); }
function doReset(){ filters.value={}; }
function bulkDelete(ids){ rows.value=rows.value.filter(r=>!ids.includes(r.id)); push({type:'success',title:'Bulk delete',message:ids.length+' removed'}); }
function onExport(k){ push({type:'info',title:'Export',message:'Generating '+k.toUpperCase()+'…'}); }
function del(row){ rows.value=rows.value.filter(r=>r!==row); push({type:'success',title:'Deleted'}); }
</script>

<template>
  <div>
    <Breadcrumb title="Notifications" :trail="[{ label: 'Notifications' }]">
    </Breadcrumb>

    <DataTable :columns="columns" :rows="rows" row-key="id" :loading="loading"
      :bulk-actions="canDelete() ? [{ label:'Delete', icon:'ri-delete-bin-line', tone:'danger', event:'bulk-delete' }] : []"
      @bulk-delete="bulkDelete" @export="onExport">
      <template #cell-id="{ value }"><span class="font-semibold text-primary-700 dark:text-primary-300">#{{ value }}</span></template>
      <template #cell-driver="{ value }"><span class="inline-flex items-center gap-2"><BaseAvatar :name="value" :size="26" /><span class="truncate">{{ value }}</span></span></template>
      <template #row-actions="{ row }">
        <div class="inline-flex items-center gap-1">
          <button class="grid place-items-center w-8 h-8 rounded-lg text-info hover:bg-info/10 transition" title="View"><i class="ri-eye-line"></i></button>
          
          <button v-if="canDelete()" @click="del(row)" class="grid place-items-center w-8 h-8 rounded-lg text-danger hover:bg-danger/10 transition" title="Delete"><i class="ri-delete-bin-line"></i></button>
        </div>
      </template>
    </DataTable>
  </div>
</template>
