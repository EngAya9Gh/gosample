<script setup>
/** /admin/cars/create — New Car. Mock submit. */
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
const form = ref({"driver":"","imei":"","plate":"","model":"","color":"","contact":"","status":"","afaqi":"","description":""});
function submit(){ saving.value=true; setTimeout(()=>{saving.value=false; push({type:'success',title:'Saved',message:'Car created'});},700); }
</script>

<template>
  <div>
    <Breadcrumb title="New Car" :trail="[{ label:'Cars', href:'#/admin/cars' }, { label:'Create' }]" />
    <form @submit.prevent="submit" class="max-w-3xl">
      <BaseCard title="Car details" icon="ri-edit-2-line">
        <div class="grid grid-cols-1 sm:grid-cols-2 gap-x-5 gap-y-4">
        <div class=""><FormSelect v-model="form.driver" label="Driver" :options="opt(['Mohammed Al-Harbi','Fatimah Nasser','Khalid Otaibi','Sara Al-Dosari'])"    /></div>
        <div class=""><FormInput v-model="form.imei" label="IMEI"   :required="true"   /></div>
        <div class=""><FormInput v-model="form.plate" label="Plate Number"   :required="true"   /></div>
        <div class=""><FormInput v-model="form.model" label="Model"      /></div>
        <div class=""><FormInput v-model="form.color" label="Color"      /></div>
        <div class=""><FormInput v-model="form.contact" label="Contact Person"   :required="true"   /></div>
        <div class=""><FormSelect v-model="form.status" label="Status" :options="opt(['Enable','Disable'])"    /></div>
        <div class=""><FormSelect v-model="form.afaqi" label="Afaqi" :options="opt(['No','Yes'])"  :required="true"  /></div>
        <div class="sm:col-span-2"><FormInput v-model="form.description" label="Description" type="textarea"     /></div>
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
