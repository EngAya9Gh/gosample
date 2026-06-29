<script setup>
/** /admin/drivers — Driver list. Auto-faithful to handoff columns. Mock data; wire @query for server-side. */
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
const filters = ref({"from":"","to":"","mobile":"","status":""});
const columns = [{"key":"id","label":"ID","sortable":true,"sticky":"start"},{"key":"name","label":"Name"},{"key":"status","label":"Status"},{"key":"username","label":"Username","mono":true},{"key":"mobile","label":"Mobile","mono":true},{"key":"email","label":"Email"}];
const rows = ref([{"id":10428,"name":"Sample Run","status":"DISABLED","username":"Sample Run","mobile":"+966510000000","email":"user1@mtc.sa"},{"id":10427,"name":"Morning Route","status":"ENABLED","username":"Morning Route","mobile":"+966510000131","email":"user2@mtc.sa"},{"id":10426,"name":"Lab Pickup","status":"ENABLED","username":"Lab Pickup","mobile":"+966510000262","email":"user3@mtc.sa"},{"id":10425,"name":"Cold Transfer","status":"ENABLED","username":"Cold Transfer","mobile":"+966510000393","email":"user4@mtc.sa"},{"id":10424,"name":"Sample Run","status":"DISABLED","username":"Sample Run","mobile":"+966510000524","email":"user5@mtc.sa"},{"id":10423,"name":"Morning Route","status":"ENABLED","username":"Morning Route","mobile":"+966510000655","email":"user6@mtc.sa"},{"id":10422,"name":"Lab Pickup","status":"ENABLED","username":"Lab Pickup","mobile":"+966510000786","email":"user7@mtc.sa"},{"id":10421,"name":"Cold Transfer","status":"ENABLED","username":"Cold Transfer","mobile":"+966510000917","email":"user8@mtc.sa"},{"id":10420,"name":"Sample Run","status":"DISABLED","username":"Sample Run","mobile":"+966510001048","email":"user9@mtc.sa"},{"id":10419,"name":"Morning Route","status":"ENABLED","username":"Morning Route","mobile":"+966510001179","email":"user10@mtc.sa"},{"id":10418,"name":"Lab Pickup","status":"ENABLED","username":"Lab Pickup","mobile":"+966510001310","email":"user11@mtc.sa"},{"id":10417,"name":"Cold Transfer","status":"ENABLED","username":"Cold Transfer","mobile":"+966510001441","email":"user12@mtc.sa"},{"id":10416,"name":"Sample Run","status":"DISABLED","username":"Sample Run","mobile":"+966510001572","email":"user13@mtc.sa"},{"id":10415,"name":"Morning Route","status":"ENABLED","username":"Morning Route","mobile":"+966510001703","email":"user14@mtc.sa"},{"id":10414,"name":"Lab Pickup","status":"ENABLED","username":"Lab Pickup","mobile":"+966510001834","email":"user15@mtc.sa"},{"id":10413,"name":"Cold Transfer","status":"ENABLED","username":"Cold Transfer","mobile":"+966510001965","email":"user16@mtc.sa"},{"id":10412,"name":"Sample Run","status":"DISABLED","username":"Sample Run","mobile":"+966510002096","email":"user17@mtc.sa"},{"id":10411,"name":"Morning Route","status":"ENABLED","username":"Morning Route","mobile":"+966510002227","email":"user18@mtc.sa"},{"id":10410,"name":"Lab Pickup","status":"ENABLED","username":"Lab Pickup","mobile":"+966510002358","email":"user19@mtc.sa"},{"id":10409,"name":"Cold Transfer","status":"ENABLED","username":"Cold Transfer","mobile":"+966510002489","email":"user20@mtc.sa"},{"id":10408,"name":"Sample Run","status":"DISABLED","username":"Sample Run","mobile":"+966510002620","email":"user21@mtc.sa"},{"id":10407,"name":"Morning Route","status":"ENABLED","username":"Morning Route","mobile":"+966510002751","email":"user22@mtc.sa"}]);

function doSearch(){ loading.value=true; setTimeout(()=>{loading.value=false; push({type:'success',title:'Filters applied',message:rows.value.length+' records'});},600); }
function doReset(){ filters.value={"from":"","to":"","mobile":"","status":""}; }
function bulkDelete(ids){ rows.value=rows.value.filter(r=>!ids.includes(r.id)); push({type:'success',title:'Bulk delete',message:ids.length+' removed'}); }
function onExport(k){ push({type:'info',title:'Export',message:'Generating '+k.toUpperCase()+'…'}); }
function del(row){ rows.value=rows.value.filter(r=>r!==row); push({type:'success',title:'Deleted'}); }
</script>

<template>
  <div>
    <Breadcrumb title="Drivers" :trail="[{ label: 'Drivers' }]">
      <template #actions>
        <BaseButton v-if="can('driver_create')" variant="primary" icon="ri-add-line">Add Driver</BaseButton>
      </template>
    </Breadcrumb>

    <FilterBar :loading="loading" @search="doSearch" @reset="doReset">
      <FormInput v-model="filters.from" label="Date From" type="date"  />
      <FormInput v-model="filters.to" label="Date To" type="date"  />
      <FormInput v-model="filters.mobile" label="Mobile" placeholder="Mobile" icon="ri-phone-line" />
      <FormSelect v-model="filters.status" label="Status" :options="opt(['Enabled','Disabled'])" placeholder="Any status" />
    </FilterBar>
    <DataTable :columns="columns" :rows="rows" row-key="id" :loading="loading"
      :bulk-actions="canDelete() ? [{ label:'Delete', icon:'ri-delete-bin-line', tone:'danger', event:'bulk-delete' }] : []"
      @bulk-delete="bulkDelete" @export="onExport">
      <template #cell-id="{ value }"><span class="font-semibold text-primary-700 dark:text-primary-300">#{{ value }}</span></template>
      <template #cell-name="{ value }"><span class="inline-flex items-center gap-2"><BaseAvatar :name="value" :size="26" /><span class="truncate">{{ value }}</span></span></template>
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
