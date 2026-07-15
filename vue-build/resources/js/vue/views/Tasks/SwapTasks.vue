<script setup>
/** /admin/swap-tasks — Swap Task list. Auto-faithful to handoff columns. Mock data; wire @query for server-side. */
import { ref } from 'vue';
import Breadcrumb from '../../components/Breadcrumb.vue';
import DataTable from '../../components/DataTable.vue';
import BaseButton from '../../components/BaseButton.vue';
import { useToast } from '../../composables/useToast';
import { usePermissions } from '../../composables/usePermissions';
import FilterBar from '../../components/FilterBar.vue';
import FormInput from '../../components/FormInput.vue';
import FormSelect from '../../components/FormSelect.vue';
import StatusBadge from '../../components/StatusBadge.vue';
import BaseAvatar from '../../components/BaseAvatar.vue';

const { push } = useToast();
const { can, canDelete } = usePermissions();
const loading = ref(false);
const opt = (a) => a.map(v => ({ value: v, label: v }));
const filters = ref({"keyword":"","status":"","driver":"","client":""});
const columns = [{"key":"id","label":"ID","sortable":true,"sticky":"start"},{"key":"orderDate","label":"Order Date"},{"key":"client","label":"Client"},{"key":"from","label":"From"},{"key":"to","label":"To"},{"key":"oldDriver","label":"Old Driver"},{"key":"driver","label":"Driver"},{"key":"collection","label":"Collection"},{"key":"freezer","label":"Freezer Date"},{"key":"swapFreezer","label":"Swap Freezer"},{"key":"freezerOut","label":"Freezer Out"},{"key":"close","label":"Close Date"},{"key":"status","label":"Status"}];
const rows = ref([{"id":10428,"orderDate":"2026-06-27","client":"King Faisal Lab","from":"Central Hub","to":"Central Hub","oldDriver":"Mohammed Al-Harbi","driver":"Mohammed Al-Harbi","collection":"Item 1","freezer":"Item 1","swapFreezer":"Item 1","freezerOut":"Item 1","close":"Item 1","status":"NEW"},{"id":10427,"orderDate":"2026-06-26","client":"Al-Noor Clinic","from":"Lab East","to":"Lab East","oldDriver":"Fatimah Nasser","driver":"Fatimah Nasser","collection":"Item 2","freezer":"Item 2","swapFreezer":"Item 2","freezerOut":"Item 2","close":"Item 2","status":"COLLECTED"},{"id":10426,"orderDate":"2026-06-25","client":"Dallah Hospital","from":"Lab West","to":"Lab West","oldDriver":"Khalid Otaibi","driver":"Khalid Otaibi","collection":"Item 3","freezer":"Item 3","swapFreezer":"Item 3","freezerOut":"Item 3","close":"Item 3","status":"IN_CONTAINER"},{"id":10425,"orderDate":"2026-06-24","client":"Saudi German","from":"North Depot","to":"North Depot","oldDriver":"Sara Al-Dosari","driver":"Sara Al-Dosari","collection":"Item 4","freezer":"Item 4","swapFreezer":"Item 4","freezerOut":"Item 4","close":"Item 4","status":"OUT_CONTAINER"},{"id":10424,"orderDate":"2026-06-23","client":"Mouwasat Lab","from":"King Faisal Lab","to":"King Faisal Lab","oldDriver":"Yousef Qahtani","driver":"Yousef Qahtani","collection":"Item 5","freezer":"Item 5","swapFreezer":"Item 5","freezerOut":"Item 5","close":"Item 5","status":"CLOSED"},{"id":10423,"orderDate":"2026-06-22","client":"Habib Medical","from":"Al-Noor Clinic","to":"Al-Noor Clinic","oldDriver":"Noura Faisal","driver":"Noura Faisal","collection":"Item 6","freezer":"Item 6","swapFreezer":"Item 6","freezerOut":"Item 6","close":"Item 6","status":"NO_SAMPLES"},{"id":10422,"orderDate":"2026-06-21","client":"King Faisal Lab","from":"Dallah Hosp.","to":"Dallah Hosp.","oldDriver":"Abdullah Zahrani","driver":"Abdullah Zahrani","collection":"Item 7","freezer":"Item 7","swapFreezer":"Item 7","freezerOut":"Item 7","close":"Item 7","status":"NEW"},{"id":10421,"orderDate":"2026-06-20","client":"Al-Noor Clinic","from":"Olaya Branch","to":"Olaya Branch","oldDriver":"Layla Ghamdi","driver":"Layla Ghamdi","collection":"Item 8","freezer":"Item 8","swapFreezer":"Item 8","freezerOut":"Item 8","close":"Item 8","status":"COLLECTED"},{"id":10420,"orderDate":"2026-06-19","client":"Dallah Hospital","from":"Central Hub","to":"Central Hub","oldDriver":"Mohammed Al-Harbi","driver":"Mohammed Al-Harbi","collection":"Item 9","freezer":"Item 9","swapFreezer":"Item 9","freezerOut":"Item 9","close":"Item 9","status":"IN_CONTAINER"},{"id":10419,"orderDate":"2026-06-18","client":"Saudi German","from":"Lab East","to":"Lab East","oldDriver":"Fatimah Nasser","driver":"Fatimah Nasser","collection":"Item 10","freezer":"Item 10","swapFreezer":"Item 10","freezerOut":"Item 10","close":"Item 10","status":"OUT_CONTAINER"},{"id":10418,"orderDate":"2026-06-17","client":"Mouwasat Lab","from":"Lab West","to":"Lab West","oldDriver":"Khalid Otaibi","driver":"Khalid Otaibi","collection":"Item 11","freezer":"Item 11","swapFreezer":"Item 11","freezerOut":"Item 11","close":"Item 11","status":"CLOSED"},{"id":10417,"orderDate":"2026-06-16","client":"Habib Medical","from":"North Depot","to":"North Depot","oldDriver":"Sara Al-Dosari","driver":"Sara Al-Dosari","collection":"Item 12","freezer":"Item 12","swapFreezer":"Item 12","freezerOut":"Item 12","close":"Item 12","status":"NO_SAMPLES"},{"id":10416,"orderDate":"2026-06-15","client":"King Faisal Lab","from":"King Faisal Lab","to":"King Faisal Lab","oldDriver":"Yousef Qahtani","driver":"Yousef Qahtani","collection":"Item 13","freezer":"Item 13","swapFreezer":"Item 13","freezerOut":"Item 13","close":"Item 13","status":"NEW"},{"id":10415,"orderDate":"2026-06-14","client":"Al-Noor Clinic","from":"Al-Noor Clinic","to":"Al-Noor Clinic","oldDriver":"Noura Faisal","driver":"Noura Faisal","collection":"Item 14","freezer":"Item 14","swapFreezer":"Item 14","freezerOut":"Item 14","close":"Item 14","status":"COLLECTED"},{"id":10414,"orderDate":"2026-06-13","client":"Dallah Hospital","from":"Dallah Hosp.","to":"Dallah Hosp.","oldDriver":"Abdullah Zahrani","driver":"Abdullah Zahrani","collection":"Item 15","freezer":"Item 15","swapFreezer":"Item 15","freezerOut":"Item 15","close":"Item 15","status":"IN_CONTAINER"},{"id":10413,"orderDate":"2026-06-12","client":"Saudi German","from":"Olaya Branch","to":"Olaya Branch","oldDriver":"Layla Ghamdi","driver":"Layla Ghamdi","collection":"Item 16","freezer":"Item 16","swapFreezer":"Item 16","freezerOut":"Item 16","close":"Item 16","status":"OUT_CONTAINER"},{"id":10412,"orderDate":"2026-06-11","client":"Mouwasat Lab","from":"Central Hub","to":"Central Hub","oldDriver":"Mohammed Al-Harbi","driver":"Mohammed Al-Harbi","collection":"Item 17","freezer":"Item 17","swapFreezer":"Item 17","freezerOut":"Item 17","close":"Item 17","status":"CLOSED"},{"id":10411,"orderDate":"2026-06-10","client":"Habib Medical","from":"Lab East","to":"Lab East","oldDriver":"Fatimah Nasser","driver":"Fatimah Nasser","collection":"Item 18","freezer":"Item 18","swapFreezer":"Item 18","freezerOut":"Item 18","close":"Item 18","status":"NO_SAMPLES"},{"id":10410,"orderDate":"2026-06-09","client":"King Faisal Lab","from":"Lab West","to":"Lab West","oldDriver":"Khalid Otaibi","driver":"Khalid Otaibi","collection":"Item 19","freezer":"Item 19","swapFreezer":"Item 19","freezerOut":"Item 19","close":"Item 19","status":"NEW"},{"id":10409,"orderDate":"2026-06-08","client":"Al-Noor Clinic","from":"North Depot","to":"North Depot","oldDriver":"Sara Al-Dosari","driver":"Sara Al-Dosari","collection":"Item 20","freezer":"Item 20","swapFreezer":"Item 20","freezerOut":"Item 20","close":"Item 20","status":"COLLECTED"},{"id":10408,"orderDate":"2026-06-07","client":"Dallah Hospital","from":"King Faisal Lab","to":"King Faisal Lab","oldDriver":"Yousef Qahtani","driver":"Yousef Qahtani","collection":"Item 21","freezer":"Item 21","swapFreezer":"Item 21","freezerOut":"Item 21","close":"Item 21","status":"IN_CONTAINER"},{"id":10407,"orderDate":"2026-06-06","client":"Saudi German","from":"Al-Noor Clinic","to":"Al-Noor Clinic","oldDriver":"Noura Faisal","driver":"Noura Faisal","collection":"Item 22","freezer":"Item 22","swapFreezer":"Item 22","freezerOut":"Item 22","close":"Item 22","status":"OUT_CONTAINER"}]);

