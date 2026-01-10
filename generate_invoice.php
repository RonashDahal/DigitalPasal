<?php
// Use the centralized init file for robust session handling
require_once 'includes/init.php'; 
require_once 'includes/db_connect.php';

// Include BOTH required libraries
require_once 'libs/fpdf/fpdf.php'; 
require_once 'libs/phpqrcode/qrlib.php'; 

// Security: User must be logged in
if (!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true) {
    header("location: login.php");
    exit;
}

$order_id = isset($_GET['order_id']) ? (int)$_GET['order_id'] : 0;
$user_id = $_SESSION['id'];

if ($order_id <= 0) {
    die('Invalid Order ID.');
}

// --- Fetch all necessary order data from the database ---
$stmt = $conn->prepare("SELECT o.*, u.name, u.email FROM orders o JOIN users u ON o.user_id = u.id WHERE o.id = ? AND o.user_id = ?");
$stmt->bind_param("ii", $order_id, $user_id);
$stmt->execute();
$order_result = $stmt->get_result();
$order = $order_result->fetch_assoc();
$stmt->close();

if (!$order) {
    die('Order not found or you do not have permission to view this invoice.');
}

$items_stmt = $conn->prepare("SELECT oi.quantity, oi.price_per_item, p.name as product_name FROM order_items oi JOIN products p ON oi.product_id = p.id WHERE oi.order_id = ?");
$items_stmt->bind_param("i", $order_id);
$items_stmt->execute();
$items_result = $items_stmt->get_result();
$conn->close();

// --- 1. QR CODE LOGIC ---
$qr_code_path = 'uploads/qr_codes/';
if (!is_dir($qr_code_path)) {
    mkdir($qr_code_path, 0755, true);
}
$qr_code_file = $qr_code_path . 'invoice_' . $order_id . '.png';

$qr_code_data = "Order ID: " . $order['id'] . "\n";
$qr_code_data .= "Customer: " . $order['shipping_name'] . "\n";
$qr_code_data .= "Date: " . date('Y-m-d', strtotime($order['created_at'])) . "\n";
$qr_code_data .= "Total Paid: $" . number_format($order['grand_total'], 2);

QRcode::png($qr_code_data, $qr_code_file);


// --- 2. PDF GENERATION using FPDF ---

class PDF extends FPDF {
    function Header() {
        $this->SetFont('Arial', 'B', 20);
        $this->Cell(80);
        $this->Cell(30, 10, 'INVOICE', 0, 0, 'C');
        $this->Ln(20);
    }
    function Footer() {
        $this->SetY(-15);
        $this->SetFont('Arial', 'I', 8);
        $this->Cell(0, 10, 'Thank you for your purchase!', 0, 0, 'C');
    }
    function InfoCell($label, $value) {
        $this->SetFont('Arial', 'B', 10);
        $this->Cell(40, 6, $label, 0, 0);
        $this->SetFont('Arial', '', 10);
        $this->Cell(0, 6, $value, 0, 1);
    }
    function ItemsTable($header, $data) {
        $this->SetFillColor(240, 240, 240);
        $this->SetTextColor(0);
        $this->SetDrawColor(220, 220, 220);
        $this->SetLineWidth(.3);
        $this->SetFont('', 'B', 10);
        $w = array(100, 30, 30, 30);
        for($i=0; $i<count($header); $i++)
            $this->Cell($w[$i], 7, $header[$i], 1, 0, 'C', true);
        $this->Ln();
        $this->SetFont('');
        foreach($data as $row) {
            $this->Cell($w[0], 6, $row[0], 'LR', 0, 'L');
            $this->Cell($w[1], 6, $row[1], 'LR', 0, 'C');
            $this->Cell($w[2], 6, 'Rs. ' . number_format($row[2], 2), 'LR', 0, 'R');
            $this->Cell($w[3], 6, 'Rs. ' . number_format($row[3], 2), 'LR', 0, 'R');
            $this->Ln();
        }
        $this->Cell(array_sum($w), 0, '', 'T');
    }
}

$pdf = new PDF();
$pdf->AddPage();

