<?php
// 1. INITIALIZATION: Handles sessions, global functions, and database connection.
require_once 'includes/init.php'; 
require_once 'includes/db_connect.php';

// 2. DATA FETCHING for the page and its various components.
$settings_result = $conn->query("SELECT setting_key, setting_value FROM site_settings");
$settings = [];
if ($settings_result) {
    while ($row = $settings_result->fetch_assoc()) { $settings[$row['setting_key']] = $row['setting_value']; }
}
$user_wishlist_ids = getUserWishlist($conn);

// The SQL query for reviews.
$reviews_query = "
    SELECT 
        r.rating, r.review_text, 
        u.name as user_name, u.profile_image_url, 
        p.name as product_name 
    FROM reviews r 
    JOIN users u ON r.user_id = u.id 
    JOIN products p ON r.product_id = p.id 
    WHERE r.rating >= 4 AND r.review_text IS NOT NULL AND TRIM(r.review_text) != '' 
    ORDER BY r.created_at DESC 
    LIMIT 3
";
$reviews_result = $conn->query($reviews_query);

// 3. PAGE RENDERING: Include the header to start the HTML document.
require_once 'includes/header.php';
?>

<div class="bg-gray-100">

    <!-- 1. FUNCTIONAL SEARCH BAR -->
    <div class="bg-white sticky top-0 z-40 shadow-sm">
        <div class="container mx-auto px-4 py-3">
            <!-- **THE FIX IS HERE**: Form now correctly points to products.php -->
            <form action="products.php" method="get" class="flex items-center">
                <div class="relative w-full">
                    <div class="absolute inset-y-0 left-0 pl-4 flex items-center pointer-events-none">
                        <i class="fas fa-search text-gray-400"></i>
                    </div>
                    <!-- Input name is "search" to match products.php -->
                    <input type="search" id="search-input" name="search" placeholder="Search for products..." class="w-full bg-gray-100 border-2 border-transparent rounded-full py-2.5 pl-12 pr-32 focus:outline-none focus:ring-2 focus:ring-indigo-400 focus:bg-white transition">
                    
                    <!-- **THE FIX IS HERE**: Buttons are grouped in a div for better positioning -->
                    <div class="absolute inset-y-0 right-0 flex items-center">
                        <button type="button" id="clear-search-btn" class="hidden h-full px-4 text-gray-500 hover:text-gray-800">
                            <i class="fas fa-times-circle"></i>
                        </button>
                        <button type="submit" class="h-full bg-indigo-600 hover:bg-indigo-700 text-white font-semibold px-6 rounded-r-full flex items-center transition-colors">Search</button>
                    </div>
                </div>
            </form>
        </div>
    </div>
    
    <!-- 2. HERO BANNER -->
    <div class="container mx-auto px-4 mt-4">
        <div class="relative rounded-lg overflow-hidden shadow-lg h-48 sm:h-64 md:h-80 lg:h-96 bg-cover bg-center" style="background-image: url('<?= htmlspecialchars($settings['hero_image'] ?? 'https://via.placeholder.com/1200x600'); ?>')">
            <div class="absolute inset-0 bg-gradient-to-t from-black/50 to-transparent"></div>
            <div class="absolute bottom-0 left-0 p-6 md:p-10">
                 <h1 class="text-2xl md:text-4xl font-bold text-white"><?= nl2br($settings['hero_title'] ?? 'Find Your Style'); ?></h1>
                 <p class="mt-2 text-white/90 max-w-lg hidden sm:block"><?= htmlspecialchars($settings['hero_description'] ?? 'Top quality products selected just for you.'); ?></p>
            </div>
        </div>
    </div>

    <!-- 3. ICON-BASED COLLECTIONS -->
    <div class="container mx-auto px-4 sm:px-6 lg:px-8 mt-6 category">
        <div class="bg-white rounded-lg shadow p-4">
            <div class="grid grid-cols-4 sm:grid-cols-5 md:grid-cols-8 gap-4 text-center">
                 <?php
                $category_result = $conn->query("SELECT * FROM categories WHERE is_active = 1 AND image_url IS NOT NULL LIMIT 8");
                if ($category_result && $category_result->num_rows > 0) {
                    while($cat = $category_result->fetch_assoc()):
                ?>
                <a href="products.php?category=<?= $cat['id'] ?>" class="group">
                    <div class="w-16 h-16 mx-auto rounded-full overflow-hidden shadow-md transition-transform transform group-hover:scale-110 border-2 border-transparent group-hover:border-indigo-500">
                        <img src="<?= htmlspecialchars($cat['image_url']) ?>" alt="<?= htmlspecialchars($cat['name']) ?>" class="w-full h-full object-cover">
                    </div>
                    <p class="mt-2 text-xs sm:text-sm font-medium text-gray-700 truncate"><?= htmlspecialchars($cat['name']) ?></p>
                </a>
                <?php 
                    endwhile; 
                }
                ?>
            </div>
        </div>
    </div>

    <!-- 4. OUR BESTSELLERS (with Wishlist) -->
    <div class="container mx-auto px-4 sm:px-6 lg:px-8 mt-8">
        <div class="bg-white rounded-lg shadow p-4">
            <div class="flex justify-between items-center mb-4">
                <h2 class="text-2xl font-bold text-gray-800">Our Bestsellers</h2>
                <a href="products.php" class="text-sm font-semibold text-indigo-600 hover:underline">Shop All <i class="fas fa-chevron-right ml-1"></i></a>
            </div>
            
            <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-4">
                <?php
                $bestseller_query = "SELECT p.id, p.name, p.price, p.image_url, p.average_rating, p.rating_count, SUM(sl.quantity_sold) as total_sold FROM sales_ledger sl JOIN products p ON sl.product_id = p.id GROUP BY p.id ORDER BY total_sold DESC LIMIT 4";
                $product_result = $conn->query($bestseller_query);
                if ($product_result && $product_result->num_rows > 0) {
                    while ($product = $product_result->fetch_assoc()):
                ?>
                <div class="group bg-white rounded-lg overflow-hidden border border-gray-200 hover:shadow-xl transition-all duration-300 flex flex-col relative">
                    <a href="#" class="wishlist-btn absolute top-3 right-3 z-10 bg-white/80 backdrop-blur-sm rounded-full p-2 shadow-md" data-product-id="<?= $product['id'] ?>">
                        <i class="fa-heart <?= in_array($product['id'], $user_wishlist_ids) ? 'fas text-red-500' : 'far text-gray-600' ?>"></i>
                    </a>
                    <a href="product_detail.php?id=<?= $product['id'] ?>" class="block">
                        <img src="<?= htmlspecialchars($product['image_url']) ?>" alt="<?= htmlspecialchars($product['name']) ?>" class="w-full h-48 object-cover transition-transform duration-300 group-hover:scale-105">
                    </a>
                    <!-- Find this block in index.php and update -->
<div class="p-3 flex flex-col flex-grow">
    <h3 class="text-sm font-medium text-gray-800 flex-grow mb-2">
        <a href="product_detail.php?id=<?= $product['id'] ?>" class="hover:text-indigo-600"><?= htmlspecialchars($product['name']) ?></a>
    </h3>
    <div class="flex items-center justify-between">
        <p class="text-lg font-bold text-gray-900">Rs. <?= number_format($product['price'], 2) ?></p>
        
        <!-- Add to Cart Form (AJAX enabled) -->
        <form action="actions/handle_cart.php" method="post" class="add-to-cart-form">
            <input type="hidden" name="product_id" value="<?= $product['id'] ?>">
            <input type="hidden" name="quantity" value="1">
            <input type="hidden" name="action" value="add">
            <button type="submit" class="bg-indigo-100 text-indigo-600 hover:bg-indigo-600 hover:text-white rounded-full w-9 h-9 flex items-center justify-center transition-colors">
                <i class="fas fa-shopping-cart"></i>
            </button>
        </form>
    </div>

            <!-- NEW: Direct Buy Button (Standard Form) -->
            <form action="buy_now.php" method="post" class="mt-3">
                <input type="hidden" name="product_id" value="<?= $product['id'] ?>">
                <input type="hidden" name="quantity" value="1">
                <button type="submit" class="w-full bg-gray-800 text-white py-2 rounded-xl text-[10px] font-black uppercase tracking-tighter hover:bg-black transition-all">
                    <i class="fas fa-bolt mr-1"></i> Buy Now
                </button>
            </form>
        </div>
                </div>
                <?php 
                    endwhile; 
                } else {
                    echo "<p class='col-span-full text-center text-gray-500 py-8'>No bestsellers to show yet.</p>";
                }
                ?>
            </div>
        </div>
    </div>
    
    <!-- 5. ANNUAL FUNCTION EVENT BANNER -->
