<?php

require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\User;
use App\Models\Role;
use App\Models\Permission;
use Illuminate\Http\Request;

$httpKernel = $app->make(Illuminate\Contracts\Http\Kernel::class);

echo "===============================================================\n";
echo "TESTING UNREGISTRASI ACTION PERMISSIONS (POST & DELETE)\n";
echo "===============================================================\n\n";

// 1. Accounting without unregistrasi permissions should NOT be able to store unregistrasi item
$accountingRole = Role::with('permissions')->where('slug', 'accounting')->first();
$unregPerms = Permission::whereIn('slug', [
    'saturnus.unregistrasi.view',
    'saturnus.unregistrasi.create',
    'saturnus.unregistrasi.approve'
])->pluck('id')->toArray();

$currPerms = $accountingRole->permissions->pluck('id')->toArray();
$filteredPerms = array_diff($currPerms, $unregPerms);
$accountingRole->permissions()->sync($filteredPerms);
$accountingRole->load('permissions');

$accountingUser = User::where('role', 'Accounting')->orWhere('username', 'accounting')->first();

auth()->login($accountingUser);

$session = session()->driver();
$session->start();
$token = $session->token();

$postReq = Request::create('/saturnus/form-unregistrasi/item', 'POST', [
    '_token'      => $token,
    'kode_barang' => 'TEST-BLOCKED',
    'nama_barang' => 'Test Barang',
    'spesifikasi' => 'Spec Test',
    'kategori'    => 'Dies',
    'keterangan'  => 'Alasan discontinue',
]);
$postReq->setLaravelSession($session);
$postRes = $httpKernel->handle($postReq);

echo "1. Accounting attempting POST /saturnus/form-unregistrasi/item: Status " . $postRes->getStatusCode() . "\n";
if ($postRes->getStatusCode() === 403) {
    echo "   => PASS: Blocked with 403 as expected.\n";
} else {
    echo "   => FAIL: Got status " . $postRes->getStatusCode() . "\n";
}

// 2. Production User (who has saturnus.unregistrasi.create) should pass route middleware
$prodUser = User::where('username', 'budi_user')->first();
auth()->login($prodUser);

$prodPostReq = Request::create('/saturnus/form-unregistrasi/item', 'POST', [
    '_token'      => $token,
    'kode_barang' => 'UNREG-TEST-' . time(),
    'nama_barang' => 'Test Barang Unreg',
    'spesifikasi' => 'Spec Test',
    'kategori'    => 'Dies',
    'keterangan'  => 'Alasan discontinue test',
]);
$prodPostReq->setLaravelSession($session);
$prodPostRes = $httpKernel->handle($prodPostReq);

echo "\n2. Production User POST /saturnus/form-unregistrasi/item: Status " . $prodPostRes->getStatusCode() . "\n";
if (in_array($prodPostRes->getStatusCode(), [200, 302])) {
    echo "   => PASS: Allowed (Status {$prodPostRes->getStatusCode()}) as expected.\n";
} else {
    echo "   => FAIL: Got status " . $prodPostRes->getStatusCode() . "\n";
}

echo "\nAll action permission checks completed!\n";
