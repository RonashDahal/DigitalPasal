<?php
if (session_status() === PHP_SESSION_NONE) { session_start(); }
if (!isset($_SESSION["loggedin"]) || $_SESSION["role"] !== 'admin') { die("Access Denied"); }
require_once '../../includes/db_connect.php';

$action = $_POST['action'];
$code = trim($_POST['code']);
$type = $_POST['type'];
$value = $_POST['value'];
$start_date = !empty($_POST['start_date']) ? $_POST['start_date'] : null;
$end_date = !empty($_POST['end_date']) ? $_POST['end_date'] : null;
$usage_limit = !empty($_POST['usage_limit']) ? (int)$_POST['usage_limit'] : null;
$is_active = (int)$_POST['is_active'];

if ($action == 'add') {
    $sql = "INSERT INTO discounts (code, type, value, start_date, end_date, usage_limit, is_active) VALUES (?, ?, ?, ?, ?, ?, ?)";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ssdssii", $code, $type, $value, $start_date, $end_date, $usage_limit, $is_active);
} elseif ($action == 'edit') {
    $id = (int)$_POST['id'];
    $sql = "UPDATE discounts SET code=?, type=?, value=?, start_date=?, end_date=?, usage_limit=?, is_active=? WHERE id=?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ssdssiii", $code, $type, $value, $start_date, $end_date, $usage_limit, $is_active, $id);
}

if ($stmt->execute()) {
    header("Location: ../discounts.php");
} else {
    echo "Error: " . $stmt->error;
}
$stmt->close();
$conn->close();
?>