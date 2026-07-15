<script setup>
/**
 * AppShell — the single application layout. Renders Sidebar + Topbar + Footer
 * and a <slot> (in production a <router-view/>) for the active screen.
 * Owns shell-level state: sidebar collapse, dark mode, language/direction,
 * global emergency alert, back-to-top, ToastHost.
 *
 * Direction follows language: en = LTR, ar = RTL. Set dir on the root so every
 * logical Tailwind utility (ps-/pe-/ms-/me-/start-/end-) resolves correctly.
 */
import { ref, computed, onMounted, watch } from 'vue';
import Sidebar from './Sidebar.vue';
import Topbar from './Topbar.vue';
import Footer from './Footer.vue';
import ToastHost from '../components/ToastHost.vue';

const props = defineProps({
  current: { type: String, default: '/dashboard' },
});
const emit = defineEmits(['navigate']);

const collapsed = ref(false);
const dark = ref(false);
const lang = ref('en');           // 'en' | 'ar'
const dir = computed(() => (lang.value === 'ar' ? 'rtl' : 'ltr'));

const emergency = ref(null);      // { message } | null
const showTop = ref(false);

function toggleSidebar() { collapsed.value = !collapsed.value; }
function toggleDark() {
  dark.value = !dark.value;
  document.documentElement.classList.toggle('dark', dark.value);
}
function setLang(l) { lang.value = l; }

/* persist prefs */
onMounted(() => {
  dark.value = localStorage.getItem('mtc-dark') === '1';
  document.documentElement.classList.toggle('dark', dark.value);
  lang.value = localStorage.getItem('mtc-lang') || 'en';

  // mock emergency poll (every 60s in production hitting /check-emergency)
  // emergency.value = { message: 'Container 7 temperature critical — Car 4821' };
});
watch(dark, (v) => localStorage.setItem('mtc-dark', v ? '1' : '0'));
watch(lang, (v) => localStorage.setItem('mtc-lang', v));

function onScroll(e) { showTop.value = e.target.scrollTop > 320; }
function toTop(e) { e.target.closest('.shell-scroll')?.scrollTo({ top: 0, behavior: 'smooth' }); }
</script>

<template>
  <div :dir="dir" :lang="lang"
    class="flex h-screen overflow-hidden bg-surface-canvas dark:bg-slate-950 font-sans text-ink dark:text-slate-200"
  >
    <Sidebar :collapsed="collapsed" :current="current" @navigate="$emit('navigate', $event)" />

    <div class="flex-1 flex flex-col min-w-0">
      <Topbar :dark="dark" :lang="lang"
        @toggle-sidebar="toggleSidebar" @toggle-dark="toggleDark" @set-lang="setLang"
        @navigate="$emit('navigate', $event)" @quick-create="$emit('navigate', '/admin/tasks/create')" />

      <!-- emergency banner -->
      <Transition name="emg">
        <div v-if="emergency" class="flex items-center gap-3 px-5 py-3 bg-danger text-white">
          <i class="ri-alarm-warning-fill text-xl animate-pulse"></i>
          <p class="text-sm font-medium flex-1">{{ emergency.message }}</p>
          <button @click="emergency = null" class="grid place-items-center w-7 h-7 rounded-lg hover:bg-white/15"><i class="ri-close-line"></i></button>
        </div>
      </Transition>

      <!-- scroll region -->
      <main class="shell-scroll flex-1 overflow-y-auto" @scroll="onScroll">
        <div class="max-w-[1600px] mx-auto px-4 sm:px-6 py-6">
          <slot></slot>
        </div>
        <Footer />
      </main>
    </div>

    <!-- back to top -->
    <Transition name="drop">
      <button v-if="showTop" @click="toTop"
        class="fixed bottom-6 inset-inline-end-6 z-50 grid place-items-center w-11 h-11 rounded-full bg-primary-700 text-white shadow-card-hover hover:bg-primary-800 transition"
        style="inset-inline-end:1.5rem">
        <i class="ri-arrow-up-line text-lg"></i>
      </button>
    </Transition>

    <ToastHost />
  </div>
</template>

<style scoped>
.shell-scroll::-webkit-scrollbar { width: 10px; }
.shell-scroll::-webkit-scrollbar-thumb { background: rgba(100,116,139,.25); border-radius: 99px; border: 3px solid transparent; background-clip: content-box; }
.emg-enter-active, .emg-leave-active { transition: all .3s ease; }
.emg-enter-from, .emg-leave-to { opacity: 0; transform: translateY(-100%); }
.drop-enter-active, .drop-leave-active { transition: all .25s cubic-bezier(.16,1,.3,1); }
.drop-enter-from, .drop-leave-to { opacity: 0; transform: translateY(8px) scale(.9); }
</style>
