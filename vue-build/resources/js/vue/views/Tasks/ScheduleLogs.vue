<script setup>
/** /admin/schedules/logs — Log list. Auto-faithful to handoff columns. Mock data; wire @query for server-side. */
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
const columns = [{"key":"date","label":"Date","sortable":true},{"key":"level","label":"Level"},{"key":"message","label":"Message"}];
const rows = ref([{"date":"2026-06-27","level":"NEW","message":"—"},{"date":"2026-06-26","level":"COLLECTED","message":"—"},{"date":"2026-06-25","level":"IN_CONTAINER","message":"—"},{"date":"2026-06-24","level":"OUT_CONTAINER","message":"—"},{"date":"2026-06-23","level":"CLOSED","message":"—"},{"date":"2026-06-22","level":"NO_SAMPLES","message":"—"},{"date":"2026-06-21","level":"NEW","message":"—"},{"date":"2026-06-20","level":"COLLECTED","message":"—"},{"date":"2026-06-19","level":"IN_CONTAINER","message":"—"},{"date":"2026-06-18","level":"OUT_CONTAINER","message":"—"},{"date":"2026-06-17","level":"CLOSED","message":"—"},{"date":"2026-06-16","level":"NO_SAMPLES","message":"—"},{"date":"2026-06-15","level":"NEW","message":"—"},{"date":"2026-06-14","level":"COLLECTED","message":"—"},{"date":"2026-06-13","level":"IN_CONTAINER","message":"—"},{"date":"2026-06-12","level":"OUT_CONTAINER","message":"—"},{"date":"2026-06-11","level":"CLOSED","message":"—"},{"date":"2026-06-10","level":"NO_SAMPLES","message":"—"},{"date":"2026-06-09","level":"NEW","message":"—"},{"date":"2026-06-08","level":"COLLECTED","message":"—"},{"date":"2026-06-07","level":"IN_CONTAINER","message":"—"},{"date":"2026-06-06","level":"OUT_CONTAINER","message":"—"}]);

function doSearch(){ loading.value=true; setTimeout(()=>{loading.value=false; push({type:'success',title:'Filters applied',message:rows.value.length+' records'});},600); }
function doReset(){ filters.value={}; }
function bulkDelete(ids){ rows.value=rows.value.filter(r=>!ids.includes(r.date)); push({type:'success',title:'Bulk delete',message:ids.length+' removed'}); }
function onExport(k){ push({type:'info',title:'Export',message:'Generating '+k.toUpperCase()+'…'}); }
function del(row){ rows.value=rows.value.filter(r=>r!==row); push({type:'success',title:'Deleted'}); }
</script>

<template>
  <div>
    <Breadcrumb title="Schedule Logs" :trail="[{ label: 'Schedule Logs' }]">
    </Breadcrumb>

    <DataTable :columns="columns" :rows="rows" row-key="date" :loading="loading"
      :bulk-actions="canDelete() ? [{ label:'Delete', icon:'ri-delete-bin-line', tone:'danger', event:'bulk-delete' }] : []"
      @bulk-delete="bulkDelete" @export="onExport">
      <template #cell-level="{ value }"><StatusBadge :status="value" /></template>
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
