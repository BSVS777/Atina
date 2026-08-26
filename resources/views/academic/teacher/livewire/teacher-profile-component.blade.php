<div class="space-y-6">
    <div class="card" style="padding: 1.25rem 1.5rem;">
        <div class="card-head">
            <span class="card-title">{{ $teacher->fullName() }}</span>
        </div>
        <p>{{ __('National ID') }}: {{ $teacher->national_id }} &middot; {{ $teacher->position?->name }}</p>
    </div>

    <div class="card">
        <div class="card-head">
            <span class="card-title">{{ __('Academic credentials') }}</span>
            @if ($canManage)
            <div class="card-actions">
                <button type="button" class="btn btn-orange" wire:click="openCreateModal">
                    <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round">
                        <line x1="12" y1="5" x2="12" y2="19"></line>
                        <line x1="5" y1="12" x2="19" y2="12"></line>
                    </svg>
                    <span>{{ __('Add') }}</span>
                </button>
            </div>
            @endif
        </div>

        <div class="table-scroll">
            <div class="table-inner" style="--table-cols: 2fr 1.2fr 2fr 1fr 1fr 0.8fr;" role="table">
                <div class="data-row data-row-head" role="row">
                    <span role="columnheader">{{ __('Specialty') }}</span>
                    <span role="columnheader">{{ __('Degree') }}</span>
                    <span role="columnheader">{{ __('Institution') }}</span>
                    <span role="columnheader">{{ __('Start date') }}</span>
                    <span role="columnheader">{{ __('End date') }}</span>
                    <span>{{ __('Actions') }}</span>
                </div>

                @forelse ($rows as $row)
                <div class="data-row" role="row" wire:key="credential-{{ $row['id'] }}">
                    <span>{{ $row['specialty'] }}</span>
                    <span>{{ __($row['degreeLevel']) }}</span>
                    <span>{{ $row['institution'] }}</span>
                    <span>{{ $row['startDate'] }}</span>
                    <span>{{ $row['endDate'] }}</span>
                    <div class="actions-cell">
                        @if ($canManage)
                        <x-ui.row-actions
                            :can-edit="true"
                            :can-delete="false"
                            edit-action="$wire.openEditModal({{ $row['id'] }})" />
                        @endif
                    </div>
                </div>
                @empty
                <div class="empty-row">{{ __('This teacher has no academic credentials yet.') }}</div>
                @endforelse
            </div>
        </div>
    </div>

    @if ($canManage)
    <x-ui.modal :show="$showModal" :title="$editingId === null ? __('New academic credential') : __('Edit academic credential')">
        <div class="form-field" style="position: relative;"
            wire:key="credential-specialty-{{ $editingId ?? 'new' }}-{{ $showModal ? 'open' : 'closed' }}"
            x-data="{
                open: false,
                query: @js($form->specialtyName),
                options: @js($specialties->pluck('name')),
                get filtered() {
                    const q = this.query.trim().toLowerCase();
                    return q ? this.options.filter(name => name.toLowerCase().includes(q)) : this.options;
                },
                select(name) {
                    this.query = name;
                    this.open = false;
                    $wire.set('form.specialtyName', name, false);
                },
            }"
            x-on:click.outside="open = false">
            <label for="credentialSpecialty">{{ __('Specialty') }}</label>
            <input type="text" id="credentialSpecialty" autocomplete="off"
                x-model="query"
                x-on:focus="open = true"
                x-on:input="open = true; $wire.set('form.specialtyName', $event.target.value, false)"
                class="{{ $errors->has('form.specialtyName') ? 'has-error' : '' }}">
            @error('form.specialtyName') <span class="form-error">{{ $message }}</span> @enderror

            <p style="margin: 0; font-size: 12.5px; color: var(--textSecondary);">
                {{ __('Select an existing specialty or type a new one to create it.') }}
            </p>

            <div class="institution-suggestions" x-show="open && filtered.length" x-cloak>
                <template x-for="name in filtered" :key="name">
                    <button type="button" class="institution-suggestion-item" x-on:click="select(name)">
                        <span class="institution-suggestion-name" x-text="name"></span>
                    </button>
                </template>
            </div>
        </div>
        <div class="form-field">
            <label for="credentialDegreeLevel">{{ __('Degree level') }}</label>
            <select id="credentialDegreeLevel" wire:model="form.degreeLevel" class="{{ $errors->has('form.degreeLevel') ? 'has-error' : '' }}">
                <option value="">{{ __('Select a degree level') }}</option>
                @foreach ($degreeLevels as $level)
                <option value="{{ $level->value }}">{{ __($level->value) }}</option>
                @endforeach
            </select>
            @error('form.degreeLevel') <span class="form-error">{{ $message }}</span> @enderror
        </div>
        <div class="form-field" style="position: relative;">
            <label for="credentialInstitution">{{ __('Institution') }}</label>
            <input type="text" id="credentialInstitution" autocomplete="off"
                wire:model.live.debounce.400ms="form.institution"
                class="{{ $errors->has('form.institution') ? 'has-error' : '' }}">
            @error('form.institution') <span class="form-error">{{ $message }}</span> @enderror

            <p style="margin: 0; font-size: 12.5px; color: var(--textSecondary);">
                {{ __('Type at least 3 characters to search real institutions via OpenAlex, or type the name manually.') }}
            </p>

            <div wire:loading.delay wire:target="form.institution" style="font-size: 12.5px; color: var(--textSecondary);">
                {{ __('Searching institutions…') }}
            </div>

            @if ($institutionSearchUnavailable)
            <p style="margin: 0; font-size: 12.5px; color: var(--textSecondary);">
                {{ __('Institution suggestions are unavailable right now. You can still type the institution manually.') }}
            </p>
            @endif

            @if ($institutionSearchPerformed && empty($institutionSuggestions) && ! $institutionSearchUnavailable)
            <p style="margin: 0; font-size: 12.5px; color: var(--textSecondary);" wire:loading.remove wire:target="form.institution">
                {{ __('No matching institutions found. You can still type the institution manually.') }}
            </p>
            @endif

            @if (! empty($institutionSuggestions))
            <div class="institution-suggestions" wire:loading.remove wire:target="form.institution">
                @foreach ($institutionSuggestions as $suggestion)
                <button type="button" class="institution-suggestion-item" wire:click="selectInstitution(@js($suggestion['name']))">
                    <span class="institution-suggestion-name">{{ $suggestion['name'] }}</span>
                    @if ($suggestion['hint'])
                    <span class="institution-suggestion-hint">{{ $suggestion['hint'] }}</span>
                    @endif
                </button>
                @endforeach
            </div>
            @endif
        </div>
        <div class="form-field">
            <label for="credentialStartDate">{{ __('Start date') }}</label>
            <input type="date" id="credentialStartDate" min="1950-01-01" max="{{ date('Y-m-d') }}" wire:model="form.startDate" class="{{ $errors->has('form.startDate') ? 'has-error' : '' }}">
            @error('form.startDate') <span class="form-error">{{ $message }}</span> @enderror
        </div>
        <div class="form-field">
            <label for="credentialEndDate">{{ __('End date') }}</label>
            <input type="date" id="credentialEndDate" min="1950-01-01" max="{{ date('Y-m-d') }}" wire:model="form.endDate" class="{{ $errors->has('form.endDate') ? 'has-error' : '' }}">
            @error('form.endDate') <span class="form-error">{{ $message }}</span> @enderror
        </div>
        <x-slot:footer>
            <button type="button" class="btn btn-secondary" wire:click="closeModal">{{ __('Cancel') }}</button>
            <button type="button" class="btn btn-primary" wire:click="save">{{ __('Confirm') }}</button>
        </x-slot:footer>
    </x-ui.modal>
    @endif
</div>
