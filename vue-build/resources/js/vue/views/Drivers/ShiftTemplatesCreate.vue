<script setup>
/** /admin/shift-templates/create — New Shift Template. Mock submit. */
import { ref } from 'vue';
import Breadcrumb from '../../components/Breadcrumb.vue';
import BaseCard from '../../components/BaseCard.vue';
import BaseButton from '../../components/BaseButton.vue';
import FormInput from '../../components/FormInput.vue';
import { useToast } from '../../composables/useToast';

const { push } = useToast();
const opt = (a) => a.map(v => ({ value: v, label: v }));
const saving = ref(false);
const form = ref({"name":"","start":"","end":""});
function submit(){ saving.value=true; setTimeout(()=>{saving.value=false; push({type:'success',title:'Saved',message:'Shift Template created'});},700); }
</script>

<template>
  <div>
    <Breadcrumb title="New Shift Template" :trail="[{ label:'Shift Templates', href:'#/admin/shift-templates' }, { label:'Create' }]" />
    <form @submit.prevent="submit" class="max-w-3xl">
      <BaseCard title="Shift Template details" icon="ri-edit-2-line">
        <div class="grid grid-cols-1 sm:grid-cols-2 gap-x-5 gap-y-4">
        <div class=""><FormInput v-model="form.name" label="Template Name"   :required="true"   /></div>
        <div class=""><FormInput v-model="form.start" label="Shift Start Time" type="time"  :required="true"   /></div>
        <div class=""><FormInput v-model="form.end" label="Shift End Time" type="time"  :required="true"   /></div>
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
