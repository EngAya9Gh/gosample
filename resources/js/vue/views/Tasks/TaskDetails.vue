<script setup>
import { ref, computed } from 'vue';
import { router, useForm } from '@inertiajs/vue3';
import Breadcrumb from '../../components/Breadcrumb.vue';
import BaseCard from '../../components/BaseCard.vue';
import BaseAvatar from '../../components/BaseAvatar.vue';
import StatusBadge from '../../components/StatusBadge.vue';
import DataTable from '../../components/DataTable.vue';
import BaseModal from '../../components/BaseModal.vue';
import VueApexCharts from 'vue3-apexcharts';
import { usePermissions } from '../../composables/usePermissions';

const props = defineProps({
  task: { type: Object, required: true },
  bags: { type: [Object, Array], default: () => ({}) },
  bag_count: { type: Number, default: 0 },
  sample_count: { type: Number, default: 0 },
  carTracking: { type: Object, default: null },
  labels: { type: Array, default: () => [] },
  temp1: { type: Array, default: () => [] },
  temp2: { type: Array, default: () => [] },
  temp3: { type: Array, default: () => [] },
});

const { can } = usePermissions();

const showEditTimesModal = ref(false);
const editTimesForm = useForm({
  freezer_out_date: props.task.freezer_out_date ? props.task.freezer_out_date.substring(0, 16).replace(' ', 'T') : '',
  close_date: props.task.close_date ? props.task.close_date.substring(0, 16).replace(' ', 'T') : '',
});

function submitEditTimes() {
  editTimesForm.put(`/app/admin/tasks/${props.task.id}/update-times`, {
    onSuccess: () => {
      showEditTimesModal.value = false;
    }
  });
}

function printPage() {
  window.print();
}

function getAvgTemp(type, carTracking) {
  if (!carTracking || !carTracking.cnt) {
    if (type === 'ROOM') return '+15C TO +25C';
    if (type === 'REFRIGERATE') return '+2C TO +8C';
    if (type === 'FROZEN') return '0C TO -18C';
    return type;
  }
  const avg1 = carTracking.total_temp_1 / carTracking.cnt;
  const avg2 = carTracking.total_temp_2 / carTracking.cnt;
  const avg3 = carTracking.total_temp_3 / carTracking.cnt;
  
  if (type === 'ROOM') {
    if (avg1 >= 15 && avg1 <= 25) return avg1.toFixed(2) + ' °C';
    if (avg2 >= 15 && avg2 <= 25) return avg2.toFixed(2) + ' °C';
    if (avg3 >= 15 && avg3 <= 25) return avg3.toFixed(2) + ' °C';
    return '+15C TO +25C';
  }
  if (type === 'REFRIGERATE') {
    if (avg1 >= 2 && avg1 <= 8) return avg1.toFixed(2) + ' °C';
    if (avg2 >= 2 && avg2 <= 8) return avg2.toFixed(2) + ' °C';
    if (avg3 >= 2 && avg3 <= 8) return avg3.toFixed(2) + ' °C';
    return '+2C TO +8C';
  }
  if (type === 'FROZEN') {
    if (avg1 >= -18 && avg1 <= 0) return avg1.toFixed(2) + ' °C';
    if (avg2 >= -18 && avg2 <= 0) return avg2.toFixed(2) + ' °C';
    if (avg3 >= -18 && avg3 <= 0) return avg3.toFixed(2) + ' °C';
    return '0C TO -18C';
  }
  return type;
}

// Group samples by bag for the data table
const bagRows = computed(() => {
  const rows = [];
  let seq = 1;
  const bagGroups = props.bags || {};
  Object.keys(bagGroups).forEach((key) => {
    let samples = bagGroups[key];
    if (!Array.isArray(samples)) {
      samples = Object.values(samples);
    }
    if (samples.length === 0) return;
    
    const barcodes = samples.map(s => `[${s.barcode_id || 'N/A'}]`).join(' ');
    const first = samples[0];
    
    rows.push({
      id: key,
      sequence: seq++,
      bag_code: key,
      sample_count_and_barcodes: `${samples.length} ${barcodes}`,
      sample_type: first.sample_type || 'N/A',
      raw_temp_type: first.temperature_type || 'ROOM',
      temperature_type: getAvgTemp(first.temperature_type, props.carTracking),
      container: first.container ? (first.container.container_code || first.container.id) : 'N/A'
    });
  });
  return rows;
});

