<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * PostgreSQL (pgsql 接続) のデータを、既定接続 (MySQL) へコピーする。
 */
class ImportFromPgsql extends Command
{
    protected $signature = 'db:import-from-pgsql
        {--truncate : 移行先のテーブルを空にしてから取り込む}
        {--dry-run : 件数の確認のみ行い、書き込まない}
        {--chunk=500 : 1 回に読み込む行数}';

    protected $description = 'PostgreSQL (pgsql 接続) のデータを既定の DB (MySQL) に移行する';

    /** 外部キーの親テーブルが先になる順序 (solid_queue_* は Laravel 版で未使用のため対象外) */
    private const TABLES = [
        'users',
        'tags',
        'anti_habits',
        'anti_habit_records',
        'anti_habit_tags',
        'comments',
        'reactions',
        'bookmarks',
        'notification_settings',
    ];

    public function handle(): int
    {
        $source = DB::connection('pgsql');
        $target = DB::connection();

        if ($target->getDriverName() === 'pgsql') {
            $this->error('移行先 (DB_CONNECTION) が pgsql のままです。MySQL に向けてください。');

            return self::FAILURE;
        }

        $dryRun = (bool) $this->option('dry-run');
        $chunk = max(1, (int) $this->option('chunk'));

        foreach (self::TABLES as $table) {
            $count = $source->table($table)->count();
            $existing = $target->table($table)->count();
            $this->line(sprintf('%-24s 移行元 %6d 件 / 移行先 %6d 件', $table, $count, $existing));

            if (! $dryRun && $existing > 0 && ! $this->option('truncate')) {
                $this->error("{$table} に既存データがあります。--truncate を付けるか移行先を空にしてください。");

                return self::FAILURE;
            }
        }

        if ($dryRun) {
            return self::SUCCESS;
        }

        Schema::disableForeignKeyConstraints();

        try {
            if ($this->option('truncate')) {
                foreach (array_reverse(self::TABLES) as $table) {
                    $target->table($table)->truncate();
                }
            }

            foreach (self::TABLES as $table) {
                $target->transaction(function () use ($source, $target, $table, $chunk) {
                    $source->table($table)->orderBy('id')->chunk($chunk, function ($rows) use ($target, $table) {
                        $target->table($table)->insert(
                            $rows->map(fn ($row) => (array) $row)->all()
                        );
                    });
                });
            }
        } finally {
            Schema::enableForeignKeyConstraints();
        }

        $ok = true;
        foreach (self::TABLES as $table) {
            $from = $source->table($table)->count();
            $to = $target->table($table)->count();
            $match = $from === $to;
            $ok = $ok && $match;
            $this->line(sprintf('%-24s %6d → %6d %s', $table, $from, $to, $match ? 'OK' : 'NG'));
        }

        return $ok ? self::SUCCESS : self::FAILURE;
    }
}
