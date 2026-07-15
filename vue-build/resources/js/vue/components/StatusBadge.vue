<script setup>
/**
 * StatusBadge — color-coded pill with optional glowing dot.
 * Pass a known `status` key (task/sample/enabled) OR a raw `tone`.
 */
import { computed } from 'vue';

const props = defineProps({
  status: { type: String, default: '' }, // NEW|COLLECTED|IN_CONTAINER|OUT_CONTAINER|CLOSED|NO_SAMPLES|ENABLED|DISABLED|LOST|RECEIVED|PENDING
  label:  { type: String, default: '' },
  dot:    { type: Boolean, default: true },
});

// token-driven; bg uses /10 tint, text uses solid, dot solid.
const MAP = {
  NEW:           ['bg-status-new/10',       'text-status-new',       'bg-status-new'],
  COLLECTED:     ['bg-status-collected/10', 'text-status-collected', 'bg-status-collected'],
  IN_CONTAINER:  ['bg-status-container/10', 'text-status-container', 'bg-status-container'],
  OUT_CONTAINER: ['bg-status-container/10', 'text-status-container', 'bg-status-container'],
  CLOSED:        ['bg-status-closed/10',    'text-status-closed',    'bg-status-closed'],
  NO_SAMPLES:    ['bg-status-none/15',      'text-status-none',      'bg-status-none'],
  ENABLED:       ['bg-status-closed/10',    'text-status-closed',    'bg-status-closed'],
  RECEIVED:      ['bg-success/10',          'text-success',          'bg-success'],
  PENDING:       ['bg-warning/15',          'text-amber-600',        'bg-warning'],
  DISABLED:      ['bg-status-lost/10',      'text-status-lost',      'bg-status-lost'],
  LOST:          ['bg-status-lost/10',      'text-status-lost',      'bg-status-lost'],
};
const FALLBACK = ['bg-slate-100', 'text-slate-600', 'bg-slate-400'];

const tone = computed(() => MAP[props.status?.toUpperCase()] || FALLBACK);
const text = computed(() => props.label || props.status?.replace(/_/g, ' '));
</script>

<template>
  <span
    class="inline-flex items-center gap-1.5 ps-2 pe-2.5 h-6 rounded-full text-[11.5px] font-semibold tracking-wide whitespace-nowrap"
    :class="[tone[0], tone[1]]"
  >
    <span v-if="dot" class="w-1.5 h-1.5 rounded-full animate-pulse-ring" :class="tone[2]"></span>
    {{ text }}
  </span>
</template>
