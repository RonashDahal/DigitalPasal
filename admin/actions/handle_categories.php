<?php
/**
 * admin/actions/handle_categories.php
 * FIXED: Placeholder and Type String Mismatch
 */
if (session_status() === PHP_SESSION_NONE) { session_start(); }

if (!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true || $_SESSION["role"] !== 'admin') {
    die("Access Denied");
}

require_once '../../includes/db_connect.php';

// Function to handle the image upload
function handle_category_image_upload($file) {
    if ($file['error'] === UPLOAD_ERR_NO_FILE) return null;
    $target_dir = "../../uploads/categories/";
    if (!is_dir($target_dir)) { mkdir($target_dir, 0755, true); }
    $imageFileType = strtolower(pathinfo($file["name"], PATHINFO_EXTENSION));
    $target_file = $target_dir . time() . '_' . uniqid() . '.' . $imageFileType;
    if (getimagesize($file["tmp_name"]) === false) die("File is not an image.");
    if (move_uploaded_file($file["tmp_name"], $target_file)) {
        return "uploads/categories/" . basename($target_file);
    }
    return null;
}

// ---------------------------------------------------------
// 1. ADD Category
// ---------------------------------------------------------
if ($_SERVER["REQUEST_METHOD"] == "POST" && $_POST['action'] == 'add') {
    $name = $_POST['name'];
    $description = $_POST['description'];
    $is_active = (int)$_POST['is_active'];
    $products_per_page = (int)$_POST['products_per_page'];
    $default_sort_order = $_POST['default_sort_order'];
    $image_url = handle_category_image_upload($_FILES['image']);
    
    $allow_cod = isset($_POST['allow_cod']) ? 1 : 0;
    $allow_esewa = isset($_POST['allow_esewa']) ? 1 : 0;

    // Total 8 placeholders
    $sql = "INSERT INTO categories (name, description, image_url, is_active, products_per_page, default_sort_order, allow_cod, allow_esewa) VALUES (?, ?, ?, ?, ?, ?, ?, ?)";
    $stmt = $conn->prepare($sql);
    
    // Total 8 type chars: sssiisii
    $stmt->bind_param("sssiisii", $name, $description, $image_url, $is_active, $products_per_page, $default_sort_order, $allow_cod, $allow_esewa);
    
    if ($stmt->execute()) {
        header("Location: ../categories.php?success=add");
    } else {
        echo "Error: " . $stmt->error;
    }
    $stmt->close();
}

// ---------------------------------------------------------
// 2. EDIT Category
// ---------------------------------------------------------
if ($_SERVER["REQUEST_METHOD"] == "POST" && $_POST['action'] == 'edit') {
    $id = (int)$_POST['id'];
    $name = $_POST['name'];
    $description = $_POST['description'];
    $is_active = (int)$_POST['is_active'];
    $products_per_page = (int)$_POST['products_per_page'];
    $default_sort_order = $_POST['default_sort_order'];
    
    $allow_cod = isset($_POST['allow_cod']) ? 1 : 0;
    $allow_esewa = isset($_POST['allow_esewa']) ? 1 : 0;
    
    $new_image_url = handle_category_image_upload($_FILES['image']);

    if ($new_image_url) {
        // CASE: Update WITH new image (9 placeholders)
        $sql = "UPDATE categories SET name=?, description=?, image_url=?, is_active=?, products_per_page=?, default_sort_order=?, allow_cod=?, allow_esewa=? WHERE id=?";
        $stmt = $conn->prepare($sql);
        // Total 9 type chars: sssiisiii
        $stmt->bind_param("sssiisiii", $name, $description, $new_image_url, $is_active, $products_per_page, $default_sort_order, $allow_cod, $allow_esewa, $id);
    } else {
        // CASE: Update WITHOUT new image (8 placeholders)
        $sql = "UPDATE categories SET name=?, description=?, is_active=?, products_per_page=?, default_sort_order=?, allow_cod=?, allow_esewa=? WHERE id=?";
        $stmt = $conn->prepare($sql);
        // Total 8 type chars: ssiisiii
        $stmt->bind_param("ssiisiii", $name, $description, $is_active, $products_per_page, $default_sort_order, $allow_cod, $allow_esewa, $id);
    }
    
    if ($stmt->execute()) {
        header("Location: ../categories.php?success=edit");
    } else {
        echo "Error: " . $stmt->error;
    }
    $stmt->close();
}

// ---------------------------------------------------------
// 3. DELETE Category
// ---------------------------------------------------------
if ($_SERVER["REQUEST_METHOD"] == "GET" && $_GET['action'] == 'delete' && isset($_GET['id'])) {
    $id = (int)$_GET['id'];
    $sql = "DELETE FROM categories WHERE id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $id);
    if ($stmt->execute()) {
        header("Location: ../categories.php?success=delete");
    } else {
        echo "Error: " . $stmt->error;
    }
    $stmt->close();
}

$conn->close();
?>