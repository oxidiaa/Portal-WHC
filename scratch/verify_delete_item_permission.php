<?php

use App\Models\User;
use App\Models\FormItem;
use App\Models\UnregistrasiItem;
use App\Http\Controllers\Saturnus\ItemController;
use App\Http\Controllers\Saturnus\FormUnregistrasiController;
use Illuminate\Support\Facades\Auth;

echo "=== VERIFYING DELETE ITEM ACCESS RESTRICTIONS ===\n\n";

$rolesToTest = ['USER', 'STAFF', 'WAREHOUSE', 'ACCOUNTING', 'PURCHASING'];
$itemController = new ItemController();
$unregController = new FormUnregistrasiController();

// 1. Test Form Registrasi Item Delete
echo "--- 1. Testing Form Registrasi Item Deletion ---\n";
$regItem = FormItem::create([
    'form_number' => '99/TEST-ITEM/09-2026',
    'no_part' => 'TEST-REG-ITEM-01',
    'nama_barang' => 'Test Item Reg Delete Perm',
    'status' => 'PENDING',
    'created_by_name' => 'Tester',
    'created_by_dept' => 'PRODUCTION',
]);

foreach ($rolesToTest as $roleName) {
    $user = User::where('role', $roleName)->orWhere('role', strtolower($roleName))->first();
    if (!$user) {
        $user = new User([
            'name' => 'Test ' . $roleName,
            'username' => 'test_' . strtolower($roleName),
            'role' => $roleName,
            'department' => 'PRODUCTION',
        ]);
    }

    Auth::login($user);
    $response = $itemController->deleteFormItem($regItem->id);
    
    $sessionError = session('error');
    $stillExists = FormItem::where('id', $regItem->id)->exists();
    
    echo "Role: {$roleName} -> Blocked: " . ($stillExists ? "YES" : "NO") . " | Error: {$sessionError}\n";
}

$adminUser = User::whereIn('role', ['MASTER', 'ADMIN', 'master', 'admin'])->first();
if (!$adminUser) {
    $adminUser = new User([
        'name' => 'Admin User',
        'username' => 'admin',
        'role' => 'MASTER',
        'department' => 'IT',
    ]);
}

Auth::login($adminUser);
$response = $itemController->deleteFormItem($regItem->id);
$stillExists = FormItem::where('id', $regItem->id)->exists();
$sessionSuccess = session('success');

echo "\nRole: Admin ({$adminUser->role}) -> Deleted: " . (!$stillExists ? "YES" : "NO") . " | Success: {$sessionSuccess}\n";


// 2. Test Form Unregistrasi Item Delete
echo "\n--- 2. Testing Form Unregistrasi Item Deletion ---\n";
$unregItem = UnregistrasiItem::create([
    'form_number' => '99/TEST-UNREG-ITEM/09-2026',
    'no_part' => 'TEST-UNREG-ITEM-01',
    'nama_barang' => 'Test Unreg Item Delete Perm',
    'status' => 'PENDING',
    'created_by_name' => 'Tester',
    'created_by_dept' => 'PRODUCTION',
]);

foreach ($rolesToTest as $roleName) {
    $user = User::where('role', $roleName)->orWhere('role', strtolower($roleName))->first();
    if (!$user) {
        $user = new User([
            'name' => 'Test ' . $roleName,
            'username' => 'test_' . strtolower($roleName),
            'role' => $roleName,
            'department' => 'PRODUCTION',
        ]);
    }

    Auth::login($user);
    $response = $unregController->deleteFormItem($unregItem->id);
    
    $sessionError = session('error');
    $stillExists = UnregistrasiItem::where('id', $unregItem->id)->exists();
    
    echo "Role: {$roleName} -> Blocked: " . ($stillExists ? "YES" : "NO") . " | Error: {$sessionError}\n";
}

Auth::login($adminUser);
$response = $unregController->deleteFormItem($unregItem->id);
$stillExists = UnregistrasiItem::where('id', $unregItem->id)->exists();
$sessionSuccess = session('success');

echo "\nRole: Admin ({$adminUser->role}) -> Deleted: " . (!$stillExists ? "YES" : "NO") . " | Success: {$sessionSuccess}\n";

echo "\n=== ALL DELETE ITEM TESTS COMPLETED ===\n";
