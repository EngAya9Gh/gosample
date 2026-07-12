<script setup>
import { ref, computed } from 'vue';
import { useForm, router } from '@inertiajs/vue3';
import { usePermissions } from '../../composables/usePermissions';
import { useToast } from '../../composables/useToast';

import Breadcrumb from '../../components/Breadcrumb.vue';
import DataTable from '../../components/DataTable.vue';
import BaseButton from '../../components/BaseButton.vue';
import BaseModal from '../../components/BaseModal.vue';
import FormInput from '../../components/FormInput.vue';

const props = defineProps({
  barcodes: Array,
});

const { can } = usePermissions();
const { push } = useToast();

const columns = [
  { key: 'id', label: 'ID', width: '80px' },
  { key: 'type', label: 'Type' },
  { key: 'last_number', label: 'Last Number' },
];

/* --- Create / Edit Modal --- */
const showModal = ref(false);
const isEditing = ref(false);
const form = useForm({
  id: null,
  type: '',
  last_number: 0,
});

function openCreate() {
  isEditing.value = false;
  form.reset();
  form.clearErrors();
  showModal.value = true;
}

function openEdit(row) {
  isEditing.value = true;
  form.id = row.id;
  form.type = row.type;
  form.last_number = row.last_number;
  form.clearErrors();
  showModal.value = true;
}

function submitForm() {
  if (isEditing.value) {
    form.put(`/app/admin/barcodes/${form.id}`, {
      onSuccess: () => {
        showModal.value = false;
        push({ type: 'success', title: 'Success', message: 'Barcode sequence updated successfully' });
      },
    });
  } else {
    form.post('/app/admin/barcodes', {
      onSuccess: () => {
        showModal.value = false;
        push({ type: 'success', title: 'Success', message: 'Barcode sequence created successfully' });
      },
    });
  }
}

/* --- Delete --- */
function deleteBarcode(row) {
  if (confirm(`Are you sure you want to delete the barcode sequence for "${row.type}"?`)) {
    router.delete(`/app/admin/barcodes/${row.id}`, {
      onSuccess: () => {
        push({ type: 'success', title: 'Deleted', message: 'Barcode sequence deleted successfully' });
      }
    });
  }
}

function deleteMultiple(ids) {
  if (confirm(`Are you sure you want to delete ${ids.length} item(s)?`)) {
    router.delete('/app/admin/barcodes/destroy', {
      data: { ids },
      onSuccess: () => {
        push({ type: 'success', title: 'Deleted', message: 'Barcode sequences deleted successfully' });
      }
    });
  }
}
</script>

<template>
  <div>
    <Breadcrumb title="Barcodes List" :trail="[{ label: 'Barcodes' }]">
      <template #actions>
        <BaseButton v-if="can('barcode_create')" variant="primary" icon="ri-add-line" @click="openCreate">Add Barcode Type</BaseButton>
      </template>
    </Breadcrumb>

    <DataTable
      title="Barcodes"
      :columns="columns"
      :rows="barcodes || []"
      row-key="id"
      :server-side="false"
      :searchable="true"
      @delete-selected="deleteMultiple"
    >

      <template #cell-type="{ value }">
        <span class="font-medium text-ink dark:text-slate-200 capitalize">{{ value }}</span>
      </template>
      
      <template #cell-last_number="{ value }">
        <span class="font-bold text-primary">{{ value }}</span>
      </template>

      <template #row-actions="{ row }">
        <div class="inline-flex items-center gap-1">
          <button v-if="can('barcode_edit')" @click="openEdit(row)" class="grid place-items-center w-8 h-8 rounded-lg text-primary-600 hover:bg-primary-50 dark:hover:bg-primary-500/10 transition" title="Edit">
            <i class="ri-pencil-line"></i>
          </button>
          <button v-if="can('barcode_delete')" @click="deleteBarcode(row)" class="grid place-items-center w-8 h-8 rounded-lg text-danger hover:bg-danger/10 transition" title="Delete">
            <i class="ri-delete-bin-line"></i>
          </button>
        </div>
      </template>
    </DataTable>

    <!-- Create/Edit Modal -->
    <BaseModal v-model="showModal" :title="isEditing ? 'Edit Barcode Sequence' : 'Add Barcode Sequence'" icon="ri-barcode-box-line" size="md">
      <form @submit.prevent="submitForm" class="space-y-4">
        
        <FormInput
          v-model="form.type"
          label="Type"
          placeholder="e.g. bag, sample"
          :error="form.errors.type"
          required
        />

        <FormInput
          v-model="form.last_number"
          type="number"
          label="Last Number"
          placeholder="Starting point or current sequence"
          :error="form.errors.last_number"
          required
        />

      </form>
      <template #footer>
        <div class="flex items-center gap-2">
          <BaseButton variant="light" @click="showModal = false">Cancel</BaseButton>
          <BaseButton @click="submitForm" :loading="form.processing">Save</BaseButton>
        </div>
      </template>
    </BaseModal>
  </div>
</template>
