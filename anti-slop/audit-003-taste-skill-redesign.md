# Audit 003: Taste-Skill & High-End Visual Design Audit

> **Framework:** `taste-skill` (`redesign-existing-projects`, `high-end-visual-design`, `design-taste-frontend`)  
> **Target Project:** Mobile Sales Force (MSF) PT Mersifarma Tirmaku Investigas  
> **Date:** 2026-10-10  
> **Evaluator:** AI Coding Assistant acting as Principal UI/UX Architect

---

## 1. Design Read & Dial Configuration

- **Design Read:**  
  *Enterprise Pharmaceutical Field Operations (MSF) Web Application for Medical Representatives & Field Managers, with a high-trust Linear-clean aesthetic, Plus Jakarta Sans + Outfit typography, Mersifarma Medical Cyan (`#0284c7`) and Deep Slate Navy (`#0f172a`), haptic double-bezel cards, micro-eyebrow pills, tabular numerals for operational dates/stats, and spring-interpolated transitions.*
- **The Three Dials:**
  - `DESIGN_VARIANCE: 3` (Strict, reliable field operations hierarchy).
  - `MOTION_INTENSITY: 4` (Snappy haptic feedback, spring button press `scale(0.98)`, backdrop-blurred modals).
  - `VISUAL_DENSITY: 6` (Optimized for field productivity: compact doctor metadata, clear badges, high-contrast action CTAs).

---

## 2. Diagnosed Issues & Targeted Upgrades

### A. Typography & Data Presentation
1. **Lack of Tabular Figures:** Dates, times, phone numbers, and counters previously rendered in proportional font, causing jitter during dynamic updates.  
   - **Fix:** Enforce `font-variant-numeric: tabular-nums` globally across badges, dates, and metric counters.
2. **Heading & Eyebrow Tension:** Page titles lacked tight tracking and micro-eyebrows.  
   - **Fix:** Add microscopic eyebrow tags (`text-[10px] uppercase tracking-[0.15em] font-bold`) and tighten title letter-spacing (`-0.025em`).
3. **Text Balance:** Multi-line descriptions and modal explanations had orphaned words.  
   - **Fix:** Apply `text-wrap: pretty` / `text-wrap: balance`.

### B. Surfaces, Borders & Shadows
1. **Flat / Generic Box Shadows:** Cards used standard `rgba(0,0,0,0.05)`.  
   - **Fix:** Replace with tinted ambient slate shadows (`rgba(15, 23, 42, 0.04)`) and subtle top-edge highlights (`inset 0 1px 0 rgba(255, 255, 255, 0.9)`).
2. **Modal Backdrops:** Previously flat dark overlay `rgba(15, 23, 42, 0.6)`.  
   - **Fix:** Elevate to agency-standard `backdrop-filter: blur(8px); background: rgba(15, 23, 42, 0.45);` with spring scale-up animation.

### C. Haptic Interactions & Buttons
1. **Tactile Feedback:** Buttons had flat hover color shifts with no physical compression.  
   - **Fix:** Add `:active { transform: scale(0.98); }` and smooth spring cubic-bezier transitions (`cubic-bezier(0.16, 1, 0.3, 1)`).
2. **Button Insets:** Add subtle inner highlight (`inset 0 1px 0 rgba(255,255,255,0.2)`) to primary actions.

### D. Empty States & Feedback
1. **Empty State Quality:** Plain dashed boxes with raw text.  
   - **Fix:** Implement refined empty state cards with custom muted iconography, supportive titles, and direct CTA guidance.

### E. Mobile Sticky Navigation
1. **Glassmorphism:** Bottom navigation bar upgraded with `backdrop-filter: blur(16px)` and translucent surface for native-app feel on mobile devices.
