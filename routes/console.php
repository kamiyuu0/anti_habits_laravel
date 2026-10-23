<?php

use App\Jobs\NotifyDispatcherJob;
use Illuminate\Support\Facades\Schedule;

// 通知時刻を迎えた悪習慣へ LINE 通知を送る
Schedule::job(new NotifyDispatcherJob)->everyFiveMinutes()->withoutOverlapping();
