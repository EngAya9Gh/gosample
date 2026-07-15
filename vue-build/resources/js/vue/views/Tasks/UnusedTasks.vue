<script setup>
/** /admin/tasks/unused — Task list. Auto-faithful to handoff columns. Mock data; wire @query for server-side. */
import { ref } from 'vue';
import Breadcrumb from '../../components/Breadcrumb.vue';
import DataTable from '../../components/DataTable.vue';
import BaseButton from '../../components/BaseButton.vue';
import { useToast } from '../../composables/useToast';
import { usePermissions } from '../../composables/usePermissions';
import FilterBar from '../../components/FilterBar.vue';
import FormInput from '../../components/FormInput.vue';
import FormSelect from '../../components/FormSelect.vue';
import BaseAvatar from '../../components/BaseAvatar.vue';

const { push } = useToast();
const { can, canDelete } = usePermissions();
const loading = ref(false);
const opt = (a) => a.map(v => ({ value: v, label: v }));
const filters = ref({"client":"","driver":"","from":"","to":""});
const columns = [{"key":"id","label":"ID","sortable":true,"sticky":"start"},{"key":"orderDate","label":"Order Date"},{"key":"client","label":"Client"},{"key":"driver","label":"Driver"},{"key":"from","label":"From Location"},{"key":"to","label":"To Location"}];
const rows = ref([{"id":10428,"orderDate":"2026-06-27","client":"King Faisal Lab","driver":"Mohammed Al-Harbi","from":"Central Hub","to":"Central Hub"},{"id":10427,"orderDate":"2026-06-26","client":"Al-Noor Clinic","driver":"Fatimah Nasser","from":"Lab East","to":"Lab East"},{"id":10426,"orderDate":"2026-06-25","client":"Dallah Hospital","driver":"Khalid Otaibi","from":"Lab West","to":"Lab West"},{"id":10425,"orderDate":"2026-06-24","client":"Saudi German","driver":"Sara Al-Dosari","from":"North Depot","to":"North Depot"},{"id":10424,"orderDate":"2026-06-23","client":"Mouwasat Lab","driver":"Yousef Qahtani","from":"King Faisal Lab","to":"King Faisal Lab"},{"id":10423,"orderDate":"2026-06-22","client":"Habib Medical","driver":"Noura Faisal","from":"Al-Noor Clinic","to":"Al-Noor Clinic"},{"id":10422,"orderDate":"2026-06-21","client":"King Faisal Lab","driver":"Abdullah Zahrani","from":"Dallah Hosp.","to":"Dallah Hosp."},{"id":10421,"orderDate":"2026-06-20","client":"Al-Noor Clinic","driver":"Layla Ghamdi","from":"Olaya Branch","to":"Olaya Branch"},{"id":10420,"orderDate":"2026-06-19","client":"Dallah Hospital","driver":"Mohammed Al-Harbi","from":"Central Hub","to":"Central Hub"},{"id":10419,"orderDate":"2026-06-18","client":"Saudi German","driver":"Fatimah Nasser","from":"Lab East","to":"Lab East"},{"id":10418,"orderDate":"2026-06-17","client":"Mouwasat Lab","driver":"Khalid Otaibi","from":"Lab West","to":"Lab West"},{"id":10417,"orderDate":"2026-06-16","client":"Habib Medical","driver":"Sara Al-Dosari","from":"North Depot","to":"North Depot"},{"id":10416,"orderDate":"2026-06-15","client":"King Faisal Lab","driver":"Yousef Qahtani","from":"King Faisal Lab","to":"King Faisal Lab"},{"id":10415,"orderDate":"2026-06-14","client":"Al-Noor Clinic","driver":"Noura Faisal","from":"Al-Noor Clinic","to":"Al-Noor Clinic"},{"id":10414,"orderDate":"2026-06-13","client":"Dallah Hospital","driver":"Abdullah Zahrani","from":"Dallah Hosp.","to":"Dallah Hosp."},{"id":10413,"orderDate":"2026-06-12","client":"Saudi German","driver":"Layla Ghamdi","from":"Olaya Branch","to":"Olaya Branch"},{"id":10412,"orderDate":"2026-06-11","client":"Mouwasat Lab","driver":"Mohammed Al-Harbi","from":"Central Hub","to":"Central Hub"},{"id":10411,"orderDate":"2026-06-10","client":"Habib Medical","driver":"Fatimah Nasser","from":"Lab East","to":"Lab East"},{"id":10410,"orderDate":"2026-06-09","client":"King Faisal Lab","driver":"Khalid Otaibi","from":"Lab West","to":"Lab West"},{"id":10409,"orderDate":"2026-06-08","client":"Al-Noor Clinic","driver":"Sara Al-Dosari","from":"North Depot","to":"North Depot"},{"id":10408,"orderDate":"2026-06-07","client":"Dallah Hospital","driver":"Yousef Qahtani","from":"King Faisal Lab","to":"King Faisal Lab"},{"id":10407,"orderDate":"2026-06-06","client":"Saudi German","driver":"Noura Faisal","from":"Al-Noor Clinic","to":"Al-Noor Clinic"}]);

function doSearch(){ loading.value=true; setTimeout(()=>{loading.value=false; push({type:'success',title:'Filters applied',message:rows.value.length+' records'});},600); }
function doReset(){ filters.value={"client":"","driver":"","from":"","to":""}; }
function bulkDelete(ids){ rows.value=rows.value.filter(r=>!ids.includes(r.id)); push({type:'success',title:'Bulk delete',message:ids.length+' removed'}); }
function onExport(k){ push({type:'info',title:'Export',message:'Generating '+k.toUpperCase()+'…'}); }
function del(row){ rows.value=rows.value.filter(r=>r!==row); push({type:'success',title:'Deleted'}); }
</script>

<template>
  <div>
    <Breadcrumb title="Unused Tasks" :trail="[{ label: 'Unused Tasks' }]">
    </Breadcrumb>

    <FilterBar :loading="loading" @search="doSearch" @reset="doReset">
      <FormSelect v-model="filters.client" label="Client" :options="opt(['King Faisal Lab','Al-Noor Clinic','Dallah Hospital','Saudi German','Mouwasat Lab','Habib Medical'])" placeholder="Any client" />
      <FormSelect v-model="filters.driver" label="Driver" :options="opt(['Mohammed Al-Harbi','Fatimah Nasser','Khalid Otaibi','Sara Al-Dosari'])" placeholder="Any driver" />
      <FormInput v-model="filters.from" label="Date From" type="date"  />
      <FormInput v-model="filters.to" label="Date To" type="date"  />
    </FilterBar>
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
