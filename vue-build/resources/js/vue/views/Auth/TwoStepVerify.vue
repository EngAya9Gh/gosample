<script setup>
import { ref } from 'vue';
import BaseButton from '../../components/BaseButton.vue';
const digits=ref(['','','','']);
function onInput(i,e){ const v=e.target.value.replace(/[^0-9]/g,'').slice(0,1); digits.value[i]=v; if(v&&i<3){ const n=e.target.parentElement.children[i+1]; n&&n.focus(); } }
</script>
<template>
  <div dir="ltr" class="min-h-screen grid lg:grid-cols-2 font-sans">
    <div class="relative hidden lg:flex flex-col justify-between p-12 text-white overflow-hidden" style="background:linear-gradient(140deg,#03363d,#005D69 55%,#0d9488)">
      <div class="absolute -top-16 -right-16 w-72 h-72 rounded-full bg-white/10"></div>
      <div class="absolute -bottom-24 -left-10 w-96 h-96 rounded-full bg-white/5"></div>
      <div class="relative flex items-center gap-3"><span class="grid place-items-center w-12 h-12 rounded-xl bg-white/15 backdrop-blur font-bold text-2xl">M</span><div><div class="font-bold text-2xl tracking-wide">MTC</div><div class="text-[11px] text-white/70 uppercase tracking-[.25em]">GoSample</div></div></div>
      <div class="relative"><h2 class="text-3xl font-bold leading-tight">Medical-sample<br>cold-chain logistics</h2><p class="text-white/70 mt-3 max-w-sm">Track every pickup, container temperature and delivery — in real time, across your fleet.</p></div>
      <div class="relative text-white/50 text-xs">© 2026 MTC. Crafted with ♥ by MTC team</div>
    </div>
    <div class="flex items-center justify-center p-6 sm:p-12 bg-surface-canvas dark:bg-slate-950">
      <div class="w-full max-w-sm animate-fade-in-up">
        <h1 class="text-2xl font-bold text-ink dark:text-slate-50">Two-step verification</h1>
        <p class="text-sm text-slate-500 mt-1 mb-6">Enter the 4-digit code we sent you</p>
        <div class="flex gap-3 justify-center mb-6" dir="ltr">
          <input v-for="(d,i) in digits" :key="i" :value="d" @input="onInput(i,$event)" maxlength="1" inputmode="numeric" class="w-14 h-16 text-center text-2xl font-bold rounded-xl border-2 border-slate-200 dark:border-white/10 bg-surface dark:bg-slate-900/40 focus:ring-4 focus:ring-primary-500/20 focus:border-primary-500 focus:outline-none" />
        </div>
        <BaseButton variant="primary" block icon="ri-shield-check-line">Confirm</BaseButton>
        <p class="text-center text-sm text-slate-500 mt-4">Did not get it? <a href="#" class="text-primary-600 font-medium">Resend</a></p>
      </div>
    </div>
  </div>
</template>
