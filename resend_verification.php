<?php
// resend_verification.php
header('Content-Type: application/json');

error_reporting(E_ALL);
ini_set('display_errors', 0);

try {
    include 'includes/db.php';
    require_once 'includes/pending_cleanup.php';   // CHANGED
    require_once 'includes/send_email.php';

    $email = isset($_POST['email']) ? trim($_POST['email']) : '';

    if (empty($email)) {
        echo json_encode(['success' => false, 'message' => 'Email is required.']);
        exit;
    }

    // Look at the pending signups table.
    $stmt = $conn->prepare("SELECT id, username FROM pending_users WHERE email = ?");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows === 0) {
        echo json_encode(['success' => false, 'message' => 'No pending signup found with this email.']);
        exit;
    }

    $pending = $result->fetch_assoc();

    // Cooldown check (30 seconds since last code was issued).
    $checkRecent = $conn->prepare("
        SELECT id FROM pending_users
        WHERE id = ? AND verification_expires > DATE_ADD(NOW(), INTERVAL 30 SECOND)
    ");
    $checkRecent->bind_param("i", $pending['id']);
    $checkRecent->execute();
    $recentResult = $checkRecent->get_result();

    if ($recentResult->num_rows > 0) {
        echo json_encode(['success' => false, 'message' => 'Please wait 60 seconds before requesting another code.']);
        exit;
    }

    $newCode = sprintf("%06d", mt_rand(100000, 999999));
    $expires = date('Y-m-d H:i:s', strtotime('+15 minutes'));

    $update = $conn->prepare("
        UPDATE pending_users
        SET verification_code = ?, verification_expires = ?
        WHERE id = ?
    ");
    $update->bind_param("ssi", $newCode, $expires, $pending['id']);

    if ($update->execute()) {
        if (sendVerificationEmail($email, $pending['username'], $newCode)) {
            echo json_encode(['success' => true, 'message' => 'A new verification code has been sent to your email.']);
        } else {
            echo json_encode(['success' => false, 'message' => 'Failed to send email. Please try again later.']);
        }
    } else {
        echo json_encode(['success' => false, 'message' => 'Error generating new code. Please try again.']);
    }

} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'An error occurred.']);
}