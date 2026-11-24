<?php

namespace App\Livewire;

use App\Models\Comment;
use Livewire\Component;

class CommentItem extends Component
{
    public Comment $comment;

    public bool $editing = false;

    public bool $replying = false;

    protected $listeners = [
        'cancelEditing' => 'cancelEditing',
        'commentUpdated' => 'commentUpdated',
        'commentCreated' => 'commentCreated',
    ];

    public function mount(Comment $comment)
    {
        $this->comment = $comment;
    }

    public function render()
    {
        return view('livewire.comment-item');
    }

    public function deleteComment()
    {

        $user = auth()->user();
        abort_unless(
            $user
                && (int) $this->comment->user_id === (int) $user->id,
            403
        );

        abort_if($this->comment->comments()->exists(), 409);

        $id = $this->comment->id;

        $this->comment->delete();
        $this->dispatch('commentDeleted', $id);
    }

    public function startCommentEdit()
    {
        abort_unless(
            auth()->check()
                && (int) auth()->id() === (int) $this->comment->user_id,
            403
        );

        $this->editing = true;
        $this->replying = false;
    }

    public function cancelEditing()
    {
        $this->editing = false;
    }

    public function commentUpdated()
    {
        $this->editing = false;
    }

    public function startReply()
    {
        $this->replying = true;
    }

    public function commentCreated()
    {
        $this->replying = false;
    }
}
