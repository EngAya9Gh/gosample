<script setup>
/** /admin/client-drivers/create — New Client Driver. Mock submit. */
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
const form = ref({"drivers":[],"client":""});
function submit(){ saving.value=true; setTimeout(()=>{saving.value=false; push({type:'success',title:'Saved',message:'Client Driver created'});},700); }
</script>

<template>
  <div>
    <Breadcrumb title="New Client Driver" :trail="[{ label:'Client Drivers', href:'#/admin/client-drivers' }, { label:'Create' }]" />
    <form @submit.prevent="submit" class="max-w-3xl">
      <BaseCard title="Client Driver details" icon="ri-edit-2-line">
        <div class="grid grid-cols-1 sm:grid-cols-2 gap-x-5 gap-y-4">
        <div class=""><FormSelect v-model="form.drivers" label="Drivers" :options="opt(['Mohammed Al-Harbi','Fatimah Nasser','Khalid Otaibi','Sara Al-Dosari','Yousef Qahtani'])" multiple :required="true"  /></div>
        <div class=""><FormSelect v-model="form.client" label="Client" :options="opt(['King Faisal Lab','Al-Noor Clinic','Dallah Hospital','Saudi German','Mouwasat Lab','Habib Medical'])"  :required="true"  /></div>
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
