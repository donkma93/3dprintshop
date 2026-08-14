<?php

namespace App\Console;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;

class Kernel extends ConsoleKernel
{
    /**
     * Define the application's command schedule.
     */
    protected function schedule(Schedule $schedule): void
    {
        // Xóa vĩnh viễn mục trong thùng rác sau 30 ngày
        $schedule->command('trash:purge')->dailyAt('02:00');

        // Đóng chat không có tin nhắn mới trong 30 phút + tin bot thông báo
        $schedule->command('chat:close-idle')->everyMinute();
    }

    /**
     * Register the commands for the application.
     */
    protected function commands(): void
    {
        $this->load(__DIR__.'/Commands');

        require base_path('routes/console.php');
    }
}
