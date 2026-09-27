/**
 * Case-insensitive search helpers shared by every filter/search box.
 *
 * One implementation so the dropdown search (FormSelect), the table search and
 * the list screens all agree on what "matches" means and shade it the same way.
 */

/** Lowercase anything (numbers, null, …) for comparison. */
export function norm(value) {
  return String(value ?? '').toLocaleLowerCase();
}

/**
 * Accept a single term or a list of them (a screen can filter by keyword AND
 * mobile at once) and return the usable ones, longest first so the widest match
 * wins where two terms overlap at the same spot.
 */
export function terms(input) {
  const list = Array.isArray(input) ? input : [input];
  const out = [];
  for (const t of list) {
    const n = norm(t).trim();
    if (n && !out.includes(n)) out.push(n);
  }
  return out.sort((a, b) => b.length - a.length);
}

/** True when `value` contains every given term, ignoring case. */
export function matches(value, term) {
  const hay = norm(value);
  return terms(term).every((t) => hay.includes(t));
}

/**
 * Split `value` into [{ text, hit }] chunks so a template can shade every
 * occurrence of the term(s). Lowercasing changes the length of a few exotic
 * characters (e.g. 'İ'); the offsets then no longer line up, so we return the
 * text unshaded rather than highlighting the wrong slice.
 */
export function highlightSegments(value, term) {
  const text = String(value ?? '');
  const needles = terms(term);
  const lower = text.toLocaleLowerCase();
  if (!needles.length || lower.length !== text.length) return [{ text, hit: false }];

  const out = [];
  let i = 0;
  while (i < text.length) {
    let at = -1;
    let len = 0;
    for (const n of needles) {
      const j = lower.indexOf(n, i);
      // Earliest match wins; `needles` is longest-first so a tie keeps the longest.
      if (j !== -1 && (at === -1 || j < at)) { at = j; len = n.length; }
    }
    if (at === -1) { out.push({ text: text.slice(i), hit: false }); break; }
    if (at > i) out.push({ text: text.slice(i, at), hit: false });
    out.push({ text: text.slice(at, at + len), hit: true });
    i = at + len;
  }
  return out;
}
