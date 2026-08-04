# Atina — Módulo de Gestión Docente (Atinencias)

Módulo de evaluación de atinencia docente (UTN Sede San Carlos): registro de
atestados académicos, catálogo de atinencias versionado, verificación
automática y flujo de Nota técnica. Cubre los requisitos DO-01 a DO-02d —
ver `Docs/MATRIZ_REQUISITOS.md`.

Este repo comparte base de datos física con los otros 4 módulos del sistema
(schema entregado por el profesor: `Docs/sistema_gestion_academica_utn.sql`).

## Stack

TALL (Tailwind + Alpine.js + Laravel + Livewire) + TypeScript, Pest, MySQL 8,
arquitectura Hexagonal + DDD.

## Arquitectura

- `src/` (namespace `Atina\`) — Domain + Application, PHP puro, **no puede
  importar Illuminate/Livewire/Flux**. Verificado automáticamente por
  `tests/Architecture/DomainNeverDependsOnFrameworkTest.php` (Pest Arch).
- `app/` (namespace `App\`) — capa de Infraestructura: adaptadores Eloquent,
  Livewire, controllers, providers.
- Ver `src/Docencia/README.md` para el detalle del bounded context.

## Setup local

Requiere PHP 8.4+, Composer, Node 18+ y MySQL 8 (todo disponible vía
[Laravel Herd](https://herd.laravel.com) + MySQL Server).

```bash
composer install
npm install
cp .env.example .env
php artisan key:generate
```

Crear la base de datos y el usuario de aplicación (una sola vez):

```sql
CREATE DATABASE IF NOT EXISTS gestion_academica_utn
  DEFAULT CHARACTER SET utf8mb4 DEFAULT COLLATE utf8mb4_unicode_ci;
CREATE DATABASE IF NOT EXISTS gestion_academica_utn_test
  DEFAULT CHARACTER SET utf8mb4 DEFAULT COLLATE utf8mb4_unicode_ci;
CREATE USER IF NOT EXISTS 'atina_app'@'localhost' IDENTIFIED BY 'tu_password';
GRANT ALL PRIVILEGES ON gestion_academica_utn.* TO 'atina_app'@'localhost';
GRANT ALL PRIVILEGES ON gestion_academica_utn_test.* TO 'atina_app'@'localhost';
FLUSH PRIVILEGES;
```

Completar `DB_PASSWORD` en `.env` con la contraseña elegida arriba, luego:

```bash
php artisan migrate --seed
npm run build   # o `npm run dev` en desarrollo
composer dev    # sirve la app + queue + vite en paralelo
```

## Tests y calidad

```bash
composer test         # config:clear + pint --test + phpstan + pest
php artisan test --testsuite=Architecture   # solo el gate hexagonal
composer lint          # pint (fix)
composer types:check    # phpstan/larastan
```

`tests/Architecture` usa una base MySQL real (`gestion_academica_utn_test`,
ver `phpunit.xml`) en vez de sqlite in-memory: el schema compartido es SQL
de MySQL puro y no corre en otros motores.

## Documentación del proyecto

- `Docs/MATRIZ_REQUISITOS.md` — requisitos, reglas de negocio, trazabilidad.
- `Docs/DUDAS_LOGICA_NEGOCIO.md` — ambigüedades del enunciado y su resolución.
- `Docs/DIARIO_DECISIONES_IA.md` — decisiones tomadas con asistencia de IA.
- `AI_HARNESS.md` — metodología y reglas de trabajo con IA de este repo.
