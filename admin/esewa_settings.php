<?php
require_once 'includes/admin_header.php';
require_once '../includes/db_connect.php';

// Fetch current settings
$settings_result = $conn->query("SELECT * FROM site_settings WHERE setting_key IN ('esewa_id', 'esewa_qr')");
$esewa = [];
while ($row = $settings_result->fetch_assoc()) {
    $esewa[$row['setting_key']] = $row['setting_value'];
}
?>

<h1 class="text-3xl font-black text-gray-800 mb-8 uppercase italic">eSewa Configuration</h1>

<div class="max-w-2xl bg-white rounded-3xl shadow-sm border border-gray-100 overflow-hidden">
    <form action="actions/handle_settings.php" method="post" enctype="multipart/form-data" class="p-8 space-y-8">
        <!-- Hidden source tracker -->
        <input type="hidden" name="source_page" value="esewa_settings">

        <!-- eSewa Phone Number -->
        <div>
            <label class="block text-[10px] font-black uppercase text-gray-400 tracking-widest mb-2 ml-1">Merchant eSewa ID (Phone Number)</label>
            <input type="text" name="esewa_id" value="<?= htmlspecialchars($esewa['esewa_id'] ?? '') ?>" 
                   class="w-full bg-gray-50 border-2 border-gray-100 rounded-2xl py-4 px-5 font-bold text-gray-700 focus:border-indigo-500 outline-none transition-all" required>
        </div>

        <!-- eSewa QR Code Upload -->
        <div>
            <label class="block text-[10px] font-black uppercase text-gray-400 tracking-widest mb-2 ml-1">Payment QR Code</label>
            <div class="flex flex-col sm:flex-row items-center gap-6 p-4 bg-gray-50 rounded-2xl border-2 border-dashed border-gray-200">
                <div class="w-32 h-32 bg-white rounded-xl overflow-hidden shadow-sm border border-gray-100 flex-shrink-0">
                    <img src="../<?= htmlspecialchars($esewa['esewa_qr'] ?? 'assets/images/placeholder_qr.png') ?>" class="w-full h-full object-contain p-2" id="qr-preview">
                </div>
                <div class="space-y-2 text-center sm:text-left">
                    <input type="file" name="esewa_qr_file" class="text-xs text-gray-500 file:mr-4 file:py-2 file:px-4 file:rounded-full file:border-0 file:bg-indigo-50 file:text-indigo-700">
                    <p class="text-[10px] text-gray-400 uppercase font-bold tracking-tighter">Recommended: Square PNG image</p>
                </div>
            </div>
        </div>

        <!-- Save Button -->
        <button type="submit" class="w-full bg-gray-900 text-white py-4 rounded-2xl font-black text-xs uppercase tracking-widest hover:bg-indigo-600 transition-all shadow-xl active:scale-95">
            Update Payment Details
        </button>
    </form>
</div>

<?php
$conn->close();
require_once 'includes/admin_footer.php';
?>