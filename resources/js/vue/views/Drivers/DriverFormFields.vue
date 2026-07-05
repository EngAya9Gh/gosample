<script setup>
/**
 * DriverFormFields — the shared field body for creating/editing a driver.
 * Rendered both by the full-page Drivers/DriverForm.vue and by the Add/Edit
 * modals on Drivers/DriversList.vue, so the fields + shift logic stay in one
 * place. The parent owns the useForm instance and passes it in as `form`.
 */
import BaseCard from '../../components/BaseCard.vue';
import FormInput from '../../components/FormInput.vue';
import FormSelect from '../../components/FormSelect.vue';
import FormDate from '../../components/FormDate.vue';

const props = defineProps({
  form:           { type: Object, required: true },  // useForm instance from the parent
  zones:          { type: Array,  default: () => [] },
  shiftTemplates: { type: Array,  default: () => [] },
  isEdit:         { type: Boolean, default: false },
  // true when rendered inside a modal → time pickers float (not clipped by overflow).
  floating:       { type: Boolean, default: false },
});

const langOptions   = [{ value: 'en', label: 'English' }, { value: 'ar', label: 'Arabic' }];
const statusOptions = [{ value: '1', label: 'Enabled' }, { value: '2', label: 'Disabled' }];
const empTypes      = [{ value: 'full_time', label: 'Full Time' }, { value: 'part_time', label: 'Part Time' }];
const shiftCounts   = [{ value: '1', label: '1 Shift' }, { value: '2', label: '2 Shifts' }, { value: '3', label: '3 Shifts' }];

function applyTemplate(e) {
  const tId = e.target.value;
  if (!tId) return;
  const tmpl = props.shiftTemplates.find((t) => String(t.id) === String(tId));
  if (tmpl) {
    props.form.working_hours_start = tmpl.start_time.slice(0, 5);
    props.form.working_hours_end = tmpl.end_time.slice(0, 5);
  }
}
</script>

<template>
  <div class="space-y-6">
    <!-- Section 1: Personal Info -->
    <BaseCard>
      <div class="p-5 border-b border-slate-100 dark:border-white/5">
        <h2 class="text-base font-bold text-ink dark:text-white flex items-center gap-2">
          <i class="ri-user-smile-line text-primary-500"></i>
          Personal Information
        </h2>
      </div>
      <div class="p-5 grid grid-cols-1 md:grid-cols-2 gap-5">
        <FormInput v-model="form.name" label="Driver Name" required :error="form.errors.name" />
        <FormInput v-model="form.username" label="Username" required :error="form.errors.username" />

        <FormInput v-model="form.mobile" label="Mobile" placeholder="05XXXXXXXX" required :error="form.errors.mobile" />
        <FormInput v-model="form.email" type="email" label="Email Address" :error="form.errors.email" />

        <FormInput v-model="form.national_id" label="National ID" :error="form.errors.national_id" />

        <FormInput v-model="form.password" type="password" :label="isEdit ? 'Password (leave blank to keep current)' : 'Password'" :required="!isEdit" :error="form.errors.password" />

        <FormSelect :floating="floating" v-model="form.language" label="Language" :options="langOptions" required :error="form.errors.language" />
        <FormSelect :floating="floating" v-model="form.status" label="Status" :options="statusOptions" required :error="form.errors.status" />
        <FormSelect :floating="floating" v-model="form.zone_id" label="Zone" :options="zones" required :error="form.errors.zone_id" />
      </div>
    </BaseCard>

    <!-- Section 2: Shift & Schedule -->
    <BaseCard>
      <div class="p-5 border-b border-slate-100 dark:border-white/5 flex flex-wrap items-center gap-4 justify-between">
        <h2 class="text-base font-bold text-ink dark:text-white flex items-center gap-2">
          <i class="ri-time-line text-teal-500"></i>
          Shift &amp; Schedule
        </h2>

        <!-- Template selector -->
        <div v-if="shiftTemplates.length" class="flex items-center gap-2">
          <span class="text-sm font-medium text-slate-500"><i class="ri-magic-line text-teal-500"></i> Template:</span>
          <select @change="applyTemplate" class="text-sm border-slate-200 dark:border-white/10 rounded-lg bg-surface dark:bg-surface-dark-solid focus:ring-primary-500 focus:border-primary-500 h-9 px-3">
            <option value="">-- Choose --</option>
            <option v-for="t in shiftTemplates" :key="t.id" :value="t.id">{{ t.name }}</option>
          </select>
        </div>
      </div>

      <div class="p-5">
        <div class="grid grid-cols-1 md:grid-cols-3 gap-5 mb-6 bg-slate-50/50 dark:bg-white/5 p-4 rounded-xl border border-slate-100 dark:border-white/5">
          <FormSelect :floating="floating" v-model="form.employment_type" label="Employment Type" :options="empTypes" required :error="form.errors.employment_type" />
          <FormInput v-model="form.total_working_hours" type="number" label="Required Hours/Day" required :error="form.errors.total_working_hours" />
          <FormSelect :floating="floating" v-model="form.shift_count" label="Number of Shifts" :options="shiftCounts" required :error="form.errors.shift_count" />
        </div>

        <div class="space-y-6">
          <!-- Shift 1 -->
          <div class="grid grid-cols-1 md:grid-cols-2 gap-5 border-l-2 border-teal-500 pl-4 py-1">
            <FormDate :floating="floating" mode="time" v-model="form.working_hours_start" label="Shift 1 Start Time" required :error="form.errors.working_hours_start" />
            <FormDate :floating="floating" mode="time" v-model="form.working_hours_end" label="Shift 1 End Time" required :error="form.errors.working_hours_end" />
          </div>

          <!-- Shift 2 -->
          <div v-if="Number(form.shift_count) >= 2" class="grid grid-cols-1 md:grid-cols-2 gap-5 border-l-2 border-primary-500 pl-4 py-1">
            <FormDate :floating="floating" mode="time" v-model="form.second_shift_working_hours_start" label="Shift 2 Start Time" :required="Number(form.shift_count) >= 2" :error="form.errors.second_shift_working_hours_start" />
            <FormDate :floating="floating" mode="time" v-model="form.second_shift_working_hours_end" label="Shift 2 End Time" :required="Number(form.shift_count) >= 2" :error="form.errors.second_shift_working_hours_end" />
          </div>

          <!-- Shift 3 -->
          <div v-if="Number(form.shift_count) >= 3" class="grid grid-cols-1 md:grid-cols-2 gap-5 border-l-2 border-indigo-500 pl-4 py-1">
            <FormDate :floating="floating" mode="time" v-model="form.third_shift_working_hours_start" label="Shift 3 Start Time" :required="Number(form.shift_count) >= 3" :error="form.errors.third_shift_working_hours_start" />
            <FormDate :floating="floating" mode="time" v-model="form.third_shift_working_hours_end" label="Shift 3 End Time" :required="Number(form.shift_count) >= 3" :error="form.errors.third_shift_working_hours_end" />
          </div>
        </div>
      </div>
    </BaseCard>
  </div>
</template>
