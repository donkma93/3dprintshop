<?php

namespace App\Console\Commands;

use App\Support\ChatIdleCloser;
use Illuminate\Console\Command;

class CloseIdleChatCommand extends Command
{
    protected $signature = 'chat:close-idle';

    protected $description = 'Đóng hội thoại chat không có tin nhắn mới trong 30 phút và gửi thông báo bot';

    public function handle(): int
    {
        $closed = ChatIdleCloser::closeAllIdle();
        $count = $closed->count();

        if ($count === 0) {
            $this->info('Không có hội thoại idle cần đóng.');
        } else {
            $this->info("Đã đóng {$count} hội thoại quá 30 phút không nhắn tin.");
            foreach ($closed as $c) {
                $this->line("  #{$c->id} — {$c->guest_name}");
            }
        }

        return self::SUCCESS;
    }
}
