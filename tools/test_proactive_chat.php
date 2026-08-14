<?php

require __DIR__.'/../vendor/autoload.php';
$app = require __DIR__.'/../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Http\Controllers\Shop\ChatController;
use Illuminate\Http\Request;

$ctrl = $app->make(ChatController::class);

// IP never seen
$req = Request::create('/chat/proactive', 'GET', [], [], [], ['REMOTE_ADDR' => '203.0.113.91']);
$out = $ctrl->proactive($req);
echo "proactive_new_ip ".$out->getContent().PHP_EOL;

// Start with name only
$startReq = Request::create(
    '/chat/start',
    'POST',
    ['guest_name' => 'Minh Test', 'message' => 'Xin chao shop'],
    [],
    [],
    ['REMOTE_ADDR' => '203.0.113.91', 'HTTP_USER_AGENT' => 'PHPTest']
);
$startOut = $ctrl->start($startReq);
echo "start ".$startOut->getStatusCode().' '.$startOut->getContent().PHP_EOL;
$startData = json_decode($startOut->getContent(), true);
$token = $startData['token'] ?? '';

// Active session should not prompt
$req2 = Request::create('/chat/proactive', 'GET', ['token' => $token], [], [], ['REMOTE_ADDR' => '203.0.113.91']);
$out2 = $ctrl->proactive($req2);
echo "proactive_active ".$out2->getContent().PHP_EOL;

// Same IP without token shortly after chat => recent_chat, no prompt
$req3 = Request::create('/chat/proactive', 'GET', [], [], [], ['REMOTE_ADDR' => '203.0.113.91']);
$out3 = $ctrl->proactive($req3);
echo "proactive_recent ".$out3->getContent().PHP_EOL;

// Empty name should 422 validation or custom message
try {
    $bad = Request::create('/chat/start', 'POST', ['guest_name' => ''], [], [], ['REMOTE_ADDR' => '203.0.113.99']);
    $badOut = $ctrl->start($bad);
    echo "empty ".$badOut->getStatusCode().' '.$badOut->getContent().PHP_EOL;
} catch (Throwable $e) {
    echo "empty_exception ".class_basename($e).': '.$e->getMessage().PHP_EOL;
}

// Phone-only was previously required — name without phone must succeed
$hasPhone = $startData['conversation']['guest_phone'] ?? 'null';
echo "guest_phone=".json_encode($hasPhone).PHP_EOL;
echo "guest_name=".($startData['conversation']['guest_name'] ?? '').PHP_EOL;
echo "done\n";
