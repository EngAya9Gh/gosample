const fs = require('fs');
const file = 'resources/js/vue/views/Tasks/ScheduledTaskShow.vue';
let content = fs.readFileSync(file, 'utf8');

content = content.replace(
  '<template #row-actions="{ row }"><a :href="`/admin/scheduled-tasks/${row.id}`" class="grid place-items-center w-8 h-8 rounded-lg text-info hover:bg-info/10"><i class="ri-eye-line"></i></a></template>',
  ''
);

fs.writeFileSync(file, content);
console.log("Removed view button from ScheduledTaskShow");
