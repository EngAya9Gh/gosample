<script setup>
/** /admin/shipments/{id} — Shipment detail (read-only). */
import Breadcrumb from '../../components/Breadcrumb.vue';
import BaseCard from '../../components/BaseCard.vue';
import BaseButton from '../../components/BaseButton.vue';
import StatusBadge from '../../components/StatusBadge.vue';
</script>

<template>
  <div>
    <Breadcrumb title="Shipment #10428" :trail="[{ label:'Shipments', href:'#/admin/shipments' }, { label:'Details' }]">
      <template #actions>
        <BaseButton variant="light" icon="ri-arrow-go-back-line">Back</BaseButton>
        <BaseButton variant="primary" icon="ri-pencil-line">Edit</BaseButton>
      </template>
    </Breadcrumb>
    <BaseCard title="Shipment information" icon="ri-information-line" class="max-w-3xl">
      <dl>
        <div class="flex items-start gap-4 py-3 border-b border-slate-100 dark:border-white/5 last:border-0">
          <dt class="w-44 shrink-0 text-sm text-slate-500">ID</dt>
          <dd class="text-sm font-medium text-ink dark:text-slate-100 " >10428</dd>
        </div>
        <div class="flex items-start gap-4 py-3 border-b border-slate-100 dark:border-white/5 last:border-0">
          <dt class="w-44 shrink-0 text-sm text-slate-500">Carrier</dt>
          <dd class="text-sm font-medium text-ink dark:text-slate-100 " >Fatimah Nasser</dd>
        </div>
        <div class="flex items-start gap-4 py-3 border-b border-slate-100 dark:border-white/5 last:border-0">
          <dt class="w-44 shrink-0 text-sm text-slate-500">Location From</dt>
          <dd class="text-sm font-medium text-ink dark:text-slate-100 " >Lab West</dd>
        </div>
        <div class="flex items-start gap-4 py-3 border-b border-slate-100 dark:border-white/5 last:border-0">
          <dt class="w-44 shrink-0 text-sm text-slate-500">Location To</dt>
          <dd class="text-sm font-medium text-ink dark:text-slate-100 " >North Depot</dd>
        </div>
        <div class="flex items-start gap-4 py-3 border-b border-slate-100 dark:border-white/5 last:border-0">
          <dt class="w-44 shrink-0 text-sm text-slate-500">Reference Number</dt>
          <dd class="text-sm font-medium text-ink dark:text-slate-100 font-mono" style="direction:ltr">REF-90068</dd>
        </div>
        <div class="flex items-start gap-4 py-3 border-b border-slate-100 dark:border-white/5 last:border-0">
          <dt class="w-44 shrink-0 text-sm text-slate-500">Pickup OTP</dt>
          <dd class="text-sm font-medium text-ink dark:text-slate-100 font-mono" style="direction:ltr">101355</dd>
        </div>
        <div class="flex items-start gap-4 py-3 border-b border-slate-100 dark:border-white/5 last:border-0">
          <dt class="w-44 shrink-0 text-sm text-slate-500">Drop-off OTP</dt>
          <dd class="text-sm font-medium text-ink dark:text-slate-100 font-mono" style="direction:ltr">101626</dd>
        </div>
        <div class="flex items-start gap-4 py-3 border-b border-slate-100 dark:border-white/5 last:border-0">
          <dt class="w-44 shrink-0 text-sm text-slate-500">Status</dt>
          <dd class="text-sm font-medium text-ink dark:text-slate-100 " ><StatusBadge status="ENABLED" /></dd>
        </div>
        <div class="flex items-start gap-4 py-3 border-b border-slate-100 dark:border-white/5 last:border-0">
          <dt class="w-44 shrink-0 text-sm text-slate-500">Batch</dt>
          <dd class="text-sm font-medium text-ink dark:text-slate-100 font-mono" style="direction:ltr">Item 9</dd>
        </div>
        <div class="flex items-start gap-4 py-3 border-b border-slate-100 dark:border-white/5 last:border-0">
          <dt class="w-44 shrink-0 text-sm text-slate-500">Task</dt>
          <dd class="text-sm font-medium text-ink dark:text-slate-100 font-mono" style="direction:ltr">Item 10</dd>
        </div>
      </dl>
    </BaseCard>
  </div>
</template>
