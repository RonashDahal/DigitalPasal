<?php
// This line prevents any PHP warnings from breaking the JSON output.
error_reporting(0);
ini_set('display_errors', 0);

// Use the robust __DIR__ constant to ensure the path is always correct.
// This goes up two directories from /admin/actions/ to the root, then into /includes/.
require_once __DIR__ . '/../../includes/init.php';
require_once __DIR__ . '/../../includes/db_connect.php';

// Set the content type to JSON for all responses from this file.
header('Content-Type: application/json');

// Security check: Ensure the user is a logged-in administrator.
if (!isset($_SESSION["loggedin"]) || $_SESSION["role"] !== 'admin') {
    // Exit with a JSON error message if not authorized.
    echo json_encode(['success' => false, 'message' => 'Access Denied']);
    exit;
}

// --- Date Range Calculation ---
$range = $_GET['range'] ?? '7d';
$timezone = new DateTimeZone('UTC'); // Use a consistent timezone for all calculations.

/**
 * Calculates the current and previous date ranges based on a key.
 * @param string $range_key The key like '7d', '30d', etc.
 * @param DateTimeZone $tz The timezone object.
 * @return array An associative array with start and end dates.
 */
function get_date_ranges(string $range_key, DateTimeZone $tz): array {
    $now = new DateTime('now', $tz);
    $current_end = (clone $now)->setTime(23, 59, 59);

    switch ($range_key) {
        case '30d':
            $current_start = (clone $now)->modify('-29 days')->setTime(0, 0, 0);
            $previous_start = (clone $current_start)->modify('-30 days');
            $previous_end = (clone $current_start)->modify('-1 day')->setTime(23, 59, 59);
            break;
        case 'this_month':
            $current_start = (clone $now)->modify('first day of this month')->setTime(0, 0, 0);
            $previous_start = (clone $now)->modify('first day of last month')->setTime(0, 0, 0);
            $previous_end = (clone $now)->modify('last day of last month')->setTime(23, 59, 59);
            break;
        case 'last_month':
            $current_start = (clone $now)->modify('first day of last month')->setTime(0, 0, 0);
            $current_end = (clone $now)->modify('last day of last month')->setTime(23, 59, 59);
            $previous_start = (clone $current_start)->modify('-1 month');
            $previous_end = (clone $current_start)->modify('-1 day')->setTime(23, 59, 59);
            break;
        case 'this_year':
            $current_start = (clone $now)->modify('first day of january this year')->setTime(0, 0, 0);
            $previous_start = (clone $current_start)->modify('-1 year');
            $previous_end = (clone $current_start)->modify('-1 day')->setTime(23, 59, 59);
            break;
        case 'all_time':
            $current_start = new DateTime('2000-01-01', $tz);
            $previous_start = clone $current_start;
            $previous_end = clone $current_start;
            break;
        case '7d':
        default:
            $current_start = (clone $now)->modify('-6 days')->setTime(0, 0, 0);
            $previous_start = (clone $current_start)->modify('-7 days');
            $previous_end = (clone $current_start)->modify('-1 day')->setTime(23, 59, 59);
            break;
    }
    return [
        'current_start' => $current_start->format('Y-m-d H:i:s'),
        'current_end' => $current_end->format('Y-m-d H:i:s'),
        'previous_start' => $previous_start->format('Y-m-d H:i:s'),
        'previous_end' => $previous_end->format('Y-m-d H:i:s'),
    ];
}

$dates = get_date_ranges($range, $timezone);

/**
 * Fetches the key performance indicators for a given date range.
 * @param mysqli $conn The database connection.
 * @param string $start The start date.
 * @param string $end The end date.
 * @return array An array of calculated stats.
 */
function get_stats_for_period(mysqli $conn, string $start, string $end): array {
    $sql_sales = "SELECT 
                    SUM(quantity_sold * price_per_item) as total_sales, 
                    COUNT(DISTINCT order_id) as total_orders
                FROM sales_ledger 
                WHERE transaction_date BETWEEN ? AND ?";
    $stmt_sales = $conn->prepare($sql_sales);
    $stmt_sales->bind_param("ss", $start, $end);
    $stmt_sales->execute();
    $sales_data = $stmt_sales->get_result()->fetch_assoc();
    $stmt_sales->close();

    $sql_customers = "SELECT COUNT(id) as new_customers FROM users WHERE role = 'customer' AND created_at BETWEEN ? AND ?";
    $stmt_customers = $conn->prepare($sql_customers);
    $stmt_customers->bind_param("ss", $start, $end);
    $stmt_customers->execute();
    $customers_data = $stmt_customers->get_result()->fetch_assoc();
    $stmt_customers->close();

    $total_sales = (float)($sales_data['total_sales'] ?? 0);
    $total_orders = (int)($sales_data['total_orders'] ?? 0);

    return [
        'total_sales' => $total_sales,
        'total_orders' => $total_orders,
        'avg_order_value' => ($total_orders > 0) ? $total_sales / $total_orders : 0,
        'new_customers' => (int)($customers_data['new_customers'] ?? 0),
    ];
}

/**
 * Calculates the percentage change between two values.
 * @param float $current The current period's value.
 * @param float $previous The previous period's value.
 * @return float The percentage change.
 */
function calculate_comparison(float $current, float $previous): float {
    if ($previous == 0) {
        return ($current > 0) ? 100.0 : 0.0;
    }
    return (($current - $previous) / $previous) * 100;
}

// --- Main Execution ---
$current_stats = get_stats_for_period($conn, $dates['current_start'], $dates['current_end']);
$previous_stats = ($range !== 'all_time') ? get_stats_for_period($conn, $dates['previous_start'], $dates['previous_end']) : $current_stats;

$comparison_data = [
    'sales_comparison' => calculate_comparison($current_stats['total_sales'], $previous_stats['total_sales']),
    'orders_comparison' => calculate_comparison($current_stats['total_orders'], $previous_stats['total_orders']),
    'aov_comparison' => calculate_comparison($current_stats['avg_order_value'], $previous_stats['avg_order_value']),
    'customers_comparison' => calculate_comparison($current_stats['new_customers'], $previous_stats['new_customers']),
];

// Prepare Chart Data
$chart_labels = [];
$chart_data = [];
$group_by_format = ($range === 'this_year' || $range === 'all_time') ? '%Y-%m' : '%Y-%m-%d';
$sql_chart = "SELECT 
                DATE_FORMAT(transaction_date, ?) as period, 
                SUM(quantity_sold * price_per_item) as period_total 
            FROM sales_ledger 
            WHERE transaction_date BETWEEN ? AND ? 
            GROUP BY period ORDER BY period ASC";
$stmt_chart = $conn->prepare($sql_chart);
$stmt_chart->bind_param("sss", $group_by_format, $dates['current_start'], $dates['current_end']);
$stmt_chart->execute();
$chart_result = $stmt_chart->get_result();
while ($row = $chart_result->fetch_assoc()) {
    $chart_labels[] = $row['period'];
    $chart_data[] = (float)$row['period_total'];
}
$stmt_chart->close();

// Final JSON Output
echo json_encode([
    'success' => true,
    'stats' => $current_stats,
    'comparison' => $comparison_data,
    'chart' => [
        'labels' => $chart_labels,
        'data' => $chart_data
    ]
]);

$conn->close();
exit(); // Explicitly stop the script after sending the JSON response.
?>