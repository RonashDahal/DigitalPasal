<?php
/**
 * actions/place_order.php
 * Fixed: Null-Safe Payment Validation and Argument Match Fix
 */

require_once '../includes/init.php';
require_once '../includes/db_connect.php';

// 1. SECURITY & REQUEST CHECK
if (!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true || empty($_SESSION['cart']) || $_SERVER["REQUEST_METHOD"] != "POST") {
    header("location: ../index.php");
    exit;
}

// 2. CAPTURE POST DATA
$user_id           = $_SESSION['id'];
$shipping_name     = trim($_POST['shipping_name'] ?? '');
$phone             = trim($_POST['phone'] ?? '');
$shipping_address  = trim($_POST['shipping_address'] ?? '');
$latitude          = (!empty($_POST['latitude'])) ? $_POST['latitude'] : null;
$longitude         = (!empty($_POST['longitude'])) ? $_POST['longitude'] : null;
$payment_method    = $_POST['payment_method'] ?? 'cod';
$transaction_code  = (!empty($_POST['transaction_code'])) ? trim($_POST['transaction_code']) : null;

// 3. BACKEND VALIDATION
if (empty($shipping_name) || empty($phone) || empty($shipping_address)) {
    die("Error: Name, Phone and Address are required.");
}
if (is_null($latitude) || is_null($longitude)) {
    die("Error: Precise GPS location is required to place your order.");
}
if ($payment_method === 'card' && empty($transaction_code)) {
    die("Error: eSewa Transaction Code is required for verification.");
}

// 4. FETCH DYNAMIC SHIPPING CHARGE
$res_ship = $conn->query("SELECT setting_value FROM site_settings WHERE setting_key = 'shipping_charge'");
$shipping_charge = (float)($res_ship->fetch_assoc()['setting_value'] ?? 0);

// 5. CALCULATE TOTALS AND VALIDATE PAYMENT RULES
$cart_items = $_SESSION['cart'];
$product_ids = array_keys($cart_items);
$placeholders = implode(',', array_fill(0, count($cart_items), '?'));

// Fetch products and their category payment rules
$sql_check = "SELECT p.id, p.price, p.stock_quantity, c.allow_cod, c.allow_esewa 
              FROM products p 
              LEFT JOIN categories c ON p.category_id = c.id 
              WHERE p.id IN ($placeholders)";

$stmt_check = $conn->prepare($sql_check);
$stmt_check->bind_param(str_repeat('i', count($product_ids)), ...$product_ids);
$stmt_check->execute();
$result_check = $stmt_check->get_result();

$total_amount = 0;
$products_data = [];

while ($row = $result_check->fetch_assoc()) {
    $p_id = $row['id'];
    $qty = $cart_items[$p_id];
    
    // Check Stock
    if ($qty > $row['stock_quantity']) {
        die("Error: Insufficient stock for product ID: $p_id.");
    }

    /**
     * FIXED PAYMENT VALIDATION
     * If a category is missing or null, we default to ALLOW (1)
     */
    $allow_cod = ($row['allow_cod'] !== null) ? (int)$row['allow_cod'] : 1;
    $allow_esewa = ($row['allow_esewa'] !== null) ? (int)$row['allow_esewa'] : 1;

    if ($payment_method === 'cod' && $allow_cod === 0) {
        die("Error: One or more items in your basket do not allow Cash on Delivery.");
    }
    if ($payment_method === 'card' && $allow_esewa === 0) {
        die("Error: One or more items in your basket do not allow eSewa.");
    }

    $total_amount += $row['price'] * $qty;
    $products_data[$p_id] = $row;
}
$stmt_check->close();

// 6. FINAL PRICE CALCULATION
$discount_code   = $_SESSION['discount']['code'] ?? null;
$discount_amount = (float)($_SESSION['discount']['amount'] ?? 0);
$discount_amount = min($total_amount, $discount_amount);
$grand_total = ($total_amount - $discount_amount) + $shipping_charge;

// 7. DATABASE TRANSACTION
$conn->begin_transaction();

try {
    // 13 placeholders for 13 variables
    $sql_order = "INSERT INTO orders (
        user_id, shipping_name, phone, total_amount, grand_total, 
        discount_code, discount_amount, shipping_charge, shipping_address, 
        latitude, longitude, payment_method, transaction_code, status
    ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'Payment Submitted')";

    $stmt_order = $conn->prepare($sql_order);
    
    // FIXED TYPE STRING: "issddsddsddss" (13 characters)
    $stmt_order->bind_param(
        "issddsddsddss", 
        $user_id,          // 1 (i)
        $shipping_name,    // 2 (s)
        $phone,            // 3 (s)
        $total_amount,     // 4 (d)
        $grand_total,      // 5 (d)
        $discount_code,    // 6 (s)
        $discount_amount,  // 7 (d)
        $shipping_charge,  // 8 (d)
        $shipping_address, // 9 (s)
        $latitude,         // 10 (d)
        $longitude,        // 11 (d)
        $payment_method,   // 12 (s)
        $transaction_code  // 13 (s)
    );

    $stmt_order->execute();
    $order_id = $stmt_order->insert_id;
    $stmt_order->close();

    // Insert order items and deduct stock
    $sql_items = "INSERT INTO order_items (order_id, product_id, quantity, price_per_item) VALUES (?, ?, ?, ?)";
    $stmt_items = $conn->prepare($sql_items);
    
    $sql_stock = "UPDATE products SET stock_quantity = stock_quantity - ? WHERE id = ?";
    $stmt_stock = $conn->prepare($sql_stock);

    foreach ($products_data as $p_id => $p_info) {
        $qty = $cart_items[$p_id];
        $u_price = $p_info['price'];
        
        $stmt_items->bind_param("iiid", $order_id, $p_id, $qty, $u_price);
        $stmt_items->execute();

        $stmt_stock->bind_param("ii", $qty, $p_id);
        $stmt_stock->execute();
    }
    $stmt_items->close();
    $stmt_stock->close();

    // Handle Voucher Usage
    if ($discount_code) {
        $stmt_v = $conn->prepare("UPDATE discounts SET times_used = times_used + 1 WHERE code = ?");
        $stmt_v->bind_param("s", $discount_code);
        $stmt_v->execute();
    }

    $conn->commit();
    unset($_SESSION['cart'], $_SESSION['discount']);
    
    header("location: ../order_success.php?order_id=" . $order_id);
    exit();

} catch (Exception $e) {
    $conn->rollback();
    die("Critical Error: We could not process your order. Please try again. " . $e->getMessage());
} finally {
    $conn->close();
}
?>