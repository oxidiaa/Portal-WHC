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

function testUrl($user, $url, $expectedCode, $httpKernel) {
    auth()->login($user);
    $req = Request::create($url, 'GET');
    $res = $httpKernel->handle($req);
    $status = $res->getStatusCode();
    $pass = ($status === $expectedCode);
    echo "  [" . ($pass ? "PASS" : "FAIL") . "] {$user->username} ({$user->role}) GET {$url} => Got {$status} (Expected {$expectedCode})\n";
    return $pass;
}

echo "===============================================================\n";
echo "COMPREHENSIVE RBAC & UNREGISTRASI PERMISSION VERIFICATION\n";
echo "===============================================================\n\n";

$allPassed = true;

// 1. SETUP ACCOUNTING ROLE WITHOUT UNREGISTRASI
echo "--- TEST CASE 1: Accounting Role WITHOUT Unregistrasi Permissions ---\n";
$accountingRole = Role::with('permissions')->where('slug', 'accounting')->first();
$unregPerms = Permission::whereIn('slug', [
    'saturnus.unregistrasi.view',
    'saturnus.unregistrasi.create',
    'saturnus.unregistrasi.approve'
])->pluck('id')->toArray();

// Remove unregistrasi permissions from accounting role
$currPerms = $accountingRole->permissions->pluck('id')->toArray();
$filteredPerms = array_diff($currPerms, $unregPerms);
$accountingRole->permissions()->sync($filteredPerms);
$accountingRole->load('permissions');

$accountingUser = User::where('role', 'Accounting')->orWhere('username', 'accounting')->first();
// Clear cached role model on user
$accountingUser->setRawAttributes($accountingUser->getAttributes());

$allPassed &= testUrl($accountingUser, '/saturnus/form-unregistrasi', 403, $httpKernel);
$allPassed &= testUrl($accountingUser, '/saturnus/unregistrasi-approval', 403, $httpKernel);
$allPassed &= testUrl($accountingUser, '/saturnus/unregistrasi-history', 403, $httpKernel);
$allPassed &= testUrl($accountingUser, '/saturnus/unregistrasi/export-excel', 403, $httpKernel);
$allPassed &= testUrl($accountingUser, '/saturnus/form-registrasi', 200, $httpKernel);
$allPassed &= testUrl($accountingUser, '/saturnus/proses-approval', 200, $httpKernel);
$allPassed &= testUrl($accountingUser, '/dashboard', 200, $httpKernel);

// 2. SETUP ACCOUNTING ROLE WITH UNREGISTRASI RESTORED
echo "\n--- TEST CASE 2: Accounting Role WITH Unregistrasi Permissions RESTORED ---\n";
$viewPerm = Permission::where('slug', 'saturnus.unregistrasi.view')->first();
$restoredPerms = array_unique(array_merge($filteredPerms, [$viewPerm->id]));
$accountingRole->permissions()->sync($restoredPerms);
$accountingRole->load('permissions');
$accountingUser = User::where('role', 'Accounting')->orWhere('username', 'accounting')->first();

$allPassed &= testUrl($accountingUser, '/saturnus/form-unregistrasi', 200, $httpKernel);
$allPassed &= testUrl($accountingUser, '/saturnus/unregistrasi-approval', 200, $httpKernel);
$allPassed &= testUrl($accountingUser, '/saturnus/unregistrasi-history', 200, $httpKernel);
$allPassed &= testUrl($accountingUser, '/saturnus/unregistrasi/export-excel', 200, $httpKernel);

// 3. MASTER / ADMIN ACCESS
echo "\n--- TEST CASE 3: Master / Admin Access (Superuser) ---\n";
$adminUser = User::where('username', 'admin')->first();
$allPassed &= testUrl($adminUser, '/saturnus/form-unregistrasi', 200, $httpKernel);
$allPassed &= testUrl($adminUser, '/saturnus/unregistrasi-approval', 200, $httpKernel);
$allPassed &= testUrl($adminUser, '/saturnus/unregistrasi-history', 200, $httpKernel);
$allPassed &= testUrl($adminUser, '/mars/item-master', 200, $httpKernel);
$allPassed &= testUrl($adminUser, '/settings/roles', 200, $httpKernel);
$allPassed &= testUrl($adminUser, '/settings/users', 200, $httpKernel);

// 4. PRODUCTION USER ACCESS
echo "\n--- TEST CASE 4: Production User Access ---\n";
$prodUser = User::where('username', 'budi_user')->first();
$allPassed &= testUrl($prodUser, '/saturnus/form-unregistrasi', 200, $httpKernel);
$allPassed &= testUrl($prodUser, '/saturnus/form-registrasi', 200, $httpKernel);
$allPassed &= testUrl($prodUser, '/settings/roles', 403, $httpKernel);
$allPassed &= testUrl($prodUser, '/settings/users', 403, $httpKernel);

// 5. GUEST USER ACCESS
echo "\n--- TEST CASE 5: Guest User Access ---\n";
$guestUser = User::where('username', 'guest')->first();
$allPassed &= testUrl($guestUser, '/dashboard', 200, $httpKernel);
$allPassed &= testUrl($guestUser, '/saturnus/form-unregistrasi', 403, $httpKernel);
$allPassed &= testUrl($guestUser, '/settings/roles', 403, $httpKernel);

echo "\n===============================================================\n";
if ($allPassed) {
    echo "🎉 ALL TESTS PASSED SUCCESSFULLY! RBAC IS ROCK SOLID!\n";
} else {
    echo "❌ SOME TESTS FAILED!\n";
    exit(1);
}
echo "===============================================================\n";
