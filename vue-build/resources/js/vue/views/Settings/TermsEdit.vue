<script setup>
/** /admin/terms/{id}/edit — Edit Term. Mock submit. */
import { ref } from 'vue';
import Breadcrumb from '../../components/Breadcrumb.vue';
import BaseCard from '../../components/BaseCard.vue';
import BaseButton from '../../components/BaseButton.vue';
import FormInput from '../../components/FormInput.vue';
import { useToast } from '../../composables/useToast';

const { push } = useToast();
const opt = (a) => a.map(v => ({ value: v, label: v }));
const saving = ref(false);
const form = ref({"english_text":"","arabic_text":""});
function submit(){ saving.value=true; setTimeout(()=>{saving.value=false; push({type:'success',title:'Saved',message:'Term updated'});},700); }
</script>

<template>
  <div>
    <Breadcrumb title="Edit Term" :trail="[{ label:'Terms', href:'#/admin/terms' }, { label:'Edit' }]" />
    <form @submit.prevent="submit" class="max-w-3xl">
      <BaseCard title="Term details" icon="ri-edit-2-line">
        <div class="grid grid-cols-1 sm:grid-cols-2 gap-x-5 gap-y-4">
        <div class="sm:col-span-2"><FormInput v-model="form.english_text" label="English Text" type="textarea"     /></div>
        <div class="sm:col-span-2"><FormInput v-model="form.arabic_text" label="Arabic Text" type="textarea"     /></div>
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
