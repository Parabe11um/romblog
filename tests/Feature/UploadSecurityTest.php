<?php

use App\Models\User;
use App\Http\Middleware\EnsureAdminFileUpload;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\URL;
use Livewire\Features\SupportFileUploads\FileUploadConfiguration;
use Spatie\Permission\Models\Role;

beforeEach(function () {
    $this->uploadDisk = FileUploadConfiguration::disk();

    Storage::fake($this->uploadDisk);
});

function fitlifeSignedUploadUrl(): string
{
    return URL::temporarySignedRoute(
        'livewire.upload-file',
        now()->addMinutes(5),
        [],
        absolute: false
    );
}

function fitlifeUploadAdmin(): User
{
    $user = User::factory()->create();

    Role::findOrCreate('admin', 'web');

    $user->assignRole('admin');

    return $user;
}

it('blocks guest file uploads', function () {
    $this->postJson(fitlifeSignedUploadUrl(), [
        'files' => [
            UploadedFile::fake()->image('photo.png'),
        ],
    ])->assertForbidden();

    expect(Storage::disk($this->uploadDisk)->allFiles())
        ->toBeEmpty();
});

it('blocks non admin file uploads', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->postJson(fitlifeSignedUploadUrl(), [
            'files' => [
                UploadedFile::fake()->image('photo.png'),
            ],
        ])->assertForbidden();

    expect(Storage::disk($this->uploadDisk)->allFiles())
        ->toBeEmpty();
});

it('allows admin png uploads', function () {
    $this->actingAs(fitlifeUploadAdmin())
        ->postJson(fitlifeSignedUploadUrl(), [
            'files' => [
                UploadedFile::fake()->image('photo.png'),
            ],
        ])
        ->assertOk()
        ->assertJsonCount(1, 'paths');

    expect(Storage::disk($this->uploadDisk)->allFiles())
        ->not->toBeEmpty();
});

it('rejects invalid file types for admins', function () {
    $this->actingAs(fitlifeUploadAdmin())
        ->postJson(fitlifeSignedUploadUrl(), [
            'files' => [
                UploadedFile::fake()->create(
                    'document.txt',
                    10,
                    'text/plain'
                ),
            ],
        ])->assertUnprocessable();

    expect(Storage::disk($this->uploadDisk)->allFiles())
        ->toBeEmpty();
});

it('rejects oversized images for admins', function () {
    $this->actingAs(fitlifeUploadAdmin())
        ->postJson(fitlifeSignedUploadUrl(), [
            'files' => [
                UploadedFile::fake()
                    ->image('large.png')
                    ->size(5121),
            ],
        ])->assertUnprocessable();

    expect(Storage::disk($this->uploadDisk)->allFiles())
        ->toBeEmpty();
});

it('rejects unsigned admin uploads', function () {
    $this->actingAs(fitlifeUploadAdmin())
        ->postJson('/livewire/upload-file', [
            'files' => [
                UploadedFile::fake()->image('photo.png'),
            ],
        ])->assertUnauthorized();

    expect(Storage::disk($this->uploadDisk)->allFiles())
        ->toBeEmpty();
});

it('protects the upload route without restricting public livewire updates', function () {
    $routes = Route::getRoutes();

    $upload = $routes->getByName('livewire.upload-file');

    $update = $routes->getByName('default.livewire.update');

    expect($upload)->not->toBeNull();

    expect($update)->not->toBeNull();

    expect($upload->gatherMiddleware())
        ->toContain(EnsureAdminFileUpload::class);

    expect($update->gatherMiddleware())
        ->not->toContain(EnsureAdminFileUpload::class);
});
