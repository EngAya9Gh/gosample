<script setup>
import { ref } from 'vue';
import Breadcrumb from '../../components/Breadcrumb.vue';
import BaseCard from '../../components/BaseCard.vue';
import BaseButton from '../../components/BaseButton.vue';
import FormInput from '../../components/FormInput.vue';
import { useToast } from '../../composables/useToast';
const { push }=useToast();
const f=ref({current:'',neu:'',confirm:''});
function submit(){ push({type:'success',title:'Password changed'}); }
</script>
<template>
  <div>
    <Breadcrumb title="Change Password" :trail="[{label:'Profile'},{label:'Change Password'}]" />
    <BaseCard title="Update password" icon="ri-lock-line" class="max-w-md">
      <div class="space-y-4">
        <FormInput v-model="f.current" label="Current Password" type="password" :required="true" />
        <FormInput v-model="f.neu" label="New Password" type="password" :required="true" />
        <FormInput v-model="f.confirm" label="Confirm New Password" type="password" :required="true" />
      </div>
      <template #footer><div class="flex justify-end"><BaseButton variant="primary" icon="ri-save-line" @click="submit">Update Password</BaseButton></div></template>
    </BaseCard>
  </div>
</template>
