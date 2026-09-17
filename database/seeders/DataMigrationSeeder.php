<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use PDO;

class DataMigrationSeeder extends Seeder
{
    /**
     * Run the database seeds to migrate existing data from MARS and SATURNUS sqlite files
     * while cleaning any test/dummy data.
     */
    public function run(): void
    {
        $baseDir = dirname(base_path()); // c:/Code/Project/warehouse
        $marsPath = $baseDir . '/MARS/database/database.sqlite';
        $saturnusPath = $baseDir . '/SATURNUS/database/database.sqlite';

        // 1. Clean test/dummy data from SATURNUS and MARS tables
        $this->command->info("Cleaning test and dummy data from MARS & SATURNUS tables...");

        // MARS tables
        DB::table('item_masters')->truncate();
        DB::table('data_pos')->truncate();
        DB::table('follow_up_pos')->truncate();
        DB::table('item_outstandings')->truncate();
        DB::table('kedatangan_barangs')->truncate();
        DB::table('histories')->truncate();

        // SATURNUS tables
        DB::table('items')->truncate();
        DB::table('form_items')->truncate();
        DB::table('form_approvals')->truncate();
        DB::table('form_comments')->truncate();
        DB::table('unregistrasi_items')->truncate();
        DB::table('unregistrasi_approvals')->truncate();
        DB::table('unregistrasi_comments')->truncate();

        // 2. Migrate Authentic Records from SATURNUS
        if (file_exists($saturnusPath)) {
            $this->command->info("Migrating authentic data from SATURNUS: {$saturnusPath}");
            $saturnusDb = new PDO("sqlite:{$saturnusPath}");

            // Migrate Users (authentic Saturnus accounts)
            $users = $saturnusDb->query("SELECT * FROM users")->fetchAll(PDO::FETCH_ASSOC);
            foreach ($users as $u) {
                $username = strtolower(trim($u['email'])); // in saturnus email was username like 'admin', 'budi_user', etc.
                $email = str_contains($u['email'], '@') ? $u['email'] : ($username . '@mai.co.id');
                
                $existing = DB::table('users')
                    ->where('username', $username)
                    ->orWhere('email', $email)
                    ->orWhere('id', $u['id'])
                    ->first();

                if ($existing) {
                    DB::table('users')->where('id', $existing->id)->update([
                        'name'       => $u['name'],
                        'username'   => $username,
                        'email'      => $email,
                        'department' => $u['department'] ?? ($existing->department ?? 'Production'),
                        'role'       => $u['role'] ?? ($existing->role ?? 'User'),
                        'status'     => $u['status'] ?? 'Aktif',
                        'password'   => $u['password'],
                        'remember_token' => $u['remember_token'] ?? null,
                    ]);
                } else {
                    DB::table('users')->insert([
                        'id'         => $u['id'],
                        'name'       => $u['name'],
                        'username'   => $username,
                        'email'      => $email,
                        'department' => $u['department'] ?? 'Production',
                        'role'       => $u['role'] ?? 'User',
                        'status'     => $u['status'] ?? 'Aktif',
                        'password'   => $u['password'],
                        'remember_token' => $u['remember_token'] ?? null,
                        'created_at' => $u['created_at'] ?? now(),
                        'updated_at' => $u['updated_at'] ?? now(),
                    ]);
                }
            }

            // Migrate Authentic Items
            $items = $saturnusDb->query("SELECT * FROM items")->fetchAll(PDO::FETCH_ASSOC);
            foreach ($items as $item) {
                DB::table('items')->insert($item);
            }
            $this->command->info("Migrated " . count($items) . " authentic items.");

            // Migrate Authentic Form Items
            $formItems = $saturnusDb->query("SELECT * FROM form_items")->fetchAll(PDO::FETCH_ASSOC);
            foreach ($formItems as $fi) {
                DB::table('form_items')->insert($fi);
            }
            $this->command->info("Migrated " . count($formItems) . " authentic form_items.");

            // Migrate Authentic Form Approvals
            $formApprovals = $saturnusDb->query("SELECT * FROM form_approvals")->fetchAll(PDO::FETCH_ASSOC);
            foreach ($formApprovals as $fa) {
                DB::table('form_approvals')->insert($fa);
            }
            $this->command->info("Migrated " . count($formApprovals) . " authentic form_approvals.");

            // Migrate Authentic Form Comments
            $formComments = $saturnusDb->query("SELECT * FROM form_comments")->fetchAll(PDO::FETCH_ASSOC);
            foreach ($formComments as $fc) {
                DB::table('form_comments')->insert($fc);
            }

            // Migrate Authentic Unregistrasi Items
            $unregItems = $saturnusDb->query("SELECT * FROM unregistrasi_items")->fetchAll(PDO::FETCH_ASSOC);
            foreach ($unregItems as $ui) {
                DB::table('unregistrasi_items')->insert($ui);
            }
            $this->command->info("Migrated " . count($unregItems) . " authentic unregistrasi_items.");

            // Migrate Authentic Unregistrasi Approvals
            $unregApprovals = $saturnusDb->query("SELECT * FROM unregistrasi_approvals")->fetchAll(PDO::FETCH_ASSOC);
            foreach ($unregApprovals as $ua) {
                DB::table('unregistrasi_approvals')->insert($ua);
            }
            $this->command->info("Migrated " . count($unregApprovals) . " authentic unregistrasi_approvals.");

            // Migrate Authentic Unregistrasi Comments
            $unregComments = $saturnusDb->query("SELECT * FROM unregistrasi_comments")->fetchAll(PDO::FETCH_ASSOC);
            foreach ($unregComments as $uc) {
                DB::table('unregistrasi_comments')->insert($uc);
            }
        }

        // 3. Migrate Authentic Data from MARS (if any records exist in source)
        if (file_exists($marsPath)) {
            $marsDb = new PDO("sqlite:{$marsPath}");

            // Migrate Item Masters
            $tables = $marsDb->query("SELECT name FROM sqlite_master WHERE type='table' AND name='item_masters'")->fetchAll();
            if (!empty($tables)) {
                $itemMasters = $marsDb->query("SELECT * FROM item_masters")->fetchAll(PDO::FETCH_ASSOC);
                if (count($itemMasters) > 0) {
                    $this->command->info("Migrating " . count($itemMasters) . " authentic item_masters from MARS");
                    foreach (array_chunk($itemMasters, 500) as $chunk) {
                        DB::table('item_masters')->insert($chunk);
                    }
                }
            }

            // Migrate Data POs
            $tables = $marsDb->query("SELECT name FROM sqlite_master WHERE type='table' AND name='data_pos'")->fetchAll();
            if (!empty($tables)) {
                $dataPos = $marsDb->query("SELECT * FROM data_pos")->fetchAll(PDO::FETCH_ASSOC);
                if (count($dataPos) > 0) {
                    $this->command->info("Migrating " . count($dataPos) . " authentic data_pos from MARS");
                    foreach (array_chunk($dataPos, 500) as $chunk) {
                        DB::table('data_pos')->insert($chunk);
                    }
                }
            }

            // Migrate Kedatangan Barangs
            $tables = $marsDb->query("SELECT name FROM sqlite_master WHERE type='table' AND name='kedatangan_barangs'")->fetchAll();
            if (!empty($tables)) {
                $kedatangan = $marsDb->query("SELECT * FROM kedatangan_barangs")->fetchAll(PDO::FETCH_ASSOC);
                if (count($kedatangan) > 0) {
                    $this->command->info("Migrating " . count($kedatangan) . " authentic kedatangan_barangs from MARS");
                    foreach (array_chunk($kedatangan, 500) as $chunk) {
                        DB::table('kedatangan_barangs')->insert($chunk);
                    }
                }
            }

            // Migrate Histories
            $tables = $marsDb->query("SELECT name FROM sqlite_master WHERE type='table' AND name='histories'")->fetchAll();
            if (!empty($tables)) {
                $histories = $marsDb->query("SELECT * FROM histories")->fetchAll(PDO::FETCH_ASSOC);
                if (count($histories) > 0) {
                    $this->command->info("Migrating " . count($histories) . " authentic histories from MARS");
                    foreach (array_chunk($histories, 500) as $chunk) {
                        DB::table('histories')->insert($chunk);
                    }
                }
            }

            // Migrate Item Outstandings
            $tables = $marsDb->query("SELECT name FROM sqlite_master WHERE type='table' AND name='item_outstandings'")->fetchAll();
            if (!empty($tables)) {
                $outstandings = $marsDb->query("SELECT * FROM item_outstandings")->fetchAll(PDO::FETCH_ASSOC);
                if (count($outstandings) > 0) {
                    $this->command->info("Migrating " . count($outstandings) . " authentic item_outstandings from MARS");
                    foreach (array_chunk($outstandings, 500) as $chunk) {
                        DB::table('item_outstandings')->insert($chunk);
                    }
                }
            }

            // Migrate Follow Up POs
            $tables = $marsDb->query("SELECT name FROM sqlite_master WHERE type='table' AND name='follow_up_pos'")->fetchAll();
            if (!empty($tables)) {
                $followUps = $marsDb->query("SELECT * FROM follow_up_pos")->fetchAll(PDO::FETCH_ASSOC);
                if (count($followUps) > 0) {
                    $this->command->info("Migrating " . count($followUps) . " authentic follow_up_pos from MARS");
                    foreach (array_chunk($followUps, 500) as $chunk) {
                        DB::table('follow_up_pos')->insert($chunk);
                    }
                }
            }
        }

        $this->command->info("Database successfully cleaned of dummy data and refreshed with authentic records.");
    }
}
