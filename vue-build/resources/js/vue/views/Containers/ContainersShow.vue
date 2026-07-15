<script setup>
/** /admin/containers/{id} — Container detail (read-only). */
import Breadcrumb from '../../components/Breadcrumb.vue';
import BaseCard from '../../components/BaseCard.vue';
import BaseButton from '../../components/BaseButton.vue';
import StatusBadge from '../../components/StatusBadge.vue';
</script>

<template>
  <div>
    <Breadcrumb title="Container #10428" :trail="[{ label:'Containers', href:'#/admin/containers' }, { label:'Details' }]">
      <template #actions>
        <BaseButton variant="light" icon="ri-arrow-go-back-line">Back</BaseButton>
        <BaseButton variant="primary" icon="ri-pencil-line">Edit</BaseButton>
      </template>
    </Breadcrumb>
    <BaseCard title="Container information" icon="ri-information-line" class="max-w-3xl">
      <dl>
        <div class="flex items-start gap-4 py-3 border-b border-slate-100 dark:border-white/5 last:border-0">
          <dt class="w-44 shrink-0 text-sm text-slate-500">ID</dt>
          <dd class="text-sm font-medium text-ink dark:text-slate-100 " >10428</dd>
        </div>
        <div class="flex items-start gap-4 py-3 border-b border-slate-100 dark:border-white/5 last:border-0">
          <dt class="w-44 shrink-0 text-sm text-slate-500">Car</dt>
          <dd class="text-sm font-medium text-ink dark:text-slate-100 font-mono" style="direction:ltr">JED 2013</dd>
        </div>
        <div class="flex items-start gap-4 py-3 border-b border-slate-100 dark:border-white/5 last:border-0">
          <dt class="w-44 shrink-0 text-sm text-slate-500">Sensor</dt>
          <dd class="text-sm font-medium text-ink dark:text-slate-100 font-mono" style="direction:ltr">864920451014</dd>
        </div>
        <div class="flex items-start gap-4 py-3 border-b border-slate-100 dark:border-white/5 last:border-0">
          <dt class="w-44 shrink-0 text-sm text-slate-500">Type</dt>
          <dd class="text-sm font-medium text-ink dark:text-slate-100 " >Pickup</dd>
        </div>
        <div class="flex items-start gap-4 py-3 border-b border-slate-100 dark:border-white/5 last:border-0">
          <dt class="w-44 shrink-0 text-sm text-slate-500">Description</dt>
          <dd class="text-sm font-medium text-ink dark:text-slate-100 " >—</dd>
        </div>
        <div class="flex items-start gap-4 py-3 border-b border-slate-100 dark:border-white/5 last:border-0">
          <dt class="w-44 shrink-0 text-sm text-slate-500">Status</dt>
          <dd class="text-sm font-medium text-ink dark:text-slate-100 " ><StatusBadge status="ENABLED" /></dd>
        </div>
      </dl>
    </BaseCard>
  </div>
</template>
