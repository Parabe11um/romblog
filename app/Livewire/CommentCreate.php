<?php

namespace App\Livewire;

use App\Models\Comment;
use App\Models\Post;
use Livewire\Component;
use Illuminate\Support\Facades\RateLimiter;

class CommentCreate extends Component
{
    public string $comment = '';

    public Post $post;

    public ?Comment $commentModel = null;
    public ?Comment $parentComment = null;



    public function mount(Post $post, $commentModel = null,  $parentComment = null)
    {
        $this->post = $post;
        $this->commentModel = $commentModel;
        $this->comment = $commentModel ? $commentModel->comment : '';

        $this->parentComment = $parentComment;
    }

    public function render()
    {
        return view('livewire.comment-create');
    }

    public function createComment()
    {
        $user = auth()->user();
        abort_unless($user, 403);

        $post = $this->post->fresh();

        abort_unless(
            $post
                && $post->active
                && $post->published_at
                && $post->published_at <= now(),
            404
        );

        if ($this->commentModel) {
            abort_unless(
                (int) $this->commentModel->user_id === (int) $user->id
                    && (int) $this->commentModel->post_id === (int) $post->id,
                403
            );
        }

        if ($this->parentComment) {
            abort_unless(
                (int) $this->parentComment->post_id === (int) $post->id,
                422
            );
        }

        $this->comment = trim($this->comment);

        $this->validate([
            'comment' => ['required', 'string', 'max:3000'],
        ]);

        $key = 'comment-write:' . $user->id;

        abort_if(
            RateLimiter::tooManyAttempts($key, 10),
            429
        );

        RateLimiter::hit($key, 60);

        if ($this->commentModel) {



            $this->commentModel->comment = $this->comment;
            $this->commentModel->save();
            $this->comment = '';
            $this->dispatch('commentUpdated');
        } else {
            $comment = Comment::create([
                'comment' => $this->comment,
                'post_id' => $this->post->id,
                'user_id' => $user->id,
                'parent_id' => $this->parentComment?->id
            ]);

            $this->dispatch('commentCreated', $comment->id);
            $this->comment = '';
        }
    }
}
