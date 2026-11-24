<?php

namespace App\Livewire;

use App\Models\Post;
use Livewire\Component;
use Illuminate\Support\Facades\RateLimiter;

class UpvoteDownvote extends Component
{
    public Post $post;

    public function mount(Post $post)
    {
        $this->post = $post;
    }

    public function render()
    {
        $upvotes = \App\Models\UpvoteDownvote::where('post_id', '=', $this->post->id)
            ->where('is_upvote', true)
            ->count();

        $downvotes = \App\Models\UpvoteDownvote::where('post_id', '=', $this->post->id)
            ->where('is_upvote', false)
            ->count();

        // The status whether current user has upvoted the post or not.
        // This will be null, true, or false
        // null means user has not done upvote or downvote
        $hasUpvote = null;

        /** @var \App\Models\User $user */
        $user = request()->user();
        if ($user) {
            $model = \App\Models\UpvoteDownvote::where('post_id', '=', $this->post->id)->where('user_id', '=', $user->id)->first();
            if ($model) {
                $hasUpvote = !!$model->is_upvote;
            }
        }

        return view('livewire.upvote-downvote', compact('upvotes', 'downvotes', 'hasUpvote'));
    }

    public function upvoteDownvote($upvote = true)
    {
        /** @var \App\Models\User $user */
        $user = request()->user();
        abort_unless(
            $user && $user->hasVerifiedEmail(),
            403
        );

        abort_unless(is_bool($upvote), 422);

        $post = $this->post->fresh();

        abort_unless(
            $post
                && $post->active
                && $post->published_at
                && $post->published_at <= now(),
            404
        );

        $key = 'post-vote:' . $user->id;

        abort_if(
            RateLimiter::tooManyAttempts($key, 30),
            429
        );

        RateLimiter::hit($key, 60);

        $model = \App\Models\UpvoteDownvote::where('post_id', '=', $this->post->id)->where('user_id', '=', $user->id)->first();

        if (!$model) {
            \App\Models\UpvoteDownvote::create([
                'is_upvote' => $upvote,
                'post_id' => $this->post->id,
                'user_id' => $user->id
            ]);

            return;
        }

        if ($upvote && $model->is_upvote || !$upvote && !$model->is_upvote) {
            $model->delete();
        } else {
            $model->is_upvote = $upvote;
            $model->save();
        }
    }
}
