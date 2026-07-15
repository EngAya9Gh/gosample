<script setup>
/** /admin/shipments/create — New Shipment. Mock submit. */
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
const form = ref({"sender_name":"","sender_long":"","sender_lat":"","sender_mobile":"","receiver_name":"","receiver_long":"","receiver_lat":"","receiver_mobile":"","carrier":"","reference_number":"","batch":"","task":"","from_location":"","to_location":"","driver":""});
function submit(){ saving.value=true; setTimeout(()=>{saving.value=false; push({type:'success',title:'Saved',message:'Shipment created'});},700); }
</script>

<template>
  <div>
    <Breadcrumb title="New Shipment" :trail="[{ label:'Shipments', href:'#/admin/shipments' }, { label:'Create' }]" />
    <form @submit.prevent="submit" class="max-w-3xl">
      <BaseCard title="Shipment details" icon="ri-edit-2-line">
        <div class="grid grid-cols-1 sm:grid-cols-2 gap-x-5 gap-y-4">
        <div class=""><FormInput v-model="form.sender_name" label="Sender Name"      /></div>
        <div class=""><FormInput v-model="form.sender_long" label="Sender Longitude"      /></div>
        <div class=""><FormInput v-model="form.sender_lat" label="Sender Latitude"      /></div>
        <div class=""><FormInput v-model="form.sender_mobile" label="Sender Mobile"      /></div>
        <div class=""><FormInput v-model="form.receiver_name" label="Receiver Name"      /></div>
        <div class=""><FormInput v-model="form.receiver_long" label="Receiver Longitude"      /></div>
        <div class=""><FormInput v-model="form.receiver_lat" label="Receiver Latitude"      /></div>
        <div class=""><FormInput v-model="form.receiver_mobile" label="Receiver Mobile"      /></div>
        <div class=""><FormSelect v-model="form.carrier" label="Carrier" :options="opt(['Mohammed Al-Harbi','Fatimah Nasser','Khalid Otaibi','Sara Al-Dosari'])"  :required="true"  /></div>
        <div class=""><FormInput v-model="form.reference_number" label="Reference Number"   :required="true"   /></div>
        <div class=""><FormInput v-model="form.batch" label="Batch"   :required="true"   /></div>
        <div class=""><FormInput v-model="form.task" label="Task"   :required="true"   /></div>
        <div class=""><FormSelect v-model="form.from_location" label="From Location" :options="opt(['Central Hub','Lab East','Lab West','North Depot','King Faisal Lab','Al-Noor Clinic','Dallah Hosp.','Olaya Branch'])"  :required="true"  /></div>
        <div class=""><FormSelect v-model="form.to_location" label="To Location" :options="opt(['Central Hub','Lab East','Lab West','North Depot','King Faisal Lab','Al-Noor Clinic','Dallah Hosp.','Olaya Branch'])"    /></div>
        <div class=""><FormSelect v-model="form.driver" label="Driver" :options="opt(['Mohammed Al-Harbi','Fatimah Nasser','Khalid Otaibi','Sara Al-Dosari'])"    /></div>
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
