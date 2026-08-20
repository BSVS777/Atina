<div class="space-y-6">
    <x-ui.data-table
        :headers="[
            ['key' => 'teacher', 'label' => __('Teacher'), 'sortable' => true],
            ['key' => 'group', 'label' => __('Course / context'), 'sortable' => true],
            ['key' => 'status', 'label' => __('Status'), 'sortable' => true],
            ['key' => 'result', 'label' => __('Verification result'), 'sortable' => true],
            ['key' => 'catalogCitation', 'label' => __('Catalog / justification'), 'sortable' => false],
        ]"
        :mode="$tableMode"
        :paginator="$assignments"
        :searchable="['teacher', 'group', 'status', 'result']"
        :sort-key="$sortKey"
        :sort-dir="$sortDir"
        :per-page="$perPage"
        table-cols="1.6fr 1.8fr 1fr 1.4fr 2.6fr 1.6fr"
        :can-create="$canPropose"
        :create-label="__('Propose teacher')"
        create-action="$wire.openProposeModal()"
        :title="__('Proposed assignments')">

        @forelse ($assignments as $row)
        <div class="data-row" role="row" wire:key="assignment-{{ $row['id'] }}" style="align-items: flex-start;">
            <span style="padding-top: 2px;">{{ $row['teacher'] }}</span>
            <span style="padding-top: 2px;">{{ $row['group'] }}</span>
            <span>
                <span class="status-badge {{ $row['status'] === 'confirmed' ? 'affinity-matched' : ($row['status'] === 'rejected' ? 'affinity-not-matched' : 'system') }}">
                    {{ __(ucfirst($row['status'])) }}
                </span>
            </span>
            <span style="display: flex; flex-direction: column; gap: 6px;">
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
            <span style="display: flex; flex-direction: column; gap: 6px; padding-top: 2px;">
                <span>
                    @if ($row['catalogCitation'])
                        {{ $row['catalogCitation'] }}
                    @elseif ($row['result'] === 'no_catalog')
                        {{ __('No catalog published for this course yet.') }}
                    @else
                        —
                    @endif
                </span>
                @if ($row['note'])
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
        <div class="empty-row">
            @if ($search !== '')
            {{ __('No records found') }}
            @else
            {{ __('No assignments proposed yet.') }}
            @endif
        </div>
        @endforelse
    </x-ui.data-table>

    @if ($canPropose)
    <x-ui.modal :show="$showProposeModal" close-action="closeProposeModal" :title="__('Propose teacher for a group')">
        <p style="color: var(--textSecondary); margin: 0;">
            {{ __('Select a teacher and a course group to automatically verify their academic affinity.') }}
        </p>
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

    <x-ui.modal :show="$showNoteModal" close-action="closeNoteModal" :title="__('Attach technical note (DO-02b)')">
        <p style="margin-bottom: 1rem; color: var(--textSecondary);">
            {{ __('Registers a provisional assignment justified by proven experience. The signed technical criterion is mandatory.') }}
        </p>
        <div class="form-field">
            <label for="noteDocument">{{ __('Signed technical criterion (PDF)') }}</label>

            {{--
                The <input type="file"> is kept in the DOM (visually hidden,
                NOT display:none) because it is what wire:model binds to --
                Livewire uploads straight from it. Alpine only decorates:
                it forwards dropped files into the input's FileList and
                re-dispatches a bubbling 'change' so Livewire's own listener
                fires exactly as if the user had used the native picker.
                The upload progress events (livewire-upload-*) are dispatched
                ON the input and bubble up, which is why this wrapper -- an
                ancestor of the input -- can listen for them.
            --}}
            <div class="dropzone {{ $errors->has('noteForm.document') ? 'has-error' : '' }}"
                x-data="{
                    dragging: false,
                    fileName: '',
                    uploading: false,
                    progress: 0,
                    invalid: false,
                    uploadFailed: false,
                    accept(list) {
                        if (! list || list.length === 0) return;
                        const file = list[0];
                        this.invalid = file.type !== 'application/pdf';
                        this.uploadFailed = false;
                        if (this.invalid) { this.fileName = ''; return; }
                        this.$refs.input.files = list;
                        this.fileName = file.name;
                        this.$refs.input.dispatchEvent(new Event('change', { bubbles: true }));
                    },
                }"
                :class="{ 'is-dragging': dragging, 'has-file': fileName !== '' }"
                x-on:dragover.prevent="dragging = true"
                x-on:dragleave.prevent="dragging = false"
                x-on:drop.prevent="dragging = false; accept($event.dataTransfer.files)"
                x-on:click="$refs.input.click()"
                x-on:livewire-upload-start="uploading = true; progress = 0; uploadFailed = false"
                x-on:livewire-upload-finish="uploading = false; progress = 100"
                x-on:livewire-upload-error="uploading = false; fileName = ''; uploadFailed = true"
                x-on:livewire-upload-progress="progress = $event.detail.progress">

                <input type="file" id="noteDocument" x-ref="input" class="dropzone-input"
                    wire:model="noteForm.document" accept="application/pdf"
                    x-on:change="accept($event.target.files)">

                <svg class="dropzone-icon" width="34" height="34" viewBox="0 0 24 24" fill="none"
                    stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z" />
                    <polyline points="14 2 14 8 20 8" />
                    <line x1="12" y1="18" x2="12" y2="12" />
                    <polyline points="9 15 12 12 15 15" />
                </svg>

                <div class="dropzone-text">
                    <template x-if="fileName === ''">
                        <span>
                            <strong>{{ __('Drag the PDF here') }}</strong>
                            {{ __('or click to browse') }}
                        </span>
                    </template>
                    <template x-if="fileName !== ''">
                        <span class="dropzone-file" x-text="fileName"></span>
                    </template>
                    <span class="dropzone-hint">{{ __('PDF only · 10 MB max') }}</span>
                </div>

                <div class="dropzone-progress" x-show="uploading" x-cloak>
                    <div class="dropzone-bar" :style="`width: ${progress}%`"></div>
                </div>

                <span class="form-error" x-show="invalid" x-cloak>{{ __('Only PDF files are accepted.') }}</span>
                <span class="form-error" x-show="uploadFailed" x-cloak>{{ __('The upload failed. Check the file size and try again.') }}</span>
            </div>

            @error('noteForm.document') <span class="form-error">{{ $message }}</span> @enderror
        </div>
        <div class="form-field">
            <label for="noteDeadline">{{ __('Ratification deadline') }}</label>
            <input type="date" id="noteDeadline" wire:model="noteForm.ratificationDeadline" class="{{ $errors->has('noteForm.ratificationDeadline') ? 'has-error' : '' }}">
            <span style="font-size: 12.5px; color: var(--textMuted);">{{ __('An Administrator must ratify this assignment before the deadline.') }}</span>
            @error('noteForm.ratificationDeadline') <span class="form-error">{{ $message }}</span> @enderror
        </div>
        <x-slot:footer>
            <button type="button" class="btn btn-secondary" wire:click="closeNoteModal">{{ __('Cancel') }}</button>
            <button type="button" class="btn btn-primary" wire:click="attachTechnicalNote">{{ __('Register technical note') }}</button>
        </x-slot:footer>
    </x-ui.modal>
    @endif
</div>
