<script setup>
/**
 * Scan & Reconcile — merged workspace that unifies the two classic pages:
 *   /admin/tasks/scan     (batch scan: driver + location → confirm each sample)
 *   /admin/tasks/missing  (single-sample triage: details / mark lost / confirm)
 *
 * One context (driver + location) pulls the pending batch; scanning a barcode
 * confirms that row IN PLACE (it stays visible and flips to a green "Received"
 * state instead of vanishing). A barcode that isn't in the batch — or a click on
 * a recent-missing chip — opens an inline single-sample triage card.
 *
 * All actions delegate to App\SampleReconciliationController (web-auth session),
 * which forwards to the same SampleController logic the classic pages use.
 */
import { ref, computed, reactive, nextTick } from 'vue';
import { Head } from '@inertiajs/vue3';
import axios from 'axios';
import Breadcrumb from '../../components/Breadcrumb.vue';
import FormSelect from '../../components/FormSelect.vue';
import { useToast } from '../../composables/useToast';

const props = defineProps({
  drivers:              { type: Array, default: () => [] },
  locations:            { type: Array, default: () => [] },
  recentMissingSamples: { type: Array, default: () => [] },
  can:                  { type: Object, default: () => ({}) },
});

const { push } = useToast();

// ---- dropdown options ----
const driverOptions   = computed(() => props.drivers.map((d) => ({ value: String(d.id), label: d.name })));
const locationOptions = computed(() => props.locations.map((l) => ({ value: String(l.id), label: l.name })));

// ---- context / state ----
const driverId   = ref('');
const locationId = ref('');
const mode       = ref('reader'); // reader | manual
const loading    = ref(false);

const scanValue = ref('');
const scanInput = ref(null);
const scanHint  = reactive({ kind: 'reader', text: 'Reader mode active — scan a barcode to receive it.' });

// Batch = the still-pending samples only. As in the classic scan page, a
// correctly-scanned/confirmed sample is REMOVED from this list (the list shrinks
// to zero). The cumulative tallies below drive the stats/progress panel.
const samples  = ref([]);         // remaining pending rows: { barcode }
const expected = ref(0);          // batch size at load time
const received = ref(0);          // cumulative confirmed (scan / confirm / confirm-all)
const lost     = ref(0);          // cumulative marked-lost
const chips    = ref([]);         // recent missing / pending chips

// ---- single-sample triage card ----
const lookup = ref(null);         // { barcode, task, type, temp, status, by, at, method, details }

// ---- endpoints ----
const URL = {
  load:       '/app/admin/tasks/reconcile/load',
  check:      '/app/admin/tasks/reconcile/check',
  confirmAll: '/app/admin/tasks/reconcile/confirm-all',
  confirm:    '/app/admin/tasks/reconcile/confirm',
  details:    '/app/admin/tasks/reconcile/details',
  lost:       '/app/admin/tasks/reconcile/lost',
};

// ---- helpers ----
const CBC = { YES: 'received', NO: 'pending', LOST: 'lost' };
const STATUS_META = {
  received: { label: 'Received', icon: 'ri-checkbox-circle-fill', dot: 'bg-success',
    chip: 'bg-success/10 text-success', ring: 'bg-success/10 text-success' },
  pending:  { label: 'Pending', icon: 'ri-time-line', dot: 'bg-warning',
    chip: 'bg-warning/15 text-amber-600 dark:text-amber-400', ring: 'bg-warning/15 text-amber-600 dark:text-amber-400' },
  lost:     { label: 'Lost', icon: 'ri-close-circle-fill', dot: 'bg-status-lost',
    chip: 'bg-status-lost/10 text-status-lost', ring: 'bg-status-lost/10 text-status-lost' },
};
// Method-label text mirrors the classic missing page's "Action Method" mapping.
const METHOD_LABEL = {
  SCAN: 'Scanner / Batch Scan', MARK_CONFIRMED: 'Mark As Confirmed Button',
  CONFIRM_ALL: 'Confirm All Button', MARK_LOST: 'Mark As Lost Button',
};
const CBC_RAW = { received: 'YES', pending: 'NO', lost: 'LOST' };

