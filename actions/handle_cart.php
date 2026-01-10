<?php
require_once '../includes/db_connect.php';
// Always start the session first.
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
// Always return JSON.
header('Content-Type: application/json');

// Wrap the entire logic in a try-catch block to handle unexpected errors.
try {
    // Initialize cart if not set.
    if (!isset($_SESSION['cart'])) {
        $_SESSION['cart'] = [];
    }

    // Default response.
    $response = ['success' => false, 'message' => 'Invalid request.'];

    // Stricter check: ensure all required POST variables are present.
    if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['action']) && isset($_POST['product_id'])) {
        
        $product_id = intval($_POST['product_id']);
        $action = $_POST['action'];

        if ($product_id > 0) {
            
            // ** THE FIX IS HERE: Correctly handle both 'add' and 'update' actions **
            
            // Handle ADDING an item to the cart
            if ($action === 'add') {
                $quantity = isset($_POST['quantity']) ? intval($_POST['quantity']) : 1;
                if ($quantity > 0) {
                    // Add the new quantity to any existing quantity for that product.
                    $_SESSION['cart'][$product_id] = ($_SESSION['cart'][$product_id] ?? 0) + $quantity;
                    $response = ['success' => true, 'message' => 'Product added to cart.'];
                }
            }
            
            // Handle UPDATING quantity or REMOVING an item from the cart page
            elseif ($action === 'update') {
                $quantity = isset($_POST['quantity']) ? intval($_POST['quantity']) : 0;
                if ($quantity > 0) {
                    // Set the quantity to the new value.
                    $_SESSION['cart'][$product_id] = $quantity;
                    $response = ['success' => true, 'message' => 'Cart updated.'];
                } else {
                    // If quantity is 0 or less, this is a removal.
                    unset($_SESSION['cart'][$product_id]);
                    $response = ['success' => true, 'message' => 'Product removed from cart.'];
                }
            }
        }
    }

    // Add the final, up-to-date cart count to the response.
    $response['cart_count'] = array_sum($_SESSION['cart'] ?? []);
    echo json_encode($response);

} catch (Exception $e) {
    // If ANY unexpected error occurs, catch it and return a clean JSON error message.
    error_log($e->getMessage()); // Log the actual error for debugging
    echo json_encode([
        'success' => false,
        'message' => 'A server error occurred. Please try again.',
        'cart_count' => array_sum($_SESSION['cart'] ?? [])
    ]);
}
exit();