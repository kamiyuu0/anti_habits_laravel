<?php

namespace App\Http\Controllers;

use App\Http\Requests\NotificationSettingRequest;
use App\Models\AntiHabit;
use App\Models\NotificationSetting;
use App\Support\AppTime;
use Illuminate\Http\Request;

class NotificationSettingController extends Controller
{
    public function create(Request $request, string $antiHabitId)
    {
        $antiHabit = $this->findAntiHabit($request, $antiHabitId);

        $setting = new NotificationSetting(['notification_enabled' => true]);
        $now = AppTime::now();
        $setting->setLocalNotificationTime(sprintf('%02d:%02d', $now->hour, intdiv($now->minute, 5) * 5));

        return view('notification_settings.create', ['antiHabit' => $antiHabit, 'setting' => $setting]);
    }

    public function store(NotificationSettingRequest $request, string $antiHabitId)
    {
        $antiHabit = $request->antiHabit();

        // has_one のため既存の設定があれば置き換える
        $antiHabit->notificationSetting()->delete();

        $setting = $antiHabit->notificationSetting()->make($request->settingAttributes());
        $setting->setLocalNotificationTime($request->notificationTime());
        $setting->save();

        return redirect()->route('anti_habits.show', $antiHabit)->with('notice', '通知設定を保存しました。');
    }

    public function edit(Request $request, string $antiHabitId)
    {
        $antiHabit = $this->findAntiHabit($request, $antiHabitId);

        if (! $antiHabit->notificationSetting) {
            return redirect()->route('anti_habits.notification_setting.create', $antiHabit);
        }

        return view('notification_settings.edit', ['antiHabit' => $antiHabit, 'setting' => $antiHabit->notificationSetting]);
    }

    public function update(NotificationSettingRequest $request, string $antiHabitId)
    {
        $antiHabit = $request->antiHabit();
        $setting = $antiHabit->notificationSetting;

        if (! $setting) {
            return redirect()->route('anti_habits.notification_setting.create', $antiHabit);
        }

        $setting->fill($request->settingAttributes());
        $setting->setLocalNotificationTime($request->notificationTime());
        $setting->save();

        return redirect()->route('anti_habits.show', $antiHabit)->with('notice', '通知設定を更新しました。');
    }

    private function findAntiHabit(Request $request, string $id): AntiHabit
    {
        return $request->user()->antiHabits()->findOrFail($id);
    }
}