function norm(c) { return (c || '').toString().trim().toLowerCase().replace(/[-\s]/g, ''); }

// Smart Matcher — ported verbatim from the classic scan page (scan.blade.php
// scanHandler) to tolerate RTL / hyphen / spacing quirks from barcode readers.
// Returns the matching batch row (whose .barcode is the exact DB string).
function smartFindBatch(code) {
  const normalizedCode = (code || '').trim().toLowerCase();
  const noHyphenCode = normalizedCode.replace(/-/g, '');
  const reversedCode = normalizedCode.split(' ').reverse().join(' ');
  const reversedHyphenCode = normalizedCode.split('-').reverse().join('-');
  const fullyReversedCode = normalizedCode.split('').reverse().join('');
  const noSpaceCode = normalizedCode.replace(/\s+/g, '');
  return samples.value.find((s) => {
    const normalizedBatch = (s.barcode || '').trim().toLowerCase();
    const noHyphenBatch = normalizedBatch.replace(/-/g, '');
    return (
      normalizedBatch === normalizedCode ||
      noHyphenBatch === noHyphenCode ||
      normalizedBatch === reversedCode ||
      normalizedBatch === reversedHyphenCode ||
      normalizedBatch === fullyReversedCode ||
      normalizedBatch.replace(/\s+/g, '') === noSpaceCode ||
      normalizedBatch.startsWith(normalizedCode + '-') ||
      normalizedCode.startsWith(normalizedBatch + '-')
    );
  });
}
// Remove a sample from the pending batch by its (normalized) barcode.
function removeFromBatch(code) {
  const n = norm(code);
  const i = samples.value.findIndex((s) => norm(s.barcode) === n);
  if (i > -1) { samples.value.splice(i, 1); return true; }
  return false;
}
function setHint(kind, text) { scanHint.kind = kind; scanHint.text = text; }
function focusScan() { nextTick(() => scanInput.value?.focus()); }

const HINT_ICON = { reader: 'ri-barcode-line', manual: 'ri-keyboard-line', ok: 'ri-checkbox-circle-fill',
  error: 'ri-error-warning-fill', info: 'ri-information-line' };
const HINT_COLOR = { reader: 'text-slate-400', manual: 'text-slate-400', ok: 'text-success',
  error: 'text-status-lost', info: 'text-info' };

// Scan-way segmented toggle options (animated sliding pill in the template).
const MODES = [['reader', 'Reader', 'ri-barcode-line'], ['manual', 'Manual', 'ri-keyboard-line']];
function setMode(m) {
  mode.value = m;
  setHint(m, m === 'reader'
    ? 'Reader mode active — scan a barcode to receive it.'
    : 'Manual mode — type a barcode and press Enter.');
  focusScan();
}

// ---- stats ----
const stats = computed(() => {
  // `expected` is seeded from the DB on load; guard so it never reads below the
  // resolved count if loose (out-of-batch) actions push received/lost higher.
  const total = Math.max(expected.value, received.value + lost.value);
  const pending = Math.max(0, total - received.value - lost.value);
  const pct = total ? Math.min(100, Math.round((received.value / total) * 100)) : 0;
  return { total, received: received.value, pending, lost: lost.value, pct };
});

// ---- batch load ----
async function getSamples() {
  if (!driverId.value) { push({ type: 'error', title: 'Pick a driver', message: 'Choose a driver first.' }); return; }
  loading.value = true;
  lookup.value = null;
  try {
    const { data } = await axios.post(URL.load, {
      driver_id: driverId.value,
      location_id: locationId.value || null,
    });
    if (data.status) {
      const rows = Array.isArray(data.data) ? data.data : [];
      samples.value = rows.map((r) => ({ barcode: r.barcode_id }));
      // Seed the stat cards from the real DB breakdown for this driver+location.
      const s = data.stats || {};
      expected.value = s.expected ?? rows.length;
      received.value = s.received ?? 0;
      lost.value = s.lost ?? 0;
      if (rows.length) {
        push({ type: 'success', title: 'Batch loaded', message: `${rows.length} pending sample(s).` });
      } else {
        push({ type: 'info', title: 'No pending samples', message: 'Nothing to reconcile for this selection.' });
      }
      focusScan();
    } else {
      samples.value = []; expected.value = 0; received.value = 0; lost.value = 0;
      push({ type: 'error', title: 'Could not load', message: data.message || 'No samples available.' });
    }
  } catch (e) {
    push({ type: 'error', title: 'Request failed', message: e.response?.data?.message || 'Server error.' });
  } finally {
    loading.value = false;
  }
}

