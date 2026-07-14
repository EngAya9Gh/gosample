<script setup>
/**
 * StatCard — KPI card matching MTC design pixel-perfectly.
 * `featured` = filled teal-gradient (Active Tasks card).
 * non-featured = white card with colored top accent bar.
 */
import { computed } from 'vue';
import { useCounter } from '../composables/useCounter';

const props = defineProps({
  label:    { type: String, required: true },
  value:    { type: Number, default: 0 },
  prefix:   { type: String, default: '' },
  suffix:   { type: String, default: '' },
  icon:     { type: String, default: 'ri-bar-chart-2-line' },
  delta:    { type: Number, default: null },
  tone:     { type: String, default: 'primary' }, // primary|success|info|warning|danger
  href:     { type: String, default: '' },
  featured: { type: Boolean, default: false },
});

const { display } = useCounter(() => props.value);

// Icon background + text color per tone
const ICON_TONES = {
  primary: 'bg-primary-50 text-primary-700 dark:bg-primary-500/15 dark:text-primary-300',
  success: 'bg-[#0ab39c]/13 text-[#0ab39c]',
  info:    'bg-[#299cdb]/13 text-[#299cdb]',
  warning: 'bg-[#f7b84b]/16 text-[#e89e2b]',
  danger:  'bg-[#dc2626]/13 text-[#dc2626]',
};
// Top accent bar color per tone
const ACCENT_COLOR = {
  primary: '#0d9488', success: '#0ab39c', info: '#299cdb', warning: '#f7b84b', danger: '#dc2626',
};

const iconCls = computed(() => props.featured ? 'bg-white/18 text-white' : (ICON_TONES[props.tone] || ICON_TONES.primary));
const accentColor = computed(() => ACCENT_COLOR[props.tone] || ACCENT_COLOR.primary);
const up = computed(() => (props.delta ?? 0) >= 0);

const cardCls = computed(() => props.featured
  ? 'bg-gradient-to-br from-[#005D69] to-[#0d9488] text-white border-transparent shadow-lg'
  : 'bg-surface dark:bg-surface-dark-card border-slate-100 dark:border-white/5');
</script>

<template>
  <component
    :is="href ? 'a' : 'div'"
    :href="href || undefined"
    class="group relative overflow-hidden block rounded-2xl border transition-all duration-300 hover:-translate-y-0.5 hover:shadow-md motion-reduce:hover:translate-y-0"
    :class="cardCls"
    style="padding: 18px;"
  >
    <!-- colored right accent bar (non-featured only) — 4px wide, full height -->
    <span
      v-if="!featured"
      class="absolute top-0 bottom-0 right-0 w-1 rounded-r-2xl"
      :style="{ background: accentColor }"
    ></span>

    <!-- Top row: icon + delta pill -->
    <div class="flex items-center justify-between mb-[14px]">
      <div
        class="grid place-items-center rounded-[11px] transition-transform duration-200 group-hover:scale-110 motion-reduce:group-hover:scale-100"
        :class="iconCls"
        style="width:42px; height:42px;"
      >
        <i :class="[icon, 'text-[21px]']"></i>
      </div>
      <span
        v-if="delta !== null"
        class="inline-flex items-center gap-0.5 text-[11.5px] font-bold px-2 py-0.5 rounded-[7px]"
        :class="featured
          ? 'bg-white/20 text-white'
          : (up
            ? 'bg-[#0ab39c]/12 text-[#0ab39c]'
            : 'bg-[#dc2626]/10 text-[#dc2626]')"
      >
        {{ up ? '▲' : '▼' }} {{ Math.abs(delta) }}%
      </span>
    </div>

    <!-- Number -->
    <div
      class="text-[27px] font-[800] tracking-[-0.02em] tabular-nums leading-none"
      :class="featured ? 'text-white' : 'text-ink dark:text-slate-50'"
    >
      {{ prefix }}{{ display.toLocaleString('en-US') }}{{ suffix }}
    </div>

    <!-- Label -->
    <div
      class="text-[12.5px] mt-[2px]"
      :class="featured ? 'text-white/85' : 'text-slate-500 dark:text-slate-400'"
    >
      {{ label }}
    </div>
  </component>
</template>
