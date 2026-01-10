<?php
require_once '../includes/init.php'; 
require_once 'auth_check.php'; 
require_once '../includes/db_connect.php'; 

$current_page = basename($_SERVER['PHP_SELF']);

// --- Dynamic counts ---
$pending_orders_count = $conn->query("SELECT COUNT(id) FROM orders WHERE status = 'Payment Submitted' OR status = 'Pending'")->fetch_row()[0] ?? 0;
$low_stock_count = $conn->query("SELECT COUNT(id) FROM products WHERE stock_quantity < 10")->fetch_row()[0] ?? 0;

// --- Profile Image Logic ---
$profile_image_url = 'https://ui-avatars.com/api/?name=' . urlencode($_SESSION['name'] ?? 'Admin') . '&background=random';
if (!empty($_SESSION['profile_image_url'])) {
    $img_path = $_SESSION['profile_image_url'];
    if (!filter_var($img_path, FILTER_VALIDATE_URL)) {
        $profile_image_url = '../' . ltrim(str_replace('\\', '/', $img_path), '/');
    } else {
        $profile_image_url = $img_path;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Panel - MyStore</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
    <style>
        .sidebar-link { transition: all 0.2s; }
        .sidebar-link.active { background-color: #4f46e5; color: #ffffff; font-weight: 600; }
        /* Mobile Sidebar Toggle Logic */
        #admin-sidebar { transition: transform 0.3s ease-in-out; }
        @media (max-width: 768px) {
            .sidebar-hidden { transform: translateX(-100%); }
            .sidebar-visible { transform: translateX(0); }
        }
    </style>
</head>
<body class="bg-gray-100">
    <!-- Overlay for mobile sidebar -->
    <div id="sidebar-overlay" onclick="toggleAdminSidebar()" class="fixed inset-0 bg-black/50 z-40 hidden md:hidden"></div>

    <div class="flex h-screen overflow-hidden">
        
        <!-- Sidebar -->
        <div id="admin-sidebar" class="fixed inset-y-0 left-0 w-64 bg-gray-900 text-gray-200 flex flex-col z-50 sidebar-hidden md:relative md:transform-none">
            
            <!-- Sidebar Header -->
            <div class="flex-shrink-0 px-4 py-6 border-b border-gray-800 flex justify-between items-center">
                <a href="index.php" class="text-white flex items-center space-x-2">
                    <i class="fas fa-store text-indigo-500"></i>
                    <span class="text-xl font-black uppercase tracking-tighter">Admin Panel</span>
                </a>
                <!-- Close button for mobile -->
                <button onclick="toggleAdminSidebar()" class="md:hidden text-gray-400 hover:text-white">
                    <i class="fas fa-times"></i>
                </button>
            </div>

            <!-- Navigation Links -->
            <nav class="flex-grow px-2 py-4 space-y-1 overflow-y-auto custom-scrollbar">
                <a href="index.php" class="sidebar-link flex items-center space-x-3 py-2.5 px-4 rounded-xl <?= $current_page == 'index.php' ? 'active' : 'hover:bg-gray-800' ?>">
                    <i class="fas fa-tachometer-alt w-5"></i><span>Dashboard</span>
                </a>
                <a href="analytics.php" class="sidebar-link flex items-center space-x-3 py-2.5 px-4 rounded-xl <?= $current_page == 'analytics.php' ? 'active' : 'hover:bg-gray-800' ?>">
                    <i class="fas fa-chart-line w-5"></i><span>Analytics</span>
                </a>
                <a href="orders.php" class="sidebar-link flex items-center justify-between py-2.5 px-4 rounded-xl <?= in_array($current_page, ['orders.php', 'order_detail.php']) ? 'active' : 'hover:bg-gray-800' ?>">
                    <div class="flex items-center space-x-3">
                        <i class="fas fa-shipping-fast w-5 text-gray-400"></i><span>Orders</span>
                    </div>
                    <?php if ($pending_orders_count > 0): ?>
                        <span class="bg-yellow-500 text-gray-900 text-[10px] font-black px-2 py-0.5 rounded-full"><?= $pending_orders_count ?></span>
                    <?php endif; ?>
                </a>
                
                <h3 class="px-4 pt-6 pb-2 text-[10px] font-black uppercase text-gray-500 tracking-widest">Manage Catalog</h3>
                
                <a href="products.php" class="sidebar-link flex items-center justify-between py-2.5 px-4 rounded-xl <?= in_array($current_page, ['products.php', 'product_form.php']) ? 'active' : 'hover:bg-gray-800' ?>">
                    <div class="flex items-center space-x-3">
                        <i class="fas fa-boxes w-5 text-gray-400"></i><span>Products</span>
                    </div>
                    <?php if ($low_stock_count > 0): ?>
                        <span class="bg-red-500 text-white text-[10px] font-black px-2 py-0.5 rounded-full"><?= $low_stock_count ?></span>
                    <?php endif; ?>
                </a>
                <a href="categories.php" class="sidebar-link flex items-center space-x-3 py-2.5 px-4 rounded-xl <?= in_array($current_page, ['categories.php', 'category_form.php']) ? 'active' : 'hover:bg-gray-800' ?>">
                    <i class="fas fa-tags w-5"></i><span>Categories</span>
                </a>
                <a href="discounts.php" class="sidebar-link flex items-center space-x-3 py-2.5 px-4 rounded-xl <?= in_array($current_page, ['discounts.php', 'discount_form.php']) ? 'active' : 'hover:bg-gray-800' ?>">
                    <i class="fas fa-percent w-5"></i><span>Vouchers</span>
                </a>
                <a href="reviews.php" class="sidebar-link flex items-center space-x-3 py-2.5 px-4 rounded-xl <?= $current_page == 'reviews.php' ? 'active' : 'hover:bg-gray-800' ?>">
                    <i class="fas fa-star w-5"></i><span>Reviews</span>
                </a>
                <a href="users.php" class="sidebar-link flex items-center space-x-3 py-2.5 px-4 rounded-xl <?= $current_page == 'users.php' ? 'active' : 'hover:bg-gray-800' ?>">
                    <i class="fas fa-users w-5"></i><span>Users</span>
                </a>

                <h3 class="px-4 pt-6 pb-2 text-[10px] font-black uppercase text-gray-500 tracking-widest">Settings</h3>

                <a href="theme_editor.php" class="sidebar-link flex items-center space-x-3 py-2.5 px-4 rounded-xl <?= $current_page == 'theme_editor.php' ? 'active' : 'hover:bg-gray-800' ?>">
                    <i class="fas fa-palette w-5"></i><span>Store Theme</span>
                </a>
                                <a href="esewa_settings.php" class="sidebar-link flex items-center space-x-3 py-2.5 px-4 rounded-xl <?= $current_page == 'esewa_settings.php' ? 'active' : 'hover:bg-gray-800' ?>">
                    <img class="logo h-6 w-6" src="../uploads/esewa_head.png" alt=""><span>eSewa Settings</span>
                </a>
                <a href="site_content.php" class="sidebar-link flex items-center space-x-3 py-2.5 px-4 rounded-xl <?= $current_page == 'site_content.php' ? 'active' : 'hover:bg-gray-800' ?>">
                    <i class="fas fa-edit w-5"></i><span>Pages</span>
                </a>
                <a href="logout.php" class="flex items-center space-x-3 py-4 px-4 text-red-400 hover:text-red-300 transition-colors">
                    <i class="fas fa-sign-out-alt w-5"></i><span>Logout</span>
                </a>
            </nav>
        </div>

        <!-- Main Content Area -->
        <div class="flex-1 flex flex-col min-w-0 overflow-hidden">
            
            <!-- Mobile Top Bar -->
            <header class="bg-white border-b border-gray-200 flex items-center justify-between p-4 md:px-8 h-16 flex-shrink-0">
                <div class="flex items-center gap-4">
                    <button onclick="toggleAdminSidebar()" class="text-gray-600 md:hidden focus:outline-none">
                        <i class="fas fa-bars fa-lg"></i>
                    </button>
                    <h2 class="font-bold text-gray-800 truncate">Control Panel</h2>
                </div>
                
                <div class="flex items-center gap-3">
                    <div class="text-right hidden sm:block">
                        <p class="text-xs font-bold text-gray-800 leading-none"><?= htmlspecialchars($_SESSION['name']) ?></p>
                        <p class="text-[10px] text-indigo-500 font-bold uppercase tracking-tighter">System Admin</p>
                    </div>
                    <img src="<?= htmlspecialchars($profile_image_url) ?>" class="w-8 h-8 rounded-full border-2 border-indigo-50 shadow-sm">
                </div>
            </header>

            <!-- Scrollable Content -->
            <main class="flex-1 overflow-x-hidden overflow-y-auto p-4 md:p-8 bg-gray-50">