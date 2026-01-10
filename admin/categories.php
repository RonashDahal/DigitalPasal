<?php
require_once 'includes/admin_header.php';
require_once '../includes/db_connect.php';

// Upgraded query to also count the number of products in each category.
$sql = "SELECT c.*, COUNT(p.id) as product_count
        FROM categories c
        LEFT JOIN products p ON c.id = p.category_id
        GROUP BY c.id
        ORDER BY c.name ASC";
$result = $conn->query($sql);
?>

<div class="flex justify-between items-center mb-8">
    <h1 class="text-3xl font-bold text-gray-800">Manage Categories</h1>
    <a href="category_form.php" class="bg-blue-500 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded-lg shadow-md hover:shadow-lg transition-shadow">
        <i class="fas fa-plus mr-2"></i> Add New Category
    </a>
</div>

<!-- Category Grid -->
<div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-8">
    <?php while ($category = $result->fetch_assoc()): ?>
    <div class="bg-white rounded-lg shadow-lg overflow-hidden transition-transform transform hover:-translate-y-1 hover:shadow-xl">
        <a href="category_form.php?id=<?= $category['id'] ?>">
            <img src="../<?= htmlspecialchars($category['image_url'] ?? 'https://via.placeholder.com/400x300?text=No+Image') ?>" 
                 alt="<?= htmlspecialchars($category['name']) ?>" 
                 class="w-full h-48 object-cover">
        </a>
        <div class="p-4">
            <h3 class="text-xl font-bold text-gray-800"><?= htmlspecialchars($category['name']) ?></h3>
            <p class="text-sm text-gray-500 mt-1"><?= $category['product_count'] ?> Products</p>
            
            <div class="flex items-center justify-between mt-4">
                <div>
                    <?php if ($category['is_active']): ?>
                        <span class="px-3 py-1 text-xs font-semibold rounded-full bg-green-100 text-green-800">Active</span>
                    <?php else: ?>
                        <span class="px-3 py-1 text-xs font-semibold rounded-full bg-red-100 text-red-800">Inactive</span>
                    <?php endif; ?>
                </div>
                <div class="space-x-3">
                    <a href="category_form.php?id=<?= $category['id'] ?>" class="text-gray-500 hover:text-indigo-600" title="Edit">
                        <i class="fas fa-pencil-alt"></i>
                    </a>
                    <a href="actions/handle_categories.php?action=delete&id=<?= $category['id'] ?>" class="text-gray-500 hover:text-red-600" title="Delete" onclick="return confirm('Are you sure you want to delete this category?');">
                        <i class="fas fa-trash"></i>
                    </a>
                </div>
            </div>
        </div>
    </div>
    <?php endwhile; ?>
</div>

<?php
$conn->close();
require_once 'includes/admin_footer.php';
?>