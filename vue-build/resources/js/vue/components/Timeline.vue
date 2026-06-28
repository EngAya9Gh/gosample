<script setup>
/**
 * Timeline — vertical step timeline for task lifecycle / driver routes.
 * steps = [{ title, time?, meta?, state: 'done'|'current'|'pending', index? }]
 */
defineProps({
  steps: { type: Array, required: true },
});
</script>

<template>
  <ol class="relative ps-2">
    <li v-for="(s, i) in steps" :key="i" class="relative flex gap-4 pb-6 last:pb-0">
      <!-- connector -->
      <span
        v-if="i < steps.length - 1"
        class="absolute top-7 w-px h-[calc(100%-1rem)] bg-slate-200 dark:bg-white/10"
        style="inset-inline-start: 13px"
      ></span>

      <!-- node -->
      <span
        class="relative z-10 grid place-items-center w-7 h-7 rounded-full shrink-0 text-xs font-bold ring-4 ring-surface dark:ring-slate-800"
        :class="{
          'bg-success text-white': s.state === 'done',
          'bg-primary-600 text-white animate-pulse-ring': s.state === 'current',
          'bg-surface-muted text-slate-400 dark:bg-white/10': s.state === 'pending',
        }"
      >
        <i v-if="s.state === 'done'" class="ri-check-line"></i>
        <i v-else-if="s.state === 'current'" class="ri-map-pin-2-fill"></i>
        <template v-else>{{ s.index ?? i + 1 }}</template>
      </span>

      <!-- body -->
      <div class="flex-1 -mt-0.5 min-w-0">
        <div class="flex items-center justify-between gap-2 flex-wrap">
          <p class="font-medium text-ink dark:text-slate-100 text-sm" :class="s.state === 'done' ? 'line-through decoration-slate-300' : ''">
            {{ s.title }}
          </p>
          <span v-if="s.meta" class="inline-flex items-center gap-1 text-[11px] font-semibold text-primary-700 bg-primary-50 dark:bg-primary-500/15 dark:text-primary-300 px-2 h-5 rounded-full">
            {{ s.meta }}
          </span>
        </div>
        <p v-if="s.time" class="text-xs text-slate-500 dark:text-slate-400 mt-0.5 ltr-data">{{ s.time }}</p>
        <slot name="extra" :step="s"></slot>
      </div>
    </li>
  </ol>
</template>

<style scoped>
.ltr-data { direction: ltr; unicode-bidi: plaintext; }
</style>
