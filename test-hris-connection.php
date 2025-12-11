<?php
/**
 * Test HRIS Database Connection
 * Run: php test-hris-connection.php
 */

echo "=== HRIS Database Connection Test ===\n\n";

// Test 1: Check if port is open
echo "1. Checking if port 3307 is open...\n";
$connection = @fsockopen('127.0.0.1', 3307, $errno, $errstr, 5);
if ($connection) {
    echo "   ✓ Port 3307 is OPEN\n";
    fclose($connection);
} else {
    echo "   ✗ Port 3307 is CLOSED\n";
    echo "   Error: $errstr ($errno)\n";
    echo "   → SSH tunnel is not running!\n";
    exit(1);
}
echo "\n";

// Test 2: Try PDO connection
echo "2. Testing PDO connection...\n";
try {
    $pdo = new PDO(
        'mysql:host=127.0.0.1;port=3307;dbname=HRIS_db',
        'root',
        '',
        [
            PDO::ATTR_TIMEOUT => 30,
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        ]
    );
    echo "   ✓ PDO connection successful\n";
} catch (PDOException $e) {
    echo "   ✗ PDO connection failed\n";
    echo "   Error: " . $e->getMessage() . "\n";
    exit(1);
}
echo "\n";

// Test 3: Query test
echo "3. Testing simple query...\n";
try {
    $stmt = $pdo->query("SELECT COUNT(*) as count FROM pegawai LIMIT 1");
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    echo "   ✓ Query successful\n";
    echo "   Total employees: " . number_format($result['count']) . "\n";
} catch (PDOException $e) {
    echo "   ✗ Query failed\n";
    echo "   Error: " . $e->getMessage() . "\n";
    exit(1);
}
echo "\n";

// Test 4: Test with Laravel
echo "4. Testing with Laravel...\n";
require __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

try {
    $count = DB::connection('hris_mysql')->table('pegawai')->count();
    echo "   ✓ Laravel connection successful\n";
    echo "   Total employees via Laravel: " . number_format($count) . "\n";
} catch (Exception $e) {
    echo "   ✗ Laravel connection failed\n";
    echo "   Error: " . $e->getMessage() . "\n";
    exit(1);
}
echo "\n";

echo "=== All Tests Passed! ===\n";
echo "HRIS database connection is working properly.\n";
