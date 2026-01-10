<?php
require_once 'includes/admin_header.php';
require_once '../includes/db_connect.php';
$categories_result = $conn->query("SELECT * FROM categories ORDER BY name ASC");

$is_edit = false;
$product = [
    'id' => '', 'name' => '', 'description' => '', 
    'price' => '', 'stock_quantity' => '', 'image_url' => ''
];

if (isset($_GET['id'])) {
    $is_edit = true;
    $id = intval($_GET['id']);
    $stmt = $conn->prepare("SELECT * FROM products WHERE id = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($result->num_rows > 0) {
        $product = $result->fetch_assoc();
    }
    $stmt->close();
}
?>

<h1 class="text-3xl font-bold text-gray-800 mb-8"><?= $is_edit ? 'Edit' : 'Add New'; ?> Product</h1>

<div class="bg-white shadow-md rounded-lg p-8 max-w-2xl mx-auto">
    <form action="actions/handle_products.php" method="post" enctype="multipart/form-data">
        <input type="hidden" name="action" value="<?= $is_edit ? 'edit' : 'add'; ?>">
        <?php if ($is_edit): ?>
            <input type="hidden" name="id" value="<?= $product['id']; ?>">
        <?php endif; ?>

        <div class="mb-4">
            <label class="block text-gray-700 font-bold mb-2" for="name">Product Name</label>
            <input type="text" id="name" name="name" value="<?= htmlspecialchars($product['name']) ?>" class="shadow appearance-none border rounded w-full py-2 px-3" required>
        </div>

        <div class="mb-4">
            <label class="block text-gray-700 font-bold mb-2" for="description">Description</label>
            <textarea id="description" name="description" rows="5" class="shadow appearance-none border rounded w-full py-2 px-3" required><?= htmlspecialchars($product['description']) ?></textarea>
        </div>
<!-- CATEGORY SELECTOR - NEW -->
        <div class="mb-4">
            <label class="block text-gray-700 font-bold mb-2" for="category_id">Category</label>
            <select id="category_id" name="category_id" class="shadow border rounded w-full py-2 px-3">
                <option value="">Select a category</option>
                <?php while($category = $categories_result->fetch_assoc()): ?>
                    <option value="<?= $category['id'] ?>" <?= (isset($product['category_id']) && $product['category_id'] == $category['id']) ? 'selected' : '' ?>>
                        <?= htmlspecialchars($category['name']) ?>
                    </option>
                <?php endwhile; ?>
            </select>
        </div>
        <div class="flex flex-wrap -mx-3 mb-4">
            <div class="w-full md:w-1/2 px-3 mb-4 md:mb-0">
                <label class="block text-gray-700 font-bold mb-2" for="price">Price</label>
                <input type="number" id="price" name="price" step="0.01" value="<?= htmlspecialchars($product['price']) ?>" class="shadow appearance-none border rounded w-full py-2 px-3" required>
            </div>
            <div class="w-full md:w-1/2 px-3">
                <label class="block text-gray-700 font-bold mb-2" for="stock">Stock Quantity</label>
                <input type="number" id="stock" name="stock_quantity" value="<?= htmlspecialchars($product['stock_quantity']) ?>" class="shadow appearance-none border rounded w-full py-2 px-3" required>
            </div>
        </div>

        <div class="mb-6">
            <label class="block text-gray-700 font-bold mb-2" for="image">Product Image</label>
            <input type="file" id="image" name="image" class="shadow appearance-none border rounded w-full py-2 px-3">
            <?php if ($is_edit && $product['image_url']): ?>
                <p class="text-sm text-gray-600 mt-2">Current image: <a href="../<?= htmlspecialchars($product['image_url']) ?>" target="_blank" class="text-blue-500"><?= htmlspecialchars($product['image_url']) ?></a></p>
                <p class="text-sm text-gray-600">Leave blank to keep the current image.</p>
            <?php endif; ?>
        </div>

        <div class="flex justify-end">
            <a href="products.php" class="bg-gray-500 hover:bg-gray-600 text-white font-bold py-2 px-4 rounded mr-2">Cancel</a>
            <button type="submit" class="bg-blue-500 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded">
                Save Product
            </button>
        </div>
    </form>
</div>

<?php
$conn->close();
require_once 'includes/admin_footer.php';
?>