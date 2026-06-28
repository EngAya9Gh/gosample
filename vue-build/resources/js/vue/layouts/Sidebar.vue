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
</script>

<template>
  <aside
    class="flex flex-col h-full bg-primary-900 text-slate-300 transition-[width] duration-300 ease-out shrink-0"
    :class="collapsed ? 'w-[76px]' : 'w-[264px]'"
  >
    <!-- logo box -->
    <div class="flex items-center gap-2.5 h-16 px-4 border-b border-white/5 shrink-0">
      <span class="grid place-items-center w-10 h-10 rounded-xl bg-gradient-to-br from-primary-500 to-primary-700 text-white font-bold text-lg shadow-lg shrink-0">M</span>
      <div v-if="!collapsed" class="leading-tight">
        <div class="font-bold text-white tracking-wide">MTC</div>
        <div class="text-[10px] text-primary-300 uppercase tracking-[.2em]">GoSample</div>
      </div>
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
              ? 'bg-primary-500/20 text-white font-medium shadow-[inset_0_0_0_1px_rgba(13,148,136,.4)]'
              : 'text-slate-300/80 hover:bg-white/5 hover:text-white'"
            :title="collapsed ? it.label : ''"
          >
            <span v-if="isActive(it.route)" class="absolute inset-inline-start-0 top-1/2 -translate-y-1/2 w-1 h-5 rounded-full bg-primary-400" style="inset-inline-start:0"></span>
            <i :class="[it.icon, 'text-lg shrink-0', isActive(it.route) ? 'text-primary-300' : 'text-slate-400 group-hover:text-primary-300']"></i>
            <span v-if="!collapsed" class="truncate flex-1">{{ it.label }}</span>
            <span v-if="!collapsed && it.badge && badges[it.badge]"
              class="inline-grid place-items-center min-w-5 h-5 px-1 rounded-full text-[10px] font-bold bg-danger text-white">
              {{ badges[it.badge] }}
            </span>
            <!-- collapsed badge dot -->
            <span v-if="collapsed && it.badge && badges[it.badge]" class="absolute top-1.5 inset-inline-end-1.5 w-2 h-2 rounded-full bg-danger" style="inset-inline-end:.375rem"></span>
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
