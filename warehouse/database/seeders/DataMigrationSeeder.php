<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use PDO;

class DataMigrationSeeder extends Seeder
{
    /**
     * Run the database seeds to migrate existing data from MARS and SATURNUS sqlite files.
     */
    public function run(): void
    {
        $baseDir = dirname(base_path()); // c:/Code/Project/warehouse
        $marsPath = $baseDir . '/MARS/database/database.sqlite';
        $saturnusPath = $baseDir . '/SATURNUS/database/database.sqlite';

        // 1. Migrate Users from SATURNUS
        if (file_exists($saturnusPath)) {
            $this->command->info("Migrating data from SATURNUS: {$saturnusPath}");
            $saturnusDb = new PDO("sqlite:{$saturnusPath}");

            // Migrate Users
            $users = $saturnusDb->query("SELECT * FROM users")->fetchAll(PDO::FETCH_ASSOC);
            foreach ($users as $u) {
                $username = strtolower(trim($u['email'])); // in saturnus email was username like 'admin', 'budi_user', etc.
                $email = str_contains($u['email'], '@') ? $u['email'] : ($username . '@mai.co.id');
                
                // Check if user exists by username or email
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

            // Migrate Items
            $items = $saturnusDb->query("SELECT * FROM items")->fetchAll(PDO::FETCH_ASSOC);
            foreach ($items as $item) {
                DB::table('items')->updateOrInsert(
                    ['id' => $item['id']],
                    $item
                );
            }

            // Migrate Form Items
            $formItems = $saturnusDb->query("SELECT * FROM form_items")->fetchAll(PDO::FETCH_ASSOC);
            foreach ($formItems as $fi) {
                DB::table('form_items')->updateOrInsert(
                    ['id' => $fi['id']],
                    $fi
                );
            }

            // Migrate Form Approvals
            $formApprovals = $saturnusDb->query("SELECT * FROM form_approvals")->fetchAll(PDO::FETCH_ASSOC);
            foreach ($formApprovals as $fa) {
                DB::table('form_approvals')->updateOrInsert(
                    ['id' => $fa['id']],
                    $fa
                );
            }

            // Migrate Form Comments
            $formComments = $saturnusDb->query("SELECT * FROM form_comments")->fetchAll(PDO::FETCH_ASSOC);
            foreach ($formComments as $fc) {
                DB::table('form_comments')->updateOrInsert(
                    ['id' => $fc['id']],
                    $fc
                );
            }

            // Migrate Unregistrasi Items
            $unregItems = $saturnusDb->query("SELECT * FROM unregistrasi_items")->fetchAll(PDO::FETCH_ASSOC);
            foreach ($unregItems as $ui) {
                DB::table('unregistrasi_items')->updateOrInsert(
                    ['id' => $ui['id']],
                    $ui
                );
            }

            // Migrate Unregistrasi Approvals
            $unregApprovals = $saturnusDb->query("SELECT * FROM unregistrasi_approvals")->fetchAll(PDO::FETCH_ASSOC);
            foreach ($unregApprovals as $ua) {
                DB::table('unregistrasi_approvals')->updateOrInsert(
                    ['id' => $ua['id']],
                    $ua
                );
            }

            // Migrate Unregistrasi Comments
            $unregComments = $saturnusDb->query("SELECT * FROM unregistrasi_comments")->fetchAll(PDO::FETCH_ASSOC);
            foreach ($unregComments as $uc) {
                DB::table('unregistrasi_comments')->updateOrInsert(
                    ['id' => $uc['id']],
                    $uc
                );
            }
        }

        // 2. Migrate Data from MARS
        if (file_exists($marsPath)) {
            $this->command->info("Migrating data from MARS: {$marsPath}");
            $marsDb = new PDO("sqlite:{$marsPath}");

            // Migrate Item Masters
            $itemMasters = $marsDb->query("SELECT * FROM item_masters")->fetchAll(PDO::FETCH_ASSOC);
            $this->command->info("Found " . count($itemMasters) . " item_masters in MARS");
            foreach (array_chunk($itemMasters, 500) as $chunk) {
                DB::table('item_masters')->upsert($chunk, ['id']);
            }

            // Migrate Data POs
            $dataPos = $marsDb->query("SELECT * FROM data_pos")->fetchAll(PDO::FETCH_ASSOC);
            $this->command->info("Found " . count($dataPos) . " data_pos in MARS");
            foreach (array_chunk($dataPos, 500) as $chunk) {
                DB::table('data_pos')->upsert($chunk, ['id']);
            }

            // Migrate Kedatangan Barangs
            $kedatangan = $marsDb->query("SELECT * FROM kedatangan_barangs")->fetchAll(PDO::FETCH_ASSOC);
            $this->command->info("Found " . count($kedatangan) . " kedatangan_barangs in MARS");
            foreach (array_chunk($kedatangan, 500) as $chunk) {
                DB::table('kedatangan_barangs')->upsert($chunk, ['id']);
            }

            // Migrate Histories
            $histories = $marsDb->query("SELECT * FROM histories")->fetchAll(PDO::FETCH_ASSOC);
            $this->command->info("Found " . count($histories) . " histories in MARS");
            foreach (array_chunk($histories, 500) as $chunk) {
                DB::table('histories')->upsert($chunk, ['id']);
            }

            // Migrate Item Outstandings
            $outstandings = $marsDb->query("SELECT * FROM item_outstandings")->fetchAll(PDO::FETCH_ASSOC);
            $this->command->info("Found " . count($outstandings) . " item_outstandings in MARS");
            foreach (array_chunk($outstandings, 500) as $chunk) {
                DB::table('item_outstandings')->upsert($chunk, ['id']);
            }

            // Migrate Follow Up POs
            $followUps = $marsDb->query("SELECT * FROM follow_up_pos")->fetchAll(PDO::FETCH_ASSOC);
            $this->command->info("Found " . count($followUps) . " follow_up_pos in MARS");
            foreach (array_chunk($followUps, 500) as $chunk) {
                DB::table('follow_up_pos')->upsert($chunk, ['id']);
            }
        }
    }
}
