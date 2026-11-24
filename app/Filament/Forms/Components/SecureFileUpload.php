<?php

namespace App\Filament\Forms\Components;

use Closure;
use Filament\Forms\Components\FileUpload;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;

class SecureFileUpload extends FileUpload
{
    public function isExistingFileAuthorized(string $path): bool
    {
        $record = $this->getRecord();

        if (! $record || ! $record->exists) {
            return false;
        }

        $original = $record->getRawOriginal(
            $this->getName()
        );

        return is_string($original)
            && $original !== ''
            && $original === $path;
    }

    public function getValidationRules(): array
    {
        return [
            function (
                string $attribute,
                mixed $value,
                Closure $fail
            ): void {
                if ($value === null) {
                    return;
                }

                if (! is_array($value)) {
                    $fail('Invalid file upload state.');

                    return;
                }

                foreach ($value as $file) {
                    if ($file instanceof TemporaryUploadedFile) {
                        continue;
                    }

                    if (
                        ! is_string($file)
                        || ! $this->isExistingFileAuthorized($file)
                    ) {
                        $fail('Unauthorized file reference.');

                        return;
                    }
                }
            },
            ...parent::getValidationRules(),
        ];
    }

    public function getUploadedFiles(): ?array
    {
        foreach ($this->getState() ?? [] as $file) {
            if ($file instanceof TemporaryUploadedFile) {
                continue;
            }

            abort_unless(
                is_string($file)
                    && $this->isExistingFileAuthorized($file),
                403
            );
        }

        return parent::getUploadedFiles();
    }

    public function removeUploadedFile(
        string $fileKey
    ): string | TemporaryUploadedFile | null {
        $file = ($this->getState() ?? [])[$fileKey] ?? null;

        if (is_string($file)) {
            abort_unless(
                $this->isExistingFileAuthorized($file),
                403
            );
        }

        return parent::removeUploadedFile($fileKey);
    }
}
