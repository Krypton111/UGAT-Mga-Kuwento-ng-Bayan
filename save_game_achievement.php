<?php
/**
 * Receives Kultura't Tradisyon achievements for the currently authenticated UGAT account.
 * POST JSON: {"achievement_ids":["heritage_collector","collect_eggs"]}
 *
 * The account ID is resolved from the server-side login session; it is never accepted
 * from the browser/game payload.
 */
session_start();
header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store');

function achievementResponse(int $status, array $payload): void {
    http_response_code($status);
    echo json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Allow: POST');
    achievementResponse(405, ['ok' => false, 'error' => 'method_not_allowed']);
}

if (empty($_SESSION['username'])) {
    achievementResponse(401, ['ok' => false, 'error' => 'not_authenticated']);
}

$contentType = strtolower(trim(explode(';', $_SERVER['CONTENT_TYPE'] ?? '')[0]));
if ($contentType !== 'application/json') {
    achievementResponse(415, ['ok' => false, 'error' => 'json_required']);
}

$payload = json_decode(file_get_contents('php://input'), true);
if (!is_array($payload)) {
    achievementResponse(400, ['ok' => false, 'error' => 'invalid_json']);
}

$allowed = [
    'heritage_collector',
    'ingredients_in_pot',
    'collect_eggs',
    'ending_city',
    'ending_stay',
];

$ids = $payload['achievement_ids'] ?? null;
if (!is_array($ids) && isset($payload['achievement_id'])) {
    $ids = [$payload['achievement_id']];
}
if (!is_array($ids) || count($ids) < 1 || count($ids) > count($allowed)) {
    achievementResponse(400, ['ok' => false, 'error' => 'invalid_achievement_ids']);
}

$ids = array_values(array_unique($ids));
foreach ($ids as $id) {
    if (!is_string($id) || !in_array($id, $allowed, true)) {
        achievementResponse(400, ['ok' => false, 'error' => 'unknown_achievement_id']);
    }
}

require_once __DIR__ . '/Includes/db.php';

$username = (string)$_SESSION['username'];
$userStmt = $conn->prepare('SELECT id FROM users WHERE username = ? LIMIT 1');
$userStmt->bind_param('s', $username);
$userStmt->execute();
$user = $userStmt->get_result()->fetch_assoc();
$userStmt->close();

if (!$user) {
    achievementResponse(401, ['ok' => false, 'error' => 'account_not_found']);
}

$userId = (int)$user['id'];
$gameName = 'Kultura at Tradisyon';
$insert = $conn->prepare(
    'INSERT IGNORE INTO player_achievements (user_id, game_name, achievement_id)
     VALUES (?, ?, ?)'
);
$saved = [];
$alreadyHad = [];

try {
    foreach ($ids as $achievementId) {
        $insert->bind_param('iss', $userId, $gameName, $achievementId);
        $insert->execute();
        if ($insert->affected_rows === 1) {
            $saved[] = $achievementId;
        } else {
            $alreadyHad[] = $achievementId;
        }
    }
} catch (Throwable $e) {
    error_log('save_game_achievement: database write failed: ' . $e->getMessage());
    $insert->close();
    achievementResponse(500, ['ok' => false, 'error' => 'save_failed']);
}
$insert->close();

achievementResponse(200, [
    'ok' => true,
    'saved' => $saved,
    'already_unlocked' => $alreadyHad,
]);
?>
