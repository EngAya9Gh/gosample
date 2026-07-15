<script setup>
import { ref } from 'vue';
import Breadcrumb from '../../components/Breadcrumb.vue';
import BaseCard from '../../components/BaseCard.vue';
import BaseButton from '../../components/BaseButton.vue';
import FormSelect from '../../components/FormSelect.vue';
import { useToast } from '../../composables/useToast';
const { push }=useToast();
const opt=a=>a.map(v=>({value:v,label:v}));
const saving=ref(false);
const form=ref({from:'Central Hub',to:'Lab East',client:'King Faisal Lab',driver:'Mohammed Al-Harbi',taskType:'Pickup',status:'COLLECTED',takasi:'No'});
function submit(){ saving.value=true; setTimeout(()=>{saving.value=false; push({type:'success',title:'Saved',message:'Task updated'});},700); }
</script>
<template>
  <div>
    <Breadcrumb title="Edit Task #10428" :trail="[{label:'Tasks',href:'#/admin/tasks'},{label:'Edit'}]" />
    <form @submit.prevent="submit" class="max-w-3xl">
      <BaseCard title="Task details" icon="ri-edit-2-line">
        <div class="grid grid-cols-1 sm:grid-cols-2 gap-x-5 gap-y-4">
          <FormSelect v-model="form.from" label="From Location" :options="opt(['Central Hub','Lab East'])" helper="Where samples are picked up" />
          <FormSelect v-model="form.to" label="To Location" :options="opt(['Lab East','North Depot'])" helper="Where samples are delivered" />
          <FormSelect v-model="form.client" label="Billing Client" :options="opt(['King Faisal Lab','Al-Noor Clinic'])" />
          <FormSelect v-model="form.driver" label="Driver" :options="opt(['Mohammed Al-Harbi','Fatimah Nasser'])" />
          <FormSelect v-model="form.taskType" label="Task Type" :options="opt(['Pickup','Delivery','Round-trip'])" />
          <FormSelect v-model="form.status" label="Status" :options="opt(['NEW','COLLECTED','IN_CONTAINER','OUT_CONTAINER','CLOSED','NO_SAMPLES'])" />
          <FormSelect v-model="form.takasi" label="Takasi" :options="opt(['No','Yes'])" helper="Third-party courier handover" />
        </div>
        <template #footer><div class="flex justify-end gap-2"><BaseButton variant="light" type="button">Cancel</BaseButton><BaseButton variant="primary" type="submit" icon="ri-save-line" :loading="saving">Save</BaseButton></div></template>
      </BaseCard>
    </form>
  </div>
</template>
