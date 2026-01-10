<?php
// Use the centralized init file for robust session handling and security.
require_once '../../includes/init.php';
require_once '../../includes/db_connect.php';

// Security Check: Must be a logged-in admin for ANY action on this page.
if (!isset($_SESSION["loggedin"]) || $_SESSION["role"] !== 'admin') {
    http_response_code(403);
    die("Access Denied. You must be an administrator to perform this action.");
}

// --- ACTION 1: HANDLE STATUS UPDATE (from order_detail.php dropdown) ---
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['order_id']) && isset($_POST['status'])) {
    
    $order_id = intval($_POST['order_id']);
    $new_status = $_POST['status'];
    
    $conn->begin_transaction();
    try {
        // Fetch current order details to get status and grand_total.
        $stmt_check = $conn->prepare("SELECT status, grand_total FROM orders WHERE id = ? FOR UPDATE");
        if (!$stmt_check) {
            throw new Exception("Prepare statement failed (check): " . $conn->error);
        }
        $stmt_check->bind_param("i", $order_id);
        $stmt_check->execute();
        $result_check = $stmt_check->get_result();
        
        if ($result_check->num_rows > 0) {
            $current_order = $result_check->fetch_assoc();
            $current_status = $current_order['status'];

            // Security check: Prevent changes to already finalized orders.
            if ($current_status === 'Completed' || $current_status === 'Cancelled') {
                throw new Exception("final");
            }

            // If the status is being changed TO 'Completed'.
            if ($new_status === 'Completed' && $current_status !== 'Completed') {
                
                // 1. Fetch all items from this specific order.
                $items_stmt = $conn->prepare("SELECT product_id, quantity, price_per_item FROM order_items WHERE order_id = ?");
                if (!$items_stmt) {
                    throw new Exception("Prepare statement failed (items): " . $conn->error);
                }
                $items_stmt->bind_param("i", $order_id);
                $items_stmt->execute();
                $items_result = $items_stmt->get_result();

                // 2. Prepare the statement to insert into the new sales_ledger.
                $ledger_sql = "INSERT INTO sales_ledger (order_id, product_id, quantity_sold, price_per_item, transaction_date) 
                               VALUES (?, ?, ?, ?, NOW()) 
                               ON DUPLICATE KEY UPDATE quantity_sold = VALUES(quantity_sold)";
                $ledger_stmt = $conn->prepare($ledger_sql);
                if (!$ledger_stmt) {
                    throw new Exception("Prepare statement failed (ledger): " . $conn->error);
                }

                // 3. Loop through each item and create a ledger entry.
                while ($item = $items_result->fetch_assoc()) {
                    $ledger_stmt->bind_param("iiid", 
                        $order_id, 
                        $item['product_id'], 
                        $item['quantity'], 
                        $item['price_per_item']
                    );
                    $ledger_stmt->execute();
                }
                $items_stmt->close();
                $ledger_stmt->close();
            }

            // 4. Now, proceed with the main order status update.
           $allowed_statuses = [
    'Pending Payment', 
    'Payment Submitted', 
    'Payment Verified', 
    'Payment Rejected', 
    'Processing', 
    'Shipped', 
    'Completed', 
    'Cancelled'
];
            if (in_array($new_status, $allowed_statuses)) {
                $sql_update = "UPDATE orders SET status = ? WHERE id = ?";
                $stmt_update = $conn->prepare($sql_update);
                if (!$stmt_update) {
                    throw new Exception("Prepare statement failed (update): " . $conn->error);
                }
                $stmt_update->bind_param("si", $new_status, $order_id);
                $stmt_update->execute();
                $stmt_update->close();
            } else {
                throw new Exception("Invalid status value provided.");
            }

            $conn->commit();
            header("Location: ../order_detail.php?id=" . $order_id . "&success=true");
            exit();

        } else {
            throw new Exception("Order not found.");
        }
    } catch (Exception $e) {
        $conn->rollback();
        if ($e->getMessage() === 'final') {
            header("Location: ../order_detail.php?id=" . $order_id . "&error=final");
            exit();
        } else {
            // In a real application, you would log this error.
            error_log("Handle Order Error: " . $e->getMessage());
            die("A database error occurred. Please check the server logs for more details.");
        }
    }
}

// --- ACTION 2: HANDLE ORDER DELETION (from a GET link on the orders list) ---
if ($_SERVER["REQUEST_METHOD"] == "GET" && isset($_GET['action']) && $_GET['action'] == 'delete' && isset($_GET['id'])) {
    $order_id = intval($_GET['id']);
    $sql_delete = "DELETE FROM orders WHERE id = ? AND (status = 'Completed' OR status = 'Cancelled')";
    $stmt_delete = $conn->prepare($sql_delete);
    $stmt_delete->bind_param("i", $order_id);
    $stmt_delete->execute();
    $stmt_delete->close();
    header("Location: ../orders.php?deleted=true");
    exit();
}

// --- ACTION 3: HANDLE ORDER CANCELLATION (from a GET link on the orders list) ---
if ($_SERVER["REQUEST_METHOD"] == "GET" && isset($_GET['action']) && $_GET['action'] == 'cancel' && isset($_GET['id'])) {
    $order_id = intval($_GET['id']);
    $sql_cancel = "UPDATE orders SET status = 'Cancelled' WHERE id = ? AND status IN ('Pending', 'Processing', 'Shipped')";
    $stmt_cancel = $conn->prepare($sql_cancel);
    $stmt_cancel->bind_param("i", $order_id);
    $stmt_cancel->execute();
    $stmt_cancel->close();
    header("Location: ../orders.php?cancelled=true");
    exit();
}

// --- FALLBACK: If no valid action is found, redirect safely ---
header("Location: ../orders.php");
exit();
?>