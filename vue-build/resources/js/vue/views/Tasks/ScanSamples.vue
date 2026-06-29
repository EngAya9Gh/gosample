<script setup>
/** /admin/tasks/scan — batch barcode scanning. Reader / Manual / Camera modes. */
import { ref } from 'vue';
import Breadcrumb from '../../components/Breadcrumb.vue';
import BaseCard from '../../components/BaseCard.vue';
import BaseButton from '../../components/BaseButton.vue';
import FormSelect from '../../components/FormSelect.vue';
import { useToast } from '../../composables/useToast';
const { push }=useToast();
const opt=a=>a.map(v=>({value:v,label:v}));
const mode=ref('reader'); const to=ref(''); const driver=ref(''); const manual=ref('');
const scanned=ref(['SM-8841-0052','SM-8841-0053','SM-8841-0061']);
const modes=[{k:'reader',l:'Reader',i:'ri-barcode-line'},{k:'manual',l:'Manual',i:'ri-keyboard-line'},{k:'camera',l:'Camera',i:'ri-camera-line'}];
function add(){ if(manual.value){ scanned.value.unshift(manual.value); manual.value=''; } }
function confirmAll(){ push({type:'success',title:'Confirmed',message:scanned.value.length+' samples confirmed'}); }
</script>
<template>
  <div>
    <Breadcrumb title="Scan Samples" :trail="[{label:'Tasks'},{label:'Scan'}]" />
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-5">
      <BaseCard title="Setup" icon="ri-settings-3-line" class="lg:col-span-1">
        <div class="space-y-4">
          <FormSelect v-model="to" label="To Location" :options="opt(['Central Hub','Lab East','Lab West'])" />
          <FormSelect v-model="driver" label="Driver" :options="opt(['Mohammed Al-Harbi','Fatimah Nasser'])" />
          <div>
            <label class="block text-[13px] font-medium text-slate-600 dark:text-slate-300 mb-1.5">Scan Mode</label>
            <div class="grid grid-cols-3 gap-1.5 p-1 rounded-xl bg-surface-muted dark:bg-white/5">
              <button v-for="m in modes" :key="m.k" @click="mode=m.k" class="flex flex-col items-center gap-1 py-2.5 rounded-lg text-xs font-medium transition" :class="mode===m.k?'bg-surface dark:bg-slate-700 text-primary-700 dark:text-primary-300 shadow-sm':'text-slate-500'"><i :class="[m.i,'text-lg']"></i>{{ m.l }}</button>
            </div>
          </div>
          <BaseButton variant="primary" block icon="ri-download-2-line">Get Samples</BaseButton>
        </div>
      </BaseCard>

      <BaseCard :padded="false" class="lg:col-span-2 overflow-hidden">
        <template #header>
          <div class="flex items-center gap-3 -m-5 mb-0 px-5 py-4 text-white" style="background:linear-gradient(120deg,#005D69,#0d9488)">
            <i class="ri-qr-scan-2-line text-xl"></i><h3 class="font-semibold flex-1">Scanned Batch</h3>
            <span class="inline-grid place-items-center min-w-7 h-7 px-2 rounded-full bg-white/20 text-sm font-bold">{{ scanned.length }}</span>
          </div>
        </template>
        <div class="p-5">
          <div v-if="mode==='manual'" class="flex gap-2 mb-4">
            <input v-model="manual" @keyup.enter="add" placeholder="Type or scan barcode…" class="flex-1 h-11 px-3.5 rounded-xl border border-slate-200 dark:border-white/10 bg-surface dark:bg-slate-900/40 font-mono text-sm focus:ring-2 focus:ring-primary-500/30 focus:outline-none" style="direction:ltr" />
            <BaseButton variant="accent" icon="ri-add-line" @click="add">Add</BaseButton>
          </div>
          <div v-else-if="mode==='camera'" class="grid place-items-center h-32 rounded-xl border-2 border-dashed border-slate-200 dark:border-white/10 text-slate-400 mb-4"><div class="text-center"><i class="ri-camera-line text-2xl"></i><p class="text-xs mt-1">Camera scanner (Scandit) mounts here</p></div></div>
          <div v-else class="flex items-center gap-2 h-12 rounded-xl bg-primary-50 dark:bg-primary-500/10 text-primary-700 dark:text-primary-300 px-4 mb-4 text-sm"><i class="ri-barcode-line text-lg"></i>Hardware reader active — scan now</div>

          <div v-if="scanned.length" class="flex flex-wrap gap-2 max-h-72 overflow-y-auto">
            <span v-for="(b,i) in scanned" :key="i" class="inline-flex items-center gap-2 ps-3 pe-2 h-9 rounded-full bg-surface-muted dark:bg-white/5 font-mono text-[13px] text-ink dark:text-slate-200" style="direction:ltr">
              {{ b }}<button @click="scanned.splice(i,1)" class="grid place-items-center w-5 h-5 rounded-full hover:bg-danger/10 hover:text-danger text-slate-400"><i class="ri-close-line"></i></button>
            </span>
          </div>
          <div v-else class="text-center py-10 text-slate-400 text-sm"><i class="ri-inbox-line text-3xl"></i><p class="mt-2">No samples scanned yet</p></div>

          <div class="flex justify-end mt-5"><BaseButton variant="success" icon="ri-check-double-line" @click="confirmAll" :disabled="!scanned.length">Confirm All</BaseButton></div>
        </div>
      </BaseCard>
    </div>
  </div>
</template>
