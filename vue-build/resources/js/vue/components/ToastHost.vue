<script setup>
/**
 * ToastHost — render once in AppShell. Reads the shared toast store.
 * Slides in from the inline-end (RTL-aware).
 */
import { useToast } from '../composables/useToast';
const { state, dismiss } = useToast();

const ICONS = {
  success: 'ri-checkbox-circle-fill text-success',
  error:   'ri-error-warning-fill text-danger',
  info:    'ri-information-fill text-info',
  warning: 'ri-alert-fill text-warning',
};
</script>

<template>
  <div class="fixed top-4 inset-inline-start-4 z-[120] flex flex-col gap-2.5 w-[330px] max-w-[calc(100vw-2rem)]" style="inset-inline-start:1rem">
    <TransitionGroup name="toast">
      <div
        v-for="t in state.items"
        :key="t.id"
        class="flex items-start gap-3 bg-surface dark:bg-slate-800 rounded-xl shadow-card-hover border border-slate-100 dark:border-white/10 p-3.5 pe-2.5"
      >
        <i :class="[ICONS[t.type] || ICONS.info, 'text-xl mt-0.5']"></i>
        <div class="flex-1 min-w-0">
          <p v-if="t.title" class="text-sm font-semibold text-ink dark:text-slate-100">{{ t.title }}</p>
          <p v-if="t.message" class="text-[13px] text-slate-500 dark:text-slate-400 leading-snug">{{ t.message }}</p>
        </div>
        <button @click="dismiss(t.id)" class="grid place-items-center w-7 h-7 rounded-lg text-slate-400 hover:bg-surface-muted dark:hover:bg-white/10 shrink-0">
          <i class="ri-close-line"></i>
        </button>
      </div>
    </TransitionGroup>
  </div>
</template>

<style scoped>
.toast-enter-active { transition: all .35s cubic-bezier(.16,1,.3,1); }
.toast-leave-active { transition: all .25s ease; position: absolute; }
.toast-enter-from { opacity: 0; transform: translateX(-24px); }
[dir="rtl"] .toast-enter-from { transform: translateX(24px); }
.toast-leave-to { opacity: 0; transform: scale(.95); }
@media (prefers-reduced-motion: reduce) {
  .toast-enter-active, .toast-leave-active { transition: opacity .2s ease; }
  .toast-enter-from { transform: none; }
}
</style>
