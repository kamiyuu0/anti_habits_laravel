<?php

namespace Tests\Feature\Support;

use App\Models\AntiHabit;
use App\Models\AntiHabitRecord;
use App\Support\AppTime;
use App\Support\RecordHistory;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class RecordHistoryTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->travelToLocal('2026-10-07 12:00:00');
    }

    private function record(AntiHabit $antiHabit, int $daysAgo): void
    {
        AntiHabitRecord::factory()->for($antiHabit)->create([
            'recorded_on' => AppTime::today()->subDays($daysAgo)->toDateString(),
        ]);
    }

    /** @return array<string, array{array<int, int>, int}> */
    public static function streakProvider(): array
    {
        return [
            '記録なし' => [[], 0],
            '今日のみ' => [[0], 1],
            '昨日のみ(今日未記録でも継続)' => [[1], 1],
            '今日から3日連続' => [[0, 1, 2], 3],
            '昨日から2日連続' => [[1, 2], 2],
            '一昨日のみは途切れ' => [[2], 0],
            '途中で途切れる' => [[0, 1, 3, 4], 2],
            '未来の記録は数えない' => [[-1, 0], 1],
        ];
    }

    /** @param array<int, int> $daysAgo */
    #[Test]
    #[DataProvider('streakProvider')]
    public function 連続記録日数を返す(array $daysAgo, int $expected): void
    {
        $antiHabit = AntiHabit::factory()->create();
        foreach ($daysAgo as $d) {
            $this->record($antiHabit, $d);
        }

        $this->assertSame($expected, (new RecordHistory($antiHabit))->consecutiveDays());
    }

    #[Test]
    public function カレンダーは指定日数分の記録有無を古い順に返す(): void
    {
        $antiHabit = AntiHabit::factory()->create();
        $this->record($antiHabit, 0);
        $this->record($antiHabit, 2);
        $this->record($antiHabit, 10); // 範囲外

        $data = (new RecordHistory($antiHabit))->calendar(3);

        $this->assertSame([['2026-10-05', 1], ['2026-10-06', 0], ['2026-10-07', 1]], $data);
        $this->assertCount(90, (new RecordHistory($antiHabit))->calendar(90));
    }

    #[Test]
    public function モデルのメソッドは同じ結果を返す(): void
    {
        $antiHabit = AntiHabit::factory()->create();
        $this->record($antiHabit, 0);
        $this->record($antiHabit, 1);

        $history = new RecordHistory($antiHabit);
        $this->assertSame($history->consecutiveDays(), $antiHabit->consecutiveDaysAchieved());
        $this->assertSame($history->calendar(90), $antiHabit->calendarData());
    }
}
