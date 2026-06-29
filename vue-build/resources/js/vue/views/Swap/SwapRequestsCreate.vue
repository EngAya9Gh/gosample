<script setup>
/** /admin/swaprequests/create — New Swap Request. Mock submit. */
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
const form = ref({"driver_a":"","task_id":[],"driver":"","status":""});
function submit(){ saving.value=true; setTimeout(()=>{saving.value=false; push({type:'success',title:'Saved',message:'Swap Request created'});},700); }
</script>

<template>
  <div>
    <Breadcrumb title="New Swap Request" :trail="[{ label:'Swap Requests', href:'#/admin/swaprequests' }, { label:'Create' }]" />
    <form @submit.prevent="submit" class="max-w-3xl">
      <BaseCard title="Swap Request details" icon="ri-edit-2-line">
        <div class="grid grid-cols-1 sm:grid-cols-2 gap-x-5 gap-y-4">
        <div class=""><FormSelect v-model="form.driver_a" label="Driver A (old)" :options="opt(['Mohammed Al-Harbi','Fatimah Nasser','Khalid Otaibi','Sara Al-Dosari'])"  :required="true"  /></div>
        <div class=""><FormSelect v-model="form.task_id" label="Tasks" :options="opt(['#10428 King Faisal Lab (NEW)','#10421 Al-Noor (COLLECTED)'])" multiple :required="true" helper="Populated from chosen old driver" /></div>
        <div class=""><FormSelect v-model="form.driver" label="Driver (new)" :options="opt(['Mohammed Al-Harbi','Fatimah Nasser','Khalid Otaibi','Sara Al-Dosari'])"  :required="true"  /></div>
        <div class=""><FormSelect v-model="form.status" label="Status" :options="opt(['Pending','Approved','Rejected'])"    /></div>
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
