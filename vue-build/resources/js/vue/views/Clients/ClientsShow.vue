<script setup>
/** /admin/clients/{id} — Client detail (read-only). */
import Breadcrumb from '../../components/Breadcrumb.vue';
import BaseCard from '../../components/BaseCard.vue';
import BaseButton from '../../components/BaseButton.vue';
import StatusBadge from '../../components/StatusBadge.vue';
</script>

<template>
  <div>
    <Breadcrumb title="Client #10428" :trail="[{ label:'Clients', href:'#/admin/clients' }, { label:'Details' }]">
      <template #actions>
        <BaseButton variant="light" icon="ri-arrow-go-back-line">Back</BaseButton>
        <BaseButton variant="primary" icon="ri-pencil-line">Edit</BaseButton>
      </template>
    </Breadcrumb>
    <BaseCard title="Client information" icon="ri-information-line" class="max-w-3xl">
      <dl>
        <div class="flex items-start gap-4 py-3 border-b border-slate-100 dark:border-white/5 last:border-0">
          <dt class="w-44 shrink-0 text-sm text-slate-500">ID</dt>
          <dd class="text-sm font-medium text-ink dark:text-slate-100 " >10428</dd>
        </div>
        <div class="flex items-start gap-4 py-3 border-b border-slate-100 dark:border-white/5 last:border-0">
          <dt class="w-44 shrink-0 text-sm text-slate-500">Status</dt>
          <dd class="text-sm font-medium text-ink dark:text-slate-100 " ><StatusBadge status="ENABLED" /></dd>
        </div>
        <div class="flex items-start gap-4 py-3 border-b border-slate-100 dark:border-white/5 last:border-0">
          <dt class="w-44 shrink-0 text-sm text-slate-500">Arabic Name</dt>
          <dd class="text-sm font-medium text-ink dark:text-slate-100 " >Lab Pickup</dd>
        </div>
        <div class="flex items-start gap-4 py-3 border-b border-slate-100 dark:border-white/5 last:border-0">
          <dt class="w-44 shrink-0 text-sm text-slate-500">English Name</dt>
          <dd class="text-sm font-medium text-ink dark:text-slate-100 " >Cold Transfer</dd>
        </div>
        <div class="flex items-start gap-4 py-3 border-b border-slate-100 dark:border-white/5 last:border-0">
          <dt class="w-44 shrink-0 text-sm text-slate-500">Email</dt>
          <dd class="text-sm font-medium text-ink dark:text-slate-100 " >user5@mtc.sa</dd>
        </div>
        <div class="flex items-start gap-4 py-3 border-b border-slate-100 dark:border-white/5 last:border-0">
          <dt class="w-44 shrink-0 text-sm text-slate-500">Address</dt>
          <dd class="text-sm font-medium text-ink dark:text-slate-100 " >Item 6</dd>
        </div>
      </dl>
    </BaseCard>
  </div>
</template>
