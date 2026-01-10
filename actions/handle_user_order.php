<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Security: Must be a logged-in user and a POST request with an action.
if (!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true || $_SERVER['REQUEST_METHOD'] !== 'POST' || !isset($_POST['action'])) {
    header("location: ../login.php");
    exit;
}

require_once '../includes/db_connect.php';

$order_id = $_POST['order_id'];
$user_id = $_SESSION['id'];
$action = $_POST['action'];

// --- HANDLE CANCEL ACTION ---
if ($action === 'cancel') {
    // Crucial security check: Ensure the order belongs to the user and is in a cancellable state.
    $sql = "UPDATE orders SET status = 'Cancelled' WHERE id = ? AND user_id = ? AND status = 'Pending'";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ii", $order_id, $user_id);
    $stmt->execute();

    if ($stmt->affected_rows > 0) {
        $_SESSION['message'] = "Order #$order_id has been successfully cancelled.";
    } else {
        $_SESSION['message'] = "Could not cancel order. It may have already been processed.";
    }
    $stmt->close();
}

// --- HANDLE DELETE ACTION ---
elseif ($action === 'delete') {
    // Crucial security check: Ensure the order belongs to the user and is in a deletable state.
    $sql = "DELETE FROM orders WHERE id = ? AND user_id = ? AND (status = 'Completed' OR status = 'Cancelled')";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ii", $order_id, $user_id);
    $stmt->execute();

    if ($stmt->affected_rows > 0) {
        $_SESSION['message'] = "Order #$order_id has been successfully deleted from your history.";
    } else {
        $_SESSION['message'] = "Could not delete order. It must be completed or cancelled first.";
    }
    $stmt->close();
}

$conn->close();

// Redirect back to the order history page
header("location: ../dashboard.php?view=orders");
exit();