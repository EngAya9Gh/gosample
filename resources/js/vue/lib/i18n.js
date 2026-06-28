// Lightweight i18n bridge. Reads a flat key→string dictionary optionally injected
// at boot (window.__MTC_BOOT__.i18n) and falls back to the provided fallback or the
// key itself. Module screens reference label keys (e.g. 'cruds.zone.fields.name')
// so AR/EN both work once the PHP lang arrays are exported.
// See frontend-migration-plans/01-foundation.md §5.
import { reactive } from 'vue';

const boot = (typeof window !== 'undefined' && window.__MTC_BOOT__) || {};

export const i18n = reactive({
    locale: boot.locale || 'ar',
    messages: boot.i18n || {},
});

export function setLocale(locale) {
    i18n.locale = locale;
}

export function t(key, fallback) {
    return i18n.messages[key] ?? fallback ?? key;
}

// Vue plugin: exposes $t in templates.
export default {
    install(app) {
        app.config.globalProperties.$t = t;
        app.provide('t', t);
    },
};
