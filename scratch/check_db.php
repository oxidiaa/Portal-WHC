<?php

require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\DB;
use App\Models\User;

echo "=== CHECKING JOBS TABLE ===\n";
try {
    $jobsCount = DB::table('jobs')->count();
    echo "Total jobs in queue: {$jobsCount}\n";
    if ($jobsCount > 0) {
        $jobs = DB::table('jobs')->limit(5)->get();
        foreach ($jobs as $j) {
            echo "- Job ID {$j->id}: queue={$j->queue}, attempts={$j->attempts}, payload snippet=" . substr($j->payload, 0, 150) . "\n";
        }
    }
} catch (\Throwable $e) {
    echo "Jobs table error: " . $e->getMessage() . "\n";
}

echo "\n=== CHECKING FAILED_JOBS TABLE ===\n";
try {
    $failedCount = DB::table('failed_jobs')->count();
    echo "Total failed jobs: {$failedCount}\n";
    if ($failedCount > 0) {
        $failed = DB::table('failed_jobs')->orderByDesc('id')->limit(5)->get();
        foreach ($failed as $f) {
            echo "- Failed ID {$f->id}: {$f->failed_at} - {$f->exception}\n";
        }
    }
} catch (\Throwable $e) {
    echo "Failed jobs table error: " . $e->getMessage() . "\n";
}

echo "\n=== CHECKING USERS & EMAILS ===\n";
$users = User::select('id', 'name', 'username', 'email', 'role', 'department', 'status')->get();
foreach ($users as $u) {
    echo "User #{$u->id}: {$u->name} | username: {$u->username} | email: [{$u->email}] | role: {$u->role} | dept: {$u->department} | status: {$u->status}\n";
}

echo "\n=== CHECKING EMAIL LOGS (IF TABLE EXISTS) ===\n";
try {
    $tables = DB::select("SELECT name FROM sqlite_master WHERE type='table'");
    foreach ($tables as $t) {
        if (str_contains($t->name, 'mail') || str_contains($t->name, 'log') || str_contains($t->name, 'reminder') || str_contains($t->name, 'schedule')) {
            echo "Found table: {$t->name}\n";
            $rows = DB::table($t->name)->limit(5)->get();
            echo "  Sample count: " . count($rows) . "\n";
        }
    }
} catch (\Throwable $e) {
    echo "Error checking tables: " . $e->getMessage() . "\n";
}
