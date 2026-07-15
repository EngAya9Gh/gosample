<script setup>
import { ref, onMounted, onUnmounted } from 'vue';
import { Head } from '@inertiajs/vue3';
import axios from 'axios';
import 'leaflet/dist/leaflet.css';
import L from 'leaflet';
import Breadcrumb from '../../components/Breadcrumb.vue';
import FormSelect from '../../components/FormSelect.vue';
import BaseModal from '../../components/BaseModal.vue';
import StatusBadge from '../../components/StatusBadge.vue';
import TabGroup from '../../components/TabGroup.vue';

const props = defineProps({
  drivers: { type: Array, default: () => [] },
  plateNumbers: { type: Array, default: () => [] }
});

const form = ref({
  driver_id: '',
  imei: '',
  plate_number: ''
});

const mapContainer = ref(null);
let map = null;
let markersLayer = null;

const showDetailsModal = ref(false);
const selectedDriver = ref(null);
const activeTab = ref('details');

function openDriverDetails(loc) {
  selectedDriver.value = loc;
  activeTab.value = 'details';
  showDetailsModal.value = true;
}

onMounted(() => {
  initMap();
  fetchLocations();
});

onUnmounted(() => {
  if (map) {
    map.remove();
  }
});

function initMap() {
  map = L.map(mapContainer.value).setView([24.7136, 46.6753], 6); // Default to Riyadh
  
  L.tileLayer('https://{s}.basemaps.cartocdn.com/rastertiles/voyager/{z}/{x}/{y}{r}.png', {
    attribution: '&copy; OpenStreetMap contributors &copy; CARTO',
    subdomains: 'abcd',
    maxZoom: 19
  }).addTo(map);

  markersLayer = L.layerGroup().addTo(map);

  // Fix Leaflet container size bug on mount
  setTimeout(() => {
    map.invalidateSize();
  }, 300);
}

async function fetchLocations() {
  try {
    const response = await axios.post('/map/filter', form.value);
    const locations = response.data;
    updateMap(locations);
  } catch (error) {
    console.error('Error fetching map data:', error);
  }
}

function updateMap(locations) {
  markersLayer.clearLayers();
  
  if (!locations || locations.length === 0) return;

  const bounds = L.latLngBounds();

  locations.forEach(loc => {
    if (!loc.lat || !loc.lng) return;

    let pinColor = '#94a3b8'; // Idle
    
    // Determine status color based on tasks
    const activeTasks = loc.driver_active_tasks || [];
    const delayedTasks = loc.driver_active_delayed_tasks || [];
    
    if (delayedTasks.length > 0) {
      pinColor = '#dc2626'; // Delayed
    } else if (activeTasks.length > 0) {
      pinColor = '#0d9488'; // On route
    }

    const taskCount = activeTasks.length + delayedTasks.length;

    const html = `
      <div style="cursor:pointer; display:flex; flex-direction:column; align-items:center; z-index:5;">
        <div style="position:relative; width:46px; height:46px; border-radius:50% 50% 50% 0; background:${pinColor}; transform:rotate(-45deg); box-shadow:0 6px 16px rgba(0,0,0,.25); display:flex; align-items:center; justify-content:center; border:2.5px solid #fff;">
          <span style="transform:rotate(45deg); color:#fff; font-size:12px; font-weight:800;">${taskCount}</span>
        </div>
        <div style="margin-top:8px; background:var(--surface); border:1px solid var(--border); box-shadow:var(--shadow); border-radius:8px; padding:3px 9px; font-size:11px; font-weight:600; white-space:nowrap; color:var(--text); text-align:center;">
          ${loc.name}
        </div>
      </div>
    `;

    const icon = L.divIcon({
      html,
      className: 'custom-map-pin',
      iconSize: [80, 80],
      iconAnchor: [40, 60] // Adjust anchor to point to the bottom tip
    });

    const marker = L.marker([loc.lat, loc.lng], { icon }).addTo(markersLayer);
    bounds.extend([loc.lat, loc.lng]);

    // Open modern modal on click instead of basic popup
    marker.on('click', () => openDriverDetails(loc));
  });

  if (locations.length > 0 && bounds.isValid()) {
    map.fitBounds(bounds, { padding: [50, 50] });
  }
}
</script>

