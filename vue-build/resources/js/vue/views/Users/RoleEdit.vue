<script setup>
import { ref, computed } from 'vue';
import Breadcrumb from '../../components/Breadcrumb.vue';
import BaseCard from '../../components/BaseCard.vue';
import BaseButton from '../../components/BaseButton.vue';
import FormInput from '../../components/FormInput.vue';
import FormToggle from '../../components/FormToggle.vue';
import { useToast } from '../../composables/useToast';
const { push }=useToast();
const name=ref('Dispatcher');
const q=ref('');
const ALL=['task_access','task_create','task_edit','task_show','task_delete','driver_access','driver_create','driver_edit','car_access','car_create','client_access','client_create','location_access','sample_access','shipment_access','container_access','zone_access','report_access','user_access','role_access','permission_access','audit_access','barcode_access','attendance_access'];
const sel=ref(new Set(['task_access','task_create','task_show','driver_access','car_access']));
const filtered=computed(()=>ALL.filter(p=>p.includes(q.value.toLowerCase())));
function tog(p){ sel.value.has(p)?sel.value.delete(p):sel.value.add(p); sel.value=new Set(sel.value); }
function all(){ sel.value=new Set(ALL); } function none(){ sel.value=new Set(); }
function submit(){ push({type:'success',title:'Saved',message:'Role updated with '+sel.value.size+' permissions'}); }
</script>
<template>
  <div>
    <Breadcrumb title="Edit Role" :trail="[{label:'Roles',href:'#/admin/roles'},{label:'Edit'}]" />
    <BaseCard title="Role details" icon="ri-vip-crown-line">
      <FormInput v-model="name" label="Name" :required="true" class="max-w-md mb-5" />
      <div class="flex flex-wrap items-center gap-3 mb-4">
        <label class="text-[13px] font-medium text-slate-600 dark:text-slate-300">Permissions</label>
        <span class="text-xs font-semibold text-primary-700 bg-primary-50 dark:bg-primary-500/15 dark:text-primary-300 px-2.5 h-6 grid place-items-center rounded-full">{{ sel.size }} / {{ ALL.length }} selected</span>
        <div class="flex gap-2 ms-auto">
          <button type="button" @click="all" class="text-xs font-medium text-primary-600 hover:underline">Select all</button><span class="text-slate-300">·</span>
          <button type="button" @click="none" class="text-xs font-medium text-slate-500 hover:underline">Deselect all</button>
        </div>
      </div>
      <div class="relative mb-4 max-w-xs"><i class="ri-search-line absolute top-1/2 -translate-y-1/2 inset-inline-start-3 text-slate-400" style="inset-inline-start:.75rem"></i><input v-model="q" placeholder="Filter permissions…" class="w-full h-10 ps-10 pe-3 rounded-xl border border-slate-200 dark:border-white/10 bg-surface dark:bg-slate-900/40 text-sm focus:ring-2 focus:ring-primary-500/30 focus:outline-none" /></div>
      <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-x-6 gap-y-3">
        <div v-for="p in filtered" :key="p" class="flex items-center justify-between gap-3 px-3 h-11 rounded-xl bg-surface-muted/60 dark:bg-white/5">
          <span class="text-sm font-mono text-ink dark:text-slate-200 truncate" style="direction:ltr">{{ p }}</span>
          <FormToggle :modelValue="sel.has(p)" @update:modelValue="tog(p)" />
        </div>
      </div>
      <template #footer><div class="flex justify-end gap-2"><BaseButton variant="light" type="button">Cancel</BaseButton><BaseButton variant="primary" icon="ri-save-line" @click="submit">Save</BaseButton></div></template>
    </BaseCard>
  </div>
</template>
