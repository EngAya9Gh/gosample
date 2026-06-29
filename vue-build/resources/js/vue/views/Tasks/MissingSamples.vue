<script setup>
import { ref } from 'vue';
import Breadcrumb from '../../components/Breadcrumb.vue';
import BaseCard from '../../components/BaseCard.vue';
import BaseButton from '../../components/BaseButton.vue';
import { useToast } from '../../composables/useToast';
const { push }=useToast();
const code=ref('');
const recent=[{b:'SM-8841-0052',lost:false},{b:'SM-8841-0107',lost:true},{b:'SM-8841-0061',lost:false},{b:'SM-8841-0072',lost:false}];
const result=ref(null);
function act(kind){ if(!code.value){ push({type:'error',title:'Enter a barcode'}); return; }
  if(kind==='lost'){ result.value={tone:'danger',msg:'Marked as LOST: '+code.value}; push({type:'success',title:'Marked lost'}); }
  else if(kind==='confirm'){ result.value={tone:'success',msg:'Confirmed: '+code.value}; push({type:'success',title:'Confirmed'}); }
  else { result.value={tone:'info',msg:'Confirmation method: Client portal · 2026-06-27 09:14'}; }
}
</script>
<template>
  <div class="max-w-2xl mx-auto">
    <Breadcrumb title="Missing Samples" :trail="[{label:'Tasks'},{label:'Missing'}]" />
    <BaseCard title="Reconcile sample" icon="ri-search-eye-line">
      <input v-model="code" autofocus placeholder="Scan or type barcode…" class="w-full h-14 px-4 rounded-xl border-2 border-slate-200 dark:border-white/10 bg-surface dark:bg-slate-900/40 font-mono text-lg text-center focus:ring-4 focus:ring-primary-500/20 focus:border-primary-500 focus:outline-none" style="direction:ltr" />
      <div class="flex flex-wrap gap-2 mt-3">
        <button v-for="r in recent" :key="r.b" @click="code=r.b" class="inline-flex items-center gap-2 ps-3 pe-2.5 h-8 rounded-full bg-surface-muted dark:bg-white/5 font-mono text-xs hover:bg-primary-50 transition" style="direction:ltr">
          {{ r.b }}<span v-if="r.lost" class="text-[10px] font-bold text-danger bg-danger/10 px-1.5 rounded-full" style="direction:ltr">LOST</span>
        </button>
      </div>
      <div class="grid grid-cols-3 gap-2 mt-5">
        <BaseButton variant="danger" icon="ri-close-circle-line" @click="act('lost')">Mark Lost</BaseButton>
        <BaseButton variant="success" icon="ri-checkbox-circle-line" @click="act('confirm')">Mark Confirmed</BaseButton>
        <BaseButton variant="info" icon="ri-information-line" @click="act('details')">Get Details</BaseButton>
      </div>
      <div v-if="result" class="mt-4 flex items-center gap-2 px-4 h-12 rounded-xl text-sm font-medium" :class="{'bg-danger/10 text-danger':result.tone==='danger','bg-success/10 text-success':result.tone==='success','bg-info/10 text-info':result.tone==='info'}">
        <i class="ri-information-line"></i>{{ result.msg }}
      </div>
    </BaseCard>
  </div>
</template>
