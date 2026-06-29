<script setup>
/** /admin/lost — Lost Sample list. Auto-faithful to handoff columns. Mock data; wire @query for server-side. */
import { ref } from 'vue';
import Breadcrumb from '../../components/Breadcrumb.vue';
import DataTable from '../../components/DataTable.vue';
import BaseButton from '../../components/BaseButton.vue';
import { useToast } from '../../composables/useToast';
import { usePermissions } from '../../composables/usePermissions';
import FilterBar from '../../components/FilterBar.vue';
import FormInput from '../../components/FormInput.vue';
import FormSelect from '../../components/FormSelect.vue';

const { push } = useToast();
const { can, canDelete } = usePermissions();
const loading = ref(false);
const opt = (a) => a.map(v => ({ value: v, label: v }));
const filters = ref({"from":"","to":"","barcode":"","status":""});
const columns = [{"key":"id","label":"ID","sortable":true,"sticky":"start"},{"key":"barcode","label":"Barcode","mono":true},{"key":"location","label":"Location"},{"key":"task","label":"Task","mono":true},{"key":"imei","label":"Container","mono":true},{"key":"sampleType","label":"Sample Type"},{"key":"tempType","label":"Temperature Type"},{"key":"bagCode","label":"Bag Code","mono":true},{"key":"confirmed","label":"Confirmed By Client"},{"key":"confirmedBy","label":"Confirmed By"}];
const rows = ref([{"id":10428,"barcode":"SM-8841-0050","location":"Central Hub","task":"Item 1","imei":"864920451000","sampleType":"Pickup","tempType":"Pickup","bagCode":"Item 1","confirmed":"Item 1","confirmedBy":"Item 1"},{"id":10427,"barcode":"SM-8841-0051","location":"Lab East","task":"Item 2","imei":"864920451007","sampleType":"Delivery","tempType":"Delivery","bagCode":"Item 2","confirmed":"Item 2","confirmedBy":"Item 2"},{"id":10426,"barcode":"SM-8841-0052","location":"Lab West","task":"Item 3","imei":"864920451014","sampleType":"Round-trip","tempType":"Round-trip","bagCode":"Item 3","confirmed":"Item 3","confirmedBy":"Item 3"},{"id":10425,"barcode":"SM-8841-0053","location":"North Depot","task":"Item 4","imei":"864920451021","sampleType":"Pickup","tempType":"Pickup","bagCode":"Item 4","confirmed":"Item 4","confirmedBy":"Item 4"},{"id":10424,"barcode":"SM-8841-0054","location":"King Faisal Lab","task":"Item 5","imei":"864920451028","sampleType":"Delivery","tempType":"Delivery","bagCode":"Item 5","confirmed":"Item 5","confirmedBy":"Item 5"},{"id":10423,"barcode":"SM-8841-0055","location":"Al-Noor Clinic","task":"Item 6","imei":"864920451035","sampleType":"Round-trip","tempType":"Round-trip","bagCode":"Item 6","confirmed":"Item 6","confirmedBy":"Item 6"},{"id":10422,"barcode":"SM-8841-0056","location":"Dallah Hosp.","task":"Item 7","imei":"864920451042","sampleType":"Pickup","tempType":"Pickup","bagCode":"Item 7","confirmed":"Item 7","confirmedBy":"Item 7"},{"id":10421,"barcode":"SM-8841-0057","location":"Olaya Branch","task":"Item 8","imei":"864920451049","sampleType":"Delivery","tempType":"Delivery","bagCode":"Item 8","confirmed":"Item 8","confirmedBy":"Item 8"},{"id":10420,"barcode":"SM-8841-0058","location":"Central Hub","task":"Item 9","imei":"864920451056","sampleType":"Round-trip","tempType":"Round-trip","bagCode":"Item 9","confirmed":"Item 9","confirmedBy":"Item 9"},{"id":10419,"barcode":"SM-8841-0059","location":"Lab East","task":"Item 10","imei":"864920451063","sampleType":"Pickup","tempType":"Pickup","bagCode":"Item 10","confirmed":"Item 10","confirmedBy":"Item 10"},{"id":10418,"barcode":"SM-8841-0060","location":"Lab West","task":"Item 11","imei":"864920451070","sampleType":"Delivery","tempType":"Delivery","bagCode":"Item 11","confirmed":"Item 11","confirmedBy":"Item 11"},{"id":10417,"barcode":"SM-8841-0061","location":"North Depot","task":"Item 12","imei":"864920451077","sampleType":"Round-trip","tempType":"Round-trip","bagCode":"Item 12","confirmed":"Item 12","confirmedBy":"Item 12"},{"id":10416,"barcode":"SM-8841-0062","location":"King Faisal Lab","task":"Item 13","imei":"864920451084","sampleType":"Pickup","tempType":"Pickup","bagCode":"Item 13","confirmed":"Item 13","confirmedBy":"Item 13"},{"id":10415,"barcode":"SM-8841-0063","location":"Al-Noor Clinic","task":"Item 14","imei":"864920451091","sampleType":"Delivery","tempType":"Delivery","bagCode":"Item 14","confirmed":"Item 14","confirmedBy":"Item 14"},{"id":10414,"barcode":"SM-8841-0064","location":"Dallah Hosp.","task":"Item 15","imei":"864920451098","sampleType":"Round-trip","tempType":"Round-trip","bagCode":"Item 15","confirmed":"Item 15","confirmedBy":"Item 15"},{"id":10413,"barcode":"SM-8841-0065","location":"Olaya Branch","task":"Item 16","imei":"864920451105","sampleType":"Pickup","tempType":"Pickup","bagCode":"Item 16","confirmed":"Item 16","confirmedBy":"Item 16"},{"id":10412,"barcode":"SM-8841-0066","location":"Central Hub","task":"Item 17","imei":"864920451112","sampleType":"Delivery","tempType":"Delivery","bagCode":"Item 17","confirmed":"Item 17","confirmedBy":"Item 17"},{"id":10411,"barcode":"SM-8841-0067","location":"Lab East","task":"Item 18","imei":"864920451119","sampleType":"Round-trip","tempType":"Round-trip","bagCode":"Item 18","confirmed":"Item 18","confirmedBy":"Item 18"},{"id":10410,"barcode":"SM-8841-0068","location":"Lab West","task":"Item 19","imei":"864920451126","sampleType":"Pickup","tempType":"Pickup","bagCode":"Item 19","confirmed":"Item 19","confirmedBy":"Item 19"},{"id":10409,"barcode":"SM-8841-0069","location":"North Depot","task":"Item 20","imei":"864920451133","sampleType":"Delivery","tempType":"Delivery","bagCode":"Item 20","confirmed":"Item 20","confirmedBy":"Item 20"},{"id":10408,"barcode":"SM-8841-0070","location":"King Faisal Lab","task":"Item 21","imei":"864920451140","sampleType":"Round-trip","tempType":"Round-trip","bagCode":"Item 21","confirmed":"Item 21","confirmedBy":"Item 21"},{"id":10407,"barcode":"SM-8841-0071","location":"Al-Noor Clinic","task":"Item 22","imei":"864920451147","sampleType":"Pickup","tempType":"Pickup","bagCode":"Item 22","confirmed":"Item 22","confirmedBy":"Item 22"}]);

