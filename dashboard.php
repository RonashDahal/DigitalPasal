<?php
// Start session and initialize global functions
require_once 'includes/init.php'; 

// Check if the user is logged in
if(!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true){
    header("location: login.php");
    exit;
}

require_once 'includes/header.php';
require_once 'includes/db_connect.php';

$view = $_GET['view'] ?? 'overview';
$user_id = $_SESSION['id'];

// Helper function to define the badge logic directly in this file
function getStatusBadge($status) {
    $class = "bg-gray-100 text-gray-600";
    switch($status) {
        case 'Payment Submitted': $class = "bg-blue-50 text-blue-600 border border-blue-100"; break;
        case 'Payment Verified':  $class = "bg-green-50 text-green-600 border border-green-100"; break;
        case 'Payment Rejected':  $class = "bg-red-50 text-red-600 border border-red-100"; break;
        case 'Processing':       $class = "bg-yellow-50 text-yellow-600 border border-yellow-100"; break;
        case 'Shipped':          $class = "bg-purple-50 text-purple-600 border border-purple-100"; break;
        case 'Completed':        $class = "bg-green-600 text-white"; break;
        case 'Cancelled':        $class = "bg-gray-500 text-white"; break;
    }
    return '<span class="px-3 py-1 rounded-full text-[10px] font-black uppercase tracking-tight ' . $class . '">' . htmlspecialchars($status) . '</span>';
}
?>

