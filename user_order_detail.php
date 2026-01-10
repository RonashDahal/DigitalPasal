<?php
/**
 * user_order_detail.php
 * Premium Responsive Version with Custom Timeline and Conditional Payment Info
 */
require_once 'includes/init.php'; 

// 1. Security: Must be logged in
if (!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true) {
    header("location: login.php");
    exit;
}

require_once 'includes/header.php';
require_once 'includes/db_connect.php';

$order_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$user_id = $_SESSION['id'];
$order = null;
$order_items = [];

// 2. Fetch Data
if ($order_id > 0) {
    $stmt = $conn->prepare("SELECT * FROM orders WHERE id = ? AND user_id = ?");
    $stmt->bind_param("ii", $order_id, $user_id);
    $stmt->execute();
    $order = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if ($order) {
        $stmt_items = $conn->prepare("SELECT oi.*, p.name as product_name, p.image_url FROM order_items oi JOIN products p ON oi.product_id = p.id WHERE oi.order_id = ?");
        $stmt_items->bind_param("i", $order_id);
        $stmt_items->execute();
        $res = $stmt_items->get_result();
        while ($row = $res->fetch_assoc()) { $order_items[] = $row; }
        $stmt_items->close();
    }
}

if (!$order) {
    echo "<div class='text-center p-20'><h2 class='text-2xl font-bold text-gray-800'>Order Not Found</h2><a href='dashboard.php?view=orders' class='text-indigo-600 underline font-bold mt-4 block'>Return to Dashboard</a></div>";
    require_once 'includes/footer.php'; exit;
}

// 3. Status Mapping for Design
$statuses = [
    ['label' => 'Payment Submitted', 'icon' => 'fa-wallet'],
    ['label' => 'Payment Verified',  'icon' => 'fa-clipboard-check'],
    ['label' => 'Processing',        'icon' => 'fa-box-open'],
    ['label' => 'Shipped',           'icon' => 'fa-truck-fast'],
    ['label' => 'Completed',         'icon' => 'fa-circle-check']
];

$current_status_index = -1;
foreach($statuses as $index => $s) {
    if($s['label'] === $order['status']) {
        $current_status_index = $index;
        break;
    }
}

$is_rejected = ($order['status'] === 'Payment Rejected');
$is_cancelled = ($order['status'] === 'Cancelled');

// COD or Esewa Check 
$is_cod = ($order['payment_method'] === 'cod');

$explanations = [
    'Payment Submitted' => $is_cod 
        ? "Your order has been submitted. Our team will verify your details and contact you if needed." 
        : "We've received your eSewa code. Admin is currently verifying the transaction.",
    
    'Payment Verified'  => $is_cod
        ? "Order verified! Your items are now in the queue for packaging."
        : "Payment confirmed! Your order is now in the queue for packaging.",
    
    'Payment Rejected'  => "Verification failed. Please contact our ground support team for assistance.",
    'Processing'        => "Your items are being collected and packed for ground delivery.",
    'Shipped'           => "The delivery runner has left the desk and is heading to your spot.",
    'Completed'         => "Delivery successful! We hope you enjoy the Annual Function.",
    'Cancelled'         => "This order has been cancelled.",
];
?>

