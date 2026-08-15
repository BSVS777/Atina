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
        :can-create="false"
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
</div>
