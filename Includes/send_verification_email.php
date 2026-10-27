<?php
// includes/send_verification_email.php
//
// Thin wrapper. The full email templates live in send_email.php.
// This file exists so older code that required send_verification_email.php
// keeps working, but it now delegates to the shared implementation there.

require_once __DIR__ . '/send_email.php';