<?php

use Illuminate\Support\Facades\Blade;

it('preserves normal article formatting', function () {
    $html = Blade::render(
        '<x-safe-rich-text :html="$content" />',
        [
            'content' => '<h2>Article</h2><p><strong>Healthy food</strong></p>',
        ]
    );

    expect($html)
        ->toContain('<h2>Article</h2>')
        ->toContain('<strong>Healthy food</strong>');
});

it('preserves safe hyperlinks', function () {
    $html = Blade::render(
        '<x-safe-rich-text :html="$content" />',
        [
            'content' => '<a href="https://example.test/article">Link</a>',
        ]
    );

    expect($html)
        ->toContain('href="https://example.test/article"')
        ->toContain('Link');
});

it('preserves safe article images', function () {
    $html = Blade::render(
        '<x-safe-rich-text :html="$content" />',
        [
            'content' => '<img src="https://example.test/photo.png" alt="Photo">',
        ]
    );

    expect($html)
        ->toContain('<img')
        ->toContain('src="https://example.test/photo.png"');
});

it('removes script elements', function () {
    $html = Blade::render(
        '<x-safe-rich-text :html="$content" />',
        [
            'content' => '<p>Article</p><script>test</script>',
        ]
    );

    expect(strtolower($html))
        ->not->toContain('<script')
        ->toContain('<p>article</p>');
});

it('removes inline event handlers', function () {
    $html = Blade::render(
        '<x-safe-rich-text :html="$content" />',
        [
            'content' => '<p onclick="noop()">Text</p><img src="/storage/photo.png" onerror="noop()">',
        ]
    );

    expect(strtolower($html))
        ->not->toContain('onclick=')
        ->not->toContain('onerror=');
});

it('removes dangerous javascript links', function () {
    $html = Blade::render(
        '<x-safe-rich-text :html="$content" />',
        [
            'content' => '<a href="javascript:void(0)">Link</a>',
        ]
    );

    expect(strtolower($html))
        ->not->toContain('javascript:');
});

it('handles empty html content', function () {
    $html = Blade::render(
        '<x-safe-rich-text :html="$content" />',
        ['content' => '']
    );

    expect(trim($html))->toBe('');
});

it('handles null html content', function () {
    $html = Blade::render(
        '<x-safe-rich-text :html="$content" />',
        ['content' => null]
    );

    expect(trim($html))->toBe('');
});

it('preserves relative storage image urls', function () {
    $html = Blade::render(
        '<x-safe-rich-text :html="$content" />',
        [
            'content' => '<img src="/storage/photo.png" alt="Photo">',
        ]
    );

    expect($html)
        ->toContain('src="/storage/photo.png"');
});
