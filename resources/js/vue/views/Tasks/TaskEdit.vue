<script setup>
/**
 * views/Tasks/TaskEdit.vue — SPA Edit Task page (replaces the edit modal that
 * used to open from the tasks list). Fields, options and the submit endpoint are
 * the same ones the modal used (App\TasksController@edit → @update, which
 * delegates to Admin\TasksController@update so CLOSED side effects stay 1:1).
 */
import { useForm, router } from '@inertiajs/vue3';
import Breadcrumb from '../../components/Breadcrumb.vue';
import BaseCard from '../../components/BaseCard.vue';
import BaseButton from '../../components/BaseButton.vue';
import StatusBadge from '../../components/StatusBadge.vue';
import FormSelect from '../../components/FormSelect.vue';
import { usePermissions } from '../../composables/usePermissions';

const props = defineProps({
  task:    { type: Object, default: () => ({}) },
  options: { type: Object, default: () => ({}) },
});

const { can } = usePermissions();

const driverOpts   = props.options?.drivers   || [];
const clientOpts   = props.options?.clients   || [];
const locationOpts = props.options?.locations || [];

// Enum options from App\Models\Task (TAKASI_SELECT / TASK_TYPE_SELECT / STATUS_SELECT).
const TAKASI_OPTS    = [{ value: 'NO', label: 'NO' }, { value: 'YES', label: 'YES' }];
const TASK_TYPE_OPTS = [{ value: 'SAMPLE', label: 'SAMPLE' }, { value: 'BOX', label: 'BOX' }];
const STATUS_OPTS = ['NEW', 'COLLECTED', 'CLOSED', 'IN_FREEZER', 'NO_SAMPLES', 'OUT_FREEZER']
  .map((s) => ({ value: s, label: s }));

const form = useForm({
  from_location:  props.task.from_location ?? '',
  to_location:    props.task.to_location ?? '',
  billing_client: props.task.billing_client ?? '',
  driver_id:      props.task.driver_id ?? '',
  task_type:      props.task.task_type ?? '',
  status:         props.task.status ?? '',
  takasi:         props.task.takasi ?? '',
});

// Read-only header context (same values the list row shows).
const meta = [
  { icon: 'ri-calendar-line',  label: 'Order Date',      value: props.task.created_at },
  { icon: 'ri-inbox-line',     label: 'Collection Date', value: props.task.collection_date },
  { icon: 'ri-check-double-line', label: 'Close Date',   value: props.task.close_date },
];

function submit() {
  // Same endpoint the old modal used; on success it redirects back to the list.
  form.put(`/admin/tasks/${props.task.id}/popup`, { preserveScroll: true });
}
function cancel() {
  router.visit('/admin/tasks');
}
</script>

<template>
  <div class="max-w-4xl mx-auto pb-12">
    <Breadcrumb
      :title="`Edit Task #${task.id}`"
      :trail="[{ label: 'Tasks', href: '/admin/tasks' }, { label: `#${task.id}` }, { label: 'Edit' }]"
    >
      <template #actions>
        <BaseButton v-if="can('task_show')" variant="light" icon="ri-eye-line" @click="router.visit(`/admin/tasks/${task.id}`)">
          View Details
        </BaseButton>
      </template>
    </Breadcrumb>

    <!-- read-only context strip -->
    <BaseCard class="mb-5">
      <div class="flex flex-wrap items-center gap-x-8 gap-y-4">
        <div class="flex items-center gap-2">
          <span class="text-xs font-semibold text-slate-500 dark:text-slate-400">Current Status</span>
          <StatusBadge :status="task.status" />
        </div>
        <div v-for="m in meta" :key="m.label" class="flex items-center gap-2 min-w-0">
          <i :class="[m.icon, 'text-primary-500']"></i>
          <div class="min-w-0">
            <p class="text-[11px] font-semibold text-slate-400 dark:text-slate-500 uppercase tracking-wide">{{ m.label }}</p>
            <p class="text-[13px] font-bold text-ink dark:text-slate-100 truncate" dir="ltr">{{ m.value || '—' }}</p>
          </div>
        </div>
      </div>
    </BaseCard>

    <form @submit.prevent="submit" class="space-y-6">
      <BaseCard :padded="false">
        <div class="p-5 border-b border-slate-100 dark:border-white/5">
          <h2 class="text-base font-bold text-ink dark:text-white flex items-center gap-2">
            <i class="ri-pencil-line text-primary-500"></i>
            Task Details
          </h2>
        </div>
        <div class="p-5 grid grid-cols-1 sm:grid-cols-2 gap-5">
          <FormSelect v-model="form.from_location" label="From Location" :options="locationOpts"
            placeholder="Select Location" required :error="form.errors.from_location" />
          <FormSelect v-model="form.to_location" label="To Location" :options="locationOpts"
            placeholder="Select Location" required :error="form.errors.to_location" />
          <FormSelect v-model="form.billing_client" label="Client" :options="clientOpts"
            placeholder="Select Client" required :error="form.errors.billing_client" />
          <FormSelect v-model="form.driver_id" label="Driver" :options="driverOpts"
            placeholder="Select Driver" required :error="form.errors.driver_id" />
          <FormSelect v-model="form.task_type" label="Task Type" :options="TASK_TYPE_OPTS" :searchable="false"
            required :error="form.errors.task_type" />
          <FormSelect v-model="form.status" label="Status" :options="STATUS_OPTS" :searchable="false"
            :error="form.errors.status" />
          <FormSelect v-model="form.takasi" label="Takasi" :options="TAKASI_OPTS" :searchable="false"
            :error="form.errors.takasi" />
        </div>
      </BaseCard>

      <div class="flex items-center justify-end gap-3">
        <BaseButton variant="light" type="button" @click="cancel" :disabled="form.processing">Cancel</BaseButton>
        <BaseButton variant="primary" type="submit" icon="ri-save-line" :loading="form.processing">Save</BaseButton>
      </div>
    </form>
  </div>
</template>
