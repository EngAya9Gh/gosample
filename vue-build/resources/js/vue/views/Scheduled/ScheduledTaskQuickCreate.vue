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
const f=ref({name:'',status:'',start:'',end:'',from:'',to:'',client:'',type:'',days:[],driver:''});
const visits=ref(['08:00']);
const saving=ref(false);
function submit(){ saving.value=true; setTimeout(()=>{saving.value=false;push({type:'success',title:'Quick schedule created'});},700); }
</script>
<template>
  <div>
    <Breadcrumb title="Quick Schedule Task" :trail="[{label:'Scheduled Tasks',href:'#/admin/scheduled-tasks'},{label:'Quick Create'}]" />
    <form @submit.prevent="submit" class="max-w-3xl">
      <BaseCard title="Quick schedule" icon="ri-calendar-todo-line">
        <div class="grid grid-cols-1 sm:grid-cols-2 gap-x-5 gap-y-4">
          <FormInput v-model="f.name" label="Name" :required="true" />
          <FormSelect v-model="f.status" label="Status" :options="opt(['Active','Paused'])" />
          <FormInput v-model="f.start" label="Start Date" type="date" />
          <FormInput v-model="f.end" label="End Date" type="date" />
          <FormSelect v-model="f.from" label="From Location" :options="opt(['Central Hub','Lab East'])" />
          <FormSelect v-model="f.to" label="To Location" :options="opt(['North Depot','Central Hub'])" />
          <FormSelect v-model="f.client" label="Client" :options="opt(['King Faisal Lab','Al-Noor Clinic'])" />
          <FormSelect v-model="f.driver" label="Driver" :options="opt(['Mohammed Al-Harbi','Fatimah Nasser'])" />
          <div class="sm:col-span-2">
            <label class="block text-[13px] font-medium text-slate-600 dark:text-slate-300 mb-1.5">Days</label>
            <div class="flex flex-wrap gap-2"><button v-for="d in DAYS" :key="d" type="button" @click="f.days.includes(d)?f.days.splice(f.days.indexOf(d),1):f.days.push(d)" class="px-3 h-9 rounded-lg text-sm font-medium transition" :class="f.days.includes(d)?'bg-primary-600 text-white':'bg-surface-muted dark:bg-white/5 text-slate-500'">{{ d }}</button></div>
          </div>
          <div class="sm:col-span-2">
            <label class="block text-[13px] font-medium text-slate-600 dark:text-slate-300 mb-1.5">Visit Hours</label>
            <div class="space-y-2">
              <div v-for="(v,i) in visits" :key="i" class="flex items-center gap-2">
                <input v-model="visits[i]" type="time" class="h-10 px-3 rounded-xl border border-slate-200 dark:border-white/10 bg-surface dark:bg-slate-900/40 text-sm flex-1 focus:ring-2 focus:ring-primary-500/30 focus:outline-none" />
                <button type="button" @click="visits.splice(i,1)" class="grid place-items-center w-10 h-10 rounded-xl text-danger hover:bg-danger/10"><i class="ri-delete-bin-line"></i></button>
              </div>
              <button type="button" @click="visits.push('08:00')" class="inline-flex items-center gap-1.5 text-sm font-medium text-primary-600 hover:text-primary-700"><i class="ri-add-line"></i>Add visit</button>
            </div>
          </div>
        </div>
        <template #footer><div class="flex justify-end gap-2"><BaseButton variant="light" type="button">Cancel</BaseButton><BaseButton variant="primary" type="submit" icon="ri-save-line" :loading="saving">Save</BaseButton></div></template>
      </BaseCard>
    </form>
  </div>
</template>
