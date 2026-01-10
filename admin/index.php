<?php
/**
 * admin/index.php
 * Step 3: Fully Responsive Dashboard with Premium UI
 */
require_once 'includes/admin_header.php';
require_once '../includes/db_connect.php';

// --- DATA FETCHING (Logic remains unchanged) ---
$total_sales = $conn->query("SELECT SUM(quantity_sold * price_per_item) as total FROM sales_ledger")->fetch_assoc()['total'] ?? 0;

$pending_orders_query = "SELECT COUNT(id) as total FROM orders 
                         WHERE status IN ('Payment Submitted', 'Payment Verified', 'Processing', 'Shipped', 'Pending')";
$pending_orders_count = $conn->query($pending_orders_query)->fetch_assoc()['total'] ?? 0;

$total_categories = $conn->query("SELECT COUNT(id) as total FROM categories WHERE is_active = 1")->fetch_assoc()['total'] ?? 0;
$pending_reviews_count = $conn->query("SELECT COUNT(id) as total FROM reviews")->fetch_assoc()['total'] ?? 0;

$recent_activity_query = "
    (SELECT 'order' as type, id, shipping_name as detail, CAST(total_amount AS DECIMAL(10,2)) as value, created_at 
     FROM orders 
     LIMIT 10)
    UNION ALL
    (SELECT 'review' as type, r.id, p.name as detail, CAST(r.rating AS DECIMAL(10,2)) as value, r.created_at 
     FROM reviews r 
     JOIN products p ON r.product_id = p.id 
     LIMIT 10)
    UNION ALL
    (SELECT 'customer' as type, id, name as detail, CAST(0 AS DECIMAL(10,2)) as value, created_at 
     FROM users 
     WHERE role = 'customer' 
     LIMIT 10)
    ORDER BY created_at DESC
    LIMIT 5
";
$recent_activity_result = $conn->query($recent_activity_query);

$top_products_query = "
    SELECT p.id, p.name, SUM(sl.quantity_sold) as total_sold
    FROM sales_ledger sl
    JOIN products p ON sl.product_id = p.id
    GROUP BY p.id
    ORDER BY total_sold DESC
    LIMIT 5
";
$top_products_result = $conn->query($top_products_query);
?>

<!-- 1. RESPONSIVE STATS GRID -->
<!-- grid-cols-1 (mobile), grid-cols-2 (tablet), grid-cols-4 (desktop) -->
<div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 md:gap-6 mb-8">
    
    <!-- Card: Revenue -->
    <div class="bg-white p-6 rounded-2xl shadow-sm border border-gray-100 flex items-center justify-between">
        <div>
            <p class="text-[10px] font-black text-gray-400 uppercase tracking-widest mb-1">Total Sales</p>
            <p class="text-xl font-black text-gray-900">Rs. <?= number_format($total_sales, 2) ?></p>
        </div>
        <div class="bg-green-100 text-green-600 rounded-xl p-3">
            <i class="fas fa-wallet fa-lg"></i>
        </div>
    </div>

    <!-- Card: Active Orders -->
    <div class="bg-white p-6 rounded-2xl shadow-sm border border-gray-100 flex items-center justify-between">
        <div>
            <p class="text-[10px] font-black text-gray-400 uppercase tracking-widest mb-1">Active Orders</p>
            <p class="text-xl font-black text-gray-900"><?= $pending_orders_count ?></p>
        </div>
        <div class="bg-yellow-100 text-yellow-600 rounded-xl p-3">
            <i class="fas fa-clock fa-lg"></i>
        </div>
    </div>

    <!-- Card: Categories -->
    <div class="bg-white p-6 rounded-2xl shadow-sm border border-gray-100 flex items-center justify-between">
        <div>
            <p class="text-[10px] font-black text-gray-400 uppercase tracking-widest mb-1">Categories</p>
            <p class="text-xl font-black text-gray-900"><?= $total_categories ?></p>
        </div>
        <div class="bg-blue-100 text-blue-600 rounded-xl p-3">
            <i class="fas fa-layer-group fa-lg"></i>
        </div>
    </div>

    <!-- Card: Reviews -->
    <div class="bg-white p-6 rounded-2xl shadow-sm border border-gray-100 flex items-center justify-between">
        <div>
            <p class="text-[10px] font-black text-gray-400 uppercase tracking-widest mb-1">Total Reviews</p>
            <p class="text-xl font-black text-gray-900"><?= $pending_reviews_count ?></p>
        </div>
        <div class="bg-purple-100 text-purple-600 rounded-xl p-3">
            <i class="fas fa-star fa-lg"></i>
        </div>
    </div>