$pdf->Image($qr_code_file, 170, 10, 30, 30);

// --- Invoice Details Section ---
$pdf->SetFont('Arial', 'B', 12);
$pdf->Cell(0, 8, 'Invoice Details', 0, 1);
$pdf->SetFont('Arial', '', 10);
$pdf->InfoCell('Invoice #:', 'INV-' . str_pad($order['id'], 6, '0', STR_PAD_LEFT));
$pdf->InfoCell('Order ID:', '#' . $order['id']);
$pdf->InfoCell('Order Date:', date('F j, Y', strtotime($order['created_at'])));
$pdf->InfoCell('Payment Method:', strtoupper($order['payment_method']));
$pdf->Ln(10);

// --- ** THE DEFINITIVE FIX FOR THE OVERLAPPING TEXT ** ---
// This new section correctly handles the layout for both columns.
$pdf->SetFont('Arial', 'B', 12);
$pdf->Cell(95, 8, 'Billed To', 0, 0);
$pdf->Cell(95, 8, 'Shipped To', 0, 1);
$pdf->Line($pdf->GetX(), $pdf->GetY(), $pdf->GetX() + 190, $pdf->GetY());
$pdf->Ln(2);

// Prepare the text content for both columns
$billed_to_text = htmlspecialchars_decode($order['name']) . "\n" . htmlspecialchars_decode($order['email']);
$shipped_to_text = htmlspecialchars_decode($order['shipping_name']) . "\n" . htmlspecialchars_decode($order['shipping_address']);

// Get the current Y position before writing the cells
$y_pos_before = $pdf->GetY();

// Write the "Billed To" information using MultiCell
$pdf->SetFont('Arial', '', 10);
$pdf->MultiCell(95, 6, $billed_to_text, 0, 'L');

// Set the position for the "Shipped To" column to be next to the "Billed To" column
$pdf->SetXY(105, $y_pos_before); // 105 = 10 (left margin) + 95 (width of first column)

// Write the "Shipped To" information using MultiCell
$pdf->MultiCell(95, 6, $shipped_to_text, 0, 'L');

// Determine the maximum Y position after both MultiCells have been drawn
$y_pos_after = max($pdf->GetY(), $y_pos_before + 20); // Add a minimum height to prevent overlap
$pdf->SetY($y_pos_after);
$pdf->Ln(5);
// --- END OF FIX ---


// --- Items Table ---
$pdf->SetFont('Arial', 'B', 12);
$pdf->Cell(0, 8, 'Order Summary', 0, 1);
$table_header = array('Product Name', 'Quantity', 'Unit Price', 'Total');
$table_data = [];
while ($item = $items_result->fetch_assoc()) {
    $table_data[] = [
        htmlspecialchars_decode($item['product_name']),
        $item['quantity'],
        $item['price_per_item'],
        $item['quantity'] * $item['price_per_item']
    ];
}
$pdf->ItemsTable($table_header, $table_data);
$pdf->Ln(5);

// --- Totals Section ---
$pdf->SetFont('Arial', '', 10);
$pdf->Cell(130, 6, 'Subtotal:', 0, 0, 'R');
$pdf->Cell(60, 6, 'Rs. ' . number_format($order['total_amount'], 2), 0, 1, 'R');

if ($order['discount_amount'] > 0) {
    $pdf->Cell(130, 6, 'Discount (' . htmlspecialchars_decode($order['discount_code']) . '):', 0, 0, 'R');
    $pdf->Cell(60, 6, '-Rs. ' . number_format($order['discount_amount'], 2), 0, 1, 'R');
}

$pdf->SetFont('Arial', 'B', 12);
$pdf->Cell(130, 8, 'Grand Total:', 0, 0, 'R');
$pdf->SetFillColor(240, 240, 240);
$pdf->Cell(60, 8, 'Rs. ' . number_format($order['grand_total'], 2), 0, 1, 'R', true);

// --- Output the PDF ---
$pdf->Output('D', 'Invoice-'.$order['id'].'.pdf');

// --- Cleanup ---
if (file_exists($qr_code_file)) {
    unlink($qr_code_file);
}
exit;
?>