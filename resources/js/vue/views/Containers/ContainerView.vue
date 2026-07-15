<script setup>
/**
 * views/Containers/ContainerView.vue — SPA rebuild of /admin/containers/{id}.
 * Logic mirrors the classic show page 1:1: the details table (ID / Car /
 * Sensor / Type / Description / Status), the on-page barcode + Print Barcode
 * action, and the bags-in-container breakdown (view_bag_container_details
 * gate; first sample's type/temperature per bag). Design follows the Tasks
 * details page: gradient header + back button + action buttons, bento cards.
 */
import { ref } from 'vue';
import { router, useForm } from '@inertiajs/vue3';
import Breadcrumb from '../../components/Breadcrumb.vue';
import BaseCard from '../../components/BaseCard.vue';
import BaseButton from '../../components/BaseButton.vue';
import BaseModal from '../../components/BaseModal.vue';
import FormInput from '../../components/FormInput.vue';
import FormSelect from '../../components/FormSelect.vue';
import StatusBadge from '../../components/StatusBadge.vue';
import DataTable from '../../components/DataTable.vue';
import { useToast } from '../../composables/useToast';
import { usePermissions } from '../../composables/usePermissions';

const props = defineProps({
  container:   { type: Object, required: true },
  barcodeSvg:  { type: String, default: '' },
  bags:        { type: Array,  default: () => [] },
  canViewBags: { type: Boolean, default: false },
  cars:        { type: Array,  default: () => [] }, // enabled cars, for the edit popup
});

const { push } = useToast();
const { can } = usePermissions();

function goBack() { router.visit('/admin/containers'); }

// Same print flow as the list page: the barcode route prints itself.
function printBarcode() {
  window.open(`/admin/containers/${props.container.id}/barcode`, '_blank');
}

/* ---------- bags table (classic columns 1:1) ---------- */
const bagColumns = [
  { key: 'bag_code',         label: 'Bag Code' },
  { key: 'total',            label: 'Total Samples', align: 'center' },
  { key: 'sample_type',      label: 'Sample Type' },
  { key: 'temperature_type', label: 'Temperature Type' },
];

/* ---------- edit popup (same parity-checked fields as the classic edit form) ---------- */
const CONTAINER_TYPE_OPTS = ['ROOM', 'REFRIGERATE', 'FROZEN'].map((v) => ({ value: v, label: v }));
const CONTAINER_STATUS_OPTS = [
  { value: '1', label: 'enabled' },
  { value: '2', label: 'disabled' },
];

const showEdit = ref(false);
const form = useForm({ car_id: '', imei: '', type: '', model: '', status: '', description: '' });

function openEdit() {
  if (!can('container_edit')) return;
  form.clearErrors();
  form.car_id      = props.container.car_id ?? '';
  form.imei        = props.container.imei ?? '';
  form.type        = props.container.type ?? '';
  form.model       = props.container.model ?? '';
  form.status      = String(props.container.status ?? '');
  form.description = props.container.description ?? '';
  showEdit.value = true;
}
function submitEdit() {
  form.put(`/admin/containers/${props.container.id}/popup`, {
    preserveScroll: true,
    onSuccess: () => {
      showEdit.value = false;
      push({ type: 'success', title: 'Updated', message: `Container #${props.container.id} updated.` });
    },
  });
}
</script>

