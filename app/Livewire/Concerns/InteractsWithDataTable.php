<?php

declare(strict_types=1);

namespace App\Livewire\Concerns;

use Illuminate\Pagination\LengthAwarePaginator;
use Livewire\Attributes\Url;

/**
 * Generic pagination/sorting/search state shared by every CRUD data-table
 * component, regardless of bounded context. Extracted so no future module
 * has to re-implement it (DRY, matches the existing App\Concerns pattern
 * used by ProfileValidationRules / PasswordValidationRules).
 *
 * Two table modes are supported, selected per-component via the
 * `$tableMode` property:
 *
 *  - 'client' (default): the full collection is fetched from the
 *    Application layer ONCE per Livewire render and handed to Alpine.js
 *    as JSON (see resources/js/data-table.js — `crudTable`). Search, sort
 *    and pagination are then resolved entirely in the browser: zero
 *    Livewire round-trips until an actual mutation (create/update/delete)
 *    happens. Intended for small, reference-style datasets — roles,
 *    permissions, catalogs, statuses, etc.
 *
 *  - 'server': classic Livewire-driven pagination. Search/sort/page
 *    changes trigger a request to the server, as is required once a
 *    dataset is too large to ship to the browser in one response.
 *
 * A concrete component only needs to:
 *   1. Set `protected string $tableMode = 'client' | 'server';`
 *   2. In `render()`, branch on `$this->isServerMode()` and call the
 *      matching Application UseCase method (`all()` vs `paginate()`).
 *
 * Everything else — the four pagination actions, the sort toggle, and
 * resetting the page on search/perPage changes — is inherited.
 */
trait InteractsWithDataTable
{
    #[Url(as: 'q', history: true)]
    public string $search = '';

    public int $perPage = 10;

    public int $page = 1;

    public string $sortKey = '';

    public string $sortDir = 'asc';

    /**
     * Exposed to the Blade view so it can pick which set of directives to
     * render (wire:* for 'server', x-* or @* for 'client')
     * without changing a single visual class.
     */
    public function tableMode(): string
    {
        return $this->tableMode;
    }

    public function isServerMode(): bool
    {
        return $this->tableMode() === 'server';
    }

    public function isClientMode(): bool
    {
        return ! $this->isServerMode();
    }

    /**
     * Server mode only — client mode resets its own page inside Alpine
     * and never touches this property over the wire.
     */
    public function updatingSearch(): void
    {
        $this->page = 1;
    }

    public function updatingPerPage(): void
    {
        $this->page = 1;
    }

    /**
     * Server mode only. In client mode, sorting is handled by Alpine's
     * `sort()` method in resources/js/data-table.js and this method is
     * simply never wired up in the Blade view.
     */
    public function sort(string $key): void
    {
        $this->sortDir = $this->sortKey === $key && $this->sortDir === 'asc' ? 'desc' : 'asc';
        $this->sortKey = $key;
        $this->page = 1;
    }

    public function previousPage(): void
    {
        $this->page = max(1, $this->page - 1);
    }

    public function nextPage(): void
    {
        $this->page++;
    }

    public function gotoPage(int $page): void
    {
        $this->page = max(1, $page);
    }

    /**
     * Client mode only — call this after any mutation (create/update/
     * delete) that changes what the table should display, passing the
     * freshly re-fetched rows. Dispatches a browser event that
     * resources/js/data-table.js's `crudTable` Alpine component listens
     * for and applies directly to its own `rows` state.
     *
     * Why this exists: Alpine's `x-data="crudTable({ rows: @js($rows) })"`
     * is evaluated once, the first time the element enters the DOM.
     * Livewire's DOM morph deliberately preserves existing Alpine
     * component state across re-renders (so open dropdowns, in-progress
     * typing, etc. survive unrelated updates) — meaning a fresh x-data
     * attribute in newly-rendered HTML is never re-read after that first
     * init. Without this, `rows` goes stale the moment any mutation
     * changes the underlying data.
     *
     * No-op in server mode, where Livewire's normal re-render already
     * updates the DOM correctly on its own — every concrete component's
     * save()/delete() can call this unconditionally regardless of mode.
     *
     * @param  array<int, array<string, mixed>>  $rows
     */
    public function refreshTable(array $rows): void
    {
        if ($this->isClientMode()) {
            $this->dispatch('data-table-refresh', rows: $rows);
        }
    }

    /**
     * Server-mode helper for Application layers that hand back a plain
     * array of row arrays instead of an Eloquent query — which is every
     * UseCase under src/, by design: the Domain never leaks a query
     * Builder into Presentation. Applies this component's current
     * $search / $sortKey / $sortDir / $perPage / $page to the already
     * materialised rows and wraps the slice in the LengthAwarePaginator
     * that <x-ui.data-table> expects on its 'server' branch.
     *
     * Components backed directly by Eloquent (TeacherComponent,
     * RoleComponent...) keep their own query->paginate() and never call
     * this.
     *
     * @param  array<int, array<string, mixed>>  $rows
     * @param  array<int, string>  $searchable  row keys the search box matches against
     * @return LengthAwarePaginator<int, array<string, mixed>>
     */
    public function paginateRows(array $rows, array $searchable): LengthAwarePaginator
    {
        $rows = $this->filterRows($rows, $searchable, $this->search);

        if ($this->sortKey !== '') {
            $key = $this->sortKey;
            $direction = $this->sortDir === 'desc' ? -1 : 1;

            usort($rows, fn (array $a, array $b): int => $direction * strnatcasecmp(
                (string) ($a[$key] ?? ''),
                (string) ($b[$key] ?? ''),
            ));
        }

        $total = count($rows);
        $lastPage = max(1, (int) ceil($total / $this->perPage));

        // Filtering can shrink the result set below the page the user was
        // standing on (searching while on page 4 of 5). Clamping keeps the
        // footer summary and the pager buttons consistent instead of
        // rendering an empty page with no way back.
        $this->page = min(max(1, $this->page), $lastPage);

        return new LengthAwarePaginator(
            array_slice($rows, ($this->page - 1) * $this->perPage, $this->perPage),
            $total,
            $this->perPage,
            $this->page,
        );
    }

    /**
     * Search-only filter, factored out of paginateRows() so an export path
     * (which must scan the full result set, never a single page) can apply
     * the exact same matching rule without going through pagination.
     *
     * @param  array<int, array<string, mixed>>  $rows
     * @param  array<int, string>  $searchable  row keys the search matches against
     * @return array<int, array<string, mixed>>
     */
    public function filterRows(array $rows, array $searchable, ?string $search): array
    {
        if ($search === null || $search === '' || $searchable === []) {
            return $rows;
        }

        $term = mb_strtolower(trim($search));

        return array_values(array_filter($rows, function (array $row) use ($searchable, $term): bool {
            foreach ($searchable as $key) {
                if (str_contains(mb_strtolower((string) ($row[$key] ?? '')), $term)) {
                    return true;
                }
            }

            return false;
        }));
    }
}
