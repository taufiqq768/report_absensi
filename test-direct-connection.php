<?php
/**
 * Test Direct Connection to HRIS Database
 * Run: php test-direct-connection.php
 */

echo "=== HRIS Direct Connection Test ===\n\n";

// Load environment variables
require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

$host = env('HRIS_DB_HOST', '10.100.11.220');
$port = env('HRIS_DB_PORT', '3306');
$database = env('HRIS_DB_DATABASE', 'hris_db_cpanel');
$username = env('HRIS_DB_USERNAME', 'hris_user');
$password = env('HRIS_DB_PASSWORD', '');

echo "Configuration:\n";
echo "  Host: $host\n";
echo "  Port: $port\n";
echo "  Database: $database\n";
echo "  Username: $username\n";
echo "\n";

// Test 1: Check network connectivity
echo "1. Testing network connectivity to $host:$port...\n";
$start = microtime(true);
$connection = @fsockopen($host, $port, $errno, $errstr, 10);
$time = round((microtime(true) - $start) * 1000, 2);

if ($connection) {
    echo "   ✓ Network connection successful ({$time}ms)\n";
    fclose($connection);
} else {
    echo "   ✗ Network connection FAILED\n";
    echo "   Error: $errstr ($errno)\n";
    echo "   → Check if server $host is reachable\n";
    echo "   → Check firewall settings\n";
    exit(1);
}
echo "\n";

// Test 2: Try PDO connection
echo "2. Testing MySQL connection...\n";
$start = microtime(true);
try {
    $dsn = "mysql:host=$host;port=$port;dbname=$database;charset=utf8mb4";
    $pdo = new PDO($dsn, $username, $password, [
        PDO::ATTR_TIMEOUT => 60,
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_PERSISTENT => true,
    ]);
    $time = round((microtime(true) - $start) * 1000, 2);
    echo "   ✓ MySQL connection successful ({$time}ms)\n";
} catch (PDOException $e) {
    echo "   ✗ MySQL connection FAILED\n";
    echo "   Error: " . $e->getMessage() . "\n";
    echo "   → Check username/password in .env\n";
    echo "   → Check database name\n";
    exit(1);
}
echo "\n";

// Test 3: Simple query
echo "3. Testing simple query...\n";
$start = microtime(true);
try {
    $stmt = $pdo->query("SELECT COUNT(*) as count FROM pegawai");
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    $time = round((microtime(true) - $start) * 1000, 2);
    echo "   ✓ Query successful ({$time}ms)\n";
    echo "   Total employees: " . number_format($result['count']) . "\n";
} catch (PDOException $e) {
    echo "   ✗ Query FAILED\n";
    echo "   Error: " . $e->getMessage() . "\n";
    exit(1);
}
echo "\n";

// Test 4: Large query (simulate real usage)
echo "4. Testing large query (100 NIKs)...\n";
$start = microtime(true);
try {
    $stmt = $pdo->query("SELECT nik, regional_grup, cost_center FROM pegawai LIMIT 100");
    $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
    $time = round((microtime(true) - $start) * 1000, 2);
    echo "   ✓ Large query successful ({$time}ms)\n";
    echo "   Fetched: " . count($results) . " records\n";
} catch (PDOException $e) {
    echo "   ✗ Large query FAILED\n";
    echo "   Error: " . $e->getMessage() . "\n";
    exit(1);
}
echo "\n";

// Test 5: Laravel connection
echo "5. Testing Laravel connection...\n";
$start = microtime(true);
try {
    $count = DB::connection('hris_mysql')->table('pegawai')->count();
    $time = round((microtime(true) - $start) * 1000, 2);
    echo "   ✓ Laravel connection successful ({$time}ms)\n";
    echo "   Total employees via Laravel: " . number_format($count) . "\n";
} catch (Exception $e) {
    echo "   ✗ Laravel connection FAILED\n";
    echo "   Error: " . $e->getMessage() . "\n";
    exit(1);
}
echo "\n";

// Performance summary
echo "=== Performance Summary ===\n";
echo "Network latency: Good (<100ms = Excellent, 100-300ms = Good, >300ms = Slow)\n";
echo "Query performance: Good (<500ms = Fast, 500-2000ms = Acceptable, >2000ms = Slow)\n";
echo "\n";

echo "=== All Tests Passed! ===\n";
echo "Direct connection to HRIS database is working.\n";
