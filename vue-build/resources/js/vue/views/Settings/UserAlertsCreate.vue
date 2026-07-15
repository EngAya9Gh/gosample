<script setup>
/** /admin/user-alerts/create — New User Alert. Mock submit. */
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
const form = ref({"alert_text":"","alert_link":"","users":[]});
function submit(){ saving.value=true; setTimeout(()=>{saving.value=false; push({type:'success',title:'Saved',message:'User Alert created'});},700); }
</script>

<template>
  <div>
    <Breadcrumb title="New User Alert" :trail="[{ label:'User Alerts', href:'#/admin/user-alerts' }, { label:'Create' }]" />
    <form @submit.prevent="submit" class="max-w-3xl">
      <BaseCard title="User Alert details" icon="ri-edit-2-line">
        <div class="grid grid-cols-1 sm:grid-cols-2 gap-x-5 gap-y-4">
        <div class="sm:col-span-2"><FormInput v-model="form.alert_text" label="Alert Text" type="textarea"  :required="true"   /></div>
        <div class=""><FormInput v-model="form.alert_link" label="Alert Link"      /></div>
        <div class=""><FormSelect v-model="form.users" label="Users" :options="opt(['Mohammed Al-Harbi','Fatimah Nasser','Khalid Otaibi','Sara Al-Dosari','Yousef Qahtani'])" multiple   /></div>
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
