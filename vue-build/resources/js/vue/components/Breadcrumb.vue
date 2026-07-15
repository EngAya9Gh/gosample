<script setup>
/**
 * Breadcrumb / PageHeader — title + trail + optional page actions slot.
 */
defineProps({
  title: { type: String, required: true },
  trail: { type: Array, default: () => [] }, // [{ label, href? }]
});
</script>

<template>
  <div class="flex flex-wrap items-end justify-between gap-3 mb-5">
    <div>
      <h1 class="text-xl font-bold text-ink dark:text-slate-50 tracking-tight">{{ title }}</h1>
      <nav class="flex items-center gap-1.5 mt-1 text-[13px] text-slate-400">
        <a href="#" class="hover:text-primary-600"><i class="ri-home-4-line"></i></a>
        <template v-for="(c, i) in trail" :key="i">
          <i class="ri-arrow-left-s-line rtl:hidden text-slate-300"></i>
          <i class="ri-arrow-right-s-line hidden rtl:inline text-slate-300"></i>
          <a v-if="c.href" :href="c.href" class="hover:text-primary-600">{{ c.label }}</a>
          <span v-else class="text-slate-500 dark:text-slate-300 font-medium">{{ c.label }}</span>
        </template>
      </nav>
    </div>
    <div class="flex items-center gap-2"><slot name="actions"></slot></div>
  </div>
</template>
