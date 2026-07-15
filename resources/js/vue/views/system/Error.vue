<script setup>
// Styled error page rendered by the Laravel exception handler for Inertia requests
// (403 / 404 / 419 / 500 / 503). See app/Exceptions/Handler.php.
import { computed } from 'vue';
import { router } from '@inertiajs/vue3';
import BaseButton from '../../components/BaseButton.vue';

const props = defineProps({ status: { type: Number, default: 500 } });

const META = {
  403: { title: 'Access denied', msg: 'You don’t have permission to view this screen.', tone: 'text-danger/20' },
  404: { title: 'Page not found', msg: 'The page you’re looking for doesn’t exist in the app.', tone: 'text-primary-700/20 dark:text-primary-300/20' },
  419: { title: 'Session expired', msg: 'Your session timed out — please refresh and try again.', tone: 'text-warning/30' },
  500: { title: 'Something went wrong', msg: 'An unexpected error occurred. Please try again.', tone: 'text-danger/20' },
  503: { title: 'Service unavailable', msg: 'The app is temporarily down for maintenance.', tone: 'text-warning/30' },
};
const info = computed(() => META[props.status] || META[500]);
</script>

<template>
  <div class="min-h-[60vh] grid place-items-center text-center">
    <div>
      <div class="text-7xl font-bold" :class="info.tone">{{ status }}</div>
      <h1 class="mt-2 text-xl font-semibold text-ink dark:text-slate-100">{{ info.title }}</h1>
      <p class="mt-1 text-sm text-slate-500">{{ info.msg }}</p>
      <div class="mt-5">
        <BaseButton variant="primary" icon="ri-home-4-line" @click="router.visit('/dashboard')">Back to dashboard</BaseButton>
      </div>
    </div>
  </div>
</template>
