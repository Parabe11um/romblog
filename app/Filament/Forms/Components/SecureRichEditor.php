<?php

namespace App\Filament\Forms\Components;

use Filament\Facades\Filament;
use Filament\Forms\Components\RichEditor;
use Filament\Models\Contracts\FilamentUser;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;

class SecureRichEditor extends RichEditor
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->saveUploadedFileAttachmentsUsing(
            static function (
                RichEditor $component,
                TemporaryUploadedFile $file
            ): string {
                $panel = Filament::getPanel('admin');

                $user = Auth::guard(
                    $panel->getAuthGuard()
                )->user();

                abort_unless(
                    $user instanceof FilamentUser
                        && $user->canAccessPanel($panel),
                    403
                );

                Validator::make(
                    ['attachment' => $file],
                    [
                        'attachment' => [
                            'required',
                            'file',
                            'mimetypes:image/jpeg,image/png,image/webp',
                            'max:5120',
                        ],
                    ]
                )->validate();

                $method = $component->getFileAttachmentsVisibility() === 'public'
                    ? 'storePublicly'
                    : 'store';

                $path = $file->{$method}(
                    $component->getFileAttachmentsDirectory(),
                    $component->getFileAttachmentsDiskName()
                );

                if (! is_string($path) || $path === '') {
                    throw ValidationException::withMessages([
                        'attachment' => 'Unable to store attachment.',
                    ]);
                }

                return $path;
            }
        );
    }
}
