<?php

require __DIR__.'/../vendor/autoload.php';
$app = require __DIR__.'/../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

foreach (DB::select('SHOW COLUMNS FROM social_videos') as $c) {
    echo $c->Field.' | '.$c->Type.PHP_EOL;
}

$svc = app(App\Services\SocialVideoMetadataService::class);
$meta = $svc->resolve('https://www.youtube.com/watch?v=aqz-KE-bpKQ');
echo 'thumb_len='.strlen((string) $meta['thumbnail']).PHP_EOL;

// Simulate long TikTok-like URL save
$long = 'https://p16-common-sign.tiktokcdn.com/tos-alisg-p-0037/'.str_repeat('a', 400).'~tplv-tiktokx-origin.image?x-expires=1&x-signature='.str_repeat('b', 80);
try {
    $v = App\Models\SocialVideo::create([
        'title' => 'Test long thumb',
        'platform' => 'tiktok',
        'url' => 'https://www.tiktok.com/@test/video/1',
        'external_id' => '1',
        'thumbnail' => $long,
        'is_active' => false,
        'published_at' => now(),
        'sort_order' => 999,
    ]);
    echo 'created id='.$v->id.' thumb_len='.strlen($v->thumbnail).PHP_EOL;
    $v->forceDelete();
    echo "ok\n";
} catch (Throwable $e) {
    echo 'ERR: '.$e->getMessage().PHP_EOL;
}
