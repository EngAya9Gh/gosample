<script setup>
/** /daily-operation — Operation list. Auto-faithful to handoff columns. Mock data; wire @query for server-side. */
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
const filters = ref({"client":"","driver":"","date":"","status":""});
const columns = [{"key":"id","label":"ID","sortable":true,"sticky":"start","width":"84px"},{"key":"from","label":"From Location"},{"key":"to","label":"To Location"},{"key":"client","label":"Billing Client"},{"key":"driver","label":"Driver"},{"key":"plate","label":"Car","mono":true},{"key":"status","label":"Status"},{"key":"arrival","label":"Arrival"},{"key":"close","label":"Close Date"},{"key":"hours","label":"Hours","align":"center"},{"key":"collection","label":"Collection"},{"key":"container","label":"Container"},{"key":"containerOut","label":"Container Out"},{"key":"created","label":"Created At"}];
const rows = ref([{"id":10428,"from":"Central Hub","to":"Central Hub","client":"King Faisal Lab","driver":"Mohammed Al-Harbi","plate":"RUH 2000","status":"NEW","arrival":"Item 1","close":"Item 1","hours":"1.0","collection":"Item 1","container":"Item 1","containerOut":"Item 1","created":"2026-06-27"},{"id":10427,"from":"Lab East","to":"Lab East","client":"Al-Noor Clinic","driver":"Fatimah Nasser","plate":"JED 2013","status":"COLLECTED","arrival":"Item 2","close":"Item 2","hours":"2.1","collection":"Item 2","container":"Item 2","containerOut":"Item 2","created":"2026-06-26"},{"id":10426,"from":"Lab West","to":"Lab West","client":"Dallah Hospital","driver":"Khalid Otaibi","plate":"DMM 2026","status":"IN_CONTAINER","arrival":"Item 3","close":"Item 3","hours":"3.2","collection":"Item 3","container":"Item 3","containerOut":"Item 3","created":"2026-06-25"},{"id":10425,"from":"North Depot","to":"North Depot","client":"Saudi German","driver":"Sara Al-Dosari","plate":"RUH 2039","status":"OUT_CONTAINER","arrival":"Item 4","close":"Item 4","hours":"4.3","collection":"Item 4","container":"Item 4","containerOut":"Item 4","created":"2026-06-24"},{"id":10424,"from":"King Faisal Lab","to":"King Faisal Lab","client":"Mouwasat Lab","driver":"Yousef Qahtani","plate":"JED 2052","status":"CLOSED","arrival":"Item 5","close":"Item 5","hours":"5.4","collection":"Item 5","container":"Item 5","containerOut":"Item 5","created":"2026-06-23"},{"id":10423,"from":"Al-Noor Clinic","to":"Al-Noor Clinic","client":"Habib Medical","driver":"Noura Faisal","plate":"DMM 2065","status":"NO_SAMPLES","arrival":"Item 6","close":"Item 6","hours":"6.5","collection":"Item 6","container":"Item 6","containerOut":"Item 6","created":"2026-06-22"},{"id":10422,"from":"Dallah Hosp.","to":"Dallah Hosp.","client":"King Faisal Lab","driver":"Abdullah Zahrani","plate":"RUH 2078","status":"NEW","arrival":"Item 7","close":"Item 7","hours":"1.6","collection":"Item 7","container":"Item 7","containerOut":"Item 7","created":"2026-06-21"},{"id":10421,"from":"Olaya Branch","to":"Olaya Branch","client":"Al-Noor Clinic","driver":"Layla Ghamdi","plate":"JED 2091","status":"COLLECTED","arrival":"Item 8","close":"Item 8","hours":"2.7","collection":"Item 8","container":"Item 8","containerOut":"Item 8","created":"2026-06-20"},{"id":10420,"from":"Central Hub","to":"Central Hub","client":"Dallah Hospital","driver":"Mohammed Al-Harbi","plate":"DMM 2104","status":"IN_CONTAINER","arrival":"Item 9","close":"Item 9","hours":"3.8","collection":"Item 9","container":"Item 9","containerOut":"Item 9","created":"2026-06-19"},{"id":10419,"from":"Lab East","to":"Lab East","client":"Saudi German","driver":"Fatimah Nasser","plate":"RUH 2117","status":"OUT_CONTAINER","arrival":"Item 10","close":"Item 10","hours":"4.0","collection":"Item 10","container":"Item 10","containerOut":"Item 10","created":"2026-06-18"},{"id":10418,"from":"Lab West","to":"Lab West","client":"Mouwasat Lab","driver":"Khalid Otaibi","plate":"JED 2130","status":"CLOSED","arrival":"Item 11","close":"Item 11","hours":"5.1","collection":"Item 11","container":"Item 11","containerOut":"Item 11","created":"2026-06-17"},{"id":10417,"from":"North Depot","to":"North Depot","client":"Habib Medical","driver":"Sara Al-Dosari","plate":"DMM 2143","status":"NO_SAMPLES","arrival":"Item 12","close":"Item 12","hours":"6.2","collection":"Item 12","container":"Item 12","containerOut":"Item 12","created":"2026-06-16"},{"id":10416,"from":"King Faisal Lab","to":"King Faisal Lab","client":"King Faisal Lab","driver":"Yousef Qahtani","plate":"RUH 2156","status":"NEW","arrival":"Item 13","close":"Item 13","hours":"1.3","collection":"Item 13","container":"Item 13","containerOut":"Item 13","created":"2026-06-15"},{"id":10415,"from":"Al-Noor Clinic","to":"Al-Noor Clinic","client":"Al-Noor Clinic","driver":"Noura Faisal","plate":"JED 2169","status":"COLLECTED","arrival":"Item 14","close":"Item 14","hours":"2.4","collection":"Item 14","container":"Item 14","containerOut":"Item 14","created":"2026-06-14"},{"id":10414,"from":"Dallah Hosp.","to":"Dallah Hosp.","client":"Dallah Hospital","driver":"Abdullah Zahrani","plate":"DMM 2182","status":"IN_CONTAINER","arrival":"Item 15","close":"Item 15","hours":"3.5","collection":"Item 15","container":"Item 15","containerOut":"Item 15","created":"2026-06-13"},{"id":10413,"from":"Olaya Branch","to":"Olaya Branch","client":"Saudi German","driver":"Layla Ghamdi","plate":"RUH 2195","status":"OUT_CONTAINER","arrival":"Item 16","close":"Item 16","hours":"4.6","collection":"Item 16","container":"Item 16","containerOut":"Item 16","created":"2026-06-12"},{"id":10412,"from":"Central Hub","to":"Central Hub","client":"Mouwasat Lab","driver":"Mohammed Al-Harbi","plate":"JED 2208","status":"CLOSED","arrival":"Item 17","close":"Item 17","hours":"5.7","collection":"Item 17","container":"Item 17","containerOut":"Item 17","created":"2026-06-11"},{"id":10411,"from":"Lab East","to":"Lab East","client":"Habib Medical","driver":"Fatimah Nasser","plate":"DMM 2221","status":"NO_SAMPLES","arrival":"Item 18","close":"Item 18","hours":"6.8","collection":"Item 18","container":"Item 18","containerOut":"Item 18","created":"2026-06-10"},{"id":10410,"from":"Lab West","to":"Lab West","client":"King Faisal Lab","driver":"Khalid Otaibi","plate":"RUH 2234","status":"NEW","arrival":"Item 19","close":"Item 19","hours":"1.0","collection":"Item 19","container":"Item 19","containerOut":"Item 19","created":"2026-06-09"},{"id":10409,"from":"North Depot","to":"North Depot","client":"Al-Noor Clinic","driver":"Sara Al-Dosari","plate":"JED 2247","status":"COLLECTED","arrival":"Item 20","close":"Item 20","hours":"2.1","collection":"Item 20","container":"Item 20","containerOut":"Item 20","created":"2026-06-08"},{"id":10408,"from":"King Faisal Lab","to":"King Faisal Lab","client":"Dallah Hospital","driver":"Yousef Qahtani","plate":"DMM 2260","status":"IN_CONTAINER","arrival":"Item 21","close":"Item 21","hours":"3.2","collection":"Item 21","container":"Item 21","containerOut":"Item 21","created":"2026-06-07"},{"id":10407,"from":"Al-Noor Clinic","to":"Al-Noor Clinic","client":"Saudi German","driver":"Noura Faisal","plate":"RUH 2273","status":"OUT_CONTAINER","arrival":"Item 22","close":"Item 22","hours":"4.3","collection":"Item 22","container":"Item 22","containerOut":"Item 22","created":"2026-06-06"}]);

