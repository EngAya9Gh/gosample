<script setup>
/**
 * BaseCard — content card with optional header/footer slots.
 */
defineProps({
  title:    { type: String, default: '' },
  subtitle: { type: String, default: '' },
  icon:     { type: String, default: '' },
  padded:   { type: Boolean, default: true },
  hover:    { type: Boolean, default: false },
});
</script>

<template>
  <section
    class="bg-surface dark:bg-surface-dark-card rounded-2xl shadow-card border border-slate-100 dark:border-white/5 transition-all duration-300 print:border-none print:shadow-none"
    :class="hover ? 'hover:shadow-card-hover hover:-translate-y-0.5 motion-reduce:hover:translate-y-0' : ''"
  >
    <header
      v-if="title || $slots.header"
      class="flex items-center justify-between gap-3 px-5 py-4 border-b border-slate-100 dark:border-white/5"
    >
      <slot name="header">
        <div class="flex items-center gap-2.5 min-w-0">
          <span v-if="icon" class="grid place-items-center w-9 h-9 rounded-xl bg-gradient-to-br from-[#0ea5a4] to-[#005D69] text-white shadow-card shrink-0">
            <i :class="[icon, 'text-lg']"></i>
          </span>
          <div class="min-w-0">
            <h3 class="font-bold text-ink dark:text-slate-100 text-[15px] truncate">{{ title }}</h3>
            <p v-if="subtitle" class="text-xs text-slate-500 dark:text-slate-400 truncate">{{ subtitle }}</p>
          </div>
        </div>
      </slot>
      <slot name="actions"></slot>
    </header>

    <div :class="padded ? 'p-5' : ''">
      <slot></slot>
    </div>

    <footer v-if="$slots.footer" class="px-5 py-3.5 border-t border-slate-100 dark:border-white/5">
      <slot name="footer"></slot>
    </footer>
  </section>
</template>
