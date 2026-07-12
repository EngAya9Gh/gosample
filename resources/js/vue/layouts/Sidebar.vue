<script setup>
/**
 * Sidebar — dark, RTL-aware, permission-gated nav tree.
 * Collapsible (compact icon mode). Badges read from a `badges` prop.
 * Active item by matching `current` route prefix.
 */
import { ref, computed } from 'vue';
import { NAV } from './nav.config';
import { usePermissions } from '../composables/usePermissions';

const props = defineProps({
  collapsed: { type: Boolean, default: false },
  current:   { type: String, default: '/dashboard' },
  user:      { type: Object, default: () => ({ name: 'Sara Al-Otaibi', role: 'Dispatcher · Admin' }) },
  badges:    { type: Object, default: () => ({ delayed: 3, lost: 2, swap: 1 }) },
});
defineEmits(['navigate']);

const { can } = usePermissions();
const openGroups = ref(new Set(NAV.map((g) => g.label))); // all open initially
const userMenuOpen = ref(false);

function toggleUserMenu() {
  userMenuOpen.value = !userMenuOpen.value;
}

// Real logout: the /logout route is POST-only (classic Blade auth), so submit
// a form with the CSRF token — navigating to /login while authenticated just
// bounces back, which made the button look dead.
function logout() {
  userMenuOpen.value = false;
  const csrf = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';
  const form = document.createElement('form');
  form.method = 'POST';
  form.action = '/logout';
  const token = document.createElement('input');
  token.type = 'hidden';
  token.name = '_token';
  token.value = csrf;
  form.appendChild(token);
  document.body.appendChild(form);
  form.submit();
}

const groups = computed(() =>
  NAV.map((g) => ({ ...g, items: g.items.filter((i) => can(i.perm)) }))
     .filter((g) => g.items.length)
);

function toggleGroup(label) {
  const s = new Set(openGroups.value);
  s.has(label) ? s.delete(label) : s.add(label);
  openGroups.value = s;
}
// Longest-prefix-wins: when one nav route is a prefix of another (e.g.
// /admin/barcodes and /admin/barcodes/generate), only the most specific
// match lights up — plain startsWith marked both.
const allRoutes = NAV.flatMap((g) => g.items.map((i) => i.route));
function isActive(route) {
  const best = allRoutes
    .filter((r) => props.current === r || (r !== '/dashboard' && props.current.startsWith(r)))
    .sort((a, b) => b.length - a.length)[0];
  return route === best;
}

// Per-badge colours (matching the design): delayed = red, lost = pink/mauve, swap = blue.
const BADGE_COLOR = { delayed: 'bg-red-500', lost: 'bg-danger', swap: 'bg-secondary' };
function badgeColor(key) { return BADGE_COLOR[key] || 'bg-danger'; }

const initials = computed(() => {
  if (!props.user?.name) return '??';
  return props.user.name.split(' ').map(n => n[0]).join('').substring(0, 2).toUpperCase();
});
</script>

