/**
 * Animated counter. Respects prefers-reduced-motion (jumps straight to value).
 * Usage in <script setup>:
 *   const { display } = useCounter(() => props.value);
 */
import { ref, watch, onMounted } from 'vue';

export function useCounter(getTarget, { duration = 1100 } = {}) {
  const display = ref(0);
  const reduce = typeof window !== 'undefined'
    && window.matchMedia
    && window.matchMedia('(prefers-reduced-motion: reduce)').matches;

  function run(to) {
    if (reduce) { display.value = to; return; }
    const from = 0;
    const start = performance.now();
    const tick = (now) => {
      const p = Math.min(1, (now - start) / duration);
      const eased = 1 - Math.pow(1 - p, 3); // easeOutCubic
      display.value = Math.round(from + (to - from) * eased);
      if (p < 1) requestAnimationFrame(tick);
      else display.value = to;
    };
    requestAnimationFrame(tick);
  }

  onMounted(() => run(Number(getTarget()) || 0));
  watch(() => getTarget(), (v) => run(Number(v) || 0));
  return { display };
}
