<script setup>
import { ref } from 'vue';
import Breadcrumb from '../../components/Breadcrumb.vue';
import BaseCard from '../../components/BaseCard.vue';
import BaseButton from '../../components/BaseButton.vue';
import BaseAvatar from '../../components/BaseAvatar.vue';
import FormInput from '../../components/FormInput.vue';
import { useToast } from '../../composables/useToast';
const { push }=useToast();
const f=ref({name:'Sara Al-Otaibi',email:'sara@mtc.sa'});
</script>
<template>
  <div>
    <Breadcrumb title="Profile" :trail="[{label:'Profile'}]" />
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-5">
      <BaseCard><div class="flex flex-col items-center text-center"><BaseAvatar name="Sara Al-Otaibi" :size="88" /><h2 class="text-lg font-bold text-ink dark:text-slate-50 mt-3">Sara Al-Otaibi</h2><p class="text-sm text-slate-400">Operations Manager</p><BaseButton variant="light" size="sm" icon="ri-upload-2-line" class="mt-4">Change avatar</BaseButton></div></BaseCard>
      <BaseCard title="Account details" icon="ri-user-settings-line" class="lg:col-span-2"><div class="grid grid-cols-1 sm:grid-cols-2 gap-x-5 gap-y-4"><FormInput v-model="f.name" label="Name" /><FormInput v-model="f.email" label="Email" type="email" /></div><template #footer><div class="flex justify-end gap-2"><a href="#/admin/change-password" class="inline-flex items-center gap-2 h-10 px-4 rounded-xl bg-surface-muted dark:bg-white/10 text-sm font-medium text-ink dark:text-slate-200"><i class="ri-lock-line"></i>Change password</a><BaseButton variant="primary" icon="ri-save-line" @click="push({type:'success',title:'Profile saved'})">Save</BaseButton></div></template></BaseCard>
    </div>
  </div>
</template>
