const fs = require('fs');
const file = 'resources/js/vue/views/SwapRequests/SwapRequestForm.vue';
let content = fs.readFileSync(file, 'utf8');

// Import watch
content = content.replace(
  "import { ref, computed } from 'vue';",
  "import { ref, computed, watch } from 'vue';"
);

// Define dynamicTasks
content = content.replace(
  "const driverOptions = computed",
  "const dynamicTasks = ref([]);\n\nconst driverOptions = computed"
);

// Replace taskOptions
content = content.replace(
  /const taskOptions = computed\(\(\) => \{[\s\S]*?\}\);/,
  `const taskOptions = computed(() => {
  if (dynamicTasks.value.length > 0) {
    return dynamicTasks.value.map(item => ({
      value: item.id,
      label: \`#\${item.id} \${item.from ? item.from.name : ''} - (\${item.status})\`
    }));
  }
  return Object.entries(props.tasks)
    .filter(([value, label]) => value !== '')
    .map(([value, label]) => ({ value: Number(value), label: \`#\${label}\` }));
});`
);

// Add watch block BEFORE form definition
const watchBlock = `
watch(() => form.driver_a, async (newVal) => {
  if (!newVal) {
    dynamicTasks.value = [];
    return;
  }
  try {
    const res = await fetch('/api/swap/tasks/list', {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
        'X-Requested-With': 'XMLHttpRequest',
        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content')
      },
      body: JSON.stringify({ driver_id: newVal })
    });
    const data = await res.json();
    if (data && data.status && data.data) {
      dynamicTasks.value = data.data;
    } else {
      dynamicTasks.value = [];
    }
  } catch (err) {
    console.error('Error fetching tasks', err);
    dynamicTasks.value = [];
  }
}, { immediate: true });
`;

// Insert it AFTER useForm (wait, we need to insert it AFTER form is declared because we watch form.driver_a!)
content = content.replace(
  /const submit = \(\) => \{/,
  watchBlock + '\n\nconst submit = () => {'
);

fs.writeFileSync(file, content);
console.log("Patched SwapRequestForm.vue with dynamic task fetching");
