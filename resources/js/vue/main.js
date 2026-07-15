import { createApp, h } from 'vue';
import { createInertiaApp } from '@inertiajs/vue3';
import { createPinia } from 'pinia';
import AppShell from './layouts/AppShell.vue';
import i18n from './lib/i18n';
import { setPermissions } from './composables/usePermissions';
import 'remixicon/fonts/remixicon.css'; // ri-* icons used across the design components
import '../../css/tailwind.css';

// Inertia.js app (see frontend-migration-plans/01-foundation.md). Pages live under
// resources/js/vue/views/ and are resolved by their Inertia name (e.g. the server
// renders 'Tasks/TasksList' → ./views/Tasks/TasksList.vue). Every page is wrapped in
// the persistent AppShell layout (sidebar + topbar + footer).
const pages = import.meta.glob('./views/**/*.vue');

createInertiaApp({
    resolve: async (name) => {
        const importer = pages[`./views/${name}.vue`];
        if (!importer) throw new Error(`Inertia page not found: ${name}`);
        const page = (await importer()).default;
        page.layout = page.layout === undefined ? AppShell : page.layout;
        return page;
    },
    setup({ el, App, props, plugin }) {
        // Seed render-gating permissions from Inertia's shared props (HandleInertiaRequests).
        // Falls back to "all granted" if empty so the panel never self-locks; the backend
        // Gates remain the real boundary.
        const shared = props.initialPage?.props || {};
        const auth = shared.auth || {};
        if (Array.isArray(auth.permissions) && auth.permissions.length) {
            setPermissions(auth.permissions, auth.canDelete !== false);
        }
        createApp({ render: () => h(App, props) })
            .use(plugin)
            .use(createPinia())
            .use(i18n)
            .mount(el);
    },
    progress: { color: '#0d9488' },
});
