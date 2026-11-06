# Round 2 — Context (Direction locked : D — La négociation)

## Status

- **Direction locked**: D — La négociation (Round 1).
- Round 1 set closed — other directions preserved, no longer under consideration.
- Round 2 in progress: concrete artifacts from the locked concept.

## Locked concept capsule — D

- **Prémisse souveraine**: the relationship the platform mediates between project owner and investor is inherently evaluative and bilateral — a silent negotiation before the alliance. Typography must translate this mutual prudence without drama.
- **Typographie**: `--font-mono` (`'Anonymous Pro'` + fallback stack) — existing project font, fallback functional.
  - eyebrow: small text, tertiary, `#0f766e`, 0.875rem, weight 600, uppercase, tracking +0.18em, margin-bottom 2.5rem.
  - display (h1): primary, `#0e1726`, sentence case, weight 700, `clamp(2.75rem, 5.5vw, 4.25rem)`, line-height 1.35, letter-spacing −0.01em, max-width 34rem, margin-bottom 2.5rem.
  - accent: `#f2b705` on the bridging word (e.g. « investisseurs »).
  - body: secondary, `#4a5568`, 1rem, line-height 1.5, max-width 32rem.
- **Échelle**: eyebrow 0.875rem · display clamp(2.75rem, 5.5vw, 4.25rem) · body 1rem.
- **Casse / poids / tracking / line**: sentence case for display and body; uppercase for eyebrow; weights 700 / 600 / 400; tracking +0.18em / −0.01em / normal; lines 1.35 / 1.5.
- **Palette**: ground `#f7f8fa`; primary `#0e1726` · secondary `#4a5568` · tertiary `#0f766e` · accent `#f2b705` (proportions ~80 / ~45 / ~8 / ~3).
- **Espace**: container max-width 42rem, padding 8rem 1.5rem; gaps 2.5rem; display max-width 34rem; body max-width 32rem; left aligned.

## Locked decisions for production artifacts

- Fonts stay as defined by the project (`--font-sans`, `--font-mono`); no new families.
- Display typography locked: mono, primary ink, weight 700, sentence case, `clamp(2.75rem, 5.5vw, 4.25rem)`, line-height 1.35, letter-spacing −0.01em.
- Eyebrow locked: tertiary `#0f766e`, mono, `text-lg`, weight 600, uppercase, tracking +0.18em.
- Body: secondary ink, line-height 1.5.
- Accent locked: `#f2b705` on bridging word.

## Invariants

- Fonts, working copy, color roles, tracking, line-height, measure.
- No new fonts, no shapes/lines/panels in Round-1 boundary (until Round 2 approval allows otherwise).

## Allowed variation

- Clamp bounds, tracking, line-height within the locked relationships.

## Unresolved risks

- Fallback mono rendering.
- French accents in display text.

## Translation into concrete Round 2 artifacts

- Inject this context as governing context; map locked roles (eyebrow tertiary / display primary / body secondary / accent accent) onto existing design tokens; keep fonts unchanged; preserve tracking / line-height / measure.
