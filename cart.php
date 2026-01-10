<?php
// 1. Initialization
require_once 'includes/init.php'; 
require_once 'includes/db_connect.php';

// 2. Initial Data Setup
$cart_items = $_SESSION['cart'] ?? [];
$products = [];
$subtotal = 0;

// 3. Calculate Subtotal from Database
if (!empty($cart_items)) {
    $product_ids = implode(',', array_keys($cart_items));
    $sql = "SELECT * FROM products WHERE id IN ($product_ids)";
    $result = $conn->query($sql);
    
    while ($row = $result->fetch_assoc()) {
        $products[$row['id']] = $row;
    }
    foreach ($products as $product_id => $product) {
        $quantity = $cart_items[$product_id];
        $subtotal += $product['price'] * $quantity;
    }
}

// 4. Handle Discounts
// Unset discount if cart is empty
if (empty($cart_items)) {
    unset($_SESSION['discount']);
}

$discount_code = $_SESSION['discount']['code'] ?? '';
$discount_amount = $_SESSION['discount']['amount'] ?? 0;

// Security: Ensure discount isn't greater than the subtotal
$discount_amount = min($subtotal, $discount_amount);

// 5. Fetch Shipping Charge from Settings
$res_ship = $conn->query("SELECT setting_value FROM site_settings WHERE setting_key = 'shipping_charge'");
$shipping_charge = (float)($res_ship->fetch_assoc()['setting_value'] ?? 0);

// 6. Final Grand Total Calculation (Now all variables exist!)
$grand_total = ($subtotal - $discount_amount) + $shipping_charge;

require_once 'includes/header.php';
?>

<h1 class="text-3xl font-bold text-gray-800 mb-8">Your Shopping Cart</h1>

<?php if (empty($cart_items)): ?>
    <div class="text-center bg-white p-12 rounded-lg shadow-md">
        <i class="fas fa-shopping-cart fa-4x text-gray-300 mb-4"></i>
        <h2 class="text-2xl font-semibold text-gray-700 mb-2">Your cart is empty</h2>
        <p class="text-gray-500 mb-6">Looks like you haven't added anything to your cart yet.</p>
        <a href="products.php" class="bg-indigo-600 text-white px-8 py-3 rounded-lg font-semibold hover:bg-indigo-700 transition-colors">
            Continue Shopping
        </a>
    </div>
<?php else: ?>
    <div class="flex flex-col lg:flex-row gap-12">
        <!-- Left Column: Cart Items -->
        <div class="w-full lg:w-2/3 bg-white p-6 rounded-lg shadow-md">
            <h2 class="text-xl font-bold mb-4 border-b pb-3">Items (<?= get_cart_count() ?>)</h2>
            <div class="space-y-6">
                <?php foreach ($products as $product): 
                    $quantity = $cart_items[$product['id']];
                ?>
                <div class="flex items-center gap-4">
                    <img src="<?= htmlspecialchars($product['image_url']) ?>" alt="<?= htmlspecialchars($product['name']) ?>" class="w-24 h-24 object-cover rounded-lg shadow">
                    <div class="flex-grow">
                        <a href="product_detail.php?id=<?= $product['id'] ?>" class="font-bold text-gray-800 hover:text-indigo-600"><?= htmlspecialchars($product['name']) ?></a>
                        <p class="text-sm text-gray-500">Unit Price: Rs. <?= number_format($product['price'], 2) ?></p>
                        <div class="flex items-center mt-2">
                            <label for="qty-<?= $product['id'] ?>" class="text-sm mr-2">Qty:</label>
                            <input type="number" id="qty-<?= $product['id'] ?>" value="<?= $quantity ?>" min="1" max="<?= $product['stock_quantity'] ?>" 
                                   class="w-20 p-1 border rounded text-center cart-quantity-input" 
                                   data-id="<?= $product['id'] ?>">
                        </div>
                    </div>
                    <div class="text-right">
                        <p class="font-bold text-lg text-gray-800">Rs. <?= number_format($product['price'] * $quantity, 2) ?></p>
                        <a href="#" class="text-red-500 hover:text-red-700 text-sm remove-from-cart mt-2" data-id="<?= $product['id'] ?>">
                            <i class="fas fa-trash-alt mr-1"></i>Remove
                        </a>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>

        <!-- Right Column: Order Summary -->
        <div class="w-full lg:w-1/3">
            <div class="bg-white p-6 rounded-lg shadow-md sticky top-24">
                <h2 class="text-xl font-bold mb-4 border-b pb-3">Summary</h2>
                <div class="space-y-2 mb-4">
                    <div class="flex justify-between text-gray-600">
                        <span>Subtotal</span>
                        <span id="cart-subtotal">Rs. <?= number_format($subtotal, 2) ?></span>
                    </div>
                    <div id="discount-row" class="<?= $discount_amount > 0 ? 'flex' : 'hidden' ?> justify-between text-green-600 font-semibold">
                        <span>Discount (<span id="discount-code"><?= htmlspecialchars($discount_code) ?></span>)</span>
                        <span id="discount-amount">-Rs. <?= number_format($discount_amount, 2) ?></span>
                    </div>
                    <div class="flex justify-between text-gray-600">
                        <span>Shipping Fee</span>
                        <span>Rs. <?= number_format($shipping_charge, 2) ?></span>
                    </div>
                </div>
                <div class="flex justify-between font-bold text-xl border-t pt-3 mb-4">
                    <span>Total</span>
                    <span id="cart-total">Rs. <?= number_format($grand_total, 2) ?></span>
                </div>

                <form id="promo-code-form" class="flex gap-2 mb-4">
                    <input type="text" id="promo-code-input" placeholder="Enter promo code" class="w-full p-2 border rounded-lg focus:outline-none focus:ring-1 focus:ring-indigo-500" required>
                    <button type="submit" class="bg-gray-800 text-white px-4 py-2 rounded-lg hover:bg-black font-semibold">Apply</button>
                </form>
                <div id="promo-message" class="text-sm text-center h-5"></div>
                
                <a href="checkout.php" class="block w-full text-center mt-4 bg-indigo-600 text-white py-3 rounded-lg hover:bg-indigo-700 transition font-semibold text-lg">
                    Proceed to Checkout
                </a>
            </div>
        </div>
    </div>
