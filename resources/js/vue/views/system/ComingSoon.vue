<script setup>
// Placeholder for nav screens not yet migrated to Inertia. The sidebar stays fully
// navigable during the migration; each unbuilt screen shows this until its module
// plan (frontend-migration-plans/) is executed.
import { computed } from 'vue';
import { usePage } from '@inertiajs/vue3';
import EmptyState from '../../components/EmptyState.vue';
import BaseButton from '../../components/BaseButton.vue';

const page = usePage();
const path = computed(() => (page.url || '').split('?')[0].replace(/^\/app/, ''));
const classicHref = computed(() => (path.value.startsWith('/admin') ? path.value : null));
</script>

<template>
    <div class="mtc-card max-w-xl mx-auto mt-10">
        <EmptyState
            icon="ri-tools-line"
            title="This screen is being migrated"
            :message="`The new Vue + Tailwind version of “${path}” is on the roadmap. The existing page still works in the classic panel.`"
        >
            <a v-if="classicHref" :href="classicHref">
                <BaseButton variant="light" icon="ri-external-link-line">Open classic page</BaseButton>
            </a>
        </EmptyState>
    </div>
</template>
