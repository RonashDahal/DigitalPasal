<?php
// Use the centralized init file for robust session handling
require_once 'includes/init.php'; 
require_once 'includes/db_connect.php';

// Fetch initial data
$categories_result = $conn->query("SELECT * FROM categories WHERE is_active = 1 ORDER BY name ASC");
$price_range_result = $conn->query("SELECT MIN(price) as min_price, MAX(price) as max_price FROM products")->fetch_assoc();
$min_price = floor($price_range_result['min_price'] ?? 0);
$max_price = ceil($price_range_result['max_price'] ?? 1000);
$category_id_from_url = isset($_GET['category']) ? (int)$_GET['category'] : 0;
$search_term_from_url = isset($_GET['search']) ? htmlspecialchars($_GET['search']) : '';

// Include the header
require_once 'includes/header.php';
?>

<div class="bg-gray-100 min-h-screen">
    <div class="container mx-auto px-4 py-6">

        <!-- **FIXED**: Main controls now include the search bar directly -->
        <div class="bg-white rounded-lg shadow p-4 mb-6 sticky top-0 z-30 space-y-4">
            <!-- Top row: Search Bar -->
            <form id="search-form">
                <div class="relative">
                    <input type="search" id="search-input" name="search" placeholder="Search products..." class="w-full pl-10 pr-4 py-2.5 border-2 border-gray-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-indigo-500" value="<?= $search_term_from_url ?>">
                    <i class="fas fa-search absolute left-3 top-1/2 transform -translate-y-1/2 text-gray-400"></i>
                </div>
            </form>
            <!-- Bottom row: Filters and Sorting -->
            <div class="flex justify-between items-center">
                <button id="open-filter-btn" class="border border-gray-300 text-gray-700 px-4 py-2 rounded-lg font-semibold flex items-center gap-2 hover:bg-gray-50">
                    <i class="fas fa-filter text-sm"></i>
                    <span>Filters</span>
                </button>
                <div class="w-1/2 sm:w-1/3 md:w-1/4">
                    <select id="sort-control" name="sort" class="w-full p-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-indigo-500">
                        <option value="newest">Sort: Newest</option>
                        <option value="price_asc">Price: Low to High</option>
                        <option value="price_desc">Price: High to Low</option>
                        <option value="name_asc">Name: A-Z</option>
                    </select>
                </div>
            </div>
        </div>

        <div class="flex flex-col md:flex-row gap-8">
            <!-- Filter Sidebar (Drawer on Mobile, Sidebar on Desktop) -->
            <aside id="filter-sidebar" class="fixed top-0 left-0 h-full z-50 transition-transform duration-300 ease-in-out transform -translate-x-full md:static md:transform-none md:w-1/4 lg:w-1/5 md:self-start">
                <div id="filter-backdrop" class="fixed inset-0 bg-black bg-opacity-50 md:hidden hidden"></div>
                <div class="relative bg-white h-full w-80 md:w-full md:rounded-lg md:shadow-md flex flex-col">
                    <div class="flex justify-between items-center p-4 border-b">
                        <h2 class="font-bold text-lg">Filters</h2>
                        <button id="close-filter-btn" class="text-gray-500 text-2xl md:hidden">×</button>
                    </div>
                    
                    <div id="filter-options" class="p-4 md:p-6 flex-grow overflow-y-auto">
                        <!-- Categories -->
                        <div class="mb-6">
                            <h3 class="font-bold text-gray-700 mb-2">Category</h3>
                            <div id="category-filters" class="space-y-2 max-h-48 overflow-y-auto pr-2"><?php mysqli_data_seek($categories_result, 0); while($category = $categories_result->fetch_assoc()): ?><div class="flex items-center"><input type="checkbox" name="categories[]" id="cat-<?= $category['id'] ?>" value="<?= $category['id'] ?>" class="h-4 w-4 text-indigo-600 border-gray-300 rounded focus:ring-indigo-500" <?= ($category_id_from_url == $category['id']) ? 'checked' : '' ?>><label for="cat-<?= $category['id'] ?>" class="ml-3 text-sm text-gray-600"><?= htmlspecialchars($category['name']) ?></label></div><?php endwhile; ?></div>
                        </div>
                        <!-- Price Range -->
                        <div class="mb-6">
                             <h3 class="font-bold text-gray-700 mb-2">Price Range</h3>
                             <div class="flex items-center gap-1.5 text-sm text-gray-600 mb-2"><span>Rs. </span><span><?= $min_price ?></span><span>-</span><span>Rs. </span><span id="max-price-display"><?= $max_price ?></span></div>
                             <input type="range" id="price-range" name="price_max" min="<?= $min_price ?>" max="<?= $max_price ?>" value="<?= $max_price ?>" class="w-full h-2 bg-gray-200 rounded-lg appearance-none cursor-pointer">
                        </div>
                    </div>
                    <div class="p-4 border-t mt-auto md:hidden">
                        <button id="done-filter-btn" class="w-full bg-indigo-600 text-white font-bold py-3 rounded-lg">Done</button>
                    </div>
                </div>
            </aside>

            <!-- Product Grid -->
            <main class="w-full md:w-3/4 lg:w-4/5">
                <div id="loading-spinner" class="hidden text-center p-20"><i class="fas fa-spinner fa-spin fa-3x text-indigo-600"></i></div>
                <div id="product-grid" class="grid grid-cols-2 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-4"></div>
            </main>
        </div>
    </div>
    
    <div class="pb-24 lg:hidden"></div>