// ---- scan / lookup (one input, two jobs) ----
async function submitScan() {
  const code = scanValue.value.trim();
  if (!code) return;
  // If a batch is loaded and this barcode is in it → scan-confirm (Scan page).
  // Otherwise → single-sample triage lookup (Missing page); no batch required.
  const row = smartFindBatch(code);
  if (row) {
    try {
      const { data } = await axios.post(URL.check, { sample_id: row.barcode });
      if (data.status) {
        // Classic scan logic: confirmed sample is removed from the batch list.
        removeFromBatch(row.barcode);
        received.value++;
        setHint('ok', `${row.barcode} received & removed from batch.`);
      } else {
        setHint('error', data.message || 'Could not confirm this sample.');
        push({ type: 'error', title: 'Not confirmed', message: data.message || row.barcode });
      }
    } catch (e) {
      push({ type: 'error', title: 'Request failed', message: e.response?.data?.message || 'Server error.' });
    }
    scanValue.value = '';
    focusScan();
    return;
  }
  // not in a batch → single-sample triage (details / confirm / lost)
  setHint('info', `Looking up ${code}…`);
  await openLookup(code, true);
  scanValue.value = '';
}

function onScanKey(e) { if (e.key === 'Enter') { e.preventDefault(); submitScan(); } }

// ---- single-sample triage ----
async function openLookup(barcode, fromScan = false) {
  try {
    const { data } = await axios.post(URL.details, { sample: barcode });
    if (data.status && data.data) {
      const s = data.data;
      lookup.value = {
        barcode: s.barcode_id,
        task: s.task_id,
        type: s.sample_type || null,
        temp: s.temperature_type || null,
        status: CBC[s.confirmed_by_client] || 'pending',
        by: s.confirmed_by || null,
        at: s.updated_at || null,
        method: s.confirmation_method || null,
        details: false,
      };
      if (!fromScan) setHint('info', `Opened ${s.barcode_id} for triage below.`);
      nextTick(() => document.getElementById('triage-card')?.scrollIntoView({ block: 'nearest', behavior: 'smooth' }));
    } else {
      lookup.value = null;
      setHint('error', `Sample not found: ${barcode}.`);
      push({ type: 'error', title: 'Not found', message: `${barcode} isn't in the system.` });
    }
  } catch (e) {
    push({ type: 'error', title: 'Request failed', message: e.response?.data?.message || 'Server error.' });
  }
}

// Move a sample between stat buckets based on its previous status → new status.
// Runs for ANY sample (batch or loose), so triage/chip actions update the cards.
function applyTransition(prev, next) {
  if (prev === next) return;
  if (prev === 'received') received.value = Math.max(0, received.value - 1);
  if (prev === 'lost')     lost.value     = Math.max(0, lost.value - 1);
  if (next === 'received') received.value++;
  if (next === 'lost')     lost.value++;
}

async function lookupConfirm() {
  const l = lookup.value; if (!l) return;
  try {
    const { data } = await axios.post(URL.confirm, { samples: [l.barcode], method: 'MARK_CONFIRMED' });
    if (data.status) {
      applyTransition(l.status, 'received');
      l.status = 'received'; l.method = 'MARK_CONFIRMED';
      removeFromBatch(l.barcode);
      push({ type: 'success', title: 'Confirmed', message: l.barcode });
    } else { push({ type: 'error', title: 'Blocked', message: data.message || l.barcode }); }
  } catch (e) { push({ type: 'error', title: 'Request failed', message: e.response?.data?.message || 'Server error.' }); }
}