const columns = [
  { key: 'bag_code', label: 'Bag Code' },
  { key: 'sequence', label: 'BAGS #', width: '80px' },
  { key: 'sample_count_and_barcodes', label: 'SAMPLE #', wrap: true },
  { key: 'sample_type', label: 'TYPE' },
  { key: 'temperature_type', label: 'TEMPERATURE' },
  { key: 'container', label: 'CONTAINER' },
];

function cellText(r, c) {
  return r[c.key] == null ? '' : String(r[c.key]);
}

function matrix() {
  const header = columns.map((c) => c.label);
  const body = bagRows.value.map((r) => columns.map((c) => cellText(r, c)));
  return { header, body };
}

function onExport(kind) {
  const { header, body } = matrix();
  if (kind === 'copy') {
    navigator.clipboard?.writeText([header.join('\t'), ...body.map((r) => r.join('\t'))].join('\n'));
    alert(`${body.length} rows copied to clipboard!`);
  } else if (kind === 'csv' || kind === 'excel') {
    const esc = (s) => `"${String(s).replace(/"/g, '""')}"`;
    const csv = [header.map(esc).join(','), ...body.map((r) => r.map(esc).join(','))].join('\n');
    const a = document.createElement('a');
    a.href = URL.createObjectURL(new Blob(['\ufeff' + csv], { type: 'text/csv;charset=utf-8' }));
    a.download = `Task_${props.task.id}_Manifest.csv`;
    a.click();
    URL.revokeObjectURL(a.href);
  } else if (kind === 'print') {
    const w = window.open('', '_blank');
    const th = header.map((h) => `<th>${h}</th>`).join('');
    const tr = body.map((r) => `<tr>${r.map((c) => `<td>${c}</td>`).join('')}</tr>`).join('');
    w.document.write(`<html dir="ltr"><head><title>Sample Manifest</title><style>table{border-collapse:collapse;width:100%;font-family:sans-serif;font-size:12px}th,td{border:1px solid #cbd5e1;padding:6px 8px;text-align:left}th{background:#007588;color:#fff}</style></head><body><h3>Task #${props.task.id} - Sample Manifest</h3><table><thead><tr>${th}</tr></thead><tbody>${tr}</tbody></table></body></html>`);
    w.document.close(); w.focus(); w.print();
  }
}

const chartSeries = computed(() => [
  { name: 'Refrigeration', data: props.temp1 },
  { name: 'Freezing', data: props.temp2 },
  { name: 'Room Temp', data: props.temp3 },
]);

const chartOptions = computed(() => ({
  chart: { type: 'line', height: 250, width: '100%', toolbar: { show: false }, background: 'transparent' },
  colors: ['#ef4444', '#3b82f6', '#22c55e'],
  stroke: { width: 3, curve: 'smooth' },
  xaxis: { 
    categories: props.labels, 
    tickAmount: 15,
    labels: { style: { colors: '#94a3b8' }, hideOverlappingLabels: true } 
  },
  yaxis: { labels: { style: { colors: '#94a3b8' } } },
  grid: { borderColor: 'rgba(148, 163, 184, 0.1)', strokeDashArray: 4 },
  legend: { show: false },
  theme: { mode: 'light' }
}));

function fmtDate(d) {
  if (!d) return '—';
  return new Date(d).toLocaleString('en-GB', { 
    year: 'numeric', month: 'short', day: 'numeric', 
    hour: '2-digit', minute: '2-digit' 
  });
}
</script>

