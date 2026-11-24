<?php

use App\Models\Category;
use App\Models\Post;
use App\Models\User;

function fitlifeContentPost(
    array $attributes = []
): Post {
    $category = Category::create([
        'title' => 'Health',
        'slug' => 'health',
    ]);

    $post = Post::create(array_merge([
        'title' => 'Healthy food',
        'slug' => 'healthy-food',
        'thumbnail' => 'photo.png',
        'body' => '<h2>Healthy lifestyle</h2>'
            . '<p>Article content</p>'
            . '<img src="/storage/photo.png" alt="Photo">',
        'active' => true,
        'published_at' => now()->subDay(),
        'user_id' => User::factory()->create()->id,
    ], $attributes));

    $post->categories()->attach($category->id);

    return $post;
}

it('renders a published article with safe html and images', function () {
    $post = fitlifeContentPost();

    $this->withHeader('User-Agent', 'Fitlife Security Test')
        ->get('/' . $post->slug)
        ->assertOk()
        ->assertSee('<h2>Healthy lifestyle</h2>', false)
        ->assertSee('src="/storage/photo.png"', false)
        ->assertSee('Article content');
});

it('sanitizes article html on the full public page', function () {
    $post = fitlifeContentPost([
        'body' => '<p>Visible article</p>'
            . '<script>security_test_marker</script>',
    ]);

    $this->withHeader('User-Agent', 'Fitlife Security Test')
        ->get('/' . $post->slug)
        ->assertOk()
        ->assertSee('Visible article')
        ->assertDontSee(
            '<script>security_test_marker</script>',
            false
        );

    expect($post->refresh()->body)
        ->toContain('<script>security_test_marker</script>');
});

it('finds published articles and preserves search highlighting', function () {
    fitlifeContentPost();

    $this->get('/search?q=food')
        ->assertOk()
        ->assertSee(
            '<span class="bg-yellow-300">food</span>',
            false
        );
});

it('does not return inactive articles in search results', function () {
    fitlifeContentPost([
        'title' => 'Hidden article marker',
        'active' => false,
    ]);

    $this->get('/search?q=Hidden')
        ->assertOk()
        ->assertDontSee('Hidden article marker');
});
