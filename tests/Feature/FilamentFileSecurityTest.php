<?php

use App\Filament\Resources\PostResource\Pages\EditPost;
use App\Filament\Resources\TextWidgetResource\Pages\EditTextWidget;
use App\Filament\Resources\TextWidgetResource\Pages\CreateTextWidget;
use App\Models\Category;
use App\Models\Post;
use App\Models\TextWidget;
use App\Models\User;
use Filament\Facades\Filament;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use Livewire\Features\SupportFileUploads\FileUploadConfiguration;
use Spatie\Permission\Models\Role;

beforeEach(function () {
    config(['filament.default_filesystem_disk' => 'public']);

    Storage::fake('public');
    Storage::fake(FileUploadConfiguration::disk());

    Role::findOrCreate('admin', 'web');

    $admin = User::factory()->create();
    $admin->assignRole('admin');

    $this->actingAs($admin);

    Filament::setCurrentPanel(
        Filament::getPanel('admin')
    );

    $this->admin = $admin;
});

function fitlifeIntegrationImage(string $path): void
{
    $file = UploadedFile::fake()->image('photo.png');

    Storage::disk('public')->put(
        $path,
        file_get_contents($file->getRealPath())
    );
}

function fitlifeIntegrationWidget(): TextWidget
{
    fitlifeIntegrationImage('widgets/original.png');

    return TextWidget::create([
        'key' => 'integration-widget',
        'title' => 'Original widget',
        'image' => 'widgets/original.png',
        'content' => '<p>Widget content</p>',
        'active' => true,
    ]);
}

it('preserves the existing widget image when saving', function () {
    $widget = fitlifeIntegrationWidget();

    Livewire::test(EditTextWidget::class, [
        'record' => $widget->getRouteKey(),
    ])
        ->fillForm(['title' => 'Updated widget'])
        ->call('save')
        ->assertHasNoFormErrors();

    $widget->refresh();

    expect($widget->title)->toBe('Updated widget');
    expect($widget->image)->toBe('widgets/original.png');

    Storage::disk('public')->assertExists(
        'widgets/original.png'
    );
});

it('preserves the existing article image when saving', function () {
    fitlifeIntegrationImage('articles/original.png');

    $category = Category::create([
        'title' => 'Test category',
        'slug' => 'test-category',
    ]);

    $post = Post::create([
        'title' => 'Original article',
        'slug' => 'original-article',
        'thumbnail' => 'articles/original.png',
        'body' => '<p>Article content</p>',
        'active' => true,
        'telegram' => false,
        'published_at' => now()->subDay(),
        'user_id' => $this->admin->id,
    ]);

    $post->categories()->attach($category->id);

    Livewire::test(EditPost::class, [
        'record' => $post->getRouteKey(),
    ])
        ->fillForm([
            'meta_title' => 'Updated meta title',
        ])
        ->call('save')
        ->assertHasNoFormErrors();

    $post->refresh();

    expect($post->thumbnail)
        ->toBe('articles/original.png');

    expect($post->meta_title)
        ->toBe('Updated meta title');

    Storage::disk('public')->assertExists(
        'articles/original.png'
    );
});

it('rejects a substituted widget image during save', function () {
    $widget = fitlifeIntegrationWidget();

    fitlifeIntegrationImage('widgets/another.png');

    Livewire::test(EditTextWidget::class, [
        'record' => $widget->getRouteKey(),
    ])
        ->set('data.image', [
            'widgets/another.png',
        ])
        ->call('save')
        ->assertHasFormErrors(['image']);

    expect($widget->refresh()->image)
        ->toBe('widgets/original.png');

    Storage::disk('public')->assertExists(
        'widgets/another.png'
    );
});

it('rejects arbitrary existing files when creating widgets', function () {
    fitlifeIntegrationImage('widgets/another.png');

    Livewire::test(CreateTextWidget::class)
        ->fillForm([
            'key' => 'unauthorized-widget',
            'title' => 'Test widget',
            'content' => '<p>Content</p>',
            'active' => true,
        ])
        ->set('data.image', [
            'widgets/another.png',
        ])
        ->call('create')
        ->assertHasFormErrors(['image']);

    expect(
        TextWidget::where(
            'key',
            'unauthorized-widget'
        )->exists()
    )->toBeFalse();
});

it('allows replacing a widget image with a new upload', function () {
    $widget = fitlifeIntegrationWidget();

    Livewire::test(EditTextWidget::class, [
        'record' => $widget->getRouteKey(),
    ])
        ->set('data.image', [
            UploadedFile::fake()->image('new.png'),
        ])
        ->call('save')
        ->assertHasNoFormErrors();

    $path = $widget->refresh()->image;

    expect($path)->toBeString();
    expect($path)->not->toBe('widgets/original.png');

    Storage::disk('public')->assertExists($path);
});

it('allows removing an optional widget image', function () {
    $widget = fitlifeIntegrationWidget();

    Livewire::test(EditTextWidget::class, [
        'record' => $widget->getRouteKey(),
    ])
        ->set('data.image', [])
        ->call('save')
        ->assertHasNoFormErrors();

    expect($widget->refresh()->image)->toBeNull();
});
