<?php
require_once 'includes/admin_header.php';
require_once '../includes/db_connect.php';

$result = $conn->query("SELECT id, name, email, role, created_at FROM users ORDER BY created_at DESC");
?>
<h1 class="text-3xl font-bold text-gray-800 mb-8">Manage Users</h1>
<div class="bg-white shadow-md rounded-lg overflow-x-auto">
    <table class="min-w-full">
        <thead class="bg-gray-200">
            <tr>
                <th class="py-3 px-6 text-left">User ID</th>
                <th class="py-3 px-6 text-left">Name</th>
                <th class="py-3 px-6 text-left">Email</th>
                <th class="py-3 px-6 text-center">Role</th>
                <th class="py-3 px-6 text-left">Date Registered</th>
            </tr>
        </thead>
        <tbody class="divide-y">
            <?php while ($user = $result->fetch_assoc()): ?>
            <tr>
                <td class="py-4 px-6">#<?= $user['id'] ?></td>
                <td class="py-4 px-6"><?= htmlspecialchars($user['name']) ?></td>
                <td class="py-4 px-6"><?= htmlspecialchars($user['email']) ?></td>
                <td class="py-4 px-6 text-center">
                    <span class="px-3 py-1 text-sm font-semibold rounded-full <?= $user['role'] == 'admin' ? 'bg-red-200 text-red-800' : 'bg-green-200 text-green-800' ?>">
                        <?= ucfirst($user['role']) ?>
                    </span>
                </td>
                <td class="py-4 px-6"><?= date_format(date_create($user['created_at']), 'F j, Y') ?></td>
            </tr>
            <?php endwhile; ?>
        </tbody>
    </table>
</div>

<?php
$conn->close();
require_once 'includes/admin_footer.php';
?>