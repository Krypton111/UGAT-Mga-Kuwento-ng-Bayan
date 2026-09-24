<?php
// includes/pending_cleanup.php
//
// Removes pending signups older than 15 minutes.
// Safe to include on every page.

if (!isset($conn) || !($conn instanceof mysqli)) {
    return;
}

static $pending_cleanup_done = false;
if ($pending_cleanup_done) {
    return;
}
$pending_cleanup_done = true;

// Use PHP's time so we're consistent with how register.php writes
// verification_expires (which also uses PHP's date()).
$now = date('Y-m-d H:i:s');

$stmt = $conn->prepare("DELETE FROM pending_users WHERE verification_expires < ?");
$stmt->bind_param("s", $now);
$stmt->execute();