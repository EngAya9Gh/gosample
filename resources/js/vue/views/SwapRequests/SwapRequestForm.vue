<script setup>
import { ref, computed } from 'vue';
import { useForm, Link } from '@inertiajs/vue3';
import Breadcrumb from '../../components/Breadcrumb.vue';
import FormInput from '../../components/FormInput.vue';
import FormSelect from '../../components/FormSelect.vue';
import BaseButton from '../../components/BaseButton.vue';

const props = defineProps({
  swaprequest: { type: Object, default: () => ({}) },
  drivers: { type: Object, default: () => ({}) },
  tasks: { type: Object, default: () => ({}) }
});

const isEdit = computed(() => !!props.swaprequest.id);

// Convert Laravel's pluck object into arrays for FormSelect
const driverOptions = computed(() => {
  return Object.entries(props.drivers)
    .filter(([value, label]) => value !== '')
    .map(([value, label]) => ({ value: Number(value), label }));
});

const taskOptions = computed(() => {
  return Object.entries(props.tasks)
    .filter(([value, label]) => value !== '')
    .map(([value, label]) => ({ value: Number(value), label: `#${label}` }));
});

const statusOptions = [
  { value: 'new', label: 'New' },
  { value: 'accepted', label: 'Accepted' },
  { value: 'rejected', label: 'Rejected' }
];

const form = useForm({
  driver_a: props.swaprequest.driver_a || '',
  driver_id: props.swaprequest.driver_id || '',
  task_id: isEdit.value ? props.swaprequest.task_id || '' : [],
  status: props.swaprequest.status || 'new',
});

const submit = () => {
  if (isEdit.value) {
    form.put(`/admin/swaprequests/${props.swaprequest.id}`);
  } else {
    form.post('/admin/swaprequests');
  }
};
</script>

<template>
  <div class="space-y-6">
    <!-- Header -->
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
      <div>
        <h1 class="text-2xl font-bold text-slate-900 dark:text-white tracking-tight">
          {{ isEdit ? 'Edit Swap Request' : 'Create Swap Request' }}
        </h1>
        <Breadcrumb 
          class="mt-1" 
          :items="[
            { label: 'Admin' }, 
            { label: 'Swap Requests', href: '/admin/swaprequests' }, 
            { label: isEdit ? 'Edit' : 'Create' }
          ]" 
        />
      </div>
      <div class="flex items-center gap-3">
        <Link 
          href="/admin/swaprequests"
          class="inline-flex items-center gap-2 px-4 py-2.5 bg-white dark:bg-slate-800 border border-slate-200 dark:border-white/10 hover:bg-slate-50 dark:hover:bg-slate-700 text-slate-700 dark:text-slate-200 text-sm font-semibold rounded-xl transition-all active:scale-95 shadow-sm"
        >
          <i class="ri-arrow-left-line text-lg"></i>
          Back to List
        </Link>
      </div>
    </div>

    <!-- Main Card -->
    <div class="bg-surface dark:bg-surface-dark-card rounded-2xl shadow-card border border-slate-200/60 dark:border-white/5 overflow-hidden">
      <div class="p-6 border-b border-slate-100 dark:border-white/5 bg-slate-50/50 dark:bg-black/20">
        <h3 class="text-base font-semibold text-slate-800 dark:text-slate-100 flex items-center gap-2">
          <div class="w-8 h-8 rounded-lg bg-primary-500/10 text-primary-600 dark:text-primary-400 flex items-center justify-center">
            <i class="ri-exchange-box-line text-lg"></i>
          </div>
          Swap Request Details
        </h3>
        <p class="text-sm text-slate-500 mt-1 ms-10">Fill in the details below to {{ isEdit ? 'update' : 'submit' }} a swap request.</p>
      </div>
      
      <form @submit.prevent="submit" class="p-6">
        <!-- Error Alerts -->
        <div v-if="Object.keys(form.errors).length > 0" class="mb-6 p-4 rounded-xl bg-danger/10 border border-danger/20 flex gap-3 items-start">
          <i class="ri-error-warning-fill text-danger text-xl"></i>
          <div>
            <h4 class="text-sm font-bold text-danger">Please correct the following errors:</h4>
            <ul class="mt-1 text-sm text-danger/80 list-disc list-inside">
              <li v-for="(error, key) in form.errors" :key="key">{{ error }}</li>
            </ul>
          </div>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
          <FormSelect
            v-model="form.driver_a"
            label="From Driver (Driver A) *"
            :options="driverOptions"
            :error="form.errors.driver_a"
            placeholder="Select From Driver"
            required
            searchable
          />

          <FormSelect
            v-model="form.driver_id"
            label="To Driver (Driver B) *"
            :options="driverOptions"
            :error="form.errors.driver_id"
            placeholder="Select To Driver"
            required
            searchable
          />

          <FormSelect
            v-model="form.task_id"
            label="Task(s) *"
            :options="taskOptions"
            :error="form.errors.task_id"
            placeholder="Select Task"
            :multiple="!isEdit"
            required
            searchable
          />

          <FormSelect
            v-if="isEdit"
            v-model="form.status"
            label="Status *"
            :options="statusOptions"
            :error="form.errors.status"
            required
          />
        </div>

        <div class="mt-8 pt-6 border-t border-slate-100 dark:border-white/5 flex justify-end gap-3">
          <Link 
            href="/admin/swaprequests" 
            class="px-5 py-2.5 text-sm font-semibold text-slate-600 dark:text-slate-300 hover:bg-slate-100 dark:hover:bg-slate-800 rounded-xl transition-colors"
          >
            Cancel
          </Link>
          <BaseButton 
            type="submit" 
            :loading="form.processing" 
            variant="primary"
            class="min-w-[140px]"
          >
            {{ isEdit ? 'Update Request' : 'Create Request' }}
          </BaseButton>
        </div>
      </form>
    </div>
  </div>
</template>
