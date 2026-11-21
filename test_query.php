<?php
require __DIR__ . '/vendor/autoload.php';
require __DIR__ . '/bootstrap/app.php';

$db = \CodeIgniter\Database\Config\Database::connect();

// Test query with parameter
$result = $db->query('SELECT * FROM firmas WHERE dni = ?', ['223456723'])->getResultArray();
echo "Test 1 (223456723): " . json_encode($result) . "\n";

$result = $db->query('SELECT * FROM firmas WHERE dni = ?', ['999999999'])->getResultArray();
echo "Test 2 (999999999): " . json_encode($result) . "\n";

$result = $db->query('SELECT * FROM firmas WHERE dni = ?', ['123456789'])->getResultArray();
echo "Test 3 (123456789): " . json_encode($result) . "\n";