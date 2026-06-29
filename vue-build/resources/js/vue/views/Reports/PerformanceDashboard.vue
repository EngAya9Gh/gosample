<script setup>
import Breadcrumb from '../../components/Breadcrumb.vue';
import BaseAvatar from '../../components/BaseAvatar.vue';
const drivers=[
  { name:'Khalid Otaibi', punct:98, speed:22, violations:0 },
  { name:'Mohammed Al-Harbi', punct:91, speed:28, violations:1 },
  { name:'Fatimah Nasser', punct:74, speed:35, violations:2 },
  { name:'Sara Al-Dosari', punct:48, speed:44, violations:4 },
  { name:'Yousef Qahtani', punct:86, speed:26, violations:1 },
  { name:'Noura Faisal', punct:82, speed:30, violations:0 },
];
</script>
<template>
  <div>
    <Breadcrumb title="KPI Performance Analysis" :trail="[{label:'Reports'},{label:'KPI Performance'}]" />
    <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-5">
      <div v-for="d in drivers" :key="d.name" class="bg-surface dark:bg-slate-800/60 rounded-2xl shadow-card border border-slate-100 dark:border-white/5 p-5 transition-all hover:shadow-card-hover hover:-translate-y-1">
        <div class="flex items-center gap-3 mb-4">
          <BaseAvatar :name="d.name" :size="44" />
          <div class="flex-1 min-w-0"><h3 class="font-semibold text-ink dark:text-slate-100 truncate">{{ d.name }}</h3><span class="inline-flex items-center gap-1 text-[11px] font-medium text-success bg-success/10 px-2 h-5 rounded-full mt-0.5"><span class="w-1.5 h-1.5 rounded-full bg-success"></span>Active Carrier</span></div>
        </div>
        <div class="space-y-3.5">
          <div>
            <div class="flex items-center justify-between text-xs mb-1"><span class="text-slate-500">Punctuality</span><span class="font-semibold text-ink dark:text-slate-200">{{ d.punct }}%</span></div>
            <div class="h-2 rounded-full bg-surface-muted dark:bg-white/10 overflow-hidden"><div class="h-full rounded-full bg-gradient-to-r from-primary-400 to-primary-600 transition-all duration-700" :style="{width:d.punct+'%'}"></div></div>
          </div>
          <div class="flex items-center justify-between"><span class="text-xs text-slate-500">Operation Speed</span><span class="text-sm font-semibold text-ink dark:text-slate-200">{{ d.speed }} min avg</span></div>
          <div class="flex items-center justify-between"><span class="text-xs text-slate-500">Operational Violations</span><span class="inline-flex items-center px-2 h-6 rounded-full text-[11.5px] font-semibold" :class="d.violations?'bg-danger/10 text-danger':'bg-slate-100 text-slate-500'">{{ d.violations }}</span></div>
        </div>
        <a href="#" class="flex items-center justify-center gap-1.5 mt-4 pt-3.5 border-t border-slate-100 dark:border-white/5 text-sm font-medium text-primary-600 hover:text-primary-700"><i class="ri-user-line"></i>View profile</a>
      </div>
    </div>
  </div>
</template>
