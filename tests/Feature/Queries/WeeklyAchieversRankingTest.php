<?php

namespace Tests\Feature\Queries;

use App\Models\AntiHabit;
use App\Models\AntiHabitRecord;
use App\Queries\WeeklyAchieversRanking;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class WeeklyAchieversRankingTest extends TestCase
{
    use RefreshDatabase;

    private function achiever(array $recordedOn, array $attributes = []): AntiHabit
    {
        $antiHabit = AntiHabit::factory()->create($attributes);
        foreach ($recordedOn as $date) {
            AntiHabitRecord::factory()->for($antiHabit)->create(['recorded_on' => $date]);
        }

        return $antiHabit;
    }

    #[Test]
    public function 月曜始まりの今週の記録だけを数える(): void
    {
        $this->travelToLocal('2026-10-07 12:00:00'); // 水曜 (月曜は 10-05)
        $this->achiever(['2026-10-04', '2026-10-05', '2026-10-06']);

        $result = (new WeeklyAchieversRanking)->get();

        $this->assertSame(2, $result[0]['weekly_days']);
    }

    #[Test]
    public function 月曜当日でも先週分を含めず今日までを数える(): void
    {
        $this->travelToLocal('2026-10-05 08:00:00'); // 月曜
        $this->achiever(['2026-10-04', '2026-10-05']);

        $this->assertSame(1, (new WeeklyAchieversRanking)->get()[0]['weekly_days']);
    }

    #[Test]
    public function 順位付けと切り詰めと非公開除外を行う(): void
    {
        $this->travelToLocal('2026-10-09 12:00:00');
        $first = $this->achiever(['2026-10-05', '2026-10-06', '2026-10-07'], ['created_at' => '2026-01-02 00:00:00']);
        $firstEarlier = $this->achiever(['2026-10-05', '2026-10-06', '2026-10-07'], ['created_at' => '2026-01-01 00:00:00']);
        $second = $this->achiever(['2026-10-05', '2026-10-06']);
        $this->achiever(['2026-10-05']); // 1位2件+2位1件で3件に達するため圏外
        $this->achiever(['2026-10-05', '2026-10-06', '2026-10-07', '2026-10-08'], ['is_public' => false]);

        $result = (new WeeklyAchieversRanking)->get();

        $this->assertSame([[1, 3], [3, 2]], array_map(fn ($r) => [$r['rank'], $r['weekly_days']], $result));
        $this->assertSame([$firstEarlier->id, $first->id], $result[0]['anti_habits']->pluck('id')->all());
        $this->assertSame([$second->id], $result[1]['anti_habits']->pluck('id')->all());
        $this->assertTrue($result[0]['anti_habits'][0]->relationLoaded('user'));
    }

    #[Test]
    public function モデルの静的メソッドは同じ結果を返す(): void
    {
        $this->travelToLocal('2026-10-07 12:00:00');
        $this->achiever(['2026-10-05']);

        $this->assertEquals((new WeeklyAchieversRanking)->get(), AntiHabit::topWeeklyAchieversWithRanks());
    }
}
