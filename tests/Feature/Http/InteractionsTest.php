<?php

namespace Tests\Feature\Http;

use App\Enums\ReactionKind;
use App\Livewire\BookmarkButton;
use App\Livewire\CommentCount;
use App\Livewire\Comments;
use App\Livewire\ReactionButtons;
use App\Models\AntiHabit;
use App\Models\AntiHabitRecord;
use App\Models\Comment;
use App\Models\NotificationSetting;
use App\Models\Tag;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/** 記録・コメント・リアクション・ブックマーク・通知設定・タグ API */
class InteractionsTest extends TestCase
{
    use RefreshDatabase;

    // ---- 記録 ----

    #[Test]
    public function 本人だけが今日の記録を作成できる(): void
    {
        $this->travelToLocal('2026-10-07 23:30:00'); // UTC では 14:30
        $owner = User::factory()->create();
        $antiHabit = AntiHabit::factory()->for($owner)->create();

        $this->post('/anti_habit_records', ['anti_habit_id' => $antiHabit->id])->assertRedirect('/users/sign_in');

        $this->actingAs(User::factory()->create())
            ->post('/anti_habit_records', ['anti_habit_id' => $antiHabit->id])
            ->assertRedirect("/anti_habits/{$antiHabit->id}")
            ->assertSessionHas('alert', '自分の悪習慣のみ記録できます。');
        $this->assertSame(0, AntiHabitRecord::count());

        $this->actingAs($owner)
            ->post('/anti_habit_records', ['anti_habit_id' => $antiHabit->id])
            ->assertRedirect("/anti_habits/{$antiHabit->id}")
            ->assertSessionHas('notice', '記録を作成しました。');
        $this->assertSame('2026-10-07', AntiHabitRecord::sole()->recorded_on->toDateString());

        $this->actingAs($owner)
            ->post('/anti_habit_records', ['anti_habit_id' => $antiHabit->id])
            ->assertSessionHas('alert', '今日はすでに記録済みです。');
        $this->assertSame(1, AntiHabitRecord::count());
    }

    #[Test]
    public function 本人だけが記録を削除できる(): void
    {
        $owner = User::factory()->create();
        $record = AntiHabitRecord::factory()->for(AntiHabit::factory()->for($owner))->create();
        $antiHabitId = $record->anti_habit_id;

        $this->delete("/anti_habit_records/{$record->id}")->assertRedirect('/users/sign_in');
        $this->actingAs(User::factory()->create())
            ->delete("/anti_habit_records/{$record->id}")
            ->assertSessionHas('alert', '自分の記録のみ削除できます。');
        $this->assertModelExists($record);

        $this->actingAs($owner)
            ->delete("/anti_habit_records/{$record->id}")
            ->assertStatus(303)
            ->assertRedirect("/anti_habits/{$antiHabitId}");
        $this->assertModelMissing($record);
    }

    // ---- コメント ----

    #[Test]
    public function コメントを投稿すると一覧と件数が更新される(): void
    {
        $user = User::factory()->create(['name' => 'コメント者']);
        $antiHabit = AntiHabit::factory()->create();

        Livewire::test(Comments::class, ['antiHabit' => $antiHabit])->set('body', 'x')->call('save')->assertForbidden();

        Livewire::actingAs($user)
            ->test(Comments::class, ['antiHabit' => $antiHabit])
            ->set('body', '応援しています')
            ->call('save')
            ->assertHasNoErrors()
            ->assertSet('body', '')
            ->assertDispatched('comment-posted')
            ->assertSee('応援しています')
            ->assertSee('コメント者');

        $this->assertSame(1, $antiHabit->fresh()->comments_count);
        $this->assertTrue(Comment::sole()->user->is($user));
    }

    #[Test]
    public function 不正なコメントはフォームにエラーを表示する(): void
    {
        $antiHabit = AntiHabit::factory()->create();

        Livewire::actingAs(User::factory()->create())
            ->test(Comments::class, ['antiHabit' => $antiHabit])
            ->set('body', str_repeat('あ', 501))
            ->call('save')
            ->assertHasErrors(['body' => 'max'])
            ->assertSee('応援メッセージは500文字以内で入力してください')
            ->set('body', '')
            ->call('save')
            ->assertHasErrors(['body' => 'required']);

        $this->assertSame(0, Comment::count());
    }

    #[Test]
    public function 非公開の他人の投稿にはコメントできない(): void
    {
        $antiHabit = AntiHabit::factory()->private()->create();

        Livewire::actingAs(User::factory()->create())
            ->test(Comments::class, ['antiHabit' => $antiHabit])
            ->set('body', 'x')
            ->call('save')
            ->assertForbidden();

        $this->assertSame(0, Comment::count());
    }

    #[Test]
    public function コメント件数はコメント投稿イベントで更新される(): void
    {
        $antiHabit = AntiHabit::factory()->create();
        $user = User::factory()->create();

        $count = Livewire::test(CommentCount::class, ['antiHabit' => $antiHabit])->assertSet('antiHabit.comments_count', 0);

        $antiHabit->comments()->make(['body' => 'a'])->user()->associate($user)->save();

        $count->dispatch('comment-posted')->assertSet('antiHabit.comments_count', 1);
    }

    // ---- リアクション ----

