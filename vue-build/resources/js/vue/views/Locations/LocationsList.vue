<script setup>
import { ref } from 'vue';
import Breadcrumb from '../../components/Breadcrumb.vue';
import FilterBar from '../../components/FilterBar.vue';
import DataTable from '../../components/DataTable.vue';
import FormInput from '../../components/FormInput.vue';
import FormSelect from '../../components/FormSelect.vue';
import BaseButton from '../../components/BaseButton.vue';
import StatusBadge from '../../components/StatusBadge.vue';
import { useToast } from '../../composables/useToast';
import { usePermissions } from '../../composables/usePermissions';
const { push }=useToast(); const { canDelete }=usePermissions();
const opt=a=>a.map(v=>({value:v,label:v}));
const f=ref({from:'',to:'',status:'',city:''});
const columns=[{key:'id',label:'ID',sortable:true,sticky:'start'},{key:'name',label:'Name'},{key:'arabicName',label:'Arabic Name'},{key:'lat',label:'Lat',mono:true},{key:'lng',label:'Lng',mono:true},{key:'mobile',label:'Mobile',mono:true},{key:'city',label:'City'},{key:'neighborhood',label:'Neighborhood'},{key:'status',label:'Status'}];
const rows=ref(Array.from({length:18},(_,i)=>({id:i+1,name:['Central Hub','Lab East','Lab West','North Depot'][i%4],arabicName:'المركز',lat:(24.71+i*0.003).toFixed(5),lng:(46.67+i*0.004).toFixed(5),mobile:'+96650'+(1000000+i),city:'Riyadh — الرياض',neighborhood:['Al-Olaya','Al-Malqa','Al-Nakheel'][i%3],status:i%5?'ENABLED':'DISABLED'})));
function copy(v){ navigator.clipboard && navigator.clipboard.writeText(v); push({type:'info',title:'Copied',message:v}); }
</script>
<template>
  <div>
    <Breadcrumb title="Locations" :trail="[{label:'Locations'}]"><template #actions><BaseButton variant="primary" icon="ri-add-line">Add Location</BaseButton></template></Breadcrumb>
    <FilterBar @search="()=>{}" @reset="()=>f={from:'',to:'',status:'',city:''}">
      <FormInput v-model="f.from" label="Date From" type="date" />
      <FormInput v-model="f.to" label="Date To" type="date" />
      <FormSelect v-model="f.status" label="Status" :options="opt(['Active','Not Active'])" placeholder="Any" />
      <FormSelect v-model="f.city" label="City" :options="opt(['Riyadh — الرياض','Jeddah — جدة','Dammam — الدمام'])" placeholder="Any city" />
    </FilterBar>
    <DataTable :columns="columns" :rows="rows" row-key="id" :bulk-actions="canDelete()?[{label:'Delete',icon:'ri-delete-bin-line',tone:'danger',event:'bulk-delete'}]:[]">
      <template #cell-id="{ value }"><span class="font-semibold text-primary-700">#{{ value }}</span></template>
      <template #cell-lat="{ value }"><button @click="copy(value)" class="inline-flex items-center gap-1 hover:text-primary-600" style="direction:ltr">{{ value }}<i class="ri-file-copy-line text-xs text-slate-400"></i></button></template>
      <template #cell-status="{ value }"><StatusBadge :status="value" /></template>
      <template #row-actions><div class="inline-flex gap-1"><button class="grid place-items-center w-8 h-8 rounded-lg text-info hover:bg-info/10"><i class="ri-eye-line"></i></button><button class="grid place-items-center w-8 h-8 rounded-lg text-primary-600 hover:bg-primary-50"><i class="ri-pencil-line"></i></button><button class="grid place-items-center w-8 h-8 rounded-lg text-danger hover:bg-danger/10"><i class="ri-delete-bin-line"></i></button></div></template>
    </DataTable>
  </div>
</template>
