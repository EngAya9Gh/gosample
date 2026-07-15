<script setup>
import { ref } from 'vue';
import Breadcrumb from '../../components/Breadcrumb.vue';
import BaseButton from '../../components/BaseButton.vue';
import BaseCard from '../../components/BaseCard.vue';
import BaseAvatar from '../../components/BaseAvatar.vue';
const month=ref('2026-06');
const rows=[
  { name:'Khalid Otaibi', present:24, absent:0, late:1, violations:0, delay:35, balance:8.5, score:98 },
  { name:'Mohammed Al-Harbi', present:23, absent:1, late:2, violations:1, delay:80, balance:2.0, score:91 },
  { name:'Fatimah Nasser', present:22, absent:2, late:5, violations:2, delay:140, balance:-3.5, score:74 },
  { name:'Sara Al-Dosari', present:18, absent:6, late:9, violations:4, delay:260, balance:-12.0, score:48 },
];
const medal=i=>['🥇','🥈','🥉'][i]||((i+1)+'th');
const sCls=s=>s>=80?'bg-success/10 text-success':s>=50?'bg-warning/15 text-amber-600':'bg-danger/10 text-danger';
</script>
<template>
  <div>
    <Breadcrumb title="Monthly Evaluation" :trail="[{label:'Reports'},{label:'Monthly'}]">
      <template #actions>
        <input v-model="month" type="month" class="h-10 px-3 rounded-xl border border-slate-200 dark:border-white/10 bg-surface dark:bg-slate-900/40 text-sm focus:ring-2 focus:ring-primary-500/30 focus:outline-none" />
        <BaseButton variant="light" icon="ri-file-excel-2-line">Export</BaseButton>
        <BaseButton variant="light" icon="ri-printer-line">Print</BaseButton>
      </template>
    </Breadcrumb>
    <BaseCard title="Driver Rankings" subtitle="Expected working days: 24" icon="ri-medal-line" :padded="false">
      <div class="overflow-x-auto"><table class="w-full text-sm">
        <thead><tr class="bg-surface-muted/60 dark:bg-white/5 text-slate-500 text-[12px] uppercase tracking-wider">
          <th class="text-center px-4 py-3 font-semibold">Rank</th><th class="text-start px-3 py-3 font-semibold">Driver</th><th class="text-center px-3 py-3 font-semibold">Present</th><th class="text-center px-3 py-3 font-semibold">Absent</th><th class="text-center px-3 py-3 font-semibold">Late</th><th class="text-center px-3 py-3 font-semibold">Violations</th><th class="text-center px-3 py-3 font-semibold">Delay (min)</th><th class="text-center px-3 py-3 font-semibold">Hrs Balance</th><th class="text-start px-3 py-3 font-semibold">Score</th><th class="text-end px-5 py-3 font-semibold">Profile</th>
        </tr></thead>
        <tbody>
          <tr v-for="(r,i) in rows" :key="r.name" class="border-t border-slate-100 dark:border-white/5 hover:bg-surface-muted/40 dark:hover:bg-white/5">
            <td class="px-4 py-3 text-center text-lg">{{ medal(i) }}</td>
            <td class="px-3 py-3"><div class="flex items-center gap-2.5"><BaseAvatar :name="r.name" :size="34" /><span class="font-medium text-ink dark:text-slate-100">{{ r.name }}</span></div></td>
            <td class="px-3 py-3 text-center tabular-nums">{{ r.present }}</td>
            <td class="px-3 py-3 text-center tabular-nums">{{ r.absent }}</td>
            <td class="px-3 py-3 text-center tabular-nums">{{ r.late }}</td>
            <td class="px-3 py-3 text-center"><span class="inline-flex items-center px-2 h-6 rounded-full text-[11.5px] font-semibold" :class="r.violations?'bg-danger/10 text-danger':'bg-slate-100 text-slate-500'">{{ r.violations }}</span></td>
            <td class="px-3 py-3 text-center tabular-nums">{{ r.delay }}</td>
            <td class="px-3 py-3 text-center tabular-nums font-medium" :class="r.balance>=0?'text-success':'text-danger'">{{ r.balance>0?'+':'' }}{{ r.balance }}</td>
            <td class="px-3 py-3"><div class="flex items-center gap-2 w-32"><div class="flex-1 h-2 rounded-full bg-surface-muted dark:bg-white/10 overflow-hidden"><div class="h-full rounded-full transition-all duration-700" :class="r.score>=80?'bg-success':r.score>=50?'bg-warning':'bg-danger'" :style="{width:r.score+'%'}"></div></div><span class="text-[11px] font-semibold px-1.5 h-5 grid place-items-center rounded-full" :class="sCls(r.score)">{{ r.score }}%</span></div></td>
            <td class="px-5 py-3 text-end"><button class="grid place-items-center w-8 h-8 rounded-lg text-info hover:bg-info/10 ms-auto"><i class="ri-eye-line"></i></button></td>
          </tr>
        </tbody>
      </table></div>
    </BaseCard>
  </div>
</template>
