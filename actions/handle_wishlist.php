<?php
// ** THE CRITICAL FIX IS HERE **
// This file needs to initialize the session to know who the user is.
// It goes up one directory ('../') to find the /includes/ folder.
require_once '../includes/init.php'; 

// Always set the content type to JSON for AJAX responses.
header('Content-Type: application/json');

// Security check: User must be logged in to use the wishlist feature.
if (!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true) {
    echo json_encode(['success' => false, 'message' => 'Please log in to add items to your wishlist.']);
    exit;
}

// Now that we know the user is logged in, we can connect to the database.
require_once '../includes/db_connect.php';

$user_id = $_SESSION['id'];
$product_id = isset($_POST['product_id']) ? (int)$_POST['product_id'] : 0;

// Validate the product ID received from the frontend.
if ($product_id <= 0) {
    echo json_encode(['success' => false, 'message' => 'Invalid product selected.']);
    exit;
}

try {
    // Check if the item is already in the user's wishlist
    $stmt_check = $conn->prepare("SELECT id FROM wishlist WHERE user_id = ? AND product_id = ?");
    $stmt_check->bind_param("ii", $user_id, $product_id);
    $stmt_check->execute();
    $result = $stmt_check->get_result();
    $stmt_check->close();

    if ($result->num_rows > 0) {
        // If it exists, the action is to REMOVE it from the wishlist.
        $stmt_delete = $conn->prepare("DELETE FROM wishlist WHERE user_id = ? AND product_id = ?");
        $stmt_delete->bind_param("ii", $user_id, $product_id);
        $stmt_delete->execute();
        $stmt_delete->close();
        echo json_encode(['success' => true, 'action' => 'removed', 'message' => 'Removed from wishlist.']);
    } else {
        // If it does not exist, the action is to ADD it to the wishlist.
        $stmt_add = $conn->prepare("INSERT INTO wishlist (user_id, product_id) VALUES (?, ?)");
        $stmt_add->bind_param("ii", $user_id, $product_id);
        $stmt_add->execute();
        $stmt_add->close();
        echo json_encode(['success' => true, 'action' => 'added', 'message' => 'Added to wishlist.']);
    }

} catch (Exception $e) {
    // If any database error occurs, log it for the developer and send a generic message.
    error_log("Wishlist Error: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'A database error occurred. Please try again.']);
}

$conn->close();
?>