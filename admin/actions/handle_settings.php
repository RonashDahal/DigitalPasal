<?php
/**
 * admin/actions/handle_settings.php
 * Universal Settings Handler: Manages Store Info, eSewa Config, and Image Uploads
 */

if (session_status() === PHP_SESSION_NONE) { session_start(); }
require_once '../../includes/db_connect.php';

// 1. SECURITY CHECK: Ensure only logged-in admins can run this script
if (!isset($_SESSION["loggedin"]) || $_SESSION["role"] !== 'admin') { 
    die("Access Denied: Unauthorized access attempt recorded."); 
}

/**
 * Helper function to update or insert a setting key-value pair
 */
function update_setting($conn, $key, $value) {
    // We use ON DUPLICATE KEY UPDATE so we don't have to check if the key exists first
    $sql = "INSERT INTO site_settings (setting_key, setting_value) 
            VALUES (?, ?) 
            ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value)";
    
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ss", $key, $value);
    $stmt->execute();
    $stmt->close();
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {

    // 2. WHITELIST: Define which keys are allowed to be saved in the database
    $allowed_settings = [
        'esewa_id', 
        'shipping_charge', 
        'hero_title', 
        'hero_description', 
        'footer_about_us', 
        'footer_address', 
        'footer_email',
        'footer_social_facebook', 
        'footer_social_instagram', 
        'footer_social_twitter',
        'about_us_title', 
        'about_us_content',
        'privacy_policy_title', 
        'privacy_policy_content'
    ];

    // 3. PROCESS TEXT FIELDS
    foreach ($_POST as $key => $value) {
        if (in_array($key, $allowed_settings)) {
            // We allow HTML for page content, but trim others
            $final_value = ($key == 'about_us_content' || $key == 'privacy_policy_content') ? $value : trim($value);
            update_setting($conn, $key, $final_value);
        }
    }

    // 4. PROCESS FILE UPLOADS (eSewa QR Code)
    if (isset($_FILES['esewa_qr_file']) && $_FILES['esewa_qr_file']['error'] === UPLOAD_ERR_OK) {
        $file = $_FILES['esewa_qr_file'];
        $target_dir = "../../uploads/";
        
        // Use time prefix to prevent browser caching issues
        $imageFileType = strtolower(pathinfo($file["name"], PATHINFO_EXTENSION));
        $new_file_name = "esewa_qr_" . time() . "." . $imageFileType;
        $target_file = $target_dir . $new_file_name;

        // Simple image validation
        $check = getimagesize($file["tmp_name"]);
        if ($check !== false) {
            if (move_uploaded_file($file["tmp_name"], $target_file)) {
                // Save the path relative to the root folder
                update_setting($conn, 'esewa_qr', "uploads/" . $new_file_name);
            }
        }
    }

    // 5. PROCESS FILE UPLOADS (Hero Image)
    if (isset($_FILES['hero_image']) && $_FILES['hero_image']['error'] === UPLOAD_ERR_OK) {
        $file = $_FILES['hero_image'];
        $target_dir = "../../uploads/";
        $imageFileType = strtolower(pathinfo($file["name"], PATHINFO_EXTENSION));
        $new_file_name = "hero_banner_" . time() . "." . $imageFileType;
        $target_file = $target_dir . $new_file_name;

        if (getimagesize($file["tmp_name"]) && move_uploaded_file($file["tmp_name"], $target_file)) {
            update_setting($conn, 'hero_image', "uploads/" . $new_file_name);
        }
    }

    // 6. REDIRECTION LOGIC
    // We check where the user came from so we can send them back to the right page
    $source_page = $_POST['source_page'] ?? 'theme_editor';
    $active_tab = $_POST['active_tab'] ?? '';

    // Build redirect URL
    $redirect_url = "../" . $source_page . ".php?success=true";
    if (!empty($active_tab)) {
        $redirect_url .= "&tab=" . urlencode($active_tab);
    }
    
    $conn->close();
    header("Location: " . $redirect_url);
    exit();
}
?>