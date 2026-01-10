<?php
session_start();
$_SESSION = array();
session_destroy();
// Redirect to the admin login page
header("location: login.php");
exit;
?>