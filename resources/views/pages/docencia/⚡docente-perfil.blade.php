<?php

use App\Models\Docente;
use App\Models\Especialidad as EspecialidadModelo;
use Atina\Docencia\Application\Docente\Exceptions\AtestadoDuplicadoException;
use Atina\Docencia\Application\Docente\Exceptions\AutorizacionDenegadaException;
use Atina\Docencia\Application\Docente\UseCases\EditarAtestadoAcademico;
use Atina\Docencia\Application\Docente\UseCases\RegistrarAtestadoAcademico;
use Atina\Docencia\Domain\Docente\AnioObtencion;
use Atina\Docencia\Domain\Docente\Especialidad as EspecialidadDominio;
use Atina\Docencia\Domain\Docente\GradoAcademico;
use Flux\Flux;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Title;
use Livewire\Component;

new #[Title('Perfil docente')] class extends Component {
    public Docente $docente;

    public ?int $atestadoIdEnEdicion = null;

    public ?int $especialidadId = null;

    public string $grado = '';

    public string $institucion = '';

    public ?int $anioObtencion = null;

    public function mount(Docente $docente): void
    {
        $this->docente = $docente;
    }

    #[Computed]
    public function atestados()
    {
        return $this->docente->atestados()->with('especialidad')->orderBy('anio_obtencion', 'desc')->get();
    }

    #[Computed]
    public function especialidades()
    {
        return EspecialidadModelo::query()->orderBy('nombre')->get();
    }

    #[Computed]
    public function grados(): array
    {
        return GradoAcademico::cases();
    }

    #[Computed]
    public function puedeGestionar(): bool
    {
        return Gate::allows('gestionar-atestados');
    }

    protected function rules(): array
    {
        return [
            'especialidadId' => ['required', 'integer', 'exists:especialidades,id'],
            'grado' => ['required', Rule::enum(GradoAcademico::class)],
            'institucion' => ['required', 'string', 'max:150'],
            'anioObtencion' => ['required', 'integer', 'min:1950', 'max:'.date('Y')],
        ];
    }

    public function abrirCrear(): void
    {
        Gate::authorize('gestionar-atestados');

        $this->reset(['atestadoIdEnEdicion', 'especialidadId', 'grado', 'institucion', 'anioObtencion']);
        $this->resetErrorBag();

        Flux::modal('atestado-form')->show();
    }

    public function abrirEditar(int $atestadoId): void
    {
        Gate::authorize('gestionar-atestados');

        $atestado = $this->docente->atestados()->findOrFail($atestadoId);

        $this->atestadoIdEnEdicion = $atestado->id;
        $this->especialidadId = $atestado->especialidad_id;
        $this->grado = $atestado->grado->value;
        $this->institucion = $atestado->institucion;
        $this->anioObtencion = $atestado->anio_obtencion;
        $this->resetErrorBag();

        Flux::modal('atestado-form')->show();
    }

    public function guardar(RegistrarAtestadoAcademico $registrar, EditarAtestadoAcademico $editar): void
    {
        Gate::authorize('gestionar-atestados');

        $this->validate();

        $especialidadModelo = EspecialidadModelo::findOrFail($this->especialidadId);
        $especialidad = new EspecialidadDominio($especialidadModelo->id, $especialidadModelo->nombre);
        $gradoAcademico = GradoAcademico::from($this->grado);
        $anio = new AnioObtencion($this->anioObtencion);
        $actor = Auth::user();

        try {
            if ($this->atestadoIdEnEdicion === null) {
                $registrar->ejecutar($actor->id, $actor->permisos(), $this->docente->id, $especialidad, $gradoAcademico, $this->institucion, $anio);
            } else {
                $editar->ejecutar($actor->id, $actor->permisos(), $this->atestadoIdEnEdicion, $especialidad, $gradoAcademico, $this->institucion, $anio);
            }
        } catch (AutorizacionDenegadaException|AtestadoDuplicadoException $e) {
            $this->addError('form', $e->getMessage());

            return;
        }

        unset($this->atestados);
        Flux::modal('atestado-form')->close();
        Flux::toast(variant: 'success', text: __('Atestado guardado correctamente.'));
    }
}; ?>

