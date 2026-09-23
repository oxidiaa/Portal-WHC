<?php

require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Config;

echo "Current Mail Configuration:\n";
echo "Mailer: " . Config::get('mail.default') . "\n";
echo "Host: " . Config::get('mail.mailers.smtp.host') . "\n";
echo "Port: " . Config::get('mail.mailers.smtp.port') . "\n";
echo "Username: " . Config::get('mail.mailers.smtp.username') . "\n";
echo "Encryption: " . Config::get('mail.mailers.smtp.encryption') . "\n";
echo "From Address: " . Config::get('mail.from.address') . "\n";
echo "Queue Connection: " . Config::get('queue.default') . "\n\n";

try {
    echo "Connecting to SMTP server...\n";
    $transport = Mail::mailer('smtp')->getSymfonyTransport();
    $transport->start();
    echo "SUCCESS: SMTP Transport connected and authenticated successfully!\n";
} catch (\Throwable $e) {
    echo "FAILED: " . $e->getMessage() . "\n";
    echo "Error Type: " . get_class($e) . "\n";
    if (method_exists($e, 'getTraceAsString')) {
        echo "Trace snippet:\n" . substr($e->getTraceAsString(), 0, 500) . "\n";
    }
}
