<script setup>
/**
 * DataTable — the single reusable table used across every list screen.
 *
 * Features: keyword search, column sort, pagination (10/25/50/100/1000),
 * row checkboxes + bulk actions, export (Copy/CSV/Excel/Print), sticky first
 * & last columns, horizontal scroll for wide tables, skeleton loading, empty
 * state, RTL-correct.
 *
 * Props:
 *  columns = [{ key, label, sortable?, align?, sticky?:'start'|'end', width?, mono?:bool }]
 *  rows    = array of objects keyed by column.key
 *  rowKey  = unique field (default 'id')
 *  loading, selectable, bulkActions=[{ label, icon, tone, event }], exportable, searchable
 *  serverSide (if true, emits 'query' on change instead of filtering locally)
 *
 * Slots: cell-<key> ({ row, value }) for custom cell rendering; row-actions ({ row }).
 */
import { ref, computed, watch, nextTick, onMounted, onBeforeUnmount, getCurrentInstance } from 'vue';
import { useToast } from '../composables/useToast';

const props = defineProps({
  title:      { type: String, default: '' },   // optional table heading (with record count)
  columns:    { type: Array, required: true },
  rows:       { type: Array, default: () => [] },
  rowKey:     { type: String, default: 'id' },
  loading:    { type: Boolean, default: false },
  selectable: { type: Boolean, default: true },
  bulkActions:{ type: Array, default: () => [{ label: 'Delete', icon: 'ri-delete-bin-line', tone: 'danger', event: 'bulk-delete' }] },
  exportable: { type: Boolean, default: true },
  searchable: { type: Boolean, default: true },
  serverSide: { type: Boolean, default: false },
  total:      { type: Number, default: null }, // server-side total
});
const emit = defineEmits(['query', 'bulk-delete', 'export']);

const q = ref('');
const sortKey = ref('');
const sortDir = ref('asc');
const page = ref(1);
const pageSize = ref(25);
const sel = ref(new Set());
const SIZES = [10, 25, 50, 100, 1000];

// Colored export buttons (soft tints, on-theme) with a per-icon hover animation.
const exportBtns = [
  { key: 'copy',  label: 'Copy',  icon: 'ri-file-copy-line',    cls: 'text-info bg-info/10 hover:bg-info/20',                                                anim: 'group-hover:scale-125 group-hover:rotate-6' },
  { key: 'csv',   label: 'CSV',   icon: 'ri-file-text-line',    cls: 'text-secondary bg-secondary/10 hover:bg-secondary/20',                                 anim: 'group-hover:scale-125' },
  { key: 'excel', label: 'Excel', icon: 'ri-file-excel-2-line', cls: 'text-green-600 dark:text-green-300 bg-green-50 dark:bg-green-500/10 hover:bg-green-100 dark:hover:bg-green-500/20', anim: 'group-hover:scale-125' },
  { key: 'print', label: 'Print', icon: 'ri-printer-line',      cls: 'text-primary-600 dark:text-primary-300 bg-primary-50 dark:bg-primary-500/10 hover:bg-primary-100 dark:hover:bg-primary-500/20', anim: 'group-hover:-translate-y-0.5 group-hover:scale-110' },
];

/* ---------- client-side derive (skipped when serverSide) ---------- */
const filtered = computed(() => {
  if (props.serverSide) return props.rows;
  let r = props.rows;
  if (q.value.trim()) {
    const needle = q.value.toLowerCase();
    r = r.filter((row) => props.columns.some((c) =>
      String(row[c.key] ?? '').toLowerCase().includes(needle)));
  }
  if (sortKey.value) {
    r = [...r].sort((a, b) => {
      const av = a[sortKey.value], bv = b[sortKey.value];
      const n = (typeof av === 'number' && typeof bv === 'number')
        ? av - bv : String(av ?? '').localeCompare(String(bv ?? ''));
      return sortDir.value === 'asc' ? n : -n;
    });
  }
  return r;
});

