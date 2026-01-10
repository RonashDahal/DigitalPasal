<?php
// /includes/init.php

// 1. Error Reporting (Good for development, should be turned off on a live server)
ini_set('display_errors', 1);
error_reporting(E_ALL);

// 2. Session Management (The critical fix)
// This ensures a session is always started, but only once per request.
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
define('BASE_URL', 'http://localhost/ecommerce-project/');
// 3. Include Core Functions
// This simplifies our other files.
require_once 'functions.php';
// ... your existing init.php code ...

/**
 * ========================================================================
 * NEW: GLOBAL WISHLIST FUNCTION (The Bulletproof Solution)
 * ========================================================================
 * Fetches the current user's wishlist product IDs directly from the database.
 * @param mysqli $conn The database connection object.
 * @return array An array of product IDs in the user's wishlist.
 */
function getUserWishlist(mysqli $conn): array {
    $wishlist_ids = [];
    if (isset($_SESSION['loggedin']) && $_SESSION['loggedin'] === true) {
        $user_id = $_SESSION['id'];
        // Use a prepared statement for security, although user_id from session is safe
        $stmt = $conn->prepare("SELECT product_id FROM wishlist WHERE user_id = ?");
        $stmt->bind_param("i", $user_id);
        $stmt->execute();
        $result = $stmt->get_result();
        while($row = $result->fetch_assoc()) {
            $wishlist_ids[] = $row['product_id'];
        }
        $stmt->close();
    }
    return $wishlist_ids;
}
?>
<?php
function getProfileImage($imageUrl) {
    // Check if the provided URL is null, empty, or just whitespace
    if (empty(trim($imageUrl))) {
        // If it's empty, return the FULL URL to the default image
        return BASE_URL . 'assets/images/default_avatar.png';
    }

    // If the image URL from the database is already a full URL, use it directly
    if (filter_var($imageUrl, FILTER_VALIDATE_URL)) {
        return htmlspecialchars($imageUrl);
    }

    // Otherwise, assume it's a relative path and build the FULL URL
    return BASE_URL . htmlspecialchars($imageUrl);
}