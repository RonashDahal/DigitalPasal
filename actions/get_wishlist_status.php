<?php
// ** THE CRITICAL FIX FOR THE REFRESH PROBLEM **
// These headers command the browser to NEVER cache this response.
header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Cache-Control: post-check=0, pre-check=0", false);
header("Pragma: no-cache");

// Now, initialize the session.
require_once '../includes/init.php';

header('Content-Type: application/json');

$wishlist_ids = [];

if (isset($_SESSION["loggedin"]) && $_SESSION["loggedin"] === true) {
    require_once '../includes/db_connect.php';
    $user_id = $_SESSION['id'];

    $wishlist_result = $conn->query("SELECT product_id FROM wishlist WHERE user_id = {$user_id}");
    while($row = $wishlist_result->fetch_assoc()) {
        $wishlist_ids[] = $row['product_id'];
    }
    $conn->close();
}

echo json_encode(['success' => true, 'wishlist' => $wishlist_ids]);
?>/