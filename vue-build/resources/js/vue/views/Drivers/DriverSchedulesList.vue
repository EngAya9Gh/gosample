<script setup>
/** /admin/driver-schedules — Driver Schedule list. Auto-faithful to handoff columns. Mock data; wire @query for server-side. */
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
const columns = [{"key":"id","label":"ID","sortable":true,"sticky":"start"},{"key":"from","label":"From Location"},{"key":"to","label":"To Location"},{"key":"driver","label":"Driver"},{"key":"note","label":"Note"},{"key":"plate","label":"Plate Number","mono":true}];
const rows = ref([{"id":10428,"from":"Central Hub","to":"Central Hub","driver":"Mohammed Al-Harbi","note":"—","plate":"RUH 2000"},{"id":10427,"from":"Lab East","to":"Lab East","driver":"Fatimah Nasser","note":"—","plate":"JED 2013"},{"id":10426,"from":"Lab West","to":"Lab West","driver":"Khalid Otaibi","note":"—","plate":"DMM 2026"},{"id":10425,"from":"North Depot","to":"North Depot","driver":"Sara Al-Dosari","note":"—","plate":"RUH 2039"},{"id":10424,"from":"King Faisal Lab","to":"King Faisal Lab","driver":"Yousef Qahtani","note":"—","plate":"JED 2052"},{"id":10423,"from":"Al-Noor Clinic","to":"Al-Noor Clinic","driver":"Noura Faisal","note":"—","plate":"DMM 2065"},{"id":10422,"from":"Dallah Hosp.","to":"Dallah Hosp.","driver":"Abdullah Zahrani","note":"—","plate":"RUH 2078"},{"id":10421,"from":"Olaya Branch","to":"Olaya Branch","driver":"Layla Ghamdi","note":"—","plate":"JED 2091"},{"id":10420,"from":"Central Hub","to":"Central Hub","driver":"Mohammed Al-Harbi","note":"—","plate":"DMM 2104"},{"id":10419,"from":"Lab East","to":"Lab East","driver":"Fatimah Nasser","note":"—","plate":"RUH 2117"},{"id":10418,"from":"Lab West","to":"Lab West","driver":"Khalid Otaibi","note":"—","plate":"JED 2130"},{"id":10417,"from":"North Depot","to":"North Depot","driver":"Sara Al-Dosari","note":"—","plate":"DMM 2143"},{"id":10416,"from":"King Faisal Lab","to":"King Faisal Lab","driver":"Yousef Qahtani","note":"—","plate":"RUH 2156"},{"id":10415,"from":"Al-Noor Clinic","to":"Al-Noor Clinic","driver":"Noura Faisal","note":"—","plate":"JED 2169"},{"id":10414,"from":"Dallah Hosp.","to":"Dallah Hosp.","driver":"Abdullah Zahrani","note":"—","plate":"DMM 2182"},{"id":10413,"from":"Olaya Branch","to":"Olaya Branch","driver":"Layla Ghamdi","note":"—","plate":"RUH 2195"},{"id":10412,"from":"Central Hub","to":"Central Hub","driver":"Mohammed Al-Harbi","note":"—","plate":"JED 2208"},{"id":10411,"from":"Lab East","to":"Lab East","driver":"Fatimah Nasser","note":"—","plate":"DMM 2221"},{"id":10410,"from":"Lab West","to":"Lab West","driver":"Khalid Otaibi","note":"—","plate":"RUH 2234"},{"id":10409,"from":"North Depot","to":"North Depot","driver":"Sara Al-Dosari","note":"—","plate":"JED 2247"},{"id":10408,"from":"King Faisal Lab","to":"King Faisal Lab","driver":"Yousef Qahtani","note":"—","plate":"DMM 2260"},{"id":10407,"from":"Al-Noor Clinic","to":"Al-Noor Clinic","driver":"Noura Faisal","note":"—","plate":"RUH 2273"}]);

function doSearch(){ loading.value=true; setTimeout(()=>{loading.value=false; push({type:'success',title:'Filters applied',message:rows.value.length+' records'});},600); }
function doReset(){ filters.value={}; }
function bulkDelete(ids){ rows.value=rows.value.filter(r=>!ids.includes(r.id)); push({type:'success',title:'Bulk delete',message:ids.length+' removed'}); }
function onExport(k){ push({type:'info',title:'Export',message:'Generating '+k.toUpperCase()+'…'}); }
function del(row){ rows.value=rows.value.filter(r=>r!==row); push({type:'success',title:'Deleted'}); }
</script>

<template>
  <div>
    <Breadcrumb title="Driver Schedules" :trail="[{ label: 'Driver Schedules' }]">
      <template #actions>
        <BaseButton v-if="can('driver_create')" variant="primary" icon="ri-add-line">Add Schedule</BaseButton>
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