function doSearch(){ loading.value=true; setTimeout(()=>{loading.value=false; push({type:'success',title:'Filters applied',message:rows.value.length+' records'});},600); }
function doReset(){ filters.value={"keyword":"","status":"","driver":"","client":""}; }
function bulkDelete(ids){ rows.value=rows.value.filter(r=>!ids.includes(r.id)); push({type:'success',title:'Bulk delete',message:ids.length+' removed'}); }
function onExport(k){ push({type:'info',title:'Export',message:'Generating '+k.toUpperCase()+'…'}); }
function del(row){ rows.value=rows.value.filter(r=>r!==row); push({type:'success',title:'Deleted'}); }
</script>

<template>
  <div>
    <Breadcrumb title="Swap Tasks" :trail="[{ label: 'Swap Tasks' }]">
    </Breadcrumb>

    <FilterBar :loading="loading" @search="doSearch" @reset="doReset">
      <FormInput v-model="filters.keyword" label="Keyword" placeholder="Keyword" icon="ri-search-line" />
      <FormSelect v-model="filters.status" label="Status" :options="opt(['NEW','COLLECTED','IN_CONTAINER','OUT_CONTAINER','CLOSED','NO_SAMPLES'])" placeholder="Any status" />
      <FormSelect v-model="filters.driver" label="Driver" :options="opt(['Mohammed Al-Harbi','Fatimah Nasser','Khalid Otaibi','Sara Al-Dosari'])" placeholder="Any driver" />
      <FormSelect v-model="filters.client" label="Client" :options="opt(['King Faisal Lab','Al-Noor Clinic','Dallah Hospital','Saudi German','Mouwasat Lab','Habib Medical'])" placeholder="Any client" />
    </FilterBar>
    <DataTable :columns="columns" :rows="rows" row-key="id" :loading="loading"
      :bulk-actions="canDelete() ? [{ label:'Delete', icon:'ri-delete-bin-line', tone:'danger', event:'bulk-delete' }] : []"
      @bulk-delete="bulkDelete" @export="onExport">
      <template #cell-id="{ value }"><span class="font-semibold text-primary-700 dark:text-primary-300">#{{ value }}</span></template>
      <template #cell-oldDriver="{ value }"><span class="inline-flex items-center gap-2"><BaseAvatar :name="value" :size="26" /><span class="truncate">{{ value }}</span></span></template>
      <template #cell-driver="{ value }"><span class="inline-flex items-center gap-2"><BaseAvatar :name="value" :size="26" /><span class="truncate">{{ value }}</span></span></template>
      <template #cell-status="{ value }"><StatusBadge :status="value" /></template>
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
