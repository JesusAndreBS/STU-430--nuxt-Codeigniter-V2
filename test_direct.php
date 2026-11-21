<?php
require __DIR__ . '/vendor/autoload.php';

use CodeIgniter\Database\Config;
use CodeIgniter\Database\MySQLi\Connection;

$config = new Config([
    'hostname' => 'db',
    'username' => 'stu430',
    'password' => 'stu430pass',
    'database' => 'stu430',
    'DBDriver' => 'MySQLi',
    'port' => 3306,
    'charset' => 'utf8mb4',
    'DBCollat' => 'utf8mb4_general_ci',
]);

$db = new Connection($config);
$db->connect();

echo "Connected to DB\n";

// Test 1: Direct query with parameter
echo "\n--- Test 1: Parameter binding ---\n";
$sql = "SELECT * FROM firmas WHERE dni = ?";
$stmt = $db->conn_id->prepare($sql);
$param = '223456723';
$stmt->bind_param('s', $param);
$stmt->execute();
$result = $stmt->get_result();
$rows = $result->fetch_all(MYSQLI_ASSOC);
echo "Test 1 (223456723): " . json_encode($rows) . "\n";

// Test 2: Direct query with parameter
echo "\n--- Test 2: Parameter binding 999999999 ---\n";
$param = '999999999';
$stmt->bind_param('s', $param);
$stmt->execute();
$result = $stmt->get_result();
$rows = $result->fetch_all(MYSQLI_ASSOC);
echo "Test 2 (999999999): " . json_encode($rows) . "\n";

// Test 3: Direct query with parameter
echo "\n--- Test 3: Parameter binding 123456789 ---\n";
$param = '123456789';
$stmt->bind_param('s', $param);
$stmt->execute();
$result = $stmt->get_result();
$rows = $result->fetch_all(MYSQLI_ASSOC);
echo "Test 3 (123456789): " . json_encode($rows) . "\n";

// Test 4: CodeIgniter query method
echo "\n--- Test 4: CodeIgniter query() method ---\n";
$rows = $db->query('SELECT * FROM firmas WHERE dni = ?', ['223456723'])->getResultArray();
echo "Test 4 (223456723): " . json_encode($rows) . "\n";

$rows = $db->query('SELECT * FROM firmas WHERE dni = ?', ['999999999'])->getResultArray();
echo "Test 4 (999999999): " . json_encode($rows) . "\n";

$rows = $db->query('SELECT * FROM firmas WHERE dni = ?', ['123456789'])->getResultArray();
echo "Test 4 (123456789): " . json_encode($rows) . "\n";