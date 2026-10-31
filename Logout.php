<?php
// Start the session so we can access it
session_start();

// Remove all session variables
session_unset();

// Destroy the session
session_destroy();

// Redirect to the home page
header("Location: index.php");
exit();
?>