<section class="w-full space-y-6">
    <div>
        <flux:heading size="xl">{{ $docente->nombreCompleto() }}</flux:heading>
        <flux:subheading>
            {{ __('Cédula') }}: {{ $docente->cedula }} · {{ $docente->puesto?->nombre }}
        </flux:subheading>
    </div>

    <div class="flex items-center justify-between">
        <flux:heading size="lg">{{ __('Atestados académicos') }}</flux:heading>

        @if ($this->puedeGestionar)
            <flux:button variant="primary" icon="plus" wire:click="abrirCrear">
                {{ __('Nuevo atestado') }}
            </flux:button>
        @endif
    </div>

    <flux:table>
        <flux:table.columns>
            <flux:table.column>{{ __('Grado') }}</flux:table.column>
            <flux:table.column>{{ __('Especialidad') }}</flux:table.column>
            <flux:table.column>{{ __('Institución') }}</flux:table.column>
            <flux:table.column>{{ __('Año') }}</flux:table.column>
            @if ($this->puedeGestionar)
                <flux:table.column>{{ __('Acciones') }}</flux:table.column>
            @endif
        </flux:table.columns>

        <flux:table.rows>
            @forelse ($this->atestados as $atestado)
                <flux:table.row wire:key="atestado-{{ $atestado->id }}">
                    <flux:table.cell>{{ $atestado->grado->value }}</flux:table.cell>
                    <flux:table.cell>{{ $atestado->especialidad->nombre }}</flux:table.cell>
                    <flux:table.cell>{{ $atestado->institucion }}</flux:table.cell>
                    <flux:table.cell>{{ $atestado->anio_obtencion }}</flux:table.cell>
                    @if ($this->puedeGestionar)
                        <flux:table.cell>
                            <flux:button size="sm" variant="ghost" icon="pencil" wire:click="abrirEditar({{ $atestado->id }})">
                                {{ __('Editar') }}
                            </flux:button>
                        </flux:table.cell>
                    @endif
                </flux:table.row>
            @empty
                <flux:table.row>
                    <flux:table.cell colspan="{{ $this->puedeGestionar ? 5 : 4 }}">
                        {{ __('Este docente todavía no tiene atestados registrados.') }}
                    </flux:table.cell>
                </flux:table.row>
            @endforelse
        </flux:table.rows>
    </flux:table>

    @if ($this->puedeGestionar)
        <flux:modal name="atestado-form" focusable class="max-w-lg">
            <form wire:submit="guardar" class="space-y-6">
                <flux:heading size="lg">
                    {{ $atestadoIdEnEdicion === null ? __('Nuevo atestado') : __('Editar atestado') }}
                </flux:heading>

                @error('form')
                    <flux:callout variant="danger" :text="$message" />
                @enderror

                <flux:select wire:model="especialidadId" :label="__('Especialidad')" placeholder="{{ __('Selecciona una especialidad') }}">
                    @foreach ($this->especialidades as $especialidad)
                        <flux:select.option value="{{ $especialidad->id }}">{{ $especialidad->nombre }}</flux:select.option>
                    @endforeach
                </flux:select>

                <flux:select wire:model="grado" :label="__('Grado académico')" placeholder="{{ __('Selecciona un grado') }}">
                    @foreach ($this->grados as $gradoOpcion)
                        <flux:select.option value="{{ $gradoOpcion->value }}">{{ $gradoOpcion->value }}</flux:select.option>
                    @endforeach
                </flux:select>

                <flux:input wire:model="institucion" :label="__('Institución')" type="text" />

                <flux:input wire:model="anioObtencion" :label="__('Año de obtención')" type="number" min="1950" max="{{ date('Y') }}" />

                <div class="flex justify-end space-x-2 rtl:space-x-reverse">
                    <flux:modal.close>
                        <flux:button variant="filled">{{ __('Cancelar') }}</flux:button>
                    </flux:modal.close>

                    <flux:button variant="primary" type="submit">{{ __('Guardar') }}</flux:button>
                </div>
            </form>
        </flux:modal>
    @endif
</section>
