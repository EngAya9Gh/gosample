<script setup>
/** /admin/clients/create — New Client. Mock submit. */
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
const form = ref({"arabic_name":"","english_name":"","email":"","address":"","status":"","drivers":[],"locations":[]});
function submit(){ saving.value=true; setTimeout(()=>{saving.value=false; push({type:'success',title:'Saved',message:'Client created'});},700); }
</script>

<template>
  <div>
    <Breadcrumb title="New Client" :trail="[{ label:'Clients', href:'#/admin/clients' }, { label:'Create' }]" />
    <form @submit.prevent="submit" class="max-w-3xl">
      <BaseCard title="Client details" icon="ri-edit-2-line">
        <div class="grid grid-cols-1 sm:grid-cols-2 gap-x-5 gap-y-4">
        <div class=""><FormInput v-model="form.arabic_name" label="Arabic Name"      /></div>
        <div class=""><FormInput v-model="form.english_name" label="English Name"      /></div>
        <div class=""><FormInput v-model="form.email" label="Email" type="email"     /></div>
        <div class=""><FormInput v-model="form.address" label="Address"      /></div>
        <div class=""><FormSelect v-model="form.status" label="Status" :options="opt(['Enabled','Disabled'])"  :required="true"  /></div>
        <div class=""><FormSelect v-model="form.drivers" label="Drivers" :options="opt(['Mohammed Al-Harbi','Fatimah Nasser','Khalid Otaibi','Sara Al-Dosari','Yousef Qahtani'])" multiple :required="true"  /></div>
        <div class=""><FormSelect v-model="form.locations" label="Locations" :options="opt(['Central Hub','Lab East','Lab West','North Depot','King Faisal Lab','Al-Noor Clinic','Dallah Hosp.','Olaya Branch'])" multiple :required="true"  /></div>
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