const totalRows = computed(() => props.serverSide ? (props.total ?? props.rows.length) : filtered.value.length);
const pageCount = computed(() => Math.max(1, Math.ceil(totalRows.value / pageSize.value)));
const paged = computed(() => {
  if (props.serverSide) return props.rows;
  const start = (page.value - 1) * pageSize.value;
  return filtered.value.slice(start, start + pageSize.value);
});
const rangeFrom = computed(() => totalRows.value === 0 ? 0 : (page.value - 1) * pageSize.value + 1);
const rangeTo = computed(() => Math.min(page.value * pageSize.value, totalRows.value));

/* ---------- rows-per-page menu ----------
 * Custom dropdown instead of a native <select>: the OS popup can't be themed and
 * its label sits off-centre in the control. Panel is teleported to <body> with
 * fixed coords (the table card is overflow-hidden, which would clip it) and
 * flips above the trigger when there's no room below. Mirrors FormSelect. */
const sizeOpen = ref(false);
const sizeActive = ref(0);
const sizeWrap = ref(null);
const sizeTrigger = ref(null);
const sizePanel = ref(null);
const sizeStyle = ref({});

function positionSizePanel() {
  const t = sizeTrigger.value;
  if (!t) return;
  const r = t.getBoundingClientRect();
  const vh = window.innerHeight;
  const panelH = sizePanel.value?.offsetHeight || 220;
  const spaceBelow = vh - r.bottom - 12;
  const openUp = spaceBelow < panelH && r.top > spaceBelow;
  sizeStyle.value = {
    left: `${r.left}px`,
    minWidth: `${Math.max(r.width, 96)}px`,
    ...(openUp ? { bottom: `${vh - r.top + 6}px` } : { top: `${r.bottom + 6}px` }),
  };
}
function onSizeReflow() { if (sizeOpen.value) positionSizePanel(); }

watch(sizeOpen, (v) => {
  if (!v) return;
  sizeActive.value = Math.max(0, SIZES.indexOf(pageSize.value));
  nextTick(positionSizePanel);
});

function pickSize(s) {
  pageSize.value = s;
  sizeOpen.value = false;
  sizeTrigger.value?.focus();
}
function onSizeKeydown(e) {
  if (!sizeOpen.value) {
    if (e.key === 'ArrowDown' || e.key === 'Enter' || e.key === ' ') { e.preventDefault(); sizeOpen.value = true; }
    return;
  }
  switch (e.key) {
    case 'ArrowDown': e.preventDefault(); sizeActive.value = Math.min(sizeActive.value + 1, SIZES.length - 1); break;
    case 'ArrowUp':   e.preventDefault(); sizeActive.value = Math.max(sizeActive.value - 1, 0); break;
    case 'Home':      e.preventDefault(); sizeActive.value = 0; break;
    case 'End':       e.preventDefault(); sizeActive.value = SIZES.length - 1; break;
    case 'Enter':     e.preventDefault(); pickSize(SIZES[sizeActive.value]); break;
    case 'Escape':    e.preventDefault(); sizeOpen.value = false; sizeTrigger.value?.focus(); break;
  }
}
function onSizeDocClick(e) {
  const inWrap  = sizeWrap.value && sizeWrap.value.contains(e.target);
  const inPanel = sizePanel.value && sizePanel.value.contains(e.target);
  if (!inWrap && !inPanel) sizeOpen.value = false;
}
onMounted(() => {
  document.addEventListener('click', onSizeDocClick);
  window.addEventListener('resize', onSizeReflow);
  window.addEventListener('scroll', onSizeReflow, true);
});
onBeforeUnmount(() => {
  document.removeEventListener('click', onSizeDocClick);
  window.removeEventListener('resize', onSizeReflow);
  window.removeEventListener('scroll', onSizeReflow, true);
});

/* ---------- sorting ---------- */
function toggleSort(c) {
  if (!c.sortable) return;
  if (sortKey.value === c.key) sortDir.value = sortDir.value === 'asc' ? 'desc' : 'asc';
  else { sortKey.value = c.key; sortDir.value = 'asc'; }
}

