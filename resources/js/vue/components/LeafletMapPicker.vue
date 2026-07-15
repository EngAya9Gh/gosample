<script setup>
import { ref, watch, onMounted, onUnmounted } from 'vue';
import axios from 'axios';
import 'leaflet/dist/leaflet.css';
import L from 'leaflet';
import debounce from 'lodash/debounce';

// Fix Leaflet's default icon paths when using Vite/Webpack
delete L.Icon.Default.prototype._getIconUrl;
L.Icon.Default.mergeOptions({
  iconRetinaUrl: 'https://cdnjs.cloudflare.com/ajax/libs/leaflet/1.9.4/images/marker-icon-2x.png',
  iconUrl: 'https://cdnjs.cloudflare.com/ajax/libs/leaflet/1.9.4/images/marker-icon.png',
  shadowUrl: 'https://cdnjs.cloudflare.com/ajax/libs/leaflet/1.9.4/images/marker-shadow.png',
});

const props = defineProps({
  lat: { type: [Number, String], default: null },
  lng: { type: [Number, String], default: null },
  zoom: { type: Number, default: 12 },
  defaultLat: { type: Number, default: 24.7156901 }, // Riyadh
  defaultLng: { type: Number, default: 46.6439257 },
});

const emit = defineEmits(['update:lat', 'update:lng']);

const mapContainer = ref(null);
const searchQuery = ref('');
const searchResults = ref([]);
const isSearching = ref(false);

let map = null;
let marker = null;

const initMap = () => {
  const initialLat = props.lat ? parseFloat(props.lat) : props.defaultLat;
  const initialLng = props.lng ? parseFloat(props.lng) : props.defaultLng;
  const center = [initialLat, initialLng];

  map = L.map(mapContainer.value).setView(center, props.lat ? 15 : props.zoom);

  L.tileLayer('https://{s}.basemaps.cartocdn.com/rastertiles/voyager/{z}/{x}/{y}{r}.png', {
    attribution: '&copy; OpenStreetMap contributors &copy; CARTO',
    subdomains: 'abcd',
    maxZoom: 19
  }).addTo(map);

  if (props.lat && props.lng) {
    placeMarker(center);
  }

  map.on('click', (e) => {
    const lat = e.latlng.lat;
    const lng = e.latlng.lng;
    placeMarker([lat, lng]);
    emit('update:lat', String(lat));
    emit('update:lng', String(lng));
  });

  // Fix Leaflet sizing bug in modals
  setTimeout(() => {
    if (map) map.invalidateSize();
  }, 300);
};

const placeMarker = (latlng) => {
  if (marker) {
    marker.setLatLng(latlng);
  } else {
    marker = L.marker(latlng).addTo(map);
  }
};

onMounted(() => {
  initMap();
});

onUnmounted(() => {
  if (map) {
    map.remove();
  }
});

watch(() => [props.lat, props.lng], ([newLat, newLng]) => {
  if (map && newLat && newLng) {
    const lat = parseFloat(newLat);
    const lng = parseFloat(newLng);
    placeMarker([lat, lng]);
    map.panTo([lat, lng]);
  } else if (!newLat && !newLng && marker) {
    map.removeLayer(marker);
    marker = null;
  }
});

const doSearch = debounce(async () => {
  if (!searchQuery.value.trim()) {
    searchResults.value = [];
    return;
  }
  isSearching.value = true;
  try {
    const { data } = await axios.get(`https://nominatim.openstreetmap.org/search`, {
      params: {
        q: searchQuery.value,
        format: 'json',
        limit: 5,
      }
    });
    searchResults.value = data;
  } catch (err) {
    console.error('Nominatim search error:', err);
  } finally {
    isSearching.value = false;
  }
}, 500);

const selectResult = (result) => {
  const lat = parseFloat(result.lat);
  const lng = parseFloat(result.lon);
  
  placeMarker([lat, lng]);
  emit('update:lat', String(lat));
  emit('update:lng', String(lng));
  
  map.setView([lat, lng], 16);
  
  searchQuery.value = result.display_name;
  searchResults.value = [];
};
</script>

<template>
  <div class="relative w-full rounded-xl overflow-hidden border border-slate-200 dark:border-white/10 bg-surface dark:bg-surface-dark">
    
    <!-- Search Overlay -->
    <div class="absolute top-3 left-3 right-3 sm:left-1/2 sm:-translate-x-1/2 sm:w-[320px] z-[400]">
      <div class="relative">
        <i class="ri-search-line absolute top-1/2 -translate-y-1/2 left-3 text-slate-400"></i>
        <input 
          v-model="searchQuery"
          @input="doSearch"
          type="text" 
          placeholder="Search location..." 
          class="w-full h-10 pl-9 pr-4 rounded-lg bg-white dark:bg-slate-800 border border-slate-200 dark:border-white/10 shadow-sm focus:outline-none focus:ring-2 focus:ring-primary-500 text-[13px] text-ink dark:text-slate-200 placeholder:text-slate-400"
        >
        <i v-if="isSearching" class="ri-loader-4-line animate-spin absolute top-1/2 -translate-y-1/2 right-3 text-primary-500"></i>
      </div>
      
      <!-- Dropdown Results -->
      <ul v-if="searchResults.length" class="mt-1 bg-white dark:bg-slate-800 rounded-lg shadow-lg border border-slate-100 dark:border-white/10 overflow-hidden text-[13px] max-h-48 overflow-y-auto">
        <li 
          v-for="res in searchResults" 
          :key="res.place_id"
          @click="selectResult(res)"
          class="px-3 py-2 cursor-pointer hover:bg-slate-50 dark:hover:bg-white/5 border-b border-slate-50 dark:border-white/5 last:border-0 truncate"
          :title="res.display_name"
        >
          <i class="ri-map-pin-line text-slate-400 mr-1.5"></i>
          {{ res.display_name }}
        </li>
      </ul>
    </div>

    <!-- Map Container -->
    <div ref="mapContainer" class="w-full h-[320px] z-10"></div>

  </div>
</template>
