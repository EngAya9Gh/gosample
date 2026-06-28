<script setup>
/**
 * StatCard — KPI card with icon, animated counter, delta % and link.
 */
import { computed } from 'vue';
import { useCounter } from '../composables/useCounter';

const props = defineProps({
  label:  { type: String, required: true },
  value:  { type: Number, default: 0 },
  prefix: { type: String, default: '' },
  suffix: { type: String, default: '' },
  icon:   { type: String, default: 'ri-bar-chart-2-line' },
  delta:  { type: Number, default: null },   // % change, null = hide
  tone:   { type: String, default: 'primary' }, // primary|success|info|warning|danger
  href:   { type: String, default: '' },
});

const { display } = useCounter(() => props.value);

const TONES = {
  primary: 'bg-primary-50 text-primary-700 dark:bg-primary-500/15 dark:text-primary-300',
  success: 'bg-success/10 text-success',
  info:    'bg-info/10 text-info',
  warning: 'bg-warning/15 text-amber-600',
  danger:  'bg-danger/10 text-danger',
};
const iconTone = computed(() => TONES[props.tone] || TONES.primary);
const up = computed(() => (props.delta ?? 0) >= 0);
</script>

<template>
  <component
    :is="href ? 'a' : 'div'"
    :href="href || undefined"
    class="group block bg-surface dark:bg-slate-800/60 rounded-2xl shadow-card border border-slate-100 dark:border-white/5 p-5 transition-all duration-300 hover:shadow-card-hover hover:-translate-y-1 motion-reduce:hover:translate-y-0"
  >
    <div class="flex items-start justify-between">
      <div class="grid place-items-center w-11 h-11 rounded-xl transition-transform group-hover:scale-110 motion-reduce:group-hover:scale-100" :class="iconTone">
        <i :class="[icon, 'text-xl']"></i>
      </div>
      <span
        v-if="delta !== null"
        class="inline-flex items-center gap-1 text-xs font-semibold px-2 h-6 rounded-full"
        :class="up ? 'text-success bg-success/10' : 'text-danger bg-danger/10'"
      >
        <i :class="up ? 'ri-arrow-up-line' : 'ri-arrow-down-line'"></i>{{ Math.abs(delta) }}%
      </span>
    </div>
    <div class="mt-4">
      <div class="text-3xl font-bold text-ink dark:text-slate-50 tabular-nums tracking-tight">
        {{ prefix }}{{ display.toLocaleString() }}{{ suffix }}
      </div>
      <div class="text-sm text-slate-500 dark:text-slate-400 mt-0.5">{{ label }}</div>
    </div>
  </component>
</template>