/* ---------- selection ---------- */
const pageKeys = computed(() => paged.value.map((r) => r[props.rowKey]));
const allOnPage = computed(() => pageKeys.value.length > 0 && pageKeys.value.every((k) => sel.value.has(k)));
function toggleAll() {
  const s = new Set(sel.value);
  if (allOnPage.value) pageKeys.value.forEach((k) => s.delete(k));
  else pageKeys.value.forEach((k) => s.add(k));
  sel.value = s;
}
function toggleRow(k) {
  const s = new Set(sel.value);
  s.has(k) ? s.delete(k) : s.add(k);
  sel.value = s;
}
function clearSel() { sel.value = new Set(); }

/* ---------- emit query upstream for server-side ---------- */
watch([q, sortKey, sortDir, page, pageSize], () => {
  if (props.serverSide)
    emit('query', { q: q.value, sortKey: sortKey.value, sortDir: sortDir.value, page: page.value, pageSize: pageSize.value });
});
watch([q, pageSize], () => { page.value = 1; });

function runBulk(ev) { emit(ev, [...sel.value]); clearSel(); }

/* ---------- export: built-in fallback ----------
 * Pages that listen for @export keep their custom handlers (formatted cells,
 * backend-generated files). Every other table gets a WORKING Copy/CSV/Excel/
 * Print out of the box, built from the visible columns + current page rows —
 * so the toolbar buttons are never dead. */
const { push } = useToast();
const inst = getCurrentInstance();

function onExportClick(kind) {
  if (inst?.vnode.props?.onExport) { emit('export', kind); return; }
  defaultExport(kind);
}

// Object cells (e.g. row.driver = { name }) print a sensible display value
// instead of "[object Object]".
function cellVal(row, col) {
  const v = row[col.key];
  if (v == null) return '';
  if (typeof v === 'object') return v.name ?? v.plate_number ?? v.label ?? v.title ?? v.id ?? '';
  return String(v);
}

function defaultExport(kind) {
  const cols = props.columns.filter((c) => c.label);
  const header = cols.map((c) => c.label);
  const body = paged.value.map((r) => cols.map((c) => cellVal(r, c)));
  if (!body.length) { push({ type: 'info', title: 'Nothing to export', message: 'No rows in the current view.' }); return; }
  const name = (props.title || 'export').toLowerCase().replace(/\s+/g, '-');

  if (kind === 'copy') {
    navigator.clipboard?.writeText([header.join('\t'), ...body.map((r) => r.join('\t'))].join('\n'));
    push({ type: 'success', title: 'Copied', message: `${body.length} rows copied to clipboard` });
  } else if (kind === 'csv') {
    const esc = (s) => `"${String(s).replace(/"/g, '""')}"`;
    const csv = [header.map(esc).join(','), ...body.map((r) => r.map(esc).join(','))].join('\n');
    const a = document.createElement('a');
    a.href = URL.createObjectURL(new Blob(['\uFEFF' + csv], { type: 'text/csv;charset=utf-8' }));
    a.download = `${name}.csv`;
    a.click();
    URL.revokeObjectURL(a.href);
  } else if (kind === 'excel') {
    import('xlsx').then((XLSX) => {
      const ws = XLSX.utils.aoa_to_sheet([header, ...body]);
      const wb = XLSX.utils.book_new();
      XLSX.utils.book_append_sheet(wb, ws, "Sheet1");
      XLSX.writeFile(wb, `${name}.xlsx`);
    }).catch(err => {
      push({ type: 'error', title: 'Export Failed', message: 'Failed to load Excel export library.' });
      console.error(err);
    });
  } else if (kind === 'print') {
    const w = window.open('', '_blank');
    const th = header.map((h) => `<th>${h}</th>`).join('');
    const tr = body.map((r) => `<tr>${r.map((c) => `<td>${c}</td>`).join('')}</tr>`).join('');
    w.document.write(`<html dir="${document.documentElement.dir}"><head><title>${props.title || 'Export'}</title><style>table{border-collapse:collapse;width:100%;font-family:Poppins,sans-serif;font-size:12px}th,td{border:1px solid #cbd5e1;padding:6px 8px;text-align:start}th{background:#005D69;color:#fff}</style></head><body><h3>${props.title || ''}</h3><table><thead><tr>${th}</tr></thead><tbody>${tr}</tbody></table></body></html>`);
    w.document.close(); w.focus(); w.print();
  }
}
// Sticky cells need an opaque base (so horizontally-scrolled content doesn't
// bleed through) that still follows the row's hover — hence group-hover. Matches
// the Analytics row hover exactly. group-hover only fires on body rows (the <tr>
// there is `group`); header th are inert.
/* Sticky cells must be OPAQUE (content scrolls under them), but translucent tints
   (white/5, surface-muted/50) can't be reused here — layered on top of the row's own
   tint they compose to a different shade and the pinned columns read as a highlighted
   band. So each state gets the flattened equivalent of what non-sticky cells show:
   tint blended over white (light) / surface-dark-card #0f1c1e (dark). */
