<?php

namespace App\Livewire;

use App\Models\AntiHabit;
use Illuminate\Contracts\View\View;
use Livewire\Component;

/** 悪習慣一覧の検索ボックス (タイトルのオートコンプリート付き) */
class AntiHabitSearch extends Component
{
    public string $query = '';

    public function mount(): void
    {
        $this->query = (string) request()->input('q.title_or_description_cont', '');
    }

    public function select(string $title): void
    {
        $this->query = $title;
    }

    public function render(): View
    {
        $query = trim($this->query);

        return view('livewire.anti-habit-search', [
            'antiHabits' => $query === ''
                ? collect()
                : AntiHabit::query()
                    ->publiclyVisible()
                    ->titleMatching($query)
                    ->recent()
                    ->limit(10)
                    ->get(),
        ]);
    }
}
