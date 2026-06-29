<script setup>
import { ref } from 'vue';
import Breadcrumb from '../../components/Breadcrumb.vue';
import BaseCard from '../../components/BaseCard.vue';
import BaseAvatar from '../../components/BaseAvatar.vue';
import TabGroup from '../../components/TabGroup.vue';
import DataTable from '../../components/DataTable.vue';
import StatusBadge from '../../components/StatusBadge.vue';
const tab=ref('shifts'); const rtab=ref('tasks');
const tabs=[{key:'shifts',label:'Working Shifts',icon:'ri-time-line'},{key:'personal',label:'Personal Info',icon:'ri-profile-line'}];
const rtabs=[{key:'tasks',label:'Tasks',icon:'ri-task-line'},{key:'cars',label:'Car Link History',icon:'ri-links-line'}];
const shifts=[{n:1,s:'08:00',e:'16:00'},{n:2,s:'16:00',e:'00:00'}];
const taskCols=[{key:'id',label:'ID'},{key:'from',label:'From'},{key:'to',label:'To'},{key:'client',label:'Client'},{key:'status',label:'Status'}];
const taskRows=Array.from({length:6},(_,i)=>({id:10428-i,from:'Central Hub',to:'Lab East',client:'King Faisal Lab',status:['CLOSED','COLLECTED','NEW'][i%3]}));
const carCols=[{key:'id',label:'ID'},{key:'imei',label:'Car',mono:true},{key:'action',label:'Action'},{key:'created',label:'Created At'}];
const carRows=Array.from({length:4},(_,i)=>({id:i+1,imei:'864920451'+i,action:i%2?'Linked':'Unlinked',created:'2026-06-0'+(i+1)}));
</script>
<template>
  <div>
    <Breadcrumb title="Mohammed Al-Harbi" :trail="[{label:'Drivers',href:'#/admin/drivers'},{label:'Profile'}]" />
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-5">
      <!-- left -->
      <div class="space-y-5">
        <BaseCard>
          <div class="flex flex-col items-center text-center">
            <BaseAvatar name="Mohammed Al-Harbi" :size="88" />
            <h2 class="text-lg font-bold text-ink dark:text-slate-50 mt-3">Mohammed Al-Harbi</h2>
            <p class="text-sm text-slate-400 font-mono" style="direction:ltr">mohammed.h</p>
            <div class="flex items-center gap-2 mt-3"><StatusBadge status="ENABLED" label="Active" /><span class="inline-flex items-center px-2.5 h-6 rounded-full bg-info/10 text-info text-[11.5px] font-semibold">English</span></div>
          </div>
          <div class="grid grid-cols-2 gap-3 mt-5">
            <div class="bg-surface-muted dark:bg-white/5 rounded-xl p-3 text-center"><div class="text-xs text-slate-400">Mobile</div><div class="text-sm font-mono font-medium mt-0.5" style="direction:ltr">+966 50 111</div></div>
            <div class="bg-surface-muted dark:bg-white/5 rounded-xl p-3 text-center"><div class="text-xs text-slate-400">Zone</div><div class="text-sm font-medium mt-0.5">North</div></div>
          </div>
        </BaseCard>
        <BaseCard title="Attendance" icon="ri-pie-chart-line">
          <div class="space-y-4">
            <div><div class="flex justify-between text-xs mb-1"><span class="text-slate-500">Punctuality Score</span><span class="font-semibold">92%</span></div><div class="h-2 rounded-full bg-surface-muted dark:bg-white/10 overflow-hidden"><div class="h-full rounded-full bg-success" style="width:92%"></div></div></div>
            <div><div class="flex justify-between text-xs mb-1"><span class="text-slate-500">Shift Completion</span><span class="font-semibold">88%</span></div><div class="h-2 rounded-full bg-surface-muted dark:bg-white/10 overflow-hidden"><div class="h-full rounded-full bg-primary-600" style="width:88%"></div></div></div>
          </div>
        </BaseCard>
      </div>
      <!-- right -->
      <div class="lg:col-span-2 space-y-5">
        <BaseCard :padded="false">
          <template #header><TabGroup :tabs="tabs" v-model:active="tab" /></template>
          <div class="p-5">
            <div v-if="tab==='shifts'">
              <div class="flex items-center justify-between mb-3"><h4 class="font-semibold text-ink dark:text-slate-100">Operational Schedule</h4><span class="inline-flex items-center px-2.5 h-6 rounded-full bg-primary-50 text-primary-700 dark:bg-primary-500/15 dark:text-primary-300 text-xs font-semibold">16 hrs total</span></div>
              <div class="grid sm:grid-cols-2 gap-3">
                <div v-for="s in shifts" :key="s.n" class="p-4 rounded-xl border border-slate-100 dark:border-white/10"><div class="text-xs text-slate-400 mb-1">Shift {{ s.n }}</div><div class="text-lg font-mono font-semibold text-ink dark:text-slate-100" style="direction:ltr">{{ s.s }} – {{ s.e }}</div></div>
              </div>
            </div>
            <dl v-else class="space-y-1">
              <div class="flex gap-4 py-2.5 border-b border-slate-100 dark:border-white/5"><dt class="w-40 text-sm text-slate-500">National ID</dt><dd class="text-sm font-medium font-mono" style="direction:ltr">1098 7654 32</dd></div>
              <div class="flex gap-4 py-2.5 border-b border-slate-100 dark:border-white/5"><dt class="w-40 text-sm text-slate-500">Email</dt><dd class="text-sm font-medium">mohammed@mtc.sa</dd></div>
              <div class="flex gap-4 py-2.5"><dt class="w-40 text-sm text-slate-500">Current Location</dt><dd class="text-sm font-medium text-primary-600"><a href="#" class="inline-flex items-center gap-1"><i class="ri-map-pin-line"></i>Open in Maps</a></dd></div>
            </dl>
          </div>
        </BaseCard>
        <BaseCard :padded="false">
          <template #header><TabGroup :tabs="rtabs" v-model:active="rtab" variant="pills" /></template>
          <div class="p-5">
            <DataTable v-if="rtab==='tasks'" :columns="taskCols" :rows="taskRows" :selectable="false" :exportable="false" :searchable="false">
              <template #cell-id="{ value }"><span class="font-semibold text-primary-700">#{{ value }}</span></template>
              <template #cell-status="{ value }"><StatusBadge :status="value" /></template>
            </DataTable>
            <DataTable v-else :columns="carCols" :rows="carRows" :selectable="false" :exportable="false" :searchable="false" />
          </div>
        </BaseCard>
      </div>
    </div>
  </div>
</template>
