<script setup>
import { ref } from 'vue';
import Breadcrumb from '../../components/Breadcrumb.vue';
import BaseCard from '../../components/BaseCard.vue';
import BaseButton from '../../components/BaseButton.vue';
import FormInput from '../../components/FormInput.vue';
import FormSelect from '../../components/FormSelect.vue';
import { useToast } from '../../composables/useToast';
const { push }=useToast();
const opt=a=>a.map(v=>({value:v,label:v}));
const saving=ref(false);
const f=ref({name:'',password:'',username:'',mobile:'',email:'',lang:'English',status:'Enabled',emp:'Full Time',hours:8,shiftCount:1,zone:'',nid:''});
function submit(){ saving.value=true; setTimeout(()=>{saving.value=false; push({type:'success',title:'Saved',message:'Driver updated'});},700); }
</script>
<template>
  <div>
    <Breadcrumb title="Edit Driver" :trail="[{label:'Drivers',href:'#/admin/drivers'},{label:'Edit'}]" />
    <form @submit.prevent="submit" class="max-w-3xl space-y-5">
      <BaseCard title="Identity & login" icon="ri-user-line">
        <div class="grid grid-cols-1 sm:grid-cols-2 gap-x-5 gap-y-4">
          <FormInput v-model="f.name" label="Name" :required="true" />
          <FormInput v-model="f.username" label="Username" :required="true" />
          <FormInput v-model="f.password" label="Password" type="password" helper="Leave empty to keep current" />
          <FormInput v-model="f.mobile" label="Mobile" :required="true" />
          <FormInput v-model="f.email" label="Email" type="email" />
          <FormSelect v-model="f.lang" label="Language" :options="opt(['English','Arabic'])" :required="true" />
          <FormSelect v-model="f.status" label="Status" :options="opt(['Enabled','Disabled'])" :required="true" />
          <FormInput v-model="f.nid" label="National ID" :required="true" />
        </div>
      </BaseCard>

      <BaseCard title="Shift & Schedule — الدوام والورديات" icon="ri-time-line">
        <div class="grid grid-cols-1 sm:grid-cols-2 gap-x-5 gap-y-4 mb-5">
          <FormSelect v-model="f.emp" label="Employment Type" :options="opt(['Full Time','Part Time'])" :required="true" />
          <FormInput v-model="f.hours" label="Required Hours" type="number" unit="hrs/day" :required="true" />
          <FormSelect v-model.number="f.shiftCount" label="Shift Count" :options="[{value:1,label:'1'},{value:2,label:'2'},{value:3,label:'3'}]" :required="true" />
          <FormSelect v-model="f.zone" label="Zone" :options="opt(['North','South','East','West'])" :required="true" />
        </div>
        <div class="space-y-3">
          <div v-for="n in f.shiftCount" :key="n" class="grid grid-cols-2 gap-3 p-3 rounded-xl bg-surface-muted dark:bg-white/5">
            <div class="col-span-2 text-xs font-semibold text-primary-700 dark:text-primary-300">Shift {{ n }}</div>
            <input type="time" class="h-10 px-3 rounded-lg border border-slate-200 dark:border-white/10 bg-surface dark:bg-slate-900/40 text-sm focus:ring-2 focus:ring-primary-500/30 focus:outline-none" />
            <input type="time" class="h-10 px-3 rounded-lg border border-slate-200 dark:border-white/10 bg-surface dark:bg-slate-900/40 text-sm focus:ring-2 focus:ring-primary-500/30 focus:outline-none" />
          </div>
        </div>
        <template #footer><div class="flex justify-end gap-2"><BaseButton variant="light" type="button">Cancel</BaseButton><BaseButton variant="primary" type="submit" icon="ri-save-line" :loading="saving">Save</BaseButton></div></template>
      </BaseCard>
    </form>
  </div>
</template>
