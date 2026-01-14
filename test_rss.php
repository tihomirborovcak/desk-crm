<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once 'config/database.php';

echo "<h2>Test RSS</h2>";

try {
    $db = getDB();
    echo "<p>✅ Konekcija na bazu OK</p>";
    
    // Test 1 - svi izvori
    $stmt = $db->query("SELECT * FROM rss_sources");
    $all = $stmt->fetchAll();
    echo "<p>Svi izvori: " . count($all) . "</p>";
    echo "<pre>" . print_r($all, true) . "</pre>";
    
    // Test 2 - samo lokalni
    $stmt = $db->query("SELECT * FROM rss_sources WHERE active = 1 AND category = 'lokalno'");
    $local = $stmt->fetchAll();
    echo "<p>Lokalni izvori: " . count($local) . "</p>";
    echo "<pre>" . print_r($local, true) . "</pre>";
    
    // Test 3 - curl test
    if (!empty($local)) {
        $url = $local[0]['url'];
        echo "<p>Testing cURL za: $url</p>";
        
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 30);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0');
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);
        curl_close($ch);
        
        echo "<p>HTTP Code: $httpCode</p>";
        echo "<p>cURL Error: $error</p>";
        echo "<p>Response length: " . strlen($response) . "</p>";
        echo "<pre>" . htmlspecialchars(substr($response, 0, 1000)) . "</pre>";
    }
    
} catch (Exception $e) {
    echo "<p>❌ Greška: " . $e->getMessage() . "</p>";
}
