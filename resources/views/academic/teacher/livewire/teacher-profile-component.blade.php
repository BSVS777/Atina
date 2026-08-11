<div class="space-y-6">
    <div class="card" style="padding: 1.25rem 1.5rem;">
        <div class="card-head">
            <span class="card-title">{{ $teacher->fullName() }}</span>
        </div>
        <p>{{ __('National ID') }}: {{ $teacher->national_id }} &middot; {{ $teacher->position?->name }}</p>
    </div>

    {{--
        A small, unpaginated list scoped to this one teacher (typically a
        handful of rows) — not <x-ui.data-table>, whose search box and
        pagination footer only make sense for a full catalog. Same CSS
        classes (.card/.data-row/.actions-cell) so it still looks native.
    --}}
    <div class="card" style="padding: 1.25rem 1.5rem;">
        <div class="form-field" style="margin-bottom: 0;">
            <label for="profileContextCourse">{{ __('Evaluate affinity in the context of a course (DO-01)') }}</label>
            <select id="profileContextCourse" wire:model.live="contextCourseId">
                <option value="">{{ __('No course selected') }}</option>
                @foreach ($courses as $course)
                <option value="{{ $course->id }}">{{ $course->code }} — {{ $course->name }}</option>
                @endforeach
            </select>
        </div>
        @if ($contextEvaluated)
        <p style="margin-top: .75rem; color: var(--textSecondary);">
            @if ($catalogCitation)
                {{ __('Catalog applied') }}: {{ $catalogCitation }}
            @else
                {{ __('No catalog published for this course yet.') }}
            @endif
        </p>
        @endif
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
            <div class="table-inner" style="--table-cols: 2fr 1.2fr 2fr 0.8fr {{ $contextEvaluated ? '1.4fr' : '' }} 0.8fr;" role="table">
                <div class="data-row data-row-head" role="row">
                    <span role="columnheader">{{ __('Specialty') }}</span>
                    <span role="columnheader">{{ __('Degree') }}</span>
                    <span role="columnheader">{{ __('Institution') }}</span>
                    <span role="columnheader">{{ __('Year') }}</span>
                    @if ($contextEvaluated)
                    <span role="columnheader">{{ __('Affinity result') }}</span>
                    @endif
                    <span>{{ __('Actions') }}</span>
                </div>

                @forelse ($rows as $row)
                <div class="data-row" role="row" wire:key="credential-{{ $row['id'] }}">
                    <span>{{ $row['specialty'] }}</span>
                    <span>{{ __($row['degreeLevel']) }}</span>
                    <span>{{ $row['institution'] }}</span>
                    <span>{{ $row['yearObtained'] }}</span>
                    @if ($contextEvaluated)
                    <span>
                        @if ($row['isAffine'] === true)
                        <span class="status-badge affinity-matched">{{ __('Atinente') }}</span>
                        @elseif ($row['isAffine'] === false)
                        <span class="status-badge affinity-not-matched">{{ __('No Atinente') }}</span>
                        @endif
                    </span>
                    @endif
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
        <div class="form-field">
            <label for="credentialSpecialty">{{ __('Specialty') }}</label>
            <select id="credentialSpecialty" wire:model="form.specialtyId" class="{{ $errors->has('form.specialtyId') ? 'has-error' : '' }}">
                <option value="">{{ __('Select a specialty') }}</option>
                @foreach ($specialties as $specialty)
                <option value="{{ $specialty->id }}">{{ $specialty->name }}</option>
                @endforeach
            </select>
            @error('form.specialtyId') <span class="form-error">{{ $message }}</span> @enderror
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
        <div class="form-field">
            <label for="credentialInstitution">{{ __('Institution') }}</label>
            <input type="text" id="credentialInstitution" wire:model="form.institution" class="{{ $errors->has('form.institution') ? 'has-error' : '' }}">
            @error('form.institution') <span class="form-error">{{ $message }}</span> @enderror
        </div>
        <div class="form-field">
            <label for="credentialYearObtained">{{ __('Year obtained') }}</label>
            <input type="number" id="credentialYearObtained" min="1950" max="{{ date('Y') }}" wire:model="form.yearObtained" class="{{ $errors->has('form.yearObtained') ? 'has-error' : '' }}">
            @error('form.yearObtained') <span class="form-error">{{ $message }}</span> @enderror
        </div>
        <x-slot:footer>
            <button type="button" class="btn btn-secondary" wire:click="closeModal">{{ __('Cancel') }}</button>
            <button type="button" class="btn btn-primary" wire:click="save">{{ __('Confirm') }}</button>
        </x-slot:footer>
    </x-ui.modal>
    @endif
</div>
