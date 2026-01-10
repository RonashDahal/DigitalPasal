<?php
session_start();

/**
 * Direct Buy Handler
 * Adds item to cart and redirects immediately to checkout.
 */

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['product_id'])) {
    
    $product_id = $_POST['product_id'];
    // Default to 1 if quantity is not provided
    $quantity = isset($_POST['quantity']) ? (int)$_POST['quantity'] : 1;

    // Clear existing cart items so only the Buy Now item is processed
    $_SESSION['cart'] = array();

    // Clear any active discount codes to prevent conflicts with the new cart state
    if (isset($_SESSION['discount'])) {
        unset($_SESSION['discount']);
    }

    // Add the specific Buy Now item
    $_SESSION['cart'][$product_id] = $quantity;

    // Redirect directly to checkout
    header("Location: cart.php");
    exit();
}

// Redirect home if accessed incorrectly
header("Location: index.php");
exit();
?>