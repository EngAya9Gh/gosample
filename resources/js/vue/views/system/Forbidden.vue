<script setup>
import { router } from '@inertiajs/vue3';
import BaseButton from '../../components/BaseButton.vue';
</script>

<template>
    <div class="min-h-[60vh] grid place-items-center text-center">
        <div>
            <div class="text-7xl font-bold text-danger/20">403</div>
            <h1 class="mt-2 text-xl font-semibold text-ink dark:text-slate-100">Access denied</h1>
            <p class="mt-1 text-sm text-slate-500">You don’t have permission to view this screen.</p>
            <div class="mt-5">
                <BaseButton variant="primary" icon="ri-home-4-line" @click="router.visit('/dashboard')">Back to dashboard</BaseButton>
            </div>
        </div>
    </div>
</template>
