<script setup>
import { ref } from 'vue';
import Breadcrumb from '../../components/Breadcrumb.vue';
import BaseButton from '../../components/BaseButton.vue';
import BaseAvatar from '../../components/BaseAvatar.vue';
import { useToast } from '../../composables/useToast';
const { push }=useToast();
const tasks=ref([
  { id:10428, from:'Central Hub', to:'Lab East', eta:14 },
  { id:10431, from:'Lab East', to:'North Depot', eta:22 },
  { id:10435, from:'North Depot', to:'Olaya Branch', eta:18 },
  { id:10440, from:'Olaya Branch', to:'Central Hub', eta:26 },
]);
const dirty=ref(false);
let dragI=null;
function dragStart(i){ dragI=i; }
function dragOver(i,e){ e.preventDefault(); if(dragI===i)return; const a=tasks.value.splice(dragI,1)[0]; tasks.value.splice(i,0,a); dragI=i; dirty.value=true; }
function save(){ dirty.value=false; push({type:'success',title:'Route saved',message:'New order applied'}); }
function smart(){ tasks.value.sort((a,b)=>a.eta-b.eta); dirty.value=true; push({type:'info',title:'Smart sort',message:'Ordered by nearest'}); }
</script>
<template>
  <div class="max-w-2xl mx-auto">
    <Breadcrumb title="مسار السائق — Driver Route" :trail="[{label:'Drivers',href:'#/admin/drivers'},{label:'Route'}]">
      <template #actions>
        <BaseButton variant="light" icon="ri-flashlight-line" @click="smart">الترتيب الذكي</BaseButton>
        <BaseButton variant="primary" icon="ri-save-line" :disabled="!dirty" @click="save">Save New Order</BaseButton>
      </template>
    </Breadcrumb>
    <div class="space-y-2.5">
      <div v-for="(t,i) in tasks" :key="t.id" draggable="true" @dragstart="dragStart(i)" @dragover="dragOver(i,$event)"
        class="flex items-center gap-3 bg-surface dark:bg-slate-800/60 rounded-xl shadow-card border border-slate-100 dark:border-white/5 p-3.5 cursor-grab active:cursor-grabbing hover:shadow-card-hover transition">
        <i class="ri-draggable text-slate-300 text-xl"></i>
        <span class="grid place-items-center w-8 h-8 rounded-lg bg-primary-50 text-primary-700 dark:bg-primary-500/15 dark:text-primary-300 text-sm font-bold shrink-0">{{ i+1 }}</span>
        <div class="flex-1 min-w-0">
          <div class="font-semibold text-primary-700 dark:text-primary-300">#{{ t.id }}</div>
          <div class="text-xs text-slate-500"><span class="text-danger"><i class="ri-map-pin-fill"></i> {{ t.from }}</span> → <span class="text-success"><i class="ri-map-pin-fill"></i> {{ t.to }}</span></div>
        </div>
        <span class="inline-flex items-center justify-center min-w-14 h-7 px-2 rounded-full bg-surface-muted dark:bg-white/5 text-xs font-semibold text-slate-600 dark:text-slate-300">{{ t.eta }} دقيقة</span>
        <BaseAvatar name="Mohammed Al-Harbi" :size="30" />
      </div>
    </div>
  </div>
</template>
