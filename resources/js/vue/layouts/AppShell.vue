<script setup>
/**
 * AppShell — persistent Inertia layout. Renders Sidebar + Topbar + Footer and a
 * <slot> for the active Inertia page. Sources current route + user from the
 * Inertia page; navigates via the Inertia router. Direction follows language
 * (ar = RTL, en = LTR).
 */
import { ref, computed, onMounted, watch } from 'vue';
import { usePage, router } from '@inertiajs/vue3';
import Sidebar from './Sidebar.vue';
import Topbar from './Topbar.vue';
import Footer from './Footer.vue';
import ToastHost from '../components/ToastHost.vue';
import { useToast } from '../composables/useToast';

const page = usePage();
const { push } = useToast();
// nav.config routes are like /admin/tasks or /dashboard; the server URL is /app/...
// so strip the /app prefix for active-state matching against nav.config.
const current = computed(() => (page.url || '/dashboard').split('?')[0].replace(/^\/app/, '') || '/dashboard');
const user = computed(() => page.props?.auth?.user || undefined);

// Surface Laravel flash messages (shared by HandleInertiaRequests) as toasts.
watch(() => page.props?.flash, (f) => {
  if (!f) return;
  if (f.success) push({ type: 'success', title: 'Success', message: f.success });
  if (f.error) push({ type: 'error', title: 'Error', message: f.error });
}, { deep: true, immediate: true });

const collapsed = ref(false);
const dark = ref(false);
const lang = ref('en');           // 'ar' | 'en' (English primary)
const dir = computed(() => (lang.value === 'ar' ? 'rtl' : 'ltr'));

const emergency = ref(null);      // { message } | null
const showTop = ref(false);

// Navigation from sidebar/topbar. SPA targets go through Inertia (prefixed with
// /app); Blade-only targets (login/logout) do a full page navigation.
function onNavigate(target) {
  if (!target) return;
  // Migrated SPA routes go through Inertia (/app); every other /admin route still
  // loads its classic Blade page. Add a route here when its SPA page ships.
  const SPA_ADMIN_ROUTES = [
    '/admin/tasks', '/admin/samples', '/admin/scheduled-tasks',
    '/admin/system-calendar', '/admin/drivers', '/admin/lost',
    '/admin/shipments', '/admin/money-transfers', '/admin/cars', '/admin/car-link-histories',
    '/admin/swaprequests', '/admin/clients',
  ];
  if (target.startsWith('/admin') && !SPA_ADMIN_ROUTES.some(route => target.startsWith(route))) {
    window.location.href = target;
  } else if (target.startsWith('http')) {
    window.open(target, '_blank');
  } else {
    router.visit('/app' + target);
  }
}
function toggleSidebar() { collapsed.value = !collapsed.value; }
function toggleDark() {
  dark.value = !dark.value;
  document.documentElement.classList.toggle('dark', dark.value);
}
function setLang(l) { lang.value = l; }

onMounted(() => {
  dark.value = localStorage.getItem('mtc-dark') === '1';
  document.documentElement.classList.toggle('dark', dark.value);
  lang.value = localStorage.getItem('mtc-lang') || 'en';
});
watch(dark, (v) => localStorage.setItem('mtc-dark', v ? '1' : '0'));
watch(lang, (v) => {
  localStorage.setItem('mtc-lang', v);
  document.documentElement.setAttribute('dir', v === 'ar' ? 'rtl' : 'ltr');
});

function onScroll(e) { showTop.value = e.target.scrollTop > 320; }
function toTop(e) { e.target.closest('.shell-scroll')?.scrollTo({ top: 0, behavior: 'smooth' }); }
</script>

<template>
  <div :dir="dir" :lang="lang"
    class="flex h-screen overflow-hidden print:h-auto print:overflow-visible bg-surface-canvas dark:bg-surface-dark font-sans text-ink dark:text-slate-200 print:bg-white"
  >
    <Sidebar :collapsed="collapsed" :current="current" :user="user" @navigate="onNavigate" />

    <div class="flex-1 flex flex-col min-w-0">
      <Topbar :dark="dark" :lang="lang" :user="user"
        @toggle-sidebar="toggleSidebar" @toggle-dark="toggleDark" @set-lang="setLang"
        @navigate="onNavigate" @quick-create="onNavigate('/admin/tasks/create')" />

      <!-- emergency banner -->
      <Transition name="emg">
        <div v-if="emergency" class="flex items-center gap-3 px-5 py-3 bg-danger text-white">
          <i class="ri-alarm-warning-fill text-xl animate-pulse"></i>
          <p class="text-sm font-medium flex-1">{{ emergency.message }}</p>
          <button @click="emergency = null" class="grid place-items-center w-7 h-7 rounded-lg hover:bg-white/15"><i class="ri-close-line"></i></button>
        </div>
      </Transition>

      <!-- scroll region -->
      <main class="shell-scroll flex-1 overflow-y-auto print:overflow-visible" @scroll="onScroll">
        <div class="max-w-[1600px] mx-auto px-4 sm:px-6 py-6 print:p-0">
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
