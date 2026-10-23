<?php

namespace App\Http\Requests;

use App\Models\AntiHabit;
use Illuminate\Foundation\Http\FormRequest;

class NotificationSettingRequest extends FormRequest
{
    private ?AntiHabit $antiHabit = null;

    /** 本人の悪習慣でなければ検証より先に 404 とする */
    public function authorize(): bool
    {
        $this->antiHabit = $this->user()->antiHabits()->findOrFail($this->route('anti_habit'));

        return true;
    }

    public function antiHabit(): AntiHabit
    {
        return $this->antiHabit;
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'notification_time' => sprintf('%s:%s', $this->input('notification_hour'), $this->input('notification_minute')),
        ]);
    }

    /** @return array<string, array<int, string>> */
    public function rules(): array
    {
        // 更新で既存の設定が無い場合、コントローラーは検証せず作成画面へリダイレクトする
        if (! $this->isMethod('POST') && $this->antiHabit->notificationSetting === null) {
            return [];
        }

        return [
            'notification_hour' => ['required', 'date_format:H'],
            'notification_minute' => ['required', 'date_format:i'],
            'notification_time' => ['required', 'date_format:H:i'],
        ];
    }

    /** @return array<string, bool> */
    public function settingAttributes(): array
    {
        return [
            'notification_enabled' => $this->boolean('notification_enabled'),
            'notify_on_reaction' => $this->boolean('notify_on_reaction'),
            'notify_on_comment' => $this->boolean('notify_on_comment'),
        ];
    }

    /** 表示用タイムゾーンの "H:i" */
    public function notificationTime(): string
    {
        return $this->input('notification_time');
    }
}
