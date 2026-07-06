<script setup>
/**
 * FormDate — designed date / time / datetime picker (flatpickr-backed).
 * Replaces native <input type="date|time|datetime-local"> so every screen gets the
 * branded teal picker instead of the raw browser control. Matches the classic
 * system, which also uses flatpickr (see partials/modern-filters.blade.php).
 *
 * Props: mode = 'date' | 'time' | 'datetime'.  v-model is the raw value string
 * (Y-m-d / H:i / "Y-m-d H:i") — same shapes the backend filters expect.
 */
import { ref, computed, onMounted, onBeforeUnmount, watch } from 'vue';
import flatpickr from 'flatpickr';
import 'flatpickr/dist/flatpickr.css';
import monthSelectPlugin from 'flatpickr/dist/plugins/monthSelect';
import 'flatpickr/dist/plugins/monthSelect/style.css';

const props = defineProps({
  modelValue: { type: [String, Number], default: '' },
  label:    { type: String, default: '' },
  mode:     { type: String, default: 'date' }, // date | time | datetime | month | range
  placeholder: { type: String, default: '' },
  helper:   { type: String, default: '' },
  error:    { type: String, default: '' },
  required: { type: Boolean, default: false },
  disabled: { type: Boolean, default: false },
  // Render the calendar into <body> (floating) instead of attached to the field.
  // Use inside modals / overflow-hidden containers so the calendar isn't clipped.
  floating: { type: Boolean, default: false },
});
const emit = defineEmits(['update:modelValue', 'range']);

const el = ref(null);
let fp = null;

const icon = computed(() => (props.mode === 'time' ? 'ri-time-line' : 'ri-calendar-line'));
const ph = computed(() =>
  props.placeholder ||
  (props.mode === 'time' ? 'hh:mm'
    : props.mode === 'datetime' ? 'dd/mm/yyyy, hh:mm'
    : props.mode === 'month' ? 'mm/yyyy'
    : 'dd/mm/yyyy')
);
// Friendly, theme-style display of the selected value (e.g. "01 Jun 2026, 9:00 AM").
// flatpickr's altInput shows this while the real input keeps the raw Y-m-d H:i value.
const altFmt = computed(() =>
  props.mode === 'time' ? 'h:i K'
  : props.mode === 'datetime' ? 'd M Y, h:i K'
  : props.mode === 'month' ? 'F Y'
  : 'd M Y'
);

const field =
  'w-full h-11 bg-surface dark:bg-white/5 text-ink dark:text-slate-100 ' +
  'border rounded-xl text-sm transition-all duration-200 placeholder:text-slate-400 ps-10 pe-3.5 ' +
  'focus:outline-none focus:ring-2 focus:ring-primary-500/40 focus:border-primary-500 cursor-pointer ' +
  'disabled:opacity-60 disabled:cursor-not-allowed';
const borderCls = computed(() =>
  props.error
    ? 'border-danger/60 focus:ring-danger/30 focus:border-danger'
    : 'border-slate-200 dark:border-white/10'
);

