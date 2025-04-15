<?php
// Start the session
session_start();

// Destroy all session variables
session_unset();

// Destroy the session
session_destroy();

// Get the current directory
$currentDir = dirname($_SERVER['PHP_SELF']);

// Redirect to the same directory
header("Location: $currentDir");
exit;
?>

