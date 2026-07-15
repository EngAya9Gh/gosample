<script setup>
import { ref } from 'vue';
import Breadcrumb from '../../components/Breadcrumb.vue';
import BaseCard from '../../components/BaseCard.vue';
import BaseButton from '../../components/BaseButton.vue';
import FormInput from '../../components/FormInput.vue';
import { useToast } from '../../composables/useToast';
const { push }=useToast(); const name=ref('');
function submit(){ push({type:'success',title:'Saved',message:'Zone created'}); }
</script>
<template>
  <div>
    <Breadcrumb title="New Zone" :trail="[{label:'Zones',href:'#/admin/zones'},{label:'Create'}]" />
    <BaseCard title="Zone area" icon="ri-shape-2-line">
      <FormInput v-model="name" label="Name" :required="true" class="max-w-md mb-4" />
      <label class="block text-[13px] font-medium text-slate-600 dark:text-slate-300 mb-1.5">Area — draw polygon</label>
      <div class="relative rounded-2xl overflow-hidden border border-slate-100 dark:border-white/5 h-[460px]" style="background:linear-gradient(135deg,#dfeef0,#eef4f5)"><div class="absolute inset-0 opacity-40" style="background-image:radial-gradient(circle,#0d948822 1px,transparent 1px);background-size:34px 34px"></div><svg class="absolute inset-0 w-full h-full"><polygon points="120,90 320,70 420,220 260,320 100,250" fill="rgba(189,107,167,.25)" stroke="#BD6BA7" stroke-width="2" /></svg><div class="absolute bottom-3 inset-inline-end-3 text-[10px] text-slate-400 bg-surface/70 px-2 py-1 rounded">Google Maps mounts here</div></div>
      <template #footer><div class="flex justify-between"><BaseButton variant="light" icon="ri-refresh-line">Reset Map</BaseButton><div class="flex gap-2"><BaseButton variant="light" type="button">Cancel</BaseButton><BaseButton variant="primary" icon="ri-save-line" @click="submit">Save</BaseButton></div></div></template>
    </BaseCard>
  </div>
</template>
