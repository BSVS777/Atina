<?php

declare(strict_types=1);

namespace Src\Academic\AcademicCredential\Application\UseCases;

use Src\Academic\AcademicCredential\Domain\Contracts\AcademicCredentialRepositoryInterface;
use Src\Academic\AcademicCredential\Domain\Entities\AcademicCredential;
use Src\Academic\AcademicCredential\Domain\Exceptions\CredentialNotFoundException;

final class FindAcademicCredentialUseCase
{
    public function __construct(
        private readonly AcademicCredentialRepositoryInterface $repository,
    ) {}

    public function handle(int $id): AcademicCredential
    {
        return $this->repository->find($id) ?? throw CredentialNotFoundException::withId($id);
    }
}
