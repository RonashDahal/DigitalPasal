<?php
/**
 * admin/order_detail.php
 * Updated Version: Includes Payment Verification and Geolocation for Admin
 */
require_once 'includes/admin_header.php';
require_once '../includes/db_connect.php';

// 1. Get the Order ID from URL
if (!isset($_GET['id'])) {
    header("Location: orders.php");
    exit();
}

$order_id = intval($_GET['id']);

// 2. Fetch Order Details (including new fields: phone, latitude, longitude, transaction_code)
$sql_order = "SELECT o.*, u.name as customer_name, u.email as customer_email 
              FROM orders o 
              JOIN users u ON o.user_id = u.id 
              WHERE o.id = ?";
$stmt_order = $conn->prepare($sql_order);
$stmt_order->bind_param("i", $order_id);
$stmt_order->execute();
$order_result = $stmt_order->get_result();
$order = $order_result->fetch_assoc();
$stmt_order->close();

if (!$order) { 
    die("<div class='p-8 text-center'><h1 class='text-2xl font-bold text-red-600'>Order not found.</h1><a href='orders.php' class='text-blue-500 underline'>Back to list</a></div>"); 
}

// 3. Fetch Order Items
$sql_items = "SELECT oi.*, p.name as product_name, p.image_url 
              FROM order_items oi 
              JOIN products p ON oi.product_id = p.id 
              WHERE oi.order_id = ?";
$stmt_items = $conn->prepare($sql_items);
$stmt_items->bind_param("i", $order_id);
$stmt_items->execute();
$items = $stmt_items->get_result();
?>

<!-- Error Messages for Finalized Orders -->
<?php if (isset($_GET['error']) && $_GET['error'] === 'final'): ?>
    <div class="bg-red-100 border-l-4 border-red-500 text-red-700 p-4 mb-6" role="alert">
        <p class="font-bold">Action Denied</p>
        <p>This order is finalized (Completed/Cancelled) and cannot be modified.</p>
    </div>
<?php endif; ?>

<div class="flex justify-between items-center mb-8">
    <h1 class="text-3xl font-bold text-gray-800">Order Details: #<?= $order['id'] ?></h1>
    <span class="px-4 py-2 rounded-lg font-bold text-sm bg-indigo-100 text-indigo-800">
        Current Status: <?= htmlspecialchars($order['status']) ?>
    </span>
</div>

<div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
    
    <!-- Left Section: Items Table -->
    <div class="lg:col-span-2 space-y-8">
        <div class="bg-white shadow-md rounded-lg p-6">
            <h2 class="text-xl font-bold mb-4 border-b pb-2">Items Ordered</h2>
            <div class="overflow-x-auto">
                <table class="min-w-full">
                    <thead class="bg-gray-50 text-xs uppercase text-gray-500">
                        <tr>
                            <th class="py-3 px-4 text-left">Product</th>
                            <th class="py-3 px-4 text-center">Quantity</th>
                            <th class="py-3 px-4 text-right">Price</th>
                            <th class="py-3 px-4 text-right">Subtotal</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y">
                    <?php while($item = $items->fetch_assoc()): ?>
                        <tr>
                            <td class="py-4 px-4 flex items-center">
                                <img src="../<?= htmlspecialchars($item['image_url'])?>" class="h-12 w-12 object-cover rounded mr-4">
                                <span class="text-sm font-medium"><?= htmlspecialchars($item['product_name'])?></span>
                            </td>
                            <td class="py-4 px-4 text-center text-sm"><?= $item['quantity'] ?></td>
                            <td class="py-4 px-4 text-right text-sm">Rs. <?= number_format($item['price_per_item'], 2) ?></td>
                            <td class="py-4 px-4 text-right text-sm font-bold">Rs. <?= number_format($item['price_per_item'] * $item['quantity'], 2) ?></td>
                        </tr>
                    <?php endwhile; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- NEW: Payment & Verification Section -->
        <div class="bg-white shadow-md rounded-lg p-6">
            <h2 class="text-xl font-bold mb-4 text-indigo-800 border-b pb-2">Payment & Location Verification</h2>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
                <!-- Payment Data -->
                <div class="space-y-4">
                    <div>
                        <span class="text-gray-500 block uppercase text-xs font-bold">Payment Method</span>
                        <span class="text-sm font-semibold">
                            <?= ($order['payment_method'] === 'card') ? 'eSewa Manual Transfer' : 'Cash on Delivery' ?>
                        </span>
                    </div>
                    <?php if ($order['payment_method'] === 'card'): ?>
                    <div>
                        <span class="text-gray-500 block uppercase text-xs font-bold">eSewa Transaction Code</span>
                        <span class="inline-block mt-1 font-mono bg-yellow-100 px-3 py-1 rounded text-red-600 font-bold text-lg">
                            <?= htmlspecialchars($order['transaction_code'] ?? 'NOT PROVIDED') ?>
                        </span>
                    </div>
                    <?php endif; ?>
                </div>

                <!-- Contact & GPS Data -->
                <div class="space-y-4">
                    <div>
                        <span class="text-gray-500 block uppercase text-xs font-bold">Contact Phone</span>
                        <span class="text-sm font-semibold"><?= htmlspecialchars($order['phone']) ?></span>
                    </div>
                    <div>
                        <span class="text-gray-500 block uppercase text-xs font-bold">GPS Coordinates (Exact Location)</span>
                        <?php if ($order['latitude'] && $order['longitude']): ?>
                            <a href="https://www.google.com/maps?q=<?= $order['latitude'] ?>,<?= $order['longitude'] ?>" target="_blank" class="inline-flex items-center mt-1 text-blue-600 font-bold hover:underline">
                                <i class="fas fa-map-marked-alt mr-2"></i> Open in Google Maps
                            </a>
                            <p class="text-xs text-gray-400 mt-1">(<?= $order['latitude'] ?>, <?= $order['longitude'] ?>)</p>
                        <?php else: ?>
                            <span class="text-gray-400 italic">No GPS coordinates provided</span>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Right Section: Summary & Status Update -->
    <div class="space-y-8">
        <!-- Totals Summary -->
        <div class="bg-white shadow-md rounded-lg p-6">
            <h2 class="text-xl font-bold mb-4 border-b pb-2">Order Summary</h2>
            <div class="space-y-3 text-sm">
                <div class="flex justify-between text-gray-600">
                    <span>Subtotal:</span>
                    <span>Rs. <?= number_format($order['total_amount'], 2) ?></span>
                </div>
                <!-- Discount Section -->
