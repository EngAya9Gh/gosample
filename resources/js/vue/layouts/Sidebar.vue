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
  badges:    { type: Object, default: () => ({ delayed: 3, lost: 2, swap: 1 }) },
});
defineEmits(['navigate']);

const { can } = usePermissions();
const openGroups = ref(new Set(NAV.map((g) => g.label))); // all open initially

const groups = computed(() =>
  NAV.map((g) => ({ ...g, items: g.items.filter((i) => can(i.perm)) }))
     .filter((g) => g.items.length)
);

function toggleGroup(label) {
  const s = new Set(openGroups.value);
  s.has(label) ? s.delete(label) : s.add(label);
  openGroups.value = s;
}
function isActive(route) {
  return props.current === route || (route !== '/dashboard' && props.current.startsWith(route));
}

// Per-badge colours (matching the design): delayed = red, lost = pink/mauve, swap = blue.
const BADGE_COLOR = { delayed: 'bg-red-500', lost: 'bg-danger', swap: 'bg-secondary' };
function badgeColor(key) { return BADGE_COLOR[key] || 'bg-danger'; }
</script>

<template>
  <aside
    class="flex flex-col h-full bg-gradient-to-b from-primary-700 to-primary-800 dark:from-primary-900 dark:to-primary-900 text-slate-300 transition-[width] duration-300 ease-out shrink-0"
    :class="collapsed ? 'w-[76px]' : 'w-[264px]'"
  >
    <!-- logo box — same brand logos as the classic panel -->
    <div class="flex items-center justify-center h-16 px-4 border-b border-white/10 shrink-0">
      <img v-if="!collapsed" :src="'/assets/images/logo-light.png'" alt="MTC" class="h-6 w-auto" />
      <img v-else :src="'/assets/images/logo-sm.png'" alt="MTC" class="h-8 w-auto" />
    </div>

    <!-- nav -->
    <nav class="flex-1 overflow-y-auto py-3 px-2.5 space-y-1 nav-scroll">
      <div v-for="g in groups" :key="g.label">
        <!-- group header -->
        <button
          v-if="!collapsed"
          @click="toggleGroup(g.label)"
          class="w-full flex items-center gap-2 px-2.5 py-2 text-[11px] font-semibold uppercase tracking-wider text-primary-300/70 hover:text-primary-200 transition"
        >
          <i :class="[g.icon, 'text-sm']"></i>
          <span>{{ g.label }}</span>
          <i class="ri-arrow-down-s-line ms-auto transition-transform" :class="openGroups.has(g.label) ? '' : '-rotate-90 rtl:rotate-90'"></i>
        </button>
        <div v-else class="my-2 mx-2 border-t border-white/5"></div>

        <!-- items -->
        <div v-show="collapsed || openGroups.has(g.label)" class="space-y-0.5 mt-0.5">
          <a
            v-for="it in g.items" :key="it.route"
            href="#"
            @click.prevent="$emit('navigate', it.route)"
            class="group relative flex items-center gap-3 px-2.5 h-10 rounded-xl text-sm transition-all duration-200"
            :class="isActive(it.route)
              ? 'bg-white/10 text-white font-semibold shadow-sm'
              : 'text-slate-200/70 hover:bg-white/5 hover:text-white'"
            :title="collapsed ? it.label : ''"
          >
            <span v-if="isActive(it.route)" class="absolute inset-inline-start-0 top-1/2 -translate-y-1/2 w-1.5 h-6 rounded-full bg-amber-400" style="inset-inline-start:0"></span>
            <i :class="[it.icon, 'text-lg shrink-0', isActive(it.route) ? 'text-white' : 'text-slate-300/70 group-hover:text-white']"></i>
            <span v-if="!collapsed" class="truncate flex-1">{{ it.label }}</span>
            <span v-if="!collapsed && it.badge && badges[it.badge]"
              :class="['inline-grid place-items-center min-w-5 h-5 px-1 rounded-full text-[10px] font-bold text-white', badgeColor(it.badge)]">
              {{ badges[it.badge] }}
            </span>
            <!-- collapsed badge dot -->
            <span v-if="collapsed && it.badge && badges[it.badge]" :class="['absolute top-1.5 inset-inline-end-1.5 w-2 h-2 rounded-full', badgeColor(it.badge)]" style="inset-inline-end:.375rem"></span>
          </a>
        </div>
      </div>
    </nav>

    <!-- footer mini -->
    <div class="px-3 py-3 border-t border-white/5 shrink-0">
      <div class="flex items-center gap-2.5 px-1" :class="collapsed ? 'justify-center' : ''">
        <span class="w-2 h-2 rounded-full bg-success animate-pulse-ring shrink-0"></span>
        <span v-if="!collapsed" class="text-[11px] text-slate-400">All systems operational</span>
      </div>
    </div>
  </aside>
</template>

<style scoped>
.nav-scroll::-webkit-scrollbar { width: 6px; }
.nav-scroll::-webkit-scrollbar-thumb { background: rgba(255,255,255,.1); border-radius: 99px; }
</style>
