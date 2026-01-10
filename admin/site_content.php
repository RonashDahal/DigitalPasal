<?php
require_once 'includes/admin_header.php';
$settings_result = $conn->query("SELECT * FROM site_settings");
$settings = [];
if ($settings_result) {
    while ($row = $settings_result->fetch_assoc()) { $settings[$row['setting_key']] = $row['setting_value']; }
}
$active_tab = $_GET['tab'] ?? 'footer';
?>
<h1 class="text-3xl font-bold text-gray-800 mb-8">Site Content Management</h1>

<?php if (isset($_GET['success'])): ?>
<div class="bg-green-100 border-l-4 border-green-500 text-green-700 p-4 mb-6" role="alert"><p>Content updated successfully!</p></div>
<?php endif; ?>

<div class="bg-white shadow-md rounded-lg max-w-5xl mx-auto">
    <div class="border-b border-gray-200">
        <nav class="-mb-px flex space-x-8 px-8" aria-label="Tabs">
            <a href="?tab=footer" class="whitespace-nowrap py-4 px-1 border-b-2 font-medium text-sm <?= $active_tab == 'footer' ? 'border-indigo-500 text-indigo-600' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300' ?>">Footer Content</a>
            <a href="?tab=about" class="whitespace-nowrap py-4 px-1 border-b-2 font-medium text-sm <?= $active_tab == 'about' ? 'border-indigo-500 text-indigo-600' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300' ?>">About Us Page</a>
            <a href="?tab=privacy" class="whitespace-nowrap py-4 px-1 border-b-2 font-medium text-sm <?= $active_tab == 'privacy' ? 'border-indigo-500 text-indigo-600' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300' ?>">Privacy Policy Page</a>
        </nav>
    </div>
    <div class="p-8">
        <form action="actions/handle_settings.php" method="post">
            <input type="hidden" name="source_page" value="site_content">
            <input type="hidden" name="active_tab" value="<?= htmlspecialchars($active_tab) ?>">

            <!-- Footer Content Panel -->
            <div class="<?= $active_tab == 'footer' ? '' : 'hidden' ?>">
                <h2 class="text-2xl font-bold text-gray-800 mb-6">Footer Settings</h2>
                <div class="mb-6"><label for="footer_about_us" class="block text-gray-700 font-bold mb-2">About Column</label><textarea id="footer_about_us" name="footer_about_us" rows="4" class="shadow border rounded w-full py-2 px-3"><?= htmlspecialchars($settings['footer_about_us'] ?? '') ?></textarea></div>
                <div class="grid md:grid-cols-2 gap-6 mb-6"><div><label for="footer_address" class="block font-bold mb-2">Address</label><textarea id="footer_address" name="footer_address" rows="3" class="shadow border rounded w-full py-2 px-3"><?= htmlspecialchars($settings['footer_address'] ?? '') ?></textarea></div><div><label for="footer_email" class="block font-bold mb-2">Contact Email</label><input type="email" id="footer_email" name="footer_email" value="<?= htmlspecialchars($settings['footer_email'] ?? '') ?>" class="shadow border rounded w-full py-2 px-3"></div></div>
                <h3 class="text-xl font-bold mb-4 mt-8">Social Media Links</h3>
                <div class="grid md:grid-cols-3 gap-6"><div><label for="footer_social_facebook" class="block font-bold mb-2"><i class="fab fa-facebook-f mr-2"></i> Facebook URL</label><input type="url" id="footer_social_facebook" name="footer_social_facebook" value="<?= htmlspecialchars($settings['footer_social_facebook'] ?? '#') ?>" class="shadow border rounded w-full py-2 px-3"></div><div><label for="footer_social_instagram" class="block font-bold mb-2"><i class="fab fa-instagram mr-2"></i> Instagram URL</label><input type="url" id="footer_social_instagram" name="footer_social_instagram" value="<?= htmlspecialchars($settings['footer_social_instagram'] ?? '#') ?>" class="shadow border rounded w-full py-2 px-3"></div><div><label for="footer_social_twitter" class="block font-bold mb-2"><i class="fab fa-twitter mr-2"></i> Twitter URL</label><input type="url" id="footer_social_twitter" name="footer_social_twitter" value="<?= htmlspecialchars($settings['footer_social_twitter'] ?? '#') ?>" class="shadow border rounded w-full py-2 px-3"></div></div>
            </div>
            
            <!-- About Us Panel -->
            <div class="<?= $active_tab == 'about' ? '' : 'hidden' ?>">
                <h2 class="text-2xl font-bold text-gray-800 mb-6">About Us Page Content</h2>
                <div class="mb-6"><label for="about_us_title" class="block font-bold mb-2">Page Title</label><input type="text" id="about_us_title" name="about_us_title" value="<?= htmlspecialchars($settings['about_us_title'] ?? '') ?>" class="shadow border rounded w-full py-2 px-3"></div>
                <div class="mb-6"><label for="about_us_content" class="block font-bold mb-2">Page Content (HTML allowed)</label><textarea id="about_us_content" name="about_us_content" rows="12" class="shadow border rounded w-full py-2 px-3"><?= htmlspecialchars($settings['about_us_content'] ?? '') ?></textarea></div>
            </div>
            
            <!-- Privacy Policy Panel -->
            <div class="<?= $active_tab == 'privacy' ? '' : 'hidden' ?>">
                <h2 class="text-2xl font-bold text-gray-800 mb-6">Privacy Policy Page Content</h2>
                <div class="mb-6"><label for="privacy_policy_title" class="block font-bold mb-2">Page Title</label><input type="text" id="privacy_policy_title" name="privacy_policy_title" value="<?= htmlspecialchars($settings['privacy_policy_title'] ?? '') ?>" class="shadow border rounded w-full py-2 px-3"></div>
                <div class="mb-6"><label for="privacy_policy_content" class="block font-bold mb-2">Page Content (HTML allowed)</label><textarea id="privacy_policy_content" name="privacy_policy_content" rows="12" class="shadow border rounded w-full py-2 px-3"><?= htmlspecialchars($settings['privacy_policy_content'] ?? '') ?></textarea></div>
            </div>

            <div class="flex justify-end mt-8 border-t pt-6"><button type="submit" class="bg-indigo-600 hover:bg-indigo-700 text-white font-bold py-3 px-6 rounded-lg text-lg shadow-md hover:shadow-lg transition-all"><i class="fas fa-save mr-2"></i>Save Content</button></div>
        </form>
    </div>
</div>
<?php
$conn->close();
require_once 'includes/admin_footer.php';
?>