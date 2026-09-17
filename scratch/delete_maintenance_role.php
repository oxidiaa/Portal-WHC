<?php

use App\Models\Role;
use App\Models\User;

echo "=== DELETING MAINTENANCE ROLE ===\n";

$roles = Role::where('slug', 'maintenance')->orWhere('name', 'like', '%maintenance%')->get();

foreach ($roles as $role) {
    echo "Found role: ID={$role->id}, Name={$role->name}, Slug={$role->slug}\n";
    $role->permissions()->detach();
    $role->delete();
    echo "Role {$role->name} successfully deleted from database.\n";
}

// Also check if any users had maintenance role and update them to User if any
$users = User::where('role', 'like', '%maintenance%')->get();
foreach ($users as $u) {
    echo "Updating user {$u->name} from role {$u->role} to User\n";
    $u->role = 'User';
    $u->save();
}

echo "Remaining roles:\n";
foreach (Role::all() as $r) {
    echo "- [ID: {$r->id}] {$r->name} (Slug: {$r->slug})\n";
}

echo "=== FINISHED ===\n";
