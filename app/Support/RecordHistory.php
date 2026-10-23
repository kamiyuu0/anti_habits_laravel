<?php

namespace App\Support;

use App\Models\AntiHabit;
use Carbon\CarbonPeriod;

/**
 * 悪習慣の記録履歴 (連続日数・ヒートマップ用カレンダー) を計算する。
 */
class RecordHistory
{
    public function __construct(private readonly AntiHabit $antiHabit) {}

    /** 今日 (未記録なら昨日) から遡った連続記録日数 */
    public function consecutiveDays(): int
    {
        $startDate = $this->antiHabit->todayRecord() ? AppTime::today() : AppTime::yesterday();

        $records = $this->antiHabit->antiHabitRecords()
            ->where('recorded_on', '<=', $startDate->toDateString())
            ->orderByDesc('recorded_on')
            ->pluck('recorded_on');

        $count = 0;
        $expectedDate = $startDate;

        foreach ($records as $recordedOn) {
            if ($recordedOn->toDateString() !== $expectedDate->toDateString()) {
                break;
            }
            $count++;
            $expectedDate = $expectedDate->subDay();
        }

        return $count;
    }

    /**
     * ヒートマップ用のカレンダーデータ ([["Y-m-d", 0|1], ...])
     *
     * @return array<int, array{0: string, 1: int}>
     */
    public function calendar(int $days): array
    {
        $endDate = AppTime::today();
        $startDate = $endDate->subDays($days - 1);

        $recordedDates = $this->antiHabit->antiHabitRecords()
            ->whereBetween('recorded_on', [$startDate->toDateString(), $endDate->toDateString()])
            ->pluck('recorded_on')
            ->map(fn ($date) => $date->toDateString())
            ->flip();

        $data = [];
        foreach (CarbonPeriod::create($startDate, $endDate) as $date) {
            $key = $date->toDateString();
            $data[] = [$key, $recordedDates->has($key) ? 1 : 0];
        }

        return $data;
    }
}
