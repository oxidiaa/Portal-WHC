<?php
$marsDb = new PDO('sqlite:' . __DIR__ . '/MARS/database/database.sqlite');
echo "=== MARS USERS ===\n";
$users1 = $marsDb->query("SELECT id, name, username, email FROM users")->fetchAll(PDO::FETCH_ASSOC);
print_r($users1);

$saturnusDb = new PDO('sqlite:' . __DIR__ . '/SATURNUS/database/database.sqlite');
echo "\n=== SATURNUS USERS ===\n";
$users2 = $saturnusDb->query("SELECT id, name, email, department, role, status FROM users")->fetchAll(PDO::FETCH_ASSOC);
print_r($users2);
