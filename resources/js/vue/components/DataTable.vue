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
import { ref, computed, watch } from 'vue';

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
function stickyCls(c) {
  if (c.sticky === 'start') return 'sticky inset-inline-start-0 z-[1] bg-surface dark:bg-slate-800';
  if (c.sticky === 'end')   return 'sticky inset-inline-end-0 z-[1] bg-surface dark:bg-slate-800';
  return '';
}
</script>

<template>
  <div class="bg-surface dark:bg-slate-800/60 rounded-2xl shadow-card border border-slate-100 dark:border-white/5 overflow-hidden">
    <!-- toolbar -->
    <div class="flex flex-wrap items-center gap-3 px-4 py-3.5 border-b border-slate-100 dark:border-white/5">
      <div v-if="title" class="flex items-center gap-2 me-2">
        <h3 class="text-base font-extrabold text-ink dark:text-slate-100">{{ title }}</h3>
        <span class="inline-flex items-center h-6 px-2.5 rounded-full bg-surface-muted dark:bg-white/10 text-xs font-semibold text-slate-500 dark:text-slate-300">{{ totalRows.toLocaleString() }} records</span>
      </div>
      <div v-if="searchable" class="relative flex-1 min-w-[200px] max-w-xs">
        <i class="ri-search-line absolute top-1/2 -translate-y-1/2 inset-inline-start-3 text-slate-400" style="inset-inline-start:.75rem"></i>
        <input v-model="q" placeholder="Search…" class="w-full h-10 ps-10 pe-3 text-sm bg-surface-muted dark:bg-white/5 border border-transparent focus:border-primary-500 rounded-xl focus:outline-none focus:ring-2 focus:ring-primary-500/30" />
      </div>

      <div class="flex items-center gap-2 ms-auto">
        <template v-if="exportable">
          <button v-for="ex in exportBtns" :key="ex.key"
            @click="$emit('export', ex.key)"
            :class="['group inline-flex items-center gap-1.5 h-9 px-3 rounded-lg text-[13px] font-semibold transition-all duration-200 active:scale-95 hover:-translate-y-0.5 hover:shadow-sm', ex.cls]">
            <i :class="[ex.icon, 'transition-transform duration-300 ease-out motion-reduce:transition-none', ex.anim]"></i><span class="hidden sm:inline">{{ ex.label }}</span>
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

    <!-- table -->
    <div class="overflow-x-auto">
      <table class="w-full text-sm border-collapse">
        <thead>
          <tr class="bg-surface-muted/60 dark:bg-white/5 text-slate-800 dark:text-slate-200">
            <th v-if="selectable" class="ps-4 pe-2 py-3 sticky inset-inline-start-0 bg-surface-muted/60 dark:bg-slate-800 z-[2]">
              <button @click="toggleAll" class="grid place-items-center w-4 h-4 rounded border transition" :class="allOnPage ? 'bg-primary-600 border-primary-600 text-white' : 'border-slate-300 dark:border-white/20'">
                <i v-if="allOnPage" class="ri-check-line text-xs"></i>
              </button>
            </th>
            <th v-for="c in columns" :key="c.key"
              class="px-3.5 py-3 font-bold text-[12px] uppercase tracking-wider whitespace-nowrap"
              :class="[c.align === 'end' ? 'text-end' : c.align === 'center' ? 'text-center' : 'text-start', stickyCls(c), c.sortable ? 'cursor-pointer select-none hover:text-primary-700' : '']"
              :style="c.width ? { width: c.width } : {}"
              @click="toggleSort(c)">
              <span class="inline-flex items-center gap-1">
                {{ c.label }}
                <span v-if="c.sortable" class="inline-flex flex-col -space-y-1.5 text-[9px] leading-none">
                  <i class="ri-arrow-up-s-fill" :class="sortKey === c.key && sortDir === 'asc' ? 'text-primary-600' : 'text-slate-300'"></i>
                  <i class="ri-arrow-down-s-fill" :class="sortKey === c.key && sortDir === 'desc' ? 'text-primary-600' : 'text-slate-300'"></i>
                </span>
              </span>
            </th>
            <th v-if="$slots['row-actions']" class="px-3.5 py-3 text-end font-bold text-[12px] uppercase tracking-wider sticky inset-inline-end-0 bg-surface-muted/60 dark:bg-slate-800 z-[2]">Actions</th>
          </tr>
        </thead>

        <tbody>
          <!-- skeleton -->
          <template v-if="loading">
            <tr v-for="n in 6" :key="'sk' + n" class="border-t border-slate-100 dark:border-white/5">
              <td v-if="selectable" class="ps-4 pe-2 py-3.5"><div class="w-4 h-4 rounded skeleton"></div></td>
              <td v-for="c in columns" :key="c.key" class="px-3.5 py-3.5"><div class="h-4 rounded skeleton" :style="{ width: (40 + (n * 7 % 50)) + '%' }"></div></td>
              <td v-if="$slots['row-actions']" class="px-3.5 py-3.5"><div class="h-4 w-16 rounded skeleton ms-auto"></div></td>
            </tr>
          </template>

          <!-- rows -->
          <template v-else>
            <tr v-for="row in paged" :key="row[rowKey]"
              class="border-t border-slate-100 dark:border-white/5 transition-colors hover:bg-primary-50/40 dark:hover:bg-white/5"
              :class="sel.has(row[rowKey]) ? 'bg-primary-50/60 dark:bg-primary-500/10' : ''">
              <td v-if="selectable" class="ps-4 pe-2 py-3 sticky inset-inline-start-0 bg-inherit z-[1]">
                <button @click="toggleRow(row[rowKey])" class="grid place-items-center w-4 h-4 rounded border transition" :class="sel.has(row[rowKey]) ? 'bg-primary-600 border-primary-600 text-white' : 'border-slate-300 dark:border-white/20'">
                  <i v-if="sel.has(row[rowKey])" class="ri-check-line text-xs"></i>
                </button>
              </td>
              <td v-for="c in columns" :key="c.key"
                class="px-3.5 py-3 text-ink dark:text-slate-200 whitespace-nowrap font-medium"
                :class="[c.align === 'end' ? 'text-end' : c.align === 'center' ? 'text-center' : 'text-start', c.mono ? 'font-mono text-[13px]' : '', stickyCls(c)]"
                :style="(c.mono || c.ltr) ? 'direction:ltr; unicode-bidi:plaintext' : ''">
                <slot :name="'cell-' + c.key" :row="row" :value="row[c.key]">{{ row[c.key] }}</slot>
              </td>
              <td v-if="$slots['row-actions']" class="px-3.5 py-3 text-end sticky inset-inline-end-0 bg-inherit z-[1]">
                <slot name="row-actions" :row="row"></slot>
              </td>
            </tr>
          </template>
        </tbody>
      </table>

      <!-- empty -->
      <EmptyState v-if="!loading && paged.length === 0" icon="ri-search-eye-line" title="No records found" message="Try adjusting your filters or search term." />
    </div>

    <!-- pagination -->
    <div v-if="!loading && totalRows" class="flex flex-wrap items-center gap-3 px-4 py-3 border-t border-slate-100 dark:border-white/5">
      <div class="flex items-center gap-2 text-sm text-slate-500">
        <span>Rows</span>
        <select v-model.number="pageSize" class="h-9 ps-2.5 pe-7 rounded-lg bg-surface-muted dark:bg-white/5 text-sm border-0 focus:ring-2 focus:ring-primary-500/30">
          <option v-for="s in SIZES" :key="s" :value="s">{{ s }}</option>
        </select>
      </div>
      <p class="text-sm text-slate-500 ms-2">{{ rangeFrom }}–{{ rangeTo }} of {{ totalRows.toLocaleString() }}</p>

      <div class="flex items-center gap-1 ms-auto">
        <button @click="page = Math.max(1, page - 1)" :disabled="page === 1" class="grid place-items-center w-9 h-9 rounded-lg text-slate-500 hover:bg-surface-muted dark:hover:bg-white/10 disabled:opacity-40 disabled:cursor-not-allowed">
          <i class="ri-arrow-right-s-line rtl:hidden"></i><i class="ri-arrow-left-s-line hidden rtl:block"></i>
        </button>
        <span class="px-3 text-sm font-medium text-ink dark:text-slate-200">{{ page }} / {{ pageCount }}</span>
        <button @click="page = Math.min(pageCount, page + 1)" :disabled="page === pageCount" class="grid place-items-center w-9 h-9 rounded-lg text-slate-500 hover:bg-surface-muted dark:hover:bg-white/10 disabled:opacity-40 disabled:cursor-not-allowed">
          <i class="ri-arrow-left-s-line rtl:hidden"></i><i class="ri-arrow-right-s-line hidden rtl:block"></i>
        </button>
      </div>
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
