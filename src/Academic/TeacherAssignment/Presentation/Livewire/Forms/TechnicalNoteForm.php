<?php

declare(strict_types=1);

namespace Src\Academic\TeacherAssignment\Presentation\Livewire\Forms;

use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;
use Livewire\Form;
use Src\Academic\TeacherAssignment\Application\DTOs\AttachTechnicalNoteDTO;
use Src\Academic\TeacherAssignment\Domain\Contracts\UploadedDocument;

class TechnicalNoteForm extends Form
{
    public ?TemporaryUploadedFile $document = null;

    public string $ratificationDeadline = '';

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'document' => ['required', 'file', 'mimes:pdf', 'max:10240'],
            'ratificationDeadline' => ['required', 'date', 'after_or_equal:today'],
        ];
    }

    public function toDto(int $teacherAssignmentId): AttachTechnicalNoteDTO
    {
        $path = $this->document->store('technical-notes', 'local');

        if ($path === false) {
            throw new \RuntimeException('Failed to store the technical note document.');
        }

        $hash = hash_file('sha256', $this->document->getRealPath());

        if ($hash === false) {
            throw new \RuntimeException('Failed to hash the technical note document.');
        }

        return new AttachTechnicalNoteDTO(
            teacherAssignmentId: $teacherAssignmentId,
            ratificationDeadline: $this->ratificationDeadline,
            document: new UploadedDocument(
                storagePath: $path,
                originalFileName: $this->document->getClientOriginalName(),
                mimeType: $this->document->getMimeType(),
                sizeBytes: $this->document->getSize(),
                hashSha256: $hash,
            ),
        );
    }
}
