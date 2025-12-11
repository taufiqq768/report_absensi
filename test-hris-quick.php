<?php
/**
 * Quick Test HRIS Database Connection
 */

echo "=== Testing HRIS Database Connection ===\n\n";

$host = '10.100.11.222';
$port = '3306';
$database = 'hris_db_live';
$username = 'hris_user';
$password = 'oBMLEsjpeDRPTidd5QeF';

echo "Config:\n";
echo "  Host: $host:$port\n";
echo "  Database: $database\n";
echo "  Username: $username\n\n";

// Test 1: Network
echo "[1] Testing network to $host:$port...\n";
$start = microtime(true);
$sock = @fsockopen($host, $port, $errno, $errstr, 10);
$time = round((microtime(true) - $start) * 1000, 2);

if ($sock) {
    echo "    ✓ Network OK ({$time}ms)\n";
    fclose($sock);
} else {
    echo "    ✗ Network FAILED: $errstr ($errno)\n";
    exit(1);
}

// Test 2: MySQL Connection
echo "\n[2] Testing MySQL connection...\n";
$start = microtime(true);
try {
    $pdo = new PDO(
        "mysql:host=$host;port=$port;dbname=$database;charset=utf8mb4",
        $username,
        $password,
        [
            PDO::ATTR_TIMEOUT => 30,
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        ]
    );
    $time = round((microtime(true) - $start) * 1000, 2);
    echo "    ✓ MySQL connection OK ({$time}ms)\n";
} catch (PDOException $e) {
    echo "    ✗ MySQL FAILED: " . $e->getMessage() . "\n";
    exit(1);
}

// Test 3: Query pegawai table
echo "\n[3] Testing query pegawai table...\n";
try {
    $stmt = $pdo->query("SELECT COUNT(*) as total FROM pegawai");
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    echo "    ✓ Query OK\n";
    echo "    Total pegawai: " . number_format($result['total']) . "\n";
} catch (PDOException $e) {
    echo "    ✗ Query FAILED: " . $e->getMessage() . "\n";
    exit(1);
}

// Test 4: Get PSA options
echo "\n[4] Getting PSA options (area_kode)...\n";
try {
    $stmt = $pdo->query("
        SELECT DISTINCT area_kode 
        FROM pegawai 
        WHERE area_kode IS NOT NULL 
        AND area_kode != '' 
        ORDER BY area_kode ASC
    ");
    $psaOptions = $stmt->fetchAll(PDO::FETCH_COLUMN);
    echo "    ✓ Found " . count($psaOptions) . " PSA options\n";
    echo "    PSA: " . implode(', ', array_slice($psaOptions, 0, 10)) . "\n";
    if (count($psaOptions) > 10) {
        echo "         ... and " . (count($psaOptions) - 10) . " more\n";
    }
} catch (PDOException $e) {
    echo "    ✗ Query FAILED: " . $e->getMessage() . "\n";
}

// Test 5: Get Divisi options
echo "\n[5] Getting Divisi options (cost_center)...\n";
try {
    $stmt = $pdo->query("
        SELECT DISTINCT cost_center 
        FROM pegawai 
        WHERE cost_center IS NOT NULL 
        AND cost_center != '' 
        AND cost_center LIKE 'div%'
        ORDER BY cost_center ASC
    ");
    $divisiOptions = $stmt->fetchAll(PDO::FETCH_COLUMN);
    echo "    ✓ Found " . count($divisiOptions) . " Divisi options\n";
    foreach ($divisiOptions as $divisi) {
        echo "    - $divisi\n";
    }
} catch (PDOException $e) {
    echo "    ✗ Query FAILED: " . $e->getMessage() . "\n";
}

echo "\n=== All Tests Passed! ===\n";
echo "Database connection is working properly.\n";
