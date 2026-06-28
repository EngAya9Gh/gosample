<script setup>
/**
 * EmptyState — friendly empty placeholder for lists/routes/notifications.
 */
defineProps({
  icon:  { type: String, default: 'ri-inbox-2-line' },
  title: { type: String, default: 'Nothing here yet' },
  message: { type: String, default: '' },
});
</script>

<template>
  <div class="flex flex-col items-center justify-center text-center py-14 px-6 animate-fade-in-up">
    <div class="grid place-items-center w-16 h-16 rounded-2xl bg-primary-50 dark:bg-primary-500/10 text-primary-500 mb-4">
      <i :class="[icon, 'text-3xl']"></i>
    </div>
    <h4 class="font-semibold text-ink dark:text-slate-100">{{ title }}</h4>
    <p v-if="message" class="text-sm text-slate-500 dark:text-slate-400 mt-1 max-w-xs">{{ message }}</p>
    <div class="mt-4"><slot></slot></div>
  </div>
</template>
