/**
 * Alpine.js is injected as a runtime global by Livewire (not an npm
 * dependency of this project — see resources/js/data-table.ts), so it has
 * no bundled type declarations. This file declares only the surface this
 * codebase's TypeScript actually calls.
 */
export {};

interface AlpineStatic {
    data(name: string, callback: (...args: never[]) => object): void;
}

declare global {
    const Alpine: AlpineStatic;

    interface Window {
        Alpine: AlpineStatic;
    }
}
