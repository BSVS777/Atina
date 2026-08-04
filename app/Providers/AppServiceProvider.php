<?php

namespace App\Providers;

use App\Docencia\Repositories\EloquentAtestadoRepository;
use App\Docencia\Repositories\EloquentAuditLogRepository;
use App\Models\User;
use Atina\Docencia\Application\Docente\Ports\AtestadoRepository;
use Atina\Docencia\Application\Docente\Ports\AuditLogRepository;
use Atina\Docencia\Domain\Docente\PoliticaAutorizacionAtestado;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;
use Illuminate\Validation\Rules\Password;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->bind(AtestadoRepository::class, EloquentAtestadoRepository::class);
        $this->app->bind(AuditLogRepository::class, EloquentAuditLogRepository::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        $this->configureDefaults();

        // DO-01-F2: adaptador delgado sobre la política de dominio — la
        // regla real (permiso `atestados.gestionar`) vive en
        // PoliticaAutorizacionAtestado, no aquí.
        Gate::define(
            'gestionar-atestados',
            fn (User $user): bool => PoliticaAutorizacionAtestado::puedeGestionar($user->permisos()),
        );
    }

    /**
     * Configure default behaviors for production-ready applications.
     */
    protected function configureDefaults(): void
    {
        Date::use(CarbonImmutable::class);

        DB::prohibitDestructiveCommands(
            app()->isProduction(),
        );

        Password::defaults(fn (): ?Password => app()->isProduction()
            ? Password::min(12)
                ->mixedCase()
                ->letters()
                ->numbers()
                ->symbols()
                ->uncompromised()
            : null,
        );
    }
}
