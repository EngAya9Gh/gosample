/**
 * MTC / GoSample — DARK THEME COLORS (single source of truth)
 * ----------------------------------------------------------------
 * Dark mode is class-based: add `class="dark"` to <html> to activate.
 * These are every dark-theme color the UI uses. Two ways to apply:
 *   (A) Use the raw hex / rgba values directly.
 *   (B) Merge the `darkTokens` block below into tailwind.config.js
 *       theme.extend.colors, then reference e.g. `dark:bg-surface-dark-card`.
 */

export const DARK = {
  // ---- Surfaces (background layers, darkest -> lightest) ----
  canvas:        '#020617',              // app/page background        (slate-950)
  topbar:        'rgba(15,23,42,0.80)',  // top bar (+ blur)           (slate-900/80)
  card:          'rgba(30,41,59,0.60)',  // cards / panels             (slate-800/60)
  cardSolid:     '#1e293b',              // modals, dropdowns          (slate-800)
  sidebar:       '#03363d',              // sidebar (teal, both themes)(primary-900)
  fill:          'rgba(255,255,255,0.05)', // chips, table header, toggle-off
  fillHover:     'rgba(255,255,255,0.10)', // hover state of the above

  // ---- Borders & dividers ----
  border:        'rgba(255,255,255,0.05)', // default border / divider
  borderStrong:  'rgba(255,255,255,0.10)', // inputs, modal edge

  // ---- Text ----
  textHeading:   '#f8fafc',  // headings / primary   (slate-50)
  textStrong:    '#f1f5f9',  // strong body          (slate-100)
  textBody:      '#e2e8f0',  // body                 (slate-200)
  textMuted:     '#94a3b8',  // secondary / labels   (slate-400)

  // ---- Brand teal in dark (use these, NOT primary-700, on dark bg) ----
  accentText:    '#6fb0b7',              // links, active nav, accents (primary-300)
  accentTint:    'rgba(13,148,136,0.15)',// icon chips, active pills    (primary-500/15)
  accentRing:    'rgba(13,148,136,0.40)',// active-nav inset ring

  // ---- Semantic (same hue both themes; bg = tint, text = solid) ----
  success: '#0ab39c',  successTint: 'rgba(10,179,156,0.12)',
  info:    '#299cdb',  infoTint:    'rgba(41,156,219,0.12)',
  warning: '#f7b84b',  warningTint: 'rgba(247,184,75,0.15)',
  danger:  '#BD6BA7',  dangerTint:  'rgba(189,107,167,0.12)', // brand mauve, not red
};

/**
 * Optional: named Tailwind tokens so dark mode references locked names
 * instead of raw slate utilities. Merge into tailwind.config.js:
 *   theme: { extend: { colors: { ...darkTokens } } }
 * Then: class="bg-surface dark:bg-surface-dark-card text-ink dark:text-ink-dark"
 */
export const darkTokens = {
  'surface-dark':        '#020617', // canvas
  'surface-dark-topbar': '#0f172a',
  'surface-dark-card':   '#1e293b',
  'ink-dark':            '#e2e8f0',
  'ink-dark-strong':     '#f8fafc',
  'ink-dark-muted':      '#94a3b8',
  'border-dark':         'rgba(255,255,255,0.08)',
  'accent-dark':         '#6fb0b7',
};

/**
 * Equivalent CSS custom properties (if you prefer variables over Tailwind):
 *
 * .dark {
 *   --bg:            #020617;
 *   --surface:       #1e293b;   --surface-topbar: rgba(15,23,42,.8);
 *   --sidebar:       #03363d;
 *   --fill:          rgba(255,255,255,.05);  --fill-hover: rgba(255,255,255,.10);
 *   --border:        rgba(255,255,255,.08);
 *   --text:          #e2e8f0;   --text-heading: #f8fafc;  --text-muted: #94a3b8;
 *   --accent:        #6fb0b7;   --accent-tint:  rgba(13,148,136,.15);
 * }
 */
