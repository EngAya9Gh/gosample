<script setup>
/** /admin/shipments — Shipment list. Auto-faithful to handoff columns. Mock data; wire @query for server-side. */
import { ref } from 'vue';
import Breadcrumb from '../../components/Breadcrumb.vue';
import DataTable from '../../components/DataTable.vue';
import BaseButton from '../../components/BaseButton.vue';
import { useToast } from '../../composables/useToast';
import { usePermissions } from '../../composables/usePermissions';
import StatusBadge from '../../components/StatusBadge.vue';
import BaseAvatar from '../../components/BaseAvatar.vue';

const { push } = useToast();
const { can, canDelete } = usePermissions();
const loading = ref(false);
const opt = (a) => a.map(v => ({ value: v, label: v }));
const filters = ref({});
const columns = [{"key":"id","label":"ID","sortable":true,"sticky":"start"},{"key":"carrier","label":"Carrier"},{"key":"from","label":"Location From"},{"key":"to","label":"Location To"},{"key":"reference","label":"Reference Number","mono":true},{"key":"pickupOtp","label":"Pickup OTP","mono":true},{"key":"status","label":"Status"},{"key":"dropOtp","label":"Drop-off OTP","mono":true},{"key":"batch","label":"Batch","mono":true},{"key":"journey","label":"Journey Type"},{"key":"sla","label":"SLA Code","mono":true},{"key":"task","label":"Task","mono":true},{"key":"created","label":"Created At"}];
const rows = ref([{"id":10428,"carrier":"Mohammed Al-Harbi","from":"Central Hub","to":"Central Hub","reference":"REF-90000","pickupOtp":"100000","status":"NEW","dropOtp":"100000","batch":"Item 1","journey":"Item 1","sla":"Item 1","task":"Item 1","created":"2026-06-27"},{"id":10427,"carrier":"Fatimah Nasser","from":"Lab East","to":"Lab East","reference":"REF-90017","pickupOtp":"100271","status":"COLLECTED","dropOtp":"100271","batch":"Item 2","journey":"Item 2","sla":"Item 2","task":"Item 2","created":"2026-06-26"},{"id":10426,"carrier":"Khalid Otaibi","from":"Lab West","to":"Lab West","reference":"REF-90034","pickupOtp":"100542","status":"IN_CONTAINER","dropOtp":"100542","batch":"Item 3","journey":"Item 3","sla":"Item 3","task":"Item 3","created":"2026-06-25"},{"id":10425,"carrier":"Sara Al-Dosari","from":"North Depot","to":"North Depot","reference":"REF-90051","pickupOtp":"100813","status":"OUT_CONTAINER","dropOtp":"100813","batch":"Item 4","journey":"Item 4","sla":"Item 4","task":"Item 4","created":"2026-06-24"},{"id":10424,"carrier":"Yousef Qahtani","from":"King Faisal Lab","to":"King Faisal Lab","reference":"REF-90068","pickupOtp":"101084","status":"CLOSED","dropOtp":"101084","batch":"Item 5","journey":"Item 5","sla":"Item 5","task":"Item 5","created":"2026-06-23"},{"id":10423,"carrier":"Noura Faisal","from":"Al-Noor Clinic","to":"Al-Noor Clinic","reference":"REF-90085","pickupOtp":"101355","status":"NO_SAMPLES","dropOtp":"101355","batch":"Item 6","journey":"Item 6","sla":"Item 6","task":"Item 6","created":"2026-06-22"},{"id":10422,"carrier":"Abdullah Zahrani","from":"Dallah Hosp.","to":"Dallah Hosp.","reference":"REF-90102","pickupOtp":"101626","status":"NEW","dropOtp":"101626","batch":"Item 7","journey":"Item 7","sla":"Item 7","task":"Item 7","created":"2026-06-21"},{"id":10421,"carrier":"Layla Ghamdi","from":"Olaya Branch","to":"Olaya Branch","reference":"REF-90119","pickupOtp":"101897","status":"COLLECTED","dropOtp":"101897","batch":"Item 8","journey":"Item 8","sla":"Item 8","task":"Item 8","created":"2026-06-20"},{"id":10420,"carrier":"Mohammed Al-Harbi","from":"Central Hub","to":"Central Hub","reference":"REF-90136","pickupOtp":"102168","status":"IN_CONTAINER","dropOtp":"102168","batch":"Item 9","journey":"Item 9","sla":"Item 9","task":"Item 9","created":"2026-06-19"},{"id":10419,"carrier":"Fatimah Nasser","from":"Lab East","to":"Lab East","reference":"REF-90153","pickupOtp":"102439","status":"OUT_CONTAINER","dropOtp":"102439","batch":"Item 10","journey":"Item 10","sla":"Item 10","task":"Item 10","created":"2026-06-18"},{"id":10418,"carrier":"Khalid Otaibi","from":"Lab West","to":"Lab West","reference":"REF-90170","pickupOtp":"102710","status":"CLOSED","dropOtp":"102710","batch":"Item 11","journey":"Item 11","sla":"Item 11","task":"Item 11","created":"2026-06-17"},{"id":10417,"carrier":"Sara Al-Dosari","from":"North Depot","to":"North Depot","reference":"REF-90187","pickupOtp":"102981","status":"NO_SAMPLES","dropOtp":"102981","batch":"Item 12","journey":"Item 12","sla":"Item 12","task":"Item 12","created":"2026-06-16"},{"id":10416,"carrier":"Yousef Qahtani","from":"King Faisal Lab","to":"King Faisal Lab","reference":"REF-90204","pickupOtp":"103252","status":"NEW","dropOtp":"103252","batch":"Item 13","journey":"Item 13","sla":"Item 13","task":"Item 13","created":"2026-06-15"},{"id":10415,"carrier":"Noura Faisal","from":"Al-Noor Clinic","to":"Al-Noor Clinic","reference":"REF-90221","pickupOtp":"103523","status":"COLLECTED","dropOtp":"103523","batch":"Item 14","journey":"Item 14","sla":"Item 14","task":"Item 14","created":"2026-06-14"},{"id":10414,"carrier":"Abdullah Zahrani","from":"Dallah Hosp.","to":"Dallah Hosp.","reference":"REF-90238","pickupOtp":"103794","status":"IN_CONTAINER","dropOtp":"103794","batch":"Item 15","journey":"Item 15","sla":"Item 15","task":"Item 15","created":"2026-06-13"},{"id":10413,"carrier":"Layla Ghamdi","from":"Olaya Branch","to":"Olaya Branch","reference":"REF-90255","pickupOtp":"104065","status":"OUT_CONTAINER","dropOtp":"104065","batch":"Item 16","journey":"Item 16","sla":"Item 16","task":"Item 16","created":"2026-06-12"},{"id":10412,"carrier":"Mohammed Al-Harbi","from":"Central Hub","to":"Central Hub","reference":"REF-90272","pickupOtp":"104336","status":"CLOSED","dropOtp":"104336","batch":"Item 17","journey":"Item 17","sla":"Item 17","task":"Item 17","created":"2026-06-11"},{"id":10411,"carrier":"Fatimah Nasser","from":"Lab East","to":"Lab East","reference":"REF-90289","pickupOtp":"104607","status":"NO_SAMPLES","dropOtp":"104607","batch":"Item 18","journey":"Item 18","sla":"Item 18","task":"Item 18","created":"2026-06-10"},{"id":10410,"carrier":"Khalid Otaibi","from":"Lab West","to":"Lab West","reference":"REF-90306","pickupOtp":"104878","status":"NEW","dropOtp":"104878","batch":"Item 19","journey":"Item 19","sla":"Item 19","task":"Item 19","created":"2026-06-09"},{"id":10409,"carrier":"Sara Al-Dosari","from":"North Depot","to":"North Depot","reference":"REF-90323","pickupOtp":"105149","status":"COLLECTED","dropOtp":"105149","batch":"Item 20","journey":"Item 20","sla":"Item 20","task":"Item 20","created":"2026-06-08"},{"id":10408,"carrier":"Yousef Qahtani","from":"King Faisal Lab","to":"King Faisal Lab","reference":"REF-90340","pickupOtp":"105420","status":"IN_CONTAINER","dropOtp":"105420","batch":"Item 21","journey":"Item 21","sla":"Item 21","task":"Item 21","created":"2026-06-07"},{"id":10407,"carrier":"Noura Faisal","from":"Al-Noor Clinic","to":"Al-Noor Clinic","reference":"REF-90357","pickupOtp":"105691","status":"OUT_CONTAINER","dropOtp":"105691","batch":"Item 22","journey":"Item 22","sla":"Item 22","task":"Item 22","created":"2026-06-06"}]);

