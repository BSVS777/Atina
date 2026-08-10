<?php

declare(strict_types=1);

namespace Src\Academic\AcademicCredential\Application\UseCases;

use Src\Academic\AcademicCredential\Domain\Contracts\AcademicCredentialRepositoryInterface;

final class ListAcademicCredentialsForTeacherUseCase
{
    public function __construct(
        private readonly AcademicCredentialRepositoryInterface $repository,
    ) {}

    /**
     * @return array<int, \Src\Academic\AcademicCredential\Domain\Entities\AcademicCredential>
     */
    public function handle(int $teacherId): array
    {
        return $this->repository->forTeacher($teacherId);
    }
}
