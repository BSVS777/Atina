<?php

use App\Models\Docente;
use Illuminate\Database\Eloquent\Builder;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

new #[Title('Docentes')] class extends Component {
    use WithPagination;

    #[Url]
    public string $busqueda = '';

    public function updatingBusqueda(): void
    {
        $this->resetPage();
    }

    #[Computed]
    public function docentes()
    {
        return Docente::query()
            ->with('puesto')
            ->withCount('atestados')
            ->when($this->busqueda !== '', function (Builder $query) {
                $termino = "%{$this->busqueda}%";
                $query->where(function (Builder $query) use ($termino) {
                    $query->where('nombre', 'like', $termino)
                        ->orWhere('primer_apellido', 'like', $termino)
                        ->orWhere('segundo_apellido', 'like', $termino)
                        ->orWhere('cedula', 'like', $termino);
                });
            })
            ->orderBy('primer_apellido')
            ->orderBy('nombre')
            ->paginate(15);
    }
}; ?>

<section class="w-full space-y-6">
    <flux:heading size="xl">{{ __('Docentes') }}</flux:heading>

    <flux:input
        wire:model.live.debounce.300ms="busqueda"
        :placeholder="__('Buscar por nombre o cédula...')"
        icon="magnifying-glass"
        clearable
    />

    <flux:table>
        <flux:table.columns>
            <flux:table.column>{{ __('Cédula') }}</flux:table.column>
            <flux:table.column>{{ __('Nombre') }}</flux:table.column>
            <flux:table.column>{{ __('Puesto') }}</flux:table.column>
            <flux:table.column>{{ __('Atestados') }}</flux:table.column>
        </flux:table.columns>

        <flux:table.rows>
            @forelse ($this->docentes as $docente)
                <flux:table.row wire:key="docente-{{ $docente->id }}">
                    <flux:table.cell>{{ $docente->cedula }}</flux:table.cell>
                    <flux:table.cell>
                        <flux:link :href="route('docencia.docentes.perfil', $docente)" wire:navigate>
                            {{ $docente->nombreCompleto() }}
                        </flux:link>
                    </flux:table.cell>
                    <flux:table.cell>{{ $docente->puesto?->nombre }}</flux:table.cell>
                    <flux:table.cell>{{ $docente->atestados_count }}</flux:table.cell>
                </flux:table.row>
            @empty
                <flux:table.row>
                    <flux:table.cell colspan="4">
                        {{ __('No hay docentes que coincidan con la búsqueda.') }}
                    </flux:table.cell>
                </flux:table.row>
            @endforelse
        </flux:table.rows>
    </flux:table>

    <flux:pagination :paginator="$this->docentes" />
</section>
