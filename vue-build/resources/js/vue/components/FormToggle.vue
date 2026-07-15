<script setup>
/**
 * FormToggle — iOS-style switch (teal). v-model boolean.
 */
defineProps({
  modelValue: { type: Boolean, default: false },
  label: { type: String, default: '' },
  helper: { type: String, default: '' },
});
defineEmits(['update:modelValue']);
</script>

<template>
  <label class="flex items-center gap-3 cursor-pointer select-none">
    <button
      type="button" role="switch" :aria-checked="modelValue"
      @click="$emit('update:modelValue', !modelValue)"
      class="relative w-11 h-6 rounded-full transition-colors duration-200 shrink-0 focus:outline-none focus-visible:ring-2 focus-visible:ring-primary-500/50"
      :class="modelValue ? 'bg-primary-600' : 'bg-slate-300 dark:bg-white/15'"
    >
      <span
        class="absolute top-0.5 w-5 h-5 bg-white rounded-full shadow transition-transform duration-200"
        style="inset-inline-start: 2px"
        :class="modelValue ? 'translate-x-5 rtl:-translate-x-5' : ''"
      ></span>
    </button>
    <span v-if="label || helper">
      <span v-if="label" class="block text-sm font-medium text-ink dark:text-slate-200">{{ label }}</span>
      <span v-if="helper" class="block text-xs text-slate-400">{{ helper }}</span>
    </span>
  </label>
</template>
