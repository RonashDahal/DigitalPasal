<?php
if (session_status() === PHP_SESSION_NONE) { session_start(); }
if (!isset($_SESSION["loggedin"])) { header("location: ../login.php"); exit; }

if ($_SERVER['REQUEST_METHOD'] !== 'POST') { exit('Invalid request method.'); }

require_once '../includes/db_connect.php';

$user_id = $_SESSION['id'];
$product_id = (int)$_POST['product_id'];
$order_item_id = (int)$_POST['order_item_id'];
$rating = (int)$_POST['rating'];
$review_text = trim($_POST['review_text']);

// --- SECURITY CHECKS ---
// 1. Verify the user is eligible to review this item
$stmt_verify = $conn->prepare(
    "SELECT oi.id FROM order_items oi JOIN orders o ON oi.order_id = o.id LEFT JOIN reviews r ON oi.id = r.order_item_id
     WHERE oi.id = ? AND o.user_id = ? AND oi.product_id = ? AND o.status = 'Completed' AND r.id IS NULL"
);
$stmt_verify->bind_param("iii", $order_item_id, $user_id, $product_id);
$stmt_verify->execute();
if ($stmt_verify->get_result()->num_rows === 0) {
    die("Verification failed. You are not eligible to review this item.");
}
$stmt_verify->close();

// 2. Validate rating value
if ($rating < 1 || $rating > 5) {
    die("Invalid rating value.");
}

// --- DATABASE OPERATIONS (use a transaction) ---
$conn->begin_transaction();
try {
    // 1. Insert the new review
    $stmt_insert = $conn->prepare(
        "INSERT INTO reviews (product_id, user_id, order_item_id, rating, review_text) VALUES (?, ?, ?, ?, ?)"
    );
    $stmt_insert->bind_param("iiiis", $product_id, $user_id, $order_item_id, $rating, $review_text);
    $stmt_insert->execute();
    $stmt_insert->close();

    // 2. Recalculate and update the product's average rating and count
    $stmt_recalc = $conn->prepare(
        "UPDATE products p SET 
            p.average_rating = (SELECT AVG(r.rating) FROM reviews r WHERE r.product_id = ?),
            p.rating_count = (SELECT COUNT(r.id) FROM reviews r WHERE r.product_id = ?)
         WHERE p.id = ?"
    );
    $stmt_recalc->bind_param("iii", $product_id, $product_id, $product_id);
    $stmt_recalc->execute();
    $stmt_recalc->close();

    // If all successful, commit the transaction
    $conn->commit();
    $_SESSION['message'] = "Thank you! Your review has been submitted.";
    header("location: ../product_detail.php?id=" . $product_id);
    exit();

} catch (mysqli_sql_exception $exception) {
    $conn->rollback();
    die("Database error: Failed to submit review. " . $exception->getMessage());
}