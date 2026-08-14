<?php

require __DIR__.'/../vendor/autoload.php';
$app = require __DIR__.'/../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

$models = [
    App\Models\Page::class,
    App\Models\Post::class,
    App\Models\Banner::class,
];

$fields = ['title', 'subtitle', 'content', 'meta_title', 'meta_description', 'excerpt', 'button_text'];
$from = '3D Print Shop';
$to = 'Shop3DPrinting';

foreach ($models as $model) {
    $n = 0;
    $model::query()->get()->each(function ($row) use ($fields, $from, $to, &$n) {
        $changed = false;
        foreach ($fields as $f) {
            if (! isset($row->{$f}) || ! is_string($row->{$f})) {
                continue;
            }
            $v = str_replace($from, $to, $row->{$f});
            if ($v !== $row->{$f}) {
                $row->{$f} = $v;
                $changed = true;
            }
        }
        if ($changed) {
            $row->save();
            $n++;
        }
    });
    echo $model.' updated '.$n.PHP_EOL;
}

echo "done\n";
