<?php
/**
 * API Connection Test Script
 * Run this to diagnose connection issues with the HCIS API
 */

echo "=== HCIS API Connection Test ===\n\n";

$apiUrl = 'http://hcis.holding-perkebunan.com/api/generate_token_api';

// Test 1: DNS Resolution
echo "1. Testing DNS Resolution...\n";
$host = parse_url($apiUrl, PHP_URL_HOST);
$ip = gethostbyname($host);
if ($ip === $host) {
    echo "   ❌ FAILED: Cannot resolve hostname '$host'\n";
    echo "   This means your computer cannot find the server.\n";
} else {
    echo "   ✓ SUCCESS: $host resolves to $ip\n";
}
echo "\n";

// Test 2: Ping test (basic connectivity)
echo "2. Testing Basic Connectivity (Ping)...\n";
$pingResult = exec("ping -n 2 $host 2>&1", $output, $returnCode);
if ($returnCode !== 0) {
    echo "   ❌ WARNING: Cannot ping $host\n";
    echo "   Note: Some servers block ping, this may not be critical.\n";
} else {
    echo "   ✓ Server is reachable via ping\n";
}
echo "\n";

// Test 3: HTTP Connection with cURL
echo "3. Testing HTTP Connection with cURL...\n";
$ch = curl_init($apiUrl);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 10);
curl_setopt($ch, CURLOPT_TIMEOUT, 30);
curl_setopt($ch, CURLOPT_VERBOSE, true);
curl_setopt($ch, CURLOPT_HEADER, true);

$verboseOutput = fopen('php://temp', 'w+');
curl_setopt($ch, CURLOPT_STDERR, $verboseOutput);

$startTime = microtime(true);
$response = curl_exec($ch);
$duration = round(microtime(true) - $startTime, 2);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$error = curl_error($ch);
$errno = curl_errno($ch);

if ($errno) {
    echo "   ❌ FAILED: cURL Error #$errno: $error\n";
    echo "   Connection took: {$duration}s\n";

    // Provide specific guidance based on error
    switch ($errno) {
        case 6: // CURLE_COULDNT_RESOLVE_HOST
            echo "   → Cannot resolve hostname. Check your DNS settings or internet connection.\n";
            break;
        case 7: // CURLE_COULDNT_CONNECT
            echo "   → Cannot connect to server. Server may be down or firewall blocking.\n";
            break;
        case 28: // CURLE_OPERATION_TIMEDOUT
            echo "   → Connection timeout. Server is too slow or not responding.\n";
            echo "   → Try again later or check if there's a network issue.\n";
            break;
        default:
            echo "   → Check your internet connection and firewall settings.\n";
    }
} else {
    echo "   ✓ SUCCESS: Connected to API (HTTP $httpCode)\n";
    echo "   Connection took: {$duration}s\n";
}

rewind($verboseOutput);
$verboseLog = stream_get_contents($verboseOutput);
curl_close($ch);

echo "\n";

// Test 4: Test with actual credentials (if provided)
echo "4. Testing API Token Generation...\n";
echo "   Enter username (or press Enter to skip): ";
$username = trim(fgets(STDIN));

if (!empty($username)) {
    echo "   Enter password: ";
    $password = trim(fgets(STDIN));

    $ch = curl_init($apiUrl);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query([
        'username' => $username,
        'password' => $password
    ]));
    curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 30);
    curl_setopt($ch, CURLOPT_TIMEOUT, 60);

    $startTime = microtime(true);
    $response = curl_exec($ch);
    $duration = round(microtime(true) - $startTime, 2);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $error = curl_error($ch);
    $errno = curl_errno($ch);

    if ($errno) {
        echo "   ❌ FAILED: $error\n";
        echo "   Time taken: {$duration}s\n";
    } else {
        echo "   ✓ Request completed (HTTP $httpCode) in {$duration}s\n";

        if ($httpCode === 200) {
            $data = json_decode($response, true);
            if (isset($data['token']) || isset($data['access_token'])) {
                echo "   ✓ Token received successfully!\n";
            } else {
                echo "   ⚠ Response received but no token found\n";
                echo "   Response: " . substr($response, 0, 200) . "...\n";
            }
        } elseif ($httpCode === 401) {
            echo "   ❌ Authentication failed (wrong username/password)\n";
        } else {
            echo "   ⚠ Unexpected HTTP code: $httpCode\n";
            echo "   Response: " . substr($response, 0, 200) . "...\n";
        }
    }
    curl_close($ch);
} else {
    echo "   Skipped.\n";
}

echo "\n=== Test Complete ===\n";
echo "\nDiagnostic Summary:\n";
echo "- If DNS resolution failed: Check your internet connection\n";
echo "- If connection timeout: The server might be down or very slow\n";
echo "- If you can connect but get timeout on login: Try increasing timeout values\n";
echo "- Check Laravel logs at: storage/logs/laravel.log\n";