</div> <!-- This closes the main bg-gray-100 container -->

<?php
require_once 'includes/footer.php';
$conn->close();
?>

<!-- **FIXED**: All new JavaScript for the separated search and filter logic -->
<script>
document.addEventListener('DOMContentLoaded', function() {
    // --- Element Selectors ---
    const searchForm = document.getElementById('search-form');
    const searchInput = document.getElementById('search-input');
    const productGrid = document.getElementById('product-grid');
    const loadingSpinner = document.getElementById('loading-spinner');
    
    // Filter controls inside the drawer
    const filterOptions = document.getElementById('filter-options');
    const priceRange = document.getElementById('price-range');
    const maxPriceDisplay = document.getElementById('max-price-display');
    
    // Drawer elements
    const openFilterBtn = document.getElementById('open-filter-btn');
    const closeFilterBtn = document.getElementById('close-filter-btn');
    const doneFilterBtn = document.getElementById('done-filter-btn');
    const filterSidebar = document.getElementById('filter-sidebar');
    const filterBackdrop = document.getElementById('filter-backdrop');
    
    // Main sort control
    const sortControl = document.getElementById('sort-control');

    // --- Price Range Slider UI ---
    if (priceRange) {
        priceRange.addEventListener('input', () => { maxPriceDisplay.textContent = priceRange.value; });
    }

    // --- Filter Drawer Logic ---
    const openDrawer = () => {
        filterBackdrop.classList.remove('hidden');
        filterSidebar.classList.remove('-translate-x-full');
    };
    
    const closeDrawer = () => {
        filterBackdrop.classList.add('hidden');
        filterSidebar.classList.add('-translate-x-full');
    };

    openFilterBtn.addEventListener('click', openDrawer);
    closeFilterBtn.addEventListener('click', closeDrawer);
    doneFilterBtn.addEventListener('click', closeDrawer);
    filterBackdrop.addEventListener('click', closeDrawer);

    // --- Debounced Search ---
    let searchTimeout;
    searchInput.addEventListener('keyup', () => {
        clearTimeout(searchTimeout);
        searchTimeout = setTimeout(fetchProducts, 350);
    });
    searchForm.addEventListener('submit', (e) => {
        e.preventDefault();
        fetchProducts();
    });

    // --- Main Data Fetching Function (Re-architected) ---
    async function fetchProducts() {
        loadingSpinner.classList.remove('hidden');
        productGrid.classList.add('hidden');

        // Manually build URL parameters from all separate inputs
        const params = new URLSearchParams();
        params.append('search', searchInput.value);
        params.append('sort', sortControl.value);
        
        // Get category filters
        const checkedCategories = filterOptions.querySelectorAll('input[name="categories[]"]:checked');
        checkedCategories.forEach(checkbox => {
            params.append('categories[]', checkbox.value);
        });

        // Get price filter
        params.append('price_max', priceRange.value);
        
        try {
            const response = await fetch(`actions/filter_products.php?${params.toString()}`);
            if (!response.ok) throw new Error(`HTTP error! status: ${response.status}`);
            productGrid.innerHTML = await response.text();
        } catch (error) {
            console.error('Failed to fetch products:', error);
            productGrid.innerHTML = '<p class="col-span-full text-center text-red-500 py-12">Failed to load products.</p>';
        } finally {
            loadingSpinner.classList.add('hidden');
            productGrid.classList.remove('hidden');
            if (window.updateWishlistUI) window.updateWishlistUI();
        }
    }

    // --- Event Listeners to trigger fetch ---
    // Any change in the filter drawer or sort control will trigger a fetch
    filterOptions.addEventListener('change', fetchProducts);
    sortControl.addEventListener('change', fetchProducts);
    
    // --- Initial Load ---
    fetchProducts();
});
</script>