<?php

namespace Tests\Feature\Models;

use App\Models\AntiHabit;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class AntiHabitSearchScopeTest extends TestCase
{
    use RefreshDatabase;

    private function habit(string $title, string $description = '', array $tags = []): AntiHabit
    {
        $antiHabit = AntiHabit::factory()->create(['title' => $title, 'description' => $description]);
        $antiHabit->syncTagNames(implode(',', $tags));

        return $antiHabit;
    }

    #[Test]
    public function 条件が空なら絞り込まない(): void
    {
        $this->habit('A');
        $this->habit('B');

        $this->assertCount(2, AntiHabit::query()->searchedBy()->get());
        $this->assertCount(2, AntiHabit::query()->searchedBy('', '')->get());
    }

    #[Test]
    public function キーワードとタグを組み合わせて絞り込む(): void
    {
        $match = $this->habit('夜更かし', '', ['睡眠']);
        $this->habit('夜食', '', ['食事']);
        $this->habit('朝寝坊', '', ['睡眠']);

        $this->assertSame([$match->id], AntiHabit::query()->searchedBy('夜', '睡眠')->pluck('id')->all());
        $this->assertCount(2, AntiHabit::query()->searchedBy('夜')->get());
        $this->assertCount(2, AntiHabit::query()->searchedBy('', '睡眠')->get());
    }

    #[Test]
    public function キーワードは説明文にも一致する(): void
    {
        $match = $this->habit('A', 'スマホを見すぎる');
        $this->habit('B', '関係ない');

        $this->assertSame([$match->id], AntiHabit::query()->searchedBy('スマホ')->pluck('id')->all());
    }
}
