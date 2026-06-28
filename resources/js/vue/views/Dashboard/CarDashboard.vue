<script setup>
/**
 * views/Dashboard/CarDashboard.vue — live fleet temperature monitoring (Inertia page).
 * Data from App\Http\Controllers\App\CarDashboardController@index, which reuses the
 * legacy Afaqy integration. Polls every 15s (replacing the classic <meta refresh>).
 * Graceful Afaqy-down state. Temperature thresholds preserved: ≥30 danger / ≥20 warning.
 */
import { onMounted, onUnmounted } from 'vue';
import { router } from '@inertiajs/vue3';
import Breadcrumb from '../../components/Breadcrumb.vue';
import BaseCard from '../../components/BaseCard.vue';
import EmptyState from '../../components/EmptyState.vue';

const props = defineProps({
  available: { type: Boolean, default: true },
  cars:      { type: Array,   default: () => [] },
});

// °C → color (matches Blade thresholds)
function tempVal(s) {
  const v = parseFloat(s?.last_val?.value_full);
  return Number.isFinite(v) ? v : null;
}
function barColor(v) {
  if (v == null) return 'bg-slate-300';
  if (v >= 30) return 'bg-danger';
  if (v >= 20) return 'bg-warning';
  return 'bg-success';
}
function barWidth(v) {
  if (v == null) return '0%';
  return Math.max(0, Math.min(100, v)) + '%'; // clamp 0–100 like the Blade bar
}
function profileRows(p) {
  if (!p) return [];
  return [
    ['Vehicle Type', p.vehicle_type],
    ['Plate Number', p.plate_number],
    ['Max Capacity', p.max_capacity],
    ['Seats', p.seats],
  ].filter(([, v]) => v !== undefined && v !== null && v !== '');
}

/* 15s polling (partial reload), cleared on unmount so no leaked Afaqy spam */
let timer = null;
onMounted(() => { timer = setInterval(() => router.reload({ only: ['available', 'cars'] }), 15000); });
onUnmounted(() => { if (timer) clearInterval(timer); });
</script>

<template>
  <div>
    <Breadcrumb title="Car Dashboard" :trail="[{ label: 'Dashboards' }, { label: 'Car Dashboard' }]">
      <template #actions>
        <span class="inline-flex items-center gap-1.5 text-xs text-slate-400">
          <span class="w-2 h-2 rounded-full bg-success animate-pulse-ring"></span> Live · refreshes every 15s
        </span>
      </template>
    </Breadcrumb>

    <!-- vehicles grid -->
    <div v-if="available && cars.length" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-4">
      <BaseCard v-for="(car, idx) in cars" :key="car.id ?? idx" :title="car.name || 'Vehicle'" icon="ri-car-line">
        <p class="text-xs text-slate-500 mb-3">IMEI: <span class="font-mono text-ink dark:text-slate-200" style="direction:ltr">{{ car.i }}</span></p>

        <!-- profile -->
        <div v-if="profileRows(car.profile).length" class="space-y-1 mb-4">
          <div v-for="[k, v] in profileRows(car.profile)" :key="k" class="flex justify-between text-[13px]">
            <span class="text-slate-400">{{ k }}</span>
            <span class="text-ink dark:text-slate-200 font-medium">{{ v }}</span>
          </div>
        </div>

        <!-- temperature sensors -->
        <div v-if="car.sensors && car.sensors.length" class="space-y-3">
          <div v-for="(s, si) in car.sensors" :key="si">
            <div class="flex justify-between text-[13px] mb-1">
              <span class="text-slate-600 dark:text-slate-300 truncate">{{ s.n || 'Temperature' }}</span>
              <span class="font-semibold text-ink dark:text-slate-100" style="direction:ltr">{{ tempVal(s) != null ? tempVal(s) + ' °C' : '—' }}</span>
            </div>
            <div class="h-2 rounded-full bg-surface-muted dark:bg-white/10 overflow-hidden">
              <div class="h-full rounded-full transition-all duration-500" :class="barColor(tempVal(s))" :style="{ width: barWidth(tempVal(s)) }"></div>
            </div>
          </div>
        </div>
        <p v-else class="text-[13px] text-slate-400">No temperature sensors.</p>
      </BaseCard>
    </div>

    <!-- Afaqy down / no vehicles -->
    <div v-else class="mtc-card max-w-xl mx-auto mt-10">
      <EmptyState
        v-if="!available"
        icon="ri-cloud-off-line"
        title="Afaqy service is not available now"
        message="Live vehicle telemetry can't be reached right now. It will retry automatically every 15 seconds."
      />
      <EmptyState
        v-else
        icon="ri-car-line"
        title="No vehicles"
        message="No Afaqy-enabled vehicles found for your account."
      />
    </div>
  </div>
</template>
