<script setup>
/** /admin/attendances — Attendance list. Auto-faithful to handoff columns. Mock data; wire @query for server-side. */
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
const columns = [{"key":"id","label":"ID","sortable":true,"sticky":"start"},{"key":"driver","label":"Driver"},{"key":"mobile","label":"Mobile","mono":true},{"key":"checkin","label":"Check-in"},{"key":"checkout","label":"Check-out"},{"key":"status","label":"Status"},{"key":"delay","label":"Delay (Min)","align":"center"},{"key":"overtime","label":"Overtime (Min)","align":"center"},{"key":"source","label":"Source"}];
const rows = ref([{"id":10428,"driver":"Mohammed Al-Harbi","mobile":"+966510000000","checkin":"08:10","checkout":"08:10","status":"NEW","delay":5,"overtime":"08:10","source":"Item 1"},{"id":10427,"driver":"Fatimah Nasser","mobile":"+966510000131","checkin":"09:11","checkout":"09:11","status":"COLLECTED","delay":8,"overtime":"09:11","source":"Item 2"},{"id":10426,"driver":"Khalid Otaibi","mobile":"+966510000262","checkin":"010:12","checkout":"010:12","status":"IN_CONTAINER","delay":11,"overtime":"010:12","source":"Item 3"},{"id":10425,"driver":"Sara Al-Dosari","mobile":"+966510000393","checkin":"011:13","checkout":"011:13","status":"OUT_CONTAINER","delay":14,"overtime":"011:13","source":"Item 4"},{"id":10424,"driver":"Yousef Qahtani","mobile":"+966510000524","checkin":"08:14","checkout":"08:14","status":"CLOSED","delay":17,"overtime":"08:14","source":"Item 5"},{"id":10423,"driver":"Noura Faisal","mobile":"+966510000655","checkin":"09:15","checkout":"09:15","status":"NO_SAMPLES","delay":20,"overtime":"09:15","source":"Item 6"},{"id":10422,"driver":"Abdullah Zahrani","mobile":"+966510000786","checkin":"010:16","checkout":"010:16","status":"NEW","delay":23,"overtime":"010:16","source":"Item 7"},{"id":10421,"driver":"Layla Ghamdi","mobile":"+966510000917","checkin":"011:17","checkout":"011:17","status":"COLLECTED","delay":26,"overtime":"011:17","source":"Item 8"},{"id":10420,"driver":"Mohammed Al-Harbi","mobile":"+966510001048","checkin":"08:18","checkout":"08:18","status":"IN_CONTAINER","delay":29,"overtime":"08:18","source":"Item 9"},{"id":10419,"driver":"Fatimah Nasser","mobile":"+966510001179","checkin":"09:19","checkout":"09:19","status":"OUT_CONTAINER","delay":32,"overtime":"09:19","source":"Item 10"},{"id":10418,"driver":"Khalid Otaibi","mobile":"+966510001310","checkin":"010:20","checkout":"010:20","status":"CLOSED","delay":35,"overtime":"010:20","source":"Item 11"},{"id":10417,"driver":"Sara Al-Dosari","mobile":"+966510001441","checkin":"011:21","checkout":"011:21","status":"NO_SAMPLES","delay":38,"overtime":"011:21","source":"Item 12"},{"id":10416,"driver":"Yousef Qahtani","mobile":"+966510001572","checkin":"08:22","checkout":"08:22","status":"NEW","delay":41,"overtime":"08:22","source":"Item 13"},{"id":10415,"driver":"Noura Faisal","mobile":"+966510001703","checkin":"09:23","checkout":"09:23","status":"COLLECTED","delay":44,"overtime":"09:23","source":"Item 14"},{"id":10414,"driver":"Abdullah Zahrani","mobile":"+966510001834","checkin":"010:24","checkout":"010:24","status":"IN_CONTAINER","delay":47,"overtime":"010:24","source":"Item 15"},{"id":10413,"driver":"Layla Ghamdi","mobile":"+966510001965","checkin":"011:25","checkout":"011:25","status":"OUT_CONTAINER","delay":50,"overtime":"011:25","source":"Item 16"},{"id":10412,"driver":"Mohammed Al-Harbi","mobile":"+966510002096","checkin":"08:26","checkout":"08:26","status":"CLOSED","delay":53,"overtime":"08:26","source":"Item 17"},{"id":10411,"driver":"Fatimah Nasser","mobile":"+966510002227","checkin":"09:27","checkout":"09:27","status":"NO_SAMPLES","delay":56,"overtime":"09:27","source":"Item 18"},{"id":10410,"driver":"Khalid Otaibi","mobile":"+966510002358","checkin":"010:28","checkout":"010:28","status":"NEW","delay":59,"overtime":"010:28","source":"Item 19"},{"id":10409,"driver":"Sara Al-Dosari","mobile":"+966510002489","checkin":"011:29","checkout":"011:29","status":"COLLECTED","delay":2,"overtime":"011:29","source":"Item 20"},{"id":10408,"driver":"Yousef Qahtani","mobile":"+966510002620","checkin":"08:30","checkout":"08:30","status":"IN_CONTAINER","delay":5,"overtime":"08:30","source":"Item 21"},{"id":10407,"driver":"Noura Faisal","mobile":"+966510002751","checkin":"09:31","checkout":"09:31","status":"OUT_CONTAINER","delay":8,"overtime":"09:31","source":"Item 22"}]);

function doSearch(){ loading.value=true; setTimeout(()=>{loading.value=false; push({type:'success',title:'Filters applied',message:rows.value.length+' records'});},600); }
function doReset(){ filters.value={}; }
function bulkDelete(ids){ rows.value=rows.value.filter(r=>!ids.includes(r.id)); push({type:'success',title:'Bulk delete',message:ids.length+' removed'}); }
function onExport(k){ push({type:'info',title:'Export',message:'Generating '+k.toUpperCase()+'…'}); }
function del(row){ rows.value=rows.value.filter(r=>r!==row); push({type:'success',title:'Deleted'}); }
</script>

<template>
  <div>
    <Breadcrumb title="Attendances" :trail="[{ label: 'Attendances' }]">
      <template #actions>
        <BaseButton v-if="can('attendance_create')" variant="primary" icon="ri-add-line">Add Attendance</BaseButton>
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
