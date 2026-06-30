<script setup>
/**
 * FilterBar — the ONE filter component (replaces the two competing styles).
 * Card above tables holding filter controls in a responsive grid, with a
 * Search (loading-aware) + Reset action row. Slot-driven so each screen
 * drops in its own FormInput/FormSelect/date controls.
 */
defineProps({
  loading:  { type: Boolean, default: false },
  title:    { type: String, default: 'Filters' },
  subtitle: { type: String, default: '' },
});
defineEmits(['search', 'reset']);
</script>

<template>
  <div class="bg-surface dark:bg-surface-dark-card rounded-2xl shadow-card border border-slate-100 dark:border-white/5 p-4 sm:p-5 mb-5">
    <div class="flex items-center gap-2 mb-4">
      <i class="ri-equalizer-line text-primary-600 text-lg"></i>
      <h3 class="text-sm font-extrabold text-ink dark:text-slate-100">{{ title }}</h3>
      <span v-if="subtitle" class="text-xs text-slate-400 dark:text-slate-500">{{ subtitle }}</span>
      <slot name="title-extra"></slot>
    </div>

    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-x-4 gap-y-3.5">
      <slot></slot>
    </div>

    <div class="flex flex-wrap items-center gap-2 mt-4 pt-4 border-t border-slate-100 dark:border-white/5">
      <div class="mr-auto flex flex-wrap items-center gap-2"><slot name="actions-extra"></slot></div>
      <BaseButton variant="light" icon="ri-refresh-line" @click="$emit('reset')">Reset</BaseButton>
      <BaseButton variant="primary" icon="ri-search-line" :loading="loading" @click="$emit('search')">Search</BaseButton>
    </div>
  </div>
</template>

<script>
import BaseButton from './BaseButton.vue';
export default { components: { BaseButton } };
</script>
