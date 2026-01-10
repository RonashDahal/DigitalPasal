<?php

// Start session only if one isn't already active
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Check if the user is logged in and is an admin.
if (!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true || $_SESSION["role"] !== 'admin') {
    // If not, redirect them to the main site's login page
    header("location: login.php");
    exit;
}
?>