<div class="container mx-auto px-4 mt-8">
    <div class="bg-gradient-to-r from-indigo-600 to-purple-700 rounded-2xl shadow-xl overflow-hidden border-b-4 border-yellow-400">
        <div class="px-6 py-8 md:py-10 flex flex-col md:flex-row items-center justify-between gap-6">
            <div class="md:w-2/3 text-center md:text-left">
                <div class="inline-block bg-yellow-400 text-indigo-900 text-[10px] font-black px-2 py-0.5 rounded-full uppercase mb-3 tracking-widest">
                    Event Special 🎭
                </div>
                <h2 class="text-2xl md:text-3xl font-black text-white leading-tight">
                    Enjoy the Show, We’ll Handle the Rest!
                </h2>
                <p class="mt-2 text-indigo-100 text-sm md:text-base font-medium">
                    Skip the long queues! Order items directly from the ground and get fast delivery to your spot.
                </p>
            </div>
            <div class="md:w-1/3 text-center md:text-right">
                <a href="products.php" class="inline-block bg-white text-indigo-600 font-black py-3 px-10 rounded-xl hover:bg-yellow-50 transition-all shadow-lg hover:scale-105 active:scale-95 uppercase text-sm tracking-tighter">
                    Order Now
                </a>
            </div>
        </div>
    </div>
