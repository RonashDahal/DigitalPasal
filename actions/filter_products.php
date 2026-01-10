<?php
// Use the centralized init file for robust session handling.
require_once '../includes/init.php';
require_once '../includes/db_connect.php';

// Get the current user's wishlist from the database.
$user_wishlist_ids = getUserWishlist($conn);

// --- BUILD THE DYNAMIC SQL QUERY ---
$sql = "SELECT p.*, c.name as category_name 
        FROM products p 
        LEFT JOIN categories c ON p.category_id = c.id";

$where_clauses = [];
$params = [];
$types = '';

// 1. Search filter
if (!empty($_GET['search'])) {
    $where_clauses[] = "p.name LIKE ?";
    $params[] = '%' . $_GET['search'] . '%';
    $types .= 's';
}

// 2. Category filter
if (!empty($_GET['categories']) && is_array($_GET['categories'])) {
    $cat_placeholders = implode(',', array_fill(0, count($_GET['categories']), '?'));
    $where_clauses[] = "p.category_id IN ($cat_placeholders)";
    foreach ($_GET['categories'] as $cat_id) {
        $params[] = (int)$cat_id;
        $types .= 'i';
    }
}

// 3. Price range filter
if (!empty($_GET['price_max'])) {
    $where_clauses[] = "p.price <= ?";
    $params[] = (float)$_GET['price_max'];
    $types .= 'd';
}

// Combine WHERE clauses if any exist
if (!empty($where_clauses)) {
    $sql .= " WHERE " . implode(' AND ', $where_clauses);
}

// 4. Sorting
$sort_order = $_GET['sort'] ?? 'newest';
switch ($sort_order) {
    case 'price_asc': $sql .= " ORDER BY p.price ASC"; break;
    case 'price_desc': $sql .= " ORDER BY p.price DESC"; break;
    case 'name_asc': $sql .= " ORDER BY p.name ASC"; break;
    default: $sql .= " ORDER BY p.created_at DESC"; break;
}

// --- PREPARE AND EXECUTE THE QUERY ---
$stmt = $conn->prepare($sql);
if (!empty($types) && !empty($params)) {
    $stmt->bind_param($types, ...$params);
}
$stmt->execute();
$result = $stmt->get_result();

// --- GENERATE AND RETURN THE HTML ---
if ($result->num_rows > 0) {
    while ($product = $result->fetch_assoc()) {
        
        $wishlist_classes = in_array($product['id'], $user_wishlist_ids) ? 'fas text-red-500' : 'far text-gray-600';

        // ** THE FIX IS HERE**: This HTML now matches the design of the homepage bestseller cards.
        echo '
        <div class="group bg-white rounded-lg overflow-hidden border border-gray-200 hover:shadow-xl transition-all duration-300 flex flex-col relative">
            
            <!-- Wishlist Button -->
            <a href="#" class="wishlist-btn absolute top-3 right-3 z-10 bg-white/80 backdrop-blur-sm rounded-full p-2 shadow-md" data-product-id="' . $product['id'] . '">
                <i class="fa-heart ' . $wishlist_classes . '"></i>
            </a>

            <!-- Product Image Link -->
            <a href="product_detail.php?id=' . $product['id'] . '" class="block">
                <img src="' . htmlspecialchars($product['image_url']) . '" 
                     alt="' . htmlspecialchars($product['name']) . '" 
                     class="w-full h-48 object-cover transition-transform duration-300 group-hover:scale-105">
            </a>

            <!-- Card Content -->
            <div class="p-3 flex flex-col flex-grow">
                <h3 class="text-sm font-medium text-gray-800 flex-grow mb-2">
                    <a href="product_detail.php?id=' . $product['id'] . '" class="hover:text-indigo-600">
                        ' . htmlspecialchars($product['name']) . '
                    </a>
                </h3>
                <div class="flex items-center justify-between mt-auto">
                    <p class="text-lg font-bold text-gray-900">Rs. ' . number_format($product['price'], 2) . '</p>
                    <form action="actions/handle_cart.php" method="post" class="add-to-cart-form">
                        <input type="hidden" name="product_id" value="' . $product['id'] . '">
                        <input type="hidden" name="quantity" value="1">
                        <input type="hidden" name="action" value="add">
                        <button type="submit" class="bg-indigo-100 text-indigo-600 hover:bg-indigo-600 hover:text-white rounded-full w-9 h-9 flex items-center justify-center transition-colors">
                            <i class="fas fa-shopping-cart"></i>
                        </button>
                    </form>
                </div>
                        <form action="buy_now.php" method="post" class="mt-3">
            <input type="hidden" name="product_id" value="' . $product['id'] . '">
            <input type="hidden" name="quantity" value="1">
            <button type="submit" class="w-full bg-gray-800 text-white py-2 rounded-xl text-[10px] font-black uppercase tracking-tighter hover:bg-black transition-all">
                <i class="fas fa-bolt mr-1"></i> Buy Now
            </button>
        </form>
            </div>
        </div>';
    }
} else {
    // If no products match, return a more visually appealing message
    echo '
    <div class="col-span-full text-center py-16 px-6">
        <i class="fas fa-box-open fa-4x text-gray-300 mb-4"></i>
        <h3 class="text-xl font-semibold text-gray-700">No Products Found</h3>
        <p class="text-gray-500 mt-2">Try adjusting your filters or search term.</p>
    </div>';
}

$stmt->close();
$conn->close();
?>