<script setup>
import { computed } from 'vue';
import { useForm, router } from '@inertiajs/vue3';
import Breadcrumb from '../../components/Breadcrumb.vue';
import FormSelect from '../../components/FormSelect.vue';

const props = defineProps({
  car: {
    type: Object,
    default: () => null,
  },
  drivers: {
    type: Array,
    default: () => [],
  },
});

const isEditing = computed(() => !!props.car);

const form = useForm({
  driver_id: props.car?.driver_id || '',
  imei: props.car?.imei || '',
  plate_number: props.car?.plate_number || '',
  model: props.car?.model || '',
  color: props.car?.color || '',
  contact_person: props.car?.contact_person || '',
  status: props.car?.status || '1',
  afaqi: props.car?.afaqi !== undefined ? props.car?.afaqi : '0',
  description: props.car?.description || '',
});

const submit = () => {
  if (isEditing.value) {
    form.put(`/admin/cars/${props.car.id}`);
  } else {
    form.post('/admin/cars');
  }
};

const cancel = () => {
  router.visit('/admin/cars');
};

const statusOpts = [
  { value: '1', label: 'Enable' },
  { value: '2', label: 'Disable' },
];

const afaqiOpts = [
  { value: '0', label: 'No' },
  { value: '1', label: 'Yes' },
];
</script>

