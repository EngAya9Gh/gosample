<script setup>
/**
 * Topbar — light bar with hamburger, quick-create, lost-samples shortcut,
 * 3-tab notifications, language switch, dark toggle, user menu.
 * Emits: toggle-sidebar, toggle-dark, set-lang, navigate, quick-create.
 */
import { ref, onMounted, onBeforeUnmount } from 'vue';
import BaseAvatar from '../components/BaseAvatar.vue';

const props = defineProps({
  dark: { type: Boolean, default: false },
  lang: { type: String, default: 'en' }, // en|ar
  user: { type: Object, default: () => ({ name: 'Sara Al-Otaibi', role: 'Operations Manager' }) },
  counts: { type: Object, default: () => ({ newTasks: 4, swap: 1, lost: 2, notif: 5 }) },
});
const emit = defineEmits(['toggle-sidebar', 'toggle-dark', 'set-lang', 'navigate', 'quick-create']);

const openMenu = ref(''); // '' | 'create' | 'notif' | 'user'
const notifTab = ref('tasks');
function toggle(m) { openMenu.value = openMenu.value === m ? '' : m; }
function close() { openMenu.value = ''; }

// Global search box (left of the bar). Enter jumps to the tasks list filtered by keyword.
const search = ref('');
const searchInput = ref(null);
function submitSearch() {
  const q = search.value.trim();
  if (q) emit('navigate', '/admin/tasks?keyword=' + encodeURIComponent(q));
}

// ⌘K / Ctrl+K focuses the search box (Esc blurs it).
function onKeydown(e) {
  if ((e.metaKey || e.ctrlKey) && (e.key === 'k' || e.key === 'K')) {
    e.preventDefault();
    searchInput.value?.focus();
    searchInput.value?.select();
  } else if (e.key === 'Escape' && document.activeElement === searchInput.value) {
    searchInput.value?.blur();
  }
}
onMounted(() => window.addEventListener('keydown', onKeydown));
onBeforeUnmount(() => window.removeEventListener('keydown', onKeydown));

const createItems = [
  { label: 'Quick Schedule Task', icon: 'ri-calendar-todo-line', key: 'quick-schedule' },
  { label: 'Add Task',            icon: 'ri-add-box-line',       key: 'task' },
  { label: 'Add Shipment',        icon: 'ri-truck-line',         key: 'shipment' },
  { label: 'List New Tasks',      icon: 'ri-list-check',         key: 'new-tasks', badge: 'newTasks' },
  { label: 'Swap Request',        icon: 'ri-swap-line',          key: 'swap', badge: 'swap' },
  { label: 'New Swap Request',    icon: 'ri-add-circle-line',    key: 'new-swap' },
];

const notifTabs = [
  { key: 'lost',   label: 'Lost Samples', icon: 'ri-flask-line' },
  { key: 'tasks',  label: 'Tasks',        icon: 'ri-task-line' },
  { key: 'alerts', label: 'Alerts',       icon: 'ri-notification-3-line' },
];

const sampleNotif = {
  lost: [
    { barcode: 'SM-8841-0052', text: 'The Sample is lost!, please check' },
    { barcode: 'SM-8841-0107', text: 'The Sample is lost!, please check' },
  ],
  tasks: [
    { title: 'New Task #10428', meta: 'King Faisal Lab → Central Hub', time: '4 min ago', tone: 'info' },
    { title: 'Pickup delayed #10391', meta: 'Driver: Mohammed A.', time: '12 min ago', tone: 'warning' },
    { title: 'New Swap Request', meta: 'Tasks: 10377, 10380', time: '28 min ago', tone: 'primary' },
  ],
  alerts: [
    { title: 'GPS sync restored', meta: 'Afaqy back online', time: '1 h ago', icon: 'ri-checkbox-circle-line' },
    { title: 'Container 12 over-temp', meta: 'Car 4821 — 9.4°C', time: '2 h ago', icon: 'ri-temp-hot-line' },
  ],
};
</script>

