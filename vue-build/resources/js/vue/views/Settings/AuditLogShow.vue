<script setup>
import Breadcrumb from '../../components/Breadcrumb.vue';
import BaseCard from '../../components/BaseCard.vue';
const meta=[['Description','Updated task status'],['Subject ID','10428'],['Subject Type','Task'],['User ID','3'],['Host','10.0.12.4'],['Created At','2026-06-27 09:14']];
const diff=[
  { field:'status', old:'COLLECTED', neu:'IN_CONTAINER' },
  { field:'container_date', old:'—', neu:'2026-06-27 08:30' },
  { field:'driver_id', old:'5', neu:'5' },
];
</script>
<template>
  <div>
    <Breadcrumb title="Audit Log #4821" :trail="[{label:'Audit Logs',href:'#/admin/audit-logs'},{label:'Details'}]" />
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-5">
      <BaseCard title="Entry" icon="ri-history-line">
        <dl class="space-y-2.5 text-sm"><div v-for="r in meta" :key="r[0]" class="flex justify-between gap-3"><dt class="text-slate-500">{{ r[0] }}</dt><dd class="font-medium text-end" :class="['Host','Subject ID','User ID'].includes(r[0])?'font-mono':''" style="direction:ltr">{{ r[1] }}</dd></div></dl>
      </BaseCard>
      <BaseCard title="Changes" subtitle="Old vs new" icon="ri-git-commit-line" class="lg:col-span-2" :padded="false">
        <div class="divide-y divide-slate-100 dark:divide-white/5">
          <div v-for="d in diff" :key="d.field" class="px-5 py-3.5">
            <div class="text-xs font-mono font-semibold text-slate-500 mb-2" style="direction:ltr">{{ d.field }}</div>
            <div class="flex items-center gap-3 flex-wrap">
              <span class="inline-flex items-center gap-1.5 px-2.5 h-7 rounded-lg bg-danger/8 text-danger text-sm font-mono line-through" style="direction:ltr">{{ d.old }}</span>
              <i class="ri-arrow-right-line text-slate-300 rtl:rotate-180"></i>
              <span class="inline-flex items-center gap-1.5 px-2.5 h-7 rounded-lg bg-success/10 text-success text-sm font-mono" style="direction:ltr">{{ d.neu }}</span>
            </div>
          </div>
        </div>
      </BaseCard>
    </div>
  </div>
</template>
