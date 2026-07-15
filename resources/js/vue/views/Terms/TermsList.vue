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
  terms: Array,
});

const { can } = usePermissions();
const { push } = useToast();

const columns = [
  { key: 'id', label: 'ID', width: '80px' },
  { key: 'english_text', label: 'English Term' },
  { key: 'arabic_text', label: 'Arabic Term' },
];

/* --- Create / Edit Modal --- */
const showModal = ref(false);
const isEditing = ref(false);
const form = useForm({
  id: null,
  english_text: '',
  arabic_text: '',
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
  form.english_text = row.english_text;
  form.arabic_text = row.arabic_text;
  form.clearErrors();
  showModal.value = true;
}

function submitForm() {
  if (isEditing.value) {
    form.put(`/admin/terms/${form.id}`, {
      onSuccess: () => {
        showModal.value = false;
        push({ type: 'success', title: 'Success', message: 'Term updated successfully' });
      },
    });
  } else {
    form.post('/admin/terms', {
      onSuccess: () => {
        showModal.value = false;
        push({ type: 'success', title: 'Success', message: 'Term created successfully' });
      },
    });
  }
}

/* --- Delete --- */
function deleteTerm(row) {
  if (confirm(`Are you sure you want to delete this term?`)) {
    router.delete(`/admin/terms/${row.id}`, {
      onSuccess: () => {
        push({ type: 'success', title: 'Deleted', message: 'Term deleted successfully' });
      }
    });
  }
}

function deleteMultiple(ids) {
  if (confirm(`Are you sure you want to delete ${ids.length} term(s)?`)) {
    router.delete('/admin/terms/destroy', {
      data: { ids },
      onSuccess: () => {
        push({ type: 'success', title: 'Deleted', message: 'Terms deleted successfully' });
      }
    });
  }
}
</script>

<template>
  <div>
    <Breadcrumb title="Terms List" :trail="[{ label: 'Terms' }]" />

    <DataTable
      title="Terms"
      :columns="columns" 
      :rows="terms || []" 
      row-key="id"
      :server-side="false" 
      :searchable="true"
      @delete-selected="deleteMultiple"
    >
      <template #header-actions>
        <BaseButton v-if="can('term_create')" icon="ri-add-line" @click="openCreate">
          Add Term
        </BaseButton>
      </template>

      <template #cell-english_text="{ value }">
        <span class="text-ink dark:text-slate-200 text-sm whitespace-pre-wrap">{{ value || '—' }}</span>
      </template>

      <template #cell-arabic_text="{ value }">
        <span class="text-ink dark:text-slate-200 text-sm whitespace-pre-wrap" dir="rtl">{{ value || '—' }}</span>
      </template>
      
      <template #row-actions="{ row }">
        <div class="inline-flex items-center gap-1">
          <button v-if="can('term_edit')" @click="openEdit(row)" class="grid place-items-center w-8 h-8 rounded-lg text-primary-600 hover:bg-primary-50 dark:hover:bg-primary-500/10 transition" title="Edit">
            <i class="ri-pencil-line"></i>
          </button>
          <button v-if="can('term_delete')" @click="deleteTerm(row)" class="grid place-items-center w-8 h-8 rounded-lg text-danger hover:bg-danger/10 transition" title="Delete">
            <i class="ri-delete-bin-line"></i>
          </button>
        </div>
      </template>
    </DataTable>

    <!-- Create/Edit Modal -->
    <BaseModal v-model="showModal" :title="isEditing ? 'Edit Term' : 'Add Term'" icon="ri-translate-2" size="md">
      <form @submit.prevent="submitForm" class="space-y-4">
        
        <FormInput
          v-model="form.english_text"
          type="textarea"
          :rows="5"
          label="English Term"
          placeholder="English term text"
          :error="form.errors.english_text"
          helper="This is the original English text of the term."
          required
        />

        <div dir="rtl" class="text-right">
          <FormInput
            v-model="form.arabic_text"
            type="textarea"
            :rows="5"
            label="النص العربي (Arabic Term)"
            placeholder="النص باللغة العربية"
            :error="form.errors.arabic_text"
            helper="هذه هي الترجمة العربية للمصطلح."
            required
            class="text-right"
          />
        </div>

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
