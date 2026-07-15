<script setup>
import { ref } from 'vue';
import Breadcrumb from '../../components/Breadcrumb.vue';
import BaseCard from '../../components/BaseCard.vue';
import BaseButton from '../../components/BaseButton.vue';
import FormInput from '../../components/FormInput.vue';
import FormSelect from '../../components/FormSelect.vue';
const opt=a=>a.map(v=>({value:v,label:v}));
const count=ref(12); const type=ref('Bag'); const generated=ref(false);
const labels=ref([]);
function gen(){ labels.value=Array.from({length:count.value},(_,i)=>(type.value==='Bag'?'BG-':'SM-')+(2300+i)); generated.value=true; }
</script>
<template>
  <div>
    <Breadcrumb title="Generate Barcodes" :trail="[{label:'Barcodes',href:'#/admin/barcodes'},{label:'Generate'}]">
      <template #actions><BaseButton v-if="generated" variant="primary" icon="ri-printer-line" @click="()=>window.print()">Print</BaseButton></template>
    </Breadcrumb>
    <BaseCard title="Options" icon="ri-barcode-box-line" class="mb-5 print:hidden">
      <div class="flex flex-wrap items-end gap-4">
        <FormInput v-model.number="count" label="Total Count" type="number" class="w-40" />
        <FormSelect v-model="type" label="Type" :options="opt(['Bag','Sample'])" class="w-40" />
        <BaseButton variant="primary" icon="ri-magic-line" @click="gen">Generate</BaseButton>
      </div>
    </BaseCard>
    <div v-if="generated" class="bg-surface rounded-2xl shadow-card border border-slate-100 p-6 print:shadow-none print:border-0">
      <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-4 gap-4">
        <div v-for="b in labels" :key="b" class="border border-slate-200 rounded-lg p-3 text-center break-inside-avoid">
          <div class="flex items-center justify-center gap-1.5 mb-1.5"><span class="grid place-items-center w-5 h-5 rounded bg-primary-700 text-white text-[10px] font-bold">M</span><span class="text-[10px] font-semibold text-primary-700">MTC</span></div>
          <div class="flex items-end justify-center gap-px h-12 mb-1"><span v-for="n in 30" :key="n" class="bg-ink" :style="{ width:'1.5px', height:(40+(n*41%60))+'%' }"></span></div>
          <p class="font-mono text-[11px] text-ink" style="direction:ltr">{{ b }}</p>
        </div>
      </div>
    </div>
  </div>
</template>
