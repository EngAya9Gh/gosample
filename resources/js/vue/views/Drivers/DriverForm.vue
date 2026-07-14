<script setup>
import { computed } from 'vue';
import { useForm, router } from '@inertiajs/vue3';
import Breadcrumb from '../../components/Breadcrumb.vue';
import BaseButton from '../../components/BaseButton.vue';
import DriverFormFields from './DriverFormFields.vue';
import { useToast } from '../../composables/useToast';

const props = defineProps({
  driver: { type: Object, default: null },
  zones: { type: Array, default: () => [] },
  shiftTemplates: { type: Array, default: () => [] },
});

const isEdit = computed(() => !!props.driver);

const form = useForm({
  name: props.driver?.name || '',
  username: props.driver?.username || '',
  password: '',
  mobile: props.driver?.mobile || '',
  email: props.driver?.email || '',
  national_id: props.driver?.national_id || '',
  language: props.driver?.language || 'en',
  status: props.driver?.status !== undefined ? String(props.driver.status) : '1',
  zone_id: props.driver?.zone_id || '',
  employment_type: props.driver?.employment_type || 'full_time',
  shift_count: props.driver?.shift_count || '1',
  total_working_hours: props.driver?.total_working_hours || 8,
  
  working_hours_start: props.driver?.working_hours_start ? props.driver.working_hours_start.slice(0, 5) : '',
  working_hours_end: props.driver?.working_hours_end ? props.driver.working_hours_end.slice(0, 5) : '',
  
  second_shift_working_hours_start: props.driver?.second_shift_working_hours_start ? props.driver.second_shift_working_hours_start.slice(0, 5) : '',
  second_shift_working_hours_end: props.driver?.second_shift_working_hours_end ? props.driver.second_shift_working_hours_end.slice(0, 5) : '',
  
  third_shift_working_hours_start: props.driver?.third_shift_working_hours_start ? props.driver.third_shift_working_hours_start.slice(0, 5) : '',
  third_shift_working_hours_end: props.driver?.third_shift_working_hours_end ? props.driver.third_shift_working_hours_end.slice(0, 5) : '',
});

const { push } = useToast();

function submit() {
  if (isEdit.value) {
    form.put(`/admin/drivers/${props.driver.id}`, {
      onSuccess: () => push({ type: 'success', title: 'Updated', message: 'Driver updated successfully.' }),
    });
  } else {
    form.post(`/admin/drivers`, {
      onSuccess: () => push({ type: 'success', title: 'Created', message: 'Driver created successfully.' }),
    });
  }
}
</script>

<template>
  <div class="max-w-5xl mx-auto pb-12">
    <Breadcrumb :title="isEdit ? 'Edit Driver' : 'Add Driver'" :trail="[{ label: 'Drivers', route: '/admin/drivers' }, { label: isEdit ? 'Edit' : 'Add' }]" />

    <form @submit.prevent="submit" class="space-y-6">
      <DriverFormFields :form="form" :zones="zones" :shift-templates="shiftTemplates" :is-edit="isEdit" />

      <!-- Actions -->
      <div class="flex items-center justify-end gap-3 pt-4">
        <BaseButton variant="light" type="button" @click="() => router.visit('/admin/drivers')">Cancel</BaseButton>
        <BaseButton variant="primary" type="submit" :loading="form.processing" icon="ri-save-line">
          {{ isEdit ? 'Save Changes' : 'Create Driver' }}
        </BaseButton>
      </div>
    </form>
  </div>
</template>
