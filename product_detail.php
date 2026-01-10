<?php
// Use the centralized init file for robust session handling
require_once 'includes/init.php';
require_once 'includes/db_connect.php';
// --- DATA FETCHING ---
$product_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if ($product_id <= 0) {
    header("location: products.php");
    exit();
}

// Get the user's wishlist to correctly render all heart icons on the page
$user_wishlist_ids = getUserWishlist($conn);

$res_ship = $conn->query("SELECT setting_value FROM site_settings WHERE setting_key = 'shipping_charge'");
$shipping_charge = (float)($res_ship->fetch_assoc()['setting_value'] ?? 0);

// Fetch main product details and its category info
$stmt = $conn->prepare("SELECT p.*, c.id as category_id, c.name as category_name FROM products p LEFT JOIN categories c ON p.category_id = c.id WHERE p.id = ?");
$stmt->bind_param("i", $product_id);
$stmt->execute();
$product = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$product) {
    require_once 'includes/header.php';
    echo "<p class='text-red-500 text-center py-12'>Product not found.</p>";
    require_once 'includes/footer.php';
    exit();
}

// Fetch "Total Sold" count
$stmt_sold = $conn->prepare("SELECT SUM(oi.quantity) as total_sold FROM order_items oi JOIN orders o ON oi.order_id = o.id WHERE oi.product_id = ? AND o.status = 'Completed'");
$stmt_sold->bind_param("i", $product_id);
$stmt_sold->execute();
$total_sold = $stmt_sold->get_result()->fetch_assoc()['total_sold'] ?? 0;
$stmt_sold->close();

// Fetch reviews for this product
$reviews = [];
$stmt_reviews = $conn->prepare("SELECT r.*, u.name as user_name,u.profile_image_url  FROM reviews r JOIN users u ON r.user_id = u.id WHERE r.product_id = ? ORDER BY r.created_at DESC");
$stmt_reviews->bind_param("i", $product_id);
$stmt_reviews->execute();
$reviews_result = $stmt_reviews->get_result();
while($row = $reviews_result->fetch_assoc()){ $reviews[] = $row; }
$stmt_reviews->close();

// Fetch "Related Products" from the same category
$related_products = [];
if ($product['category_id']) {
    $stmt_related = $conn->prepare("SELECT p.*, c.name as category_name FROM products p LEFT JOIN categories c ON p.category_id = c.id WHERE p.category_id = ? AND p.id != ? ORDER BY RAND() LIMIT 4");
    $stmt_related->bind_param("ii", $product['category_id'], $product_id);
    $stmt_related->execute();
    $related_result = $stmt_related->get_result();
    while($row = $related_result->fetch_assoc()) { $related_products[] = $row; }
    $stmt_related->close();
}

require_once 'includes/header.php';
?>

