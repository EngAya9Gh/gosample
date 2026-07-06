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
  'from-emerald-500 to-emerald-600',
  'from-indigo-500 to-indigo-600',
  'from-amber-500 to-amber-600',
  'from-pink-500 to-pink-600',
  'from-violet-500 to-violet-600',
  'from-cyan-500 to-cyan-600',
];
const grad = computed(() => {
  let h = 0;
  for (const c of (props.name || 'x')) h = (h * 31 + c.charCodeAt(0)) >>> 0;
  return GRADS[h % GRADS.length];
});

const dim = computed(() => `${parseInt(props.size)}px`);
const fz = computed(() => {
  if (parseInt(props.size) === 26) return '10px';
  if (parseInt(props.size) <= 26) return '10px';
  return `${Math.max(10, parseInt(props.size) * 0.4)}px`;
});
</script>

<template>
  <span
    :class="[
      'inline-flex items-center justify-center shrink-0 rounded-full text-white font-bold select-none overflow-hidden bg-gradient-to-br',
      !src ? grad : 'bg-surface-muted',
    ]"
    :style="{ width: dim, height: dim, fontSize: fz }"
  >
    <img v-if="src" :src="src" :alt="name" class="w-full h-full object-cover" />
    <template v-else>{{ initials }}</template>
  </span>
</template>
