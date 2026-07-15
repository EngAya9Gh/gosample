const fs = require('fs');
const file = 'resources/js/vue/views/Tasks/ScheduledTasksList.vue';
let content = fs.readFileSync(file, 'utf8');

// Add 'name: row.name' to the openEdit object assign
content = content.replace(
  "id: row.id,\n    status: row.status,",
  "id: row.id,\n    name: row.name,\n    status: row.status,"
);

// Add name to the editForm reactive object
content = content.replace(
  "id: null,\n  status: '',",
  "id: null,\n  name: '',\n  status: '',"
);

// Add the FormInput to the template
const nameFieldTpl = `
          <div class="space-y-1.5 md:col-span-2">
            <label class="text-xs font-bold text-slate-600 dark:text-slate-300">Name</label>
            <FormInput v-model="editForm.name" required class="w-full" />
          </div>
`;
content = content.replace(
  '<div class="grid grid-cols-1 md:grid-cols-2 gap-4">',
  '<div class="grid grid-cols-1 md:grid-cols-2 gap-4">' + nameFieldTpl
);

fs.writeFileSync(file, content);
console.log("Patched name field into ScheduledTasksList.vue");
