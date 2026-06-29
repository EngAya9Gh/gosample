<script setup>
import { ref } from 'vue';
import BaseButton from '../../components/BaseButton.vue';
const f=ref({email:'',username:'',password:'',confirm:''}); const show=ref(false); const terms=ref(false);
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
        <h1 class="text-2xl font-bold text-ink dark:text-slate-50">Create account</h1>
        <p class="text-sm text-slate-500 mt-1 mb-6">Join the MTC operations console</p>
        <form @submit.prevent class="space-y-4">
        <div><label class="block text-[13px] font-medium text-slate-600 dark:text-slate-300 mb-1.5">Email</label><input type="email" v-model="f.email" class="w-full h-11 px-3.5 rounded-xl border border-slate-200 dark:border-white/10 bg-surface dark:bg-slate-900/40 text-sm focus:ring-2 focus:ring-primary-500/40 focus:border-primary-500 focus:outline-none" /></div>
        <div><label class="block text-[13px] font-medium text-slate-600 dark:text-slate-300 mb-1.5">Username</label><input type="text" v-model="f.username" class="w-full h-11 px-3.5 rounded-xl border border-slate-200 dark:border-white/10 bg-surface dark:bg-slate-900/40 text-sm focus:ring-2 focus:ring-primary-500/40 focus:border-primary-500 focus:outline-none" /></div>
        <div><label class="block text-[13px] font-medium text-slate-600 dark:text-slate-300 mb-1.5">Password</label><div class="relative"><input :type="show?'text':'password'" v-model="f.password" class="w-full h-11 ps-3.5 pe-11 rounded-xl border border-slate-200 dark:border-white/10 bg-surface dark:bg-slate-900/40 text-sm focus:ring-2 focus:ring-primary-500/40 focus:border-primary-500 focus:outline-none" /><button type="button" @click="show=!show" class="absolute top-1/2 -translate-y-1/2 inset-inline-end-2 grid place-items-center w-8 h-8 rounded-lg text-slate-400 hover:text-primary-600" style="inset-inline-end:.5rem"><i :class="show?'ri-eye-off-line':'ri-eye-line'"></i></button></div></div>
        <div><label class="block text-[13px] font-medium text-slate-600 dark:text-slate-300 mb-1.5">Confirm Password</label><div class="relative"><input :type="show?'text':'password'" v-model="f.confirm" class="w-full h-11 ps-3.5 pe-11 rounded-xl border border-slate-200 dark:border-white/10 bg-surface dark:bg-slate-900/40 text-sm focus:ring-2 focus:ring-primary-500/40 focus:border-primary-500 focus:outline-none" /><button type="button" @click="show=!show" class="absolute top-1/2 -translate-y-1/2 inset-inline-end-2 grid place-items-center w-8 h-8 rounded-lg text-slate-400 hover:text-primary-600" style="inset-inline-end:.5rem"><i :class="show?'ri-eye-off-line':'ri-eye-line'"></i></button></div></div>
          <label class="flex items-center gap-2 text-sm text-slate-600 dark:text-slate-300 cursor-pointer"><input type="checkbox" v-model="terms" class="w-4 h-4 rounded accent-primary-600" />I agree to the terms</label>
          <BaseButton variant="primary" block icon="ri-user-add-line">Sign Up</BaseButton>
        </form>
      </div>
    </div>
  </div>
</template>
