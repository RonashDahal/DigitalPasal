<?php
// Start session only if one isn't already active
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once 'includes/functions.php';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>MyStore Professional</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
    <link rel="stylesheet" href="assets/style.css">
    <!-- Custom styles for the new header -->
    <style>
        .nav-link {
            position: relative;
            transition: color 0.3s ease;
        }
        .nav-link::after {
            content: '';
            position: absolute;
            width: 0;
            height: 3.5px;
            bottom: -4px;
            left: 50%;
            background-color: #4f46e5; /* indigo-600 */
            transition: all 0.3s ease;
            transform: translateX(-50%);
        }
        .nav-link:hover::after, .nav-link.active::after {
            width: 100%;
        }
        #mobile-menu-panel {
            transition: transform 0.3s ease-in-out;
        }
    </style>
</head>
<body class="bg-gray-100 font-sans leading-normal tracking-normal">

<header class="bg-white/95 backdrop-blur-sm shadow-sm sticky top-0 z-50">
    <nav class="container mx-auto px-4 sm:px-6 lg:px-8">
        <div class="flex items-center justify-between h-20">
            
            <!-- Logo -->
            <div class="flex-shrink-0">
                <a href="index.php" class="flex items-center space-x-2 text-2xl font-bold text-gray-800">
                    <i class="fas fa-store text-indigo-600"></i>
                    <span>Dada'S Store</span>
                </a>
            </div>

            <!-- Desktop Navigation Links -->
            <div class="hidden md:flex md:items-center md:space-x-10">
                <a href="index.php" class="nav-link text-gray-600 hover:text-gray-900 font-medium">Home</a>
                <a href="products.php" class="nav-link text-gray-600 hover:text-gray-900 font-medium">Products</a>
                <!-- You can add more links here e.g., About, Contact -->
            </div>

            <!-- Right side Actions (Desktop) -->
            <div class="hidden md:flex items-center space-x-6">
                <?php if (isset($_SESSION["loggedin"]) && $_SESSION["loggedin"] === true): ?>
    <a href="dashboard.php" class="flex items-center space-x-2 group">
        <?php // Display profile pic or fallback icon ?>
        <?php if (!empty($_SESSION['profile_image_url'])): ?>
            <img src="<?= htmlspecialchars($_SESSION['profile_image_url']) ?>" alt="Profile" class="w-8 h-8 rounded-full object-cover">
        <?php else: ?>
            <i class="fas fa-user-circle fa-lg text-gray-500 group-hover:text-indigo-600 transition-colors"></i>
        <?php endif; ?>
        <span class="text-sm font-medium text-gray-600 group-hover:text-indigo-600 hidden sm:inline"><?= htmlspecialchars($_SESSION['name']) ?></span>
    </a>
<?php else: ?>
                    <a href="login.php" class="text-gray-600 hover:text-gray-900 font-medium">Login</a>
                    <a href="register.php" class="bg-indigo-600 text-white px-5 py-2 rounded-full text-sm font-semibold hover:bg-indigo-700 transition-all shadow-sm hover:shadow-md">
                        Sign Up
                    </a>
                <?php endif; ?>
                
                <div class="h-6 w-px bg-gray-200"></div>

                <a href="cart.php" class="group relative">
                    <i class="fas fa-shopping-bag fa-lg text-gray-500 group-hover:text-indigo-600 transition-colors"></i>
                    <span id="cart-badge-desktop" class="absolute -top-2 -right-3 bg-red-500 text-white text-xs rounded-full h-5 w-5 flex items-center justify-center">
                        <?= get_cart_count() ?>
                    </span>
                </a>
            </div>

            <!-- Mobile Menu Button -->
            <div class="md:hidden flex items-center">
                <button id="mobile-menu-button" class="p-2 rounded-md text-gray-500 hover:text-indigo-600 focus:outline-none">
                    <i id="menu-icon" class="fas fa-bars fa-lg"></i>
                </button>
            </div>
        </div>
    </nav>
</header>

<!-- Mobile Menu Panel (Initially hidden) -->
<div id="mobile-menu-panel" class="md:hidden fixed top-0 left-0 w-full h-full bg-black bg-opacity-50 z-50 hidden">
    <div class="fixed top-0 left-0 w-4/5 max-w-sm h-full bg-white shadow-xl p-6 transform -translate-x-full">
        <!-- Mobile Menu Header -->
        <div class="flex items-center justify-between mb-8">
            <a href="index.php" class="text-xl font-bold text-gray-800">MyStore</a>
            <button id="close-mobile-menu" class="p-2 text-gray-500 hover:text-gray-900">
                <i class="fas fa-times fa-lg"></i>
            </button>
        </div>
        <!-- Mobile Menu Links -->
        <nav class="flex flex-col space-y-4">
            <a href="index.php" class="text-gray-700 hover:bg-gray-100 p-3 rounded-md font-medium">Home</a>
            <a href="products.php" class="text-gray-700 hover:bg-gray-100 p-3 rounded-md font-medium">Products</a>
            <a href="cart.php" class="text-gray-700 hover:bg-gray-100 p-3 rounded-md font-medium flex justify-between items-center">
                <span>Shopping Cart</span>
                <span id="cart-badge-mobile" class="bg-indigo-600 text-white text-xs font-bold px-2 py-1 rounded-full"><?= get_cart_count() ?></span>
            </a>
            <hr class="my-4">
            <?php if (isset($_SESSION["loggedin"]) && $_SESSION["loggedin"] === true): ?>
                <a href="dashboard.php" class="text-gray-700 hover:bg-gray-100 p-3 rounded-md font-medium">My Account</a>
                <a href="logout.php" class="text-gray-700 hover:bg-gray-100 p-3 rounded-md font-medium">Logout</a>
            <?php else: ?>
                <a href="login.php" class="text-gray-700 hover:bg-gray-100 p-3 rounded-md font-medium">Login</a>
                <a href="register.php" class="text-gray-700 hover:bg-gray-100 p-3 rounded-md font-medium">Sign Up</a>
            <?php endif; ?>
        </nav>
    </div>
</div>

<main class="container mx-auto px-6 py-8">