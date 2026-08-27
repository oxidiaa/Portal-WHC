<?php
$marsDb = new PDO('sqlite:' . __DIR__ . '/MARS/database/database.sqlite');
$tables = ['item_masters', 'data_pos', 'kedatangan_barangs', 'histories', 'item_outstandings', 'follow_up_pos'];
foreach ($tables as $t) {
    $cols = $marsDb->query("PRAGMA table_info('{$t}')")->fetchAll(PDO::FETCH_ASSOC);
    echo "=== Table $t ===\n";
    foreach ($cols as $c) {
        echo "- {$c['name']} ({$c['type']})\n";
    }
}
