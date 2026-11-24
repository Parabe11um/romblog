<?php

use App\Livewire\CommentCreate;
use App\Livewire\CommentItem;
use App\Livewire\Comments;
use App\Models\Comment;
use App\Models\Post;
use App\Models\User;
use Illuminate\Support\Facades\RateLimiter;
use Livewire\Livewire;

function fitlifeCommentPost(array $attributes = []): Post
{
    return Post::create(array_merge([
        'title' => 'Test article',
        'slug' => 'article-' . \Illuminate\Support\Str::uuid(),
        'thumbnail' => 'test.png',
        'body' => '<p>Article content</p>',
        'active' => true,
        'published_at' => now()->subDay(),
        'user_id' => User::factory()->create()->id,
    ], $attributes));
}

function fitlifeCommentRecord(
    Post $post,
    User $user,
    array $attributes = []
): Comment {
    return Comment::create(array_merge([
        'comment' => 'Original comment',
        'post_id' => $post->id,
        'user_id' => $user->id,
    ], $attributes));
}

it('allows guests to view comments', function () {
    $post = fitlifeCommentPost();

    fitlifeCommentRecord(
        $post,
        User::factory()->create()
    );

    Livewire::test(Comments::class, [
        'post' => $post,
    ])
        ->assertStatus(200)
        ->assertSee('Original comment');
});

it('blocks guest comment creation', function () {
    $post = fitlifeCommentPost();

    Livewire::test(CommentCreate::class, [
        'post' => $post,
    ])
        ->set('comment', 'Test comment')
        ->call('createComment')
        ->assertStatus(403);

    expect(Comment::count())->toBe(0);
});

it('allows authenticated users to create comments', function () {
    $user = User::factory()->create();
    $post = fitlifeCommentPost();

    $this->actingAs($user);

    Livewire::test(CommentCreate::class, [
        'post' => $post,
    ])
        ->set('comment', '  Valid comment  ')
        ->call('createComment')
        ->assertHasNoErrors();

    $this->assertDatabaseHas('comments', [
        'comment' => 'Valid comment',
        'post_id' => $post->id,
        'user_id' => $user->id,
    ]);
});

it('rejects empty comments', function () {
    $this->actingAs(User::factory()->create());

    Livewire::test(CommentCreate::class, [
        'post' => fitlifeCommentPost(),
    ])
        ->set('comment', '   ')
        ->call('createComment')
        ->assertHasErrors(['comment' => 'required']);

    expect(Comment::count())->toBe(0);
});

it('rejects oversized comments', function () {
    $this->actingAs(User::factory()->create());

    Livewire::test(CommentCreate::class, [
        'post' => fitlifeCommentPost(),
    ])
        ->set('comment', str_repeat('a', 3001))
        ->call('createComment')
        ->assertHasErrors(['comment' => 'max']);

    expect(Comment::count())->toBe(0);
});

it('rejects comments on inactive articles', function () {
    $this->actingAs(User::factory()->create());

    Livewire::test(CommentCreate::class, [
        'post' => fitlifeCommentPost(['active' => false]),
    ])
        ->set('comment', 'Test comment')
        ->call('createComment')
        ->assertStatus(404);

    expect(Comment::count())->toBe(0);
});

it('rejects comments on future articles', function () {
    $this->actingAs(User::factory()->create());

    Livewire::test(CommentCreate::class, [
        'post' => fitlifeCommentPost([
            'published_at' => now()->addDay(),
        ]),
    ])
        ->set('comment', 'Test comment')
        ->call('createComment')
        ->assertStatus(404);

    expect(Comment::count())->toBe(0);
});

it('rejects replies belonging to another article', function () {
    $user = User::factory()->create();

    $this->actingAs($user);

    $first = fitlifeCommentPost();
    $second = fitlifeCommentPost();

    $parent = fitlifeCommentRecord($first, $user);

    Livewire::test(CommentCreate::class, [
        'post' => $second,
        'parentComment' => $parent,
    ])
        ->set('comment', 'Invalid reply')
        ->call('createComment')
        ->assertStatus(422);

    expect(Comment::count())->toBe(1);
});

