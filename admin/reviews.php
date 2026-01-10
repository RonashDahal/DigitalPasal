<?php
require_once 'includes/admin_header.php';
require_once '../includes/db_connect.php';

$sql = "SELECT r.id, r.rating, r.review_text, r.created_at, u.name as user_name, p.name as product_name, p.id as product_id
        FROM reviews r
        JOIN users u ON r.user_id = u.id
        JOIN products p ON r.product_id = p.id
        ORDER BY r.created_at DESC";
$result = $conn->query($sql);
?>
<h1 class="text-3xl font-bold text-gray-800 mb-8">Manage Reviews</h1>

<div class="bg-white shadow-md rounded-lg overflow-x-auto">
    <table class="min-w-full">
        <thead class="bg-gray-200 text-sm">
            <tr>
                <th class="py-3 px-4 text-left">Product</th>
                <th class="py-3 px-4 text-left">Customer</th>
                <th class="py-3 px-4 text-center">Rating</th>
                <th class="py-3 px-4 text-left">Review</th>
                <th class="py-3 px-4 text-left">Date</th>
                <th class="py-3 px-4 text-center">Action</th>
            </tr>
        </thead>
        <tbody class="divide-y text-sm">
            <?php while($review = $result->fetch_assoc()): ?>
            <tr>
                <td class="py-3 px-4 font-semibold"><a href="../product_detail.php?id=<?= $review['product_id'] ?>" target="_blank" class="hover:underline text-indigo-600"><?= htmlspecialchars($review['product_name']) ?></a></td>
                <td class="py-3 px-4"><?= htmlspecialchars($review['user_name']) ?></td>
                <td class="py-3 px-4 text-center text-amber-500">
                    <?php for($i=0; $i < $review['rating']; $i++) echo '<i class="fas fa-star"></i>'; ?>
                </td>
                <td class="py-3 px-4 text-gray-600 max-w-sm truncate"><?= htmlspecialchars($review['review_text']) ?></td>
                <td class="py-3 px-4"><?= date_format(date_create($review['created_at']), 'M j, Y') ?></td>
                <td class="py-3 px-4 text-center">
                    <a href="actions/handle_admin_reviews.php?action=delete&id=<?= $review['id'] ?>" class="text-red-500 hover:text-red-700" onclick="return confirm('Are you sure? This will also update the product\'s average rating.');">
                        <i class="fas fa-trash"></i> Delete
                    </a>
                </td>
            </tr>
            <?php endwhile; ?>
        </tbody>
    </table>
</div>
<?php $conn->close(); require_once 'includes/admin_footer.php'; ?>