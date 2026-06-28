<script setup>
/**
 * BaseButton — the single button system.
 * variants: primary | accent | light | success | info | danger | ghost
 * sizes: sm | md | lg   |  icon-only via `icon` + no slot
 * loading shows a spinner and disables the button.
 */
import { computed } from 'vue';

const props = defineProps({
  variant: { type: String, default: 'primary' },
  size:    { type: String, default: 'md' },
  icon:    { type: String, default: '' },     // ri-* class
  loading: { type: Boolean, default: false },
  disabled:{ type: Boolean, default: false },
  block:   { type: Boolean, default: false },
  type:    { type: String, default: 'button' },
});

const base =
  'inline-flex items-center justify-center gap-2 font-medium rounded-xl ' +
  'transition-all duration-200 active:scale-[.97] focus:outline-none ' +
  'focus-visible:ring-2 focus-visible:ring-primary-500/50 disabled:opacity-50 ' +
  'disabled:cursor-not-allowed motion-reduce:transition-none select-none';

const sizes = {
  sm: 'h-9 px-3 text-[13px]',
  md: 'h-10 px-4 text-sm',
  lg: 'h-11 px-5 text-[15px]',
};

const variants = {
  primary: 'bg-primary-700 text-white shadow-card hover:bg-primary-800 hover:shadow-card-hover',
  accent:  'bg-primary-500 text-white shadow-card hover:bg-primary-600',
  light:   'bg-surface-muted text-ink hover:bg-slate-200/70 dark:bg-white/10 dark:text-slate-100 dark:hover:bg-white/20',
  success: 'bg-success text-white hover:brightness-95',
  info:    'bg-info text-white hover:brightness-95',
  danger:  'bg-danger text-white hover:brightness-95',
  ghost:   'text-primary-700 hover:bg-primary-50 dark:text-primary-300 dark:hover:bg-white/10',
};

const cls = computed(() => [
  base, sizes[props.size], variants[props.variant], props.block ? 'w-full' : '',
]);
</script>

<template>
  <button :type="type" :disabled="disabled || loading" :class="cls">
    <i v-if="loading" class="ri-loader-4-line animate-spin text-base"></i>
    <i v-else-if="icon" :class="[icon, 'text-base']"></i>
    <slot></slot>
  </button>
</template>
