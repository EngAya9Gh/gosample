<script setup>
/**
 * Breadcrumb / PageHeader — breadcrumb trail (top) + large page title (below)
 * + optional actions slot (end-aligned).
 * trail = [{ label, href? }]; the LAST item renders as the current page (teal accent).
 * Matches the Analytics dashboard header style across every page.
 */
defineProps({
  title: { type: String, required: true },
  trail: { type: Array, default: () => [] }, // [{ label, href? }]
});
</script>

<template>
  <div class="flex flex-wrap items-end justify-between gap-3 mb-5">
    <div>
      <nav v-if="trail.length" class="flex items-center gap-2 text-[15px] mb-1.5">
        <template v-for="(c, i) in trail" :key="i">
          <template v-if="i > 0">
            <i class="ri-arrow-left-s-line rtl:hidden text-slate-400 dark:text-slate-500"></i>
            <i class="ri-arrow-right-s-line hidden rtl:inline text-slate-400 dark:text-slate-500"></i>
          </template>
          <a v-if="c.href" :href="c.href"
            class="text-slate-500 dark:text-slate-400 hover:text-primary-600 dark:hover:text-primary-300 font-medium transition">{{ c.label }}</a>
          <span v-else-if="i === trail.length - 1" class="text-primary-600 dark:text-primary-300 font-semibold">{{ c.label }}</span>
          <span v-else class="text-slate-500 dark:text-slate-400 font-medium">{{ c.label }}</span>
        </template>
      </nav>
      <h1 class="text-3xl font-extrabold text-ink dark:text-slate-50 tracking-tight">{{ title }}</h1>
    </div>
    <div class="flex flex-wrap items-end gap-2"><slot name="actions"></slot></div>
  </div>
</template>
