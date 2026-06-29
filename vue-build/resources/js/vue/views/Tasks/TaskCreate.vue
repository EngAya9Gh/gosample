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
const saving=ref(false);
const form=ref({from:[],to:'',client:'',driver:'',type:'',pickup:'',dropoff:'',takasi:'No',taskType:'',visits:''});
function submit(){ saving.value=true; setTimeout(()=>{saving.value=false; push({type:'success',title:'Task created',message:'New task added'});},700); }
</script>
<template>
  <div>
    <Breadcrumb title="New Task" :trail="[{label:'Tasks',href:'#/admin/tasks'},{label:'Create'}]" />
    <form @submit.prevent="submit" class="max-w-3xl">
      <BaseCard title="Task details" icon="ri-add-box-line">
        <div class="grid grid-cols-1 sm:grid-cols-2 gap-x-5 gap-y-4">
          <div class="sm:col-span-2"><FormSelect v-model="form.from" label="From Locations" multiple :options="opt(['Central Hub','Lab East','Lab West','King Faisal Lab'])" :required="true" /></div>
          <FormSelect v-model="form.to" label="To Location" :options="opt(['North Depot','Lab West','Central Hub'])" :required="true" />
          <FormSelect v-model="form.client" label="Billing Client" :options="opt(['King Faisal Lab','Al-Noor Clinic','Dallah Hospital'])" :required="true" />
          <FormSelect v-model="form.driver" label="Driver" :options="opt(['Mohammed Al-Harbi','Fatimah Nasser','Khalid Otaibi'])" :required="true" />
          <FormSelect v-model="form.type" label="Type" :options="opt(['Standard','Urgent'])" />
          <FormInput v-model="form.pickup" label="Pickup Time" type="datetime-local" />
          <FormInput v-model="form.dropoff" label="Drop-off Time" type="datetime-local" />
          <FormSelect v-model="form.takasi" label="Takasi" :options="opt(['No','Yes'])" />
          <FormSelect v-model="form.taskType" label="Task Type" :options="opt(['Pickup','Delivery','Round-trip'])" />
          <FormInput v-model="form.visits" label="Number of Visits" type="number" />
        </div>
        <template #footer><div class="flex justify-end gap-2"><BaseButton variant="light" type="button">Cancel</BaseButton><BaseButton variant="primary" type="submit" icon="ri-save-line" :loading="saving">Save</BaseButton></div></template>
      </BaseCard>
    </form>
  </div>
</template>
