<?php

namespace App\Livewire;

use App\Enums\ReactionKind;
use App\Models\AntiHabit;
use Illuminate\Contracts\View\View;
use Livewire\Component;

/** リアクション (👀💪🧘‍♀️🔥) の付け外しボタン */
class ReactionButtons extends Component
{
    public AntiHabit $antiHabit;

    public function toggle(string $kind): void
    {
        $user = auth()->user();
        abort_unless($user && $this->antiHabit->isVisibleTo($user), 403);

        $kind = ReactionKind::fromName($kind);
        abort_if($kind === null, 422);

        if ($user->hasReacted($this->antiHabit, $kind)) {
            $user->unreaction($this->antiHabit, $kind);
        } else {
            $user->reaction($this->antiHabit, $kind);
        }
    }

    public function render(): View
    {
        $user = auth()->user();
        $antiHabit = $this->antiHabit->unsetRelation('reactions');

        return view('livewire.reaction-buttons', [
            'kinds' => collect(ReactionKind::cases())->map(fn (ReactionKind $kind) => [
                'kind' => $kind,
                'reacted' => $user->hasReacted($antiHabit, $kind),
                'count' => $antiHabit->reactionCount($kind),
            ]),
        ]);
    }
}
