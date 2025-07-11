<?php
/**
 * Simple test script to verify the REST API endpoint works
 * Run this directly on the server to test the endpoint
 */

// Test URL - adjust the domain and SID as needed
$test_url = 'https://mhanationaldev.wpengine.com/wp-json/custom-api/v1/get-sid-data/a95d4111-c7e4-493c-b9f4-b6cac117a6d0-1600812710_1';

// Test headers
$headers = [
    'x-api-key: super_secure_API_key',
    'Content-Type: application/json',
    'Accept: application/json'
];

// Make the request
$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $test_url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
curl_setopt($ch, CURLOPT_TIMEOUT, 30);
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);

$response = curl_exec($ch);
$http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$error = curl_error($ch);
curl_close($ch);

echo "=== REST API Endpoint Test ===\n";
echo "URL: $test_url\n";
echo "HTTP Code: $http_code\n";
echo "Error: " . ($error ? $error : 'None') . "\n";
echo "Response:\n";
echo $response . "\n";

// Test with invalid key
echo "\n=== Testing Invalid API Key ===\n";
$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $test_url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'x-api-key: invalid_key',
    'Content-Type: application/json',
    'Accept: application/json'
]);
curl_setopt($ch, CURLOPT_TIMEOUT, 30);
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);

$response = curl_exec($ch);
$http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

echo "HTTP Code: $http_code\n";
echo "Response:\n";
echo $response . "\n";

// Test without API key
echo "\n=== Testing Missing API Key ===\n";
$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $test_url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'Content-Type: application/json',
    'Accept: application/json'
]);
curl_setopt($ch, CURLOPT_TIMEOUT, 30);
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);

$response = curl_exec($ch);
$http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

echo "HTTP Code: $http_code\n";
echo "Response:\n";
echo $response . "\n";
?> 