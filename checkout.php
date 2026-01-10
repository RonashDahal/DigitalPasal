<?php
/**
 * checkout.php - Final Clean Professional Version
 * Includes: Compulsory GPS, Payment Restrictions, Dynamic Shipping, and Zoomable eSewa QR
 */

require_once 'includes/init.php'; 
require_once 'includes/db_connect.php';

// 1. Security Check
if (!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true) {
    header("location: login.php?redirect=checkout");
    exit;
}
if (empty($_SESSION['cart'])) {
    header("location: cart.php");
    exit;
}

// 2. Fetch Data
$cart_items = $_SESSION['cart'];
$product_ids = implode(',', array_keys($cart_items));
$products_in_cart = [];
$subtotal = 0;

$sql = "SELECT id, name, price, image_url, category_id FROM products WHERE id IN ($product_ids)";
$result = $conn->query($sql);
while ($row = $result->fetch_assoc()) {
    $products_in_cart[$row['id']] = $row;
    $subtotal += $row['price'] * $cart_items[$row['id']];
}

// 3. Payment Restriction Logic
$can_use_cod = true;
$can_use_esewa = true;
$pay_sql = "SELECT allow_cod, allow_esewa FROM categories WHERE id IN (SELECT category_id FROM products WHERE id IN ($product_ids))";
$pay_res = $conn->query($pay_sql);
while($rule = $pay_res->fetch_assoc()) {
    if($rule['allow_cod'] == 0) $can_use_cod = false;
    if($rule['allow_esewa'] == 0) $can_use_esewa = false;
}

// 4. Fetch Config (eSewa ID, QR, Shipping)
$settings_res = $conn->query("SELECT setting_key, setting_value FROM site_settings WHERE setting_key IN ('shipping_charge', 'esewa_id', 'esewa_qr')");
$config = [];
while($r = $settings_res->fetch_assoc()){ $config[$r['setting_key']] = $r['setting_value']; }

$shipping_charge = (float)($config['shipping_charge'] ?? 0);
$discount_amount = min($subtotal, (float)($_SESSION['discount']['amount'] ?? 0));
$grand_total = ($subtotal - $discount_amount) + $shipping_charge;

require_once 'includes/header.php';
?>

<style>
    /* Prevent shrinking and allow proper side-by-side layout */
    .checkout-container { display: flex; flex-direction: column; gap: 2rem; align-items: flex-start; }
    @media (min-width: 1024px) {
        .checkout-container { flex-direction: row; }
        .checkout-main { flex: 1; min-width: 0; }
        .checkout-sidebar { width: 400px; flex-shrink: 0; position: sticky; top: 100px; }
    }
    .payment-radio:checked + .payment-card {
        border-color: #4f46e5;
        background-color: #f5f7ff;
        box-shadow: 0 0 0 1px #4f46e5;
    }
    #place-order-btn:disabled { opacity: 0.5; cursor: not-allowed; background-color: #9ca3af !important; }
</style>

