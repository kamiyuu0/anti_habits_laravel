<?php

namespace App\Livewire;

use App\Models\AntiHabit;
use App\Models\Comment;
use Illuminate\Contracts\View\View;
use Livewire\Component;

/** 応援メッセージの投稿フォームと一覧 */
class Comments extends Component
{
    public AntiHabit $antiHabit;

    public string $body = '';

    public function mount(AntiHabit $antiHabit): void
    {
        $this->antiHabit = $antiHabit;
    }

    public function save(): void
    {
        $user = auth()->user();
        abort_unless($user && $this->antiHabit->isVisibleTo($user), 403);

        $validated = $this->validate([
            'body' => ['required', 'string', 'max:'.Comment::MAX_BODY_LENGTH],
        ]);

        $comment = $this->antiHabit->comments()->make($validated);
        $comment->user()->associate($user);
        $comment->save();

        $this->antiHabit->refresh();
        $this->reset('body');
        $this->dispatch('comment-posted');
    }

    public function render(): View
    {
        return view('livewire.comments', [
            'comments' => $this->antiHabit->comments()->with('user')->latest()->get(),
        ]);
    }
}