<div class="max-w-6xl mx-auto px-4 pb-20">
    <!-- Top Bar -->
    <div class="flex items-center justify-between mb-8">
        <a href="dashboard.php?view=orders" class="inline-flex items-center text-sm font-bold text-gray-500 hover:text-indigo-600 transition-colors">
            <i class="fas fa-chevron-left mr-2 text-xs"></i> BACK TO HISTORY
        </a>
        <div class="text-right">
            <p class="text-[10px] font-black text-gray-400 uppercase tracking-widest">Order Reference</p>
            <h2 class="text-xl font-black text-gray-800">#ORD-<?= $order['id'] ?></h2>
        </div>
    </div>

    <div class="flex flex-col lg:flex-row gap-8">
        
        <!-- LEFT COLUMN: Timeline & Logistics -->
        <div class="w-full lg:w-1/3 space-y-6">
            
            <!-- TRACK PROGRESS CARD -->
            <div class="bg-white rounded-3xl shadow-sm border border-gray-100 p-6 md:p-8">
                <h3 class="text-lg font-black text-gray-900 mb-6 flex items-center">
                    <i class="fas fa-map-location-dot text-indigo-600 mr-3"></i> LIVE TRACKING
                </h3>

                <?php if($is_rejected): ?>
                    <div class="p-4 bg-red-50 rounded-2xl border-2 border-red-100 text-red-700 text-center">
                        <i class="fas fa-times-circle fa-2x mb-2"></i>
                        <p class="font-black uppercase text-xs">Verification Failed</p>
                        <p class="text-sm mt-1"><?= $explanations['Payment Rejected'] ?></p>
                    </div>
                <?php elseif($is_cancelled): ?>
                    <div class="p-4 bg-gray-50 rounded-2xl border-2 border-gray-200 text-gray-500 text-center">
                        <i class="fas fa-ban fa-2x mb-2"></i>
                        <p class="font-black uppercase text-xs">Order Cancelled</p>
                    </div>
                <?php else: ?>
                    <!-- Explanatory Bubble -->
                    <div class="mb-8 p-4 bg-indigo-600 rounded-2xl text-white relative shadow-lg shadow-indigo-100">
                        <p class="text-[10px] font-black text-indigo-200 uppercase mb-1">Update from Ground:</p>
                        <p class="text-sm font-medium leading-snug">
                            <?= $explanations[$order['status']] ?? 'Processing your request...' ?>
                        </p>
                        <div class="absolute -bottom-1 left-6 w-3 h-3 bg-indigo-600 rotate-45"></div>
                    </div>

                    <!-- Modern Vertical Timeline -->
                    <div class="relative space-y-8 pl-4">
                        <div class="absolute left-[31px] top-2 bottom-2 w-1 bg-gray-100 rounded-full"></div>
                        <?php foreach($statuses as $idx => $s): 
                            $is_done = ($idx <= $current_status_index);
                            $is_current = ($idx == $current_status_index);
                        ?>
                        <div class="flex items-center group">
                            <div class="z-10 w-9 h-9 rounded-full flex items-center justify-center transition-all duration-500 
                                <?= $is_done ? 'bg-indigo-600 text-white ring-4 ring-indigo-50 shadow-md' : 'bg-white border-4 border-gray-100 text-gray-300' ?>">
                                <i class="fas <?= $s['icon'] ?> text-xs"></i>
                            </div>
                            <div class="ml-4">
                                <p class="text-xs font-black uppercase tracking-tighter <?= $is_done ? 'text-gray-900' : 'text-gray-300' ?>">
                                    <?= $s['label'] ?>
                                </p>
                                <?php if($is_current): ?>
                                    <span class="inline-block bg-yellow-400 text-indigo-900 text-[8px] px-1.5 py-0.5 rounded font-black uppercase tracking-widest mt-0.5">Active</span>
                                <?php endif; ?>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>

            <!-- LOGISTICS & ADDRESS CARD -->
            <div class="bg-gray-900 rounded-3xl p-6 md:p-8 text-white">
                <div class="mb-6">
                    <p class="text-[10px] font-black text-gray-500 uppercase mb-2 tracking-widest">Delivery Point</p>
                    <p class="font-bold text-lg"><?= htmlspecialchars($order['shipping_name']) ?></p>
                    <p class="text-gray-400 text-sm mt-1 italic"><?= nl2br(htmlspecialchars($order['shipping_address'])) ?></p>
                </div>
                <div class="pt-6 border-t border-gray-800">
                    <p class="text-[10px] font-black text-gray-500 uppercase mb-2 tracking-widest">Contact Info</p>
                    <div class="flex items-center gap-3">
                        <div class="w-8 h-8 bg-gray-800 rounded-full flex items-center justify-center">
                            <i class="fas fa-phone text-xs text-indigo-400"></i>
                        </div>
                        <span class="font-bold text-sm"><?= htmlspecialchars($order['phone']) ?></span>
                    </div>
                </div>
            </div>
        </div>

        <!-- RIGHT COLUMN: Items & Payment -->
        <div class="w-full lg:w-2/3 space-y-6">
            
            <!-- ORDERED ITEMS -->
            <div class="bg-white rounded-3xl shadow-sm border border-gray-100 overflow-hidden">
                <div class="p-6 md:p-8">
                    <h3 class="text-lg font-black text-gray-900 mb-6">Basket Items</h3>
                    <div class="grid grid-cols-1 gap-4">
                        <?php foreach($order_items as $item): ?>
                        <div class="flex items-center p-4 bg-gray-50 rounded-2xl border border-transparent hover:border-indigo-100 transition-all">
                            <img src="<?= htmlspecialchars($item['image_url']) ?>" class="w-20 h-20 object-cover rounded-xl shadow-sm">
                            <div class="ml-5 flex-grow">
                                <h4 class="font-bold text-gray-900 line-clamp-1"><?= htmlspecialchars($item['product_name']) ?></h4>
                                <p class="text-xs text-gray-400 font-bold uppercase mt-1">
                                    Qty: <?= $item['quantity'] ?> &bull; Rs. <?= number_format($item['price_per_item'], 2) ?>
                                </p>
                                
                                <?php if($order['status'] === 'Completed'): ?>
                                    <a href="write_review.php?order_item_id=<?= $item['id'] ?>" 
                                       class="inline-flex items-center mt-3 bg-amber-400 text-white px-4 py-1.5 rounded-full text-[10px] font-black uppercase tracking-wider hover:bg-amber-500 shadow-sm transition-all">
                                        <i class="fas fa-star mr-1.5"></i> Write a Review
                                    </a>
                                <?php endif; ?>
                            </div>
                            <div class="text-right">
                                <p class="font-black text-indigo-600">Rs. <?= number_format($item['price_per_item'] * $item['quantity'], 2) ?></p>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>

                <!-- PAYMENT INFO & PRICING -->
                <div class="p-6 md:p-8 bg-gray-50 border-t flex flex-col md:flex-row justify-between gap-8">
                
                                        

                    <!-- Conditional Payment Card -->
                    <div class="flex-grow">
                        <div class="bg-white p-5 rounded-2xl border border-gray-200">
                            <p class="text-[10px] font-black text-gray-400 uppercase tracking-widest mb-3">Payment Verification</p>
                            <div class="flex items-center gap-4">
                                <div class="w-12 h-12 <?= ($order['payment_method'] === 'card') ? 'bg-green-100' : 'bg-blue-100' ?> rounded-2xl flex items-center justify-center">
                                    <i class="fas <?= ($order['payment_method'] === 'card') ? 'fa-wallet text-green-600' : 'fa-money-bill-transfer text-blue-600' ?> text-xl"></i>
                                </div>
                                <div>
                                    <p class="font-black text-gray-900 uppercase text-xs">
                                        <?= ($order['payment_method'] === 'card') ? 'eSewa Manual Transfer' : 'Cash on Delivery (COD)' ?>
                                    </p>
                                    
                                    <?php if($order['payment_method'] === 'card'): ?>
                                        <p class="text-[10px] text-gray-400 font-bold uppercase mt-1">Transaction ID:</p>
                                        <p class="font-mono text-sm font-black text-red-500"><?= $order['transaction_code'] ?></p>
                                    <?php else: ?>
                                        <p class="text-[10px] text-gray-400 font-bold uppercase mt-1 italic text-indigo-500">Prepare exact change for speed.</p>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    </div>
                                <div class="flex justify-between text-sm">
                            <span class="text-gray-400 font-bold uppercase text-[10px]">Shipping Fee</span>
                            <span class="font-bold text-gray-700">Rs. <?= number_format($order['shipping_charge'], 2) ?></span>
                        </div>
                    <!-- Totals Sidebar -->
                    <div class="w-full md:w-64 space-y-2">
                        <div class="flex justify-between text-sm">
                            <span class="text-gray-400 font-bold uppercase text-[10px]">Subtotal</span>
                            <span class="font-bold text-gray-700">Rs. <?= number_format($order['total_amount'], 2) ?></span>
                        </div>
                        <?php if($order['discount_amount'] > 0): ?>
                        <div class="flex justify-between text-sm">
                            <span class="text-green-500 font-bold uppercase text-[10px]">Voucher (<?= $order['discount_code'] ?>)</span>
                            <span class="font-bold text-green-600">-Rs. <?= number_format($order['discount_amount'], 2) ?></span>
                        </div>
                        <?php endif; ?>
                        <div class="flex justify-between border-t border-gray-200 pt-3">
                            <span class="text-xs font-black text-gray-900 uppercase">Paid Total</span>
                            <span class="text-2xl font-black text-indigo-600">Rs. <?= number_format($order['grand_total'], 2) ?></span>
                        </div>
                        
                        <?php if ($order['status'] === 'Completed'): ?>
                            <a href="generate_invoice.php?order_id=<?= $order['id'] ?>" class="block w-full text-center bg-gray-900 text-white py-3 rounded-xl text-xs font-black uppercase mt-6 hover:bg-black transition-all shadow-xl shadow-gray-200">
                                <i class="fas fa-file-pdf mr-2"></i> Get Invoice
                            </a>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php
// CRITICAL: Footer must come BEFORE close() to prevent error
require_once 'includes/footer.php'; 
if(isset($conn)) { $conn->close(); }
?>