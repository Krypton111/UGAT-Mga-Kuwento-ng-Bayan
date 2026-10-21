<?php
// bridge_server.php
header('Content-Type: application/json');

// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Get POST data
$raw_data = file_get_contents('php://input');

// Parse the data (for application/x-www-form-urlencoded)
parse_str($raw_data, $data);

// If empty, try JSON
if (empty($data)) {
    $data = json_decode($raw_data, true);
}

// Log received data for debugging
error_log("📥 Bridge received: " . print_r($data, true));

if (isset($data['score']) && isset($data['game_name'])) {
    // Forward to score_save.php
    $ch = curl_init('http://localhost/GameLauncherKERWIN/score_save.php');
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($data));
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    error_log("✅ Score forwarded. Response: " . $response);
    
    echo json_encode([
        'success' => true, 
        'message' => 'Score forwarded',
        'response' => $response
    ]);
} else {
    error_log("❌ No score data received");
    echo json_encode([
        'success' => false, 
        'message' => 'No score data',
        'received' => $raw_data
    ]);
}
?>