<div class="bg-white p-4 sm:p-8 rounded-lg shadow-xl max-w-7xl mx-auto">
    <!-- Breadcrumb Navigation -->
    <nav class="text-sm mb-6 text-gray-500" aria-label="Breadcrumb">
        <ol class="list-none p-0 inline-flex items-center">
            <li class="flex items-center"><a href="index.php" class="hover:text-indigo-600">Home</a><i class="fas fa-chevron-right mx-2 text-xs"></i></li>
            <?php if ($product['category_id']): ?>
            <li class="flex items-center"><a href="products.php?category=<?= $product['category_id'] ?>" class="hover:text-indigo-600"><?= htmlspecialchars($product['category_name']) ?></a><i class="fas fa-chevron-right mx-2 text-xs"></i></li>
            <?php endif; ?>
            <li><span class="text-gray-400 truncate max-w-[200px]"><?= htmlspecialchars($product['name']) ?></span></li>
        </ol>
    </nav>

    <div class="grid grid-cols-1 lg:grid-cols-2 gap-12">
        <!-- Left Column: Product Image -->
        <div class="relative">
            <a href="#" class="wishlist-btn absolute top-4 right-4 z-10 bg-white rounded-full p-3 shadow-lg" data-product-id="<?= $product['id'] ?>">
                <i class="fa-heart fa-lg <?= in_array($product['id'], $user_wishlist_ids) ? 'fas text-red-500' : 'far' ?>"></i>
            </a>
            <img src="<?= htmlspecialchars($product['image_url']) ?>" alt="<?= htmlspecialchars($product['name']) ?>" class="w-full rounded-lg shadow-lg aspect-square object-cover">
        </div>

        <!-- Right Column: Product Details -->
        <div>
            <h1 class="text-4xl font-extrabold text-gray-900 tracking-tight"><?= htmlspecialchars($product['name']) ?></h1>
            <div class="mt-3 flex items-center">
                <?php if($product['rating_count'] > 0): ?>
                    <div class="flex items-center"><?php for($i = 1; $i <= 5; $i++): ?><i class="fas fa-star <?= $i <= round($product['average_rating']) ? 'text-amber-400' : 'text-gray-300' ?>"></i><?php endfor; ?></div>
                    <p class="ml-2 text-sm text-gray-600">(<?= $product['rating_count'] ?> reviews)</p>
                <?php else: ?><p class="text-sm text-gray-500">No reviews yet.</p><?php endif; ?>
            </div>
            <?php if ($total_sold > 5): ?>
                <div class="mt-4"><span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium bg-red-100 text-red-800"><i class="fas fa-fire mr-2"></i> <?= $total_sold ?> Sold</span></div>
            <?php endif; ?>
            <p class="mt-4 text-3xl font-bold text-gray-800">Rs. <?= number_format($product['price'], 2) ?></p>
            <div class="mt-6"><h3 class="font-semibold text-gray-700">Description</h3><p class="text-gray-600 mt-2 text-base leading-relaxed"><?= nl2br(htmlspecialchars($product['description'])) ?></p></div>
            
            <form action="actions/handle_cart.php" method="post" class="add-to-cart-form mt-8">
                <div class="flex items-center space-x-4 mb-6"><label for="quantity" class="font-semibold text-gray-700">Quantity:</label><input type="number" id="quantity" name="quantity" value="1" min="1" max="<?= $product['stock_quantity'] ?>" class="w-20 p-2 border rounded-lg text-center focus:outline-none focus:ring-2 focus:ring-indigo-500"><span class="text-sm font-medium <?= $product['stock_quantity'] > 10 ? 'text-green-600' : ($product['stock_quantity'] > 0 ? 'text-orange-600' : 'text-red-600') ?>"><?= $product['stock_quantity'] > 0 ? $product['stock_quantity'] . ' in stock' : 'Out of stock' ?></span></div>
                <input type="hidden" name="product_id" value="<?= $product['id'] ?>"><input type="hidden" name="action" value="add">
                <div class="flex flex-col sm:flex-row gap-4">
                    <button type="submit" <?= $product['stock_quantity'] <= 0 ? 'disabled' : '' ?> class="flex-1 bg-indigo-600 text-white py-4 px-8 rounded-lg text-lg font-bold hover:bg-indigo-700 transition-colors shadow-lg hover:shadow-xl disabled:bg-gray-400 disabled:cursor-not-allowed disabled:shadow-none"><i class="fas fa-cart-plus mr-3"></i> Add to Cart</button>
                    <button type="button" onclick="document.getElementById('buy_now_quantity').value = document.getElementById('quantity').value; document.getElementById('buy_now_form').submit();" <?= $product['stock_quantity'] <= 0 ? 'disabled' : '' ?> class="flex-1 bg-gray-800 text-white py-4 px-8 rounded-lg text-lg font-bold hover:bg-gray-900 transition-colors shadow-lg hover:shadow-xl disabled:bg-gray-400 disabled:cursor-not-allowed disabled:shadow-none"><i class="fas fa-bolt mr-3"></i> Buy Now</button>
                </div>
            </form>

            <!-- Hidden form for Direct Buy to bypass AJAX cart interception -->
            <form id="buy_now_form" action="buy_now.php" method="post" style="display:none;">
                <input type="hidden" name="product_id" value="<?= $product['id'] ?>">
                <input type="hidden" name="quantity" id="buy_now_quantity" value="1">
            </form>
            
      <div class="mt-8 border-t pt-6">
         <!-- Shipping Fee Section -->
          <div class="flex items-center text-gray-600">
            <i class="fas fa-truck fa-lg mr-3 text-indigo-500"></i>
            <div>
              <h4 class="font-semibold">Shipping Fee</h4>
              <p class="text-sm"><b>Rs. <?= number_format($shipping_charge) ?></b> Shipping Charge per order.</p>
            </div>
          </div>

    <!-- No Return Section -->
    <div class="flex items-center text-gray-600 mt-4">
        <i class="fas fa-times-circle fa-lg mr-3 text-indigo-500"></i>
        <div>
            <h4 class="font-semibold">No Returns</h4>
            <p class="text-sm">No return of item after purchase.</p>
        </div>
    </div>
   </div>
            
        </div>
    </div>
