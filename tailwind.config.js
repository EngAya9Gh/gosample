/** @type {import('tailwindcss').Config} */
// MTC / GoSample — Tailwind config.
// Brand identity is LOCKED (see design handoff). Do not change the core hues.
// theme.extend below is kept in lockstep with vue-build/tailwind.config.js so the
// drop-in design components render exactly as delivered.
module.exports = {
    // Scope Tailwind to the Vue SPA only, so its preflight/reset never touches
    // the existing Velzon Bootstrap Blade pages during the migration.
    // (Deliberately NOT scanning *.blade.php platform-wide — that would inject
    //  Tailwind's reset into the Velzon pages.)
    content: [
        './resources/js/vue/**/*.{vue,js,ts}',
        './resources/views/spa.blade.php',
    ],
    // A few color classes are built at runtime (e.g. Analytics donut legend does
    // `text-…`.replace('text-','bg-')), so Tailwind's static scanner can't see
    // them. Safelist them here.
    safelist: [
        'bg-info',
        'bg-secondary',
        'bg-warning',
    ],
    darkMode: 'class', // dark theme toggled by AppShell via the `dark` class on <html>
    theme: {
        extend: {
            spacing: require('./tailwind.material.js').spacing,
            fontSize: require('./tailwind.material.js').fontSize,
            colors: {
                ...require('./tailwind.material.js').colors,
                // ---- Primary brand teal (#005D69) ----
                primary: {
                    DEFAULT: '#005D69',
                    50: '#e6f1f2',
                    100: '#c2dde0',
                    200: '#9ac6cb',
                    300: '#6fb0b7',
                    400: '#3f99a2',
                    500: '#0d9488', // interactive accent (filters, CTAs, focus rings)
                    600: '#0a7a76',
                    700: '#005D69', // brand core — headers, primary buttons, active nav
                    800: '#024c56',
                    900: '#03363d',
                },
                // ---- Semantic ----
                success: '#0ab39c',
                info: '#299cdb',
                warning: '#f7b84b',
                danger: '#BD6BA7', // intentional mauve/magenta — NOT red
                secondary: '#3577f1',
                // ---- Operational status (task/sample pills) ----
                status: {
                    new: '#3b82f6',
                    collected: '#0ea5e9',
                    container: '#f59e0b', // in/out container
                    closed: '#22c55e',
                    none: '#94a3b8',
                    lost: '#dc2626',
                },
                // ---- Surfaces & ink ----
                surface: {
                    DEFAULT: '#ffffff',
                    muted: '#f3f6f9',
                    canvas: '#f3f3f9', // app background
                    // Dark-mode surfaces — teal-tinted near-black (matches the
                    // delivered reference theme; deliberately NOT Tailwind's
                    // blue `slate` scale). Used only behind the `dark` class.
                    dark: '#0A0F0E',         // app canvas + topbar
                    'dark-card': '#101A19',  // cards, panels, tables
                    'dark-solid': '#142220', // modals, dropdowns, toasts (raised)
                },
                ink: '#212529', // body text
            },
            fontFamily: {
                ...require('./tailwind.material.js').fontFamily,
                // Readex Pro carries Arabic glyphs Poppins lacks (RTL fallback)
                sans: ['Poppins', 'Readex Pro', 'system-ui', 'sans-serif'],
                serif: ['"Playfair Display"', 'Readex Pro', 'Georgia', 'serif'],
            },
            boxShadow: {
                card: '0 1px 3px rgba(16,24,40,.06), 0 1px 2px rgba(16,24,40,.04)',
                'card-hover': '0 12px 28px -8px rgba(0,93,105,.18), 0 4px 10px -4px rgba(16,24,40,.08)',
            },
            borderRadius: {
                xl: '0.85rem',
                '2xl': '1.15rem',
            },
            keyframes: {
                'fade-in-up': {
                    '0%': { opacity: '0', transform: 'translateY(10px)' },
                    '100%': { opacity: '1', transform: 'translateY(0)' },
                },
                'pulse-ring': {
                    '0%': { boxShadow: '0 0 0 0 rgba(13,148,136,.45)' },
                    '70%': { boxShadow: '0 0 0 10px rgba(13,148,136,0)' },
                    '100%': { boxShadow: '0 0 0 0 rgba(13,148,136,0)' },
                },
                'slide-in-end': {
                    '0%': { opacity: '0', transform: 'translateX(24px)' },
                    '100%': { opacity: '1', transform: 'translateX(0)' },
                },
                shimmer: {
                    '100%': { transform: 'translateX(100%)' },
                },
            },
            animation: {
                'fade-in-up': 'fade-in-up .5s cubic-bezier(.16,1,.3,1) both',
                'pulse-ring': 'pulse-ring 1.8s ease-out infinite',
                'slide-in-end': 'slide-in-end .35s cubic-bezier(.16,1,.3,1) both',
            },
        },
    },
    plugins: [
        require('@tailwindcss/forms'),
    ],
};
