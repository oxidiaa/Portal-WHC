<?php

$domains = ['mai.co.id', 'mai.com', 'stockmin.com', 'metalart-astra.co.id', 'gmail.com'];

foreach ($domains as $domain) {
    echo "Checking MX records for $domain:\n";
    $mxrecords = [];
    $hasMx = getmxrr($domain, $mxrecords);
    if ($hasMx) {
        echo "  MX records found: " . implode(', ', $mxrecords) . "\n";
    } else {
        echo "  NO MX RECORDS FOUND (Domain cannot receive emails)!\n";
    }
}
