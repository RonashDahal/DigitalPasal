</main>

<?php
// This is your original, working database query for the footer.
$footer_settings_result = $conn->query("SELECT setting_key, setting_value FROM site_settings WHERE setting_key LIKE 'footer_%'");
$footer_settings = [];
if ($footer_settings_result) {
    while ($row = $footer_settings_result->fetch_assoc()) { $footer_settings[$row['setting_key']] = $row['setting_value']; }
}
?>

<!-- Your main footer, now visible on all devices -->
<footer class="bg-gray-800 text-white pt-16 pb-6">
    <div class="container mx-auto px-6 lg:px-8">
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-10 mb-10">
            <div class="md-col-span-2 lg:col-span-1"><a href="index.php" class="flex items-center space-x-2 text-2xl font-bold mb-4"><i class="fas fa-store text-indigo-400"></i><span>Dada'S Store</span></a><p class="text-gray-400 text-sm leading-relaxed"><?= htmlspecialchars($footer_settings['footer_about_us'] ?? '') ?></p></div>
            <div><h3 class="font-bold text-lg mb-4 tracking-wider uppercase">Info</h3><ul class="space-y-3"><li><a href="page.php?slug=about-us" class="text-gray-400 hover:text-white transition-colors">About Us</a></li><li><a href="page.php?slug=privacy-policy" class="text-gray-400 hover:text-white transition-colors">Privacy Policy</a></li></ul></div>
            <div><h3 class="font-bold text-lg mb-4 tracking-wider uppercase">Account</h3><ul class="space-y-3"><?php if (isset($_SESSION["loggedin"]) && $_SESSION["loggedin"] === true): ?><li><a href="dashboard.php" class="text-gray-400 hover:text-white transition-colors">My Dashboard</a></li><li><a href="logout.php" class="text-gray-400 hover:text-white transition-colors">Logout</a></li><?php else: ?><li><a href="login.php" class="text-gray-400 hover:text-white transition-colors">Login / Register</a></li><?php endif; ?><li><a href="cart.php" class="text-gray-400 hover:text-white transition-colors">View Cart</a></li></ul></div>
            <div><h3 class="font-bold text-lg mb-4 tracking-wider uppercase">Contact</h3><ul class="space-y-3 text-gray-400 text-sm"><li class="flex items-start"><i class="fas fa-map-marker-alt fa-fw mt-1 mr-3"></i><span><?= nl2br(htmlspecialchars($footer_settings['footer_address'] ?? '')) ?></span></li><li class="flex items-center"><i class="fas fa-envelope fa-fw mr-3"></i><a href="mailto:<?= htmlspecialchars($footer_settings['footer_email'] ?? '') ?>" class="hover:text-white transition-colors"><?= htmlspecialchars($footer_settings['footer_email'] ?? '') ?></a></li></ul><div class="flex space-x-4 mt-6"><a href="<?= htmlspecialchars($footer_settings['footer_social_facebook'] ?? '#') ?>" class="text-gray-400 hover:text-white transition-colors"><i class="fab fa-facebook-f fa-lg"></i></a><a href="<?= htmlspecialchars($footer_settings['footer_social_instagram'] ?? '#') ?>" class="text-gray-400 hover:text-white transition-colors"><i class="fab fa-instagram fa-lg"></i></a><a href="<?= htmlspecialchars($footer_settings['footer_social_twitter'] ?? '#') ?>" class="text-gray-400 hover:text-white transition-colors"><i class="fab fa-twitter fa-lg"></i></a></div></div>
        </div>
        <div class="border-t border-gray-700 pt-6 mt-6 text-center text-gray-500 text-sm"><p>© <?= date('Y') ?> MyStore. All Rights Reserved.</p></div>
    </div>
    
    <!-- Spacer so footer content isn't hidden by the mobile nav bar -->
    <div class="pb-16 lg:hidden"></div>
</footer>

<!-- MOBILE BOTTOM NAVIGATION BAR -->
<?php $currentPage = basename($_SERVER['SCRIPT_NAME']); ?>
<!-- **THE FIX IS HERE**: Added an ID and a new class for the JavaScript to target -->
<div id="mobile-bottom-nav" class="mobile-nav fixed bottom-0 left-0 right-0 bg-white shadow-[0_-1px_4px_rgba(0,0,0,0.1)] z-40 lg:hidden">
    <div class="flex justify-around items-center h-16">
        <a href="index.php" class="text-center <?= ($currentPage == 'index.php') ? 'text-indigo-600' : 'text-gray-600' ?>"><i class="fas fa-home fa-lg"></i><span class="block text-xs font-medium">Home</span></a>
        <a href="products.php" class="text-center <?= ($currentPage == 'products.php' || $currentPage == 'product_detail.php') ? 'text-indigo-600' : 'text-gray-600' ?>"><i class="fas fa-th-large fa-lg"></i><span class="block text-xs font-medium">Products</span></a>
        <a href="cart.php" class="text-center <?= ($currentPage == 'cart.php') ? 'text-indigo-600' : 'text-gray-600' ?>"><i class="fas fa-shopping-cart fa-lg"></i><span class="block text-xs font-medium">Cart</span></a>
        <a href="dashboard.php" class="text-center <?= ($currentPage == 'dashboard.php') ? 'text-indigo-600' : 'text-gray-600' ?>"><i class="fas fa-user fa-lg"></i><span class="block text-xs font-medium">Account</span></a>
    </div>
</div>

<!-- =================================================================== -->
<!-- == START: JAVASCRIPT FOR SCROLL-TO-HIDE NAVIGATION BAR           == -->
<!-- =================================================================== -->
<style>
    /* This ensures the slide animation is smooth */
    .mobile-nav {
        transition: transform 0.3s ease-in-out;
    }
    /* This class will be added by JavaScript to hide the bar */
    .mobile-nav-hidden {
        transform: translateY(100%);
    }
</style>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        const bottomNav = document.getElementById('mobile-bottom-nav');
        if (bottomNav) {
            let lastScrollY = window.scrollY;

            window.addEventListener('scroll', () => {
                const currentScrollY = window.scrollY;
                // Hide only if scrolling down and past a certain point (e.g., 50px)
                if (currentScrollY > lastScrollY && currentScrollY > 50) {
                    bottomNav.classList.add('mobile-nav-hidden');
                } else {
                    // Show when scrolling up
                    bottomNav.classList.remove('mobile-nav-hidden');
                }
                // Update the last scroll position
                lastScrollY = currentScrollY;
            });
        }
    });
</script>
<!-- =================================================================== -->
<!-- == END: JAVASCRIPT FOR SCROLL-TO-HIDE NAVIGATION BAR             == -->
<!-- =================================================================== -->

<script src="assets/script.js"></script>
<!-- Global Toast Container -->
<div id="toast-container" class="fixed bottom-20 right-4 z-[60] flex flex-col gap-2 pointer-events-none md:bottom-8 md:right-8"></div>
</body>
</html>