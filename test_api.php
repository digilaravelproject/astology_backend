<?php
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();
$service = app(App\Services\LiveSessionService::class);
echo json_encode($service->getActiveSessions(), JSON_PRETTY_PRINT);
