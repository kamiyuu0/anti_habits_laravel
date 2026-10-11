<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

if (! function_exists('rails_fk_name')) {
    /**
     * Rails (ActiveRecord) が自動生成する外部キー制約名を返す。
     * Rails 版とスキーマを揃えるためにマイグレーションで使用する。
     */
    function rails_fk_name(string $table, string $column): string
    {
        return 'fk_rails_'.substr(hash('sha256', "{$table}_{$column}_fk"), 0, 10);
    }
}

if (! function_exists('rails_unique_index')) {
    /**
     * Rails と同じ形式 (UNIQUE 制約ではなく UNIQUE INDEX) で一意インデックスを作成する。
     */
    function rails_unique_index(string $table, array|string $columns, string $name): void
    {
        Schema::table($table, fn (Blueprint $blueprint) => $blueprint->unique((array) $columns, $name));
    }
}
