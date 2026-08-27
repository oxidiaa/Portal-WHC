<?php
$marsDb = new PDO('sqlite:' . __DIR__ . '/MARS/database/database.sqlite');
echo "=== MARS TABLES ===\n";
$tables = $marsDb->query("SELECT name FROM sqlite_master WHERE type='table'")->fetchAll(PDO::FETCH_COLUMN);
foreach ($tables as $table) {
    $count = $marsDb->query("SELECT COUNT(*) FROM `{$table}`")->fetchColumn();
    echo "- $table: $count rows\n";
}

$saturnusDb = new PDO('sqlite:' . __DIR__ . '/SATURNUS/database/database.sqlite');
echo "\n=== SATURNUS TABLES ===\n";
$tables = $saturnusDb->query("SELECT name FROM sqlite_master WHERE type='table'")->fetchAll(PDO::FETCH_COLUMN);
foreach ($tables as $table) {
    $count = $saturnusDb->query("SELECT COUNT(*) FROM `{$table}`")->fetchColumn();
    echo "- $table: $count rows\n";
}
