<script setup>
/**
 * BaseAvatar — deterministic initials avatar with gradient by name hash.
 * For drivers/users. Pass `name`; optional `src` overrides with a photo.
 */
import { computed } from 'vue';

const props = defineProps({
  name: { type: String, default: '' },
  src:  { type: String, default: '' },
  size: { type: [Number, String], default: 40 },
});

const initials = computed(() => {
  const parts = (props.name || '').trim().split(/\s+/).filter(Boolean);
  if (!parts.length) return '–';
  return (parts[0][0] + (parts[1]?.[0] || '')).toUpperCase();
});

// 6 on-brand gradient pairs; pick deterministically.
const GRADS = [
  'from-primary-500 to-primary-700',
  'from-info to-secondary',
  'from-success to-primary-600',
  'from-danger to-primary-700',
  'from-amber-400 to-primary-600',
  'from-secondary to-primary-700',
];
const grad = computed(() => {
  let h = 0;
  for (const c of (props.name || 'x')) h = (h * 31 + c.charCodeAt(0)) >>> 0;
  return GRADS[h % GRADS.length];
});

const dim = computed(() => `${parseInt(props.size)}px`);
const fz = computed(() => `${Math.max(11, parseInt(props.size) * 0.38)}px`);
</script>

<template>
  <span
    class="inline-grid place-items-center rounded-full text-white font-semibold shrink-0 shadow-sm ring-2 ring-white/70 dark:ring-white/10 overflow-hidden bg-gradient-to-br"
    :class="grad"
    :style="{ width: dim, height: dim, fontSize: fz }"
  >
    <img v-if="src" :src="src" :alt="name" class="w-full h-full object-cover" />
    <template v-else>{{ initials }}</template>
  </span>
</template>
