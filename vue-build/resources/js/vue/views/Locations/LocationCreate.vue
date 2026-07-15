<script setup>
import { ref } from 'vue';
import Breadcrumb from '../../components/Breadcrumb.vue';
import BaseCard from '../../components/BaseCard.vue';
import BaseButton from '../../components/BaseButton.vue';
import FormInput from '../../components/FormInput.vue';
import FormSelect from '../../components/FormSelect.vue';
import { useToast } from '../../composables/useToast';
const { push }=useToast(); const opt=a=>a.map(v=>({value:v,label:v}));
const f=ref({name:'',arabic:'',desc:'',city:'',neighborhood:'',status:'Active',lat:'24.7136',lng:'46.6753',pickup:'',dropoff:''});
function submit(){ push({type:'success',title:'Saved',message:'Location created'}); }
</script>
<template>
  <div>
    <Breadcrumb title="New Location" :trail="[{label:'Locations',href:'#/admin/locations'},{label:'Create'}]" />
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-5">
      <BaseCard title="Details" icon="ri-map-pin-2-line">
        <div class="grid grid-cols-1 sm:grid-cols-2 gap-x-5 gap-y-4">
          <FormInput v-model="f.name" label="Name" :required="true" />
          <FormInput v-model="f.arabic" label="Arabic Name" :required="true" />
          <div class="sm:col-span-2"><FormInput v-model="f.desc" label="Description" type="textarea" :required="true" /></div>
          <FormSelect v-model="f.city" label="City" :options="opt(['Riyadh — الرياض','Jeddah — جدة'])" :required="true" />
          <FormInput v-model="f.neighborhood" label="Neighborhood" />
          
          <FormSelect v-model="f.status" label="Status" :options="opt(['Active','Not Active'])" />
          <FormInput v-model="f.pickup" label="Pickup Waiting Time" unit="min" type="number" />
          <FormInput v-model="f.dropoff" label="Drop-off Waiting Time" unit="min" type="number" />
        </div>
      </BaseCard>
      <BaseCard title="Map picker" icon="ri-map-2-line">
        <div class="flex gap-2 mb-3"><div class="relative flex-1"><i class="ri-search-line absolute top-1/2 -translate-y-1/2 inset-inline-start-3 text-slate-400" style="inset-inline-start:.75rem"></i><input placeholder="Search place (Google Places)…" class="w-full h-10 ps-10 pe-3 rounded-xl border border-slate-200 dark:border-white/10 bg-surface dark:bg-slate-900/40 text-sm focus:ring-2 focus:ring-primary-500/30 focus:outline-none" /></div></div>
        <div class="relative rounded-2xl overflow-hidden border border-slate-100 dark:border-white/5 h-[460px]" style="background:linear-gradient(135deg,#dfeef0,#eef4f5)"><div class="absolute inset-0 opacity-40" style="background-image:radial-gradient(circle,#0d948822 1px,transparent 1px);background-size:34px 34px"></div><span class="absolute left-1/2 top-1/2 -translate-x-1/2 -translate-y-full text-primary-700"><i class="ri-map-pin-2-fill text-3xl drop-shadow"></i></span><div class="absolute bottom-3 inset-inline-end-3 text-[10px] text-slate-400 bg-surface/70 px-2 py-1 rounded">Google Maps mounts here</div></div>
        <div class="grid grid-cols-2 gap-3 mt-3"><FormInput v-model="f.lat" label="Latitude" /><FormInput v-model="f.lng" label="Longitude" /></div>
        <template #footer><div class="flex justify-end gap-2"><BaseButton variant="light" type="button">Cancel</BaseButton><BaseButton variant="primary" icon="ri-save-line" @click="submit">Save</BaseButton></div></template>
      </BaseCard>
    </div>
  </div>
</template>
