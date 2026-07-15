<script setup>
/** /admin/money-transfers/{id} — Money Transfer detail (read-only). */
import Breadcrumb from '../../components/Breadcrumb.vue';
import BaseCard from '../../components/BaseCard.vue';
import BaseButton from '../../components/BaseButton.vue';
import StatusBadge from '../../components/StatusBadge.vue';
</script>

<template>
  <div>
    <Breadcrumb title="Money Transfer #10428" :trail="[{ label:'Money Transfers', href:'#/admin/money-transfers' }, { label:'Details' }]">
      <template #actions>
        <BaseButton variant="light" icon="ri-arrow-go-back-line">Back</BaseButton>
        <BaseButton variant="primary" icon="ri-pencil-line">Edit</BaseButton>
      </template>
    </Breadcrumb>
    <BaseCard title="Money Transfer information" icon="ri-information-line" class="max-w-3xl">
      <dl>
        <div class="flex items-start gap-4 py-3 border-b border-slate-100 dark:border-white/5 last:border-0">
          <dt class="w-44 shrink-0 text-sm text-slate-500">ID</dt>
          <dd class="text-sm font-medium text-ink dark:text-slate-100 " >10428</dd>
        </div>
        <div class="flex items-start gap-4 py-3 border-b border-slate-100 dark:border-white/5 last:border-0">
          <dt class="w-44 shrink-0 text-sm text-slate-500">Driver</dt>
          <dd class="text-sm font-medium text-ink dark:text-slate-100 " >Fatimah Nasser</dd>
        </div>
        <div class="flex items-start gap-4 py-3 border-b border-slate-100 dark:border-white/5 last:border-0">
          <dt class="w-44 shrink-0 text-sm text-slate-500">Client</dt>
          <dd class="text-sm font-medium text-ink dark:text-slate-100 " >Dallah Hospital</dd>
        </div>
        <div class="flex items-start gap-4 py-3 border-b border-slate-100 dark:border-white/5 last:border-0">
          <dt class="w-44 shrink-0 text-sm text-slate-500">From Location</dt>
          <dd class="text-sm font-medium text-ink dark:text-slate-100 " >North Depot</dd>
        </div>
        <div class="flex items-start gap-4 py-3 border-b border-slate-100 dark:border-white/5 last:border-0">
          <dt class="w-44 shrink-0 text-sm text-slate-500">To Location</dt>
          <dd class="text-sm font-medium text-ink dark:text-slate-100 " >King Faisal Lab</dd>
        </div>
        <div class="flex items-start gap-4 py-3 border-b border-slate-100 dark:border-white/5 last:border-0">
          <dt class="w-44 shrink-0 text-sm text-slate-500">Status</dt>
          <dd class="text-sm font-medium text-ink dark:text-slate-100 " ><StatusBadge status="ENABLED" /></dd>
        </div>
        <div class="flex items-start gap-4 py-3 border-b border-slate-100 dark:border-white/5 last:border-0">
          <dt class="w-44 shrink-0 text-sm text-slate-500">From OTP</dt>
          <dd class="text-sm font-medium text-ink dark:text-slate-100 font-mono" style="direction:ltr">101626</dd>
        </div>
        <div class="flex items-start gap-4 py-3 border-b border-slate-100 dark:border-white/5 last:border-0">
          <dt class="w-44 shrink-0 text-sm text-slate-500">To OTP</dt>
          <dd class="text-sm font-medium text-ink dark:text-slate-100 font-mono" style="direction:ltr">101897</dd>
        </div>
        <div class="flex items-start gap-4 py-3 border-b border-slate-100 dark:border-white/5 last:border-0">
          <dt class="w-44 shrink-0 text-sm text-slate-500">Amount</dt>
          <dd class="text-sm font-medium text-ink dark:text-slate-100 " >850 SAR</dd>
        </div>
      </dl>
    </BaseCard>
  </div>
</template>
