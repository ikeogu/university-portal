// Shared shell-width tiers so every fixed-position element (bottom tab bar,
// sticky footers) stays visually aligned with MobileShell's own width
// without each file having to remember to update in lockstep.
//
// NARROW: single-record / focused-card screens (sign-in, public checker,
// result view) — widen just enough that they don't look stranded on a
// large monitor, without turning a small card into an oddly wide one.
//
// WIDE: data-dense screens (admin tables/lists, score entry roster) —
// grow more generously so lists and tables get real breathing room on
// tablets/desktops instead of staying pinned to phone width.
export const SHELL_WIDTH_NARROW = 'max-w-shell sm:max-w-shell-sm';
export const SHELL_WIDTH_WIDE = 'max-w-shell md:max-w-2xl lg:max-w-4xl xl:max-w-5xl';
