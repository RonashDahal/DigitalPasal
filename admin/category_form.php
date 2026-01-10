<?php
require_once 'includes/admin_header.php';
require_once '../includes/db_connect.php';

$is_edit = false;
$category = [
    'id' => '', 'name' => '', 'description' => '', 'image_url' => '',
    'is_active' => 1, 'products_per_page' => 8, 'default_sort_order' => 'newest'
];
$products_in_category = []; // Initialize an empty array for products

if (isset($_GET['id'])) {
    $is_edit = true;
    $id = intval($_GET['id']);
    
    // Fetch the category details
    $stmt = $conn->prepare("SELECT * FROM categories WHERE id = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $result = $stmt->get_result();
    $category = $result->fetch_assoc();
    $stmt->close();

    // ** NEW QUERY **: If editing, fetch all products in this category.
    if ($category) {
        $stmt_products = $conn->prepare("SELECT id, name FROM products WHERE category_id = ? ORDER BY name ASC");
        $stmt_products->bind_param("i", $id);
        $stmt_products->execute();
        $products_result = $stmt_products->get_result();
        while ($row = $products_result->fetch_assoc()) {
            $products_in_category[] = $row;
        }
        $stmt_products->close();
    }
}
?>

<h1 class="text-3xl font-bold text-gray-800 mb-8"><?= $is_edit ? 'Edit' : 'Add New'; ?> Category</h1>

<div class="bg-white shadow-md rounded-lg p-8 max-w-4xl mx-auto">
    <form action="actions/handle_categories.php" method="post" enctype="multipart/form-data">
        <input type="hidden" name="action" value="<?= $is_edit ? 'edit' : 'add'; ?>">
        <?php if ($is_edit): ?>
            <input type="hidden" name="id" value="<?= $category['id']; ?>">
        <?php endif; ?>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
            <!-- Left Column: Form Fields -->
            <div>
                <div class="mb-4">
                    <label class="block text-gray-700 font-bold mb-2" for="name">Category Name</label>
                    <input type="text" id="name" name="name" value="<?= htmlspecialchars($category['name']) ?>" class="shadow appearance-none border rounded w-full py-2 px-3" required>
                </div>
                <div class="mb-4">
                    <label class="block text-gray-700 font-bold mb-2" for="description">Description</label>
                    <textarea id="description" name="description" rows="6" class="shadow appearance-none border rounded w-full py-2 px-3"><?= htmlspecialchars($category['description'] ?? '') ?></textarea>
                </div>
                <div class="mb-6">
                    <label class="block text-gray-700 font-bold mb-2" for="image">Category Image/Banner</label>
                    <input type="file" id="image" name="image" class="shadow appearance-none border rounded w-full py-2 px-3">
                    <?php if (!empty($category['image_url'])): ?>
                        <div class="mt-4">
                            <img src="../<?= htmlspecialchars($category['image_url']) ?>" class="w-48 h-auto rounded shadow">
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Right Column - Settings -->
            <div class="bg-gray-50 p-6 rounded-lg border">
                <h3 class="text-xl font-semibold mb-4 border-b pb-2">Category Settings</h3>
                <div class="mb-4">
                    <label class="block text-gray-700 font-bold mb-2" for="is_active">Visibility</label>
                    <select id="is_active" name="is_active" class="shadow border rounded w-full py-2 px-3">
                        <option value="1" <?= ($category['is_active'] == 1) ? 'selected' : '' ?>>Active (Visible)</option>
                        <option value="0" <?= ($category['is_active'] == 0) ? 'selected' : '' ?>>Inactive (Hidden)</option>
                    </select>
                </div>
                <div class="mb-4">
                    <label class="block text-gray-700 font-bold mb-2" for="products_per_page">Products Per Page</label>
                    <input type="number" id="products_per_page" name="products_per_page" min="1" max="50" value="<?= htmlspecialchars($category['products_per_page']) ?>" class="shadow appearance-none border rounded w-full py-2 px-3">
                </div>
                <div class="mb-4">
                    <label class="block text-gray-700 font-bold mb-2" for="default_sort_order">Default Sort Order</label>
                    <select id="default_sort_order" name="default_sort_order" class="shadow border rounded w-full py-2 px-3">
                        <option value="newest" <?= ($category['default_sort_order'] == 'newest') ? 'selected' : '' ?>>Newest First</option>
                        <option value="price_asc" <?= ($category['default_sort_order'] == 'price_asc') ? 'selected' : '' ?>>Price: Low to High</option>
                        <option value="price_desc" <?= ($category['default_sort_order'] == 'price_desc') ? 'selected' : '' ?>>Price: High to Low</option>
                        <option value="name_asc" <?= ($category['default_sort_order'] == 'name_asc') ? 'selected' : '' ?>>Name: A-Z</option>
                    </select>
                </div>
            </div>
        </div>
            
                                    <div class="mb-4 p-4 bg-white rounded-xl border border-gray-200">
                            <h4 class="text-xs font-black uppercase text-gray-400 mb-3 tracking-widest">Allowed Payment Methods</h4>
                            <div class="space-y-3">
                                <label class="flex items-center cursor-pointer">
                                    <input type="checkbox" name="allow_cod" value="1" <?= ($category['allow_cod'] == 1) ? 'checked' : '' ?> class="rounded text-indigo-600 focus:ring-indigo-500 mr-3">
                                    <span class="text-sm font-bold text-gray-700">Allow Cash on Delivery (COD)</span>
                                </label>
                                <label class="flex items-center cursor-pointer">
                                    <input type="checkbox" name="allow_esewa" value="1" <?= ($category['allow_esewa'] == 1) ? 'checked' : '' ?> class="rounded text-indigo-600 focus:ring-indigo-500 mr-3">
                                    <span class="text-sm font-bold text-gray-700">Allow eSewa Manual Transfer</span>
                                </label>
                            </div>
                        </div>

        <!-- Action Buttons -->
        <div class="flex justify-end mt-8 border-t pt-6">
            <a href="categories.php" class="bg-gray-500 hover:bg-gray-600 text-white font-bold py-2 px-4 rounded mr-2">Cancel</a>
            <button type="submit" class="bg-blue-500 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded">
                Save Category
            </button>
        </div>
    </form>

    <?php // --- NEW SECTION: PRODUCTS IN THIS CATEGORY --- ?>
    <?php if ($is_edit): ?>
        <div class="mt-12 border-t pt-8">
            <h2 class="text-2xl font-bold text-gray-800 mb-4">Products in this Category</h2>
            <div class="bg-gray-50 p-6 rounded-lg border max-h-96 overflow-y-auto">
                <?php if (!empty($products_in_category)): ?>
                    <ul class="space-y-3">
                        <?php foreach ($products_in_category as $product): ?>
                            <li class="flex justify-between items-center">
                                <span class="text-gray-700"><?= htmlspecialchars($product['name']) ?></span>
                                <a href="product_form.php?id=<?= $product['id'] ?>" class="text-sm text-indigo-600 hover:underline">Edit Product</a>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                <?php else: ?>
                    <p class="text-gray-500 italic">No products are currently in this category.</p>
                <?php endif; ?>
            </div>
        </div>
    <?php endif; ?>

</div>

<?php
$conn->close();
require_once 'includes/admin_footer.php';
?>