<template>
  <header class="relative z-40 shrink-0 flex items-center gap-2 h-16 px-4 bg-surface dark:bg-surface-dark/80 backdrop-blur border-b border-slate-200/70 dark:border-white/5 print:hidden">
    <!-- hamburger -->
    <button @click="$emit('toggle-sidebar')" class="group grid place-items-center w-10 h-10 rounded-xl text-slate-500 hover:bg-surface-muted dark:hover:bg-white/10 transition">
      <i class="ri-menu-2-line text-xl transition-transform duration-300 group-hover:scale-110 motion-reduce:transition-none"></i>
    </button>

    <!-- global search -->
    <div class="relative flex-1 max-w-md">
      <i class="ri-search-line absolute top-1/2 -translate-y-1/2 inset-inline-start-3 text-slate-400" style="inset-inline-start:.75rem"></i>
      <input
        ref="searchInput"
        v-model="search" type="text" @keyup.enter="submitSearch"
        :placeholder="lang === 'ar' ? 'ابحث عن المهام، السائقين، العينات…' : 'Search tasks, drivers, samples…'"
        class="w-full h-10 ps-10 pe-12 text-sm bg-surface-muted dark:bg-white/5 border border-transparent focus:border-primary-500 rounded-xl focus:outline-none focus:ring-2 focus:ring-primary-500/30 text-ink dark:text-slate-100 placeholder:text-slate-400 transition" />
      <kbd class="absolute top-1/2 -translate-y-1/2 inset-inline-end-3 hidden md:inline-flex items-center h-6 px-2 rounded-md text-xs font-semibold text-slate-400 bg-surface dark:bg-white/10 border border-slate-200 dark:border-white/10" style="inset-inline-end:.75rem">⌘K</kbd>
    </div>

    <div class="ms-auto flex items-center gap-1">
      <!-- quick create -->
      <div class="relative me-1">
        <button @click="toggle('create')" class="group inline-flex items-center gap-2 h-10 px-3.5 rounded-xl bg-primary-700 text-white text-sm font-medium shadow-card hover:bg-primary-800 hover:-translate-y-0.5 transition-all motion-reduce:transform-none">
          <i class="ri-add-line text-base transition-transform duration-300 group-hover:rotate-90 motion-reduce:transition-none"></i>
          <span class="hidden sm:inline">Create</span>
          <i class="ri-arrow-down-s-line text-base"></i>
        </button>
        <Transition name="drop">
          <div v-if="openMenu === 'create'" class="absolute mt-2 inset-inline-end-0 w-60 bg-surface dark:bg-surface-dark-solid rounded-xl shadow-card-hover border border-slate-100 dark:border-white/10 p-1.5" style="inset-inline-end:0">
            <button v-for="c in createItems" :key="c.key" @click="$emit('quick-create', c.key); close()"
              class="w-full flex items-center gap-3 px-2.5 h-10 rounded-lg text-sm text-ink dark:text-slate-200 hover:bg-primary-50 dark:hover:bg-white/5 transition text-start">
              <i :class="[c.icon, 'text-primary-600 text-base']"></i>
              <span class="flex-1">{{ c.label }}</span>
              <span v-if="c.badge && counts[c.badge]" class="inline-grid place-items-center min-w-5 h-5 px-1 rounded-full text-[10px] font-bold bg-danger text-white">{{ counts[c.badge] }}</span>
            </button>
          </div>
        </Transition>
      </div>

      <!-- lost samples -->
      <button @click="$emit('navigate', '/admin/lost')" class="group relative grid place-items-center w-10 h-10 rounded-xl text-slate-500 hover:bg-surface-muted dark:hover:bg-white/10 transition" title="Lost samples">
        <i class="ri-flask-line text-xl transition-transform duration-300 group-hover:scale-110 group-hover:-rotate-12 motion-reduce:transition-none"></i>
        <span v-if="counts.lost" class="absolute top-1 inset-inline-end-1 inline-grid place-items-center min-w-4 h-4 px-1 rounded-full text-[10px] font-bold bg-danger text-white" style="inset-inline-end:.25rem">{{ counts.lost }}</span>
      </button>

      <!-- notifications -->
      <div class="relative">
        <button @click="toggle('notif')" class="group relative grid place-items-center w-10 h-10 rounded-xl text-slate-500 hover:bg-surface-muted dark:hover:bg-white/10 transition">
          <i class="ri-notification-3-line text-xl transition-transform duration-300 origin-top group-hover:rotate-12 motion-reduce:transition-none"></i>
          <span v-if="counts.notif" class="absolute top-1 inset-inline-end-1 inline-grid place-items-center min-w-4 h-4 px-1 rounded-full text-[10px] font-bold bg-danger text-white" style="inset-inline-end:.25rem">{{ counts.notif }}</span>
        </button>
        <Transition name="drop">
          <div v-if="openMenu === 'notif'" class="absolute mt-2 inset-inline-end-0 w-[340px] max-w-[90vw] bg-surface dark:bg-surface-dark-solid rounded-xl shadow-card-hover border border-slate-100 dark:border-white/10 overflow-hidden" style="inset-inline-end:0">
            <div class="flex border-b border-slate-100 dark:border-white/5">
              <button v-for="t in notifTabs" :key="t.key" @click="notifTab = t.key"
                class="flex-1 flex items-center justify-center gap-1.5 py-3 text-[13px] font-medium transition border-b-2"
                :class="notifTab === t.key ? 'border-primary-600 text-primary-700 dark:text-primary-300' : 'border-transparent text-slate-500 hover:text-ink'">
                <i :class="t.icon"></i><span class="hidden sm:inline">{{ t.label }}</span>
              </button>
            </div>
            <div class="max-h-[300px] overflow-y-auto p-1.5">
              <!-- lost -->
              <template v-if="notifTab === 'lost'">
                <a v-for="(n, i) in sampleNotif.lost" :key="i" href="#" class="flex items-start gap-3 p-2.5 rounded-lg hover:bg-surface-muted dark:hover:bg-white/5">
                  <span class="grid place-items-center w-9 h-9 rounded-lg bg-danger/10 text-danger shrink-0"><i class="ri-flask-line"></i></span>
                  <div class="min-w-0"><p class="text-[13px] font-semibold text-ink dark:text-slate-100 font-mono" style="direction:ltr">{{ n.barcode }}</p><p class="text-xs text-slate-500">{{ n.text }}</p></div>
                </a>
              </template>
              <!-- tasks -->
              <template v-else-if="notifTab === 'tasks'">
                <a v-for="(n, i) in sampleNotif.tasks" :key="i" href="#" class="flex items-start gap-3 p-2.5 rounded-lg hover:bg-surface-muted dark:hover:bg-white/5">
                  <span class="grid place-items-center w-9 h-9 rounded-lg shrink-0" :class="{ 'bg-info/10 text-info': n.tone==='info', 'bg-warning/15 text-amber-600': n.tone==='warning', 'bg-primary-50 text-primary-700': n.tone==='primary' }"><i class="ri-task-line"></i></span>
                  <div class="min-w-0 flex-1"><p class="text-[13px] font-semibold text-ink dark:text-slate-100 truncate">{{ n.title }}</p><p class="text-xs text-slate-500 truncate">{{ n.meta }}</p></div>
                  <span class="text-[11px] text-slate-400 shrink-0">{{ n.time }}</span>
                </a>
              </template>
              <!-- alerts -->
              <template v-else>
                <a v-for="(n, i) in sampleNotif.alerts" :key="i" href="#" class="flex items-start gap-3 p-2.5 rounded-lg hover:bg-surface-muted dark:hover:bg-white/5">
                  <span class="grid place-items-center w-9 h-9 rounded-lg bg-primary-50 text-primary-700 dark:bg-primary-500/15 dark:text-primary-300 shrink-0"><i :class="n.icon"></i></span>
                  <div class="min-w-0 flex-1"><p class="text-[13px] font-semibold text-ink dark:text-slate-100 truncate">{{ n.title }}</p><p class="text-xs text-slate-500 truncate">{{ n.meta }}</p></div>
                  <span class="text-[11px] text-slate-400 shrink-0">{{ n.time }}</span>
                </a>
              </template>
            </div>
          </div>
        </Transition>
      </div>

      <!-- language -->
      <button @click="$emit('set-lang', lang === 'en' ? 'ar' : 'en')" class="grid place-items-center w-10 h-10 rounded-xl text-slate-500 hover:bg-surface-muted dark:hover:bg-white/10 transition font-semibold text-[13px]" title="Switch language">
        {{ lang === 'en' ? 'ع' : 'EN' }}
      </button>

      <!-- dark -->
      <button @click="$emit('toggle-dark')" class="group grid place-items-center w-10 h-10 rounded-xl text-slate-500 hover:bg-surface-muted dark:hover:bg-white/10 transition" title="Toggle theme">
        <i :class="dark ? 'ri-sun-line' : 'ri-moon-line'" class="text-xl transition-transform duration-500 group-hover:rotate-90 motion-reduce:transition-none"></i>
      </button>


    </div>

    <!-- click-catcher -->
    <div v-if="openMenu" @click="close" class="fixed inset-0 z-[-1]"></div>
  </header>
</template>

<style scoped>
.drop-enter-active { transition: all .2s cubic-bezier(.16,1,.3,1); }
.drop-leave-active { transition: all .15s ease; }
.drop-enter-from, .drop-leave-to { opacity: 0; transform: translateY(-6px) scale(.97); }
@media (prefers-reduced-motion: reduce) { .drop-enter-active, .drop-leave-active { transition: opacity .15s ease; } .drop-enter-from { transform: none; } }
</style>