    #[Test]
    public function リアクションの付け外しでボタンの表示が切り替わる(): void
    {
        $user = User::factory()->create();
        $antiHabit = AntiHabit::factory()->create();

        Livewire::actingAs($user)
            ->test(ReactionButtons::class, ['antiHabit' => $antiHabit])
            ->assertSee('badge-dash', false)
            ->call('toggle', 'fire')
            ->assertSee('badge-outline badge-primary', false);
        $this->assertTrue($user->hasReacted($antiHabit, ReactionKind::Fire));

        Livewire::actingAs($user)
            ->test(ReactionButtons::class, ['antiHabit' => $antiHabit])
            ->call('toggle', 'fire')
            ->assertDontSee('badge-outline badge-primary', false);
        $this->assertFalse($user->hasReacted($antiHabit, ReactionKind::Fire));

        Livewire::actingAs($user)
            ->test(ReactionButtons::class, ['antiHabit' => $antiHabit])
            ->call('toggle', 'unknown')
            ->assertStatus(422);
        Livewire::actingAs($user)
            ->test(ReactionButtons::class, ['antiHabit' => AntiHabit::factory()->private()->create()])
            ->call('toggle', 'fire')
            ->assertForbidden();
    }

    // ---- ブックマーク ----

    #[Test]
    public function ブックマークの付け外しと一覧(): void
    {
        $user = User::factory()->create();
        $antiHabit = AntiHabit::factory()->create(['title' => 'ブクマ対象']);

        $this->get('/bookmarks')->assertRedirect('/users/sign_in');

        $button = Livewire::actingAs($user)
            ->test(BookmarkButton::class, ['antiHabit' => $antiHabit])
            ->assertSee('far fa-bookmark', false)
            ->call('toggle')
            ->assertSee('fas fa-bookmark', false);
        $this->actingAs($user)->get('/bookmarks')->assertOk()->assertSee('ブクマ対象');

        $button->call('toggle')->assertSee('far fa-bookmark', false);
        $this->actingAs($user)->get('/bookmarks')->assertSee('まだブックマークがありません');

        // 自分の投稿・非公開の投稿は不可
        Livewire::actingAs($user)
            ->test(BookmarkButton::class, ['antiHabit' => AntiHabit::factory()->for($user)->create()])
            ->call('toggle')
            ->assertForbidden();
        Livewire::actingAs($user)
            ->test(BookmarkButton::class, ['antiHabit' => AntiHabit::factory()->private()->create()])
            ->call('toggle')
            ->assertForbidden();
    }

    // ---- 通知設定 ----

    #[Test]
    public function 通知設定は本人のみ作成_更新できる(): void
    {
        $owner = User::factory()->lineLinked()->create();
        $antiHabit = AntiHabit::factory()->for($owner)->create();
        $params = ['notification_hour' => '21', 'notification_minute' => '05', 'notification_enabled' => '1'];

        $this->actingAs(User::factory()->create())
            ->get("/anti_habits/{$antiHabit->id}/notification_setting/new")
            ->assertRedirect('/anti_habits');

        $this->actingAs($owner)->get("/anti_habits/{$antiHabit->id}/notification_setting/new")->assertOk();
        $this->actingAs($owner)
            ->post("/anti_habits/{$antiHabit->id}/notification_setting", $params)
            ->assertRedirect("/anti_habits/{$antiHabit->id}")
            ->assertSessionHas('notice', '通知設定を保存しました。');

        $setting = NotificationSetting::sole();
        $this->assertSame('12:05:00', $setting->notification_time); // UTC
        $this->assertTrue($setting->notification_enabled);

        $this->actingAs($owner)->get("/anti_habits/{$antiHabit->id}")->assertSee("/anti_habits/{$antiHabit->id}/notification_setting/edit", false);
        $this->actingAs($owner)
            ->get("/anti_habits/{$antiHabit->id}/notification_setting/edit")
            ->assertOk()
            ->assertSee('<option value="21" selected>', false)
            ->assertSee('<option value="05" selected>', false);

        $this->actingAs($owner)
            ->patch("/anti_habits/{$antiHabit->id}/notification_setting", ['notification_hour' => '08', 'notification_minute' => '30', 'notification_enabled' => '0'])
            ->assertSessionHas('notice', '通知設定を更新しました。');

        $setting->refresh();
        $this->assertSame('23:30:00', $setting->notification_time);
        $this->assertFalse($setting->notification_enabled);

        $this->actingAs($owner)
            ->patch("/anti_habits/{$antiHabit->id}/notification_setting", ['notification_hour' => '99', 'notification_minute' => '30'])
            ->assertSessionHasErrors('notification_hour');
    }

    #[Test]
    public function line_未連携ユーザーには認証モーダルが表示される(): void
    {
        $owner = User::factory()->create();
        $antiHabit = AntiHabit::factory()->for($owner)->create();

        $this->actingAs($owner)->get("/anti_habits/{$antiHabit->id}")->assertSee('LINE通知にするにはLINE認証してください。');
    }

    // ---- タグ / 静的ページ / マイページ ----

    #[Test]
    public function タグ一覧を名前順の_json_で返す(): void
    {
        Tag::factory()->create(['name' => 'b']);
        Tag::factory()->create(['name' => 'a']);

        $this->getJson('/tags')->assertExactJson(['a', 'b']);
    }

    #[Test]
    public function 静的ページを表示できる(): void
    {
        $this->get('/')->assertOk()->assertSee('悪習慣を取り除く手助けをするサービス');
        $this->get('/terms')->assertOk();
        $this->get('/privacy')->assertOk();
        $this->get('/up')->assertOk();
    }

    #[Test]
    public function マイページはログインユーザー自身を表示する(): void
    {
        $user = User::factory()->create(['name' => '自分']);
        AntiHabit::factory()->for($user)->private()->create(['title' => '自分の非公開']);

        $this->get("/users/{$user->id}")->assertRedirect('/anti_habits');
        $this->actingAs($user)->get('/users/999')->assertOk()->assertSee('自分')->assertSee('自分の非公開');
    }
}
