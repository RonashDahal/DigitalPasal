<?php
if (session_status() === PHP_SESSION_NONE) { session_start(); }
if (!isset($_SESSION["loggedin"])) { header("location: login.php"); exit; }

require_once 'includes/header.php';
require_once 'includes/db_connect.php';

$order_item_id = isset($_GET['order_item_id']) ? (int)$_GET['order_item_id'] : 0;
$user_id = $_SESSION['id'];

// Security Check: Verify this order item belongs to the user, is part of a completed order, and has not been reviewed yet.
$stmt = $conn->prepare(
    "SELECT oi.id, p.id as product_id, p.name, p.image_url
     FROM order_items oi
     JOIN orders o ON oi.order_id = o.id
     JOIN products p ON oi.product_id = p.id
     LEFT JOIN reviews r ON oi.id = r.order_item_id
     WHERE oi.id = ? AND o.user_id = ? AND o.status = 'Completed' AND r.id IS NULL"
);
$stmt->bind_param("ii", $order_item_id, $user_id);
$stmt->execute();
$item_to_review = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$item_to_review) {
    echo "<div class='text-center p-8'><p class='text-red-500'>You are not eligible to review this item, or it has already been reviewed.</p><a href='dashboard.php?view=orders' class='text-indigo-600 hover:underline mt-4 inline-block'>← Back to Order History</a></div>";
    require_once 'includes/footer.php';
    exit();
}
?>
<!-- CSS for the interactive star rating -->
<style>
.rating { display: inline-block; }
.rating input { display: none; }
.rating label {
    float: right;
    cursor: pointer;
    color: #ddd;
    transition: color 0.2s;
}
.rating label:before {
    content: '\f005'; /* Font Awesome star */
    font-family: 'Font Awesome 6 Free';
    font-weight: 900;
    font-size: 2.5rem;
}
.rating input:checked ~ label,
.rating label:hover,
.rating label:hover ~ label {
    color: #fbbf24; /* amber-400 */
}
</style>

<div class="bg-white p-8 rounded-lg shadow-xl max-w-2xl mx-auto">
    <h1 class="text-3xl font-bold text-gray-800 mb-6">Write a Review</h1>
    
    <div class="flex items-center space-x-4 border-b pb-6 mb-6">
        <img src="<?= htmlspecialchars($item_to_review['image_url']) ?>" class="w-20 h-20 rounded-lg object-cover shadow">
        <div>
            <p class="text-gray-600">You are reviewing:</p>
            <h2 class="text-xl font-semibold text-gray-800"><?= htmlspecialchars($item_to_review['name']) ?></h2>
        </div>
    </div>

    <form action="actions/handle_review.php" method="POST">
        <input type="hidden" name="product_id" value="<?= $item_to_review['product_id'] ?>">
        <input type="hidden" name="order_item_id" value="<?= $item_to_review['id'] ?>">

        <div class="mb-6 text-center">
            <label class="block text-gray-700 font-bold mb-4">Your Rating</label>
            <div class="rating">
                <input type="radio" id="star5" name="rating" value="5" required><label for="star5" title="5 stars"></label>
                <input type="radio" id="star4" name="rating" value="4"><label for="star4" title="4 stars"></label>
                <input type="radio" id="star3" name="rating" value="3"><label for="star3" title="3 stars"></label>
                <input type="radio" id="star2" name="rating" value="2"><label for="star2" title="2 stars"></label>
                <input type="radio" id="star1" name="rating" value="1"><label for="star1" title="1 star"></label>
            </div>
        </div>

        <div class="mb-6">
            <label for="review_text" class="block text-gray-700 font-bold mb-2">Your Review (Optional)</label>
            <textarea name="review_text" id="review_text" rows="5" placeholder="Tell us what you thought..." class="w-full p-3 border rounded-lg focus:outline-none focus:ring-2 focus:ring-indigo-500"></textarea>
        </div>

        <button type="submit" class="w-full bg-indigo-600 text-white py-3 rounded-lg font-semibold hover:bg-indigo-700 transition-colors">Submit Review</button>
    </form>
</div>

<?php 
require_once 'includes/footer.php'; 
$conn->close();
?>