</div>


    <!-- 6. CUSTOMER TESTIMONIALS -->
    <div class="container mx-auto px-4 sm:px-6 lg:px-8 mt-8">
        <div class="mb-4">
            <h2 class="text-2xl font-bold text-gray-800">Customer Reviews</h2>
        </div>
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
            <?php if ($reviews_result && $reviews_result->num_rows > 0): ?>
                <?php while($review = $reviews_result->fetch_assoc()): ?>
                <div class="bg-white p-6 shadow rounded-lg flex flex-col">
                    <div class="flex-1">
                        <div class="flex items-center text-amber-400 mb-2"><?php for($i = 1; $i <= 5; $i++): ?><i class="fas fa-star text-sm <?= $i <= $review['rating'] ? '' : 'text-gray-300' ?>"></i><?php endfor; ?></div>
                        <p class="text-gray-600 text-sm italic">"<?= htmlspecialchars($review['review_text']) ?>"</p>
                    </div>
                    <div class="mt-4 flex items-center gap-x-3 pt-4 border-t border-gray-100">
                        <img class="h-12 w-12 rounded-full bg-gray-50 object-cover" src="<?= getProfileImage($review['profile_image_url']) ?>" alt="User profile picture">
                        <div>
                            <p class="font-semibold text-sm text-gray-900"><?= htmlspecialchars($review['user_name']) ?></p>
                            <p class="text-xs text-gray-500">For: <?= htmlspecialchars($review['product_name']) ?></p>
                        </div>
                    </div>
                </div>
                <?php endwhile; ?>
            <?php else: ?>
                <div class="bg-white p-6 rounded-lg shadow col-span-full text-center text-gray-500">No recent reviews to show.</div>
            <?php endif; ?>
        </div>
    </div>
    
    <!-- 7. EVENT TRUST & SERVICE SECTION -->
<div class="mt-8">
    <div class="bg-gray-900 text-white py-16">
        <div class="container mx-auto px-6 lg:px-8">
            <div class="text-center mb-12">
                <h2 class="text-3xl font-bold tracking-tight sm:text-4xl uppercase">Service You Can Count On</h2>
                <p class="mt-4 text-lg text-gray-400">Making your Annual Function experience seamless and enjoyable.</p>
            </div>
            <div class="grid grid-cols-2 sm:grid-cols-2 lg:grid-cols-4 gap-8">
                <!-- Service 1 -->
                <div class="text-center">
                    <div class="mx-auto h-16 w-16 flex items-center justify-center rounded-full bg-indigo-500 mb-4">
                        <i class="fas fa-running fa-2x"></i>
                    </div>
                    <h3 class="text-xl font-bold">Quick Delivery</h3>
                    <p class="mt-2 text-sm text-gray-400">Directly to your spot on the school ground.</p>
                </div>
                <!-- Service 2 -->
                <div class="text-center">
                    <div class="mx-auto h-16 w-16 flex items-center justify-center rounded-full bg-indigo-500 mb-4">
                        <i class="fas fa-check-circle fa-2x"></i>
                    </div>
                    <h3 class="text-xl font-bold">Quality Items</h3>
                    <p class="mt-2 text-sm text-gray-400">Fresh and carefully picked for our guests.</p>
                </div>
                <!-- Service 3 -->
                <div class="text-center">
                    <div class="mx-auto h-16 w-16 flex items-center justify-center rounded-full bg-indigo-500 mb-4">
                        <i class="fas fa-users fa-2x"></i>
                    </div>
                    <h3 class="text-xl font-bold">Student Help</h3>
                    <p class="mt-2 text-sm text-gray-400">Our volunteer team is ready to assist you.</p>
                </div>
                <!-- Service 4 -->
                <div class="text-center">
                    <div class="mx-auto h-16 w-16 flex items-center justify-center rounded-full bg-indigo-500 mb-4">
                        <i class="fas fa-wallet fa-2x"></i>
                    </div>
                    <h3 class="text-xl font-bold">Easy Payment</h3>
                    <p class="mt-2 text-sm text-gray-400">Pay via eSewa or Cash at the ground.</p>
                </div>
            </div>
        </div>
    </div>
</div>
    
 
</div>

<!-- JavaScript for Search Clear Button -->
<script>
document.addEventListener('DOMContentLoaded', function() {
    const searchInput = document.getElementById('search-input');
    const clearSearchBtn = document.getElementById('clear-search-btn');

    if (searchInput && clearSearchBtn) {
        searchInput.addEventListener('input', function() {
            // Show button if text exists, hide otherwise
            clearSearchBtn.classList.toggle('hidden', searchInput.value.length === 0);
        });

        clearSearchBtn.addEventListener('click', function() {
            searchInput.value = '';
            clearSearchBtn.classList.add('hidden');
            searchInput.focus();
        });
    }
});
</script>

<div class="pb-24 lg:hidden"></div>

</div> <!-- This closes the main bg-gray-100 container -->

<?php
// Correct Order: Footer is included, THEN connection is closed.
require_once 'includes/footer.php';
if(isset($conn)) { $conn->close(); }
?>