<script setup>
/** /admin/car-drivers — Car Driver list. Auto-faithful to handoff columns. Mock data; wire @query for server-side. */
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
const columns = [{"key":"id","label":"ID","sortable":true,"sticky":"start"},{"key":"imei","label":"Car","mono":true},{"key":"driver","label":"Driver"},{"key":"linked","label":"Is Linked","align":"center"}];
const rows = ref([{"id":10428,"imei":"864920451000","driver":"Mohammed Al-Harbi","linked":"Item 1"},{"id":10427,"imei":"864920451007","driver":"Fatimah Nasser","linked":"Item 2"},{"id":10426,"imei":"864920451014","driver":"Khalid Otaibi","linked":"Item 3"},{"id":10425,"imei":"864920451021","driver":"Sara Al-Dosari","linked":"Item 4"},{"id":10424,"imei":"864920451028","driver":"Yousef Qahtani","linked":"Item 5"},{"id":10423,"imei":"864920451035","driver":"Noura Faisal","linked":"Item 6"},{"id":10422,"imei":"864920451042","driver":"Abdullah Zahrani","linked":"Item 7"},{"id":10421,"imei":"864920451049","driver":"Layla Ghamdi","linked":"Item 8"},{"id":10420,"imei":"864920451056","driver":"Mohammed Al-Harbi","linked":"Item 9"},{"id":10419,"imei":"864920451063","driver":"Fatimah Nasser","linked":"Item 10"},{"id":10418,"imei":"864920451070","driver":"Khalid Otaibi","linked":"Item 11"},{"id":10417,"imei":"864920451077","driver":"Sara Al-Dosari","linked":"Item 12"},{"id":10416,"imei":"864920451084","driver":"Yousef Qahtani","linked":"Item 13"},{"id":10415,"imei":"864920451091","driver":"Noura Faisal","linked":"Item 14"},{"id":10414,"imei":"864920451098","driver":"Abdullah Zahrani","linked":"Item 15"},{"id":10413,"imei":"864920451105","driver":"Layla Ghamdi","linked":"Item 16"},{"id":10412,"imei":"864920451112","driver":"Mohammed Al-Harbi","linked":"Item 17"},{"id":10411,"imei":"864920451119","driver":"Fatimah Nasser","linked":"Item 18"},{"id":10410,"imei":"864920451126","driver":"Khalid Otaibi","linked":"Item 19"},{"id":10409,"imei":"864920451133","driver":"Sara Al-Dosari","linked":"Item 20"},{"id":10408,"imei":"864920451140","driver":"Yousef Qahtani","linked":"Item 21"},{"id":10407,"imei":"864920451147","driver":"Noura Faisal","linked":"Item 22"}]);

function doSearch(){ loading.value=true; setTimeout(()=>{loading.value=false; push({type:'success',title:'Filters applied',message:rows.value.length+' records'});},600); }
function doReset(){ filters.value={}; }
function bulkDelete(ids){ rows.value=rows.value.filter(r=>!ids.includes(r.id)); push({type:'success',title:'Bulk delete',message:ids.length+' removed'}); }
function onExport(k){ push({type:'info',title:'Export',message:'Generating '+k.toUpperCase()+'…'}); }
function del(row){ rows.value=rows.value.filter(r=>r!==row); push({type:'success',title:'Deleted'}); }
</script>

<template>
  <div>
    <Breadcrumb title="Car Drivers" :trail="[{ label: 'Car Drivers' }]">
      <template #actions>
        <BaseButton v-if="can('car_create')" variant="primary" icon="ri-add-line">Add Link</BaseButton>
      </template>
    </Breadcrumb>

    <DataTable :columns="columns" :rows="rows" row-key="id" :loading="loading"
      :bulk-actions="canDelete() ? [{ label:'Delete', icon:'ri-delete-bin-line', tone:'danger', event:'bulk-delete' }] : []"
      @bulk-delete="bulkDelete" @export="onExport">
      <template #cell-id="{ value }"><span class="font-semibold text-primary-700 dark:text-primary-300">#{{ value }}</span></template>
      <template #cell-driver="{ value }"><span class="inline-flex items-center gap-2"><BaseAvatar :name="value" :size="26" /><span class="truncate">{{ value }}</span></span></template>
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