function buildConfig() {
  const base = {
    // Normally render the calendar attached to the field (static) so it scrolls
    // with the SPA's inner scroll container and never gets "stuck". Inside a modal
    // (floating) append it to <body> so the modal's overflow doesn't clip it.
    static: !props.floating,
    ...(props.floating ? { appendTo: document.body } : {}),
    disableMobile: true,                 // always the branded picker, never native mobile UI
    // Show a friendly formatted value (altInput) while the real input keeps the
    // raw Y-m-d H:i string the backend filters expect.
    altInput: true,
    altFormat: altFmt.value,
    altInputClass: `${field} ${borderCls.value}`,
    defaultHour: 9,
    locale: { firstDayOfWeek: 0 },       // Sunday-first, matching the classic Create Task picker
    onChange: (selectedDates, str, inst) => {
      emit('update:modelValue', str);
      // Range mode: mirror the classic dashboard — only emit a usable range when
      // BOTH bounds are picked; emit empty when the range is cleared.
      if (props.mode === 'range') {
        if (selectedDates.length === 2) {
          emit('range', {
            from: inst.formatDate(selectedDates[0], 'Y-m-d'),
            to: inst.formatDate(selectedDates[1], 'Y-m-d'),
          });
        } else if (selectedDates.length === 0) {
          emit('range', { from: '', to: '' });
        }
      }
    },
    onReady: (_sel, _str, inst) => {
      // Use the EXACT classic picker theme (.mf-flatpickr from modern-filters).
      inst.calendarContainer.classList.add('mf-flatpickr');
      if (props.mode === 'time') inst.calendarContainer.classList.add('mf-flatpickr--time-only');

      // Inject "Hour" / "Minute" / "AM/PM" labels above the time controls (classic parity).
      const labelFor = (sel, text) => {
        const elm = inst.calendarContainer.querySelector(sel);
        if (!elm) return;
        const wrapper = elm.closest('.numInputWrapper') || elm;
        if (wrapper.querySelector('.mf-time-label')) return;
        const label = document.createElement('span');
        label.className = 'mf-time-label';
        label.textContent = text;
        wrapper.appendChild(label);
      };
      labelFor('.flatpickr-hour', 'Hour');
      labelFor('.flatpickr-minute', 'Minute');
      const ampm = inst.calendarContainer.querySelector('.flatpickr-am-pm');
      if (ampm && !ampm.querySelector('.mf-time-label')) {
        const label = document.createElement('span');
        label.className = 'mf-time-label';
        label.textContent = 'AM/PM';
        ampm.appendChild(label);
      }

      // Validate the time inputs: max 2 digits, clamp to a valid range while typing.
      const limit = (elm, max) => {
        if (!elm) return;
        elm.setAttribute('maxlength', '2');
        elm.addEventListener('input', () => {
          let v = elm.value.replace(/\D/g, '').slice(0, 2);
          if (v !== '' && parseInt(v, 10) > max) v = String(max);
          if (elm.value !== v) elm.value = v;
        });
      };
      limit(inst.hourElement, 12);
      limit(inst.minuteElement, 59);
      limit(inst.secondElement, 59);
    },
  };
  if (props.mode === 'datetime') {
    return { ...base, enableTime: true, time_24hr: false, dateFormat: 'Y-m-d H:i' };
  }
  if (props.mode === 'time') {
    return { ...base, enableTime: true, noCalendar: true, time_24hr: false, dateFormat: 'H:i' };
  }
  if (props.mode === 'month') {
    // Branded month picker (Y-m value, "June 2026" display) via the monthSelect plugin.
    return {
      ...base,
      dateFormat: 'Y-m',
      plugins: [monthSelectPlugin({ shorthand: true, dateFormat: 'Y-m', altFormat: 'F Y' })],
    };
  }
  if (props.mode === 'range') {
    return { ...base, mode: 'range', dateFormat: 'Y-m-d' };
  }
  return { ...base, dateFormat: 'Y-m-d' };
}

onMounted(() => {
  fp = flatpickr(el.value, buildConfig());
  if (props.modelValue) fp.setDate(props.modelValue, false);
});
watch(() => props.modelValue, (v) => {
  if (fp && String(v ?? '') !== (el.value?.value ?? '')) fp.setDate(v || '', false);
});
onBeforeUnmount(() => { fp?.destroy(); fp = null; });
</script>

<template>
  <div class="w-full">
    <label v-if="label" class="block text-[13px] font-bold text-slate-800 dark:text-slate-200 mb-1.5">
      {{ label }} <span v-if="required" class="text-danger">*</span>
    </label>
    <div class="relative">
      <i :class="[icon, 'absolute top-1/2 -translate-y-1/2 inset-inline-start-3 text-primary-600 pointer-events-none z-[1]']" style="inset-inline-start:.75rem"></i>
      <input
        ref="el" type="text" readonly :placeholder="ph" :disabled="disabled"
        :class="[field, borderCls]"
      />
    </div>
    <p v-if="error" class="flex items-center gap-1 text-xs text-danger mt-1.5"><i class="ri-error-warning-line"></i>{{ error }}</p>
    <p v-else-if="helper" class="text-xs text-slate-400 dark:text-slate-500 mt-1.5">{{ helper }}</p>
  </div>
</template>
