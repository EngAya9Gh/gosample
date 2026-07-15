<script setup>
/** Footer — slim app footer. */
const year = new Date().getFullYear();
</script>

<template>
  <footer class="flex flex-wrap items-center justify-between gap-2 px-6 py-4 text-[13px] text-slate-400 border-t border-slate-200/70 dark:border-white/5">
    <span>© {{ year }} MTC.</span>
    <span>Design &amp; Develop by <span class="text-primary-600 dark:text-primary-400 font-medium">MTC team</span></span>
  </footer>
</template>
