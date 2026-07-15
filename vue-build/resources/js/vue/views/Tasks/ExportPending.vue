<script setup>
import { ref, onMounted, onBeforeUnmount } from 'vue';
const state=ref('pending'); const secs=ref(0); let t;
onMounted(()=>{ t=setInterval(()=>{ secs.value++; if(secs.value===4) state.value='ready'; },1000); });
onBeforeUnmount(()=>clearInterval(t));
</script>
<template>
  <div class="grid place-items-center min-h-[60vh]">
    <div class="text-center max-w-sm">
      <template v-if="state==='pending'">
        <div class="grid place-items-center w-20 h-20 rounded-2xl bg-primary-50 dark:bg-primary-500/10 text-primary-600 mx-auto mb-5"><i class="ri-loader-4-line text-4xl animate-spin"></i></div>
        <h2 class="text-xl font-bold text-ink dark:text-slate-50">Preparing your report</h2>
        <p class="text-sm text-slate-500 mt-2">Gathering tasks and building the export… <span class="font-mono">{{ secs }}s</span></p>
      </template>
      <template v-else-if="state==='ready'">
        <div class="grid place-items-center w-20 h-20 rounded-2xl bg-success/10 text-success mx-auto mb-5 animate-fade-in-up"><i class="ri-checkbox-circle-line text-4xl"></i></div>
        <h2 class="text-xl font-bold text-ink dark:text-slate-50">Report ready</h2>
        <p class="text-sm text-slate-500 mt-2 mb-5">1,284 tasks included.</p>
        <a href="#" class="inline-flex items-center gap-2 h-11 px-5 rounded-xl bg-primary-700 text-white font-medium shadow-card hover:bg-primary-800"><i class="ri-download-2-line"></i>Download</a>
      </template>
      <template v-else>
        <div class="grid place-items-center w-20 h-20 rounded-2xl bg-danger/10 text-danger mx-auto mb-5"><i class="ri-error-warning-line text-4xl"></i></div>
        <h2 class="text-xl font-bold text-ink dark:text-slate-50">Export failed</h2>
        <p class="text-sm text-slate-500 mt-2">Something went wrong. Please try again.</p>
      </template>
    </div>
  </div>
</template>