async function lookupLost() {
  const l = lookup.value; if (!l) return;
  if (l.status === 'received') { push({ type: 'error', title: 'Blocked', message: `${l.barcode} is already confirmed — can't mark lost.` }); return; }
  try {
    const { data } = await axios.post(URL.lost, { sample: l.barcode });
    if (data.status) {
      applyTransition(l.status, 'lost');
      l.status = 'lost'; l.method = 'MARK_LOST';
      removeFromBatch(l.barcode);
      push({ type: 'success', title: 'Marked lost', message: l.barcode });
    } else { push({ type: 'error', title: 'Blocked', message: data.message || l.barcode }); }
  } catch (e) { push({ type: 'error', title: 'Request failed', message: e.response?.data?.message || 'Server error.' }); }
}

function closeLookup() { lookup.value = null; }

// ---- confirm all ----
async function confirmAll() {
  if (!stats.value.pending) return;
  if (!locationId.value) { push({ type: 'error', title: 'Pick a location', message: 'Confirm All needs a drop-off location.' }); return; }
  // Same guard as the classic scan page.
  if (!window.confirm('Are you sure you want to confirm all samples in the batch?')) return;
  try {
    const { data } = await axios.post(URL.confirmAll, { driver_id: driverId.value, to_location: locationId.value });
    if (data.status) {
      // Confirms every still-pending sample for this driver+location.
      const n = stats.value.pending;
      received.value += n;
      samples.value = [];
      push({ type: 'success', title: 'Confirmed all', message: `${n} pending sample(s) received.` });
    } else { push({ type: 'error', title: 'Failed', message: data.message || 'Could not confirm all.' }); }
  } catch (e) { push({ type: 'error', title: 'Request failed', message: e.response?.data?.message || 'Server error.' }); }
}

// ---- recent chips ----
chips.value = props.recentMissingSamples.map((s) => ({
  barcode: s.barcode_id,
  lost: s.confirmed_by_client === 'LOST',
}));
// Clicking a chip fills the barcode input (classic missing-page behavior) AND
// opens the triage popup for it, so the Confirm / Lost / Details actions — which
// are always available for a single barcode on the missing page — are surfaced.
function chipClick(barcode) { scanValue.value = barcode; openLookup(barcode); }

focusScan();
</script>

