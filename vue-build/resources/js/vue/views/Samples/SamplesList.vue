<script setup>
/** /admin/samples — Sample list. Auto-faithful to handoff columns. Mock data; wire @query for server-side. */
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
const filters = ref({"from":"","to":"","barcode":"","status":""});
const columns = [{"key":"id","label":"ID","sortable":true,"sticky":"start"},{"key":"location","label":"Location"},{"key":"to","label":"To Location"},{"key":"task","label":"Task","mono":true},{"key":"barcode","label":"Barcode","mono":true},{"key":"driver","label":"Driver"},{"key":"collection","label":"Collection Date"},{"key":"close","label":"Close Date"},{"key":"confirmed","label":"Confirmed By Client"}];
const rows = ref([{"id":10428,"location":"Central Hub","to":"Central Hub","task":"Item 1","barcode":"SM-8841-0050","driver":"Mohammed Al-Harbi","collection":"Item 1","close":"Item 1","confirmed":"Item 1"},{"id":10427,"location":"Lab East","to":"Lab East","task":"Item 2","barcode":"SM-8841-0051","driver":"Fatimah Nasser","collection":"Item 2","close":"Item 2","confirmed":"Item 2"},{"id":10426,"location":"Lab West","to":"Lab West","task":"Item 3","barcode":"SM-8841-0052","driver":"Khalid Otaibi","collection":"Item 3","close":"Item 3","confirmed":"Item 3"},{"id":10425,"location":"North Depot","to":"North Depot","task":"Item 4","barcode":"SM-8841-0053","driver":"Sara Al-Dosari","collection":"Item 4","close":"Item 4","confirmed":"Item 4"},{"id":10424,"location":"King Faisal Lab","to":"King Faisal Lab","task":"Item 5","barcode":"SM-8841-0054","driver":"Yousef Qahtani","collection":"Item 5","close":"Item 5","confirmed":"Item 5"},{"id":10423,"location":"Al-Noor Clinic","to":"Al-Noor Clinic","task":"Item 6","barcode":"SM-8841-0055","driver":"Noura Faisal","collection":"Item 6","close":"Item 6","confirmed":"Item 6"},{"id":10422,"location":"Dallah Hosp.","to":"Dallah Hosp.","task":"Item 7","barcode":"SM-8841-0056","driver":"Abdullah Zahrani","collection":"Item 7","close":"Item 7","confirmed":"Item 7"},{"id":10421,"location":"Olaya Branch","to":"Olaya Branch","task":"Item 8","barcode":"SM-8841-0057","driver":"Layla Ghamdi","collection":"Item 8","close":"Item 8","confirmed":"Item 8"},{"id":10420,"location":"Central Hub","to":"Central Hub","task":"Item 9","barcode":"SM-8841-0058","driver":"Mohammed Al-Harbi","collection":"Item 9","close":"Item 9","confirmed":"Item 9"},{"id":10419,"location":"Lab East","to":"Lab East","task":"Item 10","barcode":"SM-8841-0059","driver":"Fatimah Nasser","collection":"Item 10","close":"Item 10","confirmed":"Item 10"},{"id":10418,"location":"Lab West","to":"Lab West","task":"Item 11","barcode":"SM-8841-0060","driver":"Khalid Otaibi","collection":"Item 11","close":"Item 11","confirmed":"Item 11"},{"id":10417,"location":"North Depot","to":"North Depot","task":"Item 12","barcode":"SM-8841-0061","driver":"Sara Al-Dosari","collection":"Item 12","close":"Item 12","confirmed":"Item 12"},{"id":10416,"location":"King Faisal Lab","to":"King Faisal Lab","task":"Item 13","barcode":"SM-8841-0062","driver":"Yousef Qahtani","collection":"Item 13","close":"Item 13","confirmed":"Item 13"},{"id":10415,"location":"Al-Noor Clinic","to":"Al-Noor Clinic","task":"Item 14","barcode":"SM-8841-0063","driver":"Noura Faisal","collection":"Item 14","close":"Item 14","confirmed":"Item 14"},{"id":10414,"location":"Dallah Hosp.","to":"Dallah Hosp.","task":"Item 15","barcode":"SM-8841-0064","driver":"Abdullah Zahrani","collection":"Item 15","close":"Item 15","confirmed":"Item 15"},{"id":10413,"location":"Olaya Branch","to":"Olaya Branch","task":"Item 16","barcode":"SM-8841-0065","driver":"Layla Ghamdi","collection":"Item 16","close":"Item 16","confirmed":"Item 16"},{"id":10412,"location":"Central Hub","to":"Central Hub","task":"Item 17","barcode":"SM-8841-0066","driver":"Mohammed Al-Harbi","collection":"Item 17","close":"Item 17","confirmed":"Item 17"},{"id":10411,"location":"Lab East","to":"Lab East","task":"Item 18","barcode":"SM-8841-0067","driver":"Fatimah Nasser","collection":"Item 18","close":"Item 18","confirmed":"Item 18"},{"id":10410,"location":"Lab West","to":"Lab West","task":"Item 19","barcode":"SM-8841-0068","driver":"Khalid Otaibi","collection":"Item 19","close":"Item 19","confirmed":"Item 19"},{"id":10409,"location":"North Depot","to":"North Depot","task":"Item 20","barcode":"SM-8841-0069","driver":"Sara Al-Dosari","collection":"Item 20","close":"Item 20","confirmed":"Item 20"},{"id":10408,"location":"King Faisal Lab","to":"King Faisal Lab","task":"Item 21","barcode":"SM-8841-0070","driver":"Yousef Qahtani","collection":"Item 21","close":"Item 21","confirmed":"Item 21"},{"id":10407,"location":"Al-Noor Clinic","to":"Al-Noor Clinic","task":"Item 22","barcode":"SM-8841-0071","driver":"Noura Faisal","collection":"Item 22","close":"Item 22","confirmed":"Item 22"}]);

function doSearch(){ loading.value=true; setTimeout(()=>{loading.value=false; push({type:'success',title:'Filters applied',message:rows.value.length+' records'});},600); }
function doReset(){ filters.value={"from":"","to":"","barcode":"","status":""}; }
function bulkDelete(ids){ rows.value=rows.value.filter(r=>!ids.includes(r.id)); push({type:'success',title:'Bulk delete',message:ids.length+' removed'}); }
function onExport(k){ push({type:'info',title:'Export',message:'Generating '+k.toUpperCase()+'…'}); }
function del(row){ rows.value=rows.value.filter(r=>r!==row); push({type:'success',title:'Deleted'}); }
</script>

<template>
  <div>
    <Breadcrumb title="Samples" :trail="[{ label: 'Samples' }]">
    </Breadcrumb>

    <FilterBar :loading="loading" @search="doSearch" @reset="doReset">
      <FormInput v-model="filters.from" label="From Date" type="date"  />
      <FormInput v-model="filters.to" label="To Date" type="date"  />
      <FormInput v-model="filters.barcode" label="Barcode" placeholder="Barcode" icon="ri-barcode-line" />
      <FormSelect v-model="filters.status" label="Status" :options="opt(['RECEIVED','PENDING','LOST'])" placeholder="Any status" />
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
