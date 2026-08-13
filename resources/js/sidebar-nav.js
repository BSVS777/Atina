/**
 * Shared open/closed state for the sidebar's collapsible nav groups
 * (Docentes, Groups, Reports) so opening one collapses the others.
 *
 * Registered as an Alpine.store (not a per-node x-data value) because the
 * sidebar is x-persist'ed across wire:navigate transitions — each group's
 * own x-data mounts exactly once, so coordinating them requires state that
 * lives outside any single node.
 *
 * Register once in resources/js/app.js:
 *   import './sidebar-nav.js';
 */
document.addEventListener("alpine:init", () => {
  Alpine.store("sidebarNav", { openGroup: null });
});
