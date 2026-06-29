<script setup>
/** /admin/swaprequests — Swap Request list. Auto-faithful to handoff columns. Mock data; wire @query for server-side. */
import { ref } from 'vue';
import Breadcrumb from '../../components/Breadcrumb.vue';
import DataTable from '../../components/DataTable.vue';
import BaseButton from '../../components/BaseButton.vue';
import { useToast } from '../../composables/useToast';
import { usePermissions } from '../../composables/usePermissions';
import FilterBar from '../../components/FilterBar.vue';
import FormInput from '../../components/FormInput.vue';
import StatusBadge from '../../components/StatusBadge.vue';
import BaseAvatar from '../../components/BaseAvatar.vue';

const { push } = useToast();
const { can, canDelete } = usePermissions();
const loading = ref(false);
const opt = (a) => a.map(v => ({ value: v, label: v }));
const filters = ref({"from":"","to":"","task":""});
const columns = [{"key":"id","label":"ID","sortable":true,"sticky":"start"},{"key":"task","label":"Task","mono":true},{"key":"taskStatus","label":"Task Status"},{"key":"oldDriver","label":"Driver A (old)"},{"key":"driver","label":"Driver (new)"},{"key":"status","label":"Swap Status"}];
const rows = ref([{"id":10428,"task":"Item 1","taskStatus":"NEW","oldDriver":"Mohammed Al-Harbi","driver":"Mohammed Al-Harbi","status":"NEW"},{"id":10427,"task":"Item 2","taskStatus":"COLLECTED","oldDriver":"Fatimah Nasser","driver":"Fatimah Nasser","status":"COLLECTED"},{"id":10426,"task":"Item 3","taskStatus":"IN_CONTAINER","oldDriver":"Khalid Otaibi","driver":"Khalid Otaibi","status":"IN_CONTAINER"},{"id":10425,"task":"Item 4","taskStatus":"OUT_CONTAINER","oldDriver":"Sara Al-Dosari","driver":"Sara Al-Dosari","status":"OUT_CONTAINER"},{"id":10424,"task":"Item 5","taskStatus":"CLOSED","oldDriver":"Yousef Qahtani","driver":"Yousef Qahtani","status":"CLOSED"},{"id":10423,"task":"Item 6","taskStatus":"NO_SAMPLES","oldDriver":"Noura Faisal","driver":"Noura Faisal","status":"NO_SAMPLES"},{"id":10422,"task":"Item 7","taskStatus":"NEW","oldDriver":"Abdullah Zahrani","driver":"Abdullah Zahrani","status":"NEW"},{"id":10421,"task":"Item 8","taskStatus":"COLLECTED","oldDriver":"Layla Ghamdi","driver":"Layla Ghamdi","status":"COLLECTED"},{"id":10420,"task":"Item 9","taskStatus":"IN_CONTAINER","oldDriver":"Mohammed Al-Harbi","driver":"Mohammed Al-Harbi","status":"IN_CONTAINER"},{"id":10419,"task":"Item 10","taskStatus":"OUT_CONTAINER","oldDriver":"Fatimah Nasser","driver":"Fatimah Nasser","status":"OUT_CONTAINER"},{"id":10418,"task":"Item 11","taskStatus":"CLOSED","oldDriver":"Khalid Otaibi","driver":"Khalid Otaibi","status":"CLOSED"},{"id":10417,"task":"Item 12","taskStatus":"NO_SAMPLES","oldDriver":"Sara Al-Dosari","driver":"Sara Al-Dosari","status":"NO_SAMPLES"},{"id":10416,"task":"Item 13","taskStatus":"NEW","oldDriver":"Yousef Qahtani","driver":"Yousef Qahtani","status":"NEW"},{"id":10415,"task":"Item 14","taskStatus":"COLLECTED","oldDriver":"Noura Faisal","driver":"Noura Faisal","status":"COLLECTED"},{"id":10414,"task":"Item 15","taskStatus":"IN_CONTAINER","oldDriver":"Abdullah Zahrani","driver":"Abdullah Zahrani","status":"IN_CONTAINER"},{"id":10413,"task":"Item 16","taskStatus":"OUT_CONTAINER","oldDriver":"Layla Ghamdi","driver":"Layla Ghamdi","status":"OUT_CONTAINER"},{"id":10412,"task":"Item 17","taskStatus":"CLOSED","oldDriver":"Mohammed Al-Harbi","driver":"Mohammed Al-Harbi","status":"CLOSED"},{"id":10411,"task":"Item 18","taskStatus":"NO_SAMPLES","oldDriver":"Fatimah Nasser","driver":"Fatimah Nasser","status":"NO_SAMPLES"},{"id":10410,"task":"Item 19","taskStatus":"NEW","oldDriver":"Khalid Otaibi","driver":"Khalid Otaibi","status":"NEW"},{"id":10409,"task":"Item 20","taskStatus":"COLLECTED","oldDriver":"Sara Al-Dosari","driver":"Sara Al-Dosari","status":"COLLECTED"},{"id":10408,"task":"Item 21","taskStatus":"IN_CONTAINER","oldDriver":"Yousef Qahtani","driver":"Yousef Qahtani","status":"IN_CONTAINER"},{"id":10407,"task":"Item 22","taskStatus":"OUT_CONTAINER","oldDriver":"Noura Faisal","driver":"Noura Faisal","status":"OUT_CONTAINER"}]);

function doSearch(){ loading.value=true; setTimeout(()=>{loading.value=false; push({type:'success',title:'Filters applied',message:rows.value.length+' records'});},600); }
function doReset(){ filters.value={"from":"","to":"","task":""}; }
function bulkDelete(ids){ rows.value=rows.value.filter(r=>!ids.includes(r.id)); push({type:'success',title:'Bulk delete',message:ids.length+' removed'}); }
function onExport(k){ push({type:'info',title:'Export',message:'Generating '+k.toUpperCase()+'…'}); }
function del(row){ rows.value=rows.value.filter(r=>r!==row); push({type:'success',title:'Deleted'}); }
</script>

<template>
  <div>
    <Breadcrumb title="Swap Requests" :trail="[{ label: 'Swap Requests' }]">
      <template #actions>
        <BaseButton v-if="can('task_create')" variant="primary" icon="ri-add-line">Add Swap Request</BaseButton>
      </template>
    </Breadcrumb>

    <FilterBar :loading="loading" @search="doSearch" @reset="doReset">
      <FormInput v-model="filters.from" label="Date From" type="date"  />
      <FormInput v-model="filters.to" label="Date To" type="date"  />
      <FormInput v-model="filters.task" label="Task ID" placeholder="Task ID" icon="ri-hashtag" />
    </FilterBar>
    <DataTable :columns="columns" :rows="rows" row-key="id" :loading="loading"
      :bulk-actions="canDelete() ? [{ label:'Delete', icon:'ri-delete-bin-line', tone:'danger', event:'bulk-delete' }] : []"
      @bulk-delete="bulkDelete" @export="onExport">
      <template #cell-id="{ value }"><span class="font-semibold text-primary-700 dark:text-primary-300">#{{ value }}</span></template>
      <template #cell-taskStatus="{ value }"><StatusBadge :status="value" /></template>
      <template #cell-oldDriver="{ value }"><span class="inline-flex items-center gap-2"><BaseAvatar :name="value" :size="26" /><span class="truncate">{{ value }}</span></span></template>
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
