<?php
require_once 'includes/admin_header.php';
require_once '../includes/db_connect.php';

// CORRECTED QUERY: Use LEFT JOIN to fetch the category name.
// 'p' is an alias for the products table, 'c' is for the categories table.
// A LEFT JOIN ensures that products without a category will still be listed.
$sql = "SELECT p.*, c.name AS category_name 
        FROM products p
        LEFT JOIN categories c ON p.category_id = c.id
        ORDER BY p.created_at DESC";

$result = $conn->query($sql);
?>

<div class="flex justify-between items-center mb-8">
    <h1 class="text-3xl font-bold text-gray-800">Manage Products</h1>
    <a href="product_form.php" class="bg-blue-500 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded">
        <i class="fas fa-plus mr-2"></i> Add New Product
    </a>
</div>

<div class="bg-white shadow-md rounded-lg overflow-x-auto">
    <div class="bg-white shadow-sm border border-gray-200 rounded-2xl overflow-hidden">
    <div class="overflow-x-auto">
        <table class="min-w-[800px] w-full text-sm text-left">
            <thead class="bg-gray-50 border-b border-gray-100">
                <tr>
                    <th class="py-4 px-6 text-[10px] font-black uppercase text-gray-400 tracking-widest">Product</th>
                    <th class="py-4 px-6 text-[10px] font-black uppercase text-gray-400 tracking-widest">Category</th>
                    <th class="py-4 px-6 text-[10px] font-black uppercase text-gray-400 tracking-widest text-center">Stock</th>
                    <th class="py-4 px-6 text-[10px] font-black uppercase text-gray-400 tracking-widest text-center">Price</th>
                    <th class="py-4 px-6 text-[10px] font-black uppercase text-gray-400 tracking-widest text-center">Actions</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-50">
                <?php while ($product = $result->fetch_assoc()): ?>
                <tr class="hover:bg-gray-50/50 transition-colors">
                    <td class="py-4 px-6 whitespace-nowrap">
                        <div class="flex items-center gap-4">
                            <img src="../<?= htmlspecialchars($product['image_url']) ?>" class="h-12 w-12 object-cover rounded-xl shadow-sm border border-gray-100">
                            <span class="font-bold text-gray-900"><?= htmlspecialchars($product['name']) ?></span>
                        </div>
                    </td>
                    <td class="py-4 px-6 whitespace-nowrap font-medium text-gray-500">
                        <?= htmlspecialchars($product['category_name'] ?? 'Uncategorized') ?>
                    </td>
                    <td class="py-4 px-6 text-center whitespace-nowrap">
                        <?php 
                            $qty = $product['stock_quantity'];
                            $q_class = $qty < 5 ? "bg-red-50 text-red-600 border-red-100" : "bg-green-50 text-green-600 border-green-100";
                        ?>
                        <span class="px-3 py-1 rounded-lg border font-black <?= $q_class ?>">
                            <?= $qty ?>
                        </span>
                    </td>
                    <td class="py-4 px-6 text-center whitespace-nowrap font-black text-gray-900">
                        Rs. <?= number_format($product['price'], 2) ?>
                    </td>
                    <td class="py-4 px-6 text-center whitespace-nowrap space-x-4">
                        <a href="product_form.php?id=<?= $product['id'] ?>" class="text-indigo-600 hover:text-indigo-900"><i class="fas fa-pencil-alt"></i></a>
                        <a href="actions/handle_products.php?action=delete&id=<?= $product['id'] ?>" class="text-red-400 hover:text-red-600" onclick="return confirm('Delete this product?');"><i class="fas fa-trash"></i></a>
                    </td>
                </tr>
                <?php endwhile; ?>
            </tbody>
        </table>
    </div>
</div>
</div>

<?php
$conn->close();
require_once 'includes/admin_footer.php';
?>