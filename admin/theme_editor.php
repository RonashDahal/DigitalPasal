<?php
require_once 'includes/admin_header.php';
require_once '../includes/db_connect.php';

// Fetch all settings from the database
$settings_result = $conn->query("SELECT * FROM site_settings");
$settings = [];
while ($row = $settings_result->fetch_assoc()) {
    $settings[$row['setting_key']] = $row['setting_value'];
}
?>

<h1 class="text-3xl font-bold text-gray-800 mb-8">Homepage Hero Section</h1>

<div class="bg-white shadow-md rounded-lg p-8 max-w-4xl mx-auto">
    <form action="actions/handle_settings.php" method="post" enctype="multipart/form-data">

        <!-- Title -->
        <div class="mb-6">
            <label class="block text-gray-700 font-bold mb-2" for="hero_title">Hero Title</label>
            <p class="text-sm text-gray-500 mb-2">Use &lt;br&gt; for a line break.</p>
            <input type="text" id="hero_title" name="hero_title" value="" class="shadow appearance-none border rounded w-full py-2 px-3 text-lg" required>
        </div>

        <!-- Description -->
        <div class="mb-6">
            <label class="block text-gray-700 font-bold mb-2" for="hero_description">Hero Description</label>
            <textarea id="hero_description" name="hero_description" rows="4" class="shadow appearance-none border rounded w-full py-2 px-3" required></textarea>
        </div>

        <!-- Image Upload -->
        <div class="mb-6">
            <label class="block text-gray-700 font-bold mb-2" for="hero_image">Hero Image</label>
            <input type="file" id="hero_image" name="hero_image" class="shadow appearance-none border rounded w-full py-2 px-3">
            <?php if (!empty($settings['hero_image'])): ?>
                <div class="mt-4">
                    <p class="text-sm text-gray-600">Current Image:</p>
                    <img src="../<?= htmlspecialchars($settings['hero_image']) ?>" class="mt-2 w-64 h-auto rounded shadow">
                    <p class="text-xs text-gray-500 mt-2">Uploading a new image will replace the current one.</p>
                </div>
            <?php endif; ?>
        </div>

                            <!-- Shipping Charge Setting -->
                    <div class="mb-6 p-4 bg-indigo-50 rounded-xl border border-indigo-100">
                        <label class="block text-indigo-900 font-bold mb-2" for="shipping_charge">Global Shipping Charge (Rs.)</label>
                        <div class="relative">
                            <span class="absolute inset-y-0 left-0 pl-3 flex items-center text-gray-500 font-bold">Rs.</span>
                            <input type="number" id="shipping_charge" name="shipping_charge" 
                                value="<?= htmlspecialchars($settings['shipping_charge'] ?? '0') ?>" 
                                class="shadow border rounded-xl w-full py-3 pl-10 pr-4 text-gray-700 focus:ring-2 focus:ring-indigo-500 outline-none" required>
                        </div>
                        <p class="text-[10px] text-indigo-400 mt-2 uppercase font-black tracking-widest">This amount is added to every order after discounts.</p>
                    </div>

        <div class="flex justify-end mt-8">
            <button type="submit" class="bg-blue-500 hover:bg-blue-700 text-white font-bold py-3 px-6 rounded text-lg">
                <i class="fas fa-save mr-2"></i>Save Settings
            </button>
        </div>
    </form>
</div>

<?php
$conn->close();
require_once 'includes/admin_footer.php';
?>