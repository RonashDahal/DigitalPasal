<?php

if (session_status() === PHP_SESSION_NONE) { session_start(); }
header('Content-Type: application/json');

// Calculate subtotal from scratch for security.
$subtotal = 0;
try {
    require_once '../includes/db_connect.php';
    $cart = $_SESSION['cart'] ?? [];

    if (!empty($cart)) {
        $product_ids = implode(',', array_keys($cart));
        $sql_products = "SELECT id, price FROM products WHERE id IN ($product_ids)";
        $result_products = $conn->query($sql_products);
        $products = [];
        while($row = $result_products->fetch_assoc()) $products[$row['id']] = $row;
        foreach($cart as $product_id => $quantity) {
            if(isset($products[$product_id])) {
                $subtotal += $products[$product_id]['price'] * $quantity;
            }
        }
    }

    $promo_code = trim($_POST['promo_code'] ?? '');

    if (empty($promo_code)) {
        unset($_SESSION['discount']);
        throw new Exception('Promo code removed.');
    }

    $stmt = $conn->prepare("SELECT * FROM discounts WHERE code = ? AND is_active = 1");
    $stmt->bind_param("s", $promo_code);
    $stmt->execute();
    $discount = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if (!$discount) { throw new Exception('Invalid promo code.'); }

    $today = date('Y-m-d');
    if (($discount['start_date'] && $today < $discount['start_date']) || ($discount['end_date'] && $today > $discount['end_date'])) {
        throw new Exception('This code is not active today.');
    }
    if ($discount['usage_limit'] !== null && $discount['times_used'] >= $discount['usage_limit']) {
        throw new Exception('This code has reached its usage limit.');
    }

    $discount_amount = 0;
    if ($discount['type'] == 'percentage') {
        $discount_amount = $subtotal * ($discount['value'] / 100);
    } else {
        $discount_amount = $discount['value'];
    }
    $discount_amount = round(min($subtotal, $discount_amount), 2);
    $grand_total = $subtotal - $discount_amount;

    $_SESSION['discount'] = ['code' => $discount['code'], 'amount' => $discount_amount];

    echo json_encode([
        'success' => true, 'message' => 'Discount applied!', 'code' => $discount['code'],
        'subtotal' => $subtotal, 'discount_amount' => $discount_amount, 'grand_total' => $grand_total
    ]);

} catch (Exception $e) {
    unset($_SESSION['discount']);
    // Always return a valid JSON response, even on failure.
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage(),
        'subtotal' => $subtotal, // Send back the original subtotal
        'grand_total' => $subtotal
    ]);
}
$conn->close();
exit();