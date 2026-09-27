<script setup>
/**
 * HighlightText — renders `text` with every occurrence of `term` shaded.
 * Used by DataTable cells and any list screen that wants to show WHY a row
 * matched the keyword the user searched for.
 */
import { computed } from 'vue';
import { highlightSegments } from '../utils/highlight';

const props = defineProps({
  text: { type: [String, Number, null], default: '' },
  term: { type: [String, Number, Array, null], default: '' },
});

const parts = computed(() => highlightSegments(props.text, props.term));
</script>

<template><template v-for="(p, i) in parts" :key="i"><mark v-if="p.hit" class="rounded-[3px] px-0.5 bg-primary-100 text-primary-800 dark:bg-primary-500/30 dark:text-primary-100 font-semibold">{{ p.text }}</mark><template v-else>{{ p.text }}</template></template></template>