<div class="max-w-7xl mx-auto px-4 py-8">
    <h1 class="text-3xl font-black text-gray-900 mb-10 tracking-tight uppercase italic">Secure Checkout</h1>

    <form id="checkout-form" action="actions/place_order.php" method="post" class="checkout-container">
        
        <!-- LEFT: FORM AND PAYMENT -->
        <div class="checkout-main space-y-6 w-full">
            
            <!-- Shipping Section -->
            <div class="bg-white rounded-3xl shadow-sm border border-gray-100 p-6 md:p-10">
                <h2 class="text-xl font-bold mb-8 flex items-center text-gray-800">
                    <i class="fas fa-map-marker-alt text-indigo-600 mr-3"></i> Delivery Information
                </h2>
                
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
                    <div>
                        <label class="block text-[10px] font-black uppercase text-gray-400 mb-2 ml-1">Recipient Name</label>
                        <input type="text" name="shipping_name" class="w-full bg-gray-50 border-2 border-gray-50 rounded-2xl py-4 px-6 font-bold outline-none focus:border-indigo-500 transition" value="<?= htmlspecialchars($_SESSION['name']) ?>" required>
                    </div>
                    <div>
                        <label class="block text-[10px] font-black uppercase text-gray-400 mb-2 ml-1">Contact Number</label>
                        <input type="text" name="phone" placeholder="98XXXXXXXX" class="w-full bg-gray-50 border-2 border-gray-50 rounded-2xl py-4 px-6 font-bold outline-none focus:border-indigo-500 transition" required>
                    </div>
                </div>

                <div class="mb-8">
                    <label class="block text-[10px] font-black uppercase text-gray-400 mb-2 ml-1">Exact Spot / Location Description</label>
                    <textarea name="shipping_address" rows="3" class="w-full bg-gray-50 border-2 border-gray-50 rounded-2xl py-4 px-6 font-bold outline-none focus:border-indigo-500 transition" required placeholder="e.g. Near Stage, 3rd row, wearing a red shirt..."></textarea>
                </div>

                <!-- GPS BLOCK -->
                <div class="bg-gray-900 rounded-3xl p-6 text-white shadow-2xl">
                    <div class="flex flex-col md:flex-row items-center gap-6">
                        <div class="flex-grow text-center md:text-left">
                            <h3 class="font-black text-xs uppercase tracking-widest text-indigo-400 mb-1">Mandatory Location Access</h3>
                            <p class="text-xs text-gray-400 leading-relaxed">We need your GPS to deliver precisely to your spot in the crowd.</p>
                        </div>
                        <button type="button" onclick="getLocation()" class="bg-indigo-600 hover:bg-indigo-500 px-8 py-4 rounded-2xl font-black text-xs uppercase tracking-widest transition-all active:scale-95 whitespace-nowrap">
                            <i class="fas fa-crosshairs mr-2"></i> Share Location
                        </button>
                    </div>
                    <div class="mt-4 flex gap-2">
                        <input type="text" id="latitude" name="latitude" placeholder="Latitude" readonly class="w-1/2 bg-white/5 border-0 rounded-xl p-2 text-[10px] font-mono text-gray-500 text-center">
                        <input type="text" id="longitude" name="longitude" placeholder="Longitude" readonly class="w-1/2 bg-white/5 border-0 rounded-xl p-2 text-[10px] font-mono text-gray-500 text-center">
                    </div>
                    <p id="location-msg" class="text-[10px] mt-4 text-center text-yellow-400 font-bold uppercase tracking-tighter">* Required to unlock order button</p>
                </div>
            </div>

            <!-- Payment Section -->
            <div class="bg-white rounded-3xl shadow-sm border border-gray-100 p-6 md:p-10">
                <h2 class="text-xl font-bold mb-8 text-gray-800 uppercase italic">Payment Method</h2>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <?php if($can_use_cod): ?>
                    <div class="relative">
                        <input type="radio" name="payment_method" id="pay_cod" value="cod" class="payment-radio hidden" checked>
                        <label for="pay_cod" class="payment-card flex items-center p-6 border-2 border-gray-50 rounded-3xl cursor-pointer transition-all hover:bg-gray-50 h-full">
                            <i class="fas fa-hand-holding-heart text-2xl text-gray-400 mr-4"></i>
                            <div><p class="font-black text-sm uppercase">Cash</p><p class="text-[9px] text-gray-400 uppercase font-black">Pay at ground</p></div>
                        </label>
                    </div>
                    <?php endif; ?>

                    <?php if($can_use_esewa): ?>
                    <div class="relative">
                        <input type="radio" name="payment_method" id="pay_esewa" value="card" class="payment-radio hidden" <?= (!$can_use_cod)?'checked':'' ?>>
                        <label for="pay_esewa" class="payment-card flex items-center p-6 border-2 border-gray-50 rounded-3xl cursor-pointer transition-all hover:bg-gray-50 h-full">
                            <img src="uploads/esewa.png" class="w-8 h-8 object-contain mr-4">
                            <div><p class="font-black text-sm uppercase">eSewa</p><p class="text-[9px] text-gray-400 uppercase font-black">Manual Verify</p></div>
                        </label>
                    </div>
                    <?php endif; ?>
                </div>

                <!-- eSewa Dynamic Panel -->
                <div id="esewa-panel" class="hidden mt-8">
                    <div class="bg-green-50 border-2 border-green-100 rounded-[2rem] p-6 md:p-8">
                        <div class="flex flex-col sm:flex-row items-center gap-8 mb-8">
                            <div onclick="toggleQRZoom()" class="w-40 h-40 bg-white p-3 rounded-3xl border-2 border-green-200 shadow-xl cursor-zoom-in active:scale-95 transition-transform flex-shrink-0">
                                <img src="<?= htmlspecialchars($config['esewa_qr'] ?? 'uploads/default_qr.png') ?>" class="w-full h-full object-contain">
                                <p class="text-[8px] font-black text-center text-green-500 mb-2 uppercase">Tap to Zoom</p>
                            </div>
                            <div class="text-center sm:text-left space-y-3">
                                <h3 class="font-black text-green-800 text-xs uppercase tracking-widest">Merchant Account</h3>
                                <p class="text-2xl font-black text-green-900"><?= htmlspecialchars($config['esewa_id'] ?? '98XXXXXXXX') ?></p>
                                <p class="text-xs text-green-700 font-bold leading-tight italic">Scan or transfer exactly Rs. <?= number_format($grand_total, 2) ?> and enter the code below.</p>
                            </div>
                        </div>
                        <label class="block text-green-900 font-black text-[10px] uppercase mb-2 ml-2">Enter Transaction ID / Code</label>
                        <input type="text" id="transaction_code" name="transaction_code" placeholder="Example: ABC123XYZ" class="w-full border-2 border-green-200 rounded-2xl py-4 px-6 focus:border-green-600 outline-none font-mono font-bold text-green-900 shadow-inner">
                    </div>
                </div>
            </div>
        </div>

        <!-- RIGHT: SUMMARY (Fixed Width Sidebar) -->
        <div class="checkout-sidebar w-full">
            <div class="bg-white shadow-2xl border border-gray-100 rounded-[2.5rem] p-8 md:p-10">
                <h2 class="text-2xl font-black text-gray-900 mb-8 border-b border-gray-50 pb-4">My Items</h2>
                
                <div class="space-y-6 mb-8 max-h-80 overflow-y-auto pr-2 custom-scrollbar">
                    <?php foreach($products_in_cart as $product): $qty = $cart_items[$product['id']]; ?>
                    <div class="flex justify-between items-center">
                        <div class="flex items-center gap-4">
                            <div class="relative flex-shrink-0">
                                <img src="<?= htmlspecialchars($product['image_url']) ?>" class="w-14 h-14 object-cover rounded-2xl shadow-sm border border-gray-100">
                                <span class="absolute -top-2 -right-2 bg-indigo-600 text-white text-[10px] w-6 h-6 rounded-full flex items-center justify-center font-black border-2 border-white"><?= $qty ?></span>
                            </div>
                            <div class="min-w-0">
                                <p class="text-sm font-bold text-gray-800 truncate w-32"><?= htmlspecialchars($product['name']) ?></p>
                                <p class="text-[10px] text-gray-400 font-black uppercase tracking-tighter">Rs. <?= number_format($product['price'], 2) ?></p>
                            </div>
                        </div>
                        <p class="font-black text-gray-900 text-xs whitespace-nowrap">Rs. <?= number_format($product['price'] * $qty, 2) ?></p>
                    </div>
                    <?php endforeach; ?>
                </div>

                <div class="bg-gray-50 rounded-3xl p-6 space-y-4">
                    <div class="flex justify-between text-xs font-bold text-gray-400 uppercase tracking-widest">
                        <span>Items Subtotal</span>
                        <span class="text-gray-700">Rs. <?= number_format($subtotal, 2) ?></span>
                    </div>

                    <?php if ($discount_amount > 0): ?>
                    <div class="flex justify-between text-xs font-black text-green-600 uppercase tracking-widest">
                        <span>Voucher Appied</span>
                        <span>-Rs. <?= number_format($discount_amount, 2) ?></span>
                    </div>
                    <?php endif; ?>

                    <div class="flex justify-between items-center text-xs font-black text-indigo-500 uppercase tracking-widest">
                        <span>Delivery Fee</span>
                        <span class="bg-indigo-100 px-3 py-1 rounded-full text-indigo-700">Rs. <?= number_format($shipping_charge, 0) ?></span>
                    </div>

                    <div class="border-t border-gray-200 pt-4 flex justify-between items-end">
                        <span class="text-xs font-black text-gray-900 uppercase tracking-[0.2em]">Total Amount</span>
                        <span class="text-2xl font-black text-indigo-600 italic">Rs. <?= number_format($grand_total, 2) ?></span>
                    </div>
                </div>

                <button type="submit" id="place-order-btn" disabled 
                        class="w-full mt-8 bg-indigo-600 text-white font-black py-5 rounded-[1.5rem] text-xs uppercase tracking-[0.3em] shadow-xl shadow-indigo-100 hover:shadow-indigo-200 active:scale-95 transition-all">
                    SHARE LOCATION TO UNLOCK
                </button>
                
                <p class="text-[9px] text-center text-gray-400 mt-6 font-bold uppercase leading-tight px-2">
                    Student-led delivery using GPS for Annual Function Ground Spotting.
                </p>
            </div>
        </div>
    </form>
