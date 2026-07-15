<script setup>
import { ref, computed } from 'vue';
import Breadcrumb from '../../components/Breadcrumb.vue';
import BaseButton from '../../components/BaseButton.vue';
const q=ref('');
const roles=[
  { id:1, name:'Super Admin', perms:120, max:120, crown:true },
  { id:2, name:'Admin', perms:98, max:120, crown:true },
  { id:3, name:'Dispatcher', perms:54, max:120 },
  { id:4, name:'Operations Manager', perms:76, max:120 },
  { id:5, name:'Viewer', perms:22, max:120 },
  { id:6, name:'Client User', perms:14, max:120 },
];
const filtered=computed(()=>roles.filter(r=>r.name.toLowerCase().includes(q.value.toLowerCase())));
const totalPerms=roles.reduce((a,r)=>a+r.perms,0);
const GRAD=['from-primary-500 to-primary-700','from-info to-secondary','from-success to-primary-600','from-danger to-primary-700','from-amber-400 to-primary-600','from-secondary to-primary-700'];
</script>
<template>
  <div>
    <Breadcrumb title="Roles" :trail="[{label:'Users'},{label:'Roles'}]"><template #actions><BaseButton variant="primary" icon="ri-add-line">Add Role</BaseButton></template></Breadcrumb>
    <div class="flex flex-wrap items-center gap-3 mb-5">
      <div class="inline-flex items-center gap-2 px-3.5 h-10 rounded-xl bg-primary-50 dark:bg-primary-500/15 text-primary-700 dark:text-primary-300 text-sm font-medium"><i class="ri-vip-crown-line"></i>{{ roles.length }} roles</div>
      <div class="inline-flex items-center gap-2 px-3.5 h-10 rounded-xl bg-surface dark:bg-slate-800 border border-slate-200 dark:border-white/10 text-sm font-medium text-slate-600 dark:text-slate-300"><i class="ri-key-2-line"></i>{{ totalPerms }} assignments</div>
      <div class="relative ms-auto w-full sm:w-64"><i class="ri-search-line absolute top-1/2 -translate-y-1/2 inset-inline-start-3 text-slate-400" style="inset-inline-start:.75rem"></i><input v-model="q" placeholder="Search roles…" class="w-full h-10 ps-10 pe-3 rounded-xl border border-slate-200 dark:border-white/10 bg-surface dark:bg-slate-900/40 text-sm focus:ring-2 focus:ring-primary-500/30 focus:outline-none" /></div>
    </div>
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
      <div v-for="(r,i) in filtered" :key="r.id" class="bg-surface dark:bg-slate-800/60 rounded-2xl shadow-card border border-slate-100 dark:border-white/5 p-5 transition-all hover:shadow-card-hover hover:-translate-y-1">
        <div class="flex items-center gap-3 mb-4">
          <span class="grid place-items-center w-12 h-12 rounded-xl text-white font-bold bg-gradient-to-br shadow-sm" :class="GRAD[i%6]"><i v-if="r.crown" class="ri-vip-crown-fill"></i><template v-else>{{ r.name[0] }}</template></span>
          <div class="flex-1 min-w-0"><h3 class="font-semibold text-ink dark:text-slate-100 truncate">{{ r.name }}</h3><span class="text-xs text-slate-400 font-mono">ID #{{ r.id }}</span></div>
        </div>
        <div class="flex items-center justify-between text-xs mb-1.5"><span class="text-slate-500">{{ r.perms }} permissions</span><span class="text-slate-400">{{ Math.round(r.perms/r.max*100) }}%</span></div>
        <div class="h-2 rounded-full bg-surface-muted dark:bg-white/10 overflow-hidden mb-4"><div class="h-full rounded-full bg-gradient-to-r from-primary-400 to-primary-600 transition-all duration-700" :style="{width:(r.perms/r.max*100)+'%'}"></div></div>
        <div class="flex items-center gap-1 pt-3 border-t border-slate-100 dark:border-white/5">
          <button class="grid place-items-center w-8 h-8 rounded-lg text-info hover:bg-info/10"><i class="ri-eye-line"></i></button>
          <button class="grid place-items-center w-8 h-8 rounded-lg text-primary-600 hover:bg-primary-50"><i class="ri-pencil-line"></i></button>
          <button class="grid place-items-center w-8 h-8 rounded-lg text-danger hover:bg-danger/10 ms-auto"><i class="ri-delete-bin-line"></i></button>
        </div>
      </div>
    </div>
  </div>
</template>
