/**
 * assets/script.js
 * Combined Professional Version: Mobile Menu, Wishlist, and Premium Cart Animations
 */

document.addEventListener('DOMContentLoaded', function() {

    /**
     * =============================================================
     * 1. TOAST NOTIFICATION SYSTEM
     * =============================================================
     * Displays an elegant popup at the bottom of the screen.
     */
    function showToast(message, type = 'success') {
        const container = document.getElementById('toast-container');
        if (!container) return; // Guard clause if container is missing

        const toast = document.createElement('div');
        
        // Dynamic colors based on type
        const bgColor = type === 'success' ? 'bg-gray-900' : 'bg-red-600';
        const icon = type === 'success' ? 'fa-check' : 'fa-exclamation-circle';
        
        // Modern Tailwind Styling
        toast.className = `${bgColor} text-white px-6 py-4 rounded-2xl shadow-2xl flex items-center gap-3 cart-toast pointer-events-auto transition-all duration-300`;
        
        toast.innerHTML = `
            <div class="bg-white/20 rounded-full p-1 text-[10px]">
                <i class="fas ${icon}"></i>
            </div>
            <span class="text-sm font-bold tracking-tight">${message}</span>
        `;

        container.appendChild(toast);

        // Auto-remove after 3 seconds with a fade-out effect
        setTimeout(() => {
            toast.style.opacity = '0';
            toast.style.transform = 'translateY(20px) scale(0.9)';
            setTimeout(() => toast.remove(), 300);
        }, 3000);
    }

    /**
     * =============================================================
     * 2. MOBILE MENU LOGIC
     * =============================================================
     */
    const menuButton = document.getElementById('mobile-menu-button');
    const closeButton = document.getElementById('close-mobile-menu');
    const menuPanel = document.getElementById('mobile-menu-panel');

    if (menuPanel) {
        const menuContent = menuPanel.querySelector('div:first-child');
        const openMenu = () => {
            menuPanel.classList.remove('hidden');
            setTimeout(() => { if (menuContent) menuContent.classList.remove('-translate-x-full'); }, 10);
        };
        const closeMenu = () => {
            if (menuContent) menuContent.classList.add('-translate-x-full');
            setTimeout(() => { menuPanel.classList.add('hidden'); }, 300);
        };
        if (menuButton) menuButton.addEventListener('click', openMenu);
        if (closeButton) closeButton.addEventListener('click', closeMenu);
        if (menuPanel) menuPanel.addEventListener('click', (event) => { if (event.target === menuPanel) closeMenu(); });
    }

    /**
     * =============================================================
     * 3. WISHLIST SYSTEM (Optimistic UI)
     * =============================================================
     */
    document.body.addEventListener('click', function(event) {
        const wishlistButton = event.target.closest('.wishlist-btn');
        if (wishlistButton) {
            event.preventDefault();
            const icon = wishlistButton.querySelector('i');
            const productId = wishlistButton.dataset.productId;
            if (!icon || !productId) return;

            // Save original state to revert if server fails
            const isCurrentlyWishlisted = icon.classList.contains('fas');
            
            // UI Update: Spinner
            icon.classList.remove('fas', 'far', 'text-red-500');
            icon.classList.add('fas', 'fa-spinner', 'fa-spin');

            const formData = new FormData();
            formData.append('product_id', productId);

            fetch('actions/handle_wishlist.php', { method: 'POST', body: formData })
                .then(response => response.json())
                .then(data => {
                    icon.classList.remove('fa-spinner', 'fa-spin');
                    if (data.success) {
                        if (data.action === 'added') {
                            icon.classList.add('fas', 'text-red-500', 'animate-success');
                            showToast("Added to wishlist!");
                        } else {
                            icon.classList.add('far');
                            showToast("Removed from wishlist");
                        }
                    } else {
                        showToast(data.message, 'error');
                        if (isCurrentlyWishlisted) icon.classList.add('fas', 'text-red-500');
                        else icon.classList.add('far');
                    }
                })
                .catch(() => {
                    icon.classList.remove('fa-spinner', 'fa-spin');
                    showToast("Connection error", "error");
                });
        }
    });

    /**
     * =============================================================
     * 4. PREMIUM ADD TO CART SYSTEM
     * =============================================================
     */
    document.body.addEventListener('submit', function(event) {
        const addToCartForm = event.target.closest('.add-to-cart-form');
        if (addToCartForm) {
            event.preventDefault();
            const button = addToCartForm.querySelector('button[type="submit"]');
            const originalHtml = button.innerHTML;
            
            // 1. Loading State
            button.disabled = true;
            button.innerHTML = '<i class="fas fa-circle-notch fa-spin"></i>';
            button.classList.add('scale-95', 'opacity-80');

            fetch('actions/handle_cart.php', { method: 'POST', body: new FormData(addToCartForm) })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        // 2. Animate Header Badges
                        document.querySelectorAll('#cart-badge-desktop, #cart-badge-mobile').forEach(badge => {
                            if (badge) {
                                badge.textContent = data.cart_count;
                                badge.classList.add('animate-success');
                                setTimeout(() => badge.classList.remove('animate-success'), 400);
                            }
                        });

                        // 3. Button Success Animation
                        button.classList.remove('bg-indigo-100', 'text-indigo-600');
                        button.classList.add('bg-green-500', 'text-white', 'animate-success');
                        button.innerHTML = '<i class="fas fa-check"></i>';

                        // 4. Show Notification
                        showToast("Added to your cart!");

                        // 5. Reset Button
                        setTimeout(() => {
                            button.innerHTML = originalHtml;
                            button.classList.remove('animate-success', 'scale-95', 'opacity-80', 'bg-green-500', 'text-white');
                            // Ensure original styling returns
                            if (!button.classList.contains('bg-indigo-600')) {
                                button.classList.add('bg-indigo-100', 'text-indigo-600');
                            }
                            button.disabled = false;
                        }, 2000);

                    } else {
                        showToast(data.message, 'error');
                        button.innerHTML = originalHtml;
                        button.disabled = false;
                        button.classList.remove('scale-95', 'opacity-80');
                    }
                })
                .catch(error => {
                    console.error('Cart Error:', error);
                    showToast("Failed to connect to server", "error");
                    button.innerHTML = originalHtml;
                    button.disabled = false;
                    button.classList.remove('scale-95', 'opacity-80');
                });
        }
    });
});