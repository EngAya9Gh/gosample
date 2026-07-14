<script setup>
/**
 * Add Quick Schedule Task (SPA) — mirrors Admin\ScheduledTaskController@quick/quickAction.
 * Single From Location, multiple days, and a list of visit hours; the backend
 * generates one task per (day × hour), parent-child linked.
 */
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
  from_location_id: '',       // single (required)
  to_location_id: '',         // required
  client_id: '',              // required
  task_type: 'SAMPLE',        // required
  days: [],                   // weekday names
  visit_hours: [''],          // list of 'HH:mm' (required)
});

function addHour() { form.visit_hours.push(''); }
function removeHour(i) { form.visit_hours.splice(i, 1); if (!form.visit_hours.length) form.visit_hours.push(''); }

function submit() {
  form.transform((data) => ({
    ...data,
    visit_hours: (data.visit_hours || []).filter(Boolean), // drop empty hour rows
  })).post('/admin/scheduled-tasks/quick', { preserveScroll: true });
}
function cancel() { router.visit('/admin/scheduled-tasks'); }
</script>

<template>
  <div class="max-w-4xl mx-auto pb-12">
    <Breadcrumb title="Add Quick Schedule Task" :trail="[{ label: 'Tasks' }, { label: 'Scheduled Tasks', route: '/admin/scheduled-tasks' }, { label: 'Quick' }]" />

    <form @submit.prevent="submit" class="space-y-6">
      <BaseCard>
        <div class="p-5 border-b border-slate-100 dark:border-white/5">
          <h2 class="text-base font-bold text-ink dark:text-white flex items-center gap-2">
            <i class="ri-flashlight-line text-info"></i> Quick Schedule Details
          </h2>
        </div>
        <div class="p-5 grid grid-cols-1 sm:grid-cols-2 gap-5">
          <FormSelect v-model="form.driver_id" label="Driver" :options="driverOpts" placeholder="Select Driver" required :error="form.errors.driver_id" />
          <FormSelect v-model="form.status" label="Status" :options="statusOpts" :searchable="false" :error="form.errors.status" />

          <FormDate v-model="form.start_date" label="Start Date" mode="date" required :error="form.errors.start_date" />
          <FormDate v-model="form.end_date" label="End Date" mode="date" required :error="form.errors.end_date" />

          <FormSelect v-model="form.from_location_id" label="From Location" :options="locationOpts" placeholder="Select Location" required :error="form.errors.from_location_id" />
          <FormSelect v-model="form.to_location_id" label="To Location" :options="locationOpts" placeholder="Select Location" required :error="form.errors.to_location_id" />

          <FormSelect v-model="form.client_id" label="Client" :options="clientOpts" placeholder="Select Client" required :error="form.errors.client_id" />
          <FormSelect v-model="form.task_type" label="Task Type" :options="taskTypeOpts" :searchable="false" required :error="form.errors.task_type" />

          <div class="sm:col-span-2">
            <FormSelect v-model="form.days" label="Days" :options="dayOpts" multiple placeholder="Select weekdays" :error="form.errors.days" />
          </div>
        </div>
      </BaseCard>

      <!-- Visit hours list -->
      <BaseCard>
        <div class="p-5 border-b border-slate-100 dark:border-white/5 flex items-center justify-between">
          <h2 class="text-base font-bold text-ink dark:text-white flex items-center gap-2">
            <i class="ri-time-line text-teal-500"></i> Visit Hours
          </h2>
          <BaseButton variant="light" size="sm" type="button" icon="ri-add-line" @click="addHour">Add hour</BaseButton>
        </div>
        <div class="p-5 space-y-3">
          <p v-if="form.errors.general || form.errors.visit_hours" class="text-xs text-danger">{{ form.errors.general || form.errors.visit_hours }}</p>
          <div v-for="(h, i) in form.visit_hours" :key="i" class="grid grid-cols-[1fr_auto] gap-3 items-end">
            <FormDate v-model="form.visit_hours[i]" mode="time" :label="`Hour ${i + 1}`" />
            <button type="button" @click="removeHour(i)" class="h-11 w-11 grid place-items-center rounded-xl bg-danger/10 text-danger hover:bg-danger/20 transition"><i class="ri-delete-bin-line"></i></button>
          </div>
          <p class="text-xs text-slate-400">One task is created for every day × hour combination.</p>
        </div>
      </BaseCard>

      <div class="flex items-center justify-end gap-3">
        <BaseButton variant="light" type="button" @click="cancel" :disabled="form.processing">Cancel</BaseButton>
        <BaseButton variant="brand" type="submit" icon="ri-save-line" :loading="form.processing">Save Quick Schedule</BaseButton>
      </div>
    </form>
  </div>
</template>
