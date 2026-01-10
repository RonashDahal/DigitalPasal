<?php
// Core helper functions can be placed here.
// For example, a function to safely get POST data.
function get_post_data($field) {
    if (isset($_POST[$field])) {
        // Basic sanitization
        return htmlspecialchars(stripslashes(trim($_POST[$field])));
    }
    return '';
}

// Function to get the total number of items in the cart
function get_cart_count() {
    if (isset($_SESSION['cart'])) {
        return array_sum($_SESSION['cart']);
    }
    return 0;
}
?>