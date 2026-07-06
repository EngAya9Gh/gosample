<script setup>
import { computed, ref } from 'vue';
import { usePage, router } from '@inertiajs/vue3';
import Breadcrumb from '../../components/Breadcrumb.vue';
import BaseAvatar from '../../components/BaseAvatar.vue';
import StatusBadge from '../../components/StatusBadge.vue';

const props = defineProps({
  car: {
    type: Object,
    required: true
  },
  mediaUrls: {
    type: Object,
    default: () => ({})
  }
});

const tabs = [
  { id: 'containers', name: 'Containers' },
  { id: 'history', name: 'Link History' },
  { id: 'tasks', name: 'Tasks' },
  { id: 'tracking', name: 'Tracking' },
  { id: 'photos', name: 'Delivery Photos' },
];
const activeTab = ref('containers');

const formatDate = (dateString) => {
  if (!dateString) return '—';
  return new Date(dateString).toLocaleString();
};

const canEdit = computed(() => usePage().props.auth?.can?.['car_edit']);

const goBack = () => {
  router.visit('/app/admin/cars');
};

const editCar = () => {
  router.visit(`/app/admin/cars/${props.car.id}/edit`);
};

</script>

<template>
  <div class="space-y-6 max-w-5xl mx-auto pb-12">
    <!-- Header -->
    <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4">
      <Breadcrumb title="Car Details" parent="Cars" />
      
      <div class="flex items-center gap-2">
        <button @click="goBack" class="px-4 py-2 bg-white border border-slate-200 text-slate-600 rounded-lg text-sm font-medium hover:bg-slate-50 transition dark:bg-transparent dark:border-white/10 dark:text-slate-300 dark:hover:bg-white/5">
          <i class="ri-arrow-left-line mr-1"></i> Back to List
        </button>
        <button v-if="canEdit" @click="editCar" class="px-4 py-2 bg-amber-500 text-white rounded-lg text-sm font-medium hover:bg-amber-600 shadow-sm transition">
          <i class="ri-pencil-line mr-1"></i> Edit Car
        </button>
      </div>
    </div>

    <!-- Main Card -->
    <div class="bg-surface dark:bg-surface-dark border dark:border-surface-dark-border rounded-xl shadow-sm overflow-hidden">
      <!-- Banner / Title -->
      <div class="p-6 border-b border-slate-100 dark:border-white/5 bg-slate-50/50 dark:bg-black/20 flex items-center justify-between">
        <div class="flex items-center gap-4">
          <div class="w-12 h-12 rounded-xl bg-primary-100 dark:bg-primary-500/20 text-primary-600 dark:text-primary-400 flex items-center justify-center text-2xl shrink-0">
            <i class="ri-car-fill"></i>
          </div>
          <div>
            <h2 class="text-xl font-bold text-slate-800 dark:text-slate-100">{{ props.car.model || 'Unknown Model' }} - {{ props.car.plate_number }}</h2>
            <p class="text-sm text-slate-500 dark:text-slate-400 flex items-center gap-2 mt-1">
              <i class="ri-fingerprint-line"></i> IMEI: {{ props.car.imei }}
            </p>
          </div>
        </div>
        
        <div>
          <span v-if="props.car.status == 1" class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-full text-xs font-bold tracking-wide bg-emerald-50 text-emerald-600 border border-emerald-200 shadow-sm dark:bg-emerald-500/10 dark:border-emerald-500/20 dark:text-emerald-400">
            <span class="relative flex h-2 w-2 shrink-0">
              <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-emerald-400 opacity-75"></span>
              <span class="relative inline-flex rounded-full h-2 w-2 bg-emerald-500"></span>
            </span>
            ENABLED
          </span>
          <span v-else-if="props.car.status == 2" class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-full text-xs font-bold tracking-wide bg-danger/5 text-danger border border-danger/20 shadow-sm dark:bg-danger/10 dark:border-danger/20">
            <i class="ri-forbid-2-line text-[14px]"></i>
            DISABLED
          </span>
        </div>
      </div>

      <div class="p-6">
        <h3 class="text-sm font-bold text-slate-800 dark:text-slate-200 uppercase tracking-wider mb-4 pb-2 border-b border-slate-100 dark:border-white/5">General Information</h3>
        
        <dl class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-6">
          <div class="space-y-1">
            <dt class="text-xs font-semibold text-slate-500 dark:text-slate-400 uppercase tracking-wider">Car ID</dt>
            <dd class="text-sm font-medium text-slate-800 dark:text-slate-200">#{{ props.car.id }}</dd>
          </div>
          
          <div class="space-y-1">
            <dt class="text-xs font-semibold text-slate-500 dark:text-slate-400 uppercase tracking-wider">Driver</dt>
            <dd class="text-sm font-medium text-slate-800 dark:text-slate-200">
              <div v-if="props.car.driver" class="flex items-center gap-2">
                <BaseAvatar :name="props.car.driver.name" :size="24" />
                <span>{{ props.car.driver.name }}</span>
              </div>
              <span v-else class="text-slate-400">No driver assigned</span>
            </dd>
          </div>

          <div class="space-y-1">
            <dt class="text-xs font-semibold text-slate-500 dark:text-slate-400 uppercase tracking-wider">Color</dt>
            <dd class="text-sm font-medium text-slate-800 dark:text-slate-200 flex items-center gap-2">
              <span v-if="props.car.color" class="w-3 h-3 rounded-full border border-slate-200 shadow-sm" :style="{ backgroundColor: props.car.color.toLowerCase() }"></span>
              {{ props.car.color || '—' }}
            </dd>
          </div>

          <div class="space-y-1">
            <dt class="text-xs font-semibold text-slate-500 dark:text-slate-400 uppercase tracking-wider">Contact Person</dt>
            <dd class="text-sm font-medium text-slate-800 dark:text-slate-200">{{ props.car.contact_person || '—' }}</dd>
          </div>
          
          <div class="space-y-1">
            <dt class="text-xs font-semibold text-slate-500 dark:text-slate-400 uppercase tracking-wider">Afaqi Integration</dt>
            <dd class="text-sm font-medium text-slate-800 dark:text-slate-200">
              <span v-if="props.car.afaqi == 1" class="text-primary-600 font-bold"><i class="ri-check-line"></i> Yes</span>
              <span v-else class="text-slate-400">No</span>
            </dd>
          </div>

          <div class="space-y-1 sm:col-span-2 lg:col-span-3">
            <dt class="text-xs font-semibold text-slate-500 dark:text-slate-400 uppercase tracking-wider">Description</dt>
            <dd class="text-sm font-medium text-slate-800 dark:text-slate-200 bg-slate-50 dark:bg-black/20 p-3 rounded-lg border border-slate-100 dark:border-white/5 mt-1 min-h-[60px]">
              {{ props.car.description || 'No description provided.' }}
            </dd>
          </div>
        </dl>
      </div>
    </div>

    <!-- Details Tabs -->
    <div class="bg-surface dark:bg-surface-dark border dark:border-surface-dark-border rounded-xl shadow-sm overflow-hidden">
      <div class="border-b border-slate-100 dark:border-white/5 px-6">
        <nav class="-mb-px flex space-x-6 overflow-x-auto" aria-label="Tabs">
          <button v-for="tab in tabs" :key="tab.id" @click="activeTab = tab.id"
            :class="[activeTab === tab.id ? 'border-primary-500 text-primary-600 dark:text-primary-400' : 'border-transparent text-slate-500 hover:text-slate-700 hover:border-slate-300 dark:text-slate-400 dark:hover:text-slate-300', 'whitespace-nowrap py-4 px-1 border-b-2 font-medium text-sm transition-colors']">
            {{ tab.name }}
          </button>
        </nav>
      </div>

      <div class="p-6">
        <!-- Containers Tab -->
        <div v-show="activeTab === 'containers'" class="space-y-4">
          <div class="flex justify-end">
             <a href="/admin/containers/create" class="inline-flex items-center gap-2 px-3 py-1.5 bg-primary-50 text-primary-600 rounded text-sm font-medium hover:bg-primary-100 transition-colors dark:bg-primary-500/10 dark:text-primary-400 dark:hover:bg-primary-500/20">
               <i class="ri-add-line"></i> Create Container
             </a>
          </div>
          <div class="overflow-x-auto rounded-lg border border-slate-200 dark:border-white/5">
            <table class="min-w-full divide-y divide-slate-100 dark:divide-white/5">
              <thead class="bg-slate-50/50 dark:bg-black/20">
                <tr>
                  <th scope="col" class="px-5 py-3 text-left text-[11px] font-bold text-slate-400 uppercase tracking-wider">ID</th>
                  <th scope="col" class="px-5 py-3 text-left text-[11px] font-bold text-slate-400 uppercase tracking-wider">Type</th>
                  <th scope="col" class="px-5 py-3 text-left text-[11px] font-bold text-slate-400 uppercase tracking-wider">Model</th>
                  <th scope="col" class="px-5 py-3 text-left text-[11px] font-bold text-slate-400 uppercase tracking-wider">Status</th>
                  <th scope="col" class="px-5 py-3 text-right text-[11px] font-bold text-slate-400 uppercase tracking-wider">Actions</th>
                </tr>
              </thead>
              <tbody class="divide-y divide-slate-100 dark:divide-white/5">
                <tr v-for="container in props.car.containers" :key="container.id" class="hover:bg-slate-50/80 dark:hover:bg-white/5 transition-colors">
                  <td class="px-5 py-3 whitespace-nowrap text-sm font-black text-primary-600 dark:text-primary-400">#{{ container.id }}</td>
                  <td class="px-5 py-3 whitespace-nowrap text-sm text-slate-700 dark:text-slate-300 font-medium">{{ container.type }}</td>
                  <td class="px-5 py-3 whitespace-nowrap text-sm text-slate-500 dark:text-slate-400">{{ container.model }}</td>
                  <td class="px-5 py-3 whitespace-nowrap text-sm text-slate-500 dark:text-slate-400">
                    <span v-if="container.status === 'ACTIVE'" class="inline-flex items-center px-2 py-0.5 rounded text-[11px] font-bold bg-success/10 text-success">Active</span>
                    <span v-else class="inline-flex items-center px-2 py-0.5 rounded text-[11px] font-bold bg-slate-100 text-slate-600 dark:bg-white/10 dark:text-slate-400">{{ container.status }}</span>
                  </td>
                  <td class="px-5 py-3 whitespace-nowrap text-right">
                    <a :href="`/admin/containers/${container.id}/edit`" class="w-8 h-8 inline-flex items-center justify-center rounded-lg hover:bg-amber-50 dark:hover:bg-amber-500/10 text-amber-600 transition-colors" title="Edit Container">
                      <i class="ri-pencil-line text-lg"></i>
                    </a>
                  </td>
                </tr>
                <tr v-if="!props.car.containers?.length">
                  <td colspan="5" class="px-5 py-8 text-center text-sm text-slate-500">No containers associated with this car.</td>
                </tr>
              </tbody>
            </table>
          </div>
        </div>

        <!-- Link History Tab -->
        <div v-show="activeTab === 'history'" class="space-y-4">
          <div class="overflow-x-auto rounded-lg border border-slate-200 dark:border-white/5">
            <table class="min-w-full divide-y divide-slate-100 dark:divide-white/5">
              <thead class="bg-slate-50/50 dark:bg-black/20">
                <tr>
                  <th scope="col" class="px-5 py-3 text-left text-[11px] font-bold text-slate-400 uppercase tracking-wider">Driver</th>
                  <th scope="col" class="px-5 py-3 text-left text-[11px] font-bold text-slate-400 uppercase tracking-wider">Action</th>
                  <th scope="col" class="px-5 py-3 text-left text-[11px] font-bold text-slate-400 uppercase tracking-wider">Date</th>
                </tr>
              </thead>
              <tbody class="divide-y divide-slate-100 dark:divide-white/5">
                <tr v-for="history in props.car.car_car_link_histories" :key="history.id" class="hover:bg-slate-50/80 dark:hover:bg-white/5 transition-colors">
                  <td class="px-5 py-3 whitespace-nowrap text-sm font-bold text-slate-800 dark:text-slate-200">
                    <div class="flex items-center gap-2">
                      <BaseAvatar :name="history.driver?.name || 'Unknown'" :size="28" />
                      {{ history.driver?.name || 'Unknown' }}
                    </div>
                  </td>
                  <td class="px-5 py-3 whitespace-nowrap text-sm">
                    <span v-if="history.action === 'linked'" class="text-emerald-600 bg-emerald-50 px-2 py-0.5 rounded text-[11px] font-bold dark:bg-emerald-500/10 dark:text-emerald-400">
                      <i class="ri-link-m mr-1"></i>Linked
                    </span>
                    <span v-else class="text-danger bg-danger/5 px-2 py-0.5 rounded text-[11px] font-bold dark:bg-danger/10 dark:text-danger-400">
                      <i class="ri-link-unlink-m mr-1"></i>Unlinked
                    </span>
                  </td>
                  <td class="px-5 py-3 whitespace-nowrap text-sm text-slate-500 dark:text-slate-400 font-medium">{{ formatDate(history.created_at) }}</td>
                </tr>
                <tr v-if="!props.car.car_car_link_histories?.length">
                  <td colspan="3" class="px-5 py-8 text-center text-sm text-slate-500">No link history found.</td>
                </tr>
              </tbody>
            </table>
          </div>
        </div>

        <!-- Tasks Tab -->
        <div v-show="activeTab === 'tasks'" class="space-y-4">
          <div class="overflow-x-auto rounded-lg border border-slate-200 dark:border-white/5">
            <table class="min-w-full divide-y divide-slate-100 dark:divide-white/5">
              <thead class="bg-slate-50/50 dark:bg-black/20">
                <tr>
                  <th scope="col" class="px-5 py-3 text-left text-[11px] font-bold text-slate-400 uppercase tracking-wider">ID</th>
                  <th scope="col" class="px-5 py-3 text-left text-[11px] font-bold text-slate-400 uppercase tracking-wider">Route</th>
                  <th scope="col" class="px-5 py-3 text-left text-[11px] font-bold text-slate-400 uppercase tracking-wider">Billing Client</th>
                  <th scope="col" class="px-5 py-3 text-left text-[11px] font-bold text-slate-400 uppercase tracking-wider">Driver</th>
                  <th scope="col" class="px-5 py-3 text-left text-[11px] font-bold text-slate-400 uppercase tracking-wider">Status</th>
                  <th scope="col" class="px-5 py-3 text-left text-[11px] font-bold text-slate-400 uppercase tracking-wider">Created</th>
                </tr>
              </thead>
              <tbody class="divide-y divide-slate-100 dark:divide-white/5">
                <tr v-for="task in props.car.car_tasks" :key="task.id" class="hover:bg-slate-50/80 dark:hover:bg-white/5 transition-colors">
                  <td class="px-5 py-3 whitespace-nowrap text-sm font-black text-info dark:text-info">
                    <a :href="`/admin/tasks/${task.id}`" class="hover:underline" target="_blank">#{{ task.id }}</a>
                  </td>
                  <td class="px-5 py-3 text-sm font-medium text-slate-700 dark:text-slate-300">
                    <span class="inline-flex items-center gap-1"><i class="ri-map-pin-fill text-red-500 text-[11px]"></i> {{ task.from?.name || '—' }}</span>
                    <i class="ri-arrow-right-line text-slate-400 text-[11px] mx-1"></i>
                    <span class="inline-flex items-center gap-1"><i class="ri-map-pin-fill text-green-500 text-[11px]"></i> {{ task.to?.name || '—' }}</span>
                  </td>
                  <td class="px-5 py-3 whitespace-nowrap text-sm text-slate-800 dark:text-slate-200 font-medium">
                    {{ task.client?.name || '—' }}
                  </td>
                  <td class="px-5 py-3 whitespace-nowrap text-sm text-slate-800 dark:text-slate-200">
                    <div class="flex items-center gap-2">
                      <BaseAvatar v-if="task.driver" :name="task.driver.name" :size="20" />
                      {{ task.driver?.name || '—' }}
                    </div>
                  </td>
                  <td class="px-5 py-3 whitespace-nowrap text-sm">
                    <StatusBadge :status="String(task.status || '')" />
                  </td>
                  <td class="px-5 py-3 whitespace-nowrap text-sm text-slate-500 dark:text-slate-400 font-medium">{{ formatDate(task.created_at) }}</td>
                </tr>
                <tr v-if="!props.car.car_tasks?.length">
                  <td colspan="6" class="px-5 py-8 text-center text-sm text-slate-500">No tasks assigned to this car.</td>
                </tr>
              </tbody>
            </table>
          </div>
        </div>

        <!-- Tracking Tab -->
        <div v-show="activeTab === 'tracking'" class="space-y-4">
          <div class="overflow-x-auto rounded-lg border border-slate-200 dark:border-white/5">
            <table class="min-w-full divide-y divide-slate-100 dark:divide-white/5">
              <thead class="bg-slate-50/50 dark:bg-black/20">
                <tr>
                  <th scope="col" class="px-5 py-3 text-left text-[11px] font-bold text-slate-400 uppercase tracking-wider">ID</th>
                  <th scope="col" class="px-5 py-3 text-left text-[11px] font-bold text-slate-400 uppercase tracking-wider">Location</th>
                  <th scope="col" class="px-5 py-3 text-left text-[11px] font-bold text-slate-400 uppercase tracking-wider">Temp 1 (5)</th>
                  <th scope="col" class="px-5 py-3 text-left text-[11px] font-bold text-slate-400 uppercase tracking-wider">Temp 2 (6)</th>
                  <th scope="col" class="px-5 py-3 text-left text-[11px] font-bold text-slate-400 uppercase tracking-wider">Temp 3 (7)</th>
                  <th scope="col" class="px-5 py-3 text-left text-[11px] font-bold text-slate-400 uppercase tracking-wider">Temp 4 (8)</th>
                  <th scope="col" class="px-5 py-3 text-left text-[11px] font-bold text-slate-400 uppercase tracking-wider">Date</th>
                </tr>
              </thead>
              <tbody class="divide-y divide-slate-100 dark:divide-white/5">
                <tr v-for="track in props.car.car_tracking" :key="track.id" class="hover:bg-slate-50/80 dark:hover:bg-white/5 transition-colors">
                  <td class="px-5 py-3 whitespace-nowrap text-sm font-black text-slate-900 dark:text-slate-100">#{{ track.id }}</td>
                  <td class="px-5 py-3 whitespace-nowrap text-sm">
                    <a :href="`https://www.google.com/maps/place/${track.lat},${track.lng}`" target="_blank" class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded text-[11px] font-bold bg-info/10 text-info hover:bg-info/20 transition-colors">
                      <i class="ri-map-pin-line"></i> View on Map
                    </a>
                  </td>
                  <td class="px-5 py-3 whitespace-nowrap text-sm text-slate-600 dark:text-slate-300">{{ track.temp5 || '—' }}</td>
                  <td class="px-5 py-3 whitespace-nowrap text-sm text-slate-600 dark:text-slate-300">{{ track.temp6 || '—' }}</td>
                  <td class="px-5 py-3 whitespace-nowrap text-sm text-slate-600 dark:text-slate-300">{{ track.temp7 || '—' }}</td>
                  <td class="px-5 py-3 whitespace-nowrap text-sm text-slate-600 dark:text-slate-300">{{ track.temp8 || '—' }}</td>
                  <td class="px-5 py-3 whitespace-nowrap text-sm text-slate-500 dark:text-slate-400 font-medium">{{ formatDate(track.created_at) }}</td>
                </tr>
                <tr v-if="!props.car.car_tracking?.length">
                  <td colspan="7" class="px-5 py-8 text-center text-sm text-slate-500">No tracking data available.</td>
                </tr>
              </tbody>
            </table>
          </div>
        </div>

        <!-- Delivery Photos Tab -->
        <div v-show="activeTab === 'photos'" class="space-y-4">
          <div v-if="Object.keys(props.mediaUrls).length === 0" class="py-12 flex flex-col items-center justify-center text-center">
             <div class="w-16 h-16 bg-slate-100 dark:bg-surface-dark-solid rounded-full flex items-center justify-center mb-4">
               <i class="ri-image-line text-2xl text-slate-400"></i>
             </div>
             <p class="text-sm text-slate-500 max-w-sm">No delivery photos or signatures found for this car.</p>
          </div>
          
          <div v-else class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 gap-6">
            <div v-for="(url, key) in props.mediaUrls" :key="key" class="flex flex-col items-center group">
              <a :href="url" target="_blank" class="block w-full aspect-square rounded-xl overflow-hidden border border-slate-200 dark:border-white/10 shadow-sm group-hover:ring-2 ring-primary-500/50 transition-all">
                <img :src="url" :alt="key" class="w-full h-full object-cover transform group-hover:scale-105 transition-transform duration-300">
              </a>
              <span class="mt-2 text-xs font-semibold text-slate-600 dark:text-slate-300 uppercase tracking-wide">
                {{ key.replace('_', ' ') }}
              </span>
            </div>
          </div>
        </div>
      </div>
    </div>
    
  </div>
</template>