</div>

<!-- 2. MAIN CONTENT GRID -->
<!-- Stacks vertically on mobile, 2/3 + 1/3 on desktop -->
<div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
    
    <!-- Left Column: Activity Feed -->
    <div class="lg:col-span-2 bg-white p-5 md:p-8 rounded-3xl shadow-sm border border-gray-100">
        <h2 class="text-lg font-black text-gray-800 mb-6 flex items-center uppercase tracking-tighter">
            <i class="fas fa-bolt text-yellow-500 mr-3"></i> Recent Activity
        </h2>
        <div class="space-y-4">
            <?php while($activity = $recent_activity_result->fetch_assoc()): ?>
            <div class="flex items-start space-x-4 p-4 bg-gray-50 rounded-2xl border border-transparent hover:border-indigo-100 transition-all">
                <div class="hidden sm:flex rounded-xl bg-white shadow-sm p-3 flex-shrink-0">
                    <?php if($activity['type'] === 'order'): ?><i class="fas fa-shopping-basket text-blue-500"></i>
                    <?php elseif($activity['type'] === 'review'): ?><i class="fas fa-comment-alt text-amber-500"></i>
                    <?php else: ?><i class="fas fa-user-plus text-green-500"></i><?php endif; ?>
                </div>
                <div class="flex-grow min-w-0">
                    <p class="text-sm text-gray-700 leading-snug">
                        <?php if($activity['type'] === 'order'): ?>
                            New order <span class="font-bold text-gray-900">#<?= $activity['id'] ?></span> by <span class="font-bold"><?= htmlspecialchars($activity['detail']) ?></span> for <span class="text-indigo-600 font-black">Rs. <?= number_format($activity['value'], 2) ?></span>.
                        <?php elseif($activity['type'] === 'review'): ?>
                            Rating <span class="font-bold text-amber-500"><?= $activity['value'] ?>★</span> on <span class="font-bold"><?= htmlspecialchars($activity['detail']) ?></span>.
                        <?php else: ?>
                            Customer <span class="font-bold text-gray-900"><?= htmlspecialchars($activity['detail']) ?></span> joined.
                        <?php endif; ?>
                    </p>
                    <span class="text-[10px] font-bold text-gray-400 uppercase mt-1 block tracking-tight">
                        <?= date_format(date_create($activity['created_at']), 'M j • g:i a') ?>
                    </span>
                </div>
                <?php if($activity['type'] === 'order'): ?>
                <a href="order_detail.php?id=<?= $activity['id'] ?>" class="flex-shrink-0 bg-white border border-gray-200 px-3 py-1.5 rounded-lg text-[10px] font-black text-indigo-600 hover:bg-indigo-600 hover:text-white transition-all uppercase">Manage</a>
                <?php endif; ?>
            </div>
            <?php endwhile; ?>
        </div>
    </div>

    <!-- Right Column: Top Selling -->
    <div class="bg-gray-900 rounded-3xl p-6 md:p-8 text-white shadow-xl">
        <h2 class="text-base font-black mb-6 flex items-center uppercase tracking-widest text-indigo-400">
            <i class="fas fa-trophy text-yellow-400 mr-3"></i> Top Selling
        </h2>
        <div class="space-y-6">
            <?php 
            $rank = 1;
            while($product = $top_products_result->fetch_assoc()): 
            ?>
            <div class="flex items-center justify-between group">
                <div class="flex items-center gap-3 min-w-0">
                    <span class="text-xs font-black text-gray-600 italic w-4">#<?= $rank++ ?></span>
                    <a href="../product_detail.php?id=<?= $product['id'] ?>" target="_blank" class="text-sm font-bold text-gray-200 hover:text-yellow-400 transition-colors truncate">
                        <?= htmlspecialchars($product['name']) ?>
                    </a>
                </div>
                <div class="flex-shrink-0 bg-gray-800 text-yellow-400 text-[9px] font-black px-2.5 py-1 rounded-full uppercase tracking-tighter ml-2">
                    <?= $product['total_sold'] ?> Sold
                </div>
            </div>
            <?php endwhile; ?>
        </div>
        
        <div class="mt-10 p-4 bg-white/5 border border-white/10 rounded-2xl">
            <p class="text-[9px] font-black text-gray-500 uppercase mb-1 tracking-widest">System Logic</p>
            <p class="text-[10px] text-gray-400 leading-tight">These figures are pulled from the permanent ledger and remain accurate even if order history is cleared.</p>
        </div>
    </div>

</div>

<?php
// Footer inclusion and connection close
require_once 'includes/admin_footer.php';
if(isset($conn)) { $conn->close(); }
?>