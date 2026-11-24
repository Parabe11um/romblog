<?php

use App\Filament\Forms\Components\SecureRichEditor;
use App\Models\TextWidget;
use App\Models\User;
use Filament\Forms\ComponentContainer;
use Filament\Forms\Contracts\HasForms;
use Filament\Forms\Concerns\InteractsWithForms;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use Livewire\Component;
use Livewire\Features\SupportFileUploads\FileUploadConfiguration;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;
use Spatie\Permission\Models\Role;
use Symfony\Component\HttpKernel\Exception\HttpException;

beforeEach(function () {
    Storage::fake('public');
    Storage::fake(FileUploadConfiguration::disk());
});

function fitlifeRichEditor(): SecureRichEditor
{
    $livewire = new class extends Component implements HasForms {
        use InteractsWithForms;
    };

    $container = ComponentContainer::make($livewire)
        ->model(TextWidget::class);

    $editor = SecureRichEditor::make('content')
        ->fileAttachmentsDisk('public')
        ->fileAttachmentsDirectory('rich-editor')
        ->fileAttachmentsVisibility('public');

    $editor->container($container);

    return $editor;
}

function fitlifeRichEditorAdmin(): User
{
    Role::findOrCreate('admin', 'web');

    $user = User::factory()->create();
    $user->assignRole('admin');

    return $user;
}

function fitlifeRichEditorTemporaryFile(
    UploadedFile $file,
    ?string $contents = null
): TemporaryUploadedFile {
    $disk = FileUploadConfiguration::disk();

    $name = TemporaryUploadedFile::
        generateHashNameWithOriginalNameEmbedded($file);

    Storage::disk($disk)->put(
        FileUploadConfiguration::path($name),
        $contents ?? file_get_contents($file->getRealPath())
    );

    return new TemporaryUploadedFile($name, $disk);
}

it('allows admin rich editor image attachments', function () {
    $this->actingAs(fitlifeRichEditorAdmin());

    $file = fitlifeRichEditorTemporaryFile(
        UploadedFile::fake()->image('photo.png')
    );

    $url = fitlifeRichEditor()
        ->saveUploadedFileAttachment($file);

    $files = Storage::disk('public')->allFiles();

    expect($files)->toHaveCount(1);
    expect($files[0])->toStartWith('rich-editor/');
    expect($url)->toBe(
        Storage::disk('public')->url($files[0])
    );
});

it('rejects non image rich editor attachments', function () {
    $this->actingAs(fitlifeRichEditorAdmin());

    $file = fitlifeRichEditorTemporaryFile(
        UploadedFile::fake()->create(
            'document.txt',
            10,
            'text/plain'
        ),
        'Ordinary text document'
    );

    expect(
        fn () => fitlifeRichEditor()
            ->saveUploadedFileAttachment($file)
    )->toThrow(ValidationException::class);

    expect(Storage::disk('public')->allFiles())
        ->toBeEmpty();
});

it('rejects oversized rich editor attachments', function () {
    $this->actingAs(fitlifeRichEditorAdmin());

    $image = UploadedFile::fake()->image('large.png');

    $contents = file_get_contents($image->getRealPath())
        . str_repeat("\0", 5121 * 1024);

    $file = fitlifeRichEditorTemporaryFile(
        $image,
        $contents
    );

    expect(
        fn () => fitlifeRichEditor()
            ->saveUploadedFileAttachment($file)
    )->toThrow(ValidationException::class);

    expect(Storage::disk('public')->allFiles())
        ->toBeEmpty();
});

it('blocks guest rich editor attachments', function () {
    $file = fitlifeRichEditorTemporaryFile(
        UploadedFile::fake()->image('photo.png')
    );

    expect(
        fn () => fitlifeRichEditor()
            ->saveUploadedFileAttachment($file)
    )->toThrow(HttpException::class);

    expect(Storage::disk('public')->allFiles())
        ->toBeEmpty();
});

it('blocks non admin rich editor attachments', function () {
    $this->actingAs(User::factory()->create());

    $file = fitlifeRichEditorTemporaryFile(
        UploadedFile::fake()->image('photo.png')
    );

    expect(
        fn () => fitlifeRichEditor()
            ->saveUploadedFileAttachment($file)
    )->toThrow(HttpException::class);

    expect(Storage::disk('public')->allFiles())
        ->toBeEmpty();
});
