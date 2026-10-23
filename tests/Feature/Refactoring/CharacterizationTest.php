<?php

namespace Tests\Feature\Refactoring;

use App\Enums\ReactionKind;
use App\Models\AntiHabit;
use App\Models\Bookmark;
use App\Models\NotificationSetting;
use App\Models\Reaction;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/** リファクタリング前の既存動作を固定する特性化テスト */
class CharacterizationTest extends TestCase
{
    use RefreshDatabase;

    // ---- 公開/非公開の閲覧可否 ----

    #[Test]
    public function 非公開の詳細は本人だけが閲覧できる(): void
    {
        $owner = User::factory()->create();
        $antiHabit = AntiHabit::factory()->private()->for($owner)->create();

        $this->get("/anti_habits/{$antiHabit->id}")
            ->assertRedirect('/anti_habits')
            ->assertSessionHas('alert', 'このページにアクセスする権限がありません。');
        $this->actingAs(User::factory()->create())
            ->get("/anti_habits/{$antiHabit->id}")
            ->assertRedirect('/anti_habits');
        $this->actingAs($owner)->get("/anti_habits/{$antiHabit->id}")->assertOk();
    }

    #[Test]
    public function 非公開でも本人はコメントとリアクションができる(): void
    {
        $owner = User::factory()->create();
        $antiHabit = AntiHabit::factory()->private()->for($owner)->create();

        $this->actingAs($owner)
            ->post("/anti_habits/{$antiHabit->id}/comments", ['body' => 'メモ'], $this->turboStreamHeaders())
            ->assertOk();
        $this->actingAs($owner)
            ->post("/anti_habits/{$antiHabit->id}/reactions", ['reaction_kind' => 'zen'], $this->turboStreamHeaders())
            ->assertOk();

        $this->assertSame(1, $antiHabit->comments()->count());
        $this->assertTrue($owner->hasReacted($antiHabit, ReactionKind::Zen));
    }

    // ---- リアクション ----

    #[Test]
    public function リアクションは冪等で他ユーザーや他の種類に影響しない(): void
    {
        $user = User::factory()->create();
        $other = User::factory()->create();
        $antiHabit = AntiHabit::factory()->create();

        $user->reaction($antiHabit, ReactionKind::Fire);
        $user->reaction($antiHabit, ReactionKind::Fire);
        $user->reaction($antiHabit, ReactionKind::Zen);
        $other->reaction($antiHabit, ReactionKind::Fire);

        $this->assertSame(3, Reaction::count());
        $this->assertSame(2, $antiHabit->reactionCount(ReactionKind::Fire));

        $user->unreaction($antiHabit, ReactionKind::Fire);
        $user->unreaction($antiHabit, ReactionKind::Fire); // 未作成の削除は何もしない

        $this->assertFalse($user->hasReacted($antiHabit, ReactionKind::Fire));
        $this->assertTrue($user->hasReacted($antiHabit, ReactionKind::Zen));
        $this->assertTrue($other->hasReacted($antiHabit, ReactionKind::Fire));
    }

    #[Test]
    public function turbo_stream_でないリアクションとブックマークは_303_で戻る(): void
    {
        $user = User::factory()->create();
        $antiHabit = AntiHabit::factory()->create();

        $this->actingAs($user)->from('/somewhere')
            ->post("/anti_habits/{$antiHabit->id}/reactions", ['reaction_kind' => 'fire'])
            ->assertStatus(303)->assertRedirect('/somewhere');
        $this->actingAs($user)->from('/somewhere')
            ->post("/anti_habits/{$antiHabit->id}/bookmarks")
            ->assertStatus(303)->assertRedirect('/somewhere');
        $this->actingAs($user)->from('/somewhere')
            ->delete("/anti_habits/{$antiHabit->id}/bookmarks")
            ->assertStatus(303)->assertRedirect('/somewhere');
    }

    // ---- ブックマーク ----

    #[Test]
    public function ブックマークは冪等で未作成の削除は何もしない(): void
    {
        $user = User::factory()->create();
        $antiHabit = AntiHabit::factory()->create();

        $user->bookmark($antiHabit);
        $user->bookmark($antiHabit);
        $this->assertSame(1, Bookmark::count());
        $this->assertTrue($user->hasBookmarked($antiHabit));

        $user->unbookmark($antiHabit);
        $user->unbookmark($antiHabit);
        $this->assertSame(0, Bookmark::count());
        $this->assertFalse($user->hasBookmarked($antiHabit));
    }

    #[Test]
    public function ブックマーク可否は公開かつ他人の投稿のみ(): void
    {
        $user = User::factory()->create();

        $this->assertTrue($user->canBookmark(AntiHabit::factory()->create()));
        $this->assertFalse($user->canBookmark(AntiHabit::factory()->for($user)->create()));
        $this->assertFalse($user->canBookmark(AntiHabit::factory()->private()->create()));
    }

    // ---- 通知設定 ----

    /** @return array<string, array{string, string, string}> */
    public static function notificationTimeProvider(): array
    {
        return [
            '日本時間 00:00 は前日 15:00 UTC' => ['00', '00', '15:00:00'],
            '日本時間 09:00 は 00:00 UTC' => ['09', '00', '00:00:00'],
            '日本時間 23:55 は 14:55 UTC' => ['23', '55', '14:55:00'],
        ];
    }

