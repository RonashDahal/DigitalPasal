<?php
require_once 'includes/admin_header.php';
require_once '../includes/db_connect.php';
$is_edit = false;
$d = ['id' => '', 'code' => '', 'type' => 'percentage', 'value' => '', 'start_date' => '', 'end_date' => '', 'usage_limit' => '', 'is_active' => 1];
if (isset($_GET['id'])) {
    $is_edit = true;
    $stmt = $conn->prepare("SELECT * FROM discounts WHERE id = ?");
    $stmt->bind_param("i", $_GET['id']);
    $stmt->execute();
    $d = $stmt->get_result()->fetch_assoc();
    $stmt->close();
}
?>
<h1 class="text-3xl font-bold text-gray-800 mb-8"><?= $is_edit ? 'Edit' : 'Create'; ?> Discount Code</h1>
<form action="actions/handle_discounts.php" method="POST" class="bg-white p-8 rounded-lg shadow-md max-w-2xl mx-auto space-y-6">
    <input type="hidden" name="action" value="<?= $is_edit ? 'edit' : 'add'; ?>">
    <?php if ($is_edit) echo '<input type="hidden" name="id" value="' . $d['id'] . '">'; ?>
    
    <div>
        <label for="code" class="block font-bold mb-1">Discount Code</label>
        <input type="text" name="code" id="code" value="<?= htmlspecialchars($d['code']) ?>" class="w-full p-2 border rounded" required>
        <p class="text-xs text-gray-500 mt-1">Customers will enter this code at checkout. e.g., SUMMER20</p>
    </div>
    <div class="grid grid-cols-2 gap-6">
        <div>
            <label for="type" class="block font-bold mb-1">Discount Type</label>
            <select name="type" id="type" class="w-full p-2 border rounded">
                <option value="percentage" <?= $d['type'] == 'percentage' ? 'selected' : '' ?>>Percentage (%)</option>
                <option value="fixed" <?= $d['type'] == 'fixed' ? 'selected' : '' ?>>Fixed Amount (Rs. )</option>
            </select>
        </div>
        <div>
            <label for="value" class="block font-bold mb-1">Value</label>
            <input type="number" name="value" id="value" value="<?= htmlspecialchars($d['value']) ?>" step="0.01" class="w-full p-2 border rounded" required>
        </div>
    </div>
    <div class="grid grid-cols-2 gap-6">
        <div>
            <label for="start_date" class="block font-bold mb-1">Start Date (Optional)</label>
            <input type="date" name="start_date" id="start_date" value="<?= htmlspecialchars($d['start_date']) ?>" class="w-full p-2 border rounded">
        </div>
        <div>
            <label for="end_date" class="block font-bold mb-1">End Date (Optional)</label>
            <input type="date" name="end_date" id="end_date" value="<?= htmlspecialchars($d['end_date']) ?>" class="w-full p-2 border rounded">
        </div>
    </div>
    <div>
        <label for="usage_limit" class="block font-bold mb-1">Usage Limit (Optional)</label>
        <input type="number" name="usage_limit" id="usage_limit" value="<?= htmlspecialchars($d['usage_limit']) ?>" class="w-full p-2 border rounded">
        <p class="text-xs text-gray-500 mt-1">Leave blank for unlimited uses.</p>
    </div>
    <div>
        <label for="is_active" class="block font-bold mb-1">Status</label>
        <select name="is_active" id="is_active" class="w-full p-2 border rounded">
            <option value="1" <?= $d['is_active'] ? 'selected' : '' ?>>Active</option>
            <option value="0" <?= !$d['is_active'] ? 'selected' : '' ?>>Inactive</option>
        </select>
    </div>
    <div class="flex justify-end space-x-4">
        <a href="discounts.php" class="bg-gray-200 text-gray-800 px-6 py-2 rounded">Cancel</a>
        <button type="submit" class="bg-indigo-600 text-white px-6 py-2 rounded">Save Code</button>
    </div>
</form>
<?php $conn->close(); require_once 'includes/admin_footer.php'; ?>