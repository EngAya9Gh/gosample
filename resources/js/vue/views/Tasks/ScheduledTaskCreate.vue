<script setup>
/**
 * Add Scheduled Task (SPA) — mirrors Admin\ScheduledTaskController@create/store.
 * Pick multiple From Locations (each gets its own visit hour) and multiple days;
 * the backend generates one task per (from_location × day), parent-child linked.
 */
import { computed, watch } from 'vue';
import { useForm, router } from '@inertiajs/vue3';
import Breadcrumb from '../../components/Breadcrumb.vue';
import BaseCard from '../../components/BaseCard.vue';
import BaseButton from '../../components/BaseButton.vue';
import FormSelect from '../../components/FormSelect.vue';
import FormDate from '../../components/FormDate.vue';

const props = defineProps({ options: { type: Object, default: () => ({}) } });

const driverOpts   = props.options?.drivers   || [];
const clientOpts   = props.options?.clients   || [];
const locationOpts = props.options?.locations || [];
const statusOpts   = props.options?.statuses  || [{ value: 'enabled', label: 'enabled' }, { value: 'disabled', label: 'disabled' }];
const taskTypeOpts = props.options?.taskTypes || [{ value: 'SAMPLE', label: 'SAMPLE' }, { value: 'BOX', label: 'BOX' }];
const dayOpts      = (props.options?.days || ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday', 'Sunday']).map((d) => ({ value: d, label: d }));

const form = useForm({
  driver_id: '',
  status: 'enabled',
  start_date: '',
  end_date: '',
  from_location_id: [],       // multi (required)
  to_location_id: '',         // required
  client_id: '',              // required
  task_type: 'SAMPLE',        // required
  days: [],                   // weekday names
  visit_hours: {},            // { [locationId]: 'HH:mm' }  (required, one per from-location)
});

// Keep visit_hours keyed to exactly the selected from-locations.
watch(() => form.from_location_id, (ids) => {
  const next = {};
  (ids || []).forEach((id) => { next[id] = form.visit_hours[id] || ''; });
  form.visit_hours = next;
}, { deep: true });

const selectedFromLocations = computed(() =>
  (form.from_location_id || []).map((id) => ({
    id,
    label: locationOpts.find((l) => l.value === id)?.label || `#${id}`,
  }))
);

function submit() {
  form.post('/app/admin/scheduled-tasks', { preserveScroll: true });
}
function cancel() { router.visit('/app/admin/scheduled-tasks'); }
</script>

<template>
  <div class="max-w-4xl mx-auto pb-12">
    <Breadcrumb title="Add Scheduled Task" :trail="[{ label: 'Tasks' }, { label: 'Scheduled Tasks', route: '/app/admin/scheduled-tasks' }, { label: 'Create' }]" />

    <form @submit.prevent="submit" class="space-y-6">
      <BaseCard>
        <div class="p-5 border-b border-slate-100 dark:border-white/5">
          <h2 class="text-base font-bold text-ink dark:text-white flex items-center gap-2">
            <i class="ri-calendar-schedule-line text-primary-500"></i> Schedule Details
          </h2>
        </div>
        <div class="p-5 grid grid-cols-1 sm:grid-cols-2 gap-5">
          <FormSelect v-model="form.driver_id" label="Driver" :options="driverOpts" placeholder="Select Driver" required :error="form.errors.driver_id" />
          <FormSelect v-model="form.status" label="Status" :options="statusOpts" :searchable="false" :error="form.errors.status" />

          <FormDate v-model="form.start_date" label="Start Date" mode="date" required :error="form.errors.start_date" />
          <FormDate v-model="form.end_date" label="End Date" mode="date" required :error="form.errors.end_date" />

          <FormSelect v-model="form.to_location_id" label="To Location" :options="locationOpts" placeholder="Select Location" required :error="form.errors.to_location_id" />
          <FormSelect v-model="form.client_id" label="Client" :options="clientOpts" placeholder="Select Client" required :error="form.errors.client_id" />

          <FormSelect v-model="form.task_type" label="Task Type" :options="taskTypeOpts" :searchable="false" required :error="form.errors.task_type" />
          <FormSelect v-model="form.days" label="Days" :options="dayOpts" multiple placeholder="Select weekdays" :error="form.errors.days" />

          <div class="sm:col-span-2">
            <FormSelect v-model="form.from_location_id" label="From Locations" :options="locationOpts" multiple
              placeholder="Select one or more locations" required :error="form.errors.from_location_id" />
          </div>
        </div>
      </BaseCard>

      <!-- Per-location visit hour -->
      <BaseCard v-if="selectedFromLocations.length">
        <div class="p-5 border-b border-slate-100 dark:border-white/5">
          <h2 class="text-base font-bold text-ink dark:text-white flex items-center gap-2">
            <i class="ri-time-line text-teal-500"></i> Visit Hour per From-Location
          </h2>
          <p v-if="form.errors.visit_hours" class="text-xs text-danger mt-1">{{ form.errors.visit_hours }}</p>
        </div>
        <div class="p-5 space-y-4">
          <div v-for="loc in selectedFromLocations" :key="loc.id" class="grid grid-cols-1 sm:grid-cols-[1fr_200px] gap-3 items-end">
            <div class="text-sm font-semibold text-ink dark:text-slate-200 pb-2.5 flex items-center gap-2">
              <i class="ri-map-pin-2-line text-primary-500"></i> {{ loc.label }}
            </div>
            <FormDate v-model="form.visit_hours[loc.id]" mode="time" label="Visit Hour" />
          </div>
        </div>
      </BaseCard>

      <div class="flex items-center justify-end gap-3">
        <BaseButton variant="light" type="button" @click="cancel" :disabled="form.processing">Cancel</BaseButton>
        <BaseButton variant="brand" type="submit" icon="ri-save-line" :loading="form.processing">Save Scheduled Task</BaseButton>
      </div>
    </form>
  </div>
</template>
