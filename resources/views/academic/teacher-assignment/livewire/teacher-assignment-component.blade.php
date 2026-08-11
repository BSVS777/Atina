<div class="space-y-6">
    <div class="card">
        <div class="card-head">
            <span class="card-title">{{ __('Proposed assignments') }}</span>
            @if ($canPropose)
            <div class="card-actions">
                <button type="button" class="btn btn-orange" wire:click="openProposeModal">
                    <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round">
                        <line x1="12" y1="5" x2="12" y2="19"></line>
                        <line x1="5" y1="12" x2="19" y2="12"></line>
                    </svg>
                    <span>{{ __('Propose teacher') }}</span>
                </button>
            </div>
            @endif
        </div>

        <div class="table-scroll">
            <div class="table-inner" style="--table-cols: 1.6fr 2fr 1fr 1.4fr 2.2fr 2fr;" role="table">
                <div class="data-row data-row-head" role="row">
                    <span role="columnheader">{{ __('Teacher') }}</span>
                    <span role="columnheader">{{ __('Course / context') }}</span>
                    <span role="columnheader">{{ __('Status') }}</span>
                    <span role="columnheader">{{ __('Verification result') }}</span>
                    <span role="columnheader">{{ __('Catalog / justification') }}</span>
                    <span role="columnheader">{{ __('Actions') }}</span>
                </div>

                @forelse ($rows as $row)
                <div class="data-row" role="row" wire:key="assignment-{{ $row['id'] }}">
                    <span>{{ $row['teacher'] }}</span>
                    <span>{{ $row['group'] }}</span>
                    <span>
                        <span class="status-badge {{ $row['status'] === 'confirmed' ? 'affinity-matched' : ($row['status'] === 'rejected' ? 'affinity-not-matched' : 'system') }}">
                            {{ __(ucfirst($row['status'])) }}
                        </span>
                    </span>
                    <span>
                        @if ($row['result'] === 'matched')
                        <span class="status-badge affinity-matched">{{ __('Atinente') }}</span>
                        @elseif ($row['result'] === 'not_matched')
                        <span class="status-badge affinity-not-matched">{{ __('No Atinente') }}</span>
                        @elseif ($row['result'] === 'technical_note')
                        <span class="status-badge affinity-technical-note">{{ __('Nota técnica') }}</span>
                        @elseif ($row['result'] === 'no_catalog')
                        <span class="status-badge affinity-no-catalog">{{ __('Sin catálogo') }}</span>
                        @endif
                        @if ($row['isProvisional'])
                        <span class="status-badge system">{{ __('Provisional') }}</span>
                        @endif
                    </span>
                    <span>
                        @if ($row['catalogCitation'])
                            {{ $row['catalogCitation'] }}
                        @elseif ($row['result'] === 'no_catalog')
                            {{ __('No catalog published for this course yet.') }}
                        @else
                            —
                        @endif
                        @if ($row['note'])
                        <br>
                        <span class="status-badge {{ $row['note']['status'] === 'ratified' ? 'affinity-matched' : ($row['note']['status'] === 'expired' || $row['note']['status'] === 'rejected' ? 'affinity-not-matched' : 'affinity-technical-note') }}">
                            {{ __('Technical note') }}: {{ __(ucfirst(str_replace('_', ' ', $row['note']['status']))) }} ({{ $row['note']['deadline'] }})
                        </span>
                        @endif
                    </span>
                    <div class="actions-cell" style="flex-wrap: wrap;">
                        @if ($row['canAttachNote'])
                        <button type="button" class="btn btn-secondary" wire:click="openNoteModal({{ $row['id'] }})">{{ __('Attach technical note') }}</button>
                        @endif
                        @if ($row['canDecideNoCatalog'] && $canDecide)
                        <button type="button" class="btn btn-primary" wire:click="approveNoCatalog({{ $row['id'] }})">{{ __('Approve') }}</button>
                        <button type="button" class="btn btn-secondary" wire:click="rejectNoCatalog({{ $row['id'] }})">{{ __('Reject') }}</button>
                        @endif
                        @if ($row['note'] && $row['note']['isPending'] && $canApproveNote)
                        <button type="button" class="btn btn-primary" wire:click="ratifyNote({{ $row['note']['id'] }})">{{ __('Ratify') }}</button>
                        <button type="button" class="btn btn-secondary" wire:click="rejectNote({{ $row['note']['id'] }})">{{ __('Reject note') }}</button>
                        @endif
                    </div>
                </div>
                @empty
                <div class="empty-row">{{ __('No assignments proposed yet.') }}</div>
                @endforelse
            </div>
        </div>
    </div>

    @if ($canPropose)
    <x-ui.modal :show="$showProposeModal" :title="__('Propose teacher for a group')">
        <div class="form-field">
            <label for="assignmentTeacher">{{ __('Teacher') }}</label>
            <select id="assignmentTeacher" wire:model="proposeForm.teacherId" class="{{ $errors->has('proposeForm.teacherId') ? 'has-error' : '' }}">
                <option value="">{{ __('Select a teacher') }}</option>
                @foreach ($teachers as $teacher)
                <option value="{{ $teacher->id }}">{{ $teacher->fullName() }} ({{ $teacher->national_id }})</option>
                @endforeach
            </select>
            @error('proposeForm.teacherId') <span class="form-error">{{ $message }}</span> @enderror
        </div>
        <div class="form-field">
            <label for="assignmentGroup">{{ __('Course group') }}</label>
            <select id="assignmentGroup" wire:model="proposeForm.courseGroupId" class="{{ $errors->has('proposeForm.courseGroupId') ? 'has-error' : '' }}">
                <option value="">{{ __('Select a course group') }}</option>
                @foreach ($groups as $group)
                <option value="{{ $group->id }}">{{ $group->label() }}</option>
                @endforeach
            </select>
            @error('proposeForm.courseGroupId') <span class="form-error">{{ $message }}</span> @enderror
        </div>
        <x-slot:footer>
            <button type="button" class="btn btn-secondary" wire:click="closeProposeModal">{{ __('Cancel') }}</button>
            <button type="button" class="btn btn-primary" wire:click="propose">{{ __('Verify affinity') }}</button>
        </x-slot:footer>
    </x-ui.modal>

    <x-ui.modal :show="$showNoteModal" :title="__('Attach technical note (DO-02b)')">
        <p style="margin-bottom: 1rem; color: var(--textSecondary);">
            {{ __('Registers a provisional assignment justified by proven experience. The signed technical criterion is mandatory.') }}
        </p>
        <div class="form-field">
            <label for="noteDocument">{{ __('Signed technical criterion (PDF)') }}</label>
            <input type="file" id="noteDocument" wire:model="noteForm.document" accept="application/pdf" class="{{ $errors->has('noteForm.document') ? 'has-error' : '' }}">
            @error('noteForm.document') <span class="form-error">{{ $message }}</span> @enderror
        </div>
        <div class="form-field">
            <label for="noteDeadline">{{ __('Ratification deadline') }}</label>
            <input type="date" id="noteDeadline" wire:model="noteForm.ratificationDeadline" class="{{ $errors->has('noteForm.ratificationDeadline') ? 'has-error' : '' }}">
            @error('noteForm.ratificationDeadline') <span class="form-error">{{ $message }}</span> @enderror
        </div>
        <x-slot:footer>
            <button type="button" class="btn btn-secondary" wire:click="closeNoteModal">{{ __('Cancel') }}</button>
            <button type="button" class="btn btn-primary" wire:click="attachTechnicalNote">{{ __('Register technical note') }}</button>
        </x-slot:footer>
    </x-ui.modal>
    @endif
</div>
