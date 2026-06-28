/**
 * MTC / GoSample — Tailwind theme tokens (LOCKED brand palette)
 * Drop into the existing project's tailwind.config.js (merge `theme.extend`).
 * Every component in this delivery references ONLY these tokens — no inline hex.
 */
module.exports = {
  darkMode: 'class',
  content: [
    './resources/**/*.blade.php',
    './resources/**/*.vue',
    './resources/**/*.js',
  ],
  theme: {
    extend: {
      colors: {
        // ---- Primary brand teal (#005D69) ----
        primary: {
          50:  '#e6f1f2',
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
        success:   '#0ab39c',
        info:      '#299cdb',
        warning:   '#f7b84b',
        danger:    '#BD6BA7', // intentional mauve/magenta — NOT red
        secondary: '#3577f1',
        // ---- Operational status (task/sample pills) ----
        status: {
          new:       '#3b82f6',
          collected: '#0ea5e9',
          container: '#f59e0b', // in/out container
          closed:    '#22c55e',
          none:      '#94a3b8',
          lost:      '#dc2626',
        },
        // ---- Surfaces & ink ----
        surface: {
          DEFAULT: '#ffffff',
          muted:   '#f3f6f9',
          canvas:  '#f3f3f9',
        },
        ink: '#212529',
      },
      fontFamily: {
        sans:  ['Poppins', 'Readex Pro', 'system-ui', 'sans-serif'],
        serif: ['"Playfair Display"', 'Readex Pro', 'serif'],
      },
      boxShadow: {
        card:        '0 1px 3px rgba(16,24,40,.06), 0 1px 2px rgba(16,24,40,.04)',
        'card-hover':'0 12px 28px -8px rgba(0,93,105,.18), 0 4px 10px -4px rgba(16,24,40,.08)',
      },
      borderRadius: {
        xl:  '0.85rem',
        '2xl': '1.15rem',
      },
      keyframes: {
        'fade-in-up': {
          '0%':   { opacity: '0', transform: 'translateY(10px)' },
          '100%': { opacity: '1', transform: 'translateY(0)' },
        },
        'pulse-ring': {
          '0%':   { boxShadow: '0 0 0 0 rgba(13,148,136,.45)' },
          '70%':  { boxShadow: '0 0 0 10px rgba(13,148,136,0)' },
          '100%': { boxShadow: '0 0 0 0 rgba(13,148,136,0)' },
        },
        'slide-in-end': {
          '0%':   { opacity: '0', transform: 'translateX(24px)' },
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
  plugins: [],
};