<template>
  <Head title="Live Map" />

  <!-- The main AppShell container layout class: bg-surface-canvas min-h-screen text-ink -->
  <div class="flex-1 min-w-0 p-4 md:p-6 lg:p-8 bg-surface-canvas dark:bg-surface-dark text-ink dark:text-slate-100 min-h-screen flex flex-col">
    <Breadcrumb title="Live Map" />

    <div class="mt-6 flex-1 flex flex-col">
      <!-- Filter Bar (Matches MTC Design) -->
      <div class="bg-surface dark:bg-surface-dark-card border border-slate-100 dark:border-white/5 rounded-[16px] p-4 mb-4 flex items-center gap-3 flex-wrap">
        
        <!-- Driver Select -->
        <div class="flex-1 min-w-[200px]">
          <FormSelect
            v-model="form.driver_id"
            :options="[{ value: '', label: 'All drivers' }, ...drivers.map(d => ({ value: d.id, label: d.name }))]"
            placeholder="All drivers"
            :searchable="true"
          />
        </div>

        <!-- IMEI Input -->
        <div class="flex-1 min-w-[160px] h-[42px] bg-surface-muted dark:bg-white/5 border border-slate-200 dark:border-white/10 rounded-[11px] flex items-center px-3 gap-2 text-[13px] text-slate-600 dark:text-slate-300 focus-within:ring-2 focus-within:ring-primary-500/30 transition-all">
          <i class="ri-cpu-line text-primary-500"></i>
          <input type="text" v-model="form.imei" placeholder="IMEI" @keyup.enter="fetchLocations" class="w-full bg-transparent border-none focus:ring-0 p-0 outline-none" />
        </div>

        <!-- Plate Number Input -->
        <div class="flex-1 min-w-[160px] h-[42px] bg-surface-muted dark:bg-white/5 border border-slate-200 dark:border-white/10 rounded-[11px] flex items-center px-3 gap-2 text-[13px] text-slate-600 dark:text-slate-300 relative focus-within:ring-2 focus-within:ring-primary-500/30 transition-all">
          <i class="ri-car-line text-primary-500"></i>
          <input type="text" v-model="form.plate_number" placeholder="Plate number" list="plate-options" @keyup.enter="fetchLocations" class="w-full bg-transparent border-none focus:ring-0 p-0 outline-none" />
          <datalist id="plate-options">
            <option v-for="plate in plateNumbers" :key="plate" :value="plate"></option>
          </datalist>
        </div>

        <!-- Search Button -->
        <button @click="fetchLocations" class="flex items-center gap-2 h-[42px] px-[18px] border-none rounded-[11px] bg-primary-700 hover:bg-primary-800 text-white font-semibold text-[12.5px] cursor-pointer transition-all active:scale-95">
          <i class="ri-search-line"></i> Search
        </button>
      </div>

      <!-- Map Container -->
      <div class="relative flex-1 min-h-[540px] rounded-[18px] overflow-hidden border border-slate-200 dark:border-white/10 shadow-sm z-0">
        <div ref="mapContainer" class="absolute inset-0"></div>
        
        <!-- Legend (Matches MTC Design) -->
        <div class="absolute bottom-4 right-4 bg-surface dark:bg-surface-dark-card border border-slate-200 dark:border-white/10 rounded-[12px] shadow-lg p-3 flex flex-col gap-2 z-[400]">
          <div class="text-[11px] font-bold text-slate-400 uppercase tracking-wider mb-1">Driver status</div>
          <div class="flex items-center gap-2 text-[12px] text-slate-600 dark:text-slate-300">
            <span class="w-[11px] h-[11px] rounded-full bg-[#0d9488]"></span> On route
          </div>
          <div class="flex items-center gap-2 text-[12px] text-slate-600 dark:text-slate-300">
            <span class="w-[11px] h-[11px] rounded-full bg-[#dc2626]"></span> Delayed
          </div>
          <div class="flex items-center gap-2 text-[12px] text-slate-600 dark:text-slate-300">
            <span class="w-[11px] h-[11px] rounded-full bg-[#94a3b8]"></span> Idle
          </div>
        </div>
      </div>
    </div>

    <!-- Modern Task Details Modal -->
    <BaseModal v-model="showDetailsModal" title="Driver Information" icon="ri-user-location-line" size="lg">
      <div v-if="selectedDriver" class="flex flex-col gap-4">
        
        <!-- Tabs for Details / Tasks / Tracking -->
        <TabGroup
          v-model:active="activeTab"
          :tabs="[
            { key: 'details', label: 'Details', icon: 'ri-information-line' },
            { key: 'tasks', label: 'Active Tasks', icon: 'ri-file-list-3-line', badge: [...(selectedDriver.driver_active_tasks||[]), ...(selectedDriver.driver_active_delayed_tasks||[])].length },
            { key: 'tracking', label: 'Car Tracking', icon: 'ri-map-pin-line' }
          ]"
          variant="pills"
        />

        <!-- Tab 1: Driver & Vehicle Info -->
        <div v-if="activeTab === 'details'" class="grid grid-cols-1 md:grid-cols-2 gap-4 mt-2">
          <div class="p-4 bg-surface-muted dark:bg-white/5 rounded-xl border border-slate-100 dark:border-white/10 flex flex-col justify-center">
            <p class="text-[11px] text-slate-400 uppercase font-bold tracking-wider mb-1">Driver Information</p>
            <p class="font-bold text-ink dark:text-slate-100 text-lg">{{ selectedDriver.name }}</p>
            <p class="text-sm text-slate-500 mt-1 flex items-center gap-2"><i class="ri-phone-line text-primary-500"></i> {{ selectedDriver.mobile || '—' }}</p>
            <p class="text-sm text-slate-500 mt-1 flex items-center gap-2"><i class="ri-mail-line text-primary-500"></i> {{ selectedDriver.email || '—' }}</p>
          </div>
          <div class="p-4 bg-surface-muted dark:bg-white/5 rounded-xl border border-slate-100 dark:border-white/10 flex flex-col justify-center">
            <p class="text-[11px] text-slate-400 uppercase font-bold tracking-wider mb-1">Vehicle Details</p>
            <p class="font-bold text-ink dark:text-slate-100 text-lg">{{ selectedDriver.plate_number || 'No Vehicle' }}</p>
            <p class="text-sm text-slate-500 mt-1">Model: {{ selectedDriver.model || '—' }}</p>
            <p class="text-sm text-slate-500 mt-1">IMEI: <span class="font-mono bg-white/50 dark:bg-black/20 px-1 py-0.5 rounded">{{ selectedDriver.imei || '—' }}</span></p>
          </div>
        </div>

        <!-- Tab 2: Active Tasks -->
        <div v-if="activeTab === 'tasks'" class="mt-2">
          <div v-if="[...(selectedDriver.driver_active_tasks||[]), ...(selectedDriver.driver_active_delayed_tasks||[])].length > 0" class="overflow-x-auto border border-slate-200 dark:border-white/10 rounded-xl max-h-[400px]">
            <table class="w-full text-sm text-left">
              <thead class="bg-surface-muted dark:bg-white/5 text-slate-500 text-[11px] uppercase tracking-wider font-bold sticky top-0">
                <tr>
                  <th class="px-4 py-3">Task ID</th>
                  <th class="px-4 py-3">From</th>
                  <th class="px-4 py-3">To</th>
                  <th class="px-4 py-3">Status</th>
                </tr>
              </thead>
              <tbody class="divide-y divide-slate-100 dark:divide-white/5">
                <tr v-for="task in [...(selectedDriver.driver_active_tasks||[]), ...(selectedDriver.driver_active_delayed_tasks||[])]" :key="task.id" class="hover:bg-slate-50 dark:hover:bg-white/5 transition-colors">
                  <td class="px-4 py-3 font-semibold text-primary-600 dark:text-primary-400">#{{ task.id }}</td>
                  <td class="px-4 py-3 text-slate-700 dark:text-slate-300">{{ task.from?.name || '—' }}</td>
                  <td class="px-4 py-3 text-slate-700 dark:text-slate-300">{{ task.to?.name || '—' }}</td>
                  <td class="px-4 py-3"><StatusBadge :status="task.status" /></td>
                </tr>
              </tbody>
            </table>
          </div>
          <div v-else class="text-center py-8 text-slate-500 bg-surface-muted dark:bg-white/5 rounded-xl border border-dashed border-slate-200 dark:border-white/10 flex flex-col items-center gap-2">
            <i class="ri-check-double-line text-3xl text-slate-300 dark:text-slate-600"></i>
            <p>No active tasks currently assigned to this driver.</p>
          </div>
        </div>

        <!-- Tab 3: Car Tracking -->
        <div v-if="activeTab === 'tracking'" class="mt-2">
          <div v-if="selectedDriver.car?.car_tracking?.length > 0" class="overflow-x-auto border border-slate-200 dark:border-white/10 rounded-xl max-h-[400px]">
            <table class="w-full text-sm text-left whitespace-nowrap">
              <thead class="bg-surface-muted dark:bg-white/5 text-slate-500 text-[11px] uppercase tracking-wider font-bold sticky top-0 z-10">
                <tr>
                  <th class="px-4 py-3">ID</th>
                  <th class="px-4 py-3">Date</th>
                  <th class="px-4 py-3">Address</th>
                  <th class="px-4 py-3">Temp5</th>
                  <th class="px-4 py-3">Temp6</th>
                  <th class="px-4 py-3">Temp7</th>
                  <th class="px-4 py-3">Temp8</th>
                </tr>
              </thead>
              <tbody class="divide-y divide-slate-100 dark:divide-white/5">
                <!-- Limit to latest 30 points to avoid browser lag, sorted by newest -->
                <tr v-for="track in [...selectedDriver.car.car_tracking].reverse().slice(0, 30)" :key="track.id" class="hover:bg-slate-50 dark:hover:bg-white/5 transition-colors">
                  <td class="px-4 py-3 font-medium text-slate-700 dark:text-slate-300">#{{ track.id }}</td>
                  <td class="px-4 py-3 text-slate-500">{{ new Date(track.created_at).toLocaleString('en-US') }}</td>
                  <td class="px-4 py-3 text-slate-700 dark:text-slate-300 truncate max-w-[200px]" :title="track.address">{{ track.address || '—' }}</td>
                  <td class="px-4 py-3">{{ track.temp5 || '—' }}</td>
                  <td class="px-4 py-3">{{ track.temp6 || '—' }}</td>
                  <td class="px-4 py-3">{{ track.temp7 || '—' }}</td>
                  <td class="px-4 py-3">{{ track.temp8 || '—' }}</td>
                </tr>
              </tbody>
            </table>
          </div>
          <div v-else class="text-center py-8 text-slate-500 bg-surface-muted dark:bg-white/5 rounded-xl border border-dashed border-slate-200 dark:border-white/10 flex flex-col items-center gap-2">
            <i class="ri-map-pin-time-line text-3xl text-slate-300 dark:text-slate-600"></i>
            <p>No tracking history available for this vehicle.</p>
          </div>
        </div>

      </div>
    </BaseModal>

  </div>
</template>

<style>
/* Reset Leaflet custom pin background */
.custom-map-pin {
  background: transparent !important;
  border: none !important;
}

/* Ensure leaflet popups look good in dark mode */
.dark .leaflet-popup-content-wrapper, .dark .leaflet-popup-tip {
  background-color: #0f1c1e;
  color: #e8f0f0;
  border: 1px solid #1d2c2e;
}
</style>
