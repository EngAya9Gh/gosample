<script setup>
import BaseButton from '../../components/BaseButton.vue';
</script>
<template>
  <div class="min-h-screen grid place-items-center p-6 bg-surface-canvas dark:bg-slate-950 font-sans" dir="ltr">
    <div class="text-center max-w-md animate-fade-in-up">
      <div class="grid place-items-center w-24 h-24 rounded-3xl bg-primary-50 dark:bg-primary-500/10 text-primary-600 mx-auto mb-6"><i class="ri-compass-3-line text-5xl"></i></div>
      <div class="text-6xl font-black text-primary-700 dark:text-primary-300 tracking-tight">404</div>
      <h1 class="text-xl font-bold text-ink dark:text-slate-50 mt-3">Sorry, Page not Found 😭</h1>
      <p class="text-sm text-slate-500 mt-2 mb-6">The page you are looking for does not exist or was moved.</p>
      <BaseButton variant="primary" icon="ri-home-4-line">Back to home</BaseButton>
    </div>
  </div>
</template>
