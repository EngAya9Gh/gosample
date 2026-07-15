<script setup>
/** /admin/cars — Car list. Auto-faithful to handoff columns. Mock data; wire @query for server-side. */
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
const filters = ref({"from":"","to":"","imei":"","plate":"","status":""});
const columns = [{"key":"id","label":"ID","sortable":true,"sticky":"start"},{"key":"driver","label":"Driver"},{"key":"mobile","label":"Mobile","mono":true},{"key":"imei","label":"IMEI","mono":true},{"key":"plate","label":"Plate Number","mono":true},{"key":"model","label":"Model"},{"key":"color","label":"Color"},{"key":"contact","label":"Contact Person"},{"key":"status","label":"Status"},{"key":"description","label":"Description"}];
const rows = ref([{"id":10428,"driver":"Mohammed Al-Harbi","mobile":"+966510000000","imei":"864920451000","plate":"RUH 2000","model":"Item 1","color":"Item 1","contact":"Item 1","status":"DISABLED","description":"—"},{"id":10427,"driver":"Fatimah Nasser","mobile":"+966510000131","imei":"864920451007","plate":"JED 2013","model":"Item 2","color":"Item 2","contact":"Item 2","status":"ENABLED","description":"—"},{"id":10426,"driver":"Khalid Otaibi","mobile":"+966510000262","imei":"864920451014","plate":"DMM 2026","model":"Item 3","color":"Item 3","contact":"Item 3","status":"ENABLED","description":"—"},{"id":10425,"driver":"Sara Al-Dosari","mobile":"+966510000393","imei":"864920451021","plate":"RUH 2039","model":"Item 4","color":"Item 4","contact":"Item 4","status":"ENABLED","description":"—"},{"id":10424,"driver":"Yousef Qahtani","mobile":"+966510000524","imei":"864920451028","plate":"JED 2052","model":"Item 5","color":"Item 5","contact":"Item 5","status":"DISABLED","description":"—"},{"id":10423,"driver":"Noura Faisal","mobile":"+966510000655","imei":"864920451035","plate":"DMM 2065","model":"Item 6","color":"Item 6","contact":"Item 6","status":"ENABLED","description":"—"},{"id":10422,"driver":"Abdullah Zahrani","mobile":"+966510000786","imei":"864920451042","plate":"RUH 2078","model":"Item 7","color":"Item 7","contact":"Item 7","status":"ENABLED","description":"—"},{"id":10421,"driver":"Layla Ghamdi","mobile":"+966510000917","imei":"864920451049","plate":"JED 2091","model":"Item 8","color":"Item 8","contact":"Item 8","status":"ENABLED","description":"—"},{"id":10420,"driver":"Mohammed Al-Harbi","mobile":"+966510001048","imei":"864920451056","plate":"DMM 2104","model":"Item 9","color":"Item 9","contact":"Item 9","status":"DISABLED","description":"—"},{"id":10419,"driver":"Fatimah Nasser","mobile":"+966510001179","imei":"864920451063","plate":"RUH 2117","model":"Item 10","color":"Item 10","contact":"Item 10","status":"ENABLED","description":"—"},{"id":10418,"driver":"Khalid Otaibi","mobile":"+966510001310","imei":"864920451070","plate":"JED 2130","model":"Item 11","color":"Item 11","contact":"Item 11","status":"ENABLED","description":"—"},{"id":10417,"driver":"Sara Al-Dosari","mobile":"+966510001441","imei":"864920451077","plate":"DMM 2143","model":"Item 12","color":"Item 12","contact":"Item 12","status":"ENABLED","description":"—"},{"id":10416,"driver":"Yousef Qahtani","mobile":"+966510001572","imei":"864920451084","plate":"RUH 2156","model":"Item 13","color":"Item 13","contact":"Item 13","status":"DISABLED","description":"—"},{"id":10415,"driver":"Noura Faisal","mobile":"+966510001703","imei":"864920451091","plate":"JED 2169","model":"Item 14","color":"Item 14","contact":"Item 14","status":"ENABLED","description":"—"},{"id":10414,"driver":"Abdullah Zahrani","mobile":"+966510001834","imei":"864920451098","plate":"DMM 2182","model":"Item 15","color":"Item 15","contact":"Item 15","status":"ENABLED","description":"—"},{"id":10413,"driver":"Layla Ghamdi","mobile":"+966510001965","imei":"864920451105","plate":"RUH 2195","model":"Item 16","color":"Item 16","contact":"Item 16","status":"ENABLED","description":"—"},{"id":10412,"driver":"Mohammed Al-Harbi","mobile":"+966510002096","imei":"864920451112","plate":"JED 2208","model":"Item 17","color":"Item 17","contact":"Item 17","status":"DISABLED","description":"—"},{"id":10411,"driver":"Fatimah Nasser","mobile":"+966510002227","imei":"864920451119","plate":"DMM 2221","model":"Item 18","color":"Item 18","contact":"Item 18","status":"ENABLED","description":"—"},{"id":10410,"driver":"Khalid Otaibi","mobile":"+966510002358","imei":"864920451126","plate":"RUH 2234","model":"Item 19","color":"Item 19","contact":"Item 19","status":"ENABLED","description":"—"},{"id":10409,"driver":"Sara Al-Dosari","mobile":"+966510002489","imei":"864920451133","plate":"JED 2247","model":"Item 20","color":"Item 20","contact":"Item 20","status":"ENABLED","description":"—"},{"id":10408,"driver":"Yousef Qahtani","mobile":"+966510002620","imei":"864920451140","plate":"DMM 2260","model":"Item 21","color":"Item 21","contact":"Item 21","status":"DISABLED","description":"—"},{"id":10407,"driver":"Noura Faisal","mobile":"+966510002751","imei":"864920451147","plate":"RUH 2273","model":"Item 22","color":"Item 22","contact":"Item 22","status":"ENABLED","description":"—"}]);

function doSearch(){ loading.value=true; setTimeout(()=>{loading.value=false; push({type:'success',title:'Filters applied',message:rows.value.length+' records'});},600); }
function doReset(){ filters.value={"from":"","to":"","imei":"","plate":"","status":""}; }
function bulkDelete(ids){ rows.value=rows.value.filter(r=>!ids.includes(r.id)); push({type:'success',title:'Bulk delete',message:ids.length+' removed'}); }
function onExport(k){ push({type:'info',title:'Export',message:'Generating '+k.toUpperCase()+'…'}); }
function del(row){ rows.value=rows.value.filter(r=>r!==row); push({type:'success',title:'Deleted'}); }
</script>

<template>
  <div>
    <Breadcrumb title="Cars" :trail="[{ label: 'Cars' }]">
      <template #actions>
        <BaseButton v-if="can('car_create')" variant="primary" icon="ri-add-line">Add Car</BaseButton>
      </template>
    </Breadcrumb>

    <FilterBar :loading="loading" @search="doSearch" @reset="doReset">
      <FormInput v-model="filters.from" label="Date From" type="date"  />
      <FormInput v-model="filters.to" label="Date To" type="date"  />
      <FormInput v-model="filters.imei" label="IMEI" placeholder="IMEI" icon="ri-cpu-line" />
      <FormInput v-model="filters.plate" label="Plate" placeholder="Plate"  />
      <FormSelect v-model="filters.status" label="Status" :options="opt(['Enable','Disable'])" placeholder="Any status" />
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
