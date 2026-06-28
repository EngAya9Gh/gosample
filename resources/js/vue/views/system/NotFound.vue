<script setup>
import { router } from '@inertiajs/vue3';
import BaseButton from '../../components/BaseButton.vue';
</script>

<template>
    <div class="min-h-[60vh] grid place-items-center text-center">
        <div>
            <div class="text-7xl font-bold text-primary-700/20 dark:text-primary-300/20">404</div>
            <h1 class="mt-2 text-xl font-semibold text-ink dark:text-slate-100">Page not found</h1>
            <p class="mt-1 text-sm text-slate-500">The page you’re looking for doesn’t exist in the app.</p>
            <div class="mt-5">
                <BaseButton variant="primary" icon="ri-home-4-line" @click="router.visit('/app/dashboard')">Back to dashboard</BaseButton>
            </div>
        </div>
    </div>
</template>