<template>
  <div class="flex flex-col gap-5 p-4 md:p-6 pb-20 print:p-8">
    <!-- Dedicated Print Header -->
    <div class="hidden print:flex flex-col items-center justify-center mb-6 pb-4 border-b-2 border-slate-800">
      <svg height="50" viewBox="0 0 329 122" fill="none" xmlns="http://www.w3.org/2000/svg" class="mb-4">
          <path d="M92.5401 54.3401H66.4245C61.5988 54.3401 57.0569 56.3239 53.9344 60.0081C53.6506 60.2915 53.0828 60.2915 52.5151 60.0081C49.3926 56.3239 44.8507 54.3401 40.025 54.3401H13.9094V67.9433H92.5401V54.3401Z" fill="#C274BF"/>
          <path d="M103.043 54.3401H92.5401V14.9476C83.4564 15.7978 75.2243 19.7654 69.2632 26.567L60.1795 37.0528C56.4892 41.3037 50.2442 41.3037 46.5539 37.0528L37.4702 26.567C31.2252 19.7654 22.9931 15.5144 13.9094 14.9476V54.3401H3.40639C1.41933 54.3401 0 52.6397 0 50.9393V4.17843C0 2.47804 1.70319 0.777641 3.40639 0.777641H11.3546C25.264 0.777641 38.8896 6.72903 47.9733 17.4982L53.3667 23.733L58.7601 17.4982C67.8438 7.01243 81.4694 0.777641 95.3788 0.777641H103.043C105.03 0.777641 106.45 2.47804 106.45 4.17843V50.6559C106.733 52.6397 105.03 54.3401 103.043 54.3401Z" fill="#007588"/>
          <path d="M106.45 71.9109C106.733 69.9271 105.03 68.2267 103.043 68.2267H92.5401C92.5401 89.765 74.9405 107.336 53.3667 107.336C31.7929 107.336 14.1933 89.765 14.1933 68.2267H3.69026C1.7032 68.2267 7.48038e-06 69.9271 0.283873 71.9109C2.27093 99.4006 25.264 121.222 53.3667 121.222C81.4694 121.222 104.462 99.4006 106.45 71.9109Z" fill="#007588"/>
          <path d="M217.441 84.3804V46.9717C217.441 38.1863 211.196 33.3686 203.248 33.3686C202.964 33.3686 202.68 33.3686 202.396 33.3686C193.88 33.652 187.351 40.7369 187.351 48.9555C187.351 57.7409 187.351 73.6113 187.351 80.6963C187.351 82.68 185.648 84.3804 183.661 84.3804H175.713V46.9717C175.713 38.1863 169.468 33.3686 161.236 33.3686C160.952 33.3686 160.668 33.3686 160.384 33.3686C152.152 33.652 145.623 40.7369 145.623 48.9555V80.1295C145.623 82.3966 143.636 84.3804 141.365 84.3804H133.984V25.7168H141.081C143.068 25.7168 144.771 27.4172 144.771 29.401V32.235C148.462 27.7006 155.274 23.4496 164.642 23.4496C173.442 23.4496 181.106 27.1338 184.513 34.2188C189.622 27.7006 196.435 23.4496 206.654 23.4496C219.712 23.4496 229.079 31.3848 229.079 44.9879V80.9796C229.079 82.9634 227.376 84.6638 225.389 84.6638H217.441V84.3804Z" fill="#007588"/>
          <path d="M274.214 82.3967C274.214 84.6638 272.227 86.0808 270.24 86.0808C252.924 84.6638 245.544 73.3279 245.544 56.6073V35.6358H235.324V25.7168H245.544V7.57922H253.492C255.479 7.57922 257.182 9.27962 257.182 11.2634V25.4334H273.078V35.069H257.182V56.3239C257.182 67.9433 261.724 75.5951 274.498 75.5951L274.214 82.3967Z" fill="#007588"/>
          <path d="M291.246 53.2065C291.246 39.0365 301.465 31.1014 318.213 31.1014C321.619 31.1014 325.31 31.3848 328.432 32.235L328.716 25.7168C328.716 23.733 327.297 22.0326 325.594 21.7492C322.755 21.4658 319.916 21.1824 315.942 21.1824C294.368 21.1824 278.756 32.8018 278.756 54.0567C278.756 75.3117 294.368 86.931 315.942 86.931C319.632 86.931 322.755 86.6476 325.594 86.3642C327.581 86.0808 329 84.3804 328.716 82.3966L328.432 75.8785C325.31 76.4453 321.619 77.0121 318.213 77.0121C301.181 77.0121 291.246 69.0769 291.246 54.9069" fill="#007588"/>
      </svg>
      <h2 class="text-lg font-bold text-slate-800 tracking-widest uppercase">Task Manifest</h2>
    </div>

    <!-- Breadcrumbs -->
    <div class="print:hidden">
      <Breadcrumb :items="[
        { label: 'Admin', icon: 'ri-home-8-line', href: '/app/admin/dashboard' },
        { label: 'Tasks', href: '/app/admin/tasks' },
        { label: `Task #${task.id}`, active: true }
      ]" />
    </div>

    <!-- Header Section -->
    <div class="flex flex-col md:flex-row justify-between items-start md:items-center gap-4 bg-gradient-to-br from-[#005D69] to-[#0d9488] text-white p-5 rounded-2xl shadow-md print:bg-transparent print:text-ink print:shadow-none print:p-0 print:border-b print:border-slate-300 print:pb-4 print:rounded-none">
      <div class="flex flex-col gap-3">
        <div class="flex items-center gap-4">
          <button class="w-10 h-10 rounded-full border border-white/30 flex items-center justify-center text-white hover:bg-white/10 transition-colors print:hidden" @click="router.visit('/app/admin/tasks')">
            <i class="ri-arrow-left-line text-xl"></i>
          </button>
          <div>
            <!-- UI Logo above Task Title using the SVG from old view -->
            <svg height="35" viewBox="0 0 329 122" fill="none" xmlns="http://www.w3.org/2000/svg" class="mb-2 print:hidden drop-shadow-md">
                <path d="M92.5401 54.3401H66.4245C61.5988 54.3401 57.0569 56.3239 53.9344 60.0081C53.6506 60.2915 53.0828 60.2915 52.5151 60.0081C49.3926 56.3239 44.8507 54.3401 40.025 54.3401H13.9094V67.9433H92.5401V54.3401Z" fill="#C274BF"/>
                <path d="M103.043 54.3401H92.5401V14.9476C83.4564 15.7978 75.2243 19.7654 69.2632 26.567L60.1795 37.0528C56.4892 41.3037 50.2442 41.3037 46.5539 37.0528L37.4702 26.567C31.2252 19.7654 22.9931 15.5144 13.9094 14.9476V54.3401H3.40639C1.41933 54.3401 0 52.6397 0 50.9393V4.17843C0 2.47804 1.70319 0.777641 3.40639 0.777641H11.3546C25.264 0.777641 38.8896 6.72903 47.9733 17.4982L53.3667 23.733L58.7601 17.4982C67.8438 7.01243 81.4694 0.777641 95.3788 0.777641H103.043C105.03 0.777641 106.45 2.47804 106.45 4.17843V50.6559C106.733 52.6397 105.03 54.3401 103.043 54.3401Z" fill="#fff"/>
                <path d="M106.45 71.9109C106.733 69.9271 105.03 68.2267 103.043 68.2267H92.5401C92.5401 89.765 74.9405 107.336 53.3667 107.336C31.7929 107.336 14.1933 89.765 14.1933 68.2267H3.69026C1.7032 68.2267 7.48038e-06 69.9271 0.283873 71.9109C2.27093 99.4006 25.264 121.222 53.3667 121.222C81.4694 121.222 104.462 99.4006 106.45 71.9109Z" fill="#fff"/>
                <path d="M217.441 84.3804V46.9717C217.441 38.1863 211.196 33.3686 203.248 33.3686C202.964 33.3686 202.68 33.3686 202.396 33.3686C193.88 33.652 187.351 40.7369 187.351 48.9555C187.351 57.7409 187.351 73.6113 187.351 80.6963C187.351 82.68 185.648 84.3804 183.661 84.3804H175.713V46.9717C175.713 38.1863 169.468 33.3686 161.236 33.3686C160.952 33.3686 160.668 33.3686 160.384 33.3686C152.152 33.652 145.623 40.7369 145.623 48.9555V80.1295C145.623 82.3966 143.636 84.3804 141.365 84.3804H133.984V25.7168H141.081C143.068 25.7168 144.771 27.4172 144.771 29.401V32.235C148.462 27.7006 155.274 23.4496 164.642 23.4496C173.442 23.4496 181.106 27.1338 184.513 34.2188C189.622 27.7006 196.435 23.4496 206.654 23.4496C219.712 23.4496 229.079 31.3848 229.079 44.9879V80.9796C229.079 82.9634 227.376 84.6638 225.389 84.6638H217.441V84.3804Z" fill="#fff"/>
                <path d="M274.214 82.3967C274.214 84.6638 272.227 86.0808 270.24 86.0808C252.924 84.6638 245.544 73.3279 245.544 56.6073V35.6358H235.324V25.7168H245.544V7.57922H253.492C255.479 7.57922 257.182 9.27962 257.182 11.2634V25.4334H273.078V35.069H257.182V56.3239C257.182 67.9433 261.724 75.5951 274.498 75.5951L274.214 82.3967Z" fill="#fff"/>
                <path d="M291.246 53.2065C291.246 39.0365 301.465 31.1014 318.213 31.1014C321.619 31.1014 325.31 31.3848 328.432 32.235L328.716 25.7168C328.716 23.733 327.297 22.0326 325.594 21.7492C322.755 21.4658 319.916 21.1824 315.942 21.1824C294.368 21.1824 278.756 32.8018 278.756 54.0567C278.756 75.3117 294.368 86.931 315.942 86.931C319.632 86.931 322.755 86.6476 325.594 86.3642C327.581 86.0808 329 84.3804 328.716 82.3966L328.432 75.8785C325.31 76.4453 321.619 77.0121 318.213 77.0121C301.181 77.0121 291.246 69.0769 291.246 54.9069" fill="#fff"/>
            </svg>
            
            <div class="flex items-center gap-3">
              <h1 class="text-2xl font-bold tracking-tight print:text-ink">Task #{{ task.id }}</h1>
              <span class="bg-white text-primary-700 font-bold text-xs px-3 py-1 rounded-full shadow-sm print:border print:border-slate-300 print:bg-transparent print:text-slate-600 print:shadow-none">{{ task.status }}</span>
            </div>
            <p class="text-sm text-white/80 mt-1 print:text-slate-500">Created on {{ task.created_at || '—' }}</p>
          </div>
        </div>
      </div>
      
      <div class="flex flex-col md:items-end justify-end h-full gap-4 w-full md:w-auto">
        
        <!-- Action Buttons -->
        <div class="flex items-center gap-2 w-full md:w-auto print:hidden">
          <button @click="printPage" class="flex-1 md:flex-none border border-white/30 text-white text-sm px-5 py-2.5 rounded-lg hover:bg-white/10 transition-colors flex items-center justify-center gap-2 font-medium">
            <i class="ri-printer-line text-lg"></i> Print
          </button>
          <button v-if="can('task_edit_times')" @click="showEditTimesModal = true" class="flex-1 md:flex-none border border-white/30 text-white text-sm px-5 py-2.5 rounded-lg hover:bg-white/10 transition-colors flex items-center justify-center gap-2 font-medium">
            <i class="ri-edit-line text-lg"></i> Edit Times
          </button>
          <button class="flex-1 md:flex-none bg-white text-primary-700 text-sm px-5 py-2.5 rounded-lg hover:bg-slate-50 transition-colors shadow-sm flex items-center justify-center gap-2 font-bold">
            <i class="ri-swap-line text-lg"></i> Reassign
          </button>
        </div>
      </div>
    </div>

    <!-- Bento Grid Layout -->
    <div class="grid grid-cols-12 gap-5">
      <!-- Task Overview (Span 8) -->
      <BaseCard class="col-span-12 lg:col-span-8 flex flex-col gap-5 p-5 print:col-span-12 print:shadow-none print:border print:border-slate-300">
        <div class="flex justify-between items-center border-b border-slate-100 dark:border-white/5 pb-4 print:border-slate-200">
          <h2 class="text-lg font-bold text-ink dark:text-white">Task Overview</h2>
          <span class="bg-primary-50 dark:bg-primary-500/10 text-primary-600 dark:text-primary-400 px-3 py-1 rounded-full text-xs font-bold print:bg-transparent print:border print:border-slate-200 print:text-slate-600">Arrival of Pick Up Location</span>
        </div>
        
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
          <div class="space-y-4">
            <div>
              <p class="text-[11px] font-bold text-slate-500 uppercase tracking-wider">Requestor</p>
              <p class="text-base font-bold text-ink dark:text-slate-100 mt-1 print:text-ink">{{ task.client?.english_name || '—' }}</p>
            </div>
            <div>
              <p class="text-[11px] font-bold text-slate-500 uppercase tracking-wider">Billed To</p>
              <p class="text-sm text-ink dark:text-slate-200 mt-1 font-medium print:text-ink">{{ task.client?.english_name || '—' }}</p>
            </div>
          </div>
          
          <!-- Locations Timeline -->
          <div class="space-y-4 relative">
            <div class="absolute left-3 top-6 bottom-6 w-[2px] bg-slate-200 dark:bg-white/10 z-0 print:bg-slate-300"></div>
            
            <div class="relative z-10 flex gap-4">
              <div class="w-6 h-6 rounded-full border-[3px] border-primary-500 bg-surface dark:bg-surface-dark flex items-center justify-center mt-0.5 shadow-sm print:bg-white print:border-slate-500">
                <div class="w-2 h-2 rounded-full bg-primary-500 print:bg-slate-500"></div>
              </div>
              <div>
                <p class="text-[11px] font-bold text-slate-500 uppercase tracking-wider">Pickup Location</p>
                <p class="text-sm font-bold text-ink dark:text-slate-100 mt-1 print:text-ink">{{ task.from?.name || '—' }}</p>
              </div>
            </div>
            
            <div class="relative z-10 flex gap-4 pt-3">
              <div class="w-6 h-6 rounded-full border-[3px] border-slate-300 dark:border-white/20 bg-surface dark:bg-surface-dark flex items-center justify-center mt-0.5 print:bg-white print:border-slate-300">
                <i class="ri-map-pin-fill text-[11px] text-slate-400"></i>
              </div>
              <div>
                <p class="text-[11px] font-bold text-slate-500 uppercase tracking-wider">Delivery Location</p>
                <p class="text-sm font-bold text-ink dark:text-slate-100 mt-1 print:text-ink">{{ task.to?.name || '—' }}</p>
              </div>
            </div>
          </div>
        </div>
        
        <div class="border-t border-slate-100 dark:border-white/5 pt-5 mt-2 print:border-slate-200">
          <h3 class="text-base font-bold text-ink dark:text-white mb-4 print:text-ink">Task Information</h3>
          <div class="grid grid-cols-2 md:grid-cols-3 gap-y-5 gap-x-4">
            <div>
              <p class="text-[11px] font-bold text-slate-500 uppercase tracking-wider">Task Creation Date</p>
              <p class="text-[13px] font-medium text-ink dark:text-slate-200 mt-1 print:text-ink">{{ task.created_at || '—' }}</p>
            </div>
            <div>
              <p class="text-[11px] font-bold text-slate-500 uppercase tracking-wider">Type of Request</p>
              <p class="text-[13px] font-medium text-ink dark:text-slate-200 mt-1 print:text-ink">{{ task.type || 'One time' }}</p>
            </div>
            <div>
              <p class="text-[11px] font-bold text-slate-500 uppercase tracking-wider">Bag QTY</p>
              <p class="text-[13px] font-medium text-ink dark:text-slate-200 mt-1 print:text-ink">{{ bag_count }}</p>
            </div>
            <div>
              <p class="text-[11px] font-bold text-slate-500 uppercase tracking-wider">Receiving Date</p>
              <p class="text-[13px] font-medium text-ink dark:text-slate-200 mt-1 print:text-ink">{{ fmtDate(task.collection_date) }}</p>
            </div>
            <div class="print:hidden">
              <p class="text-[11px] font-bold text-slate-500 uppercase tracking-wider">Driver Name</p>
              <p class="text-[13px] font-medium text-ink dark:text-slate-200 mt-1">{{ task.driver?.name || '—' }}</p>
            </div>
            <div>
              <p class="text-[11px] font-bold text-slate-500 uppercase tracking-wider">Sample QTY</p>
              <p class="text-[13px] font-medium text-ink dark:text-slate-200 mt-1 print:text-ink">{{ sample_count }}</p>
            </div>
          </div>
        </div>
      </BaseCard>

      <!-- Driver Info (Span 4) -->
      <BaseCard class="col-span-12 lg:col-span-4 flex flex-col justify-between p-5 print:hidden">
        <div>
          <div class="flex justify-between items-center mb-4 border-b border-slate-100 dark:border-white/5 pb-4">
            <h2 class="text-lg font-bold text-ink dark:text-white">Driver Details</h2>
            <i class="ri-truck-line text-slate-400 text-xl"></i>
          </div>
          
          <div class="flex items-center gap-4 mb-5">
            <div class="w-16 h-16 rounded-full border-[3px] border-primary-100 dark:border-primary-900 overflow-hidden flex-shrink-0">
              <BaseAvatar :name="task.driver?.name || 'Unknown'" :size="64" />
            </div>
            <div>
              <p class="text-base font-bold text-ink dark:text-white leading-tight">{{ task.driver?.name || '—' }}</p>
              <p class="text-xs text-slate-500 dark:text-slate-400 mt-1.5 flex items-center gap-1 font-medium">
                <i class="ri-star-fill text-amber-400 text-sm"></i>
                4.9 Rating (124 Deliveries)
              </p>
            </div>
          </div>
        </div>
        
        <div class="grid grid-cols-2 gap-3 mt-auto">
          <button class="bg-primary-50 text-primary-600 dark:bg-primary-500/10 dark:text-primary-400 text-sm font-bold py-2.5 rounded-lg hover:bg-primary-100 dark:hover:bg-primary-500/20 transition-colors flex items-center justify-center gap-2">
            <i class="ri-phone-line text-lg"></i> Call
          </button>
          <button class="bg-primary-50 text-primary-600 dark:bg-primary-500/10 dark:text-primary-400 text-sm font-bold py-2.5 rounded-lg hover:bg-primary-100 dark:hover:bg-primary-500/20 transition-colors flex items-center justify-center gap-2">
            <i class="ri-chat-3-line text-lg"></i> Message
          </button>
        </div>
      </BaseCard>

      <!-- Progress Timeline (Span 4) -->
      <BaseCard class="col-span-12 lg:col-span-4 p-5 relative overflow-hidden">
        <div class="flex justify-between items-center mb-6 border-b border-slate-100 dark:border-white/5 pb-4 relative z-10">
          <h2 class="text-lg font-bold text-ink dark:text-white">Timeline</h2>
          <i class="ri-time-line text-slate-400 text-xl"></i>
        </div>
        
        <div class="relative z-10 pl-2">
          <!-- The vertical line -->
          <div class="absolute left-7 top-8 bottom-8 w-[2px] bg-gradient-to-b from-primary-500 via-slate-200 to-slate-200 dark:from-primary-500 dark:via-white/10 dark:to-white/10 z-0"></div>
          
          <div class="flex gap-4 mb-6 relative">
            <div class="w-10 h-10 rounded-full bg-primary-500 flex items-center justify-center text-white shadow-sm z-10 shrink-0">
              <i class="ri-check-line text-xl"></i>
            </div>
            <div class="pt-1">
              <p class="text-sm font-bold text-ink dark:text-white">Collection Information</p>
              <p class="text-xs text-slate-500 mt-1 font-medium">Arrival: {{ task.from_location_arrival_time || '—' }}</p>
              <p class="text-xs text-slate-500 mt-0.5 font-medium">Departure: {{ task.pickup_time || '—' }}</p>
            </div>
          </div>
          
          <div class="flex gap-4 mb-6 relative">
            <div class="w-10 h-10 rounded-full bg-primary-500 flex items-center justify-center text-white shadow-sm z-10 shrink-0">
              <i class="ri-car-line text-xl"></i>
            </div>
            <div class="pt-1">
              <p class="text-sm font-bold text-ink dark:text-white">Sample Placement</p>
              <p class="text-xs text-slate-500 mt-1 font-medium">Sample Receiving: {{ fmtDate(task.collection_date) }}</p>
              <p class="text-xs text-slate-500 mt-0.5 font-medium">Sample In: {{ fmtDate(task.freezer_date) }}</p>
            </div>
          </div>
          
          <div class="flex gap-4 relative">
            <div class="w-10 h-10 rounded-full border-[3px] border-primary-500 flex items-center justify-center text-primary-500 bg-surface dark:bg-surface-dark shadow-sm z-10 shrink-0 animate-pulse-ring">
              <i class="ri-test-tube-line text-xl"></i>
            </div>
            <div class="pt-1">
              <p class="text-sm font-bold text-primary-600 dark:text-primary-400">Sample Delivery</p>
              <p class="text-xs text-slate-500 mt-1 font-medium">Sample Out: {{ fmtDate(task.freezer_out_date) }}</p>
              <p class="text-xs text-slate-500 mt-0.5 font-medium">Delivery: {{ fmtDate(task.close_date) }}</p>
            </div>
          </div>
        </div>
      </BaseCard>

      <!-- Temperature Monitoring & Manifest Container (Span 8) -->
      <div class="col-span-12 lg:col-span-8 flex flex-col gap-5">
        
        <!-- Sample Manifest -->
        <BaseCard class="overflow-hidden">
          <div class="p-5 border-b border-slate-100 dark:border-white/5 flex justify-between items-center bg-slate-50/50 dark:bg-white/[0.02]">
            <h2 class="text-lg font-bold text-ink dark:text-white">Sample Manifest</h2>
            <span class="bg-primary-50 dark:bg-primary-500/10 text-primary-600 dark:text-primary-400 font-bold text-xs px-3 py-1.5 rounded-full">{{ sample_count }} Items</span>
          </div>
          
          <DataTable
            :columns="columns"
            :rows="bagRows"
            row-key="id"
            :searchable="true"
            :total="bagRows.length"
            @export="onExport"
          >
            <template #cell-bag_code="{ row }">
              <span class="font-bold text-ink dark:text-slate-100">{{ row.bag_code }}</span>
            </template>
            <template #cell-barcode_id="{ row }">
              <span class="font-medium text-slate-600 dark:text-slate-300">{{ row.barcode_id }}</span>
            </template>
            <template #cell-sample_type="{ row }">
              <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-md text-[11px] font-bold border border-primary-500/20 bg-primary-500/10 text-primary-700 dark:text-primary-400">
                <i class="ri-test-tube-line text-xs"></i>
                {{ row.sample_type }}
              </span>
            </template>
            <template #cell-temperature_type="{ row }">
              <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-md text-[11px] font-bold border"
                :class="row.raw_temp_type === 'FROZEN' ? 'border-teal-500/20 bg-teal-500/10 text-teal-700 dark:text-teal-400' :
                        row.raw_temp_type === 'REFRIGERATE' ? 'border-blue-500/20 bg-blue-500/10 text-blue-700 dark:text-blue-400' :
                        'border-amber-500/20 bg-amber-500/10 text-amber-700 dark:text-amber-400'"
              >
                <i class="ri-snowy-line text-xs" v-if="row.raw_temp_type === 'FROZEN'"></i>
                <i class="ri-fridge-line text-xs" v-else-if="row.raw_temp_type === 'REFRIGERATE'"></i>
                <i class="ri-sun-line text-xs" v-else></i>
                {{ row.temperature_type }}
              </span>
            </template>
            <template #cell-container="{ row }">
              <span class="text-slate-500 dark:text-slate-400 font-medium">{{ row.container }}</span>
            </template>
          </DataTable>
        </BaseCard>

        <!-- Temperature Graph -->
        <BaseCard class="p-5">
          <div class="flex flex-col md:flex-row justify-between items-start md:items-center gap-4 mb-6">
            <div>
              <h2 class="text-lg font-bold text-ink dark:text-white">Temperature Monitoring</h2>
              <p class="text-sm text-slate-500 font-medium mt-1">Real-time container metrics</p>
            </div>
            <div class="flex flex-wrap gap-4">
              <div class="flex items-center gap-2">
                <div class="w-4 h-1 rounded-full bg-red-500"></div>
                <span class="text-xs font-bold text-slate-500 tracking-wider uppercase">Refrigeration</span>
              </div>
              <div class="flex items-center gap-2">
                <div class="w-4 h-1 rounded-full bg-blue-500"></div>
                <span class="text-xs font-bold text-slate-500 tracking-wider uppercase">Freezing</span>
              </div>
              <div class="flex items-center gap-2">
                <div class="w-4 h-1 rounded-full bg-green-500"></div>
                <span class="text-xs font-bold text-slate-500 tracking-wider uppercase">Room Temp</span>
              </div>
            </div>
          </div>
          
          <div class="w-full max-w-full overflow-hidden mt-4">
            <!-- Dynamic ApexChart -->
            <VueApexCharts
              v-if="labels && labels.length > 0"
              type="line"
              height="250"
              :options="chartOptions"
              :series="chartSeries"
            />
            <div v-else class="h-[250px] flex items-center justify-center text-slate-400 font-medium border border-dashed border-slate-200 dark:border-white/10 rounded-xl">
              No temperature data available
            </div>
          </div>
        </BaseCard>

      </div>
    </div>
  </div>

  <!-- Edit Times Modal -->
  <BaseModal v-model="showEditTimesModal" title="Edit Delivery Times">
    <form @submit.prevent="submitEditTimes" class="flex flex-col gap-4">
      <div>
        <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">Sample Out (Container Out)</label>
        <input 
          type="datetime-local" 
          v-model="editTimesForm.freezer_out_date"
          class="w-full h-10 px-3 bg-slate-50 dark:bg-white/5 border border-slate-200 dark:border-white/10 rounded-lg focus:ring-2 focus:ring-primary-500/30 focus:border-primary-500 outline-none transition text-sm"
        />
        <div v-if="editTimesForm.errors.freezer_out_date" class="text-red-500 text-xs mt-1">{{ editTimesForm.errors.freezer_out_date }}</div>
      </div>
      <div>
        <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">Sample Delivery (Close Date)</label>
        <input 
          type="datetime-local" 
          v-model="editTimesForm.close_date"
          class="w-full h-10 px-3 bg-slate-50 dark:bg-white/5 border border-slate-200 dark:border-white/10 rounded-lg focus:ring-2 focus:ring-primary-500/30 focus:border-primary-500 outline-none transition text-sm"
        />
        <div v-if="editTimesForm.errors.close_date" class="text-red-500 text-xs mt-1">{{ editTimesForm.errors.close_date }}</div>
      </div>
      
      <div class="flex justify-end gap-3 mt-4 pt-4 border-t border-slate-100 dark:border-white/5">
        <button type="button" @click="showEditTimesModal = false" class="px-5 py-2 text-sm font-semibold text-slate-600 dark:text-slate-300 hover:bg-slate-100 dark:hover:bg-white/5 rounded-lg transition-colors">
          Cancel
        </button>
        <button type="submit" :disabled="editTimesForm.processing" class="px-5 py-2 text-sm font-semibold text-white bg-primary-600 hover:bg-primary-700 rounded-lg transition-colors flex items-center gap-2">
          <i v-if="editTimesForm.processing" class="ri-loader-4-line animate-spin"></i>
          Save Changes
        </button>
      </div>
    </form>
  </BaseModal>

</template>

<style scoped>
@media print {
  @page {
    margin: 0; /* Hides browser URL header and footer */
  }
  * {
    -webkit-print-color-adjust: exact !important;
    print-color-adjust: exact !important;
  }
}
</style>
