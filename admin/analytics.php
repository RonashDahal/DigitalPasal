<?php
require_once 'includes/admin_header.php';
?>

<div class="flex justify-between items-center mb-8">
    <h1 class="text-3xl font-bold text-gray-800">Store Analytics</h1>
    <div>
        <label for="date-range-selector" class="text-sm font-medium text-gray-700 mr-2">Filter by:</label>
        <select id="date-range-selector" class="p-2 border rounded-lg shadow-sm focus:outline-none focus:ring-2 focus:ring-indigo-500">
            <option value="7d" selected>Last 7 Days</option>
            <option value="30d">Last 30 Days</option>
            <option value="this_month">This Month</option>
            <option value="last_month">Last Month</option>
            <option value="this_year">This Year</option>
            <option value="all_time">All Time</option>
        </select>
    </div>
</div>

<!-- Loading Spinner -->
<div id="analytics-loading-spinner" class="text-center p-12">
    <i class="fas fa-spinner fa-spin fa-3x text-indigo-600"></i>
</div>

<!-- Main Analytics Content (Initially hidden) -->
<div id="analytics-content" class="hidden space-y-8">
    <!-- Interactive Stat Cards -->
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6">
        <div class="bg-white p-6 rounded-lg shadow-md"><p class="text-sm text-gray-600">Total Sales</p><p id="stat-total-sales" class="text-3xl font-bold text-gray-900">Rs. 0.00</p><div id="stat-sales-comparison" class="text-sm font-medium mt-1"></div></div>
        <div class="bg-white p-6 rounded-lg shadow-md"><p class="text-sm text-gray-600">Orders</p><p id="stat-total-orders" class="text-3xl font-bold text-gray-900">0</p><div id="stat-orders-comparison" class="text-sm font-medium mt-1"></div></div>
        <div class="bg-white p-6 rounded-lg shadow-md"><p class="text-sm text-gray-600">Avg. Order Value</p><p id="stat-avg-order-value" class="text-3xl font-bold text-gray-900">Rs. 0.00</p><div id="stat-aov-comparison" class="text-sm font-medium mt-1"></div></div>
        <div class="bg-white p-6 rounded-lg shadow-md"><p class="text-sm text-gray-600">New Customers</p><p id="stat-new-customers" class="text-3xl font-bold text-gray-900">0</p><div id="stat-customers-comparison" class="text-sm font-medium mt-1"></div></div>
    </div>

    <!-- Sales Chart -->
    <div class="bg-white p-6 rounded-lg shadow-md">
        <h2 class="text-xl font-bold mb-4 text-gray-800">Sales Overview</h2>
        <div><canvas id="sales-chart"></canvas></div>
    </div>
</div>

<!-- Chart.js CDN -->
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<!-- Page-specific JavaScript -->
<script>
document.addEventListener('DOMContentLoaded', function() {
    const selector = document.getElementById('date-range-selector');
    const spinner = document.getElementById('analytics-loading-spinner');
    const content = document.getElementById('analytics-content');
    let salesChart = null; // To hold the chart instance

    function formatComparison(value, element) {
        if (!element) return; // Safety check
        let text = '';
        let colorClass = 'text-gray-500';
        if (value > 0) {
            text = `↑ ${value.toFixed(1)}% vs previous period`;
            colorClass = 'text-green-600';
        } else if (value < 0) {
            text = `↓ ${Math.abs(value).toFixed(1)}% vs previous period`;
            colorClass = 'text-red-500';
        } else {
            text = `No change vs previous period`;
        }
        element.textContent = text;
        element.className = `text-sm font-medium mt-1 ${colorClass}`;
    }

    async function fetchAnalytics(range) {
        spinner.style.display = 'block';
        content.classList.add('hidden');

        try {
            const response = await fetch(`actions/get_analytics_data.php?range=${range}`);
            // Check if the response is OK (status in the range 200-299)
            if (!response.ok) {
                throw new Error(`Network response was not ok, status: ${response.status}`);
            }
            const data = await response.json();

            if (data.success) {
                // ** THE FIX IS HERE: We ensure all IDs are correct and data is populated safely **
                document.getElementById('stat-total-sales').textContent = `Rs. ${data.stats.total_sales.toLocaleString(undefined, {minimumFractionDigits: 2, maximumFractionDigits: 2})}`;
                document.getElementById('stat-total-orders').textContent = data.stats.total_orders.toLocaleString();
                document.getElementById('stat-avg-order-value').textContent = `Rs. ${data.stats.avg_order_value.toLocaleString(undefined, {minimumFractionDigits: 2, maximumFractionDigits: 2})}`;
                document.getElementById('stat-new-customers').textContent = data.stats.new_customers.toLocaleString();

                formatComparison(data.comparison.sales_comparison, document.getElementById('stat-sales-comparison'));
                formatComparison(data.comparison.orders_comparison, document.getElementById('stat-orders-comparison'));
                formatComparison(data.comparison.aov_comparison, document.getElementById('stat-aov-comparison'));
                formatComparison(data.comparison.customers_comparison, document.getElementById('stat-customers-comparison'));

                const ctx = document.getElementById('sales-chart').getContext('2d');
                if (salesChart) {
                    salesChart.destroy();
                }
                salesChart = new Chart(ctx, {
                    type: 'bar',
                    data: {
                        labels: data.chart.labels,
                        datasets: [{
                            label: 'Sales (Rs. )',
                            data: data.chart.data,
                            backgroundColor: 'rgba(79, 70, 229, 0.6)',
                            borderColor: 'rgba(79, 70, 229, 1)',
                            borderWidth: 1,
                            borderRadius: 4
                        }]
                    },
                    options: {
                        responsive: true,
                        scales: { y: { beginAtZero: true, ticks: { callback: value => `Rs. ${value.toLocaleString()}` } } },
                        plugins: { legend: { display: false } }
                    }
                });
            } else {
                throw new Error(data.message || 'The API returned an error.');
            }
        } catch (error) {
            console.error("Analytics Fetch Error:", error);
            content.innerHTML = `<div class="bg-red-100 border-l-4 border-red-500 text-red-700 p-4" role="alert"><p class="font-bold">Error</p><p>Could not load analytics data. The server might have an error. Please check the browser console (F12) for more details.</p></div>`;
        } finally {
            spinner.style.display = 'none';
            content.classList.remove('hidden');
        }
    }

    selector.addEventListener('change', () => {
        fetchAnalytics(selector.value);
    });

    fetchAnalytics(selector.value);
});
</script>

<?php
require_once 'includes/admin_footer.php';
?>