    #[Test]
    #[DataProvider('notificationTimeProvider')]
    public function 通知時刻は_utc_で保存される(string $hour, string $minute, string $expected): void
    {
        $owner = User::factory()->lineLinked()->create();
        $antiHabit = AntiHabit::factory()->for($owner)->create();

        $this->actingAs($owner)->post("/anti_habits/{$antiHabit->id}/notification_setting", [
            'notification_hour' => $hour,
            'notification_minute' => $minute,
        ])->assertRedirect("/anti_habits/{$antiHabit->id}");

        $this->assertSame($expected, NotificationSetting::sole()->notification_time);
    }

    #[Test]
    public function 不正な通知時刻は拒否され保存されない(): void
    {
        $owner = User::factory()->lineLinked()->create();
        $antiHabit = AntiHabit::factory()->for($owner)->create();
        $url = "/anti_habits/{$antiHabit->id}/notification_setting";

        $this->actingAs($owner)->post($url, ['notification_hour' => '24', 'notification_minute' => '00'])
            ->assertSessionHasErrors('notification_hour');
        $this->actingAs($owner)->post($url, ['notification_hour' => '10', 'notification_minute' => '60'])
            ->assertSessionHasErrors('notification_minute');
        $this->actingAs($owner)->post($url, [])
            ->assertSessionHasErrors(['notification_hour', 'notification_minute']);

        $this->assertSame(0, NotificationSetting::count());
    }

    #[Test]
    public function 通知設定を再作成すると既存の設定が置き換わり未指定のフラグは_false(): void
    {
        $owner = User::factory()->lineLinked()->create();
        $antiHabit = AntiHabit::factory()->for($owner)->create();
        $url = "/anti_habits/{$antiHabit->id}/notification_setting";

        $this->actingAs($owner)->post($url, ['notification_hour' => '10', 'notification_minute' => '00', 'notification_enabled' => '1', 'notify_on_comment' => '1']);
        $this->actingAs($owner)->post($url, ['notification_hour' => '11', 'notification_minute' => '30']);

        $setting = NotificationSetting::sole();
        $this->assertSame('02:30:00', $setting->notification_time);
        $this->assertFalse($setting->notification_enabled);
        $this->assertFalse($setting->notify_on_comment);
    }

    #[Test]
    public function 他人の投稿への通知設定は入力が不正でも検証より先に拒否される(): void
    {
        $antiHabit = AntiHabit::factory()->create();
        $url = "/anti_habits/{$antiHabit->id}/notification_setting";

        $this->actingAs(User::factory()->lineLinked()->create())
            ->post($url, ['notification_hour' => '99'])
            ->assertRedirect('/anti_habits')
            ->assertSessionHasNoErrors();
        $this->actingAs(User::factory()->lineLinked()->create())
            ->patch($url, ['notification_hour' => '99'])
            ->assertRedirect('/anti_habits')
            ->assertSessionHasNoErrors();
    }

    #[Test]
    public function 通知設定が無い場合の編集と更新は作成画面へ誘導する(): void
    {
        $owner = User::factory()->lineLinked()->create();
        $antiHabit = AntiHabit::factory()->for($owner)->create();
        $create = "/anti_habits/{$antiHabit->id}/notification_setting/new";

        $this->actingAs($owner)->get("/anti_habits/{$antiHabit->id}/notification_setting/edit")->assertRedirect($create);
        $this->actingAs($owner)->patch("/anti_habits/{$antiHabit->id}/notification_setting", ['notification_hour' => '10', 'notification_minute' => '00'])
            ->assertRedirect($create);
        // 設定が無い場合は入力が不正でも検証せず作成画面へ誘導する
        $this->actingAs($owner)->patch("/anti_habits/{$antiHabit->id}/notification_setting", ['notification_hour' => '99'])
            ->assertRedirect($create)
            ->assertSessionHasNoErrors();
    }

    // ---- 一覧の検索 ----

    #[Test]
    public function 検索条件はページネーションのリンクに引き継がれる(): void
    {
        AntiHabit::factory()->count(11)->create(['title' => '夜更かし']);

        $response = $this->get('/anti_habits?q[title_or_description_cont]='.urlencode('夜'));

        $response->assertOk();
        $this->assertStringContainsString('page=2', html_entity_decode($response->getContent()));
        $this->assertStringContainsString('q%5Btitle_or_description_cont%5D=', html_entity_decode($response->getContent()));
    }

    #[Test]
    public function 一覧は新しい順で非公開は含まない(): void
    {
        AntiHabit::factory()->create(['title' => '古い投稿', 'created_at' => now()->subDay()]);
        AntiHabit::factory()->create(['title' => '新しい投稿', 'created_at' => now()]);
        AntiHabit::factory()->private()->create(['title' => '秘密の投稿']);

        $this->get('/anti_habits')
            ->assertSeeInOrder(['新しい投稿', '古い投稿'])
            ->assertDontSee('秘密の投稿');
    }

    #[Test]
    public function 空白だけの検索語は絞り込みをしない(): void
    {
        AntiHabit::factory()->create(['title' => '投稿A']);

        $this->get('/anti_habits?q[title_or_description_cont]=%20%20')->assertSee('投稿A');
    }
}
