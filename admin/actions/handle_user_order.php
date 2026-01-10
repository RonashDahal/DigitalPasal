<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Security: Must be a logged-in user and a POST request.
if (!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true || $_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("location: ../login.php");
    exit;
}

require_once '../includes/db_connect.php';

$order_id = $_POST['order_id'];
$user_id = $_SESSION['id'];

// Crucial security check: Ensure the order belongs to the user and is in a cancellable state.
$sql = "UPDATE orders SET status = 'Cancelled' WHERE id = ? AND user_id = ? AND status = 'Pending'";

$stmt = $conn->prepare($sql);
$stmt->bind_param("ii", $order_id, $user_id);
$stmt->execute();

// Check if the update was successful (1 row affected)
if ($stmt->affected_rows > 0) {
    // Optional: Add a success message to the session to display on the dashboard
    $_SESSION['message'] = "Order #$order_id has been successfully cancelled.";
} else {
    // Optional: Add an error message
    $_SESSION['message'] = "Could not cancel order. It may have already been processed.";
}

$stmt->close();
$conn->close();

// Redirect back to the order history page
header("location: ../dashboard.php?view=orders");
exit();