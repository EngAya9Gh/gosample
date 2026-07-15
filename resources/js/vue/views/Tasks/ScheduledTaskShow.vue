<script setup>
import { computed } from 'vue';
import Breadcrumb from '../../components/Breadcrumb.vue';
import DataTable from '../../components/DataTable.vue';
import BaseButton from '../../components/BaseButton.vue';
import StatusBadge from '../../components/StatusBadge.vue';

const props = defineProps({
  task: Object,
  relatedTasks: Array
});

const columns = [
  { key: 'seq', label: '#', width: '56px' },
  { key: 'id', label: 'ID', sortable: true },
  { key: 'name', label: 'Name' },
  { key: 'status', label: 'Status' },
  { key: 'start_date', label: 'Start Date' },
  { key: 'end_date', label: 'End Date' },
  { key: 'from_location', label: 'From' },
  { key: 'to_location', label: 'To' },
  { key: 'client', label: 'Client' },
  { key: 'selected_hour', label: 'Hour' },
  { key: 'task_type', label: 'Type' },
  { key: 'day', label: 'Day' },
  { key: 'driver', label: 'Driver' }
];

const rows = computed(() => {
  return (props.relatedTasks || []).map((t, i) => ({
    seq: i + 1,
    id: t.id,
    name: t.name,
    status: t.status,
    start_date: t.start_date,
    end_date: t.end_date,
    from_location: t.from_location ? t.from_location.name : '',
    to_location: t.to_location ? t.to_location.name : '',
    client: t.client ? t.client.english_name : '',
    selected_hour: t.selected_hour,
    task_type: t.task_type,
    day: t.day,
    driver: t.driver ? t.driver.name : ''
  }));
});
</script>

<template>
  <div>
    <Breadcrumb :title="`Schedule: ${task?.name || 'Task'}`" :trail="[{label:'Scheduled Tasks',href:'/admin/scheduled-tasks'},{label:'Occurrences'}]">
      <template #actions>
        <!-- Add actions if needed -->
      </template>
    </Breadcrumb>
    <DataTable :columns="columns" :rows="rows" row-key="id">
      <template #cell-id="{ value }"><span class="font-semibold text-primary-700 dark:text-primary-300">#{{ value }}</span></template>
      <template #cell-status="{ value }"><StatusBadge :status="value" /></template>
      
    </DataTable>
  </div>
</template>
