<?php
if (session_status() === PHP_SESSION_NONE) { session_start(); }
if (!isset($_SESSION["loggedin"]) || $_SESSION["role"] !== 'admin') { die("Access Denied"); }

require_once '../../includes/db_connect.php';

if (isset($_GET['action']) && $_GET['action'] == 'delete' && isset($_GET['id'])) {
    $review_id = (int)$_GET['id'];
    
    $conn->begin_transaction();
    try {
        // 1. Get the product_id from the review before deleting it
        $stmt_get = $conn->prepare("SELECT product_id FROM reviews WHERE id = ?");
        $stmt_get->bind_param("i", $review_id);
        $stmt_get->execute();
        $product_id = $stmt_get->get_result()->fetch_assoc()['product_id'];
        $stmt_get->close();

        if ($product_id) {
            // 2. Delete the review
            $stmt_delete = $conn->prepare("DELETE FROM reviews WHERE id = ?");
            $stmt_delete->bind_param("i", $review_id);
            $stmt_delete->execute();
            $stmt_delete->close();

            // 3. Recalculate and update the product's rating and count
            $stmt_recalc = $conn->prepare(
                "UPDATE products p SET 
                    p.average_rating = COALESCE((SELECT AVG(r.rating) FROM reviews r WHERE r.product_id = ?), 0),
                    p.rating_count = (SELECT COUNT(r.id) FROM reviews r WHERE r.product_id = ?)
                 WHERE p.id = ?"
            );
            $stmt_recalc->bind_param("iii", $product_id, $product_id, $product_id);
            $stmt_recalc->execute();
            $stmt_recalc->close();
        }

        $conn->commit();
        header("Location: ../reviews.php?deleted=true");
        exit();

    } catch (mysqli_sql_exception $exception) {
        $conn->rollback();
        die("Database error: Failed to delete review. " . $exception->getMessage());
    }
}