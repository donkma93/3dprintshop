<?php

require __DIR__.'/../vendor/autoload.php';
$app = require __DIR__.'/../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

$svc = app(App\Services\SocialVideoMetadataService::class);

foreach (App\Models\SocialVideo::withTrashed()->get() as $video) {
    $meta = $svc->resolve($video->url);
    $video->thumbnail = $meta['thumbnail'];
    $video->external_id = $meta['external_id'] ?: $video->external_id;
    $video->platform = $meta['platform'] ?: $video->platform;
    if (empty($video->channel_name) && ! empty($meta['channel_name'])) {
        $video->channel_name = $meta['channel_name'];
    }
    $video->preview_url = null;
    $video->save();
    echo $video->id.' | '.$video->platform.' | '.substr((string) $video->thumbnail, 0, 90).PHP_EOL;
}

echo "done\n";
