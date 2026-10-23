<?php

namespace App\Livewire;

use App\Models\AntiHabit;
use Illuminate\Contracts\View\View;
use Livewire\Component;

/** ブックマーク / ブックマーク解除ボタン */
class BookmarkButton extends Component
{
    public AntiHabit $antiHabit;

    public function toggle(): void
    {
        $user = auth()->user();
        abort_unless($user, 403);

        if ($user->hasBookmarked($this->antiHabit)) {
            $user->unbookmark($this->antiHabit);

            return;
        }

        abort_unless($user->canBookmark($this->antiHabit), 403);

        $user->bookmark($this->antiHabit);
    }

    public function render(): View
    {
        return view('livewire.bookmark-button', [
            'bookmarked' => auth()->user()->hasBookmarked($this->antiHabit),
        ]);
    }
}
