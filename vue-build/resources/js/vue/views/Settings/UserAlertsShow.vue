<script setup>
/** /admin/user-alerts/{id} — User Alert detail (read-only). */
import Breadcrumb from '../../components/Breadcrumb.vue';
import BaseCard from '../../components/BaseCard.vue';
import BaseButton from '../../components/BaseButton.vue';
</script>

<template>
  <div>
    <Breadcrumb title="User Alert #10428" :trail="[{ label:'User Alerts', href:'#/admin/user-alerts' }, { label:'Details' }]">
      <template #actions>
        <BaseButton variant="light" icon="ri-arrow-go-back-line">Back</BaseButton>
        <BaseButton variant="primary" icon="ri-pencil-line">Edit</BaseButton>
      </template>
    </Breadcrumb>
    <BaseCard title="User Alert information" icon="ri-information-line" class="max-w-3xl">
      <dl>
        <div class="flex items-start gap-4 py-3 border-b border-slate-100 dark:border-white/5 last:border-0">
          <dt class="w-44 shrink-0 text-sm text-slate-500">ID</dt>
          <dd class="text-sm font-medium text-ink dark:text-slate-100 " >10428</dd>
        </div>
        <div class="flex items-start gap-4 py-3 border-b border-slate-100 dark:border-white/5 last:border-0">
          <dt class="w-44 shrink-0 text-sm text-slate-500">Alert Text</dt>
          <dd class="text-sm font-medium text-ink dark:text-slate-100 " >—</dd>
        </div>
        <div class="flex items-start gap-4 py-3 border-b border-slate-100 dark:border-white/5 last:border-0">
          <dt class="w-44 shrink-0 text-sm text-slate-500">Alert Link</dt>
          <dd class="text-sm font-medium text-ink dark:text-slate-100 font-mono" style="direction:ltr">Item 3</dd>
        </div>
        <div class="flex items-start gap-4 py-3 border-b border-slate-100 dark:border-white/5 last:border-0">
          <dt class="w-44 shrink-0 text-sm text-slate-500">Created At</dt>
          <dd class="text-sm font-medium text-ink dark:text-slate-100 " >2026-06-24</dd>
        </div>
      </dl>
    </BaseCard>
  </div>
</template>