const STICKY_HOVER = 'group-hover:bg-[#F9FAFC] dark:group-hover:bg-[#1B2729]';
const STICKY_BODY  = `bg-surface dark:bg-surface-dark-card ${STICKY_HOVER}`;
const STICKY_SEL   = `bg-[#F0F7F7] dark:bg-[#0F2829] ${STICKY_HOVER}`;
const STICKY_HEAD  = 'bg-[#F9FAFC] dark:bg-[#1B2729]';
function stickyCls(c, bg = STICKY_BODY) {
  if (c.sticky === 'start') return `sticky inset-inline-start-0 z-[1] ${bg}`;
  if (c.sticky === 'end')   return `sticky inset-inline-end-0 z-[1] ${bg}`;
  return '';
}
</script>

<template>
  <div class="bg-surface dark:bg-surface-dark-card rounded-2xl shadow-card border border-slate-100 dark:border-white/5 overflow-hidden print:border-none print:shadow-none">
    <!-- toolbar -->
    <div class="flex flex-wrap items-center gap-2 px-4 py-3 border-b border-slate-100 dark:border-white/5 print:hidden">
      <div v-if="title" class="flex items-center gap-2 me-2">
        <h3 class="text-[14px] font-bold text-ink dark:text-slate-100">{{ title }}</h3>
        <span class="inline-flex items-center h-5 px-2 rounded-full bg-surface-muted dark:bg-white/10 text-[11px] font-semibold text-slate-500 dark:text-slate-300">{{ totalRows.toLocaleString('en-US') }} records</span>
      </div>
      <div v-if="searchable" class="relative flex-1 min-w-[180px] max-w-xs">
        <i class="ri-search-line absolute top-1/2 -translate-y-1/2 inset-inline-start-3 text-slate-400 text-[13px]" style="inset-inline-start:.75rem"></i>
        <input v-model="q" placeholder="Search…" class="w-full h-8 ps-9 pe-3 text-[13px] bg-surface-muted dark:bg-white/5 border border-transparent focus:border-primary-500 rounded-lg focus:outline-none focus:ring-2 focus:ring-primary-500/30" />
      </div>

      <div class="flex items-center gap-1.5 ms-auto">
        <slot name="header-actions"></slot>
        <template v-if="exportable">
          <button v-for="ex in exportBtns" :key="ex.key"
            @click="onExportClick(ex.key)"
            :class="['group inline-flex items-center gap-1 h-8 px-2.5 rounded-lg text-[12px] font-semibold transition-all duration-200 active:scale-95', ex.cls]">
            <i :class="[ex.icon, 'text-[13px]']"></i><span class="hidden sm:inline">{{ ex.label }}</span>
          </button>
        </template>
      </div>
    </div>

    <!-- bulk bar -->
    <Transition name="bulk">
      <div v-if="selectable && sel.size" class="flex items-center gap-3 px-4 py-2.5 bg-primary-50 dark:bg-primary-500/10 border-b border-primary-100 dark:border-primary-500/20">
        <span class="text-sm font-medium text-primary-800 dark:text-primary-200">{{ sel.size }} selected</span>
        <div class="flex items-center gap-2 ms-auto">
          <button v-for="a in bulkActions" :key="a.event" @click="runBulk(a.event)"
            class="inline-flex items-center gap-1.5 h-8 px-3 rounded-lg text-[13px] font-medium transition"
            :class="a.tone === 'danger' ? 'text-danger bg-danger/10 hover:bg-danger/20' : 'text-primary-700 bg-white dark:bg-white/10 hover:bg-primary-100'">
            <i :class="a.icon"></i>{{ a.label }}
          </button>
          <button @click="clearSel" class="text-sm text-slate-500 hover:text-ink">Clear</button>
        </div>
      </div>
    </Transition>

    <!-- pagination — sits ABOVE the table so rows-per-page / paging is reachable
         without scrolling to the bottom of long lists -->
    <div v-if="!loading && totalRows" class="flex flex-wrap items-center gap-2 px-4 py-2.5 border-b border-slate-100 dark:border-white/5 print:hidden">
      <div class="flex items-center gap-2 text-[13px] text-slate-500">
        <span>Rows</span>
        <div ref="sizeWrap" class="relative">
          <button ref="sizeTrigger" type="button" @click="sizeOpen = !sizeOpen" @keydown="onSizeKeydown"
            class="inline-flex items-center gap-1.5 h-8 ps-3 pe-2 rounded-lg bg-surface-muted dark:bg-white/5 border text-[13px] font-semibold leading-none text-ink dark:text-slate-100 transition focus:outline-none focus:ring-2 focus:ring-primary-500/30"
            :class="sizeOpen ? 'border-primary-500/50' : 'border-transparent hover:border-slate-200 dark:hover:border-white/10'">
            <span class="tabular-nums">{{ pageSize }}</span>
            <i class="ri-arrow-down-s-line text-[15px] text-slate-400 transition-transform duration-200" :class="sizeOpen ? 'rotate-180' : ''"></i>
          </button>

          <Teleport to="body">
            <div v-if="sizeOpen" ref="sizePanel" :style="sizeStyle"
              class="fixed z-[200] p-1.5 bg-surface dark:bg-surface-dark-solid rounded-xl shadow-card-hover border border-slate-100 dark:border-white/10 animate-fade-in-up">
              <button v-for="(s, i) in SIZES" :key="s" type="button"
                @click="pickSize(s)" @mouseenter="sizeActive = i"
                class="w-full flex items-center gap-2 px-2.5 h-9 rounded-lg text-[13px] font-semibold transition"
                :class="[
                  s === pageSize ? 'text-primary-700 dark:text-primary-300' : 'text-ink dark:text-slate-200',
                  i === sizeActive ? 'bg-primary-50 dark:bg-white/10' : ''
                ]">
                <i class="ri-check-line text-[15px] text-primary-600 dark:text-primary-300" :class="s === pageSize ? '' : 'invisible'"></i>
                <span class="tabular-nums">{{ s }}</span>
              </button>
            </div>
          </Teleport>
        </div>
      </div>
      <p class="text-[13px] text-slate-500 ms-2">{{ rangeFrom }}–{{ rangeTo }} of {{ totalRows.toLocaleString('en-US') }}</p>

      <div class="flex items-center gap-1 ms-auto">
        <!-- animated: arrow slides in its travel direction on hover; button presses on click -->
        <button @click="page = Math.max(1, page - 1)" :disabled="page === 1"
          class="group grid place-items-center w-8 h-8 rounded-lg text-slate-500 transition-all duration-200 hover:bg-surface-muted dark:hover:bg-white/10 hover:text-primary-600 dark:hover:text-primary-300 active:scale-90 disabled:opacity-40 disabled:cursor-not-allowed disabled:active:scale-100" title="Previous">
          <i class="ri-arrow-left-s-line rtl:hidden transition-transform duration-200 group-hover:-translate-x-0.5 group-disabled:translate-x-0"></i>
          <i class="ri-arrow-right-s-line hidden rtl:block transition-transform duration-200 group-hover:translate-x-0.5 group-disabled:translate-x-0"></i>
        </button>
        <span class="px-2 text-[13px] font-medium text-ink dark:text-slate-200">{{ page }} / {{ pageCount }}</span>
        <button @click="page = Math.min(pageCount, page + 1)" :disabled="page === pageCount"
          class="group grid place-items-center w-8 h-8 rounded-lg text-slate-500 transition-all duration-200 hover:bg-surface-muted dark:hover:bg-white/10 hover:text-primary-600 dark:hover:text-primary-300 active:scale-90 disabled:opacity-40 disabled:cursor-not-allowed disabled:active:scale-100" title="Next">
          <i class="ri-arrow-right-s-line rtl:hidden transition-transform duration-200 group-hover:translate-x-0.5 group-disabled:translate-x-0"></i>
          <i class="ri-arrow-left-s-line hidden rtl:block transition-transform duration-200 group-hover:-translate-x-0.5 group-disabled:translate-x-0"></i>
        </button>
      </div>
    </div>

    <!-- table -->
    <div class="overflow-x-auto">
      <table class="w-full text-sm border-collapse">
        <thead>
          <tr class="bg-surface-muted/50 dark:bg-white/5 text-slate-600 dark:text-slate-300">
            <th v-if="selectable" class="ps-4 pe-2 py-2.5 sticky inset-inline-start-0 z-[2] print:hidden" :class="STICKY_HEAD">
              <button @click="toggleAll" class="grid place-items-center w-4 h-4 rounded border transition" :class="allOnPage ? 'bg-primary-600 border-primary-600 text-white' : 'border-slate-300 dark:border-white/10'">
                <i v-if="allOnPage" class="ri-check-line text-xs"></i>
              </button>
            </th>
            <th v-for="c in columns" :key="c.key"
              class="px-3 py-2.5 font-bold text-[11px] uppercase tracking-wider whitespace-nowrap"
              :class="[c.align === 'end' ? 'text-end' : c.align === 'center' ? 'text-center' : 'text-start', stickyCls(c, STICKY_HEAD), c.sortable ? 'cursor-pointer select-none hover:text-primary-700' : '']"
              :style="c.width ? { minWidth: c.width } : {}"
              @click="toggleSort(c)">
              <span class="inline-flex items-center gap-1">
                {{ c.label }}
                <span v-if="c.sortable" class="inline-flex flex-col -space-y-1.5 text-[9px] leading-none">
                  <i class="ri-arrow-up-s-fill" :class="sortKey === c.key && sortDir === 'asc' ? 'text-primary-600' : 'text-slate-300'"></i>
                  <i class="ri-arrow-down-s-fill" :class="sortKey === c.key && sortDir === 'desc' ? 'text-primary-600' : 'text-slate-300'"></i>
                </span>
              </span>
            </th>
            <th v-if="$slots['row-actions']" class="px-3 py-2.5 text-end font-bold text-[11px] uppercase tracking-wider sticky inset-inline-end-0 z-[2]" :class="STICKY_HEAD">Actions</th>
          </tr>
        </thead>

        <tbody>
          <!-- skeleton -->
          <template v-if="loading">
            <tr v-for="n in 6" :key="'sk' + n" class="border-t border-slate-100 dark:border-white/5">
              <td v-if="selectable" class="ps-4 pe-2 py-3.5 print:hidden"><div class="w-4 h-4 rounded skeleton"></div></td>
              <td v-for="c in columns" :key="c.key" class="px-3.5 py-3.5"><div class="h-4 rounded skeleton" :style="{ width: (40 + (n * 7 % 50)) + '%' }"></div></td>
              <td v-if="$slots['row-actions']" class="px-3.5 py-3.5"><div class="h-4 w-16 rounded skeleton ms-auto"></div></td>
            </tr>
          </template>

          <!-- rows -->
          <template v-else>
            <tr v-for="row in paged" :key="row[rowKey]"
              class="group border-t border-slate-100 dark:border-white/5 transition-colors hover:bg-surface-muted/50 dark:hover:bg-white/5"
              :class="sel.has(row[rowKey]) ? 'bg-primary-50/60 dark:bg-primary-500/10' : ''">
              <td v-if="selectable" class="ps-4 pe-2 py-2.5 align-top sticky inset-inline-start-0 z-[1] print:hidden" :class="sel.has(row[rowKey]) ? STICKY_SEL : STICKY_BODY">
                <button @click="toggleRow(row[rowKey])" class="grid place-items-center w-4 h-4 mt-0.5 rounded border transition" :class="sel.has(row[rowKey]) ? 'bg-primary-600 border-primary-600 text-white' : 'border-slate-300 dark:border-white/10'">
                  <i v-if="sel.has(row[rowKey])" class="ri-check-line text-xs"></i>
                </button>
              </td>
              <!-- every cell is align-top so the FIRST line of each value lines up
                   across the row, however tall the wrapped cells get -->
              <td v-for="c in columns" :key="c.key"
                class="px-3 py-2.5 align-top leading-snug text-ink dark:text-slate-200 text-[13px]"
                :class="[
                  c.align === 'end' ? 'text-end' : c.align === 'center' ? 'text-center' : 'text-start',
                  c.wrap ? '' : 'whitespace-nowrap',
                  c.mono ? 'font-mono' : '',
                  stickyCls(c, sel.has(row[rowKey]) ? STICKY_SEL : STICKY_BODY)
                ]"
                :style="(c.mono || c.ltr) ? 'direction:ltr; unicode-bidi:plaintext;' : ''"
              >
                <!-- wrap mode: constrain width on inner div so browser wraps text -->
                <div v-if="c.wrap" :style="c.width ? `min-width:${c.width}; max-width:${c.maxWidth || '450px'}; white-space:normal; word-break:break-word;` : 'white-space:normal;'">
                  <slot :name="'cell-' + c.key" :row="row" :value="row[c.key]">{{ row[c.key] }}</slot>
                </div>
                <slot v-else :name="'cell-' + c.key" :row="row" :value="row[c.key]">{{ row[c.key] }}</slot>
              </td>
              <!-- action buttons are 32px tall — a smaller vertical pad keeps them
                   optically on the row's first line instead of hanging below it -->
              <td v-if="$slots['row-actions']" class="px-3 py-1.5 align-top text-end sticky inset-inline-end-0 z-[1]" :class="sel.has(row[rowKey]) ? STICKY_SEL : STICKY_BODY">
                <slot name="row-actions" :row="row"></slot>
              </td>
            </tr>
          </template>
        </tbody>
      </table>

      <!-- empty -->
      <EmptyState v-if="!loading && paged.length === 0" icon="ri-search-eye-line" title="No records found" message="Try adjusting your filters or search term." />
    </div>

  </div>
</template>

<script>
import EmptyState from './EmptyState.vue';
export default { components: { EmptyState } };
</script>

<style scoped>
.skeleton {
  background: linear-gradient(90deg, rgba(148,163,184,.12) 25%, rgba(148,163,184,.22) 37%, rgba(148,163,184,.12) 63%);
  background-size: 400% 100%;
  animation: shimmer 1.4s ease infinite;
}
@keyframes shimmer { 0% { background-position: 100% 0; } 100% { background-position: 0 0; } }
.bulk-enter-active, .bulk-leave-active { transition: all .25s ease; }
.bulk-enter-from, .bulk-leave-to { opacity: 0; transform: translateY(-4px); }
@media (prefers-reduced-motion: reduce) { .skeleton { animation: none; } }
</style>
