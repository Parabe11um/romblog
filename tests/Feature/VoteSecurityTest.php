<?php

use App\Livewire\UpvoteDownvote;
use App\Models\UpvoteDownvote as Vote;
use App\Models\Post;
use App\Models\User;
use Illuminate\Support\Facades\RateLimiter;
use Livewire\Livewire;

function fitlifeVotePost(array $attributes = []): Post
{
    return Post::create(array_merge([
        'title' => 'Test article',
        'slug' => 'vote-' . \Illuminate\Support\Str::uuid(),
        'thumbnail' => 'test.png',
        'body' => '<p>Article content</p>',
        'active' => true,
        'published_at' => now()->subDay(),
        'user_id' => User::factory()->create()->id,
    ], $attributes));
}

it('allows guests to view vote counters', function () {
    Livewire::test(UpvoteDownvote::class, [
        'post' => fitlifeVotePost(),
    ])->assertStatus(200);
});

it('blocks guest voting', function () {
    Livewire::test(UpvoteDownvote::class, [
        'post' => fitlifeVotePost(),
    ])
        ->call('upvoteDownvote', true)
        ->assertStatus(403);

    expect(Vote::count())->toBe(0);
});

it('blocks voting without verified email', function () {
    $this->actingAs(
        User::factory()->unverified()->create()
    );

    Livewire::test(UpvoteDownvote::class, [
        'post' => fitlifeVotePost(),
    ])
        ->call('upvoteDownvote', true)
        ->assertStatus(403);

    expect(Vote::count())->toBe(0);
});

it('allows a verified user to vote', function () {
    $user = User::factory()->create();
    $post = fitlifeVotePost();

    $this->actingAs($user);

    Livewire::test(UpvoteDownvote::class, [
        'post' => $post,
    ])
        ->call('upvoteDownvote', true)
        ->assertHasNoErrors();

    $this->assertDatabaseHas('upvote_downvotes', [
        'user_id' => $user->id,
        'post_id' => $post->id,
        'is_upvote' => true,
    ]);
});

it('cancels an existing vote of the same type', function () {
    $user = User::factory()->create();
    $post = fitlifeVotePost();

    $this->actingAs($user);

    Vote::create([
        'user_id' => $user->id,
        'post_id' => $post->id,
        'is_upvote' => true,
    ]);

    Livewire::test(UpvoteDownvote::class, [
        'post' => $post,
    ])
        ->call('upvoteDownvote', true)
        ->assertHasNoErrors();

    expect(Vote::count())->toBe(0);
});

it('changes an existing vote without creating a duplicate', function () {
    $user = User::factory()->create();
    $post = fitlifeVotePost();

    $this->actingAs($user);

    Vote::create([
        'user_id' => $user->id,
        'post_id' => $post->id,
        'is_upvote' => true,
    ]);

    Livewire::test(UpvoteDownvote::class, [
        'post' => $post,
    ])
        ->call('upvoteDownvote', false)
        ->assertHasNoErrors();

    expect(Vote::count())->toBe(1);

    expect((bool) Vote::first()->is_upvote)
        ->toBeFalse();
});

it('rejects non boolean vote parameters', function () {
    $this->actingAs(User::factory()->create());

    Livewire::test(UpvoteDownvote::class, [
        'post' => fitlifeVotePost(),
    ])
        ->call('upvoteDownvote', 'invalid')
        ->assertStatus(422);

    expect(Vote::count())->toBe(0);
});

it('rejects votes on inactive articles', function () {
    $this->actingAs(User::factory()->create());

    Livewire::test(UpvoteDownvote::class, [
        'post' => fitlifeVotePost(['active' => false]),
    ])
        ->call('upvoteDownvote', true)
        ->assertStatus(404);

    expect(Vote::count())->toBe(0);
});

it('rejects votes on future articles', function () {
    $this->actingAs(User::factory()->create());

    Livewire::test(UpvoteDownvote::class, [
        'post' => fitlifeVotePost([
            'published_at' => now()->addDay(),
        ]),
    ])
        ->call('upvoteDownvote', true)
        ->assertStatus(404);

    expect(Vote::count())->toBe(0);
});

it('rate limits voting', function () {
    $user = User::factory()->create();

    $this->actingAs($user);

    $key = 'post-vote:' . $user->id;

    RateLimiter::clear($key);

    for ($i = 0; $i < 30; $i++) {
        RateLimiter::hit($key, 60);
    }

    Livewire::test(UpvoteDownvote::class, [
        'post' => fitlifeVotePost(),
    ])
        ->call('upvoteDownvote', true)
        ->assertStatus(429);

    expect(Vote::count())->toBe(0);
});
