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

// `group` lets the inner icon react to button hover (see iconAnim).
const base =
  'group inline-flex items-center justify-center gap-2 font-medium rounded-xl ' +
  'transition-all duration-200 active:scale-[.97] focus:outline-none ' +
  'focus-visible:ring-2 focus-visible:ring-primary-500/50 disabled:opacity-50 ' +
  'disabled:cursor-not-allowed motion-reduce:transition-none select-none';

// Per-icon hover animation: refresh/reset spins, search nudges + scales, others scale.
const iconAnim = computed(() => {
  const i = props.icon || '';
  if (/refresh|restart|reload|loop|recycle/.test(i)) return 'group-hover:rotate-180';
  if (/search/.test(i)) return 'group-hover:scale-125 group-hover:-rotate-12';
  if (/add|plus/.test(i)) return 'group-hover:scale-125 group-hover:rotate-90';
  return 'group-hover:scale-125';
});

const sizes = {
  sm: 'h-9 px-3 text-[13px]',
  md: 'h-10 px-4 text-sm',
  lg: 'h-11 px-5 text-[15px]',
};

const variants = {
  // Theme CTA: brand teal gradient (#0d9488 → #005D69, the dark brand core).
  primary: 'bg-gradient-to-br from-[#0d9488] to-[#005D69] text-white shadow-card hover:brightness-110 hover:shadow-card-hover',
  accent:  'bg-primary-500 text-white shadow-card hover:bg-primary-600',
  light:   'bg-surface-muted text-ink hover:bg-slate-200/70 dark:bg-white/10 dark:text-slate-100 dark:hover:bg-white/20',
  success: 'bg-success text-white hover:brightness-95',
  info:    'bg-info text-white hover:brightness-95',
  danger:  'bg-danger text-white hover:brightness-95',
  ghost:   'text-primary-700 hover:bg-primary-50 dark:text-primary-300 dark:hover:bg-white/10',
  // Soft file-export tints (PDF red, Excel green) — colored but on-theme.
  pdf:     'bg-red-50 text-red-600 hover:bg-red-100 dark:bg-red-500/10 dark:text-red-300 dark:hover:bg-red-500/20',
  excel:   'bg-green-50 text-green-700 hover:bg-green-100 dark:bg-green-500/10 dark:text-green-300 dark:hover:bg-green-500/20',
};

const cls = computed(() => [
  base, sizes[props.size], variants[props.variant], props.block ? 'w-full' : '',
]);
</script>

<template>
  <button :type="type" :disabled="disabled || loading" :class="cls">
    <i v-if="loading" class="ri-loader-4-line animate-spin text-base"></i>
    <i v-else-if="icon" :class="[icon, 'text-base transition-transform duration-300 ease-out motion-reduce:transition-none', iconAnim]"></i>
    <slot></slot>
  </button>
</template>
