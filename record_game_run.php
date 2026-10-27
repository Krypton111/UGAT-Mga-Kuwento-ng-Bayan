<?php
/**
 * Records a completed playthrough for the authenticated UGAT player.
 * The Godot web export should POST JSON: {"elapsed_seconds": 123}
 * to this endpoint after the ending sequence finishes.
 */
session_start();

header('Content-Type: application/json; charset=utf-8');

if (!isset($_SESSION['username'])) {
    http_response_code(401);
    echo json_encode(['ok' => false, 'error' => 'not_authenticated']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Allow: POST');
    http_response_code(405);
    echo json_encode(['ok' => false, 'error' => 'method_not_allowed']);
    exit;
}

$payload = json_decode(file_get_contents('php://input'), true);
$elapsed = filter_var($payload['elapsed_seconds'] ?? null, FILTER_VALIDATE_INT);

if ($elapsed === false || $elapsed === null || $elapsed < 1 || $elapsed > 86400) {
    http_response_code(400);
    echo json_encode(['ok' => false, 'error' => 'invalid_elapsed_seconds']);
    exit;
}

require_once __DIR__ . '/Includes/db.php';

$username = $_SESSION['username'];
$userStmt = $conn->prepare('SELECT id FROM users WHERE username = ? LIMIT 1');
if (!$userStmt) {
    error_log('record_game_run: user lookup prepare failed: ' . $conn->error);
    http_response_code(500);
    echo json_encode(['ok' => false, 'error' => 'database_error']);
    exit;
}
$userStmt->bind_param('s', $username);
$userStmt->execute();
$user = $userStmt->get_result()->fetch_assoc();
$userStmt->close();

if (!$user) {
    http_response_code(401);
    echo json_encode(['ok' => false, 'error' => 'account_not_found']);
    exit;
}

$gameName = 'Kultura at Tradisyon';
$insert = $conn->prepare('INSERT INTO game_runs (user_id, game_name, elapsed_seconds, completed_at) VALUES (?, ?, ?, NOW())');
if (!$insert) {
    error_log('record_game_run: insert prepare failed: ' . $conn->error);
    http_response_code(500);
    echo json_encode(['ok' => false, 'error' => 'database_error']);
    exit;
}

$userId = (int)$user['id'];
$insert->bind_param('isi', $userId, $gameName, $elapsed);
if (!$insert->execute()) {
    error_log('record_game_run: insert failed: ' . $insert->error);
    $insert->close();
    http_response_code(500);
    echo json_encode(['ok' => false, 'error' => 'save_failed']);
    exit;
}
$insert->close();

echo json_encode(['ok' => true]);
