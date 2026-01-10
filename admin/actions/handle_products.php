<?php
session_start();
require_once '../../includes/db_connect.php';

// Check admin auth
if (!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true || $_SESSION["role"] !== 'admin') {
    die("Access Denied");
}

function handle_image_upload($file) {
    if ($file['error'] === UPLOAD_ERR_NO_FILE) {
        return null;
    }
    $target_dir = "../../uploads/";
    $target_file = $target_dir . basename($file["name"]);
    if (getimagesize($file["tmp_name"]) === false) { die("File is not an image."); }
    if ($file["size"] > 5000000) { die("Sorry, your file is too large."); }
    $imageFileType = strtolower(pathinfo($target_file, PATHINFO_EXTENSION));
    if($imageFileType != "jpg" && $imageFileType != "png" && $imageFileType != "jpeg" && $imageFileType != "gif" ) {
        die("Sorry, only JPG, JPEG, PNG & GIF files are allowed.");
    }
    if (move_uploaded_file($file["tmp_name"], $target_file)) {
        return "uploads/" . basename($file["name"]);
    } else {
        die("Sorry, there was an error uploading your file.");
    }
}

// ADD Product - CORRECTED
if ($_SERVER["REQUEST_METHOD"] == "POST" && $_POST['action'] == 'add') {
    $name = $_POST['name'];
    $description = $_POST['description'];
    $category_id = !empty($_POST['category_id']) ? $_POST['category_id'] : null;
    $price = $_POST['price'];
    $stock_quantity = $_POST['stock_quantity'];
    $image_url = handle_image_upload($_FILES['image']);

    if (!$image_url) { die("Image upload is required for new products."); }

    $sql = "INSERT INTO products (name, description, price, stock_quantity, image_url, category_id) VALUES (?, ?, ?, ?, ?, ?)";
    $stmt = $conn->prepare($sql);
    // Correct bind param: s (string), s, d (double/decimal), i (integer), s, i
    $stmt->bind_param("ssdisi", $name, $description, $price, $stock_quantity, $image_url, $category_id);
    if ($stmt->execute()) {
        header("Location: ../products.php");
    } else {
        echo "Error: " . $stmt->error;
    }
    $stmt->close();
}

// EDIT Product - CORRECTED
if ($_SERVER["REQUEST_METHOD"] == "POST" && $_POST['action'] == 'edit') {
    $id = $_POST['id'];
    $name = $_POST['name'];
    $description = $_POST['description'];
    $category_id = !empty($_POST['category_id']) ? $_POST['category_id'] : null;
    $price = $_POST['price'];
    $stock_quantity = $_POST['stock_quantity'];

    $new_image_url = handle_image_upload($_FILES['image']);
    
    if ($new_image_url) {
        // With new image
        $sql = "UPDATE products SET name=?, description=?, price=?, stock_quantity=?, image_url=?, category_id=? WHERE id=?";
        $stmt = $conn->prepare($sql);
        // Correct bind param: s, s, d, i, s, i, i
        $stmt->bind_param("ssdsisi", $name, $description, $price, $stock_quantity, $new_image_url, $category_id, $id);
    } else {
        // Without new image - THIS HAD THE TYPO
        $sql = "UPDATE products SET name=?, description=?, price=?, stock_quantity=?, category_id=? WHERE id=?";
        $stmt = $conn->prepare($sql);
        // Correct bind param: s, s, d, i, i, i - The previous 'ssdi_i_i' was invalid.
        $stmt->bind_param("ssdiii", $name, $description, $price, $stock_quantity, $category_id, $id);
    }

    if ($stmt->execute()) {
        header("Location: ../products.php");
    } else {
        echo "Error: " . $stmt->error;
    }
    $stmt->close();
}

// DELETE Product
if ($_SERVER["REQUEST_METHOD"] == "GET" && isset($_GET['action']) && $_GET['action'] == 'delete' && isset($_GET['id'])) {
    $id = $_GET['id'];
    $sql = "DELETE FROM products WHERE id=?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $id);
    if ($stmt->execute()) {
        header("Location: ../products.php");
    } else {
        echo "Error: " . $stmt->error;
    }
    $stmt->close();
}

$conn->close();
?>