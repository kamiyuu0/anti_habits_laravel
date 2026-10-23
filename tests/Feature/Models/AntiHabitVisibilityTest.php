<?php

namespace Tests\Feature\Models;

use App\Models\AntiHabit;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class AntiHabitVisibilityTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function 公開中の投稿は誰でも閲覧できる(): void
    {
        $antiHabit = AntiHabit::factory()->create();

        $this->assertTrue($antiHabit->isVisibleTo(null));
        $this->assertTrue($antiHabit->isVisibleTo(User::factory()->create()));
    }

    #[Test]
    public function 非公開の投稿は本人だけが閲覧できる(): void
    {
        $owner = User::factory()->create();
        $antiHabit = AntiHabit::factory()->private()->for($owner)->create();

        $this->assertTrue($antiHabit->isVisibleTo($owner));
        $this->assertFalse($antiHabit->isVisibleTo(User::factory()->create()));
        $this->assertFalse($antiHabit->isVisibleTo(null));
    }
}
