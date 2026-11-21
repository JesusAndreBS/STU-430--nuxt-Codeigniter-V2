<?php
$mysqli = new mysqli('db', 'stu430', 'stu430pass', 'stu430', 3306);

if ($mysqli->connect_error) {
    die("Connection failed: " . $mysqli->connect_error);
}

echo "Connected successfully\n\n";

// Test 1: Prepared statement
echo "--- Test 1: Prepared statement (223456723) ---\n";
$stmt = $mysqli->prepare("SELECT * FROM firmas WHERE dni = ?");
$param = '223456723';
$stmt->bind_param('s', $param);
$stmt->execute();
$result = $stmt->get_result();
$rows = $result->fetch_all(MYSQLI_ASSOC);
echo "Test 1 (223456723): " . json_encode($rows) . "\n";

// Test 2
echo "\n--- Test 2: Prepared statement (999999999) ---\n";
$param = '999999999';
$stmt->bind_param('s', $param);
$stmt->execute();
$result = $stmt->get_result();
$rows = $result->fetch_all(MYSQLI_ASSOC);
echo "Test 2 (999999999): " . json_encode($rows) . "\n";

// Test 3
echo "\n--- Test 3: Prepared statement (123456789) ---\n";
$param = '123456789';
$stmt->bind_param('s', $param);
$stmt->execute();
$result = $stmt->get_result();
$rows = $result->fetch_all(MYSQLI_ASSOC);
echo "Test 3 (123456789): " . json_encode($rows) . "\n";

// Test 4: Direct query (no prepare)
echo "\n--- Test 4: Direct query (223456723) ---\n";
$result = $mysqli->query("SELECT * FROM firmas WHERE dni = '223456723'");
$rows = $result->fetch_all(MYSQLI_ASSOC);
echo "Test 4 (223456723): " . json_encode($rows) . "\n";

echo "\n--- Test 5: Direct query (999999999) ---\n";
$result = $mysqli->query("SELECT * FROM firmas WHERE dni = '999999999'");
$rows = $result->fetch_all(MYSQLI_ASSOC);
echo "Test 5 (999999999): " . json_encode($rows) . "\n";

echo "\n--- Test 6: Direct query (123456789) ---\n";
$result = $mysqli->query("SELECT * FROM firmas WHERE dni = '123456789'");
$rows = $result->fetch_all(MYSQLI_ASSOC);
echo "Test 6 (123456789): " . json_encode($rows) . "\n";

$mysqli->close();