</div>

<!-- QR ZOOM OVERLAY -->
<div id="qr-overlay" onclick="toggleQRZoom()" class="fixed inset-0 bg-black/95 z-[100] hidden flex-col items-center justify-center p-6 transition-all duration-300">
    <div class="bg-white p-6 rounded-[2.5rem] shadow-2xl max-w-sm w-full transform transition-all scale-100">
        <img src="<?= htmlspecialchars($config['esewa_qr'] ?? 'uploads/default_qr.png') ?>" class="w-full h-auto object-contain rounded-2xl">
        <h4 class="text-center mt-6 font-black text-gray-900 text-sm uppercase tracking-widest italic">Pay Rs. <?= number_format($grand_total, 2) ?></h4>
    </div>
    <button type="button" class="mt-10 text-white font-black uppercase text-[10px] tracking-[0.3em] bg-white/10 px-8 py-3 rounded-full border border-white/20">Close Preview</button>
</div>

<script>
    // 1. Payment Toggle
    const esewaRadio = document.getElementById('pay_esewa');
    const codRadio = document.getElementById('pay_cod');
    const esewaPanel = document.getElementById('esewa-panel');
    const transInput = document.getElementById('transaction_code');

    function updatePaymentPanel() {
        if (esewaRadio && esewaRadio.checked) {
            esewaPanel.classList.remove('hidden');
            transInput.required = true;
        } else {
            esewaPanel.classList.add('hidden');
            if(transInput) transInput.required = false;
        }
    }
    [esewaRadio, codRadio].forEach(r => r && r.addEventListener('change', updatePaymentPanel));
    updatePaymentPanel();

    // 2. Compulsory GPS
    function getLocation() {
        const msg = document.getElementById('location-msg');
        const btn = document.getElementById('place-order-btn');
        if (navigator.geolocation) {
            msg.innerHTML = "📡 Connecting to satellite...";
            navigator.geolocation.getCurrentPosition((pos) => {
                document.getElementById('latitude').value = pos.coords.latitude;
                document.getElementById('longitude').value = pos.coords.longitude;
                msg.innerHTML = "✅ GPS VERIFIED - BUTTON UNLOCKED";
                msg.className = "text-[10px] mt-4 text-center text-green-500 font-black tracking-widest";
                btn.disabled = false;
                btn.innerHTML = "CONFIRM MY ORDER";
            }, (err) => {
                msg.innerHTML = "❌ Error: " + err.message + ". Please enable GPS.";
                msg.classList.add('text-red-500');
            }, { enableHighAccuracy: true });
        }
    }

    // 3. QR Zoom
    function toggleQRZoom() {
        const ov = document.getElementById('qr-overlay');
        const isHidden = ov.classList.contains('hidden');
        ov.classList.toggle('hidden');
        ov.classList.toggle('flex');
        document.body.style.overflow = isHidden ? 'hidden' : 'auto';
    }
</script>

<?php 
require_once 'includes/footer.php'; 
if(isset($conn)) { $conn->close(); }
?>