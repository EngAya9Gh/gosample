<script setup>
/** /admin/scheduled-tasks — Scheduled Task list. Auto-faithful to handoff columns. Mock data; wire @query for server-side. */
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
const filters = ref({"from":"","to":"","driver":"","client":""});
const columns = [{"key":"seq","label":"#","sticky":"start","width":"56px"},{"key":"id","label":"ID","sortable":true,"sticky":"start"},{"key":"name","label":"Name"},{"key":"status","label":"Status"},{"key":"startDate","label":"Start Date"},{"key":"endDate","label":"End Date"},{"key":"to","label":"To Location"},{"key":"client","label":"Client"},{"key":"hour","label":"Selected Hour"},{"key":"type","label":"Task Type"},{"key":"days","label":"Days"},{"key":"addedBy","label":"Added By"},{"key":"driver","label":"Driver"}];
const rows = ref([{"seq":1,"id":10428,"name":"Sample Run","status":"NEW","startDate":"2026-06-27","endDate":"2026-06-27","to":"Central Hub","client":"King Faisal Lab","hour":"08:10","type":"Pickup","days":5,"addedBy":"System","driver":"Mohammed Al-Harbi"},{"seq":2,"id":10427,"name":"Morning Route","status":"COLLECTED","startDate":"2026-06-26","endDate":"2026-06-26","to":"Lab East","client":"Al-Noor Clinic","hour":"09:11","type":"Delivery","days":8,"addedBy":"Sara O.","driver":"Fatimah Nasser"},{"seq":3,"id":10426,"name":"Lab Pickup","status":"IN_CONTAINER","startDate":"2026-06-25","endDate":"2026-06-25","to":"Lab West","client":"Dallah Hospital","hour":"010:12","type":"Round-trip","days":11,"addedBy":"Auto-sched","driver":"Khalid Otaibi"},{"seq":4,"id":10425,"name":"Cold Transfer","status":"OUT_CONTAINER","startDate":"2026-06-24","endDate":"2026-06-24","to":"North Depot","client":"Saudi German","hour":"011:13","type":"Pickup","days":14,"addedBy":"System","driver":"Sara Al-Dosari"},{"seq":5,"id":10424,"name":"Sample Run","status":"CLOSED","startDate":"2026-06-23","endDate":"2026-06-23","to":"King Faisal Lab","client":"Mouwasat Lab","hour":"08:14","type":"Delivery","days":17,"addedBy":"Sara O.","driver":"Yousef Qahtani"},{"seq":6,"id":10423,"name":"Morning Route","status":"NO_SAMPLES","startDate":"2026-06-22","endDate":"2026-06-22","to":"Al-Noor Clinic","client":"Habib Medical","hour":"09:15","type":"Round-trip","days":20,"addedBy":"Auto-sched","driver":"Noura Faisal"},{"seq":7,"id":10422,"name":"Lab Pickup","status":"NEW","startDate":"2026-06-21","endDate":"2026-06-21","to":"Dallah Hosp.","client":"King Faisal Lab","hour":"010:16","type":"Pickup","days":23,"addedBy":"System","driver":"Abdullah Zahrani"},{"seq":8,"id":10421,"name":"Cold Transfer","status":"COLLECTED","startDate":"2026-06-20","endDate":"2026-06-20","to":"Olaya Branch","client":"Al-Noor Clinic","hour":"011:17","type":"Delivery","days":26,"addedBy":"Sara O.","driver":"Layla Ghamdi"},{"seq":9,"id":10420,"name":"Sample Run","status":"IN_CONTAINER","startDate":"2026-06-19","endDate":"2026-06-19","to":"Central Hub","client":"Dallah Hospital","hour":"08:18","type":"Round-trip","days":29,"addedBy":"Auto-sched","driver":"Mohammed Al-Harbi"},{"seq":10,"id":10419,"name":"Morning Route","status":"OUT_CONTAINER","startDate":"2026-06-18","endDate":"2026-06-18","to":"Lab East","client":"Saudi German","hour":"09:19","type":"Pickup","days":32,"addedBy":"System","driver":"Fatimah Nasser"},{"seq":11,"id":10418,"name":"Lab Pickup","status":"CLOSED","startDate":"2026-06-17","endDate":"2026-06-17","to":"Lab West","client":"Mouwasat Lab","hour":"010:20","type":"Delivery","days":35,"addedBy":"Sara O.","driver":"Khalid Otaibi"},{"seq":12,"id":10417,"name":"Cold Transfer","status":"NO_SAMPLES","startDate":"2026-06-16","endDate":"2026-06-16","to":"North Depot","client":"Habib Medical","hour":"011:21","type":"Round-trip","days":38,"addedBy":"Auto-sched","driver":"Sara Al-Dosari"},{"seq":13,"id":10416,"name":"Sample Run","status":"NEW","startDate":"2026-06-15","endDate":"2026-06-15","to":"King Faisal Lab","client":"King Faisal Lab","hour":"08:22","type":"Pickup","days":41,"addedBy":"System","driver":"Yousef Qahtani"},{"seq":14,"id":10415,"name":"Morning Route","status":"COLLECTED","startDate":"2026-06-14","endDate":"2026-06-14","to":"Al-Noor Clinic","client":"Al-Noor Clinic","hour":"09:23","type":"Delivery","days":44,"addedBy":"Sara O.","driver":"Noura Faisal"},{"seq":15,"id":10414,"name":"Lab Pickup","status":"IN_CONTAINER","startDate":"2026-06-13","endDate":"2026-06-13","to":"Dallah Hosp.","client":"Dallah Hospital","hour":"010:24","type":"Round-trip","days":47,"addedBy":"Auto-sched","driver":"Abdullah Zahrani"},{"seq":16,"id":10413,"name":"Cold Transfer","status":"OUT_CONTAINER","startDate":"2026-06-12","endDate":"2026-06-12","to":"Olaya Branch","client":"Saudi German","hour":"011:25","type":"Pickup","days":50,"addedBy":"System","driver":"Layla Ghamdi"},{"seq":17,"id":10412,"name":"Sample Run","status":"CLOSED","startDate":"2026-06-11","endDate":"2026-06-11","to":"Central Hub","client":"Mouwasat Lab","hour":"08:26","type":"Delivery","days":53,"addedBy":"Sara O.","driver":"Mohammed Al-Harbi"},{"seq":18,"id":10411,"name":"Morning Route","status":"NO_SAMPLES","startDate":"2026-06-10","endDate":"2026-06-10","to":"Lab East","client":"Habib Medical","hour":"09:27","type":"Round-trip","days":56,"addedBy":"Auto-sched","driver":"Fatimah Nasser"},{"seq":19,"id":10410,"name":"Lab Pickup","status":"NEW","startDate":"2026-06-09","endDate":"2026-06-09","to":"Lab West","client":"King Faisal Lab","hour":"010:28","type":"Pickup","days":59,"addedBy":"System","driver":"Khalid Otaibi"},{"seq":20,"id":10409,"name":"Cold Transfer","status":"COLLECTED","startDate":"2026-06-08","endDate":"2026-06-08","to":"North Depot","client":"Al-Noor Clinic","hour":"011:29","type":"Delivery","days":2,"addedBy":"Sara O.","driver":"Sara Al-Dosari"},{"seq":21,"id":10408,"name":"Sample Run","status":"IN_CONTAINER","startDate":"2026-06-07","endDate":"2026-06-07","to":"King Faisal Lab","client":"Dallah Hospital","hour":"08:30","type":"Round-trip","days":5,"addedBy":"Auto-sched","driver":"Yousef Qahtani"},{"seq":22,"id":10407,"name":"Morning Route","status":"OUT_CONTAINER","startDate":"2026-06-06","endDate":"2026-06-06","to":"Al-Noor Clinic","client":"Saudi German","hour":"09:31","type":"Pickup","days":8,"addedBy":"System","driver":"Noura Faisal"}]);

