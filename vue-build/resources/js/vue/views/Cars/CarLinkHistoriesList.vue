<script setup>
/** /admin/car-link-histories — Car Link History list. Auto-faithful to handoff columns. Mock data; wire @query for server-side. */
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
const columns = [{"key":"id","label":"ID","sortable":true,"sticky":"start"},{"key":"driver","label":"Driver"},{"key":"imei","label":"Car","mono":true},{"key":"action","label":"Action"},{"key":"created","label":"Created At"}];
const rows = ref([{"id":10428,"driver":"Mohammed Al-Harbi","imei":"864920451000","action":"Linked","created":"2026-06-27"},{"id":10427,"driver":"Fatimah Nasser","imei":"864920451007","action":"Unlinked","created":"2026-06-26"},{"id":10426,"driver":"Khalid Otaibi","imei":"864920451014","action":"Assigned","created":"2026-06-25"},{"id":10425,"driver":"Sara Al-Dosari","imei":"864920451021","action":"Linked","created":"2026-06-24"},{"id":10424,"driver":"Yousef Qahtani","imei":"864920451028","action":"Unlinked","created":"2026-06-23"},{"id":10423,"driver":"Noura Faisal","imei":"864920451035","action":"Assigned","created":"2026-06-22"},{"id":10422,"driver":"Abdullah Zahrani","imei":"864920451042","action":"Linked","created":"2026-06-21"},{"id":10421,"driver":"Layla Ghamdi","imei":"864920451049","action":"Unlinked","created":"2026-06-20"},{"id":10420,"driver":"Mohammed Al-Harbi","imei":"864920451056","action":"Assigned","created":"2026-06-19"},{"id":10419,"driver":"Fatimah Nasser","imei":"864920451063","action":"Linked","created":"2026-06-18"},{"id":10418,"driver":"Khalid Otaibi","imei":"864920451070","action":"Unlinked","created":"2026-06-17"},{"id":10417,"driver":"Sara Al-Dosari","imei":"864920451077","action":"Assigned","created":"2026-06-16"},{"id":10416,"driver":"Yousef Qahtani","imei":"864920451084","action":"Linked","created":"2026-06-15"},{"id":10415,"driver":"Noura Faisal","imei":"864920451091","action":"Unlinked","created":"2026-06-14"},{"id":10414,"driver":"Abdullah Zahrani","imei":"864920451098","action":"Assigned","created":"2026-06-13"},{"id":10413,"driver":"Layla Ghamdi","imei":"864920451105","action":"Linked","created":"2026-06-12"},{"id":10412,"driver":"Mohammed Al-Harbi","imei":"864920451112","action":"Unlinked","created":"2026-06-11"},{"id":10411,"driver":"Fatimah Nasser","imei":"864920451119","action":"Assigned","created":"2026-06-10"},{"id":10410,"driver":"Khalid Otaibi","imei":"864920451126","action":"Linked","created":"2026-06-09"},{"id":10409,"driver":"Sara Al-Dosari","imei":"864920451133","action":"Unlinked","created":"2026-06-08"},{"id":10408,"driver":"Yousef Qahtani","imei":"864920451140","action":"Assigned","created":"2026-06-07"},{"id":10407,"driver":"Noura Faisal","imei":"864920451147","action":"Linked","created":"2026-06-06"}]);

function doSearch(){ loading.value=true; setTimeout(()=>{loading.value=false; push({type:'success',title:'Filters applied',message:rows.value.length+' records'});},600); }
function doReset(){ filters.value={}; }
function bulkDelete(ids){ rows.value=rows.value.filter(r=>!ids.includes(r.id)); push({type:'success',title:'Bulk delete',message:ids.length+' removed'}); }
function onExport(k){ push({type:'info',title:'Export',message:'Generating '+k.toUpperCase()+'…'}); }
function del(row){ rows.value=rows.value.filter(r=>r!==row); push({type:'success',title:'Deleted'}); }
</script>

<template>
  <div>
    <Breadcrumb title="Car Link Histories" :trail="[{ label: 'Car Link Histories' }]">
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