function doSearch(){ loading.value=true; setTimeout(()=>{loading.value=false; push({type:'success',title:'Filters applied',message:rows.value.length+' records'});},600); }
function doReset(){ filters.value={"client":"","driver":"","date":"","status":""}; }
function bulkDelete(ids){ rows.value=rows.value.filter(r=>!ids.includes(r.id)); push({type:'success',title:'Bulk delete',message:ids.length+' removed'}); }
function onExport(k){ push({type:'info',title:'Export',message:'Generating '+k.toUpperCase()+'…'}); }
function del(row){ rows.value=rows.value.filter(r=>r!==row); push({type:'success',title:'Deleted'}); }
</script>

<template>
  <div>
    <Breadcrumb title="Daily Operation" :trail="[{ label: 'Daily Operation' }]">
    </Breadcrumb>

    <FilterBar :loading="loading" @search="doSearch" @reset="doReset">
      <FormSelect v-model="filters.client" label="Billing Client" :options="opt(['King Faisal Lab','Al-Noor Clinic','Dallah Hospital','Saudi German','Mouwasat Lab','Habib Medical'])" placeholder="Any billing client" />
      <FormSelect v-model="filters.driver" label="Driver" :options="opt(['Mohammed Al-Harbi','Fatimah Nasser','Khalid Otaibi','Sara Al-Dosari'])" placeholder="Any driver" />
      <FormInput v-model="filters.date" label="Task Date" type="date"  />
      <FormSelect v-model="filters.status" label="Status" :options="opt(['NEW','COLLECTED','IN_CONTAINER','OUT_CONTAINER','CLOSED','NO_SAMPLES'])" placeholder="Any status" />
    </FilterBar>
    <DataTable :columns="columns" :rows="rows" row-key="id" :loading="loading"
      :bulk-actions="canDelete() ? [{ label:'Delete', icon:'ri-delete-bin-line', tone:'danger', event:'bulk-delete' }] : []"
      @bulk-delete="bulkDelete" @export="onExport">
      <template #cell-id="{ value }"><span class="font-semibold text-primary-700 dark:text-primary-300">#{{ value }}</span></template>
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
