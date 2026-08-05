<script setup>
/**
 * RouteCell — the standard "From → To" table cell.
 * Origin on the first line, the connector arrow on its OWN line, destination
 * below — so long location names stay readable instead of colliding with the
 * arrow. Used by every list that has a From → To column so all of them match.
 */
defineProps({
  from: { type: String, default: '' },
  to:   { type: String, default: '' },
});
</script>

<template>
  <div dir="ltr" class="flex flex-col items-start gap-0.5 leading-snug">
    <span class="flex items-start gap-1.5">
      <i class="ri-map-pin-fill text-red-500 text-[11px] mt-[3px] shrink-0"></i>
      <span class="font-medium text-ink dark:text-slate-200" style="white-space:normal; word-break:break-word;">{{ from || '—' }}</span>
    </span>
    <i class="ri-arrow-right-line text-slate-400 dark:text-slate-500 text-[12px] ms-px"></i>
    <span class="flex items-start gap-1.5">
      <i class="ri-map-pin-fill text-green-500 text-[11px] mt-[3px] shrink-0"></i>
      <span class="font-medium text-ink dark:text-slate-200" style="white-space:normal; word-break:break-word;">{{ to || '—' }}</span>
    </span>
  </div>
</template>