function doSearch(){ loading.value=true; setTimeout(()=>{loading.value=false; push({type:'success',title:'Filters applied',message:rows.value.length+' records'});},600); }
function doReset(){ filters.value={"from":"","to":"","driver":"","client":""}; }
function bulkDelete(ids){ rows.value=rows.value.filter(r=>!ids.includes(r.id)); push({type:'success',title:'Bulk delete',message:ids.length+' removed'}); }
function onExport(k){ push({type:'info',title:'Export',message:'Generating '+k.toUpperCase()+'…'}); }
function del(row){ rows.value=rows.value.filter(r=>r!==row); push({type:'success',title:'Deleted'}); }
</script>

<template>
  <div>
    <Breadcrumb title="Scheduled Tasks" :trail="[{ label: 'Scheduled Tasks' }]">
      <template #actions>
        <BaseButton v-if="can('task_create')" variant="primary" icon="ri-add-line">Add Scheduled Task</BaseButton>
      </template>
    </Breadcrumb>

    <FilterBar :loading="loading" @search="doSearch" @reset="doReset">
      <FormInput v-model="filters.from" label="Date From" type="date"  />
      <FormInput v-model="filters.to" label="Date To" type="date"  />
      <FormSelect v-model="filters.driver" label="Driver" :options="opt(['Mohammed Al-Harbi','Fatimah Nasser','Khalid Otaibi','Sara Al-Dosari'])" placeholder="Any driver" />
      <FormSelect v-model="filters.client" label="Client" :options="opt(['King Faisal Lab','Al-Noor Clinic','Dallah Hospital','Saudi German','Mouwasat Lab','Habib Medical'])" placeholder="Any client" />
    </FilterBar>
    <DataTable :columns="columns" :rows="rows" row-key="id" :loading="loading"
      :bulk-actions="canDelete() ? [{ label:'Delete', icon:'ri-delete-bin-line', tone:'danger', event:'bulk-delete' }] : []"
      @bulk-delete="bulkDelete" @export="onExport">
      <template #cell-id="{ value }"><span class="font-semibold text-primary-700 dark:text-primary-300">#{{ value }}</span></template>
      <template #cell-status="{ value }"><StatusBadge :status="value" /></template>
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
