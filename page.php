<?php
// Use the centralized init file for robust session handling.
require_once 'includes/init.php';
require_once 'includes/db_connect.php';

$slug = $_GET['slug'] ?? '';
$allowed_slugs = ['about-us', 'privacy-policy'];

// Security: If the slug is not one of the allowed pages, redirect to the homepage.
if (!in_array($slug, $allowed_slugs)) {
    header("Location: index.php");
    exit;
}

// Determine the database keys based on the slug.
$key_prefix = str_replace('-', '_', $slug);
$title_key = $key_prefix . '_title';
$content_key = $key_prefix . '_content';

// Fetch the page title and content from the database.
$stmt = $conn->prepare("SELECT setting_key, setting_value FROM site_settings WHERE setting_key = ? OR setting_key = ?");
$stmt->bind_param("ss", $title_key, $content_key);
$stmt->execute();
$result = $stmt->get_result();
$page_content = [];
while ($row = $result->fetch_assoc()) {
    $page_content[$row['setting_key']] = $row['setting_value'];
}
$stmt->close();

// Now that all data is fetched, start rendering the page.
require_once 'includes/header.php';
?>

<div class="bg-white py-12">
    <div class="container mx-auto px-6 lg:px-8 max-w-4xl">
        <h1 class="text-4xl font-extrabold text-gray-900 mb-8 border-b pb-4">
            <?= htmlspecialchars($page_content[$title_key] ?? 'Page Not Found') ?>
        </h1>
        
        <!-- The 'prose' class from TailwindCSS provides beautiful default typography for long-form text. -->
        <div class="prose lg:prose-xl max-w-none text-gray-700">
            <?php // We echo the content directly as it is stored as HTML ?>
            <?= $page_content[$content_key] ?? '<p>The content for this page could not be found.</p>' ?>
        </div>

        <div class="mt-12 border-t pt-8">
            <a href="index.php" class="text-indigo-600 hover:text-indigo-800 font-semibold">
                ← Back to Home
            </a>
        </div>
    </div>
</div>

<?php
// ** THE CORRECTED ORDER **
// 1. First, we include the footer, which needs the open $conn variable.
require_once 'includes/footer.php'; 

// 2. NOW, after the footer has done its work, it is safe to close the connection.
if (isset($conn)) {
    $conn->close();
}
?>