<?php endif; ?>
<div class="pb-24 lg:hidden"></div>

</div>

<?php
require_once 'includes/footer.php';
$conn->close();
?>

<!-- START: PAGE-SPECIFIC JAVASCRIPT -->
<script>
document.addEventListener('DOMContentLoaded', function() {
    
    async function updateCartAndReload(productId, quantity) {
        document.body.style.opacity = '0.5';
        try {
            const formData = new FormData();
            formData.append('product_id', productId);
            formData.append('quantity', quantity);
            formData.append('action', 'update');
            const response = await fetch('actions/handle_cart.php', { method: 'POST', body: formData });
            const result = await response.json();
            if (result.success) {
                window.location.reload(); 
            } else {
                alert('Error updating cart: ' + (result.message || 'Unknown error'));
                document.body.style.opacity = '1';
            }
        } catch (error) {
            console.error('Failed to update cart:', error);
            document.body.style.opacity = '1';
        }
    }

    const quantityInputs = document.querySelectorAll('.cart-quantity-input');
    quantityInputs.forEach(input => {
        input.addEventListener('change', function() {
            updateCartAndReload(this.dataset.id, this.value);
        });
    });

    const removeButtons = document.querySelectorAll('.remove-from-cart');
    removeButtons.forEach(button => {
        button.addEventListener('click', function(e) {
            e.preventDefault();
            updateCartAndReload(this.dataset.id, 0); 
        });
    });

    const promoForm = document.getElementById('promo-code-form');
    if (promoForm) {
        promoForm.addEventListener('submit', async function(e) {
            e.preventDefault();
            const promoInput = document.getElementById('promo-code-input');
            const promoMessage = document.getElementById('promo-message');
            const button = this.querySelector('button');
            button.disabled = true;
            button.textContent = '...';

            try {
                const response = await fetch('actions/apply_promo.php', {
                    method: 'POST',
                    headers: {'Content-Type': 'application/x-www-form-urlencoded'},
                    body: `promo_code=${promoInput.value}`
                });
                const data = await response.json();

                if (data.success) {
                    promoMessage.innerHTML = `<span class="text-green-600">${data.message}</span>`;
                    document.getElementById('cart-subtotal').textContent = 'Rs. ' + data.subtotal.toFixed(2);
                    document.getElementById('discount-amount').textContent = '-Rs. ' + data.discount_amount.toFixed(2);
                    document.getElementById('discount-code').textContent = data.code;
                    document.getElementById('discount-row').classList.remove('hidden');
                    document.getElementById('discount-row').classList.add('flex');
                    document.getElementById('cart-total').textContent = 'Rs. ' + data.grand_total.toFixed(2);
                } else {
                    promoMessage.innerHTML = `<span class="text-red-500">${data.message}</span>`;
                    document.getElementById('discount-row').classList.add('hidden');
                }
            } catch (error) {
                promoMessage.innerHTML = `<span class="text-red-500">An error occurred.</span>`;
            } finally {
                button.disabled = false;
                button.textContent = 'Apply';
            }
        });
    }
});
</script>