<?php

declare(strict_types=1);

namespace Src\Academic\AffinityCatalog\Infrastructure\Persistence\Repositories;

use App\Models\AffinityCatalogVersion as AffinityCatalogVersionModel;
use App\Models\AffinityVerification as AffinityVerificationModel;
use DateTimeImmutable;
use Illuminate\Database\Eloquent\Collection;
use Src\Academic\AffinityCatalog\Domain\Contracts\AffinityCatalogVersionRepositoryInterface;
use Src\Academic\AffinityCatalog\Domain\Entities\AffinityCatalogVersion;

final class EloquentAffinityCatalogVersionRepository implements AffinityCatalogVersionRepositoryInterface
{
    public function find(int $id): ?AffinityCatalogVersion
    {
        $model = AffinityCatalogVersionModel::query()->with('especialidadesAtinentes')->find($id);

        return $model ? $this->toDomain($model) : null;
    }

    public function forCourse(int $courseId): array
    {
        /** @var Collection<int, AffinityCatalogVersionModel> $models */
        $models = AffinityCatalogVersionModel::query()
            ->with('especialidadesAtinentes')
            ->where('curso_id', $courseId)
            ->orderBy('version', 'desc')
            ->get();

        return $models->map($this->toDomain(...))->all();
    }

    public function courseIdsWithCatalog(): array
    {
        return AffinityCatalogVersionModel::query()->distinct()->pluck('curso_id')->all();
    }

    public function nextVersionNumber(int $courseId): int
    {
        $max = AffinityCatalogVersionModel::query()->where('curso_id', $courseId)->max('version');

        return $max === null ? 1 : ((int) $max) + 1;
    }

    public function save(AffinityCatalogVersion $version): AffinityCatalogVersion
    {
        $model = $version->id() !== null
            ? AffinityCatalogVersionModel::query()->findOrFail($version->id())
            : new AffinityCatalogVersionModel;

        $model->fill([
            'curso_id' => $version->courseId(),
            'version' => $version->versionNumber(),
            'acuerdo' => $version->councilAgreement(),
            'numero_gaceta' => $version->gazetteNumber(),
            'vigencia_inicio' => $version->effectiveStartDate()->format('Y-m-d'),
            'vigencia_fin' => $version->effectiveEndDate()?->format('Y-m-d'),
        ]);
        $model->save();
        $model->especialidadesAtinentes()->sync($version->specialtyIds());

        return $this->toDomain($model->load('especialidadesAtinentes'));
    }

    public function hasVerifications(int $catalogVersionId): bool
    {
        return AffinityVerificationModel::query()->where('catalogo_atinencia_id', $catalogVersionId)->exists();
    }

    private function toDomain(AffinityCatalogVersionModel $model): AffinityCatalogVersion
    {
        return new AffinityCatalogVersion(
            id: $model->id,
            courseId: $model->curso_id,
            versionNumber: $model->version,
            councilAgreement: $model->acuerdo,
            gazetteNumber: $model->numero_gaceta,
            effectiveStartDate: new DateTimeImmutable($model->vigencia_inicio->toDateString()),
            effectiveEndDate: $model->vigencia_fin !== null ? new DateTimeImmutable($model->vigencia_fin->toDateString()) : null,
            specialtyIds: $model->especialidadesAtinentes->pluck('id')->all(),
        );
    }
}