</div>

<!-- "You might also like" Section -->
<?php if (!empty($related_products)): ?>
<div class="mt-16">
    <h2 class="text-3xl font-bold text-center text-gray-800 mb-12">You Might Also Like</h2>
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-8">
        <?php foreach ($related_products as $related_product): ?>
        <div class="group relative bg-white rounded-lg shadow-md overflow-hidden transition-all duration-300 hover:shadow-xl hover:-translate-y-1">
            <a href="#" class="wishlist-btn absolute top-3 right-3 z-10 bg-white rounded-full p-2 shadow-md" data-product-id="<?= $related_product['id'] ?>">
                <!-- ** THE DEFINITIVE FIX IS HERE ** -->
                <i class="fa-heart <?= in_array($related_product['id'], $user_wishlist_ids) ? 'fas text-red-500' : 'far' ?>"></i>
            </a>
            <a href="product_detail.php?id=<?= $related_product['id'] ?>" class="block overflow-hidden"><img src="<?= htmlspecialchars($related_product['image_url']) ?>" alt="<?= htmlspecialchars($related_product['name']) ?>" class="w-full h-56 object-cover transform transition-transform duration-300 group-hover:scale-110"></a>
            <div class="p-4">
                <span class="inline-block bg-indigo-100 text-indigo-800 text-xs font-semibold px-2.5 py-0.5 rounded-full mb-2"><?= htmlspecialchars($related_product['category_name'] ?? 'Uncategorized') ?></span>
                <h3 class="text-lg font-bold text-gray-800 truncate"><a href="product_detail.php?id=<?= $related_product['id'] ?>" class="hover:text-indigo-600 transition-colors"><?= htmlspecialchars($related_product['name']) ?></a></h3>
                <p class="text-md font-semibold text-gray-700 mt-1">Rs. <?= number_format($related_product['price'], 2) ?></p>
                <form action="actions/handle_cart.php" method="post" class="add-to-cart-form mt-4"><input type="hidden" name="product_id" value="<?= $related_product['id'] ?>"><input type="hidden" name="quantity" value="1"><input type="hidden" name="action" value="add"><button type="submit" class="w-full bg-indigo-600 text-white py-2 rounded-md font-semibold hover:bg-indigo-700 transition-colors"><i class="fas fa-cart-plus mr-2"></i>Add to Cart</button></form>
            </div>
        </div>
        <?php endforeach; ?>
    </div>
</div>
<?php endif; ?>

<!-- Customer Reviews Section -->
<div class="mt-16 border-t pt-12">
    <h2 class="text-3xl font-bold text-center text-gray-800 mb-12">Customer Reviews</h2>
    <div class="space-y-8 max-w-3xl mx-auto">
        <?php if (!empty($reviews)): ?>
            <?php foreach ($reviews as $review): ?><div class="flex space-x-4 p-4 bg-gray-50 rounded-lg"><img src="<?= getProfileImage($review['profile_image_url']) ?>" alt="User" class="w-12 h-12 rounded-full object-cover"><div><div class="flex items-center space-x-2"><h4 class="font-bold text-gray-800"><?= htmlspecialchars($review['user_name']) ?></h4><span class="text-xs text-gray-400">• <?= date_format(date_create($review['created_at']), 'M j, Y') ?></span></div><div class="flex items-center mt-1"><?php for($i = 1; $i <= 5; $i++): ?><i class="fas fa-star text-xs <?= $i <= $review['rating'] ? 'text-amber-400' : 'text-gray-300' ?>"></i><?php endfor; ?></div><?php if(!empty($review['review_text'])): ?><p class="mt-2 text-gray-600"><?= nl2br(htmlspecialchars($review['review_text'])) ?></p><?php endif; ?></div></div><?php endforeach; ?>
        <?php else: ?><p class="text-center text-gray-500">This product has no reviews yet. Be the first!</p><?php endif; ?>
    </div>
</div>

<?php

require_once 'includes/footer.php';
$conn->close();
?>