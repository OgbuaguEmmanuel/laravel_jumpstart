<?php

// File name for the CSV
$outputFile = __DIR__.'/users_million.csv';

// Open file for writing
$handle = fopen($outputFile, 'w');

if ($handle === false) {
    exit('Unable to open file for writing.');
}

// Write CSV header
fputcsv($handle, ['first_name', 'last_name', 'email', 'role']);

// Available roles (nullable allowed)
$roles = ['admin', 'user', 'super admin', ''];

// Generate 1 million records
$totalRecords = 1000000;

for ($i = 1; $i <= $totalRecords; $i++) {
    $firstName = 'FirstName'.$i;
    $lastName = 'LastName'.$i;
    $email = 'user'.$i.'@laraveljumpstart.com';
    $role = $roles[array_rand($roles)];

    fputcsv($handle, [$firstName, $lastName, $email, $role]);

    // Optional: display progress every 100k rows
    if ($i % 100000 === 0) {
        echo "Generated {$i} records...\n";
    }
}

fclose($handle);

echo "CSV file created successfully at: {$outputFile}\n";
