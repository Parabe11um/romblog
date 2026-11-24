<?php

use App\Filament\Forms\Components\SecureFileUpload;
use App\Models\Post;
use App\Models\TextWidget;
use App\Models\User;
use Filament\Forms\ComponentContainer;
use Filament\Forms\Contracts\HasForms;
use Filament\Forms\Concerns\InteractsWithForms;
use Livewire\Component;

function fitlifeFileField(
    string $name,
    mixed $record
): SecureFileUpload {
    $field = SecureFileUpload::make($name);

    $livewire = new class extends Component implements HasForms {
        use InteractsWithForms;
    };

    $container = ComponentContainer::make($livewire)
        ->model($record);

    $field->container($container);

    return $field;
}

function fitlifeFilePost(): Post
{
    return Post::create([
        'title' => 'Security test article',
        'slug' => 'security-test-article',
        'thumbnail' => 'articles/original.png',
        'body' => '<p>Article</p>',
        'active' => true,
        'published_at' => now(),
        'user_id' => User::factory()->create()->id,
    ]);
}

it('allows the original article image', function () {
    $post = fitlifeFilePost();

    $field = fitlifeFileField('thumbnail', $post);

    expect(
        $field->isExistingFileAuthorized(
            'articles/original.png'
        )
    )->toBeTrue();
});

it('rejects another article image path', function () {
    $post = fitlifeFilePost();

    $field = fitlifeFileField('thumbnail', $post);

    expect(
        $field->isExistingFileAuthorized(
            'articles/another.png'
        )
    )->toBeFalse();
});

it('rejects arbitrary paths for new articles', function () {
    $field = fitlifeFileField(
        'thumbnail',
        Post::class
    );

    expect(
        $field->isExistingFileAuthorized(
            'articles/original.png'
        )
    )->toBeFalse();
});

it('rejects changes to an in-memory original file reference', function () {
    $post = fitlifeFilePost();

    $post->thumbnail = 'articles/another.png';

    $field = fitlifeFileField('thumbnail', $post);

    expect(
        $field->isExistingFileAuthorized(
            'articles/another.png'
        )
    )->toBeFalse();

    expect(
        $field->isExistingFileAuthorized(
            'articles/original.png'
        )
    )->toBeTrue();
});

it('preserves an existing widget image', function () {
    $widget = TextWidget::create([
        'key' => 'security-test-widget',
        'title' => 'Test widget',
        'image' => 'widgets/original.png',
        'content' => '<p>Widget</p>',
        'active' => true,
    ]);

    $field = fitlifeFileField('image', $widget);

    expect(
        $field->isExistingFileAuthorized(
            'widgets/original.png'
        )
    )->toBeTrue();

    expect(
        $field->isExistingFileAuthorized(
            'widgets/another.png'
        )
    )->toBeFalse();
});

it('rejects parent directory file references', function () {
    $post = fitlifeFilePost();

    $field = fitlifeFileField('thumbnail', $post);

    expect(
        $field->isExistingFileAuthorized(
            '../private/photo.png'
        )
    )->toBeFalse();
});
