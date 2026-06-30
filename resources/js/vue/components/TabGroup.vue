<script setup>
/**
 * TabGroup — tabbed switcher. Two variants: 'pills' | 'underline'.
 * v-model:active holds the active tab key.
 * Pass `tabs` = [{ key, label, icon?, badge? }]
 */
defineProps({
  tabs:    { type: Array, required: true },
  active:  { type: String, required: true },
  variant: { type: String, default: 'underline' },
});
defineEmits(['update:active']);
</script>

<template>
  <div
    :class="variant === 'pills'
      ? 'inline-flex gap-1 p-1 rounded-xl bg-surface-muted dark:bg-white/5'
      : 'flex gap-1 border-b border-slate-200 dark:border-white/10'"
  >
    <button
      v-for="t in tabs"
      :key="t.key"
      type="button"
      @click="$emit('update:active', t.key)"
      class="relative inline-flex items-center gap-2 text-sm font-medium transition-all duration-200 whitespace-nowrap"
      :class="[
        variant === 'pills'
          ? ['px-3.5 h-9 rounded-lg',
             active === t.key
               ? 'bg-surface dark:bg-white/10 text-primary-700 dark:text-primary-300 shadow-sm'
               : 'text-slate-500 hover:text-ink dark:hover:text-slate-200']
          : ['px-1 pb-3 pt-1 -mb-px border-b-2',
             active === t.key
               ? 'border-primary-600 text-primary-700 dark:text-primary-300'
               : 'border-transparent text-slate-500 hover:text-ink dark:hover:text-slate-200'],
      ]"
    >
      <i v-if="t.icon" :class="t.icon"></i>
      {{ t.label }}
      <span v-if="t.badge != null" class="ms-1 inline-grid place-items-center min-w-5 h-5 px-1 rounded-full text-[11px] font-semibold bg-primary-100 text-primary-700 dark:bg-primary-500/20 dark:text-primary-300">
        {{ t.badge }}
      </span>
    </button>
  </div>
</template>
