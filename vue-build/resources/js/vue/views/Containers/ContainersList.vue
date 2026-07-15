<script setup>
/** /admin/containers — Container list. Auto-faithful to handoff columns. Mock data; wire @query for server-side. */
import { ref } from 'vue';
import Breadcrumb from '../../components/Breadcrumb.vue';
import DataTable from '../../components/DataTable.vue';
import BaseButton from '../../components/BaseButton.vue';
import { useToast } from '../../composables/useToast';
import { usePermissions } from '../../composables/usePermissions';
import StatusBadge from '../../components/StatusBadge.vue';

const { push } = useToast();
const { can, canDelete } = usePermissions();
const loading = ref(false);
const opt = (a) => a.map(v => ({ value: v, label: v }));
const filters = ref({});
const columns = [{"key":"seq","label":"#","sticky":"start","width":"56px"},{"key":"id","label":"ID","sortable":true,"sticky":"start"},{"key":"plate","label":"Car","mono":true},{"key":"imei","label":"Sensor","mono":true},{"key":"type","label":"Type"},{"key":"description","label":"Description"},{"key":"status","label":"Status"}];
const rows = ref([{"seq":1,"id":10428,"plate":"RUH 2000","imei":"864920451000","type":"Pickup","description":"—","status":"DISABLED"},{"seq":2,"id":10427,"plate":"JED 2013","imei":"864920451007","type":"Delivery","description":"—","status":"ENABLED"},{"seq":3,"id":10426,"plate":"DMM 2026","imei":"864920451014","type":"Round-trip","description":"—","status":"ENABLED"},{"seq":4,"id":10425,"plate":"RUH 2039","imei":"864920451021","type":"Pickup","description":"—","status":"ENABLED"},{"seq":5,"id":10424,"plate":"JED 2052","imei":"864920451028","type":"Delivery","description":"—","status":"DISABLED"},{"seq":6,"id":10423,"plate":"DMM 2065","imei":"864920451035","type":"Round-trip","description":"—","status":"ENABLED"},{"seq":7,"id":10422,"plate":"RUH 2078","imei":"864920451042","type":"Pickup","description":"—","status":"ENABLED"},{"seq":8,"id":10421,"plate":"JED 2091","imei":"864920451049","type":"Delivery","description":"—","status":"ENABLED"},{"seq":9,"id":10420,"plate":"DMM 2104","imei":"864920451056","type":"Round-trip","description":"—","status":"DISABLED"},{"seq":10,"id":10419,"plate":"RUH 2117","imei":"864920451063","type":"Pickup","description":"—","status":"ENABLED"},{"seq":11,"id":10418,"plate":"JED 2130","imei":"864920451070","type":"Delivery","description":"—","status":"ENABLED"},{"seq":12,"id":10417,"plate":"DMM 2143","imei":"864920451077","type":"Round-trip","description":"—","status":"ENABLED"},{"seq":13,"id":10416,"plate":"RUH 2156","imei":"864920451084","type":"Pickup","description":"—","status":"DISABLED"},{"seq":14,"id":10415,"plate":"JED 2169","imei":"864920451091","type":"Delivery","description":"—","status":"ENABLED"},{"seq":15,"id":10414,"plate":"DMM 2182","imei":"864920451098","type":"Round-trip","description":"—","status":"ENABLED"},{"seq":16,"id":10413,"plate":"RUH 2195","imei":"864920451105","type":"Pickup","description":"—","status":"ENABLED"},{"seq":17,"id":10412,"plate":"JED 2208","imei":"864920451112","type":"Delivery","description":"—","status":"DISABLED"},{"seq":18,"id":10411,"plate":"DMM 2221","imei":"864920451119","type":"Round-trip","description":"—","status":"ENABLED"},{"seq":19,"id":10410,"plate":"RUH 2234","imei":"864920451126","type":"Pickup","description":"—","status":"ENABLED"},{"seq":20,"id":10409,"plate":"JED 2247","imei":"864920451133","type":"Delivery","description":"—","status":"ENABLED"},{"seq":21,"id":10408,"plate":"DMM 2260","imei":"864920451140","type":"Round-trip","description":"—","status":"DISABLED"},{"seq":22,"id":10407,"plate":"RUH 2273","imei":"864920451147","type":"Pickup","description":"—","status":"ENABLED"}]);

function doSearch(){ loading.value=true; setTimeout(()=>{loading.value=false; push({type:'success',title:'Filters applied',message:rows.value.length+' records'});},600); }
function doReset(){ filters.value={}; }
function bulkDelete(ids){ rows.value=rows.value.filter(r=>!ids.includes(r.id)); push({type:'success',title:'Bulk delete',message:ids.length+' removed'}); }
function onExport(k){ push({type:'info',title:'Export',message:'Generating '+k.toUpperCase()+'…'}); }
function del(row){ rows.value=rows.value.filter(r=>r!==row); push({type:'success',title:'Deleted'}); }
</script>

<template>
  <div>
    <Breadcrumb title="Containers" :trail="[{ label: 'Containers' }]">
      <template #actions>
        <BaseButton v-if="can('container_create')" variant="primary" icon="ri-add-line">Add Container</BaseButton>
      </template>
    </Breadcrumb>

    <DataTable :columns="columns" :rows="rows" row-key="id" :loading="loading"
      :bulk-actions="canDelete() ? [{ label:'Delete', icon:'ri-delete-bin-line', tone:'danger', event:'bulk-delete' }] : []"
      @bulk-delete="bulkDelete" @export="onExport">
      <template #cell-id="{ value }"><span class="font-semibold text-primary-700 dark:text-primary-300">#{{ value }}</span></template>
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
