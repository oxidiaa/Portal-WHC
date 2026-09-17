<?php

require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\FormItem;
use App\Models\FormApproval;

$approvals = FormApproval::all();

$outstanding = [];
$completed = [];

foreach ($approvals as $appr) {
    $whDone = (bool)($appr->warehouse_signed_at || $appr->status === 'Item Telah didaftarkan' || $appr->status === 'SELESAI');
    if ($whDone) {
        $completed[] = $appr->form_number;
    } else {
        $outstanding[] = $appr->form_number;
    }
}

echo "Proses Approval (Outstanding PP) Total: " . count($outstanding) . PHP_EOL;
echo "Completed (History Data Registrasi) Total: " . count($completed) . PHP_EOL;

foreach ($completed as $fNo) {
    echo " -> Completed: {$fNo}" . PHP_EOL;
}
foreach ($outstanding as $fNo) {
    echo " -> Outstanding: {$fNo}" . PHP_EOL;
}
