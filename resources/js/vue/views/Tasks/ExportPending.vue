<script setup>
import { ref, onMounted, onUnmounted } from 'vue';
import { usePage, router } from '@inertiajs/vue3';
import BaseCard from '../../components/BaseCard.vue';
import BaseButton from '../../components/BaseButton.vue';

const props = defineProps({
  token: { type: String, required: true },
});

const state = ref('pending'); // 'pending', 'ready', 'error'
const message = ref('Building your report file...');
const count = ref(0);
const downloadUrl = ref('');
const errorMessage = ref('');
const secondsElapsed = ref(0);
let timerInterval = null;
let pollInterval = null;

const checkStatus = async () => {
  try {
    const response = await fetch(`/admin/tasks/export-status/${props.token}?status=1`, {
      headers: { 'Accept': 'application/json' }
    });
    const data = await response.json();

    if (data.state === 'ready') {
      state.value = 'ready';
      count.value = data.count || 0;
      downloadUrl.value = data.download;
      clearInterval(timerInterval);
      clearInterval(pollInterval);
      window.location.href = data.download;
    } else if (data.state === 'error') {
      state.value = 'error';
      errorMessage.value = data.error || 'Unknown error';
      clearInterval(timerInterval);
      clearInterval(pollInterval);
    }
  } catch (error) {
    console.error('Polling error:', error);
  }
};

onMounted(() => {
  timerInterval = setInterval(() => {
    secondsElapsed.value++;
    message.value = `Still processing... (${secondsElapsed.value}s)`;
  }, 1000);

  pollInterval = setInterval(checkStatus, 3000);
  checkStatus(); // Initial check
});

onUnmounted(() => {
  clearInterval(timerInterval);
  clearInterval(pollInterval);
});

const goBack = () => {
  router.visit('/admin/tasks');
};
</script>

<template>
  <div class="flex justify-center mt-12">
    <div class="w-full max-w-2xl">
      <BaseCard title="Preparing your task report" class="text-center py-10">
        <!-- Pending State -->
        <div v-if="state === 'pending'">
          <div class="flex justify-center mb-6 mt-4">
            <svg class="animate-spin h-12 w-12 text-primary-500" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
              <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
              <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
            </svg>
          </div>
          <h5 class="text-xl font-bold mb-2 dark:text-white">Building your report file...</h5>
          <p class="text-slate-500 dark:text-slate-400 mb-1">{{ message }}</p>
          <p class="text-slate-400 dark:text-slate-500 text-sm">You can leave this page open — your download will start automatically.</p>
        </div>

        <!-- Ready State -->
        <div v-else-if="state === 'ready'">
          <div class="text-6xl mb-6 mt-4">✅</div>
          <h5 class="text-xl font-bold mb-2 dark:text-white">Your file is ready</h5>
          <p class="text-slate-500 dark:text-slate-400 mb-6">{{ count.toLocaleString() }} tasks included</p>
          <a :href="downloadUrl" class="inline-flex items-center justify-center gap-2 rounded-md font-semibold transition-colors focus:outline-none focus:ring-2 focus:ring-offset-2 disabled:opacity-50 disabled:pointer-events-none px-4 py-2 text-sm bg-primary-600 text-white hover:bg-primary-700 shadow-sm focus:ring-primary-600">
            <i class="ri-download-2-line"></i> Download report
          </a>
          <div class="mt-4">
            <BaseButton variant="white" @click="goBack">Back to tasks</BaseButton>
          </div>
        </div>

        <!-- Error State -->
        <div v-else>
          <div class="text-6xl mb-6 mt-4">⚠️</div>
          <h5 class="text-xl font-bold mb-2 text-red-500">Export failed</h5>
          <p class="text-slate-500 dark:text-slate-400 mb-6">{{ errorMessage }}</p>
          <BaseButton variant="white" @click="goBack">Back to tasks</BaseButton>
        </div>
      </BaseCard>
    </div>
  </div>
</template>