function doSearch(){ loading.value=true; setTimeout(()=>{loading.value=false; push({type:'success',title:'Filters applied',message:rows.value.length+' records'});},600); }
function doReset(){ filters.value={"from":"","to":"","barcode":"","status":""}; }
function bulkDelete(ids){ rows.value=rows.value.filter(r=>!ids.includes(r.id)); push({type:'success',title:'Bulk delete',message:ids.length+' removed'}); }
function onExport(k){ push({type:'info',title:'Export',message:'Generating '+k.toUpperCase()+'…'}); }
function del(row){ rows.value=rows.value.filter(r=>r!==row); push({type:'success',title:'Deleted'}); }
</script>

<template>
  <div>
    <Breadcrumb title="Lost Samples" :trail="[{ label: 'Lost Samples' }]">
    </Breadcrumb>

    <FilterBar :loading="loading" @search="doSearch" @reset="doReset">
      <FormInput v-model="filters.from" label="From Date" type="date"  />
      <FormInput v-model="filters.to" label="To Date" type="date"  />
      <FormInput v-model="filters.barcode" label="Barcode" placeholder="Barcode" icon="ri-barcode-line" />
      <FormSelect v-model="filters.status" label="Status" :options="opt(['LOST','RECEIVED','PENDING'])" placeholder="Any status" />
    </FilterBar>
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