<div class="max-w-6xl mx-auto px-2 sm:px-6">
    
    <!-- MOBILE & TABLET NAVIGATION (Horizontal Scrollable Pills) -->
    <div class="md:hidden mb-6 overflow-x-auto no-scrollbar flex space-x-2 pb-2">
        <a href="dashboard.php?view=overview" class="whitespace-nowrap px-5 py-2.5 rounded-full text-sm font-bold transition-all <?= $view === 'overview' ? 'bg-indigo-600 text-white shadow-md' : 'bg-white text-gray-600 border' ?>">
            <i class="fas fa-home mr-1"></i> Overview
        </a>
        <a href="dashboard.php?view=orders" class="whitespace-nowrap px-5 py-2.5 rounded-full text-sm font-bold transition-all <?= $view === 'orders' ? 'bg-indigo-600 text-white shadow-md' : 'bg-white text-gray-600 border' ?>">
            <i class="fas fa-box mr-1"></i> Orders
        </a>
        <a href="wishlist.php" class="whitespace-nowrap px-5 py-2.5 rounded-full text-sm font-bold bg-white text-gray-600 border transition-all">
            <i class="fas fa-heart mr-1"></i> Wishlist
        </a>
        <a href="dashboard.php?view=profile" class="whitespace-nowrap px-5 py-2.5 rounded-full text-sm font-bold transition-all <?= $view === 'profile' ? 'bg-indigo-600 text-white shadow-md' : 'bg-white text-gray-600 border' ?>">
            <i class="fas fa-user-cog mr-1"></i> Profile
        </a>
    </div>

    <div class="flex flex-col md:flex-row gap-8">

        <!-- DESKTOP SIDEBAR -->
        <aside class="hidden md:block w-1/4 bg-white p-6 rounded-2xl shadow-sm border border-gray-100 self-start sticky top-24">
            <div class="mb-8 px-2 text-center">
                <div class="w-16 h-16 bg-indigo-100 text-indigo-600 rounded-full flex items-center justify-center mx-auto mb-3 text-xl font-bold">
                    <?= substr($_SESSION['name'], 0, 1) ?>
                </div>
                <h3 class="font-bold text-gray-800"><?= htmlspecialchars($_SESSION['name']) ?></h3>
                <p class="text-xs text-gray-400">Customer Account</p>
            </div>
            <ul class="space-y-1">
                <li>
                    <a href="dashboard.php?view=overview" class="flex items-center px-4 py-3 rounded-xl font-bold text-sm transition-all <?= $view === 'overview' ? 'bg-indigo-600 text-white shadow-lg' : 'text-gray-500 hover:bg-gray-50' ?>">
                        <i class="fas fa-tachometer-alt fa-fw mr-3"></i> Overview
                    </a>
                </li>
                <li>
                    <a href="dashboard.php?view=orders" class="flex items-center px-4 py-3 rounded-xl font-bold text-sm transition-all <?= $view === 'orders' ? 'bg-indigo-600 text-white shadow-lg' : 'text-gray-500 hover:bg-gray-50' ?>">
                        <i class="fas fa-box fa-fw mr-3"></i> My Orders
                    </a>
                </li>
                <li>
                    <a href="wishlist.php" class="flex items-center px-4 py-3 rounded-xl font-bold text-sm text-gray-500 hover:bg-gray-50">
                        <i class="fas fa-heart fa-fw mr-3"></i> Wishlist
                    </a>
                </li>
                <li>
                    <a href="dashboard.php?view=profile" class="flex items-center px-4 py-3 rounded-xl font-bold text-sm transition-all <?= $view === 'profile' ? 'bg-indigo-600 text-white shadow-lg' : 'text-gray-500 hover:bg-gray-50' ?>">
                        <i class="fas fa-user-cog fa-fw mr-3"></i> Settings
                    </a>
                </li>
                <li class="pt-4 mt-4 border-t border-gray-50">
                    <a href="logout.php" class="flex items-center px-4 py-3 rounded-xl font-bold text-sm text-red-500 hover:bg-red-50">
                        <i class="fas fa-sign-out-alt fa-fw mr-3"></i> Logout
                    </a>
                </li>
            </ul>
        </aside>

        <!-- MAIN CONTENT -->
        <main class="w-full md:w-3/4">
            
            <?php if ($view === 'overview'): ?>
                <div class="bg-white p-6 md:p-10 rounded-2xl shadow-sm border border-gray-100">
                    <h1 class="text-2xl md:text-3xl font-black text-gray-900 mb-2 italic">Namaste, <?= explode(' ', $_SESSION["name"])[0]; ?>! 👋</h1>
                    <p class="text-gray-500 text-sm mb-10">Manage your orders and account details below.</p>
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div class="p-6 bg-indigo-50 rounded-2xl border border-indigo-100 text-indigo-900 font-bold">
                            <?= htmlspecialchars($_SESSION['name']) ?>
                        </div>
                        <div class="p-6 bg-gray-50 rounded-2xl border border-gray-100 text-gray-700 font-bold truncate">
                            Dashboard Home
                        </div>
                    </div>
                </div>

            <?php elseif ($view === 'orders'): ?>
                <div class="bg-white p-4 md:p-8 rounded-2xl shadow-sm border border-gray-100">
                    <h1 class="text-xl md:text-2xl font-bold text-gray-800 mb-6">Order History</h1>
                    
                    <?php 
                        $orders_stmt = $conn->prepare("SELECT o.*, 
                            (SELECT GROUP_CONCAT(p.name SEPARATOR ', ') FROM order_items oi JOIN products p ON oi.product_id = p.id WHERE oi.order_id = o.id LIMIT 2) AS product_names
                            FROM orders o WHERE o.user_id = ? ORDER BY o.created_at DESC");
                        $orders_stmt->bind_param("i", $user_id);
                        $orders_stmt->execute();
                        $orders = $orders_stmt->get_result();

                        if ($orders->num_rows > 0): 
                    ?>
                        <!-- DESKTOP TABLE VIEW -->
                        <div class="hidden md:block overflow-x-auto">
                            <table class="min-w-full">
                                <thead class="bg-gray-50 border-b">
                                    <tr>
                                        <th class="py-4 px-4 text-left text-xs font-black uppercase text-gray-400 tracking-tighter">Order</th>
                                        <th class="py-4 px-4 text-left text-xs font-black uppercase text-gray-400 tracking-tighter">Items</th>
                                        <th class="py-4 px-4 text-right text-xs font-black uppercase text-gray-400 tracking-tighter">Amount</th>
                                        <th class="py-4 px-4 text-center text-xs font-black uppercase text-gray-400 tracking-tighter">Status</th>
                                        <th class="py-4 px-4"></th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-gray-50 text-sm">
                                    <?php while($order = $orders->fetch_assoc()): ?>
                                        <tr class="hover:bg-gray-50 transition-colors">
                                            <td class="py-5 px-4 font-bold">#<?= $order['id'] ?></td>
                                            <td class="py-5 px-4 text-gray-500 truncate max-w-[180px]"><?= htmlspecialchars($order['product_names']) ?></td>
                                            <td class="py-5 px-4 text-right font-black">Rs. <?= number_format($order['grand_total'], 2) ?></td>
                                            <td class="py-5 px-4 text-center">
                                                <!-- FIXED: Calling function instead of including file -->
                                                <?= getStatusBadge($order['status']) ?>
                                            </td>
                                            <td class="py-5 px-4 text-right">
                                                <a href="user_order_detail.php?id=<?= $order['id'] ?>" class="text-indigo-600 font-bold hover:underline">View</a>
                                            </td>
                                        </tr>
                                    <?php endwhile; mysqli_data_seek($orders, 0); ?>
                                </tbody>
                            </table>
                        </div>

                        <!-- MOBILE CARD VIEW -->
                        <div class="md:hidden space-y-4">
                            <?php while($order = $orders->fetch_assoc()): ?>
                                <div class="p-5 bg-gray-50 border border-gray-100 rounded-2xl">
                                    <div class="flex justify-between items-start mb-2">
                                        <p class="text-[10px] font-black text-gray-400 uppercase tracking-widest">Order #<?= $order['id'] ?></p>
                                        <p class="font-black text-gray-900 text-sm">Rs. <?= number_format($order['grand_total'], 2) ?></p>
                                    </div>
                                    <p class="text-xs text-gray-600 mb-4 truncate italic"><?= htmlspecialchars($order['product_names']) ?></p>
                                    <div class="flex justify-between items-center pt-4 border-t border-gray-200/50">
                                        <?= getStatusBadge($order['status']) ?>
                                        <a href="user_order_detail.php?id=<?= $order['id'] ?>" class="bg-white border px-4 py-1.5 rounded-lg text-xs font-bold text-indigo-600 shadow-sm">Details</a>
                                    </div>
                                </div>
                            <?php endwhile; ?>
                        </div>

                    <?php else: ?>
                        <p class="text-center text-gray-400 py-10 font-bold">No orders found yet.</p>
                    <?php endif; $orders_stmt->close(); ?>
                </div>

            <?php elseif ($view === 'profile'): ?>
                <div class="bg-white p-6 md:p-8 rounded-2xl shadow-sm border border-gray-100">
                    <h2 class="text-xl font-bold text-gray-800 mb-6 border-b pb-4">Personal Settings</h2>
                    <?php
                        $st = $conn->prepare("SELECT name, email, profile_image_url FROM users WHERE id = ?");
                        $st->bind_param("i", $user_id); $st->execute();
                        $udata = $st->get_result()->fetch_assoc(); $st->close();
                    ?>
                    <form action="actions/handle_profile.php" method="POST" enctype="multipart/form-data" class="space-y-6">
                        <input type="hidden" name="action" value="update_details">
                        <div class="flex flex-col sm:flex-row items-center gap-6">
                            <img src="<?= getProfileImage($udata['profile_image_url']) ?>" class="w-20 h-20 rounded-2xl object-cover border shadow-sm">
                            <input type="file" name="profile_image" class="text-xs">
                        </div>
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                            <div><label class="block text-[10px] font-black uppercase text-gray-400 mb-1">Full Name</label><input type="text" name="name" value="<?= htmlspecialchars($udata['name']) ?>" class="w-full bg-gray-50 border rounded-xl py-3 px-4 outline-none"></div>
                            <div><label class="block text-[10px] font-black uppercase text-gray-400 mb-1">Email</label><input type="email" name="email" value="<?= htmlspecialchars($udata['email']) ?>" class="w-full bg-gray-50 border rounded-xl py-3 px-4 outline-none"></div>
                        </div>
                        <button type="submit" class="bg-indigo-600 text-white px-8 py-3 rounded-xl font-bold text-sm">Save Changes</button>
                    </form>
                </div>

                                    <!-- Change Password Section -->
                    <div class="bg-gray-900 p-6 md:p-10 rounded-3xl shadow-xl text-white mt-8">
                        <div class="flex items-center gap-3 mb-6">
                            <div class="w-10 h-10 bg-indigo-500 rounded-full flex items-center justify-center">
                                <i class="fas fa-lock text-sm text-white"></i>
                            </div>
                            <div>
                                <h2 class="text-xl font-bold italic">Account Security</h2>
                                <p class="text-[10px] text-gray-400 uppercase tracking-widest font-black">Update your secret password</p>
                            </div>
                        </div>

                        <form action="actions/handle_profile.php" method="POST" class="space-y-6">
                            <input type="hidden" name="action" value="change_password">
                            
                            <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                                <!-- Current Password -->
                                <div>
                                    <label class="block text-[10px] font-black uppercase text-gray-500 mb-2 ml-1">Current Password</label>
                                    <input type="password" name="current_password" placeholder="••••••••" 
                                        class="w-full bg-gray-800 border-0 rounded-2xl py-4 px-5 text-sm focus:ring-2 focus:ring-indigo-500 outline-none transition-all text-white placeholder-gray-600" required>
                                </div>

                                <!-- New Password -->
                                <div>
                                    <label class="block text-[10px] font-black uppercase text-gray-500 mb-2 ml-1">New Password</label>
                                    <input type="password" name="new_password" placeholder="Minimum 6 chars" 
                                        class="w-full bg-gray-800 border-0 rounded-2xl py-4 px-5 text-sm focus:ring-2 focus:ring-indigo-500 outline-none transition-all text-white placeholder-gray-600" required>
                                </div>

                                <!-- Confirm Password -->
                                <div>
                                    <label class="block text-[10px] font-black uppercase text-gray-500 mb-2 ml-1">Confirm New</label>
                                    <input type="password" name="confirm_password" placeholder="Repeat new password" 
                                        class="w-full bg-gray-800 border-0 rounded-2xl py-4 px-5 text-sm focus:ring-2 focus:ring-indigo-500 outline-none transition-all text-white placeholder-gray-600" required>
                                </div>
                            </div>

                            <div class="flex flex-col sm:flex-row items-center justify-between gap-4 pt-4">
                                <p class="text-xs text-gray-500 italic max-w-sm">
                                    <i class="fas fa-info-circle mr-1"></i> Make sure your new password is strong and difficult to guess.
                                </p>
                                <button type="submit" class="w-full sm:w-auto bg-white text-gray-900 px-10 py-4 rounded-2xl font-black text-xs uppercase tracking-widest hover:bg-indigo-400 hover:text-white transition-all shadow-lg active:scale-95">
                                    Update Password
                                </button>
                            </div>
                        </form>
                    </div>

            <?php endif; ?>

        </main>
    </div>
</div>

<style>
    .no-scrollbar::-webkit-scrollbar { display: none; }
    .no-scrollbar { -ms-overflow-style: none; scrollbar-width: none; }
</style>

<?php
require_once 'includes/footer.php'; 
if(isset($conn)) { $conn->close(); }
?>