<template>
  <div class="space-y-5 max-w-6xl mx-auto pb-12">
    <Breadcrumb title="Container Details"
      :trail="[{ label: 'Containers', href: '/admin/containers' }, { label: `Container #${container.id}` }]" />

    <!-- Header (Tasks details page design) -->
    <div class="flex flex-col md:flex-row justify-between items-start md:items-center gap-4 bg-gradient-to-br from-[#005D69] to-[#0d9488] text-white p-5 rounded-2xl shadow-md">
      <div class="flex items-center gap-4">
        <button class="w-10 h-10 rounded-full border border-white/30 flex items-center justify-center text-white hover:bg-white/10 transition-colors" @click="goBack">
          <i class="ri-arrow-left-line text-xl rtl:-scale-x-100"></i>
        </button>
        <div>
          <div class="flex items-center gap-3">
            <h1 class="text-2xl font-bold tracking-tight">Container #{{ container.id }}</h1>
            <span class="bg-white font-bold text-xs px-3 py-1 rounded-full shadow-sm"
              :class="container.status == 1 ? 'text-emerald-600' : 'text-red-600'">
              {{ container.status == 1 ? 'Enabled' : 'Disabled' }}
            </span>
          </div>
          <p class="text-sm text-white/80 mt-1">
            <i class="ri-temp-cold-line me-1"></i>{{ container.type || '—' }}
            <span v-if="container.created_at" class="ms-3"><i class="ri-time-line me-1"></i>Created {{ container.created_at }}</span>
          </p>
        </div>
      </div>

      <div class="flex items-center gap-2 w-full md:w-auto">
        <button @click="printBarcode" class="flex-1 md:flex-none border border-white/30 text-white text-sm px-5 py-2.5 rounded-lg hover:bg-white/10 transition-colors flex items-center justify-center gap-2 font-medium">
          <i class="ri-printer-line text-lg"></i> Print Barcode
        </button>
        <button v-if="can('container_edit')" @click="openEdit" class="flex-1 md:flex-none bg-white text-primary-700 text-sm px-5 py-2.5 rounded-lg hover:bg-slate-50 transition-colors shadow-sm flex items-center justify-center gap-2 font-bold">
          <i class="ri-pencil-line text-lg"></i> Edit
        </button>
      </div>
    </div>

    <!-- Bento Grid -->
    <div class="grid grid-cols-12 gap-5">
      <!-- Overview (classic details table 1:1) -->
      <BaseCard class="col-span-12 lg:col-span-8 p-5">
        <div class="flex justify-between items-center border-b border-slate-100 dark:border-white/5 pb-4 mb-5">
          <h2 class="text-lg font-bold text-ink dark:text-white">Container Overview</h2>
          <StatusBadge v-if="container.status == 1" status="ENABLED" label="Enabled" />
          <StatusBadge v-else status="DISABLED" label="Disabled" />
        </div>

        <div class="grid grid-cols-2 md:grid-cols-3 gap-y-6 gap-x-4">
          <div>
            <p class="text-[11px] font-bold text-slate-500 uppercase tracking-wider">ID</p>
            <p class="text-[13px] font-black text-[#0ab39c] mt-1">#{{ container.id }}</p>
          </div>
          <div>
            <p class="text-[11px] font-bold text-slate-500 uppercase tracking-wider">Car</p>
            <p class="text-[13px] font-bold text-ink dark:text-slate-200 mt-1">{{ container.car_plate || '—' }}</p>
            <p v-if="container.car_imei" class="text-[11px] font-mono text-slate-400 mt-0.5" dir="ltr">{{ container.car_imei }}</p>
          </div>
          <div>
            <p class="text-[11px] font-bold text-slate-500 uppercase tracking-wider">Sensor</p>
            <p class="text-[13px] font-mono font-medium text-ink dark:text-slate-200 mt-1" dir="ltr">{{ container.imei || '—' }}</p>
          </div>
          <div>
            <p class="text-[11px] font-bold text-slate-500 uppercase tracking-wider">Type</p>
            <p class="mt-1">
              <span v-if="container.type" class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-md text-[11.5px] font-bold border"
                :class="container.type === 'FROZEN'
                  ? 'bg-sky-50 text-sky-700 border-sky-200 dark:bg-sky-500/10 dark:text-sky-400 dark:border-sky-500/20'
                  : container.type === 'REFRIGERATE'
                    ? 'bg-cyan-50 text-cyan-700 border-cyan-200 dark:bg-cyan-500/10 dark:text-cyan-400 dark:border-cyan-500/20'
                    : 'bg-amber-50 text-amber-700 border-amber-200 dark:bg-amber-500/10 dark:text-amber-400 dark:border-amber-500/20'">
                <i :class="container.type === 'ROOM' ? 'ri-home-4-line' : 'ri-temp-cold-line'"></i>{{ container.type }}
              </span>
              <span v-else class="text-slate-400">—</span>
            </p>
          </div>
          <div>
            <p class="text-[11px] font-bold text-slate-500 uppercase tracking-wider">Model</p>
            <p class="text-[13px] font-medium text-ink dark:text-slate-200 mt-1">{{ container.model || '—' }}</p>
          </div>
          <div class="col-span-2 md:col-span-1">
            <p class="text-[11px] font-bold text-slate-500 uppercase tracking-wider">Description</p>
            <p class="text-[13px] font-medium text-ink dark:text-slate-200 mt-1 whitespace-pre-line">{{ container.description || '—' }}</p>
          </div>
        </div>
      </BaseCard>

      <!-- Barcode card (on-page SVG like the classic show page) -->
      <BaseCard class="col-span-12 lg:col-span-4 p-5 flex flex-col">
        <div class="flex justify-between items-center border-b border-slate-100 dark:border-white/5 pb-4 mb-5">
          <h2 class="text-lg font-bold text-ink dark:text-white">Barcode</h2>
          <span class="text-[11px] font-mono text-slate-400">{{ container.id }}-container</span>
        </div>
        <div class="flex-1 grid place-items-center">
          <!-- white plate so the barcode stays scannable in dark mode -->
          <div class="bg-white rounded-xl border border-slate-200 p-4 max-w-full overflow-x-auto" v-html="barcodeSvg"></div>
        </div>
        <BaseButton variant="light" icon="ri-printer-line" class="mt-4 w-full justify-center" @click="printBarcode">
          Print Barcode
        </BaseButton>
      </BaseCard>

      <!-- Bags in this container (view_bag_container_details gate, like the classic page) -->
      <BaseCard v-if="canViewBags" class="col-span-12 p-5">
        <div class="flex justify-between items-center border-b border-slate-100 dark:border-white/5 pb-4 mb-4">
          <h2 class="text-lg font-bold text-ink dark:text-white">Bags in this Container</h2>
          <span class="bg-primary-50 dark:bg-primary-500/10 text-primary-600 dark:text-primary-400 px-3 py-1 rounded-full text-xs font-bold">
            {{ bags.length }} bag(s)
          </span>
        </div>

        <DataTable v-if="bags.length"
          :columns="bagColumns" :rows="bags" row-key="bag_code"
          :selectable="false" :searchable="false" :exportable="true"
        >
          <template #cell-bag_code="{ value }">
            <span class="font-mono font-bold text-ink dark:text-slate-100">{{ value || '—' }}</span>
          </template>
          <template #cell-total="{ value }">
            <span class="inline-flex items-center justify-center min-w-[28px] h-6 px-2 rounded-full bg-primary-50 text-primary-700 dark:bg-primary-500/10 dark:text-primary-300 text-[12px] font-black">{{ value }}</span>
          </template>
          <template #cell-sample_type="{ value }">
            <span class="font-medium text-slate-700 dark:text-slate-300">{{ value || '—' }}</span>
          </template>
          <template #cell-temperature_type="{ value }">
            <span v-if="value" class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-md text-[11.5px] font-bold border"
              :class="value === 'FROZEN'
                ? 'bg-sky-50 text-sky-700 border-sky-200 dark:bg-sky-500/10 dark:text-sky-400 dark:border-sky-500/20'
                : value === 'REFRIGERATE'
                  ? 'bg-cyan-50 text-cyan-700 border-cyan-200 dark:bg-cyan-500/10 dark:text-cyan-400 dark:border-cyan-500/20'
                  : 'bg-amber-50 text-amber-700 border-amber-200 dark:bg-amber-500/10 dark:text-amber-400 dark:border-amber-500/20'">
              <i :class="value === 'ROOM' ? 'ri-home-4-line' : 'ri-temp-cold-line'"></i>{{ value }}
            </span>
            <span v-else class="text-slate-400">—</span>
          </template>
        </DataTable>

        <div v-else class="py-10 flex flex-col items-center justify-center text-center">
          <div class="w-14 h-14 bg-slate-100 dark:bg-surface-dark-solid rounded-full flex items-center justify-center mb-3">
            <i class="ri-shopping-bag-3-line text-xl text-slate-400"></i>
          </div>
          <p class="text-sm text-slate-500">No bags are currently inside this container.</p>
        </div>
      </BaseCard>
    </div>

    <!-- edit container (same popup as the list page, parity-checked fields) -->
    <BaseModal v-model="showEdit" :title="`Edit Container #${container.id}`" icon="ri-pencil-line" size="lg">
      <form @submit.prevent="submitEdit" class="grid grid-cols-1 sm:grid-cols-2 gap-4">
        <FormSelect floating v-model="form.car_id" label="Car" :options="props.cars"
          placeholder="Select car" :error="form.errors.car_id" />
        <FormInput v-model="form.imei" label="Sensor" placeholder="Enter sensor IMEI" icon="ri-focus-3-line"
          required :error="form.errors.imei" />
        <FormInput v-model="form.model" label="Model" placeholder="Enter model"
          required :error="form.errors.model" />
        <FormSelect floating v-model="form.type" label="Type" :options="CONTAINER_TYPE_OPTS" :searchable="false"
          placeholder="Select type" required :error="form.errors.type" />
        <FormInput v-model="form.description" label="Description" type="textarea" :rows="3"
          placeholder="Optional notes" :error="form.errors.description" />
        <FormSelect floating v-model="form.status" label="Status" :options="CONTAINER_STATUS_OPTS" :searchable="false"
          placeholder="Select status" required :error="form.errors.status" />
      </form>
      <template #footer>
        <BaseButton variant="light" @click="showEdit = false" :disabled="form.processing">Cancel</BaseButton>
        <BaseButton variant="primary" icon="ri-save-line" :loading="form.processing" @click="submitEdit">Save Changes</BaseButton>
      </template>
    </BaseModal>
  </div>
</template>