function doSearch(){ loading.value=true; setTimeout(()=>{loading.value=false; push({type:'success',title:'Filters applied',message:rows.value.length+' records'});},600); }
function doReset(){ filters.value={}; }
function bulkDelete(ids){ rows.value=rows.value.filter(r=>!ids.includes(r.id)); push({type:'success',title:'Bulk delete',message:ids.length+' removed'}); }
function onExport(k){ push({type:'info',title:'Export',message:'Generating '+k.toUpperCase()+'…'}); }
function del(row){ rows.value=rows.value.filter(r=>r!==row); push({type:'success',title:'Deleted'}); }
</script>

<template>
  <div>
    <Breadcrumb title="Shipments" :trail="[{ label: 'Shipments' }]">
      <template #actions>
        <BaseButton v-if="can('shipment_create')" variant="primary" icon="ri-add-line">Add Shipment</BaseButton>
      </template>
    </Breadcrumb>

    <DataTable :columns="columns" :rows="rows" row-key="id" :loading="loading"
      :bulk-actions="canDelete() ? [{ label:'Delete', icon:'ri-delete-bin-line', tone:'danger', event:'bulk-delete' }] : []"
      @bulk-delete="bulkDelete" @export="onExport">
      <template #cell-id="{ value }"><span class="font-semibold text-primary-700 dark:text-primary-300">#{{ value }}</span></template>
      <template #cell-carrier="{ value }"><span class="inline-flex items-center gap-2"><BaseAvatar :name="value" :size="26" /><span class="truncate">{{ value }}</span></span></template>
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
