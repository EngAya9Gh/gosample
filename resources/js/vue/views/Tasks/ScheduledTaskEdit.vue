<script setup>
import { computed } from 'vue';
import { useForm, router } from '@inertiajs/vue3';
import Breadcrumb from '../../components/Breadcrumb.vue';
import BaseCard from '../../components/BaseCard.vue';
import BaseButton from '../../components/BaseButton.vue';
import FormSelect from '../../components/FormSelect.vue';
import FormDate from '../../components/FormDate.vue';
import FormInput from '../../components/FormInput.vue';
import { useToast } from '../../composables/useToast';

const { push } = useToast();

const props = defineProps({ 
  task: { type: Object, required: true },
  options: { type: Object, default: () => ({}) } 
});

const driverOpts   = props.options?.drivers   || [];
const clientOpts   = props.options?.clients   || [];
const locationOpts = props.options?.locations || [];
const statusOpts   = props.options?.statuses  || [{ value: 'enabled', label: 'enabled' }, { value: 'disabled', label: 'disabled' }];
const taskTypeOpts = props.options?.taskTypes || [{ value: 'SAMPLE', label: 'SAMPLE' }, { value: 'BOX', label: 'BOX' }];

const form = useForm({
  name: props.task.name || '',
  status: props.task.status || '',
  start_date: props.task.start_date || '',
  end_date: props.task.end_date || '',
  task_type: props.task.task_type || '',
  driver_id: props.task.driver_id || '',
  client_id: props.task.client_id || '',
  from_location_id: props.task.from_location_id || '',
  to_location_id: props.task.to_location_id || '',
  update_related: false,
});

function submit() {
  form.put(`/admin/scheduled-tasks/${props.task.id}`, { 
    preserveScroll: true,
    onSuccess: () => {
      push({ type: 'success', title: 'Saved', message: 'Scheduled Task updated successfully.' });
      router.visit('/admin/scheduled-tasks');
    },
    onError: () => {
      push({ type: 'error', title: 'Error', message: 'Failed to update.' });
    }
  });
}
function cancel() { router.visit('/admin/scheduled-tasks'); }
</script>

<template>
  <div class="max-w-4xl mx-auto pb-12">
    <Breadcrumb title="Edit Scheduled Task" :trail="[{ label: 'Tasks' }, { label: 'Scheduled Tasks', route: '/admin/scheduled-tasks' }, { label: `Edit #${task.id}` }]" />

    <form @submit.prevent="submit" class="space-y-6">
      <BaseCard>
        <div class="p-5 border-b border-slate-100 dark:border-white/5">
          <h2 class="text-base font-bold text-ink dark:text-white flex items-center gap-2">
            <i class="ri-calendar-schedule-line text-primary-500"></i> Edit Schedule Details
          </h2>
        </div>
        <div class="p-5 grid grid-cols-1 md:grid-cols-2 gap-5">
          <div class="md:col-span-2">
            <FormInput v-model="form.name" label="Name" required :error="form.errors.name" />
          </div>

          <FormSelect v-model="form.status" label="Status" :options="statusOpts" :searchable="false" required :error="form.errors.status" />
          <FormSelect v-model="form.task_type" label="Task Type" :options="taskTypeOpts" :searchable="false" required :error="form.errors.task_type" />

          <FormDate v-model="form.start_date" label="Start Date" mode="date" required :error="form.errors.start_date" />
          <FormDate v-model="form.end_date" label="End Date" mode="date" required :error="form.errors.end_date" />

          <FormSelect v-model="form.driver_id" label="Driver" :options="driverOpts" placeholder="Select Driver" required :error="form.errors.driver_id" />
          <FormSelect v-model="form.client_id" label="Client" :options="clientOpts" placeholder="Select Client" required :error="form.errors.client_id" />

          <FormSelect v-model="form.from_location_id" label="From Location" :options="locationOpts" placeholder="Select Location" required :error="form.errors.from_location_id" />
          <FormSelect v-model="form.to_location_id" label="To Location" :options="locationOpts" placeholder="Select Location" required :error="form.errors.to_location_id" />
        </div>
        
        <div class="p-5 mt-2 border-t border-slate-100 dark:border-white/5 bg-slate-50 dark:bg-black/10 rounded-b-xl">
          <label class="flex items-center gap-2 cursor-pointer">
            <input type="checkbox" v-model="form.update_related" class="rounded border-slate-300 text-primary-600 focus:ring-primary-500 w-5 h-5" />
            <span class="text-sm text-slate-700 dark:text-slate-300 font-medium">Update all related occurrences</span>
          </label>
        </div>
      </BaseCard>

      <div class="flex items-center justify-end gap-3">
        <BaseButton variant="light" type="button" @click="cancel" :disabled="form.processing">Cancel</BaseButton>
        <BaseButton variant="brand" type="submit" icon="ri-save-line" :loading="form.processing">Save Changes</BaseButton>
      </div>
    </form>
  </div>
</template>
