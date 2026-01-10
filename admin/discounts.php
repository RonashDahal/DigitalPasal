<?php
require_once 'includes/admin_header.php';
require_once '../includes/db_connect.php';

$result = $conn->query("SELECT * FROM discounts ORDER BY created_at DESC");
?>
<div class="flex justify-between items-center mb-8">
    <h1 class="text-3xl font-bold text-gray-800">Manage Vouchers & Promos</h1>
    <a href="discount_form.php" class="bg-blue-500 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded">
        <i class="fas fa-plus mr-2"></i> Add New Code
    </a>
</div>
<div class="bg-white shadow-md rounded-lg overflow-x-auto">
    <table class="min-w-full">
        <thead class="bg-gray-200 text-sm">
            <tr>
                <th class="py-3 px-4 text-left">Code</th>
                <th class="py-3 px-4 text-left">Type</th>
                <th class="py-3 px-4 text-right">Value</th>
                <th class="py-3 px-4 text-center">Usage (Used/Limit)</th>
                <th class="py-3 px-4 text-left">Validity</th>
                <th class="py-3 px-4 text-center">Status</th>
                <th class="py-3 px-4 text-center">Actions</th>
            </tr>
        </thead>
        <tbody class="divide-y text-sm">
            <?php while ($d = $result->fetch_assoc()): ?>
            <tr>
                <td class="py-3 px-4 font-mono font-semibold text-indigo-600"><?= htmlspecialchars($d['code']) ?></td>
                <td class="py-3 px-4"><?= ucfirst($d['type']) ?></td>
                <td class="py-3 px-4 text-right font-semibold"><?= $d['type'] == 'percentage' ? $d['value'] . '%' : 'Rs. ' . number_format($d['value'], 2) ?></td>
                <td class="py-3 px-4 text-center"><?= $d['times_used'] ?> / <?= $d['usage_limit'] ?? '∞' ?></td>
                <td class="py-3 px-4"><?= $d['start_date'] ? date('M j, Y', strtotime($d['start_date'])) : 'N/A' ?> - <?= $d['end_date'] ? date('M j, Y', strtotime($d['end_date'])) : 'N/A' ?></td>
                <td class="py-3 px-4 text-center">
                    <?= $d['is_active'] ? '<span class="px-2 py-1 font-semibold text-xs rounded-full bg-green-200 text-green-800">Active</span>' : '<span class="px-2 py-1 font-semibold text-xs rounded-full bg-red-200 text-red-800">Inactive</span>' ?>
                </td>
                <td class="py-3 px-4 text-center">
                    <a href="discount_form.php?id=<?= $d['id'] ?>" class="text-gray-500 hover:text-indigo-600"><i class="fas fa-pencil-alt"></i></a>
                </td>
            </tr>
            <?php endwhile; ?>
        </tbody>
    </table>
</div>
<?php $conn->close(); require_once 'includes/admin_footer.php'; ?>