<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;

/**
 * Rails 版で使用していた Solid Queue のテーブルを削除する。
 * Laravel 版では使用しない。既存 DB に残っている場合のみ削除し、新規 DB では何もしない。
 */
return new class extends Migration
{
    public function up(): void
    {
        foreach (['blocked', 'claimed', 'failed', 'ready', 'recurring', 'scheduled'] as $kind) {
            Schema::dropIfExists("solid_queue_{$kind}_executions");
        }
        foreach (['jobs', 'pauses', 'processes', 'recurring_tasks', 'semaphores'] as $name) {
            Schema::dropIfExists("solid_queue_{$name}");
        }
    }

    public function down(): void
    {
        // 未使用のテーブルのため復元しない
    }
};