it('allows replies to comments on the same article', function () {
    $user = User::factory()->create();
    $post = fitlifeCommentPost();

    $parent = fitlifeCommentRecord($post, $user);

    $this->actingAs($user);

    Livewire::test(CommentCreate::class, [
        'post' => $post,
        'parentComment' => $parent,
    ])
        ->set('comment', 'Valid reply')
        ->call('createComment')
        ->assertHasNoErrors();

    $this->assertDatabaseHas('comments', [
        'comment' => 'Valid reply',
        'post_id' => $post->id,
        'parent_id' => $parent->id,
    ]);
});

it('prevents editing another users comment', function () {
    $owner = User::factory()->create();
    $post = fitlifeCommentPost();

    $comment = fitlifeCommentRecord($post, $owner);

    $this->actingAs(User::factory()->create());

    Livewire::test(CommentCreate::class, [
        'post' => $post,
        'commentModel' => $comment,
    ])
        ->set('comment', 'Unauthorized change')
        ->call('createComment')
        ->assertStatus(403);

    expect($comment->refresh()->comment)
        ->toBe('Original comment');
});

it('allows owners to edit their comments', function () {
    $user = User::factory()->create();
    $post = fitlifeCommentPost();

    $comment = fitlifeCommentRecord($post, $user);

    $this->actingAs($user);

    Livewire::test(CommentCreate::class, [
        'post' => $post,
        'commentModel' => $comment,
    ])
        ->set('comment', 'Updated comment')
        ->call('createComment')
        ->assertHasNoErrors();

    expect($comment->refresh()->comment)
        ->toBe('Updated comment');
});

it('prevents deleting another users comment', function () {
    $comment = fitlifeCommentRecord(
        fitlifeCommentPost(),
        User::factory()->create()
    );

    $this->actingAs(User::factory()->create());

    Livewire::test(CommentItem::class, [
        'comment' => $comment,
    ])
        ->call('deleteComment')
        ->assertStatus(403);

    expect($comment->fresh())->not->toBeNull();
});

it('allows owners to delete comments without replies', function () {
    $user = User::factory()->create();

    $comment = fitlifeCommentRecord(
        fitlifeCommentPost(),
        $user
    );

    $this->actingAs($user);

    Livewire::test(CommentItem::class, [
        'comment' => $comment,
    ])
        ->call('deleteComment')
        ->assertHasNoErrors();

    expect($comment->fresh())->toBeNull();
});

it('rate limits comment creation', function () {
    $user = User::factory()->create();

    $this->actingAs($user);

    $key = 'comment-write:' . $user->id;

    RateLimiter::clear($key);

    for ($i = 0; $i < 10; $i++) {
        RateLimiter::hit($key, 60);
    }

    Livewire::test(CommentCreate::class, [
        'post' => fitlifeCommentPost(),
    ])
        ->set('comment', 'Rate limited comment')
        ->call('createComment')
        ->assertStatus(429);

    expect(Comment::count())->toBe(0);
});


it('prevents deleting comments with replies', function () {
    $owner = User::factory()->create();
    $post = fitlifeCommentPost();

    $parent = fitlifeCommentRecord($post, $owner);

    $reply = fitlifeCommentRecord(
        $post,
        User::factory()->create(),
        ['parent_id' => $parent->id]
    );

    $this->actingAs($owner);

    Livewire::test(CommentItem::class, [
        'comment' => $parent,
    ])
        ->call('deleteComment')
        ->assertStatus(409);

    expect($parent->fresh())->not->toBeNull();
    expect($reply->fresh())->not->toBeNull();
});

it('prevents opening another users comment editor', function () {
    $comment = fitlifeCommentRecord(
        fitlifeCommentPost(),
        User::factory()->create()
    );

    $this->actingAs(User::factory()->create());

    Livewire::test(CommentItem::class, [
        'comment' => $comment,
    ])
        ->call('startCommentEdit')
        ->assertStatus(403);
});
