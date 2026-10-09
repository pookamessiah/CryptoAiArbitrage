<?php
// admin/logout.php
session_start();

// Clear all session data
$_SESSION = [];
session_unset();
session_destroy();

// Redirect to login
header("Location: login.php");
exit();
?>