<template>
  <div>
    <Head title="Scan & Reconcile" />
    <Breadcrumb title="Scan & Reconcile" :trail="[{ label: 'Tasks' }, { label: 'Scan & Reconcile' }]" />

    <div class="space-y-5">
      <!-- ===================== STEP 1 — CONTEXT ===================== -->
      <!-- NOTE: no overflow-hidden here — it would clip the FormSelect dropdown panels. -->
      <section class="bg-surface dark:bg-surface-dark-card rounded-2xl shadow-card border border-slate-100 dark:border-white/10">
        <div class="flex items-center gap-2.5 px-5 py-3.5 border-b border-slate-100 dark:border-white/10">
          <span class="grid place-items-center w-7 h-7 rounded-lg bg-primary-700 text-white text-xs font-bold">1</span>
          <h2 class="font-semibold text-ink dark:text-slate-100">Pickup context</h2>
          <span class="text-[12px] text-slate-400 dark:text-slate-500 hidden sm:inline">— choose where &amp; who, then pull the expected samples</span>
        </div>
        <div class="p-5">
          <div class="grid grid-cols-1 lg:grid-cols-[1fr_1fr_auto_auto] gap-4 items-end">
            <FormSelect v-model="locationId" label="To Location" :options="locationOptions"
              icon="ri-map-pin-2-line" placeholder="Select location…" />
            <FormSelect v-model="driverId" label="Driver" :options="driverOptions"
              icon="ri-steering-2-line" placeholder="Select driver…" />
            <div>
              <label class="block text-[13px] font-bold text-slate-800 dark:text-slate-200 mb-1.5">Scan way</label>
              <!-- Animated segmented toggle: the teal pill slides between options. -->
              <div class="relative grid grid-cols-2 w-[220px] h-11 p-1 rounded-xl bg-surface-muted dark:bg-white/5 select-none">
                <span aria-hidden="true"
                  class="absolute top-1 bottom-1 left-1 rounded-lg bg-primary-700 shadow-sm transition-transform duration-300 ease-[cubic-bezier(.16,1,.3,1)]"
                  style="width:calc(50% - 4px)"
                  :style="{ transform: mode === 'manual' ? 'translateX(100%)' : 'translateX(0)' }"></span>
                <button v-for="m in MODES" :key="m[0]" type="button" @click="setMode(m[0])"
                  class="relative z-10 inline-flex items-center justify-center gap-1.5 rounded-lg text-[13px] font-medium transition-colors duration-200"
                  :class="mode === m[0] ? 'text-white' : 'text-slate-500 dark:text-slate-400 hover:text-ink dark:hover:text-slate-200'">
                  <i :class="m[2]" class="transition-transform duration-200" :style="{ transform: mode === m[0] ? 'scale(1.05)' : 'scale(1)' }"></i>
                  <span>{{ m[1] }}</span>
                </button>
              </div>
            </div>
            <button @click="getSamples" :disabled="loading"
              class="group inline-flex items-center justify-center gap-2 h-11 px-5 rounded-xl bg-primary-700 text-white text-sm font-semibold shadow-card hover:bg-primary-800 transition-colors duration-200 whitespace-nowrap disabled:opacity-60 disabled:cursor-not-allowed">
              <i class="transition-transform duration-200 group-hover:translate-y-0.5" :class="loading ? 'ri-loader-4-line animate-spin' : 'ri-download-2-line'"></i>Get Samples
            </button>
          </div>
        </div>
      </section>

      <!-- ===================== STEP 2 — SCAN + STATS ===================== -->
      <section class="bg-surface dark:bg-surface-dark-card rounded-2xl shadow-card border border-slate-100 dark:border-white/10 overflow-hidden">
        <div class="flex items-center gap-2.5 px-5 py-3.5 border-b border-slate-100 dark:border-white/10">
          <span class="grid place-items-center w-7 h-7 rounded-lg bg-primary-700 text-white text-xs font-bold">2</span>
          <h2 class="font-semibold text-ink dark:text-slate-100">Scan to receive</h2>
          <span class="text-[12px] text-slate-400 dark:text-slate-500 hidden sm:inline">— each scan matches a sample &amp; marks it received</span>
        </div>
        <div class="p-5">
          <div class="grid grid-cols-1 xl:grid-cols-[1.4fr_1fr] gap-5">
            <!-- scan input + triage + chips -->
            <div>
              <div class="relative">
                <i :class="mode === 'manual' ? 'ri-keyboard-line' : 'ri-barcode-line'"
                  class="absolute top-1/2 -translate-y-1/2 left-4 text-primary-600 dark:text-primary-400 text-xl"></i>
                <input ref="scanInput" v-model="scanValue" @keydown="onScanKey" autofocus
                  placeholder="Scan or type sample barcode…"
                  class="w-full h-14 pl-12 pr-28 rounded-2xl border-2 border-slate-200 dark:border-white/10 bg-surface dark:bg-white/5 text-ink dark:text-slate-100 font-mono text-lg focus:ring-4 focus:ring-primary-500/15 focus:border-primary-500 focus:outline-none"
                  style="direction:ltr" />
                <button @click="submitScan"
                  class="group absolute top-1/2 -translate-y-1/2 right-2 inline-flex items-center gap-1.5 h-10 px-4 rounded-xl bg-primary-600 text-white text-sm font-medium hover:bg-primary-700 transition-colors duration-200">
                  <i class="ri-add-line transition-transform duration-300 group-hover:rotate-90"></i>Add
                </button>
              </div>
              <p class="flex items-center gap-1.5 text-[12px] mt-2 min-h-[20px]" :class="HINT_COLOR[scanHint.kind]">
                <i :class="HINT_ICON[scanHint.kind]"></i>{{ scanHint.text }}
              </p>

              <!-- single-sample triage card -->
              <div v-if="lookup" id="triage-card" class="mt-4 rounded-2xl border-2 border-primary-200 dark:border-primary-500/30 bg-primary-50/40 dark:bg-primary-500/[.06] p-4 animate-fade-in-up">
                <div class="flex items-center gap-2.5 mb-3">
                  <i class="ri-focus-3-line text-primary-600 dark:text-primary-400"></i>
                  <span class="font-semibold text-ink dark:text-slate-100 text-[13px]">Single-sample triage</span>
                  <span class="inline-flex items-center gap-1.5 px-2.5 h-6 rounded-full text-[11px] font-bold" :class="STATUS_META[lookup.status].chip">
                    <span class="w-1.5 h-1.5 rounded-full" :class="STATUS_META[lookup.status].dot"></span>{{ STATUS_META[lookup.status].label }}
                  </span>
                  <button @click="closeLookup" class="group ms-auto grid place-items-center w-7 h-7 rounded-lg text-slate-400 hover:bg-white dark:hover:bg-white/10 transition-colors duration-200"><i class="ri-close-line transition-transform duration-300 group-hover:rotate-90"></i></button>
                </div>
                <div class="flex items-center gap-3 mb-3">
                  <span class="grid place-items-center w-10 h-10 rounded-xl shrink-0" :class="STATUS_META[lookup.status].ring"><i class="ri-barcode-line text-xl"></i></span>
                  <div class="font-mono text-[16px] font-bold text-ink dark:text-slate-100" style="direction:ltr">{{ lookup.barcode }}</div>
                  <span class="text-[11px] text-slate-400 dark:text-slate-500 ms-auto" style="direction:ltr">Task #{{ lookup.task }}<template v-if="lookup.type"> · {{ lookup.type }}</template></span>
                </div>
                <div v-if="lookup.details" class="grid grid-cols-2 sm:grid-cols-3 gap-3 text-[12px] rounded-xl bg-white/70 dark:bg-white/5 border border-slate-100 dark:border-white/10 p-3 mb-3">
                  <div><div class="text-slate-400 dark:text-slate-500">Temperature Type</div><div class="font-medium text-ink dark:text-slate-200">{{ lookup.temp || '—' }}</div></div>
                  <div><div class="text-slate-400 dark:text-slate-500">Sample Type</div><div class="font-medium text-ink dark:text-slate-200">{{ lookup.type || '—' }}</div></div>
                  <div><div class="text-slate-400 dark:text-slate-500">Confirmed By</div><div class="font-medium text-ink dark:text-slate-200">{{ lookup.by || '—' }}</div></div>
                  <div><div class="text-slate-400 dark:text-slate-500">Confirmed By Client</div><div class="font-medium text-ink dark:text-slate-200">{{ CBC_RAW[lookup.status] }}</div></div>
                  <div><div class="text-slate-400 dark:text-slate-500">Action Date</div><div class="font-medium text-ink dark:text-slate-200" style="direction:ltr">{{ lookup.at ? new Date(lookup.at).toLocaleString('en-GB') : '—' }}</div></div>
                  <div v-if="can.check_receiving_details_advanced" class="col-span-2"><div class="text-slate-400 dark:text-slate-500">Action Method</div><div class="font-medium text-ink dark:text-slate-200">{{ lookup.method ? (METHOD_LABEL[lookup.method] || 'Unknown') : 'Legacy (Unknown)' }}</div></div>
                </div>
                <div class="grid grid-cols-3 gap-2">
                  <button v-if="can.mark_as_lost" @click="lookupLost"
                    class="group inline-flex items-center justify-center gap-1.5 h-10 rounded-xl bg-status-lost/10 text-status-lost text-[13px] font-semibold hover:bg-status-lost/20 transition-colors duration-200"><i class="ri-close-circle-line transition-transform duration-300 group-hover:rotate-90"></i>Mark lost</button>
                  <button @click="lookupConfirm"
                    class="group inline-flex items-center justify-center gap-1.5 h-10 rounded-xl bg-success/10 text-success text-[13px] font-semibold hover:bg-success/20 transition-colors duration-200"><i class="ri-checkbox-circle-line transition-transform duration-200 group-hover:scale-125"></i>Confirm</button>
                  <button @click="lookup.details = !lookup.details"
                    class="group inline-flex items-center justify-center gap-1.5 h-10 rounded-xl bg-info/10 text-info text-[13px] font-semibold hover:bg-info/20 transition-colors duration-200"><i class="ri-information-line transition-transform duration-200 group-hover:scale-125"></i>{{ lookup.details ? 'Hide' : 'Details' }}</button>
                </div>
              </div>

              <!-- recent missing / pending chips -->
              <div class="mt-4">
                <p class="flex items-center gap-1.5 text-[12px] font-medium text-slate-500 dark:text-slate-400 mb-2">
                  <i class="ri-history-line"></i>Recently missing / pending samples
                  <span class="text-slate-400 dark:text-slate-500 font-normal">— click to fill</span>
                </p>
                <div class="flex flex-wrap gap-2">
                  <button v-for="c in chips" :key="c.barcode" @click="chipClick(c.barcode)"
                    class="group inline-flex items-center gap-1.5 ps-2.5 pe-2 h-8 rounded-lg border text-[13px] font-mono transition-colors duration-200"
                    style="direction:ltr"
                    :class="c.lost
                      ? 'border-status-lost/30 bg-status-lost/5 text-status-lost hover:bg-status-lost/10'
                      : 'border-slate-200 dark:border-white/10 bg-surface dark:bg-white/5 text-slate-600 dark:text-slate-300 hover:border-primary-300 hover:text-primary-700 dark:hover:text-primary-300'">
                    <i class="ri-barcode-line transition-transform duration-200 group-hover:scale-125" :class="c.lost ? 'text-status-lost' : 'text-primary-500'"></i>{{ c.barcode }}
                    <span v-if="c.lost" class="text-[9px] font-bold bg-status-lost text-white px-1 rounded">LOST</span>
                    <span v-else class="text-[9px] font-bold bg-warning/20 text-amber-600 dark:text-amber-400 px-1 rounded">PEND</span>
                  </button>
                  <span v-if="!chips.length" class="text-[12px] text-slate-400 dark:text-slate-500">No pending or missing samples 🎉</span>
                </div>
              </div>
            </div>

            <!-- stats -->
            <div class="grid grid-cols-2 gap-3 content-start">
              <div class="rounded-2xl border border-slate-100 dark:border-white/10 bg-surface-muted/50 dark:bg-white/[.03] p-4">
                <div class="flex items-center gap-1.5 text-[11px] text-slate-400 dark:text-slate-500"><i class="ri-stack-line"></i>Expected</div>
                <div class="text-3xl font-bold text-ink dark:text-slate-100 tabular-nums mt-1">{{ stats.total }}</div>
              </div>
              <div class="rounded-2xl border border-success/20 bg-success/[.06] p-4">
                <div class="flex items-center gap-1.5 text-[11px] text-success"><i class="ri-checkbox-circle-line"></i>Received</div>
                <div class="text-3xl font-bold text-success tabular-nums mt-1">{{ stats.received }}</div>
              </div>
              <div class="rounded-2xl border border-warning/25 bg-warning/[.08] p-4">
                <div class="flex items-center gap-1.5 text-[11px] text-amber-600 dark:text-amber-400"><i class="ri-time-line"></i>Pending</div>
                <div class="text-3xl font-bold text-amber-600 dark:text-amber-400 tabular-nums mt-1">{{ stats.pending }}</div>
              </div>
              <div class="rounded-2xl border border-status-lost/20 bg-status-lost/[.05] p-4">
                <div class="flex items-center gap-1.5 text-[11px] text-status-lost"><i class="ri-close-circle-line"></i>Lost</div>
                <div class="text-3xl font-bold text-status-lost tabular-nums mt-1">{{ stats.lost }}</div>
              </div>
              <div class="col-span-2">
                <div class="flex items-center justify-between text-[11px] text-slate-400 dark:text-slate-500 mb-1"><span>Reconciliation progress</span><span>{{ stats.pct }}%</span></div>
                <div class="h-2.5 rounded-full bg-slate-100 dark:bg-white/10 overflow-hidden">
                  <div class="h-full rounded-full bg-gradient-to-r from-primary-400 to-primary-600 transition-all duration-500" :style="{ width: stats.pct + '%' }"></div>
                </div>
              </div>
            </div>
          </div>
        </div>
      </section>

      <!-- ===================== STEP 3 — BATCH LIST ===================== -->
      <section class="bg-surface dark:bg-surface-dark-card rounded-2xl shadow-card border border-slate-100 dark:border-white/10 overflow-hidden">
        <div class="flex flex-wrap items-center gap-3 px-5 py-3.5 text-white" style="background:linear-gradient(120deg,#005D69,#0d9488)">
          <i class="ri-stack-line text-lg"></i>
          <h2 class="font-semibold">Batch samples</h2>
          <span class="inline-grid place-items-center min-w-6 h-6 px-1.5 rounded-full bg-white/20 text-white text-xs font-bold">{{ samples.length }}</span>
          <span class="text-[12px] text-white/70">remaining</span>
          <div class="ms-auto flex items-center gap-2">
            <button v-if="can.confirm_all" @click="confirmAll" :disabled="!samples.length"
              class="group inline-flex items-center gap-1.5 h-9 px-3.5 rounded-xl bg-white text-primary-700 text-[13px] font-semibold hover:bg-white/90 transition-colors duration-200 disabled:opacity-50 disabled:cursor-not-allowed">
              <i class="ri-checkbox-multiple-line transition-transform duration-200 group-hover:scale-125 group-disabled:scale-100"></i>Confirm All
            </button>
          </div>
        </div>

        <div v-if="samples.length" class="divide-y divide-slate-100 dark:divide-white/5">
          <div v-for="s in samples" :key="s.barcode" @click="openLookup(s.barcode)"
            class="group flex items-center gap-3 px-5 py-3 cursor-pointer transition-colors duration-200 hover:bg-surface-muted/50 dark:hover:bg-white/[.03]">
            <span class="grid place-items-center w-9 h-9 rounded-xl shrink-0 transition-transform duration-200 group-hover:scale-105" :class="STATUS_META.pending.ring"><i :class="STATUS_META.pending.icon"></i></span>
            <div class="min-w-0 flex-1">
              <div class="font-mono text-[14px] font-semibold text-ink dark:text-slate-100" style="direction:ltr">{{ s.barcode }}</div>
              <div class="text-[11px] text-slate-400 dark:text-slate-500">Scan to receive, or click to triage</div>
            </div>
            <span class="inline-flex items-center gap-1.5 px-2.5 h-6 rounded-full text-[11px] font-bold shrink-0" :class="STATUS_META.pending.chip">
              <span class="w-1.5 h-1.5 rounded-full" :class="STATUS_META.pending.dot"></span>{{ STATUS_META.pending.label }}
            </span>
            <i class="ri-arrow-right-s-line rtl:rotate-180 text-slate-300 dark:text-slate-600 shrink-0 transition-transform duration-200 group-hover:translate-x-1"></i>
          </div>
        </div>

        <div v-else class="grid place-items-center py-20 text-center">
          <i class="ri-inbox-line text-5xl text-slate-200 dark:text-slate-700"></i>
          <p class="text-slate-400 dark:text-slate-500 mt-3 font-medium">No samples yet</p>
          <p class="text-slate-400 dark:text-slate-500 text-sm">Pick a location and driver, then click <span class="font-semibold text-primary-600 dark:text-primary-400">Get Samples</span>.</p>
        </div>
      </section>
    </div>
  </div>
</template>
