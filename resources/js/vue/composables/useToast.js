/**
 * Toast store — framework-light singleton.
 * Usage:  const { push } = useToast();  push({ type:'success', title:'Saved', message:'...' })
 * Mount <ToastHost /> once in AppShell.
 */
import { reactive } from 'vue';

const state = reactive({ items: [] });
let seq = 0;

export function useToast() {
  function push({ type = 'info', title = '', message = '', timeout = 4000 } = {}) {
    const id = ++seq;
    state.items.push({ id, type, title, message });
    if (timeout) setTimeout(() => dismiss(id), timeout);
    return id;
  }
  function dismiss(id) {
    const i = state.items.findIndex((t) => t.id === id);
    if (i > -1) state.items.splice(i, 1);
  }
  return { state, push, dismiss };
}
