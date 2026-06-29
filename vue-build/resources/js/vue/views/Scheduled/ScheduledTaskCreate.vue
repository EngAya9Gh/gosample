<script setup>
import { ref } from 'vue';
import Breadcrumb from '../../components/Breadcrumb.vue';
import BaseCard from '../../components/BaseCard.vue';
import BaseButton from '../../components/BaseButton.vue';
import FormSelect from '../../components/FormSelect.vue';
import FormInput from '../../components/FormInput.vue';
import { useToast } from '../../composables/useToast';
const { push }=useToast();
const opt=a=>a.map(v=>({value:v,label:v}));
const DAYS=['Sun','Mon','Tue','Wed','Thu','Fri','Sat'];
const f=ref({driver:'',status:'',start:'',end:'',from:[],to:'',client:'',type:'',days:[]});
const saving=ref(false);
function submit(){ saving.value=true; setTimeout(()=>{saving.value=false;push({type:'success',title:'Scheduled task created'});},700); }
</script>
<template>
  <div>
    <Breadcrumb title="New Scheduled Task" :trail="[{label:'Scheduled Tasks',href:'#/admin/scheduled-tasks'},{label:'Create'}]" />
    <form @submit.prevent="submit" class="max-w-3xl">
      <BaseCard title="Schedule details" icon="ri-calendar-schedule-line">
        <div class="grid grid-cols-1 sm:grid-cols-2 gap-x-5 gap-y-4">
          <FormSelect v-model="f.driver" label="Driver" :options="opt(['Mohammed Al-Harbi','Fatimah Nasser'])" />
          <FormSelect v-model="f.status" label="Status" :options="opt(['Active','Paused'])" />
          <FormInput v-model="f.start" label="Start Date" type="date" />
          <FormInput v-model="f.end" label="End Date" type="date" />
          <div class="sm:col-span-2"><FormSelect v-model="f.from" label="From Locations" multiple :options="opt(['Central Hub','Lab East','Lab West'])" /></div>
          <FormSelect v-model="f.to" label="To Location" :options="opt(['North Depot','Central Hub'])" />
          <FormSelect v-model="f.client" label="Client" :options="opt(['King Faisal Lab','Al-Noor Clinic'])" />
          <FormSelect v-model="f.type" label="Task Type" :options="opt(['Pickup','Delivery','Round-trip'])" />
          <div class="sm:col-span-2">
            <label class="block text-[13px] font-medium text-slate-600 dark:text-slate-300 mb-1.5">Days</label>
            <div class="flex flex-wrap gap-2">
              <button v-for="d in DAYS" :key="d" type="button" @click="f.days.includes(d)?f.days.splice(f.days.indexOf(d),1):f.days.push(d)" class="px-3 h-9 rounded-lg text-sm font-medium transition" :class="f.days.includes(d)?'bg-primary-600 text-white':'bg-surface-muted dark:bg-white/5 text-slate-500'">{{ d }}</button>
            </div>
          </div>
        </div>
        <div v-if="f.from.length" class="mt-5 pt-5 border-t border-slate-100 dark:border-white/5">
          <h4 class="text-sm font-semibold text-ink dark:text-slate-100 mb-3">Per-location visit hours</h4>
          <div class="space-y-2">
            <div v-for="loc in f.from" :key="loc" class="flex items-center gap-3"><span class="text-sm text-slate-500 w-32 truncate">{{ loc }}</span><input type="time" class="h-10 px-3 rounded-xl border border-slate-200 dark:border-white/10 bg-surface dark:bg-slate-900/40 text-sm focus:ring-2 focus:ring-primary-500/30 focus:outline-none" /></div>
          </div>
        </div>
        <template #footer><div class="flex justify-end gap-2"><BaseButton variant="light" type="button">Cancel</BaseButton><BaseButton variant="primary" type="submit" icon="ri-save-line" :loading="saving">Save</BaseButton></div></template>
      </BaseCard>
    </form>
  </div>
</template>
