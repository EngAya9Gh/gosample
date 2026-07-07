<script setup>
/**
 * views/Tasks/TaskCreate.vue — SPA Create Task page (replaces the classic
 * /admin/tasks/create Blade page). Fields, defaults and validation mirror the
 * classic form 1:1 and post to the same endpoint used by the list's Add-Task
 * modal (App\TasksController@store → StoreTaskRequest).
 */
import { useForm, router } from '@inertiajs/vue3';
import Breadcrumb from '../../components/Breadcrumb.vue';
import BaseCard from '../../components/BaseCard.vue';
import BaseButton from '../../components/BaseButton.vue';
import FormInput from '../../components/FormInput.vue';
import FormSelect from '../../components/FormSelect.vue';
import FormDate from '../../components/FormDate.vue';

const props = defineProps({
  options: { type: Object, default: () => ({}) },
});

const driverOpts   = props.options?.drivers   || [];
const clientOpts   = props.options?.clients   || [];
const locationOpts = props.options?.locations || [];

// Enum options from App\Models\Task (TYPE_SELECT / TAKASI_SELECT / TASK_TYPE_SELECT).
const TYPE_OPTS      = [{ value: 'one_time', label: 'one_time' }, { value: 'scheduled', label: 'scheduled' }];
const TAKASI_OPTS    = [{ value: 'NO', label: 'NO' }, { value: 'YES', label: 'YES' }];
const TASK_TYPE_OPTS = [{ value: 'SAMPLE', label: 'SAMPLE' }, { value: 'BOX', label: 'BOX' }];

// Defaults match the classic create form exactly.
const form = useForm({
  from_location: [],        // multi-select (required)
  to_location: '',          // required, must differ from from_location
  billing_client: '',       // required
  driver_id: '',            // required
  type: 'one_time',         // default
  pickup_time: '',          // required (datetime)
  dropoff_time: '',         // required (datetime)
  takasi: 'NO',             // default
  task_type: 'SAMPLE',      // default
  time_of_visit: 1,         // default, required (>0, <50)
});

function submit() {
  // Same store endpoint the Add-Task modal uses; on success it redirects to the list.
  form.post('/app/admin/tasks/popup', { preserveScroll: true });
}
function cancel() {
  router.visit('/app/admin/tasks');
}
</script>

<template>
  <div class="max-w-4xl mx-auto pb-12">
    <Breadcrumb title="Create Task" :trail="[{ label: 'Tasks', route: '/app/admin/tasks' }, { label: 'Create' }]" />

    <form @submit.prevent="submit" class="space-y-6">
      <BaseCard>
        <div class="p-5 border-b border-slate-100 dark:border-white/5">
          <h2 class="text-base font-bold text-ink dark:text-white flex items-center gap-2">
            <i class="ri-add-circle-line text-primary-500"></i>
            Task Details
          </h2>
        </div>
        <div class="p-5 grid grid-cols-1 sm:grid-cols-2 gap-5">
          <div class="sm:col-span-2">
            <FormSelect v-model="form.from_location" label="From Location" :options="locationOpts" multiple
              placeholder="Select one or more locations" required :error="form.errors.from_location" />
          </div>
          <FormSelect v-model="form.to_location" label="To Location" :options="locationOpts"
            placeholder="Select Location" required :error="form.errors.to_location" />
          <FormSelect v-model="form.billing_client" label="Billing Client" :options="clientOpts"
            placeholder="Select Client" required :error="form.errors.billing_client" />
          <FormSelect v-model="form.driver_id" label="Driver" :options="driverOpts"
            placeholder="Select Driver" required :error="form.errors.driver_id" />
          <FormSelect v-model="form.type" label="Type" :options="TYPE_OPTS" :searchable="false"
            required :error="form.errors.type" />
          <FormDate v-model="form.pickup_time" label="Pickup Time" mode="datetime"
            required :error="form.errors.pickup_time" />
          <FormDate v-model="form.dropoff_time" label="Dropoff Time" mode="datetime"
            required :error="form.errors.dropoff_time" />
          <FormSelect v-model="form.takasi" label="Takasi" :options="TAKASI_OPTS" :searchable="false"
            :error="form.errors.takasi" />
          <FormSelect v-model="form.task_type" label="Task Type" :options="TASK_TYPE_OPTS" :searchable="false"
            required :error="form.errors.task_type" />
          <FormInput v-model="form.time_of_visit" label="Number of Visits" type="number"
            required :error="form.errors.time_of_visit" />
        </div>
      </BaseCard>

      <div class="flex items-center justify-end gap-3">
        <BaseButton variant="light" type="button" @click="cancel" :disabled="form.processing">Cancel</BaseButton>
        <BaseButton variant="primary" type="submit" icon="ri-save-line" :loading="form.processing">Save Task</BaseButton>
      </div>
    </form>
  </div>
</template>
