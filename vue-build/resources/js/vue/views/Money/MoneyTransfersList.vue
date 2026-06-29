<script setup>
/** /admin/money-transfers — Money Transfer list. Auto-faithful to handoff columns. Mock data; wire @query for server-side. */
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
const columns = [{"key":"id","label":"ID","sortable":true,"sticky":"start"},{"key":"driver","label":"Driver"},{"key":"client","label":"Client"},{"key":"from","label":"From Location"},{"key":"to","label":"To Location"},{"key":"status","label":"Status"},{"key":"fromOtp","label":"From OTP","mono":true},{"key":"toOtp","label":"To OTP","mono":true},{"key":"amount","label":"Amount","align":"end"}];
const rows = ref([{"id":10428,"driver":"Mohammed Al-Harbi","client":"King Faisal Lab","from":"Central Hub","to":"Central Hub","status":"NEW","fromOtp":"100000","toOtp":"100000","amount":"250 SAR"},{"id":10427,"driver":"Fatimah Nasser","client":"Al-Noor Clinic","from":"Lab East","to":"Lab East","status":"COLLECTED","fromOtp":"100271","toOtp":"100271","amount":"325 SAR"},{"id":10426,"driver":"Khalid Otaibi","client":"Dallah Hospital","from":"Lab West","to":"Lab West","status":"IN_CONTAINER","fromOtp":"100542","toOtp":"100542","amount":"400 SAR"},{"id":10425,"driver":"Sara Al-Dosari","client":"Saudi German","from":"North Depot","to":"North Depot","status":"OUT_CONTAINER","fromOtp":"100813","toOtp":"100813","amount":"475 SAR"},{"id":10424,"driver":"Yousef Qahtani","client":"Mouwasat Lab","from":"King Faisal Lab","to":"King Faisal Lab","status":"CLOSED","fromOtp":"101084","toOtp":"101084","amount":"550 SAR"},{"id":10423,"driver":"Noura Faisal","client":"Habib Medical","from":"Al-Noor Clinic","to":"Al-Noor Clinic","status":"NO_SAMPLES","fromOtp":"101355","toOtp":"101355","amount":"625 SAR"},{"id":10422,"driver":"Abdullah Zahrani","client":"King Faisal Lab","from":"Dallah Hosp.","to":"Dallah Hosp.","status":"NEW","fromOtp":"101626","toOtp":"101626","amount":"700 SAR"},{"id":10421,"driver":"Layla Ghamdi","client":"Al-Noor Clinic","from":"Olaya Branch","to":"Olaya Branch","status":"COLLECTED","fromOtp":"101897","toOtp":"101897","amount":"775 SAR"},{"id":10420,"driver":"Mohammed Al-Harbi","client":"Dallah Hospital","from":"Central Hub","to":"Central Hub","status":"IN_CONTAINER","fromOtp":"102168","toOtp":"102168","amount":"850 SAR"},{"id":10419,"driver":"Fatimah Nasser","client":"Saudi German","from":"Lab East","to":"Lab East","status":"OUT_CONTAINER","fromOtp":"102439","toOtp":"102439","amount":"925 SAR"},{"id":10418,"driver":"Khalid Otaibi","client":"Mouwasat Lab","from":"Lab West","to":"Lab West","status":"CLOSED","fromOtp":"102710","toOtp":"102710","amount":"1,000 SAR"},{"id":10417,"driver":"Sara Al-Dosari","client":"Habib Medical","from":"North Depot","to":"North Depot","status":"NO_SAMPLES","fromOtp":"102981","toOtp":"102981","amount":"1,075 SAR"},{"id":10416,"driver":"Yousef Qahtani","client":"King Faisal Lab","from":"King Faisal Lab","to":"King Faisal Lab","status":"NEW","fromOtp":"103252","toOtp":"103252","amount":"1,150 SAR"},{"id":10415,"driver":"Noura Faisal","client":"Al-Noor Clinic","from":"Al-Noor Clinic","to":"Al-Noor Clinic","status":"COLLECTED","fromOtp":"103523","toOtp":"103523","amount":"1,225 SAR"},{"id":10414,"driver":"Abdullah Zahrani","client":"Dallah Hospital","from":"Dallah Hosp.","to":"Dallah Hosp.","status":"IN_CONTAINER","fromOtp":"103794","toOtp":"103794","amount":"1,300 SAR"},{"id":10413,"driver":"Layla Ghamdi","client":"Saudi German","from":"Olaya Branch","to":"Olaya Branch","status":"OUT_CONTAINER","fromOtp":"104065","toOtp":"104065","amount":"1,375 SAR"},{"id":10412,"driver":"Mohammed Al-Harbi","client":"Mouwasat Lab","from":"Central Hub","to":"Central Hub","status":"CLOSED","fromOtp":"104336","toOtp":"104336","amount":"1,450 SAR"},{"id":10411,"driver":"Fatimah Nasser","client":"Habib Medical","from":"Lab East","to":"Lab East","status":"NO_SAMPLES","fromOtp":"104607","toOtp":"104607","amount":"1,525 SAR"},{"id":10410,"driver":"Khalid Otaibi","client":"King Faisal Lab","from":"Lab West","to":"Lab West","status":"NEW","fromOtp":"104878","toOtp":"104878","amount":"1,600 SAR"},{"id":10409,"driver":"Sara Al-Dosari","client":"Al-Noor Clinic","from":"North Depot","to":"North Depot","status":"COLLECTED","fromOtp":"105149","toOtp":"105149","amount":"1,675 SAR"},{"id":10408,"driver":"Yousef Qahtani","client":"Dallah Hospital","from":"King Faisal Lab","to":"King Faisal Lab","status":"IN_CONTAINER","fromOtp":"105420","toOtp":"105420","amount":"1,750 SAR"},{"id":10407,"driver":"Noura Faisal","client":"Saudi German","from":"Al-Noor Clinic","to":"Al-Noor Clinic","status":"OUT_CONTAINER","fromOtp":"105691","toOtp":"105691","amount":"1,825 SAR"}]);

function doSearch(){ loading.value=true; setTimeout(()=>{loading.value=false; push({type:'success',title:'Filters applied',message:rows.value.length+' records'});},600); }
function doReset(){ filters.value={}; }
function bulkDelete(ids){ rows.value=rows.value.filter(r=>!ids.includes(r.id)); push({type:'success',title:'Bulk delete',message:ids.length+' removed'}); }
function onExport(k){ push({type:'info',title:'Export',message:'Generating '+k.toUpperCase()+'…'}); }
function del(row){ rows.value=rows.value.filter(r=>r!==row); push({type:'success',title:'Deleted'}); }
</script>

<template>
  <div>
    <Breadcrumb title="Money Transfers" :trail="[{ label: 'Money Transfers' }]">
      <template #actions>
        <BaseButton v-if="can('money_create')" variant="primary" icon="ri-add-line">Add Transfer</BaseButton>
      </template>
    </Breadcrumb>

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
