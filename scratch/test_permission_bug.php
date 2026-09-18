<?php

require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\User;
use App\Models\Role;
use App\Models\Permission;

echo "=======================================================\n";
echo "RBAC PERMISSION REPRODUCTION & VERIFICATION TEST\n";
echo "=======================================================\n\n";

// 1. Get Accounting Role
$accountingRole = Role::with('permissions')->where('slug', 'accounting')->first();
if (!$accountingRole) {
    echo "ERROR: Role accounting not found!\n";
    exit(1);
}

echo "Role: " . $accountingRole->name . " (" . $accountingRole->slug . ")\n";
echo "Current permissions:\n";
foreach ($accountingRole->permissions as $p) {
    echo " - " . $p->slug . " (" . $p->name . ")\n";
}

// 2. Remove saturnus.unregistrasi.view from Accounting if present
$unregPerm = Permission::where('slug', 'saturnus.unregistrasi.view')->first();
$unregApprovePerm = Permission::where('slug', 'saturnus.unregistrasi.approve')->first();
$unregCreatePerm = Permission::where('slug', 'saturnus.unregistrasi.create')->first();

$removeIds = array_filter([$unregPerm?->id, $unregApprovePerm?->id, $unregCreatePerm?->id]);
$currentPermIds = $accountingRole->permissions->pluck('id')->toArray();
$newPermIds = array_diff($currentPermIds, $removeIds);

$accountingRole->permissions()->sync($newPermIds);
$accountingRole->load('permissions');

echo "\nAfter removing unregistrasi permissions from Accounting role:\n";
foreach ($accountingRole->permissions as $p) {
    echo " - " . $p->slug . "\n";
}

// 3. Find accounting user
$accountingUser = User::where('role', 'Accounting')->orWhere('username', 'accounting')->first();
if (!$accountingUser) {
    echo "ERROR: User accounting not found!\n";
    exit(1);
}
echo "\nTesting with user: {$accountingUser->name} (Username: {$accountingUser->username}, Role: {$accountingUser->role})\n";

// Test permission check directly
$hasUnregView = $accountingUser->hasPermission('saturnus.unregistrasi.view');
$hasSaturnusWildcard = $accountingUser->hasPermission('saturnus.*');
$canAccessSaturnusModule = $accountingUser->canAccessModule('saturnus');

echo "\nDirect Permission Checks:\n";
echo " - hasPermission('saturnus.unregistrasi.view'): " . ($hasUnregView ? "TRUE (BUG! Should be FALSE)" : "FALSE (CORRECT)") . "\n";
echo " - hasPermission('saturnus.*'): " . ($hasSaturnusWildcard ? "TRUE" : "FALSE") . "\n";
echo " - canAccessModule('saturnus'): " . ($canAccessSaturnusModule ? "TRUE" : "FALSE") . "\n";

// Test HTTP request through Laravel HTTP Kernel
$request = Illuminate\Http\Request::create('/saturnus/form-unregistrasi', 'GET');
$httpKernel = $app->make(Illuminate\Contracts\Http\Kernel::class);

// Login as accounting user
auth()->login($accountingUser);

$response = $httpKernel->handle($request);
echo "\nHTTP GET /saturnus/form-unregistrasi Status: " . $response->getStatusCode() . "\n";
if ($response->getStatusCode() === 403) {
    echo "=> SUCCESS: Access Denied with 403 Forbidden as expected!\n";
} else {
    echo "=> BUG: Access ALLOWED with status " . $response->getStatusCode() . "!\n";
}

