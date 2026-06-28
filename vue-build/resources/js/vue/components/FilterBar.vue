<script setup>
/**
 * FilterBar — the ONE filter component (replaces the two competing styles).
 * Card above tables holding filter controls in a responsive grid, with a
 * Search (loading-aware) + Reset action row. Slot-driven so each screen
 * drops in its own FormInput/FormSelect/date controls.
 */
defineProps({
  loading: { type: Boolean, default: false },
  title:   { type: String, default: 'Filters' },
});
defineEmits(['search', 'reset']);
</script>

<template>
  <div class="bg-surface dark:bg-slate-800/60 rounded-2xl shadow-card border border-slate-100 dark:border-white/5 p-4 sm:p-5 mb-5">
    <div class="flex items-center gap-2 mb-4">
      <i class="ri-filter-3-line text-primary-600"></i>
      <h3 class="text-sm font-semibold text-ink dark:text-slate-100">{{ title }}</h3>
      <slot name="title-extra"></slot>
    </div>

    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-x-4 gap-y-3.5">
      <slot></slot>
    </div>

    <div class="flex flex-wrap items-center justify-end gap-2 mt-4 pt-4 border-t border-slate-100 dark:border-white/5">
      <slot name="actions-extra"></slot>
      <BaseButton variant="light" icon="ri-refresh-line" @click="$emit('reset')">Reset</BaseButton>
      <BaseButton variant="primary" icon="ri-search-line" :loading="loading" @click="$emit('search')">Search</BaseButton>
    </div>
  </div>
</template>

<script>
import BaseButton from './BaseButton.vue';
export default { components: { BaseButton } };
</script>
