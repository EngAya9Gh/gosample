<script setup>
/**
 * FormSelect — single or multi select with optional search + Select-All.
 * options = [{ value, label }]. v-model is value (single) or array (multi).
 */
import { ref, computed, watch, nextTick, onMounted, onBeforeUnmount } from 'vue';

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
  // Teleport the dropdown panel to <body> so it isn't clipped by an overflow
  // ancestor (e.g. a modal body). Use inside modals.
  floating:{ type: Boolean, default: false },
});
const emit = defineEmits(['update:modelValue']);

const open = ref(false);
const q = ref('');
const root = ref(null);
const trigger = ref(null);
const panel = ref(null);
const floatStyle = ref({});

// Position the teleported panel under the trigger (fixed → viewport coords).
// Flips ABOVE the trigger when there isn't enough room below (e.g. the last
// field of a tall modal) — otherwise the panel runs off-screen.
function positionPanel() {
  const t = trigger.value;
  if (!t) return;
  const r = t.getBoundingClientRect();
  const vh = window.innerHeight;
  const panelH = panel.value?.offsetHeight || 300;
  const spaceBelow = vh - r.bottom - 12;
  const openUp = spaceBelow < Math.min(panelH, 300) && r.top > spaceBelow;
  floatStyle.value = openUp
    ? { bottom: `${vh - r.top + 6}px`, left: `${r.left}px`, width: `${r.width}px` }
    : { top: `${r.bottom + 6}px`, left: `${r.left}px`, width: `${r.width}px` };
}
function onReflow() { if (open.value && props.floating) positionPanel(); }
watch(open, (v) => { if (v && props.floating) nextTick(positionPanel); });

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

function onDoc(e) {
  // Keep open when the click is inside the trigger OR the (possibly teleported) panel.
  const inRoot = root.value && root.value.contains(e.target);
  const inPanel = panel.value && panel.value.contains(e.target);
  if (!inRoot && !inPanel) open.value = false;
}
onMounted(() => {
  document.addEventListener('click', onDoc);
  window.addEventListener('resize', onReflow);
  window.addEventListener('scroll', onReflow, true); // capture → catches modal-body scroll
});
onBeforeUnmount(() => {
  document.removeEventListener('click', onDoc);
  window.removeEventListener('resize', onReflow);
  window.removeEventListener('scroll', onReflow, true);
});
</script>

<template>
  <div class="w-full" ref="root">
    <label v-if="label" class="block text-[13px] font-bold text-slate-800 dark:text-slate-200 mb-1.5">
      {{ label }} <span v-if="required" class="text-danger">*</span>
    </label>

    <button
      ref="trigger"
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

    <!-- panel — teleported to <body> when floating so a modal's overflow can't clip it -->
    <div class="relative">
      <Teleport to="body" :disabled="!floating">
      <div v-if="open" ref="panel"
        :style="floating ? floatStyle : undefined"
        class="bg-surface dark:bg-surface-dark-solid rounded-xl shadow-card-hover border border-slate-100 dark:border-white/10 p-1.5 animate-fade-in-up"
        :class="floating ? 'fixed z-[200]' : 'absolute z-30 mt-1.5 w-full'">
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
            class="flex items-center gap-2.5 px-2.5 min-h-[36px] py-2 rounded-lg text-sm cursor-pointer text-ink dark:text-slate-200 hover:bg-primary-50 dark:hover:bg-white/5"
          >
            <span class="grid place-items-center w-4 h-4 shrink-0 rounded border transition" :class="isSel(o.value) ? 'bg-primary-600 border-primary-600 text-white' : 'border-slate-300 dark:border-white/10'">
              <i v-if="isSel(o.value)" class="ri-check-line text-xs"></i>
            </span>
            <span class="flex-1 leading-tight break-words">{{ o.label }}</span>
          </li>
          <li v-if="!filtered.length" class="px-2.5 py-3 text-sm text-slate-400 text-center">No matches</li>
        </ul>
      </div>
      </Teleport>
    </div>

    <p v-if="error" class="flex items-center gap-1 text-xs text-danger mt-1.5"><i class="ri-error-warning-line"></i>{{ error }}</p>
    <p v-else-if="helper" class="text-xs text-slate-400 mt-1.5">{{ helper }}</p>
  </div>
</template>