<template>
  <aside
    class="flex flex-col h-full bg-gradient-to-b from-[#006b78] to-[#00424b] dark:from-[#05343b] dark:to-[#03171b] text-primary-100 transition-[width] duration-300 ease-out shrink-0 print:hidden"
    :class="collapsed ? 'w-[76px]' : 'w-[264px]'"
  >
    <!-- logo box -->
    <div class="flex items-center justify-start h-[72px] px-4 border-b border-white/10 shrink-0">
      <img v-if="!collapsed" :src="'/assets/images/logo-light.png'" alt="MTC" class="h-6 w-auto" />
      <img v-else :src="'/assets/images/logo-sm.png'" alt="MTC" class="h-8 w-auto" />
    </div>

    <!-- nav -->
    <nav class="flex-1 overflow-y-auto py-4 px-3 space-y-1 nav-scroll">
      <div v-for="g in groups" :key="g.label" class="mb-1">
        <button
          v-if="!collapsed"
          @click="toggleGroup(g.label)"
          class="w-full flex items-center gap-2 px-3 py-2 text-[10px] font-bold uppercase tracking-[.11em] hover:text-white transition"
          style="color:#6fa9ae;"
        >
          <i :class="[g.icon, 'text-sm']"></i>
          <span>{{ g.label }}</span>
          <i class="ri-arrow-down-s-line ms-auto transition-transform" :class="openGroups.has(g.label) ? '' : '-rotate-90 rtl:rotate-90'"></i>
        </button>
        <div v-else class="my-2 mx-2 border-t border-white/20"></div>

        <!-- items -->
        <div v-show="collapsed || openGroups.has(g.label)" class="space-y-1 mt-1">
          <a
            v-for="it in g.items" :key="it.route"
            href="#"
            @click.prevent="$emit('navigate', it.route)"
            class="group relative flex items-center gap-3 rounded-[10px] text-[13.5px] transition-all duration-200 no-underline"
            :style="isActive(it.route)
              ? 'display:flex; align-items:center; gap:12px; padding:11px 13px; border-radius:10px; font-size:13.5px; cursor:pointer; background:rgba(255,255,255,.16); color:#fff; font-weight:600; border-inline-start:3px solid #f7b84b;'
              : 'display:flex; align-items:center; gap:12px; padding:11px 13px; border-radius:10px; font-size:13.5px; cursor:pointer; background:transparent; color:#bfe0e2; font-weight:400; border-inline-start:3px solid transparent;'"
            :title="collapsed ? it.label : ''"
          >
            <i :class="[it.icon, 'text-[18px] shrink-0 w-5 text-center', isActive(it.route) ? 'text-white' : '']" style="flex-shrink:0;"></i>
            <span v-if="!collapsed" class="truncate flex-1">{{ it.label }}</span>
            <span v-if="!collapsed && it.badge && badges[it.badge]"
              :class="['inline-grid place-items-center min-w-[18px] h-[18px] px-1 rounded-full text-[10px] font-bold text-white', badgeColor(it.badge)]"
              style="flex-shrink:0;">
              {{ badges[it.badge] }}
            </span>
            <!-- collapsed badge dot -->
            <span v-if="collapsed && it.badge && badges[it.badge]" :class="['absolute top-2 inset-inline-end-2 w-2.5 h-2.5 rounded-full border-2 border-[#006b78]', badgeColor(it.badge)]" style="inset-inline-end:.5rem"></span>
          </a>
        </div>
      </div>
    </nav>

    <!-- User Profile Avatar at Bottom -->
    <div class="p-3 border-t border-white/10 shrink-0 relative">
      <div class="flex items-center gap-3 p-1 cursor-pointer group" :class="collapsed ? 'justify-center' : ''" @click="toggleUserMenu">
        <div class="w-9 h-9 rounded-full bg-[#f7b84b] text-white flex items-center justify-center font-bold text-[13px] shadow-sm shrink-0">
          {{ initials }}
        </div>
        <div v-if="!collapsed" class="flex-1 min-w-0">
          <div class="text-[13px] font-semibold text-white truncate leading-tight group-hover:text-amber-400 transition">{{ user.name }}</div>
          <div class="text-[11px] text-[#6fa9ae] truncate mt-0.5">{{ user.role || 'Admin' }}</div>
        </div>
        <!-- quick logout (animated: arrow nudges toward the exit on hover) -->
        <button v-if="!collapsed" @click.stop="logout()" title="Logout"
          class="logout-quick grid place-items-center w-8 h-8 rounded-lg shrink-0 text-[#6fa9ae] hover:text-red-400 hover:bg-red-500/10 transition-colors duration-200">
          <i class="ri-logout-box-r-line text-[16px] rtl:-scale-x-100"></i>
        </button>
      </div>
      <!-- User Menu Dropdown -->
      <Transition name="drop">
        <div v-if="userMenuOpen" class="absolute bottom-full start-3 w-56 mb-2 bg-surface dark:bg-surface-dark-solid rounded-xl shadow-card-hover border border-slate-100 dark:border-white/10 p-1.5 z-50">
          <div class="px-3 py-2.5 border-b border-slate-100 dark:border-white/5 mb-1">
            <p class="text-sm font-semibold text-ink dark:text-slate-100">Welcome {{ user.name.split(' ')[0] }}!</p>
            <p class="text-xs text-slate-400">{{ user.role }}</p>
          </div>
          <a href="#" @click.prevent="$emit('navigate','/admin/profile'); userMenuOpen = false" class="flex items-center gap-3 px-2.5 h-9 rounded-lg text-sm text-ink dark:text-slate-200 hover:bg-surface-muted dark:hover:bg-white/5"><i class="ri-user-line text-slate-400"></i>Profile</a>
          <a href="#" class="flex items-center gap-3 px-2.5 h-9 rounded-lg text-sm text-ink dark:text-slate-200 hover:bg-surface-muted dark:hover:bg-white/5"><i class="ri-question-line text-slate-400"></i>Help</a>
          <a href="#" @click.prevent="logout()" class="flex items-center gap-3 px-2.5 h-9 rounded-lg text-sm text-danger hover:bg-danger/5"><i class="ri-logout-box-r-line"></i>Logout</a>
        </div>
      </Transition>
    </div>
  </aside>
</template>

<style scoped>
.nav-scroll::-webkit-scrollbar { width: 6px; }
.nav-scroll::-webkit-scrollbar-thumb { background: rgba(255,255,255,.1); border-radius: 99px; }
.drop-enter-active { transition: all .2s cubic-bezier(.16,1,.3,1); }
.drop-leave-active { transition: all .15s ease; }
.drop-enter-from, .drop-leave-to { opacity: 0; transform: translateY(6px) scale(.97); }

/* quick-logout: arrow nudges toward the door while hovered */
.logout-quick i { transition: transform .2s ease; }
.logout-quick:hover i { animation: logout-nudge .55s ease-in-out infinite alternate; }
@keyframes logout-nudge {
  from { transform: translateX(0); }
  to   { transform: translateX(3px); }
}
[dir='rtl'] .logout-quick:hover i { animation-name: logout-nudge-rtl; }
@keyframes logout-nudge-rtl {
  from { transform: translateX(0) scaleX(-1); }
  to   { transform: translateX(-3px) scaleX(-1); }
}

@media (prefers-reduced-motion: reduce) { .drop-enter-active, .drop-leave-active { transition: opacity .15s ease; } .drop-enter-from { transform: none; } .logout-quick:hover i { animation: none; } }
</style>
