<script setup>
/** /admin/reports — Operational Insights hero + 3 launcher cards. */
import { ref } from 'vue';
import BaseButton from '../../components/BaseButton.vue';
const daily=ref(''); const wStart=ref(''); const wEnd=ref(''); const month=ref('');
const cards=[
  { key:'daily', title:'Daily Report', desc:'Operational logs, check-ins, delays for a single day.', icon:'ri-calendar-event-line', cta:'Generate Daily' },
  { key:'weekly', title:'Weekly Performance', desc:'Driver consistency, overtime and punctuality over a range.', icon:'ri-calendar-line', cta:'View Weekly' },
  { key:'monthly', title:'Monthly Evaluation', desc:'Full ranking leaderboard with violations and scores.', icon:'ri-medal-line', cta:'Monthly Ranking' },
];
</script>
<template>
  <div>
    <div class="relative overflow-hidden rounded-2xl p-8 mb-6 text-white shadow-card" style="background:linear-gradient(120deg,#005D69,#0d9488)">
      <div class="absolute -top-10 -right-10 w-48 h-48 rounded-full bg-white/10"></div>
      <div class="absolute -bottom-16 -left-8 w-56 h-56 rounded-full bg-white/5"></div>
      <div class="relative">
        <h1 class="text-2xl font-bold flex items-center gap-2"><i class="ri-bar-chart-box-line"></i> Operational Insights</h1>
        <p class="text-white/80 mt-2 max-w-lg text-sm">Generate performance and evaluation reports across drivers, shifts and operations.</p>
      </div>
    </div>
    <div class="grid grid-cols-1 md:grid-cols-3 gap-5">
      <div v-for="c in cards" :key="c.key" class="bg-surface dark:bg-slate-800/60 rounded-2xl shadow-card border border-slate-100 dark:border-white/5 p-6 flex flex-col transition-all hover:shadow-card-hover hover:-translate-y-1">
        <span class="grid place-items-center w-12 h-12 rounded-xl bg-primary-50 text-primary-700 dark:bg-primary-500/15 dark:text-primary-300 mb-4"><i :class="[c.icon,'text-2xl']"></i></span>
        <h3 class="font-semibold text-ink dark:text-slate-100 text-lg">{{ c.title }}</h3>
        <p class="text-sm text-slate-500 mt-1 flex-1">{{ c.desc }}</p>
        <div class="mt-4 space-y-2">
          <input v-if="c.key==='daily'" v-model="daily" type="date" class="w-full h-10 px-3 rounded-xl border border-slate-200 dark:border-white/10 bg-surface dark:bg-slate-900/40 text-sm focus:ring-2 focus:ring-primary-500/30 focus:outline-none" />
          <div v-else-if="c.key==='weekly'" class="grid grid-cols-2 gap-2">
            <input v-model="wStart" type="date" class="h-10 px-3 rounded-xl border border-slate-200 dark:border-white/10 bg-surface dark:bg-slate-900/40 text-sm focus:ring-2 focus:ring-primary-500/30 focus:outline-none" />
            <input v-model="wEnd" type="date" class="h-10 px-3 rounded-xl border border-slate-200 dark:border-white/10 bg-surface dark:bg-slate-900/40 text-sm focus:ring-2 focus:ring-primary-500/30 focus:outline-none" />
          </div>
          <input v-else v-model="month" type="month" class="w-full h-10 px-3 rounded-xl border border-slate-200 dark:border-white/10 bg-surface dark:bg-slate-900/40 text-sm focus:ring-2 focus:ring-primary-500/30 focus:outline-none" />
          <BaseButton variant="primary" block icon="ri-arrow-right-line">{{ c.cta }}</BaseButton>
        </div>
      </div>
    </div>
  </div>
</template>
