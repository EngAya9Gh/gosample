<script setup>
/** /admin/driver-schedules/create — New Driver Schedule. Mock submit. */
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
const form = ref({"from_location":"","to_location":"","driver":"","plate_number":"","note":""});
function submit(){ saving.value=true; setTimeout(()=>{saving.value=false; push({type:'success',title:'Saved',message:'Driver Schedule created'});},700); }
</script>

<template>
  <div>
    <Breadcrumb title="New Driver Schedule" :trail="[{ label:'Driver Schedules', href:'#/admin/driver-schedules' }, { label:'Create' }]" />
    <form @submit.prevent="submit" class="max-w-3xl">
      <BaseCard title="Driver Schedule details" icon="ri-edit-2-line">
        <div class="grid grid-cols-1 sm:grid-cols-2 gap-x-5 gap-y-4">
        <div class=""><FormSelect v-model="form.from_location" label="From Location" :options="opt(['Central Hub','Lab East','Lab West','North Depot','King Faisal Lab','Al-Noor Clinic','Dallah Hosp.','Olaya Branch'])"    /></div>
        <div class=""><FormSelect v-model="form.to_location" label="To Location" :options="opt(['Central Hub','Lab East','Lab West','North Depot','King Faisal Lab','Al-Noor Clinic','Dallah Hosp.','Olaya Branch'])"    /></div>
        <div class=""><FormSelect v-model="form.driver" label="Driver" :options="opt(['Mohammed Al-Harbi','Fatimah Nasser','Khalid Otaibi','Sara Al-Dosari'])"    /></div>
        <div class=""><FormInput v-model="form.plate_number" label="Plate Number"      /></div>
        <div class="sm:col-span-2"><FormInput v-model="form.note" label="Note" type="textarea"     /></div>
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
