<script setup>
/** /admin/users/create — New User. Mock submit. */
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
const form = ref({"name":"","email":"","password":"","clients":[],"roles":[]});
function submit(){ saving.value=true; setTimeout(()=>{saving.value=false; push({type:'success',title:'Saved',message:'User created'});},700); }
</script>

<template>
  <div>
    <Breadcrumb title="New User" :trail="[{ label:'Users', href:'#/admin/users' }, { label:'Create' }]" />
    <form @submit.prevent="submit" class="max-w-3xl">
      <BaseCard title="User details" icon="ri-edit-2-line">
        <div class="grid grid-cols-1 sm:grid-cols-2 gap-x-5 gap-y-4">
        <div class=""><FormInput v-model="form.name" label="Name"   :required="true"   /></div>
        <div class=""><FormInput v-model="form.email" label="Email" type="email"  :required="true"   /></div>
        <div class=""><FormInput v-model="form.password" label="Password" type="password"  :required="true"   /></div>
        <div class=""><FormSelect v-model="form.clients" label="Clients" :options="opt(['King Faisal Lab','Al-Noor Clinic','Dallah Hospital','Saudi German','Mouwasat Lab','Habib Medical'])" multiple   /></div>
        <div class=""><FormSelect v-model="form.roles" label="Roles" :options="opt(['Admin','Dispatcher','Manager','Viewer'])" multiple :required="true"  /></div>
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
