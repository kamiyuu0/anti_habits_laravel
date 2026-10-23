<?php

namespace App\Queries;

use App\Models\AntiHabit;
use App\Support\AppTime;
use Carbon\CarbonInterface;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * 今週 (月曜始まり) の達成日数ランキング TOP3。
 * 同順位の件数が多い場合は、上位 1 グループ・2 グループで打ち切る。
 */
class WeeklyAchieversRanking
{
    private const MIN_ENTRIES = 3;

    /** @return array<int, array{rank: int, weekly_days: int, anti_habits: Collection<int, AntiHabit>}> */
    public function get(): array
    {
        $rankedData = $this->rankedGroups($this->candidates());

        $firstPlaceCount = isset($rankedData[0]) ? $rankedData[0]['anti_habits']->count() : 0;
        if ($firstPlaceCount >= self::MIN_ENTRIES) {
            return array_slice($rankedData, 0, 1);
        }

        $firstTwoCount = collect(array_slice($rankedData, 0, 2))->sum(fn ($d) => $d['anti_habits']->count());
        if ($firstTwoCount >= self::MIN_ENTRIES) {
            return array_slice($rankedData, 0, 2);
        }

        return array_slice($rankedData, 0, 3);
    }

    /** @return Collection<int, AntiHabit> 公開中で今週の記録がある悪習慣 (weekly_days_count 付き) */
    private function candidates(): Collection
    {
        $today = AppTime::today();
        $startOfWeek = $today->startOfWeek(CarbonInterface::MONDAY);
        $endOfWeek = $today->min($startOfWeek->addDays(6));

        $weeklyCounts = DB::table('anti_habit_records')
            ->select('anti_habit_id', DB::raw('COUNT(*) AS count'))
            ->whereBetween('recorded_on', [$startOfWeek->toDateString(), $endOfWeek->toDateString()])
            ->groupBy('anti_habit_id');

        return AntiHabit::query()
            ->publiclyVisible()
            ->with('user')
            ->joinSub($weeklyCounts, 'weekly', 'weekly.anti_habit_id', '=', 'anti_habits.id')
            ->select('anti_habits.*', 'weekly.count AS weekly_days_count')
            ->orderByDesc('weekly.count')
            ->get();
    }

    /**
     * 達成日数ごとにグループ化し、人数分繰り下げた順位を付ける。
     *
     * @param  Collection<int, AntiHabit>  $candidates
     * @return array<int, array{rank: int, weekly_days: int, anti_habits: Collection<int, AntiHabit>}>
     */
    private function rankedGroups(Collection $candidates): array
    {
        $grouped = $candidates
            ->groupBy(fn (AntiHabit $a) => (int) $a->weekly_days_count)
            ->sortKeysDesc();

        $rankedData = [];
        $currentRank = 1;
        foreach ($grouped as $days => $antiHabits) {
            $rankedData[] = [
                'rank' => $currentRank,
                'weekly_days' => (int) $days,
                'anti_habits' => $antiHabits->sortBy('created_at')->values(),
            ];
            $currentRank += $antiHabits->count();
        }

        return $rankedData;
    }
}
