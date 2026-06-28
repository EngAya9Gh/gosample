<script setup>
/**
 * BaseModal — accessible dialog with backdrop + scale/fade transition.
 * Sizes: sm|md|lg|xl. v-model handles open state. Slots: default, footer.
 * confirmDialog helper variant via `tone` + simple confirm/cancel.
 */
import { watch } from 'vue';

const props = defineProps({
  modelValue: { type: Boolean, default: false },
  title:   { type: String, default: '' },
  icon:    { type: String, default: '' },
  size:    { type: String, default: 'md' },
  tone:    { type: String, default: 'primary' }, // header accent
});
const emit = defineEmits(['update:modelValue']);

const SIZES = { sm: 'max-w-sm', md: 'max-w-lg', lg: 'max-w-2xl', xl: 'max-w-4xl' };
const TONES = {
  primary: 'text-primary-700 bg-primary-50 dark:bg-primary-500/15 dark:text-primary-300',
  danger:  'text-danger bg-danger/10',
  success: 'text-success bg-success/10',
};

function close() { emit('update:modelValue', false); }

watch(() => props.modelValue, (open) => {
  if (typeof document !== 'undefined')
    document.body.style.overflow = open ? 'hidden' : '';
});
</script>

<template>
  <Transition name="modal">
    <div v-if="modelValue" class="fixed inset-0 z-[100] grid place-items-center p-4" @keydown.esc="close">
      <!-- backdrop -->
      <div class="absolute inset-0 bg-slate-900/45 backdrop-blur-sm" @click="close"></div>

      <!-- panel -->
      <div
        class="relative w-full bg-surface dark:bg-slate-800 rounded-2xl shadow-2xl border border-slate-100 dark:border-white/10 flex flex-col max-h-[88vh] modal-panel"
        :class="SIZES[size]"
        role="dialog" aria-modal="true"
      >
        <header v-if="title || icon" class="flex items-center gap-3 px-5 py-4 border-b border-slate-100 dark:border-white/5">
          <span v-if="icon" class="grid place-items-center w-9 h-9 rounded-xl shrink-0" :class="TONES[tone]">
            <i :class="[icon, 'text-lg']"></i>
          </span>
          <h3 class="font-semibold text-ink dark:text-slate-100 flex-1 truncate">{{ title }}</h3>
          <button @click="close" class="grid place-items-center w-8 h-8 rounded-lg text-slate-400 hover:bg-surface-muted dark:hover:bg-white/10 transition">
            <i class="ri-close-line text-lg"></i>
          </button>
        </header>

        <div class="p-5 overflow-y-auto"><slot></slot></div>

        <footer v-if="$slots.footer" class="flex items-center justify-end gap-2 px-5 py-4 border-t border-slate-100 dark:border-white/5">
          <slot name="footer"></slot>
        </footer>
      </div>
    </div>
  </Transition>
</template>

<style scoped>
.modal-enter-active, .modal-leave-active { transition: opacity .25s ease; }
.modal-enter-from, .modal-leave-to { opacity: 0; }
.modal-enter-active .modal-panel { transition: transform .3s cubic-bezier(.16,1,.3,1); }
.modal-enter-from .modal-panel { transform: translateY(12px) scale(.97); }
@media (prefers-reduced-motion: reduce) {
  .modal-enter-active, .modal-leave-active, .modal-enter-active .modal-panel { transition: none; }
}
</style>
