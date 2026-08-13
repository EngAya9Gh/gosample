<script setup>
/**
 * FormInput — unified text/email/password/number/textarea field.
 * - password: show/hide toggle
 * - number: optional unit suffix chip
 * - inline validation error + helper text
 * v-model on `modelValue`.
 */
import { ref, computed } from 'vue';

const props = defineProps({
  modelValue: { type: [String, Number], default: '' },
  label:   { type: String, default: '' },
  type:    { type: String, default: 'text' }, // text|email|password|number|textarea|date|time|datetime-local
  placeholder: { type: String, default: '' },
  icon:    { type: String, default: '' },       // leading ri-*
  unit:    { type: String, default: '' },       // suffix chip (number)
  helper:  { type: String, default: '' },
  error:   { type: String, default: '' },
  required:{ type: Boolean, default: false },
  disabled:{ type: Boolean, default: false },
  rows:    { type: Number, default: 3 },
  clearable:{ type: Boolean, default: true },
});
const emit = defineEmits(['update:modelValue']);

const show = ref(false);
const realType = computed(() =>
  props.type === 'password' ? (show.value ? 'text' : 'password') : props.type
);

const field =
  'w-full bg-surface dark:bg-white/5 text-ink dark:text-slate-100 ' +
  'border rounded-xl text-sm transition-all duration-200 placeholder:text-slate-400 ' +
  'focus:outline-none focus:ring-2 focus:ring-primary-500/40 focus:border-primary-500 dark:focus:border-primary-500 ' +
  'disabled:opacity-60 disabled:cursor-not-allowed';
const borderCls = computed(() =>
  props.error
    ? 'border-danger/60 focus:ring-danger/30 focus:border-danger dark:focus:border-danger'
    : 'border-slate-200 dark:border-white/10'
);
function onInput(e) { emit('update:modelValue', e.target.value); }
function clearInput() { emit('update:modelValue', ''); }
</script>

<template>
  <div class="w-full">
    <label v-if="label" class="block text-[13px] font-bold text-slate-800 dark:text-slate-200 mb-1.5">
      {{ label }} <span v-if="required" class="text-danger">*</span>
    </label>

    <div class="relative">
      <i v-if="icon && type !== 'textarea'" :class="[icon, 'absolute top-1/2 -translate-y-1/2 inset-inline-start-3 text-slate-400 pointer-events-none']" style="inset-inline-start:.75rem"></i>

      <textarea
        v-if="type === 'textarea'"
        :value="modelValue" :rows="rows" :placeholder="placeholder" :disabled="disabled"
        @input="onInput"
        :class="[field, borderCls, 'px-3.5 py-2.5 resize-y leading-relaxed']"
      ></textarea>

      <template v-else>
        <input
          :type="realType" :value="modelValue" :placeholder="placeholder" :disabled="disabled"
          @input="onInput"
          :class="[field, borderCls, 'h-11',
            icon ? 'ps-10' : 'ps-3.5',
            (type === 'password' || unit || (clearable && modelValue)) ? 'pe-10' : 'pe-3.5']"
        />
        
        <!-- clear button -->
        <button
          v-if="clearable && modelValue !== '' && modelValue !== null && type !== 'password' && !unit"
          type="button" @click="clearInput"
          class="absolute top-1/2 -translate-y-1/2 inset-inline-end-2 grid place-items-center w-8 h-8 rounded-lg text-slate-400 hover:text-danger hover:bg-surface-muted dark:hover:bg-white/10"
          style="inset-inline-end:.5rem"
        >
          <i class="ri-close-line text-lg"></i>
        </button>
      </template>

      <!-- password toggle -->
      <button
        v-if="type === 'password'"
        type="button" @click="show = !show"
        class="absolute top-1/2 -translate-y-1/2 inset-inline-end-2 grid place-items-center w-8 h-8 rounded-lg text-slate-400 hover:text-primary-600 hover:bg-surface-muted dark:hover:bg-white/10"
        style="inset-inline-end:.5rem"
      >
        <i :class="show ? 'ri-eye-off-line' : 'ri-eye-line'"></i>
      </button>

      <!-- unit chip -->
      <span
        v-else-if="unit"
        class="absolute top-1/2 -translate-y-1/2 inset-inline-end-2 px-2 h-7 grid place-items-center rounded-lg bg-surface-muted dark:bg-white/10 text-xs font-medium text-slate-500"
        style="inset-inline-end:.5rem"
      >{{ unit }}</span>
    </div>

    <p v-if="error" class="flex items-center gap-1 text-xs text-danger mt-1.5">
      <i class="ri-error-warning-line"></i>{{ error }}
    </p>
    <p v-else-if="helper" class="text-xs text-slate-400 dark:text-slate-500 mt-1.5">{{ helper }}</p>
  </div>
</template>