<template>
  <div class="space-y-6 max-w-4xl mx-auto pb-12">
    <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4">
      <Breadcrumb :title="isEditing ? 'Edit Car' : 'Add Car'" parent="Cars" />
    </div>

    <div class="bg-surface dark:bg-surface-dark border dark:border-white/5 rounded-xl shadow-sm overflow-hidden">
      <div class="px-6 py-5 border-b border-slate-100 dark:border-white/5 bg-slate-50/50 dark:bg-black/20">
        <h3 class="text-base font-semibold text-slate-800 dark:text-slate-100 flex items-center gap-2">
          <i :class="isEditing ? 'ri-pencil-line text-primary-500' : 'ri-car-line text-primary-500'"></i>
          {{ isEditing ? 'Edit Car Details' : 'Create New Car' }}
        </h3>
      </div>

      <form @submit.prevent="submit" class="p-6">
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
          
          <!-- Driver -->
          <div class="space-y-1.5">
            <label class="text-sm font-semibold text-slate-700 dark:text-slate-300">Driver</label>
            <FormSelect v-model="form.driver_id" :options="[{value: '', label: 'Select Driver'}, ...drivers]" class="w-full" />
            <div v-if="form.errors.driver_id" class="text-xs text-danger mt-1">{{ form.errors.driver_id }}</div>
          </div>

          <!-- Status -->
          <div class="space-y-1.5">
            <label class="text-sm font-semibold text-slate-700 dark:text-slate-300">Status</label>
            <FormSelect v-model="form.status" :options="statusOpts" class="w-full" />
            <div v-if="form.errors.status" class="text-xs text-danger mt-1">{{ form.errors.status }}</div>
          </div>

          <!-- IMEI -->
          <div class="space-y-1.5">
            <label class="text-sm font-semibold text-slate-700 dark:text-slate-300">IMEI <span class="text-danger">*</span></label>
            <input type="text" v-model="form.imei" class="block w-full h-10 rounded-md bg-surface dark:bg-white/5 text-ink dark:text-slate-100 border-slate-300 dark:border-white/10 shadow-sm focus:border-primary-500 focus:ring-primary-500 sm:text-sm" placeholder="GPS device IMEI" required>
            <div v-if="form.errors.imei" class="text-xs text-danger mt-1">{{ form.errors.imei }}</div>
          </div>

          <!-- Plate Number -->
          <div class="space-y-1.5">
            <label class="text-sm font-semibold text-slate-700 dark:text-slate-300">Plate Number <span class="text-danger">*</span></label>
            <input type="text" v-model="form.plate_number" class="block w-full h-10 rounded-md bg-surface dark:bg-white/5 text-ink dark:text-slate-100 border-slate-300 dark:border-white/10 shadow-sm focus:border-primary-500 focus:ring-primary-500 sm:text-sm" placeholder="Plate number" required>
            <div v-if="form.errors.plate_number" class="text-xs text-danger mt-1">{{ form.errors.plate_number }}</div>
          </div>

          <!-- Model -->
          <div class="space-y-1.5">
            <label class="text-sm font-semibold text-slate-700 dark:text-slate-300">Model</label>
            <input type="text" v-model="form.model" class="block w-full h-10 rounded-md bg-surface dark:bg-white/5 text-ink dark:text-slate-100 border-slate-300 dark:border-white/10 shadow-sm focus:border-primary-500 focus:ring-primary-500 sm:text-sm" placeholder="Car model">
            <div v-if="form.errors.model" class="text-xs text-danger mt-1">{{ form.errors.model }}</div>
          </div>

          <!-- Color -->
          <div class="space-y-1.5">
            <label class="text-sm font-semibold text-slate-700 dark:text-slate-300">Color</label>
            <input type="text" v-model="form.color" class="block w-full h-10 rounded-md bg-surface dark:bg-white/5 text-ink dark:text-slate-100 border-slate-300 dark:border-white/10 shadow-sm focus:border-primary-500 focus:ring-primary-500 sm:text-sm" placeholder="Car color">
            <div v-if="form.errors.color" class="text-xs text-danger mt-1">{{ form.errors.color }}</div>
          </div>

          <!-- Contact Person -->
          <div class="space-y-1.5">
            <label class="text-sm font-semibold text-slate-700 dark:text-slate-300">Contact Person <span class="text-danger">*</span></label>
            <input type="text" v-model="form.contact_person" class="block w-full h-10 rounded-md bg-surface dark:bg-white/5 text-ink dark:text-slate-100 border-slate-300 dark:border-white/10 shadow-sm focus:border-primary-500 focus:ring-primary-500 sm:text-sm" placeholder="Contact person name" required>
            <div v-if="form.errors.contact_person" class="text-xs text-danger mt-1">{{ form.errors.contact_person }}</div>
          </div>

          <!-- Afaqi -->
          <div class="space-y-1.5">
            <label class="text-sm font-semibold text-slate-700 dark:text-slate-300">Afaqi Integration <span class="text-danger">*</span></label>
            <FormSelect v-model="form.afaqi" :options="afaqiOpts" class="w-full" />
            <div v-if="form.errors.afaqi" class="text-xs text-danger mt-1">{{ form.errors.afaqi }}</div>
          </div>

          <!-- Description -->
          <div class="md:col-span-2 space-y-1.5">
            <label class="text-sm font-semibold text-slate-700 dark:text-slate-300">Description</label>
            <textarea v-model="form.description" rows="3" class="block w-full rounded-md bg-surface dark:bg-white/5 text-ink dark:text-slate-100 border-slate-300 dark:border-white/10 shadow-sm focus:border-primary-500 focus:ring-primary-500 sm:text-sm" placeholder="Optional notes about this car..."></textarea>
            <div v-if="form.errors.description" class="text-xs text-danger mt-1">{{ form.errors.description }}</div>
          </div>

        </div>

        <div class="mt-8 pt-6 border-t border-slate-100 dark:border-white/10 flex justify-end gap-3">
          <button type="button" @click="cancel" class="px-5 py-2.5 text-sm font-medium text-slate-600 bg-white border border-slate-300 rounded-lg shadow-sm hover:bg-slate-50 focus:outline-none focus:ring-2 focus:ring-primary-500/50 dark:bg-transparent dark:text-slate-300 dark:border-slate-600 dark:hover:bg-slate-800 transition-colors">
            Cancel
          </button>
          <button type="submit" :disabled="form.processing" class="inline-flex items-center gap-2 px-5 py-2.5 text-sm font-medium text-white bg-primary-600 rounded-lg shadow-sm hover:bg-primary-700 focus:outline-none focus:ring-2 focus:ring-primary-500/50 disabled:opacity-75 transition-colors">
            <i class="ri-save-3-line"></i>
            {{ isEditing ? 'Save Changes' : 'Create Car' }}
            <svg v-if="form.processing" class="animate-spin -mr-1 ml-2 h-4 w-4 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg>
          </button>
        </div>
      </form>
    </div>
  </div>
</template>
