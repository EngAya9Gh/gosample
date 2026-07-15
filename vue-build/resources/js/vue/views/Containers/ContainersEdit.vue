<script setup>
/** /admin/containers/{id}/edit — Edit Container. Mock submit. */
import { ref } from 'vue';
import Breadcrumb from '../../components/Breadcrumb.vue';
import BaseCard from '../../components/BaseCard.vue';
import BaseButton from '../../components/BaseButton.vue';
import FormInput from '../../components/FormInput.vue';
import FormSelect from '../../components/FormSelect.vue';
import { useToast } from '../../composables/useToast';

const { push } = useToast();
const opt = (a) => a.map(v => ({ value: v, label: v }));
const saving = ref(false);
const form = ref({"car_id":"","imei":"","model":"","type":"","description":"","status":""});
function submit(){ saving.value=true; setTimeout(()=>{saving.value=false; push({type:'success',title:'Saved',message:'Container updated'});},700); }
</script>

<template>
  <div>
    <Breadcrumb title="Edit Container" :trail="[{ label:'Containers', href:'#/admin/containers' }, { label:'Edit' }]" />
    <form @submit.prevent="submit" class="max-w-3xl">
      <BaseCard title="Container details" icon="ri-edit-2-line">
        <div class="grid grid-cols-1 sm:grid-cols-2 gap-x-5 gap-y-4">
        <div class=""><FormSelect v-model="form.car_id" label="Car" :options="opt(['RUH 2001','JED 2014','DMM 2027'])"    /></div>
        <div class=""><FormInput v-model="form.imei" label="Sensor (IMEI)"   :required="true"   /></div>
        <div class=""><FormInput v-model="form.model" label="Model"   :required="true"   /></div>
        <div class=""><FormSelect v-model="form.type" label="Type" :options="opt(['Room','Refrigerate','Frozen'])"  :required="true"  /></div>
        <div class="sm:col-span-2"><FormInput v-model="form.description" label="Description" type="textarea"     /></div>
        <div class=""><FormSelect v-model="form.status" label="Status" :options="opt(['Enabled','Disabled'])"  :required="true"  /></div>
        </div>
        <template #footer>
          <div class="flex items-center justify-end gap-2">
            <BaseButton variant="light" type="button">Cancel</BaseButton>
            <BaseButton variant="primary" type="submit" icon="ri-save-line" :loading="saving">Save</BaseButton>
          </div>
        </template>
      </BaseCard>
    </form>
  </div>
</template>
