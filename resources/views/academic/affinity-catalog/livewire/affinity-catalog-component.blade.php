<div class="space-y-6">
    <div class="card" style="padding: 1.25rem 1.5rem;">
        <div class="form-field" style="margin-bottom: 0;">
            <label for="catalogCourseSelect">{{ __('Course') }}</label>
            <select id="catalogCourseSelect" wire:model.live="selectedCourseId">
                @foreach ($courses as $course)
                <option value="{{ $course->id }}">{{ $course->code }} — {{ $course->name }} ({{ $course->career->name }})</option>
                @endforeach
            </select>
        </div>
    </div>

    <x-ui.data-table
        :headers="[
            ['key' => 'version', 'label' => __('Version'), 'sortable' => true],
            ['key' => 'councilAgreement', 'label' => __('Council agreement'), 'sortable' => true],
            ['key' => 'gazetteNumber', 'label' => __('Gazette number'), 'sortable' => false],
            ['key' => 'effectiveStartDate', 'label' => __('Effective from'), 'sortable' => true],
            ['key' => 'effectiveEndDate', 'label' => __('Effective until'), 'sortable' => false],
            ['key' => 'specialties', 'label' => __('Affine specialties'), 'sortable' => false],
        ]"
        :mode="$tableMode"
        :paginator="$versions"
        :searchable="['version', 'councilAgreement', 'gazetteNumber', 'effectiveStartDate', 'specialties']"
        :sort-key="$sortKey"
        :sort-dir="$sortDir"
        :per-page="$perPage"
        table-cols="0.6fr 1.4fr 1fr 1.2fr 1.2fr 2.6fr 0.6fr"
        :can-create="$canManage"
        :create-label="__('New version')"
        :can-export-pdf="Auth::user()->can('exportPdf', \Src\Academic\AffinityCatalog\Domain\Entities\AffinityCatalogVersion::class)"
        :can-export-excel="Auth::user()->can('exportExcel', \Src\Academic\AffinityCatalog\Domain\Entities\AffinityCatalogVersion::class)"
        :title="$selectedCourse ? $selectedCourse->code . ' — ' . $selectedCourse->name : __('Catalog versions')">

        @forelse ($versions as $row)
        <div class="data-row" role="row" wire:key="catalog-version-{{ $row['id'] }}">
            <span><span class="status-badge">{{ __('v:number', ['number' => $row['version']]) }}</span></span>
            <span>{{ $row['councilAgreement'] }}</span>
            <span>{{ $row['gazetteNumber'] }}</span>
            <span>{{ $row['effectiveStartDate'] }}</span>
            <span>{{ $row['effectiveEndDate'] ?? __('Indefinite') }}</span>
            <span>{{ $row['specialties'] }}</span>
            <div class="actions-cell"></div>
        </div>
        @empty
        <div class="empty-row">
            @if ($search !== '')
            {{ __('No records found') }}
            @else
            {{ __('This course has no affinity catalog published yet — verifications will be flagged "No catalog".') }}
            @endif
        </div>
        @endforelse
    </x-ui.data-table>

    @if ($canManage)
    <x-ui.modal :show="$showModal" :title="__('New catalog version')">
        <div class="form-field">
            <label for="catalogVersionCourse">{{ __('Course') }}</label>
            <select id="catalogVersionCourse" wire:model="form.courseId" class="{{ $errors->has('form.courseId') ? 'has-error' : '' }}">
                @foreach ($courses as $course)
                <option value="{{ $course->id }}">{{ $course->code }} — {{ $course->name }}</option>
                @endforeach
            </select>
            @error('form.courseId') <span class="form-error">{{ $message }}</span> @enderror
        </div>
        <div>
            <span class="control-group" style="margin-bottom:10px;">{{ __('Legal documentation') }}</span>
            <div class="form-field" style="margin-bottom:14px;">
                <label for="catalogVersionAgreement">{{ __('Council agreement') }}</label>
                <input type="text" id="catalogVersionAgreement" wire:model="form.councilAgreement" class="{{ $errors->has('form.councilAgreement') ? 'has-error' : '' }}">
                @error('form.councilAgreement') <span class="form-error">{{ $message }}</span> @enderror
            </div>
            <div class="form-field">
                <label for="catalogVersionGazette">{{ __('Gazette number') }}</label>
                <input type="text" id="catalogVersionGazette" wire:model="form.gazetteNumber" class="{{ $errors->has('form.gazetteNumber') ? 'has-error' : '' }}">
                @error('form.gazetteNumber') <span class="form-error">{{ $message }}</span> @enderror
            </div>
        </div>

        <div style="border-top:1px solid var(--border); padding-top:18px;">
            <span class="control-group" style="margin-bottom:10px;">{{ __('Validity period') }}</span>
            <div class="form-field" style="margin-bottom:14px;">
                <label for="catalogVersionStart">{{ __('Effective from') }}</label>
                <input type="date" id="catalogVersionStart" wire:model="form.effectiveStartDate" class="{{ $errors->has('form.effectiveStartDate') ? 'has-error' : '' }}">
                @error('form.effectiveStartDate') <span class="form-error">{{ $message }}</span> @enderror
            </div>
            <div class="form-field">
                <label for="catalogVersionEnd">{{ __('Effective until (optional)') }}</label>
                <input type="date" id="catalogVersionEnd" wire:model="form.effectiveEndDate">
                @error('form.effectiveEndDate') <span class="form-error">{{ $message }}</span> @enderror
            </div>
        </div>

        <div style="border-top:1px solid var(--border); padding-top:18px;" x-data="{
            specialtySearch: '',
            catalog: @js($specialties->map(fn ($specialty) => ['id' => $specialty->id, 'name' => $specialty->name])->values()),
            get filteredIds() {
                const q = this.specialtySearch.trim().toLowerCase();
                if (q === '') return this.catalog.map((specialty) => specialty.id);
                return this.catalog.filter((specialty) => specialty.name.toLowerCase().includes(q)).map((specialty) => specialty.id);
            },
        }">
            <span class="control-group" style="margin-bottom:10px;">{{ __('Affine specialties') }}</span>
            @if ($specialties->count() > 8)
            <input type="text" class="list-filter-input" x-model.debounce.100ms="specialtySearch" placeholder="{{ __('Search specialty...') }}">
            @endif
            <div class="permissions-list">
                @forelse ($specialties as $specialty)
                <label class="permission-item" x-show="filteredIds.includes({{ $specialty->id }})">
                    <input type="checkbox" value="{{ $specialty->id }}" wire:model="form.specialtyIds">
                    <span>{{ $specialty->name }}</span>
                </label>
                @empty
                <div class="permission-empty">{{ __('No specialties available') }}</div>
                @endforelse
            </div>
            @error('form.specialtyIds') <span class="form-error">{{ $message }}</span> @enderror
        </div>

        <x-slot:footer>
            <button type="button" class="btn btn-secondary" wire:click="closeModal">{{ __('Cancel') }}</button>
            <button type="button" class="btn btn-primary" wire:click="save">{{ __('Confirm') }}</button>
        </x-slot:footer>
    </x-ui.modal>
    @endif
</div>
