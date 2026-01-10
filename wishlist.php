<?php
require_once 'includes/init.php';
if (!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true) {
    header("location: login.php?redirect=wishlist");
    exit;
}
require_once 'includes/db_connect.php';

// Fetch all products in the user's wishlist
$user_id = $_SESSION['id'];
$wishlist_items = [];
$sql = "SELECT p.*, c.name as category_name 
        FROM products p 
        JOIN wishlist w ON p.id = w.product_id 
        LEFT JOIN categories c ON p.category_id = c.id
        WHERE w.user_id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
while($row = $result->fetch_assoc()) {
    $wishlist_items[] = $row;
}
$stmt->close();
// We need this for the heart icons on this page
$user_wishlist_ids = array_column($wishlist_items, 'id'); 

require_once 'includes/header.php';
?>

<h1 class="text-3xl font-bold text-gray-800 mb-8">My Wishlist</h1>

<?php if (empty($wishlist_items)): ?>
    <div class="text-center bg-white p-12 rounded-lg shadow-md">
       <i class="fa-heart <?= in_array($product['id'], $user_wishlist_ids) ? 'fas text-red-500' : 'far' ?>"></i>
        <h2 class="text-2xl font-semibold text-gray-700 mb-2">Your wishlist is empty</h2>
        <p class="text-gray-500 mb-6">Click the heart icon on any product to save it here.</p>
        <a href="products.php" class="bg-indigo-600 text-white px-8 py-3 rounded-lg font-semibold hover:bg-indigo-700 transition-colors">
            Discover Products
        </a>
    </div>
<?php else: ?>
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-8">
        <?php foreach ($wishlist_items as $product): ?>
            <!-- Using the same professional product card design -->
            <div class="group relative bg-white rounded-lg shadow-md overflow-hidden transition-all duration-300 hover:shadow-xl hover:-translate-y-1">
                <a href="#" class="wishlist-btn absolute top-3 right-3 z-10 bg-white rounded-full p-2 shadow-md" data-product-id="<?= $product['id'] ?>">
                    <i class="fa-heart <?= in_array($product['id'], $user_wishlist_ids) ? 'fas text-red-500' : 'far' ?>"></i>
                </a>
                <a href="product_detail.php?id=<?= $product['id'] ?>" class="block overflow-hidden">
                    <img src="<?= htmlspecialchars($product['image_url']) ?>" alt="<?= htmlspecialchars($product['name']) ?>" class="w-full h-56 object-cover transform transition-transform duration-300 group-hover:scale-110">
                </a>
                <div class="p-4">
                    <span class="inline-block bg-indigo-100 text-indigo-800 text-xs font-semibold px-2.5 py-0.5 rounded-full mb-2">
                        <?= htmlspecialchars($product['category_name'] ?? 'Uncategorized') ?>
                    </span>
                    <h3 class="text-lg font-bold text-gray-800 truncate">
                        <a href="product_detail.php?id=<?= $product['id'] ?>" class="hover:text-indigo-600 transition-colors">
                            <?= htmlspecialchars($product['name']) ?>
                        </a>
                    </h3>
                    <p class="text-md font-semibold text-gray-700 mt-1">$<?= number_format($product['price'], 2) ?></p>
                    <form action="actions/handle_cart.php" method="post" class="add-to-cart-form mt-4">
                        <input type="hidden" name="product_id" value="<?= $product['id'] ?>"><input type="hidden" name="quantity" value="1"><input type="hidden" name="action" value="add">
                        <button type="submit" class="w-full bg-indigo-600 text-white py-2 rounded-md font-semibold hover:bg-indigo-700 transition-colors">
                            <i class="fas fa-cart-plus mr-2"></i>Add to Cart
                        </button>
                    </form>
                            <form class="pt-4" action="buy_now.php" method="post">
                    <input type="hidden" name="product_id" value="<?= $product['id'] ?>">
                    <input type="hidden" name="quantity" value="1">
                    <button type="submit" class="w-full bg-gray-800 text-white py-2 rounded-xl text-[10px] font-black uppercase hover:bg-black transition-all">
                        Buy Now
                    </button>
                </form>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
    <div class="pb-24 lg:hidden"></div>

</div> <!-- This closes the main bg-gray-100 container -->
<?php endif; ?>

<?php
require_once 'includes/footer.php';
$conn->close();
?>
<script>
// Attach listeners on this page too, especially for removing items
document.addEventListener('DOMContentLoaded', function() {
    window.attachWishlistListeners();
    window.attachAddToCartListeners(); // For the 'Add to Cart' buttons on this page
});
</script>