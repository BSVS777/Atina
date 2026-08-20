<div>
    <x-ui.data-table
        :headers="[
            ['key' => 'nationalId', 'label' => __('National ID'), 'sortable' => true],
            ['key' => 'fullName', 'label' => __('Name'), 'sortable' => false],
            ['key' => 'position', 'label' => __('Position'), 'sortable' => false],
            ['key' => 'credentialsCount', 'label' => __('Credentials'), 'sortable' => false],
        ]"
        :mode="$tableMode"
        :rows="$rows ?? []"
        :searchable="['nationalId', 'fullName']"
        :paginator="$teachers ?? null"
        :sort-key="$sortKey"
        :sort-dir="$sortDir"
        :per-page="$perPage"
        table-cols="1.2fr 2fr 1.5fr 1fr"
        :can-create="Auth::user()->can('create', \App\Models\Teacher::class)"
        :create-label="__('Add teacher')"
        :can-export-pdf="Auth::user()->can('exportPdf', \App\Models\Teacher::class)"
        :can-export-excel="Auth::user()->can('exportExcel', \App\Models\Teacher::class)"
        :title="__('Teachers management')">

        @if ($tableMode === 'client')
        <template x-for="row in pageRows" :key="row.id">
            <div class="data-row" role="row">
                <span x-text="row.nationalId"></span>
                <a :href="`{{ url('academic/teachers') }}/${row.id}`" wire:navigate x-text="row.fullName" class="name-text"></a>
                <span x-text="row.position"></span>
                <span x-text="row.credentialsCount"></span>
                <div class="actions-cell"></div>
            </div>
        </template>
        <div class="empty-row" x-show="pageRows.length === 0">{{ __('No records found') }}</div>
        @else
        @forelse ($teachers as $teacher)
        <div class="data-row" role="row">
            <span>{{ $teacher->national_id }}</span>
            <a href="{{ route('academic.teacher.profile', $teacher) }}" wire:navigate class="name-text">{{ $teacher->fullName() }}</a>
            <span>{{ $teacher->position?->name }}</span>
            <span>{{ $teacher->academic_credentials_count }}</span>
            <div class="actions-cell"></div>
        </div>
        @empty
        <div class="empty-row">{{ __('No records found') }}</div>
        @endforelse
        @endif
    </x-ui.data-table>

    <x-ui.modal :show="$showModal" :title="__('Add teacher')">
        <div class="form-field">
            <label for="teacherPosition">{{ __('Position') }}</label>
            <select id="teacherPosition" wire:model="form.positionId" class="{{ $errors->has('form.positionId') ? 'has-error' : '' }}">
                <option value="">{{ __('Select a position') }}</option>
                @foreach ($positions as $position)
                <option value="{{ $position->id }}">{{ $position->name }}</option>
                @endforeach
            </select>
            @error('form.positionId') <span class="form-error">{{ $message }}</span> @enderror
        </div>
        <div class="form-field">
            <label for="teacherNationalId">{{ __('National ID') }}</label>
            <input type="text" id="teacherNationalId" wire:model="form.nationalId" class="{{ $errors->has('form.nationalId') ? 'has-error' : '' }}">
            @error('form.nationalId') <span class="form-error">{{ $message }}</span> @enderror
        </div>
        <div class="form-field">
            <label for="teacherFirstName">{{ __('First name') }}</label>
            <input type="text" id="teacherFirstName" wire:model="form.firstName" class="{{ $errors->has('form.firstName') ? 'has-error' : '' }}">
            @error('form.firstName') <span class="form-error">{{ $message }}</span> @enderror
        </div>
        <div class="form-field">
            <label for="teacherLastName">{{ __('Last name') }}</label>
            <input type="text" id="teacherLastName" wire:model="form.lastName" class="{{ $errors->has('form.lastName') ? 'has-error' : '' }}">
            @error('form.lastName') <span class="form-error">{{ $message }}</span> @enderror
        </div>
        <div class="form-field">
            <label for="teacherSecondLastName">{{ __('Second last name') }}</label>
            <input type="text" id="teacherSecondLastName" wire:model="form.secondLastName" class="{{ $errors->has('form.secondLastName') ? 'has-error' : '' }}">
            @error('form.secondLastName') <span class="form-error">{{ $message }}</span> @enderror
        </div>
        <div class="form-field">
            <label for="teacherEstimatedWorkload">{{ __('Estimated workload') }}</label>
            <input type="number" step="0.01" min="0" max="1" id="teacherEstimatedWorkload" wire:model="form.estimatedWorkload" class="{{ $errors->has('form.estimatedWorkload') ? 'has-error' : '' }}">
            @error('form.estimatedWorkload') <span class="form-error">{{ $message }}</span> @enderror
        </div>
        <div class="form-field">
            <label>
                <input type="checkbox" wire:model="form.active">
                {{ __('Active') }}
            </label>
        </div>
        <x-slot:footer>
            <button type="button" class="btn btn-secondary" wire:click="closeModal">{{ __('Cancel') }}</button>
            <button type="button" class="btn btn-primary" wire:click="save">{{ __('Add teacher') }}</button>
        </x-slot:footer>
    </x-ui.modal>
</div>
