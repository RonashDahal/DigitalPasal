<?php
require_once 'includes/admin_header.php';
require_once '../includes/db_connect.php';

// This advanced query fetches the order details and a concatenated list of product names.
$sql = "SELECT o.id, o.shipping_name, o.created_at, o.grand_total, o.status,
            (SELECT GROUP_CONCAT(p.name SEPARATOR ', ') 
             FROM order_items oi 
             JOIN products p ON oi.product_id = p.id 
             WHERE oi.order_id = o.id
             LIMIT 3) AS product_names
        FROM orders o
        ORDER BY o.created_at DESC";

$result = $conn->query($sql);
?>
<h1 class="text-3xl font-bold text-gray-800 mb-8">Manage Orders</h1>

<?php // These blocks display a success message if redirected from an action file.
if (isset($_GET['deleted'])): ?>
    <div class="bg-green-100 border-l-4 border-green-500 text-green-700 p-4 mb-6" role="alert">
        <p>Order has been successfully deleted.</p>
    </div>
<?php elseif (isset($_GET['cancelled'])): ?>
     <div class="bg-blue-100 border-l-4 border-blue-500 text-blue-700 p-4 mb-6" role="alert">
        <p>Order has been successfully cancelled.</p>
    </div>
<?php endif; ?>

<div class="bg-white shadow-sm border border-gray-200 rounded-2xl overflow-hidden">
    <!-- This div allows horizontal scrolling on mobile -->
    <div class="overflow-x-auto">
        <table class="min-w-[800px] w-full text-sm text-left">
            <thead class="bg-gray-50 border-b border-gray-100">
                <tr>
                    <th class="py-4 px-6 text-[10px] font-black uppercase text-gray-400 tracking-widest">Order ID</th>
                    <th class="py-4 px-6 text-[10px] font-black uppercase text-gray-400 tracking-widest">Customer</th>
                    <th class="py-4 px-6 text-[10px] font-black uppercase text-gray-400 tracking-widest">Products</th>
                    <th class="py-4 px-6 text-[10px] font-black uppercase text-gray-400 tracking-widest text-right">Total</th>
                    <th class="py-4 px-6 text-[10px] font-black uppercase text-gray-400 tracking-widest text-center">Status</th>
                    <th class="py-4 px-6 text-[10px] font-black uppercase text-gray-400 tracking-widest text-center">Actions</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-50">
                <?php while ($order = $result->fetch_assoc()): ?>
                <tr class="hover:bg-gray-50/50 transition-colors">
                    <td class="py-4 px-6 font-bold text-gray-900 whitespace-nowrap">#<?= $order['id'] ?></td>
                    <td class="py-4 px-6 font-medium text-gray-700 whitespace-nowrap"><?= htmlspecialchars($order['shipping_name']) ?></td>
                    <td class="py-4 px-6 text-gray-500 italic max-w-xs truncate">
                        <?= htmlspecialchars($order['product_names'] ?? 'N/A') ?>
                    </td>
                    <td class="py-4 px-6 text-right font-black text-gray-900 whitespace-nowrap">Rs. <?= number_format($order['grand_total'], 2) ?></td>
                    <td class="py-4 px-6 text-center whitespace-nowrap">
                        <?php 
                            $status = $order['status'];
                            $badge = "bg-gray-100 text-gray-600";
                            switch($status) {
                                case 'Payment Submitted': $badge = "bg-blue-50 text-blue-600 border border-blue-100"; break;
                                case 'Payment Verified':  $badge = "bg-green-50 text-green-600 border border-green-100"; break;
                                case 'Payment Rejected':  $badge = "bg-red-50 text-red-600 border border-red-100"; break;
                                case 'Processing':       $badge = "bg-yellow-50 text-yellow-600 border border-yellow-100"; break;
                                case 'Shipped':          $badge = "bg-purple-50 text-purple-600 border border-purple-100"; break;
                                case 'Completed':        $badge = "bg-green-600 text-white"; break;
                                case 'Cancelled':        $badge = "bg-gray-500 text-white"; break;
                            }
                        ?>
                        <span class="px-3 py-1 rounded-full text-[10px] font-black uppercase tracking-tighter <?= $badge ?>">
                            <?= htmlspecialchars($status) ?>
                        </span>
                    </td>
                    <td class="py-4 px-6 text-center whitespace-nowrap space-x-2">
                        <a href="order_detail.php?id=<?= $order['id'] ?>" class="text-indigo-600 font-bold hover:underline">View</a>
                        <?php if(in_array($order['status'], ['Pending', 'Payment Submitted', 'Processing', 'Shipped'])): ?>
                            <a href="actions/handle_orders.php?action=cancel&id=<?= $order['id'] ?>" class="text-orange-500 font-bold hover:underline" onclick="return confirm('Cancel this order?');">Cancel</a>
                        <?php endif; ?>
                    </td>
                </tr>
                <?php endwhile; ?>
            </tbody>
        </table>
    </div>
</div>
<?php
$conn->close();
require_once 'includes/admin_footer.php';
?>