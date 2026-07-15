const fs = require('fs');
const file = 'resources/js/vue/views/Tasks/ScheduledTasksList.vue';
let content = fs.readFileSync(file, 'utf8');

// Add FormInput to imports
content = content.replace("import FormDate from '../../components/FormDate.vue';", "import FormDate from '../../components/FormDate.vue';\nimport FormInput from '../../components/FormInput.vue';");

// Add edit modal state variables
const stateVars = `
// Edit Modal Logic
const showEdit = ref(false);
const editForm = reactive({
  id: null,
  status: '',
  start_date: '',
  end_date: '',
  task_type: '',
  driver_id: '',
  client_id: '',
  from_location_id: '',
  to_location_id: '',
  update_related: false,
});
const editLoading = ref(false);

function openEdit(row) {
  Object.assign(editForm, {
    id: row.id,
    status: row.status,
    start_date: row.start_date || '',
    end_date: row.end_date || '',
    task_type: row.task_type || '',
    driver_id: row.driver_id || '',
    client_id: row.client_id || '',
    from_location_id: row.from_location_id || '',
    to_location_id: row.to_location_id || '',
    update_related: false,
  });
  showEdit.value = true;
}

async function submitEdit() {
  editLoading.value = true;
  try {
    const res = await fetch('/admin/scheduled-tasks/' + editForm.id, {
      method: 'PUT',
      headers: {
        'X-Requested-With': 'XMLHttpRequest',
        'Content-Type': 'application/json',
        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content')
      },
      body: JSON.stringify(editForm)
    });
    
    if (res.ok) {
      push({ type: 'success', title: 'Saved', message: 'Scheduled Task updated successfully.' });
      showEdit.value = false;
      reload();
    } else {
      push({ type: 'error', title: 'Error', message: 'Failed to update.' });
    }
  } catch (err) {
    push({ type: 'error', title: 'Error', message: 'Network error.' });
  } finally {
    editLoading.value = false;
  }
}
`;
content = content.replace("// Bulk delete logic", stateVars + "\n// Bulk delete logic");

// Change the Edit button to open the modal instead of routing
content = content.replace(
  `<a v-if="can('scheduled_task_edit')" :href="\`/admin/scheduled-tasks/\${row.id}/edit\`" class="grid place-items-center w-8 h-8 rounded-lg text-primary-600 hover:bg-primary-50 dark:hover:bg-primary-500/10 transition" title="Edit"><i class="ri-pencil-line"></i></a>`,
  `<button v-if="can('scheduled_task_edit')" @click="openEdit(row)" class="grid place-items-center w-8 h-8 rounded-lg text-primary-600 hover:bg-primary-50 dark:hover:bg-primary-500/10 transition" title="Edit"><i class="ri-pencil-line"></i></button>`
);

// Add the Edit Modal template at the end
const editModalTpl = `
    <!-- Edit Modal -->
    <BaseModal v-model="showEdit" title="Edit Scheduled Task" icon="ri-pencil-line" size="md">
      <form @submit.prevent="submitEdit" class="space-y-4">
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
          <div class="space-y-1.5">
            <label class="text-xs font-bold text-slate-600 dark:text-slate-300">Status</label>
            <FormSelect v-model="editForm.status" :options="[{value:'enabled',label:'Enabled'},{value:'disabled',label:'Disabled'}]" class="w-full" required />
          </div>
          <div class="space-y-1.5">
            <label class="text-xs font-bold text-slate-600 dark:text-slate-300">Task Type</label>
            <FormSelect v-model="editForm.task_type" :options="[{value:'SAMPLE',label:'Sample'},{value:'BOX',label:'Box'}]" class="w-full" required />
          </div>
          <div class="space-y-1.5">
            <label class="text-xs font-bold text-slate-600 dark:text-slate-300">Start Date</label>
            <FormDate v-model="editForm.start_date" mode="single" class="w-full" />
          </div>
          <div class="space-y-1.5">
            <label class="text-xs font-bold text-slate-600 dark:text-slate-300">End Date</label>
            <FormDate v-model="editForm.end_date" mode="single" class="w-full" />
          </div>
          <div class="space-y-1.5">
            <label class="text-xs font-bold text-slate-600 dark:text-slate-300">Driver</label>
            <FormSelect v-model="editForm.driver_id" :options="driverOpts.filter(o=>o.value!=='')" class="w-full" />
          </div>
          <div class="space-y-1.5">
            <label class="text-xs font-bold text-slate-600 dark:text-slate-300">Client</label>
            <FormSelect v-model="editForm.client_id" :options="clientOpts.filter(o=>o.value!=='')" class="w-full" />
          </div>
          <div class="space-y-1.5">
            <label class="text-xs font-bold text-slate-600 dark:text-slate-300">From Location</label>
            <FormSelect v-model="editForm.from_location_id" :options="locOpts.filter(o=>o.value!=='')" class="w-full" />
          </div>
          <div class="space-y-1.5">
            <label class="text-xs font-bold text-slate-600 dark:text-slate-300">To Location</label>
            <FormSelect v-model="editForm.to_location_id" :options="locOpts.filter(o=>o.value!=='')" class="w-full" />
          </div>
        </div>
        
        <div class="mt-4 pt-4 border-t border-slate-100 dark:border-white/5">
          <label class="flex items-center gap-2 cursor-pointer">
            <input type="checkbox" v-model="editForm.update_related" class="rounded border-slate-300 text-primary-600 focus:ring-primary-500" />
            <span class="text-sm text-slate-700 dark:text-slate-300 font-medium">Update all related occurrences</span>
          </label>
        </div>
        
        <div class="flex justify-end gap-3 pt-2">
          <BaseButton type="button" variant="light" @click="showEdit = false">Cancel</BaseButton>
          <BaseButton type="submit" variant="primary" :loading="editLoading">Save Changes</BaseButton>
        </div>
      </form>
    </BaseModal>
`;
content = content.replace("  </div>\n</template>", editModalTpl + "  </div>\n</template>");

fs.writeFileSync(file, content);
console.log("Patched ScheduledTasksList.vue");