<?php if (!empty($order['discount_code'])): ?>
    <div class="flex justify-between items-center bg-green-50 p-2 rounded border border-green-200 text-green-700 my-2">
        <div class="flex flex-col">
            <span class="text-[10px] font-bold uppercase tracking-wider">Discount Applied</span>
            <span class="font-mono font-bold text-sm underline"><?= htmlspecialchars($order['discount_code']) ?></span>
        </div>
        <div class="text-right font-bold">
            -Rs. <?= number_format($order['discount_amount'], 2) ?>
        </div>
    </div>
                <?php endif; ?>
                                <div class="flex justify-between items-center py-1">
                <span class="text-gray-500 font-medium">Delivery Fee</span>
                <span class="bg-indigo-50 text-indigo-700 px-3 py-1 rounded-full text-xs font-black border border-indigo-100">
                    Rs. <?= number_format($order['shipping_charge'], 2) ?>
                </span>
            </div>
                <div class="flex justify-between font-bold text-lg pt-2 border-t mt-2">
                    <span>Grand Total:</span>
                    <span class="text-indigo-600">Rs. <?= number_format($order['grand_total'], 2) ?></span>
                </div>
            </div>
        </div>

        <!-- Shipping Address Card -->
        <div class="bg-white shadow-md rounded-lg p-6">
            <h2 class="text-xl font-bold mb-4 border-b pb-2">Shipping Information</h2>
            <div class="space-y-4 text-sm">
                <div>
                    <span class="text-gray-500 block uppercase text-xs font-bold">Recipient</span>
                    <p class="font-medium text-gray-800"><?= htmlspecialchars($order['shipping_name']) ?></p>
                    <p class="text-gray-500 text-xs"><?= htmlspecialchars($order['customer_email']) ?></p>
                </div>
                <div>
                    <span class="text-gray-500 block uppercase text-xs font-bold">Address</span>
                    <p class="text-gray-800 leading-relaxed"><?= nl2br(htmlspecialchars($order['shipping_address'])) ?></p>
                </div>
            </div>
        </div>

        <!-- Status Update Form -->
        <div class="bg-gray-800 shadow-md rounded-lg p-6 text-white">
            <h2 class="text-xl font-bold mb-4 border-b border-gray-700 pb-2">Update Order Status</h2>
            <?php 
                $is_final = in_array($order['status'], ['Completed', 'Cancelled']); 
            ?>
            
            <?php if ($is_final): ?>
                <div class="p-4 bg-gray-700 rounded-lg text-center">
                    <p class="text-gray-400 text-sm italic">This order is finalized and cannot be updated.</p>
                </div>
            <?php else: ?>
                <form action="actions/handle_orders.php" method="POST" class="space-y-4">
                    <input type="hidden" name="order_id" value="<?= $order['id'] ?>">
                    <div>
                        <label class="block text-xs font-bold uppercase text-gray-400 mb-1">Set New Status</label>
                        <select name="status" class="w-full p-2.5 bg-gray-700 border border-gray-600 rounded text-sm focus:ring-2 focus:ring-indigo-500 outline-none">
                            <option value="Payment Submitted" <?= $order['status'] == 'Payment Submitted' ? 'selected' : '' ?>>Payment Submitted</option>
                            <option value="Payment Verified" <?= $order['status'] == 'Payment Verified' ? 'selected' : '' ?>>Payment Verified (Paid)</option>
                            <option value="Payment Rejected" <?= $order['status'] == 'Payment Rejected' ? 'selected' : '' ?>>Payment Rejected</option>
                            <option value="Processing" <?= $order['status'] == 'Processing' ? 'selected' : '' ?>>Processing</option>
                            <option value="Shipped" <?= $order['status'] == 'Shipped' ? 'selected' : '' ?>>Shipped</option>
                            <option value="Completed" <?= $order['status'] == 'Completed' ? 'selected' : '' ?>>Completed</option>
                            <option value="Cancelled" <?= $order['status'] == 'Cancelled' ? 'selected' : '' ?>>Cancelled</option>
                        </select>
                    </div>
                    <button type="submit" class="w-full bg-indigo-600 hover:bg-indigo-700 text-white font-bold py-2.5 rounded transition">
                        Save Changes
                    </button>
                </form>
            <?php endif; ?>
        </div>
    </div>
</div>

<div class="mt-8 text-center">
    <a href="orders.php" class="text-gray-500 hover:text-indigo-600 font-medium">
        <i class="fas fa-arrow-left mr-2"></i> Back to All Orders
    </a>
</div>

<?php
$conn->close();
require_once 'includes/admin_footer.php';
?>