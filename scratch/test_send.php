<?php

require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\Mail;

echo "Testing sending email to noreply@metalart-astra.co.id ...\n";
try {
    Mail::raw('Ini adalah email uji coba dari sistem SATURNUS Warehouse.', function ($message) {
        $message->to('noreply@metalart-astra.co.id')
                ->subject('[TEST EMAIL] Uji Coba Koneksi Email Saturnus');
    });
    echo "SUCCESS: Email sent successfully without exception!\n";
} catch (\Throwable $e) {
    echo "ERROR sending email: " . $e->getMessage() . "\n";
    echo "Exception class: " . get_class($e) . "\n";
}
