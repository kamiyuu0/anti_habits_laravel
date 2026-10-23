<?php

namespace App\Livewire;

use App\Models\AntiHabit;
use Illuminate\Contracts\View\View;
use Livewire\Attributes\On;
use Livewire\Component;

/** 悪習慣のコメント件数。コメント投稿時に更新される */
class CommentCount extends Component
{
    public AntiHabit $antiHabit;

    #[On('comment-posted')]
    public function refreshCount(): void
    {
        $this->antiHabit->refresh();
    }

    public function render(): View
    {
        return view('livewire.comment-count');
    }
}
