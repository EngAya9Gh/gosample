<script setup>
/**
 * FormSelect — single or multi select with optional search + Select-All.
 * options = [{ value, label }]. v-model is value (single) or array (multi).
 */
import { ref, computed } from 'vue';

const props = defineProps({
  modelValue: { type: [String, Number, Array, null], default: '' },
  label:   { type: String, default: '' },
  options: { type: Array, default: () => [] },
  multiple:{ type: Boolean, default: false },
  searchable: { type: Boolean, default: true },
  placeholder: { type: String, default: 'Select…' },
  icon:    { type: String, default: '' },              // optional leading ri-* icon in the trigger
  iconClass: { type: String, default: 'text-slate-400' },
  helper:  { type: String, default: '' },
  error:   { type: String, default: '' },
  required:{ type: Boolean, default: false },
});
const emit = defineEmits(['update:modelValue']);

const open = ref(false);
const q = ref('');
const root = ref(null);

const filtered = computed(() =>
  props.options.filter((o) => o.label.toLowerCase().includes(q.value.toLowerCase()))
);
const selectedArr = computed(() =>
  props.multiple ? (Array.isArray(props.modelValue) ? props.modelValue : []) : []
);
const singleLabel = computed(() =>
  props.options.find((o) => o.value === props.modelValue)?.label || ''
);

function isSel(v) { return props.multiple ? selectedArr.value.includes(v) : props.modelValue === v; }
function pick(v) {
  if (props.multiple) {
    const set = new Set(selectedArr.value);
    set.has(v) ? set.delete(v) : set.add(v);
    emit('update:modelValue', [...set]);
  } else {
    emit('update:modelValue', v);
    open.value = false;
  }
}
function allOn() { emit('update:modelValue', props.options.map((o) => o.value)); }
function allOff() { emit('update:modelValue', []); }

function onDoc(e) { if (root.value && !root.value.contains(e.target)) open.value = false; }
import { onMounted, onBeforeUnmount } from 'vue';
onMounted(() => document.addEventListener('click', onDoc));
onBeforeUnmount(() => document.removeEventListener('click', onDoc));
</script>

<template>
  <div class="w-full" ref="root">
    <label v-if="label" class="block text-[13px] font-bold text-slate-800 dark:text-slate-200 mb-1.5">
      {{ label }} <span v-if="required" class="text-danger">*</span>
    </label>

    <button
      type="button" @click="open = !open"
      class="w-full min-h-11 px-3.5 py-1.5 flex items-center gap-1.5 flex-wrap text-start bg-surface dark:bg-white/5 border rounded-xl text-sm transition focus:outline-none focus:ring-2 focus:ring-primary-500/40"
      :class="error ? 'border-danger/60' : 'border-slate-200 dark:border-white/10'"
    >
      <i v-if="icon" :class="[icon, iconClass]" class="text-[15px] shrink-0"></i>
      <template v-if="multiple && selectedArr.length">
        <span v-for="v in selectedArr" :key="v" class="inline-flex items-center gap-1 ps-2 pe-1 h-6 rounded-md bg-primary-50 text-primary-700 dark:bg-primary-500/15 dark:text-primary-300 text-xs font-medium">
          {{ options.find((o) => o.value === v)?.label }}
          <i class="ri-close-line hover:text-danger" @click.stop="pick(v)"></i>
        </span>
      </template>
      <span v-else-if="!multiple && singleLabel" class="text-ink dark:text-slate-100">{{ singleLabel }}</span>
      <span v-else class="text-slate-400">{{ placeholder }}</span>
      <i class="ri-arrow-down-s-line ms-auto text-slate-400 transition-transform" :class="open ? 'rotate-180' : ''"></i>
    </button>

    <!-- panel -->
    <div v-if="open" class="relative">
      <div class="absolute z-30 mt-1.5 w-full bg-surface dark:bg-surface-dark-solid rounded-xl shadow-card-hover border border-slate-100 dark:border-white/10 p-1.5 animate-fade-in-up">
        <div v-if="searchable" class="relative mb-1.5">
          <i class="ri-search-line absolute top-1/2 -translate-y-1/2 inset-inline-start-2.5 text-slate-400 text-sm" style="inset-inline-start:.625rem"></i>
          <input v-model="q" placeholder="Search…" class="w-full h-9 ps-8 pe-3 text-sm bg-surface-muted dark:bg-white/5 rounded-lg focus:outline-none focus:ring-2 focus:ring-primary-500/30" />
        </div>
        <div v-if="multiple" class="flex gap-1 px-1 pb-1.5 mb-1 border-b border-slate-100 dark:border-white/5">
          <button type="button" @click="allOn" class="text-xs font-medium text-primary-600 hover:underline">Select all</button>
          <span class="text-slate-300">·</span>
          <button type="button" @click="allOff" class="text-xs font-medium text-slate-500 hover:underline">Clear</button>
        </div>
        <ul class="max-h-56 overflow-y-auto">
          <li
            v-for="o in filtered" :key="o.value"
            @click="pick(o.value)"
            class="flex items-center gap-2.5 px-2.5 h-9 rounded-lg text-sm cursor-pointer text-ink dark:text-slate-200 hover:bg-primary-50 dark:hover:bg-white/5"
          >
            <span class="grid place-items-center w-4 h-4 rounded border transition" :class="isSel(o.value) ? 'bg-primary-600 border-primary-600 text-white' : 'border-slate-300 dark:border-white/10'">
              <i v-if="isSel(o.value)" class="ri-check-line text-xs"></i>
            </span>
            {{ o.label }}
          </li>
          <li v-if="!filtered.length" class="px-2.5 py-3 text-sm text-slate-400 text-center">No matches</li>
        </ul>
      </div>
    </div>

    <p v-if="error" class="flex items-center gap-1 text-xs text-danger mt-1.5"><i class="ri-error-warning-line"></i>{{ error }}</p>
    <p v-else-if="helper" class="text-xs text-slate-400 mt-1.5">{{ helper }}</p>
  </div>
</template>
