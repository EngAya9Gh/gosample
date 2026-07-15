<script setup>
import Breadcrumb from '../../components/Breadcrumb.vue';
import BaseCard from '../../components/BaseCard.vue';
const perms=['task_access','task_create','task_show','driver_access','car_access','client_access','sample_access','report_access'];
</script>
<template>
  <div>
    <Breadcrumb title="Role #3 — Dispatcher" :trail="[{label:'Roles',href:'#/admin/roles'},{label:'Details'}]" />
    <BaseCard title="Permissions" icon="ri-key-2-line">
      <div class="flex flex-wrap gap-2"><span v-for="p in perms" :key="p" class="inline-flex items-center px-3 h-8 rounded-lg bg-primary-50 dark:bg-primary-500/15 text-primary-700 dark:text-primary-300 text-xs font-mono" style="direction:ltr">{{ p }}</span></div>
    </BaseCard>
